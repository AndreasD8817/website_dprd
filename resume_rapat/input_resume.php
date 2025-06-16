<?php
// Pastikan koneksi database tersedia
include '../config/koneksi.php'; // Path relatif dari resume_rapat/ ke config/

// Ambil kategori dari URL
$kategori_rapat_url = isset($_GET['kategori']) ? htmlspecialchars($_GET['kategori']) : 'Umum';

// Inisialisasi variabel untuk menampung data form. Ini penting untuk menjaga nilai input jika ada validasi gagal.
$tanggal_agenda_form = '';
$waktu_mulai_form = '';
$waktu_selesai_form = '';
$tempat_form = '';
$kegiatan_form = '';
$kesimpulan_form = '';
$nomor_agenda_form_selected_id = ''; // ID agenda terpilih (dari agenda_rapat)
$perihal_agenda_form = '';
$nomor_agenda_form_text = '';
$id_resume_to_edit = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : null; // Mendapatkan ID resume jika dalam mode edit
$id_agenda_from_url = isset($_GET['id_agenda']) ? htmlspecialchars($_GET['id_agenda']) : null; // Mendapatkan ID agenda jika dari daftar_agenda_by_category.php

$message = ''; // Variabel untuk pesan sukses/error
$is_edit_mode_with_participants = false; // Flag untuk mengontrol readonly/disabled saat edit
$is_new_input_from_agenda = false; // Flag untuk mengontrol readonly/disabled saat input baru dari agenda

// Variabel untuk menyimpan data agenda yang dipilih dari database
$all_unique_dates = []; // Untuk dropdown Tanggal Agenda

// --- MENGAMBIL DATA TANGGAL UNIK UNTUK DROPDOWN TANGGAL ---
// Kita perlu mengambil semua tanggal unik dari tabel agenda_rapat
// yang sesuai dengan kategori yang sedang aktif.
$stmt_dates = $conn->prepare("SELECT DISTINCT tanggal FROM agenda_rapat WHERE kategori = ? ORDER BY tanggal DESC");
$stmt_dates->bind_param("s", $kategori_rapat_url);
$stmt_dates->execute();
$result_dates = $stmt_dates->get_result();

if ($result_dates->num_rows > 0) {
    while ($row_date = $result_dates->fetch_assoc()) {
        $all_unique_dates[] = $row_date['tanggal'];
    }
}
$stmt_dates->close();

