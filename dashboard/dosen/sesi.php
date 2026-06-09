<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/navigasi.php';
?>

<!-- ═══════════════════════════════════════════════
     SESI PRESENSI & KODE QR - Dinamis
═══════════════════════════════════════════════ -->
<div id="app-sesi">
  <div class="page-header">
    <div>
      <div class="page-subtitle">Kelola sesi presensi dan pantau kehadiran secara langsung</div>
    </div>
    <div class="header-actions">
      <button class="btn btn-primary" onclick="window.location.href='jadwal.php'">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px;">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Buat Sesi Baru
      </button>
    </div>
  </div>

  <!-- Bilah Penyaring -->
  <div class="filter-bar">
    <label>Mata Kuliah</label>
    <select id="filter-mk" onchange="loadSesiList()">
      <option value="">Semua Mata Kuliah</option>
    </select>
    <label style="margin-left:8px">Kelas</label>
    <select id="filter-kelas" onchange="loadSesiList()">
      <option value="">Semua Kelas</option>
    </select>
    <label style="margin-left:8px">Status</label>
    <select id="filter-status" onchange="loadSesiList()">
      <option value="">Semua</option>
      <option value="aktif" selected>Aktif</option>
      <option value="selesai">Selesai</option>
    </select>
    <button class="btn btn-secondary btn-sm" onclick="loadSesiList()">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
      Terapkan
    </button>
  </div>

  <!-- Sesi Aktif / Kartu QR -->
  <div id="sesi-aktif-container" style="margin-bottom:18px; display:none;">
    <div style="font-size:12px;font-weight:800;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px">
      <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#10b981;margin-right:8px;animation:blink 1s infinite"></span>
      Sesi Aktif Saat Ini
    </div>
    <div class="grid-2">
      <!-- Kartu QR -->
      <div class="qr-card">
        <div style="font-size:11px;font-weight:800;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.05em;margin-bottom:16px;position:relative;z-index:1">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
            <rect x="7" y="7" width="3" height="3"></rect>
            <rect x="14" y="7" width="3" height="3"></rect>
            <rect x="7" y="14" width="3" height="3"></rect>
            <rect x="14" y="14" width="3" height="3"></rect>
          </svg>
          Kode QR Presensi
        </div>
        <div class="qr-frame" id="qr-container" style="cursor:pointer; position:relative;" onclick="perbesarQR()" title="Klik untuk memperbesar">
          <div style="color:white;text-align:center">Memuat Kode QR...</div>
          <div style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.5); color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 14px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="15 3 21 3 21 9"></polyline>
              <polyline points="9 21 3 21 3 15"></polyline>
              <line x1="21" y1="3" x2="14" y2="10"></line>
              <line x1="3" y1="21" x2="10" y2="14"></line>
            </svg>
          </div>
        </div>
        <div class="qr-timer">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
            <polyline points="1 4 1 10 7 10"></polyline>
            <polyline points="23 20 23 14 17 14"></polyline>
            <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path>
          </svg>
          <span>Pembaruan dalam</span>
          <span class="qr-timer-count" id="qr-countdown">30</span>
          <span>detik</span>
        </div>
        <div class="qr-info" style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <div><div class="qr-info-label">Mata Kuliah</div><div class="qr-info-val" id="qr-mk">-</div></div>
          <div><div class="qr-info-label">Kelas</div><div class="qr-info-val" id="qr-kelas">-</div></div>
          <div><div class="qr-info-label">Pertemuan</div><div class="qr-info-val" id="qr-pertemuan">-</div></div>
          <div><div class="qr-info-label">Waktu</div><div class="qr-info-val" id="qr-waktu">-</div></div>
        </div>
        <div style="display:flex;gap:8px;margin-top:18px;position:relative;z-index:1">
          <button class="btn btn-secondary btn-sm" style="flex:1" onclick="refreshQR()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
              <polyline points="1 4 1 10 7 10"></polyline>
              <polyline points="23 20 23 14 17 14"></polyline>
              <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path>
            </svg>
            Perbarui
          </button>
          <button class="btn btn-danger btn-sm" style="flex:1" onclick="tutupSesiAktif()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            Tutup Sesi
          </button>
        </div>
      </div>

      <!-- Ringkasan -->
      <div>
        <div class="card" style="margin-bottom:14px">
          <div class="card-title" style="margin-bottom:12px">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px;">
              <line x1="18" y1="20" x2="18" y2="10"></line>
              <line x1="12" y1="20" x2="12" y2="4"></line>
              <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            Ringkasan Kehadiran
          </div>
          <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px">
            <div style="background:var(--green-bg);border-radius:var(--r-sm);padding:12px;text-align:center">
              <div style="font-size:10px;font-weight:800;color:var(--green);text-transform:uppercase">Hadir</div>
              <div style="font-size:26px;font-weight:800;color:var(--green)" id="stat-hadir">0</div>
            </div>
            <div style="background:var(--yellow-bg);border-radius:var(--r-sm);padding:12px;text-align:center">
              <div style="font-size:10px;font-weight:800;color:var(--yellow);text-transform:uppercase">Izin</div>
              <div style="font-size:26px;font-weight:800;color:var(--yellow)" id="stat-izin">0</div>
            </div>
            <div style="background:#FFF3E0;border-radius:var(--r-sm);padding:12px;text-align:center">
              <div style="font-size:10px;font-weight:800;color:var(--ora4);text-transform:uppercase">Sakit</div>
              <div style="font-size:26px;font-weight:800;color:var(--ora4)" id="stat-sakit">0</div>
            </div>
            <div style="background:var(--red-bg);border-radius:var(--r-sm);padding:12px;text-align:center">
              <div style="font-size:10px;font-weight:800;color:var(--red);text-transform:uppercase">Tanpa Keterangan</div>
              <div style="font-size:26px;font-weight:800;color:var(--red)" id="stat-alpha">0</div>
            </div>
          </div>
          <div style="margin-top:12px;padding:10px;background:var(--surface2);border-radius:var(--r-sm);text-align:center">
            <span style="font-size:12px;font-weight:700;color:var(--text3)">Belum Presensi: </span>
            <span style="font-size:15px;font-weight:800;color:var(--text)" id="stat-belum">0</span>
            <span style="font-size:11px;color:var(--text3)"> dari <span id="stat-total-mhs">0</span> mahasiswa</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Daftar Sesi -->
  <div style="margin-bottom:18px">
    <div style="font-size:12px;font-weight:800;color:var(--text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px;">
        <line x1="8" y1="6" x2="21" y2="6"></line>
        <line x1="8" y1="12" x2="21" y2="12"></line>
        <line x1="8" y1="18" x2="21" y2="18"></line>
        <line x1="3" y1="6" x2="3.01" y2="6"></line>
        <line x1="3" y1="12" x2="3.01" y2="12"></line>
        <line x1="3" y1="18" x2="3.01" y2="18"></line>
      </svg>
      Daftar Sesi
    </div>
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>No</th><th>Tanggal</th><th>Mata Kuliah</th><th>Kelas</th><th>Pertemuan</th><th>Status</th><th>Hadir</th><th>Tindakan</th>
          </tr>
        </thead>
        <tbody id="sesi-list-body">
          <tr><td colspan="8" style="text-align:center;padding:40px">Memuat data...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tabel Pemantauan -->
  <div class="card" id="monitoring-card" style="display:none;">
    <div class="card-header">
      <div class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px;">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
          <circle cx="9" cy="7" r="4"></circle>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
        Pemantauan Presensi <span id="monitoring-title">-</span>
      </div>
      <div style="display:flex;gap:6px">
        <button class="btn btn-secondary btn-sm" onclick="loadMonitoring()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
            <polyline points="1 4 1 10 7 10"></polyline>
            <polyline points="23 20 23 14 17 14"></polyline>
            <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path>
          </svg>
          Perbarui
        </button>
        <button class="btn btn-success btn-sm" onclick="exportMonitoring()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
          </svg>
          Ekspor
        </button>
      </div>
    </div>

    <!-- Bilah Tindakan Massal -->
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:10px 14px;background:var(--surface2);border-radius:var(--r-sm);margin-bottom:14px;border:1px solid var(--border)">
      <button class="btn btn-success btn-sm" onclick="hadirSemua()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        Hadir Semua
      </button>
      <div style="width:1px;height:24px;background:var(--border-s)"></div>
      <span style="font-size:12px;font-weight:700;color:var(--text3)">Atur Terpilih:</span>
      <button class="btn btn-success btn-xs" onclick="bulkSet('hadir')">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        Hadir
      </button>
      <button class="btn btn-warn btn-xs" onclick="bulkSet('izin')">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
        </svg>
        Izin
      </button>
      <button class="btn btn-xs" style="background:#FFF3E0;color:var(--ora4);border:1px solid rgba(224,107,0,.2)" onclick="bulkSet('sakit')">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;">
          <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
        </svg>
        Sakit
      </button>
      <button class="btn btn-danger btn-xs" onclick="bulkSet('alpha')">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
        Tanpa Keterangan
      </button>
      <div style="margin-left:auto;font-size:12px;font-weight:700;color:var(--text3)" id="sel-count">0 dipilih</div>
    </div>

    <div class="tbl-wrap">
      <table id="absen-table">
        <thead>
          <tr>
            <th style="width:36px"><input type="checkbox" id="check-all" onchange="toggleAll(this)"></th>
            <th>No</th><th>NIM</th><th>Nama Mahasiswa</th><th>Status</th><th>Waktu Presensi</th><th>Tindakan Manual</th>
          </tr>
        </thead>
        <tbody id="monitoring-body">
          <tr><td colspan="7" style="text-align:center;padding:40px">Pilih sesi untuk melihat pemantauan</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════
     MODAL: Perbesar Kode QR
