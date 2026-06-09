<?php
require_once __DIR__ . '/../../../auth/check.php';
header('Content-Type: application/json');

$user_id = $user['id'] ?? 0;
$mahasiswa_id = 0;

if ($user_id) {
    $stmt = $conn->prepare("SELECT id FROM mahasiswa WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($mahasiswa_id);
    $stmt->fetch();
    $stmt->close();
}

if (!$mahasiswa_id) {
    echo json_encode(['success' => false, 'msg' => 'Data mahasiswa tidak ditemukan']);
    exit;
}

$action = $_POST['action'] ?? '';

// ─── UPLOAD FOTO ──────────────────────────────────
if ($action === 'upload_foto') {
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'msg' => 'Gagal mengunggah file']);
        exit;
    }
    
    $file = $_FILES['foto'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'msg' => 'Format file tidak diizinkan (JPG, PNG, GIF, WEBP)']);
        exit;
    }
    
    if ($file['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'msg' => 'Ukuran file maksimal 2MB']);
        exit;
    }
    
    // Buat direktori jika belum ada
    $uploadDir = __DIR__ . '/../../../uploads/profile/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Nama file: mahasiswa_{id}_{timestamp}.jpg
    $filename = 'mahasiswa_' . $mahasiswa_id . '_' . time() . '.jpg';
    $filepath = $uploadDir . $filename;
    
    // Resize dan simpan gambar
    $success = resizeAndSaveImage($file['tmp_name'], $filepath, $ext, 300);
    
    if ($success) {
        $url = '/uploads/profile/' . $filename;
        
        // Hapus foto lama jika ada
        $stmtOld = $conn->prepare("SELECT profile FROM users WHERE id = ?");
        $stmtOld->bind_param('i', $user_id);
        $stmtOld->execute();
        $stmtOld->bind_result($oldProfile);
        $stmtOld->fetch();
        $stmtOld->close();
        
        if ($oldProfile && file_exists(__DIR__ . '/../../../' . ltrim($oldProfile, '/'))) {
            @unlink(__DIR__ . '/../../../' . ltrim($oldProfile, '/'));
        }
        
        // Update kolom profile di tabel users
        $stmt = $conn->prepare("UPDATE users SET profile = ? WHERE id = ?");
        $stmt->bind_param('si', $url, $user_id);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode(['success' => true, 'msg' => 'Foto berhasil diunggah', 'url' => $url]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Gagal memproses gambar']);
    }
    exit;
}

// ─── UPDATE PROFIL ────────────────────────────────
if ($action === 'update_profil') {
    $nama = trim($_POST['nama'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? 'L';
    $no_hp = $_POST['no_hp'] ?? '';
    
    if (empty($nama)) {
        echo json_encode(['success' => false, 'msg' => 'Nama tidak boleh kosong']);
        exit;
    }
    
    if (!in_array($jenis_kelamin, ['L', 'P'])) {
        $jenis_kelamin = 'L';
    }
    
    // Update tabel mahasiswa
    $stmt = $conn->prepare("UPDATE mahasiswa SET nama = ?, tanggal_lahir = ?, jenis_kelamin = ? WHERE id = ?");
    $stmt->bind_param('sssi', $nama, $tanggal_lahir, $jenis_kelamin, $mahasiswa_id);
    
    if ($stmt->execute()) {
        // Update nama di tabel users
        $stmt2 = $conn->prepare("UPDATE users SET nama = ? WHERE id = ?");
        $stmt2->bind_param('si', $nama, $user_id);
        $stmt2->execute();
        $stmt2->close();
        
        // Update no_hp jika ada kolom (opsional, sesuaikan dengan struktur tabel)
        if (!empty($no_hp)) {
            // Cek apakah kolom no_hp ada di tabel mahasiswa
            $checkColumn = $conn->query("SHOW COLUMNS FROM mahasiswa LIKE 'no_hp'");
            if ($checkColumn && $checkColumn->num_rows > 0) {
                $stmt3 = $conn->prepare("UPDATE mahasiswa SET no_hp = ? WHERE id = ?");
                $stmt3->bind_param('si', $no_hp, $mahasiswa_id);
                $stmt3->execute();
                $stmt3->close();
            }
        }
        
        echo json_encode(['success' => true, 'msg' => 'Profil berhasil diperbarui']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Gagal memperbarui profil']);
    }
    $stmt->close();
    exit;
}

// ─── GANTI PASSWORD ────────────────────────────────
if ($action === 'ganti_password') {
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';
    
    if (empty($password_lama) || empty($password_baru) || empty($konfirmasi)) {
        echo json_encode(['success' => false, 'msg' => 'Semua field password harus diisi']);
        exit;
    }
    
    if ($password_baru !== $konfirmasi) {
        echo json_encode(['success' => false, 'msg' => 'Password baru dan konfirmasi tidak cocok']);
        exit;
    }
    
    if (strlen($password_baru) < 8) {
        echo json_encode(['success' => false, 'msg' => 'Password baru minimal 8 karakter']);
        exit;
    }
    
    // Ambil password lama dari database
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();
    
    // Verifikasi password lama
    if (!password_verify($password_lama, $hashed_password)) {
        echo json_encode(['success' => false, 'msg' => 'Password lama salah']);
        exit;
    }
    
    // Hash password baru
    $new_hashed = password_hash($password_baru, PASSWORD_DEFAULT);
    
    // Update password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param('si', $new_hashed, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'msg' => 'Password berhasil diubah']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Gagal mengubah password']);
    }
    $stmt->close();
    exit;
}

// ─── UPDATE PENGATURAN NOTIFIKASI ──────────────────
if ($action === 'update_notifikasi') {
    $key = $_POST['key'] ?? '';
    $value = $_POST['value'] ?? 0;
    
    // Simpan preferensi notifikasi (bisa disimpan di tabel settings_user atau session)
    // Untuk sementara, simpan di session
    $_SESSION['notif_' . $key] = $value;
    
    echo json_encode(['success' => true, 'msg' => 'Pengaturan berhasil disimpan']);
    exit;
}

// ─── GET DATA PROFIL ───────────────────────────────
if ($action === 'get_profil') {
    $stmt = $conn->prepare("SELECT m.id, m.nim, m.nama, m.tanggal_lahir, m.jenis_kelamin, 
                                   k.nama_kelas as kelas, m.status, u.email, u.profile
                            FROM mahasiswa m 
                            JOIN users u ON m.user_id = u.id 
                            LEFT JOIN kelas k ON m.kelas_id = k.id 
                            WHERE m.id = ? LIMIT 1");
    $stmt->bind_param('i', $mahasiswa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    if ($data) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Data tidak ditemukan']);
    }
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Aksi tidak dikenal']);
exit;

// ─── FUNGSI RESIZE & SAVE IMAGE ───────────────────
function resizeAndSaveImage($sourcePath, $destPath, $ext, $maxSize = 300) {
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $srcImage = @imagecreatefromjpeg($sourcePath);
            break;
        case 'png':
            $srcImage = @imagecreatefrompng($sourcePath);
            break;
        case 'gif':
            $srcImage = @imagecreatefromgif($sourcePath);
            break;
        case 'webp':
            $srcImage = @imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$srcImage) return false;
    
    $srcWidth = imagesx($srcImage);
    $srcHeight = imagesy($srcImage);
    
    if ($srcWidth > $srcHeight) {
        $newWidth = $maxSize;
        $newHeight = (int)($srcHeight * ($maxSize / $srcWidth));
    } else {
        $newHeight = $maxSize;
        $newWidth = (int)($srcWidth * ($maxSize / $srcHeight));
    }
    
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    if ($ext == 'png' || $ext == 'gif') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
        imagefill($newImage, 0, 0, $transparent);
    }
    
    imagecopyresampled($newImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);
    $result = imagejpeg($newImage, $destPath, 85);
    
    imagedestroy($srcImage);
    imagedestroy($newImage);
    
    return $result;
}
?>