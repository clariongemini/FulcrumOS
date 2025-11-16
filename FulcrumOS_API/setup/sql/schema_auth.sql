-- FulcrumOS v1.1 - Auth Servisi Veritabanı Şeması
-- Mimari: Ulaş Kaşıkcı & Gemini
-- Bu dosya, `docker-compose` tarafından `mysql` servisi başlatılırken otomatik olarak çalıştırılacaktır.

-- Roller Tablosu: Sistemdeki kullanıcı rollerini tanımlar (örn: Süper Admin, Depo Görevlisi).
CREATE TABLE `roller` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rol_adi` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Rolün sistemdeki adı (örn: super_admin)',
  `aciklama` VARCHAR(255) COMMENT 'Rolün açıklaması'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Yetkiler Tablosu: Sistemdeki tüm atomik izinleri listeler (örn: ürün oluşturma, sipariş listeleme).
CREATE TABLE `yetkiler` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `yetki_kodu` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Yetkinin kod adı (örn: urun_olustur)',
  `aciklama` VARCHAR(255) COMMENT 'Yetkinin ne işe yaradığının açıklaması'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rol-Yetki İlişki Tablosu: Hangi rolün hangi yetkilere sahip olduğunu belirler (Çoka-çok ilişki).
CREATE TABLE `rol_yetki_iliskisi` (
  `rol_id` INT NOT NULL,
  `yetki_id` INT NOT NULL,
  PRIMARY KEY (`rol_id`, `yetki_id`),
  FOREIGN KEY (`rol_id`) REFERENCES `roller`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`yetki_id`) REFERENCES `yetkiler`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kullanıcılar Tablosu: Sisteme giriş yapabilecek tüm kullanıcıları içerir.
CREATE TABLE `kullanicilar` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ad_soyad` VARCHAR(100) NOT NULL,
  `eposta` VARCHAR(100) NOT NULL UNIQUE,
  `parola` VARCHAR(255) NOT NULL COMMENT 'bcrypt ile hashlenmiş parola',
  `rol_id` INT NOT NULL,
  `aktif` BOOLEAN DEFAULT true,
  `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`rol_id`) REFERENCES `roller`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------
-- BAŞLANGIÇ VERİLERİ (SEED DATA)
-- ----------------------------------

-- 1. Temel Roller
INSERT INTO `roller` (`id`, `rol_adi`, `aciklama`) VALUES
(1, 'super_admin', 'Tüm sistem üzerinde tam yetkiye sahip kullanıcı'),
(2, 'depo_gorevlisi', 'Ürün, envanter ve depo yönetimi yetkilerine sahip kullanıcı'),
(3, 'musteri_hizmetleri', 'Siparişleri ve iadeleri yöneten, müşteri desteği sağlayan kullanıcı');

-- 2. Temel Yetkiler (Master Plan v7.x referans alınmıştır)
INSERT INTO `yetkiler` (`id`, `yetki_kodu`, `aciklama`) VALUES
-- Organizasyon Yetkileri
(1, 'ayarlari_yonet', 'Genel organizasyon ve fatura ayarlarını yönetme'),
(2, 'depolari_yonet', 'Depo ekleme, silme ve düzenleme'),
(3, 'api_anahtarlarini_yonet', 'Harici entegrasyon API anahtarlarını yönetme'),
-- Auth Yetkileri
(10, 'kullanicilari_yonet', 'Kullanıcı ekleme, silme ve düzenleme'),
(11, 'rolleri_yonet', 'Rol ve yetki atamalarını yönetme'),
-- Katalog & Envanter Yetkileri
(20, 'urunleri_yonet', 'Ürün ekleme, silme ve düzenleme'),
(21, 'kategorileri_yonet', 'Kategori yönetimi'),
(22, 'envanter_yonet', 'Stok ve envanter hareketlerini yönetme'),
-- Sipariş & İade Yetkileri
(30, 'siparisleri_listele', 'Tüm siparişleri listeleme ve görüntüleme'),
(31, 'siparis_detay_gor', 'Tek bir siparişin detaylarını görme'),
(32, 'siparis_durum_guncelle', 'Siparişlerin durumunu (kargoda, teslim edildi vb.) güncelleme'),
(33, 'iadeleri_yonet', 'İade taleplerini yönetme'),
-- Raporlama Yetkileri
(40, 'raporlari_goruntule', 'Satış ve envanter raporlarını görüntüleme');

-- 3. Rol - Yetki Atamaları
-- Süper Admin (Tüm yetkilere sahip)
INSERT INTO `rol_yetki_iliskisi` (`rol_id`, `yetki_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 10), (1, 11), (1, 20), (1, 21), (1, 22), (1, 30), (1, 31), (1, 32), (1, 33), (1, 40);

-- Depo Görevlisi
INSERT INTO `rol_yetki_iliskisi` (`rol_id`, `yetki_id`) VALUES
(2, 20), (2, 21), (2, 22), (2, 30), (2, 31);

-- Müşteri Hizmetleri
INSERT INTO `rol_yetki_iliskisi` (`rol_id`, `yetki_id`) VALUES
(3, 30), (3, 31), (3, 32), (3, 33);

-- 4. Varsayılan Süper Admin Kullanıcısı
-- E-posta: admin@fulcrumos.com
-- Parola: password123
INSERT INTO `kullanicilar` (`ad_soyad`, `eposta`, `parola`, `rol_id`) VALUES
('Süper Admin', 'admin@fulcrumos.com', '$2y$10$i2S9.6G0h011.t.aJ5xMJu1sCYi2z2.2zBwrPDHYxws2i0xGj2m2O', 1);
