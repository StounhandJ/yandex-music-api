<?php

namespace StounhandJ\YandexMusicApi\Models\Track;

use stdClass;
use StounhandJ\YandexMusicApi\Client;
use StounhandJ\YandexMusicApi\Models\JSONObject;

class TracksDownloadInfo extends JSONObject
{
    public string $codec;
    public bool $gain;
    public bool $preview;
    public string $downloadInfoUrl;
    protected string $directLink;
    public bool $direct;
    public int $bitrateInKbps;

    /**
     * Getting a download link
     *
     * @return string
     */
    public function getDownloadLink(): string
    {
        if (!isset($this->directLink))
        {
            $this->directLink = $this->client->getDirectLink($this->downloadInfoUrl);
        }

        return $this->directLink;
    }

    /**
     * File Download
     *
     * @param string $name Name or path of the saved file
     * @return bool|int
     */
    public function download(string $name): bool|int
    {
        return $this->client->download($this->getDownloadLink(), $name);
    }

    /**
     * @param Client $client
     * @param array|stdClass $json
     * @return TracksDownloadInfo[]
     */
    public static function deList(Client $client, array|stdClass $json): array
    {
        return array_map(fn($value): TracksDownloadInfo => new TracksDownloadInfo($client, $value), $json);
    }
}