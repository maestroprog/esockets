<?php

namespace Esockets\base;

/**
 * Событие, на которое можно подписаться.
 */
final class Event
{
    private $uid = 0;
    /**
     * @var CallbackEventListener[]
     */
    private $listeners = [];

    /**
     * Создаёт нового слушателя на основе callback функции, и возвращает его.
     * По-умолчанию все слушатели находятся в подписанном состоянии.
     *
     * @param callable $callback
     * @return CallbackEventListener
     */
    public function attachCallbackListener(callable $callback): CallbackEventListener
    {
        $uid = $this->uid++;
        return $this->listeners[$uid] = CallbackEventListener::create($uid, $callback, [$this, 'detachListener']);
    }

    public function detachListener(int $uid)
    {
        if (isset($this->listeners[$uid])) {
            unset($this->listeners);
        }
    }

    /**
     * Чистит контейнер, удаляя все события.
     *
     * @param bool $notSubscribedOnly
     * @return void
     */
    public function clearListeners(bool $notSubscribedOnly = false)
    {
        if ($notSubscribedOnly) {
            $this->clearNotSubscribedListeners();
        } else {
            $this->listeners = [];
        }
    }

    /**
     * Очистит контейнер ото всех неподписанных слушателей.
     */
    private function clearNotSubscribedListeners()
    {
        foreach ($this->listeners as $key => $listener) {
            if (!$listener->isSubscribed()) {
                unset($this->listeners[$key]);
            }
        }
    }

    /**
     * Генерирует наступление события, и оповещает об этом всех слушателей.
     *
     * @param array ...$arguments
     */
    public function call(...$arguments)
    {
        array_walk($this->listeners, function ($eventListener) use ($arguments) {
            /**
             * @var $eventListener CallbackEventListener
             */
            $eventListener->call($arguments);
        });
    }
}
