<?php
require_once __DIR__ . '/../../auth/check.php';
$seg = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/')); $page = $seg[array_search('admin',$seg)+1] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Jejak Kampus - Dashboard Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.css" />

<style>
    .nav-item {
  text-decoration: none;
  color: var(--text, #1A0F00);
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-radius: 8px;
  transition: all 0.2s ease;
}

/* hover */
.nav-item:hover {
  background: rgba(0,0,0,0.05);
}

/* active */
.nav-item.active {
  background: linear-gradient(135deg, var(--ora1), var(--ora3));
  color: #fff;
  font-weight: 600;
}

.sidebar {
  width: var(--sidebar);
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
  display: flex;
  flex-direction: column;
  background: #fff;
  overflow: hidden; /* penting */
}

.sidebar-nav {
  flex: 1;
  overflow-y: auto;
}

.btn-logout {
  text-decoration: none;
}
a {
  text-decoration: none;
}
.btn-logout {
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
</head>
<body>