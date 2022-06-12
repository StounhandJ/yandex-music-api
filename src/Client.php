<?php

namespace StounhandJ\YandexMusicApi;

use DateTime;
use DateTimeInterface;
use stdClass;
use StounhandJ\YandexMusicApi\Account\AccountSetting;
use StounhandJ\YandexMusicApi\Account\AccountStatus;
use StounhandJ\YandexMusicApi\Account\RotorAccountStatus;
use StounhandJ\YandexMusicApi\Exception\YandexMusicException;
use StounhandJ\YandexMusicApi\Queue\Queue;
use StounhandJ\YandexMusicApi\Track\Supplement\Lyric;
use StounhandJ\YandexMusicApi\Track\Supplement\Supplement;
use StounhandJ\YandexMusicApi\Track\Supplement\Video;
use StounhandJ\YandexMusicApi\Track\Track;
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
     * Getting an account uid
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
     * Returns information about the current account setting
     *
     * @return AccountSetting
     * @throws YandexMusicException
     */
    public function accountSettings(): AccountSetting
    {
        return new AccountSetting($this, $this->get("/account/settings")->result);
    }

    /**
     * Getting account status
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
     * Getting rotor account status
     *
     * @return RotorAccountStatus
     * @throws YandexMusicException
     */
    public function rotorAccountStatus(): RotorAccountStatus
    {
        return new RotorAccountStatus($this, $this->get("/rotor/account/status")->result);
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
     * Getting the values of experimental account functions
     *
     * @return array decoded json
     * @throws YandexMusicException
     */
    public function accountExperiments(): array
    {
        return json_decode(json_encode($this->get("/account/experiments")->result), true);
    }

    /**
     * Получение потока информации (фида) подобранного под пользователя.
     * Содержит умные плейлисты.
     * TODO Подготовить модель
     * @return array decoded json
     * @throws YandexMusicException
     */
    public function feed(): array
    {
        return $this->get("/feed")->result;
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
     * TODO сделать enum и модель
     * @param array|string $blocks
     *
     * @return mixed parsed json
     * @throws YandexMusicException
     */
    public function landing(array|string $blocks): mixed
    {
        $url = "/landing3?blocks=";

        if (is_array($blocks)) {
            $url .= implode(',', $blocks);
        } else {
            $url .= $blocks;
        }

        $response = $this->get($url);
        if ($response->result == null) {
            $response = $response->error;
        } else {
            $response = $response->result;
        }

        return $response;
    }

    /**
     * Getting genres of music
     * TODO модель
     * TODO СТОП
     * @return array parsed json
     * @throws YandexMusicException
     */
    public function genres(): array
    {
        return $this->get("/genres")->result;
    }

    /**
     * Получение информации о доступных вариантах загрузки трека
     *
     * @param int|string $trackId Уникальный идентификатор трека
     * @param bool $getDirectLinks Получить ли при вызове метода прямую ссылку на загрузку
     *
     * @return array parsed json
     * @throws YandexMusicException
     */
    public function tracksDownloadInfo(int|string $trackId, bool $getDirectLinks = false): array
    {
        $result = array();
        $url = "/tracks/$trackId/download-info";

        $response = $this->get($url);
        if ($response->result == null) {
            $result = $response->error;
        } else {
            if ($getDirectLinks) {
                foreach ($response->result as $item) {
                    if ($item->codec == 'mp3') {
                        $item->directLink = $this->getDirectLink(
                            $item->downloadInfoUrl,
                            $item->codec,
                            $item->bitrateInKbps
                        );
                        unset($item->downloadInfoUrl);
                        $result[] = $item;
                    }
                }
            } else {
                $result = $response->result;
            }
        }

        return $result;
    }

    /**
     * Получение прямой ссылки на загрузку из XML ответа
     *
     * Метод доступен только одну минуту с момента
     * получения информации загрузке, иначе 410 ошибка!
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
     * Метод для отправки текущего состояния прослушиваемого трека
     *
     * @param int|string $trackId Уникальный идентификатор трека
     * @param string $from Наименования клиента
     * @param int|string $albumId Уникальный идентификатор альбома
     * @param int|null $playlistId Уникальный идентификатор плейлиста, если таковой прослушивается.
     * @param bool $fromCache Проигрывается ли трек с кеша
     * @param string|null $playId Уникальный идентификатор проигрывания
     * @param int $trackLengthSeconds Продолжительность трека в секундах
     * @param int $totalPlayedSeconds Сколько было всего воспроизведено трека в секундах
     * @param int $endPositionSeconds Окончательное значение воспроизведенных секунд
     * @param string|null $client_now Текущая дата и время клиента в ISO
     *
     * @return stdClass
     *
     * @throws YandexMusicException
     */
    private function playAudio(
        int|string $trackId,
        string $from,
        int|string $albumId,
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
            'track-id' => $trackId,
            'from-cache' => $fromCache,
            'from' => $from,
            'play-id' => $playId,
            'uid' => $this->getUid(),
            'timestamp' => (new DateTime())->format(DateTime::ATOM),
            'track-length-seconds' => $trackLengthSeconds,
            'total-played-seconds' => $totalPlayedSeconds,
            'end-position-seconds' => $endPositionSeconds,
            'album-id' => $albumId,
            'playlist-id' => $playlistId,
            'client-now' => (new DateTime())->format(DateTime::ATOM)
        );

        return $this->post($url, $data);
    }

    /**
     * Получение альбома по его уникальному идентификатору вместе с треками
     *
     * @param int|string $albumId Уникальный идентификатор альбома
     *
     * @return mixed parsed json
     * @throws YandexMusicException
     */
    public function albumsWithTracks(int|string $albumId): mixed
    {
        return $this->get("/albums/$albumId/with-tracks")->result;
    }

    /**
     * Осуществление поиска по запросу и типу, получение результатов
     *
     * @param string $text Текст запроса
     * @param bool $noCorrect Без исправлений?
     * @param string $type Среди какого типа искать (трек, плейлист, альбом, исполнитель)
     * @param int $page Номер страницы
     * @param bool $playlistInBest Выдавать ли плейлисты лучшим вариантом поиска
     *
     * @return mixed parsed json
     * @throws YandexMusicException
     */
    public function search(
        string $text,
        bool $noCorrect = false,
        string $type = 'all',
        int $page = 0,
        bool $playlistInBest = true
    ): mixed {
        $url = $this->baseUrl . "/search"
            . "?text=$text"
            . "&nocorrect=$noCorrect"
            . "&type=$type"
            . "&page=$page"
            . "&playlist-in-best=$playlistInBest";

        return $this->get($url)->result;
    }

    /**
     * Получение подсказок по введенной части поискового запроса.
     *
     * @param string $part Часть поискового запроса
     *
     * @return mixed parsed json
     * @throws YandexMusicException
     */
    public function searchSuggest(string $part): mixed
    {
        return $this->get("/search/suggest?part=$part")->result;
    }

    /**
     * Получение плейлиста или списка плейлистов по уникальным идентификаторам
     *
     * @param array|int|string $kind Уникальный идентификатор плейлиста
     * @param int|null $userId Уникальный идентификатор пользователя владеющего плейлистом
     *
     * @return stdClass parsed json
     * @throws YandexMusicException
     */
    public function usersPlaylists(array|int|string $kind, int $userId = null): stdClass
    {
        if ($userId == null) {
            $userId = $this->getUid();
        }

        $url = "/users/$userId/playlists";

        $data = array(
            'kind' => $kind
        );

        return $this->post($url, $data);
    }

    /**
     * Создание плейлиста
     *
     * @param string $title Название
     * @param string $visibility Модификатор доступа
     *
     * @return mixed parsed json
     * @throws YandexMusicException
     */
    public function usersPlaylistsCreate(string $title, string $visibility = 'public'): mixed
    {
        $url = sprintf(
            "/users/%s/playlists/create",
            $this->getUid()
        );

        $data = array(
            'title' => $title,
            'visibility' => $visibility
        );

        return $this->post($url, $data)->result;
    }

    /**
     * Удаление плейлиста
     *
     * @param int|string $kind Уникальный идентификатор плейлиста
     *
     * @return mixed decoded json
     * @throws YandexMusicException
     */
    public function usersPlaylistsDelete(int|string $kind): mixed
    {
        $url = sprintf(
            "/users/%s/playlists/%s/delete",
            $this->getUid(),
            $kind
        );

        return $this->post($url)->result;
    }

    /**
     * Изменение названия плейлиста
     *
     * @param int|string $kind Уникальный идентификатор плейлиста
     * @param string $name Новое название
     *
     * @return mixed decoded json
     * @throws YandexMusicException
     */
    public function usersPlaylistsNameChange(int|string $kind, string $name): mixed
    {
        $url = sprintf(
            "/users/%s/playlists/%s/name",
            $this->getUid(),
            $kind
        );

        $data = array(
            'value' => $name
        );

        return $this->post($url, $data)->result;
    }

    /**
     * Изменение плейлиста.
     *
     * @param int|string $kind Уникальный идентификатор плейлиста
     * @param string $diff JSON представления отличий старого и нового плейлиста
     * @param int $revision
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
     * Добавление трека в плейлист
     * @param int|string $kind Уникальный идентификатор плейлиста
     * @param int|string $trackId Уникальный идентификатор трека
     * @param int|string $albumId Уникальный идентификатор альбома
     * @param int $at Индекс для вставки
     * @param int|null $revision
     *
     * @return mixed parsed json
     * @throws YandexMusicException
     */
    public function usersPlaylistsInsertTrack(
        int|string $kind,
        int|string $trackId,
        int|string $albumId,
        int $at = 0,
        int $revision = null
    ): mixed {
        if ($revision == null) {
            $revision = $this->usersPlaylists($kind)->result[0]->revision;
        }

        $ops = json_encode(array(
            [
                'op' => "insert",
                'at' => $at,
                'tracks' => [['id' => $trackId, 'albumId' => $albumId]]
            ]
        ));

        return $this->usersPlaylistsChange($kind, $ops, $revision);
    }


    /**
     * @return mixed parsed json
     * @throws YandexMusicException
     */
    public function rotorStationsDashboard(): mixed
    {
        return $this->get("/rotor/stations/dashboard")->result;
    }

    /**
     * @param string $lang Язык ответа API в ISO 639-1
     *
     * @return mixed parsed json
     * @throws YandexMusicException
     */
    public function rotorStationsList(string $lang = 'en'): mixed
    {
        return $this->get("/rotor/stations/list?language=$lang")->result;
    }

    /**
     * @param string $genre Жанр
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
     * @param int|string $artistId
     *
     * @return mixed parsed json
     * @throws YandexMusicException
     */
    public function artistsBriefInfo(int|string $artistId): mixed
    {
        $url = "/artists/$artistId/brief-info";

        return $this->get($url)->result;
    }

    /**
     * @param string $objectType
     * @param array|int|string $ids
     * @param bool $remove
     *
     * @return mixed parsed json
     * @throws YandexMusicException
     */
    private function likeAction(string $objectType, array|int|string $ids, bool $remove = false): mixed
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

        return $objectType == 'track' ? $response->revision : $response;
    }

    /**
     * @param array|int|string $trackIds
     * @return mixed
     * @throws YandexMusicException
     */
    public function usersLikesTracksAdd(array|int|string $trackIds): mixed
    {
        return $this->likeAction('track', $trackIds);
    }

    /**
     * @param array|int|string $trackIds
     * @return mixed
     * @throws YandexMusicException
     */
    public function usersLikesTracksRemove(array|int|string $trackIds): mixed
    {
        return $this->likeAction('track', $trackIds, true);
    }

    /**
     * @param array|int|string $artistIds
     * @return mixed
     * @throws YandexMusicException
     */
    public function usersLikesArtistsAdd(array|int|string $artistIds): mixed
    {
        return $this->likeAction('artist', $artistIds);
    }

    /**
     * @param array|int|string $artistIds
     * @return mixed
     * @throws YandexMusicException
     */
    public function usersLikesArtistsRemove(array|int|string $artistIds): mixed
    {
        return $this->likeAction('artist', $artistIds, true);
    }

    /**
     * @param array|int|string $playlistIds
     * @return mixed
     * @throws YandexMusicException
     */
    public function usersLikesPlaylistsAdd(array|int|string $playlistIds): mixed
    {
        return $this->likeAction('playlist', $playlistIds);
    }

    /**
     * @param array|int|string $playlistIds
     * @return mixed
     * @throws YandexMusicException
     */
    public function usersLikesPlaylistsRemove(array|int|string $playlistIds): mixed
    {
        return $this->likeAction('playlist', $playlistIds, true);
    }

    /**
     * @param array|int|string $albumIds
     * @return mixed
     * @throws YandexMusicException
     */
    public function usersLikesAlbumsAdd(array|int|string $albumIds): mixed
    {
        return $this->likeAction('album', $albumIds);
    }

    /**
     * @param array|int|string $albumIds
     * @return mixed
     * @throws YandexMusicException
     */
    public function usersLikesAlbumsRemove(array|int|string $albumIds): mixed
    {
        return $this->likeAction('album', $albumIds, true);
    }

    /**
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
     * @param array|int|string $artistIds
     * @return mixed
     * @throws YandexMusicException
     */
    public function artists(array|int|string $artistIds): mixed
    {
        return $this->getList('artist', $artistIds);
    }

    /**
     * @param array|int|string $albumIds
     * @return mixed
     * @throws YandexMusicException
     */
    public function albums(array|int|string $albumIds): mixed
    {
        return $this->getList('album', $albumIds);
    }

    /**
     *
     * @param array|int|string $trackIds
     * @return Track[] parsed json
     * @throws YandexMusicException
     */
    public function tracks(array|int|string $trackIds): array
    {
        return array_map(fn($value): Track => new Track($this, $value), $this->getList('track', $trackIds));
    }

    /**
     * @param array|int|string $playlistIds
     * @return mixed
     * @throws YandexMusicException
     */
    public function playlistsList(array|int|string $playlistIds): mixed
    {
        return $this->getList('playlist', $playlistIds);
    }

    /**
     * @return mixed parsed json
     * @throws YandexMusicException
     */
    public function usersPlaylistsList(): mixed
    {
        $url = sprintf(
            "/users/%s/playlists/list",
            $this->getUid()
        );

        return $this->get($url)->result;
    }

    /**
     * Получения списка лайков
     *
     * @param string $objectType track, album, artist, playlist
     *
     * @return mixed decoded json
     * @throws YandexMusicException
     */
    private function getLikes(string $objectType): mixed
    {
        $url = sprintf(
            "/users/%s/likes/%ss",
            $this->getUid(),
            $objectType
        );

        $response = $this->get($url)->result;

        return $objectType == "track" ? $response->library : $response;
    }

    /**
     * @return mixed
     * @throws YandexMusicException
     */
    public function getLikesTracks(): mixed
    {
        return $this->getLikes('track');
    }

    /**
     * @return mixed
     * @throws YandexMusicException
     */
    public function getLikesAlbums(): mixed
    {
        return $this->getLikes('album');
    }

    /**
     * @return mixed
     * @throws YandexMusicException
     */
    public function getLikesArtists(): mixed
    {
        return $this->getLikes('artist');
    }

    /**
     * @return mixed
     * @throws YandexMusicException
     */
    public function getLikesPlaylists(): mixed
    {
        return $this->getLikes('playlist');
    }

    /**
     * TODO: Описание функции
     *
     * @param int $ifModifiedSinceRevision
     *
     * @return mixed parsed json
     * @throws YandexMusicException
     */
    public function getDislikesTracks(int $ifModifiedSinceRevision = 0): mixed
    {
        $url = sprintf(
            "/users/%s/dislikes/tracks?if_modified_since_revision=%s",
            $this->getUid(),
            $ifModifiedSinceRevision
        );

        return $this->get($url)->result->library;
    }

    /**
     * @param array|int|string $ids
     * @param bool $remove
     *
     * @return mixed parsed json
     * @throws YandexMusicException
     */
    private function dislikeAction(array|int|string $ids, bool $remove = false): mixed
    {
        $action = $remove ? 'remove' : 'add-multiple';

        $url = sprintf(
            "/users/%s/dislikes/tracks/%s",
            $this->getUid(),
            $action
        );

        $data = array(
            'track-ids' => $ids
        );

        return $this->post($url, $data)->result;
    }

    /**
     * @param array|int|string $trackIds
     * @return mixed
     * @throws YandexMusicException
     */
    public function usersDislikesTracksAdd(array|int|string $trackIds): mixed
    {
        return $this->dislikeAction($trackIds);
    }

    /**
     * @param array|int|string $trackIds
     * @return mixed
     * @throws YandexMusicException
     */
    public function usersDislikesTracksRemove(array|int|string $trackIds): mixed
    {
        return $this->dislikeAction($trackIds, true);
    }

    /**
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
            array_map(fn($value): Video => new Video($this, $value), $result->videos ?? [])
        );
    }

    /**
     * @param string $url
     * @param null $data
     * @return stdClass
     * @throws YandexMusicException
     */
    private function post(string $url, $data = null): stdClass
    {
        return json_decode($this->requestYandexAPI->post($this->baseUrl . $url, $data));
    }

    /**
     * @param string $url
     * @return mixed
     * @throws YandexMusicException
     */
    private function get(string $url): stdClass
    {
        return json_decode(
            $this->requestYandexAPI->get($this->baseUrl . $url)
        );
    }
}

