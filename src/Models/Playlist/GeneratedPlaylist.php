<?php

namespace StounhandJ\YandexMusicApi\Models\Playlist;

use stdClass;
use StounhandJ\YandexMusicApi\Client;
use StounhandJ\YandexMusicApi\Models\JSONObject;

class GeneratedPlaylist extends JSONObject
{
    public string $type;
    public bool $ready;
    public bool $notify;
    protected array $data;

    /**
     * @return Playlist
     */
    public function getPlaylist(): Playlist
    {
        return new Playlist($this->client, $this->data);
    }

    /**
     * @param Client $client
     * @param array|stdClass $json
     * @return GeneratedPlaylist[]
     */
    public static function deList(Client $client, array|stdClass $json): array
    {
        return array_map(fn($value): GeneratedPlaylist => new GeneratedPlaylist($client, $value), $json);
    }
}