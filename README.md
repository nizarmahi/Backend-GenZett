
# **Backend-GenZett**  
# **Sport Center Reservation - Backend**  

## **Deskripsi Proyek**  
Ini adalah bagian backend untuk sistem reservasi lapangan olahraga yang dikembangkan menggunakan **Laravel**. Backend ini berfungsi sebagai RESTful API yang menyediakan data untuk frontend (dibuat dengan Next.js) dan mengelola proses autentikasi, manajemen user, lapangan, jadwal, dan reservasi.

---

## **Cara Menjalankan Proyek**  

### **1. Clone Repository**  
Pertama, clone repository ke komputer lokal:  
```bash
git clone https://github.com/nizarmahi/Backend-GenZett.git
cd Backend-GenZett
```

### **2. Install Dependencies**  
Jalankan perintah berikut untuk menginstal semua package Laravel:  
```bash
composer install
```

### **3. Setup Environment**  
Salin file `.env.example` menjadi `.env` lalu atur konfigurasi sesuai kebutuhan:
```bash
cp .env.example .env
```

Edit `.env`:
- Atur `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`
- Jika menggunakan frontend lokal, atur `SANCTUM_STATEFUL_DOMAINS=http://localhost:3000`
- Atur `APP_URL=http://localhost:8000`

### **4. Generate Key dan Migrasi Database**  
```bash
php artisan key:generate
php artisan migrate
```

### **5. Jalankan Server Laravel**  
```bash
php artisan serve
```
API akan tersedia di [http://localhost:8000](http://localhost:8000), dan semua endpoint tersedia di `routes/api.php`.

---

## **Fitur Utama API**  
- Autentikasi dengan Sanctum (Login, Register, Logout)
- CRUD untuk User, Field, Reservation, dan Sport
- CORS support agar dapat diakses dari frontend
- Validasi dan response JSON standard

---

## **Pengaturan Git & Branching**  

### **6. Setup Git & Membuat Branch Baru**  
1. Pastikan sudah mengatur Git:  
   ```bash
   git config --global user.name "Your Name"
   git config --global user.email "your-email@example.com"
   ```

2. Checkout branch `development`:  
   ```bash
   git checkout development
   git pull origin development
   ```

3. Buat branch baru berdasarkan `development`:  
   ```bash
   git checkout -b fitur-nama-fitur
   git push origin fitur-nama-fitur
   ```

---

## **Penggunaan Git Workflow**  

### **7. Commit dan Push Perubahan**  
```bash
git add .
git commit -m "Menambahkan fitur X"
git push origin fitur-nama-fitur
```

### **8. Pull Request dan Merge**  
1. Buat pull request dari GitHub ke branch `development`  
2. Pastikan deskripsi pull request menjelaskan perubahan  
3. Setelah direview dan tidak ada konflik, merge ke `development`  
4. Untuk rilis, branch `development` akan digabungkan ke `main`

---

## **Kolaborasi Tim**  

- Selalu **pull perubahan terbaru** sebelum memulai pekerjaan  
  ```bash
  git pull origin development
  ```
- Gunakan **nama branch yang jelas** dan sesuai fitur yang dikerjakan  

---

## **Dokumentasi & Sumber Daya**  
- **[Laravel Documentation](https://laravel.com/docs)**  
- **[Laravel Sanctum](https://laravel.com/docs/sanctum)**  
- **[MySQL Documentation](https://dev.mysql.com/doc/)**  

---
