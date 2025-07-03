<?php
session_start();
require_once 'config.php';

// Jika sudah login, redirect ke halaman yang sesuai
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'asisten') {
        header("Location: asisten/dashboard.php");
    } elseif ($_SESSION['role'] === 'mahasiswa') {
        header("Location: mahasiswa/dashboard.php");
    }
    exit();
}

$message = ''; // Untuk pesan error login
$status_message = ''; // Untuk pesan sukses/error dari halaman lain (misal: register.php)
$status_type = ''; // Untuk tipe pesan (success, error, warning, info)

// Ambil pesan status dari session (misalnya dari register.php)
if (isset($_SESSION['status_message'])) {
    $status_message = $_SESSION['status_message'];
    $status_type = $_SESSION['status_type'] ?? 'info'; // Default type to info if not set
    unset($_SESSION['status_message']); // Hapus pesan setelah ditampilkan
    unset($_SESSION['status_type']); // Hapus tipe pesan juga
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $message = "Email dan password harus diisi!";
    } else {
        $sql = "SELECT id, nama, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verifikasi password
            if (password_verify($password, $user['password'])) {
                // Password cocok, buat session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];

                // Redirect sesuai role
                if ($user['role'] === 'asisten') {
                    header("Location: asisten/dashboard.php");
                    exit();
                } elseif ($user['role'] === 'mahasiswa') {
                    header("Location: mahasiswa/dashboard.php");
                    exit();
                } else {
                    // Fallback jika peran tidak dikenali
                    $message = "Peran pengguna tidak valid.";
                }

            } else {
                $message = "Password yang Anda masukkan salah.";
            }
        } else {
            $message = "Akun dengan email tersebut tidak ditemukan.";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pengguna - SIMPRAK</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal flex justify-center items-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Login</h2>

        <?php
        // Menampilkan pesan status dari session (misalnya dari register.php)
        if (!empty($status_message)):
            $status_class = '';
            if ($status_type === 'success') {
                $status_class = 'bg-green-100 border-green-400 text-green-700';
            } elseif ($status_type === 'error') {
                $status_class = 'bg-red-100 border-red-400 text-red-700';
            } elseif ($status_type === 'warning') {
                $status_class = 'bg-yellow-100 border-yellow-400 text-yellow-700';
            } else {
                $status_class = 'bg-blue-100 border-blue-400 text-blue-700'; // Default
            }
        ?>
            <div class="<?php echo $status_class; ?> px-4 py-3 rounded relative mb-4 border" role="alert">
                <span class="block sm:inline"><?php echo $status_message; ?></span>
            </div>
        <?php endif; ?>

        <?php
        // Menampilkan pesan error login (dari POST request saat ini)
        if (!empty($message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                <input type="email" id="email" name="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">Login</button>
            </div>
        </form>
        <div class="text-center mt-4">
            <a href="register.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg inline-block transition-colors duration-200 shadow w-full">Daftar di sini</a>
            <a href="katalog_praktikum.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg inline-block transition-colors duration-200 shadow w-full mt-2">&larr; Lihat Katalog Praktikum</a>
        </div>
    </div>
</body>
</html>