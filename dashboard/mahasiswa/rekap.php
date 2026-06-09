<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/navigasi.php';
?>

<style>
.stat-card.ora { background: linear-gradient(135deg, #FFF5EB 0%, #FFE8D6 100%); border-left: 4px solid #F97316; }
.stat-card.blue { background: linear-gradient(135deg, #EBF5FF 0%, #D6EAFF 100%); border-left: 4px solid #3B82F6; }
.stat-card.green { background: linear-gradient(135deg, #ECFDF5 0%, #D8F3E8 100%); border-left: 4px solid #10B981; }
.stat-card.purple { background: linear-gradient(135deg, #F5F3FF 0%, #EDE9FE 100%); border-left: 4px solid #8B5CF6; }
.stat-icon-svg { width: 28px; height: 28px; margin-bottom: 8px; }
.stat-icon-svg.ora svg { stroke: #F97316; }
.stat-icon-svg.blue svg { stroke: #3B82F6; }
.stat-icon-svg.green svg { stroke: #10B981; }
.stat-icon-svg.purple svg { stroke: #8B5CF6; }
.stat-card { transition: all 0.3s ease; cursor: default; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
.mk-card { cursor: pointer; transition: all 0.2s ease; margin-bottom: 16px; }
.mk-card.selected { border: 2px solid #F97316 !important; background: linear-gradient(135deg, #FFF7ED 0%, #FFF3E8 100%); }
.mk-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.mk-nums { display: flex; gap: 12px; margin: 12px 0; flex-wrap: wrap; }
.mk-num { padding: 6px 14px; border-radius: 24px; font-size: 13px; font-weight: 700; }
.chart-bars { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; justify-content: flex-start; }
.bar-wrap { display: flex; flex-direction: column; align-items: center; width: 48px; }
.bar { width: 32px; height: 60px; border-radius: 8px 8px 0 0; transition: height 0.3s; }
.bar-val { font-size: 11px; font-weight: 800; margin-bottom: 6px; }
.bar-label { font-size: 10px; color: #6b7280; margin-top: 6px; font-weight: 600; }
@media (max-width: 640px) { .bar-wrap { width: 38px; } .bar { width: 26px; } .mk-nums { gap: 8px; } .mk-num { padding: 4px 10px; font-size: 11px; } }
.mk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 18px; margin-bottom: 24px; }
.filter-bar { margin-bottom: 20px; }
</style>

<div class="section active" id="sec-rekap">
  <div class="page-header">
    <div>
      <div class="page-title">
        <span id="semester-text" style="font-weight: 700; font-size: 18px;">Memuat...</span>
      </div>
      <div class="page-subtitle">Rekap kehadiran per mata kuliah</div>
    </div>
    <div class="header-actions">
      <button class="btn btn-secondary btn-sm" id="btn-export">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
          <polyline points="7 10 12 15 17 10"/>
          <line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        Ekspor PDF
      </button>
    </div>
  </div>

  <div class="stats-grid" style="margin-bottom:18px">
    <div class="stat-card green">
      <div class="stat-icon-svg green"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="20 6 9 17 4 12"/></svg></div>
      <div class="stat-label">Total Hadir</div>
      <div class="stat-value" id="stat-hadir">--</div>
      <div class="stat-sub" id="stat-hadir-sub">dari -- sesi total</div>
      <span class="stat-badge badge-up" id="stat-persen-badge">--%</span>
    </div>
    <div class="stat-card blue">
      <div class="stat-icon-svg blue"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
      <div class="stat-label">Izin / Sakit</div>
      <div class="stat-value" id="stat-izin">--</div>
      <div class="stat-sub">Sudah disetujui</div>
      <span class="stat-badge badge-info" id="stat-mk-terpengaruh">-- MK terpengaruh</span>
    </div>
    <div class="stat-card ora">
      <div class="stat-icon-svg ora"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
      <div class="stat-label">Alpha</div>
      <div class="stat-value" id="stat-alpha">--</div>
      <div class="stat-sub">Tidak hadir tanpa keterangan</div>
      <span class="stat-badge badge-warn" id="stat-alpha-warn">Perhatikan!</span>
    </div>
    <div class="stat-card purple">
      <div class="stat-icon-svg purple"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 12h-4l-3 9-4-18-3 9H2"/></svg></div>
      <div class="stat-label">Status Kelulusan</div>
      <div class="stat-value" id="stat-kelulusan">--/-- MK</div>
      <div class="stat-sub">≥ <span id="min-kehadiran">--</span>% aman</div>
      <span class="stat-badge" id="stat-risiko-badge">-- MK risiko</span>
    </div>
  </div>

  <div class="filter-bar">
    <label>Mata Kuliah</label>
    <select id="filter-mk"></select>
  </div>

  <div class="mk-grid" id="mk-grid">
    <div style="text-align:center;padding:40px;color:gray;">Memuat data...</div>
  </div>

  <div class="card" id="detail-panel" style="display:none; margin-top: 24px;">
    <div class="card-header">
      <div><div class="card-title" id="detail-title">Detail Kehadiran</div><div class="card-sub" id="detail-sub">--</div></div>
      <span class="pill" id="detail-status">--</span>
    </div>
    <div style="margin-bottom:20px">
      <div style="font-size:11px;font-weight:800;color:gray;text-transform:uppercase;margin-bottom:12px">GRAFIK KEHADIRAN PER PERTEMUAN</div>
      <div class="chart-bars" id="chart-bars" style="min-height: 100px;"></div>
      <div class="chart-legend" style="margin-top: 16px;">
        <span class="legend-item"><span class="legend-dot" style="background:#10B981"></span>Hadir</span>
        <span class="legend-item"><span class="legend-dot" style="background:#F59E0B"></span>Izin</span>
        <span class="legend-item"><span class="legend-dot" style="background:#EA580C"></span>Sakit</span>
        <span class="legend-item"><span class="legend-dot" style="background:#EF4444"></span>Alpha</span>
        <span class="legend-item"><span class="legend-dot" style="background:#3B82F6"></span>Belum/Sesi Aktif</span>
      </div>
    </div>
    <div class="tbl-wrap">
      <table class="tabel">
        <thead><tr><th>Pertemuan</th><th>Tanggal</th><th>Status</th><th>Waktu Absen</th><th>Keterangan</th></tr></thead>
        <tbody id="detail-table-body"></tbody>
      </table>
    </div>
  </div>
</div>

<script>
let rekapData = null;
let currentFilter = 'semua';
let currentMK = null;

document.addEventListener('DOMContentLoaded', function() {
  loadRekap();
  document.getElementById('filter-mk').addEventListener('change', function(e) {
    filterMK(e.target.value);
  });
});

function loadRekap() {
  showNotification('Memuat data rekap...', 'info');
  
  fetch('api/rekap.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_rekap'
  })
  .then(res => res.json())
  .then(response => {
    if (response.success) {
      rekapData = response;
      renderStatistik(response);
      renderFilterOptions(response.mata_kuliah);
      renderMKCards(response.mata_kuliah);
      // FIX: Element sudah ada sekarang
      const semesterEl = document.getElementById('semester-text');
      if (semesterEl) {
        semesterEl.innerHTML = response.tahun_akademik;
      }
      showNotification('Data rekap berhasil dimuat', 'success');
    } else {
      showNotification(response.msg || 'Gagal memuat data', 'error');
    }
  })
  .catch(err => {
    console.error(err);
    showNotification('Koneksi bermasalah', 'error');
  });
}

function renderStatistik(data) {
  const stat = data.statistik_global;
  document.getElementById('stat-hadir').innerHTML = stat.total_hadir + '/' + stat.total_sesi;
  document.getElementById('stat-hadir-sub').innerHTML = 'dari ' + stat.total_sesi + ' sesi total';
  document.getElementById('stat-persen-badge').innerHTML = stat.persen_kehadiran + '%';
  document.getElementById('stat-izin').innerHTML = stat.total_izin + stat.total_sakit;
  document.getElementById('stat-mk-terpengaruh').innerHTML = stat.mk_terpengaruh_izin.length + ' MK terpengaruh';
  document.getElementById('stat-alpha').innerHTML = stat.total_alpha;
  document.getElementById('stat-kelulusan').innerHTML = stat.total_mk_lulus + '/' + stat.total_mk + ' MK';
  document.getElementById('min-kehadiran').innerHTML = stat.min_kehadiran;
  document.getElementById('stat-risiko-badge').innerHTML = stat.total_mk_risiko + ' MK risiko';
}

function renderFilterOptions(mataKuliah) {
  const select = document.getElementById('filter-mk');
  select.innerHTML = '<option value="semua">Semua Mata Kuliah</option>';
  if (mataKuliah && mataKuliah.length > 0) {
    mataKuliah.forEach(mk => {
      select.innerHTML += '<option value="' + mk.id + '">' + mk.nama_mk + ' (' + mk.sks + ' SKS)</option>';
    });
  }
}

function renderMKCards(mataKuliah) {
  const container = document.getElementById('mk-grid');
  let filtered = mataKuliah;
  if (currentFilter !== 'semua') {
    filtered = mataKuliah.filter(mk => mk.id == currentFilter);
  }
  if (!filtered || filtered.length === 0) {
    container.innerHTML = '<div style="text-align:center;padding:40px;color:gray">Tidak ada mata kuliah</div>';
    return;
  }
  let html = '';
  filtered.forEach((mk) => {
    const statusClass = mk.lulus ? 'pill-green' : 'pill-red';
    const statusText = mk.lulus ? 'Lulus' : 'Risiko';
    const pctColor = mk.lulus ? '#10B981' : '#EF4444';
    html += '<div class="mk-card card" data-mk-id="' + mk.id + '" style="cursor:pointer; padding:16px; border:1px solid #e5e7eb; border-radius:14px;">';
    html += '<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">';
    html += '<div><div class="mk-name" style="font-weight:800; font-size:16px; margin-bottom:4px;">' + mk.nama_mk + '</div>';
    html += '<div class="mk-dosen" style="font-size:12px; color:gray;">' + mk.nama_dosen + ' · ' + mk.sks + ' SKS</div></div>';
    html += '<span class="pill ' + statusClass + '" style="font-size:11px;">' + statusText + '</span></div>';
    html += '<div class="mk-nums">';
    html += '<span class="mk-num" style="background:#D1FAE5; color:#059669;">Hadir: ' + mk.hadir + '</span>';
    html += '<span class="mk-num" style="background:#FEF3C7; color:#D97706;">Izin: ' + mk.izin + '</span>';
    html += '<span class="mk-num" style="background:#FFEDD5; color:#EA580C;">Sakit: ' + mk.sakit + '</span>';
    html += '<span class="mk-num" style="background:#FEE2E2; color:#DC2626;">Alpha: ' + mk.alpha + '</span></div>';
    html += '<div style="font-size:13px; font-weight:700; margin:8px 0 6px;">' + mk.persen_kehadiran + '% Kehadiran</div>';
    html += '<div class="mk-pct-bar" style="background:#E5E7EB; border-radius:10px; height:8px; overflow:hidden;">';
    html += '<div class="mk-pct-fill" style="width:' + mk.persen_kehadiran + '%; background:' + pctColor + '; height:100%; border-radius:10px;"></div></div>';
    html += '<div style="margin-top:12px; text-align:center;">';
    html += '<button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); showDetail(' + mk.id + ', this.parentElement)" style="width:100%; padding:6px; font-size:12px;">Lihat Detail</button></div></div>';
  });
  container.innerHTML = html;
}

function showDetail(mkId, element) {
  if (!rekapData || !rekapData.mata_kuliah) {
    showNotification('Data belum siap', 'error');
    return;
  }
  const mk = rekapData.mata_kuliah.find(m => m.id == mkId);
  if (!mk) {
    showNotification('Data mata kuliah tidak ditemukan', 'error');
    return;
  }
  currentMK = mkId;
  document.querySelectorAll('.mk-card').forEach(card => { card.classList.remove('selected'); });
  if (element) element.classList.add('selected');
  
  const statusClass = mk.lulus ? 'pill-green' : 'pill-red';
  const statusText = mk.lulus ? ('Lulus (' + mk.persen_kehadiran + '%)') : ('Risiko (' + mk.persen_kehadiran + '% < ' + rekapData.min_kehadiran + '%)');
  
  document.getElementById('detail-title').innerHTML = 'Detail Kehadiran — ' + mk.nama_mk;
  document.getElementById('detail-sub').innerHTML = mk.nama_dosen + ' · ' + mk.sks + ' SKS';
  document.getElementById('detail-status').innerHTML = statusText;
  document.getElementById('detail-status').className = 'pill ' + statusClass;
  
  // Grafik
  let chartHtml = '';
  if (mk.detail_pertemuan && mk.detail_pertemuan.length > 0) {
    mk.detail_pertemuan.forEach(p => {
      let statusCode = '?', barColor = '#3B82F6';
      if (p.status === 'hadir') { statusCode = 'H'; barColor = '#10B981'; }
      else if (p.status === 'telat') { statusCode = 'T'; barColor = '#F59E0B'; }
      else if (p.status === 'izin') { statusCode = 'I'; barColor = '#F59E0B'; }
      else if (p.status === 'sakit') { statusCode = 'S'; barColor = '#EA580C'; }
      else if (p.status === 'alpha') { statusCode = 'A'; barColor = '#EF4444'; }
      chartHtml += '<div class="bar-wrap"><div class="bar-val" style="color:' + barColor + '">' + statusCode + '</div><div class="bar" style="height:60px; background:' + barColor + '; opacity:0.8;"></div><div class="bar-label">P' + p.pertemuan_ke + '</div></div>';
    });
  } else {
    chartHtml = '<div style="width:100%; text-align:center; padding:30px; color:gray;">Belum ada data pertemuan</div>';
  }
  document.getElementById('chart-bars').innerHTML = chartHtml;
  
  // Tabel detail
  let tableHtml = '';
  if (mk.detail_pertemuan && mk.detail_pertemuan.length > 0) {
    mk.detail_pertemuan.forEach(p => {
      let statusClassTable = 'pill-gray', statusTextTable = 'Belum';
      if (p.status === 'hadir') { statusClassTable = 'pill-green'; statusTextTable = 'Hadir'; }
      else if (p.status === 'telat') { statusClassTable = 'pill-ora'; statusTextTable = 'Telat'; }
      else if (p.status === 'izin') { statusClassTable = 'pill-yellow'; statusTextTable = 'Izin'; }
      else if (p.status === 'sakit') { statusClassTable = 'pill-ora'; statusTextTable = 'Sakit'; }
      else if (p.status === 'alpha') { statusClassTable = 'pill-red'; statusTextTable = 'Alpha'; }
      let tgl = p.tanggal ? p.tanggal.split('-').reverse().join('/') : '-';
      let waktu = '-';
      if (p.waktu_absen) { let parts = p.waktu_absen.split(' '); if (parts.length > 1) waktu = parts[1].substring(0,5); }
      tableHtml += '<tr><td>P-' + p.pertemuan_ke + '</td><td>' + tgl + '</td><td><span class="' + statusClassTable + '">' + statusTextTable + '</span></td><td>' + waktu + '</td><td>' + (p.keterangan || '—') + '</td></tr>';
    });
  } else {
    tableHtml = '<tr><td colspan="5" style="text-align:center;padding:30px">Belum ada data pertemuan</td></tr>';
  }
  document.getElementById('detail-table-body').innerHTML = tableHtml;
  
  document.getElementById('detail-panel').style.display = 'block';
  setTimeout(() => document.getElementById('detail-panel').scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
  showNotification('Menampilkan detail ' + mk.nama_mk, 'success');
}

function filterMK(value) {
  currentFilter = value;
  currentMK = null;
  if (rekapData && rekapData.mata_kuliah) {
    renderMKCards(rekapData.mata_kuliah);
    document.getElementById('detail-panel').style.display = 'none';
    showNotification('Filter diterapkan', 'info');
  }
}

document.getElementById('btn-export').addEventListener('click', function() {
  showNotification('Mempersiapkan ekspor PDF...', 'info');
  fetch('api/rekap.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=export_pdf'
  })
  .then(res => res.json())
  .then(response => {
    if (response.success && response.html) {
      const printWindow = window.open('', '_blank', 'width=900,height=700');
      printWindow.document.write(response.html);
      printWindow.document.close();
      printWindow.print();
      showNotification('Ekspor PDF berhasil', 'success');
    } else {
      showNotification('Gagal mengekspor PDF', 'error');
    }
  })
  .catch(() => showNotification('Gagal mengekspor PDF', 'error'));
});

function showNotification(message, type) {
  const colors = { success: '#059669', error: '#dc2626', info: '#2563eb', warning: '#d97706' };
  const bgColor = colors[type] || colors.info;
  const existing = document.querySelector('.custom-notification');
  if (existing) existing.remove();
  const el = document.createElement('div');
  el.className = 'custom-notification';
  el.style.cssText = 'position:fixed;top:20px;right:20px;padding:12px 20px;background:' + bgColor + ';color:#fff;border-radius:10px;z-index:10000;box-shadow:0 8px 24px rgba(0,0,0,0.2);font-size:13px;font-weight:600;animation:slideInRight 0.3s ease-out';
  el.innerHTML = message;
  document.body.appendChild(el);
  setTimeout(() => { el.style.animation = 'slideOutRight 0.3s ease-in'; setTimeout(() => el.remove(), 300); }, 3500);
}

const styleEl = document.createElement('style');
styleEl.textContent = '@keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } } @keyframes slideOutRight { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }';
document.head.appendChild(styleEl);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>