# Combined Group Video Demonstration Script & Storyboard (5 Members)

## SWC3633/SWC4443 Web API Development Final Project
* **Project Title:** Development of a RESTful Web API for an Event Ticketing and Venue Booking System
* **Institution:** Universiti Poly-Tech Malaysia (UPTM)
* **Team Structure (5 Members):**
  * **Student 1 (Team Leader):** Aidil Najmee Bin Che Jan (Student ID: AM2412018166) - *Backend Architect & Administrator Dashboard*
  * **Student 2:** Team Member 2 - *Database Engineer & Organiser Workstation*
  * **Student 3:** Team Member 3 - *Customer Frontend UX Developer & Search Integrator*
  * **Student 4:** Team Member 4 - *Concurrency Engineer & External QR API Specialist*
  * **Student 5:** Team Member 5 - *Security Architect & QA / Postman Lead*
* **Video Platform:** YouTube (Unlisted / Public Link)
* **Total Video Duration:** ~14 minutes (~2.5 minutes per student)

---

## Complete Video Timeline Overview

| Timestamp | Section | Presenter | Key Focus & Rubric Alignment |
| :--- | :--- | :--- | :--- |
| **00:00 - 01:15** | Group Intro & System Architecture | **Aidil Najmee (Leader)** | Objectives, 3-tier architecture, tech stack, and overall system workflow |
| **01:15 - 03:45** | Student 1 Individual Presentation | **Aidil Najmee (AM2412018166)** | Admin Dashboard: Venue CRUD, User RBAC, Dual-engine DB failover, live audit logs |
| **03:45 - 06:15** | Student 2 Individual Presentation | **Student 2** | Database 3NF Design, ERD integrity constraints, Organiser Workstation & Event scheduling |
| **06:15 - 08:45** | Student 3 Individual Presentation | **Student 3** | Customer Portal: Live debounced search, category pills filter, guest vs authenticated booking |
| **08:45 - 11:15** | Student 4 Individual Presentation | **Student 4** | Booking Concurrency (ACID locks, preventing overselling), external QR Code API integration via cURL |
| **11:15 - 13:45** | Student 5 Individual Presentation | **Student 5** | RFC 7519 JWT Security, sliding-window rate limiting (HTTP 429), and 30-case Postman test run |
| **13:45 - 14:30** | Group Conclusion & Summary | **All 5 Members** | Project wrap-up, GitHub repository link, CLO3 alignment |

---

## Detailed Section-by-Section Script

### 1. Group Introduction & System Architecture (00:00 - 01:15)
**Presenter:** Aidil Najmee Bin Che Jan (AM2412018166)  
**Visual on Screen:** Title slide showing Student Names, IDs, Section, followed by System Architecture Diagram.

> **Aidil Najmee:**  
> "Assalamu’alaikum and good day to our lecturer and evaluators. Welcome to our final project presentation for **SWC3633/SWC4443 Web API Development**.  
>  
> Our 5-member team is proud to present the **EventHub Ticketing and Venue Booking System**, engineered to achieve maximum marks under Course Learning Outcome 3 (CLO3).  
>  
> Architecturally, our solution is structured into a clean three-tier model:
> 1. At the core is a shared RESTful API developed in PHP 8 with modular routing, centralized middleware, and standardized JSON envelopes.
> 2. The persistence layer features a normalized 7-table relational schema with dual-engine MySQL and portable SQLite support.
> 3. For the client tier, we developed three decoupled frontends: an Administrator Dashboard, an Organiser Workstation, and a Customer Ticketing Portal.
> 4. We also integrated a genuine external REST API—the QRServer API—to generate digital admission QR passes.  
>  
> Let us now proceed to the individual presentations."

---

### 2. Student 1: Lead Backend & Administrator Dashboard (01:15 - 03:45)
**Presenter:** Aidil Najmee Bin Che Jan (AM2412018166)  
**Visual on Screen:** `frontends/admin/login.html` ➔ `frontends/admin/index.html` ➔ Code of `api/index.php`.

> **Aidil Najmee:**  
> "I am **Aidil Najmee Bin Che Jan, Student ID AM2412018166**. As lead backend architect and developer of the **Administrator Portal**, I will demonstrate our central routing engine, database connection manager, and the executive dashboard.  
>  
> **[Action: Log in via Admin Login Page]**  
> Notice that visiting the admin portal without an active session redirects to our dedicated login page. Authenticating sends `POST /api/v1/auth/login`, verifying credentials with bcrypt and issuing an RFC 7519 JWT token with the `'admin'` role claim.  
>  
> **[Action: Show Overview KPIs & Venue Management]**  
> On our executive dashboard, `GET /api/v1/stats` computes real-time gross revenue and active metrics. In the Venues tab, admins perform full CRUD operations (`GET`, `POST`, `PUT`, `DELETE`).  
>  
> Under the hood, in `api/config/Database.php`, I engineered an automatic failover mechanism: the system tests our primary MySQL database on port 3307, and if unavailable, seamlessly fails over to `EventTicketingDB.sqlite`, guaranteeing 100% test uptime. In `api/index.php`, I built the central router and execution latency timer, which powers the live audit log table you see here."

---

### 3. Student 2: Database Architecture & Organiser Workstation (03:45 - 06:15)
**Presenter:** Student 2  
**Visual on Screen:** `docs/ERD.md` diagram ➔ `frontends/organiser/login.html` ➔ Event Scheduling Form.

