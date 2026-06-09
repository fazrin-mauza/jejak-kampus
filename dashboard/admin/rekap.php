<?php
require_once 'header.php';
require_once 'navigasi.php';
?>

<style>
/* Perbaikan tampilan rekap card */
.rekap-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    background: #FFFFFF;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid #E2E8F0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.rekap-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    border-color: #CBD5E1;
}
.rekap-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
}
.rekap-icon svg {
    width: 24px;
    height: 24px;
    stroke: white;
}

/* Modal rekap */
.modal-rekap {
    max-width: 90vw;
    width: 1000px;
    max-height: 85vh;
    overflow-y: auto;
}
.rekap-filter-bar {
    background: #F8FAFC;
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
}
.rekap-filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.rekap-filter-group label {
    font-size: 11px;
    font-weight: 600;
    color: #475569;
}
.rekap-filter-bar select, 
.rekap-filter-bar input {
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid #E2E8F0;
    background: white;
    font-size: 13px;
    min-width: 160px;
}
.stat-summary {
    background: #F8FAFC;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-around;
    flex-wrap: wrap;
    gap: 16px;
}
.stat-summary-item {
    text-align: center;
}
.stat-summary-label {
    font-size: 12px;
    color: #64748B;
}
.stat-summary-value {
    font-size: 24px;
    font-weight: 800;
    color: #1E293B;
}
.btn-export-group {
    display: flex;
    gap: 10px;
    margin-top: 16px;
    justify-content: flex-end;
}

/* Responsive */
@media (max-width: 768px) {
    .rekap-card {
        flex-direction: column;
        text-align: center;
    }
    .rekap-card span {
        display: none;
    }
    .rekap-filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    .rekap-filter-group {
        width: 100%;
    }
    .rekap-filter-bar select,
    .rekap-filter-bar input {
        width: 100%;
    }
    .btn-export-group {
        flex-direction: column;
    }
}

