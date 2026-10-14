<?php
// =====================================================================
// Category & Stats Controllers
// =====================================================================

require_once dirname(__DIR__) . '/config/Database.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/middleware/RoleMiddleware.php';

class CategoryController {
    public static function getAll(): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, category_name, description, badge_color, created_at FROM event_categories ORDER BY id ASC");
        $categories = $stmt->fetchAll();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'status' => 200,
            'count' => count($categories),
            'data' => $categories
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}

class StatsController {
    public static function getSummary(): void {
        $currentUser = AuthMiddleware::authenticate();
        if (!$currentUser) return;
        RoleMiddleware::authorize($currentUser, ['admin', 'organiser']);

        $pdo = Database::getConnection();

        // Total metrics
        $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $totalVenues = (int)$pdo->query("SELECT COUNT(*) FROM venues")->fetchColumn();
        $totalEvents = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
        $totalBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'confirmed'")->fetchColumn();
        $totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE booking_status = 'confirmed'")->fetchColumn();
        $totalTicketsSold = (int)$pdo->query("SELECT COALESCE(SUM(ticket_quantity), 0) FROM bookings WHERE booking_status = 'confirmed'")->fetchColumn();

        // Recent bookings
        $recentBookings = $pdo->query("SELECT b.booking_reference, b.ticket_quantity, b.total_amount, b.created_at, e.title AS event_title, u.full_name AS customer_name FROM bookings b JOIN events e ON b.event_id = e.id JOIN users u ON b.user_id = u.id ORDER BY b.id DESC LIMIT 5")->fetchAll();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'status' => 200,
            'data' => [
                'overview' => [
                    'total_users' => $totalUsers,
                    'total_venues' => $totalVenues,
                    'total_events' => $totalEvents,
                    'total_confirmed_bookings' => $totalBookings,
                    'total_revenue' => $totalRevenue,
                    'total_tickets_sold' => $totalTicketsSold
                ],
                'recent_activity' => $recentBookings
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
