<?php
include 'config.php';
session_start();

// Pastikan ID praktikum ada di URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: katalog_praktikum.php");
    exit();
}

$praktikum_id = $_GET['id'];
$praktikum = null;
$is_enrolled = false; // Flag untuk mengecek apakah mahasiswa sudah terdaftar
$modules = []; // Untuk menyimpan data modul

// Ambil detail praktikum
$stmt = $conn->prepare("SELECT id, nama_praktikum, deskripsi FROM praktikums WHERE id = ?");
$stmt->bind_param("i", $praktikum_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $praktikum = $result->fetch_assoc();
} else {
    // Praktikum tidak ditemukan, redirect
    header("Location: katalog_praktikum.php");
    exit();
}
$stmt->close();

// Cek apakah user login dan merupakan mahasiswa
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'mahasiswa') {
    $user_id = $_SESSION['user_id'];
    // Cek apakah mahasiswa sudah terdaftar di praktikum ini
    $stmt_check = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND praktikum_id = ?");
    $stmt_check->bind_param("ii", $user_id, $praktikum_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        $is_enrolled = true;
    }
    $stmt_check->close();

    // Logika pendaftaran jika tombol daftar diklik
    if (isset($_POST['daftar_praktikum'])) {
        if (!$is_enrolled) {
            $stmt_insert = $conn->prepare("INSERT INTO enrollments (user_id, praktikum_id) VALUES (?, ?)");
            $stmt_insert->bind_param("ii", $user_id, $praktikum_id);
            if ($stmt_insert->execute()) {
                $is_enrolled = true; // Update status setelah daftar
                $_SESSION['message'] = "Berhasil mendaftar ke praktikum " . htmlspecialchars($praktikum['nama_praktikum']) . "!";
                $_SESSION['message_type'] = "success"; // Tambahkan tipe pesan
            } else {
                $_SESSION['message'] = "Gagal mendaftar: " . $conn->error;
                $_SESSION['message_type'] = "error"; // Tambahkan tipe pesan
            }
            $stmt_insert->close();
        } else {
            $_SESSION['message'] = "Anda sudah terdaftar di praktikum ini.";
            $_SESSION['message_type'] = "warning"; // Tambahkan tipe pesan
        }
        // Redirect untuk mencegah form resubmission
        header("Location: detail_praktikum.php?id=" . $praktikum_id);
        exit();
    }

    // --- BAGIAN UNTUK MENGAMBIL MODUL DAN STATUS LAPORAN/NILAI jika mahasiswa terdaftar ---
    if ($is_enrolled) {
        $sql_modules = "SELECT m.id, m.nama_modul, m.deskripsi_modul, m.file_materi,
                        r.id AS report_id, r.file_laporan, r.tanggal_pengumpulan, r.status AS report_status,
                        g.nilai, g.feedback, g.tanggal_penilaian
                        FROM modules m
                        LEFT JOIN reports r ON m.id = r.module_id AND r.user_id = ?
                        LEFT JOIN grades g ON r.id = g.report_id
                        WHERE m.praktikum_id = ?
                        ORDER BY m.id ASC";

        $stmt_modules = $conn->prepare($sql_modules);
        $stmt_modules->bind_param("ii", $user_id, $praktikum_id);
        $stmt_modules->execute();
        $result_modules = $stmt_modules->get_result();

        if ($result_modules->num_rows > 0) {
            while ($row = $result_modules->fetch_assoc()) {
                $modules[] = $row;
            }
        }
        $stmt_modules->close();
    }
    // --- AKHIR BAGIAN PENGAMBILAN MODUL ---

}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Praktikum: <?php echo htmlspecialchars($praktikum['nama_praktikum']); ?> - SIMPRAK</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

    <nav class="bg-blue-600 p-4 text-white flex justify-between items-center">
        <a href="mahasiswa/dashboard.php" class="text-xl font-bold">SIMPRAK Mahasiswa</a>
        <div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <span>Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?></span>
                <a href="logout.php" class="ml-4 bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded text-sm">Logout</a>
            <?php else: ?>
                <a href="login.php" class="ml-4 bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded text-sm">Login</a>
            <?php endif; ?>
        </div>
    </nav>


    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Detail Praktikum</h1>

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

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h2>
            <p class="text-gray-700 text-md mb-4"><?php echo htmlspecialchars($praktikum['deskripsi']); ?></p>

            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'mahasiswa'): ?>
                <?php if ($is_enrolled): ?>
                    <p class="text-green-600 font-bold mb-4">Anda sudah terdaftar di praktikum ini.</p>
                    <a href="mahasiswa/praktikum_saya.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded mt-4 inline-block">Lihat Praktikum Saya</a>
                <?php else: ?>
                    <form method="POST" action="">
                        <button type="submit" name="daftar_praktikum" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded mt-4">Daftar Praktikum Ini</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-gray-600">Silakan <a href="login.php" class="text-blue-500 hover:underline">login</a> sebagai mahasiswa untuk mendaftar praktikum ini.</p>
            <?php endif; ?>
        </div>

        <h3 class="text-2xl font-bold text-gray-800 mb-4">Modul & Tugas</h3>
        <?php if ($is_enrolled): // Hanya tampilkan jika mahasiswa sudah terdaftar ?>
            <?php if (!empty($modules)): ?>
                <div class="grid grid-cols-1 gap-6">
                    <?php foreach ($modules as $modul): ?>
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h4 class="text-xl font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($modul['nama_modul']); ?></h4>
                            <p class="text-gray-700 text-sm mb-4"><?php echo htmlspecialchars($modul['deskripsi_modul']); ?></p>

                            <?php if (!empty($modul['file_materi'])): ?>
                                <p class="mb-2">
                                    <span class="font-semibold">Materi:</span>
                                    <a href="uploads/materi/<?php echo htmlspecialchars($modul['file_materi']); ?>" download class="text-blue-500 hover:underline ml-2">Unduh Materi</a>
                                </p>
                            <?php else: ?>
                                <p class="text-gray-500 mb-2">Materi belum tersedia.</p>
                            <?php endif; ?>

                            <hr class="my-4">

                            <h5 class="text-lg font-semibold text-gray-800 mb-2">Laporan Tugas</h5>
                            <?php if (!empty($modul['report_id'])): ?>
                                <p class="text-gray-700">Status: <span class="font-bold text-blue-600"><?php echo htmlspecialchars(ucfirst($modul['report_status'])); ?></span></p>
                                <p class="text-gray-700">Dikumpulkan pada: <?php echo date('d M Y, H:i', strtotime($modul['tanggal_pengumpulan'])); ?></p>
                                <p><a href="uploads/laporan/<?php echo htmlspecialchars($modul['file_laporan']); ?>" download class="text-blue-500 hover:underline">Unduh Laporan Anda</a></p>

                                <?php if (!empty($modul['nilai'])): ?>
                                    <h6 class="text-md font-bold mt-2">Nilai: <span class="text-green-700 text-lg"><?php echo htmlspecialchars($modul['nilai']); ?></span></h6>
                                    <?php if (!empty($modul['feedback'])): ?>
                                        <p class="text-gray-700">Feedback: <?php echo htmlspecialchars($modul['feedback']); ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-gray-500">Nilai belum tersedia.</p>
                                <?php endif; ?>

                            <?php else: ?>
                                <p class="text-gray-600 mb-2">Anda belum mengumpulkan laporan untuk modul ini.</p>
                                <form action="upload_laporan.php" method="POST" enctype="multipart/form-data" class="mt-4">
                                    <input type="hidden" name="module_id" value="<?php echo htmlspecialchars($modul['id']); ?>">
                                    <label for="file_laporan_<?php echo $modul['id']; ?>" class="block text-gray-700 text-sm font-bold mb-2">Pilih File Laporan (PDF/DOCX)</label>
                                    <input type="file" id="file_laporan_<?php echo $modul['id']; ?>" name="file_laporan" accept=".pdf,.docx" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded mt-2 text-sm">Unggah Laporan</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-600">Belum ada modul yang tersedia untuk praktikum ini.</p>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-gray-600">Daftar ke praktikum ini untuk melihat modul dan tugasnya.</p>
        <?php endif; ?>


        <div class="text-center mt-8">
            <a href="katalog_praktikum.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg inline-block transition-colors duration-200 shadow">
            &larr; Kembali ke Katalog Praktikum
            </a>
            <a href="mahasiswa/dashboard.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg inline-block transition-colors duration-200 shadow">
            &larr; Kembali ke Dashboard Mahasiswa
            </a>
        </div>
    </div>

</body>
</html>

<?php
$conn->close();
?>