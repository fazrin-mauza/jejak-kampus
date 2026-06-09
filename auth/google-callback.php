<?php
require '../config.php';
require 'google-config.php';

if(isset($_GET['code'])) {

    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    $google_service = new Google_Service_Oauth2($client);
    $data = $google_service->userinfo->get();

    $email = mysqli_real_escape_string($conn, $data->email);

    // 🔍 CEK USER
    $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    $user = mysqli_fetch_assoc($check);

    // ❌ kalau tidak ada
    if(!$user) {
        echo "<script>
            alert('User tidak terdaftar!');
            window.location.href='../';
        </script>";
        exit;
    }

    // ✅ kalau ada → buat session
    $session_token = bin2hex(random_bytes(32));

    mysqli_query($conn, "
        INSERT INTO session (email, token) 
        VALUES ('$email', '$session_token')
    ");

    // 🍪 COOKIE 30 HARI
    setcookie("login_token", $session_token, time() + (60*60*24*30), "/");

    // 🎯 REDIRECT BERDASARKAN ROLE
    if($user['role'] == 'admin') {
        header("Location: ../dashboard/admin/");
    } elseif($user['role'] == 'dosen') {
        header("Location: ../dashboard/dosen/");
    } elseif($user['role'] == 'mahasiswa') {
        header("Location: ../dashboard/mahasiswa/");
    } else {
        echo "Role tidak dikenali!";
    }

    exit;
}