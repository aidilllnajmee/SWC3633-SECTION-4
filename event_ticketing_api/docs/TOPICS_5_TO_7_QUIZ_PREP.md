# Comprehensive Master Revision Guide & Question Bank: Topics 5 to 7

## Course: SWC3633/SWC4443 Web API Development
* **Component:** Individual Assessment B2 - Understanding Quiz (30 Marks)
* **Target Grade:** 30 / 30 Marks (Highest Rubric Band: *Excellent and accurate understanding across all three topics*)

---

# PART I: COMPREHENSIVE CONCEPTUAL NOTES

## Topic 5: Web API Security, Authentication and Authorization

### 1. Authentication vs Authorization
* **Authentication (Who are you?):** Verifying the identity of a client or user (e.g., credentials check, password hashing with BCRYPT, signature verification).
* **Authorization (What are you allowed to do?):** Verifying permissions and privileges once authenticated (e.g., Role-Based Access Control `admin` vs `customer`).

### 2. JSON Web Tokens (JWT) Architecture (RFC 7519)
A JWT is a compact, URL-safe means of representing claims between two parties. It consists of three parts separated by dots (`.`):
$$\text{JWT} = \text{Base64Url}(\text{Header}) \,.\, \text{Base64Url}(\text{Payload}) \,.\, \text{Base64Url}(\text{Signature})$$

1. **Header:** Contains the token type (`typ: "JWT"`) and hashing algorithm (`alg: "HS256"` or `"RS256"`).
2. **Payload:** Contains claims:
   * *Registered Claims:* `sub` (subject/user ID), `iat` (issued at), `exp` (expiration timestamp), `iss` (issuer).
   * *Custom Claims:* `email`, `role`, `name`.
3. **Signature:** Calculated using the secret key to ensure the token has not been tampered with:
   $$\text{Signature} = \text{HMAC-SHA256}(\text{HeaderB64} \,.\, \text{PayloadB64}, \text{SecretKey})$$
* **Symmetric (HS256) vs Asymmetric (RS256):**
  * *HS256:* Uses a shared secret key for both signing and verification. Ideal for monolithic or tightly coupled services.
  * *RS256:* Uses a private key to sign and public key to verify. Ideal for microservices and identity providers (OAuth2 / OIDC).

### 3. API Key Protection
* Static secret passed in headers (e.g. `X-API-KEY: evt_live_...`).
* Used primarily for **machine-to-machine (M2M)** authentication, partner identification, and metering rather than individual end-user session tracking.

### 4. Common Web API Vulnerabilities & Mitigations
* **Broken Object Level Authorization (BOLA / IDOR):** When an authenticated customer accesses another user's resource by manipulating IDs (e.g. `GET /bookings/5`). **Mitigation:** Always verify that the authenticated user ID (`sub`) owns the requested record in the database.
* **SQL Injection:** Attacker injects malicious SQL via inputs. **Mitigation:** Parameterized queries and PDO prepared statements (`$stmt->prepare()` with `?` or named placeholders).
* **Cross-Origin Resource Sharing (CORS):** Mechanism using HTTP headers (`Access-Control-Allow-Origin`, `Access-Control-Allow-Methods`) to instruct browsers whether cross-domain scripts can access the API.

---

## Topic 6: API Management, Middleware Architecture & Optimization

### 1. Middleware Pipeline Pattern
Middleware functions execute sequentially in an onion/pipeline architecture between receiving the raw HTTP request and returning the final HTTP response:
$$\text{Request} \rightarrow [\text{CORS}] \rightarrow [\text{Logger}] \rightarrow [\text{Rate Limiter}] \rightarrow [\text{Auth}] \rightarrow [\text{RBAC}] \rightarrow [\text{Validator}] \rightarrow \text{Controller} \rightarrow \text{Response}$$

### 2. Rate Limiting Algorithms
1. **Fixed Window Counter:** Counts requests within fixed intervals (e.g., 00:00 - 01:00). *Flaw:* Traffic burst at boundary edges can cause $2 \times$ limit spikes.
2. **Sliding Window Log / Memory:** Records exact timestamps per request and evicts expired records ($t < \text{now} - \text{window}$). Highly accurate.
3. **Token Bucket:** Tokens are replenished at a constant rate into a bucket with max capacity. Bursts are permitted up to bucket capacity.
4. **Leaky Bucket:** Requests enter a queue and process at a steady outflow rate. Smoothes out traffic spikes.
* **Standard Headers:**
  * `X-RateLimit-Limit:` Total requests allowed in window.
  * `X-RateLimit-Remaining:` Remaining requests in current window.
  * `Retry-After:` Seconds client must wait before retrying when `HTTP 429 Too Many Requests` is returned.

