<?php
// =====================================================================
// Central REST API Router & Front Controller
// Entry Point: /api/v1/*
// =====================================================================

require_once __DIR__ . '/config/Cors.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/Constants.php';
require_once __DIR__ . '/middleware/RateLimiter.php';
require_once __DIR__ . '/middleware/LoggerMiddleware.php';
require_once __DIR__ . '/middleware/ApiKeyMiddleware.php';

require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/VenueController.php';
require_once __DIR__ . '/controllers/EventController.php';
require_once __DIR__ . '/controllers/BookingController.php';
require_once __DIR__ . '/controllers/StatsController.php';

// 1. Handle Cross-Origin Resource Sharing (CORS) & Pre-flight
Cors::handle();

// 2. Start Execution Timer for Audit Logging
LoggerMiddleware::start();

// 3. Centralised Error & Exception Handling
set_exception_handler(function (Throwable $e) {
    $code = $e->getCode();
    $statusCode = ($code >= 400 && $code < 600) ? (int)$code : 500;
    http_response_code($statusCode);
    
    LoggerMiddleware::log($statusCode);
    
    echo json_encode([
        'success' => false,
        'status' => $statusCode,
        'error' => [
            'type' => get_class($e),
            'message' => $e->getMessage()
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit(0);
});

// 4. Rate Limiting Middleware (100 req/min)
RateLimiter::check();

// 5. Parse Request Path and Method
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Normalize URL path to identify route under /api/v1
// Example matches: /api/v1/events, /event_ticketing_system/api/v1/events, /api/events
$route = '';
if (preg_match('#(?:/api/v1|/api)(/[^?]*)#i', $requestUri, $matches)) {
    $route = rtrim($matches[1], '/');
} else {
    // If accessed directly at /index.php/events
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $route = substr($requestUri, strlen(dirname($scriptName)));
    $route = rtrim($route, '/');
}

if (empty($route)) {
    $route = '/';
}

$segments = explode('/', trim($route, '/'));
$resource = $segments[0] ?? '';
$id = (isset($segments[1]) && is_numeric($segments[1])) ? (int)$segments[1] : null;
$subAction = $segments[1] ?? null;

// =====================================================================
// ROUTING TABLE
// =====================================================================
$statusCode = 200;

try {
    switch ($resource) {
        case '':
        case 'health':
            echo json_encode([
                'success' => true,
                'status' => 200,
                'message' => 'Event Ticketing & Venue Booking RESTful Web API is online.',
                'version' => '1.0.0',
                'database_driver' => Database::getDriver(),
                'server_time' => date('c'),
                'endpoints' => [
                    'auth' => '/api/v1/auth (POST login, register, GET me)',
                    'users' => '/api/v1/users (GET, POST, PUT, DELETE)',
                    'events' => '/api/v1/events (GET, POST, PUT, DELETE with search/filter/sort/pagination)',
                    'venues' => '/api/v1/venues (GET, POST, PUT, DELETE)',
                    'bookings' => '/api/v1/bookings (GET, POST, DELETE with QR verification)',
                    'categories' => '/api/v1/categories (GET)',
                    'stats' => '/api/v1/stats (GET)'
                ]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            break;

        // AUTHENTICATION
        case 'auth':
            if ($subAction === 'login' && $method === 'POST') {
                AuthController::login();
            } elseif ($subAction === 'register' && $method === 'POST') {
                AuthController::register();
            } elseif ($subAction === 'me' && $method === 'GET') {
                AuthController::me();
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'status' => 404, 'error' => ['message' => 'Auth endpoint not found.']]);
            }
            break;

        // USERS RESOURCE (/users)
        case 'users':
            if ($method === 'GET') {
                $id !== null ? UserController::getById($id) : UserController::getAll();
            } elseif ($method === 'POST') {
                UserController::create();
            } elseif ($method === 'PUT' || $method === 'PATCH') {
                if ($id === null) throw new Exception("User ID required for update", 400);
                UserController::update($id);
            } elseif ($method === 'DELETE') {
                if ($id === null) throw new Exception("User ID required for deletion", 400);
                UserController::delete($id);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'status' => 405, 'error' => ['message' => 'Method Not Allowed']]);
            }
            break;

        // VENUES RESOURCE (/venues)
        case 'venues':
            if ($method === 'GET') {
                $id !== null ? VenueController::getById($id) : VenueController::getAll();
            } elseif ($method === 'POST') {
                VenueController::create();
            } elseif ($method === 'PUT' || $method === 'PATCH') {
                if ($id === null) throw new Exception("Venue ID required for update", 400);
                VenueController::update($id);
            } elseif ($method === 'DELETE') {
                if ($id === null) throw new Exception("Venue ID required for deletion", 400);
                VenueController::delete($id);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'status' => 405, 'error' => ['message' => 'Method Not Allowed']]);
            }
            break;

        // EVENTS RESOURCE (/events)
        case 'events':
            if ($method === 'GET') {
                $id !== null ? EventController::getById($id) : EventController::getAll();
            } elseif ($method === 'POST') {
                EventController::create();
            } elseif ($method === 'PUT' || $method === 'PATCH') {
                if ($id === null) throw new Exception("Event ID required for update", 400);
                EventController::update($id);
            } elseif ($method === 'DELETE') {
                if ($id === null) throw new Exception("Event ID required for deletion", 400);
                EventController::delete($id);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'status' => 405, 'error' => ['message' => 'Method Not Allowed']]);
            }
            break;

        // BOOKINGS RESOURCE (/bookings)
        case 'bookings':
            if ($method === 'GET') {
                $id !== null ? BookingController::getById($id) : BookingController::getAll();
            } elseif ($method === 'POST') {
                BookingController::create();
            } elseif ($method === 'DELETE') {
                if ($id === null) throw new Exception("Booking ID required for cancellation", 400);
                BookingController::cancel($id);
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'status' => 405, 'error' => ['message' => 'Method Not Allowed']]);
            }
            break;

        // CATEGORIES
        case 'categories':
            if ($method === 'GET') {
                CategoryController::getAll();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'status' => 405, 'error' => ['message' => 'Method Not Allowed']]);
            }
            break;

        // STATS & KPI METRICS
        case 'stats':
            if ($method === 'GET') {
                StatsController::getSummary();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'status' => 405, 'error' => ['message' => 'Method Not Allowed']]);
            }
            break;

        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'status' => 404,
                'error' => [
                    'type' => 'RouteNotFoundException',
                    'message' => "Resource endpoint '/$resource' does not exist."
                ]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            break;
    }
} finally {
    $currentStatus = http_response_code() ?: 200;
    LoggerMiddleware::log($currentStatus);
}
