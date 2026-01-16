<?php
session_start();

// Cek apakah admin sudah login
// Kita cek kedua kemungkinan session key agar kompatibel dengan sistem yang ada
if ((isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Bengkel Ida Jaya Oil</title>
    
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z'/></svg>">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4">

    <!-- Background Pattern (Opsional) -->
    <div class="absolute inset-0 opacity-10 pointer-events-none overflow-hidden">
        <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-blue-600 blur-3xl"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 rounded-full bg-blue-800 blur-3xl"></div>
    </div>

    <!-- Login Card -->
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-700">
        
        <!-- Header -->
        <div class="bg-slate-800 p-8 text-center border-b border-slate-700">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-slate-700 rounded-full mb-4 shadow-inner">
                <i class="fas fa-user-shield text-4xl text-blue-500"></i>
            </div>
            <h2 class="text-2xl font-bold text-white tracking-wide">Admin Portal</h2>
            <p class="text-slate-400 text-sm mt-1">Silakan login untuk mengelola sistem</p>
        </div>

        <!-- Form Body -->
        <div class="p-8 pt-6">
            
            <!-- Alert Error -->
            <?php if (isset($_GET['error'])): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded text-sm flex items-center shadow-sm">
                    <i class="fas fa-exclamation-triangle mr-3 text-lg"></i>
                    <div>
                        <p class="font-bold">Login Gagal</p>
                        <p>Username atau password salah!</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Alert Logout -->
            <?php if (isset($_GET['status']) && $_GET['status'] === 'logout_success'): ?>
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded text-sm flex items-center shadow-sm">
                    <i class="fas fa-check-circle mr-3 text-lg"></i>
                    <span>Anda telah berhasil logout.</span>
                </div>
            <?php endif; ?>

            <form method="POST" action="login_process.php" class="space-y-6">
                
                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-semibold text-slate-700 mb-2">Username Admin</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-slate-400 group-focus-within:text-blue-600 transition"></i>
                        </div>
                        <input type="text" id="username" name="username" 
                            class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:bg-white transition text-slate-800 placeholder-slate-400" 
                            placeholder="Masukkan username" required>
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-slate-400 group-focus-within:text-blue-600 transition"></i>
                        </div>
                        <input type="password" id="password" name="password" 
                            class="w-full pl-10 pr-12 py-3 bg-slate-50 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:bg-white transition text-slate-800 placeholder-slate-400" 
                            placeholder="Masukkan password" required>
                        
                        <!-- Toggle Password Visibility -->
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer text-slate-400 hover:text-blue-600 transition" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </div>
                    </div>
                </div>

                <!-- Button -->
                <button type="submit" class="w-full bg-slate-800 hover:bg-blue-700 text-white font-bold py-3 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5 flex justify-center items-center gap-2">
                    <i class="fas fa-sign-in-alt"></i> MASUK
                </button>

            </form>
        </div>

        <!-- Footer Card -->
        <div class="bg-slate-50 p-4 text-center border-t border-slate-100">
            <a href="../index.php" class="text-sm text-slate-500 hover:text-blue-600 font-medium transition flex items-center justify-center gap-2">
                <i class="fas fa-arrow-left"></i> Kembali ke Website Utama
            </a>
        </div>
    </div>

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