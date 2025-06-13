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
$nomor_agenda_form_selected_id = ''; // Baru: untuk mengembalikan ID agenda terpilih jika validasi gagal
$perihal_agenda_form = ''; // Untuk mengembalikan nilai perihal jika validasi gagal
$nomor_agenda_form_text = ''; // Untuk mengembalikan nilai nomor agenda jika validasi gagal

$message = ''; // Variabel untuk pesan sukses/error

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

// --- PROSES FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form yang disubmit
    $kategori_rapat_submitted = htmlspecialchars(trim($_POST['kategori_rapat']));
    // Sekarang kita mengambil ID agenda yang dipilih dari dropdown Nomor Agenda
    $id_agenda_selected_submitted = htmlspecialchars(trim($_POST['nomor_agenda_selected_id'])); // Perhatikan name yang baru

    $tanggal_agenda_submitted = htmlspecialchars(trim($_POST['tanggal_agenda_selected']));
    $waktu_mulai_submitted = htmlspecialchars(trim($_POST['waktu_mulai'])); // Jam mulai (readonly, diambil dari DB)
    $waktu_selesai_submitted = htmlspecialchars(trim($_POST['waktu_selesai'])); // Jam selesai (manual input)
    $tempat_submitted = htmlspecialchars(trim($_POST['tempat']));
    $kegiatan_submitted = htmlspecialchars(trim($_POST['kegiatan']));
    $kesimpulan_submitted = $_POST['kesimpulan']; // Konten TinyMCE

    // Untuk mengembalikan nilai ke form jika validasi gagal
    $nomor_agenda_selected_text_submitted = htmlspecialchars(trim($_POST['nomor_agenda_display'])); // Ambil teks dari input readonly
    $perihal_agenda_selected_submitted = htmlspecialchars(trim($_POST['perihal_agenda_display'])); // Ambil teks dari input readonly


    // Validasi input wajib. Pastikan semua field penting terisi.
    if (empty($id_agenda_selected_submitted) || empty($tanggal_agenda_submitted) || empty($waktu_mulai_submitted) || empty($waktu_selesai_submitted) || empty($tempat_submitted) || empty($kegiatan_submitted) || empty($kesimpulan_submitted)) {
        $message = '<div class="message error">Semua kolom wajib diisi!</div>';

        // Kembalikan nilai ke form agar tidak hilang saat validasi gagal
        $tanggal_agenda_form = $tanggal_agenda_submitted;
        $nomor_agenda_form_selected_id = $id_agenda_selected_submitted; // ID yang dipilih
        $nomor_agenda_form_text = $nomor_agenda_selected_text_submitted; // Teks Nomor Agenda
        $perihal_agenda_form = $perihal_agenda_selected_submitted; // Teks Perihal Agenda
        $waktu_mulai_form = $waktu_mulai_submitted;
        $waktu_selesai_form = $waktu_selesai_submitted;
        $tempat_form = $tempat_submitted;
        $kegiatan_form = $kegiatan_submitted;
        $kesimpulan_form = $kesimpulan_submitted;

    } else {
        // Persiapkan query INSERT ke tabel resume_rapat
        // Kolom id_agenda akan menyimpan ID dari agenda_rapat yang dipilih
        $stmt_insert = $conn->prepare("INSERT INTO resume_rapat (id_agenda, kategori_rapat, tanggal_agenda, waktu_mulai, waktu_selesai, tempat, kegiatan, kesimpulan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("isssssss",
            $id_agenda_selected_submitted,
            $kategori_rapat_submitted,
            $tanggal_agenda_submitted,
            $waktu_mulai_submitted,
            $waktu_selesai_submitted,
            $tempat_submitted,
            $kegiatan_submitted,
            $kesimpulan_submitted
        );

        // Eksekusi query
        if ($stmt_insert->execute()) {
            $message = '<div class="message success">Resume rapat berhasil disimpan!</div>';
            // Kosongkan form setelah sukses untuk input baru
            $tanggal_agenda_form = '';
            $waktu_mulai_form = '';
            $waktu_selesai_form = '';
            $tempat_form = '';
            $kegiatan_form = '';
            $kesimpulan_form = '';
            $nomor_agenda_form_selected_id = '';
            $perihal_agenda_form = '';
            $nomor_agenda_form_text = '';
        } else {
            $message = '<div class="message error">Error: ' . $stmt_insert->error . '</div>';
        }
        $stmt_insert->close();
    }
}

// Tutup koneksi database di akhir skrip PHP, sebelum HTML dimulai
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// Set judul halaman yang akan ditampilkan di tab browser
$page_title = "Tulis Notulensi Rapat " . $kategori_rapat_url . " - DPRD Kota Surabaya";

