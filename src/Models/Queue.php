<?php

namespace StounhandJ\YandexMusicApi\Models;

use stdClass;
use StounhandJ\YandexMusicApi\Client;
use StounhandJ\YandexMusicApi\Exception\YandexMusicException;
use StounhandJ\YandexMusicApi\Models\Track\Track;

class Queue extends JSONObject
{
    public string $id;
    public string $modified;
    public array $context;
    protected array $tracks;
    protected int $currentIndex;

    /**
     * @param bool $force
     * @return Track[]
     * @throws YandexMusicException
     */
    public function getTracks(bool $force = false): array
    {
        if ($force || !isset($this->tracks)) {
            $queue = $this->client->queue($this->id);
            $this->tracks = $queue->getTracks();
        }

        if (count($this->tracks) > 0 && !($this->tracks[0] instanceof Track)) {
            $this->tracks = Track::deList($this->client, $this->tracks);
        }

        return $this->tracks;
    }

    /**
     * @param Client $client
     * @param array|stdClass $json
     * @return Queue[]
     */
    public static function deList(Client $client, array|stdClass $json): array
    {
        return array_map(fn($value): Queue => new Queue($client, $value), $json);
    }

    /**
     * @param bool $force
     * @return int|array
     * @throws YandexMusicException
     */
    public function getCurrentIndex(bool $force = false): int|array
    {
        if ($force || !isset($this->currentIndex)) {
            $queue = $this->client->queue($this->id);
            $this->currentIndex = $queue->getCurrentIndex();
        }

        return $this->currentIndex;
    }
}