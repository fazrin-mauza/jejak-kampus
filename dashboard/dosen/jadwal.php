<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/navigasi.php';
?>

<!-- ═══════════════════════════════════════════════
     JADWAL MENGAJAR - Dinamis
═══════════════════════════════════════════════ -->
<div id="app-jadwal">
  <div class="page-header">
    <div>
      <div class="page-subtitle">Daftar jadwal perkuliahan semester ini</div>
    </div>
    <div class="header-actions">
      <button class="btn btn-secondary btn-sm" onclick="exportJadwal()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
          <polyline points="7 10 12 15 17 10"></polyline>
          <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
        Ekspor CSV
      </button>
    </div>
  </div>

  <!-- Bilah Penyaring -->
  <div class="filter-bar">
    <label>Tahun Akademik</label>
    <select id="filter-ta" onchange="loadData()">
      <option value="">Pilih Tahun Akademik</option>
    </select>
    <label style="margin-left:8px">Mata Kuliah</label>
    <select id="filter-mk" onchange="loadData()">
      <option value="">Semua Mata Kuliah</option>
    </select>
    <button class="btn btn-secondary btn-sm" onclick="resetFilter()">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
        <polyline points="1 4 1 10 7 10"></polyline>
        <polyline points="23 20 23 14 17 14"></polyline>
        <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path>
      </svg>
      Atur Ulang
    </button>
  </div>

  <!-- Tabel -->
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>No</th>
          <th>Hari</th>
          <th>Jam</th>
          <th>Mata Kuliah</th>
          <th>SKS</th>
          <th>Kelas</th>
          <th>Ruangan</th>
          <th>Status</th>
          <th>Tindakan</th>
        </tr>
      </thead>
      <tbody id="table-body">
        <tr><td colspan="9" style="text-align:center;padding:40px">Memuat data...</td></tr>
      </tbody>
    </table>
  </div>
  
  <!-- Penomoran Halaman -->
  <div class="pagination" id="pagination" style="display:none">
    <span class="page-info" id="page-info"></span>
    <div id="page-buttons"></div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════
     MODAL: Kode QR Sesi
═══════════════════════════════════════════════ -->
<div class="modal-overlay" id="modal-qr">
  <div class="modal modal-md">
    <div class="modal-header">
      <div class="modal-title" id="modal-qr-title">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px;">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
          <rect x="7" y="7" width="3" height="3"></rect>
          <rect x="14" y="7" width="3" height="3"></rect>
          <rect x="7" y="14" width="3" height="3"></rect>
          <rect x="14" y="14" width="3" height="3"></rect>
        </svg>
        Kode QR Presensi
      </div>
      <button class="btn-close-modal" onclick="closeModal('modal-qr')">&times;</button>
    </div>
    <div class="modal-body" style="text-align:center">
      <div id="qr-info" style="margin-bottom:16px">
        <div style="font-weight:700;font-size:16px" id="qr-mk"></div>
        <div style="color:var(--text3);font-size:13px" id="qr-kelas"></div>
        <div style="color:var(--text3);font-size:12px;margin-top:4px" id="qr-pertemuan"></div>
      </div>
      
      <!-- Pembungkus Kode QR dengan tombol perbesar -->
      <div style="position: relative; display: inline-block;">
        <div id="qr-container" style="background:white;padding:20px;border-radius:12px;display:inline-block;cursor:pointer" onclick="perbesarQR()" title="Klik untuk memperbesar">
          <img id="qr-image" src="" alt="Kode QR" style="width:200px;height:200px">
          <div style="position: absolute; bottom: 24px; right: 24px; background: rgba(0,0,0,0.5); color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 14px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="15 3 21 3 21 9"></polyline>
              <polyline points="9 21 3 21 3 15"></polyline>
              <line x1="21" y1="3" x2="14" y2="10"></line>
              <line x1="3" y1="21" x2="10" y2="14"></line>
            </svg>
          </div>
        </div>
      </div>
      
      <div style="margin-top:16px;color:var(--text3);font-size:12px">
        <span id="qr-timer">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
          Pembaruan dalam 30 detik
        </span>
      </div>
      <div style="margin-top:16px;display:flex;gap:8px;justify-content:center">
        <button class="btn btn-secondary btn-sm" onclick="refreshQR()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
            <polyline points="1 4 1 10 7 10"></polyline>
            <polyline points="23 20 23 14 17 14"></polyline>
            <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path>
          </svg>
          Perbarui Kode QR
        </button>
        <button class="btn btn-danger btn-sm" onclick="tutupSesi()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
          </svg>
          Tutup Sesi
        </button>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-qr')">Tutup</button>
      <button class="btn btn-primary" onclick="window.location.href='sesi'">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
          <circle cx="12" cy="12" r="3"></circle>
        </svg>
        Lihat Pemantauan
      </button>
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

