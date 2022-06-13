<?php

namespace StounhandJ\YandexMusicApi\Models\Track;

use stdClass;
use StounhandJ\YandexMusicApi\Client;
use StounhandJ\YandexMusicApi\Exception\YandexMusicException;
use StounhandJ\YandexMusicApi\Models\JSONObject;
use StounhandJ\YandexMusicApi\Models\Track\Supplement\Lyric;
use StounhandJ\YandexMusicApi\Models\Track\Supplement\Supplement;
use StounhandJ\YandexMusicApi\Models\Track\Supplement\Video;

class Track extends JSONObject
{
    //TODO добавить скачивание трека
    protected string $trackId;
    public string $id;
    public string $realId;
    public string $title;
    public bool $available;
    public bool $availableForPremiumUsers;
    public bool $availableFullWithoutPermission;
    public string $storageDir;
    public int $durationMs;
    public int $fileSize;
    public int $previewDurationMs;
    public string $ogImage;
    public bool $lyricsAvailable;
    public bool $rememberPosition;
    public string $trackSharingFlag;
    public string $trackSource;
    public array $albums;
    public array $artists;
    public array $r128;
    public array $major;
    public array $lyricsInfo;
    private Supplement $supplement;

    /**
     * @return Lyric
     * @throws YandexMusicException
     */
    public function getLyric(): Lyric
    {
        $this->restoringTrack();
        if (!isset($this->supplement)) {
            $this->supplement = $this->client->trackSupplement($this->id);
        }
        return $this->supplement->lyric;
    }

    /**
     * @return Video[]
     * @throws YandexMusicException
     */
    public function getVideos(): array
    {
        $this->restoringTrack();
        if (!isset($this->supplement)) {
            $this->supplement = $this->client->trackSupplement($this->id);
        }
        return $this->supplement->videos;
    }

    public function trackFullId(): string
    {
        $this->restoringTrack();
        return "{$this->id}:{$this->albums[0]->id}";
    }

    private function restoringTrack()
    {
        if (!isset($this->id)) {
            $this->update();
        }
    }

    public function update(): void
    {
        if (isset($this->id)) {
            $track = $this->client->tracks($this->id)[0];
        } else {
            $track = $this->client->tracks($this->trackId)[0];
        }
        $this->id = $track->id;
        $this->realId = $track->realId;
        $this->title = $track->title;
        $this->available = $track->available;
        $this->availableForPremiumUsers = $track->availableForPremiumUsers;
        $this->availableFullWithoutPermission = $track->availableFullWithoutPermission;
        $this->storageDir = $track->storageDir;
        $this->durationMs = $track->durationMs;
        $this->fileSize = $track->fileSize;
        $this->previewDurationMs = $track->previewDurationMs;
        $this->ogImage = $track->ogImage;
        $this->lyricsAvailable = $track->lyricsAvailable;
        $this->rememberPosition = $track->rememberPosition;
        $this->trackSharingFlag = $track->trackSharingFlag;
        $this->trackSource = $track->trackSource;
        $this->albums = $track->albums;
        $this->artists = $track->artists;
        $this->r128 = $track->r128;
        $this->major = $track->major;
        $this->lyricsInfo = $track->lyricsInfo;
    }

    /**
     * @param Client $client
     * @param array|stdClass $json
     * @return Track[]
     */
    public static function deList(Client $client, array|stdClass $json): array
    {
        return array_map(fn($value): Track => new Track($client, $value), $json);
    }
}