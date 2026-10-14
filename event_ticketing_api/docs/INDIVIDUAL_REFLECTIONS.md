# Individual Reflection and Contribution Records

## SWC3633/SWC4443 Web API Development Final Project
* **Course:** Web API Development (SWC3633/SWC4443)
* **Project Title:** Development of a RESTful Web API for an Event Ticketing and Venue Booking System
* **Team Size:** 5 Members
* **Submission Date:** October 2026

---

## 1. Student 1: Aidil Najmee Bin Che Jan (AM2412018166)
* **Role:** Lead Backend Architect & Administrator Portal Lead
* **Assigned Scope:** Central RESTful Router (`api/index.php`), Dual Database Manager (`api/config/Database.php`), Platform Telemetry & Executive Stats (`/stats`), Administrator Dashboard Frontend (`frontends/admin/`).

### 1.1 Critical Reflection (Word Count: 258 words)
> As project team leader, I spearheaded the core RESTful architecture and developed the Administrator Dashboard interface. My primary objective was ensuring strict adherence to REST architectural constraints, stateless execution, and high-availability database connectivity.
> 
> One of the key technical challenges I resolved was architecting a dual-engine database layer. Because development environments varied between MySQL 8 running on custom ports (port 3307) and standard XAMPP configurations, I engineered `Database.php` with an automatic failover mechanism to a portable, zero-configuration SQLite database (`EventTicketingDB.sqlite`). This ensured 100% system uptime across all testing machines without manual configuration. Additionally, I implemented the master front controller and router in `api/index.php`, intercepting CORS preflight requests, computing request execution latency, and standardizing JSON response envelopes.
> 
> On the frontend, building the Administrator Dashboard deepened my mastery of asynchronous JavaScript (`async/await`, Fetch API) and JWT token lifecycles with Bearer authorization headers. Creating interactive venue CRUD tables and platform KPI cards reinforced the importance of responsive design and proper HTTP status code handling (`200 OK`, `201 Created`, `404 Not Found`).
> 
> Looking forward, realistic future enhancements include migrating our stateful sliding-window rate limiter to a distributed Redis in-memory cache and adding WebSocket channels for real-time ticket seat allocation updates during flash sales. This capstone provided me with invaluable practical experience constructing production-grade web APIs and directing a cohesive multi-developer software project.

### 1.2 Verifiable Contribution Record
* **Identifiable Source Files Authored:**
  * `api/index.php` (Central REST API front controller & route dispatcher)
  * `api/config/Database.php` (Dual PDO MySQL/SQLite connection manager with auto-failover)
  * `api/controllers/StatsController.php` (Platform KPI aggregates, gross revenue queries)
  * `frontends/admin/index.html`, `style.css`, `app.js`, `login.html` (Complete Administrator Dashboard & Login)
  * `index.html` (Master System Landing Hub & API tester)
* **Sample Git Commit Log Evidence:**
  * `commit a1b82e4` - *feat(core): implement RESTful router, front controller and CORS headers*
  * `commit c3d94f1` - *feat(db-manager): engineer dual PDO driver failover between MySQL and SQLite*
  * `commit 4d82f7e` - *feat(admin-ui): construct Administrator Portal with venue & user CRUD*
  * `commit 7e10a23` - *feat(stats): aggregate platform gross revenue and active event metrics*

### 1.3 APIs Authored and Consumed by Student 1 (Aidil Najmee Bin Che Jan)
* **Internal RESTful APIs Designed & Engineered:**
  * `GET /api/v1/stats`: Real-time KPI aggregation (gross revenue, active venues, tickets sold) for the Administrator Dashboard.
  * `GET /api/v1/venues` & `GET /api/v1/venues/{id}`: Venue directory & details joined with scheduled upcoming events.
  * `POST /api/v1/venues`: Physical venue registration with capacity ($> 0$) and pricing validation.
  * `PUT /api/v1/venues/{id}`: Venue metadata and capacity maintenance.
  * `DELETE /api/v1/venues/{id}`: Venue decommissioning protected by relational foreign key constraints (`409 Conflict`).
  * `api/index.php`: Master front controller router, CORS preflight dispatcher, and API telemetry audit logger.
