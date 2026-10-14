<?php
// =====================================================================
// API Management: Sliding-Window / Token-Bucket Rate Limiter
// Enforces 100 requests per 60 seconds per IP address
// =====================================================================

require_once dirname(__DIR__) . '/config/Constants.php';

class RateLimiter {
    public static function check(): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $currentTime = time();
        $windowSeconds = Constants::RATE_LIMIT_WINDOW_SECONDS;
        $maxRequests = Constants::RATE_LIMIT_MAX_REQUESTS;

        $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'event_api_ratelimit';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }

        $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $ip);
        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . "rate_{$safeKey}.json";

        $records = [];
        if (file_exists($cacheFile)) {
            $content = @file_get_contents($cacheFile);
            $records = json_decode($content, true) ?: [];
        }

        // Filter out expired timestamps
        $cutoff = $currentTime - $windowSeconds;
        $records = array_values(array_filter($records, fn($t) => $t > $cutoff));

        $count = count($records);
        $remaining = max(0, $maxRequests - $count);

        header("X-RateLimit-Limit: {$maxRequests}");
        header("X-RateLimit-Remaining: {$remaining}");

        if ($count >= $maxRequests) {
            $oldest = reset($records) ?: $currentTime;
            $retryAfter = max(1, ($oldest + $windowSeconds) - $currentTime);
            header("Retry-After: {$retryAfter}");
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'status' => 429,
                'error' => [
                    'type' => 'RateLimitExceededException',
                    'message' => "Too Many Requests: Rate limit of {$maxRequests} requests per minute exceeded.",
                    'retry_after_seconds' => $retryAfter
                ]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            exit(0);
        }

        // Add current timestamp and save
        $records[] = $currentTime;
        @file_put_contents($cacheFile, json_encode($records));
    }
}
