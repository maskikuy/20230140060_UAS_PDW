<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config.php';

// Inisialisasi variabel
$pageTitle = 'Edit Modul';
$activePage = 'modul';
$message = '';
$modul_id = null;
$praktikum_id_selected = '';
$judul_modul = '';
$deskripsi = '';
$urutan = '';
$file_materi_existing = ''; // Untuk menyimpan path file yang sudah ada

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
    $modul_id = trim($_POST['modul_id']);
    $praktikum_id_selected = trim($_POST['praktikum_id']);
    $judul_modul = trim($_POST['judul_modul']);
    $deskripsi = trim($_POST['deskripsi']);
    $urutan = trim($_POST['urutan']);
    $file_materi_existing = trim($_POST['file_materi_existing'] ?? ''); // Ambil path file lama
    $file_materi_path = $file_materi_existing; // Default path baru adalah path lama

    // Validasi sederhana
    if (empty($praktikum_id_selected) || empty($judul_modul) || empty($urutan)) {
        $message = "Mata Praktikum, Judul Modul, dan Urutan tidak boleh kosong!";
    } elseif (!is_numeric($urutan) || $urutan <= 0) {
        $message = "Urutan harus berupa angka positif!";
    } else {
        // Proses upload file materi baru
        if (isset($_FILES['file_materi_baru']) && $_FILES['file_materi_baru']['error'] == 0) {
            $allowed_types = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']; // PDF dan DOCX
            $max_file_size = 5 * 1024 * 1024; // 5 MB

            $file_name = $_FILES['file_materi_baru']['name'];
            $file_tmp_name = $_FILES['file_materi_baru']['tmp_name'];
            $file_type = $_FILES['file_materi_baru']['type'];
            $file_size = $_FILES['file_materi_baru']['size'];
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
                    // Hapus file lama jika ada dan file baru berhasil diunggah
                    if ($file_materi_existing && file_exists('../' . $file_materi_existing)) {
                        unlink('../' . $file_materi_existing);
                    }
                    $file_materi_path = 'uploads/materi_modul/' . $new_file_name; // Update path baru
                } else {
                    $message = "Gagal mengunggah file materi baru.";
                }
            }
        }
        // Jika file baru tidak diunggah atau ada error upload, $file_materi_path tetap menggunakan $file_materi_existing

        // Jika tidak ada error dari proses file, lanjutkan update ke database
        if (empty($message)) {
            // Cek apakah judul modul atau urutan sudah ada untuk praktikum yang sama (kecuali modul ini sendiri)
            $sql_check = "SELECT id FROM modul WHERE praktikum_id = ? AND (judul_modul = ? OR urutan = ?) AND id != ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("isii", $praktikum_id_selected, $judul_modul, $urutan, $modul_id);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $message = "Judul modul atau urutan modul sudah ada untuk mata praktikum ini.";
                // Hapus file baru yang sudah terlanjur diunggah jika ada duplikasi data
                if ($file_materi_path !== $file_materi_existing && $file_materi_path && file_exists('../' . $file_materi_path)) {
                    unlink('../' . $file_materi_path);
                }
            } else {
                // Update data di database
                $sql_update = "UPDATE modul SET praktikum_id = ?, judul_modul = ?, deskripsi = ?, urutan = ?, file_materi = ? WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("issisi", $praktikum_id_selected, $judul_modul, $deskripsi, $urutan, $file_materi_path, $modul_id);

                if ($stmt_update->execute()) {
                    $stmt_update->close();
                    $conn->close();
                    header("Location: modul.php?status=edit_sukses");
                    exit();
                } else {
                    $message = "Terjadi kesalahan saat memperbarui modul: " . $stmt_update->error;
                    // Hapus file baru yang sudah terlanjur diunggah jika update gagal
                    if ($file_materi_path !== $file_materi_existing && $file_materi_path && file_exists('../' . $file_materi_path)) {
                        unlink('../' . $file_materi_path);
                    }
                }
                $stmt_update->close();
            }
            $stmt_check->close();
        }
    }
    // Jika ada error, koneksi ditutup di bagian HTML
}