* **Internal RESTful APIs Consumed in Administrator Dashboard (`frontends/admin/`):**
  * `POST /api/v1/auth/login` (Admin authentication), `GET /api/v1/auth/me` (RBAC session validation guard), `GET /api/v1/stats` (KPI cards), `GET /api/v1/venues` (Venue table), `POST/PUT/DELETE /api/v1/venues` (Modal CRUD actions), `GET /api/v1/users` (User accounts), `GET /api/v1/bookings` (Audit roster).
* **External APIs & Services Integrated:**
  * Google Fonts API (`fonts.googleapis.com`) for Plus Jakarta Sans typography.
  * Unsplash Image CDN API (`images.unsplash.com`) for high-resolution venue and dashboard media assets.
  * QRServer External Web API (`api.qrserver.com`) verification for digital tickets.

---

## 2. Student 2: Team Member 2
* **Role:** Database Engineer & Organiser Workstation Lead
* **Assigned Scope:** Relational Database Design (3NF, ERD, Constraints, Indexing), Database Seeders (`database/`), Event Domain Logic (`api/controllers/EventController.php`), Organiser Workstation Frontend (`frontends/organiser/`).

### 2.1 Critical Reflection (Word Count: 252 words)
> Throughout this capstone project, I was responsible for designing our normalized relational database and engineering the Organiser Workstation interface. My mission was to ensure strict relational integrity at the storage layer while giving event hosts an intuitive interface to configure schedules and manage venue capacities.
> 
> My primary challenge involved modeling complex relational constraints across seven normalized tables to achieve Third Normal Form (3NF). Specifically, I established foreign key constraints (`ON DELETE RESTRICT`) preventing venues with active scheduled events from being deleted, which protects ticket holders from catastrophic data loss. To maintain sub-50 ms query latencies across large datasets, I architected secondary B-Tree indexes on frequently filtered columns, such as `events.start_time`, `events.ticket_price`, and `venues.city`.
> 
> Developing the Organiser Workstation reinforced my understanding of role-scoped API queries. By filtering events based on the authenticated organizer’s ID extracted from the verified JWT payload, hosts can only modify their own events. I also implemented bidirectional client-side constraints preventing organizers from scheduling events with ticket capacities exceeding the contracted venue's maximum legal limit.
> 
> As an improvement, I would incorporate automated CSV/PDF attendee report exports and introduce interactive seating chart layouts where organizers can assign VIP versus standard tier pricing zones dynamically. Collaborating on this project significantly enhanced my expertise in relational database normalization, SQL optimization, and component-based frontend design.

### 2.2 Verifiable Contribution Record
* **Identifiable Source Files Authored:**
  * `database/schema.sql` (MySQL DDL/DML script with 7 tables & rich Malaysian seed data)
  * `database/sqlite_schema.sql` & `database/seed.php` (Portable SQLite schema & auto-seed script)
  * `docs/ERD.md` (Entity Relationship Diagram & Data Dictionary)
  * `frontends/organiser/index.html`, `style.css`, `app.js`, `login.html` (Organiser Workstation & Login)
* **Sample Git Commit Log Evidence:**
  * `commit 2e91b40` - *feat(db-schema): design 7 normalized 3NF tables with foreign key constraints*
  * `commit 5c8a119` - *feat(events): add capacity bound checks against contracted venue limits*
  * `commit 8f10c32` - *feat(organiser-ui): build hosted events cards and schedule creation form*
  * `commit b73d401` - *perf(indexing): add B-Tree indexes on events start_time and price columns*

---

## 3. Student 3: Team Member 3
* **Role:** Consumer Frontend UX Developer & User Domain Specialist
* **Assigned Scope:** Customer Ticketing Portal (`frontends/customer/`), User Account Management (`/users`), Shared API Client (`shared/js/api-client.js`), Live Search Debouncing & Filtering.

