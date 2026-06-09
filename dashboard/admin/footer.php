 </div><!-- /content -->
</div><!-- /main -->
</div><!-- /flex -->



<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<script>

// ════════════ MODALS ════════════
function openModal(id, title) {
  const overlay = document.getElementById(id);
  if (!overlay) return;
  overlay.classList.add('open');
  // Update title if element exists
  const titleEl = document.getElementById(id + '-title');
  if (titleEl && title) titleEl.textContent = title;
}
function closeModal(id) {
  const overlay = document.getElementById(id);
  if (overlay) overlay.classList.remove('open');
}
function saveModal(id, msg) {
  closeModal(id);
  toast(msg, 'success');
}

// Close on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', function(e) {
    if (e.target === this) closeModal(this.id);
  });
});

// Close on Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
  }
});


// ════════════ TOAST ════════════
function toast(msg, type = 'info') {
  const container = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = `toast toast-${type}`;
  const icons = {success:'✅', error:'❌', info:'ℹ️'};
  t.innerHTML = `<span>${icons[type]||'ℹ️'}</span><span>${msg}</span>`;
  container.appendChild(t);
  setTimeout(() => { t.style.animation = 'toastIn .3s ease reverse'; setTimeout(() => t.remove(), 280); }, 3200);
}

function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');

  sidebar.classList.toggle('open');
  overlay.classList.toggle('open');
}
</script>
</body>
</html>
