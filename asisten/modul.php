<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Manajemen Modul';
$activePage = 'modul'; // Variabel untuk menandai menu aktif

require_once '../config.php';
require_once 'templates/header.php';

// Cek jika pengguna belum login atau bukan asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

// Ambil data modul dari database, beserta nama praktikumnya
$sql = "SELECT m.id, m.judul_modul, m.deskripsi, m.file_materi, m.urutan, mp.nama_praktikum
        FROM modul m
        JOIN mata_praktikum mp ON m.praktikum_id = mp.id
        ORDER BY mp.nama_praktikum ASC, m.urutan ASC";
$result = $conn->query($sql);

?>

<div class="container mx-auto p-4">
    <h2 class="text-2xl font-bold mb-4">Manajemen Modul Praktikum</h2>

    <?php
    // Tambahkan blok ini untuk menampilkan pesan status (mirip dengan mata_praktikum.php)
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        if ($status == 'tambah_sukses') {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">Modul berhasil ditambahkan!</span></div>';
        } elseif ($status == 'edit_sukses') {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">Modul berhasil diperbarui!</span></div>';
        } elseif ($status == 'hapus_sukses') {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">Modul berhasil dihapus!</span></div>';
        } elseif ($status == 'not_found') {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">Modul tidak ditemukan!</span></div>';
        } elseif ($status == 'hapus_gagal') {
            $error_message = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : 'Terjadi kesalahan saat menghapus modul.';
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">' . $error_message . '</span></div>';
        }
    }
    ?>

    <a href="tambah_modul.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mb-4 inline-block">Tambah Modul Baru</a>

    <?php if ($result->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-300">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b">ID</th>
                        <th class="py-2 px-4 border-b">Mata Praktikum</th>
                        <th class="py-2 px-4 border-b">Judul Modul</th>
                        <th class="py-2 px-4 border-b">Urutan</th>
                        <th class="py-2 px-4 border-b">Materi</th>
                        <th class="py-2 px-4 border-b">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="py-2 px-4 border-b text-center"><?php echo $row['id']; ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($row['nama_praktikum']); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($row['judul_modul']); ?></td>
                            <td class="py-2 px-4 border-b text-center"><?php echo htmlspecialchars($row['urutan']); ?></td>
                            <td class="py-2 px-4 border-b text-center">
                                <?php if (!empty($row['file_materi'])): ?>
                                    <a href="../<?php echo htmlspecialchars($row['file_materi']); ?>" target="_blank" class="text-blue-500 hover:underline">Lihat Materi</a>
                                <?php else: ?>
                                    Tidak ada
                                <?php endif; ?>
                            </td>
                            <td class="py-2 px-4 border-b text-center">
                                <a href="edit_modul.php?id=<?php echo $row['id']; ?>" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-2 rounded text-sm">Edit</a>
                                <a href="hapus_modul.php?id=<?php echo $row['id']; ?>" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded text-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus modul ini?');">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="mt-4">Belum ada modul yang tersedia.</p>
    <?php endif; ?>
</div>

<?php
$conn->close();
require_once 'templates/footer.php';
?>