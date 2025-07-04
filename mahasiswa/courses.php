<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Cari Praktikum';
$activePage = 'courses'; // Variabel untuk menandai menu aktif

require_once '../config.php';
require_once 'templates/header_mahasiswa.php'; // Menggunakan header_mahasiswa.php

// Ambil data mata praktikum dari database
$sql = "SELECT id, nama_praktikum, deskripsi FROM mata_praktikum ORDER BY nama_praktikum ASC";
$result = $conn->query($sql);

?>

<div class="container mx-auto p-4">
    <h2 class="text-2xl font-bold mb-4">Daftar Mata Praktikum Tersedia</h2>

    <?php
    // Tambahkan blok ini untuk menampilkan pesan status
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        $type = $_GET['type'] ?? 'info'; // default ke info
        $msg = $_GET['msg'] ?? '';

        $alertClass = '';
        if ($type == 'success') {
            $alertClass = 'bg-green-100 border border-green-400 text-green-700';
            $msg = $msg ?: 'Operasi berhasil!';
        } elseif ($type == 'error') {
            $alertClass = 'bg-red-100 border border-red-400 text-red-700';
            $msg = $msg ?: 'Terjadi kesalahan!';
        } elseif ($type == 'warning') {
            $alertClass = 'bg-yellow-100 border border-yellow-400 text-yellow-700';
            $msg = $msg ?: 'Peringatan!';
        } else {
            $alertClass = 'bg-blue-100 border border-blue-400 text-blue-700';
            $msg = $msg ?: 'Informasi.';
        }

        if ($status == 'register_success') {
            $msg = 'Anda berhasil mendaftar pada praktikum ini!';
        } elseif ($status == 'already_registered') {
            $msg = 'Anda sudah terdaftar pada praktikum ini.';
        } elseif ($status == 'praktikum_not_found') {
            $msg = 'Mata praktikum yang Anda coba daftar tidak ditemukan.';
        } elseif ($status == 'register_fail') {
            $msg = 'Gagal mendaftar praktikum: ' . htmlspecialchars(urldecode($msg));
        } elseif ($status == 'no_id_provided') {
            $msg = 'Tidak ada ID praktikum yang disediakan.';
        }

        echo '<div class="' . $alertClass . ' px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">' . $msg . '</span></div>';
    }
    ?>
    <?php if ($result->num_rows > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($row['nama_praktikum']); ?></h3>
                    <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($row['deskripsi']); ?></p>
                    <a href="register_praktikum.php?id=<?php echo $row['id']; ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">Daftar Praktikum</a>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="mt-4 text-gray-700">Belum ada mata praktikum yang tersedia saat ini.</p>
    <?php endif; ?>
</div>

<?php
require_once 'templates/footer_mahasiswa.php'; // Menggunakan footer_mahasiswa.php
$conn->close();
?>