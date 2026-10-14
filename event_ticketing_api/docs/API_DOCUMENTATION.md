# RESTful Web API Complete Endpoint Documentation

## SWC3633/SWC4443 Web API Development
* **Base URL:** `http://localhost:8000/api/v1` (or `http://localhost/event_ticketing_system/api/v1`)
* **Standard Exchange Format:** JSON (`application/json`)
* **Authentication Scheme:** `Authorization: Bearer <jwt_token>`
* **Partner Security Header:** `X-API-KEY: evt_live_9f83b12a84c90e`

---

## 1. Authentication Endpoints (`/auth`)

### 1.1 User Registration
* **Purpose:** Register a new user account (Customer or Organiser).
* **URL:** `/auth/register`
* **Method:** `POST`
* **Authentication:** None (Public)
* **Request Body:**
```json
{
  "full_name": "Danial Hakim",
  "email": "danial.test@example.com",
  "password": "SecurePass123!",
  "role": "customer",
  "phone_number": "+6014-8899001"
}
```
* **Success Response (HTTP 201 Created):**
```json
{
  "success": true,
  "status": 201,
  "message": "User registration successful.",
  "data": {
    "user": {
      "id": 7,
      "full_name": "Danial Hakim",
      "email": "danial.test@example.com",
      "role": "customer",
      "phone_number": "+6014-8899001"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 86400
  }
}
```
* **Relevant HTTP Status Codes:** `201 Created`, `400 Bad Request`, `409 Conflict` (Email already registered), `422 Unprocessable Entity`.

---

### 1.2 User Login
* **Purpose:** Authenticate an existing account and obtain an RFC 7519 JWT token.
* **URL:** `/auth/login`
* **Method:** `POST`
* **Authentication:** None (Public)
* **Request Body:**
```json
{
  "email": "admin@eventhub.my",
  "password": "Password123!"
}
```
* **Success Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": 200,
  "message": "Authentication successful.",
  "data": {
    "user": {
      "id": 1,
      "full_name": "System Administrator",
      "email": "admin@eventhub.my",
      "role": "admin",
      "phone_number": "+6012-3456789"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 86400
  }
}
```
* **Relevant HTTP Status Codes:** `200 OK`, `401 Unauthorized` (Invalid credentials), `422 Unprocessable Entity`.

---

### 1.3 Current User Profile
* **Purpose:** Retrieve the profile of the currently authenticated token bearer.
* **URL:** `/auth/me`
* **Method:** `GET`
* **Authentication:** `Bearer Token` (Any authenticated user)
* **Success Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": 200,
  "data": {
    "id": 1,
    "full_name": "System Administrator",
    "email": "admin@eventhub.my",
    "role": "admin",
    "phone_number": "+6012-3456789",
    "created_at": "2026-10-13 14:00:00"
  }
}
```
* **Relevant HTTP Status Codes:** `200 OK`, `401 Unauthorized`.

---

## 2. Users Resource (`/users`)

