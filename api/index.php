<?php
/**
 * Agenda Management System API
 * Main API endpoint handler
 * 
 * @author SMK Negeri 1 Kota Bekasi
 * @version 1.0.0
 */

// Error reporting untuk development (nonaktifkan di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers untuk CORS dan JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400'); // 24 hours

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// Include required files
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/ApiResponse.php';
require_once __DIR__ . '/classes/AuthService.php';
require_once __DIR__ . '/classes/AgendaService.php';
require_once __DIR__ . '/classes/UserService.php';
require_once __DIR__ . '/classes/ActivityLogger.php';

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Initialize services
    $authService = new AuthService($db);
    $agendaService = new AgendaService($db);
    $userService = new UserService($db);
    $activityLogger = new ActivityLogger($db);
    
    // Parse request
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $requestUri = $_SERVER['REQUEST_URI'];
    $pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
    
    // Remove query string from path
    $path = parse_url($requestUri, PHP_URL_PATH);
    $basePath = dirname($_SERVER['SCRIPT_NAME']);
    $path = substr($path, strlen($basePath));
    $path = trim($path, '/');
    $pathParts = empty($path) ? [] : explode('/', $path);
    
    // Get request body
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    
    // Log request for debugging
    error_log("API Request: {$requestMethod} {$path} - " . json_encode($input));
    
    // Route handling
    if (empty($pathParts[0])) {
        ApiResponse::success([
            'message' => 'Agenda Management System API',
            'version' => '1.0.0',
            'timestamp' => date('c'),
            'endpoints' => [
                'POST /login' => 'User authentication',
                'GET /agendas' => 'Get all agendas (admin only)',
                'GET /agendas/approved' => 'Get approved agendas',
                'GET /agendas/pending' => 'Get pending agendas (admin only)',
                'GET /agendas/user/{id}' => 'Get user agendas',
                'POST /agendas' => 'Create agenda',
                'PUT /agendas/{id}' => 'Update agenda',
                'PUT /agendas/{id}/approve' => 'Approve agenda',
                'PUT /agendas/{id}/reject' => 'Reject agenda',
                'DELETE /agendas/{id}' => 'Delete agenda',
                'GET /users' => 'Get all users',
                'POST /users' => 'Create user',
                'GET /stats' => 'Get statistics',
                'GET /activities' => 'Get activity logs'
            ]
        ]);
    }
    
    // Route to appropriate handler
    switch ($pathParts[0]) {
        case 'login':
            handleLogin($authService, $requestMethod, $input, $activityLogger);
            break;
            
        case 'agendas':
            handleAgendas($agendaService, $authService, $requestMethod, $pathParts, $input, $activityLogger);
            break;
            
        case 'users':
            handleUsers($userService, $authService, $requestMethod, $pathParts, $input, $activityLogger);
            break;
            
        case 'stats':
            handleStats($agendaService, $userService, $requestMethod, $activityLogger);
            break;
            
        case 'activities':
            handleActivities($activityLogger, $requestMethod);
            break;
            
        default:
            ApiResponse::error('Endpoint tidak ditemukan', 404);
    }
    
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    ApiResponse::error('Terjadi kesalahan server: ' . $e->getMessage(), 500);
}

/**
 * Handle login requests
 */
function handleLogin($authService, $method, $input, $activityLogger) {
    if ($method !== 'POST') {
        ApiResponse::error('Method tidak didukung', 405);
    }
    
    // Validate input
    if (empty($input['email']) || empty($input['password'])) {
        ApiResponse::error('Email dan password harus diisi', 400);
    }
    
    try {
        $result = $authService->login($input['email'], $input['password']);
        
        if ($result['success']) {
            // Log successful login
            $activityLogger->log($result['user']['id'], 'login', null, 'User berhasil login', getClientIP());
            
            ApiResponse::success([
                'user' => $result['user'],
                'message' => 'Login berhasil'
            ]);
        } else {
            // Log failed login attempt
            $activityLogger->log(null, 'login_failed', null, 'Percobaan login gagal: ' . $input['email'], getClientIP());
            
            ApiResponse::error($result['message'], 401);
        }
    } catch (Exception $e) {
        error_log('Login error: ' . $e->getMessage());
        ApiResponse::error('Login gagal', 500);
    }
}

