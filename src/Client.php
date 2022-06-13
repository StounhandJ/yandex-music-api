<?php

namespace StounhandJ\YandexMusicApi;

use DateTime;
use DateTimeInterface;
use stdClass;
use StounhandJ\YandexMusicApi\Enum\LandingBlock;
use StounhandJ\YandexMusicApi\Exception\YandexMusicException;
use StounhandJ\YandexMusicApi\Models\Account\AccountSetting;
use StounhandJ\YandexMusicApi\Models\Account\AccountStatus;
use StounhandJ\YandexMusicApi\Models\Account\RotorAccountStatus;
use StounhandJ\YandexMusicApi\Models\Album;
use StounhandJ\YandexMusicApi\Models\Artist\Artist;
use StounhandJ\YandexMusicApi\Models\Artist\ArtistBriefInfo;
use StounhandJ\YandexMusicApi\Models\Feed;
use StounhandJ\YandexMusicApi\Models\Genre;
use StounhandJ\YandexMusicApi\Models\Playlist\Playlist;
use StounhandJ\YandexMusicApi\Models\Queue;
use StounhandJ\YandexMusicApi\Models\Search;
use StounhandJ\YandexMusicApi\Models\Station;
use StounhandJ\YandexMusicApi\Models\Track\Supplement\Lyric;
use StounhandJ\YandexMusicApi\Models\Track\Supplement\Supplement;
use StounhandJ\YandexMusicApi\Models\Track\Supplement\Video;
use StounhandJ\YandexMusicApi\Models\Track\Track;
use StounhandJ\YandexMusicApi\Models\Track\TracksDownloadInfo;
use StounhandJ\YandexMusicApi\Utils\RequestYandexAPI;

class Client
{
    public Config $config;
    private RequestYandexAPI $requestYandexAPI;
    private AccountStatus $accountStatus;

    /**
     * Client constructor.
     * @param string $token
     * @param string $oauthUrl
     * @param string $baseUrl
     */
    public function __construct(
        string $token = "",
        public string $oauthUrl = "https://oauth.yandex.ru",
        public string $baseUrl = "https://api.music.yandex.net"
    ) {
        $this->config = new Config($token);
        $this->requestYandexAPI = new RequestYandexAPI($this->config);
    }

    /**
     * Getting an Account uid
     *
     * @return int
     * @throws YandexMusicException
     */
    public function getUid(): int
    {
        if (!isset($accountStatus)) {
            $this->accountStatus = $this->accountStatus();
        }

        return $this->accountStatus->getAccount()->uid;
    }

    /**
     * Returns information about the current Account setting
     *
     * @return AccountSetting
     * @throws YandexMusicException
     */
    public function accountSettings(): AccountSetting
    {
        return new AccountSetting($this, $this->get("/account/settings")->result);
    }

    /**
     * Getting Account status
     *
     * @return AccountStatus decoded json
     * @throws YandexMusicException
     */
    public function accountStatus(): AccountStatus
    {
        return new AccountStatus($this, $this->get("/account/status")->result);
    }

    /**
     * Getting a list of all queues at the moment
     *
     * @return Queue[] decoded json
     * @throws YandexMusicException
     */
    public function queuesList(): array
    {
        return Queue::deList($this, $this->get("/queues")->result->queues);
    }

    /**
     * Getting a specific queue
     *
     * @param $id string Queue Identifier
     * @return Queue decoded json
     * @throws YandexMusicException
     */
    public function queue(string $id): Queue
    {
        return new Queue($this, $this->get("/queues/$id")->result);
    }

    /**
     * Getting rotor Account status
     *
     * @return RotorAccountStatus
     * @throws YandexMusicException
     */
    public function rotorAccountStatus(): RotorAccountStatus
    {
        return new RotorAccountStatus($this, $this->get("/rotor/Account/status")->result);
    }

    /**
     * Receiving alerts
     *
     * @return array decoded json
     * @throws YandexMusicException
     */
    public function permissionAlerts(): array
    {
        return $this->get("/permission-alerts")->result->alerts;
    }

