<?php

namespace StounhandJ\YandexMusicApi\Account;

use StounhandJ\YandexMusicApi\JSONObject;

class Subscription extends JSONObject
{
    public array $autoRenewable;
    public array $nonAutoRenewableRemainder;
    public bool $hadAnySubscription;
    public bool $canStartTrial;
    public bool $mcdonalds;
}