/**
 * Handle agenda requests
 */
function handleAgendas($agendaService, $authService, $method, $pathParts, $input, $activityLogger) {
    switch ($method) {
        case 'GET':
            handleGetAgendas($agendaService, $pathParts);
            break;
            
        case 'POST':
            handleCreateAgenda($agendaService, $input, $activityLogger);
            break;
            
        case 'PUT':
            handleUpdateAgenda($agendaService, $pathParts, $input, $activityLogger);
            break;
            
        case 'DELETE':
            handleDeleteAgenda($agendaService, $pathParts, $activityLogger);
            break;
            
        default:
            ApiResponse::error('Method tidak didukung', 405);
    }
}

/**
 * Handle GET agenda requests
 */
function handleGetAgendas($agendaService, $pathParts) {
    try {
        if (isset($pathParts[1])) {
            switch ($pathParts[1]) {
                case 'approved':
                    $agendas = $agendaService->getApprovedAgendas();
                    ApiResponse::success($agendas);
                    break;
                    
                case 'pending':
                    $agendas = $agendaService->getPendingAgendas();
                    ApiResponse::success($agendas);
                    break;
                    
                case 'user':
                    if (!isset($pathParts[2])) {
                        ApiResponse::error('User ID diperlukan', 400);
                    }
                    $userId = intval($pathParts[2]);
                    $agendas = $agendaService->getUserAgendas($userId);
                    ApiResponse::success($agendas);
                    break;
                    
                default:
                    // Handle specific agenda ID or actions
                    $agendaId = intval($pathParts[1]);
                    if (isset($pathParts[2])) {
                        // Handle approve/reject actions
                        handleAgendaActions($agendaService, $agendaId, $pathParts[2], $input ?? [], $activityLogger ?? null);
                    } else {
                        // Get specific agenda
                        $agenda = $agendaService->getAgendaById($agendaId);
                        if ($agenda) {
                            ApiResponse::success($agenda);
                        } else {
                            ApiResponse::error('Agenda tidak ditemukan', 404);
                        }
                    }
            }
        } else {
            // Get all agendas (admin only)
            $agendas = $agendaService->getAllAgendas();
            ApiResponse::success($agendas);
        }
    } catch (Exception $e) {
        error_log('Get agendas error: ' . $e->getMessage());
        ApiResponse::error('Gagal mengambil data agenda', 500);
    }
}

/**
 * Handle agenda actions (approve/reject)
 */
function handleAgendaActions($agendaService, $agendaId, $action, $input, $activityLogger) {
    if (!in_array($action, ['approve', 'reject'])) {
        ApiResponse::error('Action tidak valid', 400);
    }
    
    $approvedBy = $input['approved_by'] ?? null;
    if (!$approvedBy) {
        ApiResponse::error('approved_by diperlukan', 400);
    }
    
    try {
        if ($action === 'approve') {
            $result = $agendaService->approveAgenda($agendaId, $approvedBy);
        } else {
            $rejectionReason = $input['rejection_reason'] ?? 'Ditolak oleh admin';
            $result = $agendaService->rejectAgenda($agendaId, $approvedBy, $rejectionReason);
        }
        
        if ($result) {
            // Log activity
            $activityLogger->log($approvedBy, $action, $agendaId, "Agenda {$action}d", getClientIP());
            ApiResponse::success(['message' => "Agenda berhasil " . ($action === 'approve' ? 'disetujui' : 'ditolak')]);
        } else {
            ApiResponse::error("Gagal {$action} agenda", 500);
        }
    } catch (Exception $e) {
        error_log("Agenda {$action} error: " . $e->getMessage());
        ApiResponse::error("Gagal {$action} agenda", 500);
    }
}

/**
 * Handle create agenda requests
 */
