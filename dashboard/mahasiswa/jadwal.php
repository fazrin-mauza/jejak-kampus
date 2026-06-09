<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/navigasi.php';

// Get mahasiswa data from session/auth
$user_id = $user['id'] ?? 0;
$mahasiswa_id = 0;
$kelas_id = 0;
$nama_kelas = '';
$semester_aktif = '';

if ($user_id) {
    $stmt = $conn->prepare("SELECT m.id, m.kelas_id, k.nama_kelas FROM mahasiswa m LEFT JOIN kelas k ON m.kelas_id = k.id WHERE m.user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($mahasiswa_id, $kelas_id, $nama_kelas);
    $stmt->fetch();
    $stmt->close();
}

// Get semester aktif
$ta = $conn->query("SELECT tahun, semester FROM tahun_akademik WHERE status = 'aktif' LIMIT 1");
if ($ta && $row = $ta->fetch_assoc()) {
    $semester_aktif = $row['tahun'] . ' ' . $row['semester'];
}
?>

<div class="section active" id="sec-jadwal">
  <div class="page-header">
    <div>
      <div class="page-subtitle">Jadwal perkuliahan semester <?= htmlspecialchars($semester_aktif) ?> &bull; <?= htmlspecialchars($nama_kelas) ?></div>
    </div>
    <div class="header-actions">
      <button class="btn btn-secondary btn-sm" onclick="exportJadwal()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
          <polyline points="7 10 12 15 17 10"></polyline>
          <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
        Ekspor
      </button>
      <button class="btn btn-primary btn-sm" onclick="cetakJadwal()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
          <polyline points="6 9 6 2 18 2 18 9"></polyline>
          <path d="M6 12H4a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2"></path>
          <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        Cetak
      </button>
    </div>
  </div>

  <!-- Bilah Penyaring -->
  <div class="filter-bar">
    <label>Pilih Hari</label>
    <select id="filter-hari" onchange="filterHari(this.value)">
      <option value="semua">Semua Hari</option>
      <option value="Senin">Senin</option>
      <option value="Selasa">Selasa</option>
      <option value="Rabu">Rabu</option>
      <option value="Kamis">Kamis</option>
      <option value="Jumat">Jumat</option>
    </select>
    <div style="margin-left:auto;display:flex;gap:8px">
      <button class="btn btn-secondary btn-xs" id="btn-view-list" onclick="setView('list')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;">
          <line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line>
        </svg>
        Daftar
      </button>
      <button class="btn btn-secondary btn-xs" id="btn-view-table" onclick="setView('table')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line><line x1="9" y1="3" x2="9" y2="21"></line>
        </svg>
        Tabel
      </button>
    </div>
  </div>

  <!-- Statistik -->
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px">
    <div class="mini-stat"><div class="mini-stat-label">Total SKS</div><div class="mini-stat-val" style="color:var(--ora4)" id="stat-sks">0</div></div>
    <div class="mini-stat"><div class="mini-stat-label">Mata Kuliah</div><div class="mini-stat-val" style="color:var(--blue)" id="stat-mk">0</div></div>
    <div class="mini-stat"><div class="mini-stat-label">Hari Kuliah</div><div class="mini-stat-val" style="color:var(--green)" id="stat-hari">0</div></div>
  </div>

  <!-- Tampilan Daftar -->
  <div id="view-list">
    <div id="jadwal-list-container">
      <div style="text-align:center;padding:40px;color:var(--text3)">Memuat jadwal...</div>
    </div>
  </div>

  <!-- Tampilan Tabel -->
  <div id="view-table" style="display:none">
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr><th>Hari</th><th>Jam</th><th>Mata Kuliah</th><th>SKS</th><th>Dosen</th><th>Ruangan</th><th>Status</th></tr>
        </thead>
        <tbody id="table-body">
          <tr><td colspan="7" style="text-align:center;padding:40px">Memuat data...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
// ════════════════════════════════════════════════
// VARIABEL GLOBAL
// ════════════════════════════════════════════════
let jadwalData = [];
let currentView = 'list';
let currentFilter = 'semua';
let sesiAktifList = [];

// ════════════════════════════════════════════════
// INISIALISASI
// ════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
  loadJadwal();
  loadSesiAktif();
  
  // Tombol tampilan aktif
  document.getElementById('btn-view-list').style.background = 'var(--ora1)';
  document.getElementById('btn-view-list').style.color = 'white';
});

