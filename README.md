
# 🏟️ Backend-GenZett
**Sistem Reservasi Lapangan Olahraga – Backend (Laravel)**

## 📄 Deskripsi Proyek
Ini adalah backend dari sistem reservasi lapangan olahraga yang dibangun dengan Laravel. Proyek ini menyediakan **RESTful API** untuk frontend (dibuat dengan Next.js) dan menangani fitur seperti:
- Autentikasi user
- Manajemen lapangan
- Jadwal dan reservasi
- Hak akses berdasarkan level pengguna

## 🚀 Cara Menjalankan Proyek

### 1. Clone Repository
```bash
git clone https://github.com/nizarmahi/Backend-GenZett.git
cd Backend-GenZett
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Setup Environment
```bash
cp .env.example .env
```
Lalu edit `.env`:
- DB_DATABASE, DB_USERNAME, DB_PASSWORD → sesuaikan dengan database lokal
- SANCTUM_STATEFUL_DOMAINS=http://localhost:3000 (untuk frontend)
- APP_URL=http://localhost:8000

### 4. Generate Key & Migrasi Database
```bash
php artisan key:generate
php artisan migrate
```

### 5. Jalankan Server
```bash
php artisan serve
```
API aktif di: `http://localhost:8000`  
Cek endpoint di: `routes/api.php`

📑 Dokumentasi API
Dokumentasi lengkap endpoint API tersedia di:
👉 `http://localhost:8000/api/documentation`

## 🧩 Fitur Utama
- 🔐 Autentikasi (Sanctum): Login, Register, Logout
- 👤 CRUD untuk: User, Field, Reservation, Sport
- 🌐 CORS Support
- ✅ Validasi & Response JSON terstandarisasi

## 🔁 Git Workflow & Branching

### 6. Setup Git
```bash
git config --global user.name "Your Name"
git config --global user.email "your-email@example.com"
```

### 7. Checkout ke `development`
```bash
git checkout development
git pull origin development
```

### 8. Buat Branch Fitur Baru
```bash
git checkout -b fitur-nama-fitur
git push origin fitur-nama-fitur
```

### 9. Commit & Push
```bash
git add .
git commit -m "Menambahkan fitur X"
git push origin fitur-nama-fitur
```

### 10. Pull Request & Merge
- Buat PR dari `fitur-nama-fitur` → `development`
- Sertakan deskripsi jelas
- Setelah review & tidak ada konflik, merge ke `development`
- Untuk **rilis**, merge `development` → `main`

## 👥 Kolaborasi Tim
- Selalu **pull perubahan terbaru** sebelum mulai kerja:
  ```bash
  git pull origin development
  ```
- Gunakan penamaan branch yang deskriptif dan sesuai fitur
- Jaga commit tetap bersih & ringkas

## 📚 Referensi
- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/10.x/sanctum)
- [MySQL Documentation](https://dev.mysql.com/doc/)
