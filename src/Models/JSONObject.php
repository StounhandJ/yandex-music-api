<?php

namespace StounhandJ\YandexMusicApi\Models;

use StounhandJ\YandexMusicApi\Client;

class JSONObject
{
    public function __construct(
        protected Client $client,
        $json = false
    ) {
        if ($json) {
            if (is_string($json)) {
                $json = json_decode($json, true);
            }
            $this->set($json);
        }
    }

    protected function set($data): void
    {
        foreach ($data as $key => $value) {
            if ($value instanceof \stdClass) {
                $value = json_decode(json_encode($value), true);
            }
            $this->{$key} = $value;
        }
    }
}