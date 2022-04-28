<?php

namespace KennedyOsaze\LaravelApiResponse\Tests\Fakes;

use Illuminate\Http\Resources\Json\ResourceCollection;

class DummyResourceCollection extends ResourceCollection
{
    private bool $useParent = true;

    public function toArray($request)
    {
        return $this->useParent ? parent::toArray($request) : [
            'data' => $this->collection,
            'link' => [
                'self' => 'link-value',
            ]
        ];
    }

    public function setWrapper(?string $wrap = null)
    {
        static::$wrap = $wrap;

        return $this;
    }

    public function useParentToArray(bool $use = true)
    {
        $this->useParent = $use;

        return $this;
    }
}
