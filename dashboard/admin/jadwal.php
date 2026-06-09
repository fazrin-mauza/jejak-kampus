<?php
require_once 'header.php';
require_once 'navigasi.php';
?>

<style>
/* Perbaikan tampilan tabel dan card */
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

/* Filter bar styling */
.filter-bar {
    background: #F8FAFC;
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
}
.filter-bar label {
    font-size: 12px;
    font-weight: 600;
    color: #475569;
}
.filter-bar select, .filter-bar button {
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid #E2E8F0;
    background: white;
    font-size: 12px;
}

/* Button styling */
.btn-xs {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* Pill colors */
.pill-ora { background: #f97316; color: white; }
.pill-purple { background: #a855f7; color: white; }
.pill-red { background: #ef4444; color: white; }
.pill-gray { background: #6b7280; color: white; }

/* Modal large */
.modal-xl { max-width: 1000px; width: 95%; }

/* Modal animation */
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}
</style>

<!-- ═══════════════════════════════════════════════
     MANAJEMEN JADWAL - Support Rentang Jam
═══════════════════════════════════════════════ -->
<div id="app-jadwal">
    <div class="page-header">
        <div>
    
            <div class="page-subtitle">Kelola jadwal perkuliahan per semester</div>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary btn-sm" onclick="printJadwal()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <path d="M6 9V3h12v6"/>
                    <rect x="6" y="15" width="12" height="6" rx="2"/>
                </svg>
                Cetak
            </button>
            <button class="btn btn-secondary btn-sm" onclick="exportCSV()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Ekspor CSV
            </button>
            <button class="btn btn-primary" onclick="openTambahModal()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Tambah Jadwal
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <label>Tahun Akademik:</label>
        <select id="filter-ta" onchange="loadData()">
            <option value="">Pilih Tahun Akademik</option>
        </select>
        <label>Hari:</label>
        <select id="filter-hari" onchange="loadData()">
            <option value="">Semua Hari</option>
            <option value="Senin">Senin</option>
            <option value="Selasa">Selasa</option>
            <option value="Rabu">Rabu</option>
            <option value="Kamis">Kamis</option>
            <option value="Jumat">Jumat</option>
            <option value="Sabtu">Sabtu</option>
        </select>
        <label>Kelas:</label>
        <select id="filter-kelas" onchange="loadData()">
            <option value="">Semua Kelas</option>
        </select>
        <label>Dosen:</label>
        <select id="filter-dosen" onchange="loadData()">
            <option value="">Semua Dosen</option>
        </select>
        <button class="btn btn-primary btn-sm" onclick="loadData()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 2px;">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            Filter
        </button>
        <button class="btn btn-secondary btn-sm" onclick="resetFilter()">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 2px;">
                <path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M20.49 15a9 9 0 0 1-14.85 3.36L1 14"/>
            </svg>
            Atur Ulang
        </button>
    </div>

    <!-- Table -->
    <div class="tbl-wrap">
        <table class="tabel">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Hari</th>
                    <th>Jam</th>
                    <th>Jam Ke-</th>
                    <th>Mata Kuliah</th>
                    <th>SKS</th>
                    <th>Dosen</th>
                    <th>Kelas</th>
                    <th>Ruangan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <tr><td colspan="10" style="text-align:center;padding:40px">Pilih Tahun Akademik untuk memuat data</td</tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ═══════════════════════════════════════════════
     MODALS
═══════════════════════════════════════════════ -->

<!-- Modal: Jadwal Form -->
<div class="modal-overlay" id="modal-jadwal-form">
    <div class="modal modal-lg">
        <div class="modal-header">
            <div class="modal-title" id="modal-jadwal-form-title">Tambah Jadwal</div>
            <button class="btn-close-modal" onclick="closeModal('modal-jadwal-form')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form id="form-jadwal">
                <input type="hidden" name="id" id="jadwal-id">
                <div class="form-row">
                    <div class="form-group">
                        <label>Tahun Akademik <span style="color:var(--red)">*</span></label>
                        <select name="tahun_akademik_id" id="jadwal-ta" required>
                            <option value="">Pilih</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Hari <span style="color:var(--red)">*</span></label>
                        <select name="hari" id="jadwal-hari" required>
                            <option value="">Pilih</option>
                            <option value="Senin">Senin</option><option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option><option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option><option value="Sabtu">Sabtu</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Kelas <span style="color:var(--red)">*</span></label>
                        <select name="kelas_id" id="jadwal-kelas" required>
                            <option value="">Pilih</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mata Kuliah <span style="color:var(--red)">*</span></label>
                        <select name="mata_kuliah_id" id="jadwal-mk" required onchange="hitungRentangJam()">
                            <option value="">Pilih</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Dosen <span style="color:var(--red)">*</span></label>
                        <select name="dosen_id" id="jadwal-dosen" required>
                            <option value="">Pilih</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ruangan <span style="color:var(--red)">*</span></label>
                        <select name="ruangan_id" id="jadwal-ruangan" required>
                            <option value="">Pilih</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Jam Mulai <span style="color:var(--red)">*</span></label>
                        <select name="jam_ke_id" id="jadwal-jam-mulai" required onchange="hitungRentangJam()">
                            <option value="">Pilih Jam Mulai</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Informasi Rentang Jam</label>
                        <input type="text" id="rentang-info" readonly style="background:var(--surface2);" placeholder="Pilih MK dan Jam Mulai">
                    </div>
                </div>
                <input type="hidden" name="sks" id="sks-hidden">
                <div id="bentrokan-info" style="margin-top:12px;padding:10px;border-radius:var(--r-sm);display:none;"></div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-jadwal-form')">Batal</button>
            <button class="btn btn-primary" onclick="validateAndSubmit()">
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

<!-- Modal: Confirm Delete -->
<div class="modal-overlay" id="modal-confirm">
    <div class="modal modal-sm">
        <div class="modal-header">
            <div class="modal-title">Konfirmasi Penghapusan</div>
            <button class="btn-close-modal" onclick="closeModal('modal-confirm')">
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
            <div style="font-size:14px;font-weight:700;margin-bottom:8px">Hapus jadwal ini?</div>
            <div id="confirm-desc" style="font-size:13px;color:var(--text3);"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-confirm')">Batal</button>
            <button class="btn btn-danger" id="confirm-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
                Hapus
            </button>
        </div>
    </div>
</div>

<!-- Modal: Print Preview -->
<div class="modal-overlay" id="modal-print">
    <div class="modal modal-xl">
        <div class="modal-header">
            <div class="modal-title">Cetak Jadwal</div>
            <button class="btn-close-modal" onclick="closeModal('modal-print')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body" id="print-content"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-print')">Tutup</button>
            <button class="btn btn-primary" onclick="doPrint()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <path d="M6 9V3h12v6"/>
                    <rect x="6" y="15" width="12" height="6" rx="2"/>
                </svg>
                Cetak
            </button>
        </div>
    </div>
</div>

<script>
let jamKeList = [], mkList = [], deleteQueue = { id: null, info: '' };

document.addEventListener('DOMContentLoaded', () => {
    loadTahunAkademikOptions();
    loadKelasOptions();
    loadDosenOptions();
    loadRuanganOptions();
    loadJamKeOptions();
    loadMataKuliahOptions();
});

// ════════════════════════════════════════════════
// FUNGSI RESET FORM
// ════════════════════════════════════════════════
function resetFormJadwal() {
    document.getElementById('form-jadwal').reset();
    document.getElementById('jadwal-id').value = '';
    document.getElementById('rentang-info').value = '';
    document.getElementById('bentrokan-info').style.display = 'none';
}

// ════════════════════════════════════════════════
// FUNGSI BUKA MODAL TAMBAH (dengan reset form)
// ════════════════════════════════════════════════
function openTambahModal() {
    resetFormJadwal();
    document.getElementById('modal-jadwal-form-title').textContent = 'Tambah Jadwal';
    openModal('modal-jadwal-form');
}

// Load options
function loadTahunAkademikOptions() {
    fetch('api/jadwal.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_tahun_akademik'
    })
    .then(r=>r.json()).then(d=>{
        if(d.success){
            let filter='<option value="">Pilih Tahun Akademik</option>', form='<option value="">Pilih</option>';
            d.data.forEach(ta=>{
                filter+=`<option value="${ta.id}" ${ta.status=='aktif'?'selected':''}>${ta.tahun} ${ta.semester}</option>`;
                form+=`<option value="${ta.id}">${ta.tahun} ${ta.semester}</option>`;
            });
            document.getElementById('filter-ta').innerHTML=filter;
            document.getElementById('jadwal-ta').innerHTML=form;
            if(d.data.find(ta=>ta.status=='aktif')) loadData();
        }
    });
}

function loadKelasOptions() {
    fetch('api/jadwal.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_kelas'
    })
    .then(r=>r.json()).then(d=>{
        if(d.success){
            let filter='<option value="">Semua Kelas</option>', form='<option value="">Pilih</option>';
            d.data.forEach(k=>{ filter+=`<option value="${k.id}">${k.nama_kelas}</option>`; form+=`<option value="${k.id}">${k.nama_kelas}</option>`; });
            document.getElementById('filter-kelas').innerHTML=filter;
            document.getElementById('jadwal-kelas').innerHTML=form;
        }
    });
}

function loadDosenOptions() {
    fetch('api/jadwal.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_dosen'
    })
    .then(r=>r.json()).then(d=>{
        if(d.success){
            let filter='<option value="">Semua Dosen</option>', form='<option value="">Pilih</option>';
            d.data.forEach(ds=>{ filter+=`<option value="${ds.id}">${ds.nama}</option>`; form+=`<option value="${ds.id}">${ds.nama}</option>`; });
            document.getElementById('filter-dosen').innerHTML=filter;
            document.getElementById('jadwal-dosen').innerHTML=form;
        }
    });
}

function loadRuanganOptions() {
    fetch('api/jadwal.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_ruangan'
    })
    .then(r=>r.json()).then(d=>{
        if(d.success){
            let form='<option value="">Pilih</option>';
            d.data.forEach(r=>{ form+=`<option value="${r.id}">${r.kode_ruangan} - ${r.nama_ruangan}</option>`; });
            document.getElementById('jadwal-ruangan').innerHTML=form;
        }
    });
}

