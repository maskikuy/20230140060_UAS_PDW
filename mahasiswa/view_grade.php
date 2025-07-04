<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ubah Judul Halaman untuk Mahasiswa
$pageTitle = 'Lihat Nilai Laporan'; //
// Ubah Active Page agar sesuai dengan navigasi mahasiswa
$activePage = 'my_courses'; //

require_once '../config.php';
// Pastikan memuat header yang benar untuk mahasiswa
require_once 'templates/header_mahasiswa.php'; // Ini sudah Anda perbaiki sebelumnya

// Cek jika pengguna belum login atau bukan mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') { //
    header("Location: ../login.php");
    exit();
}

$message = '';
$modul_id = null; // Ubah ini dari $laporan_id menjadi $modul_id
$nilai_laporan = '';
$feedback_laporan = '';
$file_laporan_mahasiswa = '';
$nama_mahasiswa = $_SESSION['nama'] ?? 'Pengguna'; // Ambil nama dari session mahasiswa
$judul_modul = '';
$nama_praktikum = '';
$email_mahasiswa = $_SESSION['email'] ?? ''; // Jika email disimpan di session
$tanggal_upload = '';
$user_id = $_SESSION['user_id']; // Dapatkan user_id dari session

// Bagian POST request ini **harus dihapus atau dinonaktifkan** karena halaman ini untuk melihat nilai, bukan memberi nilai
// if ($_SERVER["REQUEST_METHOD"] == "POST") {
//     // ... (Kode untuk memberi nilai laporan, ini tidak relevan di sini)
// }

// Ambil ID modul dari URL
if (isset($_GET['modul_id']) && !empty(trim($_GET['modul_id']))) { // Menggunakan modul_id, bukan id laporan
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

    // Ambil data laporan dan nilai untuk modul ini dan user ini
    $sql_select = "SELECT
                        l.file_laporan, l.tanggal_upload, l.nilai, l.feedback, l.status
                   FROM
                        laporan l
                   WHERE l.modul_id = ? AND l.user_id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("ii", $modul_id, $user_id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();

    if ($result_select->num_rows === 1) {
        $row = $result_select->fetch_assoc();
        $file_laporan_mahasiswa = $row['file_laporan'];
        $tanggal_upload = date('d M Y H:i', strtotime($row['tanggal_upload']));
        $nilai_laporan = $row['nilai'];
        $feedback_laporan = $row['feedback'];
        $status_laporan = $row['status']; // Tambahkan ini
    } else {
        // Jika belum ada laporan atau tidak ditemukan
        $message = "Anda belum mengumpulkan laporan untuk modul ini atau laporan tidak ditemukan.";
        $status_laporan = 'not_submitted'; // Status khusus jika belum submit
    }
    $stmt_select->close();

} else {
    // Jika tidak ada modul_id, redirect kembali
    header("Location: my_courses.php?status=no_modul_id_provided");
    exit();
}
?>

<div class="container mx-auto p-4">
    <h2 class="text-2xl font-bold mb-4">Detail Nilai Laporan</h2>
    <h3 class="text-xl text-blue-700 mb-4">Modul: <?php echo htmlspecialchars($judul_modul); ?> (Praktikum: <?php echo htmlspecialchars($nama_praktikum); ?>)</h3>

    <?php if (!empty($message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $message; ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Informasi Laporan Anda</h3>
        <p class="mb-2"><strong>Mahasiswa:</strong> <?php echo htmlspecialchars($nama_mahasiswa); ?> (<?php echo htmlspecialchars($_SESSION['email']); ?>)</p>
        <p class="mb-2">
            <strong>Status Laporan:</strong>
            <?php
            $status_color = '';
            if ($status_laporan == 'submitted') {
                $status_color = 'bg-yellow-100 text-yellow-800';
            } elseif ($status_laporan == 'graded') {
                $status_color = 'bg-green-100 text-green-800';
            } elseif ($status_laporan == 'not_submitted') {
                $status_color = 'bg-gray-100 text-gray-800';
            }
            ?>
            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_color; ?>">
                <?php echo ucfirst($status_laporan); ?>
            </span>
        </p>
        <?php if ($status_laporan != 'not_submitted'): ?>
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
            <p class="mb-2"><strong>Nilai:</strong> <?php echo ($nilai_laporan !== null) ? htmlspecialchars($nilai_laporan) : 'Belum Dinilai'; ?></p>
            <p class="mb-2"><strong>Feedback:</strong> <?php echo (!empty($feedback_laporan)) ? nl2br(htmlspecialchars($feedback_laporan)) : 'Belum ada feedback.'; ?></p>
        <?php else: ?>
            <p class="mt-4 text-gray-700">Anda belum mengumpulkan laporan untuk modul ini. Silakan <a href="submit_report.php?modul_id=<?php echo htmlspecialchars($modul_id); ?>" class="text-blue-500 hover:underline">kumpulkan laporan</a> Anda.</p>
        <?php endif; ?>
    </div>

    <div class="text-center mt-8">
        <a href="detail_praktikum.php?id=<?php echo htmlspecialchars($praktikum_id_terkait); ?>" class="inline-block bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
            Kembali ke Detail Praktikum
        </a>
    </div>
</div>

<?php
require_once 'templates/footer_mahasiswa.php'; //
if ($conn->ping()) {
    $conn->close();
}
?>