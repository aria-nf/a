<?php
return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', 'localhost'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => env('DB_DATABASE', 'perpustakaan'),
            'username'  => env('DB_USERNAME', 'perpus_user'),
            'password'  => env('DB_PASSWORD'),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => null,
        ],
    ],
    'isbn_api_key' => env('ISBN_DB_API_KEY'),
    'google_books' => env('GOOGLE_BOOKS_API_KEY'),
];
