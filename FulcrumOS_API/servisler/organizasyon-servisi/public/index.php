<?php
// FulcrumOS v1.1 - Organizasyon Servisi API Giriş Noktası

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Konfigürasyon dosyasını yükle
$config = require __DIR__ . '/../config/config.php';

// PDO (Veritabanı) bağlantısını oluştur
$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset={$config['db']['charset']}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
     $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Slim uygulamasını başlat
$app = AppFactory::create();

// Middleware: Gelen JSON body'leri otomatik olarak parse et
$app->addBodyParsingMiddleware();

/**
 * Rota: GET /api/organizasyon/depolar
 * Sistemdeki tüm aktif depoları listeler.
 * Not: Bu endpoint'in önünde Gateway tarafından yetki kontrolü (örn: 'depolari_yonet' yetkisi) yapılması beklenir.
 */
$app->get('/api/organizasyon/depolar', function (Request $request, Response $response) use ($pdo) {

    // Gateway'den gelen kullanıcı bilgilerini header'lardan al (Örnek)
    // $kullanici_id = $request->getHeaderLine('X-User-ID');
    // $yetkiler = explode(',', $request->getHeaderLine('X-Permissions'));
    // if (!in_array('depolari_yonet', $yetkiler)) {
    //     $response->getBody()->write(json_encode(['hata' => 'Bu işlem için yetkiniz yok.']));
    //     return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
    // }

    try {
        $stmt = $pdo->query("SELECT id, depo_adi, adres, aktif FROM depolar WHERE aktif = 1 ORDER BY depo_adi ASC");
        $depolar = $stmt->fetchAll();

        $response->getBody()->write(json_encode($depolar));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (\PDOException $e) {
        // Gerçek bir uygulamada burada loglama yapılmalıdır.
        $response->getBody()->write(json_encode(['hata' => 'Depolar listelenirken bir veritabanı hatası oluştu.']));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// Hata yönetimi middleware'ini ekle
$app->addErrorMiddleware(true, true, true);

// Uygulamayı çalıştır
$app->run();
