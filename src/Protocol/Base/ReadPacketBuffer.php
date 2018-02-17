<?php

namespace Esockets\Protocol\Base;

use Esockets\Base\CallbackEventListener;
use Esockets\Base\Event;
use Esockets\Base\Exception\ReadException;

/**
 * Пакетный буфер чтения.
 * Решает 2 проблемы:
 *  - чтение пакетов по порядку,
 *  - слежение за потерянными пакетами.
 */
class ReadPacketBuffer implements PacketBufferInterface
{
    const READ_TIMEOUT = 10; // 5 seconds

    private $lastCompletedId = 0;
    private $buffer = [];
    private $meta = [];

    private $eventRequestPacket;

    public function __construct()
    {
        $this->eventRequestPacket = new Event();
    }

    /**
     * @inheritDoc
     */
    public function reset()
    {
        $this->lastCompletedId = 0;
        $this->buffer = [];
        $this->meta = [];
    }

    /**
     * Назначет обработчик события, когда нужно запросить пакет.
     *
     * @param callable $callback
     *
     * @return CallbackEventListener
     */
    public function onPacketRequest(callable $callback): CallbackEventListener
    {
        return $this->eventRequestPacket->attachCallbackListener($callback);
    }

    /**
     * Добавляет прочитанный пакет в буфер.
     * Вернёт true, если пакет был добавлен в буфер,
     * и false, если пакет готов к чтению и не был добавле в буфер.
     *
     * @param int $id
     * @param bool $isPartedPacket
     * @param mixed $data
     * @param mixed $meta дополнительная meta информация к пакету
     *
     * @return bool
     */
    public function addPacket(int $id, bool $isPartedPacket, bool $isEndPart, $data, $meta = null): bool
    {
//        echo 'received  ', $id, PHP_EOL;
        if ($isPartedPacket || $id > $this->lastCompletedId + 1) {
            // частичные пакеты в любом случае добавляем в буфер чтения
            // или чтение нескольких пакетов пропущено
            // заполняем пропущенные пакеты флажками
            for ($i = $this->lastCompletedId + 1; $i < $id; $i++) {
                if (!isset($this->buffer[$i])) {
                    $this->buffer[$i] = false;
                    $this->meta[$i] = [
                        'time' => time(),
                        'part' => $isPartedPacket,
                        'end' => $isPartedPacket && $isEndPart,
                        'meta' => null
                    ];
                }
            }
            // записываем в буфер текущий пакет
            $this->buffer[$id] = $data;
            $this->meta[$id] = [
                'time' => time(),
                'part' => $isPartedPacket,
                'end' => $isPartedPacket && $isEndPart,
                'meta' => $meta
            ];
            $result = true;
        } elseif ($id === $this->lastCompletedId + 1) {
            // если чтение пакетов идёт по порядку, то все ок
            $this->lastCompletedId++;
            $result = false;
        } else {
            // duplicate of packet
            ; // nothing
            $result = true;
        }
        return $result;
    }

    /**
     * Читает пакет из буфера.
     * Метод вернёт null если читать нечего.
     * Вернёт массив с данными пакета и его мета информацией:
     *  ['data', 'meta']
     *
     * @return array|null
     * @throws \Exception
     */
    public function getPacketIfExists()
    {
        $result = null;
        $ne = 0;
        // проверка пакетов с тайм-аутом
        foreach ($this->meta as $packetId => &$data) {
            if ($this->buffer[$packetId] !== false) {
                continue;
            }
            $ne++;
            if ($data['time'] < time() - self::READ_TIMEOUT) {
                // устанавливаем количество попыток
                if (isset($data['try'])) {
                    if ($data['try'] > 10) {
                        // если за 10 попыток не удалось получить пакет
                        // помечаем пакет как удалённый
                        $data['deleted'] = true;
                        throw new ReadException(
                            'Could not receive data packet with id ' . $packetId,
                            ReadException::ERROR_PROTOCOL
                        );
                    }
                    $data['try']++;
                } else {
                    $data['try'] = 1;
                }

                // дёргаем обработчик для отправки запроса пакета
                $this->eventRequestPacket->call($packetId);
                usleep(1000);

                // обновляем время последнего запроса пакета
                $data['time'] = time();
            } else {
                break;
            }
            unset($data);
        }
        if ($ne > 0) {

//            echo 'not enough', $ne, PHP_EOL;
        }

        $nextId = $this->lastCompletedId + 1;
        if (array_key_exists($nextId, $this->buffer) && $this->buffer[$nextId] !== false) {
            // если можно прочитать следующий по порядку пакет
            if ($this->meta[$nextId]['part']) {
                // если пакет является частью
                // пройдёмся по буферу и выясним, есть ли все части идущие за этим пакетом
                for ($i = $nextId + 1; array_key_exists($i, $this->buffer); $i++) {
                    if (
                        $this->buffer[$i] === false
                    ) {
                        // если пакет на текущей итерации ещё не получен,
                        // то дальнейшие поиски бессмысленны, выходим из цикла
                        break;
                    } elseif ($this->meta[$i]['part'] && $this->meta[$i]['end']) {
                        // если наткнулись на последний частичный пакет, значит до этого момента пакеты были частями
                        // собереём их по порядку, и выйдем из цикла вернув результат - собранный пакет
                        $packet = '';
                        $meta = $this->meta[$nextId]['meta'];
                        for ($j = $nextId; $j <= $i; $j++) {
                            $packet .= $this->buffer[$j];
                            unset($this->buffer[$j]);
                            unset($this->meta[$j]);
                        }
                        // установим id успешно прочитанного последнего пакета-части
                        $this->lastCompletedId = $i;
                        $result = [$packet, $meta];
                        break;
                    } else {
                    }
                }
            } else {
                // если пакет нормальный и готов к чтению
                $result = [$this->buffer[$nextId], $this->meta[$nextId]['meta']];
                unset($this->buffer[$nextId]);
                unset($this->meta[$nextId]);
                $this->lastCompletedId = $nextId;
            }
        }
        return $result;
    }
}
