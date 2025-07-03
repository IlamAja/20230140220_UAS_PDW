<?php
session_start();
include '../config.php';

// Cek apakah user sudah login dan role-nya asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$nama_asisten = htmlspecialchars($_SESSION['nama']);
$reports = [];
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

// Filter variables
$filter_praktikum_id = $_GET['praktikum_id'] ?? '';
$filter_module_id = $_GET['module_id'] ?? '';
$filter_user_id = $_GET['user_id'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Get lists for filters
$praktikums_list = $conn->query("SELECT id, nama_praktikum FROM praktikums ORDER BY nama_praktikum ASC")->fetch_all(MYSQLI_ASSOC);
$modules_list = $conn->query("SELECT id, nama_modul, praktikum_id FROM modules ORDER BY nama_modul ASC")->fetch_all(MYSQLI_ASSOC);
$users_list = $conn->query("SELECT id, nama FROM users WHERE role = 'mahasiswa' ORDER BY nama ASC")->fetch_all(MYSQLI_ASSOC);

// Base SQL query to fetch reports
$sql = "SELECT r.id AS report_id, r.file_laporan, r.tanggal_pengumpulan, r.status,
               u.id AS user_id, u.nama AS user_nama, u.email AS user_email,
               m.id AS module_id, m.nama_modul, m.deskripsi_modul,
               p.id AS praktikum_id, p.nama_praktikum,
               g.nilai, g.feedback
        FROM reports r
        JOIN users u ON r.user_id = u.id
        JOIN modules m ON r.module_id = m.id
        JOIN praktikums p ON m.praktikum_id = p.id
        LEFT JOIN grades g ON r.id = g.report_id
        WHERE 1=1"; // Dummy condition to easily append filters

$params = [];
$param_types = '';

// Apply filters
if (!empty($filter_praktikum_id)) {
    $sql .= " AND p.id = ?";
    $param_types .= "i";
    $params[] = $filter_praktikum_id;
}
if (!empty($filter_module_id)) {
    $sql .= " AND m.id = ?";
    $param_types .= "i";
    $params[] = $filter_module_id;
}
if (!empty($filter_user_id)) {
    $sql .= " AND u.id = ?";
    $param_types .= "i";
    $params[] = $filter_user_id;
}
if (!empty($filter_status)) {
    $sql .= " AND r.status = ?";
    $param_types .= "s";
    $params[] = $filter_status;
}

$sql .= " ORDER BY r.tanggal_pengumpulan DESC";

$stmt = $conn->prepare($sql);
if ($param_types) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
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
    <title>Lihat Laporan Masuk - SIMPRAK</title>
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
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Laporan Masuk</h1>

        <?php if (!empty($message)): ?>
            <div class="<?php echo $message_type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : ($message_type === 'error' ? 'bg-red-100 border-red-400 text-red-700' : 'bg-yellow-100 border-yellow-400 text-yellow-700'); ?> px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-xl font-semibold text-gray-900 mb-4">Filter Laporan</h3>
            <form action="report_inbox.php" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label for="filter_praktikum_id" class="block text-gray-700 text-sm font-bold mb-2">Praktikum:</label>
                    <select id="filter_praktikum_id" name="praktikum_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Semua Praktikum</option>
                        <?php foreach ($praktikums_list as $pl): ?>
                            <option value="<?php echo $pl['id']; ?>" <?php echo ($filter_praktikum_id == $pl['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pl['nama_praktikum']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter_module_id" class="block text-gray-700 text-sm font-bold mb-2">Modul:</label>
                    <select id="filter_module_id" name="module_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Semua Modul</option>
                        <?php foreach ($modules_list as $ml): ?>
                            <option value="<?php echo $ml['id']; ?>" <?php echo ($filter_module_id == $ml['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ml['nama_modul']); ?> (<?php echo htmlspecialchars($ml['praktikum_id']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter_user_id" class="block text-gray-700 text-sm font-bold mb-2">Mahasiswa:</label>
                    <select id="filter_user_id" name="user_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Semua Mahasiswa</option>
                        <?php foreach ($users_list as $ul): ?>
                            <option value="<?php echo $ul['id']; ?>" <?php echo ($filter_user_id == $ul['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ul['nama']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter_status" class="block text-gray-700 text-sm font-bold mb-2">Status:</label>
                    <select id="filter_status" name="status" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Semua Status</option>
                        <option value="submitted" <?php echo ($filter_status == 'submitted') ? 'selected' : ''; ?>>Submitted</option>
                        <option value="graded" <?php echo ($filter_status == 'graded') ? 'selected' : ''; ?>>Graded</option>
                    </select>
                </div>
                <div class="col-span-full flex justify-end gap-2">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded text-sm">Terapkan Filter</button>
                    <a href="report_inbox.php" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded text-sm">Reset Filter</a>
                </div>
            </form>
        </div>

        <?php if (!empty($reports)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($reports as $report): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <p class="text-sm text-gray-500 mb-1">Praktikum: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($report['nama_praktikum']); ?></span></p>
                        <p class="text-sm text-gray-500 mb-3">Modul: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($report['nama_modul']); ?></span></p>
                        
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Mahasiswa: <?php echo htmlspecialchars($report['user_nama']); ?></h3>
                        <p class="text-gray-700 mb-1">Email: <?php echo htmlspecialchars($report['user_email']); ?></p>
                        <p class="text-gray-700 mb-2">Dikumpulkan: <?php echo date('d M Y, H:i', strtotime($report['tanggal_pengumpulan'])); ?></p>
                        
                        <p class="mb-2">Status: 
                            <span class="font-bold <?php echo ($report['status'] === 'graded') ? 'text-green-600' : 'text-blue-600'; ?>">
                                <?php echo htmlspecialchars(ucfirst($report['status'])); ?>
                            </span>
                        </p>

                        <div class="flex flex-wrap items-center gap-2 mt-4">
                            <a href="../uploads/laporan/<?php echo htmlspecialchars($report['file_laporan']); ?>" download class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded text-sm">Unduh Laporan</a>
                            
                            <?php if ($report['status'] !== 'graded'): ?>
                                <a href="grade_report.php?report_id=<?php echo $report['report_id']; ?>" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded text-sm">Beri Nilai</a>
                            <?php else: ?>
                                <p class="text-green-700 font-bold ml-2">Nilai: <?php echo htmlspecialchars($report['nilai']); ?></p>
                                <?php if (!empty($report['feedback'])): ?>
                                    <p class="text-gray-700 text-sm italic">Feedback: <?php echo htmlspecialchars(substr($report['feedback'], 0, 50)); ?><?php echo (strlen($report['feedback']) > 50 ? '...' : ''); ?></p>
                                <?php endif; ?>
                                <a href="grade_report.php?report_id=<?php echo $report['report_id']; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded text-sm">Lihat/Edit Nilai</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-600">Tidak ada laporan yang masuk.</p>
        <?php endif; ?>

        <div class="text-center mt-8">
            <a href="dashboard.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg inline-block transition-colors duration-200 shadow">Kembali ke Dashboard Asisten</a>
        </div>
    </div>
</body>
</html>