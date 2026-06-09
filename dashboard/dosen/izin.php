<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/navigasi.php';
?>

<!-- ═══════════════════════════════════════════════
     PERSETUJUAN IZIN - Dinamis
═══════════════════════════════════════════════ -->
<div id="app-izin">
  <div class="page-header">
    <div>
      <div class="page-subtitle">Tinjau dan setujui pengajuan izin atau sakit mahasiswa</div>
    </div>
  </div>

  <!-- Bilah Penyaring -->
  <div class="filter-bar">
    <label>Mata Kuliah</label>
    <select id="filter-mk" onchange="loadData()">
      <option value="">Semua Mata Kuliah</option>
    </select>
    <label style="margin-left:8px">Kelas</label>
    <select id="filter-kelas" onchange="loadData()">
      <option value="">Semua Kelas</option>
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

  <!-- Navigasi Tab -->
  <div id="tab-group" class="tab-nav">
    <button class="tab-btn active" data-tab="tab-pending" onclick="switchTab('tab-pending')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
        <circle cx="12" cy="12" r="10"></circle>
        <polyline points="12 6 12 12 16 14"></polyline>
      </svg>
      Tertunda <span class="tab-badge" id="badge-pending">0</span>
    </button>
    <button class="tab-btn" data-tab="tab-riwayat" onclick="switchTab('tab-riwayat')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
        <line x1="8" y1="6" x2="21" y2="6"></line>
        <line x1="8" y1="12" x2="21" y2="12"></line>
        <line x1="8" y1="18" x2="21" y2="18"></line>
        <line x1="3" y1="6" x2="3.01" y2="6"></line>
        <line x1="3" y1="12" x2="3.01" y2="12"></line>
        <line x1="3" y1="18" x2="3.01" y2="18"></line>
      </svg>
      Riwayat Persetujuan
    </button>
  </div>

  <!-- TAB: TERTUNDA -->
  <div class="tab-content active" id="tab-pending">
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>No</th><th>Tanggal Izin</th><th>NIM</th><th>Nama</th><th>Kelas</th><th>Mata Kuliah</th><th>Pertemuan</th><th>Jenis</th><th>Keterangan</th><th>Bukti</th><th>Tindakan</th>
          </tr>
        </thead>
        <tbody id="pending-body">
          <tr><td colspan="11" style="text-align:center;padding:40px">Memuat data...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- TAB: RIWAYAT -->
  <div class="tab-content" id="tab-riwayat">
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>No</th><th>Tanggal Izin</th><th>NIM</th><th>Nama</th><th>Kelas</th><th>Mata Kuliah</th><th>Pertemuan</th><th>Jenis</th><th>Status</th><th>Catatan</th><th>Waktu Persetujuan</th>
          </tr>
        </thead>
        <tbody id="riwayat-body">
          <tr><td colspan="11" style="text-align:center;padding:40px">Pilih tab untuk melihat data</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL: Tolak -->
<div class="modal-overlay" id="modal-reject">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div class="modal-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px;">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="15" y1="9" x2="9" y2="15"></line>
          <line x1="9" y1="9" x2="15" y2="15"></line>
        </svg>
        Tolak Izin
      </div>
      <button class="btn-close-modal" onclick="closeModal('modal-reject')">&times;</button>
    </div>
    <div class="modal-body">
      <form id="form-reject" onsubmit="doReject(event)">
        <input type="hidden" id="reject-id">
        <div id="reject-info" style="margin-bottom:14px;padding:10px;background:var(--surface2);border-radius:8px;font-size:13px"></div>
        <div class="form-group">
          <label>Alasan Penolakan <span style="color:var(--red)">*</span></label>
          <textarea id="reject-catatan" required placeholder="Jelaskan alasan penolakan..." rows="3"></textarea>
        </div>
        <div class="modal-footer" style="border:none;padding:0;margin-top:14px">
          <button type="button" class="btn btn-secondary" onclick="closeModal('modal-reject')">Batal</button>
          <button type="submit" class="btn btn-danger">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="15" y1="9" x2="9" y2="15"></line>
              <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
            Tolak
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// ════════════════════════════════════════════════
// VARIABEL GLOBAL
// ════════════════════════════════════════════════
let pendingData = [];

// ════════════════════════════════════════════════
// INISIALISASI
// ════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
  loadFilterOptions();
  loadData();
});

// ════════════════════════════════════════════════
// PERPINDAHAN TAB
// ════════════════════════════════════════════════
function switchTab(tabId) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
  
  document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
  document.getElementById(tabId).classList.add('active');
  
  if (tabId === 'tab-riwayat') loadRiwayat();
}

