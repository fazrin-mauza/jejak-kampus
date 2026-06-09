<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/navigasi.php';

// Get mahasiswa data
$user_id = $user['id'] ?? 0;
$mahasiswa_id = 0;
$kelas_id = 0;

if ($user_id) {
    $stmt = $conn->prepare("SELECT id, kelas_id FROM mahasiswa WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($mahasiswa_id, $kelas_id);
    $stmt->fetch();
    $stmt->close();
}

// Get settings
$settings = $conn->query("SELECT radius_absensi, latitude, longitude, min_kehadiran_persen FROM settings LIMIT 1")->fetch_assoc();
$min_kehadiran = $settings['min_kehadiran_persen'] ?? 75;
?>
<style>
    /* Banner untuk kondisi tidak ada sesi aktif */
.sesi-banner-inactive {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-radius: 12px;
  margin-bottom: 18px;
  background: var(--surface2);
  border: 1px solid var(--border);
}

.sesi-pulse.inactive {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: var(--gray);
  flex-shrink: 0;
}
</style>
<!-- ═══════════════════════════════════════════════
     ABSENSI & QR SCAN
═══════════════════════════════════════════════ -->
<div class="section active" id="sec-absensi">


  <!-- Sesi Aktif Banner -->
  <div id="sesi-banner-container"></div>

  <div class="absen-grid-2">
    <!-- Scan Area -->
    <div>
      <div class="scan-box" id="view-qr">
        <div style="font-size:11px;font-weight:800;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.05em;margin-bottom:16px;position:relative;z-index:1">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
            <line x1="9" y1="9" x2="15" y2="15"/>
            <line x1="15" y1="9" x2="9" y2="15"/>
          </svg>
          ARAHKAN KAMERA KE KODE QR
        </div>
        
        <div class="scan-frame-wrapper">
          <div class="scan-frame" id="scan-frame">
            <div class="scan-corner tl"></div>
            <div class="scan-corner tr"></div>
            <div class="scan-corner bl"></div>
            <div class="scan-corner br"></div>
            <div class="scan-line"></div>
            <div style="text-align:center;position:relative;z-index:1" id="scan-placeholder">
              <div style="font-size:44px;margin-bottom:8px;opacity:.6">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                  <circle cx="12" cy="12" r="3"/>
                  <line x1="9" y1="9" x2="15" y2="15"/>
                  <line x1="15" y1="9" x2="9" y2="15"/>
                </svg>
              </div>
              <div style="font-size:12px;color:rgba(255,255,255,.5)" id="qr-status-text">Kamera belum aktif</div>
            </div>
          </div>
        </div>

        <div style="position:relative;z-index:1">
          <div class="scan-status waiting" id="scan-status">Klik "Buka Kamera" untuk mulai memindai</div>
          <button class="btn-scan" style="margin:16px auto 0;display:flex" onclick="initScanner()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
              <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
              <circle cx="12" cy="13" r="4"/>
            </svg>
            <span id="camera-btn-text">Buka Kamera</span>
          </button>
          <input type="file" id="qr-file-input" accept="image/*" capture="environment" style="display:none" onchange="handleQRFile(this)">
          <div style="margin-top:8px;font-size:11px;color:rgba(255,255,255,.5);text-align:center">
            <a href="#" onclick="document.getElementById('qr-file-input').click();return false;" style="color:rgba(255,255,255,.5);text-decoration:underline;">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 3px;">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              Atau unggah gambar QR
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Info Sesi + Detail -->
    <div style="display:flex;flex-direction:column;gap:14px">
      <div class="card" id="info-sesi-card">
        <div class="card-title" style="margin-bottom:14px">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
          Informasi Sesi Aktif
        </div>
        <div id="info-sesi-content">
          <div style="text-align:center;padding:20px;color:var(--text3)">Tidak ada sesi aktif</div>
        </div>
      </div>

      <div class="card" id="status-hari-ini-card">
        <div class="card-title" style="margin-bottom:14px">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
            <path d="M22 12h-4l-3 9-4-18-3 9H2"/>
          </svg>
          Status Absensi Hari Ini
        </div>
        <div id="status-hari-ini-content">
          <div style="text-align:center;padding:20px;color:var(--text3)">Memuat data...</div>
        </div>
      </div>

      <div class="card" style="border-color:var(--border-s);background:linear-gradient(135deg,#FFF8F0,#FFF3E4)">
        <div style="font-size:13px;font-weight:700;color:var(--text);margin-bottom:6px">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
          </svg>
          Tidak dapat hadir?
        </div>
        <div style="font-size:12px;color:var(--text2);margin-bottom:12px">Ajukan izin atau sakit sebelum sesi berakhir agar tidak tercatat sebagai ketidakhadiran tanpa keterangan.</div>
        <a href="izin.php" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
          </svg>
          Ajukan Izin / Sakit
        </a>
      </div>
    </div>
  </div>

  <!-- Riwayat Absensi -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/>
          <line x1="16" y1="17" x2="8" y2="17"/>
          <polyline points="10 9 9 9 8 9"/>
        </svg>
        Riwayat Absensi
      </div>
      <a href="rekap.php" class="btn btn-secondary btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
          <line x1="18" y1="20" x2="18" y2="10"/>
          <line x1="12" y1="20" x2="12" y2="4"/>
          <line x1="6" y1="20" x2="6" y2="14"/>
        </svg>
        Rekap Lengkap
      </a>
    </div>
    <div class="tbl-wrap">
      <table class="tabel">
        <thead>
          <tr><th>Tanggal</th><th>Mata Kuliah</th><th>Pertemuan</th><th>Status</th><th>Waktu Absen</th><th>Metode</th></tr>
        </thead>
        <tbody id="riwayat-body">
          <tr><td colspan="6" style="text-align:center;padding:40px">Memuat data...</td></tr>
        </tbody>
      </table>
    </div>
    <div class="pagination" id="riwayat-pagination" style="display:none">
      <span class="page-info" id="page-info"></span>
      <div id="page-buttons"></div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

<script>
// ════════════════════════════════════════════════
// VARIABEL GLOBAL
// ════════════════════════════════════════════════
let currentSesiAktif = null;
let scannerActive = false;
let videoStream = null;
let videoElement = null;
let canvasElement = null;
let currentPage = 1;
let mahasiswaLocation = null;
let scanInterval = null;
let campusLocation = {
  lat: <?= $settings['latitude'] ?? -7.31628330 ?>,
  lng: <?= $settings['longitude'] ?? 112.72277730 ?>,
  radius: <?= $settings['radius_absensi'] ?? 200 ?>
};

// ════════════════════════════════════════════════
// INISIALISASI
// ════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
  loadSesiAktif();
  loadStatusHariIni();
  loadRiwayatAbsensi();
  getLocation();
});