═══════════════════════════════════════════════ -->
<div class="modal-overlay" id="modal-qr-zoom">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px;">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
          <rect x="7" y="7" width="3" height="3"></rect>
          <rect x="14" y="7" width="3" height="3"></rect>
          <rect x="7" y="14" width="3" height="3"></rect>
          <rect x="14" y="14" width="3" height="3"></rect>
        </svg>
        Kode QR Presensi - Tampilan Diperbesar
      </div>
      <button class="btn-close-modal" onclick="closeModal('modal-qr-zoom')">&times;</button>
    </div>
    <div class="modal-body" style="text-align:center">
      <div id="qr-zoom-info" style="margin-bottom:20px">
        <div style="font-weight:700;font-size:18px" id="qr-zoom-mk"></div>
        <div style="color:var(--text3);font-size:14px" id="qr-zoom-kelas"></div>
        <div style="color:var(--text3);font-size:13px;margin-top:6px" id="qr-zoom-pertemuan"></div>
      </div>
      <div style="background:white;padding:30px;border-radius:16px;display:inline-block;box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
        <img id="qr-image-zoom" src="" alt="Kode QR Diperbesar" style="width:400px;height:400px">
      </div>
      <div style="margin-top:16px;color:var(--text2);font-size:14px">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="16" x2="12" y2="12"></line>
          <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>
        Pindai kode QR ini menggunakan kamera perangkat seluler
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-qr-zoom')">Tutup</button>
      <button class="btn btn-primary" onclick="cetakQR()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
          <polyline points="6 9 6 2 18 2 18 9"></polyline>
          <path d="M6 12H4a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2"></path>
          <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        Cetak Kode QR
      </button>
    </div>
  </div>
