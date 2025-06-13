<?php
include '../config/koneksi.php';

$resume_id = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : null;

if (!$resume_id) {
    header("Location: index.php?status=invalid_resume_id");
    exit();
}

$page_title = "Detail Resume Rapat - DPRD Kota Surabaya";
$detail_resume = null;

$stmt_detail = $conn->prepare("
    SELECT
        rr.tanggal_agenda,
        rr.waktu_mulai,
        rr.waktu_selesai,
        rr.tempat,
        rr.kegiatan,
        rr.kesimpulan,
        rr.kategori_rapat,
        ar.nomor_undangan,
        ar.perihal
    FROM
        resume_rapat rr
    LEFT JOIN
        agenda_rapat ar ON rr.id_agenda = ar.id
    WHERE
        rr.id = ?
");
$stmt_detail->bind_param("i", $resume_id);
$stmt_detail->execute();
$result_detail = $stmt_detail->get_result();

if ($result_detail->num_rows > 0) {
    $detail_resume = $result_detail->fetch_assoc();
    $page_title = "Detail Resume " . $detail_resume['kategori_rapat'] . " - " . date('d F Y', strtotime($detail_resume['tanggal_agenda']));
} else {
    header("Location: index.php?status=resume_not_found");
    exit();
}
$stmt_detail->close();

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

include '../includes/header.php';
?>

<style>
    .detail-container {
        background-color: var(--card-bg);
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 8px 25px var(--shadow-color);
        width: 100%;
        max-width: 900px;
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
        border-radius: 8px;
        border: 1px solid var(--border-color);
    }
    .detail-item strong {
        color: var(--dark-blue);
        display: block;
        margin-bottom: 5px;
        font-size: 1.1em;
    }
    .kesimpulan-content {
        background-color: #f8f8f8;
        padding: 15px;
        border-radius: 8px;
        border: 1px dashed #ccc;
        margin-top: 10px;
        min-height: 100px;
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
    <h2>Detail Resume Rapat</h2>
    <?php if ($detail_resume): ?>
        <div class="detail-item">
            <strong>Kategori:</strong> <?php echo htmlspecialchars($detail_resume['kategori_rapat']); ?>
        </div>
        <div class="detail-item">
            <strong>Tanggal:</strong> <?php echo htmlspecialchars(date('d F Y', strtotime($detail_resume['tanggal_agenda']))); ?>
        </div>
        <div class="detail-item">
            <strong>Nomor Agenda:</strong> <?php echo htmlspecialchars($detail_resume['nomor_undangan'] ?? 'N/A'); ?>
        </div>
        <div class="detail-item">
            <strong>Perihal:</strong> <?php echo htmlspecialchars($detail_resume['perihal'] ?? 'N/A'); ?>
        </div>
        <div class="detail-item">
            <strong>Waktu:</strong> <?php echo htmlspecialchars(date('H:i', strtotime($detail_resume['waktu_mulai']))) . ' - ' . htmlspecialchars(date('H:i', strtotime($detail_resume['waktu_selesai']))); ?>
        </div>
        <div class="detail-item">
            <strong>Tempat:</strong> <?php echo htmlspecialchars($detail_resume['tempat']); ?>
        </div>
        <div class="detail-item">
            <strong>Kegiatan:</strong> <?php echo htmlspecialchars($detail_resume['kegiatan']); ?>
        </div>
        <div class="detail-item">
            <strong>Kesimpulan:</strong>
            <div class="kesimpulan-content">
                <?php echo $detail_resume['kesimpulan']; // TIDAK pakai htmlspecialchars karena sudah HTML dari TinyMCE ?>
            </div>
        </div>
        <a href="view_resume_by_category.php?kategori=<?php echo htmlspecialchars($detail_resume['kategori_rapat']); ?>" class="back-link-btn">Kembali ke Daftar Resume</a>
    <?php else: ?>
        <p style="text-align: center;">Resume tidak ditemukan.</p>
        <a href="index.php" class="back-link-btn">Kembali ke Kategori Rapat</a>
    <?php endif; ?>
</div>

<?php
include '../includes/footer.php';
?>