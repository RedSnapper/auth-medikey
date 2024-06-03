<?php

namespace RedSnapper\Medikey\Exceptions;

class TicketMismatchException extends \InvalidArgumentException
{
    public function __construct(string $sessionValue, string $valueFromMedikey)
    {
        parent::__construct("Ticket session value $sessionValue does not match value from Medikey $valueFromMedikey");
    }
}