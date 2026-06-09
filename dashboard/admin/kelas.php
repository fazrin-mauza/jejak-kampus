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

/* Search box styling */
.search-box input {
    border-radius: 40px;
    padding-left: 36px;
}
.search-icon {
    left: 12px;
}

/* Button styling */
.btn-xs {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* Modal animation */
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}
</style>

<!-- ═══════════════════════════════════════════════
     MANAJEMEN KELAS - Dynamic dengan AJAX
═══════════════════════════════════════════════ -->
<div id="app-kelas">
    <div class="page-header">
        <div>

            <div class="page-subtitle">Pengaturan kelas, program studi, dan angkatan</div>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="openModal('modal-kelas-form','Tambah Kelas')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Tambah Kelas
            </button>
        </div>
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
            <input type="text" id="search-input" placeholder="Cari nama kelas atau program studi..." onkeyup="debounceSearch()">
        </div>
        <select id="filter-jurusan" style="padding:8px 12px;border:1.5px solid var(--border-s);border-radius:var(--r-sm);font-family:inherit;font-size:12.5px" onchange="loadData()">
            <option value="">Semua Program Studi</option>
        </select>
        <select id="filter-angkatan" style="padding:8px 12px;border:1.5px solid var(--border-s);border-radius:var(--r-sm);font-family:inherit;font-size:12.5px" onchange="loadData()">
            <option value="">Semua Angkatan</option>
        </select>
    </div>

    <!-- Table -->
    <div class="tbl-wrap">
        <table class="tabel">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nama Kelas</th>
                    <th>Program Studi</th>
                    <th>Angkatan</th>
                    <th>Jumlah Mahasiswa</th>
                    <th>Tahun Akademik</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <tr><td colspan="7" style="text-align:center;padding:40px">Memuat data...</td</tr>
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
     MODALS - Kelas
═══════════════════════════════════════════════ -->

<!-- Modal: Kelas Form -->
<div class="modal-overlay" id="modal-kelas-form">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-kelas-form-title">Tambah Kelas</div>
            <button class="btn-close-modal" onclick="closeModal('modal-kelas-form')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form id="form-kelas" onsubmit="saveKelas(event)">
                <input type="hidden" name="id" id="kelas-id">
                <div class="form-group">
                    <label>Nama Kelas <span style="color:var(--red)">*</span></label>
                    <input type="text" name="nama_kelas" id="kelas-nama" placeholder="Contoh: IF-3A" required>
                </div>
                <div class="form-group">
                    <label>Program Studi <span style="color:var(--red)">*</span></label>
                    <input type="text" name="jurusan" id="kelas-jurusan" placeholder="Contoh: Teknik Informatika" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Angkatan <span style="color:var(--red)">*</span></label>
                        <input type="number" name="angkatan" id="kelas-angkatan" placeholder="2022" min="2000" max="2099" required>
                    </div>
                    <div class="form-group">
                        <label>Tahun Akademik <span style="color:var(--red)">*</span></label>
                        <select name="tahun_akademik_id" id="kelas-ta" required>
                            <option value="">Pilih Tahun Akademik</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-kelas-form')">Batal</button>
            <button class="btn btn-primary" onclick="document.getElementById('form-kelas').requestSubmit()">
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

<!-- Modal: Kelas Mahasiswa (Anggota) -->
<div class="modal-overlay" id="modal-kelas-mhs">
    <div class="modal modal-lg">
        <div class="modal-header">
            <div class="modal-title" id="modal-kelas-mhs-title">Daftar Mahasiswa</div>
            <button class="btn-close-modal" onclick="closeModal('modal-kelas-mhs')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="tbl-wrap">
                <table class="tabel">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>NIM</th>
                            <th>Nama Lengkap</th>
                            <th>Status</th>
                            <th>Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody id="anggota-body">
                        <tr><td colspan="5" style="text-align:center;padding:20px">Memuat data...</td</tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-kelas-mhs')">Tutup</button>
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
            <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:8px">Hapus kelas ini?</div>
            <div id="confirm-desc" style="font-size:13px;color:var(--text3);margin-bottom:4px"></div>
            <div style="font-size:12px;color:var(--red);margin-top:8px">Semua mahasiswa di kelas ini akan kehilangan referensi kelas</div>
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
let searchTimeout = null;
let deleteQueue = { id: null, nama: '' };
let tahunAkademikList = [];

