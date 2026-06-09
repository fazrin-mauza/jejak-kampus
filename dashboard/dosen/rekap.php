<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/navigasi.php';
?>

<!-- ═══════════════════════════════════════════════
     REKAP PRESENSI - Dinamis
═══════════════════════════════════════════════ -->
<div id="app-rekap">
  <div class="page-header">
    <div>
      <div class="page-subtitle">Ringkasan dan analisis kehadiran mahasiswa per kelas</div>
    </div>
    <div class="header-actions">
      <button class="btn btn-secondary btn-sm" onclick="exportRekap('excel')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
          <polyline points="7 10 12 15 17 10"></polyline>
          <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
        Ekspor Excel
      </button>
      <button class="btn btn-secondary btn-sm" onclick="exportRekap('pdf')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
          <line x1="16" y1="13" x2="8" y2="13"></line>
          <line x1="16" y1="17" x2="8" y2="17"></line>
          <polyline points="10 9 9 9 8 9"></polyline>
        </svg>
        Ekspor PDF
      </button>
    </div>
  </div>

  <!-- Bilah Penyaring -->
  <div class="filter-bar">
    <label>Mata Kuliah</label>
    <select id="filter-mk" onchange="loadRekap()">
      <option value="">Semua Mata Kuliah</option>
    </select>
    <label style="margin-left:8px">Kelas</label>
    <select id="filter-kelas" onchange="loadRekap()">
      <option value="">Semua Kelas</option>
    </select>
    <label style="margin-left:8px">Semester</label>
    <select id="filter-ta" onchange="loadRekap()">
      <option value="">Pilih Semester</option>
    </select>
    <button class="btn btn-secondary btn-sm" onclick="loadRekap()">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
      </svg>
      Tampilkan
    </button>
  </div>

  <!-- Navigasi Tab -->
  <div class="tab-nav">
    <button class="tab-btn active" data-tab="kelas" onclick="switchTab('kelas')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
        <line x1="8" y1="6" x2="21" y2="6"></line>
        <line x1="8" y1="12" x2="21" y2="12"></line>
        <line x1="8" y1="18" x2="21" y2="18"></line>
        <line x1="3" y1="6" x2="3.01" y2="6"></line>
        <line x1="3" y1="12" x2="3.01" y2="12"></line>
        <line x1="3" y1="18" x2="3.01" y2="18"></line>
      </svg>
      Rekap per Kelas
    </button>
    <button class="tab-btn" data-tab="pertemuan" onclick="switchTab('pertemuan')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
        <line x1="16" y1="2" x2="16" y2="6"></line>
        <line x1="8" y1="2" x2="8" y2="6"></line>
        <line x1="3" y1="10" x2="21" y2="10"></line>
      </svg>
      Rekap per Pertemuan
    </button>
    <button class="tab-btn" data-tab="mahasiswa" onclick="switchTab('mahasiswa')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
        <circle cx="12" cy="7" r="4"></circle>
      </svg>
      Detail Mahasiswa
    </button>
  </div>

  <!-- Tab: Rekap per Kelas -->
  <div id="tab-kelas" class="tab-content active">
    <!-- Grafik -->
    <div class="card" style="margin-bottom:18px">
      <div class="card-header">
        <div>
          <div class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px;">
              <line x1="18" y1="20" x2="18" y2="10"></line>
              <line x1="12" y1="20" x2="12" y2="4"></line>
              <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            Grafik Kehadiran <span id="chart-title">Pilih Kelas</span>
          </div>
          <div class="card-sub">Tren kehadiran per pertemuan</div>
        </div>
      </div>
      <div class="chart-bars" id="chart-container" style="height:140px;min-height:140px">
        <div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--text3)">
          Pilih mata kuliah dan kelas untuk melihat grafik
        </div>
      </div>
      <div class="chart-legend">
        <span class="legend-item"><span class="legend-dot" style="background:var(--green)"></span>&ge;80% (Baik)</span>
        <span class="legend-item"><span class="legend-dot" style="background:var(--ora2)"></span>60&ndash;79% (Perhatian)</span>
        <span class="legend-item"><span class="legend-dot" style="background:var(--red)"></span>&lt;60% (Kritis)</span>
        <span class="legend-item"><span class="legend-dot" style="background:var(--blue)"></span>Sesi Aktif</span>
      </div>
    </div>

    <!-- Tabel Rekap per Kelas -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px;">
            <line x1="8" y1="6" x2="21" y2="6"></line>
            <line x1="8" y1="12" x2="21" y2="12"></line>
            <line x1="8" y1="18" x2="21" y2="18"></line>
            <line x1="3" y1="6" x2="3.01" y2="6"></line>
            <line x1="3" y1="12" x2="3.01" y2="12"></line>
            <line x1="3" y1="18" x2="3.01" y2="18"></line>
          </svg>
          Rekap per Kelas <span id="table-title">Semua</span>
        </div>
        <div class="search-box">
          <span class="search-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
          </span>
          <input type="text" id="search-input" placeholder="Cari NIM atau nama..." onkeyup="filterTable()">
        </div>
      </div>
      <div class="tbl-wrap">
        <table id="rekap-table">
          <thead>
            <tr>
              <th>No</th><th>NIM</th><th>Nama</th><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Tanpa Keterangan</th><th>Persentase</th><th>Status</th>
            </tr>
          </thead>
          <tbody id="rekap-body">
            <tr><td colspan="9" style="text-align:center;padding:40px">Pilih penyaring untuk memuat data</td></tr>
          </tbody>
          <tfoot id="rekap-foot" style="display:none">
            <tr>
              <td colspan="3">Total/Rata-rata</td>
              <td id="total-hadir">0</td>
              <td id="total-izin">0</td>
              <td id="total-sakit">0</td>
              <td id="total-alpha">0</td>
              <td id="rata-persen">0%</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <div class="pagination" id="pagination" style="display:none">
        <span class="page-info" id="page-info"></span>
        <div id="page-buttons"></div>
      </div>
    </div>
  </div>

  <!-- Tab: Rekap per Pertemuan -->
  <div id="tab-pertemuan" class="tab-content">
    <div class="card">
      <div class="card-header">
        <div class="card-title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px;">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
          Rekap per Pertemuan <span id="pertemuan-title">Pilih Kelas</span>
        </div>
      </div>
      <div class="tbl-wrap">
        <table>
          <thead>
            <tr>
              <th>No</th><th>Pertemuan</th><th>Tanggal</th><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Tanpa Keterangan</th><th>Total</th><th>Persentase</th>
            </tr>
          </thead>
          <tbody id="pertemuan-body">
            <tr><td colspan="9" style="text-align:center;padding:40px">Pilih mata kuliah dan kelas</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Tab: Detail Mahasiswa -->
  <div id="tab-mahasiswa" class="tab-content">
    <div class="card">
      <div class="card-header">
        <div class="card-title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px;">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
          Detail Mahasiswa
        </div>
      </div>
      <div class="filter-bar" style="padding:0 0 16px 0">
        <label>Pilih Mahasiswa</label>
        <select id="select-mahasiswa" onchange="loadDetailMahasiswa()" style="min-width:250px">
          <option value="">Pilih Mahasiswa</option>
        </select>
      </div>
      <div id="detail-mahasiswa-container">
        <div style="text-align:center;padding:40px;color:var(--text3)">Pilih mahasiswa untuk melihat detail</div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Detail Mahasiswa -->
