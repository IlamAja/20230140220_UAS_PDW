<?php
session_start();
include '../config.php'; // Kembali satu level untuk mengakses config.php

// Cek apakah user sudah login dan role-nya asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$praktikum_id = $_GET['id'] ?? null; // Ambil ID praktikum dari URL

// Jika tidak ada ID atau ID tidak valid, redirect kembali
if (!$praktikum_id || !is_numeric($praktikum_id)) {
    $_SESSION['message'] = "ID praktikum tidak valid untuk dihapus.";
    $_SESSION['message_type'] = "error";
    header("Location: praktikum_management.php");
    exit();
}

// Query untuk menghapus praktikum
$sql_delete = "DELETE FROM praktikums WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("i", $praktikum_id);

if ($stmt_delete->execute()) {
    $_SESSION['message'] = "Mata praktikum berhasil dihapus!";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Gagal menghapus mata praktikum: " . $conn->error;
    $_SESSION['message_type'] = "error";
}
$stmt_delete->close();
$conn->close();

header("Location: praktikum_management.php"); // Redirect kembali ke halaman manajemen praktikum
exit();
?>