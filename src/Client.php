<?php

namespace StounhandJ\YandexMusicApi;

use DateTime;
use DateTimeInterface;
use Exception;
use StounhandJ\YandexMusicApi\Queue\QueueItem;
use StounhandJ\YandexMusicApi\Utils\RequestYandexAPI;

class Client
{
    public Config $config;
    private RequestYandexAPI $requestYandexAPI;
    private int $uid = -1;

    /**
     * Client constructor.
     * @param string $token
     * @param string $oauthUrl
     * @param string $baseUrl
     */
    public function __construct(
        string $token,
        public string $oauthUrl = "https://oauth.yandex.ru",
        public string $baseUrl = "https://api.music.yandex.net"
    ) {
        $this->config = new Config($token);
        $this->requestYandexAPI = new RequestYandexAPI($this->config);
    }

    public function getUid(): int
    {
        if ($this->uid != -1) {
            return $this->uid;
        }

        $this->uid = $this->account_status()->account->uid;
        return $this->uid;
    }

    public function account_settings()
    {
        return $this->get($this->baseUrl . "/account/settings");
    }

    /**
     * @return array decoded json
     */
    public function queues_list(): array
    {
        $url = $this->baseUrl . "/queues";

        return Queue\QueueItem::de_list($this->get($url)->result["queues"], $this);
    }

    /**
     * @return Queue\Queue decoded json
     */
    public function queue($id): Queue\Queue
    {
        $url = $this->baseUrl . "/queues/" . $id;

        return Queue\Queue::de_json($this->get($url)->result, $this);
    }

    /**
     * Получение статуса аккаунта
     *
     * @return mixed decoded json
     */
    public function account_status(): mixed
    {
        $url = $this->baseUrl . "/account/status";

        return $this->get($url)->result;
    }

    public function rotor_account_status(): mixed
    {
        $url = $this->baseUrl . "/rotor/account/status";

        return $this->get($url)->result;
    }

    /**
     * Получение оповещений
     *
     * @return array decoded json
     */
    public function permission_alerts(): array
    {
        $url = $this->baseUrl . "/permission-alerts";

        return $this->get($url)->result;
    }

    /**
     * Получение значений экспериментальных функций аккаунта
     *
     * @return array decoded json
     */
    public function account_experiments(): array
    {
        $url = $this->baseUrl . "/account/experiments";

        return $this->get($url)->result;
    }

    /**
     * Получение потока информации (фида) подобранного под пользователя.
     * Содержит умные плейлисты.
     *
     * @return array decoded json
     */
    public function feed(): array
    {
        $url = $this->baseUrl . "/feed";

        return $this->get($url)->result;
    }

    public function feed_wizard_is_passed(): bool
    {
        $url = $this->baseUrl . "/feed/wizard/is-passed";

        return $this->get($url)->result["isWizardPassed"] ?? false;
    }

    /**
     * Получение лендинг-страницы содержащий блоки с новыми релизами,
     * чартами, плейлистами с новинками и т.д.
     *
     * Поддерживаемые типы блоков: personalplaylists, promotions, new-releases, new-playlists,
     * mixes, chart, artists, albums, playlists, play_contexts.
     *
     * @param array|string $blocks
     *
     * @return mixed parsed json
     */
    public function landing(array|string $blocks): array
    {
        $url = $this->baseUrl . "/landing3?blocks=";

        if (is_array($blocks)) {
            $url .= implode(',', $blocks);
        } else {
            $url .= $blocks;
        }

        $response = $this->get($url);
        if ($response->result == null) {
            $response = $response["error"];
        } else {
            $response = $response->result;
        }

        return $response;
    }

    /**
     * Получение жанров музыки
     *
     * @return array parsed json
     */
    public function genres(): array
    {
        $url = $this->baseUrl . "/genres";

        return $this->get($url)->result;
    }

