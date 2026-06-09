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

/* Button styling */
.btn-xs {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* Pill colors */
.pill-ora { background: #f97316; color: white; }
.pill-blue { background: #3b82f6; color: white; }
.pill-green { background: #10b981; color: white; }
.pill-gray { background: #6b7280; color: white; }
.pill-red { background: #ef4444; color: white; }

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
     MANAJEMEN TAHUN AKADEMIK - Dynamic
═══════════════════════════════════════════════ -->
<div id="app-ta">
    <div class="page-header">
        <div>
     
            <div class="page-subtitle">Kelola tahun akademik dan semester aktif</div>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="openTambahModal()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Tambah
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="tbl-wrap">
        <table class="tabel">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Tahun</th>
                    <th>Semester</th>
                    <th>Status</th>
                    <th>Kelas</th>
                    <th>Jadwal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <tr><td colspan="7" style="text-align:center;padding:40px">Memuat data...</td</tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ═══════════════════════════════════════════════
     MODALS
═══════════════════════════════════════════════ -->

<!-- Modal: Tahun Akademik Form -->
<div class="modal-overlay" id="modal-ta-form">
    <div class="modal modal-lg">
        <div class="modal-header">
            <div class="modal-title" id="modal-ta-form-title">Tambah Tahun Akademik</div>
            <button class="btn-close-modal" onclick="closeModal('modal-ta-form')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form id="form-ta" onsubmit="saveTA(event)">
                <input type="hidden" name="id" id="ta-id">
                <div class="form-row">
                    <div class="form-group">
                        <label>Tahun <span style="color:var(--red)">*</span></label>
                        <input type="text" name="tahun" id="ta-tahun" placeholder="Contoh: 2025/2026" required maxlength="9">
                        <small style="color:var(--text3)">Format: YYYY/YYYY</small>
                    </div>
                    <div class="form-group">
                        <label>Semester <span style="color:var(--red)">*</span></label>
                        <select name="semester" id="ta-semester" required>
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="ta-status">
                        <option value="nonaktif">Nonaktif</option>
                        <option value="aktif">Aktif</option>
                    </select>
                    <small style="color:var(--text3)">Hanya satu tahun akademik yang dapat aktif. Mengaktifkan ini akan menonaktifkan yang lain.</small>
                </div>
                
                <!-- Pilihan Kelas yang Berpartisipasi -->
                <div class="form-group" style="margin-top:16px">
                    <label>Pilih Kelas yang Berpartisipasi</label>
                    <div style="border:1px solid var(--border);border-radius:var(--r-sm);padding:12px;max-height:250px;overflow-y:auto;background:var(--surface2)">
                        <div style="margin-bottom:8px">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                                <input type="checkbox" id="select-all-kelas" onchange="toggleSelectAll(this)"> 
                                <span style="font-weight:600">Pilih Semua Kelas</span>
                            </label>
                        </div>
                        <div id="kelas-checkbox-list" style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px">
                            <span style="color:var(--text3)">Memuat data kelas...</span>
                        </div>
                    </div>
                    <small style="color:var(--text3)">Kelas yang dipilih akan dikaitkan dengan tahun akademik ini.</small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-ta-form')">Batal</button>
            <button class="btn btn-primary" onclick="document.getElementById('form-ta').requestSubmit()">
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
            <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:8px">Hapus tahun akademik ini?</div>
            <div id="confirm-desc" style="font-size:13px;color:var(--text3);margin-bottom:4px"></div>
            <div style="font-size:12px;color:var(--red);margin-top:8px">Data kelas dan jadwal terkait akan terpengaruh</div>
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

<!-- Modal: Confirm Set Aktif -->
<div class="modal-overlay" id="modal-confirm-aktif">
    <div class="modal modal-sm">
        <div class="modal-header">
            <div class="modal-title">Konfirmasi Aktivasi</div>
            <button class="btn-close-modal" onclick="closeModal('modal-confirm-aktif')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body" style="text-align:center;padding:24px">
            <div style="margin-bottom:12px">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#F97316" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 8v4M12 16h.01"/>
                </svg>
            </div>
            <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:8px">Aktifkan tahun akademik ini?</div>
            <div id="confirm-aktif-desc" style="font-size:13px;color:var(--text3);margin-bottom:4px"></div>
            <div style="font-size:12px;color:var(--ora3);margin-top:8px">Tahun akademik yang sedang aktif akan dinonaktifkan</div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-confirm-aktif')">Batal</button>
            <button class="btn btn-success" id="confirm-aktif-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Aktifkan
            </button>
        </div>
    </div>
</div>

<script>
// ════════════════════════════════════════════════
// GLOBAL VARIABLES
// ════════════════════════════════════════════════
let deleteQueue = { id: null, info: '' };
let aktifQueue = { id: null, info: '' };
let allKelas = [];

// ════════════════════════════════════════════════
// FUNGSI RESET FORM
// ════════════════════════════════════════════════
function resetFormTA() {
    document.getElementById('form-ta').reset();
    document.getElementById('ta-id').value = '';
    
    if (allKelas.length > 0) {
        renderKelasCheckboxes([]);
    } else {
        loadAllKelas().then(() => renderKelasCheckboxes([]));
    }
    
    const selectAll = document.getElementById('select-all-kelas');
    if (selectAll) {
        selectAll.checked = false;
    }
}

// ════════════════════════════════════════════════
// FUNGSI BUKA MODAL TAMBAH (dengan reset form)
// ════════════════════════════════════════════════
function openTambahModal() {
    resetFormTA();
    document.getElementById('modal-ta-form-title').textContent = 'Tambah Tahun Akademik';
    openModal('modal-ta-form');
}

// ════════════════════════════════════════════════
// INITIALIZATION
// ════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    loadData();
    loadAllKelas();
});

// ════════════════════════════════════════════════
// LOAD ALL KELAS (untuk checkbox)
// ════════════════════════════════════════════════
function loadAllKelas() {
    return fetch('api/tahunakademik.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_all_kelas'
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            allKelas = response.data;
        }
        return allKelas;
    });
}

function renderKelasCheckboxes(selectedIds = []) {
    const container = document.getElementById('kelas-checkbox-list');
    if (!container) return;
    
    if (allKelas.length === 0) {
        container.innerHTML = '<span style="color:var(--text3)">Belum terdapat data kelas. Silakan tambah kelas terlebih dahulu.</span>';
        return;
    }
    
    let html = '';
    allKelas.forEach(k => {
        const checked = selectedIds.includes(k.id) ? 'checked' : '';
        html += `<label style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:4px">
            <input type="checkbox" name="kelas_ids[]" value="${k.id}" ${checked} class="kelas-checkbox">
            <span>${escapeHtml(k.nama_kelas)} - ${escapeHtml(k.jurusan)} (${k.angkatan})</span>
        </label>`;
    });
    
    container.innerHTML = html;
}

function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.kelas-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

// ════════════════════════════════════════════════
// LOAD DATA
// ════════════════════════════════════════════════
function loadData() {
    document.getElementById('table-body').innerHTML = '</table><td colspan="7" style="text-align:center;padding:40px">Memuat data...</td>\n</tr>';
    
    fetch('api/tahunakademik.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=list'
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            renderTable(response.data);
        } else {
            document.getElementById('table-body').innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px">Gagal memuat数据</td>\n</tr>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('table-body').innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px">Terjadi kesalahan</td>\n</tr>';
    });
}

// ════════════════════════════════════════════════
// RENDER TABLE
// ════════════════════════════════════════════════
function renderTable(data) {
    const tbody = document.getElementById('table-body');
    
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px">Tidak terdapat数据</td>\n</tr>';
        return;
    }
    
    let html = '';
    data.forEach((ta, index) => {
        const semesterClass = ta.semester === 'Genap' ? 'pill-blue' : 'pill-ora';
        const statusClass = ta.status === 'aktif' ? 'pill-green' : 'pill-gray';
        const statusText = ta.status === 'aktif' ? 'Aktif' : 'Nonaktif';
        
        html += `<tr>
            <td>${index + 1}</td>
            <td style="font-weight:800">${escapeHtml(ta.tahun) || '-'}</td>
            <td><span class="pill ${semesterClass}">${escapeHtml(ta.semester) || '-'}</span></td>
            <td><span class="pill ${statusClass}">${statusText}</span></td>
            <td><span style="font-weight:600">${ta.total_kelas || 0} kelas</span></td>
            <td><span style="font-weight:600;color:var(--ora3)">${ta.total_jadwal || 0} jadwal</span></td>
            <td>
                <div style="display:flex;gap:5px">`;
        
        if (ta.status !== 'aktif') {
            html += `<button class="btn btn-success btn-xs" onclick="setAktif(${ta.id}, '${escapeHtml(ta.tahun)} ${escapeHtml(ta.semester)}')">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 2px;">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Aktifkan
                    </button>`;
        }
        
        html += `<button class="btn btn-secondary btn-xs" onclick="editTA(${ta.id})">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 2px;">
                        <path d="M17 3l4 4L7 21H3v-4L17 3z"/>
                    </svg>
                    Edit
                </button>`;
        
        // Hanya bisa hapus jika tidak ada kelas dan jadwal
        if (ta.total_kelas == 0 && ta.total_jadwal == 0) {
            html += `<button class="btn btn-danger btn-xs" onclick="deleteTA(${ta.id}, '${escapeHtml(ta.tahun)} ${escapeHtml(ta.semester)}')">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 2px;">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                        Hapus
                    </button>`;
        }
        
        html += `</div>
            </td>
        </tr>`;
    });
    
    tbody.innerHTML = html;
}

