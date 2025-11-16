# FulcrumOS API v1.1

## Proje Hakkında

FulcrumOS, "1P (Fenerium Modeli) + AI Asistanı" vizyonuyla geliştirilen, başsız (headless) bir WMS/ERP platformudur. Mimarisi, bir API Gateway ve mesaj kuyruğu üzerinde çalışan dağıtık mikroservislerden oluşmaktadır.

Bu depo, FulcrumOS platformunun tüm backend servislerini ve `admin-ui` frontend servisini içeren ana API monoreposudur.

## Mevcut Durum: v1.1 (Çekirdek Servislerin İnşası)

Bu versiyon, projenin "Fiziksel İnşa Aşaması"nın ikinci adımıdır. v1.0'da oluşturulan boş iskeletin üzerine, sistemin çalışması için en kritik olan 3 çekirdek servis inşa edilmiştir.

### İnşa Edilen Servisler

1.  **Auth Servisi (`servisler/auth-servisi/`)**:
    *   **Sorumluluk**: Kullanıcı girişi, JWT (JSON Web Token) üretimi ve token doğrulamadan sorumludur.
    *   **Endpointler**: `/api/kullanici/giris`, `/internal/auth/dogrula`.

2.  **Organizasyon Servisi (`servisler/organizasyon-servisi/`)**:
    *   **Sorumluluk**: Depo, entegrasyon anahtarları ve fatura ayarları gibi temel organizasyonel verileri yönetir.
    *   **Endpointler**: `/api/organizasyon/depolar`.

3.  **Gateway Servisi (`servisler/gateway-servisi/`)**:
    *   **Sorumluluk**: Gelen tüm API istekleri için tek giriş noktasıdır. "Merkezi Kimlik Doğrulama" ve "Yönlendirme (Proxy)" mantığını uygular.
    *   **Akış**: Gelen isteğin token'ını Auth Servisi'ne sorar, doğrulanan kullanıcı bilgilerini header'lara ekler ve isteği ilgili servise yönlendirir.

### Teknoloji Yığını (v1.1)

*   **Dil**: PHP 8.1+
*   **API Framework**: [Slim 4](https://www.slimframework.com/) (Hafif ve hızlı bir yönlendirme için)
*   **Veritabanı Erişimi**: Saf PDO (PHP Data Objects) (Hafiflik ve performansı korumak için)
*   **Servisler Arası İletişim**: [GuzzleHTTP](https://github.com/guzzle/guzzle) (Gateway'in diğer servislere internal istek atması için)
*   **Kimlik Doğrulama**: [firebase/php-jwt](https://github.com/firebase/php-jwt) (JWT yönetimi için)
*   **Konfigürasyon**: [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) (`.env` dosyalarından ortam değişkenlerini okumak için)
*   **Orkestrasyon**: Docker Compose

## Sonraki Adımlar (v1.2+)

Sıradaki adımlarda, `katalog-servisi` ve `envanter-servisi` gibi diğer motor servislerinin inşasına devam edilecektir.
