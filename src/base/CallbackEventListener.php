<?php

namespace Esockets\base;

/**
 * Подписчик на
 */
final class CallbackEventListener
{
    private $uid;
    private $callback;
    private $subscribed = true; // default is subscribed
    private $detach;

    public static function create(int $uid, callable $callback, callable $detach)
    {
        return new self($uid, $callback, $detach);
    }

    private function __construct(int $uid, callable $callback, callable $detach)
    {
        $this->uid = $uid;
        $this->callback = $callback;
        $this->detach = $detach;
    }

    /**
     * Запускает вызов callback обработчика события.
     *
     * @param array $arguments
     */
    public function call(array $arguments)
    {
        if ($this->subscribed) {
            call_user_func_array($this->callback, $arguments);
        }
    }

    /**
     * Включает подписку на событие: при возникновении события callback будет выполен.
     *
     * @return CallbackEventListener
     */
    public function subscribe(): self
    {
        $this->subscribed = true;
        return $this;
    }

    /**
     * Отключает подписку на событие: при возникновении события callback не будет выполен.
     *
     * @return CallbackEventListener
     */
    public function unSubscribe(): self
    {
        $this->subscribed = false;
        return $this;
    }

    /**
     * Возвращает статус подписки.
     *
     * @return bool
     */
    public function isSubscribed(): bool
    {
        return $this->subscribed;
    }

    /**
     * Отсоединяет слушатель от события.
     * Эту операцию отменить нельзя: слушатель навсегда отсоединяется.
     */
    public function detach()
    {
        call_user_func($this->detach, $this->uid);
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }
}
