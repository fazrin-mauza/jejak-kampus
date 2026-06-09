<?php
require_once 'header.php';
require_once 'navigasi.php';

// ============================================================
// QUERY DATA DINAMIS UNTUK DASHBOARD DOSEN
// ============================================================
// Ambil dosen_id dari user yang login (pastikan user role = dosen)
$dosen_id = 0;
if (isset($user['id']) && $user['role'] == 'dosen') {
    $query_dosen = "SELECT id FROM dosen WHERE user_id = " . (int)$user['id'];
    $result_dosen = $conn->query($query_dosen);
    if ($result_dosen && $row = $result_dosen->fetch_assoc()) {
        $dosen_id = $row['id'];
        // Simpan ke session untuk penggunaan di halaman lain
        $_SESSION['dosen_id'] = $dosen_id;
    }
} elseif (isset($_SESSION['dosen_id'])) {
    // Fallback jika sudah tersimpan di session
    $dosen_id = (int)$_SESSION['dosen_id'];
}

// Jika masih 0, coba cari berdasarkan email (opsional)
if ($dosen_id == 0 && isset($user['email'])) {
    $email = $conn->real_escape_string($user['email']);
    $query_email = "SELECT d.id FROM dosen d JOIN users u ON d.user_id = u.id WHERE u.email = '$email'";
    $res_email = $conn->query($query_email);
    if ($res_email && $row = $res_email->fetch_assoc()) {
        $dosen_id = $row['id'];
        $_SESSION['dosen_id'] = $dosen_id;
    }
}

// Jika tetap 0, tampilkan pesan error (opsional)
if ($dosen_id == 0) {
    die("Error: Data dosen tidak ditemukan untuk akun ini. Hubungi administrator.");
}

// 1. Tahun akademik aktif
$ta = $conn->query("SELECT id, tahun, semester FROM tahun_akademik WHERE status = 'aktif' LIMIT 1")->fetch_assoc();
$ta_id = $ta['id'] ?? 0;
$ta_label = ($ta['tahun'] ?? '') . ' ' . ($ta['semester'] ?? '');

// 2. Hari ini (Bahasa Indonesia)
$hariMap = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
$hariIni = $hariMap[date('l')];
$tglHariIni = date('j F Y');

// 3. Total kelas diajar
$qKelas = $conn->query("SELECT COUNT(DISTINCT kelas_id) as total FROM jadwal WHERE dosen_id = $dosen_id AND tahun_akademik_id = $ta_id");
$totalKelas = $qKelas->fetch_assoc()['total'] ?? 0;

// 4. Kelas hari ini
$qKelasHariIni = $conn->query("SELECT COUNT(DISTINCT kelas_id) as total FROM jadwal WHERE dosen_id = $dosen_id AND tahun_akademik_id = $ta_id AND hari = '$hariIni'");
$kelasHariIni = $qKelasHariIni->fetch_assoc()['total'] ?? 0;

// 5. Total SKS
$qSKS = $conn->query("SELECT SUM(mk.sks) as total FROM jadwal j JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id WHERE j.dosen_id = $dosen_id AND j.tahun_akademik_id = $ta_id");
$totalSKS = $qSKS->fetch_assoc()['total'] ?? 0;

// 6. Total mata kuliah
$qMK = $conn->query("SELECT COUNT(DISTINCT mata_kuliah_id) as total FROM jadwal WHERE dosen_id = $dosen_id AND tahun_akademik_id = $ta_id");
$totalMK = $qMK->fetch_assoc()['total'] ?? 0;

// 7. Total mahasiswa (lintas kelas yang diajar)
$qMhs = $conn->query("SELECT COUNT(DISTINCT m.id) as total FROM mahasiswa m JOIN kelas k ON m.kelas_id = k.id JOIN jadwal j ON k.id = j.kelas_id WHERE j.dosen_id = $dosen_id AND j.tahun_akademik_id = $ta_id AND m.status = 'aktif'");
$totalMhs = $qMhs->fetch_assoc()['total'] ?? 0;