### 2.1 List All Users
* **Purpose:** Retrieve user accounts with optional role filtering and keyword search.
* **URL:** `/users`
* **Method:** `GET`
* **Authentication:** `Bearer Token` (Role: `admin`)
* **Request Query Parameters:** `?role=customer&search=Faizal`
* **Success Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": 200,
  "count": 1,
  "data": [
    {
      "id": 3,
      "full_name": "Ahmad Faizal (Music Guild)",
      "email": "faizal.organiser@eventhub.my",
      "role": "organiser",
      "phone_number": "+6019-2233445",
      "created_at": "2026-10-13 14:00:00"
    }
  ]
}
```
* **Relevant HTTP Status Codes:** `200 OK`, `401 Unauthorized`, `403 Forbidden`.

---

### 2.2 Get User by ID
* **Purpose:** Retrieve profile of a specific user.
* **URL:** `/users/{id}`
* **Method:** `GET`
* **Authentication:** `Bearer Token` (Role: `admin` or the profile owner)
* **Success Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": 200,
  "data": {
    "id": 4,
    "full_name": "Aidil Najmee",
    "email": "aidil.customer@eventhub.my",
    "role": "customer",
    "phone_number": "+6011-5544332",
    "created_at": "2026-10-13 14:00:00"
  }
}
```
* **Relevant HTTP Status Codes:** `200 OK`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`.

---

### 2.3 Create User
* **Purpose:** Administrator creates a new user with specific role privileges.
* **URL:** `/users`
* **Method:** `POST`
* **Authentication:** `Bearer Token` (Role: `admin`)
* **Request Body:**
```json
{
  "full_name": "Khairul Anuar",
  "email": "khairul.staff@eventhub.my",
  "password": "Password123!",
  "role": "organiser",
  "phone_number": "+6019-7766554"
}
```
* **Success Response (HTTP 201 Created):**
```json
{
  "success": true,
  "status": 201,
  "message": "User created successfully.",
  "data": {
    "id": 8,
    "full_name": "Khairul Anuar",
    "email": "khairul.staff@eventhub.my",
    "role": "organiser",
    "phone_number": "+6019-7766554"
  }
}
```
* **Relevant HTTP Status Codes:** `201 Created`, `401 Unauthorized`, `403 Forbidden`, `409 Conflict`, `422 Unprocessable Entity`.

---

### 2.4 Update User
* **Purpose:** Update user details or profile settings.
* **URL:** `/users/{id}`
* **Method:** `PUT`
* **Authentication:** `Bearer Token` (Role: `admin` or account owner)
* **Request Body:**
```json
{
  "full_name": "Khairul Anuar (Senior Organiser)",
  "email": "khairul.senior@eventhub.my",
  "phone_number": "+6019-7766999"
}
```
* **Success Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": 200,
  "message": "User profile updated successfully.",
  "data": {
    "id": 8,
    "full_name": "Khairul Anuar (Senior Organiser)",
    "email": "khairul.senior@eventhub.my",
    "role": "organiser",
    "phone_number": "+6019-7766999"
  }
}
```
* **Relevant HTTP Status Codes:** `200 OK`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `422 Unprocessable Entity`.

---

### 2.5 Delete User
* **Purpose:** Permanently remove an inactive user account.
* **URL:** `/users/{id}`
* **Method:** `DELETE`
* **Authentication:** `Bearer Token` (Role: `admin`)
* **Success Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": 200,
  "message": "User ID 6 has been permanently deleted."
}
```
* **Relevant HTTP Status Codes:** `200 OK`, `400 Bad Request` (Self-deletion prevention), `401 Unauthorized`, `403 Forbidden`, `404 Not Found`.

---

## 3. Venues Resource (`/venues`)

### 3.1 List Venues
* **Purpose:** Browse physical venues with optional city filtering.
* **URL:** `/venues`
* **Method:** `GET`
* **Authentication:** Public / All Roles
* **Request Query Parameters:** `?city=Kuala Lumpur`
* **Success Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": 200,
  "count": 3,
  "data": [
    {
      "id": 1,
      "venue_name": "Kuala Lumpur Convention Centre (KLCC)",
      "address": "Jalan Pinang, Kuala Lumpur City Centre",
      "city": "Kuala Lumpur",
      "capacity": 3000,
      "contact_email": "events@klcc.com.my",
      "contact_phone": "+603-23332888",
      "hourly_rate": 850.00,
      "image_url": "https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800",
      "created_at": "2026-10-13 14:00:00"
    }
  ]
}
```
* **Relevant HTTP Status Codes:** `200 OK`.

---