    /**
     * Getting the values of experimental Account functions
     *
     * @return array decoded json
     * @throws YandexMusicException
     */
    public function accountExperiments(): array
    {
        return json_decode(json_encode($this->get("/Account/experiments")->result), true);
    }

    /**
     * Getting a stream of information (feed) tailored to the user.
     * Contains smart playlists.
     *
     * @return Feed
     * @throws YandexMusicException
     */
    public function feed(): Feed
    {
        return new Feed($this, $this->get("/feed")->result);
    }

    /**
     * @return bool
     * @throws YandexMusicException
     */
    public function feedWizardIsPassed(): bool
    {
        return $this->get("/feed/wizard/is-passed")->result->isWizardPassed ?? false;
    }

    /**
     * Getting a landing page containing blocks with new releases,
     * charts, playlists with new products, etc.
     *
     * Supported block types: personalplaylists, promotions, new-releases, new-playlists,
     * mixes, chart, artists, albums, playlists, play_contexts.
     * TODO Разбить на методы
     * @param LandingBlock|string|string[]|LandingBlock[] $blocks
     *
     * @return mixed parsed json
     * @throws YandexMusicException
     */
    public function landing(LandingBlock|string|array $blocks): mixed
    {
        $url = "/landing3?blocks=";

        if (is_array($blocks)) {
            $url .= implode(',', $blocks);
        } else {
            $url .= $blocks;
        }

        return $this->get($url)->result->blocks;
    }

    /**
     * Getting genres of music
     *
     * @return Genre[]
     * @throws YandexMusicException
     */
    public function genres(): array
    {
        return Genre::deList($this, $this->get("/genres")->result);
    }

    /**
     * Getting information about available track loading options
     *
     * @param int|string $trackId Unique track ID
     * @param bool $getDirectLinks Whether to get a direct download link when calling the method
     *
     * @return TracksDownloadInfo[]
     * @throws YandexMusicException
     */
    public function tracksDownloadInfo(int|string $trackId, bool $getDirectLinks = false): array
    {
        $url = "/tracks/$trackId/download-info";

        $result = $this->get($url)->result;

        if ($getDirectLinks) {
            foreach ($result as $item) {
                if ($item->codec == 'mp3') {
                    $item->directLink = $this->getDirectLink(
                        $item->downloadInfoUrl,
                        $item->codec,
                        $item->bitrateInKbps
                    );
                    unset($item->downloadInfoUrl);
                }
            }
        }

        return TracksDownloadInfo::deList($this, $result);
    }

    /**
     * Getting a direct download link from an XML response
     *
     * The method is available only one minute from the moment
     * getting the download information, otherwise 410 error!
     *
     * @param string $url xml-файл с информацией
     * @param string $codec Кодек файла
     * @param string $suffix
     * @return string Прямая ссылка на загрузку трека
     */
    public function getDirectLink(string $url, string $codec = 'mp3', string $suffix = "1"): string
    {
        $response = $this->requestYandexAPI->getXml($url);

        $md5 = md5('XGRlBW9FXlekgbPrRHuSiA' . substr($response->path, 1) . $response->s);
        $urlBody = "/get-$codec/$md5/" . $response->ts . $response->path;

        return "https://" . $response->host . $urlBody;
    }

    /**
     * Method for sending the current state of the track being listened to
     *
     * @param int|string $trackId Unique track ID
     * @param string $from Customer names
     * @param int|null $playlistId The unique ID of the playlist, if one is being listened to.
     * @param bool $fromCache Is the track playing from the cache
     * @param string|null $playId Unique playback ID
     * @param int $trackLengthSeconds Track duration in seconds
     * @param int $totalPlayedSeconds How many tracks were played in total in seconds
     * @param int $endPositionSeconds Окончательное значение воспроизведенных секунд
     * @param string|null $client_now The current date and time of the client in ISO (Y-m-d\TH:i:s.u\Z)
     *
     * @return stdClass
     *
     * @throws YandexMusicException
     */
    public function playAudio(
        int|string $trackId,
        string $from,
        int $playlistId = null,
        bool $fromCache = false,
        string $playId = null,
        int $trackLengthSeconds = 0,
        int $totalPlayedSeconds = 0,
        int $endPositionSeconds = 0,
        string $client_now = null
    ): stdClass {
        $url = "/play-audio";

        $data = array(
            'trackId' => $trackId,
            'from-cache' => $fromCache,
            'from' => $from,
            'play-id' => $playId,
            'uid' => $this->getUid(),
            'timestamp' => (new DateTime())->format("Y-m-d\TH:i:s.u\Z"),
            'Track2-length-seconds' => $trackLengthSeconds,
            'total-played-seconds' => $totalPlayedSeconds,
            'end-position-seconds' => $endPositionSeconds,
            'playlist-id' => $playlistId,
            'client-now' => $client_now ?? (new DateTime())->format("Y-m-d\TH:i:s.u\Z")
        );

        return $this->post($url, $data);
    }

