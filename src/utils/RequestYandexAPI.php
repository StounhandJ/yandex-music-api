<?php

namespace StounhandJ\YandexMusicApi\Utils;

use StounhandJ\YandexMusicApi\Config;

class RequestYandexAPI
{

    public function __construct(
        private Config $config
    ) {
    }

    public function post(string $url, array $data): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->config->getHeaders());
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response ?: "";
    }

    public function get(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->config->getHeaders());
        $response = curl_exec($ch);
        curl_close($ch);

        return $response ?: "";
    }

    private function dataToPostString(array $data): string
    {
        $result = "";
        foreach ($data as $name => $value) {
            $result .= "$name=$value";
        }
        return $result;
    }

    public function getXml($url): \SimpleXMLElement|bool
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
    public function download($url, $name)
    {
        $msg = $url;
        if ($this->user != "") {
            $msg .= " User: " . $this->user;
        }
        Logger::message($msg, "RequestYandexAPI.php", "DOWNLOAD");

        return file_put_contents(dirname(__FILE__) . '/' . $name . '.mp3', fopen($url, 'r'));
    }

}