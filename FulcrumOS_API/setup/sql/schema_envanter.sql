-- --------------------------------------------------------
-- FulcrumOS (v10.4) - Envanter Servisi Veritabanı Şeması
-- Mimari: Ulaş Kaşıkcı & Gemini
-- Versiyon: v1.3 (Gerçek Kodlama)
-- --------------------------------------------------------

--
-- Tablo: `depo_stoklari` (v4.0)
-- Her bir ürün varyantının hangi depoda ne kadar bulunduğunu anlık olarak tutar.
-- Bu tablo, `envanter_hareketleri` tablosundaki loglardan beslenerek güncel tutulur.
--
CREATE TABLE `depo_stoklari` (
  `depo_stok_id` INT AUTO_INCREMENT PRIMARY KEY,
  `depo_id` INT NOT NULL COMMENT 'Organizasyon Servisi > depolar tablosuna referans',
  `varyant_id` INT NOT NULL COMMENT 'Katalog Servisi > urun_varyantlari tablosuna referans',
  `stok_miktari` INT NOT NULL DEFAULT 0 COMMENT 'Fiziksel olarak depoda bulunan miktar',
  `ayirtilmis_miktar` INT NOT NULL DEFAULT 0 COMMENT 'Gönderilmeyi bekleyen siparişler için ayrılmış miktar',
  `satilabilir_miktar` INT GENERATED ALWAYS AS (`stok_miktari` - `ayirtilmis_miktar`) STORED,
  `guncellenme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `depo_varyant_unique` (`depo_id`,`varyant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo: `envanter_seri_numaralari` (v4.0)
-- Takip yöntemi 'seri_no' olan ürünlerin her birinin kaydını tutar.
--
CREATE TABLE `envanter_seri_numaralari` (
  `seri_numarasi_id` INT AUTO_INCREMENT PRIMARY KEY,
  `varyant_id` INT NOT NULL COMMENT 'Katalog Servisi > urun_varyantlari tablosuna referans',
  `seri_numarasi` VARCHAR(255) NOT NULL,
  `depo_id` INT NOT NULL COMMENT 'Şu anda bulunduğu depo',
  `durum` ENUM('stokta', 'satildi', 'iade_edildi', 'arizali') NOT NULL DEFAULT 'stokta',
  `giris_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `guncellenme_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `varyant_seri_no_unique` (`varyant_id`, `seri_numarasi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo: `depo_stok_maliyetleri` (v4.0)
-- Her bir ürün varyantının depodaki ağırlıklı ortalama maliyetini (AOM) tutar.
-- Bu tablo, 'tedarik.mal_kabul_yapildi' gibi maliyet değiştiren olaylar sonrası güncellenir.
--
CREATE TABLE `depo_stok_maliyetleri` (
  `maliyet_id` INT AUTO_INCREMENT PRIMARY KEY,
  `depo_id` INT NOT NULL,
  `varyant_id` INT NOT NULL,
  `agirlikli_ortalama_maliyet` DECIMAL(12, 4) NOT NULL DEFAULT 0.0000,
  `son_hesaplama_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `depo_varyant_unique` (`depo_id`,`varyant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo: `envanter_hareketleri` (v2.9 - "Ledger / Hesap Defteri")
-- Tüm stok giriş ve çıkışlarının değiştirilemez kaydını tutan en kritik tablodur.
-- Raporlama ve denetim için kullanılır.
--
CREATE TABLE `envanter_hareketleri` (
  `hareket_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `varyant_id` INT NOT NULL,
  `depo_id` INT NOT NULL,
  `seri_numarasi_id` INT DEFAULT NULL,
  `hareket_tipi` ENUM(
    'tedarik_giris',
    'siparis_cikis',
    'iade_giris',
    'transfer_cikis',
    'transfer_giris',
    'sayim_duzeltme_artis',
    'sayim_duzeltme_azalis'
  ) NOT NULL,
  `miktar` INT NOT NULL COMMENT 'Pozitif: Giriş, Negatif: Çıkış',
  `birim_maliyet` DECIMAL(12, 4) COMMENT 'Hareket anındaki birim maliyet',
  `kaynak_belge_tipi` VARCHAR(50) COMMENT 'Örn: siparis, tedarik, iade',
  `kaynak_belge_id` INT COMMENT 'İlgili sipariş, tedarik veya iade IDsi',
  `aciklama` VARCHAR(255),
  `hareket_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (`seri_numarasi_id`) REFERENCES `envanter_seri_numaralari`(`seri_numarasi_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
