<?php
// FulcrumOS v1.3 - Envanter Servisi Asenkron Worker

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

echo " [*] Envanter Worker başlatılıyor. Mesaj bekleniyor. Çıkmak için CTRL+C\n";

// --- RabbitMQ ve Veritabanı Bağlantısı ---
// Not: Gerçek bir uygulamada bu bilgiler .env dosyasından okunmalıdır.
$rabbitmq_host = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
$rabbitmq_port = 5672;
$rabbitmq_user = getenv('RABBITMQ_USER') ?: 'guest';
$rabbitmq_pass = getenv('RABBITMQ_PASS') ?: 'guest';
$kuyruk_adi = 'q_envanter';

$db_host = getenv('DB_HOST') ?: 'mysql';
$db_name = getenv('DB_NAME_ENVANTER') ?: 'fulcrumos_envanter';
$db_user = getenv('DB_USER') ?: 'fulcrum_user';
$db_pass = getenv('DB_PASSWORD') ?: 'fulcrum_pass';

try {
    // RabbitMQ bağlantısını kur
    $connection = new AMQPStreamConnection($rabbitmq_host, $rabbitmq_port, $rabbitmq_user, $rabbitmq_pass);
    $channel = $connection->channel();

    // Veritabanı bağlantısını kur (PDO)
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

} catch (\Exception $e) {
    echo " [x] Bağlantı hatası: " . $e->getMessage() . "\n";
    exit(1);
}

// Kuyruğun var olduğundan emin ol
$channel->queue_declare($kuyruk_adi, false, true, false, false);

// Gelen mesajları işleyecek olan callback fonksiyonu
$callback = function (AMQPMessage $msg) use ($pdo) {
    echo ' [x] Mesaj alındı: ', $msg->body, "\n";

    $veri = json_decode($msg->body, true);
    $olay_tipi = $veri['olay_tipi'] ?? null;
    $payload = $veri['payload'] ?? [];

    if (!$olay_tipi) {
        echo " [!] Geçersiz mesaj formatı: olay_tipi eksik.\n";
        $msg->ack(); // Mesajı kuyruktan sil
        return;
    }

    try {
        // --- Olay Yönlendirme Mantığı (v6.1 Master Plan) ---
        switch ($olay_tipi) {

            case 'siparis.kargolandi':
                // TODO: Stok Düşürme Mantığı (v1.4)
                // 1. Gelen payload'dan sipariş detaylarını al (urunler, miktarlar, depo_id).
                // 2. İlgili ürünlerin depo_stoklari tablosundaki stok_miktari ve ayirtilmis_miktar'ı azalt.
                // 3. Her bir ürün için depo_stok_maliyetleri tablosundan AOM'yi al.
                // 4. envanter_hareketleri (Ledger) tablosuna 'siparis_cikis' tipinde, AOM ile birlikte kayıt at.
                // 5. Eğer ürün seri numarası takipli ise, ilgili seri numarasının durumunu 'satildi' yap.
                echo " -> [İŞLEM] 'siparis.kargolandi' olayı için stok düşürme işlemi yapılacak.\n";
                break;

            case 'tedarik.mal_kabul_yapildi':
                // TODO: Stok Artırma ve Maliyet Hesaplama Mantığı (v1.5)
                // 1. Gelen payload'dan tedarik detaylarını al (urunler, miktarlar, depo_id, birim_maliyet).
                // 2. İlgili ürünlerin depo_stoklari tablosundaki stok_miktari'ni artır.
                // 3. depo_stok_maliyetleri tablosundaki AOM'yi yeniden hesapla:
                //    (eski_stok * eski_AOM) + (gelen_miktar * gelen_maliyet) / (eski_stok + gelen_miktar)
                // 4. envanter_hareketleri (Ledger) tablosuna 'tedarik_giris' tipinde kayıt at.
                // 5. Eğer ürün seri numarası takipli ise, gelen seri numaralarını kaydet.
                echo " -> [İŞLEM] 'tedarik.mal_kabul_yapildi' olayı için stok artırma ve AOM hesaplama işlemi yapılacak.\n";
                break;

            case 'iade.stoga_geri_alindi':
                 // TODO: Stok Artırma Mantığı (v1.5)
                 // 1. Gelen payload'dan iade detaylarını al (urunler, miktarlar, depo_id).
                 // 2. İlgili ürünlerin depo_stoklari tablosundaki stok_miktari'ni artır.
                 // 3. envanter_hareketleri (Ledger) tablosuna 'iade_giris' tipinde kayıt at.
                 //    Not: Maliyet, genellikle orijinal satış maliyeti üzerinden geri alınır.
                echo " -> [İŞLEM] 'iade.stoga_geri_alindi' olayı için stok artırma işlemi yapılacak.\n";
                break;

            default:
                echo " [!] Bilinmeyen olay tipi: $olay_tipi\n";
                break;
        }

        echo " [x] İşlem tamamlandı.\n";
        $msg->ack(); // Mesajı başarıyla işlendi olarak işaretle ve kuyruktan sil

    } catch (\Exception $e) {
        echo " [!] HATA: " . $e->getMessage() . "\n";
        // Hata durumunda mesajı kuyrukta bırakmak için nack() kullanılabilir,
        // böylece başka bir worker tekrar deneyebilir.
        // $msg->nack();
    }
};

// Worker'ı dinlemeye başlat
$channel->basic_consume($kuyruk_adi, '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

// Bağlantıları kapat
$channel->close();
$connection->close();
echo " [*] Bağlantılar kapatıldı.\n";
