<?php

namespace StounhandJ\YandexMusicApi\Models;

use stdClass;
use StounhandJ\YandexMusicApi\Client;
use StounhandJ\YandexMusicApi\Models\Artist\Artist;
use StounhandJ\YandexMusicApi\Models\Artist\Label;

class Album extends JSONObject
{
    public int $id;
    public string $title;
    public string $type;
    public string $metaType;
    public int $year;
    public string $releaseDate;
    public string $coverUri;
    public string $ogImage;
    public string $genre;
    public array $buy;
    public int $trackCount;
    public int $likesCount;
    public bool $recent;
    public bool $veryImportant;
    protected array $artists;
    protected array $labels;
    public bool $available;
    public bool $availableForPremiumUsers;
    public bool $availableForMobile;
    public bool $availablePartially;
    public array $bests;

    /**
     * @return Artist[]
     */
    public function getArtists(): array
    {
        return Artist::deList($this->client, $this->artists);
    }

    /**
     * @return \StounhandJ\YandexMusicApi\Models\Artist\Label[]
     */
    public function getLabels(): array
    {
        return Label::deList($this->client, $this->artists);
    }

    /**
     * @param Client $client
     * @param array|stdClass $json
     * @return Album[]
     */
    public static function deList(Client $client, array|stdClass $json): array
    {
        return array_map(fn($value): Album => new Album($client, $value), $json);
    }
}