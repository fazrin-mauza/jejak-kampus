<?php
require '../config.php';
session_start();

$error = '';
$success = '';

// Proses login manual
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    // Cek user berdasarkan email
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);
    
    if ($user) {
        // Verifikasi password (asumsi password disimpan dalam bentuk hash)
        if (password_verify($password, $user['password'])) {
            // Login berhasil
            $session_token = bin2hex(random_bytes(32));
            
            // Hapus session lama jika ada
            mysqli_query($conn, "DELETE FROM session WHERE email = '$email'");
            
            // Buat session baru
            mysqli_query($conn, "
                INSERT INTO session (email, token) 
                VALUES ('$email', '$session_token')
            ");
            
            // Set cookie 30 hari
            setcookie("login_token", $session_token, time() + (60*60*24*30), "/");
            
            // Redirect berdasarkan role
            if ($user['role'] == 'admin') {
                header("Location: ../dashboard/admin/");
            } elseif ($user['role'] == 'dosen') {
                header("Location: ../dashboard/dosen/");
            } elseif ($user['role'] == 'mahasiswa') {
                header("Location: ../dashboard/mahasiswa/");
            } else {
                header("Location: ../");
            }
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Email tidak terdaftar!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Manual - Sistem Akademik</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-16">
        <div class="max-w-md mx-auto">
            <!-- Logo/Brand -->
            <div class="text-center mb-8">
                <div class="inline-block bg-white rounded-full p-4 shadow-lg mb-4">
                    <svg class="w-12 h-12 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Sistem Akademik</h1>
                <p class="text-gray-600">Silakan login dengan akun Anda</p>
            </div>

            <!-- Card Login Manual -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <div class="flex border-b border-gray-200 mb-6">
                    <button id="manualTab" class="flex-1 pb-3 text-center font-semibold text-indigo-600 border-b-2 border-indigo-600">
                        Login Manual
                    </button>
                    <a href="google-login.php" class="flex-1 pb-3 text-center font-semibold text-gray-500 hover:text-gray-700">
                        Login Google
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                        <p><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
                        <p><?php echo $success; ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </div>
                            <input type="email" name="email" id="email" required
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="nama@domain.com">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input type="password" name="password" id="password" required
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Masukkan password">
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="checkbox" id="remember" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="remember" class="ml-2 block text-sm text-gray-700">
                                Ingat saya
                            </label>
                        </div>
                        <a href="#" class="text-sm text-indigo-600 hover:text-indigo-500">
                            Lupa password?
                        </a>
                    </div>

                    <button type="submit"
                        class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition duration-200 font-semibold">
                        Login
                    </button>
                </form>

                <div class="mt-6 text-center text-sm text-gray-600">
                    Belum punya akun?
                    <a href="#" class="text-indigo-600 hover:text-indigo-500 font-medium">
                        Hubungi Administrator
                    </a>
                </div>
            </div>

            <!-- Info Card -->
            <div class="mt-8 bg-white bg-opacity-50 rounded-xl p-4 text-center text-sm text-gray-600">
                <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Gunakan akun yang sudah terdaftar di sistem akademik
            </div>
        </div>
    </div>

    <script>
        // Toggle remember me functionality
        const rememberCheckbox = document.getElementById('remember');
        const emailInput = document.getElementById('email');
        
        // Load remembered email if exists
        if (localStorage.getItem('rememberedEmail')) {
            emailInput.value = localStorage.getItem('rememberedEmail');
            rememberCheckbox.checked = true;
        }
        
        // Save email when form is submitted
        const form = document.querySelector('form');
        form.addEventListener('submit', function() {
            if (rememberCheckbox.checked) {
                localStorage.setItem('rememberedEmail', emailInput.value);
            } else {
                localStorage.removeItem('rememberedEmail');
            }
        });
    </script>
</body>
</html>