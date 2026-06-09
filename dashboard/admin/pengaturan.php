<?php
require_once 'header.php';
require_once 'navigasi.php';
?>

<!-- Leaflet CSS & JS untuk Peta -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
/* Tampilan pengaturan */
.setting-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
}
.setting-row:last-child {
    border-bottom: none;
}
.setting-label {
    font-weight: 700;
    font-size: 14px;
    color: var(--text);
}
.setting-sub {
    font-size: 12px;
    color: var(--text3);
    margin-top: 2px;
}
.setting-control {
    display: flex;
    align-items: center;
    gap: 8px;
}
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
.btn-icon {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.spinner-small {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
    margin-right: 6px;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Modal Map */
.map-container {
    height: 400px;
    width: 100%;
    border-radius: 12px;
    margin-top: 12px;
    margin-bottom: 12px;
    border: 1px solid var(--border);
}
.coord-input-group {
    display: flex;
    gap: 12px;
    margin-top: 12px;
}
.coord-input-group .form-group {
    flex: 1;
}
.map-buttons {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    flex-wrap: wrap;
}
.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

/* Logo preview */
.logo-preview {
    width: 80px;
    height: 80px;
    background: var(--surface2);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--border);
    overflow: hidden;
}
.logo-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
</style>

<!-- ═══════════════════════════════════════════════
     PENGATURAN SISTEM
═══════════════════════════════════════════════ -->
<div id="app-pengaturan">
    <div class="page-header">
        <div>
            <div class="page-title">Pengaturan Sistem</div>
            <div class="page-subtitle">Konfigurasi global sistem Jejak Kampus</div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Informasi Aplikasi -->
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                        </svg>
                        Informasi Aplikasi
                    </div>
                    <div class="card-sub">Nama dan deskripsi aplikasi</div>
                </div>
            </div>
            <div class="card-body">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="font-weight: 600; margin-bottom: 6px; display: block;">Nama Aplikasi</label>
                    <input type="text" id="app-name-input" class="form-control" style="width: 100%;">
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="font-weight: 600; margin-bottom: 6px; display: block;">Deskripsi Aplikasi</label>
                    <textarea id="app-desc-input" rows="3" class="form-control" style="width: 100%; resize: vertical;"></textarea>
                </div>
                <button class="btn btn-primary" onclick="simpanAppInfo()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Simpan Informasi Aplikasi
                </button>
            </div>
        </div>

        <!-- Upload Logo -->
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
                            <rect x="2" y="2" width="20" height="20" rx="2.18"/>
                            <circle cx="8.5" cy="8.5" r="2.5"/>
                            <path d="M21 15l-5-5-6 6-3-3-5 5"/>
                        </svg>
                        Logo Aplikasi
                    </div>
                    <div class="card-sub">Ubah logo yang tampil di sidebar</div>
                </div>
            </div>
            <div class="card-body">
                <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                    <div class="logo-preview">
                        <img id="logo-img" src="/assets/logo.png" alt="Logo" onerror="this.src='/assets/logo.png'">
                    </div>
                    <div style="flex: 1;">
                        <input type="file" id="logo-input" accept="image/png,image/jpeg,image/jpg,image/webp" style="display: none;">
                        <button class="btn btn-secondary" onclick="document.getElementById('logo-input').click()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                <circle cx="12" cy="13" r="4"/>
                            </svg>
                            Pilih Logo
                        </button>
                        <button class="btn btn-primary" onclick="uploadLogo()" id="btn-upload-logo" style="display: none;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
                                <polyline points="6 17 12 23 18 17"/>
                                <polyline points="6 7 12 1 18 7"/>
                                <line x1="12" y1="23" x2="12" y2="1"/>
                            </svg>
                            Upload Logo
                        </button>
                    </div>
                </div>
                <div class="info-box" style="margin-top: 16px; background: #fef3c7; border-color: #fde68a;">
               
                </div>
            </div>
        </div>

        <!-- GPS & Lokasi -->
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        Pengaturan GPS & Lokasi
                    </div>
                    <div class="card-sub">Radius dan batas area absensi</div>
                </div>
            </div>
            <div class="card-body">
                <div class="setting-row">
                    <div>
                        <div class="setting-label">Radius Absensi Default</div>
                        <div class="setting-sub">Jarak maksimal mahasiswa dari titik absensi</div>
                    </div>
                    <div class="setting-control">
                        <input type="number" id="radius-absensi" value="500" style="width:80px; padding:7px 10px; border:1.5px solid var(--border-s); border-radius:var(--r-sm); font-family:inherit; font-size:13px">
                        <span style="font-size:12px; color:var(--text3)">meter</span>
                    </div>
                </div>
                <div class="setting-row">
                    <div>
                        <div class="setting-label">Koordinat Kampus Utama</div>
                        <div class="setting-sub" id="koordinat-text">Memuat...</div>
                    </div>
                    <div class="setting-control">
                        <button class="btn btn-secondary btn-sm" onclick="openModalMap()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                                <path d="M12 2a10 10 0 0 0-10 10c0 7 10 18 10 18s10-11 10-18a10 10 0 0 0-10-10z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            Pilih di Peta
                        </button>
                    </div>
                </div>
                <div class="setting-row">
                    <div>
                        <div class="setting-label">Persentase Minimal Kehadiran</div>
                        <div class="setting-sub">Batas minimal mahasiswa untuk lulus</div>
                    </div>
                    <div class="setting-control">
                        <input type="number" id="min-kehadiran" value="75" step="0.5" style="width:70px; padding:7px 10px; border:1.5px solid var(--border-s); border-radius:var(--r-sm); font-family:inherit; font-size:13px">
                        <span style="font-size:12px; color:var(--text3)">%</span>
                    </div>
                </div>
                <div style="margin-top: 20px">
                    <button class="btn btn-primary" onclick="simpanPengaturanGPS()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/>
                            <polyline points="7 3 7 8 15 8"/>
                        </svg>
                        Simpan Pengaturan GPS
                    </button>
                </div>
            </div>
        </div>

        <!-- Informasi Sistem -->
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        Informasi Sistem
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="detail-row">
                    <div class="detail-label">Nama Aplikasi</div>
                    <div class="detail-val" id="info-app-name">Jejak Kampus</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Deskripsi</div>
                    <div class="detail-val" id="info-app-desc">-</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Versi Aplikasi</div>
                    <div class="detail-val" id="info-app-version">v2.0</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Semester Aktif</div>
                    <div class="detail-val" id="info-semester">Memuat...</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Server</div>
                    <div class="detail-val" id="info-server">Memuat...</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Versi PHP</div>
                    <div class="detail-val"><?php echo phpversion(); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Versi Database</div>
                    <div class="detail-val" id="info-db">Memuat...</div>
                </div>
                <div style="margin-top: 16px">
                    <button class="btn btn-secondary btn-sm" onclick="downloadLog()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                            <polyline points="6 17 12 23 18 17"/>
                            <polyline points="6 7 12 1 18 7"/>
                            <line x1="12" y1="23" x2="12" y2="1"/>
                        </svg>
                        Unduh Log Aktivitas
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pilih Koordinat dengan Peta -->
<div class="modal-overlay" id="modal-map">
    <div class="modal" style="max-width: 800px; width: 90%;">
        <div class="modal-header">
            <div class="modal-title">Pilih Lokasi Kampus di Peta</div>
            <button class="btn-close-modal" onclick="closeModal('modal-map')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div id="map-container" class="map-container"></div>
            <div class="coord-input-group">
                <div class="form-group">
                    <label>Latitude</label>
                    <input type="text" id="map-lat" class="form-control" placeholder="-7.31628330" readonly style="background: #f5f5f5;">
                </div>
                <div class="form-group">
                    <label>Longitude</label>
                    <input type="text" id="map-lng" class="form-control" placeholder="112.72277730" readonly style="background: #f5f5f5;">
                </div>
            </div>
            <div class="map-buttons">
                <button class="btn btn-secondary btn-sm" onclick="getCurrentLocation()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="2" x2="12" y2="4"/>
                        <line x1="12" y1="20" x2="12" y2="22"/>
                        <line x1="2" y1="12" x2="4" y2="12"/>
                        <line x1="20" y1="12" x2="22" y2="12"/>
                    </svg>
                    Gunakan Lokasi Saya
                </button>
                <button class="btn btn-secondary btn-sm" onclick="searchAddress()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    Cari Alamat
                </button>
            </div>
            <div class="info-box" style="margin-top: 12px; background: #e0f2fe; border-color: #bae6fd;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                Klik pada peta untuk memilih titik koordinat. Pin akan muncul di lokasi yang dipilih.
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-map')">Batal</button>
            <button class="btn btn-primary" onclick="simpanKoordinatDariMap()">Simpan Koordinat</button>
        </div>
    </div>
</div>

<!-- Modal Cari Alamat (Simple Prompt) -->
<div class="modal-overlay" id="modal-search">
    <div class="modal" style="max-width: 400px;">
        <div class="modal-header">
            <div class="modal-title">Cari Alamat</div>
            <button class="btn-close-modal" onclick="closeModal('modal-search')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Cari Lokasi</label>
                <input type="text" id="search-address" class="form-control" placeholder="Masukkan nama jalan, gedung, atau kota">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-search')">Batal</button>
            <button class="btn btn-primary" onclick="doSearchAddress()">Cari</button>
        </div>
    </div>
</div>

<script>
// Variabel global untuk map
let map;
let marker;
let currentLat = -7.31628330;
let currentLng = 112.72277730;
let selectedLogoFile = null;

// ════════════════════════════════════════════════
// LOAD DATA PENGATURAN
// ════════════════════════════════════════════════
function loadPengaturan() {
    fetch('api/settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_settings'
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            const data = res.data;
            document.getElementById('radius-absensi').value = data.radius_absensi || 500;
            document.getElementById('min-kehadiran').value = data.min_kehadiran_persen || 75;
            document.getElementById('koordinat-text').innerHTML = `Lat: ${data.latitude || '-'}° | Lng: ${data.longitude || '-'}°`;
            
            // Isi form info aplikasi
            document.getElementById('app-name-input').value = data.app_name || 'Jejak Kampus';
            document.getElementById('app-desc-input').value = data.app_description || '';
            
            // Update info sistem
            document.getElementById('info-app-name').textContent = data.app_name || 'Jejak Kampus';
            document.getElementById('info-app-desc').textContent = data.app_description || '-';
            document.getElementById('info-app-version').textContent = data.app_version || 'v2.0';
            
            // Update koordinat untuk map
            currentLat = parseFloat(data.latitude) || -7.31628330;
            currentLng = parseFloat(data.longitude) || 112.72277730;
        }
    });
    
    // Load semester aktif
    fetch('api/settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_semester_aktif'
    })
    .then(res => res.json())
    .then(res => {
        if (res.success && res.data) {
            document.getElementById('info-semester').textContent = `${res.data.tahun} ${res.data.semester}`;
        } else {
            document.getElementById('info-semester').textContent = 'Tidak ada semester aktif';
        }
    });
    
    // Load info server dan database
    fetch('api/settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_server_info'
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            document.getElementById('info-server').textContent = res.data.server || '-';
            document.getElementById('info-db').textContent = res.data.db_version || '-';
        }
    });
}

