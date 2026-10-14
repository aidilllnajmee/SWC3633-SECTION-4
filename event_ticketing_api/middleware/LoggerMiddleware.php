<?php
// =====================================================================
// API Management: Request & Response Audit Logger Middleware
// =====================================================================

require_once dirname(__DIR__) . '/config/Database.php';

class LoggerMiddleware {
    private static float $startTime = 0.0;

    public static function start(): void {
        self::$startTime = microtime(true);
    }

    public static function log(int $statusCode, ?int $userId = null): void {
        $endTime = microtime(true);
        $durationMs = round(($endTime - self::$startTime) * 1000, 2);

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // 1. Log to Database
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("INSERT INTO api_logs (request_method, request_uri, user_id, status_code, ip_address, execution_time_ms) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$method, $uri, $userId, $statusCode, $ip, $durationMs]);
        } catch (Exception $e) {
            // Silently handle if database logging is interrupted
        }

        // 2. Log to File
        $logDir = dirname(__DIR__) . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logEntry = sprintf(
            "[%s] %s %s - %d (User: %s) - %s ms - IP: %s\n",
            date('Y-m-d H:i:s'),
            $method,
            $uri,
            $statusCode,
            $userId ? (string)$userId : 'Guest',
            $durationMs,
            $ip
        );
        @file_put_contents($logDir . '/access.log', $logEntry, FILE_APPEND);
    }
}
