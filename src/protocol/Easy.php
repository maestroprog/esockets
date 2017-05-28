<?php

namespace Esockets\protocol;

use Esockets\base\CallbackEventListener;
use Esockets\base\ConnectorInterface;
use Esockets\base\exception\ReadException;
use Esockets\base\exception\SendException;
use Esockets\base\IoAwareInterface;
use Esockets\base\AbstractProtocol;
use Esockets\protocol\base\ReadPacketBuffer;
use Esockets\protocol\base\SendPacketBuffer;

/**
 * Протокол-обёртка над php-шными структурами данных,
 * передаваемых по сети.
 * В задачи данного протокола входит:
 *  - сериализация (json-изация) отправляемых структур данных
 *  - партицирование получившихся сериализованных данных
 *  - нумерация получившихся партиций, и их сборка после получения (нужно для UDP)
 *  - уведомления о доставке (нужно для UDP) todo
 *  - десериализация полученных на другой стороне данных.
 * Структура пакета данных протокола Easy:
 * FS####data* - короткий пакет, у него 1 байт на флаги, 1 байт на длину данных, и 4 байта на номер пакета,
 * FSSSS####data* - обычный пакет, 1 байта на флаги, по 4 байта на длину данных и номер пакета
 */
final class Easy extends AbstractProtocol
    //implements PingSupportInterface
{
    const SHORT_PACKET = 0x01; // короткий пакет
    const PACKET_END = 0x02; // запрос пакета
    const PACKET_PARTED = 0x04; // часть пакета
    const PACKET_REQUEST = self::PACKET_END | self::PACKET_PARTED;
    const DATA_INT = 0x08; // целочисленный тип
    const DATA_FLOAT = 0x10; // вещественное число
    const DATA_STRING = self::DATA_INT | self::DATA_FLOAT; // 0x18; // строка
    const DATA_ARRAY = 0x20; // массив (кодируется в json)
    const DATA_OBJECT = self::DATA_STRING | self::DATA_ARRAY; // объект (сериализуется)
//    const DATA_PING_PONG = 0x40; // reserved
    const DATA_CONTROL = 0x80; // reserved

    const SHORT_HEADER_SIZE = 6; // размер короткого заголовка Easy протокола
    const SHORT_PACKET_SIZE = 256; // размер полезных данных короткого пакета
    const SHORT_PACKET_SIZE_WITH_HEADER
        = self::SHORT_PACKET_SIZE + self::SHORT_HEADER_SIZE; // полный размер короткого пакета
    const HEADER_SIZE = 9; // размер обычного заголовка Easy протокола
    const PACKET_MAX_SIZE = 4294967296; // максимальный размер полезных данных пакета, теоретический (4GB)
    const PACKET_MAX_SIZE_WITH_HEADER
        = self::PACKET_MAX_SIZE + self::HEADER_SIZE; // полный размер обычного пакета

    /**
     * Буфер чтения.
     *
     * @var string
     */
    private $readBuffer = '';

    /**
     * ID последнего отправленного пакета.
     *
     * @var int
     */
    private $lastSendPacketId = 1;

    /**
     * Буфер не целых прочитанных пакетов.
     *
     * @var ReadPacketBuffer
     */
    private $readPackets;

    /**
     * Буфер отправленных пакетов.
     *
     * @var SendPacketBuffer
     */
    private $sendPackets;

    /**
     * @inheritDoc
     */
    public function __construct(IoAwareInterface $provider)
    {
        parent::__construct($provider);
        if ($provider->getMaxPacketSizeForWriting() > 0 && $provider->getMaxPacketSizeForWriting() < self::SHORT_PACKET_SIZE_WITH_HEADER) {
            throw new \LogicException(
                'This "' . get_class($provider)
                . '" I/O provider does not allow the transfer of packets of the required size of '
                . self::SHORT_PACKET_SIZE_WITH_HEADER . ' bytes.'
            );
        }
        $this->readPackets = new ReadPacketBuffer();
        $this->readPackets->onPacketRequest(function (int $packetId) {
            $this->provider->send($this->packPacket(
                self::DATA_INT | self::PACKET_REQUEST,
                0,
                $packetId
            ));
        });
        $this->sendPackets = new SendPacketBuffer();
        if ($provider instanceof ConnectorInterface) {
            $provider->onConnect(function () {
                $this->readPackets->reset();
                $this->sendPackets->reset();
                $this->lastSendPacketId = 1;
                $this->readBuffer = '';
            });
        }
    }

    /**
     * @inheritDoc
     */
    public function returnRead()
    {
        $result = null;
        $readAttempt = false;
        for (; ;) {
            // пытаемся прочитать данные
            $readData = $this->provider->read($this->provider->getReadBufferSize(), false);
//        Log::dumpHexString($readData);
            if (!is_null($readData)) {
                // буферизация прочитанных пакетов
                $this->readBuffer .= $readData;
            } elseif ($readAttempt) {
                // чтобы избежать зацикливания, выходим, если уже была неудачная попытка чтения,
                break;
            }
            $readAttempt = true;
            if (!is_null($nextPacket = $this->readPackets->getPacketIfExists())) {
                list($data, $flag) = $nextPacket;
                $result = $this->unpack($data, $flag);
                if (is_null($result)) {
                    // если не удалось распаковать - исключение
                    throw new SendException('Data packet is corrupted.');
                }
                // если успешно прочитали пакет из буфера - выходим из цикла
                break;
            }
            $bufferSize = strlen($this->readBuffer);
            if ($bufferSize > self::SHORT_HEADER_SIZE) {
                // если что-то можно прочитать в буфере
                // сначала прочитаем флаги нового поступившего пакета
                $flag = unpack('Cflag', substr($this->readBuffer, 0, 1))['flag'];
                if ($flag & self::SHORT_PACKET) {
                    // если пакет является коротким
                    list($size, $packetId) = array_values(unpack(
                        'Csize/Nnumber',
                        substr($this->readBuffer, 1, 5)
                    ));
                    $headerSize = self::SHORT_HEADER_SIZE;
                } else {
                    // если пакет обычный
                    list($size, $packetId) = array_values(unpack(
                        'Nsize/Nnumber',
                        substr($this->readBuffer, 1, 8)
                    ));
                    $headerSize = self::HEADER_SIZE;
                }
                $size += 1;//добавляем 1, т.к. счёт размера данных начинается с 0.
                $fullPacketSize = $headerSize + $size;
                if ($bufferSize < $fullPacketSize) {
                    // если размер буфера меньше чем кол-во данных, которые необходимо прочитать
                    $needRead = $fullPacketSize - $bufferSize; // сколько данных нужно прочитать
                    // попробуем дочитать необходимое кол-во данных
                    $appendBuffer = $this->provider->read($needRead, true);
                    $this->readBuffer .= $appendBuffer;
                    if (strlen($appendBuffer) === $needRead) {
                        // если необходимое количество данных прочитано успешно
                        // возвращаемся к началу цикла и перечитываем буфер
                        continue;
                    } else {
                        // иначе кидаем исключение
                        break;
                        throw new ReadException(
                            sprintf('Not enough length: %d bytes', $needRead - strlen($appendBuffer)),
                            ReadException::ERROR_FAIL
                        );
                    }
                }
                // прочитаем данные пакета из буфера чтения с отступом в $headerSize байт
                $data = substr($this->readBuffer, $headerSize, $size);

                // сбросим буфер чтения
                $this->flushReadBuffer($fullPacketSize);

                if (($flag & self::PACKET_REQUEST) > 0 && $packetId === 0) {
                    // если пакет - запрос другого пакета
                    var_dump('packet requested');
                    if (isset($this->sendPackets[(int)$data])) {
                        // если пакет есть в буфере отправки
                        $this->provider->send($this->sendPackets[(int)$data]);
                        var_dump('packet sended');
//                        unset($this->sendPackets[$packetId]);
                    }
                    // продолжаем чтение
                    continue;
                } else {
                    // добавим пакет в буфер пакетов
                    $addedToBuffer = $this->readPackets->addPacket(
                        $packetId,
                        ($flag & self::PACKET_PARTED) > 0
                        || ($flag & self::PACKET_END) > 0,
                        ($flag & self::PACKET_END) > 0,
                        $data,
                        $flag
                    );
                    if (!$addedToBuffer) {
                        // распакуем данные
                        $result = $this->unpack($data, $flag);
                        if (is_null($result)) {
                            // если не удалось распаковать - исключение
                            throw new SendException('Data packet is corrupted.');
                        }
                    } else {
                        continue;
                    }
                }
            }
            break;
        }
        return $result;
    }

    /**
     * Сброс буфера чтения.
     *
     * @param int $readPacketSize размер прочитанного пакета
     * @return void
     */
    private function flushReadBuffer(int $readPacketSize)
    {
        $bufferSize = strlen($this->readBuffer);
        if ($readPacketSize === $bufferSize) {
            $this->readBuffer = '';
        } else {
            $this->readBuffer = substr(
                $this->readBuffer,
                $readPacketSize,
                $bufferSize - $readPacketSize
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function onReceive(callable $callback): CallbackEventListener
    {
        return $this->eventReceive->attachCallbackListener($callback);
    }

    /**
     * @inheritdoc
     */
    public function send($data): bool
    {
        foreach ($this->pack($data) as $packetId => $packet) {
            $this->sendPackets[$packetId] = $packet;
//            Log::dumpHexString($packet);
            if (!$this->provider->send($packet)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Пакует данные в пакеты, и возвращает массив пакетов,
     * которые можно отправлять в любом порядке.
     *
     * @param $data
     * @return array
     * @throws SendException
     */
    private function pack(&$data): array
    {
        $flag = $this->flagChoice(gettype($data));
        if (($flag & self::DATA_OBJECT) === self::DATA_OBJECT) {
            $data = serialize($data);
        } elseif (($flag & self::DATA_ARRAY) === self::DATA_ARRAY) {
            $data = json_encode($data);
        }
        $size = strlen($data);
        if ($size > self::PACKET_MAX_SIZE_WITH_HEADER) {
            throw new SendException('Large size of data to send! Please break it into your code.');
        }
        $packets = [];
        $maxPacketSize = $this->provider->getMaxPacketSizeForWriting();
        if ($size < self::SHORT_PACKET_SIZE && self::SHORT_PACKET_SIZE_WITH_HEADER < $maxPacketSize) {
            // если отправляется короткий пакет
            $currentPacketId = $this->lastSendPacketId++;
            $packets[$currentPacketId] = $this->packPacket(
                $flag,
                $currentPacketId,
                $data
            );
        } elseif ($maxPacketSize > 0 && $size + self::HEADER_SIZE > $maxPacketSize) {
            // если пакет отправляется по частям
            $data = str_split($data, $maxPacketSize - self::HEADER_SIZE);
            $i = 0;
            foreach ($data as $part) {
                $currentPacketId = $this->lastSendPacketId++;
                $packets[$currentPacketId] = $this->packPacket(
                    $flag | (++$i === count($data) ? self::PACKET_END : self::PACKET_PARTED),
                    $currentPacketId,
                    $part
                );
            }
        } else {
            // если размер данных умещается в обычный пакет
            $currentPacketId = $this->lastSendPacketId++;
            $packets[$currentPacketId] = $this->packPacket(
                $flag,
                $currentPacketId,
                $data
            );
        }
        return $packets;
    }

    /**
     * Формирует пакет.
     *
     * @param int $flag
     * @param int $packetId
     * @param $data
     * @return string
     */
    private function packPacket(int $flag, int $packetId, &$data): string
    {
        $format = 'CNNa*';
        $size = strlen($data);
        if ($size <= self::SHORT_PACKET_SIZE) {
            $format = 'CCNa*';
            $flag |= self::SHORT_PACKET;
        }
        return pack($format, $flag, $size - 1, $packetId, $data);
    }

    /**
     * Распаковывает принятые из сокета данные.
     * Возвращает null если данные не были распакованы по неизвестным причинам.
     *
     * @param string $raw
     * @param int $flag
     * @return mixed
     */
    private function unpack(string $raw, int $flag)
    {
        $data = null;
        if (($flag & self::DATA_OBJECT) === self::DATA_OBJECT) {
            $data = unserialize($raw);
        } elseif (($flag & self::DATA_ARRAY) === self::DATA_ARRAY) {
            $data = json_decode($raw, JSON_OBJECT_AS_ARRAY);
        } elseif (($flag & self::DATA_STRING) === self::DATA_STRING) {
            $data = $raw; // simple string
        } elseif (($flag & self::DATA_INT) === self::DATA_INT) {
            $data = (int)$raw;
        } elseif (($flag & self::DATA_FLOAT) === self::DATA_FLOAT) {
            $data = (float)$raw;
        } else {
            ;
        }
        return $data;
    }

    /**
     * Выбирает флаг соотетствующий типу данных.
     *
     * @param string $dataType
     * @return int
     * @throws SendException
     */
    private function flagChoice(string $dataType): int
    {
        switch ($dataType) {
            case 'boolean':
                throw new SendException('Boolean data type cannot be transmitted.');
            case 'integer':
                $flag = self::DATA_INT;
                break;
            case 'double':
                $flag = self::DATA_FLOAT;
                break;
            case 'array':
                $flag = self::DATA_ARRAY;
                break;
            case 'object':
                /*if ($data instanceof PingPacket) {
                    $flag = self::DATA_INT | self::DATA_PING_PONG;
                    if (!$data->isResponse()) {
                        $flag |= self::DATA_CONTROL;
                    }
                    $data = $data->getValue();
                } else {*/
                $flag = self::DATA_OBJECT;
//                throw new SendException('Values of type Object cannot be transmitted on current Net version.');
                /*}*/
                break;
            case 'resource':
                throw new SendException('Values of type Resource cannot be transmitted on current Net version.');
            case 'NULL':
                throw new SendException('Null data type cannot be transmitted.');
            case 'unknown type':
                throw new SendException('Values of Unknown type cannot be transmitted on current Net version.');
            default:
                $flag = self::DATA_STRING;
        }
        return $flag;
    }
}