// ════════════════════════════════════════════════
// SIMPAN INFORMASI APLIKASI (Nama & Deskripsi)
// ════════════════════════════════════════════════
function simpanAppInfo() {
    const appName = document.getElementById('app-name-input').value.trim();
    const appDesc = document.getElementById('app-desc-input').value.trim();
    
    if (!appName) {
        toast('Nama aplikasi harus diisi', 'error');
        return;
    }
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-small"></span> Menyimpan...';
    
    fetch('api/settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=update_app_info&app_name=${encodeURIComponent(appName)}&app_description=${encodeURIComponent(appDesc)}`
    })
    .then(res => res.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (res.success) {
            loadPengaturan(); // Refresh semua data
            toast('Informasi aplikasi berhasil disimpan', 'success');
        } else {
            toast(res.msg || 'Gagal menyimpan', 'error');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        toast('Terjadi kesalahan', 'error');
    });
}

// ════════════════════════════════════════════════
// UPLOAD LOGO (LANGSUNG TIMPA FILE)
// ════════════════════════════════════════════════
document.getElementById('logo-input').addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
        selectedLogoFile = e.target.files[0];
        const reader = new FileReader();
        reader.onload = function(event) {
            document.getElementById('logo-img').src = event.target.result;
            document.getElementById('btn-upload-logo').style.display = 'inline-flex';
        };
        reader.readAsDataURL(selectedLogoFile);
    }
});

