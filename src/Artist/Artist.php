<?php

namespace StounhandJ\YandexMusicApi\Artist;

use stdClass;
use StounhandJ\YandexMusicApi\Client;
use StounhandJ\YandexMusicApi\JSONObject;

class Artist extends JSONObject
{
    public string $id;
    public string $name;
    public bool $various;
    public bool $composer;
    public array $cover;
    public string $ogImage;
    public array $genres;
    public array $counts;
    public bool $available;
    public array $ratings;
    public array $links;
    public bool $ticketsAvailable;

    /**
     * @param Client $client
     * @param array|stdClass $json
     * @return Artist[]
     */
    public static function deList(Client $client, array|stdClass $json): array
    {
        return array_map(fn($value): Artist => new Artist($client, $value), $json);
    }
}