    /**
     * Getting an album by its unique ID along with tracks
     *
     * @param int|string $albumId Unique Album ID
     *
     * @return Album
     * @throws YandexMusicException
     */
    public function albumWithTracks(int|string $albumId): Album
    {
        return new Album($this, $this->get("/albums/$albumId/with-tracks")->result);
    }

    /**
     * Performing search by query and type, obtaining results
     *
     * @param string $text Request text
     * @param bool $noCorrect Without corrections?
     * @param string $type Among what type to look for (track, playlist, album, artist)
     * @param int $page Page number
     * @param bool $playlistInBest Whether to give out playlists is the best search option
     *
     * @return Search
     * @throws YandexMusicException
     */
    public function search(
        string $text,
        bool $noCorrect = false,
        string $type = 'all',
        int $page = 0,
        bool $playlistInBest = true
    ): Search {
        $url = "/search"
            . "?text=$text"
            . "&nocorrect=$noCorrect"
            . "&type=$type"
            . "&page=$page"
            . "&playlist-in-best=$playlistInBest";

        return new Search($this, $this->get($url)->result);
    }

    /**
     * Getting hints for the entered part of the search query.
     *
     * @param string $part Part of the search query
     *
     * @return array
     * @throws YandexMusicException
     */
    public function searchSuggest(string $part): array
    {
        return $this->get("/search/suggest?part=$part")->result;
    }

    /**
     * Getting a playlist or playlist list by unique identifiers
     *
     * @param array|int|string $kind The unique ID of the user's playlist
     * @param int|null $userId The unique ID of the user who owns the playlist
     *
     * @return Playlist[]
     * @throws YandexMusicException
     */
    public function usersPlaylists(array|int|string $kind, int $userId = null): array
    {
        if ($userId == null) {
            $userId = $this->getUid();
        }

        $url = "/users/$userId/playlists";

        $data = array(
            'kinds' => $kind
        );

        return Playlist::deList($this, $this->post($url, $data)->result);
    }

    /**
     * Creating a playlist
     *
     * @param string $title Title
     * @param string $visibility Access Modifier (public, private)
     *
     * @return Playlist
     * @throws YandexMusicException
     */
    public function usersPlaylistsCreate(string $title, string $visibility = 'public'): Playlist
    {
        $url = sprintf(
            "/users/%s/playlists/create",
            $this->getUid()
        );

        $data = array(
            'title' => $title,
            'visibility' => $visibility
        );

        return new Playlist($this, $this->post($url, $data)->result);
    }

    /**
     * Deleting a playlist
     *
     * @param int|string $kind The unique ID of the user's playlist
     *
     * @return string result status
     * @throws YandexMusicException
     */
    public function usersPlaylistsDelete(int|string $kind): string
    {
        $url = sprintf(
            "/users/%s/playlists/%s/delete",
            $this->getUid(),
            $kind
        );

        return $this->post($url)->result;
    }

    /**
     * Changing the playlist name
     *
     * @param int|string $kind The unique ID of the user's playlist
     * @param string $name New name
     *
     * @return Playlist
     * @throws YandexMusicException
     */
    public function usersPlaylistsNameChange(int|string $kind, string $name): Playlist
    {
        $url = sprintf(
            "/users/%s/playlists/%s/name",
            $this->getUid(),
            $kind
        );

        $data = array(
            'value' => $name
        );

        return new Playlist($this->post($url, $data)->result);
    }

