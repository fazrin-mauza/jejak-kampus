 <?php
// ════════════════════════════════════════════════
// NAVIGASI ADMIN — dengan SVG profesional
// ════════════════════════════════════════════════

// Ambil data admin dari session
$nama_admin = $user['nama'] ?? 'Administrator';
$user_id = $user['id'] ?? 0;
$profile_pic = $user['profile'] ?? '';
$initial = '';
if (!empty($nama_admin)) {
    $parts = explode(' ', trim($nama_admin));
    $initial = strtoupper(substr($parts[0], 0, 1));
    $initial .= isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)) : (isset($parts[0][1]) ? strtoupper(substr($parts[0], 1, 1)) : '');
}
?>
<style>
    /* Logo gambar */
.logo-icon-img {
  width: 40px;
  height: 40px;
  object-fit: contain;
  border-radius: 10px;
}

/* Jika ingin logo saja tanpa teks JK, hapus .logo-icon */
</style>


<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

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
  
<!--  <div class="sidebar-user">
    <div class="user-av">
      <?php if ($profile_pic): ?>
        <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Foto Profil">
      <?php else: ?>
        <?= $initial ?: 'AD' ?>
      <?php endif; ?>
    </div>
    <div>
      <div class="user-name"><?= htmlspecialchars($nama_admin) ?></div>
      <div class="user-role">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 2a10 10 0 0 0-10 10c0 7 10 18 10 18s10-11 10-18a10 10 0 0 0-10-10z"/>
          <circle cx="12" cy="12" r="3"/>
        </svg>
        Administrator
      </div>
    </div>
  </div> -->
  
  <nav class="sidebar-nav">
    <!-- Utama -->
    <div class="nav-section">Utama</div>
    <a href="/dashboard/admin" class="nav-item <?= $page == 'dashboard' || $page == '' ? 'active' : '' ?>">
      <span class="nav-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
      </span>
      Dashboard Utama
    </a>

    <!-- Manajemen -->
    <div class="nav-section">Manajemen</div>
    <a href="user" class="nav-item <?= $page == 'user' ? 'active' : '' ?>">
      <span class="nav-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
      </span>
      Manajemen User
    </a>

    <a href="mahasiswa" class="nav-item <?= $page == 'mahasiswa' ? 'active' : '' ?>">
        <span class="nav-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
          <path d="M22 3.74a4 4 0 0 1 0 6.52"/>
        </svg>
      </span>
      Manajemen Mahasiswa
    </a>

    <a href="dosen" class="nav-item <?= $page == 'dosen' ? 'active' : '' ?>">
      <span class="nav-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
          <path d="M22 3.74a4 4 0 0 1 0 6.52"/>
        </svg>
      </span>
      Manajemen Dosen
    </a>

    <a href="kelas" class="nav-item <?= $page == 'kelas' ? 'active' : '' ?>">
      <span class="nav-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
          <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
        </svg>
      </span>
      Manajemen Kelas
    </a>

    <a href="matkul" class="nav-item <?= $page == 'matkul' ? 'active' : '' ?>">
      <span class="nav-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
          <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
        </svg>
      </span>
      Mata Kuliah
    </a>

    <a href="jadwal" class="nav-item <?= $page == 'jadwal' ? 'active' : '' ?>">
      <span class="nav-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
          <line x1="16" y1="2" x2="16" y2="6"/>
          <line x1="8" y1="2" x2="8" y2="6"/>
          <line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
      </span>
      Manajemen Jadwal
    </a>

    <a href="tahunakademik" class="nav-item <?= $page == 'tahunakademik' ? 'active' : '' ?>">
      <span class="nav-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
          <line x1="16" y1="2" x2="16" y2="6"/>
          <line x1="8" y1="2" x2="8" y2="6"/>
          <line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
      </span>
      Tahun Akademik
    </a>

    <!-- Monitoring -->
    <div class="nav-section">Monitoring</div>
    <a href="absensi" class="nav-item <?= $page == 'absensi' ? 'active' : '' ?>">
      <span class="nav-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
          <line x1="9" y1="9" x2="15" y2="15"/>
          <line x1="15" y1="9" x2="9" y2="15"/>
        </svg>
      </span>
      Monitoring Absensi
    </a>

    <a href="izin" class="nav-item <?= $page == 'izin' ? 'active' : '' ?>">
      <span class="nav-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/>
          <line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
      </span>
      Monitoring Izin/Sakit
    </a>

    <!-- Laporan -->
    <div class="nav-section">Laporan</div>
    <a href="rekap" class="nav-item <?= $page == 'rekap' ? 'active' : '' ?>">
      <span class="nav-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="20" x2="18" y2="10"/>
          <line x1="12" y1="20" x2="12" y2="4"/>
          <line x1="6" y1="20" x2="6" y2="14"/>
        </svg>
      </span>
      Rekap & Laporan
    </a>

    <!-- Sistem -->
    <div class="nav-section">Sistem</div>
    <a href="pengaturan" class="nav-item <?= $page == 'pengaturan' ? 'active' : '' ?>">
      <span class="nav-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="3"/>
          <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
        </svg>
      </span>
      Pengaturan Sistem
    </a>
  </nav>
  
  <div class="sidebar-bottom">
    <a href="/auth/logout.php" style="text-decoration: none;">
      <button class="btn-logout">
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

