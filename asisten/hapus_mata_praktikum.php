<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config.php';

// Cek jika pengguna belum login atau bukan asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

// Pastikan ada ID yang dikirim melalui GET
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $praktikum_id = trim($_GET['id']);

    // Hapus data dari database
    $sql_delete = "DELETE FROM mata_praktikum WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $praktikum_id);

    if ($stmt_delete->execute()) {
        header("Location: mata_praktikum.php?status=hapus_sukses");
        exit();
    } else {
        // Jika gagal, redirect dengan pesan error
        header("Location: mata_praktikum.php?status=hapus_gagal&error=" . urlencode($stmt_delete->error));
        exit();
    }
    $stmt_delete->close();
} else {
    // Jika tidak ada ID, redirect kembali
    header("Location: mata_praktikum.php");
    exit();
}

$conn->close();
?>