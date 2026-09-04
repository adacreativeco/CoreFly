# CoreFly (Legacy v1.0) - Kurumsal İntranet Sistemi

> [!NOTE]
> **ARŞİV DALI (LEGACY v1.0):**  
> Bu sürüm CoreFly'ın ilk monolitik prototipidir ve arşiv amaçlı saklanmaktadır.  
> Tüm özellikler, **React 18 + TypeScript + Vite** frontend, **PHP 8.2+ REST API**, **Resmi GİB E-Fatura/E-Arşiv**, **VoIP/WebRTC Sesli & Görüntülü Arama** ve **Çoklu Veritabanı (SQLite/MySQL/PostgreSQL)** desteği ile **CoreFly Enterprise v2.0** ana dalına (`main`) taşınmıştır.

CoreFly, şirket içi iletişimi, süreç yönetimini ve bilgi paylaşımını kolaylaştırmak için tasarlanmış intranet çözümüdür.

## 🚀 Özellikler

*   **Pano (Dashboard):** Kişiselleştirilebilir widget'lar ile genel bakış.
*   **İK Modülü (CRM/HR):** Personel yönetimi, izin takibi.
*   **Duyurular & Haberler:** Şirket genelinde anlık bilgi akışı.
*   **Görev Yönetimi:** Proje ve görev takibi.
*   **Doküman Yönetimi:** Dosya paylaşımı ve sürüm kontrolü.

## 🛠️ Kurulum

Proje PHP 8.1+ tabanlıdır ve veritabanı olarak MySQL veya SQLite destekler.

### Gereksinimler
*   PHP 8.1 veya üzeri
*   Composer
*   MySQL veya SQLite (Varsayılan: SQLite)

### Adım Adım Kurulum

1.  **Projeyi Klonlayın:**
    ```bash
    git clone https://github.com/satlasco/CoreFly.git
    cd CoreFly
    ```

2.  **Bağımlılıkları Yükleyin:**
    ```bash
    composer install
    ```

3.  **Çevre Değişkenlerini Ayarlayın:**
    ```bash
    cp .env.example .env
    ```
    `.env` dosyasını açıp veritabanı ayarlarını yapılandırın. (Varsayılan olarak SQLite kullanır).

4.  **Veritabanını Hazırlayın:**
    ```bash
    php scripts/migrate.php
    ```

5.  **Başlangıç Verilerini Ekleyin (Opsiyonel):**
    ```bash
    php scripts/seed_full_stack.php
    ```

6.  **Sunucuyu Başlatın:**
    ```bash
    php -S localhost:8000 -t public
    ```

## 🔐 Varsayılan Giriş Bilgileri

*   **URL:** http://localhost:8000
*   **Yönetici E-posta:** `admin@corefly.com`
*   **Şifre:** `Admin123!`

## 📚 Dokümantasyon

Detaylı teknik dokümantasyon ve raporlar `docs/` klasörü altında mevcuttur:

*   [Kapsamlı Audit Raporu](docs/COREFLY_KAPSAMLI_AUDIT_RAPORU_2026-01-28.md)
*   [Yazılım Mimarisi (Blueprint)](docs/ENTERPRISE_ARCHITECTURE_BLUEPRINT.md)
*   [Sistem Diyagnostik Raporu](docs/CoreFlow_System_Diagnostic_Report.md)

## 🤝 Katkıda Bulunma

Pull request göndermekten çekinmeyin! Büyük değişiklikler için önce lütfen tartışma açınız.

## 📄 Lisans

Proprietary License - CoreFly Ekibi
