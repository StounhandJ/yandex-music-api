<?php

namespace StounhandJ\YandexMusicApi\Models\Account;

use StounhandJ\YandexMusicApi\Models\JSONObject;

class Permissions extends JSONObject
{
    public string $until;
    public array $values;
    public array $default;
}