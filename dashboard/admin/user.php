<?php
require_once 'header.php';
require_once 'navigasi.php';
?>

<style>
/* ── Responsive tambahan khusus halaman user ── */
@media (max-width: 600px) {
  .tbl-wrap table thead th:nth-child(5),
  .tbl-wrap table tbody td:nth-child(5) { display: none; }
  .tbl-wrap table thead th:nth-child(3),
  .tbl-wrap table tbody td:nth-child(3) { display: none; }
  .btn-xs { padding: 4px 6px; font-size: 11px; }
  .aksi-group { flex-direction: column; gap: 3px !important; }
}
@media (max-width: 420px) {
  .tbl-wrap table thead th:nth-child(4),
  .tbl-wrap table tbody td:nth-child(4) { display: none; }
}

/* Skeleton loader */
.skeleton-row td { background: linear-gradient(90deg, var(--surface2) 25%, var(--border) 50%, var(--surface2) 75%); background-size: 200% 100%; animation: skeletonPulse 1.4s ease infinite; border-radius: 4px; color: transparent; height: 38px; }
@keyframes skeletonPulse { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

/* Pill colors */
.pill-admin   { background: #ede9fe; color: #6d28d9; }
.pill-dosen   { background: #dbeafe; color: #1d4ed8; }
.pill-mahasiswa { background: #fef3c7; color: #b45309; }

/* Empty state */
.empty-state { text-align:center; padding: 48px 16px; color: var(--text3); }
.empty-state .empty-icon svg { width: 48px; height: 48px; margin-bottom: 12px; stroke: #9ca3af; }
.empty-state .empty-title { font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 6px; }
.empty-state .empty-sub { font-size: 13px; }

/* Password toggle */
.pass-wrap { position: relative; }
.pass-wrap input { padding-right: 42px; }
.pass-toggle { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 16px; color: var(--text3); padding: 0; line-height:1; }

/* Info box */
.info-box { background: var(--surface2); border: 1.5px solid var(--border); border-radius: var(--r-sm); padding: 12px 14px; font-size: 13px; color: var(--text3); margin-bottom: 8px; }
.info-box strong { color: var(--text); }

/* Badge count */
.badge-count { display: inline-flex; align-items: center; justify-content: center; background: var(--ora3); color: #fff; border-radius: 99px; font-size: 11px; font-weight: 700; min-width: 20px; height: 20px; padding: 0 6px; margin-left: 6px; }

/* Perbaikan tampilan tabel */
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
</style>

<!-- ═══════════════════════════════════════════════
     MANAJEMEN PENGGUNA
═══════════════════════════════════════════════ -->
<div>
  <div class="page-header">
    <div>

      <div class="page-subtitle">Kelola akun administrator, dosen, dan mahasiswa</div>
    </div>
    <div class="header-actions">
      <button class="btn btn-secondary btn-sm" onclick="exportCSV()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
          <polyline points="7 10 12 15 17 10"/>
          <line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        Ekspor CSV
      </button>
      <button class="btn btn-primary" onclick="openFormModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
          <line x1="12" y1="5" x2="12" y2="19"/>
          <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Tambah Pengguna
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
      <input type="text" id="inp-search" placeholder="Cari nama atau email..." oninput="debounceSearch()">
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <select id="sel-role" style="padding:8px 12px;border:1.5px solid var(--border-s);border-radius:var(--r-sm);font-family:inherit;font-size:12.5px" onchange="loadUsers(1)">
        <option value="">Semua Peran</option>
        <option value="admin">Administrator</option>
        <option value="dosen">Dosen</option>
        <option value="mahasiswa">Mahasiswa</option>
      </select>
      <button class="btn btn-secondary btn-sm" onclick="resetFilter()">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 2px;">
          <path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M20.49 15a9 9 0 0 1-14.85 3.36L1 14"/>
        </svg>
        Atur Ulang
      </button>
    </div>
  </div>

  <!-- Table -->
  <div class="tbl-wrap">
    <table class="tabel">
      <thead>
        <tr>
          <th style="width:40px">No.</th>
          <th>Nama Lengkap</th>
          <th>Alamat Email</th>
          <th style="width:110px">Peran</th>
          <th style="width:120px">Tanggal Dibuat</th>
          <th style="width:160px">Aksi</th>
        </tr>
      </thead>
      <tbody id="tbody-user">
        <!-- Skeleton awal -->
        <?php for($i=0;$i<5;$i++): ?>
        <tr class="skeleton-row"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <?php endfor; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div class="pagination" id="pagination-wrap" style="display:none">
    <span class="page-info" id="page-info">–</span>
    <div id="page-btns" style="display:flex;gap:4px;flex-wrap:wrap"></div>
  </div>
</div>

<!-- ═══════════════════════════════════
     MODAL: Form Pengguna (Tambah / Edit)
══════════════════════════════════════ -->
<div class="modal-overlay" id="modal-user-form">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modal-user-form-title">Tambah Pengguna Baru</div>
      <button class="btn-close-modal" onclick="closeModal('modal-user-form')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="uf-id">
      <div class="form-group">
        <label>Nama Lengkap <span style="color:var(--red)">*</span></label>
        <input type="text" id="uf-nama" placeholder="Masukkan nama lengkap" maxlength="100">
      </div>
      <div class="form-group">
        <label>Alamat Email <span style="color:var(--red)">*</span></label>
        <input type="email" id="uf-email" placeholder="email@domain.ac.id" maxlength="100">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Peran <span style="color:var(--red)">*</span></label>
          <select id="uf-role">
            <option value="">— Pilih Peran —</option>
            <option value="admin">Administrator</option>
            <option value="dosen">Dosen</option>
            <option value="mahasiswa">Mahasiswa</option>
          </select>
        </div>
        <div class="form-group">
          <label id="uf-pass-label">Kata Sandi <span style="color:var(--red)">*</span></label>
          <div class="pass-wrap">
            <input type="password" id="uf-password" placeholder="Minimal 6 karakter" maxlength="100">
            <button type="button" class="pass-toggle" onclick="togglePass('uf-password',this)">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
          <small id="uf-pass-hint" style="color:var(--text3);font-size:11.5px;margin-top:4px;display:block">Kosongkan jika tidak ingin mengubah kata sandi</small>
        </div>
      </div>
      <div id="uf-error" class="info-box" style="display:none;border-color:#fca5a5;background:#fff5f5;color:var(--red)"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-user-form')">Batal</button>
      <button class="btn btn-primary" id="uf-submit-btn" onclick="submitUserForm()">
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

<!-- ═══════════════════════════════════
     MODAL: Atur Ulang Kata Sandi
══════════════════════════════════════ -->
<div class="modal-overlay" id="modal-reset-pw">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div class="modal-title">Atur Ulang Kata Sandi</div>
      <button class="btn-close-modal" onclick="closeModal('modal-reset-pw')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="rp-id">
      <div style="text-align:center;padding:8px 0 16px">
        <div style="margin-bottom:10px">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#F97316" stroke-width="1.5">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
        </div>
        <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px">Atur ulang kata sandi untuk:</div>
        <div id="rp-nama" style="font-size:13px;font-weight:700;color:var(--ora3)">–</div>
      </div>
      <div class="form-group">
        <label>Kata Sandi Baru</label>
        <div class="pass-wrap">
          <input type="password" id="rp-password" placeholder="Kosongkan = hasilkan otomatis" maxlength="100">
          <button type="button" class="pass-toggle" onclick="togglePass('rp-password',this)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
        <small style="color:var(--text3);font-size:11.5px;margin-top:4px;display:block">Jika dikosongkan, sistem akan menghasilkan kata sandi acak</small>
      </div>
      <div id="rp-result" style="display:none" class="info-box"></div>
      <div id="rp-error" class="info-box" style="display:none;border-color:#fca5a5;background:#fff5f5;color:var(--red)"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-reset-pw')">Tutup</button>
      <button class="btn btn-primary" id="rp-submit-btn" onclick="submitResetPassword()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
          <path d="M21 2v6h-6M3 12a9 9 0 0 1 15-6.7L21 8M3 22v-6h6M21 12a9 9 0 0 1-15 6.7L3 16"/>
        </svg>
        Atur Ulang Kata Sandi
      </button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════
     MODAL: Konfirmasi Hapus
══════════════════════════════════════ -->
<div class="modal-overlay" id="modal-confirm-del">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div class="modal-title">Konfirmasi Penghapusan</div>
      <button class="btn-close-modal" onclick="closeModal('modal-confirm-del')">
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
      <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:8px">Hapus pengguna ini?</div>
      <div id="del-desc" style="font-size:13px;color:var(--text3);margin-bottom:4px"></div>
      <div style="font-size:12px;color:var(--red);margin-top:10px">Tindakan ini tidak dapat dibatalkan</div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modal-confirm-del')">Batal</button>
      <button class="btn btn-danger" id="del-confirm-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
          <polyline points="3 6 5 6 21 6"/>
          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
          <line x1="10" y1="11" x2="10" y2="17"/>
          <line x1="14" y1="11" x2="14" y2="17"/>
        </svg>
        Hapus
      </button>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast-container" id="toast-container-user"></div>

<script>
// ════════════════════════════════════════
//  STATE
// ════════════════════════════════════════
let currentPage   = 1;
let searchTimer   = null;
let isEditMode    = false;

// ════════════════════════════════════════
//  HELPERS
// ════════════════════════════════════════
function toast(msg, type = 'info') {
  const container = document.getElementById('toast-container') || document.getElementById('toast-container-user');
  const t = document.createElement('div');
  t.className = `toast toast-${type}`;
  const icons = {success:'✓', error:'✗', info:'ℹ', warning:'⚠'};
  t.innerHTML = `<span>${icons[type]||'ℹ'}</span><span>${msg}</span>`;
  container.appendChild(t);
  setTimeout(() => { t.style.animation = 'toastIn .3s ease reverse'; setTimeout(() => t.remove(), 280); }, 3400);
}

function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('open');
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('open');
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) closeModal(overlay.id);
  });
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
});

function togglePass(inputId, btn) {
  const inp = document.getElementById(inputId);
  if (inp.type === 'password') { inp.type = 'text'; }
  else { inp.type = 'password'; }
}

function formatDate(dateStr) {
  if (!dateStr) return '–';
  const d = new Date(dateStr);
  return d.toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'});
}

function rolePill(role) {
  const map = {
    admin:      ['pill-admin',      'Administrator'],
    dosen:      ['pill-dosen',      'Dosen'],
    mahasiswa:  ['pill-mahasiswa',  'Mahasiswa'],
  };
  const [cls, label] = map[role] || ['', role];
  return `<span class="pill ${cls}">${label}</span>`;
}

function setLoading(btn, loading, originalText) {
  if (loading) { btn.disabled = true; btn.dataset.orig = btn.innerHTML; btn.innerHTML = '⏳ Menyimpan...'; }
  else { btn.disabled = false; btn.innerHTML = btn.dataset.orig || originalText; }
}

// ════════════════════════════════════════
//  LOAD & RENDER TABLE
// ════════════════════════════════════════
function debounceSearch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadUsers(1), 400);
}

