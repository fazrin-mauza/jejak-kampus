<?php
require_once 'config.php';
require_once 'auth/google-config.php';
$login_url = $client->createAuthUrl();


?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Jejak Kampus</title>

  <!-- EXISTING GLOBAL CSS (simulated include) -->
  <link rel="stylesheet" href="assets/css/style.css">

  <!-- Fonts already defined in your CSS root -->
  <style>
    /* ===== EXTEND FROM YOUR DESIGN SYSTEM ===== */

    body{
      background: linear-gradient(135deg, var(--bg), #FFE0A8);
      overflow:hidden;
    }

    /* SPLASH */
    .splash{
      position:fixed;
      width:100%;
      height:100%;
      background:var(--surface);
      display:flex;
      justify-content:center;
      align-items:center;
      flex-direction:column;
      z-index:999;
      animation: fadeOut 1s ease 3s forwards;
    }

    .logo{
      font-family: 'Space Grotesk', sans-serif;
      font-size:38px;
      font-weight:700;
      color:var(--ora3);
      animation: pop 1s ease;
    }

    .tagline{
      margin-top:10px;
      font-size:13px;
      color:var(--text3);
      animation: fadeIn 2s ease;
    }

    .loading{
      margin-top:20px;
      width:45px;
      height:45px;
      border:4px solid var(--border);
      border-top:4px solid var(--ora3);
      border-radius:50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin{to{transform:rotate(360deg)}}
    @keyframes pop{0%{transform:scale(0)}100%{transform:scale(1)}}
    @keyframes fadeOut{to{opacity:0;visibility:hidden}}
    @keyframes fadeIn{from{opacity:0}to{opacity:1}}

    /* HERO */
    .hero{
      display:flex;
      flex-direction:column;
      justify-content:center;
      align-items:center;
      height:100vh;
      text-align:center;
      animation: fadeIn 1.5s ease;
      padding:20px;
    }

    .hero h1{
      font-family: 'Space Grotesk', sans-serif;
      font-size:46px;
      color:var(--ora4);
      margin-bottom:10px;
      animation: slideUp 1s ease;
    }

    .hero p{
      max-width:520px;
      font-size:14px;
      color:var(--text2);
      margin-bottom:30px;
    }

    /* BUTTON OVERRIDE */
    .btn-google{
      background:#fff;
      border:1px solid var(--border-s);
      padding:12px 20px;
      border-radius:var(--r);
      display:flex;
      align-items:center;
      gap:10px;
      font-weight:600;
      cursor:pointer;
      transition:.2s;
    }

    .btn-google:hover{
      transform:translateY(-2px);
      box-shadow: var(--shadow);
      background:var(--surface2);
    }

    .btn-google img{width:20px}

    /* FLOATING SHAPES */
    .shape{
      position:absolute;
      border-radius:50%;
      opacity:0.25;
      animation: float 6s ease-in-out infinite;
    }

    .s1{width:120px;height:120px;background:var(--ora2);top:10%;left:10%}
    .s2{width:80px;height:80px;background:var(--ora3);bottom:15%;right:10%}
    .s3{width:60px;height:60px;background:var(--ora1);top:70%;left:20%}

    @keyframes float{
      0%,100%{transform:translateY(0)}
      50%{transform:translateY(-20px)}
    }

    @keyframes slideUp{
      from{opacity:0;transform:translateY(40px)}
      to{opacity:1;transform:translateY(0)}
    }

  </style>
</head>
<body>

  <!-- SPLASH -->
  <div class="splash">
    <div class="logo">Jejak Kampus</div>
    <div class="tagline">Smart Attendance System</div>
    <div class="loading"></div>
  </div>

  <!-- BACKGROUND SHAPES -->
  <div class="shape s1"></div>
  <div class="shape s2"></div>
  <div class="shape s3"></div>

  <!-- HERO -->
  <div class="hero">
    <h1><?= $config['settings']['app_name']?></h1>
    <p>
      <?= $config['settings']['app_description']?>
    </p>
 
<a href="<?= $login_url ?>" class="btn-google" style="text-decoration: none; color: inherit;">
  <img src="https://www.svgrepo.com/show/475656/google-color.svg">
  Masuk dengan Google
</a>
  </div>

</body>
</html>