// ════════════════════════════════════════════════
// MEMUAT JADWAL
// ════════════════════════════════════════════════
function loadJadwal() {
  fetch('api/jadwal.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_jadwal'
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      jadwalData = response.data;
      updateStats(response.data);
      renderListView(response.data);
      renderTableView(response.data);
    } else {
      document.getElementById('jadwal-list-container').innerHTML = 
        '<div style="text-align:center;padding:40px;color:var(--text3)">Gagal memuat jadwal</div>';
    }
  });
}

function loadSesiAktif() {
  fetch('api/jadwal.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_sesi_aktif'
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      sesiAktifList = response.data;
      if (jadwalData.length > 0) {
        renderListView(jadwalData);
        renderTableView(jadwalData);
      }
    }
  });
}

// ════════════════════════════════════════════════
// STATISTIK
// ════════════════════════════════════════════════
function updateStats(data) {
  let totalSKS = 0;
  let mkSet = new Set();
  let hariSet = new Set();
  
  data.forEach(j => {
    totalSKS += parseInt(j.sks) || 0;
    mkSet.add(j.mata_kuliah_id);
    hariSet.add(j.hari);
  });
  
  document.getElementById('stat-sks').textContent = totalSKS;
  document.getElementById('stat-mk').textContent = mkSet.size;
  document.getElementById('stat-hari').textContent = hariSet.size;
}

// ════════════════════════════════════════════════
// TAMPILAN DAFTAR
// ════════════════════════════════════════════════
function renderListView(data) {
  const container = document.getElementById('jadwal-list-container');
  
  let filteredData = data;
  if (currentFilter !== 'semua') {
    filteredData = data.filter(j => j.hari === currentFilter);
  }
  
  if (filteredData.length === 0) {
    container.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text3)">Tidak ada jadwal</div>';
    return;
  }
  
  const hariOrder = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
  const grouped = {};
  hariOrder.forEach(h => grouped[h] = []);
  filteredData.forEach(j => {
    if (grouped[j.hari]) grouped[j.hari].push(j);
  });
  
  const today = new Date().toLocaleDateString('id-ID', { weekday: 'long' });
  
  // Ikon SVG
  const dosenIcon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';
  const lokasiIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 2px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>';
  const bukuIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 2px;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>';
  const scanIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><rect x="7" y="7" width="3" height="3"></rect><rect x="14" y="7" width="3" height="3"></rect><rect x="7" y="14" width="3" height="3"></rect><rect x="14" y="14" width="3" height="3"></rect></svg>';
  
  let html = '';
  hariOrder.forEach(hari => {
    const jadwals = grouped[hari];
    if (jadwals.length === 0) return;
    
    jadwals.sort((a, b) => (a.jam_mulai || '').localeCompare(b.jam_mulai || ''));
    
    const isToday = (hari.toLowerCase() === today.toLowerCase());
    
    html += `<div class="hari-group" data-hari="${hari}">
      <div class="hari-label" style="${isToday ? 'background:linear-gradient(90deg,#E8FAF4,#C8F5E4);color:var(--green)' : ''}">
        ${hari}${isToday ? ' &larr; Hari ini' : ''}
      </div>`;
    
    jadwals.forEach(j => {
      const jamMulai = j.jam_mulai ? j.jam_mulai.substring(0, 5) : '--:--';
      const jamSelesai = j.jam_selesai ? j.jam_selesai.substring(0, 5) : '--:--';
      
      const sesiAktif = sesiAktifList.find(s => s.jadwal_id == j.id);
      const isActive = sesiAktif !== undefined;
      
      let statusHtml = '';
      let actionHtml = '';
      
      if (isActive) {
        statusHtml = '<span class="pill pill-green"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#fff;margin-right:5px;animation:blink 1s infinite"></span>Aktif</span>';
        actionHtml = `<a href="absensi.php?scan=${sesiAktif.sesi_id}" class="btn btn-primary btn-xs">${scanIcon}Pindai</a>`;
      } else if (j.sesi_terakhir) {
        statusHtml = `<span class="pill pill-blue"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px"><polyline points="20 6 9 17 4 12"/></svg>Selesai P-${j.sesi_terakhir}</span>`;
        actionHtml = '';
      } else {
        statusHtml = '<span class="pill pill-gray">Belum Dimulai</span>';
        actionHtml = '';
      }
      
      const sksClass = j.sks == 3 ? 'pill-ora' : (j.sks >= 4 ? 'pill-purple' : 'pill-blue');
      const isTodayClass = isToday ? 'today' : '';
      
      html += `<div class="jadwal-row ${isTodayClass}">
        <div class="jdwl-time" ${isActive ? 'style="background:linear-gradient(135deg,var(--green-bg),#c8f5e4)"' : ''}>
          <div class="jdwl-time-start" ${isActive ? 'style="color:var(--green)"' : ''}>${jamMulai}</div>
          <div class="jdwl-time-end" ${isActive ? 'style="color:var(--green)"' : ''}>${jamSelesai}</div>
        </div>
        <div class="jdwl-body">
          <div class="jdwl-matkul">${j.nama_mk}</div>
          <div class="jdwl-dosen">${dosenIcon} ${j.nama_dosen}</div>
          <div class="jdwl-meta">
            <span>${lokasiIcon} ${j.nama_ruangan || '-'} (${j.kode_ruangan || '-'})</span>
            <span>${bukuIcon} ${j.sks} SKS</span>
          </div>
        </div>
        <div class="jdwl-right">
          <span class="pill ${sksClass}">${j.sks} SKS</span>
          ${statusHtml}
          ${actionHtml}
        </div>
      </div>`;
    });
    
    html += '</div>';
  });
  
  container.innerHTML = html;
}

