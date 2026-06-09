</div><!-- /content -->
</div><!-- /main -->

<!-- ══════════ TOAST ══════════ -->
<div class="toast-container" id="toast-container"></div>

<!-- ══════════ FOOTER ══════════ -->
<footer style="background:#fff;border-top:1px solid var(--border);padding:12px 26px;text-align:center;font-size:12px;color:var(--text3)">
  © 2025 Jejak Kampus — UNESA &nbsp;·&nbsp; Panel Dosen v1.0
</footer>

<!-- ══════════ JAVASCRIPT ══════════ -->
<script>
// ════════════ SIDEBAR TOGGLE - VERSI RESPONSIF ════════════
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  
  if (!sidebar || !overlay) return;
  
  sidebar.classList.toggle('open');
  overlay.classList.toggle('open');
  
  // Cegah scroll di body saat sidebar terbuka (mobile)
  if (sidebar.classList.contains('open') && window.innerWidth <= 768) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
}

function closeSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  
  if (sidebar) sidebar.classList.remove('open');
  if (overlay) overlay.classList.remove('open');
  document.body.style.overflow = '';
}

// ════════════ MODALS ════════════
function openModal(id, title) {
  const overlay = document.getElementById(id);
  if (!overlay) return;
  overlay.classList.add('open');
  const titleEl = document.getElementById(id + '-title');
  if (titleEl && title) titleEl.textContent = title;
}
function closeModal(id) {
  document.getElementById(id)?.classList.remove('open');
}
function saveModal(id, msg) {
  closeModal(id);
  toast(msg, 'success');
}

// ════════════ TOAST ════════════
function toast(msg, type = 'info') {
  const container = document.getElementById('toast-container');
  if (!container) return;
  
  const t = document.createElement('div');
  t.className = `toast toast-${type}`;
  const icons = { success: '✅', error: '❌', info: 'ℹ️' };
  t.innerHTML = `<span>${icons[type] || 'ℹ️'}</span><span>${msg}</span>`;
  container.appendChild(t);
  setTimeout(() => { 
    t.style.animation = 'toastIn .3s ease reverse'; 
    setTimeout(() => t.remove(), 280); 
  }, 3000);
}

// ════════════ INIT ════════════
document.addEventListener('DOMContentLoaded', function() {
  // Tutup sidebar saat klik nav item (mobile)
  document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', (e) => {
      if (window.innerWidth <= 768) {
        // Biarkan link berfungsi, tutup sidebar setelah navigasi
        setTimeout(() => closeSidebar(), 100);
      }
    });
  });
  
  // Tutup sidebar saat resize ke desktop
  window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
      closeSidebar();
    }
  });
  
  // Tutup modal saat klik overlay
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
      if (e.target === this) this.classList.remove('open');
    });
  });
  
  // Tutup modal dengan ESC
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
      closeSidebar();
    }
  });
});

// ════════════ UTILITY ════════════
function showToast(msg, type = 'info') {
  toast(msg, type);
}
</script>
</body>
</html>