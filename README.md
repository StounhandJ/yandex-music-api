# YandexMusic API

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
