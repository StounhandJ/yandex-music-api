<?php

namespace StounhandJ\YandexMusicApi\Models;

use StounhandJ\YandexMusicApi\Models\Artist\Artist;
use StounhandJ\YandexMusicApi\Models\Playlist\Playlist;
use StounhandJ\YandexMusicApi\Models\Track\Track;

class Search extends JSONObject
{
    public bool $misspellCorrected;
    public bool $nocorrect;
    public string $searchRequestId;
    public string $text;
    protected array $best;
    protected array $albums;
    protected array $artists;
    public array $podcasts;
    protected array $podcast_episodes;
    protected array $tracks;
    protected array $playlists;

    /**
     * @return Track|Playlist|Album|Artist|null
     */
    public function getBest(): Album|Track|Playlist|Artist|null
    {
        return isset($this->best) ? match ($this->best["type"]) {
            "track" => new Track($this->client, $this->best["result"]),
            "playlist" => new Playlist($this->client, $this->best["result"]),
            "album" => new Album($this->client, $this->best["result"]),
            "artist" => new Artist($this->client, $this->best["result"]),
            default => null,
        } : null;
    }

    /**
     * @return Album[]
     */
    public function getAlbums(): array
    {
        return isset($this->albums) ? Album::deList($this->client, $this->albums["results"]) : [];
    }

    /**
     * @return Artist[]
     */
    public function getArtists(): array
    {
        return isset($this->artists) ? Artist::deList($this->client, $this->artists["results"]) : [];
    }

    /**
     * @return Track[]
     */
    public function getPodcastEpisodes(): array
    {
        return isset($this->podcast_episodes) ? Track::deList($this->client, $this->podcast_episodes["results"]) : [];
    }

    /**
     * @return Track[]
     */
    public function getTracks(): array
    {
        return isset($this->tracks) ? Track::deList($this->client, $this->tracks["results"]) : [];
    }

    /**
     * @return Playlist[]
     */
    public function getPlaylists(): array
    {
        return isset($this->playlists) ? Playlist::deList($this->client, $this->playlists["results"]) : [];
    }
}