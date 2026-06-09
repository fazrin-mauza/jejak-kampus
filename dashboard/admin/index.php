<?php
require_once 'header.php';
require_once 'navigasi.php';
?>

<style>
/* Warna pastel untuk stat card */
.stat-card.ora { background: linear-gradient(135deg, #FFF5EB 0%, #FFE8D6 100%); border-left: 4px solid #F97316; }
.stat-card.green { background: linear-gradient(135deg, #ECFDF5 0%, #D8F3E8 100%); border-left: 4px solid #10B981; }
.stat-card.blue { background: linear-gradient(135deg, #EBF5FF 0%, #D6EAFF 100%); border-left: 4px solid #3B82F6; }
.stat-card.purple { background: linear-gradient(135deg, #F5F3FF 0%, #EDE9FE 100%); border-left: 4px solid #8B5CF6; }

/* Perbaikan tampilan stat card */
.stat-card {
    transition: all 0.3s ease;
    cursor: default;
    background: #FFFFFF;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.stat-card:hover { 
    transform: translateY(-2px); 
    box-shadow: 0 8px 20px rgba(0,0,0,0.08); 
}

/* Warna icon SVG */
.stat-icon-svg { width: 32px; height: 32px; margin-bottom: 12px; }
.stat-icon-svg.ora svg { stroke: #F97316; fill: none; }
.stat-icon-svg.green svg { stroke: #10B981; fill: none; }
.stat-icon-svg.blue svg { stroke: #3B82F6; fill: none; }
.stat-icon-svg.purple svg { stroke: #8B5CF6; fill: none; }

/* Badge warna pastel */
.badge-up { background: #D1FAE5; color: #059669; }
.badge-down { background: #FEE2E2; color: #DC2626; }

/* Grid stats */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}
</style>

<!-- ═══════════════════════════════════════════════
     DASHBOARD UTAMA - DOSEN
═══════════════════════════════════════════════ -->
<div>
    <div class="page-header">
        <div>
           
            <div class="page-subtitle">Ringkasan Sistem Jejak Kampus — <span id="semester-info">Semester Genap 2024/2025</span></div>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary btn-sm" onclick="refreshDashboard()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M20.49 15a9 9 0 0 1-14.85 3.36L1 14"/>
                </svg>
                Segarkan
            </button>
            <span class="pill pill-green" id="system-status">● Sistem Aktif</span>
        </div>
    </div>

    <!-- Baris Statistik 1 -->
    <div class="stats-grid">
        <div class="stat-card ora">
            <div class="stat-icon-svg ora">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="stat-label">Total Mahasiswa</div>
            <div class="stat-value" id="total-mahasiswa">0</div>
            <div class="stat-sub">Terdaftar Aktif Semester Ini</div>
            <span class="stat-badge badge-up" id="mahasiswa-baru">↑ +0 Baru</span>
        </div>

        <div class="stat-card green">
            <div class="stat-icon-svg green">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                    <path d="M22 3.74a4 4 0 0 1 0 6.52"/>
                </svg>
            </div>
            <div class="stat-label">Total Dosen</div>
            <div class="stat-value" id="total-dosen">0</div>
            <div class="stat-sub">Aktif Mengajar</div>
            <span class="stat-badge badge-up" id="dosen-aktif">● 0 Aktif</span>
        </div>

        <div class="stat-card blue">
            <div class="stat-icon-svg blue">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                </svg>
            </div>
            <div class="stat-label">Total Kelas</div>
            <div class="stat-value" id="total-kelas">0</div>
            <div class="stat-sub">Aktif Semester Ini</div>
            <span class="stat-badge badge-info" id="total-jurusan">0 Program Studi</span>
        </div>

        <div class="stat-card purple">
            <div class="stat-icon-svg purple">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                </svg>
            </div>
            <div class="stat-label">Mata Kuliah</div>
            <div class="stat-value" id="total-mk">0</div>
            <div class="stat-sub">Total SKS Ditawarkan</div>
            <span class="stat-badge badge-info" id="total-sks">0 SKS</span>
        </div>
    </div>

    <!-- Baris Grafik -->
    <div class="grid-2" style="margin-bottom:18px">
        <!-- Statistik Status Mahasiswa -->
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Statistik Status Mahasiswa</div>
                    <div class="card-sub">Distribusi Status Keaktifan</div>
                </div>
            </div>
            <div class="donut-wrap">
                <svg class="donut-svg" width="110" height="110" viewBox="0 0 110 110">
                    <circle cx="55" cy="55" r="40" fill="none" stroke="#f0f0f0" stroke-width="18"/>
                    <circle cx="55" cy="55" r="40" fill="none" stroke="#1B9E6A" stroke-width="18" stroke-dasharray="0 251" stroke-dashoffset="0" transform="rotate(-90 55 55)" id="donut-aktif"/>
                    <circle cx="55" cy="55" r="40" fill="none" stroke="#2563EB" stroke-width="18" stroke-dasharray="0 251" stroke-dashoffset="0" transform="rotate(-90 55 55)" id="donut-cuti"/>
                    <circle cx="55" cy="55" r="40" fill="none" stroke="#FF881B" stroke-width="18" stroke-dasharray="0 251" stroke-dashoffset="0" transform="rotate(-90 55 55)" id="donut-lulus"/>
                    <circle cx="55" cy="55" r="40" fill="none" stroke="#D63B3B" stroke-width="18" stroke-dasharray="0 251" stroke-dashoffset="0" transform="rotate(-90 55 55)" id="donut-dropout"/>
                    <text x="55" y="51" text-anchor="middle" font-size="16" font-weight="800" font-family="Space Grotesk,sans-serif" fill="#1A0F00" id="total-mahasiswa-donut">0</text>
                    <text x="55" y="64" text-anchor="middle" font-size="9" fill="#B07B4A" font-family="Plus Jakarta Sans,sans-serif">Mahasiswa</text>
                </svg>
                <div class="donut-legend">
                    <div class="donut-row"><span class="donut-label"><span class="donut-dot" style="background:var(--green)"></span>Aktif</span><span class="donut-val" id="legend-aktif">0</span></div>
                    <div class="donut-row"><span class="donut-label"><span class="donut-dot" style="background:var(--blue)"></span>Cuti</span><span class="donut-val" id="legend-cuti">0</span></div>
                    <div class="donut-row"><span class="donut-label"><span class="donut-dot" style="background:var(--ora3)"></span>Lulus</span><span class="donut-val" id="legend-lulus">0</span></div>
                    <div class="donut-row"><span class="donut-label"><span class="donut-dot" style="background:var(--red)"></span>Drop Out</span><span class="donut-val" id="legend-dropout">0</span></div>
                </div>
            </div>
        </div>

        <!-- Statistik Dosen & Kehadiran -->
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Statistik Dosen dan Kehadiran</div>
                    <div class="card-sub">Status Dosen Aktif</div>
                </div>
            </div>
            <div class="mini-stats" style="margin-bottom:14px">
                <div class="mini-stat"><div class="mini-stat-label">Aktif</div><div class="mini-stat-val" style="color:var(--green)" id="dosen-aktif-stat">0</div></div>
                <div class="mini-stat"><div class="mini-stat-label">Nonaktif</div><div class="mini-stat-val" style="color:var(--red)" id="dosen-nonaktif-stat">0</div></div>
                <div class="mini-stat"><div class="mini-stat-label">Cuti</div><div class="mini-stat-val" style="color:var(--blue)" id="dosen-cuti-stat">0</div></div>
                <div class="mini-stat"><div class="mini-stat-label">Pensiun</div><div class="mini-stat-val" style="color:var(--ora3)" id="dosen-pensiun-stat">0</div></div>
            </div>
            <div style="font-size:12px;font-weight:700;color:var(--text3);margin-bottom:8px">Grafik Kehadiran Minggu Ini</div>
            <div class="chart-bars">
                <div class="bar-wrap"><div class="bar-val" id="bar-sen-val">0%</div><div class="bar" style="height:0%" id="bar-sen"></div><div class="bar-label">Sen</div></div>
                <div class="bar-wrap"><div class="bar-val" id="bar-sel-val">0%</div><div class="bar" style="height:0%" id="bar-sel"></div><div class="bar-label">Sel</div></div>
                <div class="bar-wrap"><div class="bar-val" id="bar-rab-val">0%</div><div class="bar" style="height:0%" id="bar-rab"></div><div class="bar-label">Rab</div></div>
                <div class="bar-wrap"><div class="bar-val" id="bar-kam-val">0%</div><div class="bar" style="height:0%" id="bar-kam"></div><div class="bar-label">Kam</div></div>
                <div class="bar-wrap"><div class="bar-val" id="bar-jum-val">0%</div><div class="bar" style="height:0%" id="bar-jum"></div><div class="bar-label">Jum</div></div>
            </div>
            <div class="chart-legend" style="margin-top:8px">
                <span class="legend-item"><span class="legend-dot" style="background:var(--green)"></span>Hadir</span>
                <span class="legend-item"><span class="legend-dot" style="background:var(--red)"></span>Terlambat atau Tidak Hadir</span>
            </div>
        </div>
    </div>

    <!-- Aktivitas Terbaru -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
                    <polygon points="13 2 3 7 3 17 13 22 23 17 23 7 13 2"/>
                    <line x1="13" y1="22" x2="13" y2="12"/>
                </svg>
                Aktivitas Sistem Terbaru
            </div>
            <button class="btn btn-secondary btn-sm" onclick="refreshActivities()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M20.49 15a9 9 0 0 1-14.85 3.36L1 14"/>
                </svg>
                Segarkan
            </button>
        </div>
        <div class="tbl-wrap">
            <table class="tabel">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Waktu</th>
                        <th>Aksi</th>
                        <th>Oleh</th>
                        <th>Detail</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="activities-table">
                    <tr><td colspan="6" style="text-align:center;padding:40px">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// ════════════════════════════════════════════════
// DASHBOARD DOSEN - Dynamic Data Loader
// ════════════════════════════════════════════════

function loadDashboard() {
    console.log('Memuat dashboard dosen...');
    loadMainStats();
    loadStatusStats();
    loadDosenStats();
    loadAttendanceWeekly();
    loadRecentActivities();
}

// Statistik Utama
function loadMainStats() {
    fetch('api/dashboard.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_stats'
    })
    .then(res => res.json())
    .then(response => {
        console.log('Respons statistik utama:', response);
        if (response.success) {
            const data = response.data;
            document.getElementById('total-mahasiswa').textContent = formatNumber(data.total_mahasiswa);
            document.getElementById('mahasiswa-baru').innerHTML = `↑ +${data.total_mahasiswa_baru} Baru`;
            document.getElementById('total-dosen').textContent = formatNumber(data.total_dosen);
            document.getElementById('dosen-aktif').innerHTML = `● ${data.total_dosen_aktif} Aktif`;
            document.getElementById('total-kelas').textContent = formatNumber(data.total_kelas);
            document.getElementById('total-jurusan').innerHTML = `${data.total_jurusan} Program Studi`;
            document.getElementById('total-mk').textContent = formatNumber(data.total_mk);
            document.getElementById('total-sks').innerHTML = `${data.total_sks} SKS`;
        } else {
            console.error('Error statistik utama:', response.msg);
        }
    })
    .catch(error => {
        console.error('Error pengambilan data:', error);
    });
}

// Statistik Status Mahasiswa
function loadStatusStats() {
    fetch('api/dashboard.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_status_stats'
    })
    .then(res => res.json())
    .then(response => {
        console.log('Respons statistik status:', response);
        if (response.success) {
            const data = response.data;
            document.getElementById('legend-aktif').textContent = formatNumber(data.aktif);
            document.getElementById('legend-cuti').textContent = formatNumber(data.cuti);
            document.getElementById('legend-lulus').textContent = formatNumber(data.lulus);
            document.getElementById('legend-dropout').textContent = formatNumber(data.dropout);
            document.getElementById('total-mahasiswa-donut').textContent = formatNumber(data.total);
            
            updateDonutChart(data.aktif, data.cuti, data.lulus, data.dropout, data.total);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Grafik Donut
function updateDonutChart(aktif, cuti, lulus, dropout, total) {
    if (total === 0) return;
    
    const circumference = 2 * Math.PI * 40;
    const aktifPercent = (aktif / total) * 100;
    const cutiPercent = (cuti / total) * 100;
    const lulusPercent = (lulus / total) * 100;
    const dropoutPercent = (dropout / total) * 100;
    
    const aktifDash = (aktifPercent / 100) * circumference;
    const cutiDash = (cutiPercent / 100) * circumference;
    const lulusDash = (lulusPercent / 100) * circumference;
    const dropoutDash = (dropoutPercent / 100) * circumference;
    
    const donutAktif = document.getElementById('donut-aktif');
    const donutCuti = document.getElementById('donut-cuti');
    const donutLulus = document.getElementById('donut-lulus');
    const donutDropout = document.getElementById('donut-dropout');
    
    if (donutAktif) donutAktif.setAttribute('stroke-dasharray', `${aktifDash} ${circumference - aktifDash}`);
    if (donutCuti) donutCuti.setAttribute('stroke-dasharray', `${cutiDash} ${circumference - cutiDash}`);
    if (donutCuti) donutCuti.setAttribute('stroke-dashoffset', `-${aktifDash}`);
    if (donutLulus) donutLulus.setAttribute('stroke-dasharray', `${lulusDash} ${circumference - lulusDash}`);
    if (donutLulus) donutLulus.setAttribute('stroke-dashoffset', `-${aktifDash + cutiDash}`);
    if (donutDropout) donutDropout.setAttribute('stroke-dasharray', `${dropoutDash} ${circumference - dropoutDash}`);
    if (donutDropout) donutDropout.setAttribute('stroke-dashoffset', `-${aktifDash + cutiDash + lulusDash}`);
}

// Statistik Dosen
function loadDosenStats() {
    fetch('api/dashboard.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_dosen_stats'
    })
    .then(res => res.json())
    .then(response => {
        console.log('Respons statistik dosen:', response);
        if (response.success) {
            const data = response.data;
            document.getElementById('dosen-aktif-stat').textContent = formatNumber(data.aktif);
            document.getElementById('dosen-nonaktif-stat').textContent = formatNumber(data.nonaktif);
            document.getElementById('dosen-cuti-stat').textContent = formatNumber(data.cuti);
            document.getElementById('dosen-pensiun-stat').textContent = formatNumber(data.pensiun);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Kehadiran Mingguan
function loadAttendanceWeekly() {
    fetch('api/dashboard.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_attendance_weekly'
    })
    .then(res => res.json())
    .then(response => {
        console.log('Respons kehadiran mingguan:', response);
        if (response.success) {
            const data = response.data;
            const dayMap = { 'Senin': 'sen', 'Selasa': 'sel', 'Rabu': 'rab', 'Kamis': 'kam', 'Jumat': 'jum' };
            
            for (const [day, percentage] of Object.entries(data)) {
                const shortDay = dayMap[day];
                if (shortDay) {
                    const barElement = document.getElementById(`bar-${shortDay}`);
                    const valElement = document.getElementById(`bar-${shortDay}-val`);
                    if (barElement) {
                        barElement.style.height = `${percentage}%`;
                        if (percentage >= 80) {
                            barElement.style.background = 'linear-gradient(var(--green), #5edaad)';
                        } else if (percentage >= 60) {
                            barElement.style.background = 'linear-gradient(#FFB347, #FF881B)';
                        } else {
                            barElement.style.background = 'linear-gradient(#FF8B8B, #E24B4A)';
                        }
                    }
                    if (valElement) valElement.textContent = `${percentage}%`;
                }
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

// Aktivitas Terbaru
function loadRecentActivities() {
    fetch('api/dashboard.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_recent_activities'
    })
    .then(res => res.json())
    .then(response => {
        console.log('Respons aktivitas terbaru:', response);
        if (response.success && response.data.length > 0) {
            let html = '';
            response.data.forEach(act => {
                html += `<tr>
                    <td style="font-weight:800;color:var(--text3)">${act.no}</td>
                    <td>${escapeHtml(act.waktu)}</td>
                    <td>${escapeHtml(act.aksi)}</td>
                    <td>${escapeHtml(act.oleh)}</td>
                    <td>${escapeHtml(act.detail)}</td>
                    <td><span class="pill ${act.status_class}">${escapeHtml(act.status)}</span></td>
                </tr>`;
            });
            document.getElementById('activities-table').innerHTML = html;
        } else {
            document.getElementById('activities-table').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px">Belum terdapat aktivitas</td></tr>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('activities-table').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px">Gagal memuat aktivitas</td></tr>';
    });
}

function refreshDashboard() {
    tampilkanToast('Memuat ulang data...', 'info');
    loadDashboard();
}

function refreshActivities() {
    tampilkanToast('Memuat aktivitas terbaru...', 'info');
    loadRecentActivities();
}

function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function tampilkanToast(message, type = 'info') {
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
        white-space: pre-line;
        max-width: 500px;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Tambahkan animasi CSS
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
`;
document.head.appendChild(style);

// Memuat data saat halaman siap
document.addEventListener('DOMContentLoaded', function() {
    loadDashboard();
    
    // Penyegaran otomatis setiap 60 detik
    setInterval(() => {
        loadMainStats();
        loadStatusStats();
        loadDosenStats();
    }, 60000);
});
</script>

<?php
require_once 'footer.php';
?>