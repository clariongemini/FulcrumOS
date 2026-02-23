<?php
/**
 * FulcrumOS (v10.4) - Organizasyon Servisi
 * Mimari: Ulaş Kaşıkcı & Gemini
 * Versiyon: v1.1 (Gerçek Kodlama)
 *
 * Bu servis (Slim 4 + PDO), Depolar, Fatura Ayarları,
 * API Anahtarları gibi kurumsal verileri yönetir.
 */

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// (vlucas/phpdotenv .env yüklemesi)
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../config');
// $dotenv->load();

// --- Veritabanı Bağlantısı (PDO) ---
$db_host = getenv('DB_HOST_ORGANIZASYON') ?: 'mysql'; // Docker servis adı
$db_name = getenv('DB_NAME_ORGANIZASYON') ?: 'fulcrumos_organizasyon';
$db_user = getenv('DB_USER_ORGANIZASYON') ?: 'root';
$db_pass = getenv('DB_PASS_ORGANIZASYON') ?: getenv('DB_ROOT_PASSWORD');

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['hata' => 'Organizasyon Veritabanı bağlantısı başarısız: ' . $e->getMessage()]);
    exit;
}
// ------------------------------------

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

/**
 * 1. PUBLIC API: Aktif Depoları Listele
 * (v7.3 Admin UI'nin PO Formu için gerekli olan temel veri)
 *
 * (Not: v1.1'de henüz Gateway'den gelen X-Permissions header'ını
 * kontrol edecek bir Middleware (AuthMiddleware) bu servise eklemedik.
 * Şimdilik public olarak varsayıyoruz.)
 */
$app->get('/api/organizasyon/depolar', function (Request $request, Response $response) use ($pdo) {

    // v4.0 WMS Şeması
    $stmt = $pdo->prepare("SELECT depo_id, depo_adi, depo_kodu FROM depolar WHERE aktif_mi = 1 ORDER BY depo_adi");
    $stmt->execute();
    $depolar = $stmt->fetchAll();

    $response->getBody()->write(json_encode([
        'durum' => 'basarili',
        'veriler' => $depolar
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// (v7.3'te eklenecek olan /api/admin/organizasyon/depolar [CRUD] endpoint'leri buraya gelecek)
// (v10.1'de eklenecek olan /api/admin/organizasyon/ayarlar/watermark [CRUD] endpoint'leri buraya gelecek)
// (v10.2'de eklenecek olan /api/admin/organizasyon/ayarlar/fatura [CRUD] endpoint'leri buraya gelecek)

$app->run();