// LOGIKA UNTUK MENGAMBIL DATA UNTUK FORM (JALANKAN SETELAH PROSES POST)
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $modul_id = trim($_GET['id']);

    $sql_select = "SELECT praktikum_id, judul_modul, deskripsi, urutan, file_materi FROM modul WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $modul_id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();

    if ($result_select->num_rows === 1) {
        $row = $result_select->fetch_assoc();
        // Hanya set variabel ini jika ini bukan POST request yang gagal
        if ($_SERVER["REQUEST_METHOD"] != "POST" || !empty($message)) {
            $praktikum_id_selected = $row['praktikum_id'];
            $judul_modul = $row['judul_modul'];
            $deskripsi = $row['deskripsi'];
            $urutan = $row['urutan'];
            $file_materi_existing = $row['file_materi'];
        }
    } else {
        header("Location: modul.php?status=not_found");
        exit();
    }
    $stmt_select->close();
} else {
    header("Location: modul.php");
    exit();
}

require_once 'templates/header.php';
?>

<div class="container mx-auto p-4">
    <h2 class="text-2xl font-bold mb-4">Edit Modul Praktikum</h2>

    <?php if (!empty($message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $message; ?></span>
        </div>
    <?php endif; ?>

    <form action="edit_modul.php?id=<?php echo htmlspecialchars($modul_id); ?>" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow-md">
        <input type="hidden" name="modul_id" value="<?php echo htmlspecialchars($modul_id); ?>">
        <input type="hidden" name="file_materi_existing" value="<?php echo htmlspecialchars($file_materi_existing); ?>">

        <div class="mb-4">
            <label for="praktikum_id" class="block text-gray-700 text-sm font-bold mb-2">Mata Praktikum:</label>
            <select id="praktikum_id" name="praktikum_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                <option value="">Pilih Mata Praktikum</option>
                <?php foreach ($mata_praktikum_list as $praktikum): ?>
                    <option value="<?php echo $praktikum['id']; ?>" <?php echo ($praktikum_id_selected == $praktikum['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($praktikum['nama_praktikum']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-4">
            <label for="judul_modul" class="block text-gray-700 text-sm font-bold mb-2">Judul Modul:</label>
            <input type="text" id="judul_modul" name="judul_modul" value="<?php echo htmlspecialchars($judul_modul); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>
        <div class="mb-4">
            <label for="deskripsi" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi:</label>
            <textarea id="deskripsi" name="deskripsi" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($deskripsi); ?></textarea>
        </div>
        <div class="mb-4">
            <label for="urutan" class="block text-gray-700 text-sm font-bold mb-2">Urutan Modul:</label>
            <input type="number" id="urutan" name="urutan" value="<?php echo htmlspecialchars($urutan); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required min="1">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">File Materi Saat Ini:</label>
            <?php if (!empty($file_materi_existing)): ?>
                <p class="text-gray-600 mb-2">
                    <a href="../<?php echo htmlspecialchars($file_materi_existing); ?>" target="_blank" class="text-blue-500 hover:underline">
                        <?php echo basename($file_materi_existing); ?>
                    </a>
                </p>
            <?php else: ?>
                <p class="text-gray-600 mb-2">Belum ada file materi.</p>
            <?php endif; ?>

            <label for="file_materi_baru" class="block text-gray-700 text-sm font-bold mb-2 mt-4">Unggah File Materi Baru (PDF/DOCX, maks 5MB):</label>
            <input type="file" id="file_materi_baru" name="file_materi_baru" accept=".pdf,.docx" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            <p class="text-gray-500 text-xs italic mt-1">Biarkan kosong jika tidak ingin mengubah file materi.</p>
        </div>
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Update Modul
            </button>
            <a href="modul.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                Kembali
            </a>
        </div>
    </form>
</div>

<?php
require_once 'templates/footer.php';
if ($conn->ping()) {
    $conn->close();
}
?>