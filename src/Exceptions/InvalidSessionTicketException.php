<?php

namespace RedSnapper\Medikey\Exceptions;

class InvalidSessionTicketException extends \Exception
{
    public function __construct(mixed $ticket)
    {
        parent::__construct("Medikey ticket '$ticket' in session is invalid.");
    }
}