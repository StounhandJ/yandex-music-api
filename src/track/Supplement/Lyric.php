<?php

namespace StounhandJ\YandexMusicApi\Track\Supplement;

use StounhandJ\YandexMusicApi\JSONObject;

class Lyric extends JSONObject
{
    public string $id;
    public string $lyrics;
    public string $fullLyrics;
    public bool $hasRights;
    public bool $showTranslation;
}