// ════════════════════════════════════════════════
// CRUD FUNCTIONS
// ════════════════════════════════════════════════
function saveTA(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const id = formData.get('id');
    
    // Validasi format tahun
    const tahun = formData.get('tahun');
    if (!/^\d{4}\/\d{4}$/.test(tahun)) {
        toast('Format tahun harus YYYY/YYYY (contoh: 2025/2026)', 'error');
        return;
    }
    
    formData.append('action', id ? 'update' : 'create');
    
    fetch('api/tahunakademik.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            toast(response.msg, 'success');
            closeModal('modal-ta-form');
            loadData();
        } else {
            toast(response.msg, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toast('Terjadi kesalahan', 'error');
    });
}

function editTA(id) {
    document.getElementById('modal-ta-form-title').textContent = 'Edit Tahun Akademik';
    document.getElementById('kelas-checkbox-list').innerHTML = '<span style="color:var(--text3)">Memuat data...</span>';
    
    fetch('api/tahunakademik.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=get&id=${id}`
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            const ta = response.data;
            
            document.getElementById('ta-id').value = ta.id;
            document.getElementById('ta-tahun').value = ta.tahun;
            document.getElementById('ta-semester').value = ta.semester;
            document.getElementById('ta-status').value = ta.status;
            
            // Parse kelas_ids
            let selectedIds = [];
            if (ta.kelas_ids) {
                selectedIds = ta.kelas_ids.split(',').map(id => parseInt(id.trim()));
            }
            
            // Render checkbox dengan selected IDs
            renderKelasCheckboxes(selectedIds);
            
            const selectAll = document.getElementById('select-all-kelas');
            if (selectAll) {
                selectAll.checked = false;
            }
            
            openModal('modal-ta-form');
        } else {
            toast('Gagal memuat data', 'error');
            closeModal('modal-ta-form');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toast('Terjadi kesalahan', 'error');
        closeModal('modal-ta-form');
    });
}