<!-- Modal: Buat Sesi -->
<div class="modal-overlay" id="modal-buat-sesi">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div class="modal-title">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px;">
          <polygon points="5 3 19 12 5 21 5 3"></polygon>
        </svg>
        Buat Sesi Presensi
      </div>
      <button class="btn-close-modal" onclick="closeModal('modal-buat-sesi')">&times;</button>
    </div>
    <div class="modal-body">
      <form id="form-buat-sesi" onsubmit="buatSesi(event)">
        <input type="hidden" name="jadwal_id" id="sesi-jadwal-id">
        <div id="sesi-info" style="margin-bottom:16px;padding:12px;background:var(--surface2);border-radius:8px">
          <!-- Informasi jadwal akan diisi melalui JavaScript -->
        </div>
        <div class="form-group">
          <label>Pertemuan Ke- <span style="color:var(--red)">*</span></label>
          <input type="number" name="pertemuan_ke" id="sesi-pertemuan" min="1" max="16" required>
          <small>Masukkan nomor pertemuan</small>
        </div>
        <div class="form-group">
          <label>Tanggal <span style="color:var(--red)">*</span></label>
          <input type="date" name="tanggal" id="sesi-tanggal" required>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-buat-sesi')">Batal</button>
      <button class="btn btn-primary" onclick="document.getElementById('form-buat-sesi').requestSubmit()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
          <rect x="7" y="7" width="3" height="3"></rect>
          <rect x="14" y="7" width="3" height="3"></rect>
          <rect x="7" y="14" width="3" height="3"></rect>
          <rect x="14" y="14" width="3" height="3"></rect>
        </svg>
        Buat dan Buka Kode QR
      </button>
    </div>
  </div>
</div>

<script>
// ════════════════════════════════════════════════
// VARIABEL GLOBAL
// ════════════════════════════════════════════════
let currentPage = 1;
let currentTA = '';
let currentMK = '';
let jadwalList = [];
let currentSesiId = null;
let qrTimer = null;
let currentQRData = null;

// ════════════════════════════════════════════════
// INISIALISASI
// ════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
  // Mengatur tanggal bawaan ke hari ini
  document.getElementById('sesi-tanggal').value = new Date().toISOString().split('T')[0];
  
  loadTAOptions();
  loadMKOptions();
  loadIzinCount();
});

// ════════════════════════════════════════════════
// MEMUAT OPSI
// ════════════════════════════════════════════════
function loadTAOptions() {
  fetch('api/jadwal.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_ta_options'
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      let html = '<option value="">Pilih Tahun Akademik</option>';
      response.data.forEach(ta => {
        const selected = ta.status === 'aktif' ? 'selected' : '';
        html += `<option value="${ta.id}" ${selected}>${ta.tahun} ${ta.semester}</option>`;
        if (ta.status === 'aktif') currentTA = ta.id;
      });
      document.getElementById('filter-ta').innerHTML = html;
      if (currentTA) loadData();
    }
  });
}

function loadMKOptions() {
  fetch('api/jadwal.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_mk_options'
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      let html = '<option value="">Semua Mata Kuliah</option>';
      response.data.forEach(mk => {
        html += `<option value="${mk.id}">${mk.nama_mk}</option>`;
      });
      document.getElementById('filter-mk').innerHTML = html;
    }
  });
}