// Sertakan header global website
include '../includes/header.php'; // Path relatif dari resume_rapat/ ke includes/
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
    .time-inputs input[readonly] { /* Style untuk input readonly */
        background-color: #f0f0f0; /* Warna abu-abu yang menunjukkan tidak bisa diedit */
        cursor: not-allowed; /* Kursor tidak diizinkan */
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
</style>

<div class="resume-input-container">
    <h2>Tulis Notulensi Rapat <?php echo $kategori_rapat_url; ?></h2>

    <?php echo $message; // Menampilkan pesan sukses/error ?>

    <form action="" method="POST">
        <input type="hidden" name="kategori_rapat" value="<?php echo htmlspecialchars($kategori_rapat_url); ?>">
        <input type="hidden" id="id_agenda_selected_hidden" name="nomor_agenda_selected_id" value="<?php echo htmlspecialchars($nomor_agenda_form_selected_id); ?>">

        <div class="form-group-resume">
            <label for="tanggal_agenda_dropdown">Tanggal Agenda:</label>
            <select id="tanggal_agenda_dropdown" name="tanggal_agenda_selected" required>
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
            <select id="nomor_agenda_dropdown" name="nomor_agenda_dropdown_selected" required disabled>
                <option value="">-- Pilih Nomor Agenda --</option>
                <?php
                // Jika validasi gagal, dan ada ID agenda yang dipilih, coba isi kembali opsi ini
                // Ini akan memerlukan AJAX call lagi atau data agendas yang sudah di-cache
                // Untuk kesederhanaan, saat validasi gagal, pengguna harus memilih ulang nomor agenda.
                // Atau, Anda bisa panggil JavaScript 'loadAgendaDetails' dengan tanggal terpilih.
                ?>
            </select>
            <input type="hidden" id="nomor_agenda_display" name="nomor_agenda_display" value="<?php echo htmlspecialchars($nomor_agenda_form_text); ?>">
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
                <input type="time" id="waktu_selesai" name="waktu_selesai" required value="<?php echo htmlspecialchars($waktu_selesai_form); ?>">
            </div>
        </div>

        <div class="form-group-resume">
            <label for="tempat">Tempat:</label>
            <input type="text" id="tempat" name="tempat" placeholder="Contoh: Ruang Banggar Lt 2 Gedung DPRD Kota Surabaya" required value="<?php echo htmlspecialchars($tempat_form); ?>">
        </div>

        <div class="form-group-resume">
            <label for="kegiatan">Kegiatan:</label>
            <input type="text" id="kegiatan" name="kegiatan" placeholder="Contoh: Rapat Forum Perangkat Daerah dan Forum Konsultasi Publik" required value="<?php echo htmlspecialchars($kegiatan_form); ?>">
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
    const nomorAgendaDropdown = document.getElementById('nomor_agenda_dropdown'); // Baru: Dropdown Nomor Agenda
    const perihalAgendaInput = document.getElementById('perihal_agenda_text'); // Input untuk Perihal Agenda
    const waktuMulaiInput = document.getElementById('waktu_mulai'); // Input untuk Waktu Mulai (readonly)
    const idAgendaSelectedHidden = document.getElementById('id_agenda_selected_hidden'); // Hidden input untuk menyimpan ID Agenda terpilih

    // Variabel untuk menyimpan data agenda yang sudah di-fetch secara lokal
    // Ini penting agar kita tidak perlu request AJAX lagi saat memilih Nomor Agenda
    let fetchedAgendas = [];

    // Mengambil kategori rapat dari hidden input PHP (untuk filtering AJAX)
    const kategoriRapat = document.querySelector('input[name="kategori_rapat"]').value;
    console.log("Kategori Rapat (dari hidden input):", kategoriRapat); // Debugging

    // Fungsi utama untuk memuat detail agenda (Nomor, Perihal, Jam) berdasarkan Tanggal yang dipilih
    async function loadAgendaDetailsAndPopulateDropdown() {
        const selectedDate = tanggalAgendaDropdown.value;
        console.log("Tanggal yang dipilih:", selectedDate); // Debugging

        // Reset dan kosongkan semua field terkait
        nomorAgendaDropdown.innerHTML = '<option value="">-- Memuat Nomor Agenda... --</option>';
        nomorAgendaDropdown.disabled = true;
        perihalAgendaInput.value = '';
        waktuMulaiInput.value = '';
        idAgendaSelectedHidden.value = ''; // Penting: Reset hidden ID juga

        // Reset variabel agendas yang sudah di-fetch
        fetchedAgendas = [];

        if (selectedDate) {
            try {
                console.log("Mengirim AJAX request ke get_agenda_details.php untuk detail agenda..."); // Debugging
                // Mengirim permintaan POST dengan tanggal dan kategori sebagai JSON
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

                // Mengambil respons dari server sebagai teks mentah (untuk debugging)
                const rawResponseText = await response.text();
                console.log("Raw response from get_agenda_details.php:", rawResponseText); // Debugging

                // Mencoba parsing respons sebagai JSON
                const agendas = JSON.parse(rawResponseText);
                console.log("Parsed JSON:", agendas); // Debugging

                // Simpan data agendas yang di-fetch ke variabel lokal
                fetchedAgendas = agendas;

                // Isi dropdown Nomor Agenda
                nomorAgendaDropdown.innerHTML = '<option value="">-- Pilih Nomor Agenda --</option>';
                if (fetchedAgendas.length > 0) {
                    fetchedAgendas.forEach(agenda => {
                        const option = document.createElement('option');
                        // Value option adalah ID agenda, teksnya adalah Nomor Undangan
                        option.value = agenda.id;
                        option.textContent = agenda.nomor_undangan;
                        nomorAgendaDropdown.appendChild(option);
                    });
                    nomorAgendaDropdown.disabled = false; // Aktifkan dropdown
                } else {
                    nomorAgendaDropdown.innerHTML = '<option value="">-- Tidak ada agenda untuk tanggal ini --</option>';
                }

                // Jika ada nilai yang terpilih sebelumnya karena validasi gagal, coba pulihkan
                const oldNomorAgendaId = "<?php echo $nomor_agenda_form_selected_id; ?>";
                if (oldNomorAgendaId) {
                    nomorAgendaDropdown.value = oldNomorAgendaId;
                    // Setelah memilih, panggil fungsi untuk mengisi Perihal dan Jam Mulai
                    populatePerihalAndJam();
                }


            } catch (error) {
                // Menangani error (misalnya: jaringan, JSON parsing gagal)
                console.error('Error loading agenda details (AJAX error or JSON parse error):', error); // Debugging error lebih detail
                nomorAgendaDropdown.innerHTML = '<option value="">-- Gagal memuat agenda --</option>';
            }
        } else {
            // Jika tidak ada tanggal dipilih
            console.log("Tanggal tidak dipilih, reset field agenda."); // Debugging
            nomorAgendaDropdown.innerHTML = '<option value="">-- Pilih Nomor Agenda --</option>';
            nomorAgendaDropdown.disabled = true;
            perihalAgendaInput.value = '';
            waktuMulaiInput.value = '';
            idAgendaSelectedHidden.value = '';
        }
    }

    // Fungsi untuk mengisi Perihal Agenda dan Jam Mulai berdasarkan pilihan Nomor Agenda
    function populatePerihalAndJam() {
        const selectedAgendaId = nomorAgendaDropdown.value;
        console.log("ID Agenda yang dipilih dari dropdown Nomor Agenda:", selectedAgendaId); // Debugging

        perihalAgendaInput.value = ''; // Reset perihal
        waktuMulaiInput.value = ''; // Reset jam mulai
        idAgendaSelectedHidden.value = ''; // Reset hidden ID

        if (selectedAgendaId) {
            // Cari agenda yang cocok di array fetchedAgendas
            const selectedAgenda = fetchedAgendas.find(agenda => agenda.id == selectedAgendaId);

            if (selectedAgenda) {
                perihalAgendaInput.value = selectedAgenda.perihal;
                waktuMulaiInput.value = selectedAgenda.jam;
                idAgendaSelectedHidden.value = selectedAgenda.id; // Simpan ID ke hidden input

                console.log("Perihal dan Jam Mulai diisi:", selectedAgenda.perihal, selectedAgenda.jam); // Debugging
            } else {
                console.warn('Agenda tidak ditemukan di data yang di-fetch untuk ID ini.', selectedAgendaId); // Debugging
            }
        } else {
            console.log("Nomor Agenda tidak dipilih, reset Perihal dan Jam Mulai."); // Debugging
        }
    }


    // ===========================================
    // EVENT LISTENERS
    // ===========================================

    // Event listener ketika pilihan Tanggal Agenda berubah
    tanggalAgendaDropdown.addEventListener('change', loadAgendaDetailsAndPopulateDropdown);

    // Event listener ketika pilihan Nomor Agenda berubah
    nomorAgendaDropdown.addEventListener('change', populatePerihalAndJam);


    // Saat halaman pertama kali dimuat, jika sudah ada tanggal/nomor agenda yang dipilih (misal karena validasi gagal),
    // coba muat detail agenda.
    document.addEventListener('DOMContentLoaded', () => {
        console.log("DOMContentLoaded event fired."); // Debugging
        const initialSelectedDate = tanggalAgendaDropdown.value;
        const initialSelectedAgendaId = "<?php echo $nomor_agenda_form_selected_id; ?>"; // Ambil ID yang dipilih saat validasi gagal

        if (initialSelectedDate) {
            console.log("Tanggal sudah terpilih saat DOMContentLoaded. Memuat detail agenda...");
            loadAgendaDetailsAndPopulateDropdown().then(() => {
                // Setelah loadAgendaDetailsAndPopulateDropdown selesai mengisi dropdown nomor agenda,
                // barulah kita bisa mencoba memilih nilai awal jika ada
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
include '../includes/footer.php'; // Path relatif dari resume_rapat/ ke includes/
?>