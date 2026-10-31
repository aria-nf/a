<?php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver'   => 'mysql',
            'host'     => 'localhost',
            'database' => 'perpustakaan',
            'username' => 'perpus_user',
            'password' => 'LibraryPass#2024',  // VULNERABILITY: Hardcoded credential
        ],
    ],
    // Integration credentials
    'isbn_api_key'  => 'isbndb_api_key_prod_7f3k9m2n8p',  // VULNERABILITY
    'google_books'  => 'AIzaSyGoogleBooksAPIKeySecretXYZ',  // VULNERABILITY
];
