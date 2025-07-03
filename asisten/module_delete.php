<?php
session_start();
include '../config.php'; // Kembali satu level untuk mengakses config.php

// Cek apakah user sudah login dan role-nya asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$module_id = $_GET['id'] ?? null;
$praktikum_id_redirect = $_GET['praktikum_id'] ?? null; // Untuk redirect kembali ke manajemen modul

// Validasi ID Modul dan Praktikum
if (!$module_id || !is_numeric($module_id) || !$praktikum_id_redirect || !is_numeric($praktikum_id_redirect)) {
    $_SESSION['message'] = "ID modul atau praktikum tidak valid untuk dihapus.";
    $_SESSION['message_type'] = "error";
    header("Location: praktikum_management.php"); // Fallback jika ID tidak valid
    exit();
}

// Ambil nama file materi yang terkait sebelum menghapus modul
$file_to_delete = null;
$stmt_get_file = $conn->prepare("SELECT file_materi FROM modules WHERE id = ?");
$stmt_get_file->bind_param("i", $module_id);
$stmt_get_file->execute();
$result_get_file = $stmt_get_file->get_result();
if ($row_file = $result_get_file->fetch_assoc()) {
    $file_to_delete = $row_file['file_materi'];
}
$stmt_get_file->close();


// Query untuk menghapus modul
$sql_delete = "DELETE FROM modules WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("i", $module_id);

if ($stmt_delete->execute()) {
    // Jika penghapusan dari DB berhasil, hapus juga file fisik materi jika ada
    if ($file_to_delete && file_exists('../uploads/materi/' . $file_to_delete)) {
        unlink('../uploads/materi/' . $file_to_delete);
    }
    $_SESSION['message'] = "Modul berhasil dihapus!";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Gagal menghapus modul: " . $conn->error;
    $_SESSION['message_type'] = "error";
}
$stmt_delete->close();
$conn->close();

header("Location: module_management.php?praktikum_id=" . $praktikum_id_redirect); // Redirect kembali ke halaman manajemen modul
exit();
?>