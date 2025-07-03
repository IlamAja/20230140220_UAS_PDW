<?php
session_start();
include '../config.php'; // Kembali satu level untuk mengakses config.php

// Cek apakah user sudah login dan role-nya asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$nama_asisten = htmlspecialchars($_SESSION['nama']);
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_praktikum = trim($_POST['nama_praktikum']);
    $deskripsi = trim($_POST['deskripsi']);

    if (empty($nama_praktikum)) {
        $message = "Nama praktikum tidak boleh kosong.";
        $message_type = "error";
    } else {
        // Query untuk menambahkan praktikum baru
        $sql = "INSERT INTO praktikums (nama_praktikum, deskripsi) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $nama_praktikum, $deskripsi);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Mata praktikum '" . htmlspecialchars($nama_praktikum) . "' berhasil ditambahkan!";
            $_SESSION['message_type'] = "success";
            header("Location: praktikum_management.php"); // Redirect ke halaman manajemen praktikum
            exit();
        } else {
            $message = "Gagal menambahkan mata praktikum: " . $conn->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Mata Praktikum - SIMPRAK</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="../style.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

    <nav class="bg-blue-600 p-4 text-white flex justify-between items-center">
        <a href="dashboard.php" class="text-xl font-bold">SIMPRAK Asisten</a>
        <div>
            <span>Halo, <?php echo $nama_asisten; ?> (Asisten)</span>
            <a href="../logout.php" class="ml-4 bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded text-sm">Logout</a>
        </div>
    </nav>

    <div class="container mx-auto p-4 mt-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Tambah Mata Praktikum Baru</h1>

        <?php if (!empty($message)): ?>
            <div class="<?php echo $message_type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form action="praktikum_add.php" method="POST">
                <div class="mb-4">
                    <label for="nama_praktikum" class="block text-gray-700 text-sm font-bold mb-2">Nama Praktikum</label>
                    <input type="text" id="nama_praktikum" name="nama_praktikum" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-6">
                    <label for="deskripsi" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi (Opsional)</label>
                    <textarea id="deskripsi" name="deskripsi" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Tambah Praktikum</button>
                    <a href="praktikum_management.php" class="inline-block align-baseline font-bold text-sm text-gray-600 hover:text-gray-800">Batal</a>
                </div>
            </form>
        </div>

        <div class="text-center mt-8">
            <a href="dashboard.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg inline-block transition-colors duration-200 shadow">Kembali ke Dashboard Asisten</a>
        </div>
    </div>

</body>
</html>