<div class="modal-overlay" id="modal-detail-mhs">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title" id="modal-detail-title">Detail Mahasiswa</div>
      <button class="btn-close-modal" onclick="closeModal('modal-detail-mhs')">&times;</button>
    </div>
    <div class="modal-body" id="modal-detail-content">
      <!-- Konten dinamis -->
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-detail-mhs')">Tutup</button>
    </div>
  </div>
</div>

<script>
// ════════════════════════════════════════════════
// VARIABEL GLOBAL
// ════════════════════════════════════════════════
let currentPage = 1;
let currentTab = 'kelas';
let currentMK = '';
let currentKelas = '';
let currentTA = '';
let rekapData = [];

// ════════════════════════════════════════════════
// INISIALISASI
// ════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
  loadFilterOptions();
  loadMahasiswaOptions();
});

// ════════════════════════════════════════════════
// MEMUAT OPSI PENYARING
// ════════════════════════════════════════════════
function loadFilterOptions() {
  fetch('api/rekap.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_filter_options'
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      // Opsi Mata Kuliah
      let mkHtml = '<option value="">Semua Mata Kuliah</option>';
      response.data.mk.forEach(mk => {
        mkHtml += `<option value="${mk.id}">${mk.nama_mk}</option>`;
      });
      document.getElementById('filter-mk').innerHTML = mkHtml;
      
      // Opsi Kelas
      let kelasHtml = '<option value="">Semua Kelas</option>';
      response.data.kelas.forEach(k => {
        kelasHtml += `<option value="${k.id}">${k.nama_kelas}</option>`;
      });
      document.getElementById('filter-kelas').innerHTML = kelasHtml;
      
      // Opsi Tahun Akademik
      let taHtml = '<option value="">Pilih Semester</option>';
      response.data.ta.forEach(ta => {
        const selected = ta.status === 'aktif' ? 'selected' : '';
        taHtml += `<option value="${ta.id}" ${selected}>${ta.tahun} ${ta.semester}</option>`;
        if (ta.status === 'aktif') currentTA = ta.id;
      });
      document.getElementById('filter-ta').innerHTML = taHtml;
    }
  });
}

