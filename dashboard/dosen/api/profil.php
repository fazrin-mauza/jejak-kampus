<?php
require_once __DIR__ . '/../../../auth/check.php';
header('Content-Type: application/json');

$user_id = $user['id'] ?? 0;
$dosen_id = 0;
if ($user_id) {
    $stmt = $conn->prepare("SELECT id FROM dosen WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($dosen_id);
    $stmt->fetch();
    $stmt->close();
}

if (!$dosen_id) {
    echo json_encode(['success' => false, 'msg' => 'Data dosen tidak ditemukan']);
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
    
    // Nama file: dosen_{id}_{timestamp}.jpg (selalu jpg untuk hasil resize)
    $filename = 'dosen_' . $dosen_id . '_' . time() . '.jpg';
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
    
    if (empty($nama)) {
        echo json_encode(['success' => false, 'msg' => 'Nama tidak boleh kosong']);
        exit;
    }
    
    if (!in_array($jenis_kelamin, ['L', 'P'])) {
        $jenis_kelamin = 'L';
    }
    
    // Update tabel dosen
    $stmt = $conn->prepare("UPDATE dosen SET nama = ?, tanggal_lahir = ?, jenis_kelamin = ? WHERE id = ?");
    $stmt->bind_param('sssi', $nama, $tanggal_lahir, $jenis_kelamin, $dosen_id);
    
    if ($stmt->execute()) {
        // Update nama di tabel users
        $stmt2 = $conn->prepare("UPDATE users SET nama = ? WHERE id = ?");
        $stmt2->bind_param('si', $nama, $user_id);
        $stmt2->execute();
        $stmt2->close();
        
        echo json_encode(['success' => true, 'msg' => 'Profil berhasil diperbarui']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Gagal memperbarui profil']);
    }
    $stmt->close();
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Aksi tidak dikenal']);
exit;

// ─── FUNGSI RESIZE & SAVE IMAGE ───────────────────
function resizeAndSaveImage($sourcePath, $destPath, $ext, $maxSize = 300) {
    // Buat gambar dari sumber
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
    
    // Dapatkan dimensi asli
    $srcWidth = imagesx($srcImage);
    $srcHeight = imagesy($srcImage);
    
    // Hitung dimensi baru (maksimal $maxSize px)
    if ($srcWidth > $srcHeight) {
        $newWidth = $maxSize;
        $newHeight = (int)($srcHeight * ($maxSize / $srcWidth));
    } else {
        $newHeight = $maxSize;
        $newWidth = (int)($srcWidth * ($maxSize / $srcHeight));
    }
    
    // Buat canvas baru
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency untuk PNG/GIF
    if ($ext == 'png' || $ext == 'gif') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
        imagefill($newImage, 0, 0, $transparent);
    }
    
    // Resize
    imagecopyresampled($newImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);
    
    // Simpan sebagai JPEG (ukuran kecil, kualitas 85%)
    $result = imagejpeg($newImage, $destPath, 85);
    
    // Bersihkan memory
    imagedestroy($srcImage);
    imagedestroy($newImage);
    
    return $result;
}