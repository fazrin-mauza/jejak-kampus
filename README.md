<div align="center">

<img src="assets/img/logo.png" alt="Jejak Kampus Logo" width="120" />

# Jejak Kampus

**Sistem Informasi Absensi Mahasiswa Berbasis QR Code dan Geolokasi**

[![Version](https://img.shields.io/badge/versi-1.0-orange?style=for-the-badge)](https://github.com/fazrin-mauza/jejak-kampus)
[![Status](https://img.shields.io/badge/status-In%20Development-blue?style=for-the-badge)](https://github.com/fazrin-mauza/jejak-kampus)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MariaDB](https://img.shields.io/badge/MariaDB-10.11-003545?style=for-the-badge&logo=mariadb&logoColor=white)](https://mariadb.org)
[![License](https://img.shields.io/badge/lisensi-MIT-green?style=for-the-badge)](LICENSE)

**Universitas Negeri Surabaya (UNESA)**
Dikembangkan oleh Tim Pandawa 5 · 2026

[Fitur Utama](#fitur-utama) · [Tech Stack](#tech-stack) · [Struktur Proyek](#struktur-proyek) · [Skema Database](#skema-database) · [Instalasi](#instalasi) · [Tim Pengembang](#tim-pengembang)

</div>

---

## Tentang Proyek

**Jejak Kampus** adalah sistem informasi absensi berbasis web yang dirancang untuk menggantikan mekanisme pencatatan kehadiran konvensional di lingkungan Universitas Negeri Surabaya. Sistem ini menggabungkan dua lapisan verifikasi secara bersamaan, yaitu **QR Code dinamis** yang bersifat sesi-bound dan **validasi GPS berbasis formula Haversine**, sehingga kehadiran mahasiswa dapat dipastikan secara fisik dan tidak dapat didelegasikan kepada pihak lain.

> *"Bukan sekadar absen. Jejak Kampus membuktikan kehadiran yang sesungguhnya."*

### Permasalahan yang Diselesaikan

| Kondisi Sebelumnya | Solusi Jejak Kampus |
|---|---|
| QR Code statis dapat disebarkan kepada mahasiswa yang tidak hadir | QR Code unik per sesi dengan token terenkripsi, berlaku satu kali per sesi aktif |
| Tidak ada verifikasi lokasi fisik mahasiswa | Validasi geolokasi real-time menggunakan formula Haversine dengan radius 10–20 meter |
| Pencatatan kehadiran dilakukan secara manual oleh dosen | Pemantauan real-time dan rekap otomatis, termasuk pencatatan alpha otomatis saat sesi ditutup |
| Pengajuan izin bergantung pada perantara atau penanggung jawab kelas | Upload berkas izin langsung ke sistem, verifikasi langsung oleh dosen yang bersangkutan |

---

## Fitur Utama

### Mahasiswa

Mahasiswa dapat melakukan scan QR Code langsung melalui browser tanpa memerlukan instalasi aplikasi tambahan. Setiap proses absensi dilengkapi dengan validasi GPS otomatis yang memastikan mahasiswa berada dalam radius fisik ruang kelas. Dashboard kehadiran menampilkan persentase kehadiran per mata kuliah secara real-time, sementara pengajuan izin dilakukan secara digital dengan kemampuan unggah berkas pendukung langsung ke dosen. Sistem juga dilengkapi fitur gamifikasi berupa lencana dan peringkat kehadiran.

### Dosen

Dosen dapat membuka dan menutup sesi absensi secara mandiri, di mana QR Code akan di-generate secara otomatis saat sesi dibuka. Pemantauan daftar hadir berlangsung secara real-time tanpa perlu melakukan refresh halaman. Dosen memiliki kemampuan rekap kehadiran, override manual status absensi yang tercatat dalam log perubahan, serta ekspor laporan dalam format Excel (.csv) dan PDF. Verifikasi berkas izin mahasiswa dilakukan langsung melalui antarmuka dosen. Tersedia pula fitur **Jejak AI (Beta)**, yakni asisten berbasis AI yang memungkinkan pembaruan status kehadiran melalui perintah teks natural.

### Administrator

Administrator memiliki akses penuh terhadap manajemen pengguna, mencakup operasi CRUD akun mahasiswa dan dosen serta impor massal melalui berkas CSV. Pengelolaan data akademik meliputi kelas, mata kuliah, jadwal, tahun akademik, jam ke, dan ruangan. Fitur monitoring terpusat memungkinkan pemantauan seluruh absensi dan perizinan lintas kelas. Konfigurasi global sistem seperti radius GPS, batas waktu pengajuan izin, dan parameter lainnya dapat diatur melalui panel administrasi.

---

## Tech Stack

| Layer | Teknologi |
|---|---|
| **Frontend** | HTML5, CSS3, JavaScript (Vanilla) |
| **Backend** | PHP 8.2 |
| **Database** | MariaDB 10.11 |
| **Server** | Apache (dengan konfigurasi `.htaccess`) |
| **Autentikasi** | Google SSO (Single Sign-On) + Token Session |
| **Geolokasi** | Browser Geolocation API + Formula Haversine |
| **QR Code** | Token JSON terenkripsi, sesi-bound (base64 + timestamp) |

---

## Struktur Proyek

```
jejak-kampus/
├── assets/                     # Aset statis (CSS, JS, gambar, ikon)
│   ├── css/
│   ├── js/
│   └── img/
├── auth/                       # Modul autentikasi
│   ├── login.php               # Halaman login
│   ├── google_callback.php     # Handler callback Google SSO
│   └── logout.php              # Proses logout dan hapus sesi
├── components/                 # Komponen antarmuka yang dapat digunakan ulang
│   ├── navbar.php
│   ├── sidebar.php
│   └── footer.php
├── dashboard/                  # Halaman utama per peran pengguna
│   ├── admin/                  # Modul Admin
│   │   ├── index.php           # Dashboard utama admin
│   │   ├── user.php            # Manajemen pengguna (CRUD/AJAX)
│   │   ├── mahasiswa.php       # Manajemen data mahasiswa
│   │   ├── dosen.php           # Manajemen data dosen
│   │   ├── kelas.php           # Manajemen kelas
│   │   ├── mata_kuliah.php     # Manajemen mata kuliah
│   │   ├── jadwal.php          # Manajemen jadwal perkuliahan
│   │   ├── ruangan.php         # Manajemen ruangan
│   │   ├── tahun_akademik.php  # Manajemen tahun akademik
│   │   ├── absensi.php         # Monitoring absensi lintas kelas
│   │   ├── izin.php            # Monitoring perizinan
│   │   ├── settings.php        # Konfigurasi global sistem
│   │   └── log.php             # Log aktivitas sistem
│   ├── dosen/                  # Modul Dosen
│   │   ├── index.php           # Dashboard dosen
│   │   ├── jadwal.php          # Jadwal mengajar dosen
│   │   ├── sesi.php            # Manajemen sesi absensi
│   │   ├── absensi.php         # Rekap dan edit manual absensi
│   │   ├── izin.php            # Verifikasi perizinan mahasiswa
│   │   └── ai_assistant.php    # Jejak AI (fitur beta)
│   └── mahasiswa/              # Modul Mahasiswa
│       ├── index.php           # Dashboard mahasiswa
│       ├── scan.php            # Halaman scan QR Code
│       ├── riwayat.php         # Riwayat kehadiran
│       ├── izin.php            # Pengajuan izin
│       └── prestasi_dark.php   # Halaman gamifikasi dan lencana
├── uploads/                    # Direktori unggahan berkas izin
├── .htaccess                   # Konfigurasi Apache (routing, keamanan)
├── config.php                  # Konfigurasi koneksi database dan konstanta
├── index.php                   # Entry point aplikasi (redirect ke login)
├── xfag9686_jejak_kampus.sql   # Dump database lengkap (struktur + data)
└── README.md
```

---

## Skema Database

Database sistem terdiri atas **13 tabel utama**, **2 stored procedure**, dan sejumlah **trigger** otomatis yang saling terhubung untuk menjamin konsistensi data.

### Diagram Relasi Tabel

```
users ──┬── mahasiswa ──── kelas ──── tahun_akademik
        └── dosen ─────────────────────────┐
                                           │
mata_kuliah ◄── jadwal ──────────────────► ┘
    │              │
    └── jam_ke     └── sesi_absensi ──── absensi
    └── ruangan         │
                        └── izin ──── approval

settings · session · log_aktivitas
```

### Deskripsi Tabel

#### `users`
Tabel pusat untuk seluruh akun pengguna sistem. Setiap pengguna memiliki peran (`role`) yang menentukan hak akses ke modul yang tersedia.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT | Primary key |
| `nama` | VARCHAR(100) | Nama lengkap pengguna |
| `email` | VARCHAR(100) | Alamat email (digunakan untuk login Google SSO) |
| `password` | VARCHAR(255) | Hash kata sandi (untuk login manual) |
| `role` | ENUM | Peran: `admin`, `dosen`, atau `mahasiswa` |

---

#### `mahasiswa`
Menyimpan data profil mahasiswa yang berelasi dengan tabel `users` dan `kelas`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT | Primary key |
| `user_id` | INT | Foreign key ke tabel `users` |
| `nim` | VARCHAR(20) | Nomor Induk Mahasiswa |
| `nama` | VARCHAR(100) | Nama lengkap mahasiswa |
| `tanggal_lahir` | DATE | Tanggal lahir |
| `jenis_kelamin` | ENUM | `L` (Laki-laki) atau `P` (Perempuan) |
| `kelas_id` | INT | Foreign key ke tabel `kelas` |
| `status` | ENUM | Status akademik: `aktif`, `cuti`, `lulus`, atau `dropout` |

Tabel ini memiliki tiga trigger otomatis (`tr_mahasiswa_insert`, `tr_mahasiswa_update`, `tr_mahasiswa_delete`) yang mencatat setiap perubahan data ke tabel `log_aktivitas`.

---

#### `dosen`
Menyimpan data profil dosen yang berelasi dengan tabel `users`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT | Primary key |
| `user_id` | INT | Foreign key ke tabel `users` |
| `nidn` | VARCHAR(20) | Nomor Induk Dosen Nasional |
| `nama` | VARCHAR(100) | Nama lengkap dosen beserta gelar |
| `jenis_kelamin` | ENUM | `L` atau `P` |
| `status` | ENUM | Status kepegawaian: `aktif`, `nonaktif`, `cuti`, atau `pensiun` |

Tabel ini dilengkapi tiga trigger audit (`tr_dosen_insert`, `tr_dosen_update`, `tr_dosen_delete`).

---

#### `kelas`
Menyimpan data kelas yang menjadi wadah pengelompokan mahasiswa per angkatan.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT | Primary key |
| `nama_kelas` | VARCHAR(50) | Nama kelas (contoh: `PTI 2024C`) |
| `jurusan` | VARCHAR(100) | Nama program studi |
| `angkatan` | YEAR | Tahun angkatan |
| `tahun_akademik_id` | INT | Foreign key ke tabel `tahun_akademik` |

---

#### `mata_kuliah`
Menyimpan data master mata kuliah beserta jumlah SKS.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT | Primary key |
| `kode_mk` | VARCHAR(20) | Kode singkatan mata kuliah |
| `nama_mk` | VARCHAR(100) | Nama lengkap mata kuliah |
| `sks` | INT | Jumlah Satuan Kredit Semester |

---

#### `jam_ke`
Tabel referensi slot waktu perkuliahan. Sistem mendefinisikan 12 slot jam mulai pukul 07.00 hingga 18.00, dengan jeda istirahat antara jam ke-6 dan jam ke-7.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT | Primary key |
| `jam_ke` | INT | Urutan jam (1 sampai 12) |
| `jam_mulai` | TIME | Waktu mulai slot |
| `jam_selesai` | TIME | Waktu selesai slot |
| `keterangan` | VARCHAR(50) | Label deskriptif slot waktu |

---

#### `ruangan`
Menyimpan data ruangan perkuliahan beserta kapasitas, jenis, dan lokasinya.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT | Primary key |
| `kode_ruangan` | VARCHAR(20) | Kode ruangan (contoh: `A10.01.18`) |
| `nama_ruangan` | VARCHAR(100) | Nama tampil ruangan |
| `kapasitas` | INT | Kapasitas maksimal penghuni |
| `jenis` | ENUM | Jenis ruangan: `kelas`, `lab`, `aula`, atau `studio` |
| `lokasi` | VARCHAR(100) | Deskripsi lokasi gedung dan lantai |
| `status` | ENUM | `aktif` atau `nonaktif` |

---

#### `tahun_akademik`
Menyimpan data periode akademik beserta status aktif/nonaktif.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT | Primary key |
| `tahun` | VARCHAR(9) | Format: `2025/2026` |
| `semester` | ENUM | `Ganjil` atau `Genap` |
| `status` | ENUM | `aktif` atau `nonaktif` |
| `tanggal_mulai` | DATE | Tanggal awal semester |
| `tanggal_selesai` | DATE | Tanggal akhir semester |

---

#### `jadwal`
Menyimpan jadwal perkuliahan yang menghubungkan kelas, mata kuliah, dosen, ruangan, dan slot waktu pada suatu tahun akademik.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT | Primary key |
| `kelas_id` | INT | Foreign key ke `kelas` |
| `mata_kuliah_id` | INT | Foreign key ke `mata_kuliah` |
| `dosen_id` | INT | Foreign key ke `dosen` |
| `jam_ke_id` | INT | Foreign key ke `jam_ke` (jam mulai) |
| `ruangan_id` | INT | Foreign key ke `ruangan` |
| `hari` | ENUM | Hari perkuliahan (Senin sampai Sabtu) |
| `tahun_akademik_id` | INT | Foreign key ke `tahun_akademik` |

Tabel jadwal didukung oleh **dua stored procedure** untuk mendeteksi bentrokan: `sp_cek_bentrokan_jadwal` (pengecekan per slot tunggal) dan `sp_cek_bentrokan_rentang` (pengecekan rentang slot berdasarkan jumlah SKS), yang memeriksa tiga jenis konflik secara bersamaan: bentrokan ruangan, dosen, dan kelas.

---

#### `sesi_absensi`
Merepresentasikan satu sesi absensi aktif yang dibuka oleh dosen untuk suatu jadwal pada tanggal tertentu. Tabel ini menyimpan token QR Code terenkripsi dan koordinat GPS ruang kelas.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT | Primary key |
| `jadwal_id` | INT | Foreign key ke `jadwal` |
| `tanggal` | DATE | Tanggal pelaksanaan sesi |
| `pertemuan_ke` | INT | Nomor urut pertemuan |
| `qr_code` | TEXT | Token JSON terenkripsi (sesi_id + token base64 + timestamp) |
| `latitude` | DECIMAL(10,8) | Koordinat lintang lokasi kelas |
| `longitude` | DECIMAL(11,8) | Koordinat bujur lokasi kelas |
| `radius` | INT | Radius validasi GPS dalam meter |
| `status` | ENUM | `aktif` (sesi berjalan) atau `selesai` (sesi ditutup) |
| `tahun_akademik_id` | INT | Foreign key ke `tahun_akademik` |

Tabel ini memiliki dua trigger penting:
- `tr_sesi_after_insert`: Secara otomatis mengaitkan pengajuan izin yang sudah ada (berdasarkan `jadwal_id` dan `pertemuan_ke`) ke sesi yang baru dibuka.
- `tr_sesi_before_update`: Dipicu saat status sesi berubah dari `aktif` ke `selesai`. Trigger ini melakukan tiga operasi sekaligus: auto-approve seluruh izin pending pada sesi tersebut, mencatat entri ke tabel `approval`, dan mencatat status `alpha` bagi mahasiswa yang tidak hadir dan tidak mengajukan izin yang disetujui.

---

#### `absensi`
Mencatat setiap rekam kehadiran mahasiswa beserta koordinat GPS saat absensi dilakukan.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT | Primary key |
| `sesi_id` | INT | Foreign key ke `sesi_absensi` |
| `mahasiswa_id` | INT | Foreign key ke `mahasiswa` |
| `waktu_absen` | TIMESTAMP | Waktu absensi dicatat |
| `latitude` | DECIMAL(10,8) | Koordinat GPS saat absensi |
| `longitude` | DECIMAL(11,8) | Koordinat GPS saat absensi |
| `status` | ENUM | Status kehadiran: `hadir`, `telat`, atau `alpha` |
| `keterangan` | VARCHAR(255) | Catatan tambahan jika diperlukan |

---

#### `izin`
Menyimpan pengajuan perizinan mahasiswa, baik sakit maupun izin, yang dapat dilampiri berkas pendukung.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT | Primary key |
| `mahasiswa_id` | INT | Foreign key ke `mahasiswa` |
| `jadwal_id` | INT | Foreign key ke `jadwal` (nullable, diisi sebelum sesi ada) |
| `pertemuan_ke` | INT | Nomor pertemuan yang diizinkan |
| `tanggal_izin` | DATE | Tanggal perkuliahan yang diizinkan |
| `sesi_id` | INT | Foreign key ke `sesi_absensi` (diisi setelah sesi dibuka) |
| `jenis` | ENUM | `sakit` atau `izin` |
| `file_surat` | VARCHAR(255) | Nama berkas surat yang diunggah |
| `keterangan` | TEXT | Keterangan tertulis mahasiswa |
| `status` | ENUM | `pending`, `disetujui`, atau `ditolak` |

Tabel ini memiliki trigger `tr_izin_insert` dan `tr_izin_update` untuk audit otomatis ke `log_aktivitas`.

---

#### `approval`
Mencatat rekam jejak keputusan dosen atas setiap pengajuan izin mahasiswa.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT | Primary key |
| `izin_id` | INT | Foreign key ke `izin` |
| `dosen_id` | INT | Foreign key ke `dosen` |
| `status` | ENUM | `disetujui` atau `ditolak` |
| `catatan` | TEXT | Catatan atau alasan keputusan dosen |
| `approved_at` | TIMESTAMP | Waktu keputusan dibuat |

---

#### `session`
Menyimpan token sesi autentikasi pengguna yang aktif.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT | Primary key |
| `email` | VARCHAR(50) | Alamat email pengguna |
| `token` | VARCHAR(255) | Token sesi dalam bentuk hash SHA-256 |

---

#### `settings`
Menyimpan konfigurasi global sistem yang dapat diubah melalui panel administrasi.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT | Primary key |
| `app_name` | VARCHAR(100) | Nama aplikasi |
| `latitude` | DECIMAL(10,8) | Koordinat GPS default kampus |
| `longitude` | DECIMAL(11,8) | Koordinat GPS default kampus |
| `radius_absensi` | INT | Radius validasi GPS dalam meter (default: 50m) |
| `izin_max_hours_before` | INT | Batas waktu pengajuan izin sebelum perkuliahan (jam) |
| `izin_max_days_after` | INT | Batas waktu pengajuan izin setelah perkuliahan (hari) |
| `izin_auto_approve_on_sesi_close` | TINYINT | Aktifkan auto-approve izin saat sesi ditutup |
| `min_kehadiran_persen` | FLOAT | Persentase minimum kehadiran yang diwajibkan |
| `app_version` | VARCHAR(20) | Versi aplikasi yang berjalan |

---

#### `log_aktivitas`
Tabel audit trail yang mencatat seluruh operasi data penting secara otomatis melalui trigger database.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT | Primary key |
| `aksi` | VARCHAR(50) | Jenis operasi: `Tambah`, `Edit`, `Hapus`, `Update Status` |
| `tabel` | VARCHAR(50) | Nama tabel yang terpengaruh |
| `data_id` | INT | ID record yang dioperasikan |
| `deskripsi` | TEXT | Deskripsi lengkap perubahan yang terjadi |
| `pelaku` | VARCHAR(100) | Identitas pelaku operasi (default: `Admin`) |
| `waktu` | TIMESTAMP | Waktu operasi dilakukan |

---

### Stored Procedure

**`sp_cek_bentrokan_jadwal`**
Memeriksa kemungkinan bentrokan jadwal pada slot jam tertentu berdasarkan tiga dimensi: ruangan, dosen, dan kelas. Mengembalikan daftar jadwal yang berkonflik beserta informasinya.

**`sp_cek_bentrokan_rentang`**
Memeriksa bentrokan untuk jadwal yang mencakup lebih dari satu slot jam (sesuai jumlah SKS mata kuliah). Prosedur ini juga memvalidasi apakah jadwal melintasi jam istirahat (antara jam ke-6 dan jam ke-7) atau melebihi slot jam ke-12.

---

## Sistem Gamifikasi

Mahasiswa memperoleh lencana secara otomatis berdasarkan pencapaian kumulatif kehadiran sepanjang semester.

| Lencana | Syarat Pencapaian |
|---|---|
| ⚔️ Warrior | 1 kali hadir |
| 🏅 Elite | 10 kali hadir |
| 🥈 Master | 25 kali hadir |
| 🥇 Grandmaster | 50 kali hadir |
| 💎 Epic | 75 kali hadir |
| 🌟 Legend | 100 kali hadir |
| 🔮 Mythic | 120 kali hadir |
| 🏆 Honor | 130 kali hadir |
| 👑 Glory | 140 kali hadir |
| 🔱 Immortal | 0 kali alpha selama satu semester penuh |
| 🚨 Enemy Missing | 3 kali izin/sakit yang disertai surat resmi |

---

## Desain Antarmuka

Prototipe antarmuka sistem tersedia secara publik melalui Figma:

- [Antarmuka Admin dan Dosen](https://www.figma.com/design/shdzlAT3F9KFQS1fDqVA7T/Projek-Pemweb?node-id=141-181&p=f)
- [Antarmuka Mahasiswa](https://www.figma.com/design/shdzlAT3F9KFQS1fDqVA7T/Projek-Pemweb?node-id=61-1538&p=f)

### Pratinjau Tampilan

<!-- Ganti URL berikut dengan tautan screenshot aktual -->

| Dashboard Mahasiswa | Sesi Absensi Dosen |
|:---:|:---:|
| ![Dashboard Mahasiswa](assets/img/screenshot/dashboard-mahasiswa.png) | ![Sesi Dosen](assets/img/screenshot/sesi-dosen.png) |

| Panel Admin | Halaman Gamifikasi |
|:---:|:---:|
| ![Panel Admin](assets/img/screenshot/panel-admin.png) | ![Gamifikasi](assets/img/screenshot/gamifikasi.png) |

---

## Instalasi

### Prasyarat

Sebelum melakukan instalasi, pastikan lingkungan server memenuhi spesifikasi berikut:

- PHP versi 8.2 atau lebih tinggi
- MariaDB versi 10.11 atau lebih tinggi
- Apache Web Server dengan modul `mod_rewrite` aktif
- Composer (untuk manajemen dependensi)
- Akun Google Cloud Console dengan Google OAuth 2.0 yang telah dikonfigurasi

### Langkah Instalasi

**1. Clone repositori**

```bash
git clone https://github.com/fazrin-mauza/jejak-kampus.git
cd jejak-kampus
```

**2. Konfigurasi koneksi database**

Buka berkas `config.php` dan sesuaikan parameter berikut:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'jejak_kampus');
define('DB_USER', 'root');
define('DB_PASS', 'kata_sandi_anda');
```

**3. Import skema database**

```bash
mysql -u root -p jejak_kampus < xfag9686_jejak_kampus.sql
```

**4. Konfigurasi Google SSO**

Daftarkan aplikasi pada [Google Cloud Console](https://console.cloud.google.com) dan tambahkan kredensial OAuth 2.0 ke dalam `config.php`:

```php
define('GOOGLE_CLIENT_ID',     'your_client_id');
define('GOOGLE_CLIENT_SECRET', 'your_client_secret');
define('GOOGLE_REDIRECT_URI',  'http://localhost/auth/google_callback.php');
```

**5. Konfigurasi Apache**

Pastikan berkas `.htaccess` telah aktif dan modul `mod_rewrite` pada Apache sudah diaktifkan:

```bash
a2enmod rewrite
systemctl restart apache2
```

**6. Atur izin direktori unggahan**

```bash
chmod 755 uploads/
```

**7. Akses aplikasi**

Buka `http://localhost/jejak-kampus` pada browser. Login menggunakan akun Google yang terdaftar dalam sistem.

---

## Dokumentasi Teknis

Seluruh artefak perencanaan dan analisis sistem tersedia melalui tautan berikut:

| Dokumen | Tautan |
|---|---|
| Pusat Dokumentasi | [Google Drive](https://drive.google.com/drive/folders/1e_Xyq430yBnvfDz63mZ4zCR28SsCjDQp) |
| Use Case Diagram | [Lihat Diagram](https://drive.google.com/file/d/18RWSq5HNEPq-aEuIa3Tggf_LfrcJc9_S/view) |
| Class Diagram | [Lihat Diagram](https://drive.google.com/file/d/17pSJPrPDi53RCZ4-p7kbGi1GsbKpmyyt/view) |
| Sequence Diagrams (5 SD) | [Google Drive](https://drive.google.com/drive/folders/1e_Xyq430yBnvfDz63mZ4zCR28SsCjDQp) |
| Activity Diagrams (5 AD) | [Google Drive](https://drive.google.com/drive/folders/1e_Xyq430yBnvfDz63mZ4zCR28SsCjDQp) |
| Entity Relationship Diagram | [Lihat Diagram](https://drive.google.com/file/d/1STK0mVwuPUCDKhFnObRoWkTf6Lma8uuB/view) |
| Data Flow Diagrams (3 DFD) | [Google Drive](https://drive.google.com/drive/folders/1e_Xyq430yBnvfDz63mZ4zCR28SsCjDQp) |
| Flowchart Sistem | [Lihat Diagram](https://drive.google.com/file/d/17zNpCsxditd0MzMiffxCtF05zCGQQrLt/view) |

---

## Tim Pengembang

**Tim Pandawa 5 - Universitas Negeri Surabaya, 2026**

| Nama | Peran |
|---|---|
| Iqbal Amri Sya'bana | UI/UX Designer |
| Fata Favian Cannavaro | Frontend Developer |
| Farrell Ahmed Dimitrie Dhiaul Aulia | Analis Sistem |
| Mikhael Yuli Ananda Elvan Permana | Manajer Proyek |
| Fazrin Mauza Dwi Zuhudi | Backend Developer |

**Pembimbing / Klien:** Ir. Rizky Basatha, S.Pd., M.MT.

---

## Lisensi

Proyek ini dikembangkan sebagai bagian dari tugas akhir mata kuliah **Analisis Perancangan Sistem (APS)**, **Rekayasa Perangkat Lunak (RPL)**, dan **Pemrograman Web (Pemweb)** di Universitas Negeri Surabaya. Kode sumber didistribusikan di bawah lisensi MIT. Lihat berkas [LICENSE](LICENSE) untuk ketentuan lengkap.

---

<div align="center">

© 2026 Jejak Kampus - Tim Pandawa 5 · UNESA

*Dibuat dengan dedikasi untuk sistem akademik yang lebih baik*

</div>
