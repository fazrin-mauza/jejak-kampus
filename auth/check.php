<?php
require_once __DIR__ . '/../config.php';

if(!isset($_COOKIE['login_token'])) {
    header("Location: " . $config['web']['url']);
    exit;
    }

$token = $_COOKIE['login_token'];

$query = mysqli_query($conn, "
SELECT * FROM session 
WHERE token='$token'
");

$data = mysqli_fetch_assoc($query);

if(!$data) {
    header("Location: " . $config['web']['url']);
    exit;
    }
    
// ambil user
$email = $data['email'];
$userQuery = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
$user = mysqli_fetch_assoc($userQuery);

// proteksi role per folder
$current_folder = basename(dirname($_SERVER['SCRIPT_NAME']));

if($current_folder != $user['role']) {
    if($current_folder == 'api') {
    } else {
    // redirect sesuai role
    header("Location: ../dashboard/".$user['role']."/");
    exit;
    }
}