// ════════════════════════════════════════════════
// INITIALIZATION
// ════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    loadTahunAkademikOptions();
    loadJurusanOptions();
    loadAngkatanOptions();
    loadData();
});

// ════════════════════════════════════════════════
// LOAD TAHUN AKADEMIK OPTIONS
// ════════════════════════════════════════════════
function loadTahunAkademikOptions() {
    fetch('api/kelas.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_tahun_akademik'
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            tahunAkademikList = response.data;
            let options = '';
            tahunAkademikList.forEach(ta => {
                const selected = ta.status === 'aktif' ? 'selected' : '';
                options += `<option value="${ta.id}" ${selected}>${ta.tahun} ${ta.semester} ${ta.status === 'aktif' ? '(Aktif)' : ''}</option>`;
            });
            document.getElementById('kelas-ta').innerHTML = '<option value="">Pilih Tahun Akademik</option>' + options;
        }
    });
}

// ════════════════════════════════════════════════
// LOAD JURUSAN OPTIONS (untuk filter)
// ════════════════════════════════════════════════
function loadJurusanOptions() {
    fetch('api/kelas.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_jurusan_list'
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            let options = '<option value="">Semua Program Studi</option>';
            response.data.forEach(jurusan => {
                options += `<option value="${jurusan}">${jurusan}</option>`;
            });
            document.getElementById('filter-jurusan').innerHTML = options;
        }
    });
}

// ════════════════════════════════════════════════
// LOAD ANGKATAN OPTIONS (untuk filter)
// ════════════════════════════════════════════════
function loadAngkatanOptions() {
    fetch('api/kelas.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_angkatan_list'
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            let options = '<option value="">Semua Angkatan</option>';
            response.data.forEach(angkatan => {
                options += `<option value="${angkatan}">${angkatan}</option>`;
            });
            document.getElementById('filter-angkatan').innerHTML = options;
        }
    });
}

// ════════════════════════════════════════════════
// LOAD DATA
// ════════════════════════════════════════════════
function loadData(page = 1) {
    currentPage = page;
    const search = document.getElementById('search-input').value;
    const jurusan = document.getElementById('filter-jurusan').value;
    const angkatan = document.getElementById('filter-angkatan').value;
    
    let body = `action=list&page=${page}&limit=10`;
    if (search) body += `&search=${encodeURIComponent(search)}`;
    if (jurusan) body += `&jurusan=${encodeURIComponent(jurusan)}`;
    if (angkatan) body += `&angkatan=${angkatan}`;
    
    fetch('api/kelas.php', {
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
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px">Tidak terdapat data</td></tr>';
        return;
    }
    
    let html = '';
    data.forEach((kelas, index) => {
        const no = (currentPage - 1) * 10 + index + 1;
        const taText = kelas.tahun_akademik ? `${kelas.tahun} ${kelas.semester}` : '-';
        
        html += `<tr>
            <td>${no}</td>
            <td style="font-weight:800">${kelas.nama_kelas || '-'}</td>
            <td>${kelas.jurusan || '-'}</td>
            <td>${kelas.angkatan || '-'}</td>
            <td><span class="pill pill-blue">${kelas.jumlah_mahasiswa || 0} mhs</span></td>
            <td>${taText}</td>
            <td>
                <div style="display:flex;gap:5px">
                    <button class="btn btn-info btn-xs" onclick="viewAnggota(${kelas.id}, '${kelas.nama_kelas.replace(/'/g, "\\'")}')">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 2px;">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        Anggota
                    </button>
                    <button class="btn btn-secondary btn-xs" onclick="editKelas(${kelas.id})">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 2px;">
                            <path d="M17 3l4 4L7 21H3v-4L17 3z"/>
                        </svg>
                        Edit
                    </button>
                    <button class="btn btn-danger btn-xs" onclick="deleteKelas(${kelas.id}, '${kelas.nama_kelas.replace(/'/g, "\\'")}')">
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
// SEARCH
// ════════════════════════════════════════════════
function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadData(1), 500);
}

// ════════════════════════════════════════════════
// CRUD FUNCTIONS
// ════════════════════════════════════════════════
function saveKelas(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const id = formData.get('id');
    
    formData.append('action', id ? 'update' : 'create');
    
    fetch('api/kelas.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            toast(response.msg, 'success');
            closeModal('modal-kelas-form');
            loadData(currentPage);
            loadJurusanOptions();
            loadAngkatanOptions();
            form.reset();
            document.getElementById('kelas-id').value = '';
        } else {
            toast(response.msg, 'error');
        }
    });
}

function editKelas(id) {
    fetch('api/kelas.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=get&id=${id}`
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            const kelas = response.data;
            document.getElementById('modal-kelas-form-title').textContent = 'Edit Kelas';
            document.getElementById('kelas-id').value = kelas.id;
            document.getElementById('kelas-nama').value = kelas.nama_kelas;
            document.getElementById('kelas-jurusan').value = kelas.jurusan;
            document.getElementById('kelas-angkatan').value = kelas.angkatan;
            document.getElementById('kelas-ta').value = kelas.tahun_akademik_id;
            
            openModal('modal-kelas-form', 'Edit Kelas');
        }
    });
}

