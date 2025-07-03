<?php
session_start();
include '../config.php'; // Kembali satu level untuk mengakses config.php

// Cek apakah user sudah login dan role-nya asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$nama_asisten = htmlspecialchars($_SESSION['nama']);
$praktikum_id = $_GET['praktikum_id'] ?? null;
$praktikum_nama = '';
$message = '';
$message_type = '';

// Validasi ID Praktikum
if (!$praktikum_id || !is_numeric($praktikum_id)) {
    $_SESSION['message'] = "ID praktikum tidak valid untuk menambah modul.";
    $_SESSION['message_type'] = "error";
    header("Location: praktikum_management.php");
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


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_modul = trim($_POST['nama_modul']);
    $deskripsi_modul = trim($_POST['deskripsi_modul']);
    $file_materi_name = null; // Default null jika tidak ada upload

    // Validasi input
    if (empty($nama_modul)) {
        $message = "Nama modul tidak boleh kosong.";
        $message_type = "error";
    } else {
        // Handle file upload jika ada
        if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['file_materi']['tmp_name'];
            $file_name = $_FILES['file_materi']['name'];
            $file_size = $_FILES['file_materi']['size'];
            $file_type = $_FILES['file_materi']['type'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_ext = ['pdf', 'docx']; // Sesuai spesifikasi
            $max_file_size = 10 * 1024 * 1024; // Contoh: 10 MB

            if (!in_array($file_ext, $allowed_ext)) {
                $message = "Hanya file PDF atau DOCX yang diizinkan untuk materi.";
                $message_type = "error";
            } elseif ($file_size > $max_file_size) {
                $message = "Ukuran file materi terlalu besar. Maksimal 10 MB.";
                $message_type = "error";
            } else {
                // Buat nama file unik
                $file_materi_name = uniqid('materi_', true) . '.' . $file_ext;
                $upload_path = '../uploads/materi/' . $file_materi_name; // Path relatif dari asisten/

                if (!move_uploaded_file($file_tmp_name, $upload_path)) {
                    $message = "Gagal mengunggah file materi.";
                    $message_type = "error";
                    $file_materi_name = null; // Reset jika gagal upload
                }
            }
        }
        
        // Hanya lanjutkan jika tidak ada error dari upload file atau jika tidak ada file yang diunggah
        if (empty($message_type)) {
            $sql = "INSERT INTO modules (praktikum_id, nama_modul, deskripsi_modul, file_materi) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isss", $praktikum_id, $nama_modul, $deskripsi_modul, $file_materi_name);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Modul '" . htmlspecialchars($nama_modul) . "' berhasil ditambahkan!";
                $_SESSION['message_type'] = "success";
                header("Location: module_management.php?praktikum_id=" . $praktikum_id); // Redirect ke halaman manajemen modul
                exit();
            } else {
                $message = "Gagal menambahkan modul: " . $conn->error;
                $message_type = "error";
                // Jika gagal simpan ke DB, hapus file yang sudah terupload
                if ($file_materi_name && file_exists($upload_path)) {
                    unlink($upload_path);
                }
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Modul: <?php echo $praktikum_nama; ?> - SIMPRAK</title>
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
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Tambah Modul Baru untuk Praktikum: <?php echo $praktikum_nama; ?></h1>

        <?php if (!empty($message)): ?>
            <div class="<?php echo $message_type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form action="module_add.php?praktikum_id=<?php echo htmlspecialchars($praktikum_id); ?>" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label for="nama_modul" class="block text-gray-700 text-sm font-bold mb-2">Nama Modul</label>
                    <input type="text" id="nama_modul" name="nama_modul" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-4">
                    <label for="deskripsi_modul" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi Modul (Opsional)</label>
                    <textarea id="deskripsi_modul" name="deskripsi_modul" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                </div>
                <div class="mb-6">
                    <label for="file_materi" class="block text-gray-700 text-sm font-bold mb-2">Unggah File Materi (PDF/DOCX, maks 10MB)</label>
                    <input type="file" id="file_materi" name="file_materi" accept=".pdf,.docx" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <p class="text-xs text-gray-500 mt-1">Biarkan kosong jika tidak ada materi atau ingin menambahkannya nanti.</p>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Tambah Modul</button>
                    <a href="module_management.php?praktikum_id=<?php echo htmlspecialchars($praktikum_id); ?>" class="inline-block align-baseline font-bold text-sm text-gray-600 hover:text-gray-800">Batal</a>
                </div>
            </form>
        </div>

        <div class="text-center mt-8">
            <a href="dashboard.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg inline-block transition-colors duration-200 shadow">Kembali ke Dashboard Asisten</a>
        </div>
    </div>

</body>
</html>