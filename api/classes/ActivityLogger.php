<?php
/**
 * Activity Logger
 * Menangani logging semua aktivitas user untuk audit trail
 */

class ActivityLogger {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Log user activity
     * @param int|null $userId
     * @param string $action
     * @param int|null $agendaId
     * @param string $description
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return bool
     */
    public function log($userId, $action, $agendaId = null, $description = '', $ipAddress = null, $userAgent = null) {
        try {
            // Get IP address if not provided
            if ($ipAddress === null) {
                $ipAddress = $this->getClientIP();
            }
            
            // Get user agent if not provided
            if ($userAgent === null) {
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs (user_id, action, agenda_id, description, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $userId,
                $action,
                $agendaId,
                $description,
                $ipAddress,
                $userAgent
            ]);
            
            return $result;
        } catch (Exception $e) {
            error_log('Activity log error: ' . $e->getMessage());
            return false; // Don't throw exception for logging failures
        }
    }
    
    /**
     * Get recent activities
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @return array
     */
    public function getRecentActivities($limit = 50, $offset = 0, $filters = []) {
        try {
            $whereConditions = ['1=1'];
            $params = [];
            
            // User filter
            if (!empty($filters['user_id'])) {
                $whereConditions[] = "al.user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            // Action filter
            if (!empty($filters['action'])) {
                $whereConditions[] = "al.action = ?";
                $params[] = $filters['action'];
            }
            
            // Date range filter
            if (!empty($filters['date_from'])) {
                $whereConditions[] = "DATE(al.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $whereConditions[] = "DATE(al.created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            // Agenda filter
            if (!empty($filters['agenda_id'])) {
                $whereConditions[] = "al.agenda_id = ?";
                $params[] = $filters['agenda_id'];
            }
            
            // Add limit and offset to params
            $params[] = $limit;
            $params[] = $offset;
            
            $sql = "
                SELECT 
                    al.*,
                    u.nama as user_name,
                    u.role as user_role,
                    u.email as user_email,
                    a.judul as agenda_title
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN agendas a ON al.agenda_id = a.id
                WHERE " . implode(' AND ', $whereConditions) . "
                ORDER BY al.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get recent activities error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil log aktivitas');
        }
    }
    
    /**
     * Get activity statistics
     * @param array $filters
     * @return array
     */
    public function getActivityStats($filters = []) {
        try {
            $whereConditions = ['1=1'];
            $params = [];
            
            // Date range filter
            if (!empty($filters['date_from'])) {
                $whereConditions[] = "DATE(created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $whereConditions[] = "DATE(created_at) <= ?";
                $params[] = $filters['date_to'];
            } else {
                // Default to last 30 days if no end date
                $whereConditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            }
            
            $sql = "
                SELECT 
                    COUNT(*) as total_activities,
                    COUNT(CASE WHEN action = 'login' THEN 1 END) as login_count,
                    COUNT(CASE WHEN action = 'create' THEN 1 END) as create_count,
                    COUNT(CASE WHEN action = 'update' THEN 1 END) as update_count,
                    COUNT(CASE WHEN action = 'approve' THEN 1 END) as approve_count,
                    COUNT(CASE WHEN action = 'reject' THEN 1 END) as reject_count,
                    COUNT(CASE WHEN action = 'delete' THEN 1 END) as delete_count,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT agenda_id) as affected_agendas,
                    COUNT(DISTINCT DATE(created_at)) as active_days
                FROM activity_logs
                WHERE " . implode(' AND ', $whereConditions);
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log('Get activity stats error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil statistik aktivitas');
        }
    }
    
    /**
     * Get user activity summary
     * @param int $userId
     * @param int $days
     * @return array
     */
    public function getUserActivitySummary($userId, $days = 30) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    action,
                    COUNT(*) as count,
                    MAX(created_at) as last_activity
                FROM activity_logs
                WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY action
                ORDER BY count DESC
            ");
            $stmt->execute([$userId, $days]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get user activity summary error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil ringkasan aktivitas pengguna');
        }
    }
    
    /**
     * Get agenda activity history
     * @param int $agendaId
     * @return array
     */
    public function getAgendaActivityHistory($agendaId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    al.*,
                    u.nama as user_name,
                    u.role as user_role
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.agenda_id = ?
                ORDER BY al.created_at ASC
            ");
            $stmt->execute([$agendaId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get agenda activity history error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil riwayat aktivitas agenda');
        }
    }
    
    /**
     * Get daily activity counts
     * @param int $days
     * @return array
     */
    public function getDailyActivityCounts($days = 30) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as activity_date,
                    COUNT(*) as activity_count,
                    COUNT(DISTINCT user_id) as unique_users
                FROM activity_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY activity_date DESC
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get daily activity counts error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil hitungan aktivitas harian');
        }
    }
    
