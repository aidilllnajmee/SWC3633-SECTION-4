<?php
// =====================================================================
// JWT (JSON Web Token) Service - RFC 7519 Compliant HMAC-SHA256
// =====================================================================

require_once dirname(__DIR__) . '/config/Constants.php';

class JwtService {
    public static function generateToken(array $payload, int $expiry = Constants::JWT_EXPIRY_SECONDS): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        $issuedAt = time();
        $payload['iat'] = $issuedAt;
        $payload['exp'] = $issuedAt + $expiry;
        
        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, Constants::JWT_SECRET, true);
        $base64UrlSignature = self::base64UrlEncode($signature);
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function validateToken(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        list($headerB64, $payloadB64, $sigB64) = $parts;

        // Verify header
        $header = json_decode(self::base64UrlDecode($headerB64), true);
        if (!$header || ($header['alg'] ?? '') !== 'HS256') {
            return null;
        }

        // Verify signature
        $expectedSig = hash_hmac('sha256', $headerB64 . "." . $payloadB64, Constants::JWT_SECRET, true);
        $providedSig = self::base64UrlDecode($sigB64);

        if (!hash_equals($expectedSig, $providedSig)) {
            return null;
        }

        // Verify payload and expiration
        $payload = json_decode(self::base64UrlDecode($payloadB64), true);
        if (!$payload) {
            return null;
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null; // Token expired
        }

        return $payload;
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
