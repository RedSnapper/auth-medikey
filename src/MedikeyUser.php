<?php

namespace RedSnapper\Medikey;

use Illuminate\Support\Arr;

class MedikeyUser
{
    private array $data;

    /**
     * MedikeyUser constructor.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getId(): ?string
    {
        return Arr::get($this->data, 'utente_id');
    }

    public function getFirstName(): ?string
    {
        return Arr::get($this->data, 'nome');
    }

    public function getLastName(): ?string
    {
        return Arr::get($this->data, 'cognome');
    }

    public function getName(): ?string
    {
        return $this->getFirstName()." ".$this->getLastName();
    }

    public function getSpecialties(): array
    {
        return Arr::get($this->data, 'specializzazione.specialita', []);
    }

    /**
     * Get the raw user array.
     *
     * @return array
     */
    public function getRaw(): array
    {
        return $this->data;
    }
}