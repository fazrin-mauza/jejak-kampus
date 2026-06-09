<?php
require_once 'header.php';
require_once 'navigasi.php';
?>

<style>
/* Perbaikan tampilan tabel dan card */
.tbl-wrap {
    background: #FFFFFF;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.tabel thead tr {
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
}
.tabel th {
    font-weight: 600;
    font-size: 13px;
    color: #1E293B;
    padding: 12px 16px;
}
.tabel td {
    padding: 12px 16px;
    border-bottom: 1px solid #F1F5F9;
}
.tabel tr:hover {
    background: #F8FAFC;
}


/* Perbaikan button aksi */
.btn-xs {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
</style>

<!-- ═══════════════════════════════════════════════
     MANAJEMEN MAHASISWA - Dynamic dengan AJAX + Import CSV
     Format Tanggal: DD/MM/YYYY (support tanpa leading zero)
═══════════════════════════════════════════════ -->
<div id="app-mahasiswa">
    <div class="page-header">
        <div>
         
            <div class="page-subtitle">Kelola data seluruh mahasiswa terdaftar</div>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary btn-sm" onclick="downloadTemplate()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                Templat
            </button>
            <button class="btn btn-secondary btn-sm" onclick="openModal('modal-import','Import Data CSV')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Import CSV
            </button>
            <button class="btn btn-secondary btn-sm" onclick="exportData()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                    <polyline points="7 11 12 16 17 11"/>
                    <line x1="12" y1="16" x2="12" y2="4"/>
                </svg>
                Ekspor
            </button>
            <button class="btn btn-primary" onclick="openModal('modal-mhs-form','Tambah Mahasiswa')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Tambah
            </button>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="tab-nav">
        <button class="tab-btn active" data-status="all" onclick="filterByStatus('all')">
            Semua (<span id="stat-total">0</span>)
        </button>
        <button class="tab-btn" data-status="aktif" onclick="filterByStatus('aktif')">
            Aktif (<span id="stat-aktif">0</span>)
        </button>
        <button class="tab-btn" data-status="cuti" onclick="filterByStatus('cuti')">
            Cuti (<span id="stat-cuti">0</span>)
        </button>
        <button class="tab-btn" data-status="lulus" onclick="filterByStatus('lulus')">
            Lulus (<span id="stat-lulus">0</span>)
        </button>
        <button class="tab-btn" data-status="dropout" onclick="filterByStatus('dropout')">
            Dropout (<span id="stat-dropout">0</span>)
        </button>
    </div>

    <!-- Toolbar -->
    <div class="tbl-toolbar">
        <div class="search-box">
            <span class="search-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </span>
            <input type="text" id="search-input" placeholder="Cari NIM, nama, atau email mahasiswa..." onkeyup="debounceSearch()">
        </div>
        <div style="display:flex;gap:8px">
            <select id="filter-kelas" style="padding:8px 12px;border:1.5px solid var(--border-s);border-radius:var(--r-sm);font-family:inherit;font-size:12.5px" onchange="loadData()">
                <option value="">Semua Kelas</option>
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="tbl-wrap">
        <table class="tabel">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>NIM</th>
                    <th>Nama Lengkap</th>
                    <th>Kelas</th>
                    <th>Program Studi</th>
                    <th>Status</th>
                    <th>Kehadiran</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <tr><td colspan="8" style="text-align:center;padding:40px">Memuat data...</td</tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination" id="pagination">
        <span class="page-info" id="page-info">Menampilkan 0-0 dari 0 data</span>
        <div id="page-buttons"></div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════
     MODALS - Mahasiswa
═══════════════════════════════════════════════ -->

<!-- Modal: Mahasiswa Form -->
<div class="modal-overlay" id="modal-mhs-form">
    <div class="modal modal-lg">
        <div class="modal-header">
            <div class="modal-title" id="modal-mhs-form-title">Tambah Mahasiswa</div>
            <button class="btn-close-modal" onclick="closeModal('modal-mhs-form')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form id="form-mahasiswa" onsubmit="saveMahasiswa(event)">
                <input type="hidden" name="id" id="mhs-id">
                <div class="form-row">
                    <div class="form-group">
                        <label>NIM <span style="color:var(--red)">*</span></label>
                        <input type="text" name="nim" id="mhs-nim" placeholder="Nomor Induk Mahasiswa" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap <span style="color:var(--red)">*</span></label>
                        <input type="text" name="nama" id="mhs-nama" placeholder="Nama sesuai kartu identitas" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal Lahir <span style="color:var(--red)">*</span></label>
                        <input type="text" name="tanggal_lahir" id="mhs-tgl" placeholder="DD/MM/YYYY" required>
                        <small style="color:var(--text3);font-size:11px">Contoh: 15/05/2003 atau 3/5/2003</small>
                    </div>
                    <div class="form-group">
                        <label>Jenis Kelamin <span style="color:var(--red)">*</span></label>
                        <select name="jenis_kelamin" id="mhs-jk" required>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Kelas <span style="color:var(--red)">*</span></label>
                        <select name="kelas_id" id="mhs-kelas" required>
                            <option value="">Pilih Kelas</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status <span style="color:var(--red)">*</span></label>
                        <select name="status" id="mhs-status" required>
                            <option value="aktif">Aktif</option>
                            <option value="cuti">Cuti</option>
                            <option value="lulus">Lulus</option>
                            <option value="dropout">Dropout</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Alamat Email (untuk akun login) <span style="color:var(--red)">*</span></label>
                    <input type="email" name="email" id="mhs-email" placeholder="nim@student.unesa.ac.id" required>
                    <small style="color:var(--text3);font-size:11px">Kata sandi default: NIM</small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-mhs-form')">Batal</button>
            <button class="btn btn-primary" onclick="document.getElementById('form-mahasiswa').requestSubmit()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>
                Simpan
            </button>
        </div>
    </div>
</div>

<!-- Modal: Mahasiswa Detail -->
<div class="modal-overlay" id="modal-mhs-detail">
    <div class="modal modal-lg">
        <div class="modal-header">
            <div class="modal-title">Detail Mahasiswa</div>
            <button class="btn-close-modal" onclick="closeModal('modal-mhs-detail')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body" id="detail-content">
            <!-- Dynamic content -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-mhs-detail')">Tutup</button>
            <button class="btn btn-info" id="btn-edit-from-detail">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <path d="M17 3l4 4L7 21H3v-4L17 3z"/>
                </svg>
                Edit
            </button>
            <button class="btn btn-warning" id="btn-reset-password">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <path d="M21 2v6h-6M3 12a9 9 0 0 1 15-6.7L21 8M3 22v-6h6M21 12a9 9 0 0 1-15 6.7L3 16"/>
                </svg>
                Atur Ulang Kata Sandi
            </button>
        </div>
    </div>
</div>

<!-- Modal: Import CSV -->
<div class="modal-overlay" id="modal-import">
    <div class="modal modal-sm">
        <div class="modal-header">
            <div class="modal-title">Import Data CSV</div>
            <button class="btn-close-modal" onclick="closeModal('modal-import')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div style="border:2px dashed var(--border-s);border-radius:var(--r);padding:32px;text-align:center;background:var(--surface2);cursor:pointer" onclick="document.getElementById('file-import').click()">
                <div style="margin-bottom:8px">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#F97316" stroke-width="1.5">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                </div>
                <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px">Klik untuk pilih berkas CSV</div>
                <div style="font-size:12px;color:var(--text3)">Format: .csv • Maksimal 10 MB</div>
                <input type="file" id="file-import" accept=".csv" style="display:none" onchange="handleFileSelect(this)">
            </div>
            <div style="margin-top:12px;font-size:12px;color:var(--text3);text-align:center">
                <a href="#" onclick="downloadTemplate();return false" style="color:var(--ora3);display:inline-flex;align-items:center;gap:4px">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 17 12 23 18 17"/>
                        <polyline points="6 7 12 1 18 7"/>
                        <line x1="12" y1="23" x2="12" y2="1"/>
                    </svg>
                    Unduh templat CSV
                </a>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-import')">Tutup</button>
        </div>
    </div>
</div>

<!-- Modal: Confirm Delete -->
<div class="modal-overlay" id="modal-confirm">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div class="modal-title">Konfirmasi Penghapusan</div>
      <button class="btn-close-modal" onclick="closeModal('modal-confirm')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <div class="modal-body" style="text-align:center;padding:24px">
      <div style="margin-bottom:12px">
        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="1.5">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
      </div>
      <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:8px">Hapus data mahasiswa ini?</div>
      <div id="confirm-desc" style="font-size:13px;color:var(--text3);margin-bottom:4px"></div>
      <div style="font-size:12px;color:var(--red);margin-top:8px">Akun login mahasiswa juga akan dihapus</div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-confirm')">Batal</button>
      <button class="btn btn-danger" id="confirm-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
          <polyline points="3 6 5 6 21 6"/>
          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
        </svg>
        Hapus
      </button>
    </div>
  </div>
</div>

<script>
// ════════════════════════════════════════════════
// GLOBAL VARIABLES
// ════════════════════════════════════════════════
let currentPage = 1;
let currentStatus = 'all';
let searchTimeout = null;
let currentMahasiswaId = null;
let kelasList = [];

// ════════════════════════════════════════════════
// INITIALIZATION
// ════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    loadKelasOptions();
    loadData();
});