function uploadLogo() {
    if (!selectedLogoFile) {
        toast('Pilih file logo terlebih dahulu', 'error');
        return;
    }
    
    // Validasi tipe file
    const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
    if (!allowedTypes.includes(selectedLogoFile.type)) {
        toast('Format file tidak didukung. Gunakan PNG, JPG, JPEG, atau WEBP', 'error');
        return;
    }
    
    // Validasi ukuran (max 2MB)
    if (selectedLogoFile.size > 2 * 1024 * 1024) {
        toast('Ukuran file maksimal 2MB', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'upload_logo');
    formData.append('logo', selectedLogoFile);
    
    const btn = document.getElementById('btn-upload-logo');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-small"></span> Mengupload...';
    
    fetch('api/settings.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (res.success) {
            toast('Logo berhasil diupload', 'success');
            document.getElementById('btn-upload-logo').style.display = 'none';
            // Refresh logo dengan timestamp untuk bypass cache
            document.getElementById('logo-img').src = '/assets/logo.png?t=' + new Date().getTime();
            // Refresh sidebar logo juga
            const sidebarLogo = document.querySelector('.sidebar-logo img');
            if (sidebarLogo) {
                sidebarLogo.src = '/assets/logo.png?t=' + new Date().getTime();
            }
            setTimeout(() => {
                toast('Logo telah diperbarui. Refresh halaman jika belum terlihat.', 'info');
            }, 1000);
        } else {
            toast(res.msg || 'Gagal upload logo', 'error');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        toast('Terjadi kesalahan saat upload', 'error');
    });
}

