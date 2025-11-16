-- FulcrumOS v1.1 - Organizasyon Servisi Veritabanı Şeması
-- Mimari: Ulaş Kaşıkcı & Gemini
-- Bu dosya, `docker-compose` tarafından `mysql` servisi başlatılırken otomatik olarak çalıştırılacaktır.

-- Depolar Tablosu: Fiziksel veya sanal depoları tanımlar (v4.0).
CREATE TABLE `depolar` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `depo_adi` VARCHAR(100) NOT NULL,
  `adres` VARCHAR(255),
  `aktif` BOOLEAN DEFAULT true,
  `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Entegrasyon Anahtarları Tablosu (API Kasası): Harici servislerin API anahtarlarını şifreli olarak saklar (v5.2).
CREATE TABLE `entegrasyon_anahtarlari` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `servis_adi` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Örn: iyzico, gemini, mailgun',
  `api_anahtari` TEXT NOT NULL COMMENT 'Şifrelenmiş API anahtarı',
  `gizli_anahtar` TEXT COMMENT 'Şifrelenmiş gizli anahtar (varsa)',
  `guncelleme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fatura Ayarları Tablosu: Şirketin fatura bilgilerini ve ayarlarını tutar (v10.2).
CREATE TABLE `fatura_ayarlari` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sirket_unvani` VARCHAR(255) NOT NULL,
  `vergi_dairesi` VARCHAR(100),
  `vergi_numarasi` VARCHAR(50),
  `adres` TEXT,
  `logo_url` VARCHAR(255) COMMENT 'Faturada kullanılacak logo URLsi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------
-- BAŞLANGIÇ VERİLERİ (SEED DATA)
-- ----------------------------------

-- Varsayılan bir depo oluştur.
INSERT INTO `depolar` (`depo_adi`, `adres`) VALUES
('Merkez Depo', 'İstanbul, Türkiye');

-- Varsayılan fatura ayarlarını boş olarak ekle.
INSERT INTO `fatura_ayarlari` (`sirket_unvani`) VALUES
('FulcrumOS A.Ş.');
