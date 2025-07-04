<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config.php';

// Inisialisasi variabel
$pageTitle = 'Edit Pengguna';
$activePage = 'users';
$message = '';
$user_id_to_edit = null;
$nama_user = '';
$email_user = '';
$role_user = '';

// Cek jika pengguna belum login atau bukan asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    header("Location: ../login.php");
    exit();
}

// Proses jika ada pengiriman form (metode POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id_to_edit = trim($_POST['user_id']);
    $nama_baru = trim($_POST['nama']);
    $email_baru = trim($_POST['email']);
    $password_baru = trim($_POST['password']); // Password bisa kosong jika tidak diubah
    $role_baru = trim($_POST['role']);

    // Validasi sederhana
    if (empty($nama_baru) || empty($email_baru) || empty($role_baru)) {
        $message = "Nama, Email, dan Peran tidak boleh kosong!";
    } elseif (!filter_var($email_baru, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid!";
    } elseif (!in_array($role_baru, ['mahasiswa', 'asisten'])) {
        $message = "Peran tidak valid!";
    } else {
        // Cek apakah email baru sudah terdaftar untuk pengguna lain
        $sql_check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param("si", $email_baru, $user_id_to_edit);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();

        if ($stmt_check_email->num_rows > 0) {
            $message = "Email sudah terdaftar untuk pengguna lain. Silakan gunakan email lain.";
        } else {
            // Bangun query update
            $sql_update = "UPDATE users SET nama = ?, email = ?, role = ?";
            $types = "sss";
            $params = [$nama_baru, $email_baru, $role_baru];

            // Jika password diisi, update juga passwordnya
            if (!empty($password_baru)) {
                $hashed_password = password_hash($password_baru, PASSWORD_BCRYPT);
                $sql_update .= ", password = ?";
                $types .= "s";
                $params[] = $hashed_password;
            }

            $sql_update .= " WHERE id = ?";
            $types .= "i";
            $params[] = $user_id_to_edit;

            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param($types, ...$params);

            if ($stmt_update->execute()) {
                $stmt_update->close();
                $conn->close();
                header("Location: users.php?status=edit_sukses");
                exit();
            } else {
                $message = "Terjadi kesalahan saat memperbarui pengguna: " . $stmt_update->error;
            }
            $stmt_update->close();
        }
        $stmt_check_email->close();
    }
    // Set variabel form agar tetap menampilkan nilai yang baru dimasukkan jika ada error
    $nama_user = $nama_baru;
    $email_user = $email_baru;
    $role_user = $role_baru;
}

// LOGIKA UNTUK MENGAMBIL DATA UNTUK FORM (JALANKAN SETELAH PROSES POST)
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $user_id_to_edit = trim($_GET['id']);

    $sql_select = "SELECT nama, email, role FROM users WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $user_id_to_edit);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();

    if ($result_select->num_rows === 1) {
        $row = $result_select->fetch_assoc();
        // Hanya set variabel ini jika ini bukan POST request yang gagal
        if ($_SERVER["REQUEST_METHOD"] != "POST" || !empty($message)) {
            $nama_user = $row['nama'];
            $email_user = $row['email'];
            $role_user = $row['role'];
        }
    } else {
        header("Location: users.php?status=not_found");
        exit();
    }
    $stmt_select->close();
} else {
    header("Location: users.php");
    exit();
}

require_once 'templates/header.php';
?>

<div class="container mx-auto p-4">
    <h2 class="text-2xl font-bold mb-4">Edit Pengguna</h2>

    <?php if (!empty($message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $message; ?></span>
        </div>
    <?php endif; ?>

    <form action="edit_user.php?id=<?php echo htmlspecialchars($user_id_to_edit); ?>" method="POST" class="bg-white p-6 rounded-lg shadow-md">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id_to_edit); ?>">
        <div class="mb-4">
            <label for="nama" class="block text-gray-700 text-sm font-bold mb-2">Nama Lengkap:</label>
            <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($nama_user); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>
        <div class="mb-4">
            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email_user); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>
        <div class="mb-4">
            <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password (kosongkan jika tidak diubah):</label>
            <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>
        <div class="mb-4">
            <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Peran:</label>
            <select id="role" name="role" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                <option value="mahasiswa" <?php echo ($role_user == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                <option value="asisten" <?php echo ($role_user == 'asisten') ? 'selected' : ''; ?>>Asisten</option>
            </select>
        </div>
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Update Pengguna
            </button>
            <a href="users.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
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