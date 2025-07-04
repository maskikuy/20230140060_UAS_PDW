<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Manajemen Akun Pengguna';
$activePage = 'users'; // Variabel untuk menandai menu aktif

require_once '../config.php';
require_once 'templates/header.php';

// Cek jika pengguna belum login atau bukan asisten (hanya admin yang bisa mengakses)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

// Ambil data pengguna dari database
$sql = "SELECT id, nama, email, role, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);

?>

<div class="container mx-auto p-4">
    <h2 class="text-2xl font-bold mb-4">Manajemen Akun Pengguna</h2>

    <?php
    // Pesan status dari operasi CRUD
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        if ($status == 'tambah_sukses') {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">Pengguna berhasil ditambahkan!</span></div>';
        } elseif ($status == 'edit_sukses') {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">Pengguna berhasil diperbarui!</span></div>';
        } elseif ($status == 'hapus_sukses') {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">Pengguna berhasil dihapus!</span></div>';
        } elseif ($status == 'not_found') {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">Pengguna tidak ditemukan!</span></div>';
        } elseif ($status == 'self_delete_error') {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">Anda tidak bisa menghapus akun Anda sendiri!</span></div>';
        } elseif ($status == 'hapus_gagal') {
            $error_message = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : 'Terjadi kesalahan saat menghapus pengguna.';
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">' . $error_message . '</span></div>';
        }
    }
    ?>

    <a href="tambah_user.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mb-4 inline-block">Tambah Pengguna Baru</a>

    <?php if ($result->num_rows > 0): ?>
        <div class="overflow-x-auto bg-white p-6 rounded-lg shadow-md">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dibuat Pada</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $row['id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['nama']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['email']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars(ucfirst($row['role'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                                <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-2 rounded text-xs">Edit</a>
                                <?php if ($row['id'] != $_SESSION['user_id']): // Jangan biarkan user menghapus dirinya sendiri ?>
                                    <a href="hapus_user.php?id=<?php echo $row['id']; ?>" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded text-xs" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?');">Hapus</a>
                                <?php else: ?>
                                    <span class="bg-gray-300 text-gray-600 font-bold py-1 px-2 rounded text-xs opacity-50 cursor-not-allowed">Hapus</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="mt-4 text-gray-700 bg-white p-6 rounded-lg shadow-md">Belum ada pengguna terdaftar.</p>
    <?php endif; ?>
</div>

<?php
$result->close();
require_once 'templates/footer.php';
$conn->close();
?>