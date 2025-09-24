-- Database Schema untuk Agenda Management System
-- File: database.sql

-- Buat database baru
CREATE DATABASE IF NOT EXISTS agenda_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agenda_management;

-- Tabel untuk menyimpan data pengguna (siswa, guru, admin)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('siswa', 'guru', 'admin') NOT NULL,
    nama VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk menyimpan data agenda
CREATE TABLE agendas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    judul VARCHAR(255) NOT NULL,
    deskripsi TEXT,
    tanggal DATE NOT NULL,
    waktu TIME NOT NULL,
    tempat VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_by INT NOT NULL,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_tanggal (tanggal),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by),
    INDEX idx_approved_by (approved_by),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk log aktivitas (opsional, untuk tracking perubahan)
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL, -- 'create', 'update', 'approve', 'reject', 'delete'
    agenda_id INT NULL,
    description TEXT,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (agenda_id) REFERENCES agendas(id) ON DELETE SET NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_agenda_id (agenda_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk pengaturan sistem (opsional)
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert data pengguna sample dengan password yang sudah di-hash
-- Password untuk semua user: "password"
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO users (email, password, role, nama, status) VALUES
-- Admin
('admin@smkn1kotabekasi.admin.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator', 'active'),

-- Guru
('guru1@smkn1kotabekasi.guru.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru', 'Pak Budi Santoso', 'active'),
('guru2@smkn1kotabekasi.guru.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru', 'Bu Sari Dewi', 'active'),
('guru3@smkn1kotabekasi.guru.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru', 'Pak Ahmad Rahman', 'active'),

-- Siswa
('siswa1@smkn1kotabekasi.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siswa', 'Ahmad Rizki', 'active'),
('siswa2@smkn1kotabekasi.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siswa', 'Siti Nurhaliza', 'active'),
('siswa3@smkn1kotabekasi.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siswa', 'Dandi Pratama', 'active'),
('siswa4@smkn1kotabekasi.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siswa', 'Maya Sari', 'active'),
('siswa5@smkn1kotabekasi.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siswa', 'Rudi Hermawan', 'active');

-- Insert data agenda sample (beberapa sudah approved, beberapa masih pending)
INSERT INTO agendas (judul, deskripsi, tanggal, waktu, tempat, status, created_by, approved_by, approved_at) VALUES

-- Agenda yang sudah approved (bisa dilihat siswa)
('APEL PEMBUKAAN LOMBA', 
 'Apel pembukaan acara perlombaan siswa dan guru yang akan dilaksanakan untuk membuka rangkaian kegiatan perlombaan tingkat sekolah. Seluruh siswa dan guru diharapkan hadir tepat waktu untuk mengikuti serangkaian acara pembukaan.',
 '2025-09-21', '07:00:00', 'Lapangan Sekolah', 'approved', 1, 1, NOW()),

('PERLOMBAAN AKADEMIK DAN OLAHRAGA', 
 'Perlombaan pertama dimulai pada pukul 07.30 dan diharapkan selesai pada pukul 15.45. Berbagai kategori lomba akan dilaksanakan seperti lomba akademik (matematika, bahasa Indonesia, bahasa Inggris), olahraga (sepak bola, voli, badminton), dan seni budaya (tari, musik, lukis).',
 '2025-09-21', '07:30:00', 'Berbagai Lokasi Sekolah', 'approved', 1, 1, NOW()),

('SEMINAR TOEC (Test of English Competency)', 
 'Seminar yang bertujuan untuk mensosialisasikan TOEC kepada seluruh siswa kelas XI dan XII. Materi akan disampaikan oleh narasumber yang kompeten di bidangnya untuk memberikan pemahaman yang mendalam tentang pentingnya sertifikasi bahasa Inggris.',
 '2025-09-25', '08:00:00', 'Aula Sekolah', 'approved', 2, 1, NOW()),

('STUDY TOUR ANGKATAN 28', 
 'Study Tour angkatan 28 direncanakan akan diadakan di Miyama, Jepang. Di sana, siswa dapat ikut serta dalam workshop membuat kerajinan tradisional, belajar memasak masakan tradisional Jepang, dan mempelajari budaya lokal.',
 '2025-10-15', '06:00:00', 'Miyama, Jepang', 'approved', 2, 1, NOW()),

('OUTING CLASS KELAS XII', 
 'Outing class ini bertujuan agar siswa dan guru mendapat suasana baru saat belajar mengajar. Kegiatan akan dilaksanakan di alam terbuka untuk memberikan pengalaman belajar yang berbeda dan menyenangkan.',
 '2025-10-20', '07:00:00', 'Taman Safari Indonesia, Bogor', 'approved', 3, 1, NOW()),

('LIBUR HARI RAYA IDUL FITRI', 
 'Libur nasional memperingati Hari Raya Idul Fitri 1446 H. Seluruh kegiatan belajar mengajar diliburkan selama 3 hari untuk memperingati hari raya umat Islam.',
 '2025-09-30', '00:00:00', 'Rumah Masing-masing', 'approved', 1, 1, NOW()),

('UJIAN TENGAH SEMESTER GANJIL', 
 'Pelaksanaan Ujian Tengah Semester (UTS) untuk semua kelas. Ujian akan berlangsung selama 5 hari dengan jadwal yang telah ditentukan sesuai dengan mata pelajaran masing-masing kelas.',
 '2025-10-01', '07:30:00', 'Ruang Kelas Masing-masing', 'approved', 1, 1, NOW()),

-- Agenda yang masih pending (tidak bisa dilihat siswa)
('KUNJUNGAN KE KUIL JEPANG', 
 'Study Tour lanjutan yang bertujuan agar para siswa dapat mempelajari budaya dan tradisi keagamaan Jepang. Siswa akan belajar tentang tata cara ibadah, sejarah kuil, filosofi Zen Buddhism, dan cara menghormati tempat suci.',
 '2025-11-10', '09:00:00', 'Kuil Sensoji, Tokyo, Jepang', 'pending', 2, NULL, NULL),

('WORKSHOP KERAJINAN TRADISIONAL JEPANG', 
 'Workshop membuat kerajinan tradisional Jepang seperti origami tingkat lanjut, japanese calligraphy (shodou), dan pottery (tembikar). Siswa akan belajar langsung dari artisan lokal dan dapat membawa pulang hasil karya mereka sebagai kenang-kenangan.',
 '2025-11-12', '10:00:00', 'Cultural Center Kyoto, Jepang', 'pending', 3, NULL, NULL),

('PELATIHAN GURU TEKNOLOGI PENDIDIKAN', 
 'Pelatihan untuk semua guru tentang penggunaan teknologi terbaru dalam pendidikan, termasuk pembelajaran digital, penggunaan AI dalam edukasi, dan platform pembelajaran online.',
 '2025-10-05', '13:00:00', 'Lab Komputer', 'pending', 4, NULL, NULL),

('LOMBA KREATIVITAS SISWA TINGKAT NASIONAL', 
 'Persiapan dan seleksi siswa untuk mengikuti Lomba Kreativitas Siswa tingkat nasional. Meliputi bidang karya tulis ilmiah, inovasi teknologi, dan seni budaya.',
 '2025-11-01', '08:00:00', 'Aula dan Lab', 'pending', 2, NULL, NULL);

-- Insert activity logs sample
INSERT INTO activity_logs (user_id, action, agenda_id, description, ip_address) VALUES
(1, 'create', 1, 'Admin membuat agenda APEL PEMBUKAAN LOMBA', '127.0.0.1'),
(1, 'approve', 1, 'Admin menyetujui agenda APEL PEMBUKAAN LOMBA', '127.0.0.1'),
(2, 'create', 8, 'Guru membuat agenda KUNJUNGAN KE KUIL JEPANG', '127.0.0.1'),
(3, 'create', 9, 'Guru membuat agenda WORKSHOP KERAJINAN TRADISIONAL JEPANG', '127.0.0.1'),
(1, 'create', 2, 'Admin membuat agenda PERLOMBAAN AKADEMIK DAN OLAHRAGA', '127.0.0.1'),
(1, 'approve', 2, 'Admin menyetujui agenda PERLOMBAAN AKADEMIK DAN OLAHRAGA', '127.0.0.1');

-- Insert pengaturan sistem
INSERT INTO settings (setting_key, setting_value, description) VALUES
('app_name', 'Agenda Management System', 'Nama aplikasi'),
('school_name', 'SMK Negeri 1 Kota Bekasi', 'Nama sekolah'),
('max_pending_days', '30', 'Maksimal hari agenda pending sebelum otomatis rejected'),
('notification_email', 'admin@smkn1kotabekasi.admin.sch.id', 'Email untuk notifikasi sistem'),
('timezone', 'Asia/Jakarta', 'Timezone aplikasi'),
('academic_year', '2024/2025', 'Tahun ajaran aktif'),
('semester', 'Ganjil', 'Semester aktif');

-- Views untuk mempermudah query

-- View untuk agenda dengan informasi creator
CREATE VIEW agenda_with_creator AS
SELECT 
    a.*,
    u.nama as creator_name,
    u.role as creator_role,
    approver.nama as approver_name
FROM agendas a
LEFT JOIN users u ON a.created_by = u.id
LEFT JOIN users approver ON a.approved_by = approver.id;

-- View untuk statistik agenda
CREATE VIEW agenda_stats AS
SELECT 
    COUNT(*) as total_agendas,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count,
    COUNT(CASE WHEN tanggal = CURDATE() AND status = 'approved' THEN 1 END) as today_count,
    COUNT(CASE WHEN tanggal > CURDATE() AND status = 'approved' THEN 1 END) as upcoming_count
FROM agendas;

-- Stored Procedures untuk operasi umum

DELIMITER //

-- Procedure untuk auto-reject agenda pending yang sudah terlalu lama
CREATE PROCEDURE AutoRejectOldPendingAgendas()
BEGIN
    DECLARE max_days INT DEFAULT 30;
    
    -- Ambil setting max pending days
    SELECT CAST(setting_value AS UNSIGNED) INTO max_days 
    FROM settings 
    WHERE setting_key = 'max_pending_days' 
    LIMIT 1;
    
    -- Update agenda yang sudah pending terlalu lama
    UPDATE agendas 
    SET status = 'rejected', 
        rejection_reason = 'Auto-rejected: Pending terlalu lama',
        updated_at = NOW()
    WHERE status = 'pending' 
        AND DATEDIFF(NOW(), created_at) > max_days;
        
    -- Log aktivitas
    INSERT INTO activity_logs (user_id, action, description)
    SELECT 1, 'auto_reject', CONCAT('Auto-rejected ', ROW_COUNT(), ' old pending agendas');
END //

-- Procedure untuk cleanup log lama
CREATE PROCEDURE CleanupOldLogs()
BEGIN
    -- Hapus log yang lebih dari 1 tahun
    DELETE FROM activity_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
    
    SELECT ROW_COUNT() as deleted_logs;
END //

-- Function untuk mendapatkan agenda hari ini
CREATE FUNCTION GetTodayAgendasCount() 
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE agenda_count INT DEFAULT 0;
    
    SELECT COUNT(*) INTO agenda_count
    FROM agendas 
    WHERE tanggal = CURDATE() 
        AND status = 'approved';
    
    RETURN agenda_count;
END //

DELIMITER ;

-- Triggers untuk audit trail

-- Trigger untuk log saat agenda dibuat
DELIMITER //
CREATE TRIGGER agenda_after_insert 
AFTER INSERT ON agendas
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, agenda_id, description)
    VALUES (NEW.created_by, 'create', NEW.id, CONCAT('Agenda dibuat: ', NEW.judul));