// ════════════════════════════════════════════════
// MEMUAT OPSI PENYARING
// ════════════════════════════════════════════════
function loadFilterOptions() {
  fetch('api/izin.php', {
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
// MEMUAT DATA (TERTUNDA)
// ════════════════════════════════════════════════
function loadData() {
  const mk = document.getElementById('filter-mk').value;
  const kelas = document.getElementById('filter-kelas').value;
  
  document.getElementById('pending-body').innerHTML = '<tr><td colspan="11" style="text-align:center;padding:40px">Memuat data...</td></tr>';
  
  let body = 'action=get_pending';
  if (mk) body += `&mk_id=${mk}`;
  if (kelas) body += `&kelas_id=${kelas}`;
  
  fetch('api/izin.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: body
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      pendingData = response.data;
      renderPending(response.data);
      document.getElementById('badge-pending').textContent = response.count || 0;
    }
  });
}

function renderPending(data) {
  const tbody = document.getElementById('pending-body');
  
  // Ikon SVG untuk jenis
  const sakitIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>';
  const izinIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>';
  const mataIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
  const setujuiIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;"><polyline points="20 6 9 17 4 12"></polyline></svg>';
  const tolakIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
  
  if (data.length === 0) {
    tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:40px"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;"><polyline points="20 6 9 17 4 12"></polyline></svg>Tidak ada pengajuan tertunda</td></tr>';
    return;
  }
  
  const jenisMap = {
    'sakit': { text: sakitIcon + ' Sakit', class: 'pill-ora' },
    'izin': { text: izinIcon + ' Izin', class: 'pill-blue' }
  };
  
  let html = '';
  data.forEach((d, i) => {
    const j = jenisMap[d.jenis] || { text: d.jenis, class: 'pill-gray' };
    const buktiBtn = d.file_surat 
      ? `<a href="/uploads/izin/${d.file_surat}" target="_blank" class="btn btn-info btn-xs">${mataIcon}Lihat</a>`
      : '<span style="color:var(--text3);font-size:11px">Tanpa bukti</span>';
    
    // Escape nama untuk onclick
    const namaMhsEscaped = d.nama_mhs.replace(/'/g, "\\'").replace(/"/g, '&quot;');
    const namaMkEscaped = d.nama_mk.replace(/'/g, "\\'").replace(/"/g, '&quot;');
    
    html += `<tr>
      <td>${i+1}</td>
      <td>${d.tanggal_izin || d.sesi_tanggal || '-'}</td>
      <td><code>${d.nim}</code></td>
      <td style="font-weight:600">${d.nama_mhs}</td>
      <td>${d.nama_kelas}</td>
      <td>${d.nama_mk}</td>
      <td>Ke-${d.pertemuan_ke}</td>
      <td><span class="pill ${j.class}" style="font-size:11px">${j.text}</span></td>
      <td style="max-width:150px;font-size:12px">${d.keterangan || '&mdash;'}</td>
      <td>${buktiBtn}</td>
      <td>
        <div style="display:flex;gap:5px">
          <button class="btn btn-success btn-xs" onclick="approve(${d.id})">${setujuiIcon}Setujui</button>
          <button class="btn btn-danger btn-xs" onclick="openRejectModal(${d.id}, '${namaMhsEscaped}', '${namaMkEscaped}', ${d.pertemuan_ke})">${tolakIcon}Tolak</button>
        </div>
      </td>
    </tr>`;
  });
  
  tbody.innerHTML = html;
}

// ════════════════════════════════════════════════
// MENYETUJUI
// ════════════════════════════════════════════════
function approve(izinId) {
  if (!confirm('Setujui izin ini?')) return;
  
  fetch('api/izin.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=approve&izin_id=${izinId}`
  })
  .then(res => res.json())
  .then(response => {
    showNotification(response.msg, response.success ? 'berhasil' : 'kesalahan');
    if (response.success) loadData();
  });
}

// ════════════════════════════════════════════════
// MODAL TOLAK & TINDAKAN
// ════════════════════════════════════════════════
function openRejectModal(izinId, nama, mk, pertemuan) {
  document.getElementById('reject-id').value = izinId;
  document.getElementById('reject-info').innerHTML = `
    <strong>${nama}</strong> \u2022 ${mk}<br>
    <span style="font-size:12px;color:var(--text3)">Pertemuan ke-${pertemuan}</span>
  `;
  document.getElementById('reject-catatan').value = '';
  openModal('modal-reject');
}