// ════════════════════════════════════════════════
// FUNGSI LOKASI
// ════════════════════════════════════════════════
function getLocation() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      position => {
        mahasiswaLocation = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        };
      },
      error => {
        console.warn('Gagal mendapatkan lokasi:', error.message);
        showNotification('GPS tidak aktif. Pastikan lokasi diaktifkan.', 'error');
      }
    );
  }
}

// ════════════════════════════════════════════════
// LOAD SESI AKTIF
// ════════════════════════════════════════════════
function loadSesiAktif() {
  fetch('api/absensi.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_sesi_aktif'
  })
  .then(res => res.json())
  .then(response => {
    if (response.success && response.data) {
      currentSesiAktif = response.data;
      renderSesiBanner(response.data);
      renderInfoSesi(response.data);
      loadStatusAbsensiSesi(response.data.sesi_id);
    } else {
      currentSesiAktif = null;
      renderSesiBanner(null);
      renderInfoSesi(null);
    }
  });
}

// ════════════════════════════════════════════════
// PEMINDAI QR CODE
// ════════════════════════════════════════════════
function initScanner() {
  if (!currentSesiAktif) {
    showNotification('Tidak ada sesi aktif', 'error');
    return;
  }
  
  var scanFrame = document.getElementById('scan-frame');
  var statusEl = document.getElementById('scan-status');
  var btnText = document.getElementById('camera-btn-text');
  
  if (scannerActive) {
    stopScanner();
    return;
  }
  
  if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
    statusEl.className = 'scan-status waiting';
    statusEl.textContent = '⏳ Meminta akses kamera...';
    
    navigator.mediaDevices.getUserMedia({ 
      video: { 
        facingMode: 'environment',
        width: { ideal: 640 },
        height: { ideal: 480 }
      } 
    })
    .then(function(stream) {
      videoStream = stream;
      scannerActive = true;
      if (btnText) btnText.textContent = 'Tutup Kamera';
      
      scanFrame.innerHTML = '';
      
      if (!videoElement) {
        videoElement = document.createElement('video');
        videoElement.setAttribute('playsinline', '');
        videoElement.setAttribute('autoplay', '');
        videoElement.setAttribute('muted', '');
      }
      
      videoElement.style.width = '100%';
      videoElement.style.height = '100%';
      videoElement.style.objectFit = 'cover';
      videoElement.style.position = 'absolute';
      videoElement.style.inset = '0';
      videoElement.style.borderRadius = '12px';
      videoElement.style.zIndex = '1';
      videoElement.srcObject = stream;
      
      videoElement.onloadedmetadata = function() {
        videoElement.play().catch(function(e) { console.warn('Play error:', e); });
      };
      
      scanFrame.appendChild(videoElement);
      
      ['tl', 'tr', 'bl', 'br'].forEach(function(pos) {
        var corner = document.createElement('div');
        corner.className = 'scan-corner ' + pos;
        scanFrame.appendChild(corner);
      });
      
      var scanLine = document.createElement('div');
      scanLine.className = 'scan-line';
      scanFrame.appendChild(scanLine);
      
      var placeholder = document.createElement('div');
      placeholder.id = 'scan-placeholder';
      placeholder.style.cssText = 'text-align:center;position:relative;z-index:2';
      placeholder.innerHTML = '<div style="font-size:12px;color:rgba(255,255,255,.5)" id="qr-status-text">Kamera aktif - memindai...</div>';
      scanFrame.appendChild(placeholder);
      
      statusEl.className = 'scan-status waiting';
      statusEl.textContent = '📷 Arahkan kamera ke kode QR...';
      
      if (!canvasElement) {
        canvasElement = document.createElement('canvas');
      }
      
      if (scanInterval) clearInterval(scanInterval);
      scanInterval = setInterval(scanQRCode, 300);
      
      showNotification('Kamera aktif. Arahkan ke kode QR', 'success');
    })
    .catch(function(error) {
      console.error('Camera error:', error);
      scannerActive = false;
      
      statusEl.className = 'scan-status error';
      statusEl.textContent = '❌ Gagal mengakses kamera. Pastikan izin diberikan.';
      
      var qrStatus = document.getElementById('qr-status-text');
      if (qrStatus) qrStatus.textContent = 'Kamera tidak tersedia';
      
      showNotification('Gunakan opsi unggah gambar sebagai alternatif', 'info');
      showFileUploadOption();
    });
  } else {
    statusEl.className = 'scan-status error';
    statusEl.textContent = '❌ Browser tidak mendukung kamera';
    showFileUploadOption();
  }
}

