<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Edit Mata Praktikum';
$activePage = 'mata_praktikum';

require_once '../config.php';
require_once 'templates/header.php';

// Cek jika pengguna belum login atau bukan asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$praktikum_id = null;
$nama_praktikum = '';
$deskripsi = '';

// Ambil ID praktikum dari URL
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $praktikum_id = trim($_GET['id']);

    // Ambil data praktikum dari database
    $sql_select = "SELECT nama_praktikum, deskripsi FROM mata_praktikum WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $praktikum_id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();

    if ($result_select->num_rows === 1) {
        $row = $result_select->fetch_assoc();
        $nama_praktikum = $row['nama_praktikum'];
        $deskripsi = $row['deskripsi'];
    } else {
        // Jika ID tidak ditemukan, redirect atau tampilkan pesan error
        header("Location: mata_praktikum.php?status=not_found");
        exit();
    }
    $stmt_select->close();
} else {
    // Jika tidak ada ID, redirect
    header("Location: mata_praktikum.php");
    exit();
}

// Proses jika ada pengiriman form (metode POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_praktikum_baru = trim($_POST['nama_praktikum']);
    $deskripsi_baru = trim($_POST['deskripsi']);
    $praktikum_id_post = trim($_POST['id']);

    // Validasi sederhana
    if (empty($nama_praktikum_baru)) {
        $message = "Nama Praktikum tidak boleh kosong!";
    } else {
        // Cek apakah nama praktikum baru sudah ada di praktikum lain
        $sql_check = "SELECT id FROM mata_praktikum WHERE nama_praktikum = ? AND id != ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("si", $nama_praktikum_baru, $praktikum_id_post);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $message = "Nama Praktikum sudah terdaftar untuk mata praktikum lain. Gunakan nama lain.";
        } else {
            // Update data di database
            $sql_update = "UPDATE mata_praktikum SET nama_praktikum = ?, deskripsi = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("ssi", $nama_praktikum_baru, $deskripsi_baru, $praktikum_id_post);

            if ($stmt_update->execute()) {
                // Redirect kembali ke halaman manajemen mata praktikum dengan pesan sukses
                header("Location: mata_praktikum.php?status=edit_sukses");
                exit();
            } else {
                $message = "Terjadi kesalahan saat memperbarui mata praktikum: " . $stmt_update->error;
            }
            $stmt_update->close();
        }
        $stmt_check->close();
    }
    // Update nilai variabel form agar tetap menampilkan nilai yang baru dimasukkan jika ada error
    $nama_praktikum = $nama_praktikum_baru;
    $deskripsi = $deskripsi_baru;
}
?>

<div class="container mx-auto p-4">
    <h2 class="text-2xl font-bold mb-4">Edit Mata Praktikum</h2>

    <?php if (!empty($message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $message; ?></span>
        </div>
    <?php endif; ?>

    <form action="edit_mata_praktikum.php?id=<?php echo $praktikum_id; ?>" method="POST" class="bg-white p-6 rounded-lg shadow-md">
        <input type="hidden" name="id" value="<?php echo $praktikum_id; ?>">
        <div class="mb-4">
            <label for="nama_praktikum" class="block text-gray-700 text-sm font-bold mb-2">Nama Praktikum:</label>
            <input type="text" id="nama_praktikum" name="nama_praktikum" value="<?php echo htmlspecialchars($nama_praktikum); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>
        <div class="mb-4">
            <label for="deskripsi" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi:</label>
            <textarea id="deskripsi" name="deskripsi" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($deskripsi); ?></textarea>
        </div>
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Update Praktikum
            </button>
            <a href="mata_praktikum.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                Kembali
            </a>
        </div>
    </form>
</div>

<?php
require_once 'templates/footer.php';
$conn->close();
?>