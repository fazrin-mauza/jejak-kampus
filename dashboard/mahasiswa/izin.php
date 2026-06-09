<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/navigasi.php';
?>
<style>
    /* ══════════ RADIO BUTTON IZIN/SAKIT ══════════ */
.radio-group {
  display: flex;
  gap: 10px;
}

.radio-card {
  flex: 1;
  position: relative;
}

.radio-card input[type="radio"] {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.radio-card-label {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  border: 2px solid var(--border);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  background: var(--surface);
  font-size: 14px;
  font-weight: 600;
  color: var(--text2);
  user-select: none;
}

.radio-card-label:hover {
  border-color: var(--ora2);
  background: var(--orange-bg, #FFF7ED);
}

.radio-card input[type="radio"]:checked + .radio-card-label {
  border-color: var(--ora3);
  background: #FFF7ED;
  color: var(--ora4);
  box-shadow: 0 0 0 3px rgba(249,115,22,0.1);
}

.radio-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.radio-icon.sakit {
  background: #FEE2E2;
}

.radio-icon.izin {
  background: #DBEAFE;
}

.radio-card input[type="radio"]:checked + .radio-card-label .radio-icon.sakit {
  background: #FECACA;
  transform: scale(1.05);
}

.radio-card input[type="radio"]:checked + .radio-card-label .radio-icon.izin {
  background: #BFDBFE;
  transform: scale(1.05);
}

.radio-check {
  margin-left: auto;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.radio-check::after {
  content: '';
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: transparent;
  transition: all 0.2s ease;
}

.radio-card input[type="radio"]:checked + .radio-card-label .radio-check {
  border-color: var(--ora3);
  background: var(--ora3);
}

.radio-card input[type="radio"]:checked + .radio-card-label .radio-check::after {
  background: white;
}

/* SVG Icon dalam radio */
.radio-icon svg {
  width: 22px;
  height: 22px;
}

.file-drop.dragover { background: var(--ora2); border-color: var(--ora4); }

/* Custom Notif Animation */
@keyframes slideInRight {
  from { transform: translateX(100%); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}
@keyframes slideOutRight {
  from { transform: translateX(0); opacity: 1; }
  to { transform: translateX(100%); opacity: 0; }
}

/* ══════════ PERBAIKAN RESPONSIF UNTUK HP ══════════ */
@media (max-width: 768px) {
    /* Container utama */
    .content {
        padding: 12px !important;
        overflow-x: hidden !important;
    }
    
    /* Grid 2 kolom jadi 1 kolom dan center */
    .grid-2 {
        grid-template-columns: 1fr !important;
        gap: 16px !important;
    }
    
    /* Form card */
    .card {
        width: 100% !important;
        margin: 0 !important;
        box-sizing: border-box !important;
    }
    
    /* Form elements */
    .form-group {
        width: 100% !important;
    }
    
    input, select, textarea, .file-drop {
        width: 100% !important;
        box-sizing: border-box !important;
    }
    
    /* Radio group di HP */
    .radio-group {
        flex-direction: column !important;
        gap: 10px !important;
    }
    
    .radio-card {
        width: 100% !important;
    }
    
    .radio-card-label {
        width: 100% !important;
        box-sizing: border-box !important;
    }
    
    /* Tombol submit */
    #btn-submit {
        width: 100% !important;
    }
    
    /* Tab navigasi */
    .tab-nav {
        flex-wrap: wrap !important;
        gap: 8px !important;
    }
    
    .tab-btn {
        flex: 1 !important;
        text-align: center !important;
        justify-content: center !important;
        font-size: 12px !important;
        padding: 10px 8px !important;
    }
    
    /* Tabel scroll horizontal */
    .tbl-wrap {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
    }
    
    .tabel {
        min-width: 600px !important;
    }
    
    /* Modal di HP */
    .modal {
        width: 90% !important;
        margin: 20px auto !important;
    }
}
</style>

<!-- ═══════════════════════════════════════════════
     PENGAJUAN IZIN/SAKIT - Dynamic
═══════════════════════════════════════════════ -->
<div id="app-izin">


  <!-- Tab Nav -->
  <div id="tab-group" class="tab-nav">
    <button class="tab-btn active" data-tab="tab-baru" onclick="switchTab('tab-baru')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
        <path d="M12 5v14M5 12h14"/>
      </svg>
      Pengajuan Baru
    </button>
    <button class="tab-btn" data-tab="tab-riwayat" onclick="switchTab('tab-riwayat')">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
        <line x1="16" y1="13" x2="8" y2="13"/>
        <line x1="16" y1="17" x2="8" y2="17"/>
        <polyline points="10 9 9 9 8 9"/>
      </svg>
      Riwayat Pengajuan <span class="tab-badge" id="badge-pending">0</span>
    </button>
  </div>

  <!-- TAB: PENGAJUAN BARU -->
  <div class="tab-content active" id="tab-baru">
    <div class="grid-2" style="align-items:start">

      <!-- Form -->
      <div class="card">
        <div class="card-title" style="margin-bottom:16px">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
            <path d="M12 5v14M5 12h14"/>
          </svg>
          Formulir Pengajuan
        </div>

        <form id="form-izin" enctype="multipart/form-data">
          <div class="form-group">
            <label>Jenis Ketidakhadiran <span style="color:var(--red)">*</span></label>
            <div class="radio-group">
              <label class="radio-card">
                <input type="radio" name="jenis" value="sakit" required onchange="updateJenisLabel('sakit')">
                <span class="radio-card-label">
                  <span class="radio-icon sakit">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                      <circle cx="12" cy="12" r="10"/>
                      <path d="M12 8v4M12 16h.01"/>
                    </svg>
                  </span>
                  <span>Sakit</span>
                  <span class="radio-check"></span>
                </span>
              </label>
              <label class="radio-card">
                <input type="radio" name="jenis" value="izin" required onchange="updateJenisLabel('izin')">
                <span class="radio-card-label">
                  <span class="radio-icon izin">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                      <polyline points="14 2 14 8 20 8"/>
                      <line x1="16" y1="13" x2="8" y2="13"/>
                      <line x1="16" y1="17" x2="8" y2="17"/>
                      <polyline points="10 9 9 9 8 9"/>
                    </svg>
                  </span>
                  <span>Izin</span>
                  <span class="radio-check"></span>
                </span>
              </label>
            </div>
          </div>

          <div class="form-group">
            <label>Mata Kuliah <span style="color:var(--red)">*</span></label>
            <select id="sel-mk" required onchange="loadPertemuanOptions()">
              <option value="">— Pilih Mata Kuliah —</option>
            </select>
            <small id="mk-info" style="color:var(--text3)"></small>
          </div>

          <div class="form-group">
            <label>Pertemuan ke- <span style="color:var(--red)">*</span></label>
            <select id="sel-pertemuan" required disabled>
              <option value="">— Pilih Mata Kuliah dahulu —</option>
            </select>
            <small id="pertemuan-info" style="color:var(--text3)"></small>
          </div>

          <div class="form-group">
            <label>Tanggal Izin</label>
            <input type="date" id="tgl-izin" value="<?= date('Y-m-d') ?>">
          </div>

          <div class="form-group">
            <label>Keterangan</label>
            <textarea id="ket-izin" placeholder="Jelaskan alasan ketidakhadiran secara singkat..." rows="3"></textarea>
          </div>

          <div class="form-group">
            <label id="bukti-label">Upload Bukti (Surat Dokter / Surat Izin)</label>
            <div class="file-drop" id="file-drop" onclick="document.getElementById('file-surat').click()">
              <div class="file-drop-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                  <polyline points="13 2 13 9 20 9"/>
                </svg>
              </div>
              <div class="file-drop-text" id="file-name">Klik untuk pilih file atau drag & drop</div>
              <div class="file-drop-sub">Format: PDF, JPG, PNG · Maks. 5 MB</div>
            </div>
            <input type="file" id="file-surat" name="file_surat" accept=".pdf,.jpg,.jpeg,.png" style="display:none" onchange="handleFileSelect(this)">
          </div>

          <button type="button" class="btn btn-primary" style="width:100%;justify-content:center;padding:13px" id="btn-submit" onclick="bukaKonfirmasi()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
              <line x1="22" y1="2" x2="11" y2="13"/>
              <polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
            Ajukan Sekarang
          </button>
        </form>
      </div>

      <!-- Sidebar: Panduan + Status + Peringatan -->
      <div style="display:flex;flex-direction:column;gap:14px">
        <div class="card">
          <div class="card-title" style="margin-bottom:12px">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="16" x2="12" y2="12"/>
              <line x1="12" y1="8" x2="12.01" y2="8"/>
            </svg>
            Panduan Pengajuan
          </div>
          <div style="display:flex;gap:10px;margin-bottom:10px"><div style="width:24px;height:24px;border-radius:50%;background:var(--ora2);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0">1</div><div style="font-size:13px">Ajukan H-24 sebelum jadwal perkuliahan</div></div>
          <div style="display:flex;gap:10px;margin-bottom:10px"><div style="width:24px;height:24px;border-radius:50%;background:var(--ora2);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0">2</div><div style="font-size:13px">Sertakan surat dokter atau keterangan resmi</div></div>
          <div style="display:flex;gap:10px"><div style="width:24px;height:24px;border-radius:50%;background:var(--ora2);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0">3</div><div style="font-size:13px">Keputusan berada pada dosen pengampu</div></div>
        </div>

        <!-- Pending -->
        <div class="card" id="pending-card" style="display:none">
          <div class="card-title" style="margin-bottom:12px">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
              <circle cx="12" cy="12" r="10"/>
              <polyline points="12 6 12 12 16 14"/>
            </svg>
            Pengajuan Menunggu
          </div>
          <div id="pending-list"></div>
        </div>

        <!-- Peringatan Kehadiran -->
        <div class="card" id="warning-card" style="display:none;border-color:rgba(214,59,59,.3);background:var(--red-bg)">
          <div style="font-size:13px;font-weight:800;color:var(--red);margin-bottom:8px">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            Peringatan Kehadiran
          </div>
          <div id="warning-list" style="font-size:12.5px"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- TAB: RIWAYAT -->
  <div class="tab-content" id="tab-riwayat">
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
          Riwayat Pengajuan
        </div>
      </div>
      <div class="tbl-wrap">
        <table class="tabel">
          <thead>
            <tr>
              <th>No</th><th>Tanggal Izin</th><th>Mata Kuliah</th><th>Pertemuan</th><th>Jenis</th><th>Status</th><th>Keterangan</th><th>Bukti</th>
            </tr>
          </thead>
          <tbody id="riwayat-body">
            <tr><td colspan="8" style="text-align:center;padding:40px">Memuat data...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════
     MODAL KONFIRMASI KIRIM IZIN
═══════════════════════════════════════════════ -->
<div class="modal-overlay" id="modal-konfirmasi-izin">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div class="modal-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
          <polygon points="13 2 3 7 3 17 13 22 23 17 23 7 13 2"/>
          <line x1="13" y1="22" x2="13" y2="12"/>
        </svg>
        Konfirmasi Pengajuan
      </div>
      <button class="btn-close-modal" onclick="closeModal('modal-konfirmasi-izin')">✕</button>
    </div>
    <div class="modal-body">
      <div style="background:var(--surface2);border-radius:var(--r-sm);padding:14px;margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:13px">
          <span style="color:var(--text3)">Jenis</span>
          <span style="font-weight:700" id="confirm-jenis">—</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:13px">
          <span style="color:var(--text3)">Mata Kuliah</span>
          <span style="font-weight:700" id="confirm-mk">—</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:13px">
          <span style="color:var(--text3)">Pertemuan</span>
          <span style="font-weight:700" id="confirm-ptm">—</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:13px">
          <span style="color:var(--text3)">Tanggal</span>
          <span style="font-weight:700" id="confirm-tgl">—</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:13px" id="confirm-file-row" style="display:none">
          <span style="color:var(--text3)">Bukti</span>
          <span style="font-weight:700" id="confirm-file">—</span>
        </div>
      </div>
      <div style="font-size:12.5px;color:var(--text2)">Pengajuan akan dikirim ke dosen pengampu. Pastikan data yang diisi sudah benar.</div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-konfirmasi-izin')">Batal</button>
      <button class="btn btn-primary" onclick="submitIzin()">Kirim Sekarang</button>
    </div>
  </div>
</div>

<script>
// ════════════════════════════════════════════════
// GLOBAL VARIABLES
// ════════════════════════════════════════════════
let mkData = [];
let pertemuanData = [];
let currentJadwalId = null;
let currentJenis = 'sakit';
let isSubmitting = false;

// ════════════════════════════════════════════════
// INITIALIZATION
// ════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
  loadMKOptions();
  loadPeringatan();
  loadPendingBadge();
  
  // File drop handling
  const dropZone = document.getElementById('file-drop');
  if (dropZone) {
    dropZone.addEventListener('dragover', function(e) { 
      e.preventDefault(); 
      e.stopPropagation();
      dropZone.classList.add('dragover'); 
    });
    dropZone.addEventListener('dragleave', function(e) {
      e.preventDefault();
      e.stopPropagation();
      dropZone.classList.remove('dragover');
    });
    dropZone.addEventListener('drop', function(e) {
      e.preventDefault();
      e.stopPropagation();
      dropZone.classList.remove('dragover');
      if (e.dataTransfer.files.length > 0) {
        document.getElementById('file-surat').files = e.dataTransfer.files;
        handleFileSelect(document.getElementById('file-surat'));
      }
    });
  }
});

// ════════════════════════════════════════════════
// LOAD BADGE PENDING
// ════════════════════════════════════════════════
function loadPendingBadge() {
  fetch('api/izin.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_riwayat'
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      document.getElementById('badge-pending').textContent = response.pending_count || 0;
    }
  })
  .catch(() => {});
}