<div class="main">
  <div class="topbar">
    <div class="topbar-left">
      <button class="icon-btn btn-menu-mobile" onclick="toggleSidebar()" aria-label="Buka Menu">
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
          'user' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
          'mahasiswa' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 0 0-10 10c0 7 10 18 10 18s10-11 10-18a10 10 0 0 0-10-10z"/><circle cx="12" cy="12" r="3"/></svg>',
          'dosen' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><path d="M22 3.74a4 4 0 0 1 0 6.52"/></svg>',
          'kelas' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
          'matkul' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
          'jadwal' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
          'tahunakademik' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
          'absensi' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg>',
          'izin' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
          'rekap' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
          'pengaturan' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>'
        ];
        $current_page_key = $page ?? 'dashboard';
        if ($current_page_key == '') $current_page_key = 'dashboard';
        echo $icons[$current_page_key] ?? $icons['dashboard'];
        ?>
      </span>
      
      <div>
        <?php
        $titles = [
          'dashboard' => 'Dashboard Utama',
          'user' => 'Manajemen User',
          'mahasiswa' => 'Manajemen Mahasiswa',
          'dosen' => 'Manajemen Dosen',
          'kelas' => 'Manajemen Kelas',
          'matkul' => 'Mata Kuliah',
          'jadwal' => 'Manajemen Jadwal',
          'tahunakademik' => 'Tahun Akademik',
          'absensi' => 'Monitoring Absensi',
          'izin' => 'Monitoring Izin/Sakit',
          'rekap' => 'Rekap & Laporan',
          'pengaturan' => 'Pengaturan Sistem'
        ];
        $bread_titles = $titles;
        ?>
        <div class="topbar-title">
          <?= $titles[$page] ?? 'Dashboard Utama' ?>
        </div>
        <div class="topbar-bread">
          Admin / <?= $bread_titles[$page] ?? 'Dashboard Utama' ?>
        </div>
      </div>
    </div>
    
   <div class="topbar-right">
    <!--   <button class="icon-btn" onclick="alert('Belum ada notifikasi baru')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
          <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        <span class="notif-dot"></span>
      </button> -->
      <a href="pengaturan" class="icon-btn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="3"/>
          <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
        </svg>
      </a>
    <!--  <div class="user-av" style="width:32px;height:32px;font-size:12px;cursor:pointer" onclick="window.location.href='pengaturan'">
        <?= $initial ?: 'AD' ?>
      </div> -->
    </div>
  </div>

  <div class="content">
 
 