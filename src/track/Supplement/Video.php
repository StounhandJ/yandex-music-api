<?php

namespace StounhandJ\YandexMusicApi\Track\Supplement;

use stdClass;
use StounhandJ\YandexMusicApi\Client;
use StounhandJ\YandexMusicApi\JSONObject;

class Video extends JSONObject
{
    public string $title;
    public string $cover;
    public string $embedUrl;
    public string $provider;
    public string $providerVideoId;

    /**
     * @param Client $client
     * @param array|stdClass $json
     * @return Video[]
     */
    public static function deList(Client $client, array|stdClass $json): array
    {
        return array_map(fn($value): Video => new Video($client, $value), $json);
    }
}