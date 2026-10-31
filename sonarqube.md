# Laporan Perbaikan Temuan SonarQube

Project: **STT-NF Sistem Perpustakaan (Kelompok C)**
Tanggal perbaikan: 31 Oktober 2026
Total temuan: **11 issue** (semua sudah diperbaiki)

## Ringkasan Temuan

| No | Rule | Jenis Temuan | File | Baris | Severity | Status |
|----|------|--------------|------|-------|----------|--------|
| 1 | phpsecurity:S3649 | SQL Injection | `app/Http/Controllers/BookController.php` | 20 | Blocker | Fixed |
| 2 | phpsecurity:S3649 | SQL Injection | `app/Http/Controllers/BookController.php` | 28 | Blocker | Fixed |
| 3 | phpsecurity:S2076 | Command Injection | `app/Http/Controllers/LaporanController.php` | 30 | Blocker | Fixed |
| 4 | phpsecurity:S2076 | Command Injection | `app/Http/Controllers/LaporanController.php` | 40 | Blocker | Fixed |
| 5 | phpsecurity:S3649 | SQL Injection | `app/Http/Controllers/MemberController.php` | 25 | Blocker | Fixed |
| 6 | php:S6418 | Hardcoded secret | `config/database.php` | 14 | Blocker | Fixed |
| 7 | php:S4790 | Weak hashing (MD5) | `app/Http/Controllers/MemberController.php` | 12 | High | Fixed |
| 8 | php:S4790 | Weak hashing (MD5) | `app/Http/Controllers/MemberController.php` | 24 | High | Fixed |
| 9 | php:S4502 | CSRF protection disabled | `app/Http/Middleware/VerifyCsrfToken.php` | 13 | Blocker | Fixed |
| 10 | text:S8567 | Missing dependency lock file | `composer.json` | - | Blocker | Fixed |
| 11 | php:S1135 | TODO comment | `app/Http/Middleware/VerifyCsrfToken.php` | 10 | Info | Fixed |

---

## Detail Temuan dan Perbaikan

### 1. SQL Injection pada `BookController.search()` — S3649

**File:** `app/Http/Controllers/BookController.php:20`
**CWE:** CWE-89 (SQL Injection)

**Masalah:** Nilai `judul`, `penulis`, dan `tahun` dari HTTP request diinterpolasi langsung ke string SQL. Penyerang dapat mengirim payload seperti `' OR '1'='1` untuk melewati filter atau mengekstrak seluruh isi database.

**Sebelum:**

```php
$sql = "SELECT * FROM books
        WHERE judul LIKE '%$judul%'
          AND penulis LIKE '%$penulis%'
          AND tahun_terbit = '$tahun'";
return response()->json(DB::select($sql));
```

**Perbaikan:** Query diubah menjadi prepared statement dengan parameter binding. Nilai user tidak lagi menjadi bagian dari string SQL.

```php
$sql = "SELECT * FROM books
        WHERE judul LIKE ?
          AND penulis LIKE ?
          AND tahun_terbit = ?";

$books = DB::select($sql, [
    '%' . $judul . '%',
    '%' . $penulis . '%',
    $tahun,
]);
```

---

### 2. SQL Injection pada `BookController.byGenre()` — S3649

**File:** `app/Http/Controllers/BookController.php:28`

**Masalah:** Parameter `genre` masuk langsung ke query dan parameter `sort` digunakan langsung sebagai nama kolom `ORDER BY`. Ini memungkinkan UNION-based injection maupun injeksi pada klausa ORDER BY.

**Perbaikan:**
- `genre` dipindahkan menjadi parameter binding.
- `sort` divalidasi menggunakan allowlist kolom yang diizinkan (bukan input bebas), karena nama kolom tidak dapat di-bind pada prepared statement.

```php
$sort = match ($request->input('sort', 'judul')) {
    'penulis'      => 'penulis',
    'tahun_terbit' => 'tahun_terbit',
    'genre'        => 'genre',
    default        => 'judul',
};

$books = DB::select(
    "SELECT * FROM books WHERE genre = ? ORDER BY {$sort} ASC",
    [$genre]
);
```

---

### 3. Command Injection pada `LaporanController.eksporDenda()` — S2076

**File:** `app/Http/Controllers/LaporanController.php:30`
**CWE:** CWE-78 (OS Command Injection)

**Masalah:** `bulan` dan `format` dari request dimasukkan ke perintah shell melalui `exec()`, sehingga payload seperti `2024-01; cat /etc/passwd` akan dieksekusi oleh server.

**Perbaikan:**
- Input divalidasi: `bulan` harus berformat `YYYY-MM`, `format` dibatasi ke allowlist.
- `exec()` diganti dengan `Symfony\Component\Process\Process` yang menerima argumen sebagai array, sehingga tidak ada shell yang diinterpretasikan.

```php
$validated = $request->validate([
    'bulan'  => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
    'format' => ['nullable', 'string', 'in:pdf,csv,xlsx'],
]);

$process = new Process([
    'php',
    'artisan',
    'laporan:denda',
    "--bulan={$validated['bulan']}",
    '--format=' . ($validated['format'] ?? 'pdf'),
]);
$process->run();
```

