<?php
/**
 * API Response Handler
 * Menangani format response API yang konsisten
 */

class ApiResponse {
    
    /**
     * Send success response
     * @param mixed $data
     * @param int $statusCode
     * @param string $message
     */
    public static function success($data = null, $statusCode = 200, $message = '') {
        http_response_code($statusCode);
        
        $response = [
            'success' => true,
            'timestamp' => date('c'),
            'status_code' => $statusCode
        ];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send error response
     * @param string $message
     * @param int $statusCode
     * @param mixed $errors
     */
    public static function error($message, $statusCode = 400, $errors = null) {
        http_response_code($statusCode);
        
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('c'),
            'status_code' => $statusCode
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send validation error response
     * @param array $validationErrors
     * @param string $message
     */
    public static function validationError($validationErrors, $message = 'Validation failed') {
        self::error($message, 422, $validationErrors);
    }
    
    /**
     * Send paginated response
     * @param array $data
     * @param int $total
     * @param int $page
     * @param int $perPage
     * @param string $message
     */
    public static function paginated($data, $total, $page, $perPage, $message = '') {
        $totalPages = ceil($total / $perPage);
        
        $response = [
            'success' => true,
            'timestamp' => date('c'),
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
?>