<?php

namespace StounhandJ\YandexMusicApi;

class Config
{
    /**
     * @param string $token
     * @param string $client
     */
    public function __construct(
        private string $token,
        private string $client = "os=PHP; os_version=; manufacturer=ST; model=Yandex Music API; clid=; device_id=random; uuid=random"
    ) {
    }

    /**
     * @param string $token
     */
    public function updateToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @param string $client
     */
    public function updateClient(string $client): void
    {
        $this->client = $client;
    }

    public function getHeaders(): array
    {
        $headers = array(
            "X-Yandex-Music-Client: {$this->client}",
            'User-Agent: Windows 10',
            'X-Yandex-Music-Device: os=Python; os_version=; manufacturer=Stoun; model=Yandex Music API; clid=; device_id=random; uuid=random',
            'Connection: Keep-Alive'
        );
        if ($this->token != "") {
            $headers[] = "Authorization: OAuth {$this->token}";
        }
        return $headers;
    }
}