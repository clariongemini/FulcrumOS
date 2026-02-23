-- --------------------------------------------------------
-- FulcrumOS (v10.4) - Auth Servisi Veritabanı Şeması
-- Mimari: Ulaş Kaşıkcı & Gemini
-- Versiyon: v1.1 (Gerçek Kodlama)
-- --------------------------------------------------------

--
-- Tablo: `kullanicilar` (Kullanıcılar)
--
CREATE TABLE `kullanicilar` (
  `kullanici_id` INT AUTO_INCREMENT PRIMARY KEY,
  `ad_soyad` VARCHAR(255) NOT NULL,
  `eposta` VARCHAR(255) NOT NULL UNIQUE,
  `parola` VARCHAR(255) NOT NULL,
  `rol_id` INT NOT NULL,
  `aktif_mi` TINYINT(1) DEFAULT 1,
  `olusturma_tarihi` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo: `roller` (Roller)
--
CREATE TABLE `roller` (
  `rol_id` INT AUTO_INCREMENT PRIMARY KEY,
  `rol_adi` VARCHAR(100) NOT NULL UNIQUE,
  `aciklama` VARCHAR(255),
  `fiyat_listesi_id` INT DEFAULT 1 -- v2.7 (B2B Fiyatlandırma) için
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo: `yetkiler` (Yetkiler - ACL)
--
CREATE TABLE `yetkiler` (
  `yetki_id` INT AUTO_INCREMENT PRIMARY KEY,
  `yetki_kodu` VARCHAR(100) NOT NULL UNIQUE,
  `aciklama` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo: `rol_yetki_iliskisi` (Pivot Tablo)
--
CREATE TABLE `rol_yetki_iliskisi` (
  `rol_id` INT NOT NULL,
  `yetki_id` INT NOT NULL,
  PRIMARY KEY (`rol_id`, `yetki_id`),
  FOREIGN KEY (`rol_id`) REFERENCES `roller`(`rol_id`) ON DELETE CASCADE,
  FOREIGN KEY (`yetki_id`) REFERENCES `yetkiler`(`yetki_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- VERİ YÜKLEMESİ (SEED DATA)
-- --------------------------------------------------------

-- 1. Roller (v2.5)
INSERT INTO `roller` (`rol_id`, `rol_adi`, `aciklama`, `fiyat_listesi_id`) VALUES
(1, 'super_admin', 'Tüm yetkilere sahip sistem yöneticisi', 1),
(2, 'kullanici', 'Standart son kullanıcı (Müşteri)', 1),
(3, 'depo_gorevlisi', 'WMS operasyonlarını yürüten personel', 1),
(4, 'bayi', 'B2B Fiyat listesini gören bayi', 2);

-- 2. Yetkiler (v10.4 Master Plan'a kadar tümü)
INSERT INTO `yetkiler` (`yetki_kodu`, `aciklama`) VALUES
-- v2.5 ACL
('kullanici_yonet', 'Kullanıcıları yönetme'),
('urun_yonet', 'Ürünleri yönetme (CRUD)'),
-- v4.0 WMS
('takip_yontemi_yonet', 'Ürünlerin WMS takip yöntemini (adet/seri_no) belirleme'),
-- v5.2 SEO/Entegrasyon
('seo_yonet', 'Ürünlerin SEO (meta) bilgilerini yönetme'),
('entegrasyon_anahtarlari_yonet', 'API Anahtar Kasasını (Iyzico vb.) yönetme'),
-- v7.3 Depo
('depo_yonet', 'Depoları (CRUD) yönetme'),
-- v7.4 Medya
('medya_yonet', 'Medya Kütüphanesini (yükleme/silme) yönetme'),
-- v7.5 Sipariş
('siparis_yonet', 'Siparişleri yönetme (durum güncelleme, log görme)'),
-- v7.6 İade
('iade_yonet', 'İade taleplerini (RMA) yönetme'),
-- v7.7 Tedarik
('tedarikci_yonet', 'Tedarikçileri (CRUD) yönetme'),
('tedarik_yonet', 'Satın Alma Siparişi (PO) oluşturma/yönetme'),
('tedarik_teslim_al', 'Depoya mal kabulü (PO teslim alma) yapma'),
-- v10.1 Watermark
('watermark_yonet', 'Filigran ayarlarını yönetme'),
-- v10.2 Fatura
('fatura_yonet', 'Fatura ayarlarını yönetme'),
('fatura_goruntule', 'Sipariş faturalarını indirme'),
-- v10.3 Muhasebe
('entegrasyon_yonet', 'Muhasebe entegrasyon loglarını görme/tekrar deneme'),
-- v10.4 AI Co-Pilot
('ai_copilot_goruntule', 'Admin Dashboard AI Co-Pilot önerilerini görme');

-- 3. Yetki Atamaları (Örnek: super_admin tüm yetkilere sahip)
-- (Bu kısım, 'super_admin' rolüne (rol_id=1) tüm yetkileri atayan bir SQL döngüsü veya INSERT bloğu içerecektir)
-- Örnek:
-- INSERT INTO `rol_yetki_iliskisi` (`rol_id`, `yetki_id`) VALUES (1, 1), (1, 2), (1, 3), ... (tüm yetkiler);

-- Örnek: Depo Görevlisi (rol_id=3)
-- (Sadece ilgili WMS yetkileri)
INSERT INTO `rol_yetki_iliskisi` (`rol_id`, `yetki_id`) VALUES
(3, (SELECT yetki_id FROM yetkiler WHERE yetki_kodu = 'tedarik_teslim_al'));
-- (3, (SELECT yetki_id FROM yetkiler WHERE yetki_kodu = 'iade_teslim_al')); -- (v3.2'den, 'yetkiler' tablosuna eklenmeli)