function stopScanner() {
  scannerActive = false;
  
  if (scanInterval) {
    clearInterval(scanInterval);
    scanInterval = null;
  }
  
  if (videoStream) {
    videoStream.getTracks().forEach(function(track) { track.stop(); });
    videoStream = null;
  }
  
  if (videoElement) {
    videoElement.pause();
    videoElement.srcObject = null;
  }
  
  var btnText = document.getElementById('camera-btn-text');
  if (btnText) btnText.textContent = 'Buka Kamera';
  
  var qrStatus = document.getElementById('qr-status-text');
  if (qrStatus) qrStatus.textContent = 'Kamera tidak aktif';
  
  var scanFrame = document.getElementById('scan-frame');
  if (scanFrame) {
    scanFrame.innerHTML = `
      <div class="scan-corner tl"></div>
      <div class="scan-corner tr"></div>
      <div class="scan-corner bl"></div>
      <div class="scan-corner br"></div>
      <div class="scan-line"></div>
      <div style="text-align:center;position:relative;z-index:1">
        <div style="font-size:44px;margin-bottom:8px;opacity:.6">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
            <circle cx="12" cy="12" r="3"/>
            <line x1="9" y1="9" x2="15" y2="15"/>
            <line x1="15" y1="9" x2="9" y2="15"/>
          </svg>
        </div>
        <div style="font-size:12px;color:rgba(255,255,255,.5)" id="qr-status-text">Kamera tidak aktif</div>
      </div>
    `;
  }
}