function loadMahasiswaOptions() {
  fetch('api/rekap.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_mahasiswa_options'
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      let html = '<option value="">Pilih Mahasiswa</option>';
      response.data.forEach(m => {
        html += `<option value="${m.id}">${m.nim} - ${m.nama}</option>`;
      });
      document.getElementById('select-mahasiswa').innerHTML = html;
    }
  });
}

// ════════════════════════════════════════════════
// PERPINDAHAN TAB
// ════════════════════════════════════════════════
function switchTab(tab) {
  currentTab = tab;
  
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.classList.remove('active');
    if (btn.dataset.tab === tab) btn.classList.add('active');
  });
  
  document.querySelectorAll('.tab-content').forEach(content => {
    content.classList.remove('active');
  });
  document.getElementById(`tab-${tab}`).classList.add('active');
  
  if (tab === 'kelas') loadRekap();
  else if (tab === 'pertemuan') loadRekapPertemuan();
}

// ════════════════════════════════════════════════
// MEMUAT REKAP PER KELAS
// ════════════════════════════════════════════════
function loadRekap(page = 1) {
  currentPage = page;
  currentMK = document.getElementById('filter-mk').value;
  currentKelas = document.getElementById('filter-kelas').value;
  currentTA = document.getElementById('filter-ta').value;
  
  if (!currentTA) {
    document.getElementById('rekap-body').innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px">Pilih Semester terlebih dahulu</td></tr>';
    document.getElementById('rekap-foot').style.display = 'none';
    return;
  }
  
  document.getElementById('rekap-body').innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px">Memuat data...</td></tr>';
  
  let body = `action=get_rekap_kelas&ta_id=${currentTA}&page=${page}`;
  if (currentMK) body += `&mk_id=${currentMK}`;
  if (currentKelas) body += `&kelas_id=${currentKelas}`;
  
  fetch('api/rekap.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: body
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      rekapData = response.data;
      renderRekapTable(response.data);
      renderPagination(response);
      updateChartTitle();
      loadChartData();
    }
  });
}

