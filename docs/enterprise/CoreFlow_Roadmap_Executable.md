<!-- FILE: docs/CoreFlow_Roadmap_Executable.md -->
# CoreFlow Executable Roadmap (2025-2026)

| Dönem | Hedef Sürüm | Ana Özellikler & Geliştirmeler | Teknik Bağımlılıklar & Notlar |
| :--- | :--- | :--- | :--- |
| **2025 Q1** | **v2.2 (Stability)** | - **Redis Cache/Queue:** Performans için.<br>- **Email/SMS Gateway:** Bildirimler için.<br>- **S3 Abstraction:** Dosya sistemi hazırlığı.<br>- **Bug Fixes:** Mevcut modüllerin stabilizasyonu. | Önce Redis entegrasyonu yapılmalı. |
| **2025 Q2** | **v2.3 (Connectivity)** | - **WebSocket (Real-time):** Chat ve Bildirimler için.<br>- **LDAP/SSO (Basic):** Active Directory girişi.<br>- **Global Search (V1):** SQL tabanlı gelişmiş arama. | Polling mekanizması kaldırılacak. |
| **2025 Q3** | **v3.0 (Enterprise)** | - **Mobile App (Beta):** React Native, Saha modülü odaklı.<br>- **Document Viewer:** PDF/Resim önizleme.<br>- **Outlook/Google Calendar Sync.** | Mobile API endpointleri optimize edilmeli. |
| **2025 Q4** | **v3.1 (Automation)** | - **Workflow Engine (V1):** Basit If/Then kuralları.<br>- **Mobile App (V1.0):** Tam sürüm store çıkışı.<br>- **Audit Log UI:** Gelişmiş log izleme ekranları. | Workflow için DB şeması genişletilmeli. |
| **2026 Q1** | **v3.2 (Analytics)** | - **Advanced Reporting:** Görsel dashboardlar, pivot tablolar.<br>- **Export Engine:** PDF/Excel rapor çıktıları.<br>- **Plugin Architecture (Ar-Ge):** Hazırlık. | Raporlama için Read-Replica DB düşünülebilir. |
| **2026 Q2** | **v4.0 (Intelligence)** | - **AI Assistant (Beta):** "Geçen ayki satışlar?" (NLP).<br>- **Predictive Analytics:** Stok/Bütçe tahmini.<br>- **Tenant Sharding:** Büyük ölçekli müşteriler için. | Python/FastAPI microservice entegrasyonu. |
| **2026 Q3** | **v4.1 (Ecosystem)** | - **Marketplace:** 3. parti modüller.<br>- **API Gateway:** Dış dünya entegrasyonları.<br>- **Advanced Workflow:** Çok adımlı, koşullu onaylar. | |
| **2026 Q4** | **v4.2 (Global)** | - **Multi-Language/Region:** Tam yerelleştirme.<br>- **Geo-Replication:** Veri yerelliği (Data Residency). | |