</div>

<script>
// ════════════════════════════════════════════════
// VARIABEL GLOBAL
// ════════════════════════════════════════════════
let currentSesiAktif = null;
let currentMonitoringSesiId = null;
let qrTimer = null;
let monitoringData = [];
let realtimeInterval = null;
let currentQRCodeData = '';

// ════════════════════════════════════════════════
// INISIALISASI
// ════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
  loadFilterOptions();
  loadSesiAktif();
  loadSesiList();
  
  // Mulai pemantauan real-time
  startRealtimeMonitoring();
});

// ════════════════════════════════════════════════
// PEMANTAUAN REAL-TIME
// ════════════════════════════════════════════════
function startRealtimeMonitoring() {
  // Perbarui setiap 10 detik
  if (realtimeInterval) clearInterval(realtimeInterval);
  
  realtimeInterval = setInterval(() => {
    // Perbarui sesi aktif jika ada
    if (currentSesiAktif) {
      updateSesiAktifStats();
    }
    
    // Perbarui monitoring jika sedang melihat monitoring
    if (currentMonitoringSesiId) {
      loadMonitoring(true); // true = silent update
    }
  }, 10000); // 10 detik
}

function updateSesiAktifStats() {
  if (!currentSesiAktif || !currentSesiAktif.sesi_id) return;
  
  fetch('api/sesi.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=get_sesi_stats&sesi_id=${currentSesiAktif.sesi_id}`
  })
  .then(res => res.json())
  .then(response => {
    if (response.success && response.data) {
      // Perbarui statistik tanpa refresh halaman
      document.getElementById('stat-hadir').textContent = response.data.stat_hadir || 0;
      document.getElementById('stat-izin').textContent = response.data.stat_izin || 0;
      document.getElementById('stat-sakit').textContent = response.data.stat_sakit || 0;
      document.getElementById('stat-alpha').textContent = response.data.stat_alpha || 0;
      document.getElementById('stat-belum').textContent = response.data.stat_belum || 0;
      document.getElementById('stat-total-mhs').textContent = response.data.total_mhs || 0;
      
      // Jika sesi sudah tidak aktif, muat ulang
      if (!response.data.is_active) {
        loadSesiAktif();
        loadSesiList();
      }
    }
  });
}

