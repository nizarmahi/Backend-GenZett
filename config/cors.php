<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'], // Sesuaikan dengan path API Anda

    'allowed_methods' => ['*'], // Izinkan semua metode HTTP

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://20.255.60.15:3000'), // URL Frontend Anda
        // 'http://localhost:3000', // Untuk pengembangan lokal jika perlu
    ],

    'allowed_origins_patterns' => [], // Gunakan ini jika origin Anda dinamis dengan pola regex

    'allowed_headers' => ['*'], // Izinkan semua header

    'exposed_headers' => [],

    'max_age' => 0, // Durasi cache untuk preflight request dalam detik

    'supports_credentials' => false, // Set ke true jika Anda menggunakan cookies/otentikasi session lintas domain

];