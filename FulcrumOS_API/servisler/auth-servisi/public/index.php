<?php
/**
 * FulcrumOS (v10.4) - Auth Servisi
 * Mimari: Ulaş Kaşıkcı & Gemini
 * Versiyon: v1.1 (Gerçek Kodlama)
 *
 * Bu servis (Slim 4 + PDO), kullanıcı girişi (Login) ve
 * Gateway için token doğrulama (Internal) işlemlerini yönetir.
 */

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require __DIR__ . '/../vendor/autoload.php';

// (vlucas/phpdotenv .env yüklemesi)
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../config');
// $dotenv->load();

// --- Veritabanı Bağlantısı (PDO) ---
// (Bu bilgiler config.php'den veya getenv() ile .env'den okunur)
$db_host = getenv('DB_HOST_AUTH') ?: 'mysql'; // Docker servis adı
$db_name = getenv('DB_NAME_AUTH') ?: 'fulcrumos_auth';
$db_user = getenv('DB_USER_AUTH') ?: 'root';
$db_pass = getenv('DB_PASS_AUTH') ?: getenv('DB_ROOT_PASSWORD');
$jwt_secret = getenv('JWT_SECRET_KEY') ?: 'COK_GUCLU_BIR_ANAHTAR_GIRILMELI';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    // Servis başlayamazsa kritik hata ver
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['hata' => 'Auth Veritabanı bağlantısı başarısız: ' . $e->getMessage()]);
    exit;
}
// ------------------------------------

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

/**
 * 1. PUBLIC API: Kullanıcı Girişi
 * (v2.5 Master Plan)
 */
$app->post('/api/kullanici/giris', function (Request $request, Response $response) use ($pdo, $jwt_secret) {
    $data = $request->getParsedBody();

    if (empty($data['eposta']) || empty($data['parola'])) {
        $response->getBody()->write(json_encode(['hata' => 'E-posta ve parola zorunludur.']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // 1. Kullanıcıyı bul
    $stmt = $pdo->prepare("SELECT * FROM kullanicilar WHERE eposta = ? AND aktif_mi = 1");
    $stmt->execute([$data['eposta']]);
    $kullanici = $stmt->fetch();

    // 2. Parolayı doğrula
    if (!$kullanici || !password_verify($data['parola'], $kullanici['parola'])) {
        $response->getBody()->write(json_encode(['hata' => 'E-posta veya parola geçersiz.']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    // 3. JWT Üret (v5.0 Master Plan - Gateway'in ihtiyaç duyacağı tüm veriler)
    $iat = time();
    $exp = $iat + 3600; // 1 saat geçerlilik
    $rol_id = $kullanici['rol_id'];

    // 3a. Yetkileri Çek (v2.5 ACL)
    $stmt = $pdo->prepare("
        SELECT y.yetki_kodu
        FROM yetkiler y
        JOIN rol_yetki_iliskisi ryi ON y.yetki_id = ryi.yetki_id
        WHERE ryi.rol_id = ?
    ");
    $stmt->execute([$rol_id]);
    $yetkiler_raw = $stmt->fetchAll();
    $yetkiler = array_column($yetkiler_raw, 'yetki_kodu');

    // 3b. Rol Bilgilerini Çek (v2.7 B2B Fiyatlandırma)
    $stmt = $pdo->prepare("SELECT rol_adi, fiyat_listesi_id FROM roller WHERE rol_id = ?");
    $stmt->execute([$rol_id]);
    $rol = $stmt->fetch();

    $payload = [
        'iss' => 'FulcrumOS-Auth-Servisi',
        'aud' => 'FulcrumOS-Platformu',
        'iat' => $iat,
        'exp' => $exp,
        'sub' => $kullanici['kullanici_id'],
        'data' => [
            'kullanici_id' => (int)$kullanici['kullanici_id'],
            'rol' => $rol['rol_adi'],
            'fiyat_listesi_id' => (int)$rol['fiyat_listesi_id'],
            'yetkiler' => $yetkiler
        ]
    ];

    $token = JWT::encode($payload, $jwt_secret, 'HS256');

    $response->getBody()->write(json_encode([
        'durum' => 'basarili',
        'token' => $token
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * 2. INTERNAL API: Token Doğrulama (Gateway için)
 * (v5.0 Master Plan)
 */
$app->get('/internal/auth/dogrula', function (Request $request, Response $response) use ($jwt_secret) {
    $authHeader = $request->getHeaderLine('Authorization');
    $token = str_replace('Bearer ', '', $authHeader);

    if (empty($token)) {
        $response->getBody()->write(json_encode(['hata' => 'Internal: Token eksik.']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    try {
        $decoded = JWT::decode($token, new Key($jwt_secret, 'HS256'));

        // Token geçerli. Token'daki 'data' payload'ını Gateway'e geri döndür.
        $response->getBody()->write(json_encode([
            'durum' => 'gecerli',
            'data' => $decoded->data
        ]));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (Exception $e) {
        // Token geçersiz (süresi dolmuş, imza yanlış vb.)
        $response->getBody()->write(json_encode(['hata' => 'Internal: Token gecersiz. ' . $e->getMessage()]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
});

$app->run();
