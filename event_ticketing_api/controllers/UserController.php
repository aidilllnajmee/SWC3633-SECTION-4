<?php
// =====================================================================
// User Controller: Administrative User CRUD & Management
// Resource: /users
// =====================================================================

require_once dirname(__DIR__) . '/config/Database.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/middleware/RoleMiddleware.php';
require_once dirname(__DIR__) . '/middleware/ValidationMiddleware.php';

class UserController {
    public static function getAll(): void {
        $currentUser = AuthMiddleware::authenticate();
        if (!$currentUser) return;
        RoleMiddleware::authorize($currentUser, ['admin']);

        $pdo = Database::getConnection();

        $roleFilter = $_GET['role'] ?? '';
        $search = $_GET['search'] ?? '';

        $sql = "SELECT id, full_name, email, role, phone_number, created_at FROM users WHERE 1=1";
        $params = [];

        if (!empty($roleFilter)) {
            $sql .= " AND role = ?";
            $params[] = $roleFilter;
        }

        if (!empty($search)) {
            $sql .= " AND (full_name LIKE ? OR email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'status' => 200,
            'count' => count($users),
            'data' => $users
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function getById(int $id): void {
        $currentUser = AuthMiddleware::authenticate();
        if (!$currentUser) return;

        // Admin can view anyone, normal users can only view their own profile
        if ($currentUser['role'] !== 'admin' && (int)$currentUser['sub'] !== $id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'status' => 403, 'error' => ['message' => 'Unauthorized profile view.']]);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, full_name, email, role, phone_number, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'status' => 404, 'error' => ['message' => "User with ID $id not found."]]);
            return;
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'status' => 200, 'data' => $user], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function create(): void {
        $currentUser = AuthMiddleware::authenticate();
        if (!$currentUser) return;
        RoleMiddleware::authorize($currentUser, ['admin']);

        $data = ValidationMiddleware::getJsonBody();
        ValidationMiddleware::validate($data, [
            'full_name' => ['required', 'min:3'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
            'role' => ['required', 'in:admin,organiser,customer']
        ]);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'status' => 409, 'error' => ['message' => 'Email address already exists.']]);
            return;
        }

        $hashed = password_hash($data['password'], PASSWORD_BCRYPT);
        $phone = $data['phone_number'] ?? null;

        $insertStmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, phone_number) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->execute([$data['full_name'], $data['email'], $hashed, $data['role'], $phone]);
        $newId = (int)$pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'status' => 201,
            'message' => 'User created successfully.',
            'data' => [
                'id' => $newId,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'phone_number' => $phone
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function update(int $id): void {
        $currentUser = AuthMiddleware::authenticate();
        if (!$currentUser) return;

        // Only Admin or the user themselves can update
        if ($currentUser['role'] !== 'admin' && (int)$currentUser['sub'] !== $id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'status' => 403, 'error' => ['message' => 'Forbidden update operation.']]);
            return;
        }

        $data = ValidationMiddleware::getJsonBody();
        ValidationMiddleware::validate($data, [
            'full_name' => ['required', 'min:3'],
            'email' => ['required', 'email']
        ]);

        $pdo = Database::getConnection();

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['success' => false, 'status' => 404, 'error' => ['message' => "User with ID $id not found."]]);
            return;
        }

        // Email conflict check
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$data['email'], $id]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'status' => 409, 'error' => ['message' => 'Email is already taken by another account.']]);
            return;
        }

        $roleToSet = ($currentUser['role'] === 'admin' && !empty($data['role'])) ? $data['role'] : $existing['role'];
        $phone = $data['phone_number'] ?? null;

        if (!empty($data['password'])) {
            $hashed = password_hash($data['password'], PASSWORD_BCRYPT);
            $update = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password_hash = ?, role = ?, phone_number = ? WHERE id = ?");
            $update->execute([$data['full_name'], $data['email'], $hashed, $roleToSet, $phone, $id]);
        } else {
            $update = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, phone_number = ? WHERE id = ?");
            $update->execute([$data['full_name'], $data['email'], $roleToSet, $phone, $id]);
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'status' => 200,
            'message' => 'User profile updated successfully.',
            'data' => [
                'id' => $id,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'role' => $roleToSet,
                'phone_number' => $phone
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function delete(int $id): void {
        $currentUser = AuthMiddleware::authenticate();
        if (!$currentUser) return;
        RoleMiddleware::authorize($currentUser, ['admin']);

        // Prevent self deletion
        if ((int)$currentUser['sub'] === $id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'status' => 400, 'error' => ['message' => 'Administrators cannot delete their own active account.']]);
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'status' => 404, 'error' => ['message' => "User with ID $id not found."]]);
            return;
        }

        $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $del->execute([$id]);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'status' => 200,
            'message' => "User ID $id has been permanently deleted."
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
