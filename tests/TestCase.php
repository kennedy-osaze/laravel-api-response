<?php

namespace KennedyOsaze\LaravelApiResponse\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use KennedyOsaze\LaravelApiResponse\LaravelApiResponseServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Model::unguard();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelApiResponseServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }
}
