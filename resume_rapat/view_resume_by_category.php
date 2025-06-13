<?php
// Pastikan koneksi database tersedia
include '../config/koneksi.php';

// Ambil kategori dari URL
$kategori_rapat_url = isset($_GET['kategori']) ? htmlspecialchars($_GET['kategori']) : 'Umum';

// Jika tidak ada kategori di URL, redirect kembali ke halaman kategori resume
if (!$kategori_rapat_url || $kategori_rapat_url == 'Umum') {
    header("Location: index.php"); // Kembali ke halaman kategori jika tidak ada kategori spesifik
    exit();
}

// Set judul halaman
$page_title = "Daftar Resume Rapat " . $kategori_rapat_url . " - DPRD Kota Surabaya";

// Inisialisasi variabel untuk pesan status (misal setelah hapus atau edit)
$message = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'deleted') {
        $message = '<div class="message success">Resume berhasil dihapus!</div>';
    } elseif ($_GET['status'] == 'error_delete') {
        $message = '<div class="message error">Gagal menghapus resume!</div>';
    } elseif ($_GET['status'] == 'updated') {
        $message = '<div class="message success">Resume berhasil diperbarui!</div>';
    } elseif ($_GET['status'] == 'added') {
        $message = '<div class="message success">Resume baru berhasil ditambahkan!</div>';
    }
}

