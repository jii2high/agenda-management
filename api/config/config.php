<?php
/**
 * API Configuration
 * Konfigurasi umum untuk Agenda Management System API
 */

// Prevent direct access
if (!defined('API_ACCESS')) {
    define('API_ACCESS', true);
}

// Application Information
define('API_VERSION', '1.0.0');
define('APP_NAME', 'Agenda Management System');
define('SCHOOL_NAME', 'SMK Negeri 1 Kota Bekasi');
define('APP_DESCRIPTION', 'Sistem Manajemen Agenda Sekolah');

// Timezone
date_default_timezone_set('Asia/Jakarta');
define('TIMEZONE', 'Asia/Jakarta');

// Security Configuration
define('PASSWORD_MIN_LENGTH', 6);
define('PASSWORD_MAX_LENGTH', 50);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes in seconds
define('SESSION_LIFETIME', 86400); // 24 hours in seconds

// Email Domain Validation
define('VALID_EMAIL_DOMAINS', [
    'admin' => '@smkn1kotabekasi.admin.sch.id',
    'guru' => '@smkn1kotabekasi.guru.sch.id',
    'siswa' => '@smkn1kotabekasi.sch.id'
]);

// Pagination Settings
define('DEFAULT_PAGE_SIZE', 10);
define('MAX_PAGE_SIZE', 100);

// File Upload Settings (jika diperlukan nanti)
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Cache Settings
define('CACHE_ENABLED', false); // Set true untuk production
define('CACHE_LIFETIME', 3600); // 1 hour

// Rate Limiting
define('RATE_LIMIT_ENABLED', false); // Set true untuk production
define('RATE_LIMIT_REQUESTS', 100); // requests per hour
define('RATE_LIMIT_WINDOW', 3600); // 1 hour in seconds

// Error Handling
define('DEBUG_MODE', true); // Set false untuk production
define('LOG_ERRORS', true);
define('LOG_PATH', __DIR__ . '/../logs/');

// Database Settings (akan dioverride oleh Database class)
define('DB_CHARSET', 'utf8mb4');
define('DB_TIMEOUT', 30);

// CORS Settings
define('CORS_ALLOWED_ORIGINS', ['*']); // Set specific domains untuk production
define('CORS_ALLOWED_METHODS', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);
define('CORS_ALLOWED_HEADERS', ['Content-Type', 'Authorization', 'X-Requested-With']);
define('CORS_MAX_AGE', 86400); // 24 hours

