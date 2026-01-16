<?php
session_start();
// Jika pengguna sudah login, langsung arahkan ke halaman utama.
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bengkel Ida Jaya Oil</title>
    <!-- Tailwind CSS untuk styling instan dan modern -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome untuk Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts: Poppins agar terlihat premium -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            /* Background gambar bengkel atau gradient abstrak */
            background: linear-gradient(rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.9)), url('https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <!-- Container Utama -->
    <div class="glass-effect w-full max-w-md rounded-2xl shadow-2xl overflow-hidden transform transition-all hover:scale-[1.01] duration-300">
        
        <!-- Header Bagian Atas -->
        <div class="bg-gradient-to-r from-blue-700 to-cyan-600 p-8 text-center relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-full opacity-20">
                <i class="fas fa-cogs absolute -top-4 -left-4 text-6xl text-white"></i>
                <i class="fas fa-oil-can absolute -bottom-4 -right-4 text-6xl text-white"></i>
            </div>
            
            <div class="relative z-10">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full mb-4 shadow-lg text-blue-600">
                    <i class="fas fa-user-circle text-4xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-white tracking-wide">Bengkel Ida Jaya Oil</h2>
                <p class="text-blue-100 text-sm mt-1">Silakan login untuk melanjutkan</p>
            </div>
        </div>

        <!-- Body Form -->
        <div class="p-8">
            
            <!-- Notifikasi / Alert PHP -->
            <div class="space-y-3 mb-6">
                <?php if (isset($_GET['error'])): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 rounded text-sm flex items-center shadow-sm">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span><?= htmlspecialchars($_GET['error']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['status']) && $_GET['status'] === 'logout_success'): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-3 rounded text-sm flex items-center shadow-sm">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span>Anda telah berhasil logout.</span>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['status']) && $_GET['status'] === 'registrasi_sukses'): ?>
                    <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-3 rounded text-sm flex items-center shadow-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        <span>Registrasi berhasil! Silakan login.</span>
                    </div>
                <?php endif; ?>
            </div>

            <form method="POST" action="login_process.php" class="space-y-5">
                
                <!-- Input Username -->
                <div>
                    <label for="username" class="block text-gray-700 text-sm font-semibold mb-2">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" id="username" name="username" 
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" 
                            placeholder="Masukkan username Anda" required>
                    </div>
                </div>

                <!-- Input Password -->
                <div>
                    <label for="password" class="block text-gray-700 text-sm font-semibold mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" 
                            class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200" 
                            placeholder="Masukkan password Anda" required>
                        <!-- Tombol Lihat Password -->
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" onclick="togglePassword()">
                            <i class="fas fa-eye text-gray-400 hover:text-blue-500 transition" id="toggleIcon"></i>
                        </div>
                    </div>
                </div>

                <!-- Tombol Login -->
                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-bold py-3 rounded-lg shadow-md hover:shadow-xl hover:from-blue-700 hover:to-cyan-700 transform transition hover:-translate-y-0.5 duration-200">
                    MASUK SEBAGAI PELANGGAN
                </button>
            </form>

            <!-- Footer -->
            <div class="mt-8 text-center">
                <p class="text-gray-600 text-sm">
                    Belum punya akun? 
                    <a href="registrasi.php" class="text-blue-600 font-bold hover:text-blue-800 transition duration-200 hover:underline">
                        Daftar sekarang
                    </a>
                </p>

                <!-- TOMBOL SHORTCUT KE LOGIN ADMIN -->
                <div class="mt-6 border-t border-gray-200 pt-4">
                    <a href="admin/login.php" class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-gray-600 transition duration-200">
                        <i class="fas fa-user-shield"></i> Akses Admin
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Kecil untuk Show/Hide Password -->
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>

</body>
</html>