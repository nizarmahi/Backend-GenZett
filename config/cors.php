<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'], 

    'allowed_methods' => ['*'], 

    // 'allowed_origins' => [
    //     env('FRONTEND_URL', 'http://20.255.60.15:3000'), // URL Frontend 
    //     // 'http://localhost:3000', 
    // ],
    'allowed_origins' => [
    'https://resports.web.id', // URL Frontend 
    'https://20.255.60.15', // URL Frontend 
    'http://localhost:3000', 
],

    'allowed_origins_patterns' => [], 

    'allowed_headers' => ['*'], 

    'exposed_headers' => [],

    'max_age' => 0, // Durasi cache untuk preflight request dalam detik

    'supports_credentials' => false, // Set ke true jika Anda menggunakan cookies/otentikasi session lintas domain

];
