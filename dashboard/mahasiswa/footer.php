    </div><!-- /content -->
</div><!-- /main -->

<!-- ══════════ MODALS ══════════ -->
<!-- Modal: Absensi Berhasil -->
<div class="modal-overlay" id="modal-absen-ok">
    <div class="modal modal-sm">
        <div class="modal-body" style="text-align:center;padding:36px 24px">
            <div style="width:72px;height:72px;border-radius:50%;background:var(--green-bg);display:flex;align-items:center;justify-content:center;font-size:32px;margin:0 auto 16px">✅</div>
            <div style="font-size:20px;font-weight:800;color:var(--green);margin-bottom:8px">Absensi Berhasil!</div>
            <div style="font-size:13px;color:var(--text2);margin-bottom:6px">Pemrograman Web — Pertemuan ke-10</div>
            <div style="font-size:12px;color:var(--text3)">Waktu: <strong id="absen-time">10:22</strong></div>
            <div style="margin-top:20px;padding:12px;background:var(--green-bg);border-radius:var(--r-sm)">
                <div style="font-size:11px;color:var(--green);font-weight:700">Status tercatat: HADIR</div>
            </div>
        </div>
        <div class="modal-footer" style="justify-content:center">
            <button class="btn btn-success" onclick="closeModal('modal-absen-ok')">✅ Tutup</button>
        </div>
    </div>
</div>

<!-- Modal: Preview Bukti Izin -->
<div class="modal-overlay" id="modal-bukti">
    <div class="modal modal-sm">
        <div class="modal-header">
            <div class="modal-title">📎 Preview Bukti Izin</div>
            <button class="btn-close-modal" onclick="closeModal('modal-bukti')">✕</button>
        </div>
        <div class="modal-body" style="text-align:center">
            <div style="background:var(--surface2);border-radius:var(--r);padding:32px;margin-bottom:14px">
                <div style="font-size:56px;margin-bottom:10px">📄</div>
                <div style="font-size:14px;font-weight:700;color:var(--text)">surat_keterangan_sakit.pdf</div>
                <div style="font-size:12px;color:var(--text3);margin-top:4px">231 KB &nbsp;·&nbsp; Diunggah 06 Mar 2025</div>
            </div>
            <div class="detail-row"><div class="detail-label">Jenis</div><div class="detail-val"><span class="pill pill-ora">Sakit</span></div></div>
            <div class="detail-row"><div class="detail-label">Mata Kuliah</div><div class="detail-val">Metodologi Penelitian</div></div>
            <div class="detail-row"><div class="detail-label">Pertemuan</div><div class="detail-val">Ke-5</div></div>
            <div class="detail-row"><div class="detail-label">Status</div><div class="detail-val"><span class="pill pill-yellow">Menunggu</span></div></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-bukti')">Tutup</button>
            <button class="btn btn-info btn-sm" onclick="toast('File diunduh','success')">📥 Unduh File</button>
        </div>
    </div>
</div>

<!-- Modal: Konfirmasi Kirim Izin -->
<div class="modal-overlay" id="modal-confirm-izin">
    <div class="modal modal-sm">
        <div class="modal-header">
            <div class="modal-title">📤 Konfirmasi Pengajuan</div>
            <button class="btn-close-modal" onclick="closeModal('modal-confirm-izin')">✕</button>
        </div>
        <div class="modal-body">
            <div style="background:var(--surface2);border-radius:var(--r-sm);padding:14px;margin-bottom:14px">
                <div class="detail-row"><div class="detail-label">Jenis</div><div class="detail-val" id="confirm-jenis">—</div></div>
                <div class="detail-row"><div class="detail-label">Mata Kuliah</div><div class="detail-val" id="confirm-mk">—</div></div>
                <div class="detail-row"><div class="detail-label">Pertemuan</div><div class="detail-val" id="confirm-ptm">—</div></div>
                <div class="detail-row"><div class="detail-label">Tanggal</div><div class="detail-val" id="confirm-tgl">—</div></div>
            </div>
            <div style="font-size:12.5px;color:var(--text2)">Pengajuan akan dikirim ke dosen pengampu. Pastikan bukti yang dilampirkan sudah benar.</div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-confirm-izin')">Batal</button>
            <button class="btn btn-primary" onclick="saveModal('modal-confirm-izin','Pengajuan izin berhasil dikirim!','success')">📤 Kirim</button>
        </div>
    </div>
</div>

<!-- ══════════ TOAST ══════════ -->
<div class="toast-container" id="toast-container"></div>

<!-- ══════════ FOOTER ══════════ -->
<footer class="footer">
    © 2026 Jejak Kampus - Portal Mahasiswa v1.0
</footer>

<!-- ══════════ JAVASCRIPT (DIPERBAIKI) ══════════ -->
<script>
// ════════════ SIDEBAR TOGGLE (DIPERBAIKI) ════════════
function openSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    if (sidebar) sidebar.classList.add('open');
    if (overlay) overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('open');
    document.body.style.overflow = '';
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar && sidebar.classList.contains('open')) {
        closeSidebar();
    } else {
        openSidebar();
    }
}