    /**
     * Получение информации о доступных вариантах загрузки трека
     *
     * @param int|string $trackId Уникальный идентификатор трека
     * @param bool $getDirectLinks Получить ли при вызове метода прямую ссылку на загрузку
     *
     * @return mixed parsed json
     */
    public function tracks_download_info(int|string $trackId, bool $getDirectLinks = false): mixed
    {
        $result = array();
        $url = $this->baseUrl . "/tracks/$trackId/download-info";

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
     * TODO: перенести загрузку файла в другую функию
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
     * TODO: метод не был протестирован!
     *
     * @param string|int $trackId Уникальный идентификатор трека
     * @param string $from Наименования клиента
     * @param string|int $albumId Уникальный идентификатор альбома
     * @param int $playlistId Уникальный идентификатор плейлиста, если таковой прослушивается.
     * @param bool $fromCache Проигрывается ли трек с кеша
     * @param string $playId Уникальный идентификатор проигрывания
     * @param int $trackLengthSeconds Продолжительность трека в секундах
     * @param int $totalPlayedSeconds Сколько было всего воспроизведено трека в секундах
     * @param int $endPositionSeconds Окончательное значение воспроизведенных секунд
     * @param string $client_now Текущая дата и время клиента в ISO
     *
     * @return boolean
     *
     * @throws Exception
     */
    private function playAudio(
        $trackId,
        $from,
        $albumId,
        $playlistId = null,
        $fromCache = false,
        $playId = null,
        $trackLengthSeconds = 0,
        $totalPlayedSeconds = 0,
        $endPositionSeconds = 0,
        $client_now = null
    ) {
        $url = $this->baseUrl . "/play-audio";

        $data = array(
            'track-id' => $trackId,
            'from-cache' => $fromCache,
            'from' => $from,
            'play-id' => $playId,
            'uid' => $this->account->uid,
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
     */
    public function albums_with_tracks(int|string $albumId): mixed
    {
        $url = $this->baseUrl . "/albums/$albumId/with-tracks";

        return $this->get($url)->result;
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
     */
    public function search_suggest(string $part): mixed
    {
        $url = $this->baseUrl . "/search/suggest?part=$part";

        return $this->get($url)->result;
    }

    /**
     * Получение плейлиста или списка плейлистов по уникальным идентификаторам
     *
     * TODO: метод не был протестирован!
     *
     * @param string|int|array $kind Уникальный идентификатор плейлиста
     * @param int $userId Уникальный идентификатор пользователя владеющего плейлистом
     *
     * @return mixed parsed json
     */
//    public function users_playlists($kind, $userId = null)
//    {
//        if ($userId == null) {
//            $userId = $this->account->uid;
//        }
//
//        $url = $this->baseUrl . "/users/$userId/playlists";
//
//        $data = array(
//            'kind' => $kind
//        );
//
//        return $this->post($url, $data);
//    }

    /**
     * Создание плейлиста
     *
     * @param string $title Название
     * @param string $visibility Модификатор доступа
     *
     * @return mixed parsed json
     */
//    public function users_playlists_create($title, $visibility = 'public')
//    {
//        $url = $this->baseUrl . "/users/" . $this->account->uid . "/playlists/create";
//
//        $data = array(
//            'title' => $title,
//            'visibility' => $visibility
//        );
//
//        return $this->post($url, $data)->result;
//    }

    /**
     * Удаление плейлиста
     *
     * @param int|string $kind Уникальный идентификатор плейлиста
     *
     * @return mixed decoded json
     */
//    public function users_playlists_delete(int|string $kind): mixed
//    {
//        $url = $this->baseUrl . "/users/" . $this->account->uid . "/playlists/$kind/delete";
//
//        return $this->post($url)->result;
//    }

    /**
     * Изменение названия плейлиста
     *
     * @param string|int $kind Уникальный идентификатор плейлиста
     * @param string $name Новое название
     *
     * @return mixed decoded json
     */
//    public function users_playlists_name_change($kind, $name): mixed
//    {
//        $url = $this->baseUrl . "/users/" . $this->account->uid . "/playlists/$kind/name";
//
//        $data = array(
//            'value' => $name
//        );
//
//        return $this->post($url, $data)->result;
//    }

    /**
     * Изменение плейлиста.
     *
     * TODO: функция не готова, необходим вспомогательный класс для получения отличий
     *
     * @param int|string $kind Уникальный идентификатор плейлиста
     * @param string $diff JSON представления отличий старого и нового плейлиста
     * @param int $revision TODO
     *
     * @return mixed parsed json
     */
//    private function users_playlists_change(int|string $kind, string $diff, int $revision = 1): mixed
//    {
//        $url = $this->baseUrl . "/users/" . $this->account->uid . "/playlists/$kind/change";
//
//        $data = array(
//            'kind' => $kind,
//            'revision' => $revision,
//            'diff' => $diff
//        );
//
//        return $this->post($url, $data)->result;
//    }

    /**
     * Добавление трека в плейлист
     *
     * TODO: функция не готова, необходим вспомогательный класс для получения отличий
     *
     * @param int|string $kind Уникальный идентификатор плейлиста
     * @param int|string $trackId Уникальный идентификатор трека
     * @param int|string $albumId Уникальный идентификатор альбома
     * @param int $at Индекс для вставки
     * @param int|null $revision TODO
     *
     * @return mixed parsed json
     */
//    public function usersp_playlists_insert_track(
//        int|string $kind,
//        int|string $trackId,
//        int|string $albumId,
//        int $at = 0,
//        int $revision = null): mixed
//    {
//        if ($revision == null) {
//            $revision = $this->usersPlaylists($kind)->result[0]->revision;
//        }
//
//        $ops = json_encode(array(
//            [
//                'op' => "insert",
//                'at' => $at,
//                'tracks' => [['id' => $trackId, 'albumId' => $albumId]]
//            ]
//        ));
//
//        return $this->usersPlaylistsChange($kind, $ops, $revision);
//    }


    /**
     * TODO: Описание функции
     *
     * TODO: метод не был протестирован!
     *
     * @return mixed parsed json
     */
    public function rotor_stations_dashboard(): mixed
    {
        return $this->get($this->baseUrl . "/rotor/stations/dashboard")->result;
    }

    /**
     * TODO: Описание функции
     *
     * TODO: метод не был протестирован!
     *
     * @param string $lang Язык ответа API в ISO 639-1
     *
     * @return mixed parsed json
     */
    public function rotor_stations_list(string $lang = 'en'): mixed
    {
        $url = $this->baseUrl . "/rotor/stations/list?language=" . $lang;

        return $this->get($url)->result;
    }

    /**
     * TODO: Описание функции
     *
     * TODO: метод не был протестирован!
     *
     * @param string $genre Жанр
     * @param string $type
     * @param string|null $from
     * @param int|string|null $batchId
     * @param string|null $trackId
     *
     * @return mixed parsed json
     *
     * @throws Exception
     */
//    public function rotorStationGenreFeedback(
//        string $genre,
//        string $type,
//        string $from = null,
//        int|string $batchId = null,
//        string $trackId = null): mixed
//    {
//        $url = $this->baseUrl . "/rotor/station/genre:$genre/feedback";
//        if ($batchId != null) {
//            $url .= "?batch-id=" . $batchId;
//        }
//
//        $data = array(
//            'type' => $type,
//            'timestamp' => (new DateTime())->format(DateTimeInterface::ATOM)
//        );
//        if ($from != null) {
//            $data['from'] = $from;
//        }
//        if ($trackId != null) {
//            $data['trackId'] = $trackId;
//        }
//
//        return $this->post($url, $data)->result;
//    }

    /**
     * TODO: Описание функции
     *
     * TODO: метод не был протестирован!
     *
     * @param string|int $artistId
     *
     * @return mixed parsed json
     */
    public function artistsBriefInfo($artistId)
    {
        $url = $this->baseUrl . "/artists/$artistId/brief-info";

        return $this->get($url)->result;
    }

    /**
     * TODO: Описание функции
     *
     * TODO: метод не был протестирован!
     *
     * @param string $objectType
     * @param string|int|array $ids
     * @param bool $remove
     *
     * @return mixed parsed json
     */
    private function likeAction($objectType, $ids, $remove = false)
    {
        $action = 'add-multiple';
        if ($remove) {
            $action = 'remove';
        }
        $url = $this->baseUrl . "/users/" . $this->account->uid . "/likes/" . $objectType . "s/$action";

        $data = array(
            $objectType . '-ids' => $ids
        );

        $response = $this->post($url, $data)->result;

        if ($objectType == 'track') {
            $response = $response->revision;
        }

        return $response;
    }

    public function usersLikesTracksAdd($trackIds)
    {
        return $this->likeAction('track', $trackIds);
    }

    public function usersLikesTracksRemove($trackIds)
    {
        return $this->likeAction('track', $trackIds, true);
    }

    public function usersLikesArtistsAdd($artistIds)
    {
        return $this->likeAction('artist', $artistIds);
    }

    public function usersLikesArtistsRemove($artistIds)
    {
        return $this->likeAction('artist', $artistIds, true);
    }

    public function usersLikesPlaylistsAdd($playlistIds)
    {
        return $this->likeAction('playlist', $playlistIds);
    }

    public function usersLikesPlaylistsRemove($playlistIds)
    {
        return $this->likeAction('playlist', $playlistIds, true);
    }

    public function usersLikesAlbumsAdd($albumIds)
    {
        return $this->likeAction('album', $albumIds);
    }

    public function usersLikesAlbumsRemove($albumIds)
    {
        return $this->likeAction('album', $albumIds, true);
    }

    /**
     * TODO: Описание функции
     *
     * TODO: метод не был протестирован!
     *
     * @param string $objectType
     * @param array|int|string $ids
     *
     * @return mixed parsed json
     */
//    private function getList(string $objectType, array|int|string $ids): mixed
//    {
//        $url = $this->baseUrl . "/" . $objectType . "s";
//        if ($objectType == 'playlist') {
//            $url .= "/list";
//        }
//
//        $data = array(
//            $objectType . '-ids' => $ids
//        );
//
//        return $this->post($url, $data)->result;
//    }
//
//    public function artists($artistIds)
//    {
//        return $this->getList('artist', $artistIds);
//    }
//
//    public function albums($albumIds)
//    {
//        return $this->getList('album', $albumIds);
//    }
//
//    public function tracks($trackIds)
//    {
//        return $this->getList('track', $trackIds);
//    }
//
//    public function playlistsList($playlistIds)
//    {
//        return $this->getList('playlist', $playlistIds);
//    }

    /**
     * TODO: Описание функции
     *
     * TODO: метод не был протестирован!
     *
     * @return mixed parsed json
     */
    public function usersPlaylistsList(): mixed
    {
        $url = $this->baseUrl . "/users/" . $this->getUid() . "/playlists/list";

        return $this->get($url)->result;
    }

    /**
     * Получения списка лайков
     *
     * @param string $objectType track, album, artist, playlist
     *
     * @return mixed decoded json
     */
    private function getLikes(string $objectType): mixed
    {
        $url = $this->baseUrl . "/users/" . $this->getUid() . "/likes/" . $objectType . "s";

        $response = $this->get($url)->result;

        if ($objectType == "track") {
            return $response->library;
        }

        return $response;
    }

    public function getLikesTracks()
    {
        return $this->getLikes('track');
    }

    public function getLikesAlbums()
    {
        return $this->getLikes('album');
    }

    public function getLikesArtists()
    {
        return $this->getLikes('artist');
    }

    public function getLikesPlaylists()
    {
        return $this->getLikes('playlist');
    }

    /**
     * TODO: Описание функции
     *
     * @param int $ifModifiedSinceRevision
     *
     * @return mixed parsed json
     */
    public function getDislikesTracks(int $ifModifiedSinceRevision = 0): mixed
    {
        $url = $this->baseUrl . "/users/" . $this->getUid() . "/dislikes/tracks"
            . '?if_modified_since_revision=' . $ifModifiedSinceRevision;

        return $this->get($url)->result->library;
    }

    /**
     * TODO: Описание функции
     *
     * TODO: метод не был протестирован!
     *
     * @param array|int|string $ids
     * @param bool $remove
     *
     * @return mixed parsed json
     */
    private function dislikeAction(array|int|string $ids, bool $remove = false): mixed
    {
        $action = $remove ? 'remove' : 'add-multiple';

        $url = sprintf(
            "%s/users/%s/dislikes/tracks/%s",
            $this->baseUrl,
            $this->getUid(),
            $action
        );

        $data = array(
            'track-ids' => $ids
        );

        return $this->post($url, $data)->result;
    }

    public function usersDislikesTracksAdd(array|int|string $trackIds)
    {
        return $this->dislikeAction($trackIds);
    }

    public function usersDislikesTracksRemove(array|int|string $trackIds)
    {
        return $this->dislikeAction($trackIds, true);
    }

    private function post($url, $data = null): mixed
    {
        return json_decode($this->requestYandexAPI->post($url, $data));
    }

    private function get($url): mixed
    {
        return json_decode($this->requestYandexAPI->get($url));
    }
}

