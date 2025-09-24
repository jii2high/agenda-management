# 🎓 Agenda Management System - SMK Negeri 1 Kota Bekasi

Sistem manajemen agenda sekolah berbasis web yang memungkinkan guru mengajukan agenda, admin menyetujui/menolak, dan siswa melihat agenda yang telah disetujui.

## 🚀 Fitur Utama

### 👥 Role-based Access Control
- **👨‍🎓 Siswa**: Melihat agenda yang sudah disetujui
- **👨‍🏫 Guru**: Mengajukan agenda baru, edit agenda sendiri, melihat agenda approved + milik sendiri
- **👨‍💼 Admin**: Melihat semua agenda, approve/reject, CRUD semua data

### 📅 Manajemen Agenda
- **Create**: Buat agenda baru dengan validasi form
- **Read**: Tampilan dashboard dengan agenda hari ini dan mendatang  
- **Update**: Edit agenda dengan status kembali ke pending
- **Delete**: Hapus agenda (admin only)
- **Approve/Reject**: Workflow persetujuan agenda

### 🔒 Keamanan & Performa
- **Authentication**: Login dengan email domain sekolah
- **Password Hashing**: Bcrypt encryption
- **SQL Injection Protection**: Prepared statements
- **CORS Support**: Cross-origin resource sharing
- **Activity Logging**: Tracking semua aktivitas user
- **Rate Limiting**: Pembatasan request per IP

## 📁 Struktur File

```
agenda-management/
├── index.html              # Main HTML file
├── css/
│   └── style.css           # Custom CSS styles
├── js/
│   ├── config.js           # Configuration & constants
│   ├── api.js              # API service & data manager
│   ├── auth.js             # Authentication service
│   ├── components.js       # UI components & utilities
│   └── app.js              # Main application logic
├── api/
│   ├── index.php           # Main API endpoint
│   ├── .htaccess           # URL rewriting rules
│   ├── config/
│   │   ├── database.php    # Database configuration
│   │   └── config.php      # API configuration
│   └── classes/
│       ├── ApiResponse.php # API response handler
│       ├── AuthService.php # Authentication logic
│       ├── AgendaService.php # Agenda CRUD operations
│       ├── UserService.php # User management
│       └── ActivityLogger.php # Activity logging
├── database.sql            # Database schema & sample data
└── README.md              # Documentation
```

## 🛠️ Instalasi

### Prasyarat
- **Web Server**: Apache/Nginx dengan PHP 7.4+
- **Database**: MySQL 5.7+ atau MariaDB 10.3+
- **Browser**: Chrome, Firefox, Safari (modern browsers)

### Langkah Instalasi

#### 1. Clone/Download Repository
```bash
git clone https://github.com/yourusername/agenda-management.git
cd agenda-management
```

#### 2. Setup Database
1. Buka phpMyAdmin atau MySQL command line
2. Buat database baru:
   ```sql
   CREATE DATABASE agenda_management;
   ```
3. Import file `database.sql`:
   ```bash
   mysql -u root -p agenda_management < database.sql
   ```
   Atau copy-paste isi file database.sql ke phpMyAdmin

#### 3. Konfigurasi Database
Edit file `api/config/database.php`:
```php
private $host = 'localhost';        // Host database
private $dbname = 'agenda_management'; // Nama database  
private $username = 'root';         // Username MySQL
private $password = '';             // Password MySQL
```

#### 4. Setup Web Server

**Untuk XAMPP:**
1. Copy folder project ke `htdocs/`
2. Akses: `http://localhost/agenda-management/`

**Untuk server production:**
1. Upload files ke directory web server
2. Pastikan PHP dan MySQL berjalan
3. Set permission yang sesuai untuk folder api/

#### 5. Test Installation
1. Buka browser dan akses aplikasi
2. Coba login dengan akun demo:
   - **Admin**: `admin@smkn1kotabekasi.admin.sch.id` / `password`
   - **Guru**: `guru1@smkn1kotabekasi.guru.sch.id` / `password`  
   - **Siswa**: `siswa1@smkn1kotabekasi.sch.id` / `password`

## 🔧 Konfigurasi

### Frontend Configuration
Edit `js/config.js`:
```javascript
const CONFIG = {
    API: {
        BASE_URL: 'https://yourdomain.com/api', // URL API backend
    },
    // ... konfigurasi lainnya
};
```

### Backend Configuration  
Edit `api/config/config.php`:
```php
define('API_VERSION', '1.0.0');
define('TIMEZONE', 'Asia/Jakarta');
define('MAX_LOGIN_ATTEMPTS', 5);
// ... konfigurasi lainnya
```

## 👤 Akun Demo

| Role | Email | Password | Akses |
|------|-------|----------|-------|
| Admin | admin@smkn1kotabekasi.admin.sch.id | password | Semua fitur |
| Guru | guru1@smkn1kotabekasi.guru.sch.id | password | Ajukan agenda |
| Siswa | siswa1@smkn1kotabekasi.sch.id | password | Lihat agenda |

## 📖 Panduan Penggunaan