function renderRekapTable(data) {
  const tbody = document.getElementById('rekap-body');
  const tfoot = document.getElementById('rekap-foot');
  
  if (data.length === 0) {
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px">Tidak ada data</td></tr>';
    tfoot.style.display = 'none';
    return;
  }
  
  let html = '';
  let totalHadir = 0, totalIzin = 0, totalSakit = 0, totalAlpha = 0, totalPersen = 0;
  
  data.forEach((m, i) => {
    const no = (currentPage - 1) * 10 + i + 1;
    const persen = parseFloat(m.persen) || 0;
    const minHadir = m.min_kehadiran || 75;
    
    let statusClass = 'pill-green';
    let statusText = 'Lulus';
    let barColor = 'var(--green)';
    
    if (persen < minHadir) {
      statusClass = 'pill-red';
      statusText = 'Tidak Lulus';
      barColor = 'var(--red)';
    } else if (persen < 80) {
      statusClass = 'pill-yellow';
      statusText = 'Perhatian';
      barColor = 'var(--yellow)';
    }
    
    // Escape nama untuk onclick
    const namaEscaped = m.nama.replace(/'/g, "\\'").replace(/"/g, '&quot;');
    
    html += `<tr onclick="showDetailModal(${m.mahasiswa_id}, '${namaEscaped}')" style="cursor:pointer">
      <td>${no}</td>
      <td><code>${m.nim || '-'}</code></td>
      <td style="font-weight:600">${m.nama || '-'}</td>
      <td>${m.hadir || 0}</td>
      <td>${m.izin || 0}</td>
      <td>${m.sakit || 0}</td>
      <td>${m.alpha || 0}</td>
      <td>
        <div class="pct-bar">
          <div class="pct-track"><div class="pct-fill" style="width:${persen}%;background:${barColor}"></div></div>
          ${persen}%
        </div>
      </td>
      <td><span class="pill ${statusClass}">${statusText}</span></td>
    </tr>`;
    
    totalHadir += parseInt(m.hadir) || 0;
    totalIzin += parseInt(m.izin) || 0;
    totalSakit += parseInt(m.sakit) || 0;
    totalAlpha += parseInt(m.alpha) || 0;
    totalPersen += persen;
  });
  
  tbody.innerHTML = html;
  
  const avgPersen = data.length > 0 ? (totalPersen / data.length).toFixed(1) : 0;
  document.getElementById('total-hadir').textContent = totalHadir;
  document.getElementById('total-izin').textContent = totalIzin;
  document.getElementById('total-sakit').textContent = totalSakit;
  document.getElementById('total-alpha').textContent = totalAlpha;
  document.getElementById('rata-persen').textContent = avgPersen + '%';
  tfoot.style.display = 'table-footer-group';
  
  // Perbarui judul tabel
  const mkSelect = document.getElementById('filter-mk');
  const kelasSelect = document.getElementById('filter-kelas');
  const mkText = mkSelect.value ? mkSelect.options[mkSelect.selectedIndex].text : 'Semua MK';
  const kelasText = kelasSelect.value ? kelasSelect.options[kelasSelect.selectedIndex].text : 'Semua Kelas';
  document.getElementById('table-title').textContent = `${mkText} - ${kelasText}`;
}

function renderPagination(response) {
  if (response.pages <= 1) {
    document.getElementById('pagination').style.display = 'none';
    return;
  }
  
  document.getElementById('pagination').style.display = 'flex';
  const start = (response.page - 1) * response.limit + 1;
  const end = Math.min(response.page * response.limit, response.total);
  
  document.getElementById('page-info').textContent = `${start}–${end} dari ${response.total} mahasiswa`;
  
  const pageButtons = document.getElementById('page-buttons');
  let html = '';
  
  if (response.page > 1) {
    html += `<button class="page-btn" onclick="loadRekap(${response.page - 1})">‹</button>`;
  }
  
  for (let i = Math.max(1, response.page - 2); i <= Math.min(response.pages, response.page + 2); i++) {
    html += `<button class="page-btn ${i === response.page ? 'active' : ''}" onclick="loadRekap(${i})">${i}</button>`;
  }
  
  if (response.page < response.pages) {
    html += `<button class="page-btn" onclick="loadRekap(${response.page + 1})">›</button>`;
  }
  
  pageButtons.innerHTML = html;
}

