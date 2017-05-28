<?php

namespace Esockets\socket;

trait SocketTrait
{
    /**
     * @var int type of socket
     */
    protected $socketDomain;

    /**
     * Вернёт true, если сокет создан на основе unix домена.
     *
     * @return bool
     */
    protected function isUnixAddress(): bool
    {
        return $this->socketDomain === AF_UNIX;
    }

    /**
     * Вернёт true, если сокет создан на основе обычного сетевого взаимодействия.
     *
     * @return bool
     */
    protected function isIpAddress(): bool
    {
        return $this->socketDomain === AF_INET || $this->socketDomain === AF_INET6;
    }
}
