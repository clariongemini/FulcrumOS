<?php
// FulcrumOS v1.1 - Gateway Servisi API Giriş Noktası

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Konfigürasyon dosyasını ve servis adreslerini yükle
$config = require __DIR__ . '/../config/config.php';
$servisler = $config['servisler'];

// Guzzle HTTP istemcisini oluştur (servisler arası iletişim için)
$guzzleClient = new Client();

// Slim uygulamasını başlat
$app = AppFactory::create();

// --- AuthMiddleware Tanımı ---
$authMiddleware = function (Request $request, RequestHandler $handler) use ($guzzleClient, $servisler) {
    $uri = $request->getUri()->getPath();

    // Herkese açık yolları (whitelist) bu middleware'den muaf tut
    $publicRoutes = ['/api/kullanici/giris'];
    if (in_array($uri, $publicRoutes)) {
        return $handler->handle($request);
    }

    // Authorization header'ını al
    $authHeader = $request->getHeaderLine('Authorization');
    if (empty($authHeader)) {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['hata' => 'Kimlik doğrulama token\'ı gerekli.']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    try {
        // Auth servisine token'ı doğrulaması için internal istek at
        $authResponse = $guzzleClient->request('GET', $servisler['auth'] . '/internal/auth/dogrula', [
            'headers' => [
                'Authorization' => $authHeader
            ]
        ]);

        $authBody = json_decode($authResponse->getBody()->getContents(), true);

        // Doğrulanmış kullanıcı bilgilerini isteğe yeni header olarak ekle
        $request = $request
            ->withHeader('X-User-ID', $authBody['kullanici_id'])
            ->withHeader('X-User-Role', $authBody['rol_adi'])
            ->withHeader('X-Permissions', implode(',', $authBody['yetkiler']));

        return $handler->handle($request);

    } catch (RequestException $e) {
        $response = new \Slim\Psr7\Response();
        // Auth servisinden gelen hata yanıtını doğrudan istemciye yansıt
        if ($e->hasResponse()) {
            $errorResponse = $e->getResponse();
            $response->getBody()->write($errorResponse->getBody()->getContents());
            return $response->withStatus($errorResponse->getStatusCode())->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['hata' => 'Auth servisine ulaşılamıyor.']));
        return $response->withStatus(503)->withHeader('Content-Type', 'application/json'); // Service Unavailable
    }
};


// --- Yönlendirme (Proxy) Mantığı ---
// Tüm istekleri yakalayan catch-all rota
$app->any('/{any:.+}', function (Request $request, Response $response) use ($guzzleClient, $servisler) {
    $path = $request->getUri()->getPath();

    // v1.1 için basit, hardcoded yönlendirme haritası
    $routingMap = [
        '/api/kullanici/giris' => $servisler['auth'],
        '/api/organizasyon/' => $servisler['organizasyon']
        // '/api/katalog/' => $servisler['katalog'],
        // '/api/siparis/' => $servisler['siparis'],
    ];

    $targetServiceUrl = null;
    foreach ($routingMap as $prefix => $serviceUrl) {
        if (str_starts_with($path, $prefix)) {
            $targetServiceUrl = $serviceUrl;
            break;
        }
    }

    if ($targetServiceUrl === null) {
        $response->getBody()->write(json_encode(['hata' => 'İstenen kaynak bulunamadı.']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    // İsteği hedef servise yönlendir
    try {
        $targetResponse = $guzzleClient->request(
            $request->getMethod(),
            $targetServiceUrl . $path,
            [
                'headers' => $request->getHeaders(),
                'body' => $request->getBody()
            ]
        );

        // Hedef servisten gelen yanıtı istemciye geri döndür
        $response->getBody()->write($targetResponse->getBody()->getContents());
        foreach ($targetResponse->getHeaders() as $name => $values) {
            $response = $response->withHeader($name, $values);
        }
        return $response->withStatus($targetResponse->getStatusCode());

    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $errorResponse = $e->getResponse();
            $response->getBody()->write($errorResponse->getBody()->getContents());
            return $response->withStatus($errorResponse->getStatusCode())->withHeader('Content-Type', 'application/json');
        }
        $response->getBody()->write(json_encode(['hata' => 'Hedef servise ulaşılamıyor.']));
        return $response->withStatus(503)->withHeader('Content-Type', 'application/json');
    }
});


// Middleware'leri ekle
$app->add($authMiddleware);
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// Uygulamayı çalıştır
$app->run();
