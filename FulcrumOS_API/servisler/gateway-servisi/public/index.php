<?php
/**
 * FulcrumOS (v10.4) - API Gateway Servisi
 * Mimari: Ulaş Kaşıkcı & Gemini
 * Versiyon: v1.1 (Gerçek Kodlama)
 *
 * Bu servis (Slim 4 + Guzzle) tüm dış trafiği karşılar,
 * Auth-Servisi'ne doğrulama yaptırır ve isteği ilgili
 * backend mikroservisine yönlendirir (proxy).
 */

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// 1. Composer Autoloader'ı Yükle
require __DIR__ . '/../vendor/autoload.php';

// (vlucas/phpdotenv .env yüklemesi)
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
// $dotenv->load();

$app = AppFactory::create();

// --- 2. MERKEZİ KİMLİK DOĞRULAMA (AuthMiddleware) ---
// (v5.0 Master Plan)
$authMiddleware = function (Request $request, RequestHandler $handler): Response {

    // Public (Halka Açık) Yolları Es Geç
    $public_routes = ['/api/kullanici/giris', '/api/urunler', '/api/sayfa/'];
    $path = $request->getUri()->getPath();

    foreach ($public_routes as $route) {
        if (strpos($path, $route) === 0) {
            return $handler->handle($request); // Doğrulama yapma, devam et
        }
    }

    // Token'ı al
    $authHeader = $request->getHeaderLine('Authorization');
    $token = str_replace('Bearer ', '', $authHeader);

    if (empty($token)) {
        // Token yoksa 401 hatası ver
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['hata' => 'Yetkilendirme basarisiz: Token eksik.']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    // --- Dahili (Internal) API Çağrısı ---
    // Gateway, token'ı doğrulamak için Auth-Servisi'ne (Docker ağındaki) sorar
    $auth_service_url = 'http://auth-servisi'; // v9.2 Docker Servis Keşfi

    try {
        $client = new Client();
        $authResponse = $client->request('GET', $auth_service_url . '/internal/auth/dogrula', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ]
        ]);

        $authData = json_decode($authResponse->getBody()->getContents(), true);

        // v5.0 Master Plan: Güvenli Header'ları Ekle
        // Bu header'lar, arkadaki servislere (Katalog, Sipariş vb.) kimin istek yaptığını söyler.
        $request = $request
            ->withHeader('X-User-ID', $authData['data']['kullanici_id'])
            ->withHeader('X-Role', $authData['data']['rol'])
            ->withHeader('X-Permissions', implode(',', $authData['data']['yetkiler']))
            // v2.7 B2B Fiyatlandırma
            ->withHeader('X-PriceList-ID', $authData['data']['fiyat_listesi_id'] ?? 1);

    } catch (RequestException $e) {
        // Auth-Servisi '401 Geçersiz Token' dediyse, onu geri yansıt
        $response = new \Slim\Psr7\Response();
        if ($e->hasResponse()) {
            $responseBody = $e->getResponse()->getBody();
            $response->getBody()->write($responseBody);
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode(['hata' => 'Auth Servisi\'ne ulasilamiyor.']));
        return $response->withStatus(503)->withHeader('Content-Type', 'application/json');
    }

    return $handler->handle($request);
};

// Middleware'i tüm /api/admin/ yollarına uygula (veya tüm yollara uygulayıp public'leri ayıkla)
$app->add($authMiddleware);

// --- 3. YÖNLENDİRME (ROUTING / PROXY) ---
// (v1.1 Teknik Şartname - Manuel Yönlendirme Haritası)

$routing_map = [
    // Auth (v1.1)
    '/api/kullanici/giris' => 'http://auth-servisi',
    // Organizasyon (v1.1)
    '/api/organizasyon/depolar' => 'http://organizasyon-servisi',
    // Katalog (v1.2)
    '/api/admin/urunler' => 'http://katalog-servisi',
    // Sipariş (Gelecek v1.x)
    '/api/admin/siparisler' => 'http://siparis-servisi',
    // ... (Master Plan'daki tüm 100+ endpoint burada eşleştirilir)
];

// Tüm istekleri yakalayan ana rota
$app->any('/api/{proxy:.*}', function (Request $request, Response $response, array $args) use ($routing_map) {

    $path = '/api/' . $args['proxy'];

    // 1. Manuel Haritadan hedef servisi bul
    // (Daha gelişmiş bir sistem, 'path'i analiz ederek hedefi bulur)
    $target_service_url = null;
    foreach ($routing_map as $route_prefix => $service_url) {
        if (strpos($path, $route_prefix) === 0) {
            $target_service_url = $service_url;
            break;
        }
    }

    if ($target_service_url === null) {
        $response->getBody()->write(json_encode(['hata' => 'Gateway: Rota bulunamadi.']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    // 2. Guzzle ile isteği hedef servise "Proxy"le
    $client = new Client(['timeout' => 5.0]);
    $target_path = $target_service_url . $path; // Örn: http://katalog-servisi/api/admin/urunler

    try {
        // Orijinal isteğin Metodunu, Body'sini ve (AuthMiddleware tarafından eklenen) Header'larını al
        $proxy_response = $client->request($request->getMethod(), $target_path, [
            'headers' => $request->getHeaders(), // X-User-ID vb. içerir
            'body' => $request->getBody()
        ]);

        // Hedef servisten gelen yanıtı al
        $response->getBody()->write($proxy_response->getBody()->getContents());
        return $response
            ->withStatus($proxy_response->getStatusCode())
            ->withHeader('Content-Type', $proxy_response->getHeaderLine('Content-Type'));

    } catch (RequestException $e) {
        // Eğer hedef servis (Katalog vb.) çöktüyse veya hata verdiyse
        $responseBody = $e->hasResponse() ? $e->getResponse()->getBody() : json_encode(['hata' => 'Servis erisilemiyor: ' . $target_service_url]);
        $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 503;

        $response->getBody()->write($responseBody);
        return $response->withStatus($statusCode)->withHeader('Content-Type', 'application/json');
    }
});

$app->run();
