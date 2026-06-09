<?php
require_once '../../../auth/check.php';

// ════════════════════════════════════════════════
// AJAX HANDLER — CRUD Dosen + Auto Create User + Import CSV
// Format Tanggal: DD/MM/YYYY (support tanpa leading zero)
// ════════════════════════════════════════════════
header('Content-Type: application/json');

if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'msg' => 'Action parameter required']);
    exit;
}

$action = $_POST['action'];

/**
 * Fungsi helper untuk konversi tanggal dari format D/M/YYYY atau DD/MM/YYYY ke YYYY-MM-DD
 */
function convertDateToMySQL($dateStr) {
    if (empty($dateStr)) return null;
    
    // Bersihkan spasi
    $dateStr = trim($dateStr);
    
    // Coba parse dengan berbagai format
    $formats = ['d/m/Y', 'd/m/y', 'j/n/Y', 'j/n/y'];
    
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $dateStr);
        if ($date && $date->format($format) === $dateStr) {
            return $date->format('Y-m-d');
        }
    }
    
    // Coba dengan explode manual untuk format D/M/YYYY
    $parts = explode('/', $dateStr);
    if (count($parts) === 3) {
        $day = intval($parts[0]);
        $month = intval($parts[1]);
        $year = intval($parts[2]);
        if (checkdate($month, $day, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }
    
    return null;
}

/**
 * Fungsi untuk format tanggal dari YYYY-MM-DD ke DD/MM/YYYY
 */
function formatDateToDMY($dateStr) {
    if (empty($dateStr)) return '';
    $date = new DateTime($dateStr);
    return $date->format('d/m/Y');
}

// ── LIST (dengan paginasi, search, filter) ──
if ($action === 'list') {
    $page    = max(1, (int)($_POST['page'] ?? 1));
    $limit   = 10;
    $offset  = ($page - 1) * $limit;
    $search  = trim($_POST['search'] ?? '');
    $status  = trim($_POST['status'] ?? '');

    $where   = [];
    $params  = [];
    $types   = '';

    if ($search !== '') {
        $where[] = '(d.nidn LIKE ? OR d.nama LIKE ? OR u.email LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types   .= 'sss';
    }
    if ($status !== '' && in_array($status, ['aktif','nonaktif','cuti','pensiun'])) {
        $where[] = 'd.status = ?';
        $params[] = $status;
        $types   .= 's';
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Total
    $countSQL = "SELECT COUNT(*) FROM dosen d LEFT JOIN users u ON d.user_id = u.id $whereSQL";
    $stmtCount = $conn->prepare($countSQL);
    if ($types) $stmtCount->bind_param($types, ...$params);
    $stmtCount->execute();
    $stmtCount->bind_result($total);
    $stmtCount->fetch();
    $stmtCount->close();

    // Data
    $dataSQL = "SELECT 
                    d.id, d.nidn, d.nama, d.tanggal_lahir, d.jenis_kelamin, d.status,
                    u.email,
                    (SELECT COUNT(*) FROM jadwal j WHERE j.dosen_id = d.id) as total_mk
                FROM dosen d 
                LEFT JOIN users u ON d.user_id = u.id 
                $whereSQL 
                ORDER BY d.nama ASC 
                LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($dataSQL);
    $params[] = $limit;
    $params[] = $offset;
    $types   .= 'ii';
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        // Format tanggal untuk ditampilkan di tabel (opsional)
        if ($row['tanggal_lahir']) {
            $row['tanggal_lahir_formatted'] = formatDateToDMY($row['tanggal_lahir']);
        }
        $rows[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'data'    => $rows,
        'total'   => $total,
        'page'    => $page,
        'pages'   => ceil($total / $limit),
        'limit'   => $limit,
    ]);
    exit;
}

// ── GET STATISTIK STATUS ──
if ($action === 'get_stats') {
    $stmt = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif,
            SUM(CASE WHEN status = 'nonaktif' THEN 1 ELSE 0 END) as nonaktif,
            SUM(CASE WHEN status = 'cuti' THEN 1 ELSE 0 END) as cuti,
            SUM(CASE WHEN status = 'pensiun' THEN 1 ELSE 0 END) as pensiun
        FROM dosen
    ");
    $stats = $stmt->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $stats]);
    exit;
}