// ════════════════════════════════════════════════
// MEMUAT OPSI PENYARING
// ════════════════════════════════════════════════
function loadFilterOptions() {
  fetch('api/sesi.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_filter_options'
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      let mkHtml = '<option value="">Semua Mata Kuliah</option>';
      response.data.mk.forEach(mk => {
        mkHtml += `<option value="${mk.id}">${mk.nama_mk}</option>`;
      });
      document.getElementById('filter-mk').innerHTML = mkHtml;
      
      let kelasHtml = '<option value="">Semua Kelas</option>';
      response.data.kelas.forEach(k => {
        kelasHtml += `<option value="${k.id}">${k.nama_kelas}</option>`;
      });
      document.getElementById('filter-kelas').innerHTML = kelasHtml;
    }
  });
}

// ════════════════════════════════════════════════
// MEMUAT SESI AKTIF
// ════════════════════════════════════════════════
function loadSesiAktif() {
  fetch('api/sesi.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_sesi_aktif'
  })
  .then(res => res.json())
  .then(response => {
    if (response.success && response.data) {
      currentSesiAktif = response.data;
      renderSesiAktif(response.data);
      document.getElementById('sesi-aktif-container').style.display = 'block';
      startQRTimer();
    } else {
      currentSesiAktif = null;
      document.getElementById('sesi-aktif-container').style.display = 'none';
      if (qrTimer) {
        clearInterval(qrTimer);
        qrTimer = null;
      }
    }
  });
}

function renderSesiAktif(data) {
  document.getElementById('qr-mk').textContent = data.nama_mk || '-';
  document.getElementById('qr-kelas').textContent = data.nama_kelas || '-';
  document.getElementById('qr-pertemuan').textContent = `Ke-${data.pertemuan_ke || '-'}`;
  document.getElementById('qr-waktu').textContent = data.jam || '-';
  
  document.getElementById('stat-hadir').textContent = data.stat_hadir || 0;
  document.getElementById('stat-izin').textContent = data.stat_izin || 0;
  document.getElementById('stat-sakit').textContent = data.stat_sakit || 0;
  document.getElementById('stat-alpha').textContent = data.stat_alpha || 0;
  document.getElementById('stat-belum').textContent = data.stat_belum || 0;
  document.getElementById('stat-total-mhs').textContent = data.total_mhs || 0;
  
  generateQR(data.sesi_id);
}

