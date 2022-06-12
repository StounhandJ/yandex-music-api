<?php

namespace StounhandJ\YandexMusicApi\Models\Account;

use StounhandJ\YandexMusicApi\Models\JSONObject;

class Subscription extends JSONObject
{
    public array $autoRenewable;
    public array $nonAutoRenewableRemainder;
    public bool $hadAnySubscription;
    public bool $canStartTrial;
    public bool $mcdonalds;
}