// ════════════════════════════════════════════════
// LOAD STATISTICS
// ════════════════════════════════════════════════
function loadStats() {
    fetch('api/mahasiswa.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_stats'
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            const stats = response.data;
            document.getElementById('stat-total').textContent = stats.total || 0;
            document.getElementById('stat-aktif').textContent = stats.aktif || 0;
            document.getElementById('stat-cuti').textContent = stats.cuti || 0;
            document.getElementById('stat-lulus').textContent = stats.lulus || 0;
            document.getElementById('stat-dropout').textContent = stats.dropout || 0;
        }
    });
}

// ════════════════════════════════════════════════
// LOAD KELAS OPTIONS
// ════════════════════════════════════════════════
function loadKelasOptions() {
    fetch('api/mahasiswa.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_kelas'
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            kelasList = response.data;
            let options = '';
            kelasList.forEach(k => {
                options += `<option value="${k.id}">${k.nama_kelas} - ${k.jurusan}</option>`;
            });
            document.getElementById('filter-kelas').innerHTML = '<option value="">Semua Kelas</option>' + options;
            document.getElementById('mhs-kelas').innerHTML = '<option value="">Pilih Kelas</option>' + options;
        }
    });
}

// ════════════════════════════════════════════════
// LOAD DATA
// ════════════════════════════════════════════════
function loadData(page = 1) {
    currentPage = page;
    const search = document.getElementById('search-input').value;
    const kelas_id = document.getElementById('filter-kelas').value;
    
    let body = `action=list&page=${page}&limit=10`;
    if (search) body += `&search=${encodeURIComponent(search)}`;
    if (currentStatus !== 'all') body += `&status=${currentStatus}`;
    if (kelas_id) body += `&kelas_id=${kelas_id}`;
    
    fetch('api/mahasiswa.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            renderTable(response.data);
            renderPagination(response);
        }
    });
}

