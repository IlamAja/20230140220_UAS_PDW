<?php
session_start();
include '../config.php';

// Cek apakah user sudah login dan role-nya asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$nama_asisten = htmlspecialchars($_SESSION['nama']);
$user_id = $_GET['id'] ?? null;
$user_data = null;
$message = '';
$message_type = '';

// Validasi user_id
if (!$user_id || !is_numeric($user_id)) {
    $_SESSION['message'] = "ID pengguna tidak valid.";
    $_SESSION['message_type'] = "error";
    header("Location: user_management.php");
    exit();
}

// Ambil data pengguna yang akan diedit
$stmt_fetch = $conn->prepare("SELECT id, nama, email, role FROM users WHERE id = ?");
$stmt_fetch->bind_param("i", $user_id);
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();

if ($result_fetch->num_rows > 0) {
    $user_data = $result_fetch->fetch_assoc();
} else {
    $_SESSION['message'] = "Pengguna tidak ditemukan.";
    $_SESSION['message_type'] = "error";
    header("Location: user_management.php");
    exit();
}
$stmt_fetch->close();


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'] ?? ''; // Opsional, hanya diisi jika ingin ganti password
    $confirm_password = $_POST['confirm_password'] ?? ''; // Opsional

    // Validasi input
    if (empty($nama) || empty($email) || empty($role)) {
        $message = "Nama, email, dan role tidak boleh kosong.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid.";
        $message_type = "error";
    } elseif (!empty($password) && $password !== $confirm_password) {
        $message = "Konfirmasi password tidak cocok.";
        $message_type = "error";
    } elseif (!empty($password) && strlen($password) < 6) {
        $message = "Password minimal 6 karakter.";
        $message_type = "error";
    } else {
        // Cek apakah email sudah terdaftar oleh pengguna lain (kecuali diri sendiri)
        $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt_check_email->bind_param("si", $email, $user_id);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();

        if ($stmt_check_email->num_rows > 0) {
            $message = "Email sudah terdaftar untuk pengguna lain. Gunakan email lain.";
            $message_type = "error";
        } else {
            // Bangun query UPDATE
            $sql_update = "UPDATE users SET nama = ?, email = ?, role = ?";
            $param_types = "sss";
            $params = [$nama, $email, $role];

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql_update .= ", password = ?";
                $param_types .= "s";
                $params[] = $hashed_password;
            }

            $sql_update .= " WHERE id = ?";
            $param_types .= "i";
            $params[] = $user_id;

            $stmt_update = $conn->prepare($sql_update);
            // Menggunakan call_user_func_array untuk bind_param dengan array dinamis
            call_user_func_array([$stmt_update, 'bind_param'], array_merge([$param_types], $params));

            if ($stmt_update->execute()) {
                $_SESSION['message'] = "Akun '" . htmlspecialchars($nama) . "' berhasil diperbarui!";
                $_SESSION['message_type'] = "success";
                header("Location: user_management.php"); // Redirect ke halaman manajemen pengguna
                exit();
            } else {
                $message = "Gagal memperbarui akun: " . $conn->error;
                $message_type = "error";
            }
            $stmt_update->close();
        }
        $stmt_check_email->close();
    }
    // Jika ada error, muat ulang data pengguna agar form terisi dengan data terbaru (jika ada perubahan yang valid)
    $stmt_fetch = $conn->prepare("SELECT id, nama, email, role FROM users WHERE id = ?");
    $stmt_fetch->bind_param("i", $user_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    $user_data = $result_fetch->fetch_assoc();
    $stmt_fetch->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Akun Pengguna - SIMPRAK</title>
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
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Edit Akun Pengguna: <?php echo htmlspecialchars($user_data['nama']); ?></h1>

        <?php if (!empty($message)): ?>
            <div class="<?php echo $message_type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form action="user_edit.php?id=<?php echo htmlspecialchars($user_id); ?>" method="POST">
                <div class="mb-4">
                    <label for="nama" class="block text-gray-700 text-sm font-bold mb-2">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($user_data['nama']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-4">
                    <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Role</label>
                    <select id="role" name="role" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <option value="mahasiswa" <?php echo ($user_data['role'] === 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                        <option value="asisten" <?php echo ($user_data['role'] === 'asisten') ? 'selected' : ''; ?>>Asisten</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password Baru (Kosongkan jika tidak ingin mengubah)</label>
                    <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-6">
                    <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Konfirmasi Password Baru</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Perbarui Akun</button>
                    <a href="user_management.php" class="inline-block align-baseline font-bold text-sm text-gray-600 hover:text-gray-800">Batal</a>
                </div>
            </form>
        </div>

        <div class="text-center mt-8">
            <a href="dashboard.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg inline-block transition-colors duration-200 shadow">Kembali ke Dashboard Asisten</a>
        </div>
    </div>

</body>
</html>