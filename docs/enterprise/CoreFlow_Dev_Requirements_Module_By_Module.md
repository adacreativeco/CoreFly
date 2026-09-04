<!-- FILE: docs/CoreFlow_Dev_Requirements_Module_By_Module.md -->
# CoreFlow Developer Requirements (Module-by-Module)

*Bu doküman, V2.1 sonrası geliştirme ekibi için öncelikli iş listesidir.*

## 1. Core / Auth / System
*   **Mevcut:** JWT, RBAC, Multi-tenant.
*   **Eksik:** SSO, Redis Cache, Global Search.
*   **Dev Tasks:**
    *   [Backend] `JwtService` revize edilerek Redis blacklist kontrolü eklenecek.
    *   [Backend] LDAP/AD entegrasyonu için `AuthService` genişletilecek.
    *   [Backend] `SearchService` yazılarak tüm modellerde `searchable` trait'i implemente edilecek.

## 2. Messaging (İletişim)
*   **Mevcut:** Polling (30sn), Basit DB kaydı.
*   **Eksik:** Real-time, Typing indicator, Read receipts, File preview.
*   **Dev Tasks:**
    *   [Infra] WebSocket sunucusu (Node.js veya PHP Ratchet) kurulacak.
    *   [Frontend] `app.js` içindeki polling kaldırılıp socket listener eklenecek.
    *   [Backend] Mesajlar kuyruğa (Queue) atılarak DB'ye asenkron yazılacak.

## 3. Documents (Doküman)
*   **Mevcut:** Local storage upload/download.
*   **Eksik:** S3 desteği, Önizleme, Paylaşım Linki.
*   **Dev Tasks:**
    *   [Backend] `StorageService` interface'i yazılarak Local ve S3 driver'ları eklenecek.
    *   [Frontend] PDF.js entegre edilerek PDF önizleme eklenecek.
    *   [Backend] Resimler için thumbnail generation (ImageMagick) eklenecek.

## 4. Field (Saha - Kritik)
*   **Mevcut:** Temel veri yapısı.
*   **Eksik:** Harita entegrasyonu (Google/OpenStreet), Mobil API optimizasyonu.
*   **Dev Tasks:**
    *   [Frontend] Leaflet.js veya Google Maps entegrasyonu ile dashboard'a harita eklenecek.
    *   [Backend] Geo-spatial query (yakındaki görevler) desteği eklenecek.
    *   [API] Düşük bant genişliği için veri sıkıştırma/optimizasyon.

## 5. Tasks & Projects
*   **Mevcut:** CRUD, Kanban.
*   **Eksik:** Workflow, Dependency, Recurring Tasks.
*   **Dev Tasks:**
    *   [Backend] Cron job ile tekrarlayan görev oluşturma mantığı.
    *   [Backend] Task statü değişimlerinde hook/event tetikleme altyapısı (Workflow için).

## 6. HR & Calendar
*   **Mevcut:** İzin talebi, Basit takvim.
*   **Eksik:** iCal/Google Sync, Onay mekanizması (Dinamik).
*   **Dev Tasks:**
    *   [Backend] `.ics` dosya üretimi ve export endpoint'i.
    *   [Backend] Google Calendar API entegrasyon servisi.
