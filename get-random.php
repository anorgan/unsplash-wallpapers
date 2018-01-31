<?php

require __DIR__ .'/vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$photosPath = getenv('PHOTOS_PATH');

use Intervention\Image\ImageManager;

Crew\Unsplash\HttpClient::init([
    'applicationId' => getenv('UNSPLASH_APP_ID'),
    'secret'        => getenv('UNSPLASH_APP_SECRET'),
    'utmSource'     => getenv('UNSPLASH_APP_NAME'),
    'callbackUrl'   => 'urn:ietf:wg:oauth:2.0:oob',
]);

while (true) {
    $randomPhoto = Crew\Unsplash\Photo::random([
        'featured'  => true, 
        'w'         => getenv('PHOTO_WIDTH'), 
        'h'         => getenv('PHOTO_HEIGHT'),
    ]);

    $parts    = explode('/', $randomPhoto->links['self']);
    $fileName = end($parts);
    $filePath = $photosPath .'/'. $fileName .'.jpg';

    if (file_exists($filePath)) {
        continue;
    }

    $url      = $randomPhoto->urls['full'] .'&w='. getenv('PHOTO_WIDTH') .'&fit=max';
    file_put_contents($filePath, fopen($url, 'r'));

    break;
}

$manager = new ImageManager(array('driver' => 'imagick'));

$image = $manager->make($filePath);
$image->fit(getenv('PHOTO_WIDTH'), getenv('PHOTO_HEIGHT'));
$image->save();

// Delete old, boring photos
$allPhotos = (array) glob($photosPath .'/*');

foreach ($allPhotos as $photo) {
    if (strpos($photo, 'keep') !== false) {
        // we're keeping this one
        continue;
    }

    if (filemtime($photo) < strtotime('-1 week')) {
        unlink($photo);
    }
}