// ════════════════════════════════════════════════
// SET AKTIF
// ════════════════════════════════════════════════
function setAktif(id, info) {
    aktifQueue = { id: id, info: info };
    
    document.getElementById('confirm-aktif-desc').innerHTML = `
        <strong>${escapeHtml(aktifQueue.info)}</strong><br>
        <span style="font-size:11px">ID: ${aktifQueue.id}</span>
    `;
    
    document.getElementById('confirm-aktif-btn').onclick = executeSetAktif;
    openModal('modal-confirm-aktif');
}

function executeSetAktif() {
    if (!aktifQueue.id) {
        closeModal('modal-confirm-aktif');
        return;
    }
    
    const id = aktifQueue.id;
    const confirmBtn = document.getElementById('confirm-aktif-btn');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<span class="spinner"></span> Mengaktifkan...';
    
    fetch('api/tahunakademik.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=set_aktif&id=${id}`
    })
    .then(res => res.json())
    .then(response => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
        closeModal('modal-confirm-aktif');
        
        toast(response.msg, response.success ? 'success' : 'error');
        if (response.success) loadData();
        
        aktifQueue = { id: null, info: '' };
    })
    .catch(error => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
        closeModal('modal-confirm-aktif');
        toast('Terjadi kesalahan', 'error');
        aktifQueue = { id: null, info: '' };
    });
}

// ════════════════════════════════════════════════
// DELETE
// ════════════════════════════════════════════════
function deleteTA(id, info) {
    deleteQueue = { id: id, info: info };
    
    document.getElementById('confirm-desc').innerHTML = `<strong>${escapeHtml(deleteQueue.info)}</strong>`;
    document.getElementById('confirm-btn').onclick = executeDelete;
    openModal('modal-confirm');
}

function executeDelete() {
    if (!deleteQueue.id) {
        closeModal('modal-confirm');
        return;
    }
    
    const confirmBtn = document.getElementById('confirm-btn');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<span class="spinner"></span> Menghapus...';
    
    fetch('api/tahunakademik.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=delete&id=${deleteQueue.id}`
    })
    .then(res => res.json())
    .then(response => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
        closeModal('modal-confirm');
        
        toast(response.msg, response.success ? 'success' : 'error');
        if (response.success) loadData();
        
        deleteQueue = { id: null, info: '' };
    })
    .catch(error => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
        closeModal('modal-confirm');
        toast('Terjadi kesalahan', 'error');
        deleteQueue = { id: null, info: '' };
    });
}

// ════════════════════════════════════════════════
// UTILITY FUNCTIONS
// ════════════════════════════════════════════════
function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    if (modalId === 'modal-ta-form') {
        resetFormTA();
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function toast(message, type = 'info', duration = 3000) {
    const toastEl = document.createElement('div');
    toastEl.style.cssText = `
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
        white-space: pre-line;
        max-width: 500px;
    `;
    toastEl.textContent = message;
    document.body.appendChild(toastEl);
    
    setTimeout(() => {
        toastEl.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toastEl.remove(), 300);
    }, duration);
}

// Spinner style
const spinnerStyle = document.createElement('style');
spinnerStyle.textContent = `
    .spinner {
        display: inline-block;
        width: 12px;
        height: 12px;
        border: 2px solid rgba(255,255,255,0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
        margin-right: 4px;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(spinnerStyle);

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal(this.id);
        }
    });
});

// Close modal on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(modal => {
            closeModal(modal.id);
        });
    }
});
</script>

<?php
require_once 'footer.php';
?>