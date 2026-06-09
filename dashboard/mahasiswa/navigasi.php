<?php
// ════════════════════════════════════════════════
// NAVIGASI MAHASISWA — dengan SVG profesional
// ════════════════════════════════════════════════

// Ambil data mahasiswa dari session
$nama_mahasiswa = $user['nama'] ?? 'Mahasiswa';
$user_id = $user['id'] ?? 0;
$pending_izin = 0;

// Ambil mahasiswa_id dan foto profil
$mahasiswa_id = 0;
$mahasiswa_data = null;
$profile_pic = '';
$nim_mahasiswa = '';
$kelas_mahasiswa = '';

if ($user_id) {
    $stmt = $conn->prepare("SELECT m.id, m.nama, m.nim, k.nama_kelas as kelas, u.profile FROM mahasiswa m JOIN users u ON m.user_id = u.id LEFT JOIN kelas k ON m.kelas_id = k.id WHERE m.user_id = ? LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $mahasiswa_data = $result->fetch_assoc();
    $stmt->close();
    
    if ($mahasiswa_data) {
        $mahasiswa_id = $mahasiswa_data['id'];
        $nama_mahasiswa = $mahasiswa_data['nama'] ?: $nama_mahasiswa;
        $nim_mahasiswa = $mahasiswa_data['nim'] ?? '';
        $kelas_mahasiswa = $mahasiswa_data['kelas'] ?? '';
        $profile_pic = $mahasiswa_data['profile'] ?? '';
    }
}

// Hitung pending izin real-time
$pending_izin = 0;
if ($mahasiswa_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM izin WHERE mahasiswa_id = ? AND status = 'pending'");
    $stmt->bind_param('i', $mahasiswa_id);
    $stmt->execute();
    $stmt->bind_result($pending_izin);
    $stmt->fetch();
    $stmt->close();
}

// Semester aktif
$semester_aktif = 'Semester Aktif';
$stmt = $conn->query("SELECT CONCAT(tahun, ' ', semester) as nama FROM tahun_akademik WHERE status = 'aktif' LIMIT 1");
if ($stmt && $row = $stmt->fetch_assoc()) {
    $semester_aktif = $row['nama'];
}

// Initial untuk avatar (fallback jika tidak ada foto)
$nama_parts = explode(' ', trim($nama_mahasiswa));
$initial = strtoupper(substr($nama_parts[0], 0, 1));
$initial .= isset($nama_parts[1]) ? strtoupper(substr($nama_parts[1], 0, 1)) : (isset($nama_parts[0][1]) ? strtoupper(substr($nama_parts[0], 1, 1)) : '');

// Halaman aktif
$current_page = $page ?? 'dashboard';
?>
<style>
    .logo-icon-img {
        width: 40px;
        height: 40px;
        object-fit: contain;
        border-radius: 10px;
    }
    @media (max-width: 768px) {
    .btn-menu-mobile {
        display: flex !important;
    }
    
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 280px !important;
        height: 100vh;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        z-index: 1001;
        background: var(--surface);
        overflow-y: auto;
    }
    
    .sidebar.open {
        transform: translateX(0);
        box-shadow: 0 0 40px rgba(0,0,0,.3);
    }
    
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .sidebar-overlay.open {
        opacity: 1;
        visibility: visible;
    }
    
    /* PERBAIKAN: Main content tidak terpotong */
    .main {
        width: 100%;
        overflow-x: hidden;
        padding-left: 0 !important;
        margin-left: 0 !important;
    }
    
    /* Topbar tidak kepotong */
    .topbar {
        padding-left: 12px !important;
        padding-right: 12px !important;
    }
    
    /* Content area */
    .content {
        padding: 16px !important;
        overflow-x: hidden;
    }
    
    /* Stat cards di HP */
    .stats-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 10px !important;
    }
    
    /* Grid 2 kolom jadi 1 kolom di HP */
    .grid-2 {
        grid-template-columns: 1fr !important;
        gap: 16px !important;
    }
    
    /* Tabel scroll horizontal jika terlalu lebar */
    .tbl-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .tabel {
        min-width: 600px;
    }
    
    /* Jadwal schedule item */
    .schedule-item {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<!-- ══════════ SIDEBAR OVERLAY ══════════ -->
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<!-- ══════════ SIDEBAR ══════════ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="/assets/logo.png" alt="Logo" class="logo-icon-img">
            <div>
                <div class="logo-text"><?= $config['settings']['app_name'] ?? 'Jejak Kampus' ?></div>
                <div class="logo-sub"><?= 'Sistem Informasi Absensi' ?></div>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <?php
        $nav_sections = [
            'Utama' => [
                ['page' => 'dashboard', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>', 'label' => 'Dashboard', 'url' => '/dashboard/mahasiswa']
            ],
            'Akademik' => [
                ['page' => 'jadwal', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>', 'label' => 'Jadwal Kuliah', 'url' => 'jadwal'],
                ['page' => 'absensi', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg>', 'label' => 'Absensi & QR Scan', 'url' => 'absensi'],
                ['page' => 'rekap', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>', 'label' => 'Rekap Kehadiran', 'url' => 'rekap']
            ],
            'Layanan' => [
                ['page' => 'izin', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>', 'label' => 'Pengajuan Izin', 'url' => 'izin', 'badge' => $pending_izin]
            ],
            'Prestasi' => [
                ['page' => 'prestasi', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>', 'label' => 'Jejak Tantangan', 'url' => 'prestasi']
            ],
            'Akun' => [
                ['page' => 'profil', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>', 'label' => 'Profil', 'url' => 'profil']
            ]
        ];
        
        foreach ($nav_sections as $section_name => $items):
        ?>
        <div class="nav-section"><?= $section_name ?></div>
        <?php foreach ($items as $item): ?>
        <a href="<?= $item['url'] ?>" class="nav-item <?= $current_page == $item['page'] ? 'active' : '' ?>" style="text-decoration: none;" data-page="<?= $item['page'] ?>">
            <span class="nav-icon"><?= $item['icon'] ?></span>
            <?= $item['label'] ?>
            <?php if (isset($item['badge']) && $item['badge'] > 0): ?>
            <span class="nav-badge"><?= $item['badge'] ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
    
    <div class="sidebar-bottom">
        <a href="/auth/logout.php" id="logout-link" class="btn-logout-link" style="text-decoration: none;">
            <button id="logout-button" class="btn-logout" style="width:100%; display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Keluar
            </button>
        </a>
    </div>
</aside>

<!-- ══════════ MAIN ══════════ -->
<div class="main">
    <div class="topbar">
        <div class="topbar-left">
            <button class="icon-btn btn-menu-mobile" id="menu-toggle-btn" aria-label="Buka Menu">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            
            <span style="font-size:20px" id="topbar-icon">
                <?php
                $icons = [
                    'dashboard' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
                    'jadwal' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
                    'absensi' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg>',
                    'rekap' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
                    'izin' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
                    'prestasi' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>',
                    'profil' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>'
                ];
                echo $icons[$current_page] ?? $icons['dashboard'];
                ?>
            </span>
            
            <div>
                <?php
                $titles = [
                    'dashboard' => 'Dashboard',
                    'jadwal' => 'Jadwal Kuliah',
                    'absensi' => 'Absensi & QR Scan',
                    'rekap' => 'Rekap Kehadiran',
                    'izin' => 'Pengajuan Izin',
                    'prestasi' => 'Jejak Tantangan',
                    'profil' => 'Profil'
                ];
                
                $breadcrumbs = [
                    'dashboard' => 'Mahasiswa / Dashboard',
                    'jadwal' => 'Mahasiswa / Jadwal Kuliah',
                    'absensi' => 'Mahasiswa / Absensi & QR Scan',
                    'rekap' => 'Mahasiswa / Rekap Kehadiran',
                    'izin' => 'Mahasiswa / Pengajuan Izin',
                    'prestasi' => 'Mahasiswa / Jejak Tantangan',
                    'profil' => 'Mahasiswa / Profil'
                ];
                ?>
                <div class="topbar-title"><?= $titles[$current_page] ?? 'Dashboard' ?></div>
                <div class="topbar-bread"><?= $breadcrumbs[$current_page] ?? 'Mahasiswa / Dashboard' ?></div>
            </div>
        </div>
        
        <div class="topbar-right">
            <div onclick="window.location.href='izin'" style="position:relative; width:32px; height:32px; cursor:pointer; flex-shrink:0; display:flex; align-items:center; justify-content:center; border-radius:8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <?php if ($pending_izin > 0): ?>
                <span style="position:absolute; top:-4px; right:-4px; background:#ef4444; color:white; font-size:10px; font-weight:bold; min-width:16px; height:16px; border-radius:50%; display:flex; align-items:center; justify-content:center; padding:0 3px; font-family:monospace;"><?= $pending_izin ?></span>
                <?php endif; ?>
            </div>

            <a href="prestasi" style="width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:8px; text-decoration:none;" title="Prestasi">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="7"></circle>
                    <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                </svg>
            </a>

            <div onclick="window.location.href='profil'" style="width:32px;height:32px;border-radius:50%;overflow:hidden;cursor:pointer;flex-shrink:0">
                <?php if ($profile_pic): ?>
                    <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Foto" style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                    <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--ora1),var(--ora3));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff"><?= $initial ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="content">