// --- LOGIKA UNTUK MODE EDIT (Mengambil data resume yang sudah ada) ---
// Blok ini harus ada dan berfungsi sebelum proses POST
if ($id_resume_to_edit) {
    $stmt_edit = $conn->prepare("
        SELECT
            rr.tanggal_agenda,
            rr.waktu_mulai,
            rr.waktu_selesai,
            rr.tempat,
            rr.kegiatan,
            rr.kesimpulan,
            rr.id_agenda, -- Ambil id_agenda untuk pre-select dropdown
            ar.nomor_undangan,
            ar.perihal
        FROM
            resume_rapat rr
        LEFT JOIN
            agenda_rapat ar ON rr.id_agenda = ar.id
        WHERE
            rr.id = ? AND rr.kategori_rapat = ?
    ");
    $stmt_edit->bind_param("is", $id_resume_to_edit, $kategori_rapat_url);
    $stmt_edit->execute();
    $result_edit = $stmt_edit->get_result();

    if ($result_edit->num_rows > 0) {
        $resume_data = $result_edit->fetch_assoc();
        // Isi variabel form dengan data dari database
        $tanggal_agenda_form = $resume_data['tanggal_agenda'];
        $waktu_mulai_form = $resume_data['waktu_mulai'];
        $waktu_selesai_form = $resume_data['waktu_selesai'];
        $tempat_form = $resume_data['tempat'];
        $kegiatan_form = $resume_data['kegiatan'];
        $kesimpulan_form = $resume_data['kesimpulan']; // Konten TinyMCE
        $nomor_agenda_form_selected_id = $resume_data['id_agenda']; // Untuk pre-select dropdown
        $nomor_agenda_form_text = $resume_data['nomor_undangan']; // Untuk ditampilkan
        $perihal_agenda_form = $resume_data['perihal']; // Untuk ditampilkan

        // Cek apakah resume ini sudah memiliki peserta rapat
        $stmt_check_participants = $conn->prepare("SELECT COUNT(*) FROM daftar_hadir_rapat WHERE id_resume = ?");
        $stmt_check_participants->bind_param("i", $id_resume_to_edit);
        $stmt_check_participants->execute();
        $stmt_check_participants->bind_result($participant_count);
        $stmt_check_participants->fetch();
        $stmt_check_participants->close();

        if ($participant_count > 0) {
            $is_edit_mode_with_participants = true; // Set flag menjadi true
            $message = '<div class="message info" style="background-color: #ffe0b2; color: #e65100; border-color: #ffb74d;">Resume ini sudah memiliki peserta, hanya bagian Kesimpulan yang dapat diedit.</div>';
        }

    } else {
        $message = '<div class="message error">Resume tidak ditemukan atau kategori tidak cocok!</div>';
        $id_resume_to_edit = null; // Reset agar tidak masuk mode update
    }
    $stmt_edit->close();
}


// --- BARU: LOGIKA UNTUK INPUT BARU DARI HALAMAN DAFTAR AGENDA (Auto-fill & Readonly) ---
// Ini hanya berjalan jika bukan mode edit ($id_resume_to_edit null) DAN ada id_agenda dari URL
if (!$id_resume_to_edit && $id_agenda_from_url) {
    $is_new_input_from_agenda = true; // Set flag

    // Ambil detail agenda ini untuk mengisi form awal
    $stmt_initial_agenda = $conn->prepare("SELECT nomor_undangan, perihal, tanggal, jam FROM agenda_rapat WHERE id = ?");
    $stmt_initial_agenda->bind_param("i", $id_agenda_from_url);
    $stmt_initial_agenda->execute();
    $result_initial_agenda = $stmt_initial_agenda->get_result();

    if ($result_initial_agenda->num_rows > 0) {
        $initial_agenda_data = $result_initial_agenda->fetch_assoc();
        // Isi variabel form dengan data dari agenda yang dipilih
        $tanggal_agenda_form = $initial_agenda_data['tanggal'];
        $waktu_mulai_form = $initial_agenda_data['jam'];
        $nomor_agenda_form_selected_id = $id_agenda_from_url; // Penting untuk hidden input
        $nomor_agenda_form_text = $initial_agenda_data['nomor_undangan'];
        $perihal_agenda_form = $initial_agenda_data['perihal'];
    } else {
        // Jika id_agenda dari URL tidak valid, reset flag dan berikan pesan
        $is_new_input_from_agenda = false;
        $message = '<div class="message error">ID Agenda tidak valid atau tidak ditemukan!</div>';
    }
    $stmt_initial_agenda->close();
}


// --- Mengambil semua id_agenda yang sudah memiliki resume, dengan pengecualian untuk mode edit ---
$agendas_with_existing_resume = [];
$stmt_existing_resumes = $conn->prepare("SELECT id_agenda FROM resume_rapat WHERE id_agenda IS NOT NULL");
$stmt_existing_resumes->execute();
$result_existing_resumes = $stmt_existing_resumes->get_result();
if ($result_existing_resumes->num_rows > 0) {
    while ($row_existing = $result_existing_resumes->fetch_assoc()) {
        // HANYA TAMBAHKAN KE ARRAY JIKA BUKAN ID AGENDA DARI RESUME YANG SEDANG DIEDIT
        if (!($id_resume_to_edit && $row_existing['id_agenda'] == $nomor_agenda_form_selected_id)) {
            $agendas_with_existing_resume[] = $row_existing['id_agenda'];
        }
    }
}
$stmt_existing_resumes->close();


// --- PROSES FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil ID resume jika dalam mode UPDATE (dari input hidden)
    $submitted_resume_id = isset($_POST['resume_id']) ? htmlspecialchars(trim($_POST['resume_id'])) : null;

    // Tentukan mode submission untuk validasi dan update query
    $is_submit_new_from_agenda = ($id_agenda_from_url && !$submitted_resume_id);
    $is_submit_edit_with_participants = false;
    if ($submitted_resume_id) { // Hanya cek jika ini update
        $stmt_check_participants_submit = $conn->prepare("SELECT COUNT(*) FROM daftar_hadir_rapat WHERE id_resume = ?");
        $stmt_check_participants_submit->bind_param("i", $submitted_resume_id);
        $stmt_check_participants_submit->execute();
        $stmt_check_participants_submit->bind_result($count_on_submit);
        $stmt_check_participants_submit->fetch();
        $stmt_check_participants_submit->close();
        if ($count_on_submit > 0) {
            $is_submit_edit_with_participants = true;
        }
    }

    // Ambil data form. Perhatikan jika dalam mode readonly, data diambil dari variabel form PHP, bukan POST.
    $kategori_rapat_submitted = htmlspecialchars(trim($_POST['kategori_rapat']));
    // ID Agenda, Tanggal, Waktu Mulai, Tempat, Kegiatan diambil dari variabel form PHP jika readonly
    // Ini berlaku untuk is_new_input_from_agenda dan is_submit_edit_with_participants
    $id_agenda_selected_submitted = ($is_new_input_from_agenda || $is_submit_edit_with_participants) ? $nomor_agenda_form_selected_id : htmlspecialchars(trim($_POST['nomor_agenda_selected_id']));
    $tanggal_agenda_submitted = ($is_new_input_from_agenda || $is_submit_edit_with_participants) ? $tanggal_agenda_form : htmlspecialchars(trim($_POST['tanggal_agenda_selected']));
    $waktu_mulai_submitted = ($is_new_input_from_agenda || $is_submit_edit_with_participants) ? $waktu_mulai_form : htmlspecialchars(trim($_POST['waktu_mulai']));
    $waktu_selesai_submitted = $is_submit_edit_with_participants ? $waktu_selesai_form : htmlspecialchars(trim($_POST['waktu_selesai'])); // Waktu Selesai hanya readonly saat edit_with_participants
    $tempat_submitted = $is_submit_edit_with_participants ? $tempat_form : htmlspecialchars(trim($_POST['tempat']));
    $kegiatan_submitted = $is_submit_edit_with_participants ? $kegiatan_form : htmlspecialchars(trim($_POST['kegiatan']));
    
    $kesimpulan_submitted = $_POST['kesimpulan']; // Konten TinyMCE

    // Untuk mengembalikan nilai ke form jika validasi gagal
    $nomor_agenda_selected_text_submitted = htmlspecialchars(trim($_POST['nomor_agenda_display_hidden']));
    $perihal_agenda_selected_submitted = htmlspecialchars(trim($_POST['perihal_agenda_display']));

    // Validasi input wajib. Untuk mode readonly, hanya kesimpulan yang wajib di-cek.
    if (empty($kesimpulan_submitted) || (!($is_new_input_from_agenda || $is_submit_edit_with_participants) && (empty($id_agenda_selected_submitted) || empty($tanggal_agenda_submitted) || empty($waktu_mulai_submitted) || empty($waktu_selesai_submitted) || empty($tempat_submitted) || empty($kegiatan_submitted)))) {
        $message = '<div class="message error">Semua kolom wajib diisi!</div>';

        // Kembalikan nilai ke form agar tidak hilang saat validasi gagal
        // Untuk field yang readonly, gunakan nilai form yang sudah ada dari DB
        $tanggal_agenda_form = $tanggal_agenda_submitted;
        $nomor_agenda_form_selected_id = $id_agenda_selected_submitted;
        $nomor_agenda_form_text = $nomor_agenda_selected_text_submitted;
        $perihal_agenda_form = $perihal_agenda_selected_submitted;
        $waktu_mulai_form = $waktu_mulai_submitted;
        $waktu_selesai_form = $waktu_selesai_submitted;
        $tempat_form = $tempat_submitted;
        $kegiatan_form = $kegiatan_submitted;
        $kesimpulan_form = $kesimpulan_submitted;
        $id_resume_to_edit = $submitted_resume_id;

    } else {
        // Mode INSERT atau UPDATE
        if ($submitted_resume_id) { // Jika ada resume_id, berarti UPDATE
            // Jika ini mode edit dengan peserta atau input baru dari agenda (readonly), hanya update kesimpulan.
            // Data lain diambil dari nilai awal form.
            if ($is_submit_edit_with_participants || $is_submit_new_from_agenda) {
                $stmt_action = $conn->prepare("UPDATE resume_rapat SET kesimpulan = ? WHERE id = ? AND kategori_rapat = ?");
                $stmt_action->bind_param("sis",
                    $kesimpulan_submitted,
                    $submitted_resume_id,
                    $kategori_rapat_submitted
                );
            } else {
                // UPDATE penuh jika tidak ada peserta dan bukan input dari agenda
                $stmt_action = $conn->prepare("UPDATE resume_rapat SET id_agenda = ?, kategori_rapat = ?, tanggal_agenda = ?, waktu_mulai = ?, waktu_selesai = ?, tempat = ?, kegiatan = ?, kesimpulan = ? WHERE id = ? AND kategori_rapat = ?");
                $stmt_action->bind_param("isssssssis",
                    $id_agenda_selected_submitted,
                    $kategori_rapat_submitted,
                    $tanggal_agenda_submitted,
                    $waktu_mulai_submitted,
                    $waktu_selesai_submitted,
                    $tempat_submitted,
                    $kegiatan_submitted,
                    $kesimpulan_submitted,
                    $submitted_resume_id,
                    $kategori_rapat_submitted
                );
            }
        } else { // Jika tidak ada resume_id, berarti INSERT baru
            $stmt_action = $conn->prepare("INSERT INTO resume_rapat (id_agenda, kategori_rapat, tanggal_agenda, waktu_mulai, waktu_selesai, tempat, kegiatan, kesimpulan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_action->bind_param("isssssss",
                $id_agenda_selected_submitted,
                $kategori_rapat_submitted,
                $tanggal_agenda_submitted,
                $waktu_mulai_submitted,
                $waktu_selesai_submitted,
                $tempat_submitted,
                $kegiatan_submitted,
                $kesimpulan_submitted
            );
        }
        
        // Eksekusi query
        if ($stmt_action->execute()) {
            $message = '<div class="message success">Resume rapat berhasil ' . ($submitted_resume_id ? 'diperbarui' : 'disimpan') . '!</div>';
            // Setelah sukses, kita ingin kembali ke halaman daftar resume
            header("Location: view_resume_by_category.php?kategori=" . rawurlencode($kategori_rapat_submitted) . "&status=" . ($submitted_resume_id ? 'updated' : 'added'));
            exit();
        } else {
            $message = '<div class="message error">Error: ' . $stmt_action->error . '</div>';
            $id_resume_to_edit = $submitted_resume_id; 
        }
        $stmt_action->close();
    }
}