// ════════════════════════════════════════════════
// GRAFIK
// ════════════════════════════════════════════════
function updateChartTitle() {
  const mkSelect = document.getElementById('filter-mk');
  const kelasSelect = document.getElementById('filter-kelas');
  const mkText = mkSelect.value ? mkSelect.options[mkSelect.selectedIndex].text : '';
  const kelasText = kelasSelect.value ? kelasSelect.options[kelasSelect.selectedIndex].text : '';
  
  if (mkText && kelasText) {
    document.getElementById('chart-title').textContent = ' ' + mkText + ' \u2022 ' + kelasText;
  } else {
    document.getElementById('chart-title').textContent = ' Pilih Mata Kuliah & Kelas';
  }
}

function loadChartData() {
  if (!currentMK || !currentKelas) {
    document.getElementById('chart-container').innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--text3)">Pilih mata kuliah dan kelas untuk melihat grafik</div>';
    return;
  }
  
  fetch('api/rekap.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=get_chart_data&ta_id=${currentTA}&mk_id=${currentMK}&kelas_id=${currentKelas}`
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      renderChart(response.data);
    }
  });
}

function renderChart(data) {
  const container = document.getElementById('chart-container');
  
  if (data.length === 0) {
    container.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--text3)">Belum ada data pertemuan</div>';
    return;
  }
  
  // Render grafik batang manual
  let html = '';
  const maxPersen = Math.max(...data.map(d => d.persen || 0), 100);
  
  data.forEach(d => {
    const persen = d.persen || 0;
    let color = 'var(--green)';
    if (persen < 60) color = 'var(--red)';
    else if (persen < 80) color = 'var(--ora2)';
    if (d.status === 'aktif') color = 'var(--blue)';
    
    const height = (persen / maxPersen) * 100;
    
    html += `<div class="bar-wrap">
      <div class="bar-val">${persen}%</div>
      <div class="bar" style="height:${height}%;background:${color}"></div>
      <div class="bar-label">P${d.pertemuan_ke}</div>
    </div>`;
  });
  
  container.innerHTML = html;
}

// ════════════════════════════════════════════════
// REKAP PER PERTEMUAN
// ════════════════════════════════════════════════
function loadRekapPertemuan() {
  const mk = document.getElementById('filter-mk').value;
  const kelas = document.getElementById('filter-kelas').value;
  const ta = document.getElementById('filter-ta').value;
  
  if (!ta || !mk || !kelas) {
    document.getElementById('pertemuan-body').innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px">Pilih Semester, Mata Kuliah, dan Kelas</td></tr>';
    return;
  }
  
  document.getElementById('pertemuan-body').innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px">Memuat data...</td></tr>';
  
  const mkText = document.getElementById('filter-mk').options[document.getElementById('filter-mk').selectedIndex].text;
  const kelasText = document.getElementById('filter-kelas').options[document.getElementById('filter-kelas').selectedIndex].text;
  document.getElementById('pertemuan-title').textContent = ' ' + mkText + ' \u2022 ' + kelasText;
  
  fetch('api/rekap.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=get_rekap_pertemuan&ta_id=${ta}&mk_id=${mk}&kelas_id=${kelas}`
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      renderPertemuanTable(response.data);
    }
  });
}