### 3.2 Get Venue by ID
* **Purpose:** Inspect venue details and view list of scheduled events.
* **URL:** `/venues/{id}`
* **Method:** `GET`
* **Authentication:** Public / All Roles
* **Success Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": 200,
  "data": {
    "id": 1,
    "venue_name": "Kuala Lumpur Convention Centre (KLCC)",
    "city": "Kuala Lumpur",
    "capacity": 3000,
    "hourly_rate": 850.00,
    "scheduled_events": [
      {
        "id": 1,
        "title": "Cloud Native & API Summit 2026",
        "start_time": "2026-11-15 09:00:00",
        "end_time": "2026-11-15 17:30:00",
        "status": "published"
      }
    ]
  }
}
```
* **Relevant HTTP Status Codes:** `200 OK`, `404 Not Found`.

---

### 3.3 Create Venue
* **Purpose:** Register a new conference venue or auditorium.
* **URL:** `/venues`
* **Method:** `POST`
* **Authentication:** `Bearer Token` (Role: `admin`)
* **Request Body:**
```json
{
  "venue_name": "Sunway Pyramid Convention Centre",
  "address": "3, Jalan PJS 11/15, Bandar Sunway",
  "city": "Petaling Jaya",
  "capacity": 2500,
  "contact_email": "spcc@sunway.com.my",
  "contact_phone": "+603-74928000",
  "hourly_rate": 750.00
}
```
* **Success Response (HTTP 201 Created):**
```json
{
  "success": true,
  "status": 201,
  "message": "Venue registered successfully.",
  "data": {
    "id": 6,
    "venue_name": "Sunway Pyramid Convention Centre",
    "capacity": 2500,
    "hourly_rate": 750.00
  }
}
```
* **Relevant HTTP Status Codes:** `201 Created`, `401 Unauthorized`, `403 Forbidden`, `422 Unprocessable Entity`.

---

### 3.4 Update Venue
* **Purpose:** Update venue capacity, contact details, or hourly rental cost.
* **URL:** `/venues/{id}`
* **Method:** `PUT`
* **Authentication:** `Bearer Token` (Role: `admin`)
* **Success Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": 200,
  "message": "Venue details updated successfully.",
  "data": {
    "id": 2,
    "venue_name": "Plenary Hall UPTM (Expanded)",
    "capacity": 900
  }
}
```
* **Relevant HTTP Status Codes:** `200 OK`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `422 Unprocessable Entity`.

---

### 3.5 Delete Venue
* **Purpose:** Remove venue when no events are hosted at location.
* **URL:** `/venues/{id}`
* **Method:** `DELETE`
* **Authentication:** `Bearer Token` (Role: `admin`)
* **Success Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": 200,
  "message": "Venue ID 5 deleted successfully."
}
```
* **Error Response if Events Attached (HTTP 409 Conflict):**
```json
{
  "success": false,
  "status": 409,
  "error": {
    "type": "ForeignKeyConstraintException",
    "message": "Cannot delete venue ID 1 because 2 event(s) are currently attached to it."
  }
}
```
* **Relevant HTTP Status Codes:** `200 OK`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `409 Conflict`.

---

## 4. Events Resource (`/events`)

### 4.1 List Events (Search, Filter, Sort, Pagination)
* **Purpose:** Discover events with full API management capabilities.
* **URL:** `/events`
* **Method:** `GET`
* **Authentication:** Public / All Roles
* **Query Parameters:**
  * `page` (default: 1)
  * `limit` (default: 10, max: 100)
  * `search` (keyword search title/description)
  * `category_id` (numeric filter)
  * `status` (`published`, `completed`, `cancelled`)
  * `sort` (`start_time`, `ticket_price`, `title`, `available_tickets`)
  * `order` (`asc`, `desc`)
* **Example URL:** `/events?page=1&limit=5&category_id=1&sort=ticket_price&order=asc`
* **Success Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": 200,
  "pagination": {
    "current_page": 1,
    "per_page": 5,
    "total_records": 6,
    "total_pages": 2,
    "has_next": true,
    "has_previous": false
  },
  "filters": {
    "search": null,
    "category_id": 1,
    "venue_id": null,
    "status": null,
    "sort": "ticket_price",
    "order": "ASC"
  },
  "data": [
    {
      "id": 4,
      "title": "Cyberjaya National Hackathon",
      "description": "48-hour continuous coding challenge focusing on secure API design...",
      "start_time": "2026-12-12 10:00:00",
      "ticket_price": 40.00,
      "total_tickets": 200,
      "available_tickets": 192,
      "status": "published",
      "category_name": "Technology & AI",
      "venue_name": "Cyberjaya Innovation Hub",
      "venue_city": "Cyberjaya",
      "organiser_name": "Sarah Tan (Tech Events)"
    }
  ]
}
```
* **Relevant HTTP Status Codes:** `200 OK`.

