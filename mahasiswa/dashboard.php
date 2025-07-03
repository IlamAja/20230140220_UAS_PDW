<?php
session_start();

// Cek apakah user sudah login dan role-nya mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php"); // Redirect ke halaman login jika belum login atau bukan mahasiswa
    exit();
}

$nama_mahasiswa = htmlspecialchars($_SESSION['nama']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa - SIMPRAK</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="../style.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

    <nav class="bg-blue-600 p-4 text-white flex justify-between items-center">
        <a href="dashboard.php" class="text-xl font-bold">SIMPRAK Mahasiswa</a>
        <div>
            <span>Halo, <?php echo $nama_mahasiswa; ?></span>
            <a href="../logout.php" class="ml-4 bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded text-sm">Logout</a>
        </div>
    </nav>

    <div class="container mx-auto p-4 mt-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Selamat Datang di Dashboard Mahasiswa, <?php echo $nama_mahasiswa; ?>!</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Praktikum Saya</h2>
                <p class="text-gray-700 mb-4">Lihat daftar praktikum yang Anda ikuti.</p>
                <a href="praktikum_saya.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded text-sm">Lihat Praktikum</a>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Cari Praktikum Baru</h2>
                <p class="text-gray-700 mb-4">Temukan dan daftar praktikum lainnya.</p>
                <a href="../katalog_praktikum.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded text-sm">Cari Praktikum</a>
            </div>

            </div>
    </div>

</body>
</html>