function loadJamKeOptions() {
    fetch('api/jadwal.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_jam_ke'
    })
    .then(r=>r.json()).then(d=>{
        if(d.success){
            jamKeList=d.data;
            let form='<option value="">Pilih Jam Mulai</option>';
            d.data.forEach(j=>{ form+=`<option value="${j.id}" data-jamke="${j.jam_ke}" data-mulai="${j.jam_mulai}" data-selesai="${j.jam_selesai}">Jam ke-${j.jam_ke} (${j.jam_mulai.substring(0,5)}-${j.jam_selesai.substring(0,5)})</option>`; });
            document.getElementById('jadwal-jam-mulai').innerHTML=form;
        }
    });
}

function loadMataKuliahOptions() {
    fetch('api/jadwal.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_mata_kuliah'
    })
    .then(r=>r.json()).then(d=>{
        if(d.success){
            mkList=d.data;
            let form='<option value="">Pilih</option>';
            d.data.forEach(mk=>{ form+=`<option value="${mk.id}" data-sks="${mk.sks}">${mk.kode_mk} - ${mk.nama_mk} (${mk.sks} SKS)</option>`; });
            document.getElementById('jadwal-mk').innerHTML=form;
        }
    });
}

function hitungRentangJam() {
    const mkSelect=document.getElementById('jadwal-mk');
    const jamSelect=document.getElementById('jadwal-jam-mulai');
    const mkOption=mkSelect.options[mkSelect.selectedIndex];
    const jamOption=jamSelect.options[jamSelect.selectedIndex];
    const info=document.getElementById('rentang-info');
    
    if(!mkSelect.value||!jamSelect.value){ info.value=''; return; }
    
    const sks=parseInt(mkOption.dataset.sks);
    const jamMulai=parseInt(jamOption.dataset.jamke);
    const jamSelesai=jamMulai+sks-1;
    
    if(jamMulai<=6&&jamSelesai>=7){ info.value='Melewati jam istirahat (12:00-13:00)'; return; }
    if(jamSelesai>12){ info.value='Melebihi jam ke-12'; return; }
    
    const jamSelesaiData=jamKeList.find(j=>j.jam_ke===jamSelesai);
    if(jamSelesaiData){
        const jamList=[];
        for(let i=jamMulai;i<=jamSelesai;i++) jamList.push(i);
        info.value=`${jamOption.dataset.mulai.substring(0,5)} - ${jamSelesaiData.jam_selesai.substring(0,5)} | Jam ke-${jamList.join(', ')} (${sks} SKS)`;
        document.getElementById('sks-hidden').value=sks;
        cekBentrokan();
    }
}

