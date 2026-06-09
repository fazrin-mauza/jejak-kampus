<?
require_once '../../../auth/check.php';
// ════════════════════════════════════════════
// AJAX HANDLER — semua operasi CRUD via POST
// ════════════════════════════════════════════
if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    // ── LIST (dengan paginasi, search, filter) ──
    if ($action === 'list') {
        $page    = max(1, (int)($_POST['page'] ?? 1));
        $limit   = 10;
        $offset  = ($page - 1) * $limit;
        $search  = trim($_POST['search'] ?? '');
        $role    = trim($_POST['role'] ?? '');

        $where   = [];
        $params  = [];
        $types   = '';

        if ($search !== '') {
            $where[] = '(nama LIKE ? OR email LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types   .= 'ss';
        }
        if ($role !== '' && in_array($role, ['admin','dosen','mahasiswa'])) {
            $where[] = 'role = ?';
            $params[] = $role;
            $types   .= 's';
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Total
        $stmtCount = $conn->prepare("SELECT COUNT(*) FROM users $whereSQL");
        if ($types) $stmtCount->bind_param($types, ...$params);
        $stmtCount->execute();
        $stmtCount->bind_result($total);
        $stmtCount->fetch();
        $stmtCount->close();

        // Data
        $stmt = $conn->prepare("SELECT id, nama, email, role, created_at FROM users $whereSQL ORDER BY id ASC LIMIT ? OFFSET ?");
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

    // ── GET SINGLE USER ──
    if ($action === 'get') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT id, nama, email, role, created_at FROM users WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        echo json_encode(['success' => (bool)$row, 'data' => $row]);
        exit;
    }

    // ── CREATE ──
    if ($action === 'create') {
        $nama  = trim($_POST['nama']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = trim($_POST['role']  ?? '');
        $pass  = trim($_POST['password'] ?? '');

        if (!$nama || !$email || !$role || !$pass) {
            echo json_encode(['success' => false, 'msg' => 'Semua field wajib diisi.']); exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'msg' => 'Format email tidak valid.']); exit;
        }
        if (!in_array($role, ['admin','dosen','mahasiswa'])) {
            echo json_encode(['success' => false, 'msg' => 'Role tidak valid.']); exit;
        }
        if (strlen($pass) < 6) {
            echo json_encode(['success' => false, 'msg' => 'Password minimal 6 karakter.']); exit;
        }

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

        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role, created_at) VALUES (?,?,?,?,NOW())");
        $stmt->bind_param('ssss', $nama, $email, $hash, $role);
        $ok = $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();

        echo json_encode(['success' => $ok, 'msg' => $ok ? 'User berhasil ditambahkan.' : 'Gagal menyimpan.', 'id' => $newId]);
        exit;
    }

    // ── UPDATE ──
    if ($action === 'update') {
        $id    = (int)($_POST['id'] ?? 0);
        $nama  = trim($_POST['nama']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = trim($_POST['role']  ?? '');
        $pass  = trim($_POST['password'] ?? '');

        if (!$id || !$nama || !$email || !$role) {
            echo json_encode(['success' => false, 'msg' => 'Field wajib tidak boleh kosong.']); exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'msg' => 'Format email tidak valid.']); exit;
        }
        if (!in_array($role, ['admin','dosen','mahasiswa'])) {
            echo json_encode(['success' => false, 'msg' => 'Role tidak valid.']); exit;
        }

        // Cek duplikat email (kecuali diri sendiri)
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $chk->bind_param('si', $email, $id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $chk->close();
            echo json_encode(['success' => false, 'msg' => 'Email sudah dipakai user lain.']); exit;
        }
        $chk->close();

        if ($pass !== '') {
            if (strlen($pass) < 6) {
                echo json_encode(['success' => false, 'msg' => 'Password minimal 6 karakter.']); exit;
            }
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET nama=?, email=?, role=?, password=? WHERE id=?");
            $stmt->bind_param('ssssi', $nama, $email, $role, $hash, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET nama=?, email=?, role=? WHERE id=?");
            $stmt->bind_param('sssi', $nama, $email, $role, $id);
        }
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => $ok, 'msg' => $ok ? 'User berhasil diperbarui.' : 'Gagal memperbarui.']);
        exit;
    }

    // ── RESET PASSWORD ──
    if ($action === 'reset_password') {
        $id      = (int)($_POST['id'] ?? 0);
        $newpass = trim($_POST['new_password'] ?? '');

        if (!$id) { echo json_encode(['success' => false, 'msg' => 'ID tidak valid.']); exit; }

        // Generate jika kosong
        if ($newpass === '') {
            $newpass = bin2hex(random_bytes(5)); // 10 char acak
        }
        if (strlen($newpass) < 6) {
            echo json_encode(['success' => false, 'msg' => 'Password minimal 6 karakter.']); exit;
        }

        $hash = password_hash($newpass, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param('si', $hash, $id);
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => $ok, 'msg' => $ok ? "Password berhasil direset. Password baru: <strong>$newpass</strong>" : 'Gagal reset password.', 'new_password' => $newpass]);
        exit;
    }

    // ── DELETE ──
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'msg' => 'ID tidak valid.']); exit; }

        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => $ok, 'msg' => $ok ? 'User berhasil dihapus.' : 'Gagal menghapus.']);
        exit;
    }

    // ── EXPORT CSV ──
    if ($action === 'export_csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="users_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Nama', 'Email', 'Role', 'Dibuat']);
        $result = $conn->query("SELECT id, nama, email, role, created_at FROM users ORDER BY id ASC");
        while ($row = $result->fetch_assoc()) {
            fputcsv($out, [$row['id'], $row['nama'], $row['email'], $row['role'], $row['created_at']]);
        }
        fclose($out);
        exit;
    }

    echo json_encode(['success' => false, 'msg' => 'Action tidak dikenal.']);
    exit;
}
?>