// 8. Mahasiswa aktif
$qMhsAktif = $conn->query("SELECT COUNT(DISTINCT m.id) as total FROM mahasiswa m JOIN kelas k ON m.kelas_id = k.id JOIN jadwal j ON k.id = j.kelas_id WHERE j.dosen_id = $dosen_id AND j.tahun_akademik_id = $ta_id AND m.status = 'aktif'");
$mhsAktif = $qMhsAktif->fetch_assoc()['total'] ?? 0;

// 9. Rata-rata kehadiran
$sqlRerata = "SELECT 
    ROUND(AVG(
        (SELECT COUNT(*) FROM absensi a WHERE a.sesi_id = s.id AND a.status IN ('hadir','telat')) 
        * 100.0 / 
        NULLIF((SELECT COUNT(*) FROM mahasiswa m2 JOIN kelas k2 ON m2.kelas_id = k2.id JOIN jadwal j2 ON k2.id = j2.kelas_id JOIN sesi_absensi s2 ON j2.id = s2.jadwal_id WHERE s2.id = s.id AND m2.status = 'aktif'), 0)
    ), 1) as rata
FROM sesi_absensi s 
JOIN jadwal j ON s.jadwal_id = j.id 
WHERE j.dosen_id = $dosen_id AND s.status = 'selesai'";
$qRerata = $conn->query($sqlRerata);
$rerataHadir = $qRerata->fetch_assoc()['rata'] ?? 0;

// 10. Pending izin
$qPendingIzin = $conn->query("SELECT COUNT(*) as total FROM izin i JOIN jadwal j ON i.jadwal_id = j.id WHERE j.dosen_id = $dosen_id AND i.status = 'pending'");
$pendingIzin = $qPendingIzin->fetch_assoc()['total'] ?? 0;

// 11. Jadwal hari ini
$sqlJadwalHariIni = "SELECT 
    j.id, mk.nama_mk, mk.sks, k.nama_kelas, r.nama_ruangan, r.kode_ruangan,
    jk.jam_mulai, 
    (SELECT jam_selesai FROM jam_ke WHERE jam_ke = jk.jam_ke + mk.sks - 1) as jam_selesai,
    CASE WHEN EXISTS (SELECT 1 FROM sesi_absensi WHERE jadwal_id = j.id AND status = 'aktif') THEN 1 ELSE 0 END as sesi_aktif,
    (SELECT id FROM sesi_absensi WHERE jadwal_id = j.id AND status = 'aktif' LIMIT 1) as sesi_id
FROM jadwal j
JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
JOIN kelas k ON j.kelas_id = k.id
LEFT JOIN ruangan r ON j.ruangan_id = r.id
LEFT JOIN jam_ke jk ON j.jam_ke_id = jk.id
WHERE j.dosen_id = $dosen_id AND j.tahun_akademik_id = $ta_id AND j.hari = '$hariIni'
ORDER BY jk.jam_mulai";
$jadwalHariIni = $conn->query($sqlJadwalHariIni);

// 12. Notifikasi (izin pending + sesi selesai 3 hari terakhir)
$notif = [];
// Izin pending
$qNotifIzin = $conn->query("SELECT i.id, i.jenis, i.created_at, m.nama as nama_mhs, k.nama_kelas, mk.nama_mk, 'izin' as tipe FROM izin i JOIN mahasiswa m ON i.mahasiswa_id = m.id JOIN jadwal j ON i.jadwal_id = j.id JOIN kelas k ON j.kelas_id = k.id JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id WHERE j.dosen_id = $dosen_id AND i.status = 'pending' ORDER BY i.created_at DESC LIMIT 3");
while ($row = $qNotifIzin->fetch_assoc()) $notif[] = $row;