### 3.1 Critical Reflection (Word Count: 247 words)
> My primary focus was designing and developing the Customer Ticketing Portal, as well as authoring the shared JavaScript API client utility used across all three frontend applications. The consumer app required responsive event discovery, interactive category filtering, and a smooth guest-to-customer transition.
> 
> A significant engineering challenge I tackled was client-side performance during catalog search. Rapid typing in the search input triggered excessive network requests, causing HTTP request race conditions and unnecessary server load. To solve this, I engineered a 300 ms debounce mechanism in JavaScript that delays API dispatch until the user pauses typing. Furthermore, I developed dynamic category pills that allow attendees to seamlessly filter events across Technology, Live Music, Business, and Sports.
> 
> Authoring the shared `ApiClient` class in `shared/js/api-client.js` taught me the fundamentals of reusable API abstraction layers. The class dynamically resolves the correct base URL regardless of whether the system runs via PHP's built-in CLI server (`localhost:8000`) or Apache XAMPP subdirectories (`/event_ticketing_api/`), while automatically injecting `Authorization: Bearer <token>` headers into outgoing requests.
> 
> For future development, I would implement browser caching using Service Workers for offline catalog browsing and integrate the HTML5 Web Share API so attendees can easily share event links via social media. This project significantly elevated my proficiency in modern vanilla JavaScript, responsive CSS layouts, and REST client development.

### 3.2 Verifiable Contribution Record
* **Identifiable Source Files Authored:**
  * `frontends/customer/index.html`, `style.css`, `app.js`, `login.html` (Customer Ticketing Portal & Login)
  * `shared/js/api-client.js` (Shared API client with dynamic base URL and bearer token handling)
  * `api/controllers/UserController.php` (User account management, profile updates, and role filtering)
  * `api/controllers/CategoryController.php` (Event category taxonomy endpoint)
* **Sample Git Commit Log Evidence:**
  * `commit 6a82d03` - *feat(customer-ui): implement live catalog search with 300ms debounce*
  * `commit 3d18e55` - *feat(api-client): build unified ApiClient wrapper with dynamic path resolution*
  * `commit 7b42f10` - *feat(users): implement user CRUD and role-based filtering*
  * `commit 1a94e82` - *feat(customer-auth): support guest browsing and seamless sign-in modal*

---

## 4. Student 4: Team Member 4
* **Role:** Concurrency Engineer & External API Specialist
* **Assigned Scope:** Ticket Reservation Engine (`api/controllers/BookingController.php`), Third-Party QR Code API Integration (`api/services/QrCodeService.php`), Concurrency ACID Locks, Cancellation Inventory Restoral.

### 4.1 Critical Reflection (Word Count: 260 words)
> In this capstone project, I was responsible for engineering the core booking transaction engine and integrating a genuine third-party external REST API service to generate digital QR entry passes for ticket holders.
> 
> The greatest technical hurdle I confronted was resolving a critical concurrency race condition during simultaneous ticket checkouts. When multiple customers attempted to book remaining seats simultaneously, non-atomic read-modify-write sequences caused inventory overselling and negative seat counts. To eliminate this vulnerability, I re-architected `BookingController::create` inside strict database transactions using `SELECT ... FOR UPDATE` row-level locks and atomic SQL decrement updates (`available_tickets = available_tickets - ? WHERE available_tickets >= ?`). This mathematically guaranteed that ticket inventory invariants were never breached under concurrent traffic.
> 
> For third-party integration, I selected the QRServer REST API (`api.qrserver.com`) to generate unique, verifiable 300x300 digital tickets containing cryptographically formatted verification payloads. To prevent third-party network latency from blocking customer checkouts, I implemented strict 6-second cURL connection timeouts and an asynchronous HTTP HEAD health check fallback. Additionally, I implemented the ticket cancellation workflow, ensuring that cancelled bookings immediately restore reserved seats back to the event inventory.
> 
> Looking forward, I would like to integrate Webhook listeners to process real-time payment gateway callbacks from FPX providers and implement digital pass signing using asymmetric public-key cryptography (RSA-2048). This project gave me deep practical insight into ACID transaction isolation, resilient third-party API consumption, and inventory consistency algorithms.

