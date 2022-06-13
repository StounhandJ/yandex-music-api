<?php

namespace StounhandJ\YandexMusicApi\Models\Artist;

use StounhandJ\YandexMusicApi\Models\Album;
use StounhandJ\YandexMusicApi\Models\JSONObject;
use StounhandJ\YandexMusicApi\Models\Playlist\Playlist;
use StounhandJ\YandexMusicApi\Models\Track\Supplement\Video;
use StounhandJ\YandexMusicApi\Models\Track\Track;

class ArtistBriefInfo extends JSONObject
{
    protected array $artist;
    protected array $albums;
    protected array $alsoAlbums;
    public array $lastReleaseIds;
    protected array $popularTracks;
    protected array $similarArtists;
    public array $allCovers;
    public array $concerts;
    protected array $videos;
    public array $vinyls;
    public bool $hasPromotions;
    protected array $lastReleases;
    protected array $playlistIds;
    protected array $playlists;

    /**
     * @return Artist
     */
    public function getArtist(): Artist
    {
        return new Artist($this->client, $this->artist);
    }

    /**
     * @return Album[]
     */
    public function getAlbums(): array
    {
        return Album::deList($this->client, $this->albums);
    }

    /**
     * @return Album[]
     */
    public function getAlsoAlbums(): array
    {
        return Album::deList($this->client, $this->alsoAlbums);
    }

    /**
     * @return Track[]
     */
    public function getPopularTracks(): array
    {
        return Track::deList($this->client, $this->popularTracks);
    }

    /**
     * @return Artist[]
     */
    public function getSimilarArtists(): array
    {
        return Artist::deList($this->client, $this->similarArtists);
    }

    /**
     * @return Video[]
     */
    public function getVideos(): array
    {
        return Video::deList($this->client, $this->videos);
    }

    /**
     * @return \StounhandJ\YandexMusicApi\Models\Track\Track[]
     */
    public function getLastReleases(): array
    {
        return Track::deList($this->client, $this->lastReleases);
    }

    /**
     * @return \StounhandJ\YandexMusicApi\Models\Playlist\Playlist[]
     */
    public function getPlaylists(): array
    {
        return Playlist::deList($this->client, $this->playlists);
    }

}