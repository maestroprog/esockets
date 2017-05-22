<?php

namespace Esockets\base;

final class CallbackEventsContainer
{
    /**
     * @var CallbackEvent[]
     */
    private $events = [];

    /**
     * Добавляет в контейнер новый callback для события.
     *
     * @param CallbackEvent $callbackEvent
     * @return CallbackEvent
     */
    public function addEvent(CallbackEvent $callbackEvent): CallbackEvent
    {
        return $this->events[] = $callbackEvent;
    }

    /**
     * Чистит контейнер, удаляя все события.
     *
     * @return void
     */
    public function clear()
    {
        $this->events = [];
    }

    /**
     * Очистит контейнер ото всех неподписанных callback-ов.
     */
    public function clearNotSubscribed()
    {
        foreach ($this->events as $key => $event) {
            if (!$event->isSubscribed()) {
                unset($this->events[$key]);
            }
        }
    }

    public function callEvents(...$arguments)
    {
        array_walk($this->events, function ($event) use ($arguments) {
            /**
             * @var $event CallbackEvent
             */
            $event->call($arguments);
        });
    }
}