// ════════════════════════════════════════════════
// TAB SWITCH
// ════════════════════════════════════════════════
function switchTab(tabId) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
  
  const btn = document.querySelector(`[data-tab="${tabId}"]`);
  const content = document.getElementById(tabId);
  
  if (btn) btn.classList.add('active');
  if (content) content.classList.add('active');
  
  if (tabId === 'tab-riwayat') loadRiwayat();
}

// ════════════════════════════════════════════════
// LOAD MK OPTIONS
// ════════════════════════════════════════════════
function loadMKOptions() {
  fetch('api/izin.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_mk_options'
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      mkData = response.data;
      let html = '<option value="">— Pilih Mata Kuliah —</option>';
      mkData.forEach(mk => {
        html += `<option value="${mk.id}">${mk.nama_mk} (${mk.nama_dosen})</option>`;
      });
      document.getElementById('sel-mk').innerHTML = html;
      
      if (mkData.length === 0) {
        document.getElementById('sel-mk').innerHTML = '<option value="">— Tidak ada jadwal aktif —</option>';
      }
    } else {
      showNotification(response.msg || 'Gagal memuat mata kuliah', 'error');
    }
  })
  .catch(err => {
    console.error('Error loadMKOptions:', err);
    showNotification('Gagal terhubung ke server', 'error');
  });
}

