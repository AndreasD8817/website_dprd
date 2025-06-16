<?php
session_start(); // PASTIKAN BARIS INI ADA DI PALING ATAS

// Pastikan koneksi database tersedia
include '../config/koneksi.php';

// Inisialisasi variabel untuk pesan status
$message = '';
if (isset($_SESSION['message'])) {
    $msg_type = $_SESSION['message']['type'];
    $msg_text = $_SESSION['message']['text'];
    $message = '<div class="message ' . $msg_type . '">' . htmlspecialchars($msg_text) . '</div>';
    unset($_SESSION['message']); // Hapus pesan setelah ditampilkan
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

// Data untuk disimpan sementara agar bisa diakses di HTML
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
        white-space: nowrap;
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

    .agenda-table .actions .edit-btn { background-color: #ffc107; }
    .agenda-table .actions .edit-btn:hover { background-color: #e0a800; }
    
    .agenda-table .actions .delete-btn { background-color: #dc3545; }
    .agenda-table .actions .delete-btn:hover { background-color: #c82333; }

    /* Tambahan CSS untuk tombol yang didisable */
    .actions .disabled-btn {
        background-color: #cccccc;
        cursor: not-allowed;
        opacity: 0.7;
        pointer-events: none; /* PENTING: Mencegah klik sepenuhnya */
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
    .action-buttons-top a.add-new {
        background-color: #28a745;
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

    /* Styling Modal */
    .modal {
        display: none; /* TETAPKAN INI display: none; */
        position: fixed; /* Tetap di posisi yang sama saat scroll */
        z-index: 1000; /* Letakkan di atas semua elemen lain */
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto; /* Aktifkan scroll jika konten terlalu besar */
        background-color: rgba(0,0,0,0.4); /* Latar belakang gelap semi-transparan */
        /* HAPUS display: flex; DARI SINI */
        justify-content: center;
        align-items: center;
    }
    /* Tambahkan kelas baru yang akan diberikan oleh JavaScript saat modal ingin ditampilkan */
    .modal.is-visible {
        display: flex; /* Hanya tampilkan sebagai flexbox jika kelas is-visible ada */
    }


    .modal-content {
        background-color: #fefefe;
        margin: auto;
        padding: 30px;
        border: 1px solid #888;
        width: 80%; /* Lebar modal */
        max-width: 500px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        position: relative;
        text-align: center;
    }

    .modal-content h2 {
        color: var(--primary-blue);
        margin-bottom: 20px;
        font-size: 1.8em;
    }

    .modal-content p {
        margin-bottom: 15px;
        font-size: 1.1em;
        color: var(--text-color);
    }

    .modal-content ul {
        list-style: none;
        padding: 0;
        margin-bottom: 20px;
        text-align: left;
        max-width: 80%;
        margin-left: auto;
        margin-right: auto;
    }

    .modal-content ul li {
        padding: 5px 0;
        color: var(--light-text);
    }

    .modal-buttons {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 20px;
    }

    .modal-buttons button {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        transition: background-color 0.3s ease;
    }

    .modal-buttons .btn-cancel {
        background-color: #6c757d;
        color: white;
    }
    .modal-buttons .btn-cancel:hover {
        background-color: #5a6268;
    }

    .modal-buttons .btn-delete {
        background-color: #dc3545;
        color: white;
    }
    .modal-buttons .btn-delete:hover {
        background-color: #c82333;
    }

    .close-button {
        color: #aaa;
        position: absolute;
        top: 10px;
        right: 20px;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    .close-button:hover,
    .close-button:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
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
                        $disabled_class = $has_resume ? 'disabled-btn' : '';
                        $disabled_tooltip = $has_resume ? 'title="Tidak bisa diubah/dihapus karena sudah ada notulensi rapat terkait."' : '';

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
                        echo "<a href='#' class='delete-btn " . $disabled_class . "' data-id='" . $row["id"] . "' data-perihal='" . htmlspecialchars($row["perihal"]) . "' data-nomor='" . htmlspecialchars($row["nomor_undangan"]) . "' data-has-resume='" . ($has_resume ? 'true' : 'false') . "' " . $disabled_tooltip . ">Hapus</a>";
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

<div id="deleteConfirmModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Konfirmasi Penghapusan Agenda</h2>
        <p id="modalMessage">Apakah Anda yakin ingin menghapus agenda ini? Proses ini akan menghapus:</p>
        <ul id="modalDetails">
            <li><strong><span id="modalAgendaText"></span></strong></li>
            <li id="modalResumeCount" style="display:none;">Semua resume rapat terkait (<span id="modalResumeNum">0</span> resume).</li>
        </ul>
        <div class="modal-buttons">
            <button id="cancelDeleteBtn" class="btn-cancel">Batal</button>
            <button id="confirmDeleteBtn" class="btn-delete">Hapus</button>
        </div>
    </div>
</div>
<script>
    // Pastikan ini adalah kode baru di bagian paling bawah file, setelah HTML
    const deleteModal = document.getElementById('deleteConfirmModal');
    const closeButton = document.querySelector('.close-button');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const modalAgendaText = document.getElementById('modalAgendaText');
    const modalResumeCount = document.getElementById('modalResumeCount');
    const modalResumeNum = document.getElementById('modalResumeNum');

    let agendaToDeleteId = null; // Menyimpan ID agenda yang akan dihapus
    let agendaHasResume = false; // Menyimpan status apakah agenda punya resume

    // Fungsi untuk menampilkan modal
    function openDeleteModal(id, perihal, nomor, hasResume) {
        agendaToDeleteId = id;
        agendaHasResume = hasResume;
        modalAgendaText.textContent = `${nomor} / ${perihal}`;

        if (hasResume === 'true') { // hasResume dari data attribute akan jadi string 'true'/'false'
            modalResumeCount.style.display = 'list-item';
            document.getElementById('modalMessage').textContent = 'Anda yakin ingin menghapus agenda ini? Proses ini akan menghapus:';
            modalResumeNum.textContent = 'semua';
        } else {
            modalResumeCount.style.display = 'none';
            document.getElementById('modalMessage').textContent = 'Apakah Anda yakin ingin menghapus agenda ini?';
        }
        deleteModal.classList.add('is-visible'); // GANTI: set display flex dengan menambah class
    }

    // Fungsi untuk menyembunyikan modal
    function closeDeleteModal() {
        deleteModal.classList.remove('is-visible'); // GANTI: set display none dengan menghapus class
        agendaToDeleteId = null;
        agendaHasResume = false;
    }

    // Event listeners
    closeButton.addEventListener('click', closeDeleteModal);
    cancelDeleteBtn.addEventListener('click', closeDeleteModal);

    // Saat tombol "Hapus" di tabel diklik (kita akan menggunakan event delegation)
    document.querySelector('.agenda-table').addEventListener('click', (event) => {
        const target = event.target;
        if (target.classList.contains('delete-btn') && !target.classList.contains('disabled-btn')) {
            event.preventDefault(); // Mencegah link untuk langsung jalan
            const id = target.dataset.id;
            const perihal = target.dataset.perihal;
            const nomor = target.dataset.nomor;
            const hasResume = target.dataset.hasResume; // 'true' atau 'false' string
            openDeleteModal(id, perihal, nomor, hasResume);
        }
    });

    confirmDeleteBtn.addEventListener('click', () => {
        if (agendaToDeleteId) {
            // Arahkan ke skrip delete_agenda.php
            window.location.href = `delete_agenda.php?id=${agendaToDeleteId}`;
        }
        closeDeleteModal();
    });

    // Menutup modal jika klik di luar konten modal
    window.addEventListener('click', (event) => {
        if (event.target == deleteModal) {
            closeDeleteModal();
        }
    });

</script>
<?php
include '../includes/footer.php';
?>