<?php
require_once 'header.php';
require_once 'navigasi.php';
?>

<style>
/* Perbaikan tampilan responsif */
:root {
    --responsive-break: 768px;
}

/* Perbaikan filter bar */
.filter-bar {
    background: #F8FAFC;
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
}
.filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.filter-group label {
    font-size: 11px;
    font-weight: 600;
    color: #475569;
}
.filter-bar input, 
.filter-bar select {
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid #E2E8F0;
    background: white;
    font-size: 13px;
    min-width: 140px;
}
.filter-bar button {
    padding: 8px 16px;
    font-size: 13px;
}

/* Mini stats */
.mini-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
    background: #FFFFFF;
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.mini-stat {
    flex: 1;
    text-align: center;
    padding: 12px;
    background: #F8FAFC;
    border-radius: 12px;
}
.mini-stat-label {
    font-size: 12px;
    color: #64748B;
    margin-bottom: 8px;
}
.mini-stat-val {
    font-size: 24px;
    font-weight: 800;
    font-family: 'Space Grotesk', monospace;
}

/* Tabel */
.tbl-wrap {
    background: #FFFFFF;
    border-radius: 16px;
    overflow-x: auto;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.tabel {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}
.tabel thead tr {
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
}
.tabel th {
    font-weight: 600;
    font-size: 12px;
    color: #1E293B;
    padding: 12px 12px;
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

/* Status badges */
.pill-green { background: #10b981; color: white; }
.pill-red { background: #ef4444; color: white; }
.pill-ora { background: #f97316; color: white; }
.pill-blue { background: #3b82f6; color: white; }

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

/* Responsive */
@media (max-width: 768px) {
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    .filter-group {
        width: 100%;
    }
    .filter-bar input, .filter-bar select {
        width: 100%;
    }
    .mini-stats {
        flex-direction: column;
        gap: 10px;
    }
    .mini-stat {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 16px;
    }
    .mini-stat-label {
        margin-bottom: 0;
    }
    .mini-stat-val {
        font-size: 20px;
    }
    .pagination {
        flex-direction: column;
    }
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

/* Toast */
.toast-notif {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 13px;
    z-index: 10000;
    animation: slideIn 0.3s ease;
}
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
     MONITORING ABSENSI
═══════════════════════════════════════════════ -->
<div id="app-monitoring-absensi">
    <div class="page-header">
        <div>
            <div class="page-title">Monitoring Absensi</div>
            <div class="page-subtitle">Pantau kehadiran mahasiswa per kelas dan pertemuan</div>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary btn-sm" onclick="exportData('excel')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Ekspor Excel
            </button>
            <button class="btn btn-secondary btn-sm" onclick="exportData('pdf')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
                Ekspor PDF
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-group">
            <label>Tanggal</label>
            <input type="date" id="filter-tanggal" onchange="loadData()">
        </div>
        <div class="filter-group">
            <label>Kelas</label>
            <select id="filter-kelas" onchange="loadData()">
                <option value="">Semua Kelas</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Mata Kuliah</label>
            <select id="filter-mk" onchange="loadData()">
                <option value="">Semua Mata Kuliah</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Status</label>
            <select id="filter-status" onchange="loadData()">
                <option value="">Semua Status</option>
                <option value="hadir">Hadir</option>
                <option value="telat">Terlambat</option>
                <option value="alpha">Tidak Hadir</option>
                <option value="izin">Izin</option>
                <option value="sakit">Sakit</option>
            </select>
        </div>
        <button class="btn btn-primary btn-sm" onclick="loadData()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            Tampilkan
        </button>
        <button class="btn btn-secondary btn-sm" onclick="resetFilter()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                <path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M20.49 15a9 9 0 0 1-14.85 3.36L1 14"/>
            </svg>
            Reset
        </button>
    </div>

    <!-- Mini Stats -->
    <div class="mini-stats" id="mini-stats">
        <div class="mini-stat">
            <div class="mini-stat-label">Total Pertemuan</div>
            <div class="mini-stat-val" id="stat-total">0</div>
        </div>
        <div class="mini-stat">
            <div class="mini-stat-label">Hadir</div>
            <div class="mini-stat-val" style="color:#10b981" id="stat-hadir">0%</div>
        </div>
        <div class="mini-stat">
            <div class="mini-stat-label">Tidak Hadir</div>
            <div class="mini-stat-val" style="color:#ef4444" id="stat-alpha">0%</div>
        </div>
        <div class="mini-stat">
            <div class="mini-stat-label">Izin/Sakit</div>
            <div class="mini-stat-val" style="color:#3b82f6" id="stat-izin">0%</div>
        </div>
    </div>

    <!-- Table -->
    <div class="tbl-wrap">
        <table class="tabel">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nama Mahasiswa</th>
                    <th>NIM</th>
                    <th>Kelas</th>
                    <th>Mata Kuliah</th>
                    <th>Pertemuan</th>
                    <th>Waktu Absen</th>
                    <th>Lokasi</th>
                    <th>Status</th>
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

<script>
// ════════════════════════════════════════════════
// GLOBAL VARIABLES
// ════════════════════════════════════════════════
let currentPage = 1;
let currentFilter = {
    tanggal: '',
    kelas_id: '',
    mk_id: '',
    status: ''
};

// ════════════════════════════════════════════════
// INITIALIZATION
// ════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    // Set tanggal default ke hari ini
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('filter-tanggal').value = today;
    
    loadKelasOptions();
    loadMataKuliahOptions();
    loadData();
});

// ════════════════════════════════════════════════
// LOAD OPTIONS
// ════════════════════════════════════════════════
function loadKelasOptions() {
    fetch('api/absensi.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_kelas_list'
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            let options = '<option value="">Semua Kelas</option>';
            response.data.forEach(kelas => {
                options += `<option value="${kelas.id}">${escapeHtml(kelas.nama_kelas)}</option>`;
            });
            document.getElementById('filter-kelas').innerHTML = options;
        }
    })
    .catch(error => console.error('Error:', error));
}

function loadMataKuliahOptions() {
    fetch('api/absensi.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_mk_list'
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            let options = '<option value="">Semua Mata Kuliah</option>';
            response.data.forEach(mk => {
                options += `<option value="${mk.id}">${escapeHtml(mk.nama_mk)}</option>`;
            });
            document.getElementById('filter-mk').innerHTML = options;
        }
    })
    .catch(error => console.error('Error:', error));
}

// ════════════════════════════════════════════════
// LOAD DATA
// ════════════════════════════════════════════════
function loadData(page = 1) {
    currentPage = page;
    
    const tanggal = document.getElementById('filter-tanggal').value;
    const kelas_id = document.getElementById('filter-kelas').value;
    const mk_id = document.getElementById('filter-mk').value;
    const status = document.getElementById('filter-status').value;
    
    let body = `action=get_absensi&page=${page}&limit=10`;
    if (tanggal) body += `&tanggal=${tanggal}`;
    if (kelas_id) body += `&kelas_id=${kelas_id}`;
    if (mk_id) body += `&mk_id=${mk_id}`;
    if (status) body += `&status=${status}`;
    
    // Skeleton loading
    const tbody = document.getElementById('table-body');
    tbody.innerHTML = '<tr class="skeleton-row"><td colspan="9" style="text-align:center;padding:30px"><span class="spinner"></span> Memuat...</td></tr>';
    
    fetch('api/absensi.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            renderTable(response.data);
            renderPagination(response);
            updateStats(response.stats);
        } else {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:40px;color:#ef4444">${escapeHtml(response.msg || 'Gagal memuat data')}</td></tr>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px;color:#ef4444">Terjadi kesalahan</td></tr>';
    });
}

