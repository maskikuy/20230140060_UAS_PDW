<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config.php';

// Inisialisasi variabel
$pageTitle = 'Kumpul Laporan';
$activePage = 'my_courses'; // Tetap aktifkan menu "Praktikum Saya"
$message = '';
$modul_id = null;
$judul_modul = '';
$nama_praktikum = '';
$user_id = $_SESSION['user_id'] ?? null;

// Cek jika pengguna belum login atau bukan mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit();
}

// Ambil modul_id dari URL
if (isset($_GET['modul_id']) && !empty(trim($_GET['modul_id']))) {
    $modul_id = trim($_GET['modul_id']);

    // Ambil detail modul dan praktikum terkait
    $sql_modul = "SELECT m.judul_modul, mp.nama_praktikum, mp.id AS praktikum_id
                  FROM modul m
                  JOIN mata_praktikum mp ON m.praktikum_id = mp.id
                  WHERE m.id = ?";
    $stmt_modul = $conn->prepare($sql_modul);
    $stmt_modul->bind_param("i", $modul_id);
    $stmt_modul->execute();
    $result_modul = $stmt_modul->get_result();

    if ($result_modul->num_rows === 1) {
        $row_modul = $result_modul->fetch_assoc();
        $judul_modul = $row_modul['judul_modul'];
        $nama_praktikum = $row_modul['nama_praktikum'];
        $praktikum_id_terkait = $row_modul['praktikum_id'];
    } else {
        header("Location: my_courses.php?status=modul_not_found");
        exit();
    }
    $stmt_modul->close();

    // Cek apakah mahasiswa terdaftar pada praktikum modul ini
    $sql_check_reg = "SELECT id FROM pendaftaran_praktikum WHERE user_id = ? AND praktikum_id = ?";
    $stmt_check_reg = $conn->prepare($sql_check_reg);
    $stmt_check_reg->bind_param("ii", $user_id, $praktikum_id_terkait);
    $stmt_check_reg->execute();
    $stmt_check_reg->store_result();
    if ($stmt_check_reg->num_rows === 0) {
        $stmt_check_reg->close();
        header("Location: my_courses.php?status=not_registered_for_modul_praktikum");
        exit();
    }
    $stmt_check_reg->close();

    // Cek apakah laporan untuk modul ini sudah dikumpulkan oleh mahasiswa ini
    $sql_check_laporan = "SELECT id, file_laporan, status FROM laporan WHERE modul_id = ? AND user_id = ?";
    $stmt_check_laporan = $conn->prepare($sql_check_laporan);
    $stmt_check_laporan->bind_param("ii", $modul_id, $user_id);
    $stmt_check_laporan->execute();
    $result_check_laporan = $stmt_check_laporan->get_result();

    if ($result_check_laporan->num_rows > 0) {
        $laporan_data = $result_check_laporan->fetch_assoc();
        $existing_file = $laporan_data['file_laporan']; // Untuk ditampilkan di form
    }
    $stmt_check_laporan->close();

} else {
    // Jika tidak ada modul_id, redirect kembali
    header("Location: my_courses.php");
    exit();
}