/* Tabel kecil untuk rekap */
.tabel-mini {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.tabel-mini th,
.tabel-mini td {
    padding: 8px 10px;
    border-bottom: 1px solid #E2E8F0;
    text-align: left;
}
.tabel-mini th {
    background: #F8FAFC;
    font-weight: 600;
    color: #1E293B;
}
.tabel-mini tr:hover {
    background: #F8FAFC;
}

/* Pagination kecil */
.pagination-mini {
    display: flex;
    justify-content: flex-end;
    gap: 5px;
    margin-top: 16px;
}
.page-btn-mini {
    padding: 4px 8px;
    border: 1px solid #E2E8F0;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 11px;
}
.page-btn-mini.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

/* Skeleton */
.skeleton-row td {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeletonPulse 1.5s ease infinite;
    height: 35px;
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
</style>

<!-- ═══════════════════════════════════════════════
     REKAP & LAPORAN
═══════════════════════════════════════════════ -->
<div>
    <div class="page-header">
        <div>
            <div class="page-title">Rekap & Laporan</div>
            <div class="page-subtitle">Ekspor data absensi dalam berbagai format</div>
        </div>
    </div>

    <!-- Card Menu Rekap -->
    <div class="grid-2" style="margin-bottom:18px">
        <div class="rekap-card" onclick="openRekapModal('mahasiswa')">
            <div class="rekap-icon" style="background:#10b981">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.8">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div>
                <div style="font-size:14px;font-weight:800;color:var(--text)">Rekap per Mahasiswa</div>
                <div style="font-size:12px;color:var(--text3);margin-top:3px">Detail kehadiran tiap mahasiswa</div>
            </div>
            <span style="margin-left:auto;font-size:18px;color:var(--text3)">→</span>
        </div>
        <div class="rekap-card" onclick="openRekapModal('kelas')">
            <div class="rekap-icon" style="background:#3b82f6">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.8">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                </svg>
            </div>
            <div>
                <div style="font-size:14px;font-weight:800;color:var(--text)">Rekap per Kelas</div>
                <div style="font-size:12px;color:var(--text3);margin-top:3px">Ringkasan kehadiran per kelas</div>
            </div>
            <span style="margin-left:auto;font-size:18px;color:var(--text3)">→</span>
        </div>
        <div class="rekap-card" onclick="openRekapModal('matakuliah')">
            <div class="rekap-icon" style="background:#f59e0b">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.8">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                </svg>
            </div>
            <div>
                <div style="font-size:14px;font-weight:800;color:var(--text)">Rekap per Mata Kuliah</div>
                <div style="font-size:12px;color:var(--text3);margin-top:3px">Statistik kehadiran per mata kuliah</div>
            </div>
            <span style="margin-left:auto;font-size:18px;color:var(--text3)">→</span>
        </div>
        <div class="rekap-card" onclick="openRekapModal('semester')">
            <div class="rekap-icon" style="background:#8b5cf6">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.8">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
            <div>
                <div style="font-size:14px;font-weight:800;color:var(--text)">Rekap per Semester</div>
                <div style="font-size:12px;color:var(--text3);margin-top:3px">Laporan komprehensif per semester</div>
            </div>
            <span style="margin-left:auto;font-size:18px;color:var(--text3)">→</span>
        </div>
    </div>

    <!-- Export Cepat Card -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
                    <polyline points="6 17 12 23 18 17"/>
                    <polyline points="6 7 12 1 18 7"/>
                    <line x1="12" y1="23" x2="12" y2="1"/>
                </svg>
                Ekspor Laporan Cepat
            </div>
        </div>
        <div class="filter-bar" style="margin-bottom:14px">
            <div class="filter-group">
                <label>Tipe Laporan</label>
                <select id="export-type">
                    <option value="mahasiswa">Per Mahasiswa</option>
                    <option value="kelas">Per Kelas</option>
                    <option value="matakuliah">Per Mata Kuliah</option>
                    <option value="semester">Per Semester</option>
                </select>
            </div>
            <div class="filter-group" id="export-filter-1">
                <label>Kelas</label>
                <select id="export-kelas">
                    <option value="">Semua Kelas</option>
                </select>
            </div>
            <div class="filter-group" id="export-filter-2">
                <label>Semester</label>
                <select id="export-semester">
                    <option value="">Semua Semester</option>
                </select>
            </div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button class="btn btn-success" onclick="quickExport('excel')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Ekspor Excel
            </button>
            <button class="btn btn-danger" onclick="quickExport('pdf')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
                Ekspor PDF
            </button>
            <button class="btn btn-secondary" onclick="quickPreview()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M22 12c-2.667 4.667-6 7-10 7s-7.333-2.333-10-7c2.667-4.667 6-7 10-7s7.333 2.333 10 7z"/>
                </svg>
                Pratinjau
            </button>
        </div>
    </div>
</div>

<!-- Modal Rekap Mahasiswa -->
<div class="modal-overlay" id="modal-rekap-mahasiswa">
    <div class="modal modal-rekap">
        <div class="modal-header">
            <div class="modal-title">Rekap Absensi per Mahasiswa</div>
            <button class="btn-close-modal" onclick="closeModal('modal-rekap-mahasiswa')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="rekap-filter-bar">
                <div class="rekap-filter-group">
                    <label>Kelas</label>
                    <select id="rekap-mhs-kelas" onchange="loadRekapMahasiswa()">
                        <option value="">Semua Kelas</option>
                    </select>
                </div>
                <div class="rekap-filter-group">
                    <label>Status Mahasiswa</label>
                    <select id="rekap-mhs-status" onchange="loadRekapMahasiswa()">
                        <option value="">Semua Status</option>
                        <option value="aktif">Aktif</option>
                        <option value="cuti">Cuti</option>
                        <option value="lulus">Lulus</option>
                    </select>
                </div>
                <button class="btn btn-primary btn-sm" onclick="loadRekapMahasiswa()">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Tampilkan
                </button>
            </div>
            <div id="rekap-mhs-content">
                <div style="text-align:center;padding:40px"><span class="spinner"></span> Memuat data...</div>
            </div>
            <div class="btn-export-group">
                <button class="btn btn-success btn-sm" onclick="exportRekap('mahasiswa', 'excel')">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 17 12 23 18 17"/><polyline points="6 7 12 1 18 7"/><line x1="12" y1="23" x2="12" y2="1"/></svg>
                    Ekspor Excel
                </button>
                <button class="btn btn-danger btn-sm" onclick="exportRekap('mahasiswa', 'pdf')">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Ekspor PDF
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Rekap Kelas -->
<div class="modal-overlay" id="modal-rekap-kelas">
    <div class="modal modal-rekap">
        <div class="modal-header">
            <div class="modal-title">Rekap Absensi per Kelas</div>
            <button class="btn-close-modal" onclick="closeModal('modal-rekap-kelas')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="rekap-filter-bar">
                <div class="rekap-filter-group">
                    <label>Tahun Akademik</label>
                    <select id="rekap-kelas-ta" onchange="loadRekapKelas()">
                        <option value="">Semua Tahun Akademik</option>
                    </select>
                </div>
                <button class="btn btn-primary btn-sm" onclick="loadRekapKelas()">Tampilkan</button>
            </div>
            <div id="rekap-kelas-content">
                <div style="text-align:center;padding:40px"><span class="spinner"></span> Memuat data...</div>
            </div>
            <div class="btn-export-group">
                <button class="btn btn-success btn-sm" onclick="exportRekap('kelas', 'excel')">Ekspor Excel</button>
                <button class="btn btn-danger btn-sm" onclick="exportRekap('kelas', 'pdf')">Ekspor PDF</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Rekap Mata Kuliah -->
<div class="modal-overlay" id="modal-rekap-matakuliah">
    <div class="modal modal-rekap">
        <div class="modal-header">
            <div class="modal-title">Rekap Absensi per Mata Kuliah</div>
            <button class="btn-close-modal" onclick="closeModal('modal-rekap-matakuliah')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="rekap-filter-bar">
                <div class="rekap-filter-group">
                    <label>Tahun Akademik</label>
                    <select id="rekap-mk-ta" onchange="loadRekapMatakuliah()">
                        <option value="">Semua Tahun Akademik</option>
                    </select>
                </div>
                <div class="rekap-filter-group">
                    <label>Kelas</label>
                    <select id="rekap-mk-kelas" onchange="loadRekapMatakuliah()">
                        <option value="">Semua Kelas</option>
                    </select>
                </div>
                <button class="btn btn-primary btn-sm" onclick="loadRekapMatakuliah()">Tampilkan</button>
            </div>
            <div id="rekap-mk-content">
                <div style="text-align:center;padding:40px"><span class="spinner"></span> Memuat data...</div>
            </div>
            <div class="btn-export-group">
                <button class="btn btn-success btn-sm" onclick="exportRekap('matakuliah', 'excel')">Ekspor Excel</button>
                <button class="btn btn-danger btn-sm" onclick="exportRekap('matakuliah', 'pdf')">Ekspor PDF</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Rekap Semester -->
<div class="modal-overlay" id="modal-rekap-semester">
    <div class="modal modal-rekap">
        <div class="modal-header">
            <div class="modal-title">Rekap Absensi per Semester</div>
            <button class="btn-close-modal" onclick="closeModal('modal-rekap-semester')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="rekap-filter-bar">
                <div class="rekap-filter-group">
                    <label>Tahun Akademik</label>
                    <select id="rekap-smt-ta" onchange="loadRekapSemester()">
                        <option value="">Semua Tahun Akademik</option>
                    </select>
                </div>
                <button class="btn btn-primary btn-sm" onclick="loadRekapSemester()">Tampilkan</button>
            </div>
            <div id="rekap-smt-content">
                <div style="text-align:center;padding:40px"><span class="spinner"></span> Memuat data...</div>
            </div>
            <div class="btn-export-group">
                <button class="btn btn-success btn-sm" onclick="exportRekap('semester', 'excel')">Ekspor Excel</button>
                <button class="btn btn-danger btn-sm" onclick="exportRekap('semester', 'pdf')">Ekspor PDF</button>
            </div>
        </div>
    </div>
</div>

<script>
// =====================================================
// REKAP & LAPORAN - SUPER LENGKAP
// =====================================================
let currentPageMhs = 1;

document.addEventListener('DOMContentLoaded', function() {
    loadKelasOptions();
    loadSemesterOptions();
    loadRekapMahasiswa();
});

function loadKelasOptions() {
    fetch('api/rekap.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_kelas_list'
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            let opt = '<option value="">Semua Kelas</option>';
            res.data.forEach(k => opt += `<option value="${k.id}">${k.nama_kelas}</option>`);
            document.getElementById('export-kelas').innerHTML = opt;
            document.getElementById('rekap-mhs-kelas').innerHTML = opt;
            document.getElementById('rekap-mk-kelas').innerHTML = opt;
        }
    });
}

