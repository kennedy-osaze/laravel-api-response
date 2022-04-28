<?php

namespace KennedyOsaze\LaravelApiResponse\Tests\Fakes;

use Illuminate\Http\Resources\Json\JsonResource;

class DummyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
