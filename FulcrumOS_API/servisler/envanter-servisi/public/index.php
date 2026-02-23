<?php
// FulcrumOS v1.3 - Envanter Servisi Dahili API Giriş Noktası

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

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
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['hata' => 'Envanter Veritabanı bağlantısı başarısız: ' . $e->getMessage()]);
    exit;
}

// Slim uygulamasını başlat
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

// --- Dahili API Rotaları (/internal/envanter) ---
$app->group('/internal/envanter', function (RouteCollectorProxy $group) use ($pdo) {

    /**
     * Rota: GET /internal/envanter/uygun-depo-bul
     * Sipariş servisi için stok optimizasyonu yapar.
     * v4.1 Master Plan'da işlenecek.
     */
    $group->get('/uygun-depo-bul', function (Request $request, Response $response) {
        // TODO: Gelen ürün listesine göre, en az kargo maliyeti çıkaracak
        // ve stokları en verimli şekilde kullanacak depo/depoları belirle.
        // Şimdilik boş bir array döndürülüyor.
        $response->getBody()->write(json_encode([]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    /**
     * Rota: GET /internal/envanter/ai-stok-durumu
     * AI Asistan servisi için Co-Pilot verisi sağlar.
     * v10.4 Master Plan'da işlenecek.
     */
    $group->get('/ai-stok-durumu', function (Request $request, Response $response) {
        // TODO: Düşük stoklu ürünleri, en çok satanları, yavaş hareket edenleri vb.
        // analiz ederek AI Asistan için özet veri hazırla.
        // Şimdilik boş bir array döndürülüyor.
        $response->getBody()->write(json_encode([]));
        return $response->withHeader('Content-Type', 'application/json');
    });

});

// Uygulamayı çalıştır
$app->run();
