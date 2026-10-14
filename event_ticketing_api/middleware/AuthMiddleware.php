<?php
// =====================================================================
// JWT Authentication Middleware
// =====================================================================

require_once dirname(__DIR__) . '/services/JwtService.php';

class AuthMiddleware {
    public static function authenticate(): ?array {
        $headers = self::getRequestHeaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (empty($authHeader)) {
            self::denyAccess("Authentication token is missing. Please provide 'Authorization: Bearer <token>'", 401);
            return null;
        }

        if (!preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            self::denyAccess("Invalid Authorization header format. Expected 'Bearer <token>'", 401);
            return null;
        }

        $token = $matches[1];
        $payload = JwtService::validateToken($token);

        if (!$payload) {
            self::denyAccess("Token is invalid, malformed, or has expired. Please re-authenticate.", 401);
            return null;
        }

        return $payload;
    }

    private static function denyAccess(string $message, int $statusCode): void {
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'status' => $statusCode,
            'error' => [
                'type' => 'AuthenticationException',
                'message' => $message
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit(0);
    }

    private static function getRequestHeaders(): array {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if ($headers !== false) {
                return $headers;
            }
        }
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$key] = $value;
            }
        }
        return $headers;
    }
}
