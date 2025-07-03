<?php
session_start();
include '../config.php';

// Cek apakah user sudah login dan role-nya asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$user_id_to_delete = $_GET['id'] ?? null;
$current_asisten_id = $_SESSION['user_id'];

// Validasi ID pengguna yang akan dihapus
if (!$user_id_to_delete || !is_numeric($user_id_to_delete)) {
    $_SESSION['message'] = "ID pengguna tidak valid.";
    $_SESSION['message_type'] = "error";
    header("Location: user_management.php");
    exit();
}

// Pencegahan: Asisten tidak bisa menghapus akunnya sendiri
if ($user_id_to_delete == $current_asisten_id) {
    $_SESSION['message'] = "Anda tidak dapat menghapus akun Anda sendiri.";
    $_SESSION['message_type'] = "error";
    header("Location: user_management.php");
    exit();
}

// Mulai transaksi untuk memastikan integritas data
$conn->begin_transaction();

try {
    // 1. Hapus entri terkait di tabel 'grades'
    // Ini perlu karena 'reports' dan 'grades' mungkin memiliki foreign key constraint
    $stmt_delete_grades = $conn->prepare("DELETE g FROM grades g JOIN reports r ON g.report_id = r.id WHERE r.user_id = ?");
    $stmt_delete_grades->bind_param("i", $user_id_to_delete);
    $stmt_delete_grades->execute();
    $stmt_delete_grades->close();

    // 2. Hapus entri terkait di tabel 'reports'
    // Hapus juga file laporan fisik jika ada
    $stmt_select_reports = $conn->prepare("SELECT file_laporan FROM reports WHERE user_id = ?");
    $stmt_select_reports->bind_param("i", $user_id_to_delete);
    $stmt_select_reports->execute();
    $result_reports = $stmt_select_reports->get_result();
    while ($row_report = $result_reports->fetch_assoc()) {
        $file_path = '../uploads/laporan/' . $row_report['file_laporan'];
        if (file_exists($file_path) && is_file($file_path)) {
            unlink($file_path); // Hapus file fisik
        }
    }
    $stmt_select_reports->close();

    $stmt_delete_reports = $conn->prepare("DELETE FROM reports WHERE user_id = ?");
    $stmt_delete_reports->bind_param("i", $user_id_to_delete);
    $stmt_delete_reports->execute();
    $stmt_delete_reports->close();

    // 3. Hapus entri terkait di tabel 'enrollments'
    $stmt_delete_enrollments = $conn->prepare("DELETE FROM enrollments WHERE user_id = ?");
    $stmt_delete_enrollments->bind_param("i", $user_id_to_delete);
    $stmt_delete_enrollments->execute();
    $stmt_delete_enrollments->close();

    // 4. Hapus akun pengguna dari tabel 'users'
    $stmt_delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt_delete_user->bind_param("i", $user_id_to_delete);
    $stmt_delete_user->execute();
    $stmt_delete_user->close();

    // Commit transaksi jika semua berhasil
    $conn->commit();

    $_SESSION['message'] = "Akun pengguna berhasil dihapus beserta data terkait!";
    $_SESSION['message_type'] = "success";

} catch (Exception $e) {
    // Rollback transaksi jika ada error
    $conn->rollback();
    $_SESSION['message'] = "Gagal menghapus akun: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
}

$conn->close();
header("Location: user_management.php");
exit();
?>