// ════════════ INITIALISASI ULANG EVENT LISTENER ════════════
function initSidebarEvents() {
    // Tombol menu mobile
    const menuBtn = document.getElementById('menu-toggle-btn');
    if (menuBtn) {
        // Hapus event listener lama dengan clone
        const newMenuBtn = menuBtn.cloneNode(true);
        menuBtn.parentNode.replaceChild(newMenuBtn, menuBtn);
        newMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openSidebar();
        });
    }
    
    // Overlay: tutup sidebar saat diklik
    const overlay = document.getElementById('sidebar-overlay');
    if (overlay) {
        const newOverlay = overlay.cloneNode(true);
        overlay.parentNode.replaceChild(newOverlay, overlay);
        newOverlay.addEventListener('click', function(e) {
            closeSidebar();
        });
    }
    
    // Nav item: tutup sidebar setelah klik (mobile)
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        const newItem = item.cloneNode(true);
        item.parentNode.replaceChild(newItem, item);
        newItem.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                setTimeout(() => closeSidebar(), 150);
            }
        });
    });
    
    // LOGOUT BUTTON - PERBAIKAN UTAMA
    const logoutBtn = document.getElementById('logout-button');
    if (logoutBtn) {
        const newLogoutBtn = logoutBtn.cloneNode(true);
        logoutBtn.parentNode.replaceChild(newLogoutBtn, logoutBtn);
        newLogoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (confirm('Apakah Anda yakin ingin keluar dari aplikasi?')) {
                window.location.href = '/auth/logout.php';
            }
        });
    }
    
    // Logout link (fallback)
    const logoutLink = document.getElementById('logout-link');
    if (logoutLink) {
        const newLogoutLink = logoutLink.cloneNode(true);
        logoutLink.parentNode.replaceChild(newLogoutLink, logoutLink);
        newLogoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Apakah Anda yakin ingin keluar dari aplikasi?')) {
                window.location.href = '/auth/logout.php';
            }
        });
    }
}

// ════════════ TABS ════════════
function tabSwitch(btn, groupId) {
    const group = document.getElementById(groupId);
    if (group) {
        group.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    }
    btn.classList.add('active');
    const target = btn.dataset.tab;
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    const targetEl = document.getElementById(target);
    if (targetEl) targetEl.classList.add('active');
}

// ════════════ MODALS ════════════
function openModal(id) {
    const overlay = document.getElementById(id);
    if (overlay) overlay.classList.add('open');
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('open');
}

function saveModal(id, msg, type = 'success') {
    closeModal(id);
    toast(msg, type);
}

// ════════════ TOAST ════════════
function toast(msg, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    const icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
    t.innerHTML = `<span>${icons[type] || 'ℹ️'}</span><span>${msg}</span>`;
    container.appendChild(t);
    setTimeout(() => { 
        t.style.animation = 'toastIn .3s ease reverse'; 
        setTimeout(() => t.remove(), 280); 
    }, 3000);
}

// ════════════ SCAN SIMULATION ════════════
function simulateScan() {
    toast('Kamera dibuka...', 'info');
    setTimeout(() => {
        const timeEl = document.getElementById('absen-time');
        if (timeEl) {
            timeEl.textContent = new Date().toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
        }
        openModal('modal-absen-ok');
    }, 1500);
}

function submitToken() {
    const val = document.getElementById('token-input')?.value.trim() || '';
    const st = document.getElementById('token-status');
    if (!st) return;
    if (val.length < 6) {
        st.className = 'scan-status error';
        st.textContent = '❌ Token harus 6 karakter!';
        return;
    }
    if (val === 'AB1234') {
        st.className = 'scan-status success';
        st.textContent = '✅ Token valid! Absensi berhasil.';
        const timeEl = document.getElementById('absen-time');
        if (timeEl) {
            timeEl.textContent = new Date().toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
        }
        setTimeout(() => openModal('modal-absen-ok'), 500);
    } else {
        st.className = 'scan-status error';
        st.textContent = '❌ Token tidak valid. Coba lagi.';
    }
}

function setMethod(m) {
    const qrView = document.getElementById('view-qr');
    const tokenView = document.getElementById('view-token');
    const qrBtn = document.getElementById('method-qr');
    const tokenBtn = document.getElementById('method-token');
    if (qrView) qrView.style.display = m === 'qr' ? 'block' : 'none';
    if (tokenView) tokenView.style.display = m === 'token' ? 'block' : 'none';
    if (qrBtn) qrBtn.classList.toggle('active', m === 'qr');
    if (tokenBtn) tokenBtn.classList.toggle('active', m === 'token');
}

// ════════════ FORM IZIN ════════════
const pertemuanMap = {
    pemweb: 10, metodologi: 9, ddk: 10, algods: 10, bdl: 10, kalkulus: 10, jarkom: 10, so: 10
};

function updatePertemuan() {
    const mk = document.getElementById('sel-mk')?.value;
    const sel = document.getElementById('sel-pertemuan');
    if (!sel) return;
    sel.innerHTML = '<option value="">— Pilih Pertemuan —</option>';
    if (!mk) return;
    const total = pertemuanMap[mk] || 10;
    for (let i = 1; i <= total; i++) {
        sel.innerHTML += `<option value="${i}">Pertemuan ke-${i}</option>`;
    }
}

function setJenis(j) {
    const hint = document.getElementById('bukti-hint');
    if (hint) hint.textContent = j === 'sakit' ? '(Wajib: Surat Dokter)' : '(Wajib: Surat Keterangan Resmi)';
}

// ════════════ INIT ════════════
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Loaded - Inisialisasi ulang event listener');
    
    // Inisialisasi event sidebar & logout
    initSidebarEvents();
    
    // Tutup sidebar saat resize ke desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });
    
    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        const newOverlay = overlay.cloneNode(true);
        overlay.parentNode.replaceChild(newOverlay, overlay);
        newOverlay.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('open');
        });
    });
    
    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
            closeSidebar();
        }
    });
    
    // Check URL hash for tabs
    if (window.location.hash === '#riwayat') {
        const riwayatTab = document.querySelector('[data-tab="tab-riwayat"]');
        if (riwayatTab) riwayatTab.click();
    }
    
    console.log('Sidebar & Logout siap digunakan');
});
</script>
</body>
</html>