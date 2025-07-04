<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Beri Nilai Laporan';
$activePage = 'laporan';

require_once '../config.php';
require_once 'templates/header.php';

// Cek jika pengguna belum login atau bukan asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$laporan_id = null;
$nilai_laporan = '';
$feedback_laporan = '';
$file_laporan_mahasiswa = '';
$nama_mahasiswa = '';
$judul_modul = '';
$nama_praktikum = '';
$email_mahasiswa = '';
$tanggal_upload = '';

// Proses jika ada pengiriman form (metode POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_laporan_id = trim($_POST['laporan_id']);
    $nilai_input = trim($_POST['nilai']);
    $feedback_input = trim($_POST['feedback']);

    // Validasi nilai
    if (!is_numeric($nilai_input) || $nilai_input < 0 || $nilai_input > 100) {
        $message = "Nilai harus berupa angka antara 0-100.";
    } else {
        // Update nilai dan feedback di database
        $sql_update = "UPDATE laporan SET nilai = ?, feedback = ?, status = 'graded' WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("isi", $nilai_input, $feedback_input, $current_laporan_id);

        if ($stmt_update->execute()) {
            $stmt_update->close();
            $conn->close();
            header("Location: laporan.php?status=nilai_sukses");
            exit();
        } else {
            $message = "Terjadi kesalahan saat memperbarui nilai: " . $stmt_update->error;
            $stmt_update->close();
        }
    }
    // Update nilai variabel form agar tetap menampilkan nilai yang baru dimasukkan jika ada error
    $nilai_laporan = $nilai_input;
    $feedback_laporan = $feedback_input;
}

// Ambil ID laporan dari URL dan data laporan untuk ditampilkan
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $laporan_id = trim($_GET['id']);

    $sql_select = "SELECT
                        l.file_laporan, l.tanggal_upload, l.nilai, l.feedback, l.status,
                        u.nama AS nama_mhs, u.email AS email_mhs,
                        m.judul_modul, mp.nama_praktikum
                   FROM
                        laporan l
                   JOIN
                        users u ON l.user_id = u.id
                   JOIN
                        modul m ON l.modul_id = m.id
                   JOIN
                        mata_praktikum mp ON m.praktikum_id = mp.id
                   WHERE l.id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $laporan_id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();

    if ($result_select->num_rows === 1) {
        $row = $result_select->fetch_assoc();
        $file_laporan_mahasiswa = $row['file_laporan'];
        $tanggal_upload = date('d M Y H:i', strtotime($row['tanggal_upload']));
        $nama_mahasiswa = $row['nama_mhs'];
        $email_mahasiswa = $row['email_mhs'];
        $judul_modul = $row['judul_modul'];
        $nama_praktikum = $row['nama_praktikum'];
        // Jika ini bukan POST request yang gagal, gunakan nilai dari DB
        if ($_SERVER["REQUEST_METHOD"] != "POST" || !empty($message)) {
            $nilai_laporan = $row['nilai'];
            $feedback_laporan = $row['feedback'];
        }
    } else {
        header("Location: laporan.php?status=laporan_not_found");
        exit();
    }
    $stmt_select->close();
} else {
    header("Location: laporan.php");
    exit();
}
?>

<div class="container mx-auto p-4">
    <h2 class="text-2xl font-bold mb-4">Beri Nilai Laporan</h2>

    <?php if (!empty($message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $message; ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Detail Laporan</h3>
        <p class="mb-2"><strong>Mahasiswa:</strong> <?php echo htmlspecialchars($nama_mahasiswa); ?> (<?php echo htmlspecialchars($email_mahasiswa); ?>)</p>
        <p class="mb-2"><strong>Mata Praktikum:</strong> <?php echo htmlspecialchars($nama_praktikum); ?></p>
        <p class="mb-2"><strong>Modul:</strong> <?php echo htmlspecialchars($judul_modul); ?></p>
        <p class="mb-2"><strong>Tanggal Upload:</strong> <?php echo htmlspecialchars($tanggal_upload); ?></p>
        <p class="mb-2">
            <strong>File Laporan:</strong>
            <?php if (!empty($file_laporan_mahasiswa)): ?>
                <a href="../<?php echo htmlspecialchars($file_laporan_mahasiswa); ?>" target="_blank" class="text-blue-500 hover:underline">
                    Unduh Laporan (<?php echo basename($file_laporan_mahasiswa); ?>)
                </a>
            <?php else: ?>
                Tidak ada file.
            <?php endif; ?>
        </p>
    </div>

    <form action="nilai_laporan.php?id=<?php echo htmlspecialchars($laporan_id); ?>" method="POST" class="bg-white p-6 rounded-lg shadow-md">
        <input type="hidden" name="laporan_id" value="<?php echo htmlspecialchars($laporan_id); ?>">
        <div class="mb-4">
            <label for="nilai" class="block text-gray-700 text-sm font-bold mb-2">Nilai (0-100):</label>
            <input type="number" id="nilai" name="nilai" value="<?php echo htmlspecialchars($nilai_laporan); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" min="0" max="100" required>
        </div>
        <div class="mb-4">
            <label for="feedback" class="block text-gray-700 text-sm font-bold mb-2">Feedback:</label>
            <textarea id="feedback" name="feedback" rows="5" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($feedback_laporan); ?></textarea>
        </div>
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Simpan Nilai
            </button>
            <a href="laporan.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                Kembali ke Daftar Laporan
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