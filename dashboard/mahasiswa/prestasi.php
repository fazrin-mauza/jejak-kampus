<?php
require_once __DIR__ . '/header.php';
// Hapus navigasi bawaan agar halaman eksklusif
// require_once __DIR__ . '/navigasi.php';

$user_id = $user['id'] ?? 0;
$mahasiswa_id = 0;
$nama_mahasiswa = $user['nama'] ?? 'Mahasiswa';
$kelas = '';

if ($user_id) {
    $stmt = $conn->prepare("SELECT m.id, m.nama, k.nama_kelas FROM mahasiswa m LEFT JOIN kelas k ON m.kelas_id = k.id WHERE m.user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $mahasiswa_id = $row['id'];
        $nama_mahasiswa = $row['nama'] ?? $nama_mahasiswa;
        $kelas = $row['nama_kelas'] ?? '';
    }
    $stmt->close();
}

// === AMBIL DATA REAL DARI DATABASE ===
$total_hadir = 0;
$total_sesi = 0;
$total_alpha = 0;
$total_izin_sakit = 0;
$streak_hari = 0;

if ($mahasiswa_id) {
    // Total hadir (hadir + telat)
    $q = $conn->query("SELECT COUNT(*) as total FROM absensi a JOIN sesi_absensi s ON a.sesi_id = s.id WHERE a.mahasiswa_id = $mahasiswa_id AND a.status IN ('hadir', 'telat')");
    $total_hadir = $q->fetch_assoc()['total'] ?? 0;
    
    // Total sesi perkuliahan yang sudah lewat
    $q = $conn->query("SELECT COUNT(*) as total FROM sesi_absensi s JOIN jadwal j ON s.jadwal_id = j.id JOIN kelas k ON j.kelas_id = k.id JOIN mahasiswa m ON m.kelas_id = k.id WHERE m.id = $mahasiswa_id AND s.tanggal <= CURDATE()");
    $total_sesi = $q->fetch_assoc()['total'] ?? 0;
    
    // Total alpha
    $q = $conn->query("SELECT COUNT(*) as total FROM absensi a WHERE a.mahasiswa_id = $mahasiswa_id AND a.status = 'alpha'");
    $total_alpha = $q->fetch_assoc()['total'] ?? 0;
    
    // Total izin/sakit disetujui
    $q = $conn->query("SELECT COUNT(*) as total FROM izin i WHERE i.mahasiswa_id = $mahasiswa_id AND i.status = 'disetujui' AND i.jenis IN ('izin', 'sakit')");
    $total_izin_sakit = $q->fetch_assoc()['total'] ?? 0;
    
    // Streak (hari berturut-turut hadir)
    $q = $conn->query("SELECT DISTINCT DATE(s.tanggal) as tgl FROM absensi a JOIN sesi_absensi s ON a.sesi_id = s.id WHERE a.mahasiswa_id = $mahasiswa_id AND a.status IN ('hadir', 'telat') ORDER BY s.tanggal DESC LIMIT 30");
    $dates = [];
    while ($row = $q->fetch_assoc()) {
        $dates[] = $row['tgl'];
    }
    $streak = 0;
    $today = date('Y-m-d');
    foreach ($dates as $date) {
        if ($date == $today || (strtotime($today) - strtotime($date) <= 86400 * $streak)) {
            $streak++;
        } else {
            break;
        }
    }
    $streak_hari = $streak;
}

// === KUTIPAN DINAMIS ===
$quotes = [
    ["text" => "Perjalanan seribu mil diawali dengan satu langkah.", "author" => "Lao Tzu"],
    ["text" => "Jangan pernah menyerah, karena hari ini sulit, besok akan lebih baik.", "author" => "Unknown"],
    ["text" => "Kesuksesan bukanlah akhir, kegagalan bukanlah fatal.", "author" => "Winston Churchill"],
    ["text" => "Lakukan yang terbaik sampai kamu tahu lebih baik.", "author" => "Maya Angelou"],
    ["text" => "Pendidikan adalah senjata paling ampuh untuk mengubah dunia.", "author" => "Nelson Mandela"],
    ["text" => "Kesempatan tidak datang dengan sendirinya, kamu yang menciptakannya.", "author" => "Chris Grosser"],
    ["text" => "Belajar tanpa berpikir adalah sia-sia, berpikir tanpa belajar adalah berbahaya.", "author" => "Confucius"],
    ["text" => "Jadilah seperti bunga yang memberikan keharuman meski telah layu.", "author" => "Unknown"],
    ["text" => "Ilmu tanpa amal bagaikan pohon tanpa buah.", "author" => "Ali bin Abi Thalib"],
    ["text" => "Barang siapa bersungguh-sungguh, pasti akan berhasil.", "author" => "Unknown"],
    ["text" => "Waktu adalah guru terbaik, sayangnya ia membunuh semua muridnya.", "author" => "Berthold Auerbach"],
    ["text" => "Jangan biarkan masa lalu mencuri masa depanmu.", "author" => "Unknown"],
    ["text" => "Hari ini harus lebih baik dari kemarin.", "author" => "Unknown"],
];

