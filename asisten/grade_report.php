<?php
session_start();
include '../config.php';

// Cek apakah user sudah login dan role-nya asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$nama_asisten = htmlspecialchars($_SESSION['nama']);
$report_id = $_GET['report_id'] ?? null;
$report_data = null;
$message = '';
$message_type = '';

// Validasi report_id
if (!$report_id || !is_numeric($report_id)) {
    $_SESSION['message'] = "ID laporan tidak valid.";
    $_SESSION['message_type'] = "error";
    header("Location: report_inbox.php");
    exit();
}

// Ambil detail laporan dan info terkait
$sql_report = "SELECT r.id AS report_id, r.file_laporan, r.tanggal_pengumpulan, r.status,
                      u.id AS user_id, u.nama AS user_nama, u.email AS user_email,
                      m.nama_modul,
                      p.nama_praktikum,
                      g.nilai, g.feedback, g.tanggal_penilaian
               FROM reports r
               JOIN users u ON r.user_id = u.id
               JOIN modules m ON r.module_id = m.id
               JOIN praktikums p ON m.praktikum_id = p.id
               LEFT JOIN grades g ON r.id = g.report_id
               WHERE r.id = ?";
$stmt_report = $conn->prepare($sql_report);
$stmt_report->bind_param("i", $report_id);
$stmt_report->execute();
$result_report = $stmt_report->get_result();

if ($result_report->num_rows > 0) {
    $report_data = $result_report->fetch_assoc();
} else {
    $_SESSION['message'] = "Laporan tidak ditemukan.";
    $_SESSION['message_type'] = "error";
    header("Location: report_inbox.php");
    exit();
}
$stmt_report->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nilai = trim($_POST['nilai']);
    $feedback = trim($_POST['feedback']);
    $asisten_id = $_SESSION['user_id'];

    if (!is_numeric($nilai) || $nilai < 0 || $nilai > 100) { // Validasi nilai 0-100
        $message = "Nilai harus angka antara 0 dan 100.";
        $message_type = "error";
    } else {
        if (!empty($report_data['nilai'])) { // Jika nilai sudah ada (UPDATE)
            $sql_grade = "UPDATE grades SET nilai = ?, feedback = ?, asisten_id = ?, tanggal_penilaian = CURRENT_TIMESTAMP WHERE report_id = ?";
            $stmt_grade = $conn->prepare($sql_grade);
            $stmt_grade->bind_param("dsii", $nilai, $feedback, $asisten_id, $report_id);
        } else { // Jika nilai belum ada (INSERT)
            $sql_grade = "INSERT INTO grades (report_id, asisten_id, nilai, feedback) VALUES (?, ?, ?, ?)";
            $stmt_grade = $conn->prepare($sql_grade);
            $stmt_grade->bind_param("iids", $report_id, $asisten_id, $nilai, $feedback);
        }

        if ($stmt_grade->execute()) {
            // Update status laporan menjadi 'graded' di tabel reports
            $stmt_update_report_status = $conn->prepare("UPDATE reports SET status = 'graded' WHERE id = ?");
            $stmt_update_report_status->bind_param("i", $report_id);
            $stmt_update_report_status->execute();
            $stmt_update_report_status->close();

            $_SESSION['message'] = "Nilai dan feedback laporan berhasil disimpan!";
            $_SESSION['message_type'] = "success";
            header("Location: report_inbox.php"); // Redirect kembali ke inbox laporan
            exit();
        } else {
            $message = "Gagal menyimpan nilai: " . $conn->error;
            $message_type = "error";
        }
        $stmt_grade->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beri Nilai Laporan - SIMPRAK</title>
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
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Beri Nilai Laporan</h1>

        <?php if (!empty($message)): ?>
            <div class="<?php echo $message_type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Detail Laporan</h2>
            <p class="text-gray-700 mb-1">Praktikum: <span class="font-semibold"><?php echo htmlspecialchars($report_data['nama_praktikum']); ?></span></p>
            <p class="text-gray-700 mb-1">Modul: <span class="font-semibold"><?php echo htmlspecialchars($report_data['nama_modul']); ?></span></p>
            <p class="text-gray-700 mb-1">Mahasiswa: <span class="font-semibold"><?php echo htmlspecialchars($report_data['user_nama']); ?> (<?php echo htmlspecialchars($report_data['user_email']); ?>)</span></p>
            <p class="text-gray-700 mb-4">Dikumpulkan pada: <?php echo date('d M Y, H:i', strtotime($report_data['tanggal_pengumpulan'])); ?></p>
            
            <a href="../uploads/laporan/<?php echo htmlspecialchars($report_data['file_laporan']); ?>" download class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded text-sm inline-block">Unduh File Laporan</a>

            <hr class="my-6">

            <h2 class="text-2xl font-semibold text-gray-900 mb-4">Form Penilaian</h2>
            <form action="grade_report.php?report_id=<?php echo htmlspecialchars($report_id); ?>" method="POST">
                <div class="mb-4">
                    <label for="nilai" class="block text-gray-700 text-sm font-bold mb-2">Nilai (0-100)</label>
                    <input type="number" id="nilai" name="nilai" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($report_data['nilai'] ?? ''); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-6">
                    <label for="feedback" class="block text-gray-700 text-sm font-bold mb-2">Feedback (Opsional)</label>
                    <textarea id="feedback" name="feedback" rows="5" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($report_data['feedback'] ?? ''); ?></textarea>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Simpan Nilai</button>
                    <a href="report_inbox.php" class="inline-block align-baseline font-bold text-sm text-gray-600 hover:text-gray-800">Batal</a>
                </div>
            </form>
        </div>

        <div class="text-center mt-8">
            <a href="dashboard.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg inline-block transition-colors duration-200 shadow">Kembali ke Dashboard Asisten</a>
        </div>
    </div>
</body>
</html>