function generateQR(sesiId) {
  const token = btoa(`sesi:${sesiId}:${Date.now()}`);
  const qrData = JSON.stringify({ sesi_id: sesiId, token: token, timestamp: Date.now() });
  currentQRCodeData = qrData;
  
  const qrUrl = `https://quickchart.io/qr?text=${encodeURIComponent(qrData)}&size=200`;
  
  document.getElementById('qr-container').innerHTML = `
    <img src="${qrUrl}" alt="Kode QR" style="width:150px;height:150px">
    <div style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.5); color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 14px;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="15 3 21 3 21 9"></polyline>
        <polyline points="9 21 3 21 3 15"></polyline>
        <line x1="21" y1="3" x2="14" y2="10"></line>
        <line x1="3" y1="21" x2="10" y2="14"></line>
      </svg>
    </div>
  `;
  
  fetch('api/sesi.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=update_qr&sesi_id=${sesiId}&qr_code=${encodeURIComponent(qrData)}`
  });
}

function refreshQR() {
  if (currentSesiAktif) {
    generateQR(currentSesiAktif.sesi_id);
    resetQRTimer();
    showNotification('Kode QR berhasil diperbarui', 'berhasil');
  }
}

function perbesarQR() {
  if (!currentSesiAktif || !currentQRCodeData) return;
  
  // Mengisi data modal zoom
  document.getElementById('qr-zoom-mk').textContent = document.getElementById('qr-mk').textContent;
  document.getElementById('qr-zoom-kelas').textContent = document.getElementById('qr-kelas').textContent;
  document.getElementById('qr-zoom-pertemuan').textContent = document.getElementById('qr-pertemuan').textContent;
  
  // Menghasilkan QR yang lebih besar
  const qrZoomUrl = `https://quickchart.io/qr?text=${encodeURIComponent(currentQRCodeData)}&size=400`;
  document.getElementById('qr-image-zoom').src = qrZoomUrl;
  
  openModal('modal-qr-zoom');
}

