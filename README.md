# STT-NF Sistem Perpustakaan — DevSecOps Lab (Kelompok C)

Aplikasi manajemen perpustakaan digital yang mengandung beberapa kerentanan keamanan.

## Setup

```bash
docker-compose up -d
# Tunggu SonarQube di http://localhost:9000
sonar-scanner
```

## Struktur Utama
- `app/Http/Controllers/BookController.php` — manajemen buku
- `app/Http/Controllers/MemberController.php` — manajemen anggota
- `app/Http/Controllers/LaporanController.php` — laporan & ekspor
- `config/database.php` — konfigurasi database
