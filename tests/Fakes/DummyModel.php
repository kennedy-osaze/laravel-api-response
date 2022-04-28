<?php

namespace KennedyOsaze\LaravelApiResponse\Tests\Fakes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DummyModel extends Model
{
    public static function migrate()
    {
        Schema::create('dummy_models', function (Blueprint $blueprint) {
            $blueprint->increments('id');
            $blueprint->string('name');
            $blueprint->timestamps();
        });
    }
}
