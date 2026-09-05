<div align="center">

<p align="center">
  <img src="docs/brand/logo-color.png" alt="CoreFly Enterprise Logo" width="160" />
</p>

# CoreFly Enterprise
### Enterprise Resource Planning & Operating System (ERP & OperationOS)

[🇹🇷 Türkçe](README.md) | [🇬🇧 English](README.en.md)

<br/>

![CoreFly Logo](https://img.shields.io/badge/CoreFly-Enterprise%20v2.0-blue?style=for-the-badge&logo=shield)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![React](https://img.shields.io/badge/React-18.x-61DAFB?style=for-the-badge&logo=react&logoColor=black)](https://reactjs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.x-3178C6?style=for-the-badge&logo=typescript&logoColor=white)](https://www.typescriptlang.org)
[![Vite](https://img.shields.io/badge/Vite-6.x-646CFF?style=for-the-badge&logo=vite&logoColor=white)](https://vitejs.dev)
[![Multi-DB](https://img.shields.io/badge/Databases-SQLite%20|%20MySQL%20|%20PostgreSQL-4479A1?style=for-the-badge&logo=database)](https://github.com/adacreativeco/CoreFly)
[![GİB e-Fatura](https://img.shields.io/badge/GİB-e--Invoice%20%26%20e--Archive-red?style=for-the-badge&logo=tax)](https://gib.gov.tr)
[![VoIP WebRTC](https://img.shields.io/badge/VoIP-Audio%20%26%20Video%20Calls-success?style=for-the-badge&logo=webrtc&logoColor=white)](https://webrtc.org)
[![License](https://img.shields.io/badge/License-Apache%202.0-yellow.svg?style=for-the-badge&logo=apache)](LICENSE)

<p align="center">
  <b>An end-to-end multi-tenant Enterprise Resource Planning (ERP) and operations operating system designed for modern corporations, holdings, NGOs, and field organizations.</b>
</p>

</div>

---

## 🎨 Brand Assets & Official Logos

CoreFly's official high-resolution vector and raster assets are organized in `docs/brand/` and `frontend/public/brand/`:

| Color Logo | White Logo | Black Logo (Monochrome) |
|:---:|:---:|:---:|
| <img src="docs/brand/logo-color.png" width="130" alt="Color Logo" /> | <img src="docs/brand/logo-white.png" width="130" style="background:#111827; padding:8px; border-radius:8px;" alt="White Logo" /> | <img src="docs/brand/logo-black.png" width="130" alt="Black Logo" /> |
| **Light Mode & Marketing** | **Dark Mode & Dark Navbars** | **Official Documents & Print** |

- [Color Logo](docs/brand/logo-color.png)
- [White Logo](docs/brand/logo-white.png)
- [Black Logo](docs/brand/logo-black.png)
- [Browser Favicon](frontend/public/favicon.png)

---

## 📸 Screenshot Gallery

### 1. Executive Dashboard & Metrics
![Dashboard](docs/screenshots/01_dashboard.png)

### 2. Official GİB E-Invoice & Accounting
![Accounting & E-Invoice](docs/screenshots/02_accounting_einvoice.png)

### 3. CRM & Sales Pipeline (Kanban Board)
![CRM Deals Kanban](docs/screenshots/03_crm_deals.png)

### 4. Human Resources & Employee Management
![Human Resources](docs/screenshots/04_hr_management.png)

### 5. Messaging & VoIP Audio/Video Calling
![Messaging & VoIP](docs/screenshots/05_messages_voip.png)

<details>
<summary><b>🔍 View Additional Module Screenshots (Field, Donations, Calendar, Helpdesk, Cloud Drive)</b></summary>

| Field Operations | Donations & NGO Funding |
|:---:|:---:|
| ![Field Operations](docs/screenshots/06_field_management.png) | ![Donations](docs/screenshots/07_donations_management.png) |

| Politics & Poll Volunteers | Corporate Calendar & Meetings |
|:---:|:---:|
| ![Politics & Polls](docs/screenshots/08_politics_management.png) | ![Calendar](docs/screenshots/09_calendar.png) |

| Internal Announcements | Helpdesk Ticketing System |
|:---:|:---:|
| ![Announcements](docs/screenshots/10_announcements.png) | ![Helpdesk](docs/screenshots/11_helpdesk.png) |

| Cloud Drive & Storage | User & System Settings |
|:---:|:---:|
| ![Cloud Drive](docs/screenshots/12_files_drive.png) | ![Settings](docs/screenshots/13_settings.png) |

</details>

---

## 🏛️ Technical Architecture & Features

- **Frontend:** React 18, TypeScript, Tailwind CSS, Vite, Lucide Icons, Zustand State Management.
- **Backend:** PHP 8.2+, Layered REST Router, PDO Database Abstraction, JWT Authentication.
- **Multi-Database Support:** **SQLite / MySQL / PostgreSQL** engines supported out-of-the-box via clean `.env` configuration.
- **E-Transformation:** Official GİB UBL-TR 2.1 XML generation, ETTN UUID v4, printable HTML invoice preview, QR-code, and integrator API gateway ready.
- **VoIP & Video Communications:** WebRTC audio and video calling between users, real-time Audio Visualizer animation, camera controls, and call history.
- **Design & Experience:** Full Dark Mode / Light Mode, modern Toast Notification system (`useToast`), and responsive server/client Pagination.
- **Security & Compliance:** Multi-tenant query isolation, RBAC role-based permissions, comprehensive Audit Logs, and strict HTTP security headers.

---

## 🗄️ Multi-Database Switching

CoreFly supports 3 enterprise database engines without modifying a single line of application code:

### 1. SQLite (Default - Lightweight & Dev)
```env
DB_CONNECTION=sqlite
DB_DATABASE=storage/database/corefly.sqlite
```

### 2. MySQL / MariaDB (Web Hosting & Cloud RDS)
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=corefly
DB_USERNAME=root
DB_PASSWORD=your_password
DB_CHARSET=utf8mb4
```

### 3. PostgreSQL (Enterprise Cloud - Supabase / AWS Aurora)
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=corefly
DB_USERNAME=postgres
DB_PASSWORD=your_password
DB_SCHEMA=public
```

*Test database connectivity with:*
```bash
php scripts/test_db_connection.php
```

---

## 🧾 Official GİB E-Invoice & E-Archive Integration

The Accounting module provides enterprise-grade compliance with Turkish Revenue Administration (GİB) regulations:
1. **Universal Unique Identifier (ETTN - UUID v4):** Automatically generated for every invoice.
2. **UBL-TR 2.1 Standard XML:** Schema-compliant XML generation with tax identification, VAT breakdown, exemptions, and line items.
3. **Official Visual Template (HTML & Print):** Barcode/QR code container, spelled-out Turkish Lira verbal amount generator, and print-ready templates.
4. **Integrator Gateway:** Ready-to-connect API bridge for QNB e-Finans, Sovos, Digital Planet, or official GİB Portal.

---

## 📞 VoIP & Video Calling (WebRTC)

Internal peer-to-peer audio and video communication:
- **Audio Call:** Real-time visual audio wave animation, mute/unmute microphone, speaker controls.
- **Video Call:** Real-time local and remote camera feeds (`navigator.mediaDevices.getUserMedia`), camera on/off toggles.
- **Incoming Call Notifications:** In-app animated ringing modal with Accept and Decline actions.
- **Call History:** Complete audit trail of call timestamps, durations, and call types.

---

## 📦 Complete Module Directory

| Module | Route | Description |
|---|---|---|
| **Executive Dashboard** | `/dashboard` | Financial, CRM, Task, and HR KPI cards and activity stream |
| **Corporate Tasks** | `/tasks` | Company-wide operations task board with status filtering |
| **Projects & Kanban** | `/projects`, `/projects/:id` | Project management with interactive Kanban boards and member assignments |
| **CRM & Sales** | `/crm` | Customer accounts, interactive Deals Kanban pipeline, activities |
| **Accounting & Invoices** | `/accounting` | Cash/Bank accounts, GİB e-Invoice/e-Archive, transaction history |
| **Inventory & Warehouses** | `/inventory` | Stock tracking, inventory movements, category and supplier management |
| **Human Resources** | `/hr/employees` | Employee directory and new employee onboarding modal |
| **Departments** | `/hr/departments` | Company departments and organizational hierarchy |
| **Leave Requests** | `/hr/leaves` | Paid leave, sick leave, and excuse leave approval workflows |
| **Payroll & Compensation** | `/hr/payrolls` | Monthly payroll, gross/net salary calculations, and bonus tracking |
| **Field Operations** | `/field` | Field task assignments, location tracking, and status updates |
| **Donations & NGO** | `/donations` | Donor registry and campaign fund management |
| **Politics & Elections** | `/politics` | Volunteer and ballot observer organizing |
| **Calendar & Schedule** | `/calendar` | Company meetings, deadlines, and corporate milestones |
| **Announcements** | `/announcements` | Urgent broadcasts and internal company news |
| **Helpdesk & Support** | `/helpdesk` | Internal support ticketing system with message threads |
| **Messages & VoIP** | `/messages` | Real-time chat, audio visualizer voice calls, and video calling |
| **Cloud Drive** | `/files` | Secure corporate file uploads, storage, and downloads |
| **Super Admin (RBAC)** | `/admin/roles` | Role and permission matrix management (Admin only) |
| **Audit Logs** | `/admin/logs` | Security and audit trail monitoring (Admin only) |
| **Tenant Manager** | `/admin/tenants` | Multi-tenant SaaS client management (Admin only) |
| **Profile & Security** | `/settings/profile`, `/settings/security` | Profile editing, password change, and security settings |

---

## ⚡ Quickstart & Installation

### 1. Requirements
- PHP 8.2 or higher (with PDO, pdo_sqlite, pdo_mysql, pdo_pgsql extensions)
- Node.js 18+ and npm
- Composer (optional)

### 2. Clone & Setup Database
```bash
git clone https://github.com/adacreativeco/CoreFly.git
cd CoreFly

# Run migrations (26 migrations applied automatically)
php scripts/migrate.php

# Seed default demo data and admin credentials
php scripts/seed_full_stack.php
```

### 3. Start Backend API Server
```bash
php -S 127.0.0.1:8001 -t public
```

### 4. Start Frontend Dev Server
```bash
cd frontend
npm install
npm run dev
```
Open **`http://localhost:5173`** in your browser.

### 5. Default Credentials
- **Email:** `admin@corefly.com`
- **Password:** `Admin123!`

---

## 🧪 Automated Test Suite

Test all modules through terminal scripts:
```bash
# E-Invoice UBL-TR XML & Status Verification
php scripts/test_einvoice.php

# VoIP & Video Calling End-to-End Test
php scripts/test_call_e2e.php

# Complete Phase & Module Verification Suite
php scripts/test_all_phases.php
```

---

## 📄 License & Attribution

This project is licensed under the **[Apache License 2.0](LICENSE)**.  
Suitable for commercial, enterprise, modification, and open-source distribution.

Copyright © 2026 **[ADA Creative Co.](https://adacreative.co)**. All rights reserved.