function scanQRCode() {
  if (!scannerActive) return;
  if (!videoElement || !canvasElement) return;
  if (videoElement.readyState < 2) return;
  
  var ctx = canvasElement.getContext('2d');
  
  if (videoElement.videoWidth && videoElement.videoHeight) {
    canvasElement.width = videoElement.videoWidth;
    canvasElement.height = videoElement.videoHeight;
  } else {
    return;
  }
  
  try {
    ctx.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);
    var imageData = ctx.getImageData(0, 0, canvasElement.width, canvasElement.height);
    var code = jsQR(imageData.data, canvasElement.width, canvasElement.height, {
      inversionAttempts: 'dontInvert'
    });
    
    if (code) {
      var statusEl = document.getElementById('scan-status');
      if (statusEl) {
        statusEl.className = 'scan-status success';
        statusEl.textContent = '✅ Kode QR terdeteksi!';
      }
      
      stopScanner();
      processQRData(code.data);
    }
  } catch (e) {
    console.warn('Scan error:', e);
  }
}

function showFileUploadOption() {
  var scanFrame = document.getElementById('scan-frame');
  if (!scanFrame) return;
  
  scanFrame.innerHTML = `
    <div class="scan-corner tl"></div>
    <div class="scan-corner tr"></div>
    <div class="scan-corner bl"></div>
    <div class="scan-corner br"></div>
    <div style="text-align:center;position:relative;z-index:1;padding:20px">
      <div style="font-size:36px;margin-bottom:12px">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
          <circle cx="12" cy="12" r="3"/>
        </svg>
      </div>
      <div style="font-size:12px;color:rgba(255,255,255,.7);margin-bottom:16px">Unggah gambar kode QR</div>
      <button class="btn btn-secondary btn-sm" onclick="document.getElementById('qr-file-input').click()">
        Pilih Gambar
      </button>
    </div>
  `;
  
  var qrStatus = document.getElementById('qr-status-text');
  if (qrStatus) qrStatus.textContent = 'Mode unggah gambar';
}

function handleQRFile(input) {
  var file = input.files[0];
  if (!file) return;
  
  var statusEl = document.getElementById('scan-status');
  statusEl.className = 'scan-status waiting';
  statusEl.textContent = '⏳ Memproses kode QR...';
  
  var reader = new FileReader();
  reader.onload = function(e) {
    var img = new Image();
    img.onload = function() {
      var canvas = document.createElement('canvas');
      canvas.width = img.width;
      canvas.height = img.height;
      var ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0);
      
      var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
      var code = jsQR(imageData.data, canvas.width, canvas.height);
      
      if (code) {
        statusEl.className = 'scan-status success';
        statusEl.textContent = '✅ Kode QR terdeteksi!';
        processQRData(code.data);
      } else {
        statusEl.className = 'scan-status error';
        statusEl.textContent = '❌ Kode QR tidak terbaca. Silakan coba lagi.';
      }
    };
    img.src = e.target.result;
  };
  reader.readAsDataURL(file);
  
  input.value = '';
}

function processQRData(qrData) {
  try {
    var data = JSON.parse(qrData);
    var sesiId = data.sesi_id;
    var token = data.token;
    
    if (!sesiId) {
      throw new Error('Kode QR tidak valid');
    }
    
    submitAbsensi(sesiId, token, 'qr');
  } catch (e) {
    document.getElementById('scan-status').className = 'scan-status error';
    document.getElementById('scan-status').textContent = '❌ Format kode QR tidak valid';
  }
}

