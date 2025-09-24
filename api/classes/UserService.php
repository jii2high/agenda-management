<?php
/**
 * User Service
 * Menangani semua operasi user management
 */

class UserService {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all users
     * @param bool $includeInactive
     * @return array
     */
    public function getAllUsers($includeInactive = false) {
        try {
            $whereClause = $includeInactive ? '' : "WHERE status = 'active'";
            
            $stmt = $this->db->prepare("
                SELECT id, email, role, nama, status, last_login, created_at, updated_at
                FROM users 
                {$whereClause}
                ORDER BY nama ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get all users error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil data pengguna');
        }
    }
    
    /**
     * Get user by ID
     * @param int $userId
     * @return array|null
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, email, role, nama, status, last_login, created_at, updated_at
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log('Get user by ID error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil data pengguna');
        }
    }
    
    /**
     * Get user by email
     * @param string $email
     * @return array|null
     */
    public function getUserByEmail($email) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, email, role, nama, status, last_login, created_at, updated_at
                FROM users 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log('Get user by email error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil data pengguna');
        }
    }
    
    /**
     * Create new user
     * @param array $data
     * @return int|false
     */
    public function createUser($data) {
        try {
            // Validate email domain
            if (!$this->isValidEmailDomain($data['email'])) {
                throw new Exception('Domain email tidak valid');
            }
            
            // Check if email already exists
            if ($this->emailExists($data['email'])) {
                throw new Exception('Email sudah terdaftar');
            }
            
            // Validate password
            $passwordValidation = $this->validatePassword($data['password']);
            if (!$passwordValidation['valid']) {
                throw new Exception(implode(', ', $passwordValidation['errors']));
            }
            
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password, role, nama, status)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $result = $stmt->execute([
                $data['email'],
                $hashedPassword,
                $data['role'],
                $data['nama'],
                $data['status'] ?? 'active'
            ]);
            
            if ($result) {
                $userId = $this->db->lastInsertId();
                $this->db->commit();
                return $userId;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Create user error: ' . $e->getMessage());
            throw $e; // Re-throw to preserve original error message
        }
    }
    
    /**
     * Update user
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function updateUser($userId, $data) {
        try {
            // Check if user exists
            $existingUser = $this->getUserById($userId);
            if (!$existingUser) {
                throw new Exception('Pengguna tidak ditemukan');
            }
            
            // Check email uniqueness if email is being changed
            if (isset($data['email']) && $data['email'] !== $existingUser['email']) {
                if ($this->emailExists($data['email'], $userId)) {
                    throw new Exception('Email sudah digunakan pengguna lain');
                }
                
                if (!$this->isValidEmailDomain($data['email'])) {
                    throw new Exception('Domain email tidak valid');
                }
            }
            
            $this->db->beginTransaction();
            
            $updateFields = [];
            $params = [];
            
            // Build dynamic update query
            $allowedFields = ['email', 'nama', 'role', 'status'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "{$field} = ?";
                    $params[] = $data[$field];
                }
            }
            
            // Handle password update separately
            if (isset($data['password']) && !empty($data['password'])) {
                $passwordValidation = $this->validatePassword($data['password']);
                if (!$passwordValidation['valid']) {
                    throw new Exception(implode(', ', $passwordValidation['errors']));
                }
                
                $updateFields[] = "password = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            if (empty($updateFields)) {
                throw new Exception('Tidak ada data yang akan diupdate');
            }
            
            $updateFields[] = "updated_at = NOW()";
            $params[] = $userId;
            
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Update user error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete user (soft delete by setting status to inactive)
     * @param int $userId
     * @return bool
     */
    public function deleteUser($userId) {
        try {
            // Check if user exists
            $user = $this->getUserById($userId);
            if (!$user) {
                throw new Exception('Pengguna tidak ditemukan');
            }
            
            // Prevent deleting admin if it's the only admin
            if ($user['role'] === 'admin') {
                $adminCount = $this->getActiveUserCountByRole('admin');
                if ($adminCount <= 1) {
                    throw new Exception('Tidak dapat menghapus admin terakhir');
                }
            }
            
            $this->db->beginTransaction();
            
            // Soft delete - set status to inactive
            $stmt = $this->db->prepare("
                UPDATE users 
                SET status = 'inactive', updated_at = NOW() 
                WHERE id = ?
            ");
            $result = $stmt->execute([$userId]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Delete user error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Permanently delete user (hard delete)
     * @param int $userId
     * @return bool
     */
    public function permanentDeleteUser($userId) {
        try {
            $this->db->beginTransaction();
            
            // Delete user (this will cascade delete related records due to foreign keys)
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $result = $stmt->execute([$userId]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Permanent delete user error: ' . $e->getMessage());
            throw new Exception('Gagal menghapus pengguna secara permanen');
        }
    }
    
    /**
     * Activate user
     * @param int $userId
     * @return bool
     */
    public function activateUser($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET status = 'active', updated_at = NOW() 
                WHERE id = ?
            ");
            $result = $stmt->execute([$userId]);
            return $result && $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('Activate user error: ' . $e->getMessage());
            throw new Exception('Gagal mengaktifkan pengguna');
        }
    }
    
    /**
     * Deactivate user
     * @param int $userId
     * @return bool
     */
    public function deactivateUser($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET status = 'inactive', updated_at = NOW() 
                WHERE id = ?
            ");
            $result = $stmt->execute([$userId]);
            return $result && $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('Deactivate user error: ' . $e->getMessage());
            throw new Exception('Gagal menonaktifkan pengguna');
        }
    }
    
    /**
     * Reset user password
     * @param int $userId
     * @param string $newPassword
     * @return bool
     */
    public function resetPassword($userId, $newPassword) {
        try {
            $passwordValidation = $this->validatePassword($newPassword);
            if (!$passwordValidation['valid']) {
                throw new Exception(implode(', ', $passwordValidation['errors']));
            }
            
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                UPDATE users 
                SET password = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $result = $stmt->execute([$hashedPassword, $userId]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Reset password error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get user statistics
     * @return array
     */
    public function getUserStats() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_users,
                    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_users,
                    COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_count,
                    COUNT(CASE WHEN role = 'guru' THEN 1 END) as guru_count,
                    COUNT(CASE WHEN role = 'siswa' THEN 1 END) as siswa_count,
                    COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as active_last_30_days,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_users_last_30_days
                FROM users
            ");
            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log('Get user stats error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil statistik pengguna');
        }
    }
    
    /**
     * Get users by role
     * @param string $role
     * @param bool $activeOnly
     * @return array
     */
    public function getUsersByRole($role, $activeOnly = true) {
        try {
            $whereClause = "WHERE role = ?";
            $params = [$role];
            
            if ($activeOnly) {
                $whereClause .= " AND status = 'active'";
            }
            
            $stmt = $this->db->prepare("
                SELECT id, email, role, nama, status, last_login, created_at
                FROM users 
                {$whereClause}
                ORDER BY nama ASC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get users by role error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil pengguna berdasarkan role');
        }
    }
    
    /**
     * Search users
     * @param string $query
     * @param array $filters
     * @return array
     */
    public function searchUsers($query, $filters = []) {
        try {
            $whereConditions = ['1=1'];
            $params = [];
            
            // Text search
            if (!empty($query)) {
                $whereConditions[] = "(nama LIKE ? OR email LIKE ?)";
                $searchTerm = "%{$query}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Role filter
            if (!empty($filters['role'])) {
                $whereConditions[] = "role = ?";
                $params[] = $filters['role'];
            }
            
            // Status filter
            if (!empty($filters['status'])) {
                $whereConditions[] = "status = ?";
                $params[] = $filters['status'];
            }
            
            $sql = "
                SELECT id, email, role, nama, status, last_login, created_at, updated_at
                FROM users
                WHERE " . implode(' AND ', $whereConditions) . "
                ORDER BY nama ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Search users error: ' . $e->getMessage());
            throw new Exception('Gagal mencari pengguna');
        }
    }
    
    /**
     * Check if email exists
     * @param string $email
     * @param int $excludeId
     * @return bool
     */
    private function emailExists($email, $excludeId = null) {
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
     * Validate password
     * @param string $password
     * @return array
     */
    private function validatePassword($password) {
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
     * Get active user count by role
     * @param string $role
     * @return int
     */
    private function getActiveUserCountByRole($role) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM users 
                WHERE role = ? AND status = 'active'
            ");
            $stmt->execute([$role]);
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Get active user count by role error: ' . $e->getMessage());
            return 0;
        }
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
     * Bulk import users from array
     * @param array $users
     * @return array Results
     */
    public function bulkImportUsers($users) {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($users as $index => $userData) {
            try {
                $this->createUser($userData);
                $results['success']++;
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Row " . ($index + 1) . ": " . $e->getMessage();
            }
        }
        
        return $results;
    }
    
    /**
     * Export users to CSV format
     * @param array $filters
     * @return string CSV content
     */
    public function exportUsersToCSV($filters = []) {
        try {
            $users = $this->searchUsers('', $filters);
            
            $csv = "ID,Email,Role,Nama,Status,Last Login,Created At\n";
            
            foreach ($users as $user) {
                $csv .= sprintf(
                    "%d,%s,%s,%s,%s,%s,%s\n",
                    $user['id'],
                    $user['email'],
                    $user['role'],
                    $user['nama'],
                    $user['status'],
                    $user['last_login'] ?? 'Never',
                    $user['created_at']
                );
            }
            
            return $csv;
        } catch (Exception $e) {
            error_log('Export users to CSV error: ' . $e->getMessage());
            throw new Exception('Gagal mengekspor data pengguna');
        }
    }
}
?>