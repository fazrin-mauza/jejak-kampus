<div align="center">

<img src="https://img.shields.io/badge/version-v1.0-orange?style=for-the-badge" alt="Version"/>
<img src="https://img.shields.io/badge/status-In%20Development-blue?style=for-the-badge" alt="Status"/>
<img src="https://img.shields.io/badge/license-MIT-green?style=for-the-badge" alt="License"/>

# 📍 Jejak Kampus
### Sistem Absensi Berbasis Geolokasi & QR Code

**Universitas Negeri Surabaya (UNESA)**  
*Dikembangkan oleh Tim Pandawa 5 · 2026*

---

[Fitur](#-fitur-utama) · [Tech Stack](#-tech-stack) · [Arsitektur](#-arsitektur-sistem) · [Screenshot](#-desain-antarmuka) · [Instalasi](#-instalasi) · [Tim](#-tim-pengembang)

</div>

---

## 🎯 Tentang Proyek

**Jejak Kampus** adalah sistem absensi berbasis web yang dirancang untuk menggantikan sistem kehadiran konvensional di UNESA. Dengan menggabungkan dua lapisan verifikasi — **QR Code dinamis** dan **validasi GPS (Haversine)** — sistem ini memastikan mahasiswa benar-benar hadir secara fisik di lokasi kelas.

> *"Bukan sekadar absen. Jejak Kampus membuktikan kehadiran yang sesungguhnya."*

### Masalah yang Diselesaikan

| ❌ Masalah Lama | ✅ Solusi Jejak Kampus |
|---|---|
| QR Code bisa disebarkan ke teman yang tidak hadir | QR Code unik per sesi + validasi GPS radius 10–20 m |
| Tidak ada verifikasi lokasi fisik | Geolokasi real-time menggunakan formula Haversine |
| Pencatatan kehadiran manual oleh dosen | Pemantauan real-time & rekap otomatis |
| Perizinan harus melalui perantara PJ | Upload berkas langsung ke sistem, langsung ke dosen |

---

## ✨ Fitur Utama

### 👨‍🎓 Mahasiswa
- **Scan QR Code** via browser — tanpa install aplikasi tambahan
- **Validasi GPS** otomatis (radius 10–20 meter dari kelas)
- **Dashboard Kehadiran** — persentase per mata kuliah secara real-time
- **Pengajuan Izin Digital** — upload surat langsung ke dosen
- **Gamifikasi** — sistem lencana & ranking kehadiran (Warrior → Immortal)

### 👨‍🏫 Dosen
- **Manajemen Sesi Absensi** — buka/tutup sesi, QR Code auto-generate
- **Pemantauan Real-time** — daftar hadir live tanpa refresh
- **Rekap & Edit Manual** — override status kehadiran dengan log perubahan
- **Ekspor Laporan** — format Excel (.csv) dan PDF
- **Verifikasi Perizinan** — setujui/tolak berkas langsung di sistem
- **Jejak AI (Beta)** — asisten AI untuk update status via perintah teks

### ⚙️ Admin
- **Manajemen Pengguna** — CRUD akun mahasiswa & dosen, import massal via CSV
- **Manajemen Akademik** — kelas, mata kuliah, jadwal, tahun akademik, ruangan
- **Monitoring Terpusat** — pantau seluruh absensi & perizinan lintas kelas
- **Konfigurasi Global** — atur radius GPS, batas pengajuan izin, dan parameter sistem
- **Rekap & Ekspor** — per mahasiswa, per MK, per kelas, atau per semester

---

## 🛠 Tech Stack

<div align="center">

| Layer | Teknologi |
|---|---|
| **Frontend** | HTML5, CSS3, JavaScript |
| **Backend** | PHP 8.2 |
| **Database** | MariaDB 10.11 |
| **Server** | Apache |
| **Auth** | Google SSO (Single Sign-On) |
| **Geolokasi** | Browser Geolocation API + Formula Haversine |
| **QR Code** | Token JSON terenkripsi, sesi-bound |

</div>

---

## 🏗 Arsitektur Sistem

```
┌─────────────────────────────────────────────────┐
│                 JEJAK KAMPUS                     │
├──────────┬──────────────┬───────────────────────┤
│  ADMIN   │    DOSEN     │      MAHASISWA         │
│          │              │                        │
│ Kelola   │ Buka/Tutup   │  Scan QR + GPS         │
│ Data     │ Sesi Absensi │  Dashboard Kehadiran   │
│ Master   │ Pantau RT    │  Pengajuan Izin        │
│          │ Verif. Izin  │  Gamifikasi            │
└────┬─────┴──────┬───────┴────────────┬───────────┘
     │            │                    │
     └────────────┴────────────────────┘
                  │
     ┌────────────▼────────────────────┐
     │      BACKEND SERVICE            │
     │  • Auth & Role Validation       │
     │  • QR Token Verification        │
     │  • GPS Haversine Validation     │
     │  • Notifikasi & Gamifikasi      │
     └────────────┬────────────────────┘
                  │
     ┌────────────▼────────────────────┐
     │         DATABASE (MariaDB)      │
     │  users · mahasiswa · dosen      │
     │  jadwal · sesi_absensi          │
     │  absensi · izin · approval      │
     │  settings · log_aktivitas       │
     └─────────────────────────────────┘
```

---

## 🗃 Struktur Database (Ringkasan)

Sistem menggunakan **13 tabel utama** yang saling terhubung:

```
users ──┬── mahasiswa ── kelas ── tahun_akademik
        └── dosen ──────────────────────┐
                                        ▼
jadwal ◄── mata_kuliah        sesi_absensi ── absensi
  │                                │
  └── ruangan, jam_ke              └── izin ── approval
  
settings · session · log_aktivitas
```

---

## 🎮 Sistem Gamifikasi

Mahasiswa mendapatkan lencana berdasarkan pencapaian kehadiran:

| Lencana | Syarat |
|---|---|
| ⚔️ **Warrior** | 1× hadir |
| 🏅 **Elite** | 10× hadir |
| 🥈 **Master** | 25× hadir |
| 🥇 **Grandmaster** | 50× hadir |
| 💎 **Epic** | 75× hadir |
| 🌟 **Legend** | 100× hadir |
| 🔮 **Mythic** | 120× hadir |
| 🏆 **Honor** | 130× hadir |
| 👑 **Glory** | 140× hadir |
| 🔱 **Immortal** | 0× alpha sepanjang semester |
| 🚨 **Enemy Missing** | 3× izin/sakit dengan surat resmi |

---

## 🖥 Desain Antarmuka

Desain UI tersedia di Figma:

- 🔗 [Antarmuka Admin & Dosen](https://www.figma.com/design/shdzlAT3F9KFQS1fDqVA7T/Projek-Pemweb?node-id=141-181&p=f)
- 🔗 [Antarmuka Mahasiswa](https://www.figma.com/design/shdzlAT3F9KFQS1fDqVA7T/Projek-Pemweb?node-id=61-1538&p=f)

---

## 🚀 Instalasi

### Prasyarat
- PHP >= 8.2
- MariaDB >= 10.11
- Apache / Nginx
- Composer

### Langkah Setup

```bash
# 1. Clone repositori
git clone https://github.com/[username]/jejak-kampus.git
cd jejak-kampus

# 2. Install dependensi
composer install

# 3. Salin file konfigurasi
cp .env.example .env

# 4. Konfigurasi database di .env
DB_HOST=localhost
DB_DATABASE=jejak_kampus
DB_USERNAME=root
DB_PASSWORD=your_password

# 5. Import skema database
mysql -u root -p jejak_kampus < database/jejak_kampus.sql

# 6. Konfigurasi Google SSO
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URI=http://localhost/auth/google/callback

# 7. Jalankan server
php -S localhost:8000 -t public/
```

---

## 📁 Dokumentasi Teknis

| Artefak | Tautan |
|---|---|
| 📋 Pusat Dokumentasi | [Google Drive](https://drive.google.com/drive/folders/1e_Xyq430yBnvfDz63mZ4zCR28SsCjDQp) |
| 📊 Use Case Diagram | [Lihat](https://drive.google.com/file/d/18RWSq5HNEPq-aEuIa3Tggf_LfrcJc9_S/view) |
| 🗂 Class Diagram | [Lihat](https://drive.google.com/file/d/17pSJPrPDi53RCZ4-p7kbGi1GsbKpmyyt/view) |
| 🔁 Sequence Diagrams (5 SD) | [Google Drive](https://drive.google.com/drive/folders/1e_Xyq430yBnvfDz63mZ4zCR28SsCjDQp) |
| 📌 Activity Diagrams (5 AD) | [Google Drive](https://drive.google.com/drive/folders/1e_Xyq430yBnvfDz63mZ4zCR28SsCjDQp) |
| 🗄 Entity Relation Diagram | [Lihat](https://drive.google.com/file/d/1STK0mVwuPUCDKhFnObRoWkTf6Lma8uuB/view) |
| 🌊 Data Flow Diagrams (3 DFD) | [Google Drive](https://drive.google.com/drive/folders/1e_Xyq430yBnvfDz63mZ4zCR28SsCjDQp) |
| 🔄 Flowchart Sistem | [Lihat](https://drive.google.com/file/d/17zNpCsxditd0MzMiffxCtF05zCGQQrLt/view) |

---

## 👥 Tim Pengembang

**Tim Pandawa 5 — Universitas Negeri Surabaya**

| Nama | Peran |
|---|---|
| **Iqbal Amri Sya'bana** | UI/UX Designer |
| **Fata Favian Cannavaro** | Frontend Developer |
| **Farrell Ahmed Dimitrie Dhiaul Aulia** | Analis Sistem |
| **Mikhael Yuli Ananda Elvan Permana** | Manajer Proyek |
| **Fazrin Mauza Dwi Zuhudi** | Backend Developer |

**Pembimbing / Klien:** Ir. Rizky Basatha, S.Pd., M.MT.

---

## 📄 Lisensi

Proyek ini dikembangkan sebagai bagian dari tugas mata kuliah **Analisis Perancangan Sistem (APS)**, **Rekayasa Perangkat Lunak (RPL)**, dan **Pemrograman Web (Pemweb)** di Universitas Negeri Surabaya.

---

<div align="center">

© 2026 Jejak Kampus — Tim Pandawa 5 · UNESA

*Dibuat dengan ❤️ untuk sistem akademik yang lebih baik*

</div>