function submitAbsensi(sesiId, token, metode) {
  if (!mahasiswaLocation) {
    showNotification('Mohon aktifkan GPS untuk melakukan absensi', 'error');
    getLocation();
    return;
  }
  
  var distance = calculateDistance(
    mahasiswaLocation.lat, mahasiswaLocation.lng,
    campusLocation.lat, campusLocation.lng
  );
  
  if (distance > campusLocation.radius) {
    showNotification('Anda berada di luar radius kampus (' + Math.round(distance) + 'm dari batas ' + campusLocation.radius + 'm)', 'error');
    document.getElementById('scan-status').className = 'scan-status error';
    document.getElementById('scan-status').textContent = 'Di luar radius kampus (' + Math.round(distance) + 'm)';
    return;
  }
  
  fetch('api/absensi.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=submit_absensi&sesi_id=' + sesiId + '&token=' + encodeURIComponent(token) + '&metode=' + metode + '&lat=' + mahasiswaLocation.lat + '&lng=' + mahasiswaLocation.lng
  })
  .then(function(res) { return res.json(); })
  .then(function(response) {
    if (response.success) {
      document.getElementById('scan-status').className = 'scan-status success';
      document.getElementById('scan-status').textContent = 'Absensi berhasil!';
      
      document.getElementById('absen-time').textContent = new Date().toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'});
      openModal('modal-absen-ok');
      
      loadSesiAktif();
      loadStatusHariIni();
      loadRiwayatAbsensi();
    } else {
      document.getElementById('scan-status').className = 'scan-status error';
      document.getElementById('scan-status').textContent = '❌ ' + (response.msg || 'Gagal melakukan absensi');
    }
  });
}

function calculateDistance(lat1, lon1, lat2, lon2) {
  var R = 6371e3;
  var φ1 = lat1 * Math.PI/180;
  var φ2 = lat2 * Math.PI/180;
  var Δφ = (lat2-lat1) * Math.PI/180;
  var Δλ = (lon2-lon1) * Math.PI/180;
  
  var a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
            Math.cos(φ1) * Math.cos(φ2) *
            Math.sin(Δλ/2) * Math.sin(Δλ/2);
  var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  
  return R * c;
}

window.addEventListener('beforeunload', function() {
  if (scannerActive) {
    stopScanner();
  }
});

// ════════════════════════════════════════════════
// FUNGSI RENDER
// ════════════════════════════════════════════════
function renderSesiBanner(data) {
  var container = document.getElementById('sesi-banner-container');
  
  if (!container) return;
  
  if (!data) {
    // Perbaikan: tidak pakai border-left yang tidak dikenali CSS
    container.innerHTML = `
      <div class="sesi-banner-inactive">
        <div class="sesi-pulse inactive"></div>
        <div class="sesi-info">
          <div class="sesi-title">Tidak Ada Sesi Aktif</div>
          <div class="sesi-meta">Belum ada sesi absensi yang sedang berlangsung</div>
        </div>
        <span class="pill pill-gray">Nonaktif</span>
      </div>
    `;
    return;
  }
  
  container.innerHTML = `
    <div class="sesi-banner">
      <div class="sesi-pulse"></div>
      <div class="sesi-info">
        <div class="sesi-title">Sesi Aktif — ${escapeHtml(data.nama_mk)}</div>
        <div class="sesi-meta">
          Pertemuan ke-${data.pertemuan_ke} · ${escapeHtml(data.nama_dosen)} · ${escapeHtml(data.nama_ruangan || 'Ruangan')} · ${data.jam_mulai?.substring(0,5)} – ${data.jam_selesai?.substring(0,5)}
        </div>
      </div>
      <span class="pill pill-green">● Aktif</span>
    </div>
  `;
}

