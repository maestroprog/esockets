<?php

namespace Esockets\base;

final class CallbackEvent
{
    private $callback;
    private $subscribed = false;

    public static function create(callable $callback)
    {
        return new self($callback);
    }

    private function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Запускает вызов callback события.
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
     * @return CallbackEvent
     */
    public function subscribe(): self
    {
        $this->subscribed = true;
        return $this;
    }

    /**
     * Отключает подписку на событие: при возникновении события callback будет выполен.
     *
     * @return CallbackEvent
     */
    public function unSubscribe(): self
    {
        $this->subscribed = false;
        return $this;
    }

    public function isSubscribed(): bool
    {
        return $this->subscribed;
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }
}
