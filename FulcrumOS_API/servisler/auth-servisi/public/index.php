<?php
// FulcrumOS v1.1 - Auth Servisi API Giriş Noktası

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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
 * Rota: POST /api/kullanici/giris
 * Kullanıcı e-posta ve parolası ile giriş yapar, başarılı ise JWT döndürür.
 */
$app->post('/api/kullanici/giris', function (Request $request, Response $response) use ($pdo, $config) {
    $data = $request->getParsedBody();
    $eposta = $data['eposta'] ?? null;
    $parola = $data['parola'] ?? null;

    if (!$eposta || !$parola) {
        $response->getBody()->write(json_encode(['hata' => 'E-posta ve parola alanları zorunludur.']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $stmt = $pdo->prepare("SELECT id, parola FROM kullanicilar WHERE eposta = ? AND aktif = 1");
    $stmt->execute([$eposta]);
    $kullanici = $stmt->fetch();

    if ($kullanici && password_verify($parola, $kullanici['parola'])) {
        $secretKey = $config['jwt']['secret'];
        $issuer_claim = "fulcrumos_auth_servisi";
        $audience_claim = "fulcrumos_gateway";
        $issuedat_claim = time();
        $expire_claim = $issuedat_claim + 3600; // 1 saat geçerli

        $payload = [
            'iss' => $issuer_claim,
            'aud' => $audience_claim,
            'iat' => $issuedat_claim,
            'exp' => $expire_claim,
            'data' => [
                'kullanici_id' => $kullanici['id']
            ]
        ];

        $token = JWT::encode($payload, $secretKey, $config['jwt']['algo']);

        $response->getBody()->write(json_encode(['token' => $token]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $response->getBody()->write(json_encode(['hata' => 'Geçersiz kimlik bilgileri.']));
    return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
});


/**
 * Rota: GET /internal/auth/dogrula
 * Gateway tarafından gönderilen JWT'yi doğrular ve kullanıcı bilgilerini döndürür.
 */
$app->get('/internal/auth/dogrula', function (Request $request, Response $response) use ($pdo, $config) {
    $authHeader = $request->getHeaderLine('Authorization');
    $token = str_replace('Bearer ', '', $authHeader);

    if (empty($token)) {
        $response->getBody()->write(json_encode(['hata' => 'Token bulunamadı.']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    try {
        $decoded = JWT::decode($token, new Key($config['jwt']['secret'], $config['jwt']['algo']));
        $kullanici_id = $decoded->data->kullanici_id;

        // Kullanıcının rolünü ve yetkilerini veritabanından çek
        $sql = "SELECT
                    k.id AS kullanici_id,
                    r.rol_adi,
                    GROUP_CONCAT(y.yetki_kodu) AS yetkiler
                FROM kullanicilar k
                JOIN roller r ON k.rol_id = r.id
                LEFT JOIN rol_yetki_iliskisi ryi ON r.id = ryi.rol_id
                LEFT JOIN yetkiler y ON ryi.yetki_id = y.id
                WHERE k.id = ?
                GROUP BY k.id, r.rol_adi";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$kullanici_id]);
        $kullanici_detaylari = $stmt->fetch();

        if (!$kullanici_detaylari) {
             $response->getBody()->write(json_encode(['hata' => 'Token geçerli ancak kullanıcı bulunamadı.']));
             return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Yetkileri bir diziye çevir
        $kullanici_detaylari['yetkiler'] = $kullanici_detaylari['yetkiler'] ? explode(',', $kullanici_detaylari['yetkiler']) : [];

        $response->getBody()->write(json_encode($kullanici_detaylari));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['hata' => 'Geçersiz veya süresi dolmuş token.', 'detay' => $e->getMessage()]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
});


// Hata yönetimi middleware'ini ekle
$app->addErrorMiddleware(true, true, true);

// Uygulamayı çalıştır
$app->run();