function loadSemesterOptions() {
    fetch('api/rekap.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_tahun_akademik_list'
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            let opt = '<option value="">Semua Semester</option>';
            res.data.forEach(ta => opt += `<option value="${ta.id}">${ta.tahun} ${ta.semester}</option>`);
            document.getElementById('export-semester').innerHTML = opt;
            document.getElementById('rekap-kelas-ta').innerHTML = opt;
            document.getElementById('rekap-mk-ta').innerHTML = opt;
            document.getElementById('rekap-smt-ta').innerHTML = opt;
        }
    });
}

// ========== REKAP MAHASISWA ==========
function loadRekapMahasiswa(page = 1) {
    currentPageMhs = page;
    const kelas = document.getElementById('rekap-mhs-kelas').value;
    const status = document.getElementById('rekap-mhs-status').value;
    const body = `action=rekap_mahasiswa&page=${page}&limit=15&kelas_id=${kelas}&status=${status}`;
    
    document.getElementById('rekap-mhs-content').innerHTML = '<div style="text-align:center;padding:40px"><span class="spinner"></span> Memuat...</div>';
    
    fetch('api/rekap.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            renderRekapMahasiswa(res);
        } else {
            document.getElementById('rekap-mhs-content').innerHTML = '<div style="text-align:center;color:red">Gagal memuat data</div>';
        }
    });
}

