<?php
// FulcrumOS v1.1 - Gateway Servisi Konfigürasyonu

require_once __DIR__ . '/../vendor/autoload.php';

// .env dosyasını servis kök dizininden bir üst dizinde (FulcrumOS_API/) arar.
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Servislerin Docker içindeki internal adresleri
// Docker Compose'daki servis adlarıyla eşleşmelidir.
return [
    'servisler' => [
        'auth' => 'http://auth-servisi',
        'katalog' => 'http://katalog-servisi',
        'siparis' => 'http://siparis-servisi',
        'organizasyon' => 'http://organizasyon-servisi'
        // Diğer tüm servisler buraya eklenecek...
    ]
];
