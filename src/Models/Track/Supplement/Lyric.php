<?php

namespace StounhandJ\YandexMusicApi\Models\Track\Supplement;

use StounhandJ\YandexMusicApi\Models\JSONObject;

class Lyric extends JSONObject
{
    public string $id = "";
    public string $lyrics = "";
    public string $fullLyrics = "";
    public bool $hasRights = false;
    public bool $showTranslation = false;
}