-- --------------------------------------------------------
-- FulcrumOS (v10.4) - Organizasyon Servisi Veritabanı Şeması
-- Mimari: Ulaş Kaşıkcı & Gemini
-- Versiyon: v1.1 (Gerçek Kodlama)
-- --------------------------------------------------------

--
-- Tablo: `depolar` (v4.0 - WMS)
--
CREATE TABLE `depolar` (
  `depo_id` INT AUTO_INCREMENT PRIMARY KEY,
  `depo_adi` VARCHAR(255) NOT NULL,
  `depo_kodu` VARCHAR(50) UNIQUE,
  `adres` TEXT,
  `il` VARCHAR(100),
  `ilce` VARCHAR(100),
  `aktif_mi` TINYINT(1) DEFAULT 1,
  `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo: `entegrasyon_anahtarlari` (v5.2 - API Kasa)
--
CREATE TABLE `entegrasyon_anahtarlari` (
  `anahtar_id` INT AUTO_INCREMENT PRIMARY KEY,
  `servis_adi` VARCHAR(100) NOT NULL, -- Örn: 'iyzico', 'google_analytics', 'gemini'
  `anahtar_adi` VARCHAR(100) NOT NULL, -- Örn: 'api_key', 'secret_key'
  `anahtar_degeri` TEXT NOT NULL, -- Güvenlik için şifrelenmiş (encrypted) olmalı
  `guncellenme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo: `fatura_ayarlari` (v10.2 - Fatura)
--
CREATE TABLE `fatura_ayarlari` (
  `ayar_id` INT AUTO_INCREMENT PRIMARY KEY,
  `ayar_adi` VARCHAR(100) NOT NULL UNIQUE, -- Örn: 'firma_unvani', 'vergi_dairesi', 'vergi_no', 'firma_adresi'
  `ayar_degeri` TEXT,
  `guncellenme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo: `watermark_ayarlari` (v10.1 - Filigran)
--
CREATE TABLE `watermark_ayarlari` (
  `ayar_id` INT AUTO_INCREMENT PRIMARY KEY,
  `ayar_adi` VARCHAR(100) NOT NULL UNIQUE, -- Örn: 'aktif_mi', 'resim_medya_id', 'pozisyon'
  `ayar_degeri` VARCHAR(255),
  `guncellenme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
