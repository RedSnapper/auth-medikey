<?php

namespace RedSnapper\Medikey\Tests;

use RedSnapper\Medikey\MedikeyServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
          MedikeyServiceProvider::class,
        ];
    }
}