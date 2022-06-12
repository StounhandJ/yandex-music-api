<?php

namespace StounhandJ\YandexMusicApi\Exception;

class UnauthorizedException extends \Exception
{
    protected $message = "The client is not authorized";
}