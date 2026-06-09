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

/* Pill colors */
.pill-ora { background: #f97316; color: white; }
.pill-purple { background: #a855f7; color: white; }

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
     MANAJEMEN MATA KULIAH - Dynamic dengan AJAX
═══════════════════════════════════════════════ -->
<div id="app-matkul">
    <div class="page-header">
        <div>
          
            <div class="page-subtitle">Daftar seluruh mata kuliah yang ditawarkan</div>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="openTambahModal()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Tambah MK
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
            <input type="text" id="search-input" placeholder="Cari kode atau nama mata kuliah..." onkeyup="debounceSearch()">
        </div>
    </div>

    <!-- Table -->
    <div class="tbl-wrap">
        <table class="tabel">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kode MK</th>
                    <th>Nama Mata Kuliah</th>
                    <th>SKS</th>
                    <th>Kelas Aktif</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <tr><td colspan="6" style="text-align:center;padding:40px">Memuat data...</td</tr>
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
     MODALS - Mata Kuliah
═══════════════════════════════════════════════ -->

<!-- Modal: Matkul Form -->
<div class="modal-overlay" id="modal-matkul-form">
    <div class="modal modal-sm">
        <div class="modal-header">
            <div class="modal-title" id="modal-matkul-form-title">Tambah Mata Kuliah</div>
            <button class="btn-close-modal" onclick="closeModal('modal-matkul-form')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form id="form-matkul" onsubmit="saveMatkul(event)">
                <input type="hidden" name="id" id="matkul-id">
                <div class="form-group">
                    <label>Kode MK <span style="color:var(--red)">*</span></label>
                    <input type="text" name="kode_mk" id="matkul-kode" placeholder="Contoh: IF301" required maxlength="20">
                </div>
                <div class="form-group">
                    <label>Nama Mata Kuliah <span style="color:var(--red)">*</span></label>
                    <input type="text" name="nama_mk" id="matkul-nama" placeholder="Nama lengkap mata kuliah" required>
                </div>
                <div class="form-group">
                    <label>SKS <span style="color:var(--red)">*</span></label>
                    <input type="number" name="sks" id="matkul-sks" placeholder="2" min="1" max="6" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-matkul-form')">Batal</button>
            <button class="btn btn-primary" onclick="document.getElementById('form-matkul').requestSubmit()">
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
            <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:8px">Hapus mata kuliah ini?</div>
            <div id="confirm-desc" style="font-size:13px;color:var(--text3);margin-bottom:4px"></div>
            <div style="font-size:12px;color:var(--red);margin-top:8px">Mata kuliah yang sudah digunakan di jadwal tidak dapat dihapus</div>
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
let deleteQueue = { id: null, kode: '', nama: '' };

// ════════════════════════════════════════════════
// INITIALIZATION
// ════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    loadData();
});

// ════════════════════════════════════════════════
// FUNGSI RESET FORM
// ════════════════════════════════════════════════
function resetForm() {
    document.getElementById('matkul-id').value = '';
    document.getElementById('matkul-kode').value = '';
    document.getElementById('matkul-nama').value = '';
    document.getElementById('matkul-sks').value = '';
}

// ════════════════════════════════════════════════
// FUNGSI BUKA MODAL TAMBAH (dengan reset form)
// ════════════════════════════════════════════════
function openTambahModal() {
    resetForm();
    document.getElementById('modal-matkul-form-title').textContent = 'Tambah Mata Kuliah';
    openModal('modal-matkul-form');
}

// ════════════════════════════════════════════════
// LOAD DATA
// ════════════════════════════════════════════════
function loadData(page = 1) {
    currentPage = page;
    const search = document.getElementById('search-input').value;
    
    let body = `action=list&page=${page}&limit=10`;
    if (search) body += `&search=${encodeURIComponent(search)}`;
    
    fetch('api/matkul.php', {
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
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px">Tidak terdapat数据</td>';
        return;
    }
    
    let html = '';
    data.forEach((mk, index) => {
        const no = (currentPage - 1) * 10 + index + 1;
        
        // SKS color
        let sksClass = 'pill-blue';
        if (mk.sks == 3) sksClass = 'pill-ora';
        else if (mk.sks >= 4) sksClass = 'pill-purple';
        
        html += `<tr>
            <td>${no}</td>
            <td><code>${escapeHtml(mk.kode_mk || '-')}</code></td>
            <td style="font-weight:700">${escapeHtml(mk.nama_mk || '-')}</td>
            <td><span class="pill ${sksClass}">${mk.sks || 0} SKS</span></td>
            <td><span style="font-weight:600;color:var(--ora3)">${mk.total_kelas || 0} kelas</span></td>
            <td>
                <div style="display:flex;gap:5px">
                    <button class="btn btn-secondary btn-xs" onclick="editMatkul(${mk.id})">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 2px;">
                            <path d="M17 3l4 4L7 21H3v-4L17 3z"/>
                        </svg>
                        Edit
                    </button>
                    <button class="btn btn-danger btn-xs" onclick="deleteMatkul(${mk.id}, '${escapeHtml(mk.kode_mk)}', '${escapeHtml(mk.nama_mk).replace(/'/g, "\\'")}')">
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
function saveMatkul(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const id = formData.get('id');
    
    formData.append('action', id ? 'update' : 'create');
    
    fetch('api/matkul.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            toast(response.msg, 'success');
            closeModal('modal-matkul-form');
            resetForm(); // Reset form setelah sukses simpan
            loadData(currentPage);
        } else {
            toast(response.msg, 'error');
        }
    });
}

function editMatkul(id) {
    fetch('api/matkul.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=get&id=${id}`
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            const mk = response.data;
            document.getElementById('modal-matkul-form-title').textContent = 'Edit Mata Kuliah';
            document.getElementById('matkul-id').value = mk.id;
            document.getElementById('matkul-kode').value = mk.kode_mk;
            document.getElementById('matkul-nama').value = mk.nama_mk;
            document.getElementById('matkul-sks').value = mk.sks;
            
            openModal('modal-matkul-form');
        }
    });
}

function deleteMatkul(id, kode, nama) {
    deleteQueue = { id: id, kode: kode, nama: nama };
    
    document.getElementById('confirm-desc').innerHTML = `
        <strong>${escapeHtml(deleteQueue.kode)} - ${escapeHtml(deleteQueue.nama)}</strong><br>
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
    
    fetch('api/matkul.php', {
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
        }
        
        deleteQueue = { id: null, kode: '', nama: '' };
    })
    .catch(error => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
        closeModal('modal-confirm');
        toast('Terjadi kesalahan: ' + error.message, 'error');
        deleteQueue = { id: null, kode: '', nama: '' };
    });
}

// ════════════════════════════════════════════════
// UTILITY FUNCTIONS
// ════════════════════════════════════════════════
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;')
              .replace(/"/g, '&quot;')
              .replace(/'/g, '&#39;');
}

function openModal(modalId) {
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

// Tutup modal saat klik overlay
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal(this.id);
            if (this.id === 'modal-matkul-form') {
                resetForm();
            }
        }
    });
});

// Tutup modal saat tekan ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(modal => {
            closeModal(modal.id);
            if (modal.id === 'modal-matkul-form') {
                resetForm();
            }
        });
    }
});
</script>

<?php
require_once 'footer.php';
?>