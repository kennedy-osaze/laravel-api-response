<?php

namespace KennedyOsaze\LaravelApiResponse\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use KennedyOsaze\LaravelApiResponse\LaravelApiResponseServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
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
        $app->config->set('database.default', 'testing');

        $app->config->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
