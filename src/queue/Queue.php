<?php

namespace StounhandJ\YandexMusicApi\Queue;

use stdClass;
use StounhandJ\YandexMusicApi\Client;
use StounhandJ\YandexMusicApi\JSONObject;

class Queue extends JSONObject
{
    public string $id;
    public string $modified;
    public array $context;
    protected array $tracks;
    protected int $currentIndex;

    /**
     * @param bool $force
     * @return array
     */
    public function getTracks(bool $force = false): array
    {
        if ($force || !isset($this->tracks)) {
            $queue = $this->client->queue($this->id);
            $this->tracks = $queue->getTracks();
        }

        return $this->tracks;
    }

    /**
     * @param bool $force
     * @return int|array
     */
    public function getCurrentIndex(bool $force = false): int|array
    {
        if ($force || !isset($this->currentIndex)) {
            $queue = $this->client->queue($this->id);
            $this->currentIndex = $queue->getCurrentIndex();
        }

        return $this->currentIndex;
    }

    /**
     * @param Client $client
     * @param array|stdClass $json
     * @return Queue[]
     */
    public static function de_list(Client $client, array|stdClass $json): array
    {
        return array_map(fn($value): Queue => new Queue($client, $value), $json);
    }
}