// ════════════════════════════════════════════════
// LOAD PERTEMUAN OPTIONS
// ════════════════════════════════════════════════
function loadPertemuanOptions() {
  const mkId = document.getElementById('sel-mk').value;
  const pertemuanSelect = document.getElementById('sel-pertemuan');
  const pertemuanInfo = document.getElementById('pertemuan-info');
  
  if (!mkId) {
    pertemuanSelect.innerHTML = '<option value="">— Pilih Mata Kuliah dahulu —</option>';
    pertemuanSelect.disabled = true;
    pertemuanInfo.textContent = '';
    document.getElementById('mk-info').textContent = '';
    return;
  }
  
  pertemuanSelect.innerHTML = '<option value="">Memuat...</option>';
  pertemuanSelect.disabled = true;
  
  fetch('api/izin.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=get_pertemuan_options&mk_id=${mkId}`
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      pertemuanData = response.data;
      currentJadwalId = response.jadwal_id;
      
      let html = '<option value="">— Pilih Pertemuan —</option>';
      pertemuanData.forEach(p => {
        let label = `Pertemuan ke-${p.pertemuan_ke}`;
        let disabled = false;
        
        if (p.tanggal) label += ` (${p.tanggal})`;
        
        if (p.izin_ada) {
          if (p.izin_status === 'pending') {
            label += ' — Menunggu';
            disabled = true;
          } else if (p.izin_status === 'disetujui') {
            label += ' — Disetujui';
            disabled = true;
          } else if (p.izin_status === 'ditolak') {
            label += ' — Ditolak (bisa ajukan lagi)';
          }
        }
        
        if (p.status === 'selesai' && !p.izin_ada) {
          label += ' — [Selesai]';
          disabled = true;
        }
        
        html += `<option value="${p.pertemuan_ke}" data-sesi="${p.sesi_id || ''}" data-tanggal="${p.tanggal || ''}" ${disabled ? 'disabled' : ''}>${label}</option>`;
      });
      
      pertemuanSelect.innerHTML = html;
      pertemuanSelect.disabled = false;
      
      const mk = mkData.find(m => m.id == mkId);
      if (mk) {
        document.getElementById('mk-info').textContent = `${mk.sks} SKS — ${mk.nama_dosen}`;
      }
    } else {
      showNotification(response.msg || 'Gagal memuat pertemuan', 'error');
      pertemuanSelect.innerHTML = '<option value="">— Gagal memuat —</option>';
    }
  })
  .catch(err => {
    console.error('Error loadPertemuanOptions:', err);
    showNotification('Gagal terhubung ke server', 'error');
    pertemuanSelect.innerHTML = '<option value="">— Gagal memuat —</option>';
  });
}

