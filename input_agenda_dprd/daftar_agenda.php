<?php
// Pastikan koneksi database tersedia
include '../config/koneksi.php'; // Path relatif dari input_agenda_dprd/ ke config/

// Set judul halaman
$page_title = "Daftar Agenda Rapat - DPRD Kota Surabaya";

// Inisialisasi variabel untuk pesan status (misal setelah hapus atau edit)
$message = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'deleted') {
        $message = '<div class="message success">Agenda berhasil dihapus!</div>';
    } elseif ($_GET['status'] == 'error_delete') {
        $message = '<div class="message error">Gagal menghapus agenda!</div>';
    } elseif ($_GET['status'] == 'updated') {
        $message = '<div class="message success">Agenda berhasil diperbarui!</div>';
    } elseif ($_GET['status'] == 'added') {
        $message = '<div class="message success">Agenda baru berhasil ditambahkan!</div>';
    } elseif ($_GET['status'] == 'invalid_edit_id') {
        $message = '<div class="message error">ID agenda tidak valid untuk diedit!</div>';
    } elseif ($_GET['status'] == 'agenda_not_found') {
        $message = '<div class="message error">Agenda tidak ditemukan!</div>';
    }
}

// Query untuk mengambil semua data dari tabel agenda_rapat
$stmt_agenda = $conn->prepare("
    SELECT
        id,
        perihal,
        nomor_undangan,
        kategori,
        tanggal,
        jam
    FROM
        agenda_rapat
    ORDER BY
        tanggal DESC, jam DESC
");
$stmt_agenda->execute();
$result_agenda = $stmt_agenda->get_result();

// Data untuk disimpan sementara agar bisa diakses di HTML setelah statement ditutup
$agendas_data = [];
if ($result_agenda->num_rows > 0) {
    while($row = $result_agenda->fetch_assoc()) {
        $agendas_data[] = $row;
    }
}
$stmt_agenda->close();


// Query untuk mengecek apakah suatu agenda memiliki resume terkait
// Kita akan membuat array ID agenda yang punya resume
$agendas_with_resume = [];
$stmt_check_resume = $conn->prepare("SELECT DISTINCT id_agenda FROM resume_rapat");
$stmt_check_resume->execute();
$result_check_resume = $stmt_check_resume->get_result();
if ($result_check_resume->num_rows > 0) {
    while($row_check = $result_check_resume->fetch_assoc()) {
        $agendas_with_resume[] = $row_check['id_agenda'];
    }
}
$stmt_check_resume->close();


// Tutup koneksi database setelah semua query selesai
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// Sertakan header global website
include '../includes/header.php';
?>

<style>
    /* Styling umum container */
    .agenda-list-container {
        background-color: var(--card-bg);
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 8px 25px var(--shadow-color);
        width: 100%;
        max-width: 1100px; /* Lebar yang cukup untuk tabel */
        margin: 40px auto;
        box-sizing: border-box;
    }

    .agenda-list-container h2 {
        text-align: center;
        color: var(--primary-blue);
        margin-bottom: 30px;
        font-size: 2.2em;
        font-weight: 700;
    }

    .table-responsive {
        overflow-x: auto; /* Agar tabel responsif di layar kecil */
    }

    .agenda-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .agenda-table th, .agenda-table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
        vertical-align: top;
        font-size: 0.9em;
    }

    .agenda-table th {
        background-color: var(--primary-blue);
        color: white;
        font-weight: 600;
        white-space: nowrap; /* Mencegah header tabel pecah baris */
    }

    .agenda-table tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    .agenda-table .actions {
        white-space: nowrap; /* Mencegah tombol pecah baris */
    }

    .agenda-table .actions a {
        display: inline-block;
        padding: 6px 10px;
        margin: 3px;
        text-decoration: none;
        color: white;
        border-radius: 4px;
        transition: background-color 0.3s ease;
    }

    .agenda-table .actions .edit-btn { background-color: #ffc107; } /* Kuning */
    .agenda-table .actions .edit-btn:hover { background-color: #e0a800; }
    
    .agenda-table .actions .delete-btn { background-color: #dc3545; } /* Merah */
    .agenda-table .actions .delete-btn:hover { background-color: #c82333; }

    /* Tambahan CSS untuk tombol yang didisable */
    .actions .disabled-btn {
        background-color: #cccccc; /* Warna abu-abu */
        cursor: not-allowed;
        opacity: 0.7;
        pointer-events: none; /* PENTING: Mencegah klik sepenuhnya */
    }

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

<div class="agenda-list-container">
    <h2>Daftar Agenda Rapat</h2>

    <?php echo $message; // Menampilkan pesan status ?>

    <div class="action-buttons-top">
        <a href="index.php" class="add-new">+ Input Agenda Baru</a>
        <a href="../index.php" class="back-to-home">Kembali ke Beranda</a>
    </div>

    <div class="table-responsive">
        <table class="agenda-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Perihal Agenda</th>
                    <th>Nomor Agenda</th>
                    <th>Kategori</th>
                    <th>Tanggal</th>
                    <th>Jam</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($agendas_data) > 0) {
                    $counter = 1;
                    foreach($agendas_data as $row) {
                        // Tentukan apakah agenda ini memiliki resume terkait
                        $has_resume = in_array($row['id'], $agendas_with_resume);
                        $disabled_class = $has_resume ? 'disabled-btn' : ''; // Tambahkan class jika ada resume
                        $disabled_tooltip = $has_resume ? 'title="Tidak bisa diubah/dihapus karena sudah ada notulensi rapat terkait."' : ''; // Tooltip

                        echo "<tr>";
                        echo "<td>" . $counter++ . "</td>";
                        echo "<td>" . htmlspecialchars($row["perihal"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["nomor_undangan"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["kategori"]) . "</td>";
                        echo "<td>" . htmlspecialchars(date('d F Y', strtotime($row["tanggal"]))) . "</td>";
                        echo "<td>" . htmlspecialchars(date('H:i', strtotime($row["jam"]))) . "</td>";
                        echo "<td class='actions'>";
                        // Link untuk mengedit agenda
                        echo "<a href='edit_agenda.php?id=" . $row["id"] . "' class='edit-btn " . $disabled_class . "' " . $disabled_tooltip . ">Edit</a>";
                        // Link untuk menghapus agenda
                        echo "<a href='delete_agenda.php?id=" . $row["id"] . "' class='delete-btn " . $disabled_class . "' onclick='return " . ($has_resume ? 'false' : 'confirm("Apakah Anda yakin ingin menghapus agenda ini?");') . "' " . $disabled_tooltip . ">Hapus</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='text-center'>Belum ada agenda rapat yang tersimpan.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include '../includes/footer.php';
?>