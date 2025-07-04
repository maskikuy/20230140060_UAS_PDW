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
    $modul_id = trim($_GET['id']);

    // Ambil path file materi sebelum menghapus record dari database
    $file_to_delete = null;
    $sql_get_file = "SELECT file_materi FROM modul WHERE id = ?";
    $stmt_get_file = $conn->prepare($sql_get_file);
    $stmt_get_file->bind_param("i", $modul_id);
    $stmt_get_file->execute();
    $result_get_file = $stmt_get_file->get_result();
    if ($row = $result_get_file->fetch_assoc()) {
        $file_to_delete = $row['file_materi'];
    }
    $stmt_get_file->close();

    // Hapus data dari database
    $sql_delete = "DELETE FROM modul WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $modul_id);

    if ($stmt_delete->execute()) {
        // Jika berhasil dihapus dari DB, hapus juga file fisik jika ada
        if ($file_to_delete && file_exists('../' . $file_to_delete)) {
            unlink('../' . $file_to_delete);
        }
        $stmt_delete->close();
        $conn->close();
        header("Location: modul.php?status=hapus_sukses");
        exit();
    } else {
        $stmt_delete->close();
        $conn->close();
        // Jika gagal, redirect dengan pesan error
        header("Location: modul.php?status=hapus_gagal&error=" . urlencode($stmt_delete->error));
        exit();
    }
} else {
    // Jika tidak ada ID, redirect kembali
    header("Location: modul.php");
    exit();
}

// $conn->close(); // Tidak perlu lagi di sini
?>