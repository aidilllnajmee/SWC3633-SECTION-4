<?php
// =====================================================================
// Auth Controller: Login, Registration, Current Profile
// =====================================================================

require_once dirname(__DIR__) . '/config/Database.php';
require_once dirname(__DIR__) . '/services/JwtService.php';
require_once dirname(__DIR__) . '/middleware/ValidationMiddleware.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';

class AuthController {
    public static function register(): void {
        $data = ValidationMiddleware::getJsonBody();
        ValidationMiddleware::validate($data, [
            'full_name' => ['required', 'min:3'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
            'role' => ['in:customer,organiser,admin']
        ]);

        $pdo = Database::getConnection();

        // Check if email already registered
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'status' => 409,
                'error' => [
                    'type' => 'DuplicateEmailException',
                    'message' => 'An account with this email address already exists.'
                ]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        }

        $role = $data['role'] ?? 'customer';
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $phone = $data['phone_number'] ?? null;

        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, phone_number) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$data['full_name'], $data['email'], $hashedPassword, $role, $phone]);

        $newUserId = (int)$pdo->lastInsertId();

        $tokenPayload = [
            'sub' => $newUserId,
            'email' => $data['email'],
            'name' => $data['full_name'],
            'role' => $role
        ];
        $token = JwtService::generateToken($tokenPayload);

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'status' => 201,
            'message' => 'User registration successful.',
            'data' => [
                'user' => [
                    'id' => $newUserId,
                    'full_name' => $data['full_name'],
                    'email' => $data['email'],
                    'role' => $role,
                    'phone_number' => $phone
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => Constants::JWT_EXPIRY_SECONDS
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function login(): void {
        $data = ValidationMiddleware::getJsonBody();
        ValidationMiddleware::validate($data, [
            'email' => ['required', 'email'],
            'password' => ['required']
        ]);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, full_name, email, password_hash, role, phone_number FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'status' => 401,
                'error' => [
                    'type' => 'InvalidCredentialsException',
                    'message' => 'Invalid email or password combination.'
                ]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        }

        $tokenPayload = [
            'sub' => (int)$user['id'],
            'email' => $user['email'],
            'name' => $user['full_name'],
            'role' => $user['role']
        ];
        $token = JwtService::generateToken($tokenPayload);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'status' => 200,
            'message' => 'Authentication successful.',
            'data' => [
                'user' => [
                    'id' => (int)$user['id'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'phone_number' => $user['phone_number']
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => Constants::JWT_EXPIRY_SECONDS
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function me(): void {
        $currentUser = AuthMiddleware::authenticate();
        if (!$currentUser) return;

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, full_name, email, role, phone_number, created_at FROM users WHERE id = ?");
        $stmt->execute([$currentUser['sub']]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'status' => 404,
                'error' => ['type' => 'UserNotFoundException', 'message' => 'User profile not found.']
            ]);
            return;
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'status' => 200,
            'data' => $user
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