function renderPertemuanTable(data) {
  const tbody = document.getElementById('pertemuan-body');
  
  if (data.length === 0) {
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px">Belum ada sesi pertemuan</td></tr>';
    return;
  }
  
  let html = '';
  data.forEach((p, i) => {
    const total = (p.hadir || 0) + (p.izin || 0) + (p.sakit || 0) + (p.alpha || 0);
    const persen = total > 0 ? ((p.hadir || 0) / total * 100).toFixed(1) : 0;
    
    html += `<tr>
      <td>${i+1}</td>
      <td><strong>P-${p.pertemuan_ke}</strong></td>
      <td>${p.tanggal || '-'}</td>
      <td>${p.hadir || 0}</td>
      <td>${p.izin || 0}</td>
      <td>${p.sakit || 0}</td>
      <td>${p.alpha || 0}</td>
      <td>${total}</td>
      <td><span style="font-weight:700;color:${persen >= 80 ? 'var(--green)' : (persen >= 60 ? 'var(--ora2)' : 'var(--red)')}">${persen}%</span></td>
    </tr>`;
  });
  
  tbody.innerHTML = html;
}

// ════════════════════════════════════════════════
// DETAIL MAHASISWA
// ════════════════════════════════════════════════
function loadDetailMahasiswa() {
  const mhsId = document.getElementById('select-mahasiswa').value;
  const ta = document.getElementById('filter-ta').value;
  
  if (!mhsId) {
    document.getElementById('detail-mahasiswa-container').innerHTML = '<div style="text-align:center;padding:40px;color:var(--text3)">Pilih mahasiswa</div>';
    return;
  }
  
  fetch('api/rekap.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=get_detail_mahasiswa&mhs_id=${mhsId}&ta_id=${ta}`
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      renderDetailMahasiswa(response.data);
    }
  });
}

function renderDetailMahasiswa(data) {
  const container = document.getElementById('detail-mahasiswa-container');
  
  let mkHtml = '';
  data.matkul.forEach(mk => {
    const persen = mk.persen || 0;
    mkHtml += `<tr>
      <td>${mk.kode_mk} - ${mk.nama_mk}</td>
      <td>${mk.sks} SKS</td>
      <td>${mk.hadir || 0}</td>
      <td>${mk.izin || 0}</td>
      <td>${mk.sakit || 0}</td>
      <td>${mk.alpha || 0}</td>
      <td><span style="font-weight:700;color:${persen >= 75 ? 'var(--green)' : 'var(--red)'}">${persen}%</span></td>
    </tr>`;
  });
  
  container.innerHTML = `
    <div style="margin-bottom:16px">
      <h3 style="margin:0">${data.nama}</h3>
      <p style="margin:4px 0 0;color:var(--text3)">${data.nim} \u2022 ${data.nama_kelas || '-'}</p>
    </div>
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>Mata Kuliah</th><th>SKS</th><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Tanpa Keterangan</th><th>Persentase</th></tr></thead>
        <tbody>${mkHtml || '<tr><td colspan="7" style="text-align:center">Tidak ada data</td></tr>'}</tbody>
      </table>
    </div>
  `;
}

