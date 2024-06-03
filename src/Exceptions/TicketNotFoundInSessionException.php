<?php

namespace RedSnapper\Medikey\Exceptions;

class TicketNotFoundInSessionException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Medikey ticket was not found in the session');
    }
}