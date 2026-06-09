<?php
require_once __DIR__ . '/../../auth/check.php';
$seg = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
$page = $seg[array_search('mahasiswa', $seg) + 1] ?? 'dashboard';

// Data dummy mahasiswa (nanti diganti dari database)
$mahasiswa = $mahasiswa ?? [
    'nama' => 'Fazrin Mauza Dwi Zuhudi',
    'nim' => '24050974090',
    'kelas' => 'PTI 2024C',
    'jurusan' => 'Pendidikan Teknologi Informasi'
];

$semester_aktif = $semester_aktif ?? 'Genap 2025/2026';
$pending_izin = $pending_izin ?? 1;

// Generate initial nama
$nama_parts = explode(' ', trim($mahasiswa['nama']));
$initial = strtoupper(substr($nama_parts[0], 0, 1));
$initial .= isset($nama_parts[1]) ? strtoupper(substr($nama_parts[1], 0, 1)) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Jejak Kampus - Dashboard Mahasiswa</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
    /* ══════════ PERBAIKAN MOBILE SIDEBAR ══════════ */
    .btn-menu-mobile {
        display: none !important;
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: var(--surface2);
        border: none;
        cursor: pointer;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: var(--text);
        flex-shrink: 0;
    }

    .btn-menu-mobile:hover {
        background: var(--border);
    }

    @media (max-width: 768px) {
        .btn-menu-mobile {
            display: flex !important;
        }
        
        .sidebar-close-btn {
            display: none !important;
        }
        
        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            position: fixed;
            z-index: 1000;
        }
        
        .sidebar.open {
            transform: translateX(0);
            box-shadow: 0 0 40px rgba(0,0,0,.2);
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar-overlay.open {
            opacity: 1;
            visibility: visible;
        }
    }

    /* Tambahan styling untuk mobile */
    @media (max-width: 480px) {
        .topbar-title {
            font-size: 14px !important;
        }
        .topbar-bread {
            font-size: 10px !important;
        }
        .stat-value {
            font-size: 20px !important;
        }
        .stat-label {
            font-size: 11px !important;
        }
    }
    

</style>
</head>
<body>