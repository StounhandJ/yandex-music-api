<?php

namespace StounhandJ\YandexMusicApi\Track\Supplement;

class Supplement
{
    /**
     * @param Lyric $lyric
     * @param Video[] $videos
     */
    public function __construct
    (
        public Lyric $lyric,
        public array $videos
    ) {
    }

}