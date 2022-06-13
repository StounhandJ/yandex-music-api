<?php

namespace StounhandJ\YandexMusicApi\Models;

use StounhandJ\YandexMusicApi\Models\Playlist\GeneratedPlaylist;

class Feed extends JSONObject
{
    public string $nextRevision;
    public bool $canGetMoreEvents;
    public bool $pumpkin;
    public bool $isWizardPassed;
    protected array $generatedPlaylists;
    public array $headlines;
    public string $today;
    public array $days;

    /**
     * @return GeneratedPlaylist[]
     */
    public function getGeneratedPlaylists(): array
    {
        return GeneratedPlaylist::deList($this->client, $this->generatedPlaylists);
    }
}