### 3. Pagination Paradigms
* **Offset Pagination (`?page=2&limit=10`):** Uses `LIMIT 10 OFFSET 10`. *Pros:* Simple, allows jumping to page $N$. *Cons:* Slow on large datasets ($O(N)$ scan to skip rows), susceptible to "page drift" if rows are inserted/deleted between requests.
* **Keyset / Cursor Pagination (`?cursor=1045&limit=10`):** Uses `WHERE id > 1045 ORDER BY id ASC LIMIT 10`. *Pros:* Constant time $O(1)$ index seek, immune to page drift. *Cons:* Cannot jump directly to arbitrary page numbers.

### 4. Query Performance & The N+1 Query Problem
* **The N+1 Problem:** Occurs when an application retrieves a parent list of $N$ items with 1 query, then executes $N$ additional queries in a loop to fetch related child records ($1 + N$ queries).
* **Remedy:** Use SQL relational `INNER JOIN` or `LEFT JOIN` in a single compound query to retrieve parent and child records in a single round-trip.
* **Database Indexing:** Adding B-Tree indexes on foreign keys, unique columns, and `WHERE` filter attributes changes query execution from expensive Full Table Scans ($O(N)$) to logarithmic index seeks ($O(\log N)$).

---

## Topic 7: External API Integration, Webhooks & Advanced REST Architecture

### 1. The Richardson Maturity Model (REST Maturity)
* **Level 0 (The Swamp of POX):** Single URI, single HTTP POST method for all commands (e.g. SOAP, XML-RPC).
* **Level 1 (Resources):** Multiple distinct URIs for individual resources (`/events`, `/venues`), but often still relies on one HTTP method.
* **Level 2 (HTTP Verbs):** Uses standard HTTP methods correctly (`GET`, `POST`, `PUT`, `DELETE`) and returns appropriate HTTP status codes (`200`, `201`, `404`, `409`).
* **Level 3 (Hypermedia Controls / HATEOAS):** Responses include hypermedia links informing clients of allowable subsequent actions dynamically (e.g. `_links: { "cancel": "/bookings/1/cancel" }`).

### 2. HTTP Clients & External API Integration via cURL
* **PHP cURL Workflow:**
  1. `curl_init($url)`: Initializes session.
  2. `curl_setopt($ch, CURLOPT_RETURNTRANSFER, true)`: Captures response string.
  3. `curl_setopt($ch, CURLOPT_TIMEOUT, 6)`: Enforces fail-safe execution timeout.
  4. `curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false)`: Disables strict local SSL checking in development environments.
  5. `curl_exec($ch)` & `curl_getinfo($ch, CURLINFO_HTTP_CODE)`: Executes call and inspects status.
  6. `curl_close($ch)`: Releases socket handle.

### 3. Webhooks vs Long Polling
* **Polling (Client-driven pull):** Client repeatedly requests the server at set intervals (`setInterval()`) asking "Has the payment completed?". *Cons:* Wastes network bandwidth, introduces latency.
* **Webhooks (Server-driven push):** The external service makes an HTTP POST request to a pre-registered callback URL on your server the instant the event occurs (e.g., Stripe/FPX payment confirmed). *Pros:* Real-time, resource-efficient, event-driven.

### 4. Idempotency in HTTP Methods
An HTTP method is **idempotent** if making multiple identical requests has the same outcome on the server state as making a single request:
* `GET`, `HEAD`, `OPTIONS`: Idempotent & Safe.
* `PUT`: Idempotent (replacing a resource 5 times yields identical final state).
* `DELETE`: Idempotent (deleting resource ID 5 multiple times leaves the resource absent).
* `POST`: **NOT Idempotent** (submitting `POST /bookings` 5 times creates 5 distinct bookings with separate charges!).

---

# PART II: 30 MODEL QUIZ QUESTIONS & DETAILED ANSWERS

### Questions 1–10: Topic 5 (Security & JWT)

#### Q1. Which part of a JSON Web Token (JWT) guarantees data integrity and prevents payload tampering?
* **A)** Base64Url Header
* **B)** Cryptographic Signature *(Correct)*
* **C)** Registered Claims Payload
* **D)** Public Key Envelope
> **Explanation:** The cryptographic signature is generated by hashing the header and payload with a secret key. If an attacker modifies the payload, the recomputed signature will not match, causing immediate token rejection.