### Untuk Admin
1. **Login** dengan akun admin
2. **Dashboard** - Lihat semua agenda dan statistik
3. **Pending** - Review dan approve/reject agenda dari guru
4. **Tambah Agenda** - Buat agenda baru (langsung approved)
5. **Manage Users** - Kelola akun pengguna

### Untuk Guru  
1. **Login** dengan akun guru
2. **Dashboard** - Lihat agenda approved + agenda sendiri
3. **Ajukan Agenda** - Buat agenda baru (status pending)
4. **Edit Agenda** - Ubah agenda sendiri (kembali ke pending)

### Untuk Siswa
1. **Login** dengan akun siswa
2. **Dashboard** - Lihat agenda yang sudah disetujui
3. **Detail Agenda** - Klik agenda untuk melihat detail

## 🔍 API Endpoints

### Authentication
- `POST /api/login` - Login user

### Agendas
- `GET /api/agendas` - Get all agendas (admin)
- `GET /api/agendas/approved` - Get approved agendas
- `GET /api/agendas/pending` - Get pending agendas (admin)
- `GET /api/agendas/user/{id}` - Get user agendas
- `POST /api/agendas` - Create agenda
- `PUT /api/agendas/{id}` - Update agenda
- `PUT /api/agendas/{id}/approve` - Approve agenda (admin)
- `PUT /api/agendas/{id}/reject` - Reject agenda (admin)
- `DELETE /api/agendas/{id}` - Delete agenda (admin)

### Users & Statistics
- `GET /api/users` - Get all users
- `POST /api/users` - Create user
- `GET /api/stats` - Get system statistics
- `GET /api/activities` - Get activity logs

## 🛡️ Keamanan

### Authentication
- Email domain validation (harus menggunakan email sekolah)
- Password hashing dengan bcrypt
- Session management dengan localStorage
- Auto logout setelah 24 jam

### Database Security
- Prepared statements untuk semua query
- Input sanitization dan validation
- Foreign key constraints
- Activity logging untuk audit trail

### API Security
- CORS configuration
- Rate limiting (opsional)
- Error handling yang aman
- Input validation di semua endpoint

## 🔧 Troubleshooting

### Database Connection Error
**Error**: `Database connection failed`
**Solusi**:
- Pastikan MySQL/MariaDB berjalan
- Cek username/password di `database.php`
- Pastikan database `agenda_management` sudah dibuat

### CORS Error
**Error**: `Access blocked by CORS policy`
**Solusi**:
- Pastikan `.htaccess` ada di folder `api/`
- Enable mod_rewrite di Apache
- Cek konfigurasi CORS di `api/index.php`

### Login Failed
**Error**: `Email atau password salah`
**Solusi**:
- Pastikan menggunakan email dengan domain yang benar
- Cek apakah data user sudah ter-import dari `database.sql`
- Password default semua akun demo: `password`

### 404 Not Found (API)
**Error**: `Endpoint tidak ditemukan`
**Solusi**:
- Pastikan URL API benar di `config.js`
- Cek `.htaccess` di folder `api/`
- Pastikan mod_rewrite aktif

## 🚀 Deployment Production

### Persiapan
1. **Database**: Buat database production dan import schema
2. **Environment**: Set environment variables untuk keamanan
3. **HTTPS**: Gunakan SSL certificate
4. **Caching**: Setup opcache untuk PHP

### Environment Variables
```bash
DB_HOST=localhost
DB_NAME=agenda_management_prod
DB_USER=agenda_user
DB_PASS=secure_password
```

### Optimasi
1. **Minify**: Compress CSS/JS files
2. **CDN**: Gunakan CDN untuk assets static
3. **Caching**: Implementasi caching untuk API response
4. **Monitoring**: Setup error logging dan monitoring

## 📊 Database Schema

### Tables
- **users**: Data pengguna (admin, guru, siswa)
- **agendas**: Data agenda dengan status approval
- **activity_logs**: Log aktivitas untuk audit
- **settings**: Pengaturan sistem

### Key Features
- **Foreign Keys**: Referential integrity
- **Indexes**: Optimasi query performance  
- **Triggers**: Auto logging untuk audit trail
- **Views**: Simplified data access
- **Stored Procedures**: Common operations

## 🤝 Contributing

### Development Setup
1. Fork repository
2. Create feature branch
3. Make changes
4. Test thoroughly
5. Submit pull request

### Coding Standards
- **PHP**: PSR-4 autoloading, PSR-2 coding style
- **JavaScript**: ES6+ features, meaningful names
- **SQL**: Proper indexing, normalized structure
- **Documentation**: Inline comments and README updates

## 📞 Support

Jika mengalami masalah atau butuh bantuan:

1. **Check Documentation**: Baca README dan komentar kode
2. **Log Files**: Cek error log PHP dan database
3. **GitHub Issues**: Buat issue di repository
4. **Email**: Contact admin sekolah

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🙏 Acknowledgments

- **SMK Negeri 1 Kota Bekasi** - Project sponsor
- **Tailwind CSS** - UI framework
- **Lucide Icons** - Icon library
- **PHP** - Backend language
- **MySQL** - Database system

---

**Developed with ❤️ for SMK Negeri 1 Kota Bekasi**

© 2025 SMK Negeri 1 Kota Bekasi - All rights reserved