function loadIzinCount() {
  fetch('api/izin.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_counts'
  })
  .then(res => res.json())
  .then(response => {
    if (response.success && response.pending > 0) {
      // Menambahkan lencana di tindakan judul
      const headerActions = document.querySelector('.header-actions');
      if (headerActions) {
        const badge = document.createElement('a');
        badge.href = 'izin.php';
        badge.className = 'btn btn-warning btn-sm';
        badge.style.cssText = 'position:relative';
        badge.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>Izin Tertunda <span style="background:#ef4444;color:white;border-radius:10px;padding:1px 6px;font-size:10px;margin-left:4px">${response.pending}</span>`;
        headerActions.insertBefore(badge, headerActions.firstChild);
      }
    }
  });
}

// ════════════════════════════════════════════════
// MEMUAT DATA
// ════════════════════════════════════════════════
function loadData(page = 1) {
  currentPage = page;
  const ta = document.getElementById('filter-ta').value;
  const mk = document.getElementById('filter-mk').value;
  
  if (!ta) {
    document.getElementById('table-body').innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px">Pilih Tahun Akademik</td></tr>';
    return;
  }
  
  currentTA = ta;
  currentMK = mk;
  
  document.getElementById('table-body').innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px">Memuat data...</td></tr>';
  
  let body = `action=list&tahun_akademik_id=${ta}&page=${page}`;
  if (mk) body += `&mata_kuliah_id=${mk}`;
  
  fetch('api/jadwal.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: body
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      jadwalList = response.data;
      renderTable(response.data);
      renderPagination(response);
    }
  });
}

// ════════════════════════════════════════════════
// MERENDER TABEL
// ════════════════════════════════════════════════
function renderTable(data) {
  const tbody = document.getElementById('table-body');
  
  if (data.length === 0) {
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px">Tidak ada jadwal</td></tr>';
    return;
  }
  
  const hariColors = {
    'Senin': '#3b82f6', 'Selasa': '#10b981', 'Rabu': '#f97316',
    'Kamis': '#a855f7', 'Jumat': '#ef4444', 'Sabtu': '#6b7280'
  };
  
  const sksColors = { 1: '#6b7280', 2: '#3b82f6', 3: '#f97316', 4: '#a855f7' };
  
  let html = '';
  data.forEach((j, index) => {
    const no = (currentPage - 1) * 10 + index + 1;
    const sksColor = sksColors[j.sks] || '#6b7280';
    
    let statusHtml = '';
    let actionHtml = '';
    
    // SVG Ikon untuk tombol
    const qrIcon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><rect x="7" y="7" width="3" height="3"></rect><rect x="14" y="7" width="3" height="3"></rect><rect x="7" y="14" width="3" height="3"></rect><rect x="14" y="14" width="3" height="3"></rect></svg>';
    const mataIcon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    const putarIcon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>';
    
    if (j.sesi_aktif) {
      statusHtml = '<span class="pill pill-green"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#ef4444;margin-right:6px;animation:blink 1s infinite"></span>Berlangsung</span>';
      actionHtml = `
        <button class="btn btn-primary btn-xs" onclick="showQR(${j.sesi_id}, '${j.nama_mk.replace(/'/g, "\\'")}', '${j.nama_kelas}', ${j.pertemuan_ke})">${qrIcon}Kode QR</button>
        <button class="btn btn-info btn-xs" onclick="window.location.href='sesi?id=${j.sesi_id}'">${mataIcon}Sesi</button>
      `;
 } else if (j.sesi_terakhir) {
  // Escape nama_mk dan nama_kelas untuk mencegah error JavaScript
  const namaMkEscaped = j.nama_mk.replace(/'/g, "\\'").replace(/"/g, '&quot;');
  const namaKelasEscaped = j.nama_kelas.replace(/'/g, "\\'").replace(/"/g, '&quot;');
  
  statusHtml = '<span class="pill pill-blue"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><polyline points="20 6 9 17 4 12"></polyline></svg>Selesai P-' + j.sesi_terakhir + '</span>';
  
  actionHtml = '<button class="btn btn-primary btn-xs" onclick="bukaModalBuatSesi(' + j.id + ', \'' + namaMkEscaped + '\', \'' + namaKelasEscaped + '\', ' + j.sesi_terakhir + ')">' + putarIcon + 'Buat Sesi</button>' +
               '<button class="btn btn-info btn-xs" onclick="window.location.href=\'sesi?jadwal_id=' + j.id + '\'">' + mataIcon + 'Sesi</button>';
} else {
      statusHtml = '<span class="pill pill-gray"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>Belum Dimulai</span>';
      actionHtml = `
        <button class="btn btn-primary btn-xs" onclick="bukaModalBuatSesi(${j.id}, '${j.nama_mk.replace(/'/g, "\\'")}', '${j.nama_kelas}', 0)">${putarIcon}Buat Sesi</button>
      `;
    }
    
    html += `<tr>
      <td>${no}</td>
      <td style="font-weight:700;color:${hariColors[j.hari] || '#333'}">${j.hari}</td>
      <td><code>${j.jam_mulai?.substring(0,5)}–${j.jam_selesai?.substring(0,5) || '-'}</code></td>
      <td style="font-weight:700">${j.nama_mk}</td>
      <td><span class="pill" style="background:${sksColor};color:white">${j.sks} SKS</span></td>
      <td>${j.nama_kelas}</td>
      <td>${j.nama_ruangan || '-'} (${j.kode_ruangan || '-'})</td>
      <td>${statusHtml}</td>
      <td><div style="display:flex;gap:5px">${actionHtml}</div></td>
    </tr>`;
  });
  
  tbody.innerHTML = html;
}

// ════════════════════════════════════════════════
// MERENDER PENOMORAN HALAMAN
// ════════════════════════════════════════════════
function renderPagination(response) {
  if (response.pages <= 1) {
    document.getElementById('pagination').style.display = 'none';
    return;
  }
  
  document.getElementById('pagination').style.display = 'flex';
  const start = (response.page - 1) * response.limit + 1;
  const end = Math.min(response.page * response.limit, response.total);
  
  document.getElementById('page-info').textContent = `Menampilkan ${start}–${end} dari ${response.total} data`;
  
  const pageButtons = document.getElementById('page-buttons');
  let html = '';
  
  if (response.page > 1) {
    html += `<button class="page-btn" onclick="loadData(${response.page - 1})">‹</button>`;
  }
  
  for (let i = Math.max(1, response.page - 2); i <= Math.min(response.pages, response.page + 2); i++) {
    html += `<button class="page-btn ${i === response.page ? 'active' : ''}" onclick="loadData(${i})">${i}</button>`;
  }
  
  if (response.page < response.pages) {
    html += `<button class="page-btn" onclick="loadData(${response.page + 1})">›</button>`;
  }
  
  pageButtons.innerHTML = html;
}

// ════════════════════════════════════════════════
// PENYARING
// ════════════════════════════════════════════════
function resetFilter() {
  document.getElementById('filter-mk').value = '';
  loadData(1);
}

// ════════════════════════════════════════════════
// MODAL BUAT SESI
// ════════════════════════════════════════════════
function bukaModalBuatSesi(jadwalId, namaMk, namaKelas, pertemuanTerakhir) {
  const nextPertemuan = pertemuanTerakhir + 1;
  
  document.getElementById('sesi-jadwal-id').value = jadwalId;
  document.getElementById('sesi-pertemuan').value = nextPertemuan;
  document.getElementById('sesi-pertemuan').min = nextPertemuan;
  
  document.getElementById('sesi-info').innerHTML = `
    <strong>${namaMk}</strong><br>
    <span style="font-size:13px;color:var(--text3)">Kelas: ${namaKelas}</span><br>
    <span style="font-size:12px;color:var(--ora3)">Pertemuan sebelumnya: P-${pertemuanTerakhir || 0}</span>
  `;
  
  openModal('modal-buat-sesi');
}

function buatSesi(event) {
  event.preventDefault();
  
  const formData = new FormData(event.target);
  formData.append('action', 'create_sesi');
  
  fetch('api/jadwal.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      showNotification('Sesi berhasil dibuat', 'berhasil');
      closeModal('modal-buat-sesi');
      showQR(response.sesi_id, response.nama_mk, response.nama_kelas, response.pertemuan_ke);
      loadData(currentPage);
    } else {
      showNotification(response.msg, 'kesalahan');
    }
  });
}

// ════════════════════════════════════════════════
// KODE QR
// ════════════════════════════════════════════════
function showQR(sesiId, namaMk, namaKelas, pertemuanKe) {
  currentSesiId = sesiId;
  
  document.getElementById('qr-mk').textContent = namaMk;
  document.getElementById('qr-kelas').textContent = `Kelas: ${namaKelas}`;
  document.getElementById('qr-pertemuan').textContent = `Pertemuan ke-${pertemuanKe}`;
  
  generateQR();
  startQRTimer();
  
  openModal('modal-qr');
}

function generateQR() {
  if (!currentSesiId) return;
  
  const token = btoa(`sesi:${currentSesiId}:${Date.now()}`);
  const qrData = JSON.stringify({
    sesi_id: currentSesiId,
    token: token,
    timestamp: Date.now()
  });
  
  currentQRData = qrData;
  
  const qrUrl = `https://quickchart.io/qr?text=${encodeURIComponent(qrData)}&size=200`;
  document.getElementById('qr-image').src = qrUrl;
  
  // Memperbarui gambar zoom juga
  const qrZoomUrl = `https://quickchart.io/qr?text=${encodeURIComponent(qrData)}&size=400`;
  document.getElementById('qr-image-zoom').src = qrZoomUrl;
  
  fetch('api/jadwal.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=update_qr&sesi_id=${currentSesiId}&qr_code=${encodeURIComponent(qrData)}`
  });
}