> **Student 2:**  
> "Greetings, I am **[Student 2 Name, ID]**. My responsibility was designing our normalized relational database and developing the **Organiser Workstation**.  
>  
> **[Action: Display ERD Diagram]**  
> Our database is normalized to Third Normal Form (3NF) across 7 related tables: `users`, `venues`, `event_categories`, `events`, `bookings`, `payments`, and `api_logs`. We enforce strict referential integrity. For example, foreign key constraints (`ON DELETE RESTRICT`) prevent deleting venues that currently have active scheduled events, avoiding orphaned records. We also added B-Tree indexes on `start_time` and `ticket_price` for sub-50 ms lookup performance.  
>  
> **[Action: Log into Organiser Workstation & Create Event]**  
> On the Organiser Portal, hosts log in to manage their events. In the '+ Schedule Event' form, our client interacts with `POST /api/v1/events`. The system dynamically reads the chosen venue’s capacity and enforces venue limits so organisers cannot oversell seats beyond the physical venue size. Date validation ensures end times occur strictly after start times."

---

### 4. Student 3: Customer Portal UX & Catalog Filtering (06:15 - 08:45)
**Presenter:** Student 3  
**Visual on Screen:** `frontends/customer/index.html` (Catalog search, category pills, price sort).

> **Student 3:**  
> "Greetings, I am **[Student 3 Name, ID]**. I developed the **Customer Ticketing Portal** and the shared client-side API abstraction layer.  
>  
> **[Action: Demonstrate Live Search & Category Filtering]**  
> The Customer Portal allows public attendees to discover events across Malaysia. To optimize client performance, I implemented a 300 ms debounce timer on the search bar. When a user types 'Summit' or 'Hackathon', the application waits until typing pauses before dispatching `GET /api/v1/events?search=...`, eliminating network spam.  
>  
> **[Action: Click Category Pills & Price Sorting]**  
> Attendees can filter by categories like 'Technology', 'Live Music', or 'Sports' using dynamic pills, and sort by price or date. In `shared/js/api-client.js`, I authored a unified API wrapper that dynamically calculates root endpoints whether hosted on CLI port 8000 or Apache XAMPP, automatically attaching Bearer tokens to authenticated requests."

---

### 5. Student 4: Concurrency Control & External QR API Integration (08:45 - 11:15)
**Presenter:** Student 4  
**Visual on Screen:** Customer Checkout Modal ➔ Digital QR Pass Modal ➔ Code of `QrCodeService.php` and `BookingController.php`.

> **Student 4:**  
> "Greetings, I am **[Student 4 Name, ID]**. My focus was engineering concurrency-safe booking transactions and integrating our genuine third-party external REST API service.  
>  
> **[Action: Reserve Tickets in Checkout Modal]**  
> When an attendee reserves seats, `POST /api/v1/bookings` executes inside an atomic ACID database transaction. To eliminate overselling race conditions, the backend uses `SELECT ... FOR UPDATE` row-level locks and atomic inventory decrements (`available_tickets = available_tickets - quantity WHERE available_tickets >= quantity`).  
>  
> **[Action: Show Generated Digital QR Pass Modal]**  
> Once booked, the backend immediately consumes the third-party **QRServer REST API** (`api.qrserver.com`) via PHP cURL. We pass a secure verification payload containing the booking reference, user ID, and timestamp. The authentic QR code is generated live and displayed on this digital pass. Furthermore, if a customer cancels their booking, our cancellation endpoint immediately restores the tickets back to the available inventory pool."

---

### 6. Student 5: Security Architecture & Postman QA Testing (11:15 - 13:45)
**Presenter:** Student 5  
**Visual on Screen:** Postman App running Collection ➔ `api/services/JwtService.php` & `RateLimiter.php`.

> **Student 5:**  
> "Greetings, I am **[Student 5 Name, ID]**. I was responsible for our security architecture, middleware governance, and the automated API testing suite.  
>  
> **[Action: Show JwtService.php Code & Rate Limiter]**  
> Rather than relying on external libraries, I built a lightweight RFC 7519 JSON Web Token engine from scratch in `JwtService.php`. It generates HMAC-SHA256 signatures and verifies them using constant-time `hash_equals()` to prevent timing attacks. In `RateLimiter.php`, we implemented a sliding-window rate limiter (100 req/min per IP) returning `HTTP 429 Too Many Requests` to protect against DoS attacks.  
>  
> **[Action: Run Postman Collection Runner]**  
> For quality assurance, I authored our Postman test suite containing 30 comprehensive automated test assertions. Let us run the collection runner right now.  
> As you can see, all 30 tests pass with 100% green checkmarks across `/users`, `/events`, `/venues`, and `/bookings`, verifying both successful CRUD operations and negative security tests (`401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`)."

---

### 7. Group Conclusion & Summary (13:45 - 14:30)
**Presenter:** Aidil Najmee & All Team Members  
**Visual on Screen:** Summary Slide with GitHub repository link and verification checklist.

> **Aidil Najmee:**  
> "In conclusion, our team has delivered a fully compliant, production-grade RESTful Web API system meeting all university rubric requirements:
> - Dual-engine normalized database with 7 tables and zero-config SQLite failover.
> - Full CRUD operations across all four required resource endpoints with JSON standards.
> - RFC 7519 JWT authentication, RBAC, and sliding-window rate limiting.
> - Genuine external QR Code REST API integration via cURL.
> - Three decoupled, responsive frontends and 30 automated Postman test assertions.  
>  
> We thank our lecturer and evaluators for their time and guidance throughout this course. Thank you very much!"
