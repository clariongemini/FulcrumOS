<?php
// FulcrumOS v1.1 - Auth Servisi Konfigürasyonu

require_once __DIR__ . '/../vendor/autoload.php';

// .env dosyasını servis kök dizininden bir üst dizinde (FulcrumOS_API/) arar.
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

return [
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? 'mysql',
        'dbname' => $_ENV['DB_NAME_AUTH'] ?? 'fulcrumos_auth',
        'user' => $_ENV['DB_USER'] ?? 'fulcrum_user', // .env.example'da tanımlanmalı
        'pass' => $_ENV['DB_PASSWORD'] ?? 'fulcrum_pass', // .env.example'da tanımlanmalı
        'charset' => 'utf8mb4'
    ],
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET_KEY'] ?? 'BURAYA_COK_GUCLU_VE_RASTGELE_BIR_ANAHTAR_GIRIN',
        'algo' => 'HS256'
    ]
];
