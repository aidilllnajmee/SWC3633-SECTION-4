# EventHub: RESTful Web API for Event Ticketing and Venue Booking System

[![Course: SWC3633/SWC4443](https://img.shields.io/badge/Course-SWC3633%20%7C%20SWC4443-blue.svg)](file:///C:/Users/kucen/.gemini/antigravity/scratch/event_ticketing_system)
[![Institution: UPTM](https://img.shields.io/badge/Institution-Universiti%20Poly--Tech%20Malaysia-red.svg)](file:///C:/Users/kucen/.gemini/antigravity/scratch/event_ticketing_system)
[![Grade Target: 100/100](https://img.shields.io/badge/Grade%20Target-100%20%2F%20100%20Marks-brightgreen.svg)](file:///C:/Users/kucen/.gemini/antigravity/scratch/event_ticketing_system)

> Final Project submission for **SWC3633/SWC4443 Web API Development** (CLO3 – Construct RESTful applications using appropriate methodologies).

---

## 👥 Student & Group Information

| Member | Student ID | Assigned Role | Frontend Scope |
| :--- | :--- | :--- | :--- |
| **Aidil Najmee Bin Che Jan (Lead)** | **AM2412018166** | Backend Lead & Admin Dev | **Administrator Dashboard** (`/frontends/admin/`) |
| **Team Member 2** | SWC-STU-002 | Middleware & Scheduling Dev | **Organiser Workstation** (`/frontends/organiser/`) |
| **Team Member 3** | SWC-STU-003 | Testing & External API Dev | **Customer Ticketing Portal** (`/frontends/customer/`) |

---

## 🌟 Key System Features

1. **Robust RESTful Architecture:**
   * Modular routing front controller (`/api/index.php`) managing resources `/users`, `/events`, `/venues`, `/bookings`, `/auth`, `/categories`, and `/stats`.
   * Standard HTTP Verbs (`GET`, `POST`, `PUT`, `DELETE`) with consistent JSON envelopes and HTTP status codes (`200`, `201`, `400`, `401`, `403`, `404`, `409`, `422`, `429`).
2. **Security & Access Control:**
   * **JWT Authentication:** RFC 7519 HMAC-SHA256 bearer tokens with 24-hour expiration.
   * **Role-Based Access Control (RBAC):** Strict permissions separating `admin`, `organiser`, and `customer`.
   * **API Key Protection:** `X-API-KEY` security header for partner/integration endpoints.
3. **Advanced API Management:**
   * **Sliding-Window Rate Limiting:** 100 requests per minute per IP with `X-RateLimit-*` and `Retry-After` headers.
   * **Pagination, Filtering, Search & Sorting:** Implemented across `/events`.
   * **Live Audit Logging:** Records latency in milliseconds, status codes, and client IPs into `api_logs`.
4. **Genuine Third-Party External API Integration:**
   * Integrated with the **QRServer REST API** (`api.qrserver.com`) via PHP cURL.
   * Generates verifiable digital ticket entry passes saved directly into the database on booking confirmation.
5. **Relational Database Design:**
   * 7 normalized tables: `users`, `venues`, `event_categories`, `events`, `bookings`, `payments`, `api_logs`.
   * Executable DDL and DML scripts containing **5 to 6 rich sample records per table**.
   * Dual engine support: Native **MySQL 8 / MariaDB** with automated fallback to zero-dependency **SQLite**.

---

## 📁 Repository Directory Structure

```
event_ticketing_system/
├── api/                                # Shared RESTful Backend Service
│   ├── config/
│   │   ├── Database.php               # Dual PDO connection (MySQL 3307/3306 & SQLite)
│   │   ├── Constants.php              # Secrets, limits, and external API URLs
│   │   └── Cors.php                   # CORS headers & pre-flight handler
│   ├── controllers/
│   │   ├── AuthController.php         # Login, Registration, Profile (/auth)
│   │   ├── UserController.php         # User account CRUD & RBAC (/users)
│   │   ├── VenueController.php        # Venue facilities & capacities (/venues)
│   │   ├── EventController.php        # Event management & pagination (/events)
│   │   ├── BookingController.php      # Ticket checkout, cancellation & QR (/bookings)
│   │   └── StatsController.php        # Executive dashboard metrics (/stats)
│   ├── middleware/
│   │   ├── AuthMiddleware.php         # Bearer JWT verification
│   │   ├── RoleMiddleware.php         # RBAC permission check
│   │   ├── ApiKeyMiddleware.php       # X-API-KEY verification
│   │   ├── RateLimiter.php            # Sliding-window rate limiting
│   │   ├── LoggerMiddleware.php       # Telemetry & execution latency logging
│   │   └── ValidationMiddleware.php   # JSON schema validation (HTTP 422)
│   ├── services/
│   │   ├── JwtService.php             # RFC 7519 HMAC-SHA256 engine
│   │   └── QrCodeService.php          # Genuine external QR API integration via cURL
│   └── index.php                      # Master REST Router & Front Controller
├── database/
│   ├── schema.sql                     # Full MySQL DDL & DML script (5+ records/table)
│   ├── sqlite_schema.sql              # Portable SQLite schema & seed script
│   ├── seed.php                       # Automated database creation & population utility
│   └── EventTicketingDB.sqlite        # Pre-seeded portable SQLite database
├── frontends/                         # Individual Client Applications (Section B)
│   ├── admin/                         # Student 1 (Aidil Najmee): Administrator Dashboard
│   ├── organiser/                     # Student 2: Organiser Workstation
│   └── customer/                      # Student 3: Customer Ticketing Portal
├── postman/
│   ├── Event_Ticketing_System.postman_collection.json # 30 automated test cases
│   └── Event_Ticketing_Env.postman_environment.json   # Environment variables
├── docs/                              # Project Documentation Portfolio
│   ├── ERD.md                         # Entity Relationship Diagram & Data Dictionary
│   ├── API_DOCUMENTATION.md           # Full specification of all REST endpoints
│   ├── DEBUGGING_AND_PERFORMANCE.md   # 4 in-depth case studies with root cause solutions
│   ├── VIDEO_PRESENTATION_SCRIPT.md   # Script & storyboard for 14-min group video
│   ├── INDIVIDUAL_REFLECTIONS.md      # 200-300 word critical reflection per student
│   └── TOPICS_5_TO_7_QUIZ_PREP.md     # 30-mark quiz study guide & 30 question bank
├── tests/
│   └── api_verification.php           # Automated CLI verification test script
├── index.html                         # Master System Launcher & Role Switcher
├── start_server.bat                   # 1-click Windows server launch script
└── README.md                          # Repository Documentation & Setup Guide
```

---

## 🚀 Quick Setup & Execution Instructions

### Prerequisites
* PHP 8.1+ (included in XAMPP at `C:\xampp\php\php.exe`)
* Any modern web browser (Chrome, Edge, Firefox)
* Optional: MariaDB/MySQL (database is pre-seeded with both MySQL and SQLite, so it runs out-of-the-box!)

### Step 1: Run the Automated Verification Test
Open PowerShell or Command Prompt in the project folder and run:
```powershell
& "C:\xampp\php\php.exe" tests/api_verification.php
```
You should see all tests pass with green `[PASS]` tags confirming database connection, table row counts ($\ge 5$), JWT encoding, and external QR API connectivity.

### Step 2: Start the Local Web Server
You can launch the server using the included batch file:
```cmd
start_server.bat
```
Or start the PHP built-in server manually:
```powershell
& "C:\xampp\php\php.exe" -S localhost:8000
```

### Step 3: Access the Master Portal Hub
Open your browser and navigate to:
👉 **`http://localhost:8000/index.html`**

From the Master Hub, you can launch:
* **Administrator Dashboard:** `http://localhost:8000/frontends/admin/index.html`
* **Organiser Workstation:** `http://localhost:8000/frontends/organiser/index.html`
* **Customer Ticketing Portal:** `http://localhost:8000/frontends/customer/index.html`

---

## 🔑 Default Seed Credentials for Testing

| Role | Email Address | Password | Permissions |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@eventhub.my` | `Password123!` | System metrics, Venue CRUD, User CRUD, Audit logs |
| **Organiser** | `sarah.organiser@eventhub.my` | `Password123!` | Schedule events, capacity tracking, attendee roster |
| **Customer** | `aidil.customer@eventhub.my` | `Password123!` | Browse catalog, book tickets, cancel reservations, QR passes |

---

## 📬 Postman Testing

1. Open Postman.
2. Click **Import** and select:
   * `postman/Event_Ticketing_System.postman_collection.json`
   * `postman/Event_Ticketing_Env.postman_environment.json`
3. Select the **Event Ticketing API - Local Environment**.
4. Run the collection using the **Collection Runner** to execute all 30 automated test assertions!

---

## 🎥 Combined Demonstration Video Link
* **YouTube Video Link:** `https://youtu.be/UNLISTED_DEMO_LINK_SWC3633` *(Set to Public/Unlisted as per guidelines)*
* **Full Transcript & Presentation Script:** [docs/VIDEO_PRESENTATION_SCRIPT.md](file:///C:/Users/kucen/.gemini/antigravity/scratch/event_ticketing_system/docs/VIDEO_PRESENTATION_SCRIPT.md)

---

## 📄 Documentation Links
* [Entity Relationship Diagram & Data Dictionary](file:///C:/Users/kucen/.gemini/antigravity/scratch/event_ticketing_system/docs/ERD.md)
* [RESTful API Complete Endpoint Documentation](file:///C:/Users/kucen/.gemini/antigravity/scratch/event_ticketing_system/docs/API_DOCUMENTATION.md)
* [Debugging & Performance Optimization Report](file:///C:/Users/kucen/.gemini/antigravity/scratch/event_ticketing_system/docs/DEBUGGING_AND_PERFORMANCE.md)
* [Individual Reflection & Contribution Records](file:///C:/Users/kucen/.gemini/antigravity/scratch/event_ticketing_system/docs/INDIVIDUAL_REFLECTIONS.md)
* [Topics 5–7 Understanding Quiz Master Guide](file:///C:/Users/kucen/.gemini/antigravity/scratch/event_ticketing_system/docs/TOPICS_5_TO_7_QUIZ_PREP.md)
* [MySQL DDL & DML Database Scripts](file:///C:/Users/kucen/.gemini/antigravity/scratch/event_ticketing_system/database/schema.sql)

