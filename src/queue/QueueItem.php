<?php

namespace StounhandJ\YandexMusicApi\Queue;

use StounhandJ\YandexMusicApi\Client;

class QueueItem
{
    /**
     * @param array $context
     * @param string $modified
     * @param string $id
     * @param Client $client
     */
    public function __construct(
        public array $context,
        public string $modified,
        public string $id,
        public Client $client,
    ) {
    }

    public function fetch_queue()
    {
        return $this->client->queue($this->id);
    }

    public static function de_list(array $data, Client $client): array
    {
        if ($data == null) {
            return [];
        }

        $result = [];
        foreach ($data as $queue) {
            $result[] = new QueueItem($queue['context'], $queue['modified'], $queue['id'], $client);
        }
        return $result;
    }
}