// Tutup koneksi database di akhir skrip PHP, sebelum HTML dimulai
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// Set judul halaman yang akan ditampilkan di tab browser
$page_title = ($id_resume_to_edit ? "Edit Notulensi Rapat" : "Tulis Notulensi Rapat") . " " . $kategori_rapat_url . " - DPRD Kota Surabaya";

// Sertakan header global website
include '../includes/header.php';
?>

<style>
    /* CSS Kustom untuk Tampilan Formulir Notulensi */
    .resume-input-container {
        background-color: var(--card-bg); /* Menggunakan warna dari main.css */
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 8px 25px var(--shadow-color); /* Menggunakan shadow dari main.css */
        width: 100%;
        max-width: 900px; /* Lebar yang lebih besar untuk form notulensi */
        margin: 40px auto; /* Margin atas/bawah dan tengah otomatis */
        box-sizing: border-box;
    }

    .resume-input-container h2 {
        text-align: center;
        color: var(--primary-blue); /* Warna dari main.css */
        margin-bottom: 30px;
        font-size: 2.2em;
        font-weight: 700;
    }

    .form-group-resume {
        margin-bottom: 20px;
    }

    .form-group-resume label {
        display: block;
        margin-bottom: 8px;
        color: var(--text-color); /* Warna dari main.css */
        font-weight: 600;
        font-size: 0.95em;
    }

    /* Styling untuk semua input teks, tanggal, waktu, textarea, dan select */
    .form-group-resume input[type="text"],
    .form-group-resume input[type="date"],
    .form-group-resume input[type="time"],
    .form-group-resume textarea,
    .form-group-resume select {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid var(--border-color); /* Warna dari main.css */
        border-radius: 6px;
        box-sizing: border-box;
        font-size: 1em;
        color: var(--text-color); /* Warna dari main.css */
        background-color: var(--light-blue); /* Warna dari main.css */
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    /* Styling saat input fokus */
    .form-group-resume input[type="text"]:focus,
    .form-group-resume input[type="date"]:focus,
    .form-group-resume input[type="time"]:focus,
    .form-group-resume textarea:focus,
    .form-group-resume select:focus {
        border-color: var(--primary-blue); /* Warna dari main.css */
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25); /* Efek glow biru */
        outline: none;
    }

    /* Styling khusus untuk textarea */
    .form-group-resume textarea {
        min-height: 200px;
        resize: vertical; /* Hanya bisa di-resize secara vertikal */
    }

    /* Styling untuk grup input waktu (mulai - selesai) */
    .time-inputs {
        display: flex;
        gap: 15px; /* Jarak antara input waktu mulai dan selesai */
        align-items: center; /* Pusatkan secara vertikal */
    }

    .time-inputs input {
        flex: 1; /* Agar kedua input waktu memiliki lebar yang sama */
    }
    
    /* Styling khusus untuk input yang readonly */
    .form-group-resume input[readonly],
    .form-group-resume select[disabled] { /* BARU: Tambahkan select[disabled] */
        background-color: #f0f0f0; /* Warna abu-abu yang menunjukkan tidak bisa diedit */
        cursor: not-allowed; /* Kursor tidak diizinkan */
    }
    .form-group-resume input[readonly]:focus,
    .form-group-resume select[disabled]:focus { /* Hapus focus effect untuk disabled */
        box-shadow: none;
        border-color: var(--border-color);
    }


    /* Styling untuk tombol submit */
    .submit-button {
        background-color: var(--primary-blue); /* Warna dari main.css */
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
        background-color: var(--dark-blue); /* Warna dari main.css */
        transform: translateY(-2px); /* Efek angkat sedikit */
        box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
    }

    /* Styling untuk pesan sukses/error */
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
        background-color: var(--success-bg); /* Warna dari main.css */
        color: var(--success-text); /* Warna dari main.css */
        border: 1px solid #c3e6cb;
    }
    .message.error {
        background-color: var(--error-bg); /* Warna dari main.css */
        color: var(--error-text); /* Warna dari main.css */
        border: 1px solid #f5c6cb;
    }
    .message.info { /* Style untuk pesan info */
        background-color: #e0f2f7;
        color: #01579b;
        border: 1px solid #81d4fa;
    }
