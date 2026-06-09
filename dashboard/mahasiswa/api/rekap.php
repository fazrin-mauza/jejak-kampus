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

if ($action === 'get_rekap') {
    
    $result = ['success' => true];
    
    // Get settings min kehadiran
    $min = $conn->query("SELECT min_kehadiran_persen FROM settings LIMIT 1");
    $min_kehadiran = $min->fetch_assoc()['min_kehadiran_persen'] ?? 75;
    $result['min_kehadiran'] = (float)$min_kehadiran;
    
    // Get tahun akademik aktif
    $ta = $conn->query("SELECT tahun, semester FROM tahun_akademik WHERE status = 'aktif' LIMIT 1");
    $ta_data = $ta->fetch_assoc();
    $result['tahun_akademik'] = ($ta_data['tahun'] ?? '2025/2026') . ' ' . ($ta_data['semester'] ?? 'Genap');
    
    // Get semua mata kuliah mahasiswa
    $mk_sql = "SELECT DISTINCT j.mata_kuliah_id, mk.kode_mk, mk.nama_mk, mk.sks, d.nama as nama_dosen,
                      d.id as dosen_id
               FROM jadwal j
               JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
               JOIN dosen d ON j.dosen_id = d.id
               WHERE j.kelas_id = $kelas_id
               ORDER BY mk.nama_mk";
    
    $mk_res = $conn->query($mk_sql);
    $mata_kuliah_list = [];
    
    // Statistik global
    $total_hadir_global = 0;
    $total_sesi_global = 0;
    $total_izin_global = 0;
    $total_sakit_global = 0;
    $total_alpha_global = 0;
    $total_mk_lulus = 0;
    $total_mk_risiko = 0;
    $mk_terpengaruh_izin = [];
    
    while ($mk = $mk_res->fetch_assoc()) {
        $mk_id = $mk['mata_kuliah_id'];
        
        // Get semua sesi untuk MK ini
        $sesi_sql = "SELECT s.id as sesi_id, s.pertemuan_ke, s.tanggal,
                            a.status as absen_status, a.waktu_absen,
                            i.status as izin_status, i.jenis as izin_jenis
                     FROM sesi_absensi s
                     JOIN jadwal j ON s.jadwal_id = j.id
                     LEFT JOIN absensi a ON a.sesi_id = s.id AND a.mahasiswa_id = $mahasiswa_id
                     LEFT JOIN izin i ON i.sesi_id = s.id AND i.mahasiswa_id = $mahasiswa_id
                     WHERE j.mata_kuliah_id = $mk_id AND j.kelas_id = $kelas_id
                     ORDER BY s.pertemuan_ke ASC";
        
        $sesi_res = $conn->query($sesi_sql);
        $detail_pertemuan = [];
        $hadir = 0;
        $izin = 0;
        $sakit = 0;
        $alpha = 0;
        $total = 0;
        
        while ($sesi = $sesi_res->fetch_assoc()) {
            $total++;
            $status = 'belum';
            
            if ($sesi['absen_status'] == 'hadir' || $sesi['absen_status'] == 'telat') {
                $status = 'hadir';
                $hadir++;
            } elseif ($sesi['izin_status'] == 'disetujui') {
                if ($sesi['izin_jenis'] == 'sakit') {
                    $status = 'sakit';
                    $sakit++;
                } else {
                    $status = 'izin';
                    $izin++;
                }
                if (!in_array($mk['nama_mk'], $mk_terpengaruh_izin)) {
                    $mk_terpengaruh_izin[] = $mk['nama_mk'];
                }
            } elseif ($sesi['absen_status'] == 'alpha') {
                $status = 'alpha';
                $alpha++;
            } elseif ($sesi['absen_status'] === null && $sesi['izin_status'] === null) {
                $status = 'alpha';
                $alpha++;
            }
            
            $detail_pertemuan[] = [
                'pertemuan_ke' => $sesi['pertemuan_ke'],
                'tanggal' => $sesi['tanggal'],
                'status' => $status,
                'waktu_absen' => $sesi['waktu_absen'],
                'keterangan' => ($sesi['izin_jenis'] == 'sakit' ? 'Sakit' : ($sesi['izin_jenis'] == 'izin' ? 'Izin' : ''))
            ];
        }
        
        $persen = $total > 0 ? round(($hadir / $total) * 100) : 0;
        $lulus = $persen >= $min_kehadiran;
        
        if ($lulus) {
            $total_mk_lulus++;
        } else {
            $total_mk_risiko++;
        }
        
        $total_hadir_global += $hadir;
        $total_sesi_global += $total;
        $total_izin_global += $izin;
        $total_sakit_global += $sakit;
        $total_alpha_global += $alpha;
        
        $mata_kuliah_list[] = [
            'id' => $mk_id,
            'kode_mk' => $mk['kode_mk'],
            'nama_mk' => $mk['nama_mk'],
            'sks' => (int)$mk['sks'],
            'nama_dosen' => $mk['nama_dosen'],
            'dosen_id' => $mk['dosen_id'],
            'total_pertemuan' => $total,
            'hadir' => $hadir,
            'izin' => $izin,
            'sakit' => $sakit,
            'alpha' => $alpha,
            'persen_kehadiran' => $persen,
            'lulus' => $lulus,
            'detail_pertemuan' => $detail_pertemuan
        ];
    }
    
    $persen_global = $total_sesi_global > 0 ? round(($total_hadir_global / $total_sesi_global) * 100) : 0;
    
    $result['statistik_global'] = [
        'total_hadir' => $total_hadir_global,
        'total_sesi' => $total_sesi_global,
        'total_izin' => $total_izin_global,
        'total_sakit' => $total_sakit_global,
        'total_alpha' => $total_alpha_global,
        'persen_kehadiran' => $persen_global,
        'min_kehadiran' => (float)$min_kehadiran,
        'total_mk' => count($mata_kuliah_list),
        'total_mk_lulus' => $total_mk_lulus,
        'total_mk_risiko' => $total_mk_risiko,
        'mk_terpengaruh_izin' => $mk_terpengaruh_izin
    ];
    
    $result['mata_kuliah'] = $mata_kuliah_list;
    
    echo json_encode($result);
    exit;
}

