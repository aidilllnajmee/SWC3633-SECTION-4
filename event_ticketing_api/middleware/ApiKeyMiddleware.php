<?php
// =====================================================================
// API Key Protection Middleware
// Enforces X-API-KEY header validation for public integration endpoints
// =====================================================================

require_once dirname(__DIR__) . '/config/Constants.php';

class ApiKeyMiddleware {
    public static function verify(): void {
        $headers = self::getRequestHeaders();
        $apiKey = $headers['X-Api-Key'] ?? $headers['x-api-key'] ?? $headers['X-API-KEY'] ?? '';

        if (empty($apiKey) || !hash_equals(Constants::API_KEY, $apiKey)) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'status' => 401,
                'error' => [
                    'type' => 'ApiKeyAuthenticationException',
                    'message' => 'Missing or invalid X-API-KEY security header.'
                ]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            exit(0);
        }
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
