<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/navigasi.php';

// Ambil data mahasiswa dari session
$user_id = $user['id'] ?? 0;
$mahasiswa_id = 0;
$mahasiswa_data = null;

if ($user_id) {
    $stmt = $conn->prepare("SELECT m.id, m.nim, m.nama, m.tanggal_lahir, m.jenis_kelamin, m.status,
                                   k.nama_kelas as kelas, k.jurusan, u.email, u.profile
                            FROM mahasiswa m 
                            JOIN users u ON m.user_id = u.id 
                            LEFT JOIN kelas k ON m.kelas_id = k.id 
                            WHERE m.user_id = ? LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $mahasiswa_data = $result->fetch_assoc();
    $stmt->close();
}

if ($mahasiswa_data) {
    $mahasiswa_id = $mahasiswa_data['id'];
    $nim = $mahasiswa_data['nim'] ?? '-';
    $nama = $mahasiswa_data['nama'] ?? 'Mahasiswa';
    $email = $mahasiswa_data['email'] ?? '-';
    $kelas = $mahasiswa_data['kelas'] ?? '-';
    $jurusan = $mahasiswa_data['jurusan'] ?? '-';
    $tanggal_lahir = $mahasiswa_data['tanggal_lahir'] ?? '';
    $jenis_kelamin = $mahasiswa_data['jenis_kelamin'] ?? 'L';
    $profile_pic = $mahasiswa_data['profile'] ?? '';
    $status_mahasiswa = $mahasiswa_data['status'] ?? 'aktif';
} else {
    $nim = '-';
    $nama = 'Mahasiswa';
    $email = '-';
    $kelas = '-';
    $jurusan = '-';
    $tanggal_lahir = '';
    $jenis_kelamin = 'L';
    $profile_pic = '';
    $status_mahasiswa = 'aktif';
}

// Semester aktif
$semester_aktif = 'Semester Aktif';
$stmt = $conn->query("SELECT CONCAT(tahun, ' ', semester) as nama FROM tahun_akademik WHERE status = 'aktif' LIMIT 1");
if ($stmt && $row = $stmt->fetch_assoc()) {
    $semester_aktif = $row['nama'];
}

// Status mahasiswa
$status_text = $status_mahasiswa == 'aktif' ? 'Mahasiswa Aktif' : 'Mahasiswa Nonaktif';
$status_class = $status_mahasiswa == 'aktif' ? 'pill-green' : 'pill-gray';

// Angkatan dari NIM (4 digit pertama)
$angkatan = substr($nim, 0, 4) ?: '2024';

// Initial untuk avatar
$nama_parts = explode(' ', trim($nama));
$initial = strtoupper(substr($nama_parts[0], 0, 1));
$initial .= isset($nama_parts[1]) ? strtoupper(substr($nama_parts[1], 0, 1)) : '';
if (empty($initial) || strlen($initial) < 1) $initial = strtoupper(substr($nama, 0, 1));

// Statistik akademik
$total_sks = 0;
$total_mk = 0;
$total_hadir = 0;
$total_sesi = 0;
$persen_kehadiran = 0;

if ($mahasiswa_id) {
    // Total SKS dan MK
    $stmtSks = $conn->prepare("SELECT COALESCE(SUM(mk.sks), 0) as total_sks, COUNT(DISTINCT j.mata_kuliah_id) as total_mk
                               FROM jadwal j 
                               JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id 
                               JOIN kelas k ON j.kelas_id = k.id
                               JOIN mahasiswa m ON m.kelas_id = k.id
                               WHERE m.id = ?");
    $stmtSks->bind_param('i', $mahasiswa_id);
    $stmtSks->execute();
    $sksResult = $stmtSks->get_result();
    if ($row = $sksResult->fetch_assoc()) {
        $total_sks = (int)($row['total_sks'] ?? 0);
        $total_mk = (int)($row['total_mk'] ?? 0);
    }
    $stmtSks->close();
    
    // Total sesi dan kehadiran
    $stmtHadir = $conn->prepare("SELECT 
                                    (SELECT COUNT(*) FROM sesi_absensi s 
                                     JOIN jadwal j ON s.jadwal_id = j.id 
                                     JOIN kelas k ON j.kelas_id = k.id
                                     JOIN mahasiswa m ON m.kelas_id = k.id
                                     WHERE m.id = ?) as total_sesi,
                                    (SELECT COUNT(*) FROM absensi a 
                                     JOIN sesi_absensi s ON a.sesi_id = s.id 
                                     JOIN jadwal j ON s.jadwal_id = j.id 
                                     JOIN kelas k ON j.kelas_id = k.id
                                     JOIN mahasiswa m ON m.kelas_id = k.id
                                     WHERE m.id = ? AND a.mahasiswa_id = ? AND a.status IN ('hadir', 'telat')) as total_hadir");
    $stmtHadir->bind_param('iii', $mahasiswa_id, $mahasiswa_id, $mahasiswa_id);
    $stmtHadir->execute();
    $hadirResult = $stmtHadir->get_result();
    if ($row = $hadirResult->fetch_assoc()) {
        $total_sesi = (int)($row['total_sesi'] ?? 0);
        $total_hadir = (int)($row['total_hadir'] ?? 0);
        $persen_kehadiran = $total_sesi > 0 ? round(($total_hadir / $total_sesi) * 100) : 0;
    }
    $stmtHadir->close();
}

// Izin pending
$pending_izin = 0;
if ($mahasiswa_id) {
    $stmtIzin = $conn->prepare("SELECT COUNT(*) as total FROM izin WHERE mahasiswa_id = ? AND status = 'pending'");
    $stmtIzin->bind_param('i', $mahasiswa_id);
    $stmtIzin->execute();
    $izinResult = $stmtIzin->get_result();
    if ($row = $izinResult->fetch_assoc()) {
        $pending_izin = (int)$row['total'];
    }
    $stmtIzin->close();
}

// MK risiko (kehadiran < 75%)
$mk_risiko = [];
if ($mahasiswa_id && $kelas) {
    $kelas_id = $conn->query("SELECT id FROM kelas WHERE nama_kelas = '$kelas' LIMIT 1")->fetch_assoc()['id'] ?? 0;
    if ($kelas_id) {
        $stmtRisiko = $conn->prepare("SELECT mk.nama_mk, 
                                             COALESCE((SELECT COUNT(*) FROM sesi_absensi s 
                                              JOIN jadwal j2 ON s.jadwal_id = j2.id 
                                              WHERE j2.mata_kuliah_id = mk.id AND j2.kelas_id = ?), 0) as total_sesi,
                                             COALESCE((SELECT COUNT(*) FROM absensi a 
                                              JOIN sesi_absensi s ON a.sesi_id = s.id 
                                              JOIN jadwal j2 ON s.jadwal_id = j2.id 
                                              WHERE j2.mata_kuliah_id = mk.id AND j2.kelas_id = ? AND a.mahasiswa_id = ? AND a.status IN ('hadir', 'telat')), 0) as total_hadir
                                      FROM mata_kuliah mk
                                      JOIN jadwal j ON mk.id = j.mata_kuliah_id
                                      WHERE j.kelas_id = ?
                                      GROUP BY mk.id");
        $stmtRisiko->bind_param('iiii', $kelas_id, $kelas_id, $mahasiswa_id, $kelas_id);
        $stmtRisiko->execute();
        $risikoResult = $stmtRisiko->get_result();
        while ($row = $risikoResult->fetch_assoc()) {
            $persen_mk = $row['total_sesi'] > 0 ? round(($row['total_hadir'] / $row['total_sesi']) * 100) : 0;
            if ($persen_mk < 75 && $persen_mk > 0) {
                $mk_risiko[] = $row['nama_mk'];
            }
        }
        $stmtRisiko->close();
    }
}
?>

<style>
@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes slideOutRight {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}
#avatar-wrapper:hover .avatar-overlay {
    opacity: 1 !important;
}
.setting-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
}
.setting-row:last-child {
    border-bottom: none;
}
.setting-label {
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 2px;
}
.setting-sub {
    font-size: 11px;
    color: var(--text3);
}
.toggle {
    width: 44px;
    height: 24px;
    background: #cbd5e1;
    border-radius: 24px;
    border: none;
    cursor: pointer;
    position: relative;
    transition: all 0.3s ease;
}
.toggle::after {
    content: '';
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    position: absolute;
    top: 2px;
    left: 2px;
    transition: all 0.3s ease;
}
.toggle.on {
    background: #10b981;
}
.toggle.on::after {
    left: 22px;
}

/* ══════════ PERBAIKAN RESPONSIF UNTUK HP ══════════ */
@media (max-width: 768px) {
    /* Container utama */
    .content {
        padding: 12px !important;
        overflow-x: hidden !important;
    }
    
    /* Grid 2 kolom jadi 1 kolom */
    .grid-2 {
        grid-template-columns: 1fr !important;
        gap: 16px !important;
    }
    
    /* Card */
    .card {
        width: 100% !important;
        margin: 0 !important;
        box-sizing: border-box !important;
    }
    
    .card-body {
        padding: 14px !important;
    }
    
    /* Form elements */
    .form-group {
        width: 100% !important;
        margin-bottom: 14px !important;
    }
    
    .form-row {
        flex-direction: column !important;
        gap: 12px !important;
    }
    
    input, select, textarea {
        width: 100% !important;
        box-sizing: border-box !important;
        font-size: 14px !important; /* Mencegah zoom otomatis di iOS */
        padding: 10px 12px !important;
    }
    
    /* Tombol */
    .btn {
        width: 100% !important;
        justify-content: center !important;
        padding: 12px 16px !important;
    }
    
    /* Detail row */
    .detail-row {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 4px !important;
    }
    
    .detail-label {
        width: 100% !important;
        font-size: 11px !important;
    }
    
    .detail-val {
        width: 100% !important;
        font-size: 13px !important;
    }
    
    /* Avatar */
    #avatar-wrapper {
        margin: 0 auto 16px !important;
    }
    
    /* Pills */
    .pill {
        font-size: 10px !important;
        padding: 3px 8px !important;
    }
}


</style>

<div class="section active" id="sec-profil">


    <div class="grid-2" style="align-items:start">

        <!-- Kolom Kiri -->
        <div style="display:flex;flex-direction:column;gap:18px">
            <!-- Kartu Foto Profil -->
            <div class="card" style="text-align:center">
                <div id="avatar-wrapper" style="position:relative;width:100px;height:100px;margin:0 auto 16px;cursor:pointer" onclick="document.getElementById('input-foto').click()" title="Klik untuk mengganti foto">
                    <?php if ($profile_pic): ?>
                        <img id="avatar-img" src="<?= htmlspecialchars($profile_pic) ?>" alt="Foto Profil" style="width:100px;height:100px;border-radius:50%;object-fit:cover;display:block">
                    <?php else: ?>
                        <div id="avatar-initial" style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,var(--ora1),var(--ora3));display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:800;color:#fff">
                            <?= htmlspecialchars($initial) ?>
                        </div>
                    <?php endif; ?>
                    <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.55);color:#fff;font-size:10px;padding:6px 0;text-align:center;border-radius:0 0 50% 50%;opacity:0;transition:opacity 0.2s;line-height:1" class="avatar-overlay">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="vertical-align: middle; margin-right: 2px;">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        Ganti
                    </div>
                </div>
                <input type="file" id="input-foto" accept="image/*" style="display:none" onchange="uploadFoto(event)">
                
                <div style="font-size:18px;font-weight:800"><?= htmlspecialchars($nama) ?></div>
                <div style="font-size:13px;color:var(--text3);margin-top:4px">NIM: <?= htmlspecialchars($nim) ?></div>
                <div style="margin-top:10px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
                    <span class="pill <?= $status_class ?>">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <?= $status_text ?>
                    </span>
                    <span class="pill pill-blue">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                        </svg>
                        <?= htmlspecialchars($kelas) ?>
                    </span>
                </div>
            </div>

            <!-- Form Data Diri -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        Data Diri
                    </div>
                </div>
                <form id="form-profil" onsubmit="simpanProfil(event)">
                    <div class="form-group">
                        <label>Nama Lengkap <span style="color:var(--red)">*</span></label>
                        <input type="text" name="nama" value="<?= htmlspecialchars($nama) ?>" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>NIM</label>
                            <input type="text" value="<?= htmlspecialchars($nim) ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Angkatan</label>
                            <input type="text" value="<?= htmlspecialchars($angkatan) ?>" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Kelas</label>
                            <input type="text" value="<?= htmlspecialchars($kelas) ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Jurusan</label>
                            <input type="text" value="<?= htmlspecialchars($jurusan) ?>" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" value="<?= htmlspecialchars($tanggal_lahir) ?>">
                        </div>
                        <div class="form-group">
                            <label>Jenis Kelamin</label>
                            <select name="jenis_kelamin">
                                <option value="L" <?= $jenis_kelamin == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="P" <?= $jenis_kelamin == 'P' ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?= htmlspecialchars($email) ?>" readonly>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:6px">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/>
                            <polyline points="7 3 7 8 15 8"/>
                        </svg>
                        Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>

        <!-- Kolom Kanan -->
        <div style="display:flex;flex-direction:column;gap:18px">
            <!-- Info Akademik -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:6px">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                            <path d="M6 12v5c0 1.1 2.7 2 6 2s6-.9 6-2v-5"/>
                        </svg>
                        Informasi Akademik
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Semester Aktif</div>
                    <div class="detail-val"><?= htmlspecialchars($semester_aktif) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status Mahasiswa</div>
                    <div class="detail-val"><?= ucfirst($status_mahasiswa) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Total SKS</div>
                    <div class="detail-val"><?= $total_sks ?> SKS (<?= $total_mk ?> Mata Kuliah)</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Total Kehadiran</div>
                    <div class="detail-val"><?= $total_hadir ?> / <?= $total_sesi ?> Sesi</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Persentase Kehadiran</div>
                    <div class="detail-val">
                        <span style="color:<?= $persen_kehadiran >= 75 ? '#10b981' : '#ef4444' ?>;font-weight:800">
                            <?= $persen_kehadiran ?>%
                        </span>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Mata Kuliah Risiko</div>
                    <div class="detail-val">
                        <?php if (count($mk_risiko) > 0): ?>
                            <span style="color:var(--red)">
                                <?= implode(', ', array_slice($mk_risiko, 0, 3)) ?>
                                <?= count($mk_risiko) > 3 ? ' + ' . (count($mk_risiko) - 3) . ' lainnya' : '' ?>
                            </span>
                        <?php else: ?>
                            <span style="color:var(--green)">Tidak ada</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Izin Menunggu</div>
                    <div class="detail-val">
                        <span class="pill pill-yellow" style="font-size:11px">
                            <?= $pending_izin ?> Pengajuan
                        </span>
                    </div>
                </div>
                <a href="rekap" class="btn btn-info btn-sm" style="margin-top:12px">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    Lihat Rekap Kehadiran
                </a>
            </div>

            <!-- Metode Masuk (Google SSO) -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:6px">
                            <circle cx="12" cy="12" r="10"/>
                            <circle cx="12" cy="12" r="4"/>
                            <line x1="21.17" y1="8" x2="12" y2="8"/>
                            <line x1="3.95" y1="6.06" x2="8.54" y2="14"/>
                            <line x1="10.88" y1="21.94" x2="15.46" y2="14"/>
                            <line x1="21.17" y1="16" x2="12" y2="16"/>
                            <line x1="3.95" y1="17.94" x2="8.54" y2="10"/>
                        </svg>
                        Akun Google
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Email Google</div>
                    <div class="detail-val"><?= htmlspecialchars($email) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Metode Masuk</div>
                    <div class="detail-val">
                        <span class="pill pill-blue" style="background:#4285F4;color:white">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">
                                <circle cx="12" cy="12" r="10"/>
                                <circle cx="12" cy="12" r="4"/>
                                <line x1="21.17" y1="8" x2="12" y2="8"/>
                                <line x1="3.95" y1="6.06" x2="8.54" y2="14"/>
                                <line x1="10.88" y1="21.94" x2="15.46" y2="14"/>
                                <line x1="21.17" y1="16" x2="12" y2="16"/>
                                <line x1="3.95" y1="17.94" x2="8.54" y2="10"/>
                            </svg>
                            Google Single Sign-On
                        </span>
                    </div>
                </div>
            </div>
                 <!-- Tombol Logout -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:6px">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Keluar Aplikasi
                    </div>
                    <div class="card-sub">Logout dari akun Jejak Kampus</div>
                </div>
                <div class="card-body">
                    <a href="/auth/logout.php" class="btn-logout-bottom" style="display:block; width:100%; text-decoration:none;" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                        <button type="button" style="width:100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 12px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            Logout / Keluar
                        </button>
                    </a>
                </div>
            </div>
            <!-- Pengaturan Notifikasi -->
          
            </div>
        </div>
    </div>