function handleCreateAgenda($agendaService, $input, $activityLogger) {
    // Validate required fields
    $requiredFields = ['judul', 'tanggal', 'waktu', 'tempat', 'created_by'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            ApiResponse::error("Field {$field} wajib diisi", 400);
        }
    }
    
    try {
        $agendaData = [
            'judul' => $input['judul'],
            'deskripsi' => $input['deskripsi'] ?? '',
            'tanggal' => $input['tanggal'],
            'waktu' => $input['waktu'],
            'tempat' => $input['tempat'],
            'status' => $input['status'] ?? 'pending',
            'created_by' => $input['created_by']
        ];
        
        $agendaId = $agendaService->createAgenda($agendaData);
        
        if ($agendaId) {
            // Log activity
            $activityLogger->log($input['created_by'], 'create', $agendaId, "Agenda dibuat: {$input['judul']}", getClientIP());
            
            ApiResponse::success([
                'agenda_id' => $agendaId,
                'message' => 'Agenda berhasil dibuat'
            ], 201);
        } else {
            ApiResponse::error('Gagal membuat agenda', 500);
        }
    } catch (Exception $e) {
        error_log('Create agenda error: ' . $e->getMessage());
        ApiResponse::error('Gagal membuat agenda', 500);
    }
}

/**
 * Handle update agenda requests
 */
function handleUpdateAgenda($agendaService, $pathParts, $input, $activityLogger) {
    if (empty($pathParts[1])) {
        ApiResponse::error('Agenda ID diperlukan', 400);
    }
    
    $agendaId = intval($pathParts[1]);
    
    // Check if this is an action (approve/reject)
    if (isset($pathParts[2])) {
        handleAgendaActions($agendaService, $agendaId, $pathParts[2], $input, $activityLogger);
        return;
    }
    
    // Validate required fields for update
    $requiredFields = ['judul', 'tanggal', 'waktu', 'tempat'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            ApiResponse::error("Field {$field} wajib diisi", 400);
        }
    }
    
    try {
        // Get current agenda to check ownership
        $currentAgenda = $agendaService->getAgendaById($agendaId);
        if (!$currentAgenda) {
            ApiResponse::error('Agenda tidak ditemukan', 404);
        }
        
        $agendaData = [
            'judul' => $input['judul'],
            'deskripsi' => $input['deskripsi'] ?? '',
            'tanggal' => $input['tanggal'],
            'waktu' => $input['waktu'],
            'tempat' => $input['tempat'],
            'status' => 'pending' // Reset to pending when updated
        ];
        
        $result = $agendaService->updateAgenda($agendaId, $agendaData);
        
        if ($result) {
            // Log activity
            $activityLogger->log($currentAgenda['created_by'], 'update', $agendaId, "Agenda diperbarui: {$input['judul']}", getClientIP());
            
            ApiResponse::success(['message' => 'Agenda berhasil diperbarui']);
        } else {
            ApiResponse::error('Gagal memperbarui agenda', 500);
        }
    } catch (Exception $e) {
        error_log('Update agenda error: ' . $e->getMessage());
        ApiResponse::error('Gagal memperbarui agenda', 500);
    }
}

/**
 * Handle delete agenda requests
 */
function handleDeleteAgenda($agendaService, $pathParts, $activityLogger) {
    if (empty($pathParts[1])) {
        ApiResponse::error('Agenda ID diperlukan', 400);
    }
    
    $agendaId = intval($pathParts[1]);
    
    try {
        // Get current agenda before deletion for logging
        $currentAgenda = $agendaService->getAgendaById($agendaId);
        if (!$currentAgenda) {
            ApiResponse::error('Agenda tidak ditemukan', 404);
        }
        
        $result = $agendaService->deleteAgenda($agendaId);
        
        if ($result) {
            // Log activity
            $activityLogger->log($currentAgenda['created_by'], 'delete', null, "Agenda dihapus: {$currentAgenda['judul']}", getClientIP());
            
            ApiResponse::success(['message' => 'Agenda berhasil dihapus']);
        } else {
            ApiResponse::error('Gagal menghapus agenda', 500);
        }
    } catch (Exception $e) {
        error_log('Delete agenda error: ' . $e->getMessage());
        ApiResponse::error('Gagal menghapus agenda', 500);
    }
}