$dayOfYear = date('z');
$quoteIndex = $dayOfYear % count($quotes);
$todayQuote = $quotes[$quoteIndex];
$quoteText = $todayQuote['text'];
$quoteAuthor = $todayQuote['author'];

// === BADGES dengan progress REAL dari database ===
$badges = [
    ['id' => 1, 'nama' => 'Warrior', 'icon' => 'seedling', 'deskripsi' => '1 kali absen hadir', 'target' => 1, 'progress' => min(1, $total_hadir), 'locked' => $total_hadir < 1, 'color' => '#10B981'],
    ['id' => 2, 'nama' => 'Elite', 'icon' => 'book', 'deskripsi' => '10 kali absen hadir', 'target' => 10, 'progress' => min(10, $total_hadir), 'locked' => $total_hadir < 10, 'color' => '#3B82F6'],
    ['id' => 3, 'nama' => 'Master', 'icon' => 'graduation', 'deskripsi' => '25 kali absen hadir', 'target' => 25, 'progress' => min(25, $total_hadir), 'locked' => $total_hadir < 25, 'color' => '#8B5CF6'],
    ['id' => 4, 'nama' => 'Grandmaster', 'icon' => 'trophy', 'deskripsi' => '50 kali absen hadir', 'target' => 50, 'progress' => min(50, $total_hadir), 'locked' => $total_hadir < 50, 'color' => '#F59E0B'],
    ['id' => 5, 'nama' => 'Epic', 'icon' => 'shield', 'deskripsi' => '75 kali absen hadir', 'target' => 75, 'progress' => min(75, $total_hadir), 'locked' => $total_hadir < 75, 'color' => '#A855F7'],
    ['id' => 6, 'nama' => 'Legend', 'icon' => 'flame', 'deskripsi' => '100 kali absen hadir', 'target' => 100, 'progress' => min(100, $total_hadir), 'locked' => $total_hadir < 100, 'color' => '#F97316'],
    ['id' => 7, 'nama' => 'Enemy Missing', 'icon' => 'heart', 'deskripsi' => '3 kali izin/sakit dengan surat', 'target' => 3, 'progress' => min(3, $total_izin_sakit), 'locked' => $total_izin_sakit < 3, 'color' => '#F59E0B'],
    ['id' => 8, 'nama' => 'Mythic', 'icon' => 'sparkle', 'deskripsi' => '120 kali absen hadir', 'target' => 120, 'progress' => min(120, $total_hadir), 'locked' => $total_hadir < 120, 'color' => '#EC4899'],
    ['id' => 9, 'nama' => 'Honor', 'icon' => 'badge', 'deskripsi' => '130 kali absen hadir', 'target' => 130, 'progress' => min(130, $total_hadir), 'locked' => $total_hadir < 130, 'color' => '#06B6D4'],
    ['id' => 10, 'nama' => 'Glory', 'icon' => 'crown', 'deskripsi' => '140 kali absen hadir', 'target' => 140, 'progress' => min(140, $total_hadir), 'locked' => $total_hadir < 140, 'color' => '#FBBF24'],
    ['id' => 11, 'nama' => 'Immortal', 'icon' => 'heart', 'deskripsi' => '0 kali alpha sepanjang semester', 'target' => 1, 'progress' => $total_alpha > 0 ? 0 : 1, 'locked' => $total_alpha > 0, 'color' => '#EF4444'],
];

$total_badges = count($badges);
$unlocked_badges = count(array_filter($badges, function($b) { return !$b['locked']; }));
$persen_prestasi = $total_badges > 0 ? round(($unlocked_badges / $total_badges) * 100) : 0;

function getBadgeIcon($iconName) {
    $icons = [
        'seedling' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/><circle cx="12" cy="12" r="3"/></svg>',
        'book' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
        'graduation' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 1.1 2.7 2 6 2s6-.9 6-2v-5"/></svg>',
        'trophy' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v4M4 2v4h16V2M6 2v6c0 3.3 2.7 6 6 6s6-2.7 6-6V2"/><path d="M12 14v8M8 22h8"/></svg>',
        'shield' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><circle cx="12" cy="12" r="3"/></svg>',
        'heart' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
        'flame' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8.5 14.5A4.5 4.5 0 0 0 13 19c1.5 0 2.5-.5 3-1.5M12 2c-1.5 3-4 5-4 8.5 0 3 2 5.5 4.5 5.5S17 13.5 17 10.5c0-3-2-5.5-5-8.5z"/><path d="M12 5v8"/></svg>',
        'sparkle' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.64 5.64l2.12 2.12M16.24 16.24l2.12 2.12M5.64 18.36l2.12-2.12M16.24 7.76l2.12-2.12"/><circle cx="12" cy="12" r="4"/></svg>',
        'badge' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>',
        'crown' => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12l2-8 4 6 4-6 4 6 4-6 2 8-2 8H4l-2-8z"/></svg>',
    ];
    return $icons[$iconName] ?? $icons['badge'];
}

