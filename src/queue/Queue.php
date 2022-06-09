<?php

namespace StounhandJ\YandexMusicApi\Queue;

use StounhandJ\YandexMusicApi\Client;

class Queue
{
    /**
     * @param array $context
     * @param array $tracks
     * @param int $current_index
     * @param string $modified
     * @param string $id
     * @param Client $client
     */
    public function __construct(
        public array $context,
        public array $tracks,
        public int $current_index,
        public string $modified,
        public string $id,
        public Client $client
    ) {
    }

    public function get_current_track()
    {
        return $this->tracks[$this->current_index];
    }

    public static function de_json(array $data, Client $client): ?Queue
    {
        if ($data == null) {
            return null;
        }

        return new Queue(
            $data['context'],
            $data['tracks'],
            $data['currentIndex'],
            $data['modified'],
            $data['id'],
            $client
        );
    }
}