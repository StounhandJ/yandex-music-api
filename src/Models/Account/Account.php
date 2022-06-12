<?php

namespace StounhandJ\YandexMusicApi\Models\Account;

use StounhandJ\YandexMusicApi\Models\JSONObject;

class Account extends JSONObject
{
    public string $now;
    public int $uid;
    public string $login;
    public int $region;
    public string $fullName;
    public string $secondName;
    public string $firstName;
    public string $displayName;
    public bool $serviceAvailable;
    public bool $hostedUser;
    public array $passport_phones;
    public string $registeredAt;
}