// ── GET SINGLE DOSEN ──
if ($action === 'get') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $conn->prepare("
        SELECT 
            d.*, u.email,
            (SELECT GROUP_CONCAT(DISTINCT mk.nama_mk SEPARATOR ', ') 
             FROM jadwal j 
             JOIN mata_kuliah mk ON j.mata_kuliah_id = mk.id 
             WHERE j.dosen_id = d.id) as mata_kuliah_list,
            (SELECT GROUP_CONCAT(DISTINCT k.nama_kelas SEPARATOR ', ') 
             FROM jadwal j 
             JOIN kelas k ON j.kelas_id = k.id 
             WHERE j.dosen_id = d.id) as kelas_list,
            (SELECT COUNT(*) FROM sesi_absensi sa 
             JOIN jadwal j ON sa.jadwal_id = j.id 
             WHERE j.dosen_id = d.id AND sa.status = 'aktif') as total_sesi
        FROM dosen d 
        LEFT JOIN users u ON d.user_id = u.id 
        WHERE d.id = ?
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Format tanggal untuk ditampilkan di form
    if ($row && $row['tanggal_lahir']) {
        $row['tanggal_lahir_formatted'] = formatDateToDMY($row['tanggal_lahir']);
    }
    
    echo json_encode(['success' => (bool)$row, 'data' => $row]);
    exit;
}

// ── CREATE (dengan auto-create user) ──
if ($action === 'create') {
    $nidn           = trim($_POST['nidn'] ?? '');
    $nama           = trim($_POST['nama'] ?? '');
    $tanggal_lahir  = trim($_POST['tanggal_lahir'] ?? '');
    $jenis_kelamin  = trim($_POST['jenis_kelamin'] ?? '');
    $status         = trim($_POST['status'] ?? 'aktif');
    $email          = trim($_POST['email'] ?? '');

    // Validasi
    if (!$nidn || !$nama || !$tanggal_lahir || !$jenis_kelamin || !$email) {
        echo json_encode(['success' => false, 'msg' => 'Semua field wajib diisi.']); exit;
    }
    if (!in_array($jenis_kelamin, ['L', 'P'])) {
        echo json_encode(['success' => false, 'msg' => 'Jenis kelamin tidak valid.']); exit;
    }
    if (!in_array($status, ['aktif','nonaktif','cuti','pensiun'])) {
        echo json_encode(['success' => false, 'msg' => 'Status tidak valid.']); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'msg' => 'Format email tidak valid.']); exit;
    }
    
    // Konversi tanggal dari format DD/MM/YYYY ke YYYY-MM-DD
    $dateConverted = convertDateToMySQL($tanggal_lahir);
    if (!$dateConverted) {
        echo json_encode(['success' => false, 'msg' => 'Format tanggal tidak valid. Gunakan format DD/MM/YYYY (contoh: 15/05/1980 atau 5/5/1980).']); exit;
    }

    // Cek duplikat NIDN
    $chk = $conn->prepare("SELECT id FROM dosen WHERE nidn = ?");
    $chk->bind_param('s', $nidn);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $chk->close();
        echo json_encode(['success' => false, 'msg' => 'NIDN sudah terdaftar.']); exit;
    }
    $chk->close();

    // Cek duplikat email
    $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $chk->bind_param('s', $email);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $chk->close();
        echo json_encode(['success' => false, 'msg' => 'Email sudah terdaftar.']); exit;
    }
    $chk->close();

    $conn->begin_transaction();

    try {
        // Create user account
        $default_password = password_hash($nidn, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role, created_at) VALUES (?, ?, ?, 'dosen', NOW())");
        $stmt->bind_param('sss', $nama, $email, $default_password);
        $stmt->execute();
        $user_id = $stmt->insert_id;
        $stmt->close();

        // Create dosen record
        $stmt = $conn->prepare("INSERT INTO dosen (user_id, nidn, nama, tanggal_lahir, jenis_kelamin, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isssss', $user_id, $nidn, $nama, $dateConverted, $jenis_kelamin, $status);
        $stmt->execute();
        $dosen_id = $stmt->insert_id;
        $stmt->close();

        $conn->commit();
        echo json_encode([
            'success' => true, 
            'msg' => 'Dosen berhasil ditambahkan. Akun login dibuat dengan password: ' . $nidn,
            'id' => $dosen_id
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'msg' => 'Gagal menyimpan: ' . $e->getMessage()]);
    }
    exit;
}

