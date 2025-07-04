<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config.php';

// Inisialisasi variabel
$pageTitle = 'Tambah Modul';
$activePage = 'modul';
$message = '';

// Cek jika pengguna belum login atau bukan asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

// Ambil daftar mata praktikum untuk dropdown
$sql_praktikum = "SELECT id, nama_praktikum FROM mata_praktikum ORDER BY nama_praktikum ASC";
$result_praktikum = $conn->query($sql_praktikum);
$mata_praktikum_list = [];
while ($row = $result_praktikum->fetch_assoc()) {
    $mata_praktikum_list[] = $row;
}

// Proses jika ada pengiriman form (metode POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $praktikum_id = trim($_POST['praktikum_id']);
    $judul_modul = trim($_POST['judul_modul']);
    $deskripsi = trim($_POST['deskripsi']);
    $urutan = trim($_POST['urutan']);
    $file_materi_path = null;

    // Validasi sederhana
    if (empty($praktikum_id) || empty($judul_modul) || empty($urutan)) {
        $message = "Nama Praktikum, Judul Modul, dan Urutan tidak boleh kosong!";
    } elseif (!is_numeric($urutan) || $urutan <= 0) {
        $message = "Urutan harus berupa angka positif!";
    } else {
        // Proses upload file materi
        if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] == 0) {
            $allowed_types = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']; // PDF dan DOCX
            $max_file_size = 5 * 1024 * 1024; // 5 MB

            $file_name = $_FILES['file_materi']['name'];
            $file_tmp_name = $_FILES['file_materi']['tmp_name'];
            $file_type = $_FILES['file_materi']['type'];
            $file_size = $_FILES['file_materi']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (!in_array($file_type, $allowed_types) || !in_array($file_ext, ['pdf', 'docx'])) {
                $message = "Tipe file tidak diizinkan. Hanya PDF dan DOCX yang diperbolehkan.";
            } elseif ($file_size > $max_file_size) {
                $message = "Ukuran file terlalu besar. Maksimal 5MB.";
            } else {
                $upload_dir = '../uploads/materi_modul/';
                $new_file_name = uniqid('modul_') . '.' . $file_ext;
                $destination = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp_name, $destination)) {
                    $file_materi_path = 'uploads/materi_modul/' . $new_file_name; // Simpan path relatif ke database
                } else {
                    $message = "Gagal mengunggah file materi.";
                }
            }
        }

        // Jika tidak ada error dari upload file, lanjutkan insert ke database
        if (empty($message)) {
            // Cek apakah judul modul dan urutan sudah ada untuk praktikum yang sama
            $sql_check = "SELECT id FROM modul WHERE praktikum_id = ? AND (judul_modul = ? OR urutan = ?)";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("isi", $praktikum_id, $judul_modul, $urutan);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $message = "Judul modul atau urutan modul sudah ada untuk mata praktikum ini.";
                // Hapus file yang sudah terlanjur diunggah jika ada duplikasi data
                if ($file_materi_path && file_exists('../' . $file_materi_path)) {
                    unlink('../' . $file_materi_path);
                }
            } else {
                // Masukkan data ke database
                $sql_insert = "INSERT INTO modul (praktikum_id, judul_modul, deskripsi, urutan, file_materi) VALUES (?, ?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("issis", $praktikum_id, $judul_modul, $deskripsi, $urutan, $file_materi_path);

                if ($stmt_insert->execute()) {
                    $stmt_insert->close();
                    $conn->close();
                    header("Location: modul.php?status=tambah_sukses");
                    exit();
                } else {
                    $message = "Terjadi kesalahan saat menambahkan modul: " . $stmt_insert->error;
                    // Hapus file yang sudah terlanjur diunggah jika insert gagal
                    if ($file_materi_path && file_exists('../' . $file_materi_path)) {
                        unlink('../' . $file_materi_path);
                    }
                }
                $stmt_insert->close();
            }
            $stmt_check->close();
        }
    }
    // Jika ada error, koneksi ditutup di bagian HTML
}

require_once 'templates/header.php';
?>

<div class="container mx-auto p-4">
    <h2 class="text-2xl font-bold mb-4">Tambah Modul Baru</h2>

    <?php if (!empty($message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $message; ?></span>
        </div>
    <?php endif; ?>

    <form action="tambah_modul.php" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow-md">
        <div class="mb-4">
            <label for="praktikum_id" class="block text-gray-700 text-sm font-bold mb-2">Mata Praktikum:</label>
            <select id="praktikum_id" name="praktikum_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                <option value="">Pilih Mata Praktikum</option>
                <?php foreach ($mata_praktikum_list as $praktikum): ?>
                    <option value="<?php echo $praktikum['id']; ?>"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (empty($mata_praktikum_list)): ?>
                <p class="text-red-500 text-xs italic mt-2">Belum ada mata praktikum yang tersedia. Harap tambahkan mata praktikum terlebih dahulu.</p>
            <?php endif; ?>
        </div>
        <div class="mb-4">
            <label for="judul_modul" class="block text-gray-700 text-sm font-bold mb-2">Judul Modul:</label>
            <input type="text" id="judul_modul" name="judul_modul" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>
        <div class="mb-4">
            <label for="deskripsi" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi:</label>
            <textarea id="deskripsi" name="deskripsi" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
        </div>
        <div class="mb-4">
            <label for="urutan" class="block text-gray-700 text-sm font-bold mb-2">Urutan Modul:</label>
            <input type="number" id="urutan" name="urutan" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required min="1">
        </div>
        <div class="mb-4">
            <label for="file_materi" class="block text-gray-700 text-sm font-bold mb-2">File Materi (PDF/DOCX, maks 5MB):</label>
            <input type="file" id="file_materi" name="file_materi" accept=".pdf,.docx" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Simpan Modul
            </button>
            <a href="modul.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                Kembali
            </a>
        </div>
    </form>
</div>

<?php
require_once 'templates/footer.php';
// Tutup koneksi database di akhir script jika belum ditutup di blok POST
if ($conn->ping()) { // Cek apakah koneksi masih aktif sebelum ditutup
    $conn->close();
}
?>