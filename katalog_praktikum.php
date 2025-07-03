<?php
// Sertakan file konfigurasi database
include 'config.php';

// Pastikan session dimulai jika nanti ada kebutuhan untuk pesan atau user role
// session_start(); // Dapat diaktifkan jika perlu session di halaman publik ini

// Query untuk mengambil semua mata praktikum dari tabel 'praktikums'
$sql = "SELECT id, nama_praktikum, deskripsi FROM praktikums ORDER BY nama_praktikum ASC";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Mata Praktikum - SIMPRAK</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Katalog Mata Praktikum</h1>

        <?php if ($result->num_rows > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($row['nama_praktikum']); ?></h2>
                        <p class="text-gray-700 text-sm mb-4"><?php echo htmlspecialchars($row['deskripsi']); ?></p>
                        <div class="flex justify-end">
                            <a href="detail_praktikum.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded text-sm">Lihat Detail / Daftar</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-600">Belum ada mata praktikum yang tersedia saat ini.</p>
        <?php endif; ?>

        <div class="text-center mt-8 space-y-4">
            <a href="login.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg inline-block transition-colors duration-200 shadow w-60">Login</a>
            
            <a href="register.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg inline-block transition-colors duration-200 shadow w-60">Registrasi Akun</a>
        </div>
    </div>

</body>
</html>

<?php
// Tutup koneksi database
$conn->close();
?>