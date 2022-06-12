<?php

namespace StounhandJ\YandexMusicApi\Models;

use stdClass;
use StounhandJ\YandexMusicApi\Client;

class Station extends JSONObject
{
    public array $station;
    public array $settings;
    public array $settings2;
    public array $adParams;
    public string $explanation;
    public string $rupTitle;
    public string $rupDescription;

    /**
     * @param Client $client
     * @param array|stdClass $json
     * @return Station[]
     */
    public static function deList(Client $client, array|stdClass $json): array
    {
        return array_map(fn($value): Station => new Station($client, $value), $json);
    }
}