function deleteKelas(id, nama) {
    deleteQueue = { id: id, nama: nama };
    
    document.getElementById('confirm-desc').innerHTML = `
        <strong>${deleteQueue.nama}</strong><br>
        <span style="font-size:11px">ID: ${deleteQueue.id}</span>
    `;
    
    document.getElementById('confirm-btn').onclick = executeDelete;
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
    
    fetch('api/kelas.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(response => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
        closeModal('modal-confirm');
        
        toast(response.msg, response.success ? 'success' : 'error');
        
        if (response.success) {
            const rows = document.querySelectorAll('#table-body tr').length;
            if (rows === 1 && currentPage > 1) {
                currentPage--;
            }
            loadData(currentPage);
            loadJurusanOptions();
            loadAngkatanOptions();
        }
        
        deleteQueue = { id: null, nama: '' };
    })
    .catch(error => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
        closeModal('modal-confirm');
        toast('Terjadi kesalahan: ' + error.message, 'error');
        deleteQueue = { id: null, nama: '' };
    });
}

// ════════════════════════════════════════════════
// VIEW ANGGOTA KELAS
// ════════════════════════════════════════════════
function viewAnggota(id, namaKelas) {
    document.getElementById('modal-kelas-mhs-title').textContent = `Daftar Mahasiswa — ${namaKelas}`;
    document.getElementById('anggota-body').innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px">Memuat data...</td></tr>';
    
    openModal('modal-kelas-mhs');
    
    fetch('api/kelas.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=get_anggota&kelas_id=${id}`
    })
    .then(res => res.json())
    .then(response => {
        const tbody = document.getElementById('anggota-body');
        
        if (!response.success || response.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px">Tidak terdapat mahasiswa di kelas ini</td></tr>';
            return;
        }
        
        let html = '';
        response.data.forEach((mhs, index) => {
            const statusClass = {
                'aktif': 'pill-green',
                'cuti': 'pill-blue',
                'lulus': 'pill-purple',
                'dropout': 'pill-red'
            }[mhs.status] || 'pill-gray';
            
            const kehadiranColor = mhs.persentase >= 80 ? 'var(--green)' : 
                                   mhs.persentase >= 60 ? 'var(--ora3)' : 'var(--red)';
            
            html += `<tr>
                <td>${index + 1}</td>
                <td><code>${mhs.nim || '-'}</code></td>
                <td style="font-weight:600">${mhs.nama || '-'}</td>
                <td><span class="pill ${statusClass}">${mhs.status || '-'}</span></td>
                <td><span style="font-weight:800;color:${kehadiranColor}">${mhs.persentase || 0}%</span></td>
            </tr>`;
        });
        
        tbody.innerHTML = html;
    });
}

// ════════════════════════════════════════════════
// UTILITY FUNCTIONS
// ════════════════════════════════════════════════
function openModal(modalId, title = null) {
    if (modalId === 'modal-kelas-form' && title) {
        document.getElementById('modal-kelas-form-title').textContent = title;
        if (title.includes('Tambah')) {
            document.getElementById('form-kelas').reset();
            document.getElementById('kelas-id').value = '';
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
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}
</script>

<?php
require_once 'footer.php';
?>