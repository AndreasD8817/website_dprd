<?php
$page_title = "Input Agenda Rapat"; // Judul halaman ini
include '../config/koneksi.php'; // Path relatif ke file koneksi
include '../includes/header.php'; // Path relatif ke file header
?>

<style>
    /* CSS Khusus untuk Formulir Agenda Rapat */
    .body-form-agenda { /* Class baru untuk body halaman ini jika ingin background video */
        background-color: var(--warm-blue-bg); /* Fallback jika video tidak dimuat */
        margin: 0;
        color: var(--text-color);
        line-height: 1.6;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 100px); /* Kurangi tinggi header dan footer */
        padding: 20px; /* Tambahkan padding agar tidak terlalu mepet */
    }

    /* Jika Anda ingin video latar belakang juga di halaman ini */
    /* Maka Anda harus menambahkan kembali elemen <video> di HTML ini dan CSS-nya */
    /* Untuk saat ini, kita asumsikan video hanya di homepage */

    .container {
        background-color: var(--card-bg); /* Menggunakan warna card dari main.css */
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 8px 25px var(--shadow-color);
        width: 100%;
        max-width: 550px;
        box-sizing: border-box;
    }

    h2 {
        text-align: center;
        color: var(--primary-blue);
        margin-bottom: 30px;
        font-size: 2em;
        font-weight: 600;
        letter-spacing: -0.5px;
    }

    .logo-container {
        text-align: center;
        margin-bottom: 25px;
    }

    .logo {
        max-width: 160px;
        height: auto;
        display: block;
        margin: 0 auto;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        color: var(--text-color);
        font-weight: 600;
        font-size: 0.95em;
    }

    input[type="text"],
    input[type="date"],
    input[type="time"],
    select {
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

    input[type="text"]:focus,
    input[type="date"]:focus,
    input[type="time"]:focus,
    select:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        outline: none;
        background-color: rgba(255, 255, 255, 0.9);
    }

    select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23333' class='bi bi-chevron-down' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 16px;
        padding-right: 30px;
    }

    input[type="submit"] {
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

    input[type="submit"]:hover {
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
        border: 1px solid #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

<div class="body-form-agenda">
    <div class="container">
        <div class="logo-container">
            <img src="../images/logo_dprd.png" alt="Logo DPRD Kota Surabaya" class="logo">
        </div>
        <h2>Input Agenda Rapat DPRD Kota Surabaya</h2>

        <?php
        // ... (Kode PHP untuk memproses form input agenda Anda) ...
        // Bagian ini adalah kode PHP yang sebelumnya ada di index.php Anda,
        // dimulai dari $message = ''; hingga $conn->close();
        // Saya asumsikan Anda sudah memiliki kode ini dari percakapan sebelumnya.

        // Jika Anda ingin kode PHP lengkapnya di sini, saya bisa menempelkannya.
        // Untuk saat ini, saya hanya akan menunjukkan bagaimana ia diintegrasikan.
        
        $message = ''; // Variabel untuk menyimpan pesan sukses/gagal
        // Inisialisasi variabel untuk menampung nilai input, agar form tidak kosong jika error validasi
        $perihal = '';
        $nomor_undangan = '';
        $kategori = '';
        $tanggal = '';
        $jam = '';

        // Cek apakah form sudah disubmit
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Mengambil data dari form dan membersihkan input
            $perihal = htmlspecialchars(trim($_POST['perihal_agenda']));
            $nomor_undangan = htmlspecialchars(trim($_POST['nomor_undangan']));
            $kategori = htmlspecialchars(trim($_POST['kategori_agenda']));
            $tanggal = htmlspecialchars(trim($_POST['tanggal_agenda']));
            $jam = htmlspecialchars(trim($_POST['jam_agenda']));

            // Validasi input wajib isi
            if (empty($perihal) || empty($nomor_undangan) || empty($kategori) || empty($tanggal) || empty($jam)) {
                $message = '<div class="message error">Semua kolom wajib diisi!</div>';
            } else {
                $stmt = $conn->prepare("INSERT INTO agenda_rapat (perihal, nomor_undangan, kategori, tanggal, jam) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $perihal, $nomor_undangan, $kategori, $tanggal, $jam);

                if ($stmt->execute()) {
                    $message = '<div class="message success">Data agenda rapat berhasil disimpan!</div>';
                    $perihal = '';
                    $nomor_undangan = '';
                    $kategori = '';
                    $tanggal = '';
                    $jam = '';
                } else {
                    $message = '<div class="message error">Error: ' . $stmt->error . '</div>';
                }
                $stmt->close();
            }
        }
        // Pastikan koneksi ditutup hanya jika $conn ada dan valid
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->close();
        }
        ?>

        <?php echo $message; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="perihal_agenda">Perihal Agenda Rapat DPRD:</label>
                <input type="text" id="perihal_agenda" name="perihal_agenda" required value="<?php echo htmlspecialchars($perihal); ?>">
            </div>

            <div class="form-group">
                <label for="nomor_undangan">Nomor Agenda Undangan Rapat DPRD:</label>
                <input type="text" id="nomor_undangan" name="nomor_undangan" required value="<?php echo htmlspecialchars($nomor_undangan); ?>">
            </div>

            <div class="form-group">
                <label for="kategori_agenda">Kategori Agenda Rapat DPRD:</label>
                <select id="kategori_agenda" name="kategori_agenda" required>
                    <option value="">-- Pilih Kategori --</option>
                    <option value="Komisi A" <?php echo ($kategori == 'Komisi A') ? 'selected' : ''; ?>>Komisi A</option>
                    <option value="Komisi B" <?php echo ($kategori == 'Komisi B') ? 'selected' : ''; ?>>Komisi B</option>
                    <option value="Komisi C" <?php echo ($kategori == 'Komisi C') ? 'selected' : ''; ?>>Komisi C</option>
                    <option value="Komisi D" <?php echo ($kategori == 'Komisi D') ? 'selected' : ''; ?>>Komisi D</option>
                </select>
            </div>

            <div class="form-group">
                <label for="tanggal_agenda">Tanggal Agenda Rapat DPRD:</label>
                <input type="date" id="tanggal_agenda" name="tanggal_agenda" required value="<?php echo htmlspecialchars($tanggal); ?>">
            </div>

            <div class="form-group">
                <label for="jam_agenda">Jam Agenda Rapat DPRD:</label>
                <input type="time" id="jam_agenda" name="jam_agenda" required value="<?php echo htmlspecialchars($jam); ?>">
            </div>

            <input type="submit" value="Simpan Agenda">
        </form>
    </div>
</div> <?php
include '../includes/footer.php'; // Path relatif ke file footer
?>