// Tambahkan fungsi escape HTML untuk keamanan
function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/[&<>]/g, function(m) {
    if (m === '&') return '&amp;';
    if (m === '<') return '&lt;';
    if (m === '>') return '&gt;';
    return m;
  });
}

function renderInfoSesi(data) {
  var container = document.getElementById('info-sesi-content');
  
  if (!data) {
    container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text3)">Tidak ada sesi aktif</div>';
    return;
  }
  
  container.innerHTML = `
    <div class="detail-row"><div class="detail-label">Mata Kuliah</div><div class="detail-val">${data.nama_mk}</div></div>
    <div class="detail-row"><div class="detail-label">Dosen Pengampu</div><div class="detail-val">${data.nama_dosen}</div></div>
    <div class="detail-row"><div class="detail-label">Ruangan</div><div class="detail-val">${data.nama_ruangan || '-'} (${data.kode_ruangan || '-'})</div></div>
    <div class="detail-row"><div class="detail-label">Pertemuan</div><div class="detail-val">Ke-${data.pertemuan_ke}</div></div>
    <div class="detail-row"><div class="detail-label">Waktu Pelaksanaan</div><div class="detail-val">${data.jam_mulai?.substring(0,5)} – ${data.jam_selesai?.substring(0,5)} WIB</div></div>
    <div class="detail-row"><div class="detail-label">Status Absensi</div><div class="detail-val" id="status-absensi-sesi">Memuat...</div></div>
  `;
}

function loadStatusAbsensiSesi(sesiId) {
  fetch('api/absensi.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_status_sesi&sesi_id=' + sesiId
  })
  .then(function(res) { return res.json(); })
  .then(function(response) {
    var statusEl = document.getElementById('status-absensi-sesi');
    if (statusEl) {
      if (response.success && response.data) {
        var status = response.data.status;
        var statusMap = {
          'hadir': '<span class="pill pill-green">Hadir</span>',
          'telat': '<span class="pill pill-ora">Telat</span>',
          'izin': '<span class="pill pill-yellow">Izin</span>',
          'sakit': '<span class="pill pill-ora">Sakit</span>',
          'alpha': '<span class="pill pill-red">Tidak Hadir (Alpha)</span>',
          'belum': '<span class="pill pill-gray">Belum Absen</span>'
        };
        statusEl.innerHTML = statusMap[status] || statusMap['belum'];
      } else {
        statusEl.innerHTML = '<span class="pill pill-gray">Belum Absen</span>';
      }
    }
  });
}

function loadStatusHariIni() {
  fetch('api/absensi.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_status_hari_ini'
  })
  .then(function(res) { return res.json(); })
  .then(function(response) {
    var container = document.getElementById('status-hari-ini-content');
    
    if (!response.success || !response.data || response.data.length === 0) {
      container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text3)">Tidak ada jadwal hari ini</div>';
      return;
    }
    
    var html = '';
    response.data.forEach(function(j) {
      var statusMap = {
        'hadir': '<span class="pill pill-green">Hadir</span>',
        'telat': '<span class="pill pill-ora">Telat</span>',
        'izin': '<span class="pill pill-yellow">Izin</span>',
        'sakit': '<span class="pill pill-ora">Sakit</span>',
        'alpha': '<span class="pill pill-red">Tidak Hadir</span>',
        'belum': '<span class="pill pill-gray">Belum</span>'
      };
      
      html += '<div class="detail-row"><div class="detail-label">' + (j.jam_mulai?.substring(0,5) || '') + ' - ' + j.nama_mk + '</div><div class="detail-val">' + (statusMap[j.status] || statusMap['belum']) + '</div></div>';
    });
    
    container.innerHTML = html;
  });
}

function loadRiwayatAbsensi(page) {
  page = page || 1;
  currentPage = page;
  
  fetch('api/absensi.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_riwayat&page=' + page
  })
  .then(function(res) { return res.json(); })
  .then(function(response) {
    if (response.success) {
      renderRiwayatTable(response.data);
      renderPagination(response);
    }
  });
}

