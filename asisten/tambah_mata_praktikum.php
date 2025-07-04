<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Tambah Mata Praktikum';
$activePage = 'mata_praktikum'; // Tetap aktifkan menu mata praktikum

require_once '../config.php';
require_once 'templates/header.php';

// Cek jika pengguna belum login atau bukan asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$message = '';

// Proses jika ada pengiriman form (metode POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_praktikum = trim($_POST['nama_praktikum']);
    $deskripsi = trim($_POST['deskripsi']);

    // Validasi sederhana
    if (empty($nama_praktikum)) {
        $message = "Nama Praktikum tidak boleh kosong!";
    } else {
        // Cek apakah nama praktikum sudah ada
        $sql_check = "SELECT id FROM mata_praktikum WHERE nama_praktikum = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $nama_praktikum);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $message = "Nama Praktikum sudah terdaftar. Gunakan nama lain.";
        } else {
            // Masukkan data ke database
            $sql_insert = "INSERT INTO mata_praktikum (nama_praktikum, deskripsi) VALUES (?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ss", $nama_praktikum, $deskripsi);

            if ($stmt_insert->execute()) {
                // Redirect kembali ke halaman manajemen mata praktikum dengan pesan sukses
                header("Location: mata_praktikum.php?status=tambah_sukses");
                exit();
            } else {
                $message = "Terjadi kesalahan saat menambahkan mata praktikum: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
?>

<div class="container mx-auto p-4">
    <h2 class="text-2xl font-bold mb-4">Tambah Mata Praktikum Baru</h2>

    <?php if (!empty($message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $message; ?></span>
        </div>
    <?php endif; ?>

    <form action="tambah_mata_praktikum.php" method="POST" class="bg-white p-6 rounded-lg shadow-md">
        <div class="mb-4">
            <label for="nama_praktikum" class="block text-gray-700 text-sm font-bold mb-2">Nama Praktikum:</label>
            <input type="text" id="nama_praktikum" name="nama_praktikum" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>
        <div class="mb-4">
            <label for="deskripsi" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi:</label>
            <textarea id="deskripsi" name="deskripsi" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
        </div>
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Simpan Praktikum
            </button>
            <a href="mata_praktikum.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                Kembali
            </a>
        </div>
    </form>
</div>

<?php
require_once 'templates/footer.php';
?>