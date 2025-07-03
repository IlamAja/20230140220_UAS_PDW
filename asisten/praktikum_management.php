<?php
session_start(); // Pastikan session dimulai
include '../config.php'; // Kembali satu level untuk mengakses config.php

// Cek apakah user sudah login dan role-nya asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$nama_asisten = htmlspecialchars($_SESSION['nama']);
$praktikums = [];

// Query untuk mengambil semua mata praktikum
$sql = "SELECT id, nama_praktikum, deskripsi FROM praktikums ORDER BY nama_praktikum ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $praktikums[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mata Praktikum - SIMPRAK</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="../style.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

    <nav class="bg-purple-700 p-4 text-white flex justify-between items-center">
        <a href="dashboard.php" class="text-xl font-bold">SIMPRAK Asisten</a>
        <div>
            <span>Halo, <?php echo $nama_asisten; ?> (Asisten)</span>
            <a href="../logout.php" class="ml-4 bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded text-sm">Logout</a>
        </div>
    </nav>

    <div class="container mx-auto p-4 mt-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Kelola Mata Praktikum</h1>

        <?php
        // Menampilkan pesan feedback dari session
        if (isset($_SESSION['message'])):
            $message_class = '';
            if (isset($_SESSION['message_type'])) {
                if ($_SESSION['message_type'] === 'success') {
                    $message_class = 'bg-green-100 border-green-400 text-green-700';
                } elseif ($_SESSION['message_type'] === 'error') {
                    $message_class = 'bg-red-100 border-red-400 text-red-700';
                } elseif ($_SESSION['message_type'] === 'warning') {
                    $message_class = 'bg-yellow-100 border-yellow-400 text-yellow-700';
                } else {
                    $message_class = 'bg-blue-100 border-blue-400 text-blue-700'; // Default
                }
            } else {
                $message_class = 'bg-blue-100 border-blue-400 text-blue-700'; // Default if type not set
            }
        ?>
            <div class="<?php echo $message_class; ?> px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['message']; ?></span>
            </div>
        <?php
            unset($_SESSION['message']); // Hapus pesan setelah ditampilkan
            unset($_SESSION['message_type']);
        endif;
        ?>
        
        <div class="mb-6">
            <a href="praktikum_add.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded text-sm">Tambah Praktikum Baru</a>
        </div>

        <?php if (!empty($praktikums)): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Praktikum</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($praktikums as $praktikum): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($praktikum['nama_praktikum']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars(substr($praktikum['deskripsi'], 0, 100)) . (strlen($praktikum['deskripsi']) > 100 ? '...' : ''); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <a href="module_management.php?praktikum_id=<?php echo $praktikum['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-4">Kelola Modul</a>
                                    <a href="praktikum_edit.php?id=<?php echo $praktikum['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                    <a href="praktikum_delete.php?id=<?php echo $praktikum['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Apakah Anda yakin ingin menghapus praktikum ini? Semua modul, laporan, dan pendaftaran terkait akan ikut terhapus.');">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-600">Belum ada mata praktikum yang ditambahkan.</p>
        <?php endif; ?>

        <div class="text-center mt-8">
            <a href="dashboard.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg inline-block transition-colors duration-200 shadow">Kembali ke Dashboard Asisten</a>
        </div>
    </div>

</body>
</html>