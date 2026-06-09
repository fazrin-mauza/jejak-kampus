<?php
require_once 'header.php';
require_once 'navigasi.php';
?>

<style>
/* Perbaikan tampilan responsif */
:root {
    --responsive-break: 768px;
}

/* Perbaikan tabel untuk mobile */
@media (max-width: 1024px) {
    .tbl-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .tabel {
        min-width: 900px;
    }
    
    .filter-bar {
        flex-wrap: wrap;
    }
    
    .filter-bar label {
        margin-left: 0;
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .header-actions {
        width: 100%;
        justify-content: flex-start;
    }
    
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-bar label {
        width: 100%;
        margin-top: 8px;
    }
    
    .filter-bar select, 
    .filter-bar button {
        width: 100%;
    }
    
    .pagination {
        flex-direction: column;
        gap: 10px;
    }
    
    /* Sembunyikan kolom yang kurang penting di mobile */
    .tabel th:nth-child(5),
    .tabel td:nth-child(5),
    .tabel th:nth-child(9),
    .tabel td:nth-child(9) {
        display: none;
    }
}

@media (max-width: 480px) {
    /* Sembunyikan lebih banyak kolom di mobile kecil */
    .tabel th:nth-child(4),
    .tabel td:nth-child(4),
    .tabel th:nth-child(6),
    .tabel td:nth-child(6) {
        display: none;
    }
    
    .btn-xs {
        padding: 4px 6px;
        font-size: 10px;
    }
    
    .pill {
        font-size: 10px;
        padding: 2px 8px;
    }
}

/* Filter bar styling lebih compact */
.filter-bar {
    background: #F8FAFC;
    padding: 12px 16px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}
.filter-bar label {
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    margin-left: 8px;
}
.filter-bar label:first-child {
    margin-left: 0;
}
.filter-bar select {
    padding: 6px 10px;
    border-radius: 8px;
    border: 1px solid #E2E8F0;
    background: white;
    font-size: 12px;
    min-width: 120px;
}
.filter-bar button {
    padding: 6px 12px;
    font-size: 12px;
}

/* Tabel styling */
.tbl-wrap {
    background: #FFFFFF;
    border-radius: 16px;
    overflow-x: auto;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.tabel {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}
.tabel thead tr {
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
}
.tabel th {
    font-weight: 600;
    font-size: 12px;
    color: #1E293B;
    padding: 10px 12px;
    white-space: nowrap;
}
.tabel td {
    padding: 10px 12px;
    border-bottom: 1px solid #F1F5F9;
    font-size: 12px;
    vertical-align: middle;
}
.tabel tr:hover {
    background: #F8FAFC;
}

/* Status badges - lebih compact */
.pill {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}
.pill-pending { background: #f59e0b; color: white; }
.pill-disetujui { background: #10b981; color: white; }
.pill-ditolak { background: #ef4444; color: white; }
.pill-sakit { background: #3b82f6; color: white; }
.pill-izin { background: #8b5cf6; color: white; }

/* Button styling */
.btn-xs {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    font-size: 11px;
    border-radius: 6px;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}
.btn-info {
    background: #e0f2fe;
    color: #0284c7;
}
.btn-info:hover {
    background: #bae6fd;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin-top: 20px;
    gap: 10px;
}
.page-info {
    font-size: 12px;
    color: #64748B;
}
.page-btn {
    padding: 6px 10px;
    border: 1px solid #E2E8F0;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.2s;
}
.page-btn.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}
.page-btn:hover:not(:disabled) {
    background: #e2e8f0;
}
.page-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Modal responsif */
.modal {
    max-width: 90vw;
    width: 800px;
    max-height: 90vh;
    overflow-y: auto;
}
.modal-lg {
    max-width: 90vw;
    width: 900px;
}
.modal-sm {
    max-width: 90vw;
    width: 450px;
}

/* Detail row */
.detail-row {
    display: flex;
    flex-wrap: wrap;
    padding: 8px 0;
    border-bottom: 1px solid #E2E8F0;
}
.detail-label {
    width: 130px;
    font-weight: 600;
    color: #475569;
    font-size: 12px;
}
.detail-value {
    flex: 1;
    color: #1E293B;
    font-size: 12px;
    word-break: break-word;
}
@media (max-width: 480px) {
    .detail-label {
        width: 100%;
        margin-bottom: 4px;
    }
    .detail-value {
        width: 100%;
    }
}

/* File link */
.file-link {
    color: #3b82f6;
    text-decoration: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
}
.file-link:hover {
    text-decoration: underline;
}

/* Skeleton loader */
.skeleton-row td {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeletonPulse 1.5s ease infinite;
    height: 40px;
}
@keyframes skeletonPulse {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* Spinner */
.spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(0,0,0,0.1);
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
    vertical-align: middle;
    margin-right: 6px;
}
@keyframes spin {
    to { transform: rotate(360deg); }
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

/* Toast notification */
.toast-notif {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 10px 16px;
    background: #3b82f6;
    color: white;
    border-radius: 8px;
    font-size: 13px;
    z-index: 10000;
    animation: slideIn 0.3s ease;
}
</style>

<!-- ═══════════════════════════════════════════════
     MONITORING IZIN MAHASISWA
═══════════════════════════════════════════════ -->
<div id="app-monitoring-izin">
    <div class="page-header">
        <div>
            <div class="page-title">Monitoring Izin Mahasiswa</div>
            <div class="page-subtitle">Melacak pengajuan izin dan sakit mahasiswa beserta keputusan dosen</div>
        </div>
    </div>

    <!-- Filter Bar - Compact -->
    <div class="filter-bar">
        <label>Status:</label>
        <select id="filter-status" onchange="loadData()">
            <option value="">Semua</option>
            <option value="pending">Pending</option>
            <option value="disetujui">Disetujui</option>
            <option value="ditolak">Ditolak</option>
        </select>
        <label>Jenis:</label>
        <select id="filter-jenis" onchange="loadData()">
            <option value="">Semua</option>
            <option value="sakit">Sakit</option>
            <option value="izin">Izin</option>
        </select>
        <label>Dosen:</label>
        <select id="filter-dosen" onchange="loadData()">
            <option value="">Semua Dosen</option>
        </select>
        <button class="btn btn-primary btn-sm" onclick="loadData()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            Filter
        </button>
        <button class="btn btn-secondary btn-sm" onclick="resetFilter()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M20.49 15a9 9 0 0 1-14.85 3.36L1 14"/>
            </svg>
            Reset
        </button>
        <button class="btn btn-secondary btn-sm" onclick="refreshData()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 2v6h-6M3 12a9 9 0 0 1 15-6.7L21 8M3 22v-6h6M21 12a9 9 0 0 1-15 6.7L3 16"/>
            </svg>
            Segarkan
        </button>
    </div>

    <!-- Table Wrapper dengan overflow-x auto -->
    <div class="tbl-wrap">
        <table class="tabel">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Mahasiswa</th>
                    <th>Jenis</th>
                    <th>Keterangan</th>
                    <th>Dosen</th>
                    <th>Status</th>
                    <th>Tgl Pengajuan</th>
                    <th>Bukti</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <tr><td colspan="9" style="text-align:center;padding:40px">Memuat data...</td</tr>
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
     MODAL DETAIL IZIN
═══════════════════════════════════════════════ -->
<div class="modal-overlay" id="modal-detail-izin">
    <div class="modal modal-lg">
        <div class="modal-header">
            <div class="modal-title">Detail Pengajuan Izin</div>
            <button class="btn-close-modal" onclick="closeModal('modal-detail-izin')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body" id="detail-izin-content">
            <div style="text-align:center;padding:40px">Memuat data...</div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-detail-izin')">Tutup</button>
        </div>
    </div>
</div>

<!-- Modal: Preview File -->
<div class="modal-overlay" id="modal-preview-file">
    <div class="modal modal-sm">
        <div class="modal-header">
            <div class="modal-title">Pratinjau Bukti Surat</div>
            <button class="btn-close-modal" onclick="closeModal('modal-preview-file')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body" id="preview-content" style="text-align:center;min-height:200px">
            <div style="text-align:center;padding:40px">Memuat berkas...</div>
        </div>
        <div class="modal-footer">
            <a href="#" id="download-link" class="btn btn-primary" download style="text-decoration:none;display:inline-flex;align-items:center;gap:6px">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 17 12 23 18 17"/>
                    <polyline points="6 7 12 1 18 7"/>
                    <line x1="12" y1="23" x2="12" y2="1"/>
                </svg>
                Unduh
            </a>
            <button class="btn btn-secondary" onclick="closeModal('modal-preview-file')">Tutup</button>
        </div>
    </div>
</div>

<script>
// ════════════════════════════════════════════════
// GLOBAL VARIABLES
// ════════════════════════════════════════════════
let currentPage = 1;
let totalPages = 1;
let totalRecords = 0;

// ════════════════════════════════════════════════
// INITIALIZATION
// ════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    loadDosenOptions();
    loadData();
});

// ════════════════════════════════════════════════
// LOAD DOSEN OPTIONS
// ════════════════════════════════════════════════
function loadDosenOptions() {
    fetch('api/izin.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_dosen_list'
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            let options = '<option value="">Semua Dosen</option>';
            response.data.forEach(dosen => {
                options += `<option value="${dosen.id}">${escapeHtml(dosen.nama)}</option>`;
            });
            document.getElementById('filter-dosen').innerHTML = options;
        }
    })
    .catch(error => console.error('Error loading dosen:', error));
}

// ════════════════════════════════════════════════
// LOAD DATA
// ════════════════════════════════════════════════
function loadData(page = 1) {
    currentPage = page;
    
    const status = document.getElementById('filter-status').value;
    const jenis = document.getElementById('filter-jenis').value;
    const dosen_id = document.getElementById('filter-dosen').value;
    
    let body = `action=get_all_izin&page=${page}&limit=10`;
    if (status) body += `&status=${status}`;
    if (jenis) body += `&jenis=${jenis}`;
    if (dosen_id) body += `&dosen_id=${dosen_id}`;
    
    const tbody = document.getElementById('table-body');
    tbody.innerHTML = '<tr class="skeleton-row"><td colspan="9" style="text-align:center;padding:30px"><span class="spinner"></span> Memuat...</td></tr>';
    
    fetch('api/izin.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            renderTable(response.data);
            renderPagination(response);
        } else {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--red)">${escapeHtml(response.msg || 'Gagal memuat data')}</td></tr>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--red)">Terjadi kesalahan</td></tr>';
    });
}

// ════════════════════════════════════════════════
// RENDER TABLE
// ════════════════════════════════════════════════
function renderTable(data) {
    const tbody = document.getElementById('table-body');
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px">Tidak terdapat data pengajuan izin</td></tr>';
        return;
    }
    
    let html = '';
    data.forEach((item, index) => {
        const no = (currentPage - 1) * 10 + index + 1;
        
        let statusClass = 'pill-pending';
        let statusText = 'Pending';
        if (item.status === 'disetujui') {
            statusClass = 'pill-disetujui';
            statusText = 'Disetujui';
        } else if (item.status === 'ditolak') {
            statusClass = 'pill-ditolak';
            statusText = 'Ditolak';
        }
        
        let jenisClass = item.jenis === 'sakit' ? 'pill-sakit' : 'pill-izin';
        let jenisText = item.jenis === 'sakit' ? 'Sakit' : 'Izin';
        
        const tglPengajuan = formatDateTime(item.created_at);
        
        let buktiHtml = '-';
        if (item.file_surat) {
            buktiHtml = `<a href="javascript:void(0)" class="file-link" onclick="previewFile('${escapeHtml(item.file_surat)}')">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                            Lihat
                        </a>`;
        }
        
        html += `<tr>
            <td>${no}</td>
            <td><strong>${escapeHtml(item.nama_mahasiswa)}</strong><br><small style="color:#64748B">${escapeHtml(item.nim)}</small></td>
            <td><span class="pill ${jenisClass}">${jenisText}</span></td>
            <td style="max-width:200px;word-break:break-word">${escapeHtml(item.keterangan || '-').substring(0,50)}${escapeHtml(item.keterangan || '').length > 50 ? '...' : ''}</td>
            <td>${escapeHtml(item.nama_dosen || '-')}</td>
            <td><span class="pill ${statusClass}">${statusText}</span></td>
            <td>${tglPengajuan}</td>
            <td>${buktiHtml}</td>
            <td>
                <button class="btn btn-info btn-xs" onclick="viewDetail(${item.id})">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M22 12c-2.667 4.667-6 7-10 7s-7.333-2.333-10-7c2.667-4.667 6-7 10-7s7.333 2.333 10 7z"/>
                    </svg>
                    Detail
                </button>
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
    
    document.getElementById('page-info').textContent = `${start}-${end} dari ${response.total}`;
    
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
// VIEW DETAIL
// ════════════════════════════════════════════════
function viewDetail(id) {
    const contentDiv = document.getElementById('detail-izin-content');
    contentDiv.innerHTML = '<div style="text-align:center;padding:40px"><span class="spinner"></span> Memuat...</div>';
    openModal('modal-detail-izin');
    
    fetch('api/izin.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=get_detail_izin&id=${id}`
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            renderDetailContent(response.data);
        } else {
            contentDiv.innerHTML = `<div style="text-align:center;padding:40px;color:var(--red)">${escapeHtml(response.msg || 'Gagal memuat detail')}</div>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        contentDiv.innerHTML = '<div style="text-align:center;padding:40px;color:var(--red)">Terjadi kesalahan</div>';
    });
}

function renderDetailContent(item) {
    const statusClass = item.status === 'disetujui' ? 'pill-disetujui' : (item.status === 'ditolak' ? 'pill-ditolak' : 'pill-pending');
    const statusText = item.status === 'disetujui' ? 'Disetujui' : (item.status === 'ditolak' ? 'Ditolak' : 'Pending');
    const jenisClass = item.jenis === 'sakit' ? 'pill-sakit' : 'pill-izin';
    const jenisText = item.jenis === 'sakit' ? 'Sakit' : 'Izin';
    
    let fileHtml = '-';
    if (item.file_surat) {
        fileHtml = `<a href="javascript:void(0)" onclick="previewFile('${escapeHtml(item.file_surat)}')" class="file-link" style="display:inline-flex;align-items:center;gap:6px">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                        Lihat Berkas
                    </a>`;
    }
    
    const content = `
        <div class="detail-card" style="background:#F8FAFC;border-radius:12px;padding:16px">
            <div class="detail-row"><div class="detail-label">Mahasiswa</div><div class="detail-value"><strong>${escapeHtml(item.nama_mahasiswa)}</strong></div></div>
            <div class="detail-row"><div class="detail-label">NIM</div><div class="detail-value">${escapeHtml(item.nim)}</div></div>
            <div class="detail-row"><div class="detail-label">Kelas</div><div class="detail-value">${escapeHtml(item.nama_kelas)}</div></div>
            <div class="detail-row"><div class="detail-label">Jenis Izin</div><div class="detail-value"><span class="pill ${jenisClass}">${jenisText}</span></div></div>
            <div class="detail-row"><div class="detail-label">Mata Kuliah</div><div class="detail-value">${escapeHtml(item.nama_mk)}</div></div>
            <div class="detail-row"><div class="detail-label">Pertemuan Ke-</div><div class="detail-value">${item.pertemuan_ke || '-'}</div></div>
            <div class="detail-row"><div class="detail-label">Keterangan</div><div class="detail-value">${escapeHtml(item.keterangan || '-')}</div></div>
            <div class="detail-row"><div class="detail-label">Dosen</div><div class="detail-value">${escapeHtml(item.nama_dosen)}</div></div>
            <div class="detail-row"><div class="detail-label">Status</div><div class="detail-value"><span class="pill ${statusClass}">${statusText}</span></div></div>
            <div class="detail-row"><div class="detail-label">Catatan Dosen</div><div class="detail-value">${escapeHtml(item.catatan || '-')}</div></div>
            <div class="detail-row"><div class="detail-label">Tgl Pengajuan</div><div class="detail-value">${formatDateTime(item.created_at)}</div></div>
            <div class="detail-row"><div class="detail-label">Tgl Approval</div><div class="detail-value">${item.approved_at ? formatDateTime(item.approved_at) : '-'}</div></div>
            <div class="detail-row"><div class="detail-label">Bukti Surat</div><div class="detail-value">${fileHtml}</div></div>
        </div>
    `;
    
    document.getElementById('detail-izin-content').innerHTML = content;
}

// ════════════════════════════════════════════════
// PREVIEW FILE
// ════════════════════════════════════════════════
function previewFile(filename) {
    const fileUrl = `/uploads/izin/${filename}`;
    const fileExt = filename.split('.').pop().toLowerCase();
    const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt);
    const isPDF = fileExt === 'pdf';
    
    const previewDiv = document.getElementById('preview-content');
    const downloadLink = document.getElementById('download-link');
    downloadLink.href = fileUrl;
    
    if (isImage) {
        previewDiv.innerHTML = `<img src="${fileUrl}" alt="Bukti Surat" style="max-width:100%;max-height:60vh;border-radius:8px">`;
    } else if (isPDF) {
        previewDiv.innerHTML = `<iframe src="${fileUrl}" style="width:100%;height:60vh;border:none;border-radius:8px"></iframe>`;
    } else {
        previewDiv.innerHTML = `
            <div style="text-align:center;padding:30px">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
                <p style="margin-top:12px;color:var(--text3)">Pratinjau tidak tersedia</p>
                <p style="font-size:12px;color:var(--text3)">Silakan unduh berkas untuk melihatnya</p>
            </div>
        `;
    }
    
    openModal('modal-preview-file');
}

// ════════════════════════════════════════════════
// FILTER FUNCTIONS
// ════════════════════════════════════════════════
function resetFilter() {
    document.getElementById('filter-status').value = '';
    document.getElementById('filter-jenis').value = '';
    document.getElementById('filter-dosen').value = '';
    loadData(1);
}

function refreshData() {
    loadData(currentPage);
    toast('Data berhasil disegarkan', 'success');
}

// ════════════════════════════════════════════════
// UTILITY FUNCTIONS
// ════════════════════════════════════════════════
function formatDateTime(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

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
    const toastEl = document.createElement('div');
    toastEl.className = 'toast-notif';
    toastEl.style.background = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6';
    toastEl.textContent = message;
    document.body.appendChild(toastEl);
    
    setTimeout(() => {
        toastEl.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toastEl.remove(), 300);
    }, duration);
}

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