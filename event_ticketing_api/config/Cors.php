<?php
// =====================================================================
// CORS (Cross-Origin Resource Sharing) Handler
// =====================================================================

class Cors {
    public static function handle(): void {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY, X-Requested-With");
        header("Access-Control-Expose-Headers: X-RateLimit-Limit, X-RateLimit-Remaining, Retry-After");
        header("Content-Type: application/json; charset=UTF-8");

        // Handle pre-flight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit(0);
        }
    }
}
