<?php
session_start();
include '../config.php'; // Kembali satu level untuk mengakses config.php

// Cek apakah user sudah login dan role-nya asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$nama_asisten = htmlspecialchars($_SESSION['nama']);
$module_id = $_GET['id'] ?? null;
$praktikum_id_redirect = $_GET['praktikum_id'] ?? null; // Untuk redirect kembali ke manajemen modul
$module_data = null;
$praktikum_nama = '';
$message = '';
$message_type = '';

// Validasi ID Modul dan Praktikum
if (!$module_id || !is_numeric($module_id) || !$praktikum_id_redirect || !is_numeric($praktikum_id_redirect)) {
    $_SESSION['message'] = "ID modul atau praktikum tidak valid.";
    $_SESSION['message_type'] = "error";
    header("Location: praktikum_management.php");
    exit();
}

// Ambil nama praktikum (untuk ditampilkan di judul)
$stmt_praktikum = $conn->prepare("SELECT nama_praktikum FROM praktikums WHERE id = ?");
$stmt_praktikum->bind_param("i", $praktikum_id_redirect);
$stmt_praktikum->execute();
$result_praktikum = $stmt_praktikum->get_result();
if ($row_praktikum = $result_praktikum->fetch_assoc()) {
    $praktikum_nama = htmlspecialchars($row_praktikum['nama_praktikum']);
} else {
    // Praktikum tidak ditemukan, redirect
    $_SESSION['message'] = "Mata praktikum terkait tidak ditemukan.";
    $_SESSION['message_type'] = "error";
    header("Location: praktikum_management.php");
    exit();
}
$stmt_praktikum->close();


// Ambil data modul yang akan diedit
$stmt_select = $conn->prepare("SELECT id, nama_modul, deskripsi_modul, file_materi FROM modules WHERE id = ? AND praktikum_id = ?");
$stmt_select->bind_param("ii", $module_id, $praktikum_id_redirect);
$stmt_select->execute();
$result_select = $stmt_select->get_result();

if ($result_select->num_rows > 0) {
    $module_data = $result_select->fetch_assoc();
} else {
    $_SESSION['message'] = "Modul tidak ditemukan atau tidak terkait dengan praktikum ini.";
    $_SESSION['message_type'] = "error";
    header("Location: module_management.php?praktikum_id=" . $praktikum_id_redirect);
    exit();
}
$stmt_select->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_modul = trim($_POST['nama_modul']);
    $deskripsi_modul = trim($_POST['deskripsi_modul']);
    $current_file_materi = $module_data['file_materi']; // Nama file materi yang lama
    $new_file_materi_name = $current_file_materi; // Default ke nama file lama

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

            $allowed_ext = ['pdf', 'docx'];
            $max_file_size = 10 * 1024 * 1024; // 10 MB

            if (!in_array($file_ext, $allowed_ext)) {
                $message = "Hanya file PDF atau DOCX yang diizinkan untuk materi.";
                $message_type = "error";
            } elseif ($file_size > $max_file_size) {
                $message = "Ukuran file materi terlalu besar. Maksimal 10 MB.";
                $message_type = "error";
            } else {
                // Buat nama file unik
                $new_file_materi_name = uniqid('materi_', true) . '.' . $file_ext;
                $upload_path = '../uploads/materi/' . $new_file_materi_name;

                if (move_uploaded_file($file_tmp_name, $upload_path)) {
                    // Hapus file lama jika ada dan berhasil upload file baru
                    if ($current_file_materi && file_exists('../uploads/materi/' . $current_file_materi)) {
                        unlink('../uploads/materi/' . $current_file_materi);
                    }
                } else {
                    $message = "Gagal mengunggah file materi baru.";
                    $message_type = "error";
                    $new_file_materi_name = $current_file_materi; // Kembali ke file lama jika upload gagal
                }
            }
        }
        
        // Hanya lanjutkan jika tidak ada error dari upload file atau jika tidak ada file yang diunggah
        if (empty($message_type)) {
            $sql_update = "UPDATE modules SET nama_modul = ?, deskripsi_modul = ?, file_materi = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("sssi", $nama_modul, $deskripsi_modul, $new_file_materi_name, $module_id);

            if ($stmt_update->execute()) {
                $_SESSION['message'] = "Modul '" . htmlspecialchars($nama_modul) . "' berhasil diperbarui!";
                $_SESSION['message_type'] = "success";
                header("Location: module_management.php?praktikum_id=" . $praktikum_id_redirect);
                exit();
            } else {
                $message = "Gagal memperbarui modul: " . $conn->error;
                $message_type = "error";
                // Jika gagal simpan ke DB, hapus file baru yang sudah terupload (jika ada)
                if ($new_file_materi_name && $new_file_materi_name !== $current_file_materi && file_exists($upload_path)) {
                    unlink($upload_path);
                }
            }
            $stmt_update->close();
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
    <title>Edit Modul: <?php echo htmlspecialchars($module_data['nama_modul'] ?? ''); ?> - SIMPRAK</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
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
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Edit Modul untuk Praktikum: <?php echo $praktikum_nama; ?></h1>

        <?php if (!empty($message)): ?>
            <div class="<?php echo $message_type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form action="module_edit.php?id=<?php echo htmlspecialchars($module_id); ?>&praktikum_id=<?php echo htmlspecialchars($praktikum_id_redirect); ?>" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label for="nama_modul" class="block text-gray-700 text-sm font-bold mb-2">Nama Modul</label>
                    <input type="text" id="nama_modul" name="nama_modul" value="<?php echo htmlspecialchars($module_data['nama_modul'] ?? ''); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-4">
                    <label for="deskripsi_modul" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi Modul (Opsional)</label>
                    <textarea id="deskripsi_modul" name="deskripsi_modul" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($module_data['deskripsi_modul'] ?? ''); ?></textarea>
                </div>
                <div class="mb-6">
                    <label for="file_materi" class="block text-gray-700 text-sm font-bold mb-2">Ganti File Materi (PDF/DOCX, maks 10MB)</label>
                    <?php if (!empty($module_data['file_materi'])): ?>
                        <p class="text-sm text-gray-600 mb-2">File materi saat ini: <a href="../uploads/materi/<?php echo htmlspecialchars($module_data['file_materi']); ?>" download class="text-blue-500 hover:underline"><?php echo htmlspecialchars($module_data['file_materi']); ?></a></p>
                    <?php else: ?>
                        <p class="text-sm text-gray-600 mb-2">Belum ada file materi.</p>
                    <?php endif; ?>
                    <input type="file" id="file_materi" name="file_materi" accept=".pdf,.docx" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <p class="text-xs text-gray-500 mt-1">Pilih file baru untuk mengganti yang lama, atau biarkan kosong jika tidak ingin mengubah.</p>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Simpan Perubahan</button>
                    <a href="module_management.php?praktikum_id=<?php echo htmlspecialchars($praktikum_id_redirect); ?>" class="inline-block align-baseline font-bold text-sm text-gray-600 hover:text-gray-800">Batal</a>
                </div>
            </form>
        </div>

        <div class="text-center mt-8">
            <a href="dashboard.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg inline-block transition-colors duration-200 shadow">Kembali ke Dashboard Asisten</a>
        </div>
    </div>

</body>
</html>