</style>

<div class="resume-input-container">
    <h2><?php echo ($id_resume_to_edit ? "Edit Notulensi Rapat" : "Tulis Notulensi Rapat") . " " . $kategori_rapat_url; ?></h2>

    <?php echo $message; // Menampilkan pesan sukses/error ?>

    <form action="" method="POST">
        <?php if ($id_resume_to_edit): ?>
        <input type="hidden" name="resume_id" value="<?php echo htmlspecialchars($id_resume_to_edit); ?>">
        <?php endif; ?>
        <input type="hidden" name="kategori_rapat" value="<?php echo htmlspecialchars($kategori_rapat_url); ?>">
        <input type="hidden" id="id_agenda_selected_hidden" name="nomor_agenda_selected_id" value="<?php echo htmlspecialchars($nomor_agenda_form_selected_id); ?>">

        <div class="form-group-resume">
            <label for="tanggal_agenda_dropdown">Tanggal Agenda:</label>
            <select id="tanggal_agenda_dropdown" name="tanggal_agenda_selected" required <?php echo ($is_new_input_from_agenda || $is_edit_mode_with_participants) ? 'disabled' : ''; ?>>
                <option value="">-- Pilih Tanggal Agenda --</option>
                <?php
                // Mengisi dropdown tanggal dengan data dari $all_unique_dates (diambil dari PHP)
                foreach ($all_unique_dates as $date) {
                    $selected = ($tanggal_agenda_form == $date) ? 'selected' : ''; // Menjaga pilihan jika validasi gagal
                    echo "<option value='" . htmlspecialchars($date) . "' " . $selected . ">" . htmlspecialchars(date('d F Y', strtotime($date))) . "</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group-resume">
            <label for="nomor_agenda_dropdown">Nomor Agenda:</label>
            <select id="nomor_agenda_dropdown" name="nomor_agenda_dropdown_selected" required <?php echo ($is_new_input_from_agenda || $is_edit_mode_with_participants) ? 'disabled' : ''; ?>>
                <option value="">-- Pilih Nomor Agenda --</option>
                <?php
                // Ini akan memastikan opsi yang dipilih saat validasi gagal (atau mode edit) akan tampil
                if ($nomor_agenda_form_selected_id && $nomor_agenda_form_text) {
                    echo "<option value='" . htmlspecialchars($nomor_agenda_form_selected_id) . "' selected>" . htmlspecialchars($nomor_agenda_form_text) . "</option>";
                }
                ?>
            </select>
            <input type="hidden" id="nomor_agenda_display_hidden" name="nomor_agenda_display" value="<?php echo htmlspecialchars($nomor_agenda_form_text); ?>">
        </div>

        <div class="form-group-resume">
            <label for="perihal_agenda_text">Perihal Agenda:</label>
            <input type="text" id="perihal_agenda_text" name="perihal_agenda_display" required readonly value="<?php echo htmlspecialchars($perihal_agenda_form); ?>">
        </div>

        <div class="form-group-resume">
            <label>Waktu Kegiatan:</label>
            <div class="time-inputs">
                <input type="time" id="waktu_mulai" name="waktu_mulai" required readonly value="<?php echo htmlspecialchars($waktu_mulai_form); ?>">
                <span>-</span>
                <input type="time" id="waktu_selesai" name="waktu_selesai" required <?php echo ($is_edit_mode_with_participants) ? 'readonly' : ''; ?> value="<?php echo htmlspecialchars($waktu_selesai_form); ?>">
            </div>
        </div>

        <div class="form-group-resume">
            <label for="tempat">Tempat:</label>
            <input type="text" id="tempat" name="tempat" placeholder="Contoh: Ruang Banggar Lt 2 Gedung DPRD Kota Surabaya" required <?php echo ($is_edit_mode_with_participants) ? 'readonly' : ''; ?> value="<?php echo htmlspecialchars($tempat_form); ?>">
        </div>

        <div class="form-group-resume">
            <label for="kegiatan">Kegiatan:</label>
            <input type="text" id="kegiatan" name="kegiatan" placeholder="Contoh: Rapat Forum Perangkat Daerah dan Forum Konsultasi Publik" required <?php echo ($is_edit_mode_with_participants) ? 'readonly' : ''; ?> value="<?php echo htmlspecialchars($kegiatan_form); ?>">
        </div>

        <div class="form-group-resume">
            <label for="kesimpulan">Kesimpulan:</label>
            <textarea id="kesimpulan" name="kesimpulan"><?php echo htmlspecialchars($kesimpulan_form); ?></textarea>
        </div>

        <button type="submit" class="submit-button">Simpan Notulensi</button>
        <a href="index.php" style="display: block; text-align: center; margin-top: 15px; color: var(--primary-blue); text-decoration: none; font-weight: 500;">Kembali ke Kategori Rapat</a>
    </form>
</div>

<script src="https://cdn.tiny.cloud/1/aeklnl9fouxrne8ygv7fa9z67zinbflmb6unw1s08318mbxr/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<script>
    // Inisialisasi TinyMCE Editor untuk textarea Kesimpulan
    tinymce.init({
        selector: '#kesimpulan', // ID dari textarea yang akan diubah menjadi editor
        plugins: 'advlist autolink lists link image charmap print preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste code help wordcount',
        toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter ' +
                 'alignright alignjustify | bullist numlist outdent indent | removeformat | help',
        content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
        height: 300, // Tinggi editor
        menubar: false, // Sembunyikan menu bar di atas toolbar
        statusbar: false // Sembunyikan status bar di bawah editor
    });

    // ===========================================
    // LOGIKA JAVASCRIPT UNTUK DROPDOWN DINAMIS (AJAX)
    // ===========================================

    // Mendapatkan referensi ke elemen-elemen HTML yang akan dimanipulasi
    const tanggalAgendaDropdown = document.getElementById('tanggal_agenda_dropdown');
    const nomorAgendaDropdown = document.getElementById('nomor_agenda_dropdown'); // Dropdown Nomor Agenda
    const perihalAgendaInput = document.getElementById('perihal_agenda_text'); // Input untuk Perihal Agenda
    const waktuMulaiInput = document.getElementById('waktu_mulai'); // Input untuk Waktu Mulai (readonly)
    const idAgendaSelectedHidden = document.getElementById('id_agenda_selected_hidden'); // Hidden input untuk menyimpan ID Agenda terpilih
    const nomorAgendaDisplayTextHidden = document.getElementById('nomor_agenda_display_hidden'); // Hidden input untuk menyimpan teks Nomor Agenda yang ditampilkan

    let fetchedAgendas = []; // Variabel global untuk menyimpan data agenda yang sudah di-fetch

    // Mengambil kategori rapat dari hidden input PHP (untuk filtering AJAX)
    const kategoriRapat = document.querySelector('input[name="kategori_rapat"]').value;
    console.log("Kategori Rapat (dari hidden input):", kategoriRapat); // Debugging

    // BARU: Mendapatkan status mode dari PHP (untuk JavaScript)
    const isEditModeWithParticipantsJS = <?php echo json_encode($is_edit_mode_with_participants); ?>;
    const isNewInputFromAgendaJS = <?php echo json_encode($is_new_input_from_agenda); ?>;
    const initialSelectedDate = "<?php echo $tanggal_agenda_form; ?>"; // Ambil nilai awal tanggal dari PHP
    const initialSelectedAgendaId = "<?php echo $nomor_agenda_form_selected_id; ?>"; // Ambil ID agenda awal dari PHP

    console.log("Is Edit Mode With Participants:", isEditModeWithParticipantsJS); // Debugging
    console.log("Is New Input From Agenda:", isNewInputFromAgendaJS); // Debugging

    // Fungsi utama untuk memuat detail agenda (Nomor, Perihal, Jam) berdasarkan Tanggal yang dipilih
    async function loadAgendaDetailsAndPopulateDropdown() {
        const selectedDate = tanggalAgendaDropdown.value;
        console.log("Tanggal yang dipilih:", selectedDate); // Debugging

        nomorAgendaDropdown.innerHTML = '<option value="">-- Memuat Nomor Agenda... --</option>';
        nomorAgendaDropdown.disabled = true; // Dinonaktifkan secara default
        perihalAgendaInput.value = '';
        waktuMulaiInput.value = '';
        idAgendaSelectedHidden.value = '';
        nomorAgendaDisplayTextHidden.value = '';

        fetchedAgendas = []; // Reset data agendas yang sudah di-fetch

        // BARU: Jika ini mode edit dengan peserta, isi langsung dari nilai PHP
        // Logika untuk isNewInputFromAgendaJS TIDAK lagi ada di sini,
        // karena itu akan memungkinkan AJAX
        if (isEditModeWithParticipantsJS) {
            console.log("Mode Edit dengan Peserta: Memulihkan detail agenda dari nilai PHP.");
            
            nomorAgendaDropdown.innerHTML = '<option value="' + "<?php echo htmlspecialchars($nomor_agenda_form_selected_id); ?>" + '" selected>' + "<?php echo htmlspecialchars($nomor_agenda_form_text); ?>" + '</option>';
            nomorAgendaDropdown.disabled = true;

            perihalAgendaInput.value = "<?php echo htmlspecialchars($perihal_agenda_form); ?>";
            waktuMulaiInput.value = "<?php echo htmlspecialchars($waktu_mulai_form); ?>";
            idAgendaSelectedHidden.value = "<?php echo htmlspecialchars($nomor_agenda_form_selected_id); ?>";
            nomorAgendaDisplayTextHidden.value = "<?php echo htmlspecialchars($nomor_agenda_form_text); ?>";
            
            return; // Hentikan fungsi karena tidak perlu AJAX atau pemrosesan lebih lanjut
        }

        // Ini adalah bagian yang akan berjalan untuk input baru biasa DAN input baru dari agenda
        if (selectedDate) {
            try {
                console.log("Mengirim AJAX request ke get_agenda_details.php untuk detail agenda...");
                const response = await fetch('get_agenda_details.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        tanggal: selectedDate,
                        kategori: kategoriRapat
                    })
                });

                const rawResponseText = await response.text();
                console.log("Raw response from get_agenda_details.php:", rawResponseText);

                const agendas = JSON.parse(rawResponseText);
                console.log("Parsed JSON:", agendas);

                fetchedAgendas = agendas;

                nomorAgendaDropdown.innerHTML = '<option value="">-- Pilih Nomor Agenda --</option>';
                if (fetchedAgendas.length > 0) {
                    fetchedAgendas.forEach(agenda => {
                        const option = document.createElement('option');
                        option.value = agenda.id;
                        option.textContent = agenda.nomor_undangan;
                        nomorAgendaDropdown.appendChild(option);
                    });
                    nomorAgendaDropdown.disabled = false; // Aktifkan dropdown
                } else {
                    nomorAgendaDropdown.innerHTML = '<option value="">-- Tidak ada agenda untuk tanggal ini --</option>';
                }

                // Setelah dropdown terisi, coba pre-select jika ada nilai dari PHP (mode edit atau validasi gagal)
                // Ini akan bekerja untuk input baru dari agenda dan validasi gagal.
                if (initialSelectedAgendaId && initialSelectedDate === selectedDate) {
                    if (nomorAgendaDropdown.querySelector(`option[value="${initialSelectedAgendaId}"]`)) {
                        nomorAgendaDropdown.value = initialSelectedAgendaId;
                        populatePerihalAndJam(); // Isi Perihal dan Jam Mulai
                    }
                }

            } catch (error) {
                console.error('Error loading agenda details (AJAX error or JSON parse error):', error);
                nomorAgendaDropdown.innerHTML = '<option value="">-- Gagal memuat agenda --</option>';
            }
        } else {
            console.log("Tanggal tidak dipilih, reset field agenda.");
            nomorAgendaDropdown.innerHTML = '<option value="">-- Pilih Nomor Agenda --</option>';
            nomorAgendaDropdown.disabled = true;
            perihalAgendaInput.value = '';
            waktuMulaiInput.value = '';
            idAgendaSelectedHidden.value = '';
            nomorAgendaDisplayTextHidden.value = '';
        }
    }

    // Fungsi untuk mengisi Perihal Agenda dan Jam Mulai berdasarkan pilihan Nomor Agenda
    function populatePerihalAndJam() {
        const selectedAgendaId = nomorAgendaDropdown.value;
        console.log("ID Agenda yang dipilih dari dropdown Nomor Agenda:", selectedAgendaId);

        perihalAgendaInput.value = '';
        waktuMulaiInput.value = '';
        idAgendaSelectedHidden.value = '';
        nomorAgendaDisplayTextHidden.value = ''; // Reset hidden display text

        if (selectedAgendaId) {
            const selectedAgenda = fetchedAgendas.find(agenda => agenda.id == selectedAgendaId);

            if (selectedAgenda) {
                perihalAgendaInput.value = selectedAgenda.perihal;
                waktuMulaiInput.value = selectedAgenda.jam;
                idAgendaSelectedHidden.value = selectedAgenda.id;
                nomorAgendaDisplayTextHidden.value = selectedAgenda.nomor_undangan;

                console.log("Perihal dan Jam Mulai diisi:", selectedAgenda.perihal, selectedAgenda.jam);
            } else {
                console.warn('Agenda tidak ditemukan di data yang di-fetch untuk ID ini.', selectedAgendaId);
            }
        } else {
            console.log("Nomor Agenda tidak dipilih, reset Perihal dan Jam Mulai.");
        }
    }


    // ===========================================
    // EVENT LISTENERS
    // ===========================================

    // Event listener ketika pilihan Tanggal Agenda berubah
    tanggalAgendaDropdown.addEventListener('change', loadAgendaDetailsAndPopulateDropdown);

    // Event listener ketika pilihan Nomor Agenda berubah
    nomorAgendaDropdown.addEventListener('change', populatePerihalAndJam);


    // Saat halaman pertama kali dimuat (DOMContentLoaded)
    document.addEventListener('DOMContentLoaded', () => {
        console.log("DOMContentLoaded event fired.");
        // initialSelectedDate dan initialSelectedAgendaId sudah didefinisikan di awal script
        
        // Jika ada tanggal yang sudah terpilih dari PHP (mode edit atau validasi gagal)
        // ATAU jika ini mode input baru dari agenda
        if (initialSelectedDate) { // initialSelectedDate akan terisi di kedua skenario ini
            tanggalAgendaDropdown.value = initialSelectedDate;
            console.log("Tanggal sudah terpilih saat DOMContentLoaded. Memuat detail agenda dan mencoba pre-select...");
            
            // Panggil fungsi untuk memuat agenda, dan setelah selesai (then),
            // coba pre-select Nomor Agenda dan isi Perihal/Jam Mulai
            loadAgendaDetailsAndPopulateDropdown().then(() => {
                // Hanya pre-select dan populate jika initialSelectedAgendaId ada (berarti ada agenda spesifik yang dipilih)
                if (initialSelectedAgendaId && nomorAgendaDropdown.querySelector(`option[value="${initialSelectedAgendaId}"]`)) {
                    nomorAgendaDropdown.value = initialSelectedAgendaId;
                    populatePerihalAndJam(); // Isi Perihal dan Jam Mulai
                }
            });
        }
    });

</script>

<?php
// Sertakan footer global website
include '../includes/footer.php';
?>