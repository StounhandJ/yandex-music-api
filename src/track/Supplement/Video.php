<?php

namespace StounhandJ\YandexMusicApi\Track\Supplement;

use StounhandJ\YandexMusicApi\JSONObject;

class Video extends JSONObject
{
    public string $title;
    public string $cover;
    public string $embedUrl;
    public string $provider;
    public string $providerVideoId;
}