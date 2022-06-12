<?php

namespace StounhandJ\YandexMusicApi\Exception;

class NetworkException extends YandexMusicException
{
    protected $message = "Bad Gateway";
}