function showDetailModal(mhsId, nama) {
  const ta = document.getElementById('filter-ta').value;
  
  document.getElementById('modal-detail-title').textContent = 'Detail ' + nama;
  document.getElementById('modal-detail-content').innerHTML = '<div style="text-align:center;padding:40px">Memuat data...</div>';
  openModal('modal-detail-mhs');
  
  fetch('api/rekap.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=get_detail_mahasiswa&mhs_id=${mhsId}&ta_id=${ta}`
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      const data = response.data;
      let mkHtml = '';
      data.matkul.forEach(mk => {
        const persen = mk.persen || 0;
        mkHtml += `<tr>
          <td>${mk.kode_mk} - ${mk.nama_mk}</td>
          <td>${mk.sks} SKS</td>
          <td>${mk.hadir || 0}</td>
          <td>${mk.izin || 0}</td>
          <td>${mk.sakit || 0}</td>
          <td>${mk.alpha || 0}</td>
          <td><span style="font-weight:700;color:${persen >= 75 ? 'var(--green)' : 'var(--red)'}">${persen}%</span></td>
        </tr>`;
      });
      
      document.getElementById('modal-detail-content').innerHTML = `
        <div style="margin-bottom:16px">
          <h3 style="margin:0">${data.nama}</h3>
          <p style="margin:4px 0 0;color:var(--text3)">${data.nim} \u2022 ${data.nama_kelas || '-'}</p>
        </div>
        <div class="tbl-wrap">
          <table>
            <thead><tr><th>Mata Kuliah</th><th>SKS</th><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Tanpa Keterangan</th><th>Persentase</th></tr></thead>
            <tbody>${mkHtml || '<tr><td colspan="7" style="text-align:center">Tidak ada data</td></tr>'}</tbody>
          </table>
        </div>
      `;
    }
  });
}

// ════════════════════════════════════════════════
// PENYARING & EKSPOR
// ════════════════════════════════════════════════
function filterTable() {
  const search = document.getElementById('search-input').value.toLowerCase();
  const rows = document.querySelectorAll('#rekap-body tr');
  
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(search) ? '' : 'none';
  });
}

