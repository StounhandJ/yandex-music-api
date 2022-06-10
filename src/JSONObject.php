<?php

namespace StounhandJ\YandexMusicApi;

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
            $this->{$key} = $value;
        }
    }
}