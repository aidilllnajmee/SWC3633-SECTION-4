<?php
// =====================================================================
// Request Validation Middleware & Sanitizer
// =====================================================================

class ValidationMiddleware {
    public static function getJsonBody(): array {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'status' => 400,
                'error' => [
                    'type' => 'JsonParseException',
                    'message' => 'Malformed JSON in request payload: ' . json_last_error_msg()
                ]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            exit(0);
        }

        return is_array($decoded) ? $decoded : [];
    }

    public static function validate(array $data, array $rules): array {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                if ($rule === 'required' && ($value === null || $value === '')) {
                    $errors[$field][] = "The '{$field}' field is required.";
                } elseif ($value !== null && $value !== '') {
                    if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = "The '{$field}' must be a valid email address.";
                    }
                    if ($rule === 'numeric' && !is_numeric($value)) {
                        $errors[$field][] = "The '{$field}' must be a valid number.";
                    }
                    if ($rule === 'integer' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                        $errors[$field][] = "The '{$field}' must be an integer.";
                    }
                    if ($rule === 'positive' && floatval($value) <= 0) {
                        $errors[$field][] = "The '{$field}' must be greater than zero.";
                    }
                    if ($rule === 'non-negative' && floatval($value) < 0) {
                        $errors[$field][] = "The '{$field}' cannot be negative.";
                    }
                    if (str_starts_with($rule, 'min:')) {
                        $minLen = (int)substr($rule, 4);
                        if (strlen((string)$value) < $minLen) {
                            $errors[$field][] = "The '{$field}' must be at least {$minLen} characters.";
                        }
                    }
                    if (str_starts_with($rule, 'in:')) {
                        $allowed = explode(',', substr($rule, 3));
                        if (!in_array($value, $allowed)) {
                            $errors[$field][] = "The '{$field}' must be one of: " . implode(', ', $allowed);
                        }
                    }
                }
            }
        }

        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'status' => 422,
                'error' => [
                    'type' => 'ValidationException',
                    'message' => 'The given data failed validation.',
                    'details' => $errors
                ]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            exit(0);
        }

        return $data;
    }
}