function cekBentrokan() {
    const ta=document.getElementById('jadwal-ta').value;
    const hari=document.getElementById('jadwal-hari').value;
    const jamMulai=document.getElementById('jadwal-jam-mulai');
    const mk=document.getElementById('jadwal-mk');
    const ruangan=document.getElementById('jadwal-ruangan').value;
    const dosen=document.getElementById('jadwal-dosen').value;
    const kelas=document.getElementById('jadwal-kelas').value;
    const exclude=document.getElementById('jadwal-id').value;
    
    if(!ta||!hari||!jamMulai.value||!mk.value) return;
    
    const jamOption=jamMulai.options[jamMulai.selectedIndex];
    const mkOption=mk.options[mk.selectedIndex];
    const jamMulaiKe=parseInt(jamOption.dataset.jamke);
    const sks=parseInt(mkOption.dataset.sks);
    
    const formData = new FormData();
    formData.append('action','cek_bentrokan_rentang');
    formData.append('tahun_akademik_id',ta);
    formData.append('hari',hari);
    formData.append('jam_mulai_ke',jamMulaiKe);
    formData.append('sks',sks);
    if(ruangan) formData.append('ruangan_id',ruangan);
    if(dosen) formData.append('dosen_id',dosen);
    if(kelas) formData.append('kelas_id',kelas);
    if(exclude) formData.append('exclude_id',exclude);
    
    fetch('api/jadwal.php', {
        method: 'POST',
        body: formData
    })
    .then(r=>r.json()).then(d=>{
        const infoDiv=document.getElementById('bentrokan-info');
        if(d.success&&d.bentrokan&&d.bentrokan.length>0){
            let html='<div style="background:#fee2e2;border:1px solid #fecaca;padding:10px;border-radius:8px"><strong style="color:#dc2626">Bentrokan:</strong><ul style="margin:5px 0 0 20px;color:#991b1b">';
            d.bentrokan.forEach(b=>{ html+=`<li>${b.pesan}</li>`; });
            html+='</ul></div>';
            infoDiv.innerHTML=html;
            infoDiv.style.display='block';
        }else{
            infoDiv.style.display='none';
        }
    });
}

