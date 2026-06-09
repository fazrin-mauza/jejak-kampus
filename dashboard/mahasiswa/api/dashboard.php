<?php
require_once __DIR__ . '/../../../auth/check.php';
header('Content-Type: application/json');

$user_id = $user['id'] ?? 0;
$mahasiswa_id = 0;
$kelas_id = 0;

if ($user_id) {
    $q = $conn->query("SELECT id, kelas_id FROM mahasiswa WHERE user_id = $user_id LIMIT 1");
    if ($q && $row = $q->fetch_assoc()) {
        $mahasiswa_id = $row['id'];
        $kelas_id = $row['kelas_id'];
    }
}

if (!$mahasiswa_id) {
    echo json_encode(['success' => false, 'msg' => 'Data mahasiswa tidak ditemukan']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get_dashboard') {
    
    $result = ['success' => true];
    
    // ========== 1. DATA MAHASISWA ==========
    $mhs = $conn->query("SELECT m.nim, m.nama, k.nama_kelas as kelas 
                         FROM mahasiswa m 
                         LEFT JOIN kelas k ON m.kelas_id = k.id 
                         WHERE m.id = $mahasiswa_id");
    $result['mahasiswa'] = $mhs->fetch_assoc();
    
    // ========== 2. TAHUN AKADEMIK ==========
    $ta = $conn->query("SELECT tahun, semester FROM tahun_akademik WHERE status = 'aktif' LIMIT 1");
    $ta_data = $ta->fetch_assoc();
    $result['tahun_akademik'] = ($ta_data['tahun'] ?? '2025/2026') . ' ' . ($ta_data['semester'] ?? 'Genap');
    
    // ========== 3. STATISTIK ==========
    $sks = $conn->query("SELECT SUM(mk.sks) as total FROM jadwal j JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id WHERE j.kelas_id = $kelas_id");
    $total_sks = $sks->fetch_assoc()['total'] ?? 0;
    
    $mk = $conn->query("SELECT COUNT(DISTINCT mata_kuliah_id) as total FROM jadwal WHERE kelas_id = $kelas_id");
    $total_mk = $mk->fetch_assoc()['total'] ?? 0;
    
    $sesi = $conn->query("SELECT COUNT(*) as total FROM sesi_absensi s JOIN jadwal j ON s.jadwal_id = j.id WHERE j.kelas_id = $kelas_id");
    $total_sesi = $sesi->fetch_assoc()['total'] ?? 0;
    
    $hadir = $conn->query("SELECT COUNT(*) as total FROM absensi a 
                           JOIN sesi_absensi s ON a.sesi_id = s.id 
                           JOIN jadwal j ON s.jadwal_id = j.id 
                           WHERE j.kelas_id = $kelas_id AND a.mahasiswa_id = $mahasiswa_id 
                           AND a.status IN ('hadir', 'telat')");
    $total_hadir = $hadir->fetch_assoc()['total'] ?? 0;
    
    $persen = $total_sesi > 0 ? round(($total_hadir / $total_sesi) * 100) : 0;
    
    $min = $conn->query("SELECT min_kehadiran_persen FROM settings LIMIT 1");
    $min_kehadiran = $min->fetch_assoc()['min_kehadiran_persen'] ?? 75;
    
    $alpha_q = $conn->query("SELECT COUNT(*) as total FROM absensi a 
                             JOIN sesi_absensi s ON a.sesi_id = s.id 
                             JOIN jadwal j ON s.jadwal_id = j.id 
                             WHERE j.kelas_id = $kelas_id AND a.mahasiswa_id = $mahasiswa_id AND a.status = 'alpha'");
    $alpha = $alpha_q->fetch_assoc()['total'] ?? 0;
    
    $izin_q = $conn->query("SELECT COUNT(*) as total FROM izin WHERE mahasiswa_id = $mahasiswa_id AND status = 'disetujui'");
    $izin = $izin_q->fetch_assoc()['total'] ?? 0;
    
    $result['statistik'] = [
        'total_sks' => (int)$total_sks,
        'total_mk' => (int)$total_mk,
        'total_kehadiran' => (int)$total_hadir,
        'total_sesi' => (int)$total_sesi,
        'persen_kehadiran' => $persen,
        'min_kehadiran' => (float)$min_kehadiran,
        'di_atas_batas' => $persen >= $min_kehadiran,
        'alpha' => (int)$alpha,
        'izin' => (int)$izin
    ];
    
    // ========== 4. SESI AKTIF ==========
    $aktif = $conn->query("SELECT s.id as sesi_id, s.pertemuan_ke, mk.nama_mk, d.nama as nama_dosen, r.nama_ruangan,
                                  jk.jam_mulai, jk.jam_selesai
                           FROM sesi_absensi s
                           JOIN jadwal j ON s.jadwal_id = j.id
                           JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
                           JOIN dosen d ON j.dosen_id = d.id
                           LEFT JOIN ruangan r ON j.ruangan_id = r.id
                           LEFT JOIN jam_ke jk ON j.jam_ke_id = jk.id
                           WHERE j.kelas_id = $kelas_id AND s.status = 'aktif'
                           LIMIT 1");
    $result['sesi_aktif'] = $aktif->num_rows > 0 ? $aktif->fetch_assoc() : null;
    
    // ========== 5. JADWAL HARI INI (PASTIKAN FORMATNYA) ==========
    $hari_map = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
    $hari_ini = $hari_map[date('N')];
    
    $jadwal_today = $conn->query("SELECT j.id, mk.nama_mk, d.nama as nama_dosen, r.nama_ruangan,
                                         jk.jam_mulai, jk.jam_selesai,
                                         (SELECT MAX(pertemuan_ke) FROM sesi_absensi WHERE jadwal_id = j.id) as pertemuan_terakhir
                                  FROM jadwal j
                                  JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
                                  JOIN dosen d ON j.dosen_id = d.id
                                  LEFT JOIN ruangan r ON j.ruangan_id = r.id
                                  LEFT JOIN jam_ke jk ON j.jam_ke_id = jk.id
                                  WHERE j.kelas_id = $kelas_id AND j.hari = '$hari_ini'
                                  ORDER BY jk.jam_mulai");
    
    $result['jadwal_hari_ini'] = [];
    while ($row = $jadwal_today->fetch_assoc()) {
        $result['jadwal_hari_ini'][] = $row;
    }
    
    // ========== 6. RIWAYAT TERBARU ==========
    $riwayat = $conn->query("SELECT s.tanggal, mk.nama_mk, s.pertemuan_ke, a.status, a.waktu_absen
                             FROM sesi_absensi s
                             JOIN jadwal j ON s.jadwal_id = j.id
                             JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
                             LEFT JOIN absensi a ON a.sesi_id = s.id AND a.mahasiswa_id = $mahasiswa_id
                             WHERE j.kelas_id = $kelas_id
                             ORDER BY s.tanggal DESC, s.pertemuan_ke DESC
                             LIMIT 5");
    
    $result['riwayat_terbaru'] = [];
    while ($row = $riwayat->fetch_assoc()) {
        // Jika belum absen, cek izin
        if (is_null($row['status'])) {
            $cek_izin = $conn->query("SELECT i.id FROM izin i 
                WHERE i.mahasiswa_id = $mahasiswa_id 
                AND i.sesi_id = (SELECT id FROM sesi_absensi WHERE tanggal = '{$row['tanggal']}' AND pertemuan_ke = {$row['pertemuan_ke']} LIMIT 1)
                AND i.status = 'disetujui' LIMIT 1");
            if ($cek_izin && $cek_izin->num_rows > 0) {
                $row['status'] = 'izin';
            } else {
                $row['status'] = 'alpha';
            }
        }
        $result['riwayat_terbaru'][] = $row;
    }
    
    // ========== 7. NOTIFIKASI ==========
    $result['notifikasi'] = [];
    
    // Notifikasi izin disetujui
    $notif = $conn->query("SELECT i.jenis, i.pertemuan_ke, mk.nama_mk
                           FROM izin i
                           LEFT JOIN jadwal j ON i.jadwal_id = j.id
                           LEFT JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
                           WHERE i.mahasiswa_id = $mahasiswa_id AND i.status = 'disetujui'
                           ORDER BY i.created_at DESC LIMIT 3");
    while ($row = $notif->fetch_assoc()) {
        $result['notifikasi'][] = [
            'type' => 'izin',
            'title' => 'Izin ' . ($row['jenis'] == 'sakit' ? 'Sakit' : 'Izin') . ' Disetujui',
            'message' => ($row['nama_mk'] ? $row['nama_mk'] . ' • ' : '') . 'Pertemuan ke-' . $row['pertemuan_ke']
        ];
    }
    
    // Notifikasi sesi aktif
    if ($result['sesi_aktif']) {
        $result['notifikasi'][] = [
            'type' => 'sesi_aktif',
            'title' => 'Sesi Absensi Aktif',
            'message' => $result['sesi_aktif']['nama_mk'] . ' • Pertemuan ke-' . $result['sesi_aktif']['pertemuan_ke']
        ];
    }
    
    echo json_encode($result);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Aksi tidak dikenal']);
?>