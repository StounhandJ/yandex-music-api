# YandexMusic API

<p align="center">
<a href="https://packagist.org/packages/stounhandj/yandex-music-api"><img src="https://img.shields.io/packagist/dt/stounhandj/yandex-music-api" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/stounhandj/yandex-music-api"><img src="https://img.shields.io/packagist/v/stounhandj/yandex-music-api" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/stounhandj/yandex-music-api"><img src="https://img.shields.io/packagist/l/stounhandj/yandex-music-api" alt="License"></a>
</p>

### Not an official package for working with the Yandex API.
## Installation

```
$ composer require stounhandj/yandex-music-api
```
Or
```json
{
    "require": {
        "stounhandj/yandex-music-api": "^0.5.2"
    }
}
```
## Example get  likes tracks

```php
use StounhandJ\YandexMusicApi\Client;

$token = "AQAAAAANDd5rMAG1XnIKIEVVMGV4ibf8kw3FeA1";
$client = new Client($token);

foreach ($client->getLikesTracks() as $track){
    echo $track->title;
}
```

## Example of downloading the first track from the album you like

```php
use StounhandJ\YandexMusicApi\Client;

$token = "AQAAAAANDd5rMAG1XnIKIEVVMGV4ibf8kw3FeA1";
$client = new Client($token);

$likesAlbum = $client->getLikesAlbums()[0];
$firstTrack = $likesAlbum->getTracks()[0];
$result = $firstTrack->downloadTrack("test.mp3");
```
