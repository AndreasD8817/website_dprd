<?php
// Pastikan koneksi database tersedia
include '../config/koneksi.php'; // Path relatif dari resume_rapat/ ke config/

// Ambil kategori dari URL
$kategori_dari_url = isset($_GET['kategori']) ? htmlspecialchars($_GET['kategori']) : null;

// Jika tidak ada kategori di URL, redirect kembali ke halaman kategori resume
if (!$kategori_dari_url) {
    header("Location: index.php?status=no_category_selected");
    exit();
}

// Set judul halaman
$page_title = "Daftar Agenda Rapat " . $kategori_dari_url . " - DPRD Kota Surabaya";

// Inisialisasi variabel untuk pesan status (misal setelah hapus, edit, tambah resume)
$message = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'deleted_agenda') {
        $message = '<div class="message success">Agenda berhasil dihapus!</div>';
    } elseif ($_GET['status'] == 'error_delete_agenda') {
        $message = '<div class="message error">Gagal menghapus agenda!</div>';
    } elseif ($_GET['status'] == 'updated_agenda') {
        $message = '<div class="message success">Agenda berhasil diperbarui!</div>';
    } elseif ($_GET['status'] == 'added_resume') {
        $message = '<div class="message success">Resume baru berhasil ditambahkan!</div>';
    } elseif ($_GET['status'] == 'updated_resume') {
        $message = '<div class="message success">Resume berhasil diperbarui!</div>';
    }
}