function renderRekapMahasiswa(res) {
    let html = `
        <div class="stat-summary">
            <div class="stat-summary-item"><div class="stat-summary-label">Total Mahasiswa</div><div class="stat-summary-value">${res.total_mahasiswa}</div></div>
            <div class="stat-summary-item"><div class="stat-summary-label">Total Pertemuan</div><div class="stat-summary-value">${res.total_pertemuan}</div></div>
            <div class="stat-summary-item"><div class="stat-summary-label">Rata-rata Kehadiran</div><div class="stat-summary-value" style="color:#10b981">${res.rata_kehadiran}%</div></div>
        </div>
        <table class="tabel-mini">
            <thead><tr><th>No.</th><th>NIM</th><th>Nama</th><th>Kelas</th><th>Hadir</th><th>Alpha</th><th>Izin</th><th>%</th></tr></thead>
            <tbody>
    `;
    res.data.forEach((item, i) => {
        const no = (res.page-1)*res.limit + i+1;
        html += `<tr>
            <td>${no}</td>
            <td><code>${escapeHtml(item.nim)}</code></td>
            <td><strong>${escapeHtml(item.nama)}</strong></td>
            <td>${escapeHtml(item.nama_kelas)}</td>
            <td class="text-center">${item.hadir}</td>
            <td class="text-center">${item.alpha}</td>
            <td class="text-center">${item.izin}</td>
            <td class="text-center ${item.persen >= 75 ? 'text-success' : 'text-danger'}">${item.persen}%</td>
        </tr>`;
    });
    html += `</tbody></table>`;
    if (res.pages > 1) {
        html += `<div class="pagination-mini">`;
        if (res.page > 1) html += `<button class="page-btn-mini" onclick="loadRekapMahasiswa(${res.page-1})">‹</button>`;
        for (let i=Math.max(1, res.page-2); i<=Math.min(res.pages, res.page+2); i++) {
            html += `<button class="page-btn-mini ${i===res.page?'active':''}" onclick="loadRekapMahasiswa(${i})">${i}</button>`;
        }
        if (res.page < res.pages) html += `<button class="page-btn-mini" onclick="loadRekapMahasiswa(${res.page+1})">›</button>`;
        html += `</div>`;
    }
    document.getElementById('rekap-mhs-content').innerHTML = html;
}

