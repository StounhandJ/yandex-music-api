<?php

namespace StounhandJ\YandexMusicApi\Models\Account;

use StounhandJ\YandexMusicApi\Models\JSONObject;

class AccountSetting extends JSONObject
{
    public int $uid;
    public bool $lastFmScrobblingEnabled;
    public bool $facebookScrobblingEnabled;
    public bool $shuffleEnabled;
    public bool $addNewTrackOnPlaylistTop;
    public int $volumePercents;
    public string $userMusicVisibility;
    public string $userSocialVisibility;
    public bool $adsDisabled;
    public string $modified;
    public bool $rbtDisabled;
    public string $theme;
    public bool $promosDisabled;
    public bool $autoPlayRadio;
    public bool $syncQueueEnabled;
    public bool $childModEnabled;
}