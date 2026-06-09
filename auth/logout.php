<?php
require '../config.php';

// cek apakah cookie ada
if(isset($_COOKIE['login_token'])) {

    $token = mysqli_real_escape_string($conn, $_COOKIE['login_token']);

    // hapus dari database
    mysqli_query($conn, "DELETE FROM session WHERE token='$token'");

    // hapus cookie
    setcookie("login_token", "", time() - 3600, "/");
}

// redirect ke halaman utama
header("Location: ../");
exit;