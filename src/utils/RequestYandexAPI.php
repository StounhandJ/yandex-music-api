<?php

namespace StounhandJ\YandexMusicApi\Utils;

use StounhandJ\YandexMusicApi\Config;

class RequestYandexAPI
{

    public function __construct(
        private Config $config
    ) {
    }

    public function post($url, $data)
    {
//        $msg = $url;
//        if ($this->user != "") {
//            $msg .= " User: " . $this->user;
//        }
//        Logger::message($msg, "RequestYandexAPI.php", "POST");
//
//        $query = http_build_query($data);
//
//        $opts = array(
//            'http' =>
//                array(
//                    'method' => 'POST',
//                    'header' => $this->headers,
//                    'content' => $query
//                )
//        );
//        $context = stream_context_create($opts);
//
//        return file_get_contents($url, false, $context);
        return "{}";
    }

    public function get($url): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->config->getHeaders());
        $html = curl_exec($ch);
        curl_close($ch);

        return $html ?: "";
    }

    public function getXml($url)
    {
        $msg = $url;
        if ($this->user != "") {
            $msg .= " User: " . $this->user;
        }
        Logger::message($msg, "RequestYandexAPI.php", "GET_XML");

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