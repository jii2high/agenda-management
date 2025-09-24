<?php
/**
 * Agenda Service
 * Menangani semua operasi CRUD agenda
 */

class AgendaService {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all agendas (admin only)
     * @return array
     */
    public function getAllAgendas() {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, u.nama as creator_name, u.role as creator_role,
                       approver.nama as approver_name
                FROM agendas a
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN users approver ON a.approved_by = approver.id
                ORDER BY a.tanggal DESC, a.waktu ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get all agendas error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil data agenda');
        }
    }
    
    /**
     * Get approved agendas only
     * @return array
     */
    public function getApprovedAgendas() {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, u.nama as creator_name, u.role as creator_role
                FROM agendas a
                LEFT JOIN users u ON a.created_by = u.id
                WHERE a.status = 'approved'
                ORDER BY a.tanggal ASC, a.waktu ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get approved agendas error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil agenda yang disetujui');
        }
    }
    
    /**
     * Get pending agendas (admin only)
     * @return array
     */
    public function getPendingAgendas() {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, u.nama as creator_name, u.role as creator_role
                FROM agendas a
                LEFT JOIN users u ON a.created_by = u.id
                WHERE a.status = 'pending'
                ORDER BY a.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get pending agendas error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil agenda pending');
        }
    }
    
    /**
     * Get agendas for specific user (guru can see their own + approved)
     * @param int $userId
     * @return array
     */
    public function getUserAgendas($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, u.nama as creator_name, u.role as creator_role,
                       approver.nama as approver_name
                FROM agendas a
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN users approver ON a.approved_by = approver.id
                WHERE a.status = 'approved' OR a.created_by = ?
                ORDER BY a.tanggal ASC, a.waktu ASC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get user agendas error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil agenda user');
        }
    }
    
    /**
     * Get agenda by ID
     * @param int $agendaId
     * @return array|null
     */
    public function getAgendaById($agendaId) {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, u.nama as creator_name, u.role as creator_role,
                       approver.nama as approver_name
                FROM agendas a
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN users approver ON a.approved_by = approver.id
                WHERE a.id = ?
            ");
            $stmt->execute([$agendaId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log('Get agenda by ID error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil detail agenda');
        }
    }
    
    /**
     * Create new agenda
     * @param array $data
     * @return int|false
     */
    public function createAgenda($data) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                INSERT INTO agendas (judul, deskripsi, tanggal, waktu, tempat, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $data['judul'],
                $data['deskripsi'] ?? '',
                $data['tanggal'],
                $data['waktu'],
                $data['tempat'],
                $data['status'] ?? 'pending',
                $data['created_by']
            ]);
            
            if ($result) {
                $agendaId = $this->db->lastInsertId();
                $this->db->commit();
                return $agendaId;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Create agenda error: ' . $e->getMessage());
            throw new Exception('Gagal membuat agenda');
        }
    }
    
    /**
     * Update existing agenda
     * @param int $agendaId
     * @param array $data
     * @return bool
     */
    public function updateAgenda($agendaId, $data) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                UPDATE agendas 
                SET judul = ?, deskripsi = ?, tanggal = ?, waktu = ?, 
                    tempat = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $data['judul'],
                $data['deskripsi'] ?? '',
                $data['tanggal'],
                $data['waktu'],
                $data['tempat'],
                $data['status'] ?? 'pending',
                $agendaId
            ]);
            
            if ($result) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Update agenda error: ' . $e->getMessage());
            throw new Exception('Gagal memperbarui agenda');
        }
    }
    
    /**
     * Approve agenda
     * @param int $agendaId
     * @param int $approvedBy
     * @return bool
     */
    public function approveAgenda($agendaId, $approvedBy) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                UPDATE agendas 
                SET status = 'approved', approved_by = ?, approved_at = NOW(), updated_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            
            $result = $stmt->execute([$approvedBy, $agendaId]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Approve agenda error: ' . $e->getMessage());
            throw new Exception('Gagal menyetujui agenda');
        }
    }
    
    /**
     * Reject agenda
     * @param int $agendaId
     * @param int $approvedBy
     * @param string $reason
     * @return bool
     */
    public function rejectAgenda($agendaId, $approvedBy, $reason = '') {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                UPDATE agendas 
                SET status = 'rejected', approved_by = ?, rejection_reason = ?, updated_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            
            $result = $stmt->execute([$approvedBy, $reason, $agendaId]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Reject agenda error: ' . $e->getMessage());
            throw new Exception('Gagal menolak agenda');
        }
    }
    
    /**
     * Delete agenda
     * @param int $agendaId
     * @return bool
     */
    public function deleteAgenda($agendaId) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("DELETE FROM agendas WHERE id = ?");
            $result = $stmt->execute([$agendaId]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Delete agenda error: ' . $e->getMessage());
            throw new Exception('Gagal menghapus agenda');
        }
    }
    
    /**
     * Get agenda statistics
     * @return array
     */
    public function getAgendaStats() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_agendas,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
                    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count,
                    COUNT(CASE WHEN tanggal = CURDATE() AND status = 'approved' THEN 1 END) as today_count,
                    COUNT(CASE WHEN tanggal > CURDATE() AND status = 'approved' THEN 1 END) as upcoming_count,
                    COUNT(CASE WHEN tanggal < CURDATE() AND status = 'approved' THEN 1 END) as past_count
                FROM agendas
            ");
            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log('Get agenda stats error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil statistik agenda');
        }
    }
    
    /**
     * Get today's agendas count
     * @return int
     */
    public function getTodayAgendasCount() {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM agendas 
                WHERE tanggal = CURDATE() AND status = 'approved'
            ");
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Get today agendas count error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get upcoming agendas count
     * @return int
     */
    public function getUpcomingAgendasCount() {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM agendas 
                WHERE tanggal > CURDATE() AND status = 'approved'
            ");
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Get upcoming agendas count error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Search agendas
     * @param string $query
     * @param array $filters
     * @return array
     */
    public function searchAgendas($query, $filters = []) {
        try {
            $whereConditions = ['1=1'];
            $params = [];
            
            // Text search
            if (!empty($query)) {
                $whereConditions[] = "(a.judul LIKE ? OR a.deskripsi LIKE ? OR a.tempat LIKE ?)";
                $searchTerm = "%{$query}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Status filter
            if (!empty($filters['status'])) {
                $whereConditions[] = "a.status = ?";
                $params[] = $filters['status'];
            }
            
            // Date range filter
            if (!empty($filters['date_from'])) {
                $whereConditions[] = "a.tanggal >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $whereConditions[] = "a.tanggal <= ?";
                $params[] = $filters['date_to'];
            }
            
            // Creator filter
            if (!empty($filters['created_by'])) {
                $whereConditions[] = "a.created_by = ?";
                $params[] = $filters['created_by'];
            }
            
            $sql = "
                SELECT a.*, u.nama as creator_name, u.role as creator_role,
                       approver.nama as approver_name
                FROM agendas a
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN users approver ON a.approved_by = approver.id
                WHERE " . implode(' AND ', $whereConditions) . "
                ORDER BY a.tanggal DESC, a.waktu ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Search agendas error: ' . $e->getMessage());
            throw new Exception('Gagal mencari agenda');
        }
    }
    
    /**
     * Get agendas by date range
     * @param string $startDate
     * @param string $endDate
     * @param string $status
     * @return array
     */
    public function getAgendasByDateRange($startDate, $endDate, $status = 'approved') {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, u.nama as creator_name
                FROM agendas a
                LEFT JOIN users u ON a.created_by = u.id
                WHERE a.tanggal BETWEEN ? AND ? AND a.status = ?
                ORDER BY a.tanggal ASC, a.waktu ASC
            ");
            $stmt->execute([$startDate, $endDate, $status]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Get agendas by date range error: ' . $e->getMessage());
            throw new Exception('Gagal mengambil agenda berdasarkan tanggal');
        }
    }
    
    /**
     * Auto-reject old pending agendas
     * @param int $maxDays
     * @return int Number of rejected agendas
     */
    public function autoRejectOldPendingAgendas($maxDays = 30) {
        try {
            $stmt = $this->db->prepare("
                UPDATE agendas 
                SET status = 'rejected', 
                    rejection_reason = 'Auto-rejected: Pending terlalu lama',
                    updated_at = NOW()
                WHERE status = 'pending' 
                    AND DATEDIFF(NOW(), created_at) > ?
            ");
            $stmt->execute([$maxDays]);
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log('Auto reject old pending agendas error: ' . $e->getMessage());
            throw new Exception('Gagal auto-reject agenda lama');
        }
    }
}
?>