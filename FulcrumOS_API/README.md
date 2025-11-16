# FulcrumOS API v1.1

## Proje Hakkında

FulcrumOS, "1P (Fenerium Modeli) + AI Asistanı" vizyonuyla geliştirilen, başsız (headless) bir WMS/ERP platformudur. Mimarisi, bir API Gateway ve mesaj kuyruğu üzerinde çalışan dağıtık mikroservislerden oluşmaktadır.

Bu depo, FulcrumOS platformunun tüm backend servislerini ve `admin-ui` frontend servisini içeren ana API monoreposudur.

## Mevcut Durum: v1.1 (Çekirdek Servislerin Nihai İnşası)

Bu versiyon, projenin "Fiziksel İnşa Aşaması"nın ikinci adımını tamamlamaktadır. v1.0'da oluşturulan boş iskeletin üzerine, sistemin çalışması için en kritik olan 3 çekirdek servisin **nihai ve fonksiyonel kod temelleri** atılmıştır.

### İnşa Edilen Servisler ve Mimarideki Rolleri

1.  **Auth Servisi (`servisler/auth-servisi/`)**:
    *   **Sorumluluk**: Kullanıcı girişi, JWT (JSON Web Token) üretimi ve token doğrulamadan sorumludur.
    *   **Veritabanı**: `schema_auth.sql` ile v10.4 Master Plan'a uygun `kullanicilar`, `roller` ve `yetkiler` yapısı kurulmuştur.
    *   **API**: `Slim + PDO` kullanılarak `/api/kullanici/giris` ve Gateway için `/internal/auth/dogrula` endpoint'leri kodlanmıştır.

2.  **Organizasyon Servisi (`servisler/organizasyon-servisi/`)**:
    *   **Sorumluluk**: Depo, entegrasyon anahtarları, fatura ve filigran ayarları gibi temel organizasyonel verileri yönetir.
    *   **Veritabanı**: `schema_organizasyon.sql` ile `depolar`, `entegrasyon_anahtarlari`, `fatura_ayarlari` ve `watermark_ayarlari` tabloları oluşturulmuştur.
    *   **API**: `Slim + PDO` kullanılarak `/api/organizasyon/depolar` endpoint'i kodlanmıştır.

3.  **Gateway Servisi (`servisler/gateway-servisi/`)**:
    *   **Sorumluluk**: Mimarinin kalbidir. Gelen tüm API istekleri için tek giriş noktasıdır. "Merkezi Kimlik Doğrulama" ve "Yönlendirme (Proxy)" mantığını uygular.
    *   **Akış**: Gelen isteğin token'ını Auth Servisi'ne sorar (`Guzzle` ile), doğrulanan kullanıcı bilgilerini (`X-User-ID`, `X-Role`, `X-Permissions` vb.) isteğe header olarak ekler ve isteği hardcoded bir harita üzerinden ilgili servise yönlendirir.

### Teknoloji Yığını (v1.1)

*   **Dil**: PHP 8.1+
*   **API Framework**: [Slim 4](https://www.slimframework.com/)
*   **Veritabanı Erişimi**: Saf PDO (PHP Data Objects)
*   **Servisler Arası İletişim**: [GuzzleHTTP](https://github.com/guzzle/guzzle)
*   **Kimlik Doğrulama**: [firebase/php-jwt](https://github.com/firebase/php-jwt)
*   **Orkestrasyon**: Docker Compose

## Sonraki Adımlar (v1.2+)

Sıradaki adımlarda, `katalog-servisi` ve `envanter-servisi` gibi diğer motor servislerinin inşasına devam edilecektir.