function refreshQR() {
  generateQR();
  resetQRTimer();
  showNotification('Kode QR berhasil diperbarui', 'informasi');
}

function perbesarQR() {
  // Mengisi data zoom modal
  document.getElementById('qr-zoom-mk').textContent = document.getElementById('qr-mk').textContent;
  document.getElementById('qr-zoom-kelas').textContent = document.getElementById('qr-kelas').textContent;
  document.getElementById('qr-zoom-pertemuan').textContent = document.getElementById('qr-pertemuan').textContent;
  
  // Menghasilkan QR yang lebih besar
  if (currentQRData) {
    const qrZoomUrl = `https://quickchart.io/qr?text=${encodeURIComponent(currentQRData)}&size=400`;
    document.getElementById('qr-image-zoom').src = qrZoomUrl;
  }
  
  openModal('modal-qr-zoom');
}

function cetakQR() {
  const printWindow = window.open('', '_blank');
  const qrImageSrc = document.getElementById('qr-image-zoom').src;
  const mk = document.getElementById('qr-zoom-mk').textContent;
  const kelas = document.getElementById('qr-zoom-kelas').textContent;
  const pertemuan = document.getElementById('qr-zoom-pertemuan').textContent;
  
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
        .qr-footer {
          font-size: 12px;
          color: #999;
          margin-top: 12px;
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
        <div class="qr-footer">Pindai untuk melakukan presensi • Dicetak pada ${new Date().toLocaleDateString('id-ID')}</div>
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
  
  showNotification('Membuka dialog pencetakan...', 'informasi');
}

function startQRTimer() {
  let seconds = 30;
  const timerEl = document.getElementById('qr-timer');
  
  if (qrTimer) clearInterval(qrTimer);
  
  qrTimer = setInterval(() => {
    seconds--;
    if (seconds <= 0) {
      generateQR();
      seconds = 30;
    }
    timerEl.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>Pembaruan dalam ${seconds} detik`;
  }, 1000);
}

function resetQRTimer() {
  if (qrTimer) clearInterval(qrTimer);
  startQRTimer();
}

function tutupSesi() {
  if (!currentSesiId) return;
  
  if (confirm('Tutup sesi presensi ini? Mahasiswa tidak dapat melakukan presensi lagi.')) {
    fetch('api/jadwal.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `action=tutup_sesi&sesi_id=${currentSesiId}`
    })
    .then(res => res.json())
    .then(response => {
      if (response.success) {
        showNotification('Sesi berhasil ditutup', 'berhasil');
        closeModal('modal-qr');
        if (qrTimer) clearInterval(qrTimer);
        loadData(currentPage);
      }
    });
  }
}

// ════════════════════════════════════════════════
// EKSPOR
// ════════════════════════════════════════════════
function exportJadwal() {
  const ta = document.getElementById('filter-ta').value;
  if (!ta) {
    showNotification('Pilih Tahun Akademik terlebih dahulu', 'kesalahan');
    return;
  }
  
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'api/jadwal.php';
  
  const actionInput = document.createElement('input');
  actionInput.type = 'hidden';
  actionInput.name = 'action';
  actionInput.value = 'export_csv';
  form.appendChild(actionInput);
  
  const taInput = document.createElement('input');
  taInput.type = 'hidden';
  taInput.name = 'tahun_akademik_id';
  taInput.value = ta;
  form.appendChild(taInput);
  
  if (currentMK) {
    const mkInput = document.createElement('input');
    mkInput.type = 'hidden';
    mkInput.name = 'mata_kuliah_id';
    mkInput.value = currentMK;
    form.appendChild(mkInput);
  }
  
  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);
  
  showNotification('Ekspor sedang diproses...', 'informasi');
}

// ════════════════════════════════════════════════
// UTILITAS
// ════════════════════════════════════════════════
function openModal(id) {
  document.getElementById(id).classList.add('active');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('active');
  if ((id === 'modal-qr' || id === 'modal-qr-zoom') && qrTimer) {
    // Jangan bersihkan timer saat menutup zoom
    if (id === 'modal-qr') {
      clearInterval(qrTimer);
      qrTimer = null;
    }
  }
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

// Tambahkan gaya animasi untuk indikator berkedip
const style = document.createElement('style');
style.textContent = `
  @keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
  }
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