// ── UPDATE ──
if ($action === 'update') {
    $id             = (int)($_POST['id'] ?? 0);
    $nidn           = trim($_POST['nidn'] ?? '');
    $nama           = trim($_POST['nama'] ?? '');
    $tanggal_lahir  = trim($_POST['tanggal_lahir'] ?? '');
    $jenis_kelamin  = trim($_POST['jenis_kelamin'] ?? '');
    $status         = trim($_POST['status'] ?? 'aktif');
    $email          = trim($_POST['email'] ?? '');

    if (!$id || !$nidn || !$nama || !$tanggal_lahir || !$jenis_kelamin || !$email) {
        echo json_encode(['success' => false, 'msg' => 'Semua field wajib diisi.']); exit;
    }
    
    // Konversi tanggal dari format DD/MM/YYYY ke YYYY-MM-DD
    $dateConverted = convertDateToMySQL($tanggal_lahir);
    if (!$dateConverted) {
        echo json_encode(['success' => false, 'msg' => 'Format tanggal tidak valid. Gunakan format DD/MM/YYYY (contoh: 15/05/1980 atau 5/5/1980).']); exit;
    }

    // Cek duplikat NIDN
    $chk = $conn->prepare("SELECT id FROM dosen WHERE nidn = ? AND id != ?");
    $chk->bind_param('si', $nidn, $id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $chk->close();
        echo json_encode(['success' => false, 'msg' => 'NIDN sudah digunakan dosen lain.']); exit;
    }
    $chk->close();

    // Get user_id
    $stmt = $conn->prepare("SELECT user_id FROM dosen WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();

    // Cek duplikat email
    $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $chk->bind_param('si', $email, $user_id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $chk->close();
        echo json_encode(['success' => false, 'msg' => 'Email sudah digunakan user lain.']); exit;
    }
    $chk->close();

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE dosen SET nidn=?, nama=?, tanggal_lahir=?, jenis_kelamin=?, status=? WHERE id=?");
        $stmt->bind_param('sssssi', $nidn, $nama, $dateConverted, $jenis_kelamin, $status, $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE users SET nama=?, email=? WHERE id=?");
        $stmt->bind_param('ssi', $nama, $email, $user_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'msg' => 'Data dosen berhasil diperbarui.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'msg' => 'Gagal memperbarui: ' . $e->getMessage()]);
    }
    exit;
}

// ── RESET PASSWORD ──
if ($action === 'reset_password') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'msg' => 'ID tidak valid.']); exit; }

    $stmt = $conn->prepare("SELECT user_id, nidn FROM dosen WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($user_id, $nidn);
    $stmt->fetch();
    $stmt->close();

    $hash = password_hash($nidn, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->bind_param('si', $hash, $user_id);
    $ok = $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => $ok, 
        'msg' => $ok ? "Password berhasil direset ke NIDN: <strong>$nidn</strong>" : 'Gagal reset password.',
        'new_password' => $nidn
    ]);
    exit;
}

// ── DELETE ──
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'msg' => 'ID tidak valid.']); exit; }

    $stmt = $conn->prepare("SELECT user_id FROM dosen WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("DELETE FROM dosen WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'msg' => 'Dosen berhasil dihapus.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'msg' => 'Gagal menghapus: ' . $e->getMessage()]);
    }
    exit;
}

// ── EXPORT CSV (dengan filter) ──
if ($action === 'export_csv') {
    $status = trim($_POST['status'] ?? '');
    $search = trim($_POST['search'] ?? '');
    
    $where = [];
    $params = [];
    $types = '';
    
    if ($search !== '') {
        $where[] = '(d.nidn LIKE ? OR d.nama LIKE ? OR u.email LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= 'sss';
    }
    if ($status !== '' && in_array($status, ['aktif','nonaktif','cuti','pensiun'])) {
        $where[] = 'd.status = ?';
        $params[] = $status;
        $types .= 's';
    }
    
    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="dosen_' . date('Ymd_His') . '.csv"');
    echo "\xEF\xBB\xBF";
    
    $out = fopen('php://output', 'w');
    fputcsv($out, ['NIDN', 'Nama', 'Tanggal Lahir', 'Jenis Kelamin', 'Status', 'Email', 'Total MK']);
    
    $sql = "
        SELECT 
            d.nidn, d.nama, d.tanggal_lahir, d.jenis_kelamin, d.status, u.email,
            (SELECT COUNT(*) FROM jadwal j WHERE j.dosen_id = d.id) as total_mk
        FROM dosen d
        LEFT JOIN users u ON d.user_id = u.id
        $whereSQL 
        ORDER BY d.nama ASC
    ";
    
    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Konversi tanggal ke format DD/MM/YYYY untuk export
        $tanggal_formatted = '';
        if ($row['tanggal_lahir']) {
            $tanggal_formatted = formatDateToDMY($row['tanggal_lahir']);
        }
        
        fputcsv($out, [
            $row['nidn'],
            $row['nama'],
            $tanggal_formatted,
            $row['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan',
            $row['status'],
            $row['email'] ?: '-',
            $row['total_mk']
        ]);
    }
    
    fclose($out);
    exit;
}