// ========== REKAP KELAS ==========
function loadRekapKelas() {
    const ta = document.getElementById('rekap-kelas-ta').value;
    const body = `action=rekap_kelas&tahun_akademik_id=${ta}`;
    document.getElementById('rekap-kelas-content').innerHTML = '<div style="text-align:center;padding:40px"><span class="spinner"></span> Memuat...</div>';
    fetch('api/rekap.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body})
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                let html = `<table class="tabel-mini"><thead><tr><th>No.</th><th>Kelas</th><th>Jurusan</th><th>Angkatan</th><th>Jml Mahasiswa</th><th>Rata Kehadiran</th></tr></thead><tbody>`;
                res.data.forEach((row, idx) => {
                    html += `<tr>
                        <td>${idx+1}</td>
                        <td><strong>${escapeHtml(row.nama_kelas)}</strong></td>
                        <td>${escapeHtml(row.jurusan)}</td>
                        <td>${row.angkatan}</td>
                        <td class="text-center">${row.jml_mhs}</td>
                        <td class="text-center ${row.rata_persen >= 75 ? 'text-success' : 'text-danger'}">${row.rata_persen}%</td>
                    </tr>`;
                });
                html += `</tbody></table>`;
                document.getElementById('rekap-kelas-content').innerHTML = html;
            } else {
                document.getElementById('rekap-kelas-content').innerHTML = '<div style="text-align:center;color:red">Gagal memuat data</div>';
            }
        });
}

// ========== REKAP MATA KULIAH ==========
function loadRekapMatakuliah() {
    const ta = document.getElementById('rekap-mk-ta').value;
    const kelas = document.getElementById('rekap-mk-kelas').value;
    const body = `action=rekap_matakuliah&tahun_akademik_id=${ta}&kelas_id=${kelas}`;
    document.getElementById('rekap-mk-content').innerHTML = '<div style="text-align:center;padding:40px"><span class="spinner"></span> Memuat...</div>';
    fetch('api/rekap.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body})
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                let html = `<table class="tabel-mini"><thead><tr><th>No.</th><th>Kode</th><th>Mata Kuliah</th><th>SKS</th><th>Total Pertemuan</th><th>Rata Kehadiran</th></tr></thead><tbody>`;
                res.data.forEach((row, idx) => {
                    html += `<tr>
                        <td>${idx+1}</td>
                        <td><code>${escapeHtml(row.kode_mk)}</code></td>
                        <td><strong>${escapeHtml(row.nama_mk)}</strong></td>
                        <td class="text-center">${row.sks}</td>
                        <td class="text-center">${row.total_pertemuan}</td>
                        <td class="text-center ${row.rata_persen >= 75 ? 'text-success' : 'text-danger'}">${row.rata_persen}%</td>
                    </tr>`;
                });
                html += `</tbody></table>`;
                document.getElementById('rekap-mk-content').innerHTML = html;
            } else {
                document.getElementById('rekap-mk-content').innerHTML = '<div style="text-align:center;color:red">Gagal memuat data</div>';
            }
        });
}

// ========== REKAP SEMESTER ==========
function loadRekapSemester() {
    const ta = document.getElementById('rekap-smt-ta').value;
    const body = `action=rekap_semester&tahun_akademik_id=${ta}`;
    document.getElementById('rekap-smt-content').innerHTML = '<div style="text-align:center;padding:40px"><span class="spinner"></span> Memuat...</div>';
    fetch('api/rekap.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body})
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                let html = `<table class="tabel-mini"><thead><tr><th>No.</th><th>Tahun</th><th>Semester</th><th>Status</th><th>Total Kelas</th><th>Total Mahasiswa</th><th>Rata Kehadiran</th></tr></thead><tbody>`;
                res.data.forEach((row, idx) => {
                    html += `<tr>
                        <td>${idx+1}</td>
                        <td>${row.tahun}</td>
                        <td>${row.semester}</td>
                        <td>${row.status === 'aktif' ? 'Aktif' : 'Nonaktif'}</td>
                        <td class="text-center">${row.total_kelas}</td>
                        <td class="text-center">${row.total_mahasiswa}</td>
                        <td class="text-center ${row.rata_persen >= 75 ? 'text-success' : 'text-danger'}">${row.rata_persen}%</td>
                    </tr>`;
                });
                html += `</tbody></table>`;
                document.getElementById('rekap-smt-content').innerHTML = html;
            } else {
                document.getElementById('rekap-smt-content').innerHTML = '<div style="text-align:center;color:red">Gagal memuat data</div>';
            }
        });
}