// ════════════════════════════════════════════════
// TAMPILAN TABEL
// ════════════════════════════════════════════════
function renderTableView(data) {
  const tbody = document.getElementById('table-body');
  
  let filteredData = data;
  if (currentFilter !== 'semua') {
    filteredData = data.filter(j => j.hari === currentFilter);
  }
  
  if (filteredData.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px">Tidak ada jadwal</td></tr>';
    return;
  }
  
  const hariOrder = { 'Senin': 1, 'Selasa': 2, 'Rabu': 3, 'Kamis': 4, 'Jumat': 5, 'Sabtu': 6 };
  filteredData.sort((a, b) => {
    if (hariOrder[a.hari] !== hariOrder[b.hari]) return hariOrder[a.hari] - hariOrder[b.hari];
    return (a.jam_mulai || '').localeCompare(b.jam_mulai || '');
  });
  
  const today = new Date().toLocaleDateString('id-ID', { weekday: 'long' });
  
  let html = '';
  filteredData.forEach(j => {
    const jamMulai = j.jam_mulai ? j.jam_mulai.substring(0, 5) : '--:--';
    const jamSelesai = j.jam_selesai ? j.jam_selesai.substring(0, 5) : '--:--';
    const isToday = (j.hari.toLowerCase() === today.toLowerCase());
    const sesiAktif = sesiAktifList.find(s => s.jadwal_id == j.id);
    
    let statusText = sesiAktif ? 'Aktif' : (j.sesi_terakhir ? 'Selesai' : 'Belum');
    let statusClass = sesiAktif ? 'pill-green' : (j.sesi_terakhir ? 'pill-blue' : 'pill-gray');
    
    html += `<tr ${isToday ? 'style="background:var(--green-bg)"' : ''}>
      <td>${j.hari} ${isToday ? '<span style="color:var(--green);font-size:10px">&#9733;</span>' : ''}</td>
      <td><code>${jamMulai}&ndash;${jamSelesai}</code></td>
      <td style="font-weight:600">${j.nama_mk}</td>
      <td>${j.sks}</td>
      <td>${j.nama_dosen}</td>
      <td>${j.nama_ruangan || '-'} (${j.kode_ruangan || '-'})</td>
      <td><span class="pill ${statusClass}">${statusText}</span></td>
    </tr>`;
  });
  
  tbody.innerHTML = html;
}

// ════════════════════════════════════════════════
// PENYARING & TAMPILAN
// ════════════════════════════════════════════════
function filterHari(value) {
  currentFilter = value;
  renderListView(jadwalData);
  renderTableView(jadwalData);
}

function setView(view) {
  currentView = view;
  
  document.getElementById('view-list').style.display = view === 'list' ? 'block' : 'none';
  document.getElementById('view-table').style.display = view === 'table' ? 'block' : 'none';
  
  const btnList = document.getElementById('btn-view-list');
  const btnTable = document.getElementById('btn-view-table');
  
  if (view === 'list') {
    btnList.style.background = 'var(--ora1)';
    btnList.style.color = 'white';
    btnTable.style.background = '';
    btnTable.style.color = '';
  } else {
    btnTable.style.background = 'var(--ora1)';
    btnTable.style.color = 'white';
    btnList.style.background = '';
    btnList.style.color = '';
  }
}

// ════════════════════════════════════════════════
// EKSPOR
// ════════════════════════════════════════════════
function exportJadwal() {
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'api/jadwal.php';
  
  const input = document.createElement('input');
  input.type = 'hidden';
  input.name = 'action';
  input.value = 'export_csv';
  form.appendChild(input);
  
  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);
  
  showNotification('Ekspor sedang diproses...', 'informasi');
}

