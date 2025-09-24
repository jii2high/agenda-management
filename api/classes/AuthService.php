<?php
/**
 * Authentication Service
 * Menangani login, validasi, dan keamanan user
 */

class AuthService {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Login user
     * @param string $email
     * @param string $password
     * @return array
     */
    public function login($email, $password) {
        try {
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Format email tidak valid'];
            }
            
            // Validate email domain
            if (!$this->isValidEmailDomain($email)) {
                return ['success' => false, 'message' => 'Domain email tidak valid. Gunakan email sekolah.'];
            }
            
            // Get user from database
            $stmt = $this->db->prepare("
                SELECT id, email, password, role, nama, status, last_login 
                FROM users 
                WHERE email = ? AND status = 'active'
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Email tidak terdaftar atau akun tidak aktif'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Password salah'];
            }
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Remove password from response
            unset($user['password']);
            
            return [
                'success' => true,
                'user' => $user,
                'message' => 'Login berhasil'
            ];
            
        } catch (Exception $e) {
            error_log('Auth service login error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem'];
        }
    }
    
    /**
     * Validate email domain
     * @param string $email
     * @return bool
     */
    private function isValidEmailDomain($email) {
        $validDomains = [
            '@smkn1kotabekasi.admin.sch.id',
            '@smkn1kotabekasi.guru.sch.id', 
            '@smkn1kotabekasi.sch.id'
        ];
        
        foreach ($validDomains as $domain) {
            if (strpos($email, $domain) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Update user last login
     * @param int $userId
     */
    private function updateLastLogin($userId) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log('Update last login error: ' . $e->getMessage());
        }
    }
    
    /**
     * Hash password
     * @param string $password
     * @return string
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Validate password strength
     * @param string $password
     * @return array
     */
    public static function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 6) {
            $errors[] = 'Password minimal 6 karakter';
        }
        
        if (strlen($password) > 50) {
            $errors[] = 'Password maksimal 50 karakter';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Get user by ID
     * @param int $userId
     * @return array|null
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, email, role, nama, status, last_login, created_at 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log('Get user by ID error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if email exists
     * @param string $email
     * @param int $excludeId
     * @return bool
     */
    public function emailExists($email, $excludeId = null) {
        try {
            $sql = "SELECT id FROM users WHERE email = ?";
            $params = [$email];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('Email exists check error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get role from email domain
     * @param string $email
     * @return string|null
     */
    public static function getRoleFromEmail($email) {
        if (strpos($email, '@smkn1kotabekasi.admin.sch.id') !== false) {
            return 'admin';
        } elseif (strpos($email, '@smkn1kotabekasi.guru.sch.id') !== false) {
            return 'guru';
        } elseif (strpos($email, '@smkn1kotabekasi.sch.id') !== false) {
            return 'siswa';
        }
        
        return null;
    }
    
    /**
     * Validate user permissions
     * @param string $userRole
     * @param string $action
     * @return bool
     */
    public static function hasPermission($userRole, $action) {
        $permissions = [
            'admin' => [
                'create_agenda', 'edit_agenda', 'delete_agenda',
                'approve_agenda', 'reject_agenda', 'view_pending',
                'view_all_agendas', 'create_user', 'edit_user',
                'delete_user', 'view_stats', 'view_activities'
            ],
            'guru' => [
                'create_agenda', 'edit_own_agenda', 'view_own_agendas'
            ],
            'siswa' => [
                'view_approved_agendas'
            ]
        ];
        
        return isset($permissions[$userRole]) && in_array($action, $permissions[$userRole]);
    }
    
    /**
     * Generate random password
     * @param int $length
     * @return string
     */
    public static function generateRandomPassword($length = 12) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $password;
    }
    
    /**
     * Clean expired sessions (untuk implementasi lanjutan)
     * @param int $maxAgeHours
     */
    public function cleanExpiredSessions($maxAgeHours = 24) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET last_login = NULL 
                WHERE last_login < DATE_SUB(NOW(), INTERVAL ? HOUR)
            ");
            $stmt->execute([$maxAgeHours]);
            
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log('Clean expired sessions error: ' . $e->getMessage());
            return 0;
        }
    }
}
?>