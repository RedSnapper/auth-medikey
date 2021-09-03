<?php
namespace RedSnapper\Medikey\Exceptions;

use Exception;

class MedikeyException extends Exception
{
    private int $error_id;

    /**
     * MedikeyException constructor.
     */
    public function __construct(string $message, int $error_id)
    {
        parent::__construct($message);
        $this->error_id = $error_id;
    }

    public function getErrorId(): int
    {
        return $this->error_id;
    }

}