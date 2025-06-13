<?php
// Pastikan koneksi database tersedia
include '../config/koneksi.php'; // Path relatif dari input_agenda_dprd/ ke config/

// Ambil ID Agenda dari URL
$id_agenda_to_edit = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : null;

// Jika tidak ada ID agenda di URL, redirect kembali ke halaman daftar agenda
if (!$id_agenda_to_edit) {
    header("Location: daftar_agenda.php?status=invalid_edit_id");
    exit();
}

// Inisialisasi variabel untuk menampung data form
$perihal_agenda_form = '';
$nomor_undangan_form = '';
$kategori_agenda_form = '';
$tanggal_agenda_form = '';
$jam_agenda_form = '';
$message = ''; // Variabel untuk pesan sukses/error

// --- LOGIKA UNTUK MENGAMBIL DATA AGENDA YANG SUDAH ADA (MODE EDIT) ---
$stmt_edit = $conn->prepare("
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
$stmt_edit->bind_param("i", $id_agenda_to_edit);
$stmt_edit->execute();
$result_edit = $stmt_edit->get_result();

if ($result_edit->num_rows > 0) {
    $agenda_data = $result_edit->fetch_assoc();
    // Isi variabel form dengan data dari database
    $perihal_agenda_form = $agenda_data['perihal'];
    $nomor_undangan_form = $agenda_data['nomor_undangan'];
    $kategori_agenda_form = $agenda_data['kategori'];
    $tanggal_agenda_form = $agenda_data['tanggal'];
    $jam_agenda_form = $agenda_data['jam'];
} else {
    // Jika ID agenda tidak ditemukan, berikan pesan error dan redirect
    header("Location: daftar_agenda.php?status=agenda_not_found");
    exit();
}
$stmt_edit->close();

// --- PROSES FORM SUBMISSION (UPDATE AGENDA) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form yang disubmit
    $id_agenda_submitted = htmlspecialchars(trim($_POST['agenda_id_hidden'])); // ID agenda dari hidden input
    $perihal_agenda_submitted = htmlspecialchars(trim($_POST['perihal_agenda']));
    $nomor_undangan_submitted = htmlspecialchars(trim($_POST['nomor_undangan']));
    $kategori_agenda_submitted = htmlspecialchars(trim($_POST['kategori_agenda']));
    $tanggal_agenda_submitted = htmlspecialchars(trim($_POST['tanggal_agenda']));
    $jam_agenda_submitted = htmlspecialchars(trim($_POST['jam_agenda']));

    // Validasi input wajib
    if (empty($perihal_agenda_submitted) || empty($nomor_undangan_submitted) || empty($kategori_agenda_submitted) || empty($tanggal_agenda_submitted) || empty($jam_agenda_submitted)) {
        $message = '<div class="message error">Semua kolom wajib diisi!</div>';

        // Kembalikan nilai ke form agar tidak hilang saat validasi gagal
        $perihal_agenda_form = $perihal_agenda_submitted;
        $nomor_undangan_form = $nomor_undangan_submitted;
        $kategori_agenda_form = $kategori_agenda_submitted;
        $tanggal_agenda_form = $tanggal_agenda_submitted;
        $jam_agenda_form = $jam_agenda_submitted;

    } else {
        // Persiapkan query UPDATE ke tabel agenda_rapat
        $stmt_update = $conn->prepare("UPDATE agenda_rapat SET perihal = ?, nomor_undangan = ?, kategori = ?, tanggal = ?, jam = ? WHERE id = ?");
        // sssssi: s=perihal, s=nomor, s=kategori, s=tanggal, s=jam, i=id
        $stmt_update->bind_param("sssssi",
            $perihal_agenda_submitted,
            $nomor_undangan_submitted,
            $kategori_agenda_submitted,
            $tanggal_agenda_submitted,
            $jam_agenda_submitted,
            $id_agenda_submitted
        );

        // Eksekusi query
        if ($stmt_update->execute()) {
            $message = '<div class="message success">Agenda rapat berhasil diperbarui!</div>';
            // Redirect kembali ke halaman daftar agenda setelah sukses update
            header("Location: daftar_agenda.php?status=updated");
            exit();
        } else {
            $message = '<div class="message error">Error memperbarui agenda: ' . $stmt_update->error . '</div>';
            // Jika error, pastikan nilai form tetap terisi
            $perihal_agenda_form = $perihal_agenda_submitted;
            $nomor_undangan_form = $nomor_undangan_submitted;
            $kategori_agenda_form = $kategori_agenda_submitted;
            $tanggal_agenda_form = $tanggal_agenda_submitted;
            $jam_agenda_form = $jam_agenda_submitted;
        }
        $stmt_update->close();
    }
}

// Tutup koneksi database di akhir skrip PHP, sebelum HTML dimulai
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// Set judul halaman
$page_title = "Edit Agenda Rapat - DPRD Kota Surabaya";

// Sertakan header global website
include '../includes/header.php'; // Path relatif dari input_agenda_dprd/ ke includes/
?>