---

### 4.2 Get Event by ID
* **Purpose:** Retrieve complete details of a specific event.
* **URL:** `/events/{id}`
* **Method:** `GET`
* **Authentication:** Public
* **Success Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": 200,
  "data": {
    "id": 1,
    "title": "Cloud Native & API Summit 2026",
    "description": "Leading annual cloud architecture conference exploring REST, GraphQL, and microservices.",
    "ticket_price": 120.00,
    "total_tickets": 500,
    "available_tickets": 485,
    "status": "published",
    "category_name": "Technology & AI",
    "venue_name": "Kuala Lumpur Convention Centre (KLCC)"
  }
}
```
* **Relevant HTTP Status Codes:** `200 OK`, `404 Not Found`.

---

### 4.3 Create Event
* **Purpose:** Schedule and publish a new event.
* **URL:** `/events`
* **Method:** `POST`
* **Authentication:** `Bearer Token` (Role: `organiser` or `admin`)
* **Request Body:**
```json
{
  "title": "ASEAN Cyber Resilience Forum 2026",
  "description": "Two-day intensive cybersecurity summit addressing zero trust and API resilience.",
  "category_id": 1,
  "venue_id": 1,
  "start_time": "2026-11-25 09:00:00",
  "end_time": "2026-11-26 17:00:00",
  "ticket_price": 180.00,
  "total_tickets": 400
}
```
* **Success Response (HTTP 201 Created):**
```json
{
  "success": true,
  "status": 201,
  "message": "Event scheduled and published successfully.",
  "data": {
    "id": 7,
    "title": "ASEAN Cyber Resilience Forum 2026",
    "organiser_id": 2,
    "total_tickets": 400,
    "available_tickets": 400,
    "status": "published"
  }
}
```
* **Relevant HTTP Status Codes:** `201 Created`, `401 Unauthorized`, `403 Forbidden`, `422 Unprocessable Entity` (Date constraint violation or exceeding venue capacity).

---

### 4.4 Update Event
* **Purpose:** Update event title, pricing, or ticket capacity.
* **URL:** `/events/{id}`
* **Method:** `PUT`
* **Authentication:** `Bearer Token` (Role: `admin` or the event's `organiser`)
* **Success Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": 200,
  "message": "Event updated successfully.",
  "data": {
    "id": 1,
    "title": "Cloud Native & API Summit 2026 (Updated)",
    "ticket_price": 135.00,
    "total_tickets": 550,
    "available_tickets": 535
  }
}
```
* **Relevant HTTP Status Codes:** `200 OK`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `422 Unprocessable Entity`.

---

### 4.5 Delete Event
* **Purpose:** Remove or cancel a scheduled event.
* **URL:** `/events/{id}`
* **Method:** `DELETE`
* **Authentication:** `Bearer Token` (Role: `admin` or event's `organiser`)
* **Success Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": 200,
  "message": "Event 'Regional E-Sports Grand Championship' (ID: 6) successfully removed."
}
```
* **Relevant HTTP Status Codes:** `200 OK`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`.

---

## 5. Bookings Resource (`/bookings`)

