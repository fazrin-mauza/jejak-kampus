<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/navigasi.php';
?>

<style>
/* Warna pastel untuk stat card */
.stat-card.ora { background: linear-gradient(135deg, #FFF5EB 0%, #FFE8D6 100%); border-left: 4px solid #F97316; }
.stat-card.blue { background: linear-gradient(135deg, #EBF5FF 0%, #D6EAFF 100%); border-left: 4px solid #3B82F6; }
.stat-card.green { background: linear-gradient(135deg, #ECFDF5 0%, #D8F3E8 100%); border-left: 4px solid #10B981; }
.stat-card.purple { background: linear-gradient(135deg, #F5F3FF 0%, #EDE9FE 100%); border-left: 4px solid #8B5CF6; }

/* Warna icon SVG */
.stat-icon-svg { width: 28px; height: 28px; margin-bottom: 8px; }
.stat-icon-svg.ora svg { stroke: #F97316; }
.stat-icon-svg.blue svg { stroke: #3B82F6; }
.stat-icon-svg.green svg { stroke: #10B981; }
.stat-icon-svg.purple svg { stroke: #8B5CF6; }

/* Badge warna pastel */
.badge-up { background: #D1FAE5; color: #059669; }
.badge-down { background: #FEE2E2; color: #DC2626; }

/* Hover effect card */
.stat-card { transition: all 0.3s ease; cursor: default; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
</style>

<div class="section active" id="sec-dashboard">
  <div class="page-header">
    <div>
      <div class="page-title" id="welcome-text">Selamat Datang, Mahasiswa 👋</div>
      <div class="page-subtitle" id="subtitle-text">Memuat data...</div>
    </div>
    <div class="header-actions">
      <button class="btn btn-secondary btn-sm" onclick="refreshDashboard()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M20.49 15a9 9 0 0 1-14.85 3.36L1 14"/>
        </svg>
        Segarkan
      </button>
      <span class="pill pill-green" id="status-badge">● Aktif</span>
    </div>
  </div>

  <!-- Stats Grid dengan SVG Professional -->
  <div class="stats-grid">
    <!-- Card 1: Total SKS -->
    <div class="stat-card ora">
      <div class="stat-icon-svg ora">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
          <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
          <line x1="8" y1="7" x2="8" y2="7.01"/>
          <line x1="16" y1="7" x2="16" y2="7.01"/>
        </svg>
      </div>
      <div class="stat-label">Total SKS</div>
      <div class="stat-value" id="stat-sks">--</div>
      <div class="stat-sub">Semester ini</div>
      <span class="stat-badge badge-info" id="stat-mk-badge">-- Mata Kuliah</span>
    </div>

    <!-- Card 2: Mata Kuliah -->
    <div class="stat-card blue">
      <div class="stat-icon-svg blue">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
          <line x1="9" y1="9" x2="15" y2="15"/>
          <line x1="15" y1="9" x2="9" y2="15"/>
          <circle cx="12" cy="12" r="2"/>
        </svg>
      </div>
      <div class="stat-label">Mata Kuliah</div>
      <div class="stat-value" id="stat-mk">--</div>
      <div class="stat-sub">Aktif semester ini</div>
      <span class="stat-badge badge-info" id="stat-semester">--</span>
    </div>

    <!-- Card 3: Kehadiran -->
    <div class="stat-card green">
      <div class="stat-icon-svg green">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 12h-4l-3 9-4-18-3 9H2"/>
          <path d="M12 2v4M12 18v4"/>
        </svg>
      </div>
      <div class="stat-label">Rata-rata Kehadiran</div>
      <div class="stat-value" id="stat-persen">--%</div>
      <div class="stat-sub">Dari semua mata kuliah</div>
      <span class="stat-badge" id="status-batas"></span>
    </div>

    <!-- Card 4: Total Kehadiran -->
    <div class="stat-card purple">
      <div class="stat-icon-svg purple">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
          <path d="M12 3v2M12 9v2"/>
        </svg>
      </div>
      <div class="stat-label">Total Kehadiran</div>
      <div class="stat-value" id="stat-hadir">--<span style="font-size:16px">/--</span></div>
      <div class="stat-sub">Dari semua sesi</div>
      <span class="stat-badge" id="stat-alpha-izin"></span>
    </div>
  </div>

  <!-- Sesi Aktif Banner -->
  <div id="sesi-aktif-container" style="display: none;">
    <div class="sesi-banner">
      <div class="sesi-pulse"></div>
      <div class="sesi-info">
        <div class="sesi-title" id="sesi-title">🟢 Sesi Aktif: --</div>
        <div class="sesi-meta" id="sesi-meta">--</div>
      </div>
      <a href="absensi" id="sesi-link" class="btn btn-primary btn-sm">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
          <line x1="9" y1="9" x2="15" y2="15"/>
          <line x1="15" y1="9" x2="9" y2="15"/>
        </svg>
        Absen Sekarang
      </a>
    </div>
  </div>

  <!-- Grid 2 Kolom -->
  <div class="grid-2" style="margin-bottom: 18px">
    <!-- Jadwal Hari Ini -->
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
              <line x1="16" y1="2" x2="16" y2="6"/>
              <line x1="8" y1="2" x2="8" y2="6"/>
              <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            Jadwal Hari Ini
          </div>
          <div class="card-sub" id="today-date">--</div>
        </div>
        <a href="jadwal" class="btn btn-secondary btn-sm">Semua Jadwal</a>
      </div>
      <div id="jadwal-hari-ini-container">
        <div style="text-align:center;padding:30px;color:gray">⏳ Memuat jadwal...</div>
      </div>
    </div>

    <!-- Aksi Cepat & Notifikasi -->
    <div style="display:flex;flex-direction:column;gap:14px">
      <div class="card">
        <div class="card-title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
            <polygon points="13 2 3 7 3 17 13 22 23 17 23 7 13 2"/>
            <line x1="13" y1="22" x2="13" y2="12"/>
          </svg>
          Aksi Cepat 
        </div><br>
        <div style="display:flex;flex-direction:column;gap:8px">
          <a href="absensi" class="btn btn-primary" style="justify-content:flex-start">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
              <line x1="9" y1="9" x2="15" y2="15"/>
              <line x1="15" y1="9" x2="9" y2="15"/>
            </svg>
            Scan QR Absensi
          </a>
          <a href="izin" class="btn btn-secondary" style="justify-content:flex-start">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
              <line x1="16" y1="13" x2="8" y2="13"/>
              <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            Ajukan Izin / Sakit
          </a>
          <a href="jadwal" class="btn btn-secondary" style="justify-content:flex-start">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
              <line x1="16" y1="2" x2="16" y2="6"/>
              <line x1="8" y1="2" x2="8" y2="6"/>
              <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            Lihat Jadwal Lengkap
          </a>
          <a href="rekap" class="btn btn-info" style="justify-content:flex-start">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
              <line x1="18" y1="20" x2="18" y2="10"/>
              <line x1="12" y1="20" x2="12" y2="4"/>
              <line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            Cek Rekap Kehadiran
          </a>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            Notifikasi
          </div>
          <span class="pill pill-red" id="notif-count">0</span>
        </div>
        <div id="notifikasi-container">
          <div style="padding:20px;text-align:center;color:gray">Memuat...</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Riwayat Absensi -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/>
          <line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
        Riwayat Absensi Terbaru
      </div>
      <a href="rekap" class="btn btn-secondary btn-sm">Lihat Semua</a>
    </div>
    <div class="tbl-wrap">
      <table class="tabel">
        <thead><tr><th>Tanggal</th><th>Mata Kuliah</th><th>Pertemuan</th><th>Status</th><th>Waktu</th></tr></thead>
        <tbody id="riwayat-body"><tr><td colspan="5" style="text-align:center;padding:40px">⏳ Memuat data...</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<script>
let refreshTimer = null;

document.addEventListener('DOMContentLoaded', function() {
  loadDashboard();
  if (refreshTimer) clearInterval(refreshTimer);
  refreshTimer = setInterval(loadDashboard, 60000);
});

function loadDashboard() {
  fetch('api/dashboard.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_dashboard'
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      // Header
      const firstName = response.mahasiswa.nama ? response.mahasiswa.nama.split(' ')[0] : 'Mahasiswa';
      document.getElementById('welcome-text').innerHTML = `Selamat Datang, ${firstName} 👋`;
      document.getElementById('subtitle-text').innerHTML = `NIM: ${response.mahasiswa.nim || '-'} · ${response.mahasiswa.kelas || '-'} · ${response.tahun_akademik || '-'}`;
      
      // Stats
      document.getElementById('stat-sks').textContent = response.statistik.total_sks || 0;
      document.getElementById('stat-mk').textContent = response.statistik.total_mk || 0;
      document.getElementById('stat-mk-badge').innerHTML = `${response.statistik.total_mk || 0} Mata Kuliah`;
      document.getElementById('stat-semester').innerHTML = response.tahun_akademik || '-';
      document.getElementById('stat-persen').innerHTML = `${response.statistik.persen_kehadiran || 0}%`;
      document.getElementById('stat-hadir').innerHTML = `${response.statistik.total_kehadiran || 0}/${response.statistik.total_sesi || 0}`;
      document.getElementById('stat-alpha-izin').innerHTML = `● ${(response.statistik.alpha || 0) + (response.statistik.izin || 0)} alpha/izin`;
      
      const batasEl = document.getElementById('status-batas');
      const persen = response.statistik.persen_kehadiran || 0;
      const minKehadiran = response.statistik.min_kehadiran || 75;
      if (response.statistik.di_atas_batas) {
        batasEl.innerHTML = `<span class="badge-up">↑ ${persen}% ≥ ${minKehadiran}%</span>`;
      } else {
        batasEl.innerHTML = `<span class="badge-down">↓ ${persen}% < ${minKehadiran}%</span>`;
      }
      
      // Sesi Aktif
      if (response.sesi_aktif) {
        document.getElementById('sesi-aktif-container').style.display = 'block';
        document.getElementById('sesi-title').innerHTML = `🟢 Sesi Aktif: ${response.sesi_aktif.nama_mk} — P-${response.sesi_aktif.pertemuan_ke}`;
        document.getElementById('sesi-meta').innerHTML = `${response.sesi_aktif.nama_dosen} · ${response.sesi_aktif.nama_ruangan || '-'}`;
        document.getElementById('sesi-link').href = `absensi?scan=${response.sesi_aktif.sesi_id}`;
      } else {
        document.getElementById('sesi-aktif-container').style.display = 'none';
      }
      
      // Jadwal Hari Ini
      const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
      const today = new Date();
      document.getElementById('today-date').innerHTML = `${days[today.getDay()]}, ${today.toLocaleDateString('id-ID')}`;
      
      const jadwalContainer = document.getElementById('jadwal-hari-ini-container');
      if (response.jadwal_hari_ini && response.jadwal_hari_ini.length > 0) {
        let html = '';
        response.jadwal_hari_ini.forEach(j => {
          const mulai = j.jam_mulai ? j.jam_mulai.substring(0,5) : '--:--';
          const selesai = j.jam_selesai ? j.jam_selesai.substring(0,5) : '--:--';
          html += `<div class="schedule-item">
            <div class="schedule-time">${mulai}–${selesai}</div>
            <div class="schedule-body">
              <div class="schedule-matkul">${j.nama_mk}</div>
              <div class="schedule-meta">👨‍🏫 ${j.nama_dosen} · 📍 ${j.nama_ruangan || '-'}</div>
            </div>
            <div class="schedule-actions"><span class="pill pill-gray">Belum</span></div>
          </div>`;
        });
        jadwalContainer.innerHTML = html;
      } else {
        jadwalContainer.innerHTML = '<div style="text-align:center;padding:30px;color:gray">Tidak ada jadwal hari ini</div>';
      }
      
      // Notifikasi
      const notifContainer = document.getElementById('notifikasi-container');
      const notifCount = document.getElementById('notif-count');
      if (response.notifikasi && response.notifikasi.length > 0) {
        notifCount.textContent = `${response.notifikasi.length}`;
        let html = '';
        response.notifikasi.forEach(n => {
          let title = n.title;
          if (title.includes('Izin Izin')) title = title.replace('Izin Izin', 'Izin');
          html += `<div style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid #e5e7eb">
            <div style="width:32px;height:32px;border-radius:8px;background:#FEF3C7;display:flex;align-items:center;justify-content:center">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <div><div style="font-weight:700;font-size:13px">${title}</div><div style="font-size:11px;color:gray">${n.message}</div></div>
          </div>`;
        });
        notifContainer.innerHTML = html;
      } else {
        notifCount.textContent = '0';
        notifContainer.innerHTML = '<div style="padding:20px;text-align:center;color:gray">Tidak ada notifikasi</div>';
      }
      
      // Riwayat
      const riwayatBody = document.getElementById('riwayat-body');
      if (response.riwayat_terbaru && response.riwayat_terbaru.length > 0) {
        let html = '';
        response.riwayat_terbaru.forEach(r => {
          let statusClass = 'pill-gray';
          let statusText = r.status;
          if (r.status === 'hadir') { statusClass = 'pill-green'; statusText = 'Hadir'; }
          else if (r.status === 'telat') { statusClass = 'pill-yellow'; statusText = 'Telat'; }
          else if (r.status === 'alpha') { statusClass = 'pill-red'; statusText = 'Alpha'; }
          else if (r.status === 'izin') { statusClass = 'pill-blue'; statusText = 'Izin'; }
          
          let tgl = r.tanggal ? r.tanggal.split('-').reverse().join('/') : '-';
          let waktu = '-';
          if (r.waktu_absen) {
            let parts = r.waktu_absen.split(' ');
            if (parts.length > 1) waktu = parts[1].substring(0,5);
          }
          
          html += `<tr>
            <td>${tgl}</td>
            <td>${r.nama_mk || '-'}</td>
            <td>Ke-${r.pertemuan_ke || '-'}</td>
            <td><span class="${statusClass}">${statusText}</span></td>
            <td>${waktu}</td>
          </tr>`;
        });
        riwayatBody.innerHTML = html;
      } else {
        riwayatBody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:30px">Belum ada riwayat</td></tr>';
      }
    } else {
      showNotification(response.msg || 'Gagal memuat data', 'kesalahan');
    }
  })
  .catch(err => {
    console.error(err);
    showNotification('Koneksi bermasalah', 'kesalahan');
  });
}

function refreshDashboard() {
  showNotification('Memuat ulang...', 'informasi');
  loadDashboard();
  setTimeout(function() {
    showNotification('Memuat ulang selesai', 'berhasil');
  }, 3000);
}

function showNotification(msg, type) {
  let bg = '#2563eb';
  if (type === 'kesalahan') bg = '#dc2626';
  else if (type === 'berhasil') bg = '#059669';
  const el = document.createElement('div');
  el.className = 'custom-notification';
  el.style.cssText = `position:fixed;top:20px;right:20px;padding:12px 20px;background:${bg};color:#fff;border-radius:8px;z-index:10000;font-size:13px;animation:slideInRight 0.3s ease`;
  el.innerHTML = msg;
  document.body.appendChild(el);
  setTimeout(() => el.remove(), 3000);
}

// Style tambahan untuk schedule-item
const style = document.createElement('style');
style.textContent = `
  @keyframes slideInRight { from { transform: translateX(100%); opacity:0; } to { transform: translateX(0); opacity:1; } }
  .schedule-item { display: flex; padding: 12px 0; border-bottom: 1px solid #e5e7eb; gap: 12px; align-items: center; flex-wrap: wrap; }
  .schedule-time { min-width: 70px; font-weight: 600; font-size: 13px; }
  .schedule-body { flex: 1; }
  .schedule-matkul { font-weight: 700; font-size: 14px; margin-bottom: 4px; }
  .schedule-meta { font-size: 11px; color: gray; }
  .schedule-actions { display: flex; gap: 8px; align-items: center; }
  @media (max-width: 600px) { .schedule-item { flex-direction: column; align-items: flex-start; } }
`;
document.head.appendChild(style);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>