// ════════════════════════════════════════════════
// CETAK JADWAL
// ════════════════════════════════════════════════
function cetakJadwal() {
  const semester = '<?= htmlspecialchars($semester_aktif) ?>';
  const kelas = '<?= htmlspecialchars($nama_kelas) ?>';
  const today = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
  
  const hariOrder = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
  
  // Bangun tabel HTML untuk cetak
  let rowsHtml = '';
  let no = 1;
  
  hariOrder.forEach(hari => {
    const jadwals = jadwalData.filter(j => j.hari === hari);
    if (jadwals.length === 0) return;
    
    jadwals.sort((a, b) => (a.jam_mulai || '').localeCompare(b.jam_mulai || ''));
    
    jadwals.forEach(j => {
      const jamMulai = j.jam_mulai ? j.jam_mulai.substring(0, 5) : '--:--';
      const jamSelesai = j.jam_selesai ? j.jam_selesai.substring(0, 5) : '--:--';
      
      rowsHtml += `<tr>
        <td>${no}</td>
        <td>${hari}</td>
        <td>${jamMulai} &ndash; ${jamSelesai}</td>
        <td>${j.nama_mk}</td>
        <td>${j.sks}</td>
        <td>${j.nama_dosen}</td>
        <td>${j.nama_ruangan || '-'} (${j.kode_ruangan || '-'})</td>
      </tr>`;
      no++;
    });
  });
  
  const printWindow = window.open('', '_blank', 'width=1000,height=800');
  printWindow.document.write(`
    <!DOCTYPE html>
    <html lang="id">
    <head>
      <meta charset="UTF-8">
      <title>Jadwal Kuliah - ${kelas}</title>
      <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; padding: 30px; color: #1f2937; }
        .header { text-align: center; margin-bottom: 24px; }
        .header h2 { margin: 0 0 4px; font-size: 20px; }
        .header p { margin: 0; color: #6b7280; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #f3f4f6; padding: 10px 8px; border: 1px solid #d1d5db; text-align: left; font-weight: 600; }
        td { padding: 8px; border: 1px solid #e5e7eb; }
        tr:nth-child(even) { background: #f9fafb; }
        .footer { margin-top: 20px; text-align: center; font-size: 11px; color: #9ca3af; }
        @media print { body { padding: 15px; } }
      </style>
    </head>
    <body>
      <div class="header">
        <h2>Jadwal Perkuliahan</h2>
        <p>Semester ${semester} &bull; ${kelas} &bull; Dicetak pada ${today}</p>
      </div>
      <table>
        <thead>
          <tr><th>No</th><th>Hari</th><th>Jam</th><th>Mata Kuliah</th><th>SKS</th><th>Dosen</th><th>Ruangan</th></tr>
        </thead>
        <tbody>${rowsHtml}</tbody>
      </table>
      <div class="footer">Dicetak melalui Jejak Kampus</div>
      <script>
        window.onload = function() { window.print(); setTimeout(function(){ window.close(); }, 500); }
      <\/script>
    </body>
    </html>
  `);
  printWindow.document.close();
}

// ════════════════════════════════════════════════
// NOTIFIKASI CUSTOM
// ════════════════════════════════════════════════
function showNotification(m, t = 'informasi') {
  const colors = {
    'berhasil': '#059669',
    'kesalahan': '#dc2626',
    'informasi': '#2563eb',
    'peringatan': '#d97706'
  };
  
  const bgColor = colors[t] || colors['informasi'];
  
  const existing = document.querySelector('.custom-notification');
  if (existing) existing.remove();
  
  const el = document.createElement('div');
  el.className = 'custom-notification';
  el.style.cssText = `position:fixed;top:20px;right:20px;padding:14px 24px;background:${bgColor};color:#fff;border-radius:10px;z-index:10000;box-shadow:0 8px 24px rgba(0,0,0,0.2);font-size:14px;font-weight:600;max-width:400px;word-wrap:break-word;animation:slideInRight 0.3s ease-out`;
  
  const icons = {
    'berhasil': '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> ',
    'kesalahan': '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> ',
    'informasi': '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg> '
  };
  
  el.innerHTML = (icons[t] || icons['informasi']) + m;
  document.body.appendChild(el);
  
  setTimeout(() => {
    el.style.animation = 'slideOutRight 0.3s ease-in';
    setTimeout(() => el.remove(), 300);
  }, 3500);
}

// CSS Animasi
const styleEl = document.createElement('style');
styleEl.textContent = `
  @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
  @keyframes slideOutRight { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
  @keyframes blink { 0%,100% { opacity:1; } 50% { opacity:0.3; } }
  .pill-purple { background: #a855f7; color: #fff; font-weight: 600; }
`;
document.head.appendChild(styleEl);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>