/**
 * Handle user requests
 */
function handleUsers($userService, $authService, $method, $pathParts, $input, $activityLogger) {
    switch ($method) {
        case 'GET':
            try {
                $users = $userService->getAllUsers();
                ApiResponse::success($users);
            } catch (Exception $e) {
                error_log('Get users error: ' . $e->getMessage());
                ApiResponse::error('Gagal mengambil data pengguna', 500);
            }
            break;
            
        case 'POST':
            // Validate required fields
            $requiredFields = ['email', 'password', 'role', 'nama'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    ApiResponse::error("Field {$field} wajib diisi", 400);
                }
            }
            
            try {
                $userData = [
                    'email' => $input['email'],
                    'password' => $input['password'],
                    'role' => $input['role'],
                    'nama' => $input['nama'],
                    'status' => $input['status'] ?? 'active'
                ];
                
                $userId = $userService->createUser($userData);
                
                if ($userId) {
                    // Log activity
                    $activityLogger->log($userId, 'create_user', null, "User baru dibuat: {$input['nama']}", getClientIP());
                    
                    ApiResponse::success([
                        'user_id' => $userId,
                        'message' => 'User berhasil dibuat'
                    ], 201);
                } else {
                    ApiResponse::error('Gagal membuat user', 500);
                }
            } catch (Exception $e) {
                error_log('Create user error: ' . $e->getMessage());
                
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    ApiResponse::error('Email sudah terdaftar', 400);
                } else {
                    ApiResponse::error('Gagal membuat user', 500);
                }
            }
            break;
            
        default:
            ApiResponse::error('Method tidak didukung', 405);
    }
}

/**
 * Handle statistics requests
 */
function handleStats($agendaService, $userService, $method, $activityLogger) {
    if ($method !== 'GET') {
        ApiResponse::error('Method tidak didukung', 405);
    }
    
    try {
        $stats = [
            'agendas' => $agendaService->getAgendaStats(),
            'users' => $userService->getUserStats(),
            'today_agendas' => $agendaService->getTodayAgendasCount(),
            'upcoming_agendas' => $agendaService->getUpcomingAgendasCount(),
            'last_updated' => date('c')
        ];
        
        ApiResponse::success($stats);
    } catch (Exception $e) {
        error_log('Get stats error: ' . $e->getMessage());
        ApiResponse::error('Gagal mengambil statistik', 500);
    }
}

/**
 * Handle activity logs requests
 */
function handleActivities($activityLogger, $method) {
    if ($method !== 'GET') {
        ApiResponse::error('Method tidak didukung', 405);
    }
    
    try {
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
        $limit = min($limit, 100); // Max 100 records
        
        $activities = $activityLogger->getRecentActivities($limit);
        ApiResponse::success($activities);
    } catch (Exception $e) {
        error_log('Get activities error: ' . $e->getMessage());
        ApiResponse::error('Gagal mengambil log aktivitas', 500);
    }
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Validate request rate limiting (simple implementation)
 */
function checkRateLimit($identifier, $maxRequests = 100, $timeWindow = 3600) {
    $cacheFile = sys_get_temp_dir() . '/api_rate_' . md5($identifier);
    
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        
        if ($data['timestamp'] > time() - $timeWindow) {
            if ($data['count'] >= $maxRequests) {
                ApiResponse::error('Rate limit exceeded', 429);
            }
            $data['count']++;
        } else {
            $data = ['count' => 1, 'timestamp' => time()];
        }
    } else {
        $data = ['count' => 1, 'timestamp' => time()];
    }
    
    file_put_contents($cacheFile, json_encode($data));
}

// Optional: Apply rate limiting based on IP
// checkRateLimit(getClientIP());

?>