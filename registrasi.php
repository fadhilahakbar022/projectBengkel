<?php
// Mulai sesi untuk menampilkan pesan error atau sukses
session_start();

// Jika pengguna sudah login, arahkan ke halaman utama
// Cek kedua kemungkinan variabel session (user atau user_id) untuk keamanan
if (isset($_SESSION['user']) || isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Akun - Bengkel Ida Jaya Oil</title>
    <!-- FAVICON (Tambahkan baris ini) -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z'/></svg>">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            /* Background disamakan dengan halaman login agar konsisten */
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
    <div class="glass-effect w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden transform transition-all hover:scale-[1.005] duration-300 my-8">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-700 to-cyan-600 p-6 text-center relative overflow-hidden">
            <!-- Dekorasi Background Header -->
            <div class="absolute top-0 left-0 w-full h-full opacity-20">
                <i class="fas fa-cogs absolute -top-4 -left-4 text-6xl text-white"></i>
                <i class="fas fa-tools absolute -bottom-4 -right-4 text-6xl text-white"></i>
            </div>
            
            <div class="relative z-10">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full mb-3 shadow-lg text-blue-600">
                    <i class="fas fa-user-plus text-3xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-white tracking-wide">Buat Akun Baru</h2>
                <p class="text-blue-100 text-sm mt-1">Gabung dengan Bengkel Ida Jaya Oil</p>
            </div>
        </div>

        <!-- Body Form -->
        <div class="p-8">
            
            <!-- Alert Session (Error Message) -->
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 rounded text-sm flex items-center shadow-sm mb-6 animate-pulse">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span><?php echo $_SESSION['error_message']; ?></span>
                </div>
                <?php unset($_SESSION['error_message']); // Hapus pesan setelah ditampilkan ?>
            <?php endif; ?>

            <form method="POST" action="proses_registrasi.php" class="space-y-4">
                
                <!-- Nama Lengkap -->
                <div>
                    <label for="nama_lengkap" class="block text-gray-700 text-xs font-bold mb-1 uppercase tracking-wide">Nama Lengkap</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-id-card text-gray-400"></i>
                        </div>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" 
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition text-sm" 
                            placeholder="Nama Lengkap Anda" required>
                    </div>
                </div>

                <!-- Grid untuk Username & No Telp agar rapi -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-gray-700 text-xs font-bold mb-1 uppercase tracking-wide">Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" id="username" name="username" 
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition text-sm" 
                                placeholder="Username" required>
                        </div>
                    </div>

                    <!-- No Telp (BARU DITAMBAHKAN) -->
                    <div>
                        <label for="no_telp" class="block text-gray-700 text-xs font-bold mb-1 uppercase tracking-wide">No. Telepon</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-phone text-gray-400"></i>
                            </div>
                            <input type="tel" id="no_telp" name="no_telp" 
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition text-sm" 
                                placeholder="08xxxxxxxxxx" required
                                maxlength="12"
                                pattern="[0-9]*"
                                inputmode="numeric"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-gray-700 text-xs font-bold mb-1 uppercase tracking-wide">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" 
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition text-sm" 
                            placeholder="Buat password" required>
                            <!-- Tombol Lihat Password -->
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" onclick="togglePassword()">
                                <i class="fas fa-eye text-gray-400 hover:text-blue-500 transition" id="toggleIcon"></i>
                            </div>
                    </div>
                </div>

                <!-- Konfirmasi Password -->
                <div>
                    <label for="konfirmasi_password" class="block text-gray-700 text-xs font-bold mb-1 uppercase tracking-wide">Konfirmasi Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-check-circle text-gray-400"></i>
                        </div>
                        <input type="password" id="konfirmasi_password" name="konfirmasi_password" 
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition text-sm" 
                            placeholder="Ulangi password" required>
                            <!-- Tombol Lihat Password -->
                             <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" onclick="togglePassword()">
                                <i class="fas fa-eye text-gray-400 hover:text-blue-500 transition" id="toggleIcon"></i>
                            </div>
                    </div>
                </div>

                <!-- Tombol Register -->
                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-bold py-3 rounded-lg shadow-md hover:shadow-xl hover:from-blue-700 hover:to-cyan-700 transform transition hover:-translate-y-0.5 duration-200 mt-2">
                    DAFTAR SEKARANG
                </button>
            </form>

            <!-- Footer -->
            <div class="mt-6 text-center">
                <p class="text-gray-600 text-sm">
                    Sudah punya akun? 
                    <a href="login.php" class="text-blue-600 font-bold hover:text-blue-800 transition duration-200 hover:underline">
                        Masuk di sini
                    </a>
                </p>
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