if ($action === 'export_pdf') {
    // Generate HTML untuk ekspor
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Rekap Kehadiran</title>
        <style>
            body { font-family: "Segoe UI", Arial, sans-serif; padding: 30px; }
            .header { text-align: center; margin-bottom: 30px; }
            .header h1 { margin: 0; color: #1f2937; }
            .header p { color: #6b7280; margin: 5px 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #e5e7eb; padding: 8px 12px; text-align: left; }
            th { background: #f3f4f6; }
            .footer { margin-top: 30px; text-align: center; font-size: 11px; color: #9ca3af; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Rekap Kehadiran Mahasiswa</h1>
            <p>Dicetak pada: ' . date('d/m/Y H:i:s') . '</p>
        </div>
        <table>
            <thead>
                <tr><th>Mata Kuliah</th><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Alpha</th><th>Total</th><th>Persentase</th></tr>
            </thead>
            <tbody>';
    
    $mk_sql = "SELECT DISTINCT j.mata_kuliah_id, mk.nama_mk, mk.sks
               FROM jadwal j
               JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id
               WHERE j.kelas_id = (SELECT kelas_id FROM mahasiswa WHERE id = $mahasiswa_id)";
    $mk_res = $conn->query($mk_sql);
    
    while ($mk = $mk_res->fetch_assoc()) {
        $mk_id_val = $mk['mata_kuliah_id'];
        $sesi_sql = "SELECT COUNT(*) as total, 
                            SUM(CASE WHEN a.status IN ('hadir','telat') THEN 1 ELSE 0 END) as hadir,
                            SUM(CASE WHEN i.status = 'disetujui' AND i.jenis = 'izin' THEN 1 ELSE 0 END) as izin,
                            SUM(CASE WHEN i.status = 'disetujui' AND i.jenis = 'sakit' THEN 1 ELSE 0 END) as sakit,
                            SUM(CASE WHEN a.status = 'alpha' OR (a.status IS NULL AND i.status IS NULL) THEN 1 ELSE 0 END) as alpha
                     FROM sesi_absensi s
                     JOIN jadwal j ON s.jadwal_id = j.id
                     LEFT JOIN absensi a ON a.sesi_id = s.id AND a.mahasiswa_id = $mahasiswa_id
                     LEFT JOIN izin i ON i.sesi_id = s.id AND i.mahasiswa_id = $mahasiswa_id
                     WHERE j.mata_kuliah_id = $mk_id_val";
        $sesi_res = $conn->query($sesi_sql);
        $data = $sesi_res->fetch_assoc();
        $total = $data['total'];
        $hadir = $data['hadir'];
        $persen = $total > 0 ? round(($hadir / $total) * 100) : 0;
        
        $html .= '<tr>
            <td>' . htmlspecialchars($mk['nama_mk']) . ' (' . $mk['sks'] . ' SKS)</td>
            <td>' . $hadir . '</td>
            <td>' . $data['izin'] . '</td>
            <td>' . $data['sakit'] . '</td>
            <td>' . $data['alpha'] . '</td>
            <td>' . $total . '</td>
            <td>' . $persen . '%</td>
        </tr>';
    }
    
    $html .= '</tbody>
        </table>
        <div class="footer">Sistem Informasi Absensi - Jejak Kampus</div>
    </body>
    </html>';
    
    echo json_encode(['success' => true, 'html' => $html]);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Aksi tidak dikenal']);
?>