// ════════════════════════════════════════════════
// BUKA MODAL KONFIRMASI
// ════════════════════════════════════════════════
function bukaKonfirmasi() {
  const jenis = document.querySelector('input[name="jenis"]:checked')?.value;
  const mkSelect = document.getElementById('sel-mk');
  const mkId = mkSelect.value;
  const pertemuan = document.getElementById('sel-pertemuan').value;
  const tglIzin = document.getElementById('tgl-izin').value;
  const fileInput = document.getElementById('file-surat');
  
  if (!jenis) { showNotification('Pilih jenis izin atau sakit', 'error'); return; }
  if (!mkId || mkId === '') { showNotification('Pilih mata kuliah', 'error'); return; }
  if (!pertemuan || pertemuan === '') { showNotification('Pilih pertemuan', 'error'); return; }
  
  document.getElementById('confirm-jenis').innerHTML = jenis === 'sakit' ? 'Sakit' : 'Izin';
  document.getElementById('confirm-mk').textContent = mkSelect.options[mkSelect.selectedIndex].text;
  document.getElementById('confirm-ptm').textContent = 'Pertemuan ke-' + pertemuan;
  document.getElementById('confirm-tgl').textContent = tglIzin || '—';
  
  const fileRow = document.getElementById('confirm-file-row');
  const fileName = document.getElementById('confirm-file');
  if (fileInput.files.length > 0) {
    fileRow.style.display = 'flex';
    fileName.textContent = fileInput.files[0].name;
  } else {
    fileRow.style.display = 'none';
  }
  
  openModal('modal-konfirmasi-izin');
}

