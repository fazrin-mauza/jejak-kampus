<?php

date_default_timezone_set('Asia/Jakarta');
error_reporting(1);
$maintenance = 0; //** 1 = ya ..  0 = tidak
if($maintenance == 1) {
    die("Site under Maintenance.");
}

// database
$config['db'] = array(
	'host' => 'localhost',
	'name' => 'xfag9686_jejak_kampus',
	'username' => 'xfag9686_jejak_kampus',
	'password' => '$jejak_kampus123'
);

$conn = mysqli_connect($config['db']['host'], $config['db']['username'], $config['db']['password'], $config['db']['name']);
if(!$conn) {
	die("Koneksi Gagal : ".mysqli_connect_error());
}

$config['web'] = array(
	'url' => 'https://jejak-kampus.web.id/', // url web:
	'domain' => 'jejak-kampus.web.id' // domain web: 
);

// Pengaturan default (fallback jika database kosong)
$config['settings'] = array(
	'app_name' => 'Jejak Kampus', 
	'app_description' => 'Sistem Absensi Kampus Berbasis QR Code & Geolokasi' 
);

// Ambil pengaturan dari database
$query_settings = mysqli_query($conn, "SELECT * FROM settings LIMIT 1");
if($query_settings && mysqli_num_rows($query_settings) > 0) {
	$row_settings = mysqli_fetch_assoc($query_settings);
	
	// Update dengan data dari database jika kolomnya ada
	if(isset($row_settings['app_name']) && !empty($row_settings['app_name'])) {
		$config['settings']['app_name'] = $row_settings['app_name'];
	}
	if(isset($row_settings['app_description']) && !empty($row_settings['app_description'])) {
		$config['settings']['app_description'] = $row_settings['app_description'];
	}
}

// date & time
$date = date("Y-m-d");
$time = date("H:i:s");

?>