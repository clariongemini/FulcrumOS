-- --------------------------------------------------------
-- FulcrumOS (v10.4) - Katalog Servisi Veritabanı Şeması
-- Mimari: Ulaş Kaşıkcı & Gemini
-- Versiyon: v1.2 (Gerçek Kodlama)
-- --------------------------------------------------------

--
-- Tablo: `kategoriler`
-- Ürünlerin hiyerarşik kategori yapısını tutar.
--
CREATE TABLE `kategoriler` (
  `kategori_id` INT AUTO_INCREMENT PRIMARY KEY,
  `ust_kategori_id` INT DEFAULT NULL,
  `kategori_adi` VARCHAR(255) NOT NULL,
  `aktif_mi` TINYINT(1) DEFAULT 1,
  `sira` INT DEFAULT 0,
  FOREIGN KEY (`ust_kategori_id`) REFERENCES `kategoriler`(`kategori_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo: `urunler`
-- Varyantların çatısı olan ana ürün bilgilerini içerir.
--
CREATE TABLE `urunler` (
  `urun_id` INT AUTO_INCREMENT PRIMARY KEY,
  `kategori_id` INT,
  `urun_adi` VARCHAR(255) NOT NULL,
  `aciklama` TEXT,
  `aktif_mi` TINYINT(1) DEFAULT 1,
  `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `guncellenme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- v5.2 SEO/Merchant Sütunları
  `meta_baslik` VARCHAR(255),
  `meta_aciklama` VARCHAR(500),
  `gtin` VARCHAR(14) COMMENT 'Global Trade Item Number',
  `marka` VARCHAR(100),
  FOREIGN KEY (`kategori_id`) REFERENCES `kategoriler`(`kategori_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo: `urun_varyantlari`
-- Satılabilir ve stok takibi yapılabilir her bir ürün çeşidini temsil eder.
--
CREATE TABLE `urun_varyantlari` (
  `varyant_id` INT AUTO_INCREMENT PRIMARY KEY,
  `urun_id` INT NOT NULL,
  `varyant_kodu` VARCHAR(100) NOT NULL UNIQUE COMMENT 'SKU - Stock Keeping Unit',
  `barkod` VARCHAR(100) UNIQUE,
  `secenek_degerleri` JSON COMMENT 'Örn: {"Renk": "Kırmızı", "Beden": "L"}',
  `aktif_mi` TINYINT(1) DEFAULT 1,
  -- v4.0 WMS Sütunu
  `takip_yontemi` ENUM('adet', 'seri_no') DEFAULT 'adet' NOT NULL,
  FOREIGN KEY (`urun_id`) REFERENCES `urunler`(`urun_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo: `fiyat_listeleri`
-- Farklı müşteri grupları (roller) için farklı fiyat listeleri tanımlar (v2.7 B2B).
--
CREATE TABLE `fiyat_listeleri` (
  `fiyat_listesi_id` INT AUTO_INCREMENT PRIMARY KEY,
  `liste_adi` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Örn: Genel Fiyat Listesi, Bayi Fiyat Listesi',
  `aciklama` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo: `varyant_fiyatlari`
-- Her bir varyantın, her bir fiyat listesindeki fiyatını belirler (v2.7 B2B).
--
CREATE TABLE `varyant_fiyatlari` (
  `varyant_fiyat_id` INT AUTO_INCREMENT PRIMARY KEY,
  `varyant_id` INT NOT NULL,
  `fiyat_listesi_id` INT NOT NULL,
  `fiyat` DECIMAL(10, 2) NOT NULL,
  `para_birimi` VARCHAR(3) DEFAULT 'TRY',
  UNIQUE KEY `varyant_liste_unique` (`varyant_id`,`fiyat_listesi_id`),
  FOREIGN KEY (`varyant_id`) REFERENCES `urun_varyantlari`(`varyant_id`) ON DELETE CASCADE,
  FOREIGN KEY (`fiyat_listesi_id`) REFERENCES `fiyat_listeleri`(`fiyat_listesi_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- BAŞLANGIÇ VERİLERİ (SEED DATA)
-- --------------------------------------------------------
INSERT INTO `fiyat_listeleri` (`fiyat_listesi_id`, `liste_adi`, `aciklama`) VALUES
(1, 'Genel Fiyat Listesi', 'Tüm standart son kullanıcılara uygulanan varsayılan fiyat listesi.'),
(2, 'Bayi Fiyat Listesi', 'B2B bayilerine özel indirimli fiyatları içeren liste.');
