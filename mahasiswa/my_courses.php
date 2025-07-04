<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Praktikum Saya';
$activePage = 'my_courses'; // Variabel untuk menandai menu aktif

require_once '../config.php';
require_once 'templates/header_mahasiswa.php';

// Cek jika pengguna belum login atau bukan mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data praktikum yang diikuti oleh mahasiswa dari database
$sql = "SELECT mp.id, mp.nama_praktikum, mp.deskripsi, pp.tanggal_daftar
        FROM pendaftaran_praktikum pp
        JOIN mata_praktikum mp ON pp.praktikum_id = mp.id
        WHERE pp.user_id = ?
        ORDER BY pp.tanggal_daftar DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<div class="container mx-auto p-4">
    <h2 class="text-2xl font-bold mb-4">Praktikum yang Saya Ikuti</h2>

    <?php if ($result->num_rows > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($row['nama_praktikum']); ?></h3>
                    <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($row['deskripsi']); ?></p>
                    <p class="text-gray-500 text-xs">Terdaftar pada: <?php echo date('d M Y', strtotime($row['tanggal_daftar'])); ?></p>
                    <a href="detail_praktikum.php?id=<?php echo $row['id']; ?>" class="mt-4 inline-block bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm">
                        Lihat Detail
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p class="mt-4 text-gray-700">Anda belum mendaftar pada praktikum apapun.</p>
        <a href="courses.php" class="inline-block mt-4 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
            Cari Praktikum untuk Didaftar
        </a>
    <?php endif; ?>
</div>

<?php
$stmt->close();
require_once 'templates/footer_mahasiswa.php';
$conn->close();
?>