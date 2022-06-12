<?php

namespace StounhandJ\YandexMusicApi\Account;

use StounhandJ\YandexMusicApi\JSONObject;

class RotorAccountStatus extends JSONObject
{
    protected array $account;
    protected array $permissions;
    protected array $subscription;
    public int $skipsPerHour;
    public bool $stationExists;
    public array $plus;
    public int $premiumRegion;

    /**
     * @return Account
     */
    public function getAccount(): Account
    {
        return new Account($this->client, $this->account);
    }

    /**
     * @return Permissions
     */
    public function getPermissions(): Permissions
    {
        return new Permissions($this->client, $this->permissions);
    }

    /**
     * @return Subscription
     */
    public function getSubscription(): Subscription
    {
        return new Subscription($this->client, $this->subscription);
    }
}