function cetakQR() {
  const qrImageSrc = document.getElementById('qr-image-zoom').src;
  const mk = document.getElementById('qr-zoom-mk').textContent;
  const kelas = document.getElementById('qr-zoom-kelas').textContent;
  const pertemuan = document.getElementById('qr-zoom-pertemuan').textContent;
  
  const printWindow = window.open('', '_blank');
  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Cetak Kode QR Presensi</title>
      <style>
        body {
          font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
          display: flex;
          justify-content: center;
          align-items: center;
          min-height: 100vh;
          margin: 0;
          padding: 20px;
        }
        .print-container {
          text-align: center;
          max-width: 500px;
        }
        .qr-title {
          font-size: 18px;
          font-weight: 700;
          margin-bottom: 8px;
        }
        .qr-subtitle {
          font-size: 14px;
          color: #666;
          margin-bottom: 4px;
        }
        .qr-image {
          width: 350px;
          height: 350px;
          margin: 20px 0;
          border: 2px solid #e5e7eb;
          border-radius: 12px;
          padding: 20px;
        }
        @media print {
          body { margin: 0; }
        }
      </style>
    </head>
    <body>
      <div class="print-container">
        <div class="qr-title">${mk}</div>
        <div class="qr-subtitle">${kelas}</div>
        <div class="qr-subtitle">${pertemuan}</div>
        <img src="${qrImageSrc}" alt="Kode QR" class="qr-image">
        <p style="font-size:12px;color:#999;">Pindai untuk melakukan presensi • Dicetak pada ${new Date().toLocaleDateString('id-ID')}</p>
      </div>
    </body>
    </html>
  `);
  
  printWindow.document.close();
  printWindow.focus();
  setTimeout(() => {
    printWindow.print();
    printWindow.close();
  }, 500);
}

function startQRTimer() {
  let seconds = 30;
  const timerEl = document.getElementById('qr-countdown');
  
  if (qrTimer) clearInterval(qrTimer);
  
  qrTimer = setInterval(() => {
    seconds--;
    timerEl.textContent = seconds;
    if (seconds <= 0) {
      if (currentSesiAktif) generateQR(currentSesiAktif.sesi_id);
      seconds = 30;
    }
  }, 1000);
}

function resetQRTimer() {
  if (qrTimer) clearInterval(qrTimer);
  startQRTimer();
}

function tutupSesiAktif() {
  if (!currentSesiAktif) return;
  
  if (confirm('Tutup sesi presensi ini? Mahasiswa yang belum presensi akan otomatis ditandai Tanpa Keterangan.')) {
    fetch('api/sesi.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `action=tutup_sesi&sesi_id=${currentSesiAktif.sesi_id}`
    })
    .then(res => res.json())
    .then(response => {
      if (response.success) {
        showNotification('Sesi berhasil ditutup', 'berhasil');
        if (qrTimer) clearInterval(qrTimer);
        loadSesiAktif();
        loadSesiList();
      } else {
        showNotification(response.msg || 'Gagal menutup sesi', 'kesalahan');
      }
    });
  }
}

// ════════════════════════════════════════════════
// MEMUAT DAFTAR SESI
// ════════════════════════════════════════════════
function loadSesiList() {
  const mk = document.getElementById('filter-mk').value;
  const kelas = document.getElementById('filter-kelas').value;
  const status = document.getElementById('filter-status').value;
  
  let body = 'action=get_sesi_list';
  if (mk) body += `&mk_id=${mk}`;
  if (kelas) body += `&kelas_id=${kelas}`;
  if (status) body += `&status=${status}`;
  
  fetch('api/sesi.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: body
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      renderSesiList(response.data);
    }
  });
}

function renderSesiList(data) {
  const tbody = document.getElementById('sesi-list-body');
  
  if (data.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px">Tidak ada sesi</td></tr>';
    return;
  }
  
  let html = '';
  data.forEach((s, i) => {
    const statusClass = s.status === 'aktif' ? 'pill-green' : 'pill-gray';
    const statusDot = s.status === 'aktif' ? 
      '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#10b981;margin-right:6px;animation:blink 1s infinite"></span>' : 
      '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#6b7280;margin-right:6px"></span>';
    const statusText = s.status === 'aktif' ? 'Aktif' : 'Selesai';
    
    html += `<tr>
      <td>${i+1}</td>
      <td>${s.tanggal || '-'}</td>
      <td style="font-weight:600">${s.nama_mk}</td>
      <td>${s.nama_kelas}</td>
      <td>P-${s.pertemuan_ke}</td>
      <td><span class="pill ${statusClass}">${statusDot}${statusText}</span></td>
      <td>${s.total_hadir || 0}/${s.total_mhs || 0}</td>
      <td>
        <div style="display:flex;gap:5px">
          <button class="btn btn-info btn-xs" onclick="viewMonitoring(${s.id}, '${s.nama_mk} - ${s.nama_kelas} (P-${s.pertemuan_ke})')">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
            Pantau
          </button>
          ${s.status === 'aktif' ? `<button class="btn btn-warning btn-xs" onclick="tutupSesi(${s.id})">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            Tutup
          </button>` : ''}
        </div>
      </td>
    </tr>`;
  });
  
  tbody.innerHTML = html;
}

// ════════════════════════════════════════════════
// PEMANTAUAN
// ════════════════════════════════════════════════
function viewMonitoring(sesiId, title) {
  currentMonitoringSesiId = sesiId;
  document.getElementById('monitoring-title').textContent = title;
  document.getElementById('monitoring-card').style.display = 'block';
  loadMonitoring();
  
  // Gulir ke kartu pemantauan
  document.getElementById('monitoring-card').scrollIntoView({ behavior: 'smooth' });
}

function loadMonitoring(silent = false) {
  if (!currentMonitoringSesiId) return;
  
  if (!silent) {
    document.getElementById('monitoring-body').innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px">Memuat data pemantauan...</td></tr>';
  }
  
  fetch('api/sesi.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=get_monitoring&sesi_id=${currentMonitoringSesiId}`
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      monitoringData = response.data;
      renderMonitoring(response.data);
    }
  });
}

