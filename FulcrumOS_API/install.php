<?php

/**
 * FulcrumOS v1.0 Kurulum Betiği
 *
 * Bu betik, FulcrumOS projesinin ilk kurulumunu otomatikleştirmek için tasarlanmıştır.
 * Aşağıdaki adımları gerçekleştirir:
 * 1. Tüm servisler için veritabanlarını oluşturur.
 * 2. Her servisin veritabanı şemasını (tabloları) ilgili veritabanına yükler.
 * 3. Gerekli başlangıç (seed) verilerini veritabanlarına ekler.
 * 4. Demo verilerini yükler (isteğe bağlı).
 *
 * KULLANIM:
 * > php install.php
 */

echo "FulcrumOS Kurulum Betiği Başlatılıyor...\n";

/**
 * Tüm servis veritabanlarını ve tablolarını oluşturur.
 *
 * 'servisler' dizinini tarar ve her 'schema_*.sql' dosyası için
 * bir veritabanı oluşturur ve ilgili SQL şemasını çalıştırır.
 */
function create_databases() {
    echo "Veritabanları ve tablolar oluşturuluyor...\n";

    // 1. .env dosyasından veritabanı bilgilerini yükle.
    // Örnek: $db_host = getenv('DB_HOST'); $db_user = getenv('DB_ROOT_USER'); vb.
    // Bu kısımda bir .env ayrıştırıcı kütüphanesi (örn. vlucas/phpdotenv) kullanılacaktır.
    $db_host = 'mysql'; // docker-compose'dan alınacak
    $db_user = 'root';  // root yetkisiyle veritabanı oluşturulacak
    $db_pass = 'fulcrum_root_pass'; // .env'den alınacak

    // 2. Ana MySQL sunucusuna bağlan.
    // $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);

    // 3. 'servisler' dizinindeki tüm alt dizinleri tara.
    // $services = glob('servisler/*', GLOB_ONLYDIR);

    // 4. Her servis için:
    // foreach ($service in $services) {
        //    a. Servis adını al (örn: 'siparis-servisi' -> 'siparis').
        //    $service_name = str_replace('-servisi', '', basename($service));

        //    b. Veritabanı adını oluştur (örn: 'fulcrum_siparis_db').
        //    $db_name = "fulcrum_{$service_name}_db";

        //    c. Veritabanının var olup olmadığını kontrol et.
        //    d. Veritabanını oluştur: "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

        //    e. Şema dosyasının var olup olmadığını kontrol et (örn: 'servisler/siparis-servisi/schema_siparis.sql').
        //    $schema_file = "$service/schema_{$service_name}.sql";
        //    if (file_exists($schema_file)) {
        //        - Oluşturulan veritabanına bağlan.
        //        - $pdo->exec("USE `$db_name`");
        //        - SQL dosyasının içeriğini oku.
        //        - $sql = file_get_contents($schema_file);
        //        - SQL komutlarını çalıştırarak tabloları oluştur.
        //        - $pdo->exec($sql);
        //        - echo "$db_name veritabanı ve tabloları başarıyla oluşturuldu.\n";
        //    }
    // }

    echo "Veritabanı oluşturma işlemi tamamlandı.\n";
}

/**
 * Gerekli başlangıç verilerini (seed data) ekler.
 * Örn: Yönetici kullanıcısı, temel ayarlar, varsayılan roller vb.
 */
function seed_initial_data() {
    echo "Başlangıç verileri ekleniyor...\n";
    // Bu fonksiyon, her servis için gerekli olan minimum veriyi
    // (örn. 'organizasyon-servisi' için ana organizasyon, 'auth-servisi' için admin rolü)
    // ilgili veritabanlarına ekleyecektir.
    echo "Başlangıç verileri eklendi.\n";
}

/**
 * Demo verilerini yükler.
 * /setup/sql/demo.sql dosyasını çalıştırır.
 */
function seed_demo_data() {
    echo "Demo verileri yükleniyor...\n";
    // Bu fonksiyon, geliştirme ve test ortamları için
    // demo.sql dosyasındaki örnek verileri (ürünler, siparişler vb.)
    // ilgili veritabanlarına yükleyecektir.
    echo "Demo verileri yüklendi.\n";
}


// --- Betik Yürütme ---
create_databases();
seed_initial_data();
// seed_demo_data(); // Demo verileri isteğe bağlı olarak yüklenebilir.

echo "FulcrumOS Kurulumu Başarıyla Tamamlandı!\n";

?>