function validateAndSubmit(){
    const info=document.getElementById('rentang-info').value;
    if(info.includes('Melewati')||info.includes('Melebihi')){
        toast('Perbaiki rentang jam terlebih dahulu','error');
        return;
    }
    if(document.getElementById('bentrokan-info').style.display==='block'){
        if(!confirm('Terdapat bentrokan jadwal. Tetap simpan?')) return;
    }
    saveJadwal();
}

function saveJadwal(){
    const form=document.getElementById('form-jadwal');
    const formData = new FormData(form);
    formData.append('action', formData.get('id') ? 'update' : 'create');
    
    fetch('api/jadwal.php', {
        method: 'POST',
        body: formData
    })
    .then(r=>r.json()).then(d=>{
        toast(d.msg,d.success?'success':'error');
        if(d.success){ 
            closeModal('modal-jadwal-form'); 
            resetFormJadwal();
            loadData(); 
        }
    });
}

function loadData(){
    const ta=document.getElementById('filter-ta').value;
    if(!ta){ document.getElementById('table-body').innerHTML='<tr><td colspan="10" style="text-align:center;padding:40px">Pilih Tahun Akademik</td></tr>'; return; }
    
    const formData = new FormData();
    formData.append('action','list');
    formData.append('tahun_akademik_id',ta);
    const hari=document.getElementById('filter-hari').value; if(hari) formData.append('hari',hari);
    const kelas=document.getElementById('filter-kelas').value; if(kelas) formData.append('kelas_id',kelas);
    const dosen=document.getElementById('filter-dosen').value; if(dosen) formData.append('dosen_id',dosen);
    
    fetch('api/jadwal.php', {
        method: 'POST',
        body: formData
    })
    .then(r=>r.json()).then(d=>{
        if(d.success) renderTable(d.data);
    });
}