END //

-- Trigger untuk log saat agenda diupdate
CREATE TRIGGER agenda_after_update 
AFTER UPDATE ON agendas
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO activity_logs (user_id, action, agenda_id, description)
        VALUES (
            COALESCE(NEW.approved_by, NEW.created_by), 
            CASE NEW.status 
                WHEN 'approved' THEN 'approve'
                WHEN 'rejected' THEN 'reject'
                ELSE 'update'
            END,
            NEW.id, 
            CONCAT('Status agenda diubah dari ', OLD.status, ' menjadi ', NEW.status, ': ', NEW.judul)
        );
    ELSE
        INSERT INTO activity_logs (user_id, action, agenda_id, description)
        VALUES (NEW.created_by, 'update', NEW.id, CONCAT('Agenda diperbarui: ', NEW.judul));
    END IF;
END //

-- Trigger untuk update last_login saat user login
CREATE TRIGGER user_after_update_login
BEFORE UPDATE ON users
FOR EACH ROW
BEGIN
    IF OLD.last_login != NEW.last_login THEN
        SET NEW.updated_at = NOW();
    END IF;
END //

DELIMITER ;

-- Index tambahan untuk performa
CREATE INDEX idx_agendas_date_status ON agendas(tanggal, status);
CREATE INDEX idx_agendas_creator_status ON agendas(created_by, status);
CREATE INDEX idx_activity_logs_user_date ON activity_logs(user_id, created_at);

