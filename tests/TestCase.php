<?php

namespace Tests;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function skipUnlessFortifyHas(string $feature): void
    {
        $available = config('fortify.features', []);

        if (! in_array($feature, $available)) {
            $this->markTestSkipped("Fortify feature [{$feature}] is not enabled.");
        }
    }
}
