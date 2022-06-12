<?php

namespace StounhandJ\YandexMusicApi\Artist;

use stdClass;
use StounhandJ\YandexMusicApi\Client;
use StounhandJ\YandexMusicApi\JSONObject;

class Label extends JSONObject
{
    public int $id;
    public string $name;

    /**
     * @param Client $client
     * @param array|stdClass $json
     * @return Label[]
     */
    public static function deList(Client $client, array|stdClass $json): array
    {
        return array_map(fn($value): Label => new Label($client, $value), $json);
    }
}