function renderTable(data){
    const tbody=document.getElementById('table-body');
    if(!data.length){ tbody.innerHTML='<tr><td colspan="10" style="text-align:center;padding:40px">Tidak terdapat jadwal</td></tr>'; return; }
    
    const colors={'Senin':'pill-blue','Selasa':'pill-green','Rabu':'pill-ora','Kamis':'pill-purple','Jumat':'pill-red','Sabtu':'pill-gray'};
    let html='';
    data.forEach((j,i)=>{
        const sksClass=j.sks==3?'pill-ora':(j.sks>=4?'pill-purple':'pill-blue');
        html+=`<tr>
            <td>${i+1}</td>
            <td><span class="pill ${colors[j.hari]||'pill-blue'}">${j.hari}</span></td>
            <td><code>${j.jam_mulai?.substring(0,5)}–${j.jam_selesai?.substring(0,5)||'-'}</code></td>
            <td><span class="pill pill-gray">${j.jam_ke_list||j.jam_mulai_ke}</span></td>
            <td style="font-weight:700">${escapeHtml(j.nama_mk)}</td>
            <td><span class="pill ${sksClass}">${j.sks} SKS</span></td>
            <td>${escapeHtml(j.nama_dosen)}</td>
            <td>${escapeHtml(j.nama_kelas)}</td>
            <td><code>${escapeHtml(j.kode_ruangan)}</code><br><small>${escapeHtml(j.nama_ruangan)}</small></td>
            <td><div style="display:flex;gap:5px">
                <button class="btn btn-secondary btn-xs" onclick="editJadwal(${j.id})">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3l4 4L7 21H3v-4L17 3z"/></svg>
                    Edit
                </button>
                <button class="btn btn-danger btn-xs" onclick="deleteJadwal(${j.id},'${escapeHtml(j.hari)} / ${escapeHtml(j.nama_kelas)} / ${escapeHtml(j.nama_mk)}')">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    Hapus
                </button>
            </div></td>
        </tr>`;
    });
    tbody.innerHTML=html;
}

function resetFilter(){ 
    document.getElementById('filter-hari').value=''; 
    document.getElementById('filter-kelas').value=''; 
    document.getElementById('filter-dosen').value=''; 
    loadData(); 
}

function editJadwal(id){
    fetch('api/jadwal.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get&id='+id
    })
    .then(r=>r.json()).then(d=>{
        if(d.success){
            const j=d.data;
            document.getElementById('modal-jadwal-form-title').textContent='Edit Jadwal';
            document.getElementById('jadwal-id').value=j.id;
            document.getElementById('jadwal-ta').value=j.tahun_akademik_id;
            document.getElementById('jadwal-hari').value=j.hari;
            document.getElementById('jadwal-kelas').value=j.kelas_id;
            document.getElementById('jadwal-mk').value=j.mata_kuliah_id;
            document.getElementById('jadwal-dosen').value=j.dosen_id;
            document.getElementById('jadwal-ruangan').value=j.ruangan_id;
            document.getElementById('jadwal-jam-mulai').value=j.jam_ke_id;
            hitungRentangJam();
            openModal('modal-jadwal-form');
        }
    });
}

function deleteJadwal(id,info){ 
    deleteQueue={id,info}; 
    document.getElementById('confirm-desc').innerHTML=`<strong>${escapeHtml(info)}</strong>`; 
    document.getElementById('confirm-btn').onclick=()=>{
        fetch('api/jadwal.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=delete&id='+id
        })
        .then(r=>r.json()).then(d=>{ 
            toast(d.msg,d.success?'success':'error'); 
            if(d.success){ closeModal('modal-confirm'); loadData(); } 
        });
    }; 
    openModal('modal-confirm'); 
}

