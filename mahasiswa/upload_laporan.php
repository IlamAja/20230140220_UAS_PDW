<?php
session_start();
include 'config.php';

// Pastikan hanya mahasiswa yang sudah login yang bisa mengakses ini
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    $_SESSION['message'] = "Anda harus login sebagai mahasiswa untuk mengunggah laporan.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$module_id = $_POST['module_id'] ?? null;
$praktikum_id_redirect = ''; // Untuk redirect kembali ke detail praktikum yang benar

if ($_SERVER["REQUEST_METHOD"] == "POST" && $module_id) {
    // Ambil praktikum_id dari module_id untuk redirect yang benar
    $stmt_get_praktikum_id = $conn->prepare("SELECT praktikum_id FROM modules WHERE id = ?");
    $stmt_get_praktikum_id->bind_param("i", $module_id);
    $stmt_get_praktikum_id->execute();
    $result_get_praktikum_id = $stmt_get_praktikum_id->get_result();
    if ($row = $result_get_praktikum_id->fetch_assoc()) {
        $praktikum_id_redirect = $row['praktikum_id'];
    }
    $stmt_get_praktikum_id->close();

    // Pastikan praktikum_id_redirect ditemukan
    if (empty($praktikum_id_redirect)) {
        $_SESSION['message'] = "ID modul tidak valid.";
        $_SESSION['message_type'] = "error";
        header("Location: mahasiswa/dashboard.php"); // Redirect ke dashboard jika modul tidak valid
        exit();
    }

    // Cek apakah mahasiswa sudah terdaftar di praktikum modul ini
    $stmt_check_enrollment = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND praktikum_id = ?");
    $stmt_check_enrollment->bind_param("ii", $user_id, $praktikum_id_redirect);
    $stmt_check_enrollment->execute();
    $result_check_enrollment = $stmt_check_enrollment->get_result();
    if ($result_check_enrollment->num_rows === 0) {
        $_SESSION['message'] = "Anda tidak terdaftar di praktikum untuk modul ini.";
        $_SESSION['message_type'] = "error";
        header("Location: detail_praktikum.php?id=" . $praktikum_id_redirect);
        exit();
    }
    $stmt_check_enrollment->close();

    // Cek apakah laporan untuk modul ini sudah diunggah sebelumnya oleh user ini
    $stmt_check_report = $conn->prepare("SELECT id FROM reports WHERE user_id = ? AND module_id = ?");
    $stmt_check_report->bind_param("ii", $user_id, $module_id);
    $stmt_check_report->execute();
    $result_check_report = $stmt_check_report->get_result();
    if ($result_check_report->num_rows > 0) {
        $_SESSION['message'] = "Anda sudah mengunggah laporan untuk modul ini. Untuk memperbarui, hubungi asisten.";
        $_SESSION['message_type'] = "warning";
        header("Location: detail_praktikum.php?id=" . $praktikum_id_redirect);
        exit();
    }
    $stmt_check_report->close();

    // Handle file upload
    if (isset($_FILES['file_laporan']) && $_FILES['file_laporan']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['file_laporan']['tmp_name'];
        $file_name = $_FILES['file_laporan']['name'];
        $file_size = $_FILES['file_laporan']['size'];
        $file_type = $_FILES['file_laporan']['type'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_ext = ['pdf', 'docx'];
        $max_file_size = 5 * 1024 * 1024; // 5 MB

        if (!in_array($file_ext, $allowed_ext)) {
            $_SESSION['message'] = "Hanya file PDF atau DOCX yang diizinkan.";
            $_SESSION['message_type'] = "error";
        } elseif ($file_size > $max_file_size) {
            $_SESSION['message'] = "Ukuran file terlalu besar. Maksimal 5 MB.";
            $_SESSION['message_type'] = "error";
        } else {
            // Buat nama file unik untuk mencegah konflik
            $new_file_name = uniqid('laporan_', true) . '.' . $file_ext;
            $upload_path = 'uploads/laporan/' . $new_file_name; // Path relatif dari root proyek

            if (move_uploaded_file($file_tmp_name, $upload_path)) {
                // Simpan info laporan ke database
                $stmt_insert_report = $conn->prepare("INSERT INTO reports (user_id, module_id, file_laporan, status) VALUES (?, ?, ?, 'submitted')");
                $stmt_insert_report->bind_param("iis", $user_id, $module_id, $new_file_name);

                if ($stmt_insert_report->execute()) {
                    $_SESSION['message'] = "Laporan berhasil diunggah!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Gagal menyimpan info laporan ke database: " . $conn->error;
                    $_SESSION['message_type'] = "error";
                    // Hapus file jika gagal simpan ke DB
                    unlink($upload_path);
                }
                $stmt_insert_report->close();
            } else {
                $_SESSION['message'] = "Gagal mengunggah file laporan.";
                $_SESSION['message_type'] = "error";
            }
        }
    } else {
        $_SESSION['message'] = "Tidak ada file yang diunggah atau terjadi kesalahan.";
        $_SESSION['message_type'] = "error";
    }
} else {
    // Jika diakses tanpa POST atau module_id
    $_SESSION['message'] = "Akses tidak valid.";
    $_SESSION['message_type'] = "error";
}

$conn->close();

// Redirect kembali ke halaman detail praktikum
header("Location: detail_praktikum.php?id=" . ($praktikum_id_redirect ?: '')); // Fallback jika praktikum_id tidak ditemukan
exit();
?>