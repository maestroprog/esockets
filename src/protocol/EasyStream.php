<?php

namespace Esockets\protocol;

use Esockets\base\AbstractProtocol;
use Esockets\base\CallbackEventListener;
use Esockets\base\exception\ReadException;
use Esockets\base\exception\SendException;
use Esockets\base\IoAwareInterface;
use Esockets\base\PingPacket;
use Esockets\base\PingSupportInterface;

/**
 * Протокол потоковой передачи данных любого типа.
 * В задачи данного протокола входит:
 *  - сериализация (json-изация) отправляемых структур данных
 *  - десериализация полученных на другой стороне данных.
 * Структура пакета данных протокола EasyStream:
 * FSdata* - короткий пакет, у него 1 байт на флаги, 1 байт на длину данных,
 * FSSSSdata* - обычный пакет, 1 байт на флаги, по 4 байта на длину данных и номер пакета
 */
final class EasyStream extends AbstractProtocol implements PingSupportInterface
{
    const SHORT_PACKET = 0x01; // короткий пакет
    const DATA_INT = 0x02; // целочисленный тип
    const DATA_FLOAT = 0x04; // вещественное число
    const DATA_STRING = 0x08; // строка
    const DATA_ARRAY = 0x10; // массив (кодируется в json)
    const DATA_OBJECT = 0x20; // объект (сериализуется)
//    const DATA_PING_PONG = 0x40; // reserved
//    const DATA_CONTROL = 0x80; // reserved

    const SHORT_HEADER_SIZE = 2; // размер короткого заголовка EasyStream протокола
    const SHORT_PACKET_SIZE = 256; // размер полезных данных короткого пакета
    const SHORT_PACKET_SIZE_WITH_HEADER
        = self::SHORT_PACKET_SIZE + self::SHORT_HEADER_SIZE; // полный размер короткого пакета
    const HEADER_SIZE = 5; // размер обычного заголовка EasyStream протокола
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
     * @inheritDoc
     */
    public function __construct(IoAwareInterface $provider)
    {
        parent::__construct($provider);
        if (
            $provider->getMaxPacketSizeForWriting() > 0
            && $provider->getMaxPacketSizeForWriting() < self::SHORT_PACKET_SIZE_WITH_HEADER
        ) {
            throw new \LogicException(
                'This "' . get_class($provider)
                . '" I/O provider does not allow the transfer of packets of the required size of '
                . self::SHORT_PACKET_SIZE_WITH_HEADER . ' bytes.'
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function returnRead()
    {
        $result = null;
        $readAttempt = false;

//        var_dump($this->provider->getReadBufferSize());
        do {
            // пытаемся прочитать данные

            $readData = $this->provider->read($this->provider->getReadBufferSize(), false);
//        Log::dumpHexString($readData);
            if (!is_null($readData)) {
                // буферизация прочитанных пакетов
                $this->readBuffer .= $readData;
            } elseif ($readAttempt) {
                // чтобы избежать зацикливания, выходим, если уже была попытка чтения,
//                break;
            } else {
                $readAttempt = true; // TODO ВНИМАНИЕ
            }
            $bufferSize = strlen($this->readBuffer);
            if ($bufferSize > self::SHORT_HEADER_SIZE) {
                // если что-то можно прочитать в буфере
                // сначала прочитаем флаги нового поступившего пакета
                $flag = unpack('Cflag', substr($this->readBuffer, 0, 1))['flag'];
                if ($flag & self::SHORT_PACKET) {
                    // если пакет является коротким
                    $size = unpack(
                        'Csize',
                        substr($this->readBuffer, 1, self::SHORT_HEADER_SIZE - 1)
                    )['size'];
                    $headerSize = self::SHORT_HEADER_SIZE;
                } else {
                    // если пакет обычный
                    $size = unpack(
                        'Nsize',
                        substr($this->readBuffer, 1, self::HEADER_SIZE - 1)
                    )['size'];
                    $headerSize = self::HEADER_SIZE;
                }
                $size += 1; // добавляем 1, т.к. счёт размера данных начинается с 0.
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
//                        continue;
                    } else {
                        // иначе кидаем исключение
//                        break;
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

                // распакуем данные
                $result = $this->unpack($data, $flag);
                if (is_null($result)) {
                    // если не удалось распаковать - исключение
                    throw new SendException('Data packet is corrupted.');
                } elseif ($result instanceof PingPacket) {
                    if (!$result->isResponse()) {
                        $this->send(PingPacket::response($result->getValue()));
                    } else {
                        $this->pongReceived($result);
                    }
                    $result = null;
                }
            }
        } while (false); // цикл только для использования continue;
        return $result;
    }

    /**
     * Сброс буфера чтения.
     *
     * @param int $flushSize Размер данных для сброса
     * @return void
     */
    private function flushReadBuffer(int $flushSize)
    {
        $bufferSize = strlen($this->readBuffer);
        if ($flushSize === $bufferSize) {
            $this->readBuffer = '';
        } else {
            $this->readBuffer = substr(
                $this->readBuffer,
                $flushSize,
                $bufferSize - $flushSize
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
        if (!$this->provider->send($this->pack($data))) {
            return false;
        }
        return true;
    }

    /**
     * Пакует данные в пакеты, и возвращает массив пакетов,
     * которые можно отправлять в любом порядке.
     *
     * @param $data
     * @return string
     * @throws SendException
     */
    private function pack($data): string
    {
        $flag = $this->flagChoice(gettype($data));
        if ($flag & self::DATA_OBJECT) {
            $data = serialize($data);
        } elseif ($flag & self::DATA_ARRAY) {
            $data = json_encode($data);
        }
        $size = strlen($data);
        if ($size > self::PACKET_MAX_SIZE_WITH_HEADER) {
            throw new SendException('Large size of data to send! Please break it into your code.');
        }
        $format = 'CNa*';
        if ($size <= self::SHORT_PACKET_SIZE) {
            $format = 'CCa*';
            $flag |= self::SHORT_PACKET;
        }
        return pack($format, $flag, $size - 1, $data);
    }

    /**
     * Распаковывает принятые из сокета данные.
     * Возвращает null если данные не были распакованы по неизвестным причинам.
     *
     * @param string $raw
     * @param int $flag
     * @return mixed
     * @throws \InvalidArgumentException
     */
    private function unpack(string $raw, int $flag)
    {
        $data = null;
        if ($flag & self::DATA_STRING) {
            $data = $raw; // simple string
        } elseif ($flag & self::DATA_INT) {
            $data = (int)$raw;
        } elseif ($flag & self::DATA_FLOAT) {
            $data = (float)$raw;
        } elseif ($flag & self::DATA_ARRAY) {
            $data = json_decode($raw, JSON_OBJECT_AS_ARRAY);
        } elseif ($flag & self::DATA_OBJECT) {
            $data = unserialize($raw);
        } else {
            throw new \InvalidArgumentException('Invalid data type.');
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

    /**
     * @inheritDoc
     */
    public function ping(PingPacket $pingPacket)
    {
        $this->send($pingPacket);
    }

    private $pongCallback;

    /**
     * @inheritDoc
     */
    public function pong(callable $pongReceived)
    {
        $this->pongCallback = $pongReceived;
    }

    private function pongReceived(PingPacket $pong)
    {
        if (!is_null($this->pongCallback)) {
            call_user_func($this->pongCallback, $pong);
            $this->pongCallback = null;
        }
    }
}