function printJadwal() {
    const ta = document.getElementById('filter-ta').value;
    
    if (!ta) {
        toast('Pilih Tahun Akademik terlebih dahulu', 'error');
        return;
    }
    
    const taSelect = document.getElementById('filter-ta');
    const taText = taSelect.options[taSelect.selectedIndex].text;
    
    const hari = document.getElementById('filter-hari').value;
    const kelas = document.getElementById('filter-kelas').value;
    const dosen = document.getElementById('filter-dosen').value;
    
    document.getElementById('print-content').innerHTML = '<div style="text-align:center;padding:40px">Memuat data...</div>';
    openModal('modal-print');
    
    const formData = new FormData();
    formData.append('action', 'get_print_data');
    formData.append('tahun_akademik_id', ta);
    if (hari) formData.append('hari', hari);
    if (kelas) formData.append('kelas_id', kelas);
    if (dosen) formData.append('dosen_id', dosen);
    
    fetch('api/jadwal.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            renderPrintContent(d.data, taText, hari, kelas, dosen);
        } else {
            document.getElementById('print-content').innerHTML = '<div style="text-align:center;padding:40px;color:red">Gagal memuat data</div>';
        }
    });
}

function renderPrintContent(data, taText, filterHari, filterKelas, filterDosen) {
    const grouped = {};
    ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'].forEach(h => grouped[h] = []);
    
    data.forEach(j => {
        if (grouped[j.hari]) grouped[j.hari].push(j);
    });
    
    let filterInfo = [];
    if (filterHari) filterInfo.push(`Hari: ${filterHari}`);
    if (filterKelas) {
        const kelasSelect = document.getElementById('filter-kelas');
        filterInfo.push(`Kelas: ${kelasSelect.options[kelasSelect.selectedIndex].text}`);
    }
    if (filterDosen) {
        const dosenSelect = document.getElementById('filter-dosen');
        filterInfo.push(`Dosen: ${dosenSelect.options[dosenSelect.selectedIndex].text}`);
    }
    const filterText = filterInfo.length > 0 ? filterInfo.join(' | ') : 'Semua';
    
    let html = `
        <div style="padding:20px;font-family:'Inter',sans-serif">
            <div style="text-align:center;margin-bottom:20px">
                <h2 style="margin:0 0 5px;font-size:20px">JADWAL PERKULIAHAN</h2>
                <h3 style="margin:0 0 5px;font-size:16px;color:#555">${escapeHtml(taText)}</h3>
                <p style="margin:0;font-size:12px;color:#777">${escapeHtml(filterText)}</p>
            </div>
            <table style="width:100%;border-collapse:collapse;font-size:12px">
                <thead>
                    <tr style="background:#f5f5f5">
                        <th style="border:1px solid #ddd;padding:8px">Hari</th>
                        <th style="border:1px solid #ddd;padding:8px">Jam</th>
                        <th style="border:1px solid #ddd;padding:8px">Jam Ke-</th>
                        <th style="border:1px solid #ddd;padding:8px">Mata Kuliah</th>
                        <th style="border:1px solid #ddd;padding:8px">SKS</th>
                        <th style="border:1px solid #ddd;padding:8px">Dosen</th>
                        <th style="border:1px solid #ddd;padding:8px">Kelas</th>
                        <th style="border:1px solid #ddd;padding:8px">Ruangan</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    for (let hari in grouped) {
        const jadwals = grouped[hari];
        if (jadwals.length === 0) continue;
        
        jadwals.sort((a, b) => (a.jam_mulai || '').localeCompare(b.jam_mulai || ''));
        
        jadwals.forEach((j, idx) => {
            html += `<tr>
                <td style="border:1px solid #ddd;padding:6px">${idx === 0 ? hari : ''}</td>
                <td style="border:1px solid #ddd;padding:6px">${(j.jam_mulai || '').substring(0,5)}-${(j.jam_selesai || '').substring(0,5)}</td>
                <td style="border:1px solid #ddd;padding:6px;text-align:center">${j.jam_ke_list || j.jam_mulai_ke || '-'}</td>
                <td style="border:1px solid #ddd;padding:6px;font-weight:600">${escapeHtml(j.nama_mk || '-')}</td>
                <td style="border:1px solid #ddd;padding:6px;text-align:center">${j.sks || '-'}</td>
                <td style="border:1px solid #ddd;padding:6px">${escapeHtml(j.nama_dosen || '-')}</td>
                <td style="border:1px solid #ddd;padding:6px">${escapeHtml(j.nama_kelas || '-')}</td>
                <td style="border:1px solid #ddd;padding:6px">${escapeHtml(j.nama_ruangan || '-')}</td>
            </tr>`;
        });
    }
    
    html += `</tbody>
            </table>
        <div style="margin-top:20px;font-size:11px;color:#999;text-align:right">
            Dicetak: ${new Date().toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'})}
        </div>
    </div>`;
    
    document.getElementById('print-content').innerHTML = html;
}

function doPrint() {
    const content = document.getElementById('print-content').innerHTML;
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Cetak Jadwal</title>
            <style>
                body { font-family: 'Inter', sans-serif; margin: 20px; }
                h2, h3 { margin: 5px 0; }
                table { width: 100%; border-collapse: collapse; font-size: 11px; }
                th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
                th { background: #f5f5f5; }
                @media print {
                    body { margin: 10px; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>${content}</body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}

function exportCSV() {
    const ta = document.getElementById('filter-ta').value;
    
    if (!ta) {
        toast('Pilih Tahun Akademik terlebih dahulu', 'error');
        return;
    }
    
    const hari = document.getElementById('filter-hari').value;
    const kelas = document.getElementById('filter-kelas').value;
    const dosen = document.getElementById('filter-dosen').value;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api/jadwal.php';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'export_csv';
    form.appendChild(actionInput);
    
    const taInput = document.createElement('input');
    taInput.type = 'hidden';
    taInput.name = 'tahun_akademik_id';
    taInput.value = ta;
    form.appendChild(taInput);
    
    if (hari) {
        const hariInput = document.createElement('input');
        hariInput.type = 'hidden';
        hariInput.name = 'hari';
        hariInput.value = hari;
        form.appendChild(hariInput);
    }
    
    if (kelas) {
        const kelasInput = document.createElement('input');
        kelasInput.type = 'hidden';
        kelasInput.name = 'kelas_id';
        kelasInput.value = kelas;
        form.appendChild(kelasInput);
    }
    
    if (dosen) {
        const dosenInput = document.createElement('input');
        dosenInput.type = 'hidden';
        dosenInput.name = 'dosen_id';
        dosenInput.value = dosen;
        form.appendChild(dosenInput);
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    toast('Ekspor CSV sedang diproses...', 'info');
}

// ════════════════════════════════════════════════
// UTILITY FUNCTIONS
// ════════════════════════════════════════════════
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;')
              .replace(/"/g, '&quot;')
              .replace(/'/g, '&#39;');
}

function openModal(id) { 
    document.getElementById(id).classList.add('active'); 
}

function closeModal(id){ 
    document.getElementById(id).classList.remove('active'); 
    if (id === 'modal-jadwal-form') {
        resetFormJadwal();
    }
}

function toast(m,t='info',d=3000){ 
    const el=document.createElement('div'); 
    el.style.cssText=`position:fixed;bottom:20px;right:20px;padding:12px 20px;background:${t=='success'?'#10b981':t=='error'?'#ef4444':'#3b82f6'};color:#fff;border-radius:8px;z-index:10000;animation:slideIn 0.3s ease;`; 
    el.textContent=m; 
    document.body.appendChild(el); 
    setTimeout(()=>{
        el.style.animation='slideOut 0.3s ease';
        setTimeout(()=>el.remove(),300);
    },d); 
}

// Tutup modal saat klik overlay
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal(this.id);
        }
    });
});

// Tutup modal saat tekan ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(modal => {
            closeModal(modal.id);
        });
    }
});
</script>

<?php require_once 'footer.php'; ?>