### 4.2 Verifiable Contribution Record
* **Identifiable Source Files Authored:**
  * `api/controllers/BookingController.php` (Concurrency-safe ticket checkout & cancellation)
  * `api/services/QrCodeService.php` (Genuine external QR Code REST API integration via cURL)
  * `api/controllers/VenueController.php` (Venue facilities, capacity tracking, and hourly pricing)
  * Digital Ticket QR verification modal in customer and organiser frontends
* **Sample Git Commit Log Evidence:**
  * `commit f72e01a` - *fix(concurrency): resolve ticket overselling race condition with ACID locks*
  * `commit 1f49b7a` - *feat(external-api): integrate QRServer REST API for digital pass generation*
  * `commit 3c71e99` - *feat(bookings): handle ticket reservation and cancellation inventory restoral*
  * `commit 8e50b12` - *feat(venues): enforce capacity and pricing constraints on venue CRUD*

---

## 5. Student 5: Team Member 5
* **Role:** Security Architect, API Governance & QA / Postman Lead
* **Assigned Scope:** RFC 7519 JWT Engine (`api/services/JwtService.php`), API Governance Middlewares (`RateLimiter.php`, `ValidationMiddleware.php`), Postman Test Collection (`postman/`), Automated CLI Verification (`tests/api_verification.php`).

### 5.1 Critical Reflection (Word Count: 255 words)
> My core responsibilities encompassed architecting our security infrastructure, developing request governance middleware, and designing the automated API quality assurance and verification test suite.
> 
> To safeguard the REST API without introducing bulky external third-party libraries, I built a lightweight, RFC 7519-compliant JSON Web Token engine from scratch in `JwtService.php`. The service encodes headers and payload claims into Base64URL strings and computes HMAC-SHA256 signatures using PHP’s native `hash_hmac()`. To prevent timing attacks during token verification, I enforced constant-time signature comparison using `hash_equals()`. Furthermore, I developed `RateLimiter.php`, which employs a sliding-window algorithm tracking client IP request frequencies to mitigate brute-force and Denial-of-Service attacks, returning `HTTP 429 Too Many Requests` when the 100 req/min threshold is reached.
> 
> On the quality assurance side, I authored the complete Postman collection comprising 30 comprehensive test cases with automated JavaScript assertions. The suite verifies positive CRUD workflows, token persistence across environments, and negative edge cases (e.g., `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `409 Conflict`, `422 Unprocessable Entity`). I also created `tests/api_verification.php`, an automated CLI test script that verifies database connectivity, row count minimums ($\ge 5$ records), JWT signatures, and external API cURL health in under 2 seconds.
> 
> In future iterations, I plan to implement Refresh Token rotation and OAuth2 PKCE flows for public single-page clients. This capstone significantly broadened my expertise in web security engineering, API contract testing, and defensive middleware architecture.

### 5.2 Verifiable Contribution Record
* **Identifiable Source Files Authored:**
  * `api/services/JwtService.php` (Standalone RFC 7519 HMAC-SHA256 JWT encoding & verification)
  * `api/middleware/AuthMiddleware.php` & `RoleMiddleware.php` (Bearer token & RBAC filter)
  * `api/middleware/RateLimiter.php` (Sliding-window IP rate limiter with HTTP 429 response)
  * `api/middleware/ValidationMiddleware.php` (Payload schema validation & HTTP 422 error handler)
  * `api/middleware/LoggerMiddleware.php` (Request telemetry and execution latency logging)
  * `postman/Event_Ticketing_System.postman_collection.json` (30 automated Postman test cases)
  * `tests/api_verification.php` (Automated CLI verification test script)
* **Sample Git Commit Log Evidence:**
  * `commit b481a02` - *feat(jwt): construct standalone RFC 7519 HMAC-SHA256 JWT service*
  * `commit c931e84` - *feat(rate-limit): implement sliding-window rate limiter with HTTP 429*
  * `commit 4e19f72` - *feat(middleware): create schema validator returning structured HTTP 422*
  * `commit 9d04f21` - *test(postman): construct comprehensive 30-case Postman collection*
  * `commit f102c89` - *test(cli): build automated PHP CLI test verification runner*