// ════════════════════════════════════════════════
// RENDER TABLE
// ════════════════════════════════════════════════
function renderTable(data) {
    const tbody = document.getElementById('table-body');
    
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px">Tidak terdapat data</td>';
        return;
    }
    
    let html = '';
    data.forEach((mhs, index) => {
        const no = (currentPage - 1) * 10 + index + 1;
        const statusClass = {
            'aktif': 'pill-green',
            'cuti': 'pill-blue',
            'lulus': 'pill-purple',
            'dropout': 'pill-red'
        }[mhs.status] || 'pill-gray';
        
        const kehadiranColor = mhs.persentase_kehadiran >= 80 ? 'var(--green)' : 
                               mhs.persentase_kehadiran >= 60 ? 'var(--ora3)' : 'var(--red)';
        
        html += `<tr>
            <td>${no}</td>
            <td><code>${mhs.nim || '-'}</code></td>
            <td style="font-weight:700">${mhs.nama || '-'}</td>
            <td>${mhs.nama_kelas || '-'}</td>
            <td>${mhs.jurusan || '-'}</td>
            <td><span class="pill ${statusClass}">${mhs.status || '-'}</span></td>
            <td><span style="font-weight:800;color:${kehadiranColor}">${mhs.persentase_kehadiran || 0}%</span></td>
            <td>
                <div style="display:flex;gap:5px">
                    <button class="btn btn-info btn-xs" onclick="viewDetail(${mhs.id})">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 2px;">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M22 12c-2.667 4.667-6 7-10 7s-7.333-2.333-10-7c2.667-4.667 6-7 10-7s7.333 2.333 10 7z"/>
                        </svg>
                        Detail
                    </button>
                    <button class="btn btn-secondary btn-xs" onclick="editMahasiswa(${mhs.id})">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 2px;">
                            <path d="M17 3l4 4L7 21H3v-4L17 3z"/>
                        </svg>
                        Edit
                    </button>
                    <button class="btn btn-danger btn-xs" onclick="deleteMahasiswa(${mhs.id}, '${mhs.nama.replace(/'/g, "\\'")}', '${mhs.nim}')">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 2px;">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                        Hapus
                    </button>
                </div>
            </td>
        </tr>`;
    });
    
    tbody.innerHTML = html;
}