#### Q2. In the JWT payload, what does the registered claim `exp` denote?
* **A)** Encryption protocol type
* **B)** Execution privilege level
* **C)** Expiration timestamp in Unix epoch seconds *(Correct)*
* **D)** Exportable token identifier
> **Explanation:** RFC 7519 defines `exp` as the expiration time on or after which the JWT must not be accepted for processing.

#### Q3. What is the key functional difference between HS256 and RS256 in JWT signing?
* **A)** HS256 uses asymmetric cryptography, while RS256 uses symmetric keys.
* **B)** HS256 uses a single shared secret key, while RS256 uses a public/private key pair. *(Correct)*
* **C)** HS256 encrypts the payload, while RS256 only hashes the header.
* **D)** RS256 is deprecated in modern REST APIs.
> **Explanation:** HS256 (HMAC with SHA-256) is a symmetric algorithm requiring the exact same key to sign and verify. RS256 (RSA Signature with SHA-256) is asymmetric; only the identity provider holds the private key to sign, while any client can use the public key to verify.

#### Q4. Which HTTP header is conventionally used by a client to submit a JWT bearer token to a protected API endpoint?
* **A)** `X-Auth-Token: <token>`
* **B)** `Cookie: session_id=<token>`
* **C)** `Authorization: Bearer <token>` *(Correct)*
* **D)** `Security: JWT <token>`
> **Explanation:** RFC 6750 specifies the `Authorization` header with the `Bearer` authentication scheme.

#### Q5. What vulnerability occurs when an API allows a user to access or manipulate another user's record simply by altering the ID in the request URL?
* **A)** Broken Object Level Authorization (BOLA / IDOR) *(Correct)*
* **B)** Cross-Site Request Forgery (CSRF)
* **C)** SQL Injection (SQLi)
* **D)** Distributed Denial of Service (DDoS)
> **Explanation:** BOLA (Insecure Direct Object Reference) happens when the application fails to validate whether the authenticated user has permission to access the specific object ID requested.

#### Q6. How does Role-Based Access Control (RBAC) differ from Attribute-Based Access Control (ABAC)?
* **A)** RBAC checks static user roles (e.g. `admin`, `customer`), whereas ABAC evaluates contextual attributes (e.g. time, IP, relationship, location). *(Correct)*
* **B)** RBAC is client-side only, while ABAC is server-side.
* **C)** RBAC requires biometric hardware.
* **D)** ABAC does not support database relationships.
> **Explanation:** RBAC grants permissions based on pre-defined roles. ABAC evaluates dynamic policies based on user attributes, resource attributes, and environmental conditions.

#### Q7. Which HTTP response status code must be returned when a client provides NO authentication token on a protected endpoint?
* **A)** 400 Bad Request
* **B)** 401 Unauthorized *(Correct)*
* **C)** 403 Forbidden
* **D)** 404 Not Found
> **Explanation:** HTTP 401 indicates that the request lacks valid authentication credentials. HTTP 403 indicates the client is authenticated but lacks required permission.

#### Q8. Which HTTP status code should be returned when an authenticated `customer` attempts to delete a venue on an endpoint reserved exclusively for `admin`?
* **A)** 401 Unauthorized
* **B)** 403 Forbidden *(Correct)*
* **C)** 405 Method Not Allowed
* **D)** 422 Unprocessable Entity
> **Explanation:** The customer's identity is verified, but their role is forbidden from performing administrative deletions.

#### Q9. Why is `hash_equals()` preferred over standard string equality (`===`) when verifying API keys or signatures in PHP?
* **A)** It executes faster on multithreaded cores.
* **B)** It prevents timing attack vulnerabilities by running in constant time. *(Correct)*
* **C)** It automatically decrypts RSA keys.
* **D)** It strips malicious HTML tags.
> **Explanation:** Regular string comparison exits on the first mismatched byte, allowing attackers to measure execution time differences to guess the secret byte-by-byte. `hash_equals()` always compares all bytes in constant time.

#### Q10. What is the primary purpose of an API Key compared to a JWT session token?
* **A)** API Keys identify the calling application/system for metering and rate limiting, while JWTs represent an authenticated user session. *(Correct)*
* **B)** API Keys are always temporary, whereas JWTs never expire.
* **C)** API Keys are encrypted with AES-256.
* **D)** API Keys cannot be passed in headers.
> **Explanation:** API Keys are intended for server-to-server identification, whereas JWTs carry user identities and permission claims.

---

### Questions 11–20: Topic 6 (API Management & Middleware)

#### Q11. Which architectural pattern describes the execution flow where a request passes through multiple sequential filter layers before reaching the route controller?
* **A)** Singleton Pattern
* **B)** Middleware Pipeline / Chain of Responsibility *(Correct)*
* **C)** Observer Pattern
* **D)** Factory Pattern
> **Explanation:** In REST frameworks, incoming requests pass through a pipeline of middleware handlers (CORS, Rate Limiter, Auth, Validation) before reaching the target controller.

