<?php
session_start();
include '../config.php';

// Cek apakah user sudah login dan role-nya asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$nama_asisten = htmlspecialchars($_SESSION['nama']);
$praktikum_id = $_GET['praktikum_id'] ?? null;
$praktikum_nama = '';
$modules = [];
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']); // Hapus pesan setelah ditampilkan

// Validasi ID Praktikum
if (!$praktikum_id || !is_numeric($praktikum_id)) {
    $_SESSION['message'] = "ID praktikum tidak valid untuk melihat modul. Silakan pilih praktikum dari daftar.";
    $_SESSION['message_type'] = "error";
    header("Location: praktikum_management.php"); // Kembali ke manajemen praktikum jika ID tidak valid
    exit();
}

// Ambil nama praktikum
$stmt_praktikum = $conn->prepare("SELECT nama_praktikum FROM praktikums WHERE id = ?");
$stmt_praktikum->bind_param("i", $praktikum_id);
$stmt_praktikum->execute();
$result_praktikum = $stmt_praktikum->get_result();
if ($row_praktikum = $result_praktikum->fetch_assoc()) {
    $praktikum_nama = htmlspecialchars($row_praktikum['nama_praktikum']);
} else {
    $_SESSION['message'] = "Mata praktikum tidak ditemukan.";
    $_SESSION['message_type'] = "error";
    header("Location: praktikum_management.php");
    exit();
}
$stmt_praktikum->close();

// Query untuk mengambil semua modul untuk praktikum ini
$sql_modules = "SELECT id, nama_modul, deskripsi_modul, file_materi FROM modules WHERE praktikum_id = ? ORDER BY id ASC";
$stmt_modules = $conn->prepare($sql_modules);
$stmt_modules->bind_param("i", $praktikum_id);
$stmt_modules->execute();
$result_modules = $stmt_modules->get_result();

if ($result_modules->num_rows > 0) {
    while ($row_module = $result_modules->fetch_assoc()) {
        $modules[] = $row_module;
    }
}
$stmt_modules->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Modul: <?php echo $praktikum_nama; ?> - SIMPRAK</title>
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
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Kelola Modul untuk Praktikum: <?php echo $praktikum_nama; ?></h1>

        <?php if (!empty($message)): ?>
            <div class="<?php echo $message_type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <div class="mb-6 flex justify-between items-center">
            <a href="praktikum_management.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded text-sm">&lt; Kembali ke Daftar Praktikum</a>
            <a href="module_add.php?praktikum_id=<?php echo htmlspecialchars($praktikum_id); ?>" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded text-sm">Tambah Modul Baru</a>
        </div>

        <?php if (!empty($modules)): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Modul</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Materi</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($modules as $module): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($module['nama_modul']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars(substr($module['deskripsi_modul'], 0, 100)) . (strlen($module['deskripsi_modul']) > 100 ? '...' : ''); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if (!empty($module['file_materi'])): ?>
                                        <a href="../uploads/materi/<?php echo htmlspecialchars($module['file_materi']); ?>" download class="text-blue-500 hover:underline">Unduh</a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <a href="module_edit.php?id=<?php echo $module['id']; ?>&praktikum_id=<?php echo htmlspecialchars($praktikum_id); ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                    <a href="module_delete.php?id=<?php echo $module['id']; ?>&praktikum_id=<?php echo htmlspecialchars($praktikum_id); ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Apakah Anda yakin ingin menghapus modul ini? Semua laporan dan nilai terkait akan ikut terhapus.');">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-600">Belum ada modul untuk praktikum ini. Silakan tambahkan satu.</p>
        <?php endif; ?>

        <div class="text-center mt-8">
            <a href="dashboard.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg inline-block transition-colors duration-200 shadow">Kembali ke Dashboard Asisten</a>
        </div>
    </div>

</body>
</html>