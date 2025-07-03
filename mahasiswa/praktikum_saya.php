<?php
session_start();
include '../config.php'; // Kembali satu level untuk mengakses config.php

// Cek apakah user sudah login dan role-nya mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$nama_mahasiswa = htmlspecialchars($_SESSION['nama']);
$praktikum_diikuti = [];

// Query untuk mengambil praktikum yang diikuti oleh mahasiswa ini
$sql = "SELECT p.id, p.nama_praktikum, p.deskripsi
        FROM praktikums p
        JOIN enrollments e ON p.id = e.praktikum_id
        WHERE e.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $praktikum_diikuti[] = $row;
    }
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Praktikum Saya - SIMPRAK</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
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
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Praktikum yang Saya Ikuti</h1>

        <?php if (!empty($praktikum_diikuti)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($praktikum_diikuti as $praktikum): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h2>
                        <p class="text-gray-700 text-sm mb-4"><?php echo htmlspecialchars($praktikum['deskripsi']); ?></p>
                        <div class="flex justify-end">
                            <a href="../detail_praktikum.php?id=<?php echo htmlspecialchars($praktikum['id']); ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded text-sm">Lihat Detail & Tugas</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-600">Anda belum mendaftar praktikum apapun. Silakan <a href="../katalog_praktikum.php" class="text-blue-600 hover:underline">cari praktikum baru</a>.</p>
        <?php endif; ?>

        <div class="text-center mt-8">
            <a href="dashboard.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg inline-block transition-colors duration-200 shadow">
                &larr; Kembali ke Dashboard Mahasiswa
            </a>
            </div>
    </div>

</body>
</html>