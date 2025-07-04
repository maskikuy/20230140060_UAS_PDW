<?php
// Include file konfigurasi database dan header asisten
include_once '../config.php';
include_once 'templates/header.php';

// Pastikan hanya asisten yang bisa mengakses halaman ini
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

// Ambil data mata praktikum dari database
$sql = "SELECT * FROM mata_praktikum ORDER BY nama_praktikum ASC";
$result = $conn->query($sql);

?>

<div class="container mx-auto p-4">
    <h2 class="text-2xl font-bold mb-4">Manajemen Mata Praktikum</h2>

    <a href="tambah_mata_praktikum.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mb-4 inline-block">Tambah Mata Praktikum Baru</a>

    <?php
    // Tambahkan blok ini untuk menampilkan pesan status
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        if ($status == 'tambah_sukses') {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">Mata praktikum berhasil ditambahkan!</span></div>';
        } elseif ($status == 'edit_sukses') {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">Mata praktikum berhasil diperbarui!</span></div>';
        } elseif ($status == 'hapus_sukses') {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">Mata praktikum berhasil dihapus!</span></div>';
        } elseif ($status == 'not_found') {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">Mata praktikum tidak ditemukan!</span></div>';
        } elseif ($status == 'hapus_gagal') {
            $error_message = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : 'Terjadi kesalahan saat menghapus mata praktikum.';
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">' . $error_message . '</span></div>';
        }
    }
    ?>


    <?php if ($result->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-300">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b">ID</th>
                        <th class="py-2 px-4 border-b">Nama Praktikum</th>
                        <th class="py-2 px-4 border-b">Deskripsi</th>
                        <th class="py-2 px-4 border-b">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="py-2 px-4 border-b text-center"><?php echo $row['id']; ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($row['nama_praktikum']); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($row['deskripsi']); ?></td>
                            <td class="py-2 px-4 border-b text-center">
                                <a href="edit_mata_praktikum.php?id=<?php echo $row['id']; ?>" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-2 rounded text-sm">Edit</a>
                                <a href="hapus_mata_praktikum.php?id=<?php echo $row['id']; ?>" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded text-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus mata praktikum ini?');">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="mt-4">Belum ada mata praktikum yang tersedia.</p>
    <?php endif; ?>
</div>

<?php
// Include footer asisten
include_once 'templates/footer.php';
?>