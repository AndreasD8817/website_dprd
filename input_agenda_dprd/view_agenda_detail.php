<?php
// Pastikan koneksi database tersedia
include '../config/koneksi.php'; // Path relatif dari input_agenda_dprd/ ke config/

// Ambil ID Agenda dari URL
$agenda_id = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : null;

// Jika tidak ada ID agenda di URL, redirect kembali ke daftar agenda
if (!$agenda_id) {
    header("Location: daftar_agenda.php?status=invalid_id");
    exit();
}

// Set judul halaman default
$page_title = "Detail Agenda Rapat - DPRD Kota Surabaya";
$agenda_detail = null; // Variabel untuk menyimpan data agenda

// Query untuk mengambil detail agenda
$stmt_detail = $conn->prepare("
    SELECT
        perihal,
        nomor_undangan,
        kategori,
        tanggal,
        jam
    FROM
        agenda_rapat
    WHERE
        id = ?
");
$stmt_detail->bind_param("i", $agenda_id);
$stmt_detail->execute();
$result_detail = $stmt_detail->get_result();

if ($result_detail->num_rows > 0) {
    $agenda_detail = $result_detail->fetch_assoc();
    // Perbarui judul halaman dengan data agenda
    $page_title = "Detail Agenda " . htmlspecialchars($agenda_detail['perihal']) . " - DPRD Kota Surabaya";
} else {
    // Jika agenda tidak ditemukan
    header("Location: daftar_agenda.php?status=agenda_not_found");
    exit();
}
$stmt_detail->close();

// Tutup koneksi database setelah semua query selesai
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// Sertakan header global website
include '../includes/header.php';
?>

<style>
    /* Styling umum container */
    .detail-container {
        background-color: var(--card-bg);
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 8px 25px var(--shadow-color);
        width: 100%;
        max-width: 700px; /* Lebar yang sesuai untuk detail */
        margin: 40px auto;
        box-sizing: border-box;
    }

    .detail-container h2 {
        text-align: center;
        color: var(--primary-blue);
        margin-bottom: 30px;
        font-size: 2.2em;
        font-weight: 700;
    }

    .detail-item {
        margin-bottom: 15px;
        padding: 10px;
        background-color: var(--light-blue);
        border: 1px solid var(--border-color);
        border-radius: 8px;
    }

    .detail-item strong {
        color: var(--dark-blue);
        display: block; /* Agar label tebal berada di baris sendiri */
        margin-bottom: 5px;
        font-size: 1.1em;
    }

    .back-link-btn {
        display: inline-block;
        margin-top: 30px;
        background-color: var(--primary-blue);
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 500;
        transition: background-color 0.3s ease;
    }
    .back-link-btn:hover {
        background-color: var(--dark-blue);
    }
</style>

<div class="detail-container">
    <h2>Detail Agenda Rapat</h2>

    <?php if ($agenda_detail): // Pastikan data agenda ditemukan ?>
        <div class="detail-item">
            <strong>Perihal Agenda:</strong> <?php echo htmlspecialchars($agenda_detail['perihal']); ?>
        </div>
        <div class="detail-item">
            <strong>Nomor Agenda:</strong> <?php echo htmlspecialchars($agenda_detail['nomor_undangan']); ?>
        </div>
        <div class="detail-item">
            <strong>Kategori:</strong> <?php echo htmlspecialchars($agenda_detail['kategori']); ?>
        </div>
        <div class="detail-item">
            <strong>Tanggal:</strong> <?php echo htmlspecialchars(date('d F Y', strtotime($agenda_detail['tanggal']))); ?>
        </div>
        <div class="detail-item">
            <strong>Jam:</strong> <?php echo htmlspecialchars(date('H:i', strtotime($agenda_detail['jam']))); ?>
        </div>
        <a href="daftar_agenda.php" class="back-link-btn">Kembali ke Daftar Agenda</a>
    <?php else: ?>
        <p style="text-align: center;">Agenda tidak ditemukan atau tidak valid.</p>
        <a href="daftar_agenda.php" class="back-link-btn">Kembali ke Daftar Agenda</a>
    <?php endif; ?>
</div>

<?php
include '../includes/footer.php';
?>