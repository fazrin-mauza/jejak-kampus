<?php
require_once '../../../auth/check.php';

// ════════════════════════════════════════════════
// AJAX HANDLER — CRUD Mahasiswa + Auto Create User + Import CSV
// ════════════════════════════════════════════════
if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    // ── LIST (dengan paginasi, search, filter) ──
    if ($action === 'list') {
        $page    = max(1, (int)($_POST['page'] ?? 1));
        $limit   = 10;
        $offset  = ($page - 1) * $limit;
        $search  = trim($_POST['search'] ?? '');
        $status  = trim($_POST['status'] ?? '');
        $kelas_id = (int)($_POST['kelas_id'] ?? 0);

        $where   = [];
        $params  = [];
        $types   = '';

        if ($search !== '') {
            $where[] = '(m.nim LIKE ? OR m.nama LIKE ? OR u.email LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types   .= 'sss';
        }
        if ($status !== '' && in_array($status, ['aktif','cuti','lulus','dropout'])) {
            $where[] = 'm.status = ?';
            $params[] = $status;
            $types   .= 's';
        }
        if ($kelas_id > 0) {
            $where[] = 'm.kelas_id = ?';
            $params[] = $kelas_id;
            $types   .= 'i';
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Total
        $countSQL = "SELECT COUNT(*) FROM mahasiswa m 
                     LEFT JOIN users u ON m.user_id = u.id 
                     LEFT JOIN kelas k ON m.kelas_id = k.id 
                     $whereSQL";
        $stmtCount = $conn->prepare($countSQL);
        if ($types) $stmtCount->bind_param($types, ...$params);
        $stmtCount->execute();
        $stmtCount->bind_result($total);
        $stmtCount->fetch();
        $stmtCount->close();

        // Data dengan statistik kehadiran
        $dataSQL = "SELECT 
                        m.id, m.nim, m.nama, m.tanggal_lahir, m.jenis_kelamin, m.status,
                        u.email,
                        k.id as kelas_id, k.nama_kelas, k.jurusan, k.angkatan,
                        COALESCE(stat.total_hadir, 0) as total_hadir,
                        COALESCE(stat.total_alpha, 0) as total_alpha,
                        COALESCE(stat.total_izin, 0) as total_izin,
                        COALESCE(stat.persentase, 0) as persentase_kehadiran
                    FROM mahasiswa m 
                    LEFT JOIN users u ON m.user_id = u.id 
                    LEFT JOIN kelas k ON m.kelas_id = k.id
                    LEFT JOIN (
                        SELECT 
                            a.mahasiswa_id,
                            COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as total_hadir,
                            COUNT(CASE WHEN a.status = 'alpha' THEN 1 END) as total_alpha,
                            COUNT(CASE WHEN a.status = 'telat' THEN 1 END) as total_telat,
                            COUNT(CASE WHEN iz.id IS NOT NULL THEN 1 END) as total_izin,
                            ROUND(COUNT(CASE WHEN a.status IN ('hadir','telat') THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0), 2) as persentase
                        FROM absensi a
                        LEFT JOIN izin iz ON a.mahasiswa_id = iz.mahasiswa_id AND a.sesi_id = iz.sesi_id AND iz.status = 'disetujui'
                        GROUP BY a.mahasiswa_id
                    ) stat ON m.id = stat.mahasiswa_id
                    $whereSQL 
                    ORDER BY m.nim ASC 
                    LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($dataSQL);
        $params[] = $limit;
        $params[] = $offset;
        $types   .= 'ii';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
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

    // ── GET SINGLE MAHASISWA ──
    if ($action === 'get') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("
            SELECT 
                m.*, u.email, k.nama_kelas, k.jurusan, k.angkatan,
                COALESCE(stat.total_hadir, 0) as total_hadir,
                COALESCE(stat.total_alpha, 0) as total_alpha,
                COALESCE(stat.total_izin, 0) as total_izin,
                COALESCE(stat.persentase, 0) as persentase_kehadiran
            FROM mahasiswa m 
            LEFT JOIN users u ON m.user_id = u.id 
            LEFT JOIN kelas k ON m.kelas_id = k.id
            LEFT JOIN (
                SELECT 
                    a.mahasiswa_id,
                    COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as total_hadir,
                    COUNT(CASE WHEN a.status = 'alpha' THEN 1 END) as total_alpha,
                    COUNT(CASE WHEN iz.id IS NOT NULL THEN 1 END) as total_izin,
                    ROUND(COUNT(CASE WHEN a.status IN ('hadir','telat') THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0), 2) as persentase
                FROM absensi a
                LEFT JOIN izin iz ON a.mahasiswa_id = iz.mahasiswa_id AND a.sesi_id = iz.sesi_id AND iz.status = 'disetujui'
                GROUP BY a.mahasiswa_id
            ) stat ON m.id = stat.mahasiswa_id
            WHERE m.id = ?
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($row && $row['tanggal_lahir']) {
            $date = new DateTime($row['tanggal_lahir']);
            $row['tanggal_lahir_formatted'] = $date->format('d/m/Y');
        }
        
        echo json_encode(['success' => (bool)$row, 'data' => $row]);
        exit;
    }

    // ── GET STATISTIK STATUS ──
    if ($action === 'get_stats') {
        $stmt = $conn->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif,
                SUM(CASE WHEN status = 'cuti' THEN 1 ELSE 0 END) as cuti,
                SUM(CASE WHEN status = 'lulus' THEN 1 ELSE 0 END) as lulus,
                SUM(CASE WHEN status = 'dropout' THEN 1 ELSE 0 END) as dropout
            FROM mahasiswa
        ");
        $stats = $stmt->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $stats]);
        exit;
    }

    // ── GET KELAS LIST ──
    if ($action === 'get_kelas') {
        $result = $conn->query("
            SELECT k.*, ta.tahun, ta.semester 
            FROM kelas k 
            LEFT JOIN tahun_akademik ta ON k.tahun_akademik_id = ta.id 
            ORDER BY k.angkatan DESC, k.nama_kelas ASC
        ");
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }

    /**
     * Fungsi helper untuk konversi tanggal
     */
    function convertDateToMySQL($dateStr) {
        if (empty($dateStr)) return null;
        $dateStr = trim($dateStr);
        
        $formats = ['d/m/Y', 'd/m/y', 'j/n/Y', 'j/n/y'];
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $dateStr);
            if ($date && $date->format($format) === $dateStr) {
                return $date->format('Y-m-d');
            }
        }
        
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

    // ── CREATE ──
    if ($action === 'create') {
        $nim            = trim($_POST['nim'] ?? '');
        $nama           = trim($_POST['nama'] ?? '');
        $tanggal_lahir  = trim($_POST['tanggal_lahir'] ?? '');
        $jenis_kelamin  = trim($_POST['jenis_kelamin'] ?? '');
        $kelas_id       = (int)($_POST['kelas_id'] ?? 0);
        $status         = trim($_POST['status'] ?? 'aktif');
        $email          = trim($_POST['email'] ?? '');

        if (!$nim || !$nama || !$tanggal_lahir || !$jenis_kelamin || !$kelas_id || !$email) {
            echo json_encode(['success' => false, 'msg' => 'Semua field wajib diisi.']); exit;
        }
        if (!in_array($jenis_kelamin, ['L', 'P'])) {
            echo json_encode(['success' => false, 'msg' => 'Jenis kelamin tidak valid.']); exit;
        }
        if (!in_array($status, ['aktif','cuti','lulus','dropout'])) {
            echo json_encode(['success' => false, 'msg' => 'Status tidak valid.']); exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'msg' => 'Format email tidak valid.']); exit;
        }
        
        $dateConverted = convertDateToMySQL($tanggal_lahir);
        if (!$dateConverted) {
            echo json_encode(['success' => false, 'msg' => 'Format tanggal tidak valid. Gunakan DD/MM/YYYY (contoh: 15/05/2003 atau 3/5/2003).']); exit;
        }

        $chk = $conn->prepare("SELECT id FROM mahasiswa WHERE nim = ?");
        $chk->bind_param('s', $nim);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $chk->close();
            echo json_encode(['success' => false, 'msg' => 'NIM sudah terdaftar.']); exit;
        }
        $chk->close();

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
            $default_password = password_hash($nim, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role, created_at) VALUES (?, ?, ?, 'mahasiswa', NOW())");
            $stmt->bind_param('sss', $nama, $email, $default_password);
            $stmt->execute();
            $user_id = $stmt->insert_id;
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO mahasiswa (user_id, nim, nama, tanggal_lahir, jenis_kelamin, kelas_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('issssis', $user_id, $nim, $nama, $dateConverted, $jenis_kelamin, $kelas_id, $status);
            $stmt->execute();
            $mhs_id = $stmt->insert_id;
            $stmt->close();

            $conn->commit();
            echo json_encode([
                'success' => true, 
                'msg' => 'Mahasiswa berhasil ditambahkan. Akun login dibuat dengan password: ' . $nim,
                'id' => $mhs_id,
                'user_id' => $user_id
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
        $nim            = trim($_POST['nim'] ?? '');
        $nama           = trim($_POST['nama'] ?? '');
        $tanggal_lahir  = trim($_POST['tanggal_lahir'] ?? '');
        $jenis_kelamin  = trim($_POST['jenis_kelamin'] ?? '');
        $kelas_id       = (int)($_POST['kelas_id'] ?? 0);
        $status         = trim($_POST['status'] ?? 'aktif');
        $email          = trim($_POST['email'] ?? '');

        if (!$id || !$nim || !$nama || !$tanggal_lahir || !$jenis_kelamin || !$kelas_id || !$email) {
            echo json_encode(['success' => false, 'msg' => 'Semua field wajib diisi.']); exit;
        }
        
        $dateConverted = convertDateToMySQL($tanggal_lahir);
        if (!$dateConverted) {
            echo json_encode(['success' => false, 'msg' => 'Format tanggal tidak valid. Gunakan DD/MM/YYYY (contoh: 15/05/2003 atau 3/5/2003).']); exit;
        }

        $chk = $conn->prepare("SELECT id FROM mahasiswa WHERE nim = ? AND id != ?");
        $chk->bind_param('si', $nim, $id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $chk->close();
            echo json_encode(['success' => false, 'msg' => 'NIM sudah digunakan mahasiswa lain.']); exit;
        }
        $chk->close();

        $stmt = $conn->prepare("SELECT user_id FROM mahasiswa WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($user_id);
        $stmt->fetch();
        $stmt->close();

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
            $stmt = $conn->prepare("UPDATE mahasiswa SET nim=?, nama=?, tanggal_lahir=?, jenis_kelamin=?, kelas_id=?, status=? WHERE id=?");
            $stmt->bind_param('ssssisi', $nim, $nama, $dateConverted, $jenis_kelamin, $kelas_id, $status, $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE users SET nama=?, email=? WHERE id=?");
            $stmt->bind_param('ssi', $nama, $email, $user_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            echo json_encode(['success' => true, 'msg' => 'Data mahasiswa berhasil diperbarui.']);
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

        $stmt = $conn->prepare("SELECT user_id, nim FROM mahasiswa WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($user_id, $nim);
        $stmt->fetch();
        $stmt->close();

        $newpass = $nim;
        $hash = password_hash($newpass, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param('si', $hash, $user_id);
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode([
            'success' => $ok, 
            'msg' => $ok ? "Password berhasil direset ke NIM: <strong>$newpass</strong>" : 'Gagal reset password.',
            'new_password' => $newpass
        ]);
        exit;
    }

    // ── DELETE ──
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'msg' => 'ID tidak valid.']); exit; }

        $stmt = $conn->prepare("SELECT user_id FROM mahasiswa WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($user_id);
        $stmt->fetch();
        $stmt->close();

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("DELETE FROM mahasiswa WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            echo json_encode(['success' => true, 'msg' => 'Mahasiswa berhasil dihapus.']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'msg' => 'Gagal menghapus: ' . $e->getMessage()]);
        }
        exit;
    }

    // ── EXPORT CSV ──
    if ($action === 'export_csv') {
        $status   = trim($_POST['status'] ?? '');
        $search   = trim($_POST['search'] ?? '');
        $kelas_id = (int)($_POST['kelas_id'] ?? 0);
        
        $where   = [];
        $params  = [];
        $types   = '';
        
        if ($search !== '') {
            $where[] = '(m.nim LIKE ? OR m.nama LIKE ? OR u.email LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types   .= 'sss';
        }
        if ($status !== '' && in_array($status, ['aktif','cuti','lulus','dropout'])) {
            $where[] = 'm.status = ?';
            $params[] = $status;
            $types   .= 's';
        }
        if ($kelas_id > 0) {
            $where[] = 'm.kelas_id = ?';
            $params[] = $kelas_id;
            $types   .= 'i';
        }
        
        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="mahasiswa_' . date('Ymd_His') . '.csv"');
        echo "\xEF\xBB\xBF";
        
        $out = fopen('php://output', 'w');
        fputcsv($out, ['NIM', 'Nama', 'Tanggal Lahir', 'Kelas', 'Jurusan', 'Angkatan', 'Status', 'Email', 'Hadir', 'Alpha', 'Izin', 'Kehadiran']);
        
        $sql = "
            SELECT 
                m.nim, m.nama, m.tanggal_lahir, k.nama_kelas, k.jurusan, k.angkatan, m.status, u.email,
                COALESCE(stat.total_hadir, 0) as total_hadir,
                COALESCE(stat.total_alpha, 0) as total_alpha,
                COALESCE(stat.total_izin, 0) as total_izin,
                COALESCE(stat.persentase, 0) as persentase
            FROM mahasiswa m
            LEFT JOIN users u ON m.user_id = u.id
            LEFT JOIN kelas k ON m.kelas_id = k.id
            LEFT JOIN (
                SELECT 
                    a.mahasiswa_id,
                    COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as total_hadir,
                    COUNT(CASE WHEN a.status = 'alpha' THEN 1 END) as total_alpha,
                    COUNT(CASE WHEN iz.id IS NOT NULL THEN 1 END) as total_izin,
                    ROUND(COUNT(CASE WHEN a.status IN ('hadir','telat') THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0), 2) as persentase
                FROM absensi a
                LEFT JOIN izin iz ON a.mahasiswa_id = iz.mahasiswa_id AND a.sesi_id = iz.sesi_id AND iz.status = 'disetujui'
                GROUP BY a.mahasiswa_id
            ) stat ON m.id = stat.mahasiswa_id
            $whereSQL 
            ORDER BY m.nim ASC
        ";
        
        $stmt = $conn->prepare($sql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $tanggal_formatted = '';
            if ($row['tanggal_lahir']) {
                $date = new DateTime($row['tanggal_lahir']);
                $tanggal_formatted = $date->format('d/m/Y');
            }
            
            fputcsv($out, [
                $row['nim'],
                $row['nama'],
                $tanggal_formatted,
                $row['nama_kelas'] ?: '-',
                $row['jurusan'] ?: '-',
                $row['angkatan'] ?: '-',
                $row['status'],
                $row['email'] ?: '-',
                $row['total_hadir'],
                $row['total_alpha'],
                $row['total_izin'],
                $row['persentase'] . '%'
            ]);
        }
        
        fclose($out);
        exit;
    }

    // ── DOWNLOAD TEMPLATE ──
    if ($action === 'download_template') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="template_mahasiswa.csv"');
        echo "\xEF\xBB\xBF";
        
        $out = fopen('php://output', 'w');
        fputcsv($out, ['NIM', 'Nama Lengkap', 'Tanggal Lahir (DD/MM/YYYY)', 'Jenis Kelamin (L/P)', 'Kelas ID', 'Status (aktif/cuti/lulus/dropout)', 'Email']);
        fputcsv($out, ['24050001', 'Ahmad Wijaya', '15/5/2003', 'L', '2', 'aktif', '24050001@student.unesa.ac.id']);
        fputcsv($out, ['24050002', 'Siti Aminah', '22/8/2003', 'P', '2', 'aktif', '24050002@student.unesa.ac.id']);
        fclose($out);
        exit;
    }

    // ── IMPORT CSV ── (DIPERBAIKI - LEBIH FLEKSIBEL)
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
            'nim' => -1,
            'nama' => -1,
            'tanggal' => -1,
            'jk' => -1,
            'kelas_id' => -1,
            'status' => -1,
            'email' => -1
        ];
        
        foreach ($cleanHeader as $i => $col) {
            $colLower = strtolower($col);
            if (strpos($colLower, 'nim') !== false && $colIndex['nim'] === -1) $colIndex['nim'] = $i;
            if (strpos($colLower, 'nama lengkap') !== false || (strpos($colLower, 'nama') !== false && strpos($colLower, 'lengkap') !== false)) $colIndex['nama'] = $i;
            if (strpos($colLower, 'tanggal lahir') !== false) $colIndex['tanggal'] = $i;
            if (strpos($colLower, 'jenis kelamin') !== false || strpos($colLower, 'jk') !== false) $colIndex['jk'] = $i;
            if (strpos($colLower, 'kelas id') !== false || strpos($colLower, 'kelas') !== false) $colIndex['kelas_id'] = $i;
            if (strpos($colLower, 'status') !== false) $colIndex['status'] = $i;
            if (strpos($colLower, 'email') !== false) $colIndex['email'] = $i;
        }
        
        // Validasi kolom wajib
        $required = ['nim', 'nama', 'email'];
        $missing = [];
        foreach ($required as $req) {
            if ($colIndex[$req] === -1) {
                $missing[] = $req;
            }
        }
        
        if (!empty($missing)) {
            fclose($handle);
            echo json_encode(['success' => false, 'msg' => 'Header tidak lengkap. Kolom yang wajib ada: NIM, Nama Lengkap, Email. Header yang ditemukan: ' . implode(', ', $cleanHeader)]);
            exit;
        }
        
        // Jika kolom tanggal tidak ditemukan, cari alternatif
        if ($colIndex['tanggal'] === -1) {
            // Coba cari kolom dengan kata tanggal
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
        
        // Jika kolom kelas_id tidak ditemukan
        if ($colIndex['kelas_id'] === -1) {
            $colIndex['kelas_id'] = -1;
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
            $nim = $colIndex['nim'] !== -1 ? trim($row[$colIndex['nim']] ?? '') : '';
            $nama = $colIndex['nama'] !== -1 ? trim($row[$colIndex['nama']] ?? '') : '';
            $tanggal_lahir_dmy = $colIndex['tanggal'] !== -1 ? trim($row[$colIndex['tanggal']] ?? '') : '';
            $jenis_kelamin = $colIndex['jk'] !== -1 ? trim($row[$colIndex['jk']] ?? '') : 'L';
            $kelas_id = $colIndex['kelas_id'] !== -1 ? (int)($row[$colIndex['kelas_id']] ?? 0) : 0;
            $status = $colIndex['status'] !== -1 ? trim($row[$colIndex['status']] ?? '') : 'aktif';
            $email = $colIndex['email'] !== -1 ? trim($row[$colIndex['email']] ?? '') : '';
            
            $errors = [];
            
            // Validasi
            if (!$nim) $errors[] = 'NIM kosong';
            if (!$nama) $errors[] = 'Nama kosong';
            if (!$email) $errors[] = 'Email kosong';
            
            if ($tanggal_lahir_dmy && !convertDateToMySQL($tanggal_lahir_dmy)) {
                $errors[] = "Format tanggal '$tanggal_lahir_dmy' tidak valid. Gunakan DD/MM/YYYY";
            }
            
            if ($jenis_kelamin && !in_array($jenis_kelamin, ['L', 'P'])) {
                $errors[] = 'Jenis kelamin harus L atau P';
            }
            
            if ($kelas_id <= 0) {
                $errors[] = 'Kelas ID tidak valid (harus angka > 0)';
            }
            
            if ($status && !in_array($status, ['aktif', 'cuti', 'lulus', 'dropout'])) {
                $errors[] = "Status '$status' tidak valid";
            }
            
            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Email '$email' tidak valid";
            }
            
            if (!empty($errors)) {
                $failedRows[] = ['row' => $rowNumber, 'nim' => $nim, 'nama' => $nama, 'errors' => $errors];
                continue;
            }
            
            // Konversi tanggal
            $tanggal_convert = $tanggal_lahir_dmy ? convertDateToMySQL($tanggal_lahir_dmy) : null;
            
            try {
                // Cek duplikat NIM
                $chk = $conn->prepare("SELECT id FROM mahasiswa WHERE nim = ?");
                $chk->bind_param('s', $nim);
                $chk->execute();
                $chk->store_result();
                if ($chk->num_rows > 0) {
                    $failedRows[] = ['row' => $rowNumber, 'nim' => $nim, 'nama' => $nama, 'errors' => ['NIM sudah terdaftar']];
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
                    $failedRows[] = ['row' => $rowNumber, 'nim' => $nim, 'nama' => $nama, 'errors' => ['Email sudah terdaftar']];
                    $chk->close();
                    continue;
                }
                $chk->close();
                
                // Cek kelas
                $chk = $conn->prepare("SELECT id FROM kelas WHERE id = ?");
                $chk->bind_param('i', $kelas_id);
                $chk->execute();
                $chk->store_result();
                if ($chk->num_rows === 0) {
                    $failedRows[] = ['row' => $rowNumber, 'nim' => $nim, 'nama' => $nama, 'errors' => ["Kelas ID $kelas_id tidak ditemukan"]];
                    $chk->close();
                    continue;
                }
                $chk->close();
                
                // Create user
                $default_password = password_hash($nim, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role, created_at) VALUES (?, ?, ?, 'mahasiswa', NOW())");
                $stmt->bind_param('sss', $nama, $email, $default_password);
                $stmt->execute();
                $user_id = $stmt->insert_id;
                $stmt->close();
                
                // Create mahasiswa
                $stmt = $conn->prepare("INSERT INTO mahasiswa (user_id, nim, nama, tanggal_lahir, jenis_kelamin, kelas_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('issssis', $user_id, $nim, $nama, $tanggal_convert, $jenis_kelamin, $kelas_id, $status);
                $stmt->execute();
                $stmt->close();
                
                $successCount++;
                
            } catch (Exception $e) {
                $failedRows[] = ['row' => $rowNumber, 'nim' => $nim, 'nama' => $nama, 'errors' => ['Database error: ' . $e->getMessage()]];
            }
        }
        
        fclose($handle);
        
        if ($successCount > 0) {
            $conn->commit();
            $message = "Berhasil mengimport $successCount data mahasiswa.";
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
}
?>