// ════════════════════════════════════════════════
// SIMPAN PENGATURAN GPS
// ════════════════════════════════════════════════
function simpanPengaturanGPS() {
    const radius = document.getElementById('radius-absensi').value;
    const minKehadiran = document.getElementById('min-kehadiran').value;
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-small"></span> Menyimpan...';
    
    fetch('api/settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=update_settings&radius_absensi=${radius}&min_kehadiran_persen=${minKehadiran}`
    })
    .then(res => res.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (res.success) {
            toast('Pengaturan GPS berhasil disimpan', 'success');
        } else {
            toast(res.msg || 'Gagal menyimpan', 'error');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        toast('Terjadi kesalahan', 'error');
    });
}

// ════════════════════════════════════════════════
// MODAL MAP
// ════════════════════════════════════════════════
function openModalMap() {
    openModal('modal-map');
    setTimeout(() => {
        initMap();
    }, 100);
}

function initMap() {
    const container = document.getElementById('map-container');
    if (!container) return;
    
    // Cek apakah map sudah diinisialisasi
    if (map) {
        map.remove();
    }
    
    // Inisialisasi map
    map = L.map('map-container').setView([currentLat, currentLng], 17);
    
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(map);
    
    // Marker
    marker = L.marker([currentLat, currentLng], { draggable: true }).addTo(map);
    
    // Update input ketika marker dipindah
    marker.on('dragend', function(e) {
        const pos = marker.getLatLng();
        document.getElementById('map-lat').value = pos.lat.toFixed(8);
        document.getElementById('map-lng').value = pos.lng.toFixed(8);
        currentLat = pos.lat;
        currentLng = pos.lng;
    });
    
    // Update input ketika map diklik
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        marker.setLatLng([lat, lng]);
        document.getElementById('map-lat').value = lat.toFixed(8);
        document.getElementById('map-lng').value = lng.toFixed(8);
        currentLat = lat;
        currentLng = lng;
    });
    
    // Set nilai awal
    document.getElementById('map-lat').value = currentLat.toFixed(8);
    document.getElementById('map-lng').value = currentLng.toFixed(8);
}