</div>

<script>
// ─────────────────────────────────────────────
// UPLOAD FOTO
// ─────────────────────────────────────────────
function uploadFoto(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    if (!file.type.match('image.*')) {
        showNotification('File harus berupa gambar', 'kesalahan');
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        showNotification('Ukuran file maksimal 2MB', 'kesalahan');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'upload_foto');
    formData.append('foto', file);
    showNotification('Mengunggah foto...', 'informasi');
    
    fetch('api/profil.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(resp => {
        showNotification(resp.msg, resp.success ? 'berhasil' : 'kesalahan');
        if (resp.success) {
            const wrapper = document.getElementById('avatar-wrapper');
            const initial = document.getElementById('avatar-initial');
            let img = document.getElementById('avatar-img');
            
            if (initial) initial.remove();
            if (!img) {
                img = document.createElement('img');
                img.id = 'avatar-img';
                img.style.cssText = 'width:100px;height:100px;border-radius:50%;object-fit:cover;display:block';
                wrapper.insertBefore(img, wrapper.firstChild);
            }
            img.src = resp.url + '?t=' + Date.now();
        }
    })
    .catch(err => {
        console.error(err);
        showNotification('Gagal mengunggah foto', 'kesalahan');
    });
}

// ─────────────────────────────────────────────
// SIMPAN PROFIL
// ─────────────────────────────────────────────
function simpanProfil(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'update_profil');
    
    showNotification('Menyimpan data...', 'informasi');
    
    fetch('api/profil.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(resp => {
        showNotification(resp.msg, resp.success ? 'berhasil' : 'kesalahan');
        if (resp.success) {
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(err => {
        console.error(err);
        showNotification('Gagal menyimpan profil', 'kesalahan');
    });
}

// ─────────────────────────────────────────────
// TOGGLE NOTIFIKASI
// ─────────────────────────────────────────────
function toggleNotifikasi(key, btn) {
    const isOn = btn.classList.contains('on');
    const newValue = isOn ? 0 : 1;
    
    fetch('api/profil.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=update_notifikasi&key=' + key + '&value=' + newValue
    })
    .then(r => r.json())
    .then(resp => {
        if (resp.success) {
            btn.classList.toggle('on');
            showNotification(resp.msg, 'berhasil');
        } else {
            showNotification(resp.msg, 'kesalahan');
        }
    })
    .catch(err => {
        console.error(err);
        showNotification('Gagal menyimpan pengaturan', 'kesalahan');
    });
}

