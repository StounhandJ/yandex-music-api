<?php

namespace StounhandJ\YandexMusicApi\Models\Track;

use stdClass;
use StounhandJ\YandexMusicApi\Client;
use StounhandJ\YandexMusicApi\Models\JSONObject;

class TracksDownloadInfo extends JSONObject
{
    public string $codec;
    public bool $gain;
    public bool $preview;
    public string $downloadInfoUrl;
    public string $directLink; //TODO Добавить его получение из downloadInfoUrl
    public bool $direct;
    public int $bitrateInKbps;

    /**
     * @param Client $client
     * @param array|stdClass $json
     * @return TracksDownloadInfo[]
     */
    public static function deList(Client $client, array|stdClass $json): array
    {
        return array_map(fn($value): TracksDownloadInfo => new TracksDownloadInfo($client, $value), $json);
    }
}