#### Q12. What HTTP status code is specified by RFC 6585 when a client exceeds the allowable rate limit?
* **A)** 400 Bad Request
* **B)** 403 Forbidden
* **C)** 429 Too Many Requests *(Correct)*
* **D)** 503 Service Unavailable
> **Explanation:** HTTP 429 specifically indicates that the user has sent too many requests in a given amount of time.

#### Q13. Which HTTP header informs a rate-limited client how many seconds to wait before initiating another request?
* **A)** `X-RateLimit-Reset`
* **B)** `Retry-After` *(Correct)*
* **C)** `Wait-Time`
* **D)** `Keep-Alive`
> **Explanation:** `Retry-After` is the standard HTTP header indicating how long to wait before making a follow-up request.

#### Q14. In database query optimization, what causes the "N+1 Query Problem"?
* **A)** Too many primary keys on a table.
* **B)** Executing 1 query to fetch parent rows, followed by N separate queries inside a loop to fetch related child rows. *(Correct)*
* **C)** A query that takes $N+1$ seconds to complete.
* **D)** An infinite recursive trigger.
> **Explanation:** Querying related entities iteratively causes $N$ roundtrips to the database, creating massive network and query execution overhead.

#### Q15. How is the N+1 query problem best resolved in SQL?
* **A)** Increasing the query timeout.
* **B)** Refactoring the query to use an `INNER JOIN` or `LEFT JOIN` in a single compound statement. *(Correct)*
* **C)** Storing all records in a single flat file.
* **D)** Disabling database indexes.
> **Explanation:** A relational `JOIN` enables the database engine to retrieve parent and related child data in a single optimized query execution.

#### Q16. What is the major limitation of Offset Pagination (`OFFSET 10000 LIMIT 10`) on large datasets?
* **A)** It cannot sort in descending order.
* **B)** The database must scan and discard all preceding 10,000 rows, resulting in $O(N)$ performance degradation. *(Correct)*
* **C)** It requires WebSocket connections.
* **D)** JSON cannot serialize offset metadata.
> **Explanation:** High offsets force the database to traverse and discard thousands of index entries, causing performance to degrade as page numbers increase.

#### Q17. What distinguishes Keyset / Cursor-based Pagination from Offset Pagination?
* **A)** Keyset pagination filters using a known column index (e.g. `WHERE id > last_seen_id LIMIT 10`), providing constant $O(1)$ seek time. *(Correct)*
* **B)** Keyset pagination requires full table scans.
* **C)** Keyset pagination only works with MongoDB.
* **D)** Keyset pagination allows jumping directly to page 50.
> **Explanation:** Keyset pagination uses a sequential index cursor to jump directly to the target record, eliminating row-skipping overhead.

#### Q18. What HTTP status code should an API return when incoming request JSON is syntactically valid but fails business rules (e.g., negative ticket quantity)?
* **A)** 400 Bad Request
* **B)** 404 Not Found
* **C)** 422 Unprocessable Entity *(Correct)*
* **D)** 500 Internal Server Error
> **Explanation:** RFC 4918 specifies 422 Unprocessable Entity when the server understands the content type and syntax, but cannot process the contained instructions due to semantic validation errors.

#### Q19. In a Rate Limiting algorithm, how does the "Sliding Window" approach improve upon the "Fixed Window" approach?
* **A)** It consumes zero memory.
* **B)** It prevents traffic boundary spikes by calculating a rolling time window across adjacent boundaries. *(Correct)*
* **C)** It automatically disables CORS.
* **D)** It encrypts incoming IP addresses.
> **Explanation:** Fixed windows can permit twice the rate limit if bursts occur at the end of window 1 and start of window 2. Sliding windows track timestamps dynamically, smoothing bursts.

#### Q20. What is the effect of creating a B-Tree index on `events(category_id)` in MySQL?
* **A)** It converts the table into a NoSQL document.
* **B)** It allows the database optimizer to locate matching category rows in $O(\log N)$ time instead of scanning all table rows. *(Correct)*
* **C)** It encrypts the category names with AES.
* **D)** It drops foreign key constraints.
> **Explanation:** Indexes provide pre-sorted lookup structures that eliminate full table scans during search and filter operations.

---

### Questions 21–30: Topic 7 (External API & Advanced Architecture)

