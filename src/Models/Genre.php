<?php

namespace StounhandJ\YandexMusicApi\Models;

use stdClass;
use StounhandJ\YandexMusicApi\Client;

class Genre extends JSONObject
{
    public string $id;
    public int $weight;
    public bool $composerTop;
    public string $urlPart;
    public string $title;
    public array $titles;
    public string $color;
    public array $images;
    public bool $showInMenu;
    public array $radioIcon;
    protected array $subGenres;

     /**
     * @return Genre[]
     */
    public function getSubGenres(): array
    {
        return isset($this->subGenres) ? Genre::deList($this->client, $this->subGenres) : [];
    }

    /**
     * @param Client $client
     * @param array|stdClass $json
     * @return Genre[]
     */
    public static function deList(Client $client, array|stdClass $json): array
    {
        return array_map(fn($value): Genre => new Genre($client, $value), $json);
    }
}