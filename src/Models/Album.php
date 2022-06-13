<?php

namespace StounhandJ\YandexMusicApi\Models;

use stdClass;
use StounhandJ\YandexMusicApi\Client;
use StounhandJ\YandexMusicApi\Exception\YandexMusicException;
use StounhandJ\YandexMusicApi\Models\Artist\Artist;
use StounhandJ\YandexMusicApi\Models\Artist\Label;
use StounhandJ\YandexMusicApi\Models\Track\Track;

class Album extends JSONObject
{
    public int $id;
    public string $title;
    public string $type;
    public string $metaType;
    public int $year;
    public string $releaseDate;
    public string $coverUri;
    public string $ogImage;
    public string $genre;
    public array $buy;
    public int $trackCount;
    public int $likesCount;
    public bool $recent;
    public bool $veryImportant;
    protected array $artists;
    protected array $labels;
    public bool $available;
    public bool $availableForPremiumUsers;
    public bool $availableForMobile;
    public bool $availablePartially;
    public array $bests;
    public int $durationSec;
    public string $sortOrder;
    protected array $volumes;
    public array $pager;
    public string $shortDescription;
    public string $description;
    public bool $isPremiere;
    public bool $isBanner;

    /**
     * @return Artist[]
     */
    public function getArtists(): array
    {
        return Artist::deList($this->client, $this->artists);
    }

    /**
     * @return Label[]
     */
    public function getLabels(): array
    {
        return Label::deList($this->client, $this->artists);
    }

    /**
     * @return Track[]
     * @throws YandexMusicException
     */
    public function getTracks(): array
    {
        if (!isset($this->volumes) || count($this->volumes) == 0) {
            $this->updateTrack();
        }
        return is_array($this->volumes[0]) ? Track::deList($this->client, $this->volumes[0]) : $this->volumes;
    }

    /**
     * Updates the playlist's track list
     *
     * @return void
     * @throws YandexMusicException
     */
    public function updateTrack(): void
    {
        $album = $this->client->albumWithTracks($this->id);

        $this->durationSec = $album->durationSec;
        $this->sortOrder = $album->sortOrder;
        $this->volumes = $album->getTracks();
        $this->pager = $album->pager;
        $this->shortDescription = $album->shortDescription;
        $this->description = $album->description;
        $this->isPremiere = $album->isPremiere;
        $this->isBanner = $album->isBanner;
    }

    /**
     * @param Client $client
     * @param array|stdClass $json
     * @return Album[]
     */
    public static function deList(Client $client, array|stdClass $json): array
    {
        return array_map(fn($value): Album => new Album($client, $value), $json);
    }
}