    /**
     * Get most active users
     * @param int $limit
     * @param int $days
     * @return array
     */
    public function getMostActiveUsers($limit = 10, $days = 30) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    u.id,
                    u.nama,
                    u.role,
                    u.email,
                    COUNT(al.id) as activity_count,
                    MAX(al.created_at) as last_activity
                FROM users u
                JOIN activity_logs al ON u.id = al.user_id
                WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY u.id, u.nama, u.role, u.email
                ORDER BY activity_count DESC
                LIMIT ?
            ");
            $stmt->execute([$days, $limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get most active users error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil pengguna paling aktif');
        }
    }
    
    /**
     * Clean old activity logs
     * @param int $keepDays
     * @return int Number of deleted records
     */
    public function cleanOldLogs($keepDays = 365) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM activity_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$keepDays]);
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log('Clean old logs error: ' . $e->getMessage());
            throw new Exception('Gagal membersihkan log lama');
        }
    }
    
    /**
     * Search activities
     * @param string $query
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function searchActivities($query = '', $filters = [], $limit = 50, $offset = 0) {
        try {
            $whereConditions = ['1=1'];
            $params = [];
            
            // Text search in description
            if (!empty($query)) {
                $whereConditions[] = "al.description LIKE ?";
                $params[] = "%{$query}%";
            }
            
            // Apply filters
            if (!empty($filters['user_id'])) {
                $whereConditions[] = "al.user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            if (!empty($filters['action'])) {
                $whereConditions[] = "al.action = ?";
                $params[] = $filters['action'];
            }
            
            if (!empty($filters['agenda_id'])) {
                $whereConditions[] = "al.agenda_id = ?";
                $params[] = $filters['agenda_id'];
            }
            
            if (!empty($filters['date_from'])) {
                $whereConditions[] = "DATE(al.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $whereConditions[] = "DATE(al.created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['ip_address'])) {
                $whereConditions[] = "al.ip_address = ?";
                $params[] = $filters['ip_address'];
            }
            
            // Add limit and offset
            $params[] = $limit;
            $params[] = $offset;
            
            $sql = "
                SELECT 
                    al.*,
                    u.nama as user_name,
                    u.role as user_role,
                    u.email as user_email,
                    a.judul as agenda_title
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN agendas a ON al.agenda_id = a.id
                WHERE " . implode(' AND ', $whereConditions) . "
                ORDER BY al.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Search activities error: ' . $e->getMessage());
            throw new Exception('Gagal mencari aktivitas');
        }
    }
    
    /**
     * Get activity by ID
     * @param int $activityId
     * @return array|null
     */
    public function getActivityById($activityId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    al.*,
                    u.nama as user_name,
                    u.role as user_role,
                    u.email as user_email,
                    a.judul as agenda_title
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN agendas a ON al.agenda_id = a.id
                WHERE al.id = ?
            ");
            $stmt->execute([$activityId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log('Get activity by ID error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil detail aktivitas');
        }
    }
    
    /**
     * Export activities to CSV
     * @param array $filters
     * @return string CSV content
     */
    public function exportActivitiesToCSV($filters = []) {
        try {
            $activities = $this->searchActivities('', $filters, 10000, 0); // Export max 10000 records
            
            $csv = "ID,User,Role,Action,Description,Agenda,IP Address,Created At\n";
            
            foreach ($activities as $activity) {
                $csv .= sprintf(
                    "%d,%s,%s,%s,%s,%s,%s,%s\n",
                    $activity['id'],
                    $activity['user_name'] ?? 'System',
                    $activity['user_role'] ?? 'System',
                    $activity['action'],
                    str_replace(["\n", "\r", ","], [" ", " ", ";"], $activity['description']),
                    $activity['agenda_title'] ?? '',
                    $activity['ip_address'] ?? '',
                    $activity['created_at']
                );
            }
            
            return $csv;
        } catch (Exception $e) {
            error_log('Export activities to CSV error: ' . $e->getMessage());
            throw new Exception('Gagal mengekspor log aktivitas');
        }
    }
    
    /**
     * Get login attempts
     * @param string $email
     * @param int $timeWindow minutes
     * @return int
     */
    public function getLoginAttempts($email = null, $timeWindow = 15) {
        try {
            $whereConditions = ["action IN ('login', 'login_failed')"];
            $params = [];
            
            if ($email) {
                $whereConditions[] = "description LIKE ?";
                $params[] = "%{$email}%";
            } else {
                // Use IP address if no email provided
                $whereConditions[] = "ip_address = ?";
                $params[] = $this->getClientIP();
            }
            
            $whereConditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)";
            $params[] = $timeWindow;
            
            $sql = "
                SELECT COUNT(*) as attempt_count
                FROM activity_logs
                WHERE " . implode(' AND ', $whereConditions);
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            return $result['attempt_count'] ?? 0;
        } catch (Exception $e) {
            error_log('Get login attempts error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get suspicious activities
     * @param int $days
     * @return array
     */
    public function getSuspiciousActivities($days = 7) {
        try {
            // Look for unusual patterns like:
            // - Multiple failed logins
            // - High activity from single IP
            // - Unusual times
            
            $stmt = $this->db->prepare("
                SELECT 
                    ip_address,
                    COUNT(*) as activity_count,
                    COUNT(CASE WHEN action = 'login_failed' THEN 1 END) as failed_logins,
                    COUNT(DISTINCT user_id) as unique_users,
                    MIN(created_at) as first_activity,
                    MAX(created_at) as last_activity
                FROM activity_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY ip_address
                HAVING failed_logins > 10 OR activity_count > 100
                ORDER BY failed_logins DESC, activity_count DESC
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get suspicious activities error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil aktivitas mencurigakan');
        }
    }
    
    /**
     * Log system event
     * @param string $event
     * @param string $description
     * @param array $context
     * @return bool
     */
    public function logSystemEvent($event, $description, $context = []) {
        try {
            $contextJson = !empty($context) ? json_encode($context) : null;
            $fullDescription = $description;
            
            if ($contextJson) {
                $fullDescription .= ' | Context: ' . $contextJson;
            }
            
            return $this->log(null, 'system_' . $event, null, $fullDescription);
        } catch (Exception $e) {
            error_log('Log system event error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get client IP address
     * @return string
     */
    private function getClientIP() {
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
     * Format activity description for display
     * @param array $activity
     * @return string
     */
    public static function formatActivityDescription($activity) {
        $description = $activity['description'];
        
        // Add context based on action type
        switch ($activity['action']) {
            case 'login':
                return "User logged in successfully";
            case 'login_failed':
                return "Failed login attempt";
            case 'create':
                return $activity['agenda_title'] ? 
                    "Created agenda: {$activity['agenda_title']}" : 
                    $description;
            case 'update':
                return $activity['agenda_title'] ? 
                    "Updated agenda: {$activity['agenda_title']}" : 
                    $description;
            case 'approve':
                return $activity['agenda_title'] ? 
                    "Approved agenda: {$activity['agenda_title']}" : 
                    $description;
            case 'reject':
                return $activity['agenda_title'] ? 
                    "Rejected agenda: {$activity['agenda_title']}" : 
                    $description;
            case 'delete':
                return $description ?: "Deleted an agenda";
            default:
                return $description;
        }
    }
    
    /**
     * Get activity count for specific action
     * @param string $action
     * @param int $days
     * @return int
     */
    public function getActionCount($action, $days = 30) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM activity_logs
                WHERE action = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$action, $days]);
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Get action count error: ' . $e->getMessage());
            return 0;
        }
    }
}
?>