<style>
    /* CSS Styling (Hampir sama dengan input_agenda_dprd/index.php, tapi diulang di sini) */
    .agenda-input-container {
        background-color: var(--card-bg);
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 8px 25px var(--shadow-color);
        width: 100%;
        max-width: 550px;
        margin: 40px auto;
        box-sizing: border-box;
    }

    .agenda-input-container h2 {
        text-align: center;
        color: var(--primary-blue);
        margin-bottom: 30px;
        font-size: 2.2em;
        font-weight: 700;
    }

    .form-group-agenda {
        margin-bottom: 20px;
    }

    .form-group-agenda label {
        display: block;
        margin-bottom: 8px;
        color: var(--text-color);
        font-weight: 600;
        font-size: 0.95em;
    }

    .form-group-agenda input[type="text"],
    .form-group-agenda input[type="date"],
    .form-group-agenda input[type="time"],
    .form-group-agenda select {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        box-sizing: border-box;
        font-size: 1em;
        color: var(--text-color);
        background-color: var(--light-blue);
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .form-group-agenda input[type="text"]:focus,
    .form-group-agenda input[type="date"]:focus,
    .form-group-agenda input[type="time"]:focus,
    .form-group-agenda select:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        outline: none;
    }

    .submit-button {
        background-color: var(--primary-blue);
        color: white;
        padding: 14px 25px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        width: 100%;
        font-size: 1.1em;
        font-weight: 600;
        margin-top: 30px;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .submit-button:hover {
        background-color: var(--dark-blue);
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
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

<div class="agenda-input-container">
    <h2>Edit Agenda Rapat</h2>

    <?php echo $message; // Menampilkan pesan sukses/error ?>

    <form action="" method="POST">
        <input type="hidden" name="agenda_id_hidden" value="<?php echo htmlspecialchars($id_agenda_to_edit); ?>">

        <div class="form-group-agenda">
            <label for="perihal_agenda">Perihal Agenda Rapat DPRD:</label>
            <input type="text" id="perihal_agenda" name="perihal_agenda" required value="<?php echo htmlspecialchars($perihal_agenda_form); ?>">
        </div>

        <div class="form-group-agenda">
            <label for="nomor_undangan">Nomor Agenda Undangan Rapat DPRD:</label>
            <input type="text" id="nomor_undangan" name="nomor_undangan" required value="<?php echo htmlspecialchars($nomor_undangan_form); ?>">
        </div>

        <div class="form-group-agenda">
            <label for="kategori_agenda">Kategori Agenda Rapat DPRD:</label>
            <select id="kategori_agenda" name="kategori_agenda" required>
                <option value="">-- Pilih Kategori --</option>
                <option value="Komisi A" <?php echo ($kategori_agenda_form == 'Komisi A') ? 'selected' : ''; ?>>Komisi A</option>
                <option value="Komisi B" <?php echo ($kategori_agenda_form == 'Komisi B') ? 'selected' : ''; ?>>Komisi B</option>
                <option value="Komisi C" <?php echo ($kategori_agenda_form == 'Komisi C') ? 'selected' : ''; ?>>Komisi C</option>
                <option value="Komisi D" <?php echo ($kategori_agenda_form == 'Komisi D') ? 'selected' : ''; ?>>Komisi D</option>
                <option value="Rapat Pimpinan" <?php echo ($kategori_agenda_form == 'Rapat Pimpinan') ? 'selected' : ''; ?>>Rapat Pimpinan</option>
                <option value="Badan Anggaran" <?php echo ($kategori_agenda_form == 'Badan Anggaran') ? 'selected' : ''; ?>>Badan Anggaran</option>
                <option value="Badan Musyawarah" <?php echo ($kategori_agenda_form == 'Badan Musyawarah') ? 'selected' : ''; ?>>Badan Musyawarah</option>
                <option value="Paripurna" <?php echo ($kategori_agenda_form == 'Paripurna') ? 'selected' : ''; ?>>Paripurna</option>
            </select>
        </div>

        <div class="form-group-agenda">
            <label for="tanggal_agenda">Tanggal Agenda Rapat DPRD:</label>
            <input type="date" id="tanggal_agenda" name="tanggal_agenda" required value="<?php echo htmlspecialchars($tanggal_agenda_form); ?>">
        </div>

        <div class="form-group-agenda">
            <label for="jam_agenda">Jam Agenda Rapat DPRD (HH:MM):</label>
            <input type="time" id="jam_agenda" name="jam_agenda" required value="<?php echo htmlspecialchars($jam_agenda_form); ?>">
        </div>

        <button type="submit" class="submit-button">Perbarui Agenda</button>
        <a href="daftar_agenda.php" style="display: block; text-align: center; margin-top: 15px; color: var(--primary-blue); text-decoration: none; font-weight: 500;">Kembali ke Daftar Agenda</a>
    </form>
</div>

<?php
include '../includes/footer.php'; // Path relatif dari input_agenda_dprd/ ke includes/
?>