function doReject(event) {
  event.preventDefault();
  
  const izinId = document.getElementById('reject-id').value;
  const catatan = document.getElementById('reject-catatan').value;
  
  fetch('api/izin.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=reject&izin_id=${izinId}&catatan=${encodeURIComponent(catatan)}`
  })
  .then(res => res.json())
  .then(response => {
    showNotification(response.msg, response.success ? 'berhasil' : 'kesalahan');
    if (response.success) {
      closeModal('modal-reject');
      loadData();
    }
  });
}

// ════════════════════════════════════════════════
// MEMUAT RIWAYAT
// ════════════════════════════════════════════════
function loadRiwayat() {
  const mk = document.getElementById('filter-mk').value;
  const kelas = document.getElementById('filter-kelas').value;
  
  document.getElementById('riwayat-body').innerHTML = '<tr><td colspan="11" style="text-align:center;padding:40px">Memuat data...</td></tr>';
  
  let body = 'action=get_riwayat';
  if (mk) body += `&mk_id=${mk}`;
  if (kelas) body += `&kelas_id=${kelas}`;
  
  fetch('api/izin.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: body
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) renderRiwayat(response.data);
  });
}

function renderRiwayat(data) {
  const tbody = document.getElementById('riwayat-body');
  
  // Ikon SVG
  const disetujuiIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;"><polyline points="20 6 9 17 4 12"></polyline></svg>';
  const ditolakIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
  const sakitIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>';
  const izinIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 3px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>';
  
  if (data.length === 0) {
    tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:40px">Belum ada riwayat persetujuan</td></tr>';
    return;
  }
  
  const statusMap = {
    'disetujui': { text: disetujuiIcon + ' Disetujui', class: 'pill-green' },
    'ditolak': { text: ditolakIcon + ' Ditolak', class: 'pill-red' }
  };
  const jenisMap = {
    'sakit': { text: sakitIcon + ' Sakit', class: 'pill-ora' },
    'izin': { text: izinIcon + ' Izin', class: 'pill-blue' }
  };
  
  let html = '';
  data.forEach((d, i) => {
    const s = statusMap[d.status] || { text: d.status, class: 'pill-gray' };
    const j = jenisMap[d.jenis] || { text: d.jenis, class: 'pill-gray' };
    
    html += `<tr>
      <td>${i+1}</td>
      <td>${d.tanggal_izin || '-'}</td>
      <td><code>${d.nim}</code></td>
      <td style="font-weight:600">${d.nama_mhs}</td>
      <td>${d.nama_kelas}</td>
      <td>${d.nama_mk}</td>
      <td>Ke-${d.pertemuan_ke}</td>
      <td><span class="pill ${j.class}" style="font-size:11px">${j.text}</span></td>
      <td><span class="pill ${s.class}" style="font-size:11px">${s.text}</span></td>
      <td style="max-width:200px;font-size:12px">${d.catatan || '&mdash;'}</td>
      <td style="font-size:11px;color:var(--text3)">${d.approved_at ? d.approved_at.substring(0,16) : '&mdash;'}</td>
    </tr>`;
  });
  
  tbody.innerHTML = html;
}

// ════════════════════════════════════════════════
// UTILITAS
// ════════════════════════════════════════════════
function resetFilter() {
  document.getElementById('filter-mk').value = '';
  document.getElementById('filter-kelas').value = '';
  loadData();
}

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
  
  .custom-notification {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    line-height: 1.4;
  }
`;
document.head.appendChild(styleEl);
</script>

<style>
.pill-yellow { background: #f59e0b; color: #ffffff; font-size:11px; padding:2px 8px; border-radius:10px; font-weight:600; }
.pill-blue { background: #3b82f6; color: #ffffff; font-size:11px; padding:2px 8px; border-radius:10px; font-weight:600; }
.pill-green { background: #059669; color: #ffffff; font-size:11px; padding:2px 8px; border-radius:10px; font-weight:600; }
.pill-red { background: #dc2626; color: #ffffff; font-size:11px; padding:2px 8px; border-radius:10px; font-weight:600; }
.pill-ora { background: #ea580c; color: #ffffff; font-size:11px; padding:2px 8px; border-radius:10px; font-weight:600; }
.pill-gray { background: #6b7280; color: #ffffff; font-size:11px; padding:2px 8px; border-radius:10px; font-weight:600; }
</style>

<?php require_once __DIR__ . '/footer.php'; ?>