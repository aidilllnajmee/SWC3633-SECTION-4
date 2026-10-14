-- =====================================================================
-- SWC3633/SWC4443 WEB API DEVELOPMENT FINAL PROJECT
-- SQLite Schema & Seed Script (Portable, zero-dependency dialect)
-- =====================================================================

DROP TABLE IF EXISTS api_logs;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS venues;
DROP TABLE IF EXISTS event_categories;
DROP TABLE IF EXISTS users;

-- 1. Users
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('admin', 'organiser', 'customer')) DEFAULT 'customer',
    phone_number TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. Venues
CREATE TABLE venues (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    venue_name TEXT NOT NULL,
    address TEXT NOT NULL,
    city TEXT NOT NULL,
    capacity INTEGER NOT NULL CHECK (capacity > 0),
    contact_email TEXT NOT NULL,
    contact_phone TEXT NOT NULL,
    hourly_rate REAL NOT NULL DEFAULT 0.00 CHECK (hourly_rate >= 0),
    image_url TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. Event Categories
CREATE TABLE event_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_name TEXT NOT NULL UNIQUE,
    description TEXT,
    badge_color TEXT DEFAULT '#3b82f6',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 4. Events
CREATE TABLE events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    category_id INTEGER NOT NULL REFERENCES event_categories(id),
    venue_id INTEGER NOT NULL REFERENCES venues(id),
    organiser_id INTEGER NOT NULL REFERENCES users(id),
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    ticket_price REAL NOT NULL DEFAULT 0.00 CHECK (ticket_price >= 0),
    total_tickets INTEGER NOT NULL CHECK (total_tickets > 0),
    available_tickets INTEGER NOT NULL CHECK (available_tickets >= 0),
    status TEXT NOT NULL CHECK (status IN ('draft', 'published', 'cancelled', 'completed')) DEFAULT 'published',
    banner_image TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 5. Bookings
CREATE TABLE bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_reference TEXT NOT NULL UNIQUE,
    user_id INTEGER NOT NULL REFERENCES users(id),
    event_id INTEGER NOT NULL REFERENCES events(id),
    ticket_quantity INTEGER NOT NULL CHECK (ticket_quantity > 0),
    total_amount REAL NOT NULL CHECK (total_amount >= 0),
    booking_status TEXT NOT NULL CHECK (booking_status IN ('confirmed', 'cancelled', 'pending')) DEFAULT 'confirmed',
    qr_code_url TEXT,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 6. Payments
CREATE TABLE payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_id INTEGER NOT NULL UNIQUE REFERENCES bookings(id),
    transaction_ref TEXT NOT NULL UNIQUE,
    payment_method TEXT NOT NULL CHECK (payment_method IN ('Credit Card', 'Online Banking (FPX)', 'E-Wallet', 'PayPal')),
    amount REAL NOT NULL CHECK (amount >= 0),
    payment_status TEXT NOT NULL CHECK (payment_status IN ('completed', 'failed', 'refunded')) DEFAULT 'completed',
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 7. API Logs
CREATE TABLE api_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    request_method TEXT NOT NULL,
    request_uri TEXT NOT NULL,
    user_id INTEGER,
    status_code INTEGER NOT NULL,
    ip_address TEXT NOT NULL,
    execution_time_ms REAL NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for Query Performance Optimization
CREATE INDEX idx_user_role ON users(role);
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_event_status ON events(status);
CREATE INDEX idx_event_start ON events(start_time);
CREATE INDEX idx_event_price ON events(ticket_price);
CREATE INDEX idx_booking_ref ON bookings(booking_reference);
CREATE INDEX idx_booking_status ON bookings(booking_status);
CREATE INDEX idx_venue_city ON venues(city);

-- Sample Data (Minimum 5+ records per table)
INSERT INTO users (id, full_name, email, password_hash, role, phone_number) VALUES
(1, 'System Administrator', 'admin@eventhub.my', 'y$LRh6dQkmzAoIga.HzZXvFu7ZnWCf7iYneimkue4.D7ad70MEANVrS', 'admin', '+6012-3456789'),
(2, 'Sarah Tan (Tech Events)', 'sarah.organiser@eventhub.my', 'y$LRh6dQkmzAoIga.HzZXvFu7ZnWCf7iYneimkue4.D7ad70MEANVrS', 'organiser', '+6013-9876543'),
(3, 'Ahmad Faizal (Music Guild)', 'faizal.organiser@eventhub.my', 'y$LRh6dQkmzAoIga.HzZXvFu7ZnWCf7iYneimkue4.D7ad70MEANVrS', 'organiser', '+6019-2233445'),
(4, 'Aidil Najmee', 'aidil.customer@eventhub.my', 'y$LRh6dQkmzAoIga.HzZXvFu7ZnWCf7iYneimkue4.D7ad70MEANVrS', 'customer', '+6011-5544332'),
(5, 'Nurul Huda', 'huda.customer@eventhub.my', 'y$LRh6dQkmzAoIga.HzZXvFu7ZnWCf7iYneimkue4.D7ad70MEANVrS', 'customer', '+6017-6655443'),
(6, 'Jason Wong', 'jason.customer@eventhub.my', 'y$LRh6dQkmzAoIga.HzZXvFu7ZnWCf7iYneimkue4.D7ad70MEANVrS', 'customer', '+6018-7788990');

INSERT INTO event_categories (id, category_name, description, badge_color) VALUES
(1, 'Technology & AI', 'Workshops, hackathons, and developer conferences', '#2563eb'),
(2, 'Live Music & Concerts', 'Acoustic sessions, orchestras, and rock concerts', '#7c3aed'),
(3, 'Business & Startup', 'Networking, venture summits, and pitch competitions', '#059669'),
(4, 'Sports & Fitness', 'Marathons, esports tournaments, and fitness camps', '#ea580c'),
(5, 'Arts & Culture', 'Exhibitions, stage theatre, and culinary showcases', '#db2777');

INSERT INTO venues (id, venue_name, address, city, capacity, contact_email, contact_phone, hourly_rate, image_url) VALUES
(1, 'Kuala Lumpur Convention Centre (KLCC)', 'Jalan Pinang, Kuala Lumpur City Centre', 'Kuala Lumpur', 3000, 'events@klcc.com.my', '+603-23332888', 850.00, 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800'),
(2, 'Plenary Hall UPTM', 'Jalan 6/91, Taman Shamelin Perkasa, Cheras', 'Kuala Lumpur', 800, 'hall@uptm.edu.my', '+603-92837186', 250.00, 'https://images.unsplash.com/photo-1511578314322-379afb476865?w=800'),
(3, 'Spice Arena Penang', '180, Jalan Tun Dr Awang, Bayan Lepas', 'Penang', 5000, 'contact@spicearena.my', '+604-6435988', 1200.00, 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=800'),
(4, 'Cyberjaya Innovation Hub', 'Persiaran Multimedia, Cyberjaya', 'Cyberjaya', 450, 'bookings@cyberhub.my', '+603-83128000', 180.00, 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?w=800'),
(5, 'Axiata Arena Bukit Jalil', 'Lebuhraya Bukit Jalil, Bukit Jalil', 'Kuala Lumpur', 12000, 'info@axiataarena.com.my', '+603-89920888', 2500.00, 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=800');

INSERT INTO events (id, title, description, category_id, venue_id, organiser_id, start_time, end_time, ticket_price, total_tickets, available_tickets, status, banner_image) VALUES
(1, 'Cloud Native & API Summit 2026', 'Leading annual cloud architecture conference exploring REST, GraphQL, and microservices.', 1, 1, 2, '2026-11-15 09:00:00', '2026-11-15 17:30:00', 120.00, 500, 485, 'published', 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800'),
(2, 'Symphony Under The Stars', 'A mesmerizing classical orchestral performance featuring international soloists.', 2, 2, 3, '2026-11-20 20:00:00', '2026-11-20 23:00:00', 85.00, 300, 290, 'published', 'https://images.unsplash.com/photo-1465847899084-d164df4dedc6?w=800'),
(3, 'Malaysia FinTech & AI Expo', 'Connect with 100+ fintech startups, venture capitalists, and digital banking leaders.', 3, 1, 2, '2026-12-05 08:30:00', '2026-12-06 18:00:00', 250.00, 1000, 980, 'published', 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=800'),
(4, 'Cyberjaya National Hackathon', '48-hour continuous coding challenge focusing on secure API design and IoT solutions.', 1, 4, 2, '2026-12-12 10:00:00', '2026-12-14 12:00:00', 40.00, 200, 192, 'published', 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=800'),
(5, 'Acoustic Indie Fest 2026', 'Independent songwriters showcase featuring top acoustic performers in Southeast Asia.', 2, 3, 3, '2026-12-18 19:30:00', '2026-12-18 23:30:00', 65.00, 800, 785, 'published', 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=800'),
(6, 'Regional E-Sports Grand Championship', 'Top Dota 2 and Valorant teams battle for the RM100,000 championship trophy.', 4, 5, 3, '2026-12-28 11:00:00', '2026-12-29 21:00:00', 50.00, 3000, 2970, 'published', 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800');

INSERT INTO bookings (id, booking_reference, user_id, event_id, ticket_quantity, total_amount, booking_status, qr_code_url, notes) VALUES
(1, 'BK-2026-001A', 4, 1, 2, 240.00, 'confirmed', 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=BK-2026-001A_USER-4_EVENT-1', 'Early bird attendee passes'),
(2, 'BK-2026-002B', 5, 2, 2, 170.00, 'confirmed', 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=BK-2026-002B_USER-5_EVENT-2', 'VIP Balcony Seating'),
(3, 'BK-2026-003C', 6, 3, 1, 250.00, 'confirmed', 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=BK-2026-003C_USER-6_EVENT-3', 'Standard Corporate delegate'),
(4, 'BK-2026-004D', 4, 4, 2, 80.00, 'confirmed', 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=BK-2026-004D_USER-4_EVENT-4', 'Hackathon student team admission'),
(5, 'BK-2026-005E', 5, 5, 3, 195.00, 'confirmed', 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=BK-2026-005E_USER-5_EVENT-5', 'Group admission with friends');

INSERT INTO payments (id, booking_id, transaction_ref, payment_method, amount, payment_status, payment_date) VALUES
(1, 1, 'TXN-20261101-88910', 'Online Banking (FPX)', 240.00, 'completed', '2026-10-01 10:15:30'),
(2, 2, 'TXN-20261102-12093', 'Credit Card', 170.00, 'completed', '2026-10-02 14:22:10'),
(3, 3, 'TXN-20261103-77641', 'Online Banking (FPX)', 250.00, 'completed', '2026-10-03 09:40:05'),
(4, 4, 'TXN-20261104-33219', 'E-Wallet', 80.00, 'completed', '2026-10-04 18:05:44'),
(5, 5, 'TXN-20261105-99432', 'Credit Card', 195.00, 'completed', '2026-10-05 21:12:18');

INSERT INTO api_logs (id, request_method, request_uri, user_id, status_code, ip_address, execution_time_ms) VALUES
(1, 'POST', '/api/v1/auth/login', 1, 200, '127.0.0.1', 42.15),
(2, 'GET', '/api/v1/events?page=1&limit=10', NULL, 200, '127.0.0.1', 12.30),
(3, 'POST', '/api/v1/bookings', 4, 201, '127.0.0.1', 88.64),
(4, 'GET', '/api/v1/venues', 1, 200, '127.0.0.1', 8.45),
(5, 'GET', '/api/v1/users', 1, 200, '127.0.0.1', 15.10);