// Query untuk mengambil semua data dari tabel agenda_rapat yang sesuai kategori
// dan JOIN dengan resume_rapat untuk mengecek keberadaan resume dan ID-nya
$stmt_agenda_filtered = $conn->prepare("
    SELECT
        ar.id AS agenda_id,
        ar.perihal,
        ar.nomor_undangan,
        ar.kategori,
        ar.tanggal,
        ar.jam,
        rr.id AS resume_id -- Ambil ID resume jika ada
    FROM
        agenda_rapat ar
    LEFT JOIN
        resume_rapat rr ON ar.id = rr.id_agenda
    WHERE
        ar.kategori = ?
    ORDER BY
        ar.tanggal DESC, ar.jam DESC
");
$stmt_agenda_filtered->bind_param("s", $kategori_dari_url);
$stmt_agenda_filtered->execute();
$result_agenda_filtered = $stmt_agenda_filtered->get_result();

// Tutup koneksi database setelah semua query selesai
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// Sertakan header global website
include '../includes/header.php';
?>

<style>
    /* Styling umum container (mirip agenda-list-container) */
    .agenda-filtered-container {
        background-color: var(--card-bg);
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 8px 25px var(--shadow-color);
        width: 100%;
        max-width: 1200px; /* Lebih lebar karena banyak tombol */
        margin: 40px auto;
        box-sizing: border-box;
    }

    .agenda-filtered-container h2 {
        text-align: center;
        color: var(--primary-blue);
        margin-bottom: 30px;
        font-size: 2.2em;
        font-weight: 700;
    }

    .table-responsive {
        overflow-x: auto;
    }

    .agenda-filtered-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .agenda-filtered-table th, .agenda-filtered-table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
        vertical-align: top;
        font-size: 0.9em;
    }

    .agenda-filtered-table th {
        background-color: var(--primary-blue);
        color: white;
        font-weight: 600;
        white-space: nowrap;
    }

    .agenda-filtered-table tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    .agenda-filtered-table .actions {
        white-space: nowrap; /* Pastikan semua tombol aksi tidak pecah baris */
        min-width: 250px; /* Beri ruang cukup untuk tombol */
    }

    .agenda-filtered-table .actions a {
        display: inline-block;
        padding: 6px 10px;
        margin: 3px;
        text-decoration: none;
        color: white;
        border-radius: 4px;
        transition: background-color 0.3s ease;
    }

    /* Warna tombol aksi */
    .actions .input-resume-btn { background-color: #007bff; }
    .actions .input-resume-btn:hover { background-color: #0056b3; }

    .actions .daftar-hadir-btn { background-color: #28a745; }
    .actions .daftar-hadir-btn:hover { background-color: #218838; }

    .actions .view-agenda-btn { background-color: #17a2b8; }
    .actions .view-agenda-btn:hover { background-color: #138496; }

    .actions .edit-agenda-btn { background-color: #ffc107; }
    .actions .edit-agenda-btn:hover { background-color: #e0a800; }
    
    .actions .delete-agenda-btn { background-color: #dc3545; }
    .actions .delete-agenda-btn:hover { background-color: #c82333; }

    /* Tambahan CSS untuk tombol yang didisable */
    .actions .disabled-btn {
        background-color: #cccccc;
        cursor: not-allowed;
        opacity: 0.7;
        pointer-events: none;
    }
    .actions .info-text {
        color: #666;
        font-size: 0.85em;
        margin: 5px 0;
        display: block;
    }

    .action-buttons-top {
        margin-bottom: 20px;
        text-align: right;
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
        margin-left: 10px;
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

<div class="agenda-filtered-container">
    <h2>Daftar Agenda Rapat Kategori: <?php echo htmlspecialchars($kategori_dari_url); ?></h2>

    <?php echo $message; // Menampilkan pesan status ?>

    <div class="action-buttons-top">
        <a href="index.php" class="back-to-categories">Kembali ke Kategori Rapat</a>
        <a href="../input_agenda_dprd/index.php" class="add-new">Input Agenda Baru</a>
    </div>

    <div class="table-responsive">
        <table class="agenda-filtered-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Perihal Agenda</th>
                    <th>Nomor Agenda</th>
                    <th>Tanggal</th>
                    <th>Jam</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result_agenda_filtered->num_rows > 0) {
                    $counter = 1;
                    while($row = $result_agenda_filtered->fetch_assoc()) {
                        // Cek apakah agenda ini sudah memiliki resume terkait
                        $has_resume = !empty($row['resume_id']);
                        
                        // Menentukan kelas disabled untuk tombol Edit/Hapus Agenda
                        $agenda_disabled_class = $has_resume ? 'disabled-btn' : '';
                        $agenda_disabled_tooltip = $has_resume ? 'title="Tidak bisa diubah/dihapus karena sudah ada notulensi rapat terkait."' : '';

                        echo "<tr>";
                        echo "<td>" . $counter++ . "</td>";
                        echo "<td>" . htmlspecialchars($row["perihal"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["nomor_undangan"]) . "</td>";
                        echo "<td>" . htmlspecialchars(date('d F Y', strtotime($row["tanggal"]))) . "</td>";
                        echo "<td>" . htmlspecialchars(date('H:i', strtotime($row["jam"]))) . "</td>";
                        echo "<td class='actions'>";
                        
                        // Tombol Input Resume
                        // Jika sudah ada resume, disable tombol Input Resume dan ganti teksnya
                        if ($has_resume) {
                            echo "<a href='#' class='input-resume-btn disabled-btn' title='Notulensi sudah ada.'>Input Resume</a>";
                            echo "<a href='input_resume.php?id=" . $row["resume_id"] . "&kategori=" . rawurlencode($row["kategori"]) . "' class='input-resume-btn edit-resume-btn'>Edit Resume</a>"; // Tombol Edit Resume
                        } else {
                            echo "<a href='input_resume.php?kategori=" . rawurlencode($row["kategori"]) . "&id_agenda=" . $row["agenda_id"] . "' class='input-resume-btn'>Input Resume</a>";
                        }
                        
                        // Tombol Daftar Hadir
                        if ($has_resume) {
                            echo "<a href='daftar_hadir.php?id_resume=" . $row["resume_id"] . "' class='daftar-hadir-btn'>Daftar Hadir</a>";
                        } else {
                            echo "<a href='#' class='daftar-hadir-btn disabled-btn' title='Belum ada resume untuk agenda ini.'>Daftar Hadir</a>";
                        }

                        // Tombol Lihat Agenda Asli
                        echo "<a href='../input_agenda_dprd/view_agenda_detail.php?id=" . $row["agenda_id"] . "' class='view-agenda-btn'>Lihat Agenda</a>"; // Asumsi file ini ada/akan dibuat

                        // Tombol Edit Agenda
                        echo "<a href='../input_agenda_dprd/edit_agenda.php?id=" . $row["agenda_id"] . "' class='edit-agenda-btn " . $agenda_disabled_class . "' " . $agenda_disabled_tooltip . ">Edit Agenda</a>";
                        
                        // Tombol Hapus Agenda
                        echo "<a href='../input_agenda_dprd/delete_agenda.php?id=" . $row["agenda_id"] . "' class='delete-agenda-btn " . $agenda_disabled_class . "' onclick='return " . ($has_resume ? 'false' : 'confirm(\"Apakah Anda yakin ingin menghapus agenda ini?\");') . "' " . $agenda_disabled_tooltip . ">Hapus Agenda</a>";
                        
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center'>Belum ada agenda rapat untuk kategori ini.</td></tr>";
                }
                $stmt_agenda_filtered->close();
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include '../includes/footer.php';
?>