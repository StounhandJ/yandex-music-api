<?php

namespace StounhandJ\YandexMusicApi\Account;

use StounhandJ\YandexMusicApi\JSONObject;

class Permissions extends JSONObject
{
    public string $until;
    public array $values;
    public array $default;
}