// ─────────────────────────────────────────────
// NOTIFIKASI CUSTOM
// ─────────────────────────────────────────────
function showNotification(message, type = 'informasi') {
    const colors = {
        'berhasil': '#059669',
        'kesalahan': '#dc2626',
        'informasi': '#2563eb',
        'peringatan': '#d97706'
    };
    const bgColor = colors[type] || colors['informasi'];
    
    const existing = document.querySelector('.custom-notification');
    if (existing) existing.remove();
    
    const el = document.createElement('div');
    el.className = 'custom-notification';
    el.style.cssText = `position:fixed;top:20px;right:20px;padding:12px 20px;background:${bgColor};color:#fff;border-radius:10px;z-index:10000;box-shadow:0 8px 24px rgba(0,0,0,0.2);font-size:13px;font-weight:600;max-width:350px;word-wrap:break-word;animation:slideInRight 0.3s ease-out`;
    
    const icons = {
        'berhasil': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> ',
        'kesalahan': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> ',
        'informasi': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg> '
    };
    
    el.innerHTML = (icons[type] || icons['informasi']) + message;
    document.body.appendChild(el);
    
    setTimeout(() => {
        el.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => el.remove(), 300);
    }, 3500);
}

// Tambahkan style animasi
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes slideOutRight { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
`;
document.head.appendChild(style);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>