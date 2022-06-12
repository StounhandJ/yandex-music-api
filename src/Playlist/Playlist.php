<?php

namespace StounhandJ\YandexMusicApi\Playlist;

use stdClass;
use StounhandJ\YandexMusicApi\Client;
use StounhandJ\YandexMusicApi\JSONObject;

class Playlist extends JSONObject
{
    public array $owner;
    public string $playlistUuid;
    public bool $available;
    public int $uid;
    public int $kind;
    public string $title;
    public string $description;
    public string $descriptionFormatted;
    public int $revision;
    public int $snapshot;
    public int $trackCount;
    public string $visibility;
    public bool $collective;
    public string $created;
    public string $modified;
    public bool $isBanner;
    public bool $isPremiere;
    public int $durationMs;
    public array $cover;
    public string $ogImage;
    public array $tags;
    public int $likesCount;

    /**
     * @param Client $client
     * @param array|stdClass $json
     * @return Playlist[]
     */
    public static function deList(Client $client, array|stdClass $json): array
    {
        return array_map(fn($value): Playlist => new Playlist($client, $value), $json);
    }
}