// ════════════════════════════════════════════════
// SUBMIT IZIN
// ════════════════════════════════════════════════
function submitIzin() {
  closeModal('modal-konfirmasi-izin');
  
  if (isSubmitting) return;
  
  const jenis = document.querySelector('input[name="jenis"]:checked')?.value;
  const mkId = document.getElementById('sel-mk').value;
  const pertemuan = document.getElementById('sel-pertemuan').value;
  const tglIzin = document.getElementById('tgl-izin').value;
  const keterangan = document.getElementById('ket-izin').value;
  const fileInput = document.getElementById('file-surat');
  
  const selectedOption = document.querySelector(`#sel-pertemuan option[value="${pertemuan}"]`);
  const sesiId = selectedOption?.dataset?.sesi || '';
  
  isSubmitting = true;
  const btn = document.getElementById('btn-submit');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Mengirim...';
  
  const formData = new FormData();
  formData.append('action', 'submit');
  formData.append('jadwal_id', currentJadwalId);
  formData.append('pertemuan_ke', pertemuan);
  formData.append('sesi_id', sesiId);
  formData.append('tanggal_izin', tglIzin);
  formData.append('jenis', jenis);
  formData.append('keterangan', keterangan);
  
  if (fileInput.files.length > 0) {
    formData.append('file_surat', fileInput.files[0]);
  }
  
  fetch('api/izin.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(response => {
    isSubmitting = false;
    btn.disabled = false;
    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Ajukan Sekarang';
    
    if (response.success) {
      showNotification('Pengajuan berhasil dikirim!', 'success');
      
      const checkedRadio = document.querySelector('input[name="jenis"]:checked');
      if (checkedRadio) checkedRadio.checked = false;
      document.getElementById('sel-mk').value = '';
      document.getElementById('sel-pertemuan').innerHTML = '<option value="">— Pilih Mata Kuliah dahulu —</option>';
      document.getElementById('sel-pertemuan').disabled = true;
      document.getElementById('file-surat').value = '';
      document.getElementById('file-name').textContent = 'Klik untuk pilih file atau drag & drop';
      document.getElementById('mk-info').textContent = '';
      document.getElementById('pertemuan-info').textContent = '';
      document.getElementById('ket-izin').value = '';
      
      loadPendingBadge();
      loadPeringatan();
      loadMKOptions();
    } else {
      showNotification(response.msg || 'Gagal mengirim', 'error');
    }
  })
  .catch(err => {
    isSubmitting = false;
    btn.disabled = false;
    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Ajukan Sekarang';
    console.error('Error submitIzin:', err);
    showNotification('Gagal terhubung ke server', 'error');
  });
}

