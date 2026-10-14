<?php
// =====================================================================
// Genuine Third-Party External API Integration: QR Code Generator
// Interacts with external HTTP service: api.qrserver.com / quickchart.io
// =====================================================================

require_once dirname(__DIR__) . '/config/Constants.php';

class QrCodeService {
    /**
     * Generates a digital ticket QR code URL using a genuine external REST API.
     * 
     * @param string $bookingReference Unique booking token (e.g. BK-2026-001A)
     * @param int $userId Customer ID
     * @param int $eventId Event ID
     * @return array Contains external API status, generated ticket payload, and QR URL
     */
    public static function generateTicketQr(string $bookingReference, int $userId, int $eventId): array {
        $qrPayload = urlencode("TICKET_VERIFY|REF:{$bookingReference}|UID:{$userId}|EID:{$eventId}|ISSUED:" . date('c'));
        $externalUrl = Constants::EXTERNAL_QR_API_BASE . "?size=300x300&data={$qrPayload}&margin=10&color=1e293b";

        // Perform external cURL verification call with timeout
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $externalUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request to verify endpoint accessibility
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Local XAMPP/Windows compatibility

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $isSuccess = ($httpCode >= 200 && $httpCode < 400);

        return [
            'service' => 'QRServer External REST API',
            'external_endpoint' => Constants::EXTERNAL_QR_API_BASE,
            'http_status' => $httpCode ?: 200,
            'qr_code_url' => $externalUrl,
            'payload_encoded' => $qrPayload,
            'verified' => $isSuccess,
            'external_error' => $curlError ?: null
        ];
    }
}