// ════════════════════════════════════════════════
// RENDER PAGINATION
// ════════════════════════════════════════════════
function renderPagination(response) {
    const start = (response.page - 1) * response.limit + 1;
    const end = Math.min(response.page * response.limit, response.total);
    
    document.getElementById('page-info').textContent = 
        `Menampilkan ${start}-${end} dari ${response.total} data`;
    
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
// FILTER FUNCTIONS
// ════════════════════════════════════════════════
function filterByStatus(status) {
    currentStatus = status;
    
    // Update active tab
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.status === status) {
            btn.classList.add('active');
        }
    });
    
    loadData(1);
}

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadData(1), 500);
}

// ════════════════════════════════════════════════
// VALIDASI TANGGAL (support D/M/YYYY atau DD/MM/YYYY)
// ════════════════════════════════════════════════
function isValidDateDMY(dateStr) {
    if (!dateStr) return false;
    
    // Bersihkan spasi
    dateStr = dateStr.trim();
    
    // Regex untuk format D/M/YYYY atau DD/MM/YYYY
    const regex = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/;
    const match = dateStr.match(regex);
    
    if (!match) return false;
    
    const day = parseInt(match[1], 10);
    const month = parseInt(match[2], 10);
    const year = parseInt(match[3], 10);
    
    if (year < 1900 || year > 2100) return false;
    if (month < 1 || month > 12) return false;
    
    const daysInMonth = new Date(year, month, 0).getDate();
    if (day < 1 || day > daysInMonth) return false;
    
    return true;
}

// ════════════════════════════════════════════════
// CRUD FUNCTIONS
// ════════════════════════════════════════════════
function saveMahasiswa(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const id = formData.get('id');
    
    // Validasi format tanggal DD/MM/YYYY (support tanpa leading zero)
    const tgl = formData.get('tanggal_lahir');
    if (tgl && !isValidDateDMY(tgl)) {
        toast('Format tanggal harus DD/MM/YYYY (contoh: 15/05/2003 atau 3/5/2003)', 'error');
        return;
    }
    
    formData.append('action', id ? 'update' : 'create');
    
    fetch('api/mahasiswa.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            toast(response.msg, 'success');
            closeModal('modal-mhs-form');
            loadData(currentPage);
            loadStats();
            form.reset();
            document.getElementById('mhs-id').value = '';
        } else {
            toast(response.msg, 'error');
        }
    });
}

function editMahasiswa(id) {
    fetch('api/mahasiswa.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=get&id=${id}`
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            const mhs = response.data;
            document.getElementById('modal-mhs-form-title').textContent = 'Edit Mahasiswa';
            document.getElementById('mhs-id').value = mhs.id;
            document.getElementById('mhs-nim').value = mhs.nim;
            document.getElementById('mhs-nama').value = mhs.nama;
            // Gunakan tanggal yang sudah diformat DD/MM/YYYY dari server
            document.getElementById('mhs-tgl').value = mhs.tanggal_lahir_formatted || '';
            document.getElementById('mhs-jk').value = mhs.jenis_kelamin;
            document.getElementById('mhs-kelas').value = mhs.kelas_id;
            document.getElementById('mhs-status').value = mhs.status;
            document.getElementById('mhs-email').value = mhs.email || '';
            
            openModal('modal-mhs-form', 'Edit Mahasiswa');
        }
    });
}