    /**
     * Changing the playlist
     *
     * @param int|string $kind The unique ID of the user's playlist
     * @param string $diff JSON representation of changes
     * @param int $revision Action number
     *
     * @return mixed parsed json
     * @throws YandexMusicException
     */
    private function usersPlaylistsChange(
        int|string $kind,
        string $diff,
        int $revision = 1
    ): mixed {
        $url = sprintf(
            "/users/%s/playlists/%s/change",
            $this->getUid(),
            $kind
        );

        $data = array(
            'kind' => $kind,
            'revision' => $revision,
            'diff' => $diff
        );

        return $this->post($url, $data)->result;
    }

    /**
     * Adding a Track2 to a playlist
     * @param int|string $kind The unique ID of the user's playlist
     * @param int|string $trackId Unique Track2 ID
     * @param int $at Index to insert
     * @param int|null $revision Action number
     *
     * @return Playlist
     * @throws YandexMusicException
     */
    public function usersPlaylistsInsertTrack(
        int|string $kind,
        int|string $trackId,
        int $at = 0,
        int $revision = null
    ): Playlist {
        if ($revision == null) {
            $revision = $this->usersPlaylists($kind)[0]->revision;
        }

        $ops = json_encode(array(
            [
                'op' => "insert",
                'at' => $at,
                'tracks' => [['id' => $trackId]]
            ]
        ));

        return new Playlist($this, $this->usersPlaylistsChange($kind, $ops, $revision));
    }

    /**
     * Getting personal stations
     *
     * @return Station[]
     * @throws YandexMusicException
     */
    public function rotorStationsDashboard(): array
    {
        return Station::deList($this, $this->get("/rotor/stations/dashboard")->result->stations);
    }

    /**
     * Getting all the stations
     *
     * @param string $lang API response language in ISO 639-1
     *
     * @return Station[]
     * @throws YandexMusicException
     */
    public function rotorStationsList(string $lang = 'en'): array
    {
        return Station::deList($this, $this->get("/rotor/stations/list?language=$lang")->result);
    }

    /**
     * @param string $genre
     * @param string $type
     * @param string|null $from
     * @param int|string|null $batchId
     * @param string|null $trackId
     *
     * @return mixed parsed json
     *
     * @throws YandexMusicException
     */
    public function rotorStationGenreFeedback(
        string $genre,
        string $type,
        string $from = null,
        int|string $batchId = null,
        string $trackId = null
    ): mixed {
        $url = $this->baseUrl . "/rotor/station/genre:$genre/feedback";
        if ($batchId != null) {
            $url .= "?batch-id=" . $batchId;
        }

        $data = array(
            'type' => $type,
            'timestamp' => (new DateTime())->format(DateTimeInterface::ATOM)
        );
        if ($from != null) {
            $data['from'] = $from;
        }
        if ($trackId != null) {
            $data['trackId'] = $trackId;
        }

        return $this->post($url, $data)->result;
    }

    /**
     * Getting all the information about the artist from his page
     *
     * @param int|string $artistId
     *
     * @return ArtistBriefInfo
     * @throws YandexMusicException
     */
    public function artistsBriefInfo(int|string $artistId): ArtistBriefInfo
    {
        $url = "/artists/$artistId/brief-info";

        return new ArtistBriefInfo($this, $this->get($url)->result);
    }

    /**
     * Actions from the list of likes
     *
     * @param string $objectType
     * @param array|int|string $ids
     * @param bool $remove
     *
     * @return int Action number
     * @throws YandexMusicException
     */
    private function likeAction(string $objectType, array|int|string $ids, bool $remove = false): int
    {
        $action = $remove ? 'remove' : 'add-multiple';

        $url = sprintf(
            "/users/%s/likes/%ss/%s",
            $this->getUid(),
            $objectType,
            $action
        );

        $data = array(
            "$objectType-ids" => $ids
        );

        $response = $this->post($url, $data)->result;

        return $objectType == 'Track2' ? $response->revision : $response;
    }

    /**
     * Adding a Track2 to a favorite
     *
     * @param array|int|string $trackIds
     * @return int Action number
     * @throws YandexMusicException
     */
    public function usersLikesTracksAdd(array|int|string $trackIds): int
    {
        return $this->likeAction('Track2', $trackIds);
    }

