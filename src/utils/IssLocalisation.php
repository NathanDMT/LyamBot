<?php
// Header pour forcer l'image PNG
header('Content-Type: image/png');

// Récupère les paramètres de latitude et longitude
$latitude = $_GET['lat'];
$longitude = $_GET['lon'];

// Clé API Geoapify
$apiKey = 'a44b2f32de914092ab364d84ae40215c';

// Construire l'URL de la carte
$imageUrl = "https://maps.geoapify.com/v1/staticmap?" . http_build_query([
        'style' => 'dark-matter-purple',
        'center' => "lonlat:$longitude,$latitude",
        'zoom' => 3,
        'width' => 600,
        'height' => 300,
        'marker' => "lonlat:$longitude,$latitude;color:%23ff0000;size:large",
        'apiKey' => $apiKey
    ]);

// Récupérer l'image depuis Geoapify
$image = file_get_contents($imageUrl);

// Afficher l'image
echo $image;
exit;