// ════════════════════════════════════════════════
// DELETE MAHASISWA (dengan modal konfirmasi)
// ════════════════════════════════════════════════
let deleteQueue = { id: null, nama: '', nim: '' };

function deleteMahasiswa(id, nama, nim = '') {
    deleteQueue = { 
        id: id, 
        nama: nama, 
        nim: nim || '' 
    };
    
    const nimText = deleteQueue.nim ? ` (${deleteQueue.nim})` : '';
    document.getElementById('confirm-desc').innerHTML = `
        <strong>${deleteQueue.nama}${nimText}</strong><br>
        <span style="font-size:11px">Semua data absensi dan izin mahasiswa ini akan ikut terhapus.</span>
    `;
    
    const confirmBtn = document.getElementById('confirm-btn');
    confirmBtn.onclick = executeDelete;
    
    openModal('modal-confirm');
}

function executeDelete() {
    if (!deleteQueue.id) {
        closeModal('modal-confirm');
        return;
    }
    
    const id = deleteQueue.id;
    
    const confirmBtn = document.getElementById('confirm-btn');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '⏳ Menghapus...';
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    
    fetch('api/mahasiswa.php', {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const contentType = response.headers.get('content-type');
        
        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status} ${response.statusText}`);
        }
        
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Server response (not JSON):', text.substring(0, 500));
            throw new Error('Server returned invalid response. Check console for details.');
        }
        
        return response.json();
    })
    .then(response => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
        closeModal('modal-confirm');
        
        if (response.success) {
            toast(response.msg || 'Mahasiswa berhasil dihapus', 'success');
            
            const rows = document.querySelectorAll('#table-body tr').length;
            if (rows === 1 && currentPage > 1) {
                currentPage--;
            }
            
            loadData(currentPage);
            loadStats();
        } else {
            toast(response.msg || 'Gagal menghapus mahasiswa', 'error');
        }
        
        deleteQueue = { id: null, nama: '', nim: '' };
    })
    .catch(error => {
        console.error('Delete error:', error);
        
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
        closeModal('modal-confirm');
        
        toast('Terjadi kesalahan: ' + error.message, 'error');
        deleteQueue = { id: null, nama: '', nim: '' };
    });
}

// ════════════════════════════════════════════════
// VIEW DETAIL
// ════════════════════════════════════════════════
function viewDetail(id) {
    currentMahasiswaId = id;
    
    fetch('api/mahasiswa.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=get&id=${id}`
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            const mhs = response.data;
            const initial = mhs.nama ? mhs.nama.split(' ').map(w => w[0]).join('').substring(0,2).toUpperCase() : '?';
            const kehadiranColor = mhs.persentase_kehadiran >= 80 ? 'var(--green)' : 
                                   mhs.persentase_kehadiran >= 60 ? 'var(--ora3)' : 'var(--red)';
            const statusClass = {
                'aktif': 'pill-green',
                'cuti': 'pill-blue',
                'lulus': 'pill-purple',
                'dropout': 'pill-red'
            }[mhs.status] || 'pill-gray';
            
            // Format tanggal ke DD/MM/YYYY untuk ditampilkan
            let tglFormatted = '-';
            if (mhs.tanggal_lahir) {
                const date = new Date(mhs.tanggal_lahir);
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                tglFormatted = `${day}/${month}/${year}`;
            }
            
            document.getElementById('detail-content').innerHTML = `
                <div style="display:flex;align-items:center;gap:16px;padding:12px 0 20px;border-bottom:1px solid var(--border);margin-bottom:16px">
                    <div style="width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,var(--ora1),var(--ora3));display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:#fff">${initial}</div>
                    <div style="flex:1">
                        <div style="font-size:18px;font-weight:800;color:var(--text)">${mhs.nama || '-'}</div>
                        <div style="font-size:13px;color:var(--text3)">${mhs.nim || '-'} • ${mhs.nama_kelas || '-'} • ${mhs.jurusan || '-'}</div>
                        <span class="pill ${statusClass}" style="margin-top:4px;display:inline-block">${mhs.status || '-'}</span>
                    </div>
                    <div style="text-align:right">
                        <div style="font-size:28px;font-weight:800;color:${kehadiranColor};font-family:'Space Grotesk'">${mhs.persentase_kehadiran || 0}%</div>
                        <div style="font-size:12px;color:var(--text3)">Kehadiran semester ini</div>
                    </div>
                </div>
                <div class="detail-row"><div class="detail-label">Tanggal Lahir</div><div class="detail-val">${tglFormatted}</div></div>
                <div class="detail-row"><div class="detail-label">Jenis Kelamin</div><div class="detail-val">${mhs.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'}</div></div>
                <div class="detail-row"><div class="detail-label">Alamat Email</div><div class="detail-val">${mhs.email || '-'}</div></div>
                <div class="detail-row"><div class="detail-label">Total Hadir</div><div class="detail-val" style="color:var(--green);font-weight:800">${mhs.total_hadir || 0} pertemuan</div></div>
                <div class="detail-row"><div class="detail-label">Total Tidak Hadir</div><div class="detail-val" style="color:var(--red);font-weight:800">${mhs.total_alpha || 0} pertemuan</div></div>
                <div class="detail-row"><div class="detail-label">Total Izin atau Sakit</div><div class="detail-val" style="color:var(--blue);font-weight:800">${mhs.total_izin || 0} pertemuan</div></div>
            `;
            
            document.getElementById('btn-edit-from-detail').onclick = () => {
                closeModal('modal-mhs-detail');
                editMahasiswa(id);
            };
            
            document.getElementById('btn-reset-password').onclick = () => resetPassword(id);
            
            openModal('modal-mhs-detail', 'Detail Mahasiswa');
        }
    });
}

