<?php

namespace RedSnapper\Medikey\Exceptions;

class InvalidTicketException extends \Exception
{
    public function __construct(mixed $ticket, int $siteId)
    {
        parent::__construct("Ticket '$ticket' returned from Medikey ticket request is invalid. Site id: $siteId");
    }
}