-- Query examples untuk testing

-- 1. Query untuk mengambil agenda berdasarkan role
-- Untuk role 'siswa': hanya agenda yang approved
-- SELECT * FROM agenda_with_creator WHERE status = 'approved' ORDER BY tanggal ASC, waktu ASC;

-- 2. Untuk role 'guru': semua agenda yang dia buat + agenda approved
-- SELECT * FROM agenda_with_creator WHERE status = 'approved' OR created_by = [USER_ID] ORDER BY tanggal ASC, waktu ASC;

-- 3. Untuk role 'admin': semua agenda
-- SELECT * FROM agenda_with_creator ORDER BY tanggal ASC, waktu ASC;

-- 4. Query untuk pending agendas (hanya admin yang bisa lihat)
-- SELECT * FROM agenda_with_creator WHERE status = 'pending' ORDER BY created_at DESC;

-- 5. Query untuk statistik dashboard
-- SELECT * FROM agenda_stats;

-- 6. Query untuk agenda hari ini
-- SELECT * FROM agenda_with_creator WHERE tanggal = CURDATE() AND status = 'approved';

-- 7. Query untuk agenda mendatang
-- SELECT * FROM agenda_with_creator WHERE tanggal > CURDATE() AND status = 'approved' ORDER BY tanggal ASC LIMIT 10;

-- Commit all changes
COMMIT;