// Sesi selesai 3 hari terakhir
$qNotifSesi = $conn->query("SELECT s.id, s.tanggal, mk.nama_mk, k.nama_kelas, s.pertemuan_ke, (SELECT COUNT(*) FROM absensi WHERE sesi_id = s.id AND status IN ('hadir','telat')) as hadir, (SELECT COUNT(*) FROM mahasiswa m2 WHERE m2.kelas_id = j.kelas_id AND m2.status = 'aktif') as total_mhs, 'sesi' as tipe FROM sesi_absensi s JOIN jadwal j ON s.jadwal_id = j.id JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id JOIN kelas k ON j.kelas_id = k.id WHERE j.dosen_id = $dosen_id AND s.status = 'selesai' AND s.tanggal >= DATE_SUB(CURDATE(), INTERVAL 3 DAY) ORDER BY s.tanggal DESC LIMIT 3");
while ($row = $qNotifSesi->fetch_assoc()) $notif[] = $row;

// Sort notif by waktu
usort($notif, function($a, $b) {
    $ta = strtotime($a['created_at'] ?? $a['tanggal'] ?? 'now');
    $tb = strtotime($b['created_at'] ?? $b['tanggal'] ?? 'now');
    return $tb - $ta;
});
$notif = array_slice($notif, 0, 4);

// 13. Aktivitas sesi terbaru (5)
$sqlSesiTerbaru = "SELECT s.id, s.tanggal, s.pertemuan_ke, s.status, mk.nama_mk, k.nama_kelas,
    (SELECT COUNT(*) FROM absensi WHERE sesi_id = s.id AND status IN ('hadir','telat')) as hadir,
    (SELECT COUNT(*) FROM mahasiswa m2 WHERE m2.kelas_id = j.kelas_id AND m2.status = 'aktif') as total_mhs
FROM sesi_absensi s
JOIN jadwal j ON s.jadwal_id = j.id
JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
JOIN kelas k ON j.kelas_id = k.id
WHERE j.dosen_id = $dosen_id
ORDER BY s.tanggal DESC, s.id DESC
LIMIT 5";
$sesiTerbaru = $conn->query($sqlSesiTerbaru);
?>

