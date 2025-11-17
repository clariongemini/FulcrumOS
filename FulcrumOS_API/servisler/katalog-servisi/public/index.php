<?php
// FulcrumOS v1.2 - Katalog Servisi API Giriş Noktası

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
    echo json_encode(['hata' => 'Katalog Veritabanı bağlantısı başarısız: ' . $e->getMessage()]);
    exit;
}

// Slim uygulamasını başlat
$app = AppFactory::create();

// Middleware: Gelen JSON body'leri otomatik olarak parse et
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

// --- Auth Middleware (Servis İçi Yetki Kontrolü) ---
// Not: v1.2'de bu middleware prototip olarak eklenmiştir.
// Gelecekte tüm servislerde standart hale getirilecektir.
$authMiddleware = function (Request $request, $handler) {
    // $yetkiler = explode(',', $request->getHeaderLine('X-Permissions'));
    // if (!in_array('urun_yonet', $yetkiler)) {
    //     $response = new \Slim\Psr7\Response();
    //     $response->getBody()->write(json_encode(['hata' => 'Bu işlem için yetkiniz bulunmamaktadır. (urun_yonet)']));
    //     return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
    // }
    return $handler->handle($request);
};


// --- API Rotaları (/api/admin/urunler) ---
$app->group('/api/admin/urunler', function (RouteCollectorProxy $group) use ($pdo) {

    /**
     * Rota: GET /api/admin/urunler
     * Tüm ürünleri listeler.
     */
    $group->get('', function (Request $request, Response $response) use ($pdo) {
        $stmt = $pdo->query("SELECT urun_id, urun_adi, aktif_mi, marka FROM urunler ORDER BY urun_adi ASC");
        $urunler = $stmt->fetchAll();
        $response->getBody()->write(json_encode($urunler));
        return $response->withHeader('Content-Type', 'application/json');
    });

    /**
     * Rota: POST /api/admin/urunler
     * Yeni bir ürün oluşturur.
     */
    $group->post('', function (Request $request, Response $response) use ($pdo) {
        $data = $request->getParsedBody();

        $sql = "INSERT INTO urunler (urun_adi, kategori_id, aciklama, aktif_mi, meta_baslik, meta_aciklama, gtin, marka) VALUES (:urun_adi, :kategori_id, :aciklama, :aktif_mi, :meta_baslik, :meta_aciklama, :gtin, :marka)";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':urun_adi' => $data['urun_adi'] ?? null,
            ':kategori_id' => $data['kategori_id'] ?? null,
            ':aciklama' => $data['aciklama'] ?? null,
            ':aktif_mi' => $data['aktif_mi'] ?? 1,
            ':meta_baslik' => $data['meta_baslik'] ?? null,
            ':meta_aciklama' => $data['meta_aciklama'] ?? null,
            ':gtin' => $data['gtin'] ?? null,
            ':marka' => $data['marka'] ?? null
        ]);

        $lastInsertId = $pdo->lastInsertId();
        $response->getBody()->write(json_encode(['mesaj' => 'Ürün başarıyla oluşturuldu.', 'urun_id' => $lastInsertId]));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    });

    /**
     * Rota: GET /api/admin/urunler/{id}
     * Belirtilen ID'ye sahip ürünü getirir.
     */
    $group->get('/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM urunler WHERE urun_id = ?");
        $stmt->execute([$args['id']]);
        $urun = $stmt->fetch();

        if ($urun) {
            $response->getBody()->write(json_encode($urun));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['hata' => 'Ürün bulunamadı.']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    });

    /**
     * Rota: PUT /api/admin/urunler/{id}
     * Belirtilen ID'ye sahip ürünü günceller.
     */
    $group->put('/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($pdo) {
        $data = $request->getParsedBody();

        $sql = "UPDATE urunler SET urun_adi = :urun_adi, kategori_id = :kategori_id, aciklama = :aciklama, aktif_mi = :aktif_mi, meta_baslik = :meta_baslik, meta_aciklama = :meta_aciklama, gtin = :gtin, marka = :marka WHERE urun_id = :urun_id";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':urun_id' => $args['id'],
            ':urun_adi' => $data['urun_adi'] ?? null,
            ':kategori_id' => $data['kategori_id'] ?? null,
            ':aciklama' => $data['aciklama'] ?? null,
            ':aktif_mi' => $data['aktif_mi'] ?? 1,
            ':meta_baslik' => $data['meta_baslik'] ?? null,
            ':meta_aciklama' => $data['meta_aciklama'] ?? null,
            ':gtin' => $data['gtin'] ?? null,
            ':marka' => $data['marka'] ?? null
        ]);

        $response->getBody()->write(json_encode(['mesaj' => 'Ürün başarıyla güncellendi.']));
        return $response->withHeader('Content-Type', 'application/json');
    });

})->add($authMiddleware);

// Uygulamayı çalıştır
$app->run();
