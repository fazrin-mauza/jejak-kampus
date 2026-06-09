<?php
// ════════════════════════════════════════════════
// NAVIGASI DOSEN — dengan data real-time
// ════════════════════════════════════════════════

// Ambil data dosen dari session
$nama_dosen = $user['nama'] ?? 'Dosen';
$user_id = $user['id'] ?? 0;

// Ambil dosen_id dan foto profil dari tabel dosen & users
$dosen_id = 0;
$dosen_data = null;
$profile_pic = '';
if ($user_id) {
    $stmt = $conn->prepare("SELECT d.id, d.nama, d.nidn, u.profile FROM dosen d JOIN users u ON d.user_id = u.id WHERE d.user_id = ? LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $dosen_data = $result->fetch_assoc();
    $stmt->close();
    
    if ($dosen_data) {
        $dosen_id = $dosen_data['id'];
        $nama_dosen = $dosen_data['nama'] ?: $nama_dosen;
        $profile_pic = $dosen_data['profile'] ?? '';
    }
}

// Hitung pending izin real-time
$pending_izin = 0;
if ($dosen_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM izin i 
                            JOIN jadwal j ON i.jadwal_id = j.id 
                            WHERE j.dosen_id = ? AND i.status = 'pending'");
    $stmt->bind_param('i', $dosen_id);
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
$nama_parts = explode(' ', trim($nama_dosen));
$initial = strtoupper(substr($nama_parts[0], 0, 1));
$initial .= isset($nama_parts[1]) ? strtoupper(substr($nama_parts[1], 0, 1)) : strtoupper(substr($nama_parts[0], -1, 1));

// Halaman aktif
$current_page = $page ?? 'dashboard';
?>

<style>
    /* Logo gambar */
    .logo-icon-img {
        width: 40px;
        height: 40px;
        object-fit: contain;
        border-radius: 10px;
    }
    
    /* Badge BETA dengan animasi kelap-kelip hijau */
    .nav-badge-beta {
        display: inline-block;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        font-size: 9px;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 20px;
        margin-left: 8px;
        letter-spacing: 0.5px;
        animation: blinkGreen 1.2s ease-in-out infinite;
        box-shadow: 0 0 5px rgba(16, 185, 129, 0.5);
    }
    
    @keyframes blinkGreen {
        0%, 100% {
            opacity: 1;
            box-shadow: 0 0 5px rgba(16, 185, 129, 0.5);
        }
        50% {
            opacity: 0.6;
            box-shadow: 0 0 12px rgba(16, 185, 129, 0.8);
        }
    }
    
    /* Ikon AI spesifik */
    .nav-icon-ai svg {
        stroke-width: 1.8;
    }
</style>

<!-- ══════════ SIDEBAR OVERLAY ══════════ -->
<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

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
                ['page' => 'dashboard', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>', 'label' => 'Dashboard', 'url' => '/dashboard/dosen']
            ],
            'Akademik' => [
                ['page' => 'jadwal', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>', 'label' => 'Jadwal Mengajar', 'url' => 'jadwal'],
                ['page' => 'sesi', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>', 'label' => 'Sesi Absensi & QR', 'url' => 'sesi'],
                ['page' => 'rekap', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>', 'label' => 'Rekap Absensi', 'url' => 'rekap']
            ],
            'Layanan' => [
                ['page' => 'izin', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>', 'label' => 'Approval Izin', 'url' => 'izin', 'badge' => $pending_izin],
                // Menu JEJAK AI dengan BETA dan ikon khusus
                ['page' => 'ai', 'icon' => '<svg class="nav-icon-ai" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 0 1 10 10c0 5.5-4.5 10-10 10S2 17.5 2 12 6.5 2 12 2z"/><path d="M12 6v6l4 2"/><path d="M8 11h.01"/><path d="M16 11h.01"/></svg>', 'label' => 'Jejak AI', 'url' => 'ai', 'beta' => true]
            ],
            'Akun' => [
                ['page' => 'profil', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>', 'label' => 'Profil & Pengaturan', 'url' => 'profil']
            ]
        ];
        
        foreach ($nav_sections as $section_name => $items):
        ?>
        <div class="nav-section"><?= $section_name ?></div>
        <?php foreach ($items as $item): ?>
        <a href="<?= $item['url'] ?>" class="nav-item <?= $current_page == $item['page'] ? 'active' : '' ?>" style="text-decoration: none;">
            <span class="nav-icon"><?= $item['icon'] ?></span>
            <?= $item['label'] ?>
            <?php if (isset($item['badge']) && $item['badge'] > 0): ?>
            <span class="nav-badge"><?= $item['badge'] ?></span>
            <?php endif; ?>
            <?php if (isset($item['beta']) && $item['beta'] === true): ?>
            <span class="nav-badge-beta">BETA</span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
    
    <div class="sidebar-bottom">
        <a href="/auth/logout.php" class="btn-logout-link" style="text-decoration: none;">
            <button class="btn-logout" style="width:100%">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px;">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
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
            <button class="icon-btn btn-menu-mobile" onclick="toggleSidebar()" aria-label="Buka Menu">
                <span class="hamburger-icon">&#9776;</span>
            </button>
            
            <span style="font-size:20px" id="topbar-icon">
                <?php
                $icons = [
                    'dashboard' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>',
                    'jadwal' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
                    'sesi' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>',
                    'rekap' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>',
                    'izin' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>',
                    'profil' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
                    'ai' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 0 1 10 10c0 5.5-4.5 10-10 10S2 17.5 2 12 6.5 2 12 2z"/><path d="M12 6v6l4 2"/><path d="M8 11h.01"/><path d="M16 11h.01"/></svg>'
                ];
                echo $icons[$current_page] ?? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>';
                ?>
            </span>
            
            <div>
                <?php
                $titles = [
                    'dashboard' => 'Dashboard',
                    'jadwal' => 'Jadwal Mengajar',
                    'sesi' => 'Sesi Absensi & QR Code',
                    'rekap' => 'Rekap Absensi',
                    'izin' => 'Persetujuan Izin',
                    'profil' => 'Profil & Pengaturan',
                    'ai' => 'Jejak AI - Asisten Presensi'
                ];
                $breadcrumbs = [
                    'dashboard' => 'Dosen / Dashboard',
                    'jadwal' => 'Dosen / Jadwal Mengajar',
                    'sesi' => 'Dosen / Sesi Absensi',
                    'rekap' => 'Dosen / Rekap Absensi',
                    'izin' => 'Dosen / Persetujuan Izin',
                    'profil' => 'Dosen / Profil',
                    'ai' => 'Dosen / Jejak AI'
                ];
                ?>
                <div class="topbar-title"><?= $titles[$current_page] ?? 'Dashboard' ?></div>
                <div class="topbar-bread"><?= $breadcrumbs[$current_page] ?? 'Dosen / Dashboard' ?></div>
            </div>
        </div>
        
        <div class="topbar-right">
            <!-- Icon Notifikasi (Bell) -->
            <div onclick="window.location.href='izin'" style="position:relative; width:32px; height:32px; cursor:pointer; flex-shrink:0; display:flex; align-items:center; justify-content:center; border-radius:8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <?php if ($pending_izin > 0): ?>
                <span style="position:absolute; top:-4px; right:-4px; background:#ef4444; color:white; font-size:10px; font-weight:bold; min-width:16px; height:16px; border-radius:50%; display:flex; align-items:center; justify-content:center; padding:0 3px; font-family:monospace;"><?= $pending_izin ?></span>
                <?php endif; ?>
            </div>
            
            <!-- Foto Profil -->
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