<?php
// =====================================================================
// Event Controller: Event Management, Filtering, Search, Sorting & Pagination
// Resource: /events
// =====================================================================

require_once dirname(__DIR__) . '/config/Database.php';
require_once dirname(__DIR__) . '/config/Constants.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/middleware/RoleMiddleware.php';
require_once dirname(__DIR__) . '/middleware/ValidationMiddleware.php';

class EventController {
    public static function getAll(): void {
        $pdo = Database::getConnection();

        // 1. Query parameters extraction
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(Constants::MAX_PAGE_LIMIT, max(1, (int)($_GET['limit'] ?? Constants::DEFAULT_PAGE_LIMIT)));
        $offset = ($page - 1) * $limit;

        $search = trim($_GET['search'] ?? '');
        $categoryId = isset($_GET['category_id']) && is_numeric($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $venueId = isset($_GET['venue_id']) && is_numeric($_GET['venue_id']) ? (int)$_GET['venue_id'] : null;
        $status = $_GET['status'] ?? null;
        $sortBy = strtolower($_GET['sort'] ?? 'start_time');
        $order = strtolower($_GET['order'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

        // Allowed sort columns (SQL Injection prevention)
        $allowedSorts = [
            'start_time' => 'e.start_time',
            'ticket_price' => 'e.ticket_price',
            'title' => 'e.title',
            'available_tickets' => 'e.available_tickets',
            'created_at' => 'e.created_at'
        ];
        $sortColumn = $allowedSorts[$sortBy] ?? 'e.start_time';

        // Build WHERE clause
        $where = ["1=1"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(e.title LIKE ? OR e.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($categoryId !== null) {
            $where[] = "e.category_id = ?";
            $params[] = $categoryId;
        }

        if ($venueId !== null) {
            $where[] = "e.venue_id = ?";
            $params[] = $venueId;
        }

        if (!empty($status)) {
            $where[] = "e.status = ?";
            $params[] = $status;
        }

        $whereSql = implode(" AND ", $where);

        // Count total records for pagination metadata
        $countSql = "SELECT COUNT(*) AS total FROM events e WHERE $whereSql";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = (int)$countStmt->fetch()['total'];

        // Main data retrieval with JOINs
        $sql = "SELECT 
                    e.id, e.title, e.description, e.start_time, e.end_time, 
                    e.ticket_price, e.total_tickets, e.available_tickets, e.status, e.banner_image, e.created_at,
                    c.id AS category_id, c.category_name, c.badge_color,
                    v.id AS venue_id, v.venue_name, v.address AS venue_address, v.city AS venue_city, v.capacity AS venue_capacity,
                    u.id AS organiser_id, u.full_name AS organiser_name, u.email AS organiser_email
                FROM events e
                JOIN event_categories c ON e.category_id = c.id
                JOIN venues v ON e.venue_id = v.id
                JOIN users u ON e.organiser_id = u.id
                WHERE $whereSql
                ORDER BY $sortColumn $order
                LIMIT ? OFFSET ?";

        $fetchParams = array_merge($params, [$limit, $offset]);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($fetchParams);
        $events = $stmt->fetchAll();

        $totalPages = (int)ceil($totalRecords / $limit);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'status' => 200,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_records' => $totalRecords,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_previous' => $page > 1
            ],
            'filters' => [
                'search' => $search ?: null,
                'category_id' => $categoryId,
                'venue_id' => $venueId,
                'status' => $status,
                'sort' => $sortBy,
                'order' => $order
            ],
            'data' => $events
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function getById(int $id): void {
        $pdo = Database::getConnection();
        $sql = "SELECT 
                    e.id, e.title, e.description, e.start_time, e.end_time, 
                    e.ticket_price, e.total_tickets, e.available_tickets, e.status, e.banner_image, e.created_at,
                    c.id AS category_id, c.category_name, c.badge_color,
                    v.id AS venue_id, v.venue_name, v.address AS venue_address, v.city AS venue_city, v.capacity AS venue_capacity,
                    u.id AS organiser_id, u.full_name AS organiser_name, u.email AS organiser_email
                FROM events e
                JOIN event_categories c ON e.category_id = c.id
                JOIN venues v ON e.venue_id = v.id
                JOIN users u ON e.organiser_id = u.id
                WHERE e.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $event = $stmt->fetch();

        if (!$event) {
            http_response_code(404);
            echo json_encode(['success' => false, 'status' => 404, 'error' => ['message' => "Event with ID $id not found."]]);
            return;
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'status' => 200, 'data' => $event], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function create(): void {
        $currentUser = AuthMiddleware::authenticate();
        if (!$currentUser) return;
        RoleMiddleware::authorize($currentUser, ['admin', 'organiser']);

        $data = ValidationMiddleware::getJsonBody();
        ValidationMiddleware::validate($data, [
            'title' => ['required', 'min:3'],
            'description' => ['required', 'min:10'],
            'category_id' => ['required', 'integer'],
            'venue_id' => ['required', 'integer'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'ticket_price' => ['required', 'numeric', 'non-negative'],
            'total_tickets' => ['required', 'integer', 'positive']
        ]);

        $pdo = Database::getConnection();

        // Business rule 1: Date coherence (end_time > start_time)
        $start = strtotime($data['start_time']);
        $end = strtotime($data['end_time']);
        if (!$start || !$end || $end <= $start) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'status' => 422,
                'error' => ['message' => 'Event end_time must be strictly after start_time.']
            ]);
            return;
        }

        // Business rule 2: Venue validation and capacity bounds
        $vStmt = $pdo->prepare("SELECT id, capacity FROM venues WHERE id = ?");
        $vStmt->execute([(int)$data['venue_id']]);
        $venue = $vStmt->fetch();
        if (!$venue) {
            http_response_code(404);
            echo json_encode(['success' => false, 'status' => 404, 'error' => ['message' => "Selected venue does not exist."]]);
            return;
        }

        if ((int)$data['total_tickets'] > (int)$venue['capacity']) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'status' => 422,
                'error' => ['message' => "Total tickets ({$data['total_tickets']}) cannot exceed venue maximum capacity ({$venue['capacity']})."]
            ]);
            return;
        }

        // Business rule 3: Category validation
        $cStmt = $pdo->prepare("SELECT id FROM event_categories WHERE id = ?");
        $cStmt->execute([(int)$data['category_id']]);
        if (!$cStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'status' => 404, 'error' => ['message' => "Selected category does not exist."]]);
            return;
        }