<style>
/* ==================================================
   DASHBOARD DOSEN — WARNA PASTEL
================================================== */
/* Warna pastel untuk stat card */
.stat-card.ora { background: linear-gradient(135deg, #FFF5EB 0%, #FFE8D6 100%); border-left: 4px solid #F97316; }
.stat-card.blue { background: linear-gradient(135deg, #EBF5FF 0%, #D6EAFF 100%); border-left: 4px solid #3B82F6; }
.stat-card.green { background: linear-gradient(135deg, #ECFDF5 0%, #D8F3E8 100%); border-left: 4px solid #10B981; }
.stat-card.purple { background: linear-gradient(135deg, #F5F3FF 0%, #EDE9FE 100%); border-left: 4px solid #8B5CF6; }

.stat-card {
    transition: all 0.3s ease;
    background: #FFFFFF;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}
.stat-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
    margin-bottom: 12px;
}
.stat-icon svg {
    width: 24px;
    height: 24px;
    stroke: currentColor;
    stroke-width: 1.8;
    fill: none;
}
.stat-icon.ora { background: rgba(249,115,22,0.1); color: #F97316; }
.stat-icon.blue { background: rgba(59,130,246,0.1); color: #3B82F6; }
.stat-icon.green { background: rgba(16,185,129,0.1); color: #10B981; }
.stat-icon.purple { background: rgba(139,92,246,0.1); color: #8B5CF6; }

.stat-label { font-size: 13px; color: #64748B; margin-bottom: 6px; }
.stat-value { font-size: 32px; font-weight: 800; color: #1E293B; line-height: 1.2; }
.stat-sub { font-size: 11px; color: #94A3B8; margin-top: 6px; }
.stat-badge { display: inline-block; margin-top: 10px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-up { background: #D1FAE5; color: #059669; }
.badge-info { background: #DBEAFE; color: #1D4ED8; }

/* Quick Actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.quick-action {
    background: #FFFFFF;
    border-radius: 16px;
    padding: 16px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid #E2E8F0;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}
.quick-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    border-color: #CBD5E1;
}
.qa-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 12px;
    background: rgba(249,115,22,0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #F97316;
}
.qa-label { font-weight: 700; font-size: 14px; margin-bottom: 4px; color: #1E293B; }
.qa-sub { font-size: 11px; color: #64748B; }

/* Jadwal Hari Ini */
.schedule-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px 0;
    border-bottom: 1px solid #F1F5F9;
}
.schedule-time {
    min-width: 65px;
    font-weight: 600;
    font-size: 13px;
    color: #1E293B;
}
.schedule-body { flex: 1; }
.schedule-matkul { font-weight: 700; font-size: 14px; margin-bottom: 4px; }
.schedule-meta { font-size: 11px; color: #64748B; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.schedule-actions { display: flex; gap: 6px; align-items: center; }

/* Activity items */
.activity-item {
    display: flex;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #F1F5F9;
}
.activity-dot {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.activity-body { flex: 1; }
.activity-title { font-weight: 700; font-size: 13px; margin-bottom: 2px; }
.activity-meta { font-size: 11px; color: #64748B; }
.activity-time { font-size: 11px; color: #94A3B8; font-weight: 500; }

/* Responsive */
@media (max-width: 640px) {
    .quick-actions { grid-template-columns: 1fr; }
    .schedule-item { flex-wrap: wrap; }
}

/* ==================================================
   AKSI CEPAT - GRID MODERN
================================================== */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0 32px;
}

.quick-action {
    background: #FFFFFF;
    border-radius: 24px;
    padding: 20px 16px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid #F1F5F9;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
    position: relative;
    overflow: hidden;
}

/* Garis aksen atas saat hover */
.quick-action::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #F97316, #FFB347);
    opacity: 0;
    transition: opacity 0.3s;
}

.quick-action:hover {
    transform: translateY(-5px);
    border-color: #E2E8F0;
    box-shadow: 0 16px 24px -8px rgba(0, 0, 0, 0.1);
}

.quick-action:hover::before {
    opacity: 1;
}

/* Icon styling */
.qa-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 16px;
    background: #FFF5EB;
    border-radius: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #F97316;
    transition: all 0.3s;
}

.quick-action:hover .qa-icon {
    background: #F97316;
    color: white;
    transform: scale(1.02);
}

.qa-icon svg {
    width: 28px;
    height: 28px;
    stroke: currentColor;
    stroke-width: 1.8;
    fill: none;
}

/* Label & sub */
.qa-label {
    font-weight: 800;
    font-size: 15px;
    color: #1E293B;
    margin-bottom: 6px;
    letter-spacing: -0.2px;
}

.qa-sub {
    font-size: 12px;
    color: #64748B;
    line-height: 1.4;
}

/* Warna khusus untuk Approval Izin (pending) */
.quick-action[data-type="approval"] .qa-icon {
    background: #FEF2F2;
    color: #EF4444;
}

.quick-action[data-type="approval"]:hover .qa-icon {
    background: #EF4444;
    color: white;
}

/* Badge pending */
.pending-badge {
    display: inline-block;
    background: #EF4444;
    color: white;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    margin-left: 6px;
    vertical-align: middle;
}

/* Responsive untuk mobile */
@media (max-width: 640px) {
    .quick-actions {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .quick-action {
        display: flex;
        align-items: center;
        text-align: left;
        gap: 16px;
        padding: 16px;
    }
    
    .qa-icon {
        width: 48px;
        height: 48px;
        margin: 0;
        flex-shrink: 0;
    }
    
    .qa-icon svg {
        width: 24px;
        height: 24px;
    }
    
    .quick-action .qa-label,
    .quick-action .qa-sub {
        text-align: left;
    }
    
    /* Aksen samping untuk mobile */
    .quick-action::before {
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, #F97316, #FFB347);
    }
}

/* ==================================================
   AKSI CEPAT - GRID WARNA PASTEL
================================================== */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0 32px;
}

.quick-action {
    background: #FFFFFF;
    border-radius: 20px;
    padding: 20px 16px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.02);
}

/* Warna latar berbeda untuk setiap kartu (bisa menggunakan nth-child atau class) */
.quick-action:nth-child(1) {
    background: linear-gradient(135deg, #FFF5EB 0%, #FFE8D6 100%);
    border-left: 4px solid #F97316;
}
.quick-action:nth-child(2) {
    background: linear-gradient(135deg, #FEF2F2 0%, #FEE2E2 100%);
    border-left: 4px solid #EF4444;
}
.quick-action:nth-child(3) {
    background: linear-gradient(135deg, #EBF5FF 0%, #D6EAFF 100%);
    border-left: 4px solid #3B82F6;
}

/* Efek hover */
.quick-action:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.15);
}

/* Lingkaran ikon */
.qa-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto 16px;
    border-radius: 30px;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

/* Warna ikon sesuai kartu */
.quick-action:nth-child(1) .qa-icon { color: #F97316; }
.quick-action:nth-child(2) .qa-icon { color: #EF4444; }
.quick-action:nth-child(3) .qa-icon { color: #3B82F6; }

.quick-action:hover .qa-icon {
    background: white;
    transform: scale(1.05);
}

/* Teks */
.qa-label {
    font-weight: 800;
    font-size: 15px;
    margin-bottom: 6px;
    color: #1E293B;
}

.qa-sub {
    font-size: 12px;
    color: #475569;
    line-height: 1.4;
}

/* Badge pending untuk approval */
.pending-badge {
    display: inline-block;
    background: #EF4444;
    color: white;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    margin-left: 6px;
}

/* Responsif mobile */
@media (max-width: 640px) {
    .quick-actions {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    .quick-action {
        display: flex;
        align-items: center;
        text-align: left;
        gap: 16px;
        padding: 14px 16px;
    }
    .qa-icon {
        margin: 0;
        width: 48px;
        height: 48px;
        flex-shrink: 0;
    }
    .qa-icon svg {
        width: 24px;
        height: 24px;
    }
    .qa-label, .qa-sub {
        text-align: left;
    }
    /* Aksen samping untuk mobile */
    .quick-action::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: inherit;
        border-radius: 20px 0 0 20px;
    }
}
</style>

<!-- ═══════════════════════════════════════════════
     DASHBOARD DOSEN — WARNA PASTEL
═══════════════════════════════════════════════ -->
<div class="section active" id="sec-dashboard">
  <div class="page-header">
    <div>
      <div class="page-title">Selamat Datang, <?= htmlspecialchars($nama_dosen) ?></div>
      <div class="page-subtitle">Semester <?= htmlspecialchars($ta_label) ?> &vert; <?= $hariIni ?>, <?= $tglHariIni ?></div>
    </div>
    <div class="header-actions">
      <button class="btn btn-secondary btn-sm" onclick="location.reload()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg> Segarkan
      </button>
      <span class="pill pill-green">● Aktif Mengajar</span>
    </div>
  </div>

  <!-- ═══════ STATS GRID (PASTEL) ═══════ -->
  <div class="stats-grid">
    <div class="stat-card ora">
      <div class="stat-icon ora">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      </div>
      <div class="stat-label">Total Kelas Diajar</div>
      <div class="stat-value"><?= $totalKelas ?></div>
      <div class="stat-sub">Semester ini</div>
      <span class="stat-badge badge-info"><?= $kelasHariIni ?> hari ini</span>
    </div>

    <div class="stat-card blue">
      <div class="stat-icon blue">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      </div>
      <div class="stat-label">Total SKS</div>
      <div class="stat-value"><?= $totalSKS ?></div>
      <div class="stat-sub"><?= $totalMK ?> mata kuliah</div>
      <span class="stat-badge badge-info"><?= htmlspecialchars($ta_label) ?></span>
    </div>

    <div class="stat-card green">
      <div class="stat-icon green">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <div class="stat-label">Total Mahasiswa</div>
      <div class="stat-value"><?= $totalMhs ?></div>
      <div class="stat-sub">Lintas <?= $totalKelas ?> kelas</div>
      <span class="stat-badge badge-up">● <?= $mhsAktif ?> aktif</span>
    </div>

    <div class="stat-card purple">
      <div class="stat-icon purple">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      </div>
      <div class="stat-label">Rata-rata Kehadiran</div>
      <div class="stat-value"><?= $rerataHadir ?>%</div>
      <div class="stat-sub">Semester berjalan</div>
      <span class="stat-badge badge-up">▲ Sesi selesai</span>
    </div>
  </div>

  <!-- ═══════ QUICK ACTIONS ═══════ -->
  <div style="margin-bottom:8px;font-size:12px;font-weight:800;color:#64748B;text-transform:uppercase;letter-spacing:.05em">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="display:inline;margin-right:4px"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> Aksi Cepat
  </div>
  <div class="quick-actions">
    <div class="quick-action" onclick="window.location.href='sesi.php'">
      <div class="qa-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><rect x="7" y="7" width="3" height="3"/><rect x="14" y="7" width="3" height="3"/><rect x="7" y="14" width="3" height="3"/><rect x="14" y="14" width="3" height="3"/></svg>
      </div>
      <div class="qa-label">Mulai Sesi Absensi</div>
      <div class="qa-sub">Buat sesi & QR Code</div>
    </div>
    <div class="quick-action" onclick="window.location.href='izin.php'">
      <div class="qa-icon" style="background:rgba(239,68,68,.1);color:#ef4444">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      </div>
      <div class="qa-label">Approval Izin</div>
      <div class="qa-sub" style="color:#ef4444;font-weight:700"><?= $pendingIzin ?> pending</div>
    </div>
    <div class="quick-action" onclick="window.location.href='jadwal.php'">
      <div class="qa-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      </div>
      <div class="qa-label">Lihat Jadwal</div>
      <div class="qa-sub">Jadwal mengajar lengkap</div>
    </div>
  </div>

  <!-- ═══════ JADWAL HARI INI + NOTIFIKASI ═══════ -->
  <div class="grid-2" style="margin-bottom:18px">
    <!-- Jadwal Hari Ini -->
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
            Jadwal Hari Ini
          </div>
          <div class="card-sub"><?= $hariIni ?>, <?= $tglHariIni ?></div>
        </div>
        <button class="btn btn-secondary btn-sm" onclick="window.location.href='jadwal.php'">Lihat Semua</button>
      </div>
      <?php 
      $hasJadwal = false;
      while ($j = $jadwalHariIni->fetch_assoc()): 
        $hasJadwal = true;
        $jamMulai = substr($j['jam_mulai'], 0, 5);
        $jamSelesai = substr($j['jam_selesai'] ?? $j['jam_mulai'], 0, 5);
        $ruangan = $j['kode_ruangan'] ? $j['kode_ruangan'] . ' (' . $j['nama_ruangan'] . ')' : '-';
      ?>
      <div class="schedule-item">
        <div class="schedule-time"><?= $jamMulai ?>–<?= $jamSelesai ?></div>
        <div class="schedule-body">
          <div class="schedule-matkul"><?= htmlspecialchars($j['nama_mk']) ?></div>
          <div class="schedule-meta">
            <span>🏫 <?= htmlspecialchars($j['nama_kelas']) ?></span>
            <span>📍 <?= htmlspecialchars($ruangan) ?></span>
            <span>📖 <?= $j['sks'] ?> SKS</span>
          </div>
        </div>
        <div class="schedule-actions">
          <?php if ($j['sesi_aktif']): ?>
            <span class="pill pill-green" style="font-size:10px">Berlangsung</span>
            <button class="btn btn-primary btn-xs" onclick="window.location.href='sesi.php?id=<?= $j['sesi_id'] ?>'">QR</button>
          <?php else: ?>
            <span class="pill pill-gray" style="font-size:10px">Belum Mulai</span>
            <button class="btn btn-secondary btn-xs" onclick="window.location.href='jadwal.php'">Mulai</button>
          <?php endif; ?>
        </div>
      </div>
      <?php endwhile; ?>
      <?php if (!$hasJadwal): ?>
      <div style="text-align:center;padding:30px;color:#94A3B8">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div style="margin-top:8px;font-size:13px">Tidak ada jadwal hari ini</div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Notifikasi -->
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            Notifikasi
          </div>
          <div class="card-sub">Aktivitas terbaru</div>
        </div>
        <?php if ($pendingIzin > 0): ?>
        <span class="pill pill-red"><?= $pendingIzin ?> baru</span>
        <?php endif; ?>
      </div>
      <?php if (empty($notif)): ?>
      <div style="text-align:center;padding:30px;color:#94A3B8">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <div style="margin-top:8px;font-size:13px">Semua sudah terpantau</div>
      </div>
      <?php endif; ?>
      <?php foreach ($notif as $n): ?>
        <?php if ($n['tipe'] === 'izin'): ?>
        <div class="activity-item">
          <div class="activity-dot" style="background:#FEF3C7;color:#D97706">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          </div>
          <div class="activity-body">
            <div class="activity-title">Pengajuan <?= $n['jenis'] ?> - <?= htmlspecialchars($n['nama_mhs']) ?></div>
            <div class="activity-meta"><?= htmlspecialchars($n['nama_kelas']) ?> • <?= htmlspecialchars($n['nama_mk']) ?> • <?= ucfirst($n['jenis']) ?></div>
          </div>
          <div class="activity-time"><?= date('H:i', strtotime($n['created_at'])) ?></div>
        </div>
        <?php else: ?>
        <div class="activity-item">
          <div class="activity-dot" style="background:#D1FAE5;color:#059669">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <div class="activity-body">
            <div class="activity-title">Sesi Absensi Selesai</div>
            <div class="activity-meta"><?= htmlspecialchars($n['nama_kelas']) ?> • <?= date('d M', strtotime($n['tanggal'])) ?> • <?= $n['hadir'] ?>/<?= $n['total_mhs'] ?> hadir</div>
          </div>
          <div class="activity-time">P-<?= $n['pertemuan_ke'] ?></div>
        </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ═══════ AKTIVITAS SESI TERBARU ═══════ -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Aktivitas Sesi Absensi Terbaru
      </div>
      <button class="btn btn-secondary btn-sm" onclick="window.location.href='sesi.php'">Lihat Semua</button>
    </div>
    <div class="tbl-wrap">
      <table class="tabel">
        <thead>
          <tr><th>Tanggal</th><th>Mata Kuliah</th><th>Kelas</th><th>Pertemuan</th><th>Hadir</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php 
          $no = 1;
          while ($s = $sesiTerbaru->fetch_assoc()): 
            $statusClass = $s['status'] === 'aktif' ? 'pill-green' : 'pill-blue';
            $statusText = $s['status'] === 'aktif' ? 'Aktif' : 'Selesai';
            $hadirColor = ($s['hadir'] / max($s['total_mhs'], 1)) >= 0.75 ? '#10B981' : '#F97316';
          ?>
          <tr>
            <td><?= date('d M Y', strtotime($s['tanggal'])) ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($s['nama_mk']) ?></td>
            <td><?= htmlspecialchars($s['nama_kelas']) ?></td>
            <td>Ke-<?= $s['pertemuan_ke'] ?></td>
            <td><span style="color:<?= $hadirColor ?>;font-weight:800"><?= $s['hadir'] ?>/<?= $s['total_mhs'] ?></span></td>
            <td><span class="pill <?= $statusClass ?>">● <?= $statusText ?></span></td>
          </tr>
          <?php $no++; endwhile; ?>
          <?php if ($no === 1): ?>
          <tr><td colspan="6" style="text-align:center;padding:40px;color:#94A3B8">Belum ada sesi absensi</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function toast(m, t = 'info') {
  const el = document.createElement('div');
  el.style.cssText = `position:fixed;top:20px;right:20px;padding:12px 20px;background:${t=='success'?'#10b981':t=='error'?'#ef4444':'#3b82f6'};color:#fff;border-radius:8px;z-index:10000;animation:slideIn 0.3s ease;`;
  el.textContent = m;
  document.body.appendChild(el);
  setTimeout(() => el.remove(), 3000);
}
</script>

<?php require_once 'footer.php'; ?>