// ════════════════════════════════════════════════
// LOAD RIWAYAT
// ════════════════════════════════════════════════
function loadRiwayat() {
  document.getElementById('riwayat-body').innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px">Memuat data...</td></tr>';
  
  fetch('api/izin.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_riwayat'
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      renderRiwayat(response.data);
      document.getElementById('badge-pending').textContent = response.pending_count || 0;
    }
  });
}

function renderRiwayat(data) {
  const tbody = document.getElementById('riwayat-body');
  
  if (data.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px">Belum ada pengajuan</td></tr>';
    return;
  }
  
  const statusMap = {
    'pending': { text: 'Menunggu', class: 'pill-yellow' },
    'disetujui': { text: 'Disetujui', class: 'pill-green' },
    'ditolak': { text: 'Ditolak', class: 'pill-red' }
  };
  
  const jenisMap = {
    'sakit': { text: 'Sakit', class: 'pill-ora' },
    'izin': { text: 'Izin', class: 'pill-blue' }
  };
  
  let html = '';
  data.forEach((d, i) => {
    const s = statusMap[d.status] || { text: d.status, class: 'pill-gray' };
    const j = jenisMap[d.jenis] || { text: d.jenis, class: 'pill-gray' };
    
    html += `<tr>
      <td>${i+1}</td>
      <td>${d.tanggal_izin || '-'}</td>
      <td style="font-weight:600">${d.nama_mk}<br><small style="color:var(--text3)">${d.nama_dosen}</small></td>
      <td>Ke-${d.pertemuan_ke}</td>
      <td><span class="pill ${j.class}" style="font-size:11px">${j.text}</span></td>
      <td><span class="pill ${s.class}" style="font-size:11px">${s.text}</span></td>
      <td>${d.keterangan || '—'}</td>
      <td>${d.file_surat ? `<a href="/uploads/izin/${d.file_surat}" target="_blank" class="btn btn-info btn-xs">Lihat</a>` : '—'}</td>
    </table>`;
  });
  
  tbody.innerHTML = html;
}

