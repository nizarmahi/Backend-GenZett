# 📘 Dokumentasi Infrastruktur Deployment Proyek GenZett

## 📦 Ringkasan Infrastruktur

Proyek GenZett dideploy menggunakan 3 VM terpisah sebagai berikut:

| VM               | Fungsi                   | Domain/Subdomain           | Docker | SSL (Let's Encrypt) |
|------------------|--------------------------|-----------------------------|--------|----------------------|
| ReSports-BE      | Backend Laravel + MySQL  | `api.resports.web.id`       | ✅     | ✅ (via Docker)      |
| ReSports-FE      | Frontend Next.js         | `resports.web.id`           | ✅     | ✅ (via Docker)      |
| ReSports-Manager | Portainer (Dashboard)    | `portainer.resports.web.id` | ✅     | ✅ (via Nginx manual) |

> 🔧 Masing-masing VM menjalankan **nginx-proxy + letsencrypt companion container sendiri**, sehingga SSL dikelola terpisah per VM.

---

## 🗂️ Struktur VM

### 🖥️ 1. ReSports-BE (Backend)
- **Fungsi**: Laravel backend + MySQL database
- **Domain**: `api.resports.web.id`
- **Dockerized**: Ya
- **Docker Compose**:
  - jwilder/nginx-proxy
  - jrcs/letsencrypt-nginx-proxy-companion
  - Laravel backend
  - MySQL database
- **SSL**: Otomatis dengan Let's Encrypt via companion

**Struktur direktori:**
```
VM
├── Backend-GenZett/
└── deploy/
    ├── Dockerfile
    └── docker-compose.yml
```
**Container berjalan:**
- `deploy-backend`
- ` mysql:8.0`
- `nginx-proxy`
- `letsencrypt-nginx-proxy-companion`
- `portainer_agent`

---

### 🖥️ 2. ReSports-FE (Frontend)
- **Fungsi**: Next.js frontend
- **Domain**: `resports.web.id`
- **Dockerized**: Ya
- **Docker Compose**:
  - jwilder/nginx-proxy
  - jrcs/letsencrypt-nginx-proxy-companion
  - Next.js App
- **SSL**: Otomatis dengan Let's Encrypt via companion

**Struktur direktori:**
```
VM
├── Backend-GenZett/
└── deploy/
    ├── Dockerfile
    └── docker-compose.yml
```

**Container berjalan:**
- `frontend-genzett`
- `nginx-proxy`
- `letsencrypt-nginx-proxy-companion`
- `portainer_agent`

---

### 🖥️ 3. ReSports-Manager (Portainer)
- **Fungsi**: Dashboard monitoring Portainer
- **Domain**: `portainer.resports.web.id`
- **Dockerized**: Ya (Portainer saja)
- **Reverse Proxy**: Manual via Nginx host
- **SSL**: Manual Let's Encrypt menggunakan Certbot (tidak via Docker)

---

## 🔐 SSL dan Reverse Proxy per VM

| VM               | Reverse Proxy         | SSL Issuer             | Type     |
|------------------|-----------------------|-------------------------|----------|
| ReSports-BE      | `nginx-proxy` Docker  | Let's Encrypt (companion)| Otomatis |
| ReSports-FE      | `nginx-proxy` Docker  | Let's Encrypt (companion)| Otomatis |
| ReSports-Portainer | Manual Nginx Host     | Let's Encrypt (Certbot) | Manual   |

---

## 🚀 CI/CD

Deployment otomatis ke dua VM (Frontend dan Backend) menggunakan GitHub Actions via SSH, namun dengan pendekatan berbeda:

- Frontend (Next.js)
    - Setiap push ke branch development, workflow akan:
        1. Menghapus folder lama di VM.
        2. Upload seluruh source code via SCP ke VM frontend (~/Frontend-GenZett).
        3. Jalankan ulang Docker Compose khusus service frontend.

- Backend (Laravel)
  - Setiap push atau pull request ke branch development, workflow akan:
    1. SSH ke VM dan masuk ke folder backend.
    2. Git pull langsung di VM (tanpa upload kode).
    3. Restart Docker Compose untuk service backend dan mysql.
---

## 📎 Catatan Tambahan

- Portainer Agent berjalan di FE & BE untuk bisa dikelola dari Manager
- Portainer pusat berada di VM Manager, dapat diakses di:
  [https://portainer.resports.web.id](https://portainer.resports.web.id)

---

📌 **Dibuat oleh**: Kelompok 4 - GenZett