---

### 4. Command Injection pada `LaporanController.konversiFormat()` — S2076

**File:** `app/Http/Controllers/LaporanController.php:40`

**Masalah:** `input` dan `output_format` dari request dimasukkan ke `shell_exec()` tanpa validasi, memungkinkan eksekusi perintah arbitrer.

**Perbaikan:**
- `input` diambil basename-nya agar tidak bisa memakai path traversal.
- `output_format` dibatasi ke allowlist format yang didukung.
- `shell_exec()` diganti dengan `Process` berbasis array argumen.

```php
$validated = $request->validate([
    'input'         => ['required', 'string', 'max:255'],
    'output_format' => ['required', 'string', 'in:pdf,docx,xlsx,pptx,odt'],
]);

$process = new Process([
    'libreoffice',
    '--headless',
    '--convert-to',
    $validated['output_format'],
    basename($validated['input']),
]);
$process->run();
```

---

### 5. SQL Injection pada `MemberController.masuk()` — S3649

**File:** `app/Http/Controllers/MemberController.php:25`

**Masalah:** `nim` diinterpolasi langsung ke query login. Penyerang dapat login sebagai user mana pun dengan payload seperti `admin' --`.

**Perbaikan:** Query menggunakan parameter binding, dan verifikasi password dilakukan dengan `Hash::check()`.

```php
$member = DB::select('SELECT * FROM members WHERE nim = ? LIMIT 1', [$validated['nim']]);

if ($member && Hash::check($validated['password'], $member[0]->password)) {
    session(['member_id' => $member[0]->id, 'role' => $member[0]->role]);
    return response()->json(['status' => 'ok']);
}
```

---

### 6. Hardcoded Secret pada `config/database.php` — S6418

**File:** `config/database.php:10,14,15`

**Masalah:** Password database, ISBN API key, dan Google Books API key ditulis langsung di source code. Siapa pun yang dapat membaca repository dapat menggunakan kredensial tersebut.

**Perbaikan:** Semua kredensial dipindahkan ke environment variable melalui `env()`. Nilai asli harus disimpan di `.env` (tidak di-commit) atau secret manager.

```php
'password'     => env('DB_PASSWORD'),
'isbn_api_key' => env('ISBN_DB_API_KEY'),
'google_books' => env('GOOGLE_BOOKS_API_KEY'),
```

---

### 7. Weak Hashing (MD5) pada `MemberController.daftar()` — S4790

**File:** `app/Http/Controllers/MemberController.php:12`

**Masalah:** Password anggota di-hash dengan MD5. MD5 cepat dan sudah rusak secara kriptografis, sehingga rentan brute-force dan rainbow table.

**Perbaikan:** Menggunakan `Hash::make()` (bcrypt) yang merupakan algoritma hashing password yang aman dan lambat secara desain.

```php
Hash::make($validated['password']);
```

---

### 8. Weak Hashing (MD5) pada `MemberController.masuk()` — S4790

**File:** `app/Http/Controllers/MemberController.php:24`

**Masalah:** Password login di-hash dengan MD5 lalu dibandingkan langsung di query.

**Perbaikan:** Password diverifikasi dengan `Hash::check()` terhadap hash bcrypt yang tersimpan.

```php
Hash::check($validated['password'], $member[0]->password);
```

---

### 9. CSRF Protection Disabled pada `VerifyCsrfToken` — S4502

**File:** `app/Http/Middleware/VerifyCsrfToken.php:13`
**CWE:** CWE-352 (Cross-Site Request Forgery)

**Masalah:** `protected $except = ['*'];` menonaktifkan proteksi CSRF untuk seluruh route, sehingga form dari situs eksternal dapat mengirim request atas nama user yang sedang login.

**Perbaikan:** Daftar pengecualian dikosongkan agar proteksi CSRF aktif untuk semua route.

```php
protected $except = [];
```

---

### 10. Dependency Lock File Hilang — text:S8567

**File:** `composer.json`

**Masalah:** `composer.lock` tidak ada di repository, sehingga versi dependency tidak deterministik dan build bisa berbeda antar mesin.

**Perbaikan:**
- Nama package diperbaiki menjadi lowercase (`stt-nf/kelompok-c-perpustakaan`) agar valid terhadap skema Composer.
- `composer.lock` dibuat dengan `composer update --no-install` dan di-commit ke repository.

---

### 11. TODO Comment pada `VerifyCsrfToken` — S1135

**File:** `app/Http/Middleware/VerifyCsrfToken.php:10`

**Masalah:** Komentar `TODO: Remove this before production!` menandakan pekerjaan yang belum diselesaikan.

**Perbaikan:** Pengecualian CSRF `'*'` dihapus (lihat temuan #9), sehingga TODO tersebut sudah terselesaikan dan komentarnya dihilangkan.

---

## Catatan

Perbaikan ini mencakup **11 temuan SonarQube** di atas. Komentar `VULNERABILITY` lain yang masih ada di codebase (IDOR, path traversal, mass assignment) berada di luar cakupan 11 temuan ini dan perlu ditangani pada iterasi berikutnya.
