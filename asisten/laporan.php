<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Laporan Masuk';
$activePage = 'laporan';

require_once '../config.php';
require_once 'templates/header.php';

// Cek jika pengguna belum login atau bukan asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

$message = '';
// Inisialisasi filter
$filter_modul_id = $_GET['modul_id'] ?? '';
$filter_user_id = $_GET['user_id'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Ambil daftar modul untuk filter dropdown
$sql_modul_filter = "SELECT id, judul_modul FROM modul ORDER BY judul_modul ASC";
$result_modul_filter = $conn->query($sql_modul_filter);
$modul_list = [];
while ($row = $result_modul_filter->fetch_assoc()) {
    $modul_list[] = $row;
}

// Ambil daftar mahasiswa untuk filter dropdown
$sql_users_filter = "SELECT id, nama FROM users WHERE role = 'mahasiswa' ORDER BY nama ASC";
$result_users_filter = $conn->query($sql_users_filter);
$users_list = [];
while ($row = $result_users_filter->fetch_assoc()) {
    $users_list[] = $row;
}


// Bangun query SQL dasar
$sql = "SELECT
            l.id AS laporan_id,
            l.file_laporan,
            l.tanggal_upload,
            l.nilai,
            l.feedback,
            l.status,
            u.nama AS nama_mahasiswa,
            u.email AS email_mahasiswa,
            m.judul_modul,
            mp.nama_praktikum
        FROM
            laporan l
        JOIN
            users u ON l.user_id = u.id
        JOIN
            modul m ON l.modul_id = m.id
        JOIN
            mata_praktikum mp ON m.praktikum_id = mp.id
        WHERE 1=1"; // Kondisi awal true agar mudah menambahkan AND

$params = [];
$types = '';

// Tambahkan filter jika ada
if (!empty($filter_modul_id)) {
    $sql .= " AND l.modul_id = ?";
    $types .= 'i';
    $params[] = $filter_modul_id;
}
if (!empty($filter_user_id)) {
    $sql .= " AND l.user_id = ?";
    $types .= 'i';
    $params[] = $filter_user_id;
}
if (!empty($filter_status)) {
    $sql .= " AND l.status = ?";
    $types .= 's';
    $params[] = $filter_status;
}

$sql .= " ORDER BY l.tanggal_upload DESC"; // Urutkan laporan terbaru di atas

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

?>

<div class="container mx-auto p-4">
    <h2 class="text-2xl font-bold mb-4">Laporan Masuk</h2>

    <?php
    // Pesan status dari operasi pemberian nilai
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        if ($status == 'nilai_sukses') {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">Nilai berhasil diberikan/diperbarui!</span></div>';
        } elseif ($status == 'nilai_gagal') {
            $error_message = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : 'Terjadi kesalahan saat memberi nilai.';
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">' . $error_message . '</span></div>';
        }
    }
    ?>

    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Filter Laporan</h3>
        <form action="laporan.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="modul_id" class="block text-gray-700 text-sm font-bold mb-2">Modul:</label>
                <select id="modul_id" name="modul_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">Semua Modul</option>
                    <?php foreach ($modul_list as $modul): ?>
                        <option value="<?php echo $modul['id']; ?>" <?php echo ($filter_modul_id == $modul['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($modul['judul_modul']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="user_id" class="block text-gray-700 text-sm font-bold mb-2">Mahasiswa:</label>
                <select id="user_id" name="user_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">Semua Mahasiswa</option>
                    <?php foreach ($users_list as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo ($filter_user_id == $user['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['nama']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="status" class="block text-gray-700 text-sm font-bold mb-2">Status:</label>
                <select id="status" name="status" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">Semua Status</option>
                    <option value="submitted" <?php echo ($filter_status == 'submitted') ? 'selected' : ''; ?>>Submitted</option>
                    <option value="graded" <?php echo ($filter_status == 'graded') ? 'selected' : ''; ?>>Graded</option>
                </select>
            </div>
            <div class="col-span-full mt-4 flex justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Filter
                </button>
                <a href="laporan.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded ml-2">Reset Filter</a>
            </div>
        </form>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <div class="overflow-x-auto bg-white p-6 rounded-lg shadow-md">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Laporan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mahasiswa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mata Praktikum</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modul</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File Laporan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Upload</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nilai</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $row['laporan_id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['nama_mahasiswa']); ?> <br><span class="text-xs text-gray-500"><?php echo htmlspecialchars($row['email_mahasiswa']); ?></span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['nama_praktikum']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['judul_modul']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php if (!empty($row['file_laporan'])): ?>
                                    <a href="../<?php echo htmlspecialchars($row['file_laporan']); ?>" target="_blank" class="text-blue-500 hover:underline">Unduh</a>
                                <?php else: ?>
                                    Tidak ada
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('d M Y H:i', strtotime($row['tanggal_upload'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo ($row['nilai'] !== null) ? htmlspecialchars($row['nilai']) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php
                                    $status_color = '';
                                    if ($row['status'] == 'submitted') {
                                        $status_color = 'bg-yellow-100 text-yellow-800';
                                    } elseif ($row['status'] == 'graded') {
                                        $status_color = 'bg-green-100 text-green-800';
                                    }
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_color; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="nilai_laporan.php?id=<?php echo $row['laporan_id']; ?>" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-1 px-2 rounded text-xs">Beri Nilai</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="mt-4 text-gray-700 bg-white p-6 rounded-lg shadow-md">Belum ada laporan yang dikumpulkan.</p>
    <?php endif; ?>
</div>

<?php
$stmt->close();
require_once 'templates/footer.php';
$conn->close();
?>