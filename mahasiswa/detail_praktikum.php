<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Detail Praktikum';
$activePage = 'my_courses'; // Tetap aktifkan menu "Praktikum Saya"

require_once '../config.php';
require_once 'templates/header_mahasiswa.php';

// Cek jika pengguna belum login atau bukan mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$praktikum_id = null;
$nama_praktikum = '';
$praktikum_deskripsi = '';
$is_registered = false; // Flag untuk mengecek apakah mahasiswa terdaftar di praktikum ini

// Ambil ID praktikum dari URL
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $praktikum_id = trim($_GET['id']);

    // 1. Cek apakah mata praktikum ini ada
    $sql_praktikum = "SELECT nama_praktikum, deskripsi FROM mata_praktikum WHERE id = ?";
    $stmt_praktikum = $conn->prepare($sql_praktikum);
    $stmt_praktikum->bind_param("i", $praktikum_id);
    $stmt_praktikum->execute();
    $result_praktikum = $stmt_praktikum->get_result();

    if ($result_praktikum->num_rows === 1) {
        $row_praktikum = $result_praktikum->fetch_assoc();
        $nama_praktikum = $row_praktikum['nama_praktikum'];
        $praktikum_deskripsi = $row_praktikum['deskripsi'];
    } else {
        header("Location: my_courses.php?status=praktikum_not_found");
        exit();
    }
    $stmt_praktikum->close();

    // 2. Cek apakah mahasiswa yang sedang login terdaftar pada praktikum ini
    $sql_check_registration = "SELECT id FROM pendaftaran_praktikum WHERE user_id = ? AND praktikum_id = ?";
    $stmt_check_registration = $conn->prepare($sql_check_registration);
    $stmt_check_registration->bind_param("ii", $user_id, $praktikum_id);
    $stmt_check_registration->execute();
    $stmt_check_registration->store_result();

    if ($stmt_check_registration->num_rows === 1) {
        $is_registered = true;
    }
    $stmt_check_registration->close();

    // Jika mahasiswa tidak terdaftar, arahkan kembali ke halaman praktikum saya atau halaman cari praktikum
    if (!$is_registered) {
        header("Location: my_courses.php?status=not_registered_for_course");
        exit();
    }

    // 3. Ambil daftar modul untuk praktikum ini
    $sql_modul = "SELECT id, judul_modul, deskripsi, file_materi, urutan FROM modul WHERE praktikum_id = ? ORDER BY urutan ASC";
    $stmt_modul = $conn->prepare($sql_modul);
    $stmt_modul->bind_param("i", $praktikum_id);
    $stmt_modul->execute();
    $result_modul = $stmt_modul->get_result();

} else {
    // Jika tidak ada praktikum_id, redirect kembali
    header("Location: my_courses.php");
    exit();
}
?>

<div class="container mx-auto p-4">
    <h2 class="text-3xl font-bold text-gray-800 mb-4">Detail Praktikum: <?php echo htmlspecialchars($nama_praktikum); ?></h2>
    <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($praktikum_deskripsi); ?></p>
    <?php
    // Tambahkan blok ini untuk menampilkan pesan status
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        $message = '';
        $alert_class = 'bg-blue-100 border-blue-400 text-blue-700'; // Default info

        if ($status == 'laporan_submit_sukses') {
            $message = 'Laporan berhasil dikumpulkan!';
            $alert_class = 'bg-green-100 border-green-400 text-green-700';
        } elseif ($status == 'laporan_update_sukses') {
            $message = 'Laporan berhasil diperbarui!';
            $alert_class = 'bg-green-100 border-green-400 text-green-700';
        } elseif ($status == 'modul_not_found') {
            $message = 'Modul tidak ditemukan!';
            $alert_class = 'bg-red-100 border-red-400 text-red-700';
        } elseif ($status == 'not_registered_for_modul_praktikum') {
            $message = 'Anda tidak terdaftar di praktikum yang terkait dengan modul ini.';
            $alert_class = 'bg-yellow-100 border-yellow-400 text-yellow-700';
        }

        if (!empty($message)) {
            echo '<div class="' . $alert_class . ' px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">' . $message . '</span></div>';
        }
    }
    ?>

    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h3 class="text-2xl font-bold text-gray-800 mb-4">Daftar Modul</h3>
        <?php if ($result_modul->num_rows > 0): ?>
            <div class="space-y-6">
                <?php while($modul = $result_modul->fetch_assoc()): ?>
                    <div class="border-b pb-4 last:border-b-0">
                        <h4 class="text-xl font-semibold text-blue-700 mb-2">Modul <?php echo htmlspecialchars($modul['urutan']); ?>: <?php echo htmlspecialchars($modul['judul_modul']); ?></h4>
                        <p class="text-gray-700 mb-3"><?php echo htmlspecialchars($modul['deskripsi']); ?></p>

                        <?php if (!empty($modul['file_materi'])): ?>
                            <a href="../<?php echo htmlspecialchars($modul['file_materi']); ?>" target="_blank" class="inline-flex items-center text-green-600 hover:text-green-800 font-medium text-sm">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Unduh Materi
                            </a>
                        <?php else: ?>
                            <p class="text-gray-500 text-sm">Materi belum tersedia.</p>
                        <?php endif; ?>

                        <div class="mt-3 border-t pt-3">
                            <h5 class="font-semibold text-gray-800">Laporan & Nilai:</h5>
                            <p class="text-gray-600 text-sm">
                                <a href="submit_report.php?modul_id=<?php echo $modul['id']; ?>" class="text-indigo-600 hover:underline">Kumpulkan Laporan</a> |
                                <a href="view_grade.php?modul_id=<?php echo $modul['id']; ?>" class="text-indigo-600 hover:underline">Lihat Nilai</a>
                                <span class="text-gray-500">(Fitur akan dikembangkan)</span>
                            </p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-700">Belum ada modul yang ditambahkan untuk praktikum ini.</p>
        <?php endif; ?>
    </div>

    <div class="text-center mt-8">
        <a href="my_courses.php" class="inline-block bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
            Kembali ke Praktikum Saya
        </a>
    </div>
</div>

<?php
$stmt_modul->close();
require_once 'templates/footer_mahasiswa.php';
$conn->close();
?>