        $organiserId = ($currentUser['role'] === 'admin' && !empty($data['organiser_id']))
            ? (int)$data['organiser_id']
            : (int)$currentUser['sub'];

        $bannerImage = $data['banner_image'] ?? 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800';
        $status = $data['status'] ?? 'published';
        $totalTickets = (int)$data['total_tickets'];

        $stmt = $pdo->prepare("INSERT INTO events (title, description, category_id, venue_id, organiser_id, start_time, end_time, ticket_price, total_tickets, available_tickets, status, banner_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['title'],
            $data['description'],
            (int)$data['category_id'],
            (int)$data['venue_id'],
            $organiserId,
            date('Y-m-d H:i:s', $start),
            date('Y-m-d H:i:s', $end),
            (float)$data['ticket_price'],
            $totalTickets,
            $totalTickets, // Initially all tickets available
            $status,
            $bannerImage
        ]);
        $newId = (int)$pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'status' => 201,
            'message' => 'Event scheduled and published successfully.',
            'data' => [
                'id' => $newId,
                'title' => $data['title'],
                'organiser_id' => $organiserId,
                'venue_id' => (int)$data['venue_id'],
                'total_tickets' => $totalTickets,
                'available_tickets' => $totalTickets,
                'ticket_price' => (float)$data['ticket_price'],
                'status' => $status
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function update(int $id): void {
        $currentUser = AuthMiddleware::authenticate();
        if (!$currentUser) return;
        RoleMiddleware::authorize($currentUser, ['admin', 'organiser']);

        $pdo = Database::getConnection();
        $check = $pdo->prepare("SELECT id, organiser_id, total_tickets, available_tickets FROM events WHERE id = ?");
        $check->execute([$id]);
        $existing = $check->fetch();

        if (!$existing) {
            http_response_code(404);
            echo json_encode(['success' => false, 'status' => 404, 'error' => ['message' => "Event with ID $id not found."]]);
            return;
        }

        // Organiser can only edit their own events; Admin can edit any
        if ($currentUser['role'] !== 'admin' && (int)$currentUser['sub'] !== (int)$existing['organiser_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'status' => 403, 'error' => ['message' => 'You do not have permission to modify events created by another organiser.']]);
            return;
        }

        $data = ValidationMiddleware::getJsonBody();
        ValidationMiddleware::validate($data, [
            'title' => ['required', 'min:3'],
            'description' => ['required'],
            'ticket_price' => ['required', 'numeric', 'non-negative']
        ]);

        $soldTickets = (int)$existing['total_tickets'] - (int)$existing['available_tickets'];
        $newTotal = isset($data['total_tickets']) ? (int)$data['total_tickets'] : (int)$existing['total_tickets'];

        if ($newTotal < $soldTickets) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'status' => 422,
                'error' => ['message' => "Cannot reduce total tickets below already sold count ($soldTickets)."]
            ]);
            return;
        }

        $newAvailable = $newTotal - $soldTickets;
        $status = $data['status'] ?? null;
        $banner = $data['banner_image'] ?? null;

        $stmt = $pdo->prepare("UPDATE events SET 
            title = ?, 
            description = ?, 
            ticket_price = ?, 
            total_tickets = ?, 
            available_tickets = ?,
            status = COALESCE(?, status),
            banner_image = COALESCE(?, banner_image)
            WHERE id = ?");
        $stmt->execute([
            $data['title'],
            $data['description'],
            (float)$data['ticket_price'],
            $newTotal,
            $newAvailable,
            $status,
            $banner,
            $id
        ]);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'status' => 200,
            'message' => 'Event updated successfully.',
            'data' => [
                'id' => $id,
                'title' => $data['title'],
                'ticket_price' => (float)$data['ticket_price'],
                'total_tickets' => $newTotal,
                'available_tickets' => $newAvailable
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function delete(int $id): void {
        $currentUser = AuthMiddleware::authenticate();
        if (!$currentUser) return;
        RoleMiddleware::authorize($currentUser, ['admin', 'organiser']);

        $pdo = Database::getConnection();
        $check = $pdo->prepare("SELECT id, organiser_id, title FROM events WHERE id = ?");
        $check->execute([$id]);
        $existing = $check->fetch();

        if (!$existing) {
            http_response_code(404);
            echo json_encode(['success' => false, 'status' => 404, 'error' => ['message' => "Event with ID $id not found."]]);
            return;
        }

        if ($currentUser['role'] !== 'admin' && (int)$currentUser['sub'] !== (int)$existing['organiser_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'status' => 403, 'error' => ['message' => 'You do not have permission to delete events created by another organiser.']]);
            return;
        }

        $del = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $del->execute([$id]);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'status' => 200,
            'message' => "Event '{$existing['title']}' (ID: $id) successfully removed."
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