// ── DOWNLOAD TEMPLATE CSV ──
if ($action === 'download_template') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="template_dosen.csv"');
    echo "\xEF\xBB\xBF";
    
    $out = fopen('php://output', 'w');
    fputcsv($out, ['NIDN', 'Nama Lengkap', 'Tanggal Lahir (DD/MM/YYYY)', 'Jenis Kelamin (L/P)', 'Status (aktif/nonaktif/cuti/pensiun)', 'Email']);
    fputcsv($out, ['0012345678', 'Dr. Akhsan Maulana, M.T.', '15/5/1980', 'L', 'aktif', 'akhsan@unesa.ac.id']);
    fputcsv($out, ['0012345679', 'Prof. Dr. Siti Aminah, M.Pd.', '22/8/1975', 'P', 'aktif', 'siti@unesa.ac.id']);
    fclose($out);
    exit;
}

// ── IMPORT CSV ── (FLEKSIBEL - support berbagai format header)
if ($action === 'import_csv') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'msg' => 'File tidak ditemukan atau gagal diupload.']);
        exit;
    }
    
    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($ext !== 'csv') {
        echo json_encode(['success' => false, 'msg' => 'Format file harus CSV. Gunakan template yang disediakan.']);
        exit;
    }
    
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        echo json_encode(['success' => false, 'msg' => 'Gagal membaca file.']);
        exit;
    }
    
    // Baca header
    $header = fgetcsv($handle);
    if (!$header) {
        fclose($handle);
        echo json_encode(['success' => false, 'msg' => 'File kosong atau format tidak valid.']);
        exit;
    }
    
    // Bersihkan header
    $cleanHeader = [];
    foreach ($header as $h) {
        $cleanHeader[] = trim(preg_replace('/\s+/', ' ', $h));
    }
    
    // Cari indeks kolom berdasarkan kata kunci (FLEKSIBEL)
    $colIndex = [
        'nidn' => -1,
        'nama' => -1,
        'tanggal' => -1,
        'jk' => -1,
        'status' => -1,
        'email' => -1
    ];
    
    foreach ($cleanHeader as $i => $col) {
        $colLower = strtolower($col);
        if (strpos($colLower, 'nidn') !== false) $colIndex['nidn'] = $i;
        if (strpos($colLower, 'nama lengkap') !== false || (strpos($colLower, 'nama') !== false && strpos($colLower, 'lengkap') !== false)) $colIndex['nama'] = $i;
        if (strpos($colLower, 'nama') !== false && $colIndex['nama'] === -1) $colIndex['nama'] = $i;
        if (strpos($colLower, 'tanggal lahir') !== false) $colIndex['tanggal'] = $i;
        if (strpos($colLower, 'jenis kelamin') !== false || strpos($colLower, 'jk') !== false) $colIndex['jk'] = $i;
        if (strpos($colLower, 'status') !== false) $colIndex['status'] = $i;
        if (strpos($colLower, 'email') !== false) $colIndex['email'] = $i;
    }
    
    // Validasi kolom wajib
    $required = ['nidn', 'nama', 'email'];
    $missing = [];
    foreach ($required as $req) {
        if ($colIndex[$req] === -1) {
            $missing[] = $req;
        }
    }
    
    if (!empty($missing)) {
        fclose($handle);
        echo json_encode(['success' => false, 'msg' => 'Header tidak lengkap. Kolom yang wajib ada: NIDN, Nama Lengkap, Email. Header yang ditemukan: ' . implode(', ', $cleanHeader)]);
        exit;
    }
    
    // Jika kolom tanggal tidak ditemukan, cari alternatif
    if ($colIndex['tanggal'] === -1) {
        foreach ($cleanHeader as $i => $col) {
            if (strpos(strtolower($col), 'tanggal') !== false) {
                $colIndex['tanggal'] = $i;
                break;
            }
        }
    }
    
    // Jika kolom JK tidak ditemukan, default ke L
    if ($colIndex['jk'] === -1) {
        $colIndex['jk'] = -1;
    }
    
    // Jika kolom status tidak ditemukan, default ke aktif
    if ($colIndex['status'] === -1) {
        $colIndex['status'] = -1;
    }
    
    $conn->begin_transaction();
    $successCount = 0;
    $failedRows = [];
    $rowNumber = 1;
    
    while (($row = fgetcsv($handle)) !== false) {
        $rowNumber++;
        
        // Skip baris kosong
        if (count($row) < 2 || (empty($row[0]) && empty($row[1]))) {
            continue;
        }
        
        // Ambil data berdasarkan indeks kolom
        $nidn = $colIndex['nidn'] !== -1 ? trim($row[$colIndex['nidn']] ?? '') : '';
        $nama = $colIndex['nama'] !== -1 ? trim($row[$colIndex['nama']] ?? '') : '';
        $tanggal_lahir_dmy = $colIndex['tanggal'] !== -1 ? trim($row[$colIndex['tanggal']] ?? '') : '';
        $jenis_kelamin = $colIndex['jk'] !== -1 ? trim(strtoupper($row[$colIndex['jk']] ?? '')) : 'L';
        $status = $colIndex['status'] !== -1 ? trim(strtolower($row[$colIndex['status']] ?? '')) : 'aktif';
        $email = $colIndex['email'] !== -1 ? trim($row[$colIndex['email']] ?? '') : '';
        
        $errors = [];
        
        // Validasi
        if (!$nidn) $errors[] = 'NIDN kosong';
        if (!$nama) $errors[] = 'Nama kosong';
        if (!$email) $errors[] = 'Email kosong';
        
        if ($tanggal_lahir_dmy && !convertDateToMySQL($tanggal_lahir_dmy)) {
            $errors[] = "Format tanggal '$tanggal_lahir_dmy' tidak valid. Gunakan DD/MM/YYYY (contoh: 15/5/1980)";
        }
        
        if ($jenis_kelamin && !in_array($jenis_kelamin, ['L', 'P'])) {
            $errors[] = 'Jenis kelamin harus L atau P';
        }
        
        if ($status && !in_array($status, ['aktif', 'nonaktif', 'cuti', 'pensiun'])) {
            $errors[] = "Status '$status' tidak valid (aktif/nonaktif/cuti/pensiun)";
        }
        
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email '$email' tidak valid";
        }
        
        if (!empty($errors)) {
            $failedRows[] = ['row' => $rowNumber, 'nidn' => $nidn, 'nama' => $nama, 'errors' => $errors];
            continue;
        }
        
        // Konversi tanggal
        $tanggal_convert = $tanggal_lahir_dmy ? convertDateToMySQL($tanggal_lahir_dmy) : null;
        
        try {
            // Cek duplikat NIDN
            $chk = $conn->prepare("SELECT id FROM dosen WHERE nidn = ?");
            $chk->bind_param('s', $nidn);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $failedRows[] = ['row' => $rowNumber, 'nidn' => $nidn, 'nama' => $nama, 'errors' => ['NIDN sudah terdaftar']];
                $chk->close();
                continue;
            }
            $chk->close();
            
            // Cek duplikat email
            $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $chk->bind_param('s', $email);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $failedRows[] = ['row' => $rowNumber, 'nidn' => $nidn, 'nama' => $nama, 'errors' => ['Email sudah terdaftar']];
                $chk->close();
                continue;
            }
            $chk->close();
            
            // Create user account
            $default_password = password_hash($nidn, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role, created_at) VALUES (?, ?, ?, 'dosen', NOW())");
            $stmt->bind_param('sss', $nama, $email, $default_password);
            $stmt->execute();
            $user_id = $stmt->insert_id;
            $stmt->close();
            
            // Create dosen record
            $stmt = $conn->prepare("INSERT INTO dosen (user_id, nidn, nama, tanggal_lahir, jenis_kelamin, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('isssss', $user_id, $nidn, $nama, $tanggal_convert, $jenis_kelamin, $status);
            $stmt->execute();
            $stmt->close();
            
            $successCount++;
            
        } catch (Exception $e) {
            $failedRows[] = ['row' => $rowNumber, 'nidn' => $nidn, 'nama' => $nama, 'errors' => ['Database error: ' . $e->getMessage()]];
        }
    }
    
    fclose($handle);
    
    if ($successCount > 0) {
        $conn->commit();
        $message = "Berhasil mengimport $successCount data dosen.";
        if (!empty($failedRows)) {
            $message .= " Gagal: " . count($failedRows) . " data.";
        }
        echo json_encode([
            'success' => true,
            'msg' => $message,
            'success_count' => $successCount,
            'failed_count' => count($failedRows),
            'failed_rows' => $failedRows
        ]);
    } else {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'msg' => 'Tidak ada data yang berhasil diimport. Periksa format file dan data.',
            'failed_rows' => $failedRows
        ]);
    }
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Action tidak dikenal.']);
exit;
?>