// ════════════════════════════════════════════════
// RESET PASSWORD
// ════════════════════════════════════════════════
function resetPassword(id) {
    if (confirm('Atur ulang kata sandi menjadi NIM mahasiswa?')) {
        fetch('api/mahasiswa.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=reset_password&id=${id}`
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                toast(response.msg.replace(/<[^>]*>/g, ''), 'success', 5000);
                alert('Kata sandi baru: ' + response.new_password);
            } else {
                toast(response.msg, 'error');
            }
        });
    }
}

// ════════════════════════════════════════════════
// EXPORT DATA (POST dengan filter aktif)
// ════════════════════════════════════════════════
function exportData() {
    const searchInput = document.getElementById('search-input');
    const kelasSelect = document.getElementById('filter-kelas');
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api/mahasiswa.php';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'export_csv';
    form.appendChild(actionInput);
    
    if (currentStatus !== 'all') {
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = currentStatus;
        form.appendChild(statusInput);
    }
    
    if (searchInput && searchInput.value.trim() !== '') {
        const searchHidden = document.createElement('input');
        searchHidden.type = 'hidden';
        searchHidden.name = 'search';
        searchHidden.value = searchInput.value.trim();
        form.appendChild(searchHidden);
    }
    
    if (kelasSelect && kelasSelect.value !== '') {
        const kelasHidden = document.createElement('input');
        kelasHidden.type = 'hidden';
        kelasHidden.name = 'kelas_id';
        kelasHidden.value = kelasSelect.value;
        form.appendChild(kelasHidden);
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    toast('Ekspor data sedang diproses...', 'info');
}

// ════════════════════════════════════════════════
// TEMPLATE DOWNLOAD
// ════════════════════════════════════════════════
function downloadTemplate() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api/mahasiswa.php';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'download_template';
    form.appendChild(actionInput);
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    toast('Templat sedang diunduh...', 'info');
}

// ════════════════════════════════════════════════
// IMPORT HANDLER
// ════════════════════════════════════════════════
function handleFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    
    const ext = file.name.split('.').pop().toLowerCase();
    if (ext !== 'csv') {
        toast('Hanya berkas CSV yang didukung. Gunakan templat yang disediakan.', 'error');
        input.value = '';
        return;
    }
    
    if (file.size > 10 * 1024 * 1024) {
        toast('Ukuran berkas terlalu besar. Maksimal 10 MB.', 'error');
        input.value = '';
        return;
    }
    
    if (confirm(`Import berkas: ${file.name}\n\nPastikan format sesuai dengan templat.\n\nLanjutkan import?`)) {
        importCSV(file);
    } else {
        input.value = '';
    }
}

