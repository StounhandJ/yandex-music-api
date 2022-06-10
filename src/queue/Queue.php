<?php

namespace StounhandJ\YandexMusicApi\Queue;

use StounhandJ\YandexMusicApi\Client;
use StounhandJ\YandexMusicApi\JSONObject;

class Queue extends JSONObject
{
    public array $context;
    public array $tracks;
    public int $current_index;
    public string $modified;
    public string $id;
}