// Proses jika ada pengiriman form (metode POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_modul_id = trim($_POST['modul_id']); // Ambil modul_id dari hidden input
    $user_id = $_SESSION['user_id']; // Ambil user_id dari session

    // Pastikan laporan belum dikumpulkan atau jika ingin update, pastikan belum dinilai
    $sql_check_existing = "SELECT id, status, file_laporan FROM laporan WHERE modul_id = ? AND user_id = ?";
    $stmt_check_existing = $conn->prepare($sql_check_existing);
    $stmt_check_existing->bind_param("ii", $current_modul_id, $user_id);
    $stmt_check_existing->execute();
    $result_existing = $stmt_check_existing->get_result();
    $existing_report = $result_existing->fetch_assoc();
    $stmt_check_existing->close();

    // Jika laporan sudah dinilai, tidak bisa diubah
    if ($existing_report && $existing_report['status'] === 'graded') {
        $message = "Laporan ini sudah dinilai dan tidak bisa diubah.";
    } else {
        $file_laporan_path = null;

        if (isset($_FILES['file_laporan']) && $_FILES['file_laporan']['error'] == 0) {
            $allowed_types = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']; // PDF dan DOCX
            $max_file_size = 10 * 1024 * 1024; // 10 MB (laporan mungkin lebih besar)

            $file_name = $_FILES['file_laporan']['name'];
            $file_tmp_name = $_FILES['file_laporan']['tmp_name'];
            $file_type = $_FILES['file_laporan']['type'];
            $file_size = $_FILES['file_laporan']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (!in_array($file_type, $allowed_types) || !in_array($file_ext, ['pdf', 'docx'])) {
                $message = "Tipe file tidak diizinkan. Hanya PDF dan DOCX yang diperbolehkan.";
            } elseif ($file_size > $max_file_size) {
                $message = "Ukuran file terlalu besar. Maksimal 10MB.";
            } else {
                $upload_dir = '../uploads/laporan_tugas/';
                $new_file_name = uniqid('laporan_') . '_' . $user_id . '_' . $current_modul_id . '.' . $file_ext;
                $destination = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp_name, $destination)) {
                    $file_laporan_path = 'uploads/laporan_tugas/' . $new_file_name; // Simpan path relatif ke database
                } else {
                    $message = "Gagal mengunggah file laporan.";
                }
            }
        } else {
            $message = "Pilih file laporan untuk diunggah.";
        }

        // Jika tidak ada error dari upload file, lanjutkan insert/update ke database
        if (empty($message)) {
            if ($existing_report) {
                // Update laporan yang sudah ada
                // Hapus file lama jika ada dan file baru berhasil diunggah
                if ($existing_report['file_laporan'] && file_exists('../' . $existing_report['file_laporan'])) {
                    unlink('../' . $existing_report['file_laporan']);
                }
                $sql_update = "UPDATE laporan SET file_laporan = ?, tanggal_upload = CURRENT_TIMESTAMP, status = 'submitted', nilai = NULL, feedback = NULL WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("si", $file_laporan_path, $existing_report['id']);

                if ($stmt_update->execute()) {
                    $stmt_update->close();
                    $conn->close();
                    header("Location: detail_praktikum.php?id={$praktikum_id_terkait}&status=laporan_update_sukses");
                    exit();
                } else {
                    $message = "Terjadi kesalahan saat memperbarui laporan: " . $stmt_update->error;
                }
                $stmt_update->close();
            } else {
                // Insert laporan baru
                $sql_insert = "INSERT INTO laporan (modul_id, user_id, file_laporan) VALUES (?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("iis", $current_modul_id, $user_id, $file_laporan_path);

                if ($stmt_insert->execute()) {
                    $stmt_insert->close();
                    $conn->close();
                    header("Location: detail_praktikum.php?id={$praktikum_id_terkait}&status=laporan_submit_sukses");
                    exit();
                } else {
                    $message = "Terjadi kesalahan saat mengumpulkan laporan: " . $stmt_insert->error;
                    // Hapus file yang sudah terlanjur diunggah jika insert gagal
                    if ($file_laporan_path && file_exists('../' . $file_laporan_path)) {
                        unlink('../' . $file_laporan_path);
                    }
                    // Cek jika error karena duplikat entry
                    if ($conn->errno == 1062) {
                        $message = "Anda sudah mengumpulkan laporan untuk modul ini.";
                    }
                }
                $stmt_insert->close();
            }
        }
    }
}

require_once 'templates/header_mahasiswa.php';
?>

<div class="container mx-auto p-4">
    <h2 class="text-2xl font-bold mb-4">Kumpulkan Laporan untuk Modul:</h2>
    <h3 class="text-xl text-blue-700 mb-4"><?php echo htmlspecialchars($nama_praktikum); ?> - <?php echo htmlspecialchars($judul_modul); ?></h3>

    <?php if (!empty($message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $message; ?></span>
        </div>
    <?php endif; ?>

    <form action="submit_report.php?modul_id=<?php echo htmlspecialchars($modul_id); ?>" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow-md">
        <input type="hidden" name="modul_id" value="<?php echo htmlspecialchars($modul_id); ?>">

        <?php if (isset($existing_report)): ?>
            <div class="mb-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative">
                <p>Anda sudah mengumpulkan laporan untuk modul ini.</p>
                <p>Status: <strong><?php echo ucfirst($laporan_data['status']); ?></strong></p>
                <?php if ($laporan_data['file_laporan']): ?>
                    <p>File saat ini: <a href="../<?php echo htmlspecialchars($laporan_data['file_laporan']); ?>" target="_blank" class="underline"><?php echo basename($laporan_data['file_laporan']); ?></a></p>
                <?php endif; ?>
                <?php if ($laporan_data['status'] === 'graded'): ?>
                    <p class="font-bold mt-2">Laporan ini sudah dinilai dan tidak bisa diubah.</p>
                <?php else: ?>
                    <p class="font-bold mt-2">Anda dapat mengunggah ulang laporan untuk memperbarui.</p>
                <?php endif; ?>
            </div>
            <?php if ($laporan_data['status'] === 'graded'): ?>
                <fieldset disabled>
            <?php endif; ?>
        <?php endif; ?>

        <div class="mb-4">
            <label for="file_laporan" class="block text-gray-700 text-sm font-bold mb-2">Unggah File Laporan (PDF/DOCX, maks 10MB):</label>
            <input type="file" id="file_laporan" name="file_laporan" accept=".pdf,.docx" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" <?php echo isset($existing_report) && $existing_report['status'] === 'graded' ? 'disabled' : 'required'; ?>>
            <p class="text-gray-500 text-xs italic mt-1">Unggah ulang laporan jika ada revisi.</p>
        </div>
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" <?php echo isset($existing_report) && $existing_report['status'] === 'graded' ? 'disabled' : ''; ?>>
                <?php echo isset($existing_report) ? 'Perbarui Laporan' : 'Kumpulkan Laporan'; ?>
            </button>
            <a href="detail_praktikum.php?id=<?php echo htmlspecialchars($praktikum_id_terkait); ?>" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                Kembali ke Detail Praktikum
            </a>
        </div>
        <?php if (isset($existing_report) && $existing_report['status'] === 'graded'): ?>
            </fieldset>
        <?php endif; ?>
    </form>
</div>

<?php
require_once 'templates/footer_mahasiswa.php';
if ($conn->ping()) {
    $conn->close();
}
?>