<?php

namespace StounhandJ\YandexMusicApi\Account;

use StounhandJ\YandexMusicApi\JSONObject;

class AccountStatus extends JSONObject
{
    protected array $account;
    protected array $permissions;
    protected array $subscription;
    public bool $subeditor;
    public int $subeditorLevel;
    public bool $pretrialActive;
    public array $masterhub;
    public array $plus;
    public string $defaultEmail;
    public string $userhash;

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