// Response Settings
define('JSON_OPTIONS', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
define('JSON_PRETTY_PRINT', DEBUG_MODE ? JSON_PRETTY_PRINT : 0);

// Validation Rules
define('VALIDATION_RULES', [
    'agenda' => [
        'judul' => ['required' => true, 'max_length' => 255],
        'deskripsi' => ['required' => false, 'max_length' => 1000],
        'tanggal' => ['required' => true, 'type' => 'date'],
        'waktu' => ['required' => true, 'type' => 'time'],
        'tempat' => ['required' => true, 'max_length' => 255]
    ],
    'user' => [
        'email' => ['required' => true, 'type' => 'email', 'max_length' => 100],
        'password' => ['required' => true, 'min_length' => PASSWORD_MIN_LENGTH, 'max_length' => PASSWORD_MAX_LENGTH],
        'nama' => ['required' => true, 'max_length' => 100],
        'role' => ['required' => true, 'values' => ['admin', 'guru', 'siswa']]
    ]
]);

// Status Constants
define('AGENDA_STATUS', [
    'PENDING' => 'pending',
    'APPROVED' => 'approved',
    'REJECTED' => 'rejected'
]);

define('USER_STATUS', [
    'ACTIVE' => 'active',
    'INACTIVE' => 'inactive'
]);

define('USER_ROLES', [
    'ADMIN' => 'admin',
    'GURU' => 'guru',
    'SISWA' => 'siswa'
]);

// Activity Types
define('ACTIVITY_TYPES', [
    'LOGIN' => 'login',
    'LOGOUT' => 'logout',
    'CREATE' => 'create',
    'UPDATE' => 'update',
    'DELETE' => 'delete',
    'APPROVE' => 'approve',
    'REJECT' => 'reject',
    'VIEW' => 'view'
]);

// HTTP Status Codes
define('HTTP_STATUS', [
    'OK' => 200,
    'CREATED' => 201,
    'NO_CONTENT' => 204,
    'BAD_REQUEST' => 400,
    'UNAUTHORIZED' => 401,
    'FORBIDDEN' => 403,
    'NOT_FOUND' => 404,
    'METHOD_NOT_ALLOWED' => 405,
    'CONFLICT' => 409,
    'UNPROCESSABLE_ENTITY' => 422,
    'TOO_MANY_REQUESTS' => 429,
    'INTERNAL_SERVER_ERROR' => 500,
    'SERVICE_UNAVAILABLE' => 503
]);

// Error Messages
define('ERROR_MESSAGES', [
    'INVALID_REQUEST' => 'Invalid request format',
    'MISSING_PARAMETER' => 'Missing required parameter',
    'INVALID_PARAMETER' => 'Invalid parameter value',
    'AUTHENTICATION_FAILED' => 'Authentication failed',
    'PERMISSION_DENIED' => 'Permission denied',
    'RESOURCE_NOT_FOUND' => 'Resource not found',
    'RESOURCE_CONFLICT' => 'Resource conflict',
    'VALIDATION_FAILED' => 'Validation failed',
    'DATABASE_ERROR' => 'Database error occurred',
    'SERVER_ERROR' => 'Internal server error',
    'SERVICE_UNAVAILABLE' => 'Service temporarily unavailable'
]);

// Success Messages
define('SUCCESS_MESSAGES', [
    'CREATED' => 'Resource created successfully',
    'UPDATED' => 'Resource updated successfully',
    'DELETED' => 'Resource deleted successfully',
    'LOGIN_SUCCESS' => 'Login successful',
    'LOGOUT_SUCCESS' => 'Logout successful',
    'APPROVED' => 'Agenda approved successfully',
    'REJECTED' => 'Agenda rejected successfully'
]);

// Helper Functions
if (!function_exists('getConfig')) {
    /**
     * Get configuration value
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function getConfig($key, $default = null) {
        return defined($key) ? constant($key) : $default;
    }
}

if (!function_exists('isDebugMode')) {
    /**
     * Check if debug mode is enabled
     * @return bool
     */
    function isDebugMode() {
        return getConfig('DEBUG_MODE', false);
    }
}

if (!function_exists('logError')) {
    /**
     * Log error message
     * @param string $message
     * @param array $context
     */
    function logError($message, $context = []) {
        if (getConfig('LOG_ERRORS', true)) {
            $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;
            if (!empty($context)) {
                $logMessage .= ' - Context: ' . json_encode($context);
            }
            error_log($logMessage);
        }
    }
}

if (!function_exists('validateInput')) {
    /**
     * Validate input based on rules
     * @param array $data
     * @param array $rules
     * @return array
     */
    function validateInput($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            // Required validation
            if (isset($fieldRules['required']) && $fieldRules['required'] && empty($value)) {
                $errors[$field] = "Field {$field} is required";
                continue;
            }
            
            // Skip other validations if empty and not required
            if (empty($value) && (!isset($fieldRules['required']) || !$fieldRules['required'])) {
                continue;
            }
            
            // Type validation
            if (isset($fieldRules['type'])) {
                switch ($fieldRules['type']) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "Field {$field} must be a valid email";
                        }
                        break;
                    case 'date':
                        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                            $errors[$field] = "Field {$field} must be a valid date (YYYY-MM-DD)";
                        }
                        break;
                    case 'time':
                        if (!preg_match('/^\d{2}:\d{2}$/', $value)) {
                            $errors[$field] = "Field {$field} must be a valid time (HH:MM)";
                        }
                        break;
                }
            }
            
            // Length validation
            if (isset($fieldRules['min_length']) && strlen($value) < $fieldRules['min_length']) {
                $errors[$field] = "Field {$field} must be at least {$fieldRules['min_length']} characters";
            }
            
            if (isset($fieldRules['max_length']) && strlen($value) > $fieldRules['max_length']) {
                $errors[$field] = "Field {$field} must not exceed {$fieldRules['max_length']} characters";
            }
            
            // Values validation
            if (isset($fieldRules['values']) && !in_array($value, $fieldRules['values'])) {
                $errors[$field] = "Field {$field} must be one of: " . implode(', ', $fieldRules['values']);
            }
        }
        
        return $errors;
    }
}

if (!function_exists('sanitizeInput')) {
    /**
     * Sanitize input data
     * @param mixed $data
     * @return mixed
     */
    function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map('sanitizeInput', $data);
        }
        
        if (is_string($data)) {
            return trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
        }
        
        return $data;
    }
}

if (!function_exists('generateToken')) {
    /**
     * Generate random token
     * @param int $length
     * @return string
     */
    function generateToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
}

if (!function_exists('isValidUUID')) {
    /**
     * Check if string is valid UUID
     * @param string $uuid
     * @return bool
     */
    function isValidUUID($uuid) {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }
}

// Auto-create directories if they don't exist
$directories = [
    getConfig('LOG_PATH', __DIR__ . '/../logs/'),
    getConfig('UPLOAD_PATH', __DIR__ . '/../uploads/')
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Auto-create log files if they don't exist
$logFile = getConfig('LOG_PATH', __DIR__ . '/../logs/') . 'api.log';
if (!file_exists($logFile)) {
    @touch($logFile);
    @chmod($logFile, 0644);
}

// Set error handler untuk production
if (!isDebugMode()) {
    set_error_handler(function($severity, $message, $file, $line) {
        logError("PHP Error: {$message} in {$file} on line {$line}");
        return true;
    });
    
    set_exception_handler(function($exception) {
        logError("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error',
            'timestamp' => date('c')
        ], JSON_OPTIONS);
        exit;
    });
}

// Initialize session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>