$back_url = '/dashboard/mahasiswa';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prestasi - Jejak Kampus</title>
   <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
   <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg-deep: #050510;
            --accent: #f97316; --accent2: #fb923c; --glow1: #8b5cf6; --glow2: #3b82f6;
            --gold: #fbbf24; --text1: #f8fafc; --text2: #cbd5e1; --text3: #94a3b8;
            --border: rgba(255,255,255,0.08); --card-bg: rgba(15,15,30,0.7);
        }
        body {
            background: var(--bg-deep);
            color: var(--text1);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* ===== COSMIC BACKGROUND CANVAS ===== */
        #cosmicCanvas {
            position: fixed; inset: 0; z-index: 0;
        }

        /* ===== LOADING SCREEN ===== */
        #loading {
            position: fixed; inset: 0; background: var(--bg-deep); z-index: 9999;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            transition: opacity 0.7s ease, visibility 0.7s ease;
        }
        #loading.hide { opacity: 0; visibility: hidden; pointer-events: none; }
        #loading canvas { position: absolute; inset: 0; width: 100%; height: 100%; }
        .load-inner { position: relative; z-index: 2; text-align: center; }
        .load-logo {
            font-family: 'Orbitron', sans-serif; font-size: 36px; font-weight: 900;
            background: linear-gradient(135deg, #f97316, #fb923c, #fbbf24);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 20px rgba(249,115,22,0.6));
            margin-bottom: 8px; letter-spacing: 4px;
            animation: logoGlow 2s ease-in-out infinite alternate;
        }
        @keyframes logoGlow {
            0% { filter: drop-shadow(0 0 10px rgba(249,115,22,0.4)); }
            100% { filter: drop-shadow(0 0 30px rgba(249,115,22,0.9)); }
        }
        .load-sub { color: var(--text3); font-size: 13px; letter-spacing: 6px; text-transform: uppercase; margin-bottom: 40px; font-family: 'Inter', sans-serif; font-weight: 500; }
        .load-bar-wrap { width: 280px; height: 3px; background: rgba(255,255,255,0.08); border-radius: 2px; margin: 0 auto 16px; position: relative; overflow: visible; }
        .load-bar { height: 100%; width: 0; background: linear-gradient(90deg, #f97316, #fbbf24); border-radius: 2px; transition: width 0.1s linear; position: relative; }
        .load-bar::after {
            content: ''; position: absolute; right: -1px; top: 50%; transform: translateY(-50%);
            width: 8px; height: 8px; background: #fbbf24; border-radius: 50%;
            box-shadow: 0 0 12px #fbbf24, 0 0 24px #fbbf24;
        }
        .load-pct { font-family: 'Orbitron', sans-serif; font-size: 22px; font-weight: 700; color: #fbbf24; margin-bottom: 8px; }
        .load-status { color: var(--text3); font-size: 11px; letter-spacing: 3px; text-transform: uppercase; height: 16px; font-family: 'Inter', sans-serif; font-weight: 500; }
        .load-ring { position: relative; width: 100px; height: 100px; margin: 0 auto 32px; }
        .load-ring svg { animation: spinRing 3s linear infinite; }
        @keyframes spinRing { to { transform: rotate(360deg); } }

        /* ===== MAIN CONTENT ===== */
        #main { opacity: 0; transform: translateY(20px); transition: all 0.6s ease; position: relative; z-index: 1; }
        #main.show { opacity: 1; transform: translateY(0); }
        .container { max-width: 960px; margin: 0 auto; padding: 20px 20px; }

        /* TOP BAR EXIT */
        .topbar-exit {
            display: flex; justify-content: flex-end; padding: 16px 20px;
            background: rgba(10,10,25,0.7); backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border); margin-bottom: 28px;
            position: sticky; top: 0; z-index: 10;
        }
        .exit-btn {
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 12px;
            padding: 8px 18px; color: var(--text2); font-family: 'Inter', sans-serif;
            font-size: 13px; font-weight: 600; letter-spacing: 0.5px; cursor: pointer;
            display: flex; align-items: center; gap: 6px; transition: all 0.3s; text-decoration: none;
            backdrop-filter: blur(8px);
        }
        .exit-btn:hover { border-color: var(--accent); color: var(--accent); box-shadow: 0 0 16px rgba(249,115,22,0.25); background: rgba(249,115,22,0.08); }

        /* STATS ROW */
        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 28px; }
        .scard {
            background: var(--card-bg); backdrop-filter: blur(12px);
            border: 1px solid var(--border); border-radius: 18px;
            padding: 18px 16px; position: relative; overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s, border-color 0.3s;
        }
        .scard:hover {
            transform: translateY(-4px);
            border-color: rgba(255,255,255,0.15);
            box-shadow: 0 12px 32px rgba(0,0,0,0.5), 0 0 20px rgba(139,92,246,0.1);
        }
        .scard::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
            background: var(--card-color, #f97316);
            box-shadow: 0 0 12px var(--card-color, #f97316);
        }
        .scard-icon { margin-bottom: 10px; opacity: 0.9; }
        .scard-label { font-size: 11px; color: var(--text3); letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 6px; font-weight: 600; }
        .scard-val { font-family: 'Orbitron', sans-serif; font-size: 28px; font-weight: 800; color: var(--card-color, #f97316); line-height: 1; margin-bottom: 4px; }
        .scard-sub { font-size: 11px; color: var(--text3); font-weight: 500; }
        .scard-bar { height: 4px; background: rgba(255,255,255,0.06); border-radius: 2px; margin-top: 12px; overflow: hidden; }
        .scard-bar-fill {
            height: 100%; border-radius: 2px; background: var(--card-color, #f97316);
            box-shadow: 0 0 10px var(--card-color, #f97316);
            animation: barFill 1.2s ease-out forwards; transform-origin: left;
        }
        @keyframes barFill { from { width: 0 !important; } }

        /* QUOTE BOX */
        .quote-box {
            background: var(--card-bg); backdrop-filter: blur(12px);
            border: 1px solid rgba(139,92,246,0.25); border-radius: 18px;
            padding: 24px 28px; margin-bottom: 28px; position: relative; overflow: hidden;
        }
        .quote-box::before {
            content: ''; position: absolute; top: -50%; right: -20%; width: 250px; height: 250px;
            background: radial-gradient(circle, rgba(139,92,246,0.12), transparent 70%);
        }
        .quote-text { font-size: 16px; color: var(--text1); font-style: italic; line-height: 1.7; margin-bottom: 10px; font-weight: 500; position: relative; }
        .quote-author { font-size: 12px; color: var(--text3); letter-spacing: 1px; font-weight: 600; }

        /* SECTION HEADER */
        .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
        .section-title {
            font-family: 'Orbitron', sans-serif; font-size: 14px; font-weight: 700;
            color: var(--text1); letter-spacing: 2px; display: flex; align-items: center; gap: 8px;
        }
        .section-title::before { content: '//'; color: var(--accent); font-size: 12px; }
        .section-badge {
            background: rgba(249,115,22,0.12); border: 1px solid rgba(249,115,22,0.3);
            border-radius: 20px; padding: 5px 14px; font-size: 11px; color: var(--accent);
            font-weight: 600;
        }

        /* BADGE GRID */
        .badge-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 28px; }
        .bcard {
            background: var(--card-bg); backdrop-filter: blur(12px);
            border: 1px solid var(--border); border-radius: 18px;
            padding: 18px; position: relative; overflow: hidden;
            transition: all 0.35s ease;
        }
        .bcard.unlocked {
            border-color: rgba(249,115,22,0.35);
            background: linear-gradient(135deg, rgba(15,15,30,0.8), rgba(249,115,22,0.04));
        }
        .bcard.unlocked:hover {
            border-color: rgba(249,115,22,0.7);
            box-shadow: 0 0 24px rgba(249,115,22,0.2), 0 8px 28px rgba(0,0,0,0.5);
            transform: translateY(-6px) scale(1.02);
        }
        .bcard.locked { opacity: 0.45; }
        .bcard.locked:hover { opacity: 0.65; transform: translateY(-2px); }
        .bcard.unlocked::before {
            content: ''; position: absolute; top: 0; left: -100%; width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent);
            animation: shimmer 3s ease-in-out infinite;
        }
        @keyframes shimmer { 0%, 80% { left: -100%; } 100% { left: 150%; } }
        .bcard-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px; }
        .bcard-icon {
            width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
        }
        .bcard.unlocked .bcard-icon { box-shadow: 0 0 14px rgba(249,115,22,0.25); }
        .bcard-status-unlocked {
            width: 22px; height: 22px; background: var(--accent); border-radius: 50%;
            display: flex; align-items: center; justify-content: center; color: white; font-weight: 700;
            box-shadow: 0 0 10px var(--accent); flex-shrink: 0;
        }
        .bcard-status-locked {
            width: 22px; height: 22px; background: rgba(255,255,255,0.06); border-radius: 50%;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .bcard-name { font-size: 14px; font-weight: 700; color: var(--text1); margin-bottom: 4px; letter-spacing: -0.2px; }
        .bcard-desc { font-size: 11px; color: var(--text3); line-height: 1.4; margin-bottom: 14px; font-weight: 500; }
        .bcard-prog-wrap { background: rgba(255,255,255,0.05); border-radius: 4px; height: 5px; overflow: hidden; }
        .bcard-prog { height: 100%; border-radius: 4px; animation: barFill 1.4s ease-out forwards; }
        .bcard-prog-txt { font-size: 10px; color: var(--text3); margin-top: 8px; display: flex; justify-content: space-between; font-weight: 500; }

        /* SHARE BOX */
        .share-box {
            background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(139,92,246,0.1));
            backdrop-filter: blur(12px);
            border: 1px solid rgba(99,102,241,0.25); border-radius: 18px;
            padding: 24px 28px; margin-bottom: 28px;
            display: flex; align-items: center; justify-content: space-between; gap: 20px;
            position: relative; overflow: hidden;
        }
        .share-box h3 { font-family: 'Orbitron', sans-serif; font-size: 16px; color: var(--text1); margin-bottom: 4px; letter-spacing: 1px; }
        .share-box p { font-size: 13px; color: var(--text2); font-weight: 500; }
        .share-btn {
            background: linear-gradient(135deg, #6366f1, #8b5cf6); border: none;
            border-radius: 12px; padding: 11px 22px; color: white;
            font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 700;
            cursor: pointer; display: flex; align-items: center; gap: 8px; letter-spacing: 0.5px;
            transition: all 0.3s; box-shadow: 0 0 18px rgba(99,102,241,0.5); flex-shrink: 0;
        }
        .share-btn:hover { transform: scale(1.05); box-shadow: 0 0 28px rgba(99,102,241,0.7); }

        /* RECENT */
        .recent-list { display: flex; flex-wrap: wrap; gap: 12px; }
        .recent-item {
            background: rgba(251,191,36,0.06); backdrop-filter: blur(8px);
            border: 1px solid rgba(251,191,36,0.15); border-radius: 14px;
            padding: 12px 16px; display: flex; align-items: center; gap: 12px; flex: 1; min-width: 180px;
            transition: all 0.3s;
        }
        .recent-item:hover { border-color: rgba(251,191,36,0.4); background: rgba(251,191,36,0.1); }
        .recent-icon svg { width: 22px; height: 22px; }
        .recent-name { font-size: 13px; font-weight: 700; color: var(--text1); }
        .recent-desc { font-size: 10px; color: var(--text3); font-weight: 500; }

        @media(max-width:640px) {
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            .badge-grid { grid-template-columns: repeat(2, 1fr); }
            .share-box { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

<!-- LOADING SCREEN -->
<div id="loading">
    <canvas id="loadCanvas"></canvas>
    <div class="load-inner">
        <div class="load-ring">
            <svg width="100" height="100" viewBox="0 0 100 100">
                <defs>
                    <linearGradient id="ringGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#f97316"/>
                        <stop offset="100%" stop-color="#fbbf24"/>
                    </linearGradient>
                </defs>
                <polygon points="50,5 95,27.5 95,72.5 50,95 5,72.5 5,27.5" fill="none" stroke="url(#ringGrad)" stroke-width="2" stroke-dasharray="240" opacity="0.25"/>
                <polygon points="50,15 85,32.5 85,67.5 50,85 15,67.5 15,32.5" fill="none" stroke="url(#ringGrad)" stroke-width="1.5" stroke-dasharray="8 4" opacity="0.5"/>
                <polygon points="50,5 95,27.5 95,72.5 50,95 5,72.5 5,27.5" fill="none" stroke="url(#ringGrad)" stroke-width="2.5" stroke-dasharray="30 210">
                    <animateTransform attributeName="transform" type="rotate" from="0 50 50" to="360 50 50" dur="2s" repeatCount="indefinite"/>
                </polygon>
                <circle cx="50" cy="50" r="16" fill="none" stroke="#f97316" stroke-width="1.5" opacity="0.3"/>
                <polygon points="50,34 57,45 50,54 43,45" fill="none" stroke="#fbbf24" stroke-width="1.5"/>
                <circle cx="50" cy="45" r="3.5" fill="#fbbf24" opacity="0.9"/>
            </svg>
        </div>
        <div class="load-logo">JEJAK TANTANGAN</div>
        <div class="load-sub">Achievement System</div>
        <div class="load-pct" id="loadPct">0%</div>
        <div class="load-bar-wrap"><div class="load-bar" id="loadBar"></div></div>
        <div class="load-status" id="loadStatus">INITIALIZING...</div>
    </div>
</div>

<!-- COSMIC BACKGROUND -->
<canvas id="cosmicCanvas"></canvas>

<!-- MAIN CONTENT -->
<div id="main">
    <div class="topbar-exit">
        <a href="<?= htmlspecialchars($back_url) ?>" class="exit-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            KEMBALI
        </a>
    </div>

    <div class="container">
        <!-- STATS -->
        <div class="stats-row">
            <div class="scard" style="--card-color:#8b5cf6">
                <div class="scard-icon"><?= getBadgeIcon('badge') ?></div>
                <div class="scard-label">Total Lencana</div>
                <div class="scard-val"><?= $unlocked_badges ?>/<?= $total_badges ?></div>
                <div class="scard-sub">Lencana terkumpul</div>
                <div class="scard-bar"><div class="scard-bar-fill" style="width:<?= $persen_prestasi ?>%"></div></div>
            </div>
            <div class="scard" style="--card-color:#f97316">
                <div class="scard-icon"><?= getBadgeIcon('flame') ?></div>
                <div class="scard-label">Streak Belajar</div>
                <div class="scard-val"><?= $streak_hari ?></div>
                <div class="scard-sub">Hari berturut-turut</div>
            </div>
            <div class="scard" style="--card-color:#10b981">
                <div class="scard-icon"><?= getBadgeIcon('seedling') ?></div>
                <div class="scard-label">Total Kehadiran</div>
                <div class="scard-val"><?= $total_hadir ?>/<?= $total_sesi ?></div>
                <div class="scard-sub">dari total sesi</div>
            </div>
            <div class="scard" style="--card-color:#3b82f6">
                <div class="scard-icon"><?= getBadgeIcon('heart') ?></div>
                <div class="scard-label">Izin / Sakit</div>
                <div class="scard-val"><?= $total_izin_sakit ?></div>
                <div class="scard-sub">Dengan surat resmi</div>
            </div>
        </div>

        <!-- QUOTE -->
        <div class="quote-box">
            <div class="quote-text">&ldquo;<?= htmlspecialchars($quoteText) ?>&rdquo;</div>
            <div class="quote-author">&mdash; <?= htmlspecialchars($quoteAuthor) ?></div>
        </div>

        <!-- BADGES -->
        <div class="section-header">
            <div class="section-title">KOLEKSI LENCANA</div>
            <div class="section-badge"><?= $unlocked_badges ?> / <?= $total_badges ?> DIRAIH</div>
        </div>
        <div class="badge-grid" id="badgeGrid"></div>

        <!-- SHARE -->
        <div class="share-box">
            <div>
                <h3>BAGIKAN PRESTASIMU</h3>
                <p>Tunjukkan pencapaianmu kepada dunia</p>
            </div>
            <button class="share-btn" onclick="generateShareCard()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="2" width="20" height="20" rx="2.5"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                EKSPOR GAMBAR
            </button>
        </div>

        <!-- RECENT -->
        <div class="section-header">
            <div class="section-title">PRESTASI TERBARU</div>
        </div>
        <div class="recent-list" id="recentList"></div>
    </div>
</div>

<div id="sharePreview" style="position:fixed;top:-9999px;left:-9999px;width:520px;z-index:-1"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
// ===== DATA DARI PHP =====
const badgesData = <?= json_encode($badges) ?>;
const userData = {
    nama: '<?= addslashes($nama_mahasiswa) ?>',
    kelas: '<?= addslashes($kelas) ?>',
    unlocked: <?= $unlocked_badges ?>,
    total: <?= $total_badges ?>,
    persen: <?= $persen_prestasi ?>,
    streak: <?= $streak_hari ?>,
    totalHadir: <?= $total_hadir ?>,
    totalSesi: <?= $total_sesi ?>,
    totalIzinSakit: <?= $total_izin_sakit ?>,
    quote: '<?= addslashes($quoteText) ?>',
    author: '<?= addslashes($quoteAuthor) ?>'
};

// ===== ICON SVGs =====
function getIconSvg(icon) {
    const svgs = {
        seedling: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/><circle cx="12" cy="12" r="3"/></svg>',
        book: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
        graduation: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 1.1 2.7 2 6 2s6-.9 6-2v-5"/></svg>',
        trophy: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v4M4 2v4h16V2M6 2v6c0 3.3 2.7 6 6 6s6-2.7 6-6V2"/><path d="M12 14v8M8 22h8"/></svg>',
        shield: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><circle cx="12" cy="12" r="3"/></svg>',
        heart: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
        flame: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8.5 14.5A4.5 4.5 0 0 0 13 19c1.5 0 2.5-.5 3-1.5M12 2c-1.5 3-4 5-4 8.5 0 3 2 5.5 4.5 5.5S17 13.5 17 10.5c0-3-2-5.5-5-8.5z"/><path d="M12 5v8"/></svg>',
        sparkle: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.64 5.64l2.12 2.12M16.24 16.24l2.12 2.12M5.64 18.36l2.12-2.12M16.24 7.76l2.12-2.12"/><circle cx="12" cy="12" r="4"/></svg>',
        badge: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>',
        crown: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12l2-8 4 6 4-6 4 6 4-6 2 8-2 8H4l-2-8z"/></svg>',
    };
    return svgs[icon] || svgs.badge;
}

// ===== RENDER BADGES =====
function renderBadges() {
    const grid = document.getElementById('badgeGrid');
    if (!grid) return;
    
    grid.innerHTML = badgesData.map(b => {
        const pct = Math.min(100, Math.round((b.progress / b.target) * 100));
        const cls = b.locked ? 'locked' : 'unlocked';
        return `<div class="bcard ${cls}">
            <div class="bcard-top">
                <div class="bcard-icon" style="color:${b.color}">${getIconSvg(b.icon)}</div>
                ${b.locked
                    ? '<div class="bcard-status-locked"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></div>'
                    : '<div class="bcard-status-unlocked"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>'}
            </div>
            <div class="bcard-name">${b.nama}</div>
            <div class="bcard-desc">${b.deskripsi}</div>
            <div class="bcard-prog-wrap">
                <div class="bcard-prog" style="width:${pct}%;background:linear-gradient(90deg,${b.color}99,${b.color})"></div>
            </div>
            <div class="bcard-prog-txt">
                <span>${b.locked ? b.progress + ' / ' + b.target : 'Telah diraih'}</span>
                <span style="color:${b.color};font-weight:700">${pct}%</span>
            </div>
        </div>`;
    }).join('');
}

// ===== RENDER RECENT =====
function renderRecent() {
    const list = document.getElementById('recentList');
    if (!list) return;
    
    const unlocked = badgesData.filter(b => !b.locked).slice(0, 4);
    list.innerHTML = unlocked.map(b => `
    <div class="recent-item">
        <div class="recent-icon" style="color:${b.color}">${getIconSvg(b.icon)}</div>
        <div>
            <div class="recent-name">${b.nama}</div>
            <div class="recent-desc">${b.deskripsi}</div>
        </div>
    </div>`).join('');
}

// ===== COSMIC BACKGROUND =====
const cosmicCanvas = document.getElementById('cosmicCanvas');
const cctx = cosmicCanvas.getContext('2d');
cosmicCanvas.width = window.innerWidth;
cosmicCanvas.height = window.innerHeight;
window.addEventListener('resize', () => {
    cosmicCanvas.width = window.innerWidth;
    cosmicCanvas.height = window.innerHeight;
});

const stars = [];
for (let i = 0; i < 180; i++) {
    stars.push({
        x: Math.random() * cosmicCanvas.width,
        y: Math.random() * cosmicCanvas.height,
        r: Math.random() * 1.6 + 0.3,
        twinkle: Math.random() * Math.PI * 2,
        speed: Math.random() * 0.015 + 0.005,
        alpha: Math.random() * 0.7 + 0.3,
        color: Math.random() < 0.8 ? '255,255,255' : (Math.random() < 0.5 ? '139,92,246' : '249,115,22')
    });
}

const nebulae = [];
for (let i = 0; i < 5; i++) {
    nebulae.push({
        x: Math.random() * cosmicCanvas.width,
        y: Math.random() * cosmicCanvas.height,
        r: Math.random() * 250 + 100,
        dx: (Math.random() - 0.5) * 0.15,
        dy: (Math.random() - 0.5) * 0.15,
        color: i < 3 ? '139,92,246' : '59,130,246'
    });
}

function drawCosmic() {
    cctx.clearRect(0, 0, cosmicCanvas.width, cosmicCanvas.height);
    
    nebulae.forEach(n => {
        const grad = cctx.createRadialGradient(n.x, n.y, 0, n.x, n.y, n.r);
        grad.addColorStop(0, `rgba(${n.color}, 0.06)`);
        grad.addColorStop(0.5, `rgba(${n.color}, 0.03)`);
        grad.addColorStop(1, 'rgba(0,0,0,0)');
        cctx.fillStyle = grad;
        cctx.beginPath();
        cctx.arc(n.x, n.y, n.r, 0, Math.PI * 2);
        cctx.fill();
        
        n.x += n.dx;
        n.y += n.dy;
        if (n.x < -n.r) n.x = cosmicCanvas.width + n.r;
        if (n.x > cosmicCanvas.width + n.r) n.x = -n.r;
        if (n.y < -n.r) n.y = cosmicCanvas.height + n.r;
        if (n.y > cosmicCanvas.height + n.r) n.y = -n.r;
    });
    
    stars.forEach(s => {
        s.twinkle += s.speed;
        const a = s.alpha * (0.6 + 0.4 * Math.sin(s.twinkle));
        cctx.beginPath();
        cctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
        cctx.fillStyle = `rgba(${s.color},${a})`;
        cctx.fill();
        if (s.r > 1.2) {
            cctx.beginPath();
            cctx.arc(s.x, s.y, s.r * 2.5, 0, Math.PI * 2);
            cctx.fillStyle = `rgba(${s.color},${a * 0.15})`;
            cctx.fill();
        }
    });
    
    requestAnimationFrame(drawCosmic);
}
drawCosmic();

// ===== LOADING ANIMATION =====
const loadCanvas = document.getElementById('loadCanvas');
const lctx = loadCanvas.getContext('2d');
loadCanvas.width = window.innerWidth;
loadCanvas.height = window.innerHeight;

const loadParticles = [];
for (let i = 0; i < 100; i++) {
    loadParticles.push({
        x: Math.random() * loadCanvas.width, y: Math.random() * loadCanvas.height,
        vx: (Math.random() - 0.5) * 0.6, vy: (Math.random() - 0.5) * 0.6,
        size: Math.random() * 2.2 + 0.5, alpha: Math.random() * 0.7 + 0.2,
        color: Math.random() > 0.5 ? '249,115,22' : '139,92,246'
    });
}

function animLoadParticles() {
    lctx.clearRect(0, 0, loadCanvas.width, loadCanvas.height);
    lctx.fillStyle = 'rgba(5,5,16,0.35)';
    lctx.fillRect(0, 0, loadCanvas.width, loadCanvas.height);
    loadParticles.forEach(p => {
        p.x += p.vx; p.y += p.vy;
        if (p.x < 0 || p.x > loadCanvas.width) p.vx *= -1;
        if (p.y < 0 || p.y > loadCanvas.height) p.vy *= -1;
        lctx.beginPath(); lctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
        lctx.fillStyle = `rgba(${p.color},${p.alpha})`; lctx.fill();
    });
    requestAnimationFrame(animLoadParticles);
}
animLoadParticles();

const statuses = ['INITIALIZING SYSTEMS...','LOADING ACHIEVEMENT DATA...','SYNCING BADGE REGISTRY...','CALIBRATING PROFILE...','PREPARING DASHBOARD...','SYSTEM READY'];
let pct = 0;
const bar = document.getElementById('loadBar');
const pctEl = document.getElementById('loadPct');
const statusEl = document.getElementById('loadStatus');

function tick() {
    if (pct >= 100) {
        statusEl.textContent = 'SYSTEM READY';
        setTimeout(() => {
            document.getElementById('loading').classList.add('hide');
            document.getElementById('main').classList.add('show');
            // Render setelah loading selesai
            renderBadges();
            renderRecent();
        }, 500);
        return;
    }
    pct += Math.random() * 3.5 + 1;
    if (pct > 100) pct = 100;
    if (bar) bar.style.width = pct + '%';
    if (pctEl) pctEl.textContent = Math.round(pct) + '%';
    if (statusEl) statusEl.textContent = statuses[Math.min(Math.floor(pct / 17), 5)];
    setTimeout(tick, 55);
}
setTimeout(tick, 350);

// ===== GENERATE SHARE CARD (pakai data REAL) =====
async function generateShareCard() {
    const sp = document.getElementById('sharePreview');
    
    // Ambil daftar lencana yang sudah terbuka
    const unlockedBadges = badgesData.filter(b => !b.locked);
    const badgesHtml = unlockedBadges.map(b => `<span style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:10px;padding:6px 12px;font-size:12px;color:rgba(226,232,240,0.85);font-weight:500">${b.nama}</span>`).join('');
    
    sp.innerHTML = `
    <div style="width:520px;padding:36px;background:linear-gradient(160deg,#08081a 0%,#0d0d28 40%,#120d30 100%);font-family:'Inter','Segoe UI',sans-serif;border-radius:24px;border:1px solid rgba(139,92,246,0.35);position:relative;overflow:hidden;color:#f1f5f9">
        <div style="position:absolute;top:-80px;right:-80px;width:240px;height:240px;background:radial-gradient(circle,rgba(249,115,22,0.25),transparent 70%);border-radius:50%"></div>
        <div style="position:absolute;bottom:-80px;left:-80px;width:240px;height:240px;background:radial-gradient(circle,rgba(139,92,246,0.22),transparent 70%);border-radius:50%"></div>
        <div style="text-align:center;margin-bottom:28px;position:relative">
            <div style="font-family:'Orbitron',sans-serif;font-size:11px;letter-spacing:3px;color:rgba(249,115,22,0.8);margin-bottom:10px">JEJAK KAMPUS</div>
            <div style="font-family:'Orbitron',sans-serif;font-size:24px;font-weight:900;background:linear-gradient(135deg,#f97316,#fbbf24);-webkit-background-clip:text;-webkit-text-fill-color:transparent">PENCAPAIAN</div>
            <div style="font-size:14px;color:rgba(203,213,225,0.7);margin-top:8px;font-weight:500">${userData.nama} • ${userData.kelas}</div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px">
            <div style="background:rgba(139,92,246,0.12);border:1px solid rgba(139,92,246,0.25);border-radius:16px;padding:18px;text-align:center">
                <div style="font-family:'Orbitron',sans-serif;font-size:34px;font-weight:900;color:#c4b5fd">${userData.unlocked}/${userData.total}</div>
                <div style="font-size:11px;letter-spacing:2px;color:rgba(148,163,184,0.8);margin-top:4px;font-weight:600">LENCANA</div>
            </div>
            <div style="background:rgba(249,115,22,0.1);border:1px solid rgba(249,115,22,0.25);border-radius:16px;padding:18px;text-align:center">
                <div style="font-family:'Orbitron',sans-serif;font-size:34px;font-weight:900;color:#fdba74">${userData.streak}</div>
                <div style="font-size:11px;letter-spacing:2px;color:rgba(148,163,184,0.8);margin-top:4px;font-weight:600">HARI STREAK</div>
            </div>
        </div>
        <div style="margin-bottom:20px">
            <div style="display:flex;justify-content:space-between;font-size:11px;color:rgba(148,163,184,0.7);margin-bottom:8px;font-weight:600">
                <span>PROGRESS PRESTASI</span><span style="color:#f97316">${userData.persen}%</span>
            </div>
            <div style="background:rgba(255,255,255,0.06);border-radius:4px;height:8px;overflow:hidden">
                <div style="width:${userData.persen}%;height:100%;background:linear-gradient(90deg,#f97316,#fbbf24);border-radius:4px;box-shadow:0 0 12px rgba(249,115,22,0.5)"></div>
            </div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:24px">
            ${badgesHtml}
        </div>
        <div style="border-top:1px solid rgba(255,255,255,0.06);padding-top:16px;text-align:center">
            <div style="font-style:italic;font-size:13px;color:rgba(203,213,225,0.65);font-weight:500">"${userData.quote}"</div>
            <div style="font-size:10px;color:rgba(148,163,184,0.4);margin-top:10px;letter-spacing:1px;font-weight:500">JEJAK KAMPUS • ${new Date().toLocaleDateString('id-ID')}</div>
        </div>
    </div>`;
    
    try {
        const canvas = await html2canvas(sp.firstElementChild, { scale: 2, backgroundColor: null, logging: false });
        const link = document.createElement('a');
        link.download = 'prestasi_jejak_kampus.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
        alert('Gambar berhasil disimpan!');
    } catch (e) {
        console.error(e);
        alert('Gagal menyimpan gambar.');
    }
}
</script>
</body>
</html>