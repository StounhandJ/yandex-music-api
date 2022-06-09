<?php

namespace StounhandJ\YandexMusicApi\Track;

use StounhandJ\YandexMusicApi\Client;

class TrackId
{
    public function __construct(
        public int $trackId,
        public int $albumId,
        public string $from,
        public Client $client
    ) {
    }

    public function track_full_id(): string
    {
        return "{$this->trackId}:{$this->albumId}";
    }

    public function fetch_track()
    {
        return $this->client->tracks($this->track_full_id());
    }

    public static function de_json(array $data, Client $client): ?TrackId
    {
        if ($data == null) {
            return null;
        }

        return new TrackId(
            $data['$trackId'],
            $data['albumId'],
            $data['from'],
            $client
        );
    }
}