function exportRekap(type) {
  const ta = document.getElementById('filter-ta').value;
  const mk = document.getElementById('filter-mk').value;
  const kelas = document.getElementById('filter-kelas').value;
  
  if (!ta) {
    showNotification('Pilih Semester terlebih dahulu', 'kesalahan');
    return;
  }

  if (type === 'excel') {
    // Excel: submit form seperti biasa
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api/rekap.php';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'export_excel';
    form.appendChild(actionInput);
    
    const taInput = document.createElement('input');
    taInput.type = 'hidden';
    taInput.name = 'ta_id';
    taInput.value = ta;
    form.appendChild(taInput);
    
    if (mk) {
      const mkInput = document.createElement('input');
      mkInput.type = 'hidden';
      mkInput.name = 'mk_id';
      mkInput.value = mk;
      form.appendChild(mkInput);
    }
    if (kelas) {
      const kelasInput = document.createElement('input');
      kelasInput.type = 'hidden';
      kelasInput.name = 'kelas_id';
      kelasInput.value = kelas;
      form.appendChild(kelasInput);
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
  } else {
    // PDF: fetch data dan buka halaman cetak
    showNotification('Menyiapkan data untuk cetak PDF...', 'informasi');
    
    let body = `action=get_rekap_kelas&ta_id=${ta}&page=1&limit=1000`; // ambil data cukup banyak
    if (mk) body += `&mk_id=${mk}`;
    if (kelas) body += `&kelas_id=${kelas}`;
    
    fetch('api/rekap.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: body
    })
    .then(res => res.json())
    .then(response => {
      if (response.success) {
        const data = response.data;
        const mkText = mk ? document.getElementById('filter-mk').options[document.getElementById('filter-mk').selectedIndex].text : 'Semua MK';
        const kelasText = kelas ? document.getElementById('filter-kelas').options[document.getElementById('filter-kelas').selectedIndex].text : 'Semua Kelas';
        const taText = ta ? document.getElementById('filter-ta').options[document.getElementById('filter-ta').selectedIndex].text : '';
        
        // Buka jendela baru untuk cetak
        const printWindow = window.open('', '_blank', 'width=1000,height=800');
        printWindow.document.write(`
          <!DOCTYPE html>
          <html lang="id">
          <head>
            <meta charset="UTF-8">
            <title>Rekap Presensi - ${taText}</title>
            <style>
              body { font-family: 'Segoe UI', sans-serif; padding: 30px; color: #333; }
              h2 { margin:0; }
              .info { color: #666; margin:5px 0 20px; font-size:14px; }
              table { width:100%; border-collapse: collapse; font-size:13px; }
              th, td { padding: 8px 10px; border: 1px solid #ddd; text-align: left; }
              th { background: #f5f5f5; font-weight:600; }
              .text-right { text-align: right; }
              .status-lulus { color: #059669; font-weight:600; }
              .status-tidak { color: #dc2626; font-weight:600; }
              .footer { margin-top:20px; font-size:12px; color:#999; text-align:center; }
              @media print { body { padding:20px; } }
            </style>
          </head>
          <body>
            <h2>Rekap Presensi Mahasiswa</h2>
            <div class="info">${taText} • ${mkText} • ${kelasText} • ${new Date().toLocaleDateString('id-ID')}</div>
            <table>
              <thead>
                <tr>
                  <th>No</th><th>NIM</th><th>Nama</th><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Tanpa Keterangan</th><th>%</th><th>Status</th>
                </tr>
              </thead>
              <tbody>
                ${data.map((m, i) => {
                  const persen = parseFloat(m.persen) || 0;
                  const status = persen >= (m.min_kehadiran||75) ? 'Lulus' : 'Tidak Lulus';
                  const statusClass = status === 'Lulus' ? 'status-lulus' : 'status-tidak';
                  return `<tr>
                    <td>${i+1}</td>
                    <td>${m.nim || '-'}</td>
                    <td>${m.nama || '-'}</td>
                    <td>${m.hadir || 0}</td>
                    <td>${m.izin || 0}</td>
                    <td>${m.sakit || 0}</td>
                    <td>${m.alpha || 0}</td>
                    <td>${persen}%</td>
                    <td class="${statusClass}">${status}</td>
                  </tr>`;
                }).join('')}
              </tbody>
            </table>
            <div class="footer">Dicetak melalui Jejak Kampus • ${new Date().toISOString().split('T')[0]}</div>
            <script>
              window.onload = function() { window.print(); setTimeout(function(){ window.close(); }, 500); }
            <\/script>
          </body>
          </html>
        `);
        printWindow.document.close();
      } else {
        showNotification('Gagal mengambil data untuk PDF', 'kesalahan');
      }
    });
  }
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
  const colors = {
    'berhasil': '#059669',
    'kesalahan': '#dc2626',
    'informasi': '#2563eb',
    'peringatan': '#d97706',
    'info': '#2563eb',
    'success': '#059669',
    'error': '#dc2626'
  };
  
  const bgColor = colors[t] || colors['informasi'];
  
  const existingNotification = document.querySelector('.custom-notification');
  if (existingNotification) existingNotification.remove();
  
  const el = document.createElement('div');
  el.className = 'custom-notification';
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
  
  const icons = {
    'berhasil': '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;"><polyline points="20 6 9 17 4 12"></polyline></svg>',
    'kesalahan': '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
    'informasi': '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
  };
  
  const icon = icons[t] || icons['informasi'] || '';
  el.innerHTML = icon + m;
  
  document.body.appendChild(el);
  
  setTimeout(() => {
    el.style.animation = 'slideOutRight 0.3s ease-in';
    setTimeout(() => el.remove(), 300);
  }, 3500);
}

// Tambahkan CSS untuk animasi
const styleEl = document.createElement('style');
styleEl.textContent = `
  @keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
  }
  
  @keyframes slideOutRight {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
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
  
  .pill-green {
    background: #059669;
    color: #ffffff;
    font-weight: 600;
  }
  
  .custom-notification {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    line-height: 1.4;
  }
`;
document.head.appendChild(styleEl);
</script>

<style>
.tab-content { display: none; }
.tab-content.active { display: block; }
.pct-bar { display: flex; align-items: center; gap: 8px; }
.pct-track { width: 60px; height: 6px; background: var(--surface2); border-radius: 3px; overflow: hidden; }
.pct-fill { height: 100%; border-radius: 3px; }
</style>

<?php require_once __DIR__ . '/footer.php'; ?>