function renderMonitoring(data) {
  const tbody = document.getElementById('monitoring-body');
  
  if (data.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px">Tidak ada mahasiswa</td></tr>';
    return;
  }
  
  const statusClasses = {
    'hadir': 'pill-green',
    'telat': 'pill-ora',
    'izin': 'pill-yellow',
    'sakit': 'pill-ora',
    'alpha': 'pill-red',
    'belum': 'pill-gray'
  };
  
  const statusIcons = {
    'hadir': '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><polyline points="20 6 9 17 4 12"></polyline></svg>',
    'telat': '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
    'izin': '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>',
    'sakit': '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>',
    'alpha': '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
    'belum': '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>'
  };
  
  const statusTexts = {
    'hadir': 'Hadir',
    'telat': 'Telat',
    'izin': 'Izin',
    'sakit': 'Sakit',
    'alpha': 'Tanpa Keterangan',
    'belum': 'Belum Presensi'
  };
  
  let html = '';
  data.forEach((m, i) => {
    const status = m.status || 'belum';
    const statusText = statusIcons[status] + (statusTexts[status] || 'Belum');
    const waktu = m.waktu_absen ? m.waktu_absen.substring(0,5) : '—';
    
    html += `<tr>
      <td><input type="checkbox" class="row-check" value="${m.mahasiswa_id}" onchange="updateSelCount()"></td>
      <td>${i+1}</td>
      <td><code>${m.nim || '-'}</code></td>
      <td style="font-weight:600">${m.nama || '-'}</td>
      <td><span class="status-pill pill ${statusClasses[status] || 'pill-gray'}">${statusText}</span></td>
      <td>${waktu}</td>
      <td>
        <div style="display:flex;gap:5px">
          <select class="status-sel" id="sel-${m.mahasiswa_id}" style="padding:4px 8px;border-radius:6px;font-size:12px">
            <option value="hadir" ${status === 'hadir' ? 'selected' : ''}>Hadir</option>
            <option value="izin" ${status === 'izin' ? 'selected' : ''}>Izin</option>
            <option value="sakit" ${status === 'sakit' ? 'selected' : ''}>Sakit</option>
            <option value="alpha" ${status === 'alpha' ? 'selected' : ''}>Tanpa Keterangan</option>
          </select>
          <button class="btn btn-primary btn-xs" onclick="updateStatus(${m.mahasiswa_id})">Perbarui</button>
        </div>
      </td>
    </tr>`;
  });
  
  tbody.innerHTML = html;
  updateSelCount();
}

function updateStatus(mahasiswaId) {
  const select = document.getElementById(`sel-${mahasiswaId}`);
  const status = select.value;
  
  fetch('api/sesi.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=update_absensi&sesi_id=${currentMonitoringSesiId}&mahasiswa_id=${mahasiswaId}&status=${status}`
  })
  .then(res => res.json())
  .then(response => {
    showNotification(response.msg, response.success ? 'berhasil' : 'kesalahan');
    if (response.success) {
      loadMonitoring(true);
      updateSesiAktifStats();
    }
  });
}

// ════════════════════════════════════════════════
// TINDAKAN MASSAL
// ════════════════════════════════════════════════
function updateSelCount() {
  const checked = document.querySelectorAll('.row-check:checked').length;
  document.getElementById('sel-count').textContent = `${checked} dipilih`;
}

function toggleAll(checkbox) {
  document.querySelectorAll('.row-check').forEach(cb => cb.checked = checkbox.checked);
  updateSelCount();
}

function hadirSemua() {
  if (!currentMonitoringSesiId) return;
  if (!confirm('Tandai HADIR semua mahasiswa yang belum presensi?')) return;
  
  fetch('api/sesi.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=hadir_semua&sesi_id=${currentMonitoringSesiId}`
  })
  .then(res => res.json())
  .then(response => {
    showNotification(response.msg, response.success ? 'berhasil' : 'kesalahan');
    if (response.success) {
      loadMonitoring(true);
      updateSesiAktifStats();
    }
  });
}

function bulkSet(status) {
  if (!currentMonitoringSesiId) return;
  
  const checked = document.querySelectorAll('.row-check:checked');
  if (checked.length === 0) {
    showNotification('Pilih mahasiswa terlebih dahulu', 'kesalahan');
    return;
  }
  
  const ids = Array.from(checked).map(cb => cb.value);
  const statusLabels = { hadir: 'Hadir', izin: 'Izin', sakit: 'Sakit', alpha: 'Tanpa Keterangan' };
  
  if (!confirm(`Tetapkan status "${statusLabels[status] || status}" untuk ${ids.length} mahasiswa terpilih?`)) return;
  
  fetch('api/sesi.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=bulk_update&sesi_id=${currentMonitoringSesiId}&status=${status}&ids=${ids.join(',')}`
  })
  .then(res => res.json())
  .then(response => {
    showNotification(response.msg, response.success ? 'berhasil' : 'kesalahan');
    if (response.success) {
      loadMonitoring(true);
      updateSesiAktifStats();
    }
  });
}

