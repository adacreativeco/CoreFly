<!-- FILE: docs/CoreFlow_Max_Roadmap_2026.md -->
# CoreFlow Max Roadmap 2026: The "Operation OS" Vision

**Doküman Tarihi:** 07.12.2025  
**Vizyon Hedefi:** 2026 Sonu  
**Konsept:** Field-First Operation OS (Sahadan Merkeze Operasyon İşletim Sistemi)

---

## 1. Yüksek Seviye Vizyon (2025 → 2026)

CoreFlow, klasik "Intranet" pazarındaki SharePoint veya Slack gibi devlerle "sosyal özellikler" veya "chat" üzerinden rekabet etmeyi bırakıyor. Bunun yerine, **saha operasyonu yöneten**, **çok lokasyonlu**, **hiyerarşik** ve **denetim gerektiren** kurumların (STK, Siyasi Parti, İnşaat, Lojistik, Perakende) ana işletim sistemi (**Operation OS**) olmayı hedefliyor.

**Maksimum Potansiyel Tanımı:**  
CoreFlow, bir kurumun sahadaki personelinden gelen veriyi (ziyaret, stok, sorun, üye kaydı) anlık olarak merkeze taşıyan, merkezden sahaya görev ve strateji indiren, uçtan uca şifreli, KVKK uyumlu ve %100 izlenebilir **dijital genel merkezdir.**

### Dönüşüm Fazları

*   **V2.x: Foundation / Operation OS (2025 Q1-Q2)**  
    *   *Mevcut Durum:* Modüler yapı, güçlü RBAC, Multi-tenant.  
    *   *Hedef:* Teknik borcun temizlenmesi, saha modüllerinin (Field, Politics, Donations) stabil hale gelmesi.
*   **V3.x: Enterprise Feature Set (2025 Q3-Q4)**  
    *   *Mevcut Durum:* Yerel dosya, polling chat, hardcoded workflow.  
    *   *Hedef:* Kurumsal satışın önündeki engellerin (SSO, Global Search, Workflow Engine, S3) kaldırılması.
*   **V4.x: AI & Scale (2026 Q1-Q4)**  
    *   *Mevcut Durum:* SQL bazlı raporlama.  
    *   *Hedef:* Yapay zeka destekli operasyonel içgörü, tahminleme, tenant sharding ile global ölçeklenme.

---

## 2. Bugünkü Durum (As-Is - 2025 Başlangıcı)

| Alan | Mevcut Durum | Puan (0-100) | Kritik Sorun |
| :--- | :--- | :--- | :--- |
| **İletişim** | Basit chat (polling), duyurular | 55 | Real-time yok, email/SMS entegrasyonu vizyonda var ama kodda yok. |
| **Operasyon** | Görev, Proje, Takvim (CRUD) | 70 | Entegrasyon yok (Outlook/Google). Workflow hardcoded. |
| **Saha/Dikey** | Seçim, Bağış, Üye (Çok Güçlü) | 95 | Rakiplerde yok. En büyük avantaj. |
| **Doküman** | Dosya depolama (Local) | 40 | Önizleme yok, arama zayıf, S3 yok. |
| **Altyapı** | Monolitik PHP, Shared DB | 85 | 10k kullanıcıya kadar yeterli ama global ölçek için değil. |

---

## 3. Maksimum Potansiyel (To-Be - 2026 Sonu)

CoreFlow 2026'da şunları yapabilmelidir:

1.  **Tam Entegre Saha Yönetimi:** Bir saha elemanı, mobil uygulamadan (offline-first) fotoğraf çekip rapor attığında, merkezdeki haritada anlık pin oluşmalı ve ilgili yöneticiye push notification gitmeli.
2.  **Dinamik İş Akışları:** "Eğer bağış > 10.000 TL ise, İl Başkanına onaya düşsün, onaylanırsa Muhasebeye fatura kes emri gitsin." (Kod yazmadan, UI üzerinden).
3.  **Yapay Zeka Destekli İçgörü:** "Geçen seçime göre bu mahalledeki ziyaret sayımız %30 düştü, sebebi X personelin izinli olması olabilir."
4.  **Kurumsal Entegrasyon:** Azure AD (Microsoft 365) ile tek tıkla giriş (SSO), Outlook takvim senkronizasyonu.
5.  **Global Ölçek:** Verinin, kurumun istediği ülkede/bölgede (Data Residency) tutulabildiği, sharded veritabanı yapısı.

---

## 4. Stratejik Sıçrama Noktaları (Breakpoints)

Bu hedefe ulaşmak için aşılması gereken kritik eşikler:

1.  **Mobil Uygulama (Q3 2025):** Web wrapper değil, **React Native** ile yazılmış, internetsiz çalışabilen (offline-sync) native uygulama. Saha operasyonu için şart.
2.  **Workflow Engine (Q4 2025):** Ürünü sadece "veri giriş ekranı" olmaktan çıkarıp "iş süreçlerini yöneten beyin" yapan modül.
3.  **API-First Dönüşümü (Q2 2026):** 3. parti geliştiricilerin kendi modüllerini yazabilmesi için Plugin mimarisine geçiş.

---

## 5. Riskler ve Bağımlılıklar

*   **Teknik Borç Riski:** Polling tabanlı chat ve notification sistemi, 5000+ kullanıcıda veritabanını kilitleyebilir. Acilen WebSocket'e geçilmeli.
*   **Pazar Riski:** Genel intranet pazarında (SharePoint) kaybolmak. Bu yüzden "Operation OS" ve "Saha" dikeyi hayati önem taşıyor.
*   **Kaynak Riski:** AI ve Mobil aynı anda yürütülemezse, Mobil önceliklendirilmeli. Saha operasyonu için mobil, AI'dan daha kritiktir.

---

## 6. Değer Önerisi (Sahada Çalışan Kurumlar İçin)

> "SharePoint ofis çalışanlarınız içindir. CoreFlow ise sahada ter döken, kapı kapı dolaşan, stok sayan, bağış toplayan **gerçek operasyon ekibiniz** içindir. Ofis ve Sahayı tek bir güvenli çatı altında, KVKK uyumlu olarak birleştiriyoruz."