// ════════════════════════════════════════════════
// UPDATE STATS
// ════════════════════════════════════════════════
function updateStats(stats) {
    if (!stats) return;
    document.getElementById('stat-total').textContent = stats.total_absensi || 0;
    document.getElementById('stat-hadir').textContent = (stats.persen_hadir || 0) + '%';
    document.getElementById('stat-alpha').textContent = (stats.persen_alpha || 0) + '%';
    document.getElementById('stat-izin').textContent = (stats.persen_izin || 0) + '%';
}

// ════════════════════════════════════════════════
// RENDER TABLE
// ════════════════════════════════════════════════
function renderTable(data) {
    const tbody = document.getElementById('table-body');
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px">Tidak terdapat data absensi</td></tr>';
        return;
    }
    
    let html = '';
    data.forEach((item, index) => {
        const no = (currentPage - 1) * 10 + index + 1;
        
        let statusClass = '';
        let statusText = '';
        let lokasiText = '-';
        
        switch(item.status) {
            case 'hadir':
                statusClass = 'pill-green';
                statusText = 'Hadir';
                lokasiText = item.latitude ? '✅ Dalam area' : '-';
                break;
            case 'telat':
                statusClass = 'pill-ora';
                statusText = 'Terlambat';
                lokasiText = item.latitude ? '✅ Dalam area' : '-';
                break;
            case 'alpha':
                statusClass = 'pill-red';
                statusText = 'Tidak Hadir';
                lokasiText = '-';
                break;
            case 'izin':
                statusClass = 'pill-blue';
                statusText = 'Izin';
                lokasiText = '-';
                break;
            default:
                statusClass = 'pill-gray';
                statusText = item.status || '-';
        }
        
        const waktuAbsen = item.waktu_absen ? formatDateTime(item.waktu_absen) : '-';
        
        html += `<tr>
            <td>${no}</td>
            <td style="font-weight:700">${escapeHtml(item.nama_mahasiswa)}</td>
            <td><code>${escapeHtml(item.nim)}</code></td>
            <td>${escapeHtml(item.nama_kelas)}</td>
            <td>${escapeHtml(item.nama_mk)}</td>
            <td style="text-align:center">Ke-${item.pertemuan_ke || '-'}</td>
            <td>${waktuAbsen}</td>
            <td>${lokasiText}</td>
            <td><span class="pill ${statusClass}">${statusText}</span></td>
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
    
    document.getElementById('page-info').textContent = `Menampilkan ${start}-${end} dari ${response.total} data`;
    
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
function resetFilter() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('filter-tanggal').value = today;
    document.getElementById('filter-kelas').value = '';
    document.getElementById('filter-mk').value = '';
    document.getElementById('filter-status').value = '';
    loadData(1);
}

// ════════════════════════════════════════════════
// EXPORT FUNCTIONS
// ════════════════════════════════════════════════
function exportData(type) {
    const tanggal = document.getElementById('filter-tanggal').value;
    const kelas_id = document.getElementById('filter-kelas').value;
    const mk_id = document.getElementById('filter-mk').value;
    const status = document.getElementById('filter-status').value;
    
    let params = `action=export_${type}`;
    if (tanggal) params += `&tanggal=${tanggal}`;
    if (kelas_id) params += `&kelas_id=${kelas_id}`;
    if (mk_id) params += `&mk_id=${mk_id}`;
    if (status) params += `&status=${status}`;
    
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api/absensi.php';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = `export_${type}`;
    form.appendChild(actionInput);
    
    if (tanggal) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'tanggal';
        input.value = tanggal;
        form.appendChild(input);
    }
    
    if (kelas_id) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'kelas_id';
        input.value = kelas_id;
        form.appendChild(input);
    }
    
    if (mk_id) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'mk_id';
        input.value = mk_id;
        form.appendChild(input);
    }
    
    if (status) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'status';
        input.value = status;
        form.appendChild(input);
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    toast(`Ekspor ${type.toUpperCase()} sedang diproses...`, 'info');
}

// ════════════════════════════════════════════════
// UTILITY FUNCTIONS
// ════════════════════════════════════════════════
function formatDateTime(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${hours}:${minutes}`;
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;')
              .replace(/"/g, '&quot;')
              .replace(/'/g, '&#39;');
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
</script>

<?php
require_once 'footer.php';
?>