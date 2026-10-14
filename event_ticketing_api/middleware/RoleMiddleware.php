<?php
// =====================================================================
// Role-Based Access Control (RBAC) Authorization Middleware
// =====================================================================

class RoleMiddleware {
    public static function authorize(array $user, array $allowedRoles): void {
        $userRole = strtolower($user['role'] ?? '');

        $normalizedAllowed = array_map('strtolower', $allowedRoles);

        if (!in_array($userRole, $normalizedAllowed, true)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'status' => 403,
                'error' => [
                    'type' => 'AuthorizationException',
                    'message' => "Access forbidden: Role '{$userRole}' is not permitted to access this resource. Required role(s): " . implode(', ', $allowedRoles)
                ]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            exit(0);
        }
    }
}