// Query untuk mengambil semua data resume dari tabel resume_rapat
// dan juga mengambil detail agenda dari tabel agenda_rapat menggunakan JOIN
$stmt_resume = $conn->prepare("
    SELECT
        rr.id AS resume_id,
        rr.tanggal_agenda,
        rr.waktu_mulai,
        rr.waktu_selesai,
        rr.tempat,
        rr.kegiatan,
        rr.kesimpulan,
        ar.nomor_undangan,
        ar.perihal
    FROM
        resume_rapat rr
    LEFT JOIN
        agenda_rapat ar ON rr.id_agenda = ar.id
    WHERE
        rr.kategori_rapat = ?
    ORDER BY
        rr.tanggal_agenda DESC, rr.waktu_mulai DESC
");
$stmt_resume->bind_param("s", $kategori_rapat_url);
$stmt_resume->execute();
$result_resume = $stmt_resume->get_result();

// Tutup koneksi database setelah semua query selesai
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// Sertakan header global website
include '../includes/header.php';
?>

<style>
    /* Styling umum container */
    .resume-list-container {
        background-color: var(--card-bg);
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 8px 25px var(--shadow-color);
        width: 100%;
        max-width: 1100px; /* Lebar lebih besar untuk tabel resume */
        margin: 40px auto;
        box-sizing: border-box;
    }

    .resume-list-container h2 {
        text-align: center;
        color: var(--primary-blue);
        margin-bottom: 30px;
        font-size: 2.2em;
        font-weight: 700;
    }

    .table-responsive {
        overflow-x: auto; /* Agar tabel responsif di layar kecil */
    }

    .resume-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .resume-table th, .resume-table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
        vertical-align: top;
        font-size: 0.9em;
    }

    .resume-table th {
        background-color: var(--primary-blue);
        color: white;
        font-weight: 600;
        white-space: nowrap; /* Mencegah header tabel pecah baris */
    }

    .resume-table tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    .resume-table .actions {
        white-space: nowrap; /* Mencegah tombol pecah baris */
    }

    .resume-table .actions a {
        display: inline-block;
        padding: 6px 10px;
        margin: 3px;
        text-decoration: none;
        color: white;
        border-radius: 4px;
        transition: background-color 0.3s ease;
    }

    .resume-table .actions .view-btn { background-color: #17a2b8; } /* Biru kehijauan */
    .resume-table .actions .view-btn:hover { background-color: #138496; }

    .resume-table .actions .edit-btn { background-color: #ffc107; } /* Kuning */
    .resume-table .actions .edit-btn:hover { background-color: #e0a800; }
    
    .resume-table .actions .hadir-btn { background-color: #28a745; } /* Hijau */
    .resume-table .actions .hadir-btn:hover { background-color: #218838; }

    .resume-table .actions .delete-btn { background-color: #dc3545; } /* Merah */
    .resume-table .actions .delete-btn:hover { background-color: #c82333; }

    .action-buttons-top {
        margin-bottom: 20px;
        text-align: right; /* Tombol rata kanan */
    }
    .action-buttons-top a {
        display: inline-block;
        background-color: var(--primary-blue);
        color: white;
        padding: 10px 15px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 500;
        transition: background-color 0.3s ease;
        margin-left: 10px; /* Jarak antar tombol */
    }
    .action-buttons-top a.add-new {
        background-color: #28a745; /* Hijau untuk tambah baru */
    }
    .action-buttons-top a.add-new:hover {
        background-color: #218838;
    }
    .action-buttons-top a:hover {
        background-color: var(--dark-blue);
    }

    .message {
        text-align: center;
        margin-bottom: 20px;
        padding: 12px 15px;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.95em;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }
    .message.success {
        background-color: var(--success-bg);
        color: var(--success-text);
        border: 1px solid #c3e6cb;
    }
    .message.error {
        background-color: var(--error-bg);
        color: var(--error-text);
        border: 1px solid #f5c6cb;
    }
</style>

<div class="resume-list-container">
    <h2>Daftar Resume Rapat Kategori: <?php echo htmlspecialchars($kategori_rapat_url); ?></h2>

    <?php echo $message; // Menampilkan pesan status ?>

    <div class="action-buttons-top">
        <a href="input_resume.php?kategori=<?php echo rawurlencode($kategori_rapat_url); ?>" class="add-new">+ Input Resume Baru</a>
        <a href="index.php" class="back-to-categories">Kembali ke Kategori Rapat</a>
    </div>

    <div class="table-responsive">
        <table class="resume-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Tanggal</th>
                    <th>Nomor Agenda</th>
                    <th>Perihal Agenda</th>
                    <th>Waktu</th>
                    <th>Tempat</th>
                    <th>Kegiatan</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result_resume->num_rows > 0) {
                    $counter = 1;
                    while($row = $result_resume->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $counter++ . "</td>";
                        echo "<td>" . htmlspecialchars(date('d F Y', strtotime($row["tanggal_agenda"]))) . "</td>";
                        echo "<td>" . htmlspecialchars($row["nomor_undangan"] ?? 'N/A') . "</td>";
                        echo "<td>" . htmlspecialchars($row["perihal"] ?? 'N/A') . "</td>";
                        echo "<td>" . htmlspecialchars(date('H:i', strtotime($row["waktu_mulai"]))) . " - " . htmlspecialchars(date('H:i', strtotime($row["waktu_selesai"]))) . "</td>";
                        echo "<td>" . htmlspecialchars($row["tempat"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["kegiatan"]) . "</td>";
                        echo "<td class='actions'>";
                        echo "<a href='view_resume_detail.php?id=" . $row["resume_id"] . "' class='view-btn'>Lihat</a>";
                        echo "<a href='input_resume.php?id=" . $row["resume_id"] . "&kategori=" . rawurlencode($kategori_rapat_url) . "' class='edit-btn'>Edit</a>";
                        echo "<a href='daftar_hadir.php?id_resume=" . $row["resume_id"] . "' class='hadir-btn'>Daftar Hadir</a>";
                        echo "<a href='delete_resume.php?id=" . $row["resume_id"] . "&kategori=" . rawurlencode($kategori_rapat_url) . "' class='delete-btn' onclick='return confirm(\"Apakah Anda yakin ingin menghapus resume ini?\");'>Hapus</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center'>Belum ada resume rapat untuk kategori ini.</td></tr>";
                }
                $stmt_resume->close();
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include '../includes/footer.php';
?>