function renderRiwayatTable(data) {
  var tbody = document.getElementById('riwayat-body');
  
  if (data.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px">Belum ada riwayat absensi</td></tr>';
    return;
  }
  
  var html = '';
  data.forEach(function(r) {
    var statusMap = {
      'hadir': '<span class="pill pill-green">Hadir</span>',
      'telat': '<span class="pill pill-ora">Telat</span>',
      'izin': '<span class="pill pill-yellow">Izin</span>',
      'sakit': '<span class="pill pill-ora">Sakit</span>',
      'alpha': '<span class="pill pill-red">Tidak Hadir</span>'
    };
    
    var methodMap = {
      'qr': '<span class="pill pill-blue">QR Code</span>',
      'token': '<span class="pill pill-ora">Token</span>',
      'manual': '<span class="pill pill-gray">Manual</span>',
      'izin': '<span class="pill pill-yellow">Izin</span>'
    };
    
    var waktu = r.waktu_absen ? new Date(r.waktu_absen).toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'}) : '—';
    
    html += '<tr><td>' + (r.tanggal || '—') + '</td><td style="font-weight:600">' + r.nama_mk + '</td><td>P-' + r.pertemuan_ke + '</td><td>' + (statusMap[r.status] || '—') + '</td><td>' + waktu + '</td><td>' + (methodMap[r.metode] || '—') + '</td></tr>';
  });
  
  tbody.innerHTML = html;
}

function renderPagination(response) {
  if (response.pages <= 1) {
    document.getElementById('riwayat-pagination').style.display = 'none';
    return;
  }
  
  document.getElementById('riwayat-pagination').style.display = 'flex';
  var start = (response.page - 1) * response.limit + 1;
  var end = Math.min(response.page * response.limit, response.total);
  
  document.getElementById('page-info').textContent = start + '–' + end + ' dari ' + response.total + ' data';
  
  var pageButtons = document.getElementById('page-buttons');
  var html = '';
  
  if (response.page > 1) {
    html += '<button class="page-btn" onclick="loadRiwayatAbsensi(' + (response.page - 1) + ')">‹ Sebelumnya</button>';
  }
  
  for (var i = Math.max(1, response.page - 2); i <= Math.min(response.pages, response.page + 2); i++) {
    html += '<button class="page-btn ' + (i === response.page ? 'active' : '') + '" onclick="loadRiwayatAbsensi(' + i + ')">' + i + '</button>';
  }
  
  if (response.page < response.pages) {
    html += '<button class="page-btn" onclick="loadRiwayatAbsensi(' + (response.page + 1) + ')">Selanjutnya ›</button>';
  }
  
  pageButtons.innerHTML = html;
}

// ════════════════════════════════════════════════
// NOTIFIKASI CUSTOM
// ════════════════════════════════════════════════
function showNotification(message, type = 'info') {
  const colors = {
    success: '#059669',
    error: '#dc2626',
    info: '#2563eb',
    warning: '#d97706'
  };
  
  const bgColor = colors[type] || colors.info;
  
  const existing = document.querySelector('.custom-notification');
  if (existing) existing.remove();
  
  const el = document.createElement('div');
  el.className = 'custom-notification';
  el.style.cssText = `position:fixed;top:20px;right:20px;padding:12px 20px;background:${bgColor};color:#fff;border-radius:10px;z-index:10000;box-shadow:0 8px 24px rgba(0,0,0,0.2);font-size:13px;font-weight:600;max-width:350px;word-wrap:break-word;animation:slideInRight 0.3s ease-out`;
  
  const icons = {
    success: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> ',
    error: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> ',
    info: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg> '
  };
  
  el.innerHTML = (icons[type] || icons.info) + message;
  document.body.appendChild(el);
  
  setTimeout(() => {
    el.style.animation = 'slideOutRight 0.3s ease-in';
    setTimeout(() => el.remove(), 300);
  }, 3500);
}

function toast(m, t) {
  showNotification(m, t);
}

