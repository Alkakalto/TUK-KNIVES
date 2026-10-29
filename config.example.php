<?php
// Copy OUTSIDE the document root as ../tuk-config.php. Never store real secrets here.
return [
    'base_url' => 'https://www.example.se', // No trailing slash; root installation.
    'force_https' => true,
    'db' => ['dsn' => 'mysql:host=localhost;dbname=CPANEL_tuk;charset=utf8mb4',
             'user' => 'CPANEL_tukuser', 'password' => 'REPLACE'],
    'app_key' => 'REPLACE_WITH_64_RANDOM_HEX_CHARACTERS',
    'smtp' => ['host' => 'mail.example.se', 'port' => 587, 'encryption' => 'tls',
               'username' => 'butik@example.se', 'password' => 'REPLACE',
               'from_email' => 'butik@example.se', 'from_name' => 'TUK-KNIVES'],
];
