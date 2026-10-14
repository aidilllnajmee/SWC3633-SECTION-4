<?php
// =====================================================================
// Venue Controller: Venue Management & Capacity Planning
// Resource: /venues
// =====================================================================

require_once dirname(__DIR__) . '/config/Database.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/middleware/RoleMiddleware.php';
require_once dirname(__DIR__) . '/middleware/ValidationMiddleware.php';

class VenueController {
    public static function getAll(): void {
        $pdo = Database::getConnection();

        $city = $_GET['city'] ?? '';
        $search = $_GET['search'] ?? '';

        $sql = "SELECT id, venue_name, address, city, capacity, contact_email, contact_phone, hourly_rate, image_url, created_at FROM venues WHERE 1=1";
        $params = [];

        if (!empty($city)) {
            $sql .= " AND LOWER(city) = LOWER(?)";
            $params[] = $city;
        }

        if (!empty($search)) {
            $sql .= " AND (venue_name LIKE ? OR address LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY venue_name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $venues = $stmt->fetchAll();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'status' => 200,
            'count' => count($venues),
            'data' => $venues
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function getById(int $id): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, venue_name, address, city, capacity, contact_email, contact_phone, hourly_rate, image_url, created_at FROM venues WHERE id = ?");
        $stmt->execute([$id]);
        $venue = $stmt->fetch();

        if (!$venue) {
            http_response_code(404);
            echo json_encode(['success' => false, 'status' => 404, 'error' => ['message' => "Venue with ID $id not found."]]);
            return;
        }

        // Fetch associated scheduled events at this venue
        $evStmt = $pdo->prepare("SELECT id, title, start_time, end_time, status FROM events WHERE venue_id = ? ORDER BY start_time ASC");
        $evStmt->execute([$id]);
        $venue['scheduled_events'] = $evStmt->fetchAll();

        http_response_code(200);
        echo json_encode(['success' => true, 'status' => 200, 'data' => $venue], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function create(): void {
        $currentUser = AuthMiddleware::authenticate();
        if (!$currentUser) return;
        RoleMiddleware::authorize($currentUser, ['admin']);

        $data = ValidationMiddleware::getJsonBody();
        ValidationMiddleware::validate($data, [
            'venue_name' => ['required', 'min:3'],
            'address' => ['required'],
            'city' => ['required'],
            'capacity' => ['required', 'integer', 'positive'],
            'contact_email' => ['required', 'email'],
            'contact_phone' => ['required'],
            'hourly_rate' => ['required', 'numeric', 'non-negative']
        ]);

        $imageUrl = $data['image_url'] ?? 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800';

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO venues (venue_name, address, city, capacity, contact_email, contact_phone, hourly_rate, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['venue_name'],
            $data['address'],
            $data['city'],
            (int)$data['capacity'],
            $data['contact_email'],
            $data['contact_phone'],
            (float)$data['hourly_rate'],
            $imageUrl
        ]);
        $newId = (int)$pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'status' => 201,
            'message' => 'Venue registered successfully.',
            'data' => array_merge(['id' => $newId], $data)
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function update(int $id): void {
        $currentUser = AuthMiddleware::authenticate();
        if (!$currentUser) return;
        RoleMiddleware::authorize($currentUser, ['admin']);

        $data = ValidationMiddleware::getJsonBody();
        ValidationMiddleware::validate($data, [
            'venue_name' => ['required', 'min:3'],
            'address' => ['required'],
            'city' => ['required'],
            'capacity' => ['required', 'integer', 'positive'],
            'contact_email' => ['required', 'email'],
            'contact_phone' => ['required'],
            'hourly_rate' => ['required', 'numeric', 'non-negative']
        ]);

        $pdo = Database::getConnection();
        $check = $pdo->prepare("SELECT id FROM venues WHERE id = ?");
        $check->execute([$id]);
        if (!$check->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'status' => 404, 'error' => ['message' => "Venue with ID $id not found."]]);
            return;
        }

        $imageUrl = $data['image_url'] ?? null;

        $stmt = $pdo->prepare("UPDATE venues SET venue_name = ?, address = ?, city = ?, capacity = ?, contact_email = ?, contact_phone = ?, hourly_rate = ?, image_url = COALESCE(?, image_url) WHERE id = ?");
        $stmt->execute([
            $data['venue_name'],
            $data['address'],
            $data['city'],
            (int)$data['capacity'],
            $data['contact_email'],
            $data['contact_phone'],
            (float)$data['hourly_rate'],
            $imageUrl,
            $id
        ]);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'status' => 200,
            'message' => 'Venue details updated successfully.',
            'data' => array_merge(['id' => $id], $data)
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function delete(int $id): void {
        $currentUser = AuthMiddleware::authenticate();
        if (!$currentUser) return;
        RoleMiddleware::authorize($currentUser, ['admin']);

        $pdo = Database::getConnection();

        // Check if existing events are scheduled at this venue
        $evCheck = $pdo->prepare("SELECT COUNT(*) AS total FROM events WHERE venue_id = ?");
        $evCheck->execute([$id]);
        $res = $evCheck->fetch();
        if ($res && (int)$res['total'] > 0) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'status' => 409,
                'error' => [
                    'type' => 'ForeignKeyConstraintException',
                    'message' => "Cannot delete venue ID $id because {$res['total']} event(s) are currently attached to it."
                ]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        }

        $del = $pdo->prepare("DELETE FROM venues WHERE id = ?");
        $del->execute([$id]);

        if ($del->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'status' => 404, 'error' => ['message' => "Venue ID $id not found."]]);
            return;
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'status' => 200, 'message' => "Venue ID $id deleted successfully."]);
    }
}