    /**
     * Deleting a Track2 from a favorite
     *
     * @param array|int|string $trackIds
     * @return int Action number
     * @throws YandexMusicException
     */
    public function usersLikesTracksRemove(array|int|string $trackIds): int
    {
        return $this->likeAction('Track2', $trackIds, true);
    }

    /**
     * Adding an artist to your favorite
     *
     * @param array|int|string $artistIds
     * @return int Action number
     * @throws YandexMusicException
     */
    public function usersLikesArtistsAdd(array|int|string $artistIds): int
    {
        return $this->likeAction('artist', $artistIds);
    }

    /**
     * Removing an artist from a favorite
     *
     * @param array|int|string $artistIds
     * @return int Action number
     * @throws YandexMusicException
     */
    public function usersLikesArtistsRemove(array|int|string $artistIds): int
    {
        return $this->likeAction('artist', $artistIds, true);
    }

    /**
     * Adding a playlist to your favorite
     *
     * @param array|int|string $playlistIds
     * @return int Action number
     * @throws YandexMusicException
     */
    public function usersLikesPlaylistsAdd(array|int|string $playlistIds): int
    {
        return $this->likeAction('playlist', $playlistIds);
    }

    /**
     * Deleting a playlist from a favorite
     *
     * @param array|int|string $playlistIds
     * @return int Action number
     * @throws YandexMusicException
     */
    public function usersLikesPlaylistsRemove(array|int|string $playlistIds): int
    {
        return $this->likeAction('playlist', $playlistIds, true);
    }

    /**
     * Adding an album to your favorite
     *
     * @param array|int|string $albumIds
     * @return int Action number
     * @throws YandexMusicException
     */
    public function usersLikesAlbumsAdd(array|int|string $albumIds): int
    {
        return $this->likeAction('album', $albumIds);
    }

    /**
     * Deleting an album from a favorite
     *
     * @param array|int|string $albumIds
     * @return int Action number
     * @throws YandexMusicException
     */
    public function usersLikesAlbumsRemove(array|int|string $albumIds): int
    {
        return $this->likeAction('album', $albumIds, true);
    }

    /**
     * Getting a list of objects by IDs
     *
     * @param string $objectType
     * @param array|int|string $ids
     *
     * @return mixed parsed json
     * @throws YandexMusicException
     */
    private function getList(string $objectType, array|int|string $ids): mixed
    {
        $url = sprintf(
            "/%ss",
            $objectType
        );

        if ($objectType == 'playlist') {
            $url .= "/list";
        }

        $data = array(
            $objectType . '-ids' => $ids
        );

        return $this->post($url, $data)->result;
    }

    /**
     * Getting artists by IDs
     *
     * @param array|int|string $artistIds
     * @return Artist[]
     * @throws YandexMusicException
     */
    public function artists(array|int|string $artistIds): array
    {
        return Artist::deList($this, $this->getList('artist', $artistIds));
    }

    /**
     * Getting albums by IDs
     *
     * @param array|int|string $albumIds
     * @return Album[]
     * @throws YandexMusicException
     */
    public function albums(array|int|string $albumIds): array
    {
        return Album::deList($this, $this->getList('album', $albumIds));
    }

    /**
     * Getting tracks by IDs
     *
     * @param array|int|string $trackIds
     * @return Track[] parsed json
     * @throws YandexMusicException
     */
    public function tracks(array|int|string $trackIds): array
    {
        return Track::deList($this, $this->getList('Track2', $trackIds));
    }

    /**
     * Getting playlists by IDs
     *
     * @param array|int|string $playlistIds
     * @return mixed
     * @throws YandexMusicException
     */
    public function playlistsList(array|int|string $playlistIds): mixed
    {
        return $this->getList('playlist', $playlistIds);
    }

    /**
     * Getting personal user playlists
     *
     * @return Playlist[]
     * @throws YandexMusicException
     */
    public function usersPlaylistsList(): array
    {
        $url = sprintf(
            "/users/%s/playlists/list",
            $this->getUid()
        );

        return Playlist::deList($this, $this->get($url)->result);
    }

