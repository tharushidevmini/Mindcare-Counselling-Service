# MindCare Counselling Service 🌿

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%20%7C%20MariaDB-4479A1?logo=mysql&logoColor=white)](https://mysql.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Platform](https://img.shields.io/badge/Platform-XAMPP%20%7C%20WAMP-orange)](https://www.apachefriends.org/)

A secure, multi-tier campus mental health and counseling support web platform developed for **LNBTI (Lanka Nippon BizTech Institute)** in Sri Lanka. MindCare empowers students to proactively manage psychological wellbeing through encrypted self-reflection, real-time crisis triage, professional counseling sessions, peer discussions, and AI-assisted conversational support.

---

## 📑 Table of Contents
- [System Architecture & Roles](#-system-architecture--roles)
- [Core Features](#-core-features)
- [Technology Stack](#-technology-stack)
- [Database Architecture](#-database-architecture)
- [Security & Privacy Engineering](#-security--privacy-engineering)
- [Installation & Local Setup](#-installation--local-setup)
- [Default Seed Accounts](#-default-seed-accounts)
- [Directory Structure](#-directory-structure)
- [API Reference Overview](#-api-reference-overview)
- [Authors & Acknowledgments](#-authors--acknowledgments)

---

## 🏛 System Architecture & Roles

MindCare is architected around 5 distinct stakeholder access layers:

```
                              ┌──────────────────────────┐
                              │  MindCare Public Portal  │
                              └────────────┬─────────────┘
                                           │
         ┌──────────────────┬──────────────┴───────┬──────────────────┐
         ▼                  ▼                      ▼                  ▼
┌─────────────────┐ ┌────────────────┐ ┌──────────────────────┐ ┌───────────────┐
│ Student Portal  │ │Counselor Portal│ │Learning Advisor (CSP)│ │ System Admin  │
└────────┬────────┘ └───────┬────────┘ └──────────┬───────────┘ └───────┬───────┘
         │                  │                     │                     │
         └──────────────────┼─────────────────────┴─────────────────────┘
                            ▼
              ┌───────────────────────────┐
              │ Guardian Emergency Portal │
              └───────────────────────────┘
```

1. **Students**: Daily PHQ-9 wellbeing checks, AES-256 encrypted mood diary, appointment scheduling, AI counselor chat, anonymous peer forum, audio relaxation lounge.
2. **Counselors**: Clinical appointment management, confidential clinical notes, student mood triage alerts, academic relief forwarding, psychoeducational resource publishing.
3. **Learning Advisors (CSP Module)**: Anonymized academic relief & accommodation management without access to private clinical or diary records.
4. **System Administrators**: User directory governance, role assignment, password resets, database backup creation/downloads, audit logging, content moderation.
5. **Parents & Guardians**: SMS/Email OTP-verified emergency access portal to book urgent sessions and verify student status without compromising privacy.

---

## ✨ Core Features

### 🎓 Student Hub
- **Daily PHQ-9 Check-ins**: Validated mental health questionnaire with automatic severity scoring and immediate high-risk triage triggers.
- **Encrypted Mood Diary**: Personal diary with mood emoji tagging, client-side encryption toggle, and individual sharing permissions for counselors and guardians.
- **Counselor Booking**: Real-time slot availability checking with multi-tier conflict detection (prevents overlapping sessions, blocked dates, and emergency holds).
- **AI Mental Health Counselor**: Warm, empathetic conversational assistant powered by Llama 3.3 (Groq API) with an instant, intelligent local fallback engine for offline environments and crisis keyword detection.
- **Anonymous Community Forum**: Safe peer-support space with pseudonymous user keys (`User#XXXX`), category tagging, like interactions, and moderation reports.
- **Audio & Relaxation Suite**: Guided breathing bubble animations (Box breathing, 4-7-8, Deep calm), binaural audio player, and custom meditation timers.

### 👩‍⚕️ Counselor Clinical Suite
- **Appointment Operations**: Accept, cancel, or reassign sessions with real-time student notification alerts.
- **Confidential Clinical Notes**: Private, role-restricted recordkeeping per student case.
- **Triage & Crisis Alerts**: Real-time flagged alerts for moderate-to-severe PHQ-9 responses or self-harm keywords.
- **Academic Accommodation Requests**: Direct forwarding of accommodation recommendations to Learning Advisors using privacy-preserving student tokens.
- **Resource Management**: Publication of articles, guides, and relaxation media to the student library.

### 🛡️ System Administration & Governance
- **Account Management**: Live user search, instant enable/disable toggles, self-disable protection, and temporary password reset generation.
- **Database Backup Engine**: One-click SQL backup generation, download, and storage tracking.
- **Audit Logs**: Immutable system activity logging (logins, backups, administrative actions) with log pinning capabilities.
- **Content Manager**: Administration of breathing patterns, music tracks, and meditation guides.

---

## 💻 Technology Stack

| Layer | Technologies |
| :--- | :--- |
| **Frontend** | HTML5, CSS3 (Custom Design System, Flexbox/Grid), Vanilla JavaScript (ES6+), Bootstrap Icons |
| **Backend** | PHP 8.0+ (Procedural & Object-Oriented APIs, Session Management, cURL) |
| **Database** | MySQL 8.0 / MariaDB (InnoDB, Foreign Key Constraints, Port 3307 / 3306) |
| **Cryptography** | AES-256-CBC via PHP OpenSSL with unique initialization vectors (`IV`) |
| **Email Service** | PHPMailer with resilient dual-port fallback (587 STARTTLS / 465 SSL) and local dev emulation |
| **AI Integration** | Groq Cloud API (`llama-3.3-70b-versatile`) with offline rule-based empathetic fallback |

---

## 🗄 Database Architecture

The application relies on 22 normalized relational tables:

- `users` — Campus accounts (Student, Counselor, Learning Advisor, Admin)
- `appointments` — Counseling sessions, booking sources, and statuses
- `daily_checkins` — PHQ-9 question responses, scores, and calculated severity
- `diary_entries` — AES-256 encrypted personal reflections and mood indicators
- `counselor_notes` — Confidential counselor case management documentation
- `advisor_requests` — Academic accommodation petitions and approval workflows
- `forum_posts` — Anonymous peer reflections, likes, and moderation states
- `forum_reports` — Reported community content under counselor review
- `guardian_sessions` — OTP-authenticated emergency guardian access tokens
- `guardian_daily_slots` — Daily reserved slots dedicated to emergency guardian bookings
- `system_logs` — Activity tracking, administrative actions, and IP audit trails
- `system_settings` — Platform configurations and emergency hotline directories
- `relaxation_exercises`, `music_tracks`, `meditation_types` — Audio and breathing media

---

## 🔒 Security & Privacy Engineering

- **End-to-End Diary Encryption**: Entries are encrypted before database insertion using AES-256-CBC with dynamic 16-byte initialization vectors:
  $$\text{Payload} = \text{base64}(iv) \mathbin{\Vert} \text{ciphertext}$$
- **Role-Based Access Control (RBAC)**: Enforced via `backend/middleware/auth_check.php` on all API endpoints; session states are continuously verified against the database.
- **XSS Prevention**: Strict HTML entity encoding across all dynamic DOM injections (`escapeHtml()`).
- **Conflict-Resistant Booking**: Multi-level database locking ensures zero overlapping slots between student bookings, counselor blocked times, and guardian holds.
- **Local Dev Resilience**: Gracefully handles missing SMTP credentials or offline Groq API tokens without crashing or blocking user workflows.

---

## 🚀 Installation & Local Setup

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (PHP 8.0+, Apache, MySQL)
- Git for Windows

### Step 1: Clone the Repository
```bash
cd C:\xampp\htdocs
git clone https://github.com/tharushidevmini/Mindcare-Counselling-Service.git mindcare_final
```

### Step 2: Configure MySQL Database
1. Open XAMPP Control Panel and start **Apache** and **MySQL**.
2. Open MySQL CLI or phpMyAdmin:
   ```sql
   CREATE DATABASE IF NOT EXISTS mindcare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Import the complete database schema and seed records:
   ```bash
   mysql -u root -P 3307 mindcare_db < C:\xampp\htdocs\mindcare_final\database\mindcare_db.sql
   ```
   *(Note: If your MySQL runs on default port 3306, adjust the `-P` parameter accordingly).*

### Step 3: Configure Environment Secrets
Review `backend/config/secrets.php` to verify your environment variables:
```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');        // Set to 3306 if using default XAMPP MySQL port
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mindcare_db');

define('ENCRYPTION_KEY', 'mindcare_aes_32char_secret_key!!');

// Optional: Add your Groq API key (system uses local empathetic AI fallback if left empty)
define('GROQ_API_KEY', 'YOUR_GROQ_API_KEY_HERE');

// Optional: Gmail SMTP configuration for live outbound emails
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'projectmindcare7@gmail.com');
define('SMTP_PASS', 'YOUR_GMAIL_APP_PASSWORD_HERE');
define('SMTP_PORT', 587);
```

### Step 4: Run the Application
Open your browser and navigate to:
```
http://localhost/mindcare_final/frontend/pages/public/index.html
```

---

## 🔑 Default Seed Accounts

All default test accounts share the password: **`password`**

| Role | Full Name | Campus Email | Password | Access Portal |
| :--- | :--- | :--- | :--- | :--- |
| **System Admin** | System Admin | `admin@edu.lnbti.lk` | `password` | `/frontend/pages/admin/dashboard.html` |
| **Head Counselor** | Miss Dhanushi Perera | `Dhanushi@edu.lnbti.lk` | `password` | `/frontend/pages/counselor/dashboard.html` |
| **Assistant Counselor**| Miss Mekala Harshani | `mekala@edu.lnbti.lk` | `password` | `/frontend/pages/counselor/dashboard.html` |
| **Student** | Heshali Kaluarachchi | `Heshali.uog09@edu.lnbti.lk` | `password` | `/frontend/pages/student/dashboard.html` |
| **Student** | Tharushi Devmini | `tharushi.old@edu.lnbti.lk` | `password` | `/frontend/pages/student/dashboard.html` |
| **Learning Advisor** | Dr. Karunarathna | `learningadvisor@edu.lnbti.lk` | `password` | `/frontend/pages/advisor/dashboard.html` |

---

## 📁 Directory Structure

```
mindcare_final/
├── backend/
│   ├── api/
│   │   ├── admin/             # User management, logs, settings, backups
│   │   ├── advisor/           # Academic accommodation requests & emails
│   │   ├── ai/                # Llama-3 AI counselor chat & history
│   │   ├── alerts/            # Severe PHQ-9 crisis notifications
│   │   ├── appointments/      # Booking, scheduling, slot management
│   │   ├── counselor/         # Notes, diary reviews, emergency slots
│   │   ├── diary/             # AES-256 encrypted diary endpoints
│   │   ├── forum/             # Anonymous posts, likes, moderation
│   │   ├── guardian/          # OTP verification, student status, booking
│   │   ├── mood/              # Daily check-in submissions & analytics
│   │   ├── profile/           # Photo upload and profile retrieval
│   │   ├── relaxation/        # Breathing, music, meditation manager
│   │   └── resources/         # Public mental health articles & files
│   ├── auth/                  # Login, registration OTP, password reset
│   ├── config/                # Secrets, DB connection credentials
│   ├── helpers/               # Resilient PHPMailer service
│   ├── middleware/            # Role and session authentication guards
│   └── uploads/               # Uploaded resource covers and images
├── database/
│   ├── backups/               # Automated SQL snapshots
│   └── mindcare_db.sql        # Master database schema & seed data
├── frontend/
│   ├── assets/                # Nature audio, logos, avatars, resource art
│   ├── css/                   # Global design tokens and role stylesheets
│   ├── js/                    # Controllers, auth state, diary, forum
│   └── pages/
│       ├── admin/             # User directory, logs, security, content
│       ├── advisor/           # CSP academic accommodations
│       ├── counselor/         # Clinical dashboard, reviews, alerts
│       ├── guardian/          # Emergency booking and status views
│       ├── public/            # Landing page, guides, emergency hotlines
│       └── student/           # Dashboard, diary, AI counselor, booking
├── vendor/                    # Bundled PHPMailer dependencies
├── composer.json              # Dependency specifications
└── README.md                  # Comprehensive platform documentation
```

---

## 📞 Emergency & Crisis Contacts (Sri Lanka)

- **Sumithrayo Emotional Support**: `011-2692909` / `011-2682535` (Confidential 24/7)
- **CCCline Lifeline**: `1333` (Toll-Free Crisis Hotline)
- **National Mental Health Helpline**: `1926`
- **Sri Lanka Police Emergency**: `119`
- **National Ambulance Service**: `1990`

---

## 📄 License
This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.
