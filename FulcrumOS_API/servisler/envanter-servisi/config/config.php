<?php
// FulcrumOS v1.3 - Envanter Servisi Konfigürasyonu

require_once __DIR__ . '/../vendor/autoload.php';

// .env dosyasını servis kök dizininden bir üst dizinde (FulcrumOS_API/) arar.
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

return [
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? 'mysql',
        'dbname' => $_ENV['DB_NAME_ENVANTER'] ?? 'fulcrumos_envanter',
        'user' => $_ENV['DB_USER'] ?? 'fulcrum_user',
        'pass' => $_ENV['DB_PASSWORD'] ?? 'fulcrum_pass',
        'charset' => 'utf8mb4'
    ]
];
