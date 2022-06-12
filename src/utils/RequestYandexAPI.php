<?php

namespace StounhandJ\YandexMusicApi\Utils;

use SimpleXMLElement;
use StounhandJ\YandexMusicApi\Config;
use StounhandJ\YandexMusicApi\Exception\BadRequestException;
use StounhandJ\YandexMusicApi\Exception\NetworkException;
use StounhandJ\YandexMusicApi\Exception\NotFoundException;
use StounhandJ\YandexMusicApi\Exception\UnauthorizedException;

class RequestYandexAPI
{

    public function __construct(
        private Config $config
    ) {
    }

    /**
     * @throws UnauthorizedException
     * @throws NetworkException
     * @throws NotFoundException
     * @throws BadRequestException
     */
    public function post(string $url, array $data = []): string
    {
        $data = $this->dataNormalization($data);

        $headers = $this->config->getHeaders();
        if (count($data) > 0) {
            $headers[] = "Content-Type: multipart/form-data;charset=utf-8";
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        return $this->getContent($ch);
    }

    /**
     * @throws NetworkException
     * @throws NotFoundException
     * @throws BadRequestException
     * @throws UnauthorizedException
     */
    public function get(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->config->getHeaders());

        return $this->getContent($ch);
    }

    private function dataNormalization(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if ($value instanceof \stdClass) {
                $value = json_decode(json_encode($value), true);
            }
            if (is_array($value)) {
                $value = implode(",", $value);
            }
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @throws NetworkException
     * @throws NotFoundException
     * @throws BadRequestException
     * @throws UnauthorizedException
     */
    private function getContent(\CurlHandle $curlHandle): string
    {
        $response = curl_exec($curlHandle);
        $httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $error = $this->parseError($response);

        curl_close($curlHandle);

        if ($httpCode >= 200 && $httpCode <= 299) {
            return $response ?: "";
        }

        throw match ($httpCode) {
            400 => new UnauthorizedException($error),
            401, 403 => new BadRequestException($error),
            404 => new NotFoundException($error),
            409, 413 => new NetworkException($error),
            502, 503 => new NetworkException(),
            default => new NetworkException("$error $httpCode $response"),
        };
    }

    private function parseError(string $response): string
    {
        if ($response == "") {
            return "";
        }
        $response = json_decode($response);
        return isset($response->error) ? sprintf(
            "%s %s",
            $response->error->name,
            $response->error->message
        ) : "";
    }

    public function getXml($url): SimpleXMLElement|bool
    {
        return simplexml_load_file($url);
    }

    /**
     * Загрузка трека по direct url
     *
     * @param string $url Ссылка на файл
     * @param string $name Название сохраняемого файла
     * @return bool|int
     */
    public function download(string $url, string $name): bool|int
    {
        return file_put_contents($name, fopen($url, 'r'));
    }

}