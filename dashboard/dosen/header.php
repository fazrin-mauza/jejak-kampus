<?php
require_once __DIR__ . '/../../auth/check.php';
$seg = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
$page = $seg[array_search('dosen', $seg) + 1] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Jejak Kampus - Dashboard Dosen</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
    /* ══════════ RESPONSIVE SIDEBAR ══════════ */

/* Sidebar Overlay untuk Mobile */
.sidebar-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 99;
  display: none;
  backdrop-filter: blur(2px);
}

.sidebar-overlay.open {
  display: block;
}

/* Tombol Close Sidebar (Mobile) */
.sidebar-close-btn {
  display: none;
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  color: var(--text3);
  padding: 4px 8px;
  border-radius: var(--r-sm);
  transition: all 0.2s;
}

.sidebar-close-btn:hover {
  background: var(--surface2);
  color: var(--text);
}

/* Tombol Menu Mobile */
.btn-menu-mobile {
  display: none !important;
}

.hamburger-icon {
  font-size: 20px;
  font-weight: 700;
}

/* Sidebar Scrollable */
.sidebar {
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: var(--ora3) var(--surface2);
}

.sidebar::-webkit-scrollbar {
  width: 5px;
}

.sidebar::-webkit-scrollbar-track {
  background: var(--surface2);
}

.sidebar::-webkit-scrollbar-thumb {
  background: var(--ora3);
  border-radius: 10px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
  background: var(--ora4);
}

/* Sidebar Navigation Scroll */
.sidebar-nav {
  max-height: calc(100vh - 280px);
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: var(--ora3) var(--surface2);
}

.sidebar-nav::-webkit-scrollbar {
  width: 4px;
}

.sidebar-nav::-webkit-scrollbar-track {
  background: var(--surface2);
}

.sidebar-nav::-webkit-scrollbar-thumb {
  background: var(--ora3);
  border-radius: 10px;
}

/* Logout Link - TANPA GARIS BAWAH */
.btn-logout-link {
  text-decoration: none;
  display: block;
  width: 100%;
}

/* NAVIGASI TANPA GARIS BAWAH */
.nav-item {
  text-decoration: none;
}

/* ══════════ MOBILE STYLES ══════════ */
@media (max-width: 768px) {
  /* Sidebar di mobile */
  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 85%;
    max-width: 280px;
    transform: translateX(-100%);
    transition: transform 0.3s ease-in-out;
    z-index: 100;
    box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
  }
  
  .sidebar.open {
    transform: translateX(0);
  }
  
  /* Tampilkan tombol close di mobile */
  .sidebar-close-btn {
    display: block;
  }
  
  /* Main content full width */
  .main {
    margin-left: 0 !important;
    width: 100%;
  }
  
  /* Tampilkan tombol menu mobile */
  .btn-menu-mobile {
    display: flex !important;
  }
  
  /* Topbar lebih compact di mobile */
  .topbar {
    padding: 10px 16px !important;
  }
  
  /* Sembunyikan breadcrumb di mobile */
  .topbar-bread {
    display: none;
  }
  
  /* Perkecil font title di mobile */
  .topbar-title {
    font-size: 14px !important;
  }
  
  /* Content padding adjustment */
  .content {
    padding: 16px !important;
  }
  
  /* Sidebar header adjustment */
  .sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  
  /* Sidebar nav max height adjustment */
  .sidebar-nav {
    max-height: calc(100vh - 320px);
  }
  
  /* Sembunyikan icon di topbar mobile (optional) */
  #topbar-icon {
    display: none;
  }
}

/* ══════════ TABLET & DESKTOP ══════════ */
@media (min-width: 769px) {
  .main {
    margin-left: var(--sidebar) !important;
  }
  
  .btn-menu-mobile {
    display: none !important;
  }
  
  .sidebar-close-btn {
    display: none;
  }
  
  .sidebar {
    transform: translateX(0) !important;
  }
}

/* ══════════ SIDEBAR BOTTOM ══════════ */
.sidebar-bottom {
  position: sticky;
  bottom: 0;
  background: #fff;
  padding: 14px 18px;
  border-top: 1px solid var(--border);
  flex-shrink: 0;
  z-index: 5;
}

/* Sidebar flex layout untuk scroll yang baik */
.sidebar {
  display: flex;
  flex-direction: column;
  height: 100vh;
}

.sidebar-header {
  flex-shrink: 0;
}

.sidebar-user {
  flex-shrink: 0;
}

.sidebar-nav {
  flex: 1;
  min-height: 0;
}

/* Active state visual */
.nav-item.active {
  background: linear-gradient(90deg, #FFF3D4, #FFE4C0);
  color: var(--ora4);
  font-weight: 700;
  position: relative;
}

.nav-item.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 20px;
  background: var(--ora3);
  border-radius: 0 3px 3px 0;
}

/* Footer responsif */
footer {
  padding: 12px 16px !important;
  font-size: 11px !important;
}

@media (min-width: 769px) {
  footer {
    padding: 12px 26px !important;
    font-size: 12px !important;
  }
}


/* ══════════ PERBAIKAN TAMBAHAN ══════════ */

/* Hilangkan garis bawah semua link navigasi */
a, a:hover, a:focus, a:active {
  text-decoration: none !important;
}

/* Perbaikan topbar agar hanya 1 baris */
.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: nowrap;
  background: #fff;
  border-bottom: 1px solid var(--border);
  padding: 14px 26px;
  position: sticky;
  top: 0;
  z-index: 50;
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  min-width: 0; /* Biar bisa shrink */
}

.topbar-title {
  font-size: 16px;
  font-weight: 800;
  color: var(--text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.topbar-bread {
  font-size: 12px;
  color: var(--text3);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

/* Perbaikan untuk mobile - topbar lebih rapi */
@media (max-width: 768px) {
  .topbar {
    padding: 10px 12px !important;
  }
  
  .topbar-left {
    gap: 8px;
  }
  
  .topbar-title {
    font-size: 13px !important;
    max-width: 150px;
  }
  
  .topbar-bread {
    display: none; /* Sembunyikan breadcrumb di mobile */
  }
  
  #topbar-icon {
    display: none; /* Sembunyikan icon di mobile */
  }
  
  .topbar-right .icon-btn:first-child {
    display: none; /* Sembunyikan notifikasi di mobile jika terlalu banyak */
  }
}

/* Tablet */
@media (min-width: 769px) and (max-width: 1024px) {
  .topbar-title {
    font-size: 15px;
  }
  
  .topbar-bread {
    font-size: 11px;
  }
}
</style>
</head>
<body>