// ========== EXPORT (CSV & PDF) ==========
function quickExport(format) {
    const type = document.getElementById('export-type').value;
    const kelas = document.getElementById('export-kelas').value;
    const ta = document.getElementById('export-semester').value;
    let action = '';
    if (format === 'excel') action = `export_excel_${type}`;
    else action = `export_pdf_${type}`;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api/rekap.php';
    form.target = '_blank';
    form.innerHTML = `<input type="hidden" name="action" value="${action}">`;
    if (kelas) form.innerHTML += `<input type="hidden" name="kelas_id" value="${kelas}">`;
    if (ta) form.innerHTML += `<input type="hidden" name="tahun_akademik_id" value="${ta}">`;
    if (type === 'mahasiswa') {
        const status = document.getElementById('export-status') ? document.getElementById('export-status').value : '';
        if (status) form.innerHTML += `<input type="hidden" name="status" value="${status}">`;
    }
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    toast(`Ekspor ${format.toUpperCase()} sedang diproses...`, 'info');
}

function quickPreview() {
    const type = document.getElementById('export-type').value;
    const kelas = document.getElementById('export-kelas').value;
    const ta = document.getElementById('export-semester').value;
    let url = `api/rekap.php?action=export_pdf_${type}`;
    if (kelas) url += `&kelas_id=${kelas}`;
    if (ta) url += `&tahun_akademik_id=${ta}`;
    if (type === 'mahasiswa') {
        const status = document.getElementById('export-status') ? document.getElementById('export-status').value : '';
        if (status) url += `&status=${status}`;
    }
    window.open(url, '_blank');
}

function exportRekap(type, format) {
    let action = (format === 'excel') ? `export_excel_${type}` : `export_pdf_${type}`;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api/rekap.php';
    form.target = '_blank';
    form.innerHTML = `<input type="hidden" name="action" value="${action}">`;
    if (type === 'mahasiswa') {
        const kelas = document.getElementById('rekap-mhs-kelas').value;
        const status = document.getElementById('rekap-mhs-status').value;
        if (kelas) form.innerHTML += `<input type="hidden" name="kelas_id" value="${kelas}">`;
        if (status) form.innerHTML += `<input type="hidden" name="status" value="${status}">`;
    } else if (type === 'kelas') {
        const ta = document.getElementById('rekap-kelas-ta').value;
        if (ta) form.innerHTML += `<input type="hidden" name="tahun_akademik_id" value="${ta}">`;
    } else if (type === 'matakuliah') {
        const ta = document.getElementById('rekap-mk-ta').value;
        const kelas = document.getElementById('rekap-mk-kelas').value;
        if (ta) form.innerHTML += `<input type="hidden" name="tahun_akademik_id" value="${ta}">`;
        if (kelas) form.innerHTML += `<input type="hidden" name="kelas_id" value="${kelas}">`;
    } else if (type === 'semester') {
        const ta = document.getElementById('rekap-smt-ta').value;
        if (ta) form.innerHTML += `<input type="hidden" name="tahun_akademik_id" value="${ta}">`;
    }
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    toast(`Ekspor ${format.toUpperCase()} sedang diproses...`, 'info');
}

function openRekapModal(type) {
    openModal(`modal-rekap-${type}`);
    if (type === 'mahasiswa') loadRekapMahasiswa();
    else if (type === 'kelas') loadRekapKelas();
    else if (type === 'matakuliah') loadRekapMatakuliah();
    else if (type === 'semester') loadRekapSemester();
}

// Utility
function escapeHtml(str) { if (!str) return ''; return str.replace(/[&<>]/g, function(m){if(m==='&') return '&amp;'; if(m==='<') return '&lt;'; if(m==='>') return '&gt;'; return m;}); }
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
function toast(msg, type) { /* sederhana */ alert(msg); } // ganti dengan toast yang sudah ada
</script>

<?php
require_once 'footer.php';
?>