function resetFilter() {
  document.getElementById('inp-search').value = '';
  document.getElementById('sel-role').value = '';
  loadUsers(1);
}

function loadUsers(page = 1) {
  currentPage = page;
  const search = document.getElementById('inp-search').value.trim();
  const role   = document.getElementById('sel-role').value;

  // Skeleton
  const tbody = document.getElementById('tbody-user');
  tbody.innerHTML = Array(5).fill('<tr class="skeleton-row"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>').join('');

  const fd = new FormData();
  fd.append('action', 'list');
  fd.append('page',   page);
  fd.append('search', search);
  fd.append('role',   role);

  fetch('/dashboard/admin/api/user.php', { method:'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (!res.success) { toast('Gagal memuat data.','error'); return; }
      renderTable(res);
      renderPagination(res);
    })
    .catch(() => toast('Koneksi bermasalah.','error'));
}

function renderTable(res) {
  const tbody = document.getElementById('tbody-user');
  if (!res.data || res.data.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6">
      <div class="empty-state">
        <div class="empty-icon">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
        </div>
        <div class="empty-title">Tidak ada pengguna ditemukan</div>
        <div class="empty-sub">Coba ubah kata kunci atau filter pencarian.</div>
      </div>
    </td></tr>`;
    return;
  }
  const start = (res.page - 1) * res.limit;
  tbody.innerHTML = res.data.map((u, i) => `
    <tr>
      <td>${start + i + 1}</td>
      <td><div style="font-weight:700">${escHtml(u.nama)}</div></td>
      <td><span style="font-size:12.5px;color:var(--text3)">${escHtml(u.email)}</span></td>
      <td>${rolePill(u.role)}</td>
      <td style="font-size:12.5px;color:var(--text3)">${formatDate(u.created_at)}</td>
      <td>
        <div class="aksi-group" style="display:flex;gap:5px;flex-wrap:wrap">
          <button class="btn btn-info btn-xs" onclick="openEditModal(${u.id})">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 2px;">
              <path d="M17 3l4 4L7 21H3v-4L17 3z"/>
            </svg>
            Edit
          </button>
          <button class="btn btn-secondary btn-xs" onclick="openResetModal(${u.id},'${escHtml(u.nama).replace(/'/g,"\\'")}')">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 2px;">
              <path d="M21 2v6h-6M3 12a9 9 0 0 1 15-6.7L21 8M3 22v-6h6M21 12a9 9 0 0 1-15 6.7L3 16"/>
            </svg>
            Reset
          </button>
          <button class="btn btn-danger btn-xs" onclick="openDeleteModal(${u.id},'${escHtml(u.nama).replace(/'/g,"\\'")}')">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 2px;">
              <polyline points="3 6 5 6 21 6"/>
              <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
            </svg>
            Hapus
          </button>
        </div>
      </td>
    </tr>
  `).join('');
}

function renderPagination(res) {
  const wrap = document.getElementById('pagination-wrap');
  const info = document.getElementById('page-info');
  const btns = document.getElementById('page-btns');

  wrap.style.display = 'flex';
  const from = (res.page - 1) * res.limit + 1;
  const to   = Math.min(res.page * res.limit, res.total);
  info.textContent = `Menampilkan ${from}–${to} dari ${res.total.toLocaleString('id-ID')} data`;

  // Buat tombol halaman (max 5 ditampilkan)
  let html = '';
  html += `<button class="page-btn" ${res.page<=1?'disabled':''} onclick="loadUsers(${res.page-1})">‹</button>`;

  let startP = Math.max(1, res.page - 2);
  let endP   = Math.min(res.pages, startP + 4);
  if (endP - startP < 4) startP = Math.max(1, endP - 4);

  if (startP > 1) html += `<button class="page-btn" onclick="loadUsers(1)">1</button>${startP>2?'<span style="padding:0 4px;align-self:center;color:var(--text3)">…</span>':''}`;
  for (let p = startP; p <= endP; p++) {
    html += `<button class="page-btn ${p===res.page?'active':''}" onclick="loadUsers(${p})">${p}</button>`;
  }
  if (endP < res.pages) html += `${endP<res.pages-1?'<span style="padding:0 4px;align-self:center;color:var(--text3)">…</span>':''}<button class="page-btn" onclick="loadUsers(${res.pages})">${res.pages}</button>`;

  html += `<button class="page-btn" ${res.page>=res.pages?'disabled':''} onclick="loadUsers(${res.page+1})">›</button>`;
  btns.innerHTML = html;
}

function escHtml(str) {
  if (!str) return '';
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ════════════════════════════════════════
//  FORM TAMBAH / EDIT
// ════════════════════════════════════════
function openFormModal() {
  isEditMode = false;
  document.getElementById('uf-id').value       = '';
  document.getElementById('uf-nama').value     = '';
  document.getElementById('uf-email').value    = '';
  document.getElementById('uf-role').value     = '';
  document.getElementById('uf-password').value = '';
  document.getElementById('uf-pass-label').innerHTML = 'Kata Sandi <span style="color:var(--red)">*</span>';
  document.getElementById('uf-pass-hint').style.display = 'none';
  document.getElementById('uf-error').style.display = 'none';
  document.getElementById('modal-user-form-title').textContent = 'Tambah Pengguna Baru';
  openModal('modal-user-form');
}

function openEditModal(id) {
  isEditMode = true;
  document.getElementById('uf-error').style.display = 'none';
  document.getElementById('modal-user-form-title').textContent = 'Edit Pengguna';
  document.getElementById('uf-pass-label').innerHTML = 'Kata Sandi';
  document.getElementById('uf-pass-hint').style.display = 'block';

  const fd = new FormData();
  fd.append('action', 'get');
  fd.append('id', id);

  fetch('/dashboard/admin/api/user.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(res => {
      if (!res.success || !res.data) { toast('Data pengguna tidak ditemukan.','error'); return; }
      const u = res.data;
      document.getElementById('uf-id').value       = u.id;
      document.getElementById('uf-nama').value     = u.nama;
      document.getElementById('uf-email').value    = u.email;
      document.getElementById('uf-role').value     = u.role;
      document.getElementById('uf-password').value = '';
      openModal('modal-user-form');
    })
    .catch(() => toast('Gagal memuat data pengguna.','error'));
}

function submitUserForm() {
  const id    = document.getElementById('uf-id').value;
  const nama  = document.getElementById('uf-nama').value.trim();
  const email = document.getElementById('uf-email').value.trim();
  const role  = document.getElementById('uf-role').value;
  const pass  = document.getElementById('uf-password').value.trim();
  const errEl = document.getElementById('uf-error');

  errEl.style.display = 'none';

  // Client-side validasi
  if (!nama)  { showErr(errEl, 'Nama lengkap wajib diisi.'); return; }
  if (!email) { showErr(errEl, 'Alamat email wajib diisi.'); return; }
  if (!role)  { showErr(errEl, 'Peran wajib dipilih.'); return; }
  if (!isEditMode && !pass) { showErr(errEl, 'Kata sandi wajib diisi untuk pengguna baru.'); return; }

  const btn = document.getElementById('uf-submit-btn');
  setLoading(btn, true);

  const fd = new FormData();
  fd.append('action',   isEditMode ? 'update' : 'create');
  if (isEditMode) fd.append('id', id);
  fd.append('nama',     nama);
  fd.append('email',    email);
  fd.append('role',     role);
  fd.append('password', pass);

  fetch('/dashboard/admin/api/user.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(res => {
      setLoading(btn, false);
      if (!res.success) { showErr(errEl, res.msg || 'Gagal menyimpan.'); return; }
      closeModal('modal-user-form');
      toast(res.msg, 'success');
      loadUsers(currentPage);
    })
    .catch(() => { setLoading(btn, false); toast('Koneksi bermasalah.','error'); });
}

function showErr(el, msg) {
  el.textContent = msg;
  el.style.display = 'block';
}

// ════════════════════════════════════════
//  RESET PASSWORD
// ════════════════════════════════════════
function openResetModal(id, nama) {
  document.getElementById('rp-id').value         = id;
  document.getElementById('rp-nama').textContent = nama;
  document.getElementById('rp-password').value   = '';
  document.getElementById('rp-result').style.display = 'none';
  document.getElementById('rp-error').style.display  = 'none';
  document.getElementById('rp-submit-btn').style.display = 'inline-flex';
  openModal('modal-reset-pw');
}

function submitResetPassword() {
  const id   = document.getElementById('rp-id').value;
  const pass = document.getElementById('rp-password').value.trim();
  const btn  = document.getElementById('rp-submit-btn');
  const errEl = document.getElementById('rp-error');
  const resEl = document.getElementById('rp-result');

  errEl.style.display = 'none';
  resEl.style.display = 'none';
  setLoading(btn, true);

  const fd = new FormData();
  fd.append('action',       'reset_password');
  fd.append('id',           id);
  fd.append('new_password', pass);

  fetch('/dashboard/admin/api/user.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(res => {
      setLoading(btn, false);
      if (!res.success) { showErr(errEl, res.msg); return; }
      resEl.innerHTML = `✓ ${res.msg}`;
      resEl.style.display = 'block';
      btn.style.display = 'none';
      toast('Kata sandi berhasil diatur ulang.','success');
    })
    .catch(() => { setLoading(btn, false); toast('Koneksi bermasalah.','error'); });
}

// ════════════════════════════════════════
//  DELETE
// ════════════════════════════════════════
function openDeleteModal(id, nama) {
  document.getElementById('del-desc').textContent = `"${nama}"`;
  const btn = document.getElementById('del-confirm-btn');
  btn.onclick = () => deleteUser(id, nama);
  openModal('modal-confirm-del');
}

function deleteUser(id, nama) {
  const btn = document.getElementById('del-confirm-btn');
  setLoading(btn, true);

  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id',     id);

  fetch('/dashboard/admin/api/user.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(res => {
      setLoading(btn, false);
      closeModal('modal-confirm-del');
      if (!res.success) { toast(res.msg || 'Gagal menghapus.','error'); return; }
      toast(`Pengguna "${nama}" berhasil dihapus.`,'success');
      loadUsers(currentPage);
    })
    .catch(() => { setLoading(btn, false); toast('Koneksi bermasalah.','error'); });
}

// ════════════════════════════════════════
//  EXPORT CSV
// ════════════════════════════════════════
function exportCSV() {
  const frm = document.createElement('form');
  frm.method = 'POST';
  frm.action = 'api/user.php';
  const inp = document.createElement('input');
  inp.type = 'hidden'; inp.name = 'action'; inp.value = 'export_csv';
  frm.appendChild(inp);
  document.body.appendChild(frm);
  frm.submit();
  document.body.removeChild(frm);
  toast('Ekspor CSV sedang diunduh...','info');
}

// ════════════════════════════════════════
//  INIT
// ════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => loadUsers(1));
</script>

<?php require_once 'footer.php'; ?>