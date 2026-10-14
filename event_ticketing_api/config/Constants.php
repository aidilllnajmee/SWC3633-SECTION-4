<?php
// =====================================================================
// System Configuration & Constants
// =====================================================================

class Constants {
    const JWT_SECRET = 'swc3633_event_ticketing_super_secret_jwt_key_2026';
    const JWT_EXPIRY_SECONDS = 86400; // 24 hours
    const API_KEY = 'evt_live_9f83b12a84c90e';
    const DEFAULT_PAGE_LIMIT = 10;
    const MAX_PAGE_LIMIT = 100;
    const RATE_LIMIT_MAX_REQUESTS = 100;
    const RATE_LIMIT_WINDOW_SECONDS = 60;
    const EXTERNAL_QR_API_BASE = 'https://api.qrserver.com/v1/create-qr-code/';
}
