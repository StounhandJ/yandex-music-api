<?php

namespace StounhandJ\YandexMusicApi\Track;

use StounhandJ\YandexMusicApi\JSONObject;
use StounhandJ\YandexMusicApi\Track\Supplement\Lyric;
use StounhandJ\YandexMusicApi\Track\Supplement\Supplement;
use StounhandJ\YandexMusicApi\Track\Supplement\Video;

class Track extends JSONObject
{
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
    private Supplement $supplement;

    /**
     * @return Lyric
     */
    public function getLyric(): Lyric
    {
        if (!isset($this->supplement)) {
            $this->supplement = $this->client->trackSupplement($this->id);
        }
        return $this->supplement->lyric;
    }

    /**
     * @return Video[]
     */
    public function getVideos(): array
    {
        if (!isset($this->supplement)) {
            $this->supplement = $this->client->trackSupplement($this->id);
        }
        return $this->supplement->videos;
    }
}