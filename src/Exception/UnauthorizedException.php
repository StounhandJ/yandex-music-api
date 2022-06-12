<?php

namespace StounhandJ\YandexMusicApi\Exception;

class UnauthorizedException extends YandexMusicException
{
    protected $message = "The client is not authorized";
}