// ════════════════════════════════════════════════
// LOAD PERINGATAN KEHADIRAN
// ════════════════════════════════════════════════
function loadPeringatan() {
  fetch('api/izin.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_peringatan'
  })
  .then(res => res.json())
  .then(response => {
    if (response.success && response.data.length > 0) {
      const card = document.getElementById('warning-card');
      const list = document.getElementById('warning-list');
      
      let html = '';
      response.data.forEach(d => {
        html += `<div style="margin-bottom:8px;padding:8px;background:rgba(255,255,255,.5);border-radius:6px">
          <strong>${d.nama_mk}</strong>: ${d.persen_kehadiran}% (${d.total_hadir}/${d.total_sesi} pertemuan)
        </div>`;
      });
      
      html += `<div style="font-size:11px;color:var(--text3);margin-top:4px">Minimum: ${response.min_kehadiran}%</div>`;
      
      list.innerHTML = html;
      card.style.display = 'block';
    }
  });
  
  fetch('api/izin.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_riwayat'
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      const pending = response.data.filter(d => d.status === 'pending');
      const card = document.getElementById('pending-card');
      const list = document.getElementById('pending-list');
      
      if (pending.length > 0) {
        let html = '';
        pending.forEach(d => {
          html += `<div style="background:var(--yellow-bg);border-radius:var(--r-sm);padding:10px;margin-bottom:8px;display:flex;gap:10px">
            <div>${d.jenis === 'sakit' ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>' : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'}</div>
            <div>
              <div style="font-size:12px;font-weight:700">${d.jenis === 'sakit' ? 'Sakit' : 'Izin'} — ${d.nama_mk}</div>
              <div style="font-size:11px;color:var(--text2)">Pertemuan ke-${d.pertemuan_ke} · ${d.tanggal_izin || '-'}</div>
              <span class="pill pill-yellow" style="font-size:10px;margin-top:4px">Menunggu</span>
            </div>
          </div>`;
        });
        
        list.innerHTML = html;
        card.style.display = 'block';
      } else {
        card.style.display = 'none';
      }
    }
  });
}

// ════════════════════════════════════════════════
// UTILITY FUNCTIONS
// ════════════════════════════════════════════════
function handleFileSelect(input) {
  if (input.files.length > 0) {
    const file = input.files[0];
    const sizeMB = (file.size / 1024 / 1024).toFixed(1);
    document.getElementById('file-name').textContent = `${file.name} (${sizeMB} MB)`;
    
    if (file.size > 5 * 1024 * 1024) {
      showNotification('Ukuran file maksimal 5 MB', 'error');
      input.value = '';
      document.getElementById('file-name').textContent = 'Klik untuk pilih file atau drag & drop';
    }
  }
}

function updateJenisLabel(jenis) {
  currentJenis = jenis;
  const label = document.getElementById('bukti-label');
  if (label) {
    label.textContent = jenis === 'sakit' ? 'Upload Bukti (Surat Dokter)' : 'Upload Bukti (Surat Izin)';
  }
}

function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('open');
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('open');
}

function showNotification(message, type) {
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
  el.style.cssText = `position:fixed;top:20px;right:20px;padding:12px 20px;background:${bgColor};color:#fff;border-radius:10px;z-index:10000;box-shadow:0 8px 24px rgba(0,0,0,0.2);font-size:13px;font-weight:600;max-width:350px;animation:slideInRight 0.3s ease-out`;
  el.innerHTML = message;
  document.body.appendChild(el);
  setTimeout(() => {
    el.style.animation = 'slideOutRight 0.3s ease-in';
    setTimeout(() => el.remove(), 300);
  }, 3500);
}

// Alias toast untuk kompatibilitas
function toast(msg, type) {
  showNotification(msg, type);
}
</script>

<style>
.pill-yellow { background: #f59e0b; color: white; font-size:11px; padding:2px 8px; border-radius:10px; }
.pill-blue { background: #3b82f6; color: white; font-size:11px; padding:2px 8px; border-radius:10px; }
.pill-green { background: #10b981; color: white; font-size:11px; padding:2px 8px; border-radius:10px; }
.pill-red { background: #ef4444; color: white; font-size:11px; padding:2px 8px; border-radius:10px; }
.pill-ora { background: #f97316; color: white; font-size:11px; padding:2px 8px; border-radius:10px; }
.pill-gray { background: #6b7280; color: white; font-size:11px; padding:2px 8px; border-radius:10px; }
.file-drop { border: 2px dashed var(--border); border-radius: 12px; padding: 24px; text-align: center; cursor: pointer; transition: all 0.2s; background: var(--surface2); }
.file-drop:hover { border-color: var(--ora3); background: var(--orange-bg, #FFF7ED); }
.file-drop-icon { font-size: 28px; margin-bottom: 8px; }
.file-drop-text { font-size: 13px; color: var(--text2); margin-bottom: 4px; }
.file-drop-sub { font-size: 11px; color: var(--text3); }
.spinner { display: inline-block; width: 14px; height: 14px; border: 2px solid #fff; border-radius: 50%; border-top-color: transparent; animation: spin 0.6s linear infinite; vertical-align: middle; margin-right: 6px; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<?php require_once __DIR__ . '/footer.php'; ?>