    /**
     * Getting the objects you like
     *
     * @param string $objectType Track2, album, artist, playlist
     *
     * @return array decoded json
     * @throws YandexMusicException
     */
    private function getLikes(string $objectType): array
    {
        $url = sprintf(
            "/users/%s/likes/%ss",
            $this->getUid(),
            $objectType
        );

        $response = $this->get($url)->result;

        return $objectType == "Track2" ? $response->library : $response;
    }

    /**
     * Getting tracks you like
     *
     * @return Track[]
     * @throws YandexMusicException
     */
    public function getLikesTracks(): array
    {
        return Track::deList($this, $this->getLikes('Track2'));
    }

    /**
     * Getting the albums you like
     *
     * @return Album[]
     * @throws YandexMusicException
     */
    public function getLikesAlbums(): array
    {
        return Album::deList($this, $this->getLikes('album'));
    }

    /**
     * Getting the artists you like
     *
     * @return Artist[]
     * @throws YandexMusicException
     */
    public function getLikesArtists(): array
    {
        return Artist::deList($this, $this->getLikes('artist'));
    }

    /**
     * Getting playlists you like
     *
     * @return Playlist[]
     * @throws YandexMusicException
     */
    public function getLikesPlaylists(): array
    {
        return Playlist::deList(
            $this,
            array_map(fn($v): stdClass => $v->playlist, $this->getLikes('playlist'))
        );
    }

    /**
     * Getting tracks you don't like
     *
     * @param int $ifModifiedSinceRevision
     *
     * @return Track[]
     * @throws YandexMusicException
     */
    public function getDislikesTracks(int $ifModifiedSinceRevision = 0): array
    {
        $url = sprintf(
            "/users/%s/dislikes/tracks?if_modified_since_revision=%s",
            $this->getUid(),
            $ifModifiedSinceRevision
        );

        return Track::deList($this, $this->get($url)->result->library->tracks);
    }

    /**
     * Actions with a list of tracks you don't like
     *
     * @param array|int|string $ids
     * @param bool $remove
     *
     * @return int Action Number
     * @throws YandexMusicException
     */
    private function dislikeAction(array|int|string $ids, bool $remove = false): int
    {
        $action = $remove ? 'remove' : 'add-multiple';

        $url = sprintf(
            "/users/%s/dislikes/tracks/%s",
            $this->getUid(),
            $action
        );

        $data = array(
            'Track2-ids' => $ids
        );

        return $this->post($url, $data)->result->revision;
    }

    /**
     * Adding a Track2 to the disliked list
     *
     * @param array|int|string $trackIds
     * @return int Action Number
     * @throws YandexMusicException
     */
    public function usersDislikesTracksAdd(array|int|string $trackIds): int
    {
        return $this->dislikeAction($trackIds);
    }

    /**
     * Deleting a Track2 from the disliked list
     *
     * @param array|int|string $trackIds
     * @return int Action Number
     * @throws YandexMusicException
     */
    public function usersDislikesTracksRemove(array|int|string $trackIds): int
    {
        return $this->dislikeAction($trackIds, true);
    }

    /**
     * Getting a Supplement Track2
     *
     * @param int|string $trackId
     * @return Supplement
     * @throws YandexMusicException
     */
    public function trackSupplement(int|string $trackId): Supplement
    {
        $url = sprintf(
            "/tracks/%s/supplement",
            $trackId,
        );
        $result = $this->get($url)->result;
        return new Supplement(
            new Lyric($this, $result->lyrics ?? null),
            Video::deList($this, $result->videos ?? [])
        );
    }

    /**
     * Sending a post request
     *
     * @param string $url
     * @param array $data
     * @return stdClass
     * @throws YandexMusicException
     */
    private function post(string $url, array $data = []): stdClass
    {
        return json_decode($this->requestYandexAPI->post($this->baseUrl . $url, $data));
    }

    /**
     * Sending a get request
     *
     * @param string $url
     * @return stdClass
     * @throws YandexMusicException
     */
    private function get(string $url): stdClass
    {
        return json_decode(
            $this->requestYandexAPI->get($this->baseUrl . $url)
        );
    }
}

