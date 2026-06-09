<?php
require_once '../../../auth/check.php';

header('Content-Type: application/json');

if (!isset($_POST['action']) && !isset($_GET['action'])) {
    echo json_encode(['success' => false, 'msg' => 'Action parameter required']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── GET SETTINGS ──
if ($action === 'get_settings') {
    $query = "SELECT * FROM settings WHERE id = 1";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        // Default settings jika belum ada
        echo json_encode(['success' => true, 'data' => [
            'id' => 1,
            'app_name' => 'Jejak Kampus',
            'app_description' => 'Sistem Absensi Kampus Berbasis QR Code & Geolokasi',
            'latitude' => '-7.31628330',
            'longitude' => '112.72277730',
            'radius_absensi' => 500,
            'izin_max_hours_before' => 24,
            'izin_max_days_after' => 1,
            'izin_auto_approve_on_sesi_close' => 1,
            'min_kehadiran_persen' => 75,
            'app_version' => 'v2.0'
        ]]);
    }
    exit;
}

// ── UPDATE APP INFO (nama & deskripsi) ──
if ($action === 'update_app_info') {
    $app_name = isset($_POST['app_name']) ? trim($_POST['app_name']) : 'Jejak Kampus';
    $app_description = isset($_POST['app_description']) ? trim($_POST['app_description']) : '';
    
    $query = "UPDATE settings SET app_name = ?, app_description = ? WHERE id = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $app_name, $app_description);
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        echo json_encode(['success' => true, 'msg' => 'Informasi aplikasi berhasil disimpan']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Gagal menyimpan informasi aplikasi']);
    }
    exit;
}

// ── UPDATE SETTINGS (radius & min kehadiran) ──
if ($action === 'update_settings') {
    $radius = isset($_POST['radius_absensi']) ? (int)$_POST['radius_absensi'] : 500;
    $minKehadiran = isset($_POST['min_kehadiran_persen']) ? (float)$_POST['min_kehadiran_persen'] : 75;
    
    $query = "UPDATE settings SET radius_absensi = ?, min_kehadiran_persen = ? WHERE id = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('id', $radius, $minKehadiran);
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        echo json_encode(['success' => true, 'msg' => 'Pengaturan berhasil disimpan']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Gagal menyimpan pengaturan']);
    }
    exit;
}

// ── UPDATE COORDINATES ──
if ($action === 'update_coordinates') {
    $lat = isset($_POST['latitude']) ? $_POST['latitude'] : null;
    $lng = isset($_POST['longitude']) ? $_POST['longitude'] : null;
    
    if ($lat === null || $lng === null) {
        echo json_encode(['success' => false, 'msg' => 'Latitude dan Longitude harus diisi']);
        exit;
    }
    
    $query = "UPDATE settings SET latitude = ?, longitude = ? WHERE id = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $lat, $lng);
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        echo json_encode(['success' => true, 'msg' => 'Koordinat berhasil disimpan']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Gagal menyimpan koordinat']);
    }
    exit;
}

// ── UPLOAD LOGO (LANGSUNG TIMPA FILE) ──
if ($action === 'upload_logo') {
    // Cek apakah ada file yang diupload
    if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'msg' => 'File logo tidak ditemukan atau gagal upload']);
        exit;
    }
    
    $file = $_FILES['logo'];
    $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    // Validasi tipe file
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        echo json_encode(['success' => false, 'msg' => 'Format file tidak didukung. Gunakan PNG, JPG, JPEG, atau WEBP']);
        exit;
    }
    
    // Validasi ukuran file
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'msg' => 'Ukuran file maksimal 2MB']);
        exit;
    }
    
    // Tentukan path upload (relative dari root)
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/';
    
    // Buat direktori assets jika belum ada
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Path file logo (langsung timpa logo.png)
    $uploadPath = $uploadDir . 'logo.png';
    
    // Upload file (timpa langsung)
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        echo json_encode(['success' => true, 'msg' => 'Logo berhasil diupload', 'logo_path' => 'assets/logo.png']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Gagal menyimpan file logo']);
    }
    exit;
}

// ── GET SEMESTER AKTIF ──
if ($action === 'get_semester_aktif') {
    $query = "SELECT tahun, semester FROM tahun_akademik WHERE status = 'aktif' LIMIT 1";
    $result = $conn->query($query);
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Tidak ada semester aktif']);
    }
    exit;
}

// ── GET SERVER INFO ──
if ($action === 'get_server_info') {
    $server = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
    $db_version = $conn->query("SELECT VERSION() as version")->fetch_assoc()['version'] ?? 'Unknown';
    echo json_encode(['success' => true, 'data' => [
        'server' => $server,
        'db_version' => $db_version
    ]]);
    exit;
}

// ── EXPORT LOG AKTIVITAS (CSV) ──
if ($action === 'export_log') {
    $query = "SELECT id, aksi, tabel, data_id, deskripsi, pelaku, waktu FROM log_aktivitas ORDER BY waktu DESC LIMIT 5000";
    $result = $conn->query($query);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="log_aktivitas_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF";
    
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Aksi', 'Tabel', 'Data ID', 'Deskripsi', 'Pelaku', 'Waktu']);
    while ($row = $result->fetch_assoc()) {
        fputcsv($out, [
            $row['id'],
            $row['aksi'],
            $row['tabel'],
            $row['data_id'],
            $row['deskripsi'],
            $row['pelaku'],
            $row['waktu']
        ]);
    }
    fclose($out);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Action tidak dikenali']);
exit;
?>