<?php

namespace RedSnapper\Medikey\Exceptions;

class MissingTicketInResponseException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Ticket was not found in the response when requesting a ticket from Medikey');
    }
}