<?php

namespace StounhandJ\YandexMusicApi\Track;

use StounhandJ\YandexMusicApi\JSONObject;

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
}