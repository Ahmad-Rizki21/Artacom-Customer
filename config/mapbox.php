<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mapbox Access Token
    |--------------------------------------------------------------------------
    |
    | Mapbox memerlukan access token untuk API-nya. Masukkan token Anda di sini.
    | Dapatkan token di https://account.mapbox.com/
    |
    */
    'access_token' => env('MAPBOX_ACCESS_TOKEN', ''),
    
    /*
    |--------------------------------------------------------------------------
    | Default Map Style
    |--------------------------------------------------------------------------
    |
    | Style map yang digunakan secara default
    | Options: streets-v11, outdoors-v11, light-v10, dark-v10, satellite-v9, satellite-streets-v11
    |
    */
    'default_style' => env('MAPBOX_STYLE', 'streets-v11'),
    
    /*
    |--------------------------------------------------------------------------
    | Default Map Center
    |--------------------------------------------------------------------------
    |
    | Koordinat default untuk center map [longitude, latitude]
    |
    */
    'default_center' => [118.0149, -2.5489], // Indonesia
    
    /*
    |--------------------------------------------------------------------------
    | Default Map Zoom
    |--------------------------------------------------------------------------
    |
    | Zoom level default untuk map
    |
    */
    'default_zoom' => 5,
];