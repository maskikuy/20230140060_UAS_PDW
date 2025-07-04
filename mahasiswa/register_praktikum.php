<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config.php';

// Cek jika pengguna belum login (hanya mahasiswa yang bisa mendaftar)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$praktikum_id = null;
$message = '';
$status_type = 'error'; // default status type

// Pastikan praktikum_id diterima dari URL
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $praktikum_id = trim($_GET['id']);

    // Cek apakah praktikum_id valid dan mata praktikum ada
    $sql_check_praktikum = "SELECT id FROM mata_praktikum WHERE id = ?";
    $stmt_check_praktikum = $conn->prepare($sql_check_praktikum);
    $stmt_check_praktikum->bind_param("i", $praktikum_id);
    $stmt_check_praktikum->execute();
    $stmt_check_praktikum->store_result();

    if ($stmt_check_praktikum->num_rows === 0) {
        $message = "Mata praktikum tidak ditemukan.";
        // Langsung redirect karena praktikum tidak valid
        header("Location: courses.php?status=praktikum_not_found");
        exit();
    }
    $stmt_check_praktikum->close();

    // Cek apakah mahasiswa sudah terdaftar pada praktikum ini
    $sql_check_reg = "SELECT id FROM pendaftaran_praktikum WHERE user_id = ? AND praktikum_id = ?";
    $stmt_check_reg = $conn->prepare($sql_check_reg);
    $stmt_check_reg->bind_param("ii", $user_id, $praktikum_id);
    $stmt_check_reg->execute();
    $stmt_check_reg->store_result();

    if ($stmt_check_reg->num_rows > 0) {
        $message = "Anda sudah terdaftar pada praktikum ini.";
        $status_type = 'warning'; // Ganti ke warning karena bukan error fatal
        header("Location: courses.php?status=already_registered&type={$status_type}");
        exit();
    }
    $stmt_check_reg->close();

    // Jika belum terdaftar, lakukan pendaftaran
    $sql_insert = "INSERT INTO pendaftaran_praktikum (user_id, praktikum_id) VALUES (?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("ii", $user_id, $praktikum_id);

    if ($stmt_insert->execute()) {
        $message = "Berhasil mendaftar pada praktikum.";
        $status_type = 'success';
        header("Location: courses.php?status=register_success&type={$status_type}");
        exit();
    } else {
        $message = "Gagal mendaftar pada praktikum: " . $stmt_insert->error;
        $status_type = 'error';
        // Check for duplicate entry error specifically (error code 1062)
        if ($conn->errno == 1062) {
            $message = "Anda sudah terdaftar pada praktikum ini (kesalahan duplikat).";
            $status_type = 'warning';
        }
        header("Location: courses.php?status=register_fail&type={$status_type}&msg=" . urlencode($message));
        exit();
    }
    $stmt_insert->close();

} else {
    // Jika tidak ada praktikum_id, redirect kembali ke halaman daftar praktikum
    header("Location: courses.php?status=no_id_provided");
    exit();
}

$conn->close();
?>