#### Q21. According to the Richardson Maturity Model, what constitutes a Level 2 REST API?
* **A)** A single URI with POST requests only (POX).
* **B)** URIs for resources, combined with standard HTTP verbs (`GET`, `POST`, `PUT`, `DELETE`) and status codes. *(Correct)*
* **C)** Inclusion of HATEOAS hypermedia links in all responses.
* **D)** Full GraphQL query support.
> **Explanation:** Level 1 introduces resources; Level 2 introduces proper HTTP verbs and status codes; Level 3 introduces Hypermedia Controls (HATEOAS).

#### Q22. What does HATEOAS stand for in RESTful architecture?
* **A)** Hypertext As The Engine Of Application State *(Correct)*
* **B)** High Availability Transfer Protocol Optimization
* **C)** Hosted API Token Exchange And Open System
* **D)** Hyperlink Authentication Transport Encoding Standard
> **Explanation:** HATEOAS is a constraint of REST Level 3 where clients interact with a network application entirely through hypermedia provided dynamically by the server.

#### Q23. Which HTTP method is defined as NOT idempotent?
* **A)** `GET`
* **B)** `PUT`
* **C)** `DELETE`
* **D)** `POST` *(Correct)*
* **Explanation:** Repeating a `POST` request creates multiple separate resources and changes system state multiple times, whereas repeating `GET`, `PUT`, or `DELETE` produces the same state.

#### Q24. When consuming a third-party external API in PHP using cURL, what is the purpose of setting `CURLOPT_TIMEOUT`?
* **A)** To force the server to retry indefinitely.
* **B)** To prevent external network delays from hanging the backend thread indefinitely. *(Correct)*
* **C)** To compress the payload using gzip.
* **D)** To encrypt the cURL session with TLS 1.3.
> **Explanation:** Explicit timeouts guarantee that if the third-party API is slow or unresponsive, the application can fail gracefully instead of timing out the user.

#### Q25. What is the primary operational advantage of Webhooks over HTTP Long Polling for event notifications?
* **A)** Webhooks require client polling every 2 seconds.
* **B)** Webhooks push event data immediately from server to server via an HTTP POST callback, saving resources and eliminating latency. *(Correct)*
* **C)** Webhooks run exclusively over UDP.
* **D)** Webhooks do not require an internet connection.
> **Explanation:** Webhooks are push-based; the provider sends notifications as events happen, avoiding wasteful continuous polling loops.

#### Q26. If an external payment API experiences temporary network blips, which resilience pattern retries the request with exponentially increasing delay intervals?
* **A)** Circuit Breaker
* **B)** Exponential Backoff with Jitter *(Correct)*
* **C)** Leaky Bucket
* **D)** Bulkhead Isolation
> **Explanation:** Exponential backoff spaces out retry attempts (e.g. 1s, 2s, 4s, 8s) to avoid overwhelming recovering upstream services.

#### Q27. What is the role of the Circuit Breaker pattern in microservices and third-party integrations?
* **A)** To restart the database server whenever an error occurs.
* **B)** To detect upstream failures and stop routing requests to a failing service immediately, returning a fallback response until recovery. *(Correct)*
* **C)** To prevent SQL injection in form inputs.
* **D)** To generate QR codes locally.
> **Explanation:** A circuit breaker prevents recurring cascading failures by failing fast when a dependency is down, allowing the remote service time to recover.

#### Q28. Which API versioning strategy embeds the version directly in the resource path (e.g., `https://api.example.com/v1/events`)?
* **A)** URI Path Versioning *(Correct)*
* **B)** Query Parameter Versioning
* **C)** Content Negotiation (Accept Header) Versioning
* **D)** Custom Request Header Versioning
> **Explanation:** URI path versioning places the version number directly in the URL path, providing clear, human-readable routing.

#### Q29. When a client performs `DELETE /api/v1/bookings/10` twice in succession, what status code should ideally be returned on the second request?
* **A)** 200 OK (or 204 No Content) confirming the resource is absent, or 404 Not Found indicating it no longer exists. *(Correct)*
* **B)** 500 Internal Server Error
* **C)** 405 Method Not Allowed
* **D)** 429 Too Many Requests
> **Explanation:** Both 200/204 (idempotent state achieved) or 404 (resource already gone) are valid RESTful responses for repeated deletions.

#### Q30. Why does passing `CURLOPT_NOBODY => true` speed up external service availability health checks?
* **A)** It performs an HTTP HEAD request, verifying endpoint status headers without downloading the response body. *(Correct)*
* **B)** It skips DNS resolution.
* **C)** It encrypts the request with SSL.
* **D)** It caches the response in RAM.
> **Explanation:** An HTTP HEAD request only downloads the headers, saving bandwidth and network latency when simply checking endpoint responsiveness.