// ════════════════════════════════════════════════
// MODAL
// ════════════════════════════════════════════════
function openModal(id) {
  document.getElementById(id).classList.add('open');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

// Style tambahan untuk animasi notifikasi
const styleEl = document.createElement('style');
styleEl.textContent = `
  @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
  @keyframes slideOutRight { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
`;
document.head.appendChild(styleEl);
</script>

<style>
/* ══════════ GRID RESPONSIF ══════════ */
.absen-grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
  margin-bottom: 22px;
  align-items: start;
}

@media (max-width: 768px) {
  .absen-grid-2 {
    grid-template-columns: 1fr;
  }
}

/* ══════════ KOTAK SCAN ══════════ */
.scan-box {
  background: linear-gradient(145deg, #1a1a2e, #16213e);
  border-radius: 16px;
  padding: 20px;
}

/* ══════════ FRAME SCAN ══════════ */
.scan-frame-wrapper {
  width: 100%;
  max-width: 360px;
  margin: 0 auto 12px;
}

.scan-frame {
  position: relative;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 16px;
  overflow: hidden;
  background: rgba(0,0,0,.4);
}

@media (max-width: 480px) {
  .scan-frame-wrapper {
    max-width: 100%;
  }
  .scan-box {
    padding: 14px;
  }
}

/* ══════════ SUDUT SCAN ══════════ */
.scan-corner {
  position: absolute;
  width: 30px;
  height: 30px;
  border-color: #f97316;
  border-style: solid;
  z-index: 2;
}
.scan-corner.tl { top: 16px; left: 16px; border-width: 3px 0 0 3px; border-radius: 6px 0 0 0; }
.scan-corner.tr { top: 16px; right: 16px; border-width: 3px 3px 0 0; border-radius: 0 6px 0 0; }
.scan-corner.bl { bottom: 16px; left: 16px; border-width: 0 0 3px 3px; border-radius: 0 0 0 6px; }
.scan-corner.br { bottom: 16px; right: 16px; border-width: 0 3px 3px 0; border-radius: 0 0 6px 0; }

/* ══════════ GARIS SCAN ══════════ */
.scan-line {
  position: absolute;
  left: 10%;
  width: 80%;
  height: 2px;
  background: linear-gradient(90deg, transparent, #f97316, transparent);
  animation: scanAnim 2s ease-in-out infinite;
  z-index: 2;
}

@keyframes scanAnim {
  0% { top: 16px; }
  50% { top: calc(100% - 20px); }
  100% { top: 16px; }
}

/* ══════════ TOMBOL SCAN ══════════ */
.btn-scan {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 28px;
  background: #f97316;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: .2s;
  width: 100%;
  justify-content: center;
  max-width: 360px;
}
.btn-scan:hover { background: #ea580c; }

/* ══════════ STATUS SCAN ══════════ */
.scan-status {
  font-size: 12px;
  font-weight: 700;
  padding: 8px 12px;
  border-radius: 8px;
  margin-bottom: 12px;
}
.scan-status.waiting { background: rgba(255,255,255,.08); color: rgba(255,255,255,.7); }
.scan-status.success { background: rgba(16,185,129,.15); color: #10b981; }
.scan-status.error { background: rgba(239,68,68,.15); color: #ef4444; }

/* ══════════ BANNER SESI ══════════ */
.sesi-banner {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-radius: 12px;
  margin-bottom: 18px;
  background: linear-gradient(135deg, #FFF7ED, #FFEDD5);
  border: 2px solid #f97316;
}

.sesi-pulse {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #10b981;
  animation: pulse 2s infinite;
  flex-shrink: 0;
}

@keyframes pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(16,185,129,.4); }
  50% { box-shadow: 0 0 0 10px rgba(16,185,129,0); }
}

.sesi-info { flex: 1; min-width: 0; }
.sesi-title { font-weight: 700; font-size: 14px; }
.sesi-meta { font-size: 12px; color: var(--text2); }

@media (max-width: 480px) {
  .sesi-banner { flex-wrap: wrap; }
}

/* ══════════ TABEL RESPONSIF ══════════ */
@media (max-width: 640px) {
  .tabel { font-size: 11px; }
  .tabel th, .tabel td { padding: 8px 4px; }
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>