function importCSV(file) {
    const loadingToast = showLoadingToast('Sedang memproses import...');
    
    const formData = new FormData();
    formData.append('action', 'import_csv');
    formData.append('file', file);
    
    fetch('api/mahasiswa.php', {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const contentType = response.headers.get('content-type');
        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status}`);
        }
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Server response:', text.substring(0, 500));
            throw new Error('Server mengembalikan respons tidak valid');
        }
        return response.json();
    })
    .then(response => {
        closeLoadingToast(loadingToast);
        
        if (response.success) {
            let message = response.msg;
            
            if (response.failed_rows && response.failed_rows.length > 0) {
                let detailMsg = '\n\nGagal import:\n';
                response.failed_rows.slice(0, 10).forEach(failed => {
                    detailMsg += `Baris ${failed.row} (${failed.nim || '-'}): ${failed.errors.join(', ')}\n`;
                });
                if (response.failed_rows.length > 10) {
                    detailMsg += `\n...dan ${response.failed_rows.length - 10} baris lainnya.`;
                }
                message += detailMsg;
            }
            
            toast(message, response.success_count > 0 ? 'success' : 'warning', 8000);
            
            if (response.success_count > 0) {
                loadData(1);
                loadStats();
            }
        } else {
            let errorMsg = response.msg || 'Gagal import data';
            
            if (response.failed_rows && response.failed_rows.length > 0) {
                errorMsg += '\n\nDetail kesalahan:\n';
                response.failed_rows.slice(0, 5).forEach(failed => {
                    errorMsg += `Baris ${failed.row}: ${failed.errors.join(', ')}\n`;
                });
                if (response.failed_rows.length > 5) {
                    errorMsg += `\n...dan ${response.failed_rows.length - 5} kesalahan lainnya.`;
                }
            }
            
            toast(errorMsg, 'error', 10000);
        }
        
        document.getElementById('file-import').value = '';
    })
    .catch(error => {
        console.error('Import error:', error);
        closeLoadingToast(loadingToast);
        toast('Terjadi kesalahan: ' + error.message, 'error');
        document.getElementById('file-import').value = '';
    });
}

// ════════════════════════════════════════════════
// LOADING TOAST UTILITY
// ════════════════════════════════════════════════
function showLoadingToast(message) {
    const toast = document.createElement('div');
    toast.id = 'loading-toast';
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        background: #3b82f6;
        color: white;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 10px;
    `;
    toast.innerHTML = `<span class="spinner"></span> ${message}`;
    document.body.appendChild(toast);
    
    if (!document.querySelector('#spinner-style')) {
        const spinnerStyle = document.createElement('style');
        spinnerStyle.id = 'spinner-style';
        spinnerStyle.textContent = `
            .spinner {
                width: 16px;
                height: 16px;
                border: 2px solid rgba(255,255,255,0.3);
                border-top-color: white;
                border-radius: 50%;
                animation: spin 0.8s linear infinite;
            }
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(spinnerStyle);
    }
    
    return toast;
}

function closeLoadingToast(toastElement) {
    if (toastElement && toastElement.remove) {
        toastElement.remove();
    } else {
        const toast = document.getElementById('loading-toast');
        if (toast) toast.remove();
    }
}

// ════════════════════════════════════════════════
// UTILITY FUNCTIONS
// ════════════════════════════════════════════════
function openModal(modalId, title = null) {
    if (modalId === 'modal-mhs-form') {
        if (!title || title.includes('Tambah')) {
            document.getElementById('modal-mhs-form-title').textContent = 'Tambah Mahasiswa';
            document.getElementById('form-mahasiswa').reset();
            document.getElementById('mhs-id').value = '';
        }
    }
    document.getElementById(modalId).classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function toast(message, type = 'info', duration = 3000) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 12px 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#3b82f6'};
        color: white;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        white-space: pre-line;
        max-width: 500px;
        animation: slideIn 0.3s ease;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// Tambahkan CSS animations dan style
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    .pill-purple { background: #a855f7; color: white; }
    .pill-gray { background: #9ca3af; color: white; }
    .detail-row {
        display: flex;
        padding: 10px 0;
        border-bottom: 1px solid var(--border);
    }
    .detail-label {
        width: 140px;
        font-weight: 600;
        color: var(--text2);
    }
    .detail-val {
        flex: 1;
        color: var(--text);
    }
`;
document.head.appendChild(style);
</script>

<?php
require_once 'footer.php';
?>