### 5.1 List Bookings (Role-Scoped)
* **Purpose:** Retrieve reservations. Customer gets only their own bookings; Organiser gets bookings for events they host; Admin gets all.
* **URL:** `/bookings`
* **Method:** `GET`
* **Authentication:** `Bearer Token`
* **Success Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": 200,
  "role_scope": "customer",
  "count": 2,
  "data": [
    {
      "id": 1,
      "booking_reference": "BK-2026-001A",
      "ticket_quantity": 2,
      "total_amount": 240.00,
      "booking_status": "confirmed",
      "qr_code_url": "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=BK-2026-001A_USER-4_EVENT-1",
      "event_title": "Cloud Native & API Summit 2026",
      "event_date": "2026-11-15 09:00:00",
      "venue_name": "Kuala Lumpur Convention Centre (KLCC)",
      "transaction_ref": "TXN-20261101-88910",
      "payment_method": "Online Banking (FPX)",
      "created_at": "2026-10-13 14:00:00"
    }
  ]
}
```
* **Relevant HTTP Status Codes:** `200 OK`, `401 Unauthorized`.

---

### 5.2 Create Booking (With Genuine External QR API)
* **Purpose:** Reserve tickets, deduct available tickets atomically, call external QR Code Generator API, and log payment.
* **URL:** `/bookings`
* **Method:** `POST`
* **Authentication:** `Bearer Token` (Role: `customer` or `admin`)
* **Request Body:**
```json
{
  "event_id": 1,
  "ticket_quantity": 2,
  "payment_method": "Online Banking (FPX)",
  "notes": "VIP Front Row Seating"
}
```
* **Success Response (HTTP 201 Created):**
```json
{
  "success": true,
  "status": 201,
  "message": "Ticket reservation successfully confirmed!",
  "data": {
    "booking_id": 6,
    "booking_reference": "BK-2026-F41A92B1",
    "event_title": "Cloud Native & API Summit 2026",
    "ticket_quantity": 2,
    "total_amount": 240.00,
    "booking_status": "confirmed",
    "transaction_ref": "TXN-20261013-8E12A09F",
    "payment_method": "Online Banking (FPX)",
    "qr_code_url": "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=TICKET_VERIFY%7CREF%3ABK-2026-F41A92B1%7CUID%3A4%7CEID%3A1%7CISSUED%3A2026-10-13T14%3A00%3A00%2B08%3A00&margin=10&color=1e293b",
    "external_api_evidence": {
      "provider": "QRServer External REST API",
      "endpoint": "https://api.qrserver.com/v1/create-qr-code/",
      "status": 200,
      "verified": true
    }
  }
}
```
* **Relevant HTTP Status Codes:** `201 Created`, `400 Bad Request` (Event not published), `401 Unauthorized`, `404 Not Found` (Event not found), `409 Conflict` (Insufficient tickets available), `422 Unprocessable Entity`.

---

### 5.3 Cancel Booking
* **Purpose:** Cancel reservation, restore allocated tickets to event pool, and refund transaction.
* **URL:** `/bookings/{id}`
* **Method:** `DELETE`
* **Authentication:** `Bearer Token` (Customer owner or Admin)
* **Success Response (HTTP 200 OK):**
```json
{
  "success": true,
  "status": 200,
  "message": "Booking 'BK-2026-005E' has been cancelled and tickets restored to inventory.",
  "data": {
    "booking_id": 5,
    "booking_reference": "BK-2026-005E",
    "tickets_restored": 3,
    "status": "cancelled"
  }
}
```
* **Relevant HTTP Status Codes:** `200 OK`, `400 Bad Request` (Already cancelled), `401 Unauthorized`, `403 Forbidden`, `404 Not Found`.

---

## 6. Categories & Stats Endpoints

### 6.1 List Categories (`GET /categories`)
* **Purpose:** Retrieve event categories and color badges.
* **Method:** `GET`
* **Status Code:** `200 OK`

### 6.2 Executive System Statistics (`GET /stats`)
* **Purpose:** Retrieve gross revenue, total tickets reserved, venue counts, and recent transactions.
* **Method:** `GET`
* **Authentication:** `Bearer Token` (Role: `admin` or `organiser`)
* **Status Code:** `200 OK`