function tutupSesi(sesiId) {
  if (confirm('Tutup sesi ini? Mahasiswa yang belum presensi akan otomatis ditandai Tanpa Keterangan.')) {
    fetch('api/sesi.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `action=tutup_sesi&sesi_id=${sesiId}`
    })
    .then(res => res.json())
    .then(response => {
      showNotification(response.msg, response.success ? 'berhasil' : 'kesalahan');
      if (response.success) {
        loadSesiList();
        loadSesiAktif();
      }
    });
  }
}

function exportMonitoring() {
  if (!currentMonitoringSesiId) return;
  window.location.href = `api/sesi.php?action=export_monitoring&sesi_id=${currentMonitoringSesiId}`;
}

// ════════════════════════════════════════════════
// UTILITAS
// ════════════════════════════════════════════════
function openModal(id) {
  document.getElementById(id).classList.add('active');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('active');
}

function showNotification(m, t = 'informasi') {
  // Warna yang lebih kontras dan terang
  const colors = {
    'berhasil': '#059669',    // Hijau lebih gelap agar kontras
    'kesalahan': '#dc2626',   // Merah lebih terang
    'informasi': '#2563eb',   // Biru lebih gelap
    'peringatan': '#d97706',  // Oranye/kuning gelap
    'info': '#2563eb',
    'success': '#059669',
    'error': '#dc2626'
  };
  
  const bgColor = colors[t] || colors['informasi'];
  
  // Hapus showNotification sebelumnya jika ada
  const existingshowNotification = document.querySelector('.custom-showNotification');
  if (existingshowNotification) existingshowNotification.remove();
  
  const el = document.createElement('div');
  el.className = 'custom-showNotification';
  el.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 14px 24px;
    background: ${bgColor};
    color: #ffffff;
    border-radius: 10px;
    z-index: 10000;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 0.01em;
    animation: slideInRight 0.3s ease-out;
    max-width: 400px;
    word-wrap: break-word;
  `;
  
  // Tambahkan ikon sesuai tipe
  const icons = {
    'berhasil': '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;"><polyline points="20 6 9 17 4 12"></polyline></svg>',
    'kesalahan': '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
    'informasi': '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
    'success': '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;"><polyline points="20 6 9 17 4 12"></polyline></svg>',
    'error': '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
  };
  
  const icon = icons[t] || icons['informasi'] || '';
  el.innerHTML = icon + m;
  
  document.body.appendChild(el);
  
  // Hapus setelah 3.5 detik dengan animasi
  setTimeout(() => {
    el.style.animation = 'slideOutRight 0.3s ease-in';
    setTimeout(() => el.remove(), 300);
  }, 3500);
}

// Bersihkan interval saat halaman ditutup
window.addEventListener('beforeunload', () => {
  if (realtimeInterval) clearInterval(realtimeInterval);
  if (qrTimer) clearInterval(qrTimer);
});

// Tambahkan CSS untuk animasi
const style = document.createElement('style');
style.textContent = `
  @keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
  }
  
  @keyframes slideInRight {
    from {
      transform: translateX(100%);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  
  @keyframes slideOutRight {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(100%);
      opacity: 0;
    }
  }
  
  .pill-yellow { 
    background: #f59e0b; 
    color: #ffffff;
    font-weight: 600;
  }
  
  .pill-red {
    background: #dc2626;
    color: #ffffff;
    font-weight: 600;
  }
  
  .pill-ora {
    background: #ea580c;
    color: #ffffff;
    font-weight: 600;
  }
  
  .pill-green {
    background: #059669;
    color: #ffffff;
    font-weight: 600;
  }
  
  .pill-gray {
    background: #6b7280;
    color: #ffffff;
    font-weight: 600;
  }
  
  .pill-blue {
    background: #2563eb;
    color: #ffffff;
    font-weight: 600;
  }
  
  .custom-showNotification {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    line-height: 1.4;
  }
`;
document.head.appendChild(style);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>