function getCurrentLocation() {
    if (!navigator.geolocation) {
        toast('Browser tidak mendukung geolokasi', 'error');
        return;
    }
    
    toast('Mendapatkan lokasi...', 'info');
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            if (map) {
                map.setView([lat, lng], 18);
                marker.setLatLng([lat, lng]);
                document.getElementById('map-lat').value = lat.toFixed(8);
                document.getElementById('map-lng').value = lng.toFixed(8);
                currentLat = lat;
                currentLng = lng;
                toast('Lokasi berhasil didapatkan', 'success');
            }
        },
        function(error) {
            let msg = 'Gagal mendapatkan lokasi';
            if (error.code === 1) msg = 'Akses lokasi ditolak';
            else if (error.code === 2) msg = 'Lokasi tidak tersedia';
            else if (error.code === 3) msg = 'Timeout mendapatkan lokasi';
            toast(msg, 'error');
        }
    );
}

function searchAddress() {
    closeModal('modal-map');
    setTimeout(() => {
        openModal('modal-search');
    }, 200);
}

function doSearchAddress() {
    const address = document.getElementById('search-address').value.trim();
    if (!address) {
        toast('Masukkan alamat yang ingin dicari', 'error');
        return;
    }
    
    closeModal('modal-search');
    toast('Mencari alamat...', 'info');
    
    // Menggunakan Nominatim OpenStreetMap untuk geocoding
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`)
        .then(res => res.json())
        .then(data => {
            if (data && data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lng = parseFloat(data[0].lon);
                
                if (map) {
                    map.setView([lat, lng], 17);
                    marker.setLatLng([lat, lng]);
                    document.getElementById('map-lat').value = lat.toFixed(8);
                    document.getElementById('map-lng').value = lng.toFixed(8);
                    currentLat = lat;
                    currentLng = lng;
                    toast('Lokasi ditemukan', 'success');
                }
                openModal('modal-map');
            } else {
                toast('Alamat tidak ditemukan', 'error');
                openModal('modal-map');
            }
        })
        .catch(err => {
            toast('Gagal mencari alamat', 'error');
            openModal('modal-map');
        });
}

function simpanKoordinatDariMap() {
    const lat = document.getElementById('map-lat').value;
    const lng = document.getElementById('map-lng').value;
    
    if (!lat || !lng) {
        toast('Latitude dan Longitude harus diisi', 'error');
        return;
    }
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-small"></span> Menyimpan...';
    
    fetch('api/settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=update_coordinates&latitude=${lat}&longitude=${lng}`
    })
    .then(res => res.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (res.success) {
            closeModal('modal-map');
            loadPengaturan();
            toast('Koordinat berhasil disimpan', 'success');
        } else {
            toast(res.msg || 'Gagal menyimpan koordinat', 'error');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        toast('Terjadi kesalahan', 'error');
    });
}

// ════════════════════════════════════════════════
// UNDUH LOG AKTIVITAS
// ════════════════════════════════════════════════
function downloadLog() {
    window.open('api/settings.php?action=export_log', '_blank');
    toast('Log aktivitas sedang diunduh...', 'info');
}

// ════════════════════════════════════════════════
// MODAL
// ════════════════════════════════════════════════
function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// ════════════════════════════════════════════════
// TOAST NOTIFICATION
// ════════════════════════════════════════════════
function toast(message, type = 'info', duration = 3000) {
    // Hapus toast lama jika ada
    const oldToast = document.querySelector('.toast-notification');
    if (oldToast) oldToast.remove();
    
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 10px 16px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 8px;
        font-size: 13px;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// ════════════════════════════════════════════════
// INIT
// ════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    loadPengaturan();
});

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});
</script>

<?php require_once 'footer.php'; ?>