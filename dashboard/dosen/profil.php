<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/navigasi.php';

// Ambil data dosen dari session
$nama_dosen = $user['nama'] ?? 'Dosen';
$user_id = $user['id'] ?? 0;

// Ambil data lengkap dosen
$dosen_id = 0;
$dosen_data = null;
$nidn = '-';
$email = '-';
$tanggal_lahir = '';
$jenis_kelamin = 'L';
$profile_pic = '';
$total_sks = 0;
$total_kelas = 0;

if ($user_id) {
    $stmt = $conn->prepare("SELECT d.id, d.nama, d.nidn, d.tanggal_lahir, d.jenis_kelamin, d.status, u.email, u.profile 
                            FROM dosen d 
                            JOIN users u ON d.user_id = u.id 
                            WHERE d.user_id = ? LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $dosen_data = $result->fetch_assoc();
    $stmt->close();
    
    if ($dosen_data) {
        $dosen_id = $dosen_data['id'];
        $nama_dosen = $dosen_data['nama'] ?: $nama_dosen;
        $nidn = $dosen_data['nidn'] ?? '-';
        $email = $dosen_data['email'] ?? '-';
        $tanggal_lahir = $dosen_data['tanggal_lahir'] ?? '';
        $jenis_kelamin = $dosen_data['jenis_kelamin'] ?? 'L';
        $profile_pic = $dosen_data['profile'] ?? '';
    }
    
    if ($dosen_id) {
        $stmtSks = $conn->prepare("SELECT COALESCE(SUM(mk.sks), 0), COUNT(DISTINCT j.kelas_id) 
                                   FROM jadwal j 
                                   JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id 
                                   JOIN tahun_akademik ta ON j.tahun_akademik_id = ta.id 
                                   WHERE j.dosen_id = ? AND ta.status = 'aktif'");
        $stmtSks->bind_param('i', $dosen_id);
        $stmtSks->execute();
        $stmtSks->bind_result($total_sks, $total_kelas);
        $stmtSks->fetch();
        $stmtSks->close();
    }
}

$semester_aktif = 'Semester Aktif';
$stmt = $conn->query("SELECT CONCAT(tahun, ' ', semester) as nama FROM tahun_akademik WHERE status = 'aktif' LIMIT 1");
if ($stmt && $row = $stmt->fetch_assoc()) {
    $semester_aktif = $row['nama'];
}

$status_dosen = $dosen_data['status'] ?? 'aktif';
$status_text = $status_dosen == 'aktif' ? 'Dosen Aktif' : 'Dosen Nonaktif';
$status_class = $status_dosen == 'aktif' ? 'pill-green' : 'pill-gray';

$nama_parts = explode(' ', trim($nama_dosen));
$initial = strtoupper(substr($nama_parts[0], 0, 1));
$initial .= isset($nama_parts[1]) ? strtoupper(substr($nama_parts[1], 0, 1)) : '';
if (empty($initial) || strlen($initial) < 1) $initial = strtoupper(substr($nama_dosen, 0, 1));
?>

<div class="section active" id="sec-profil">
  <div class="page-header">
    <div>
      <div class="page-subtitle">Kelola data diri dan preferensi akun</div>
    </div>
  </div>

  <div class="grid-2" style="align-items:start">
    <!-- Kolom Kiri -->
    <div style="display:flex;flex-direction:column;gap:18px">
      <!-- Kartu Profil -->
      <div class="card" style="text-align:center">
        <!-- Avatar -->
        <div id="avatar-wrapper" style="position:relative;width:80px;height:80px;margin:0 auto 16px;cursor:pointer" onclick="document.getElementById('input-foto').click()" title="Klik untuk mengganti foto">
          <?php if ($profile_pic): ?>
            <img id="avatar-img" src="<?= htmlspecialchars($profile_pic) ?>" alt="Foto Profil" style="width:80px;height:80px;border-radius:50%;object-fit:cover;display:block">
          <?php else: ?>
            <div id="avatar-initial" style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--ora1),var(--ora3));display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:#fff">
              <?= htmlspecialchars($initial) ?>
            </div>
          <?php endif; ?>
          <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.55);color:#fff;font-size:9px;padding:5px 0;text-align:center;border-radius:0 0 50% 50%;opacity:0;transition:opacity 0.2s;line-height:1" class="avatar-overlay">
            Ganti
          </div>
        </div>
        <input type="file" id="input-foto" accept="image/*" style="display:none" onchange="uploadFoto(event)">
        
        <div style="font-size:16px;font-weight:800"><?= htmlspecialchars($nama_dosen) ?></div>
        <div style="font-size:12px;color:var(--text3);margin-top:4px">NIDN: <?= htmlspecialchars($nidn) ?></div>
        <div style="margin-top:6px">
          <span class="pill <?= $status_class ?>">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <?= $status_text ?>
          </span>
        </div>
      </div>

      <!-- Form Data Diri -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px;">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Data Diri
          </div>
        </div>
        <form id="form-profil" onsubmit="simpanProfil(event)">
          <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama" value="<?= htmlspecialchars($nama_dosen) ?>" required></div>
          <div class="form-group"><label>NIDN</label><input type="text" value="<?= htmlspecialchars($nidn) ?>" readonly></div>
          <div class="form-group"><label>Email</label><input type="email" value="<?= htmlspecialchars($email) ?>" readonly></div>
          <div class="form-row">
            <div class="form-group"><label>Tanggal Lahir</label><input type="date" name="tanggal_lahir" value="<?= htmlspecialchars($tanggal_lahir) ?>"></div>
            <div class="form-group"><label>Jenis Kelamin</label><select name="jenis_kelamin"><option value="L" <?= $jenis_kelamin=='L'?'selected':'' ?>>Laki-laki</option><option value="P" <?= $jenis_kelamin=='P'?'selected':'' ?>>Perempuan</option></select></div>
          </div>
          <button type="submit" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
            Simpan Perubahan
          </button>
        </form>
      </div>
    </div>

    <!-- Kolom Kanan -->
    <div style="display:flex;flex-direction:column;gap:18px">
      <!-- Info Akademik -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:6px"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c0 1.1 2.7 2 6 2s6-.9 6-2v-5"></path></svg>
            Informasi Akademik
          </div>
        </div>
        <div class="detail-row"><div class="detail-label">Semester Aktif</div><div class="detail-val"><?= htmlspecialchars($semester_aktif) ?></div></div>
        <div class="detail-row"><div class="detail-label">Status Dosen</div><div class="detail-val"><?= ucfirst($status_dosen) ?></div></div>
        <div class="detail-row"><div class="detail-label">Total SKS</div><div class="detail-val"><?= $total_sks ?> SKS</div></div>
        <div class="detail-row"><div class="detail-label">Total Kelas</div><div class="detail-val"><?= $total_kelas ?> Kelas</div></div>
      </div>

      <!-- Akun Google -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:6px"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="4"></circle><line x1="21.17" y1="8" x2="12" y2="8"></line><line x1="3.95" y1="6.06" x2="8.54" y2="14"></line><line x1="10.88" y1="21.94" x2="15.46" y2="14"></line><line x1="21.17" y1="16" x2="12" y2="16"></line><line x1="3.95" y1="17.94" x2="8.54" y2="10"></line></svg>
            Akun Google
          </div>
        </div>
        <div class="detail-row"><div class="detail-label">Email Google</div><div class="detail-val"><?= htmlspecialchars($email) ?></div></div>
        <div class="detail-row"><div class="detail-label">Metode Masuk</div><div class="detail-val">Google Single Sign-On</div></div>
      </div>
    </div>
  </div>
</div>

<script>
function uploadFoto(event) {
  const file = event.target.files[0];
  if (!file) return;
  if (!file.type.match('image.*')) { showNotification('File harus berupa gambar','kesalahan'); return; }
  if (file.size > 2*1024*1024) { showNotification('Ukuran file maksimal 2MB','kesalahan'); return; }
  
  const formData = new FormData();
  formData.append('action','upload_foto');
  formData.append('foto',file);
  showNotification('Mengunggah foto...','informasi');
  
  fetch('api/profil.php',{method:'POST',body:formData})
  .then(r=>r.json()).then(resp=>{
    showNotification(resp.msg,resp.success?'berhasil':'kesalahan');
    if(resp.success){
      const wrapper = document.getElementById('avatar-wrapper');
      const initial = document.getElementById('avatar-initial');
      let img = document.getElementById('avatar-img');
      if(initial) initial.remove();
      if(!img){
        img = document.createElement('img');
        img.id = 'avatar-img';
        img.style.cssText = 'width:80px;height:80px;border-radius:50%;object-fit:cover;display:block';
        wrapper.insertBefore(img, wrapper.firstChild);
      }
      img.src = resp.url + '?t=' + Date.now();
    }
  });
}

function simpanProfil(event) {
  event.preventDefault();
  const fd = new FormData(event.target);
  fd.append('action','update_profil');
  fetch('api/profil.php',{method:'POST',body:fd})
  .then(r=>r.json()).then(resp=>{
    showNotification(resp.msg,resp.success?'berhasil':'kesalahan');
    if(resp.success) setTimeout(()=>location.reload(),1000);
  });
}

function showNotification(m,t='informasi'){
  const c={berhasil:'#059669',kesalahan:'#dc2626',informasi:'#2563eb'};
  const bg=c[t]||c['informasi'];
  const ex=document.querySelector('.custom-notification');
  if(ex) ex.remove();
  const el=document.createElement('div');
  el.className='custom-notification';
  el.style.cssText=`position:fixed;top:20px;right:20px;padding:14px 24px;background:${bg};color:#fff;border-radius:10px;z-index:10000;box-shadow:0 8px 24px rgba(0,0,0,0.2);font-size:14px;font-weight:600;max-width:400px;word-wrap:break-word;animation:slideInRight .3s ease-out`;
  const i={berhasil:'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> ',kesalahan:'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> ',informasi:'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg> '};
  el.innerHTML=(i[t]||i['informasi'])+m;
  document.body.appendChild(el);
  setTimeout(()=>{el.style.animation='slideOutRight .3s ease-in';setTimeout(()=>el.remove(),300)},3500);
}

const s=document.createElement('style');
s.textContent=`@keyframes slideInRight{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}@keyframes slideOutRight{from{transform:translateX(0);opacity:1}to{transform:translateX(100%);opacity:0}}#avatar-wrapper:hover .avatar-overlay{opacity:1!important}`;
document.head.appendChild(s);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>