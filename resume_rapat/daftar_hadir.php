<?php
// Pastikan koneksi database tersedia
include '../config/koneksi.php'; // Path relatif dari resume_rapat/ ke config/

// Ambil ID Resume atau Kategori dari URL
$id_resume = isset($_GET['id_resume']) ? htmlspecialchars($_GET['id_resume']) : null;
$kategori_dari_url = isset($_GET['kategori']) ? htmlspecialchars($_GET['kategori']) : null;

$page_title = "Daftar Hadir Rapat - DPRD Kota Surabaya"; // Judul default

// Inisialisasi variabel untuk menampung data form input peserta (jika mode input)
$nama_peserta_form = '';
$jabatan_instansi_form = '';
$keterangan_form = '';
$message = ''; // Variabel untuk pesan sukses/error

// Variabel untuk menyimpan hasil query daftar resume jika dalam mode kategori
$result_resume_list = null; // Inisialisasi null

// --- LOGIKA UTAMA: Tentukan apakah menampilkan daftar rapat atau form input daftar hadir ---
if ($id_resume) {
    // ===========================================
    // MODE: MENAMPILKAN FORM INPUT DAFTAR HADIR UNTUK RESUME TERTENTU
    // ===========================================

    // --- Mengambil Detail Rapat (Resume) yang Terkait ---
    $tanggal_rapat = '';
    $waktu_rapat = '';
    $tempat_rapat = '';
    $kegiatan_rapat = '';
    $nomor_agenda_rapat = '';
    $perihal_agenda_rapat = '';
    $kategori_rapat_display = '';

    $stmt_rapat_detail = $conn->prepare("
        SELECT
            rr.tanggal_agenda,
            rr.waktu_mulai,
            rr.waktu_selesai,
            rr.tempat,
            rr.kegiatan,
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
    $stmt_rapat_detail->bind_param("i", $id_resume);
    $stmt_rapat_detail->execute();
    $result_rapat_detail = $stmt_rapat_detail->get_result();

    if ($result_rapat_detail->num_rows > 0) {
        $detail = $result_rapat_detail->fetch_assoc();
        $tanggal_rapat = date('d F Y', strtotime($detail['tanggal_agenda']));
        $waktu_rapat = date('H:i', strtotime($detail['waktu_mulai'])) . ' - ' . date('H:i', strtotime($detail['waktu_selesai']));
        $tempat_rapat = $detail['tempat'];
        $kegiatan_rapat = $detail['kegiatan'];
        $nomor_agenda_rapat = $detail['nomor_undangan'] ?? 'N/A';
        $perihal_agenda_rapat = $detail['perihal'] ?? 'N/A';
        $kategori_rapat_display = $detail['kategori_rapat'];
        $page_title = "Daftar Hadir Rapat - " . $kategori_rapat_display . " - " . $tanggal_rapat;

        // --- PROSES FORM SUBMISSION (Menambah Peserta Hadir) ---
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $id_resume_submitted = htmlspecialchars(trim($_POST['id_resume_hidden']));
            $nama_peserta_submitted = htmlspecialchars(trim($_POST['nama_peserta']));
            $jabatan_instansi_submitted = htmlspecialchars(trim($_POST['jabatan_instansi']));
            $keterangan_submitted = htmlspecialchars(trim($_POST['keterangan']));
            $signature_data_submitted = $_POST['signature_data'] ?? ''; // Ambil data tanda tangan

            if (empty($nama_peserta_submitted)) {
                $message = '<div class="message error">Nama Peserta wajib diisi!</div>';
                $nama_peserta_form = $nama_peserta_submitted;
                $jabatan_instansi_form = $jabatan_instansi_submitted;
                $keterangan_form = $keterangan_submitted;
            } else {
                $tanda_tangan_path = null; // Default null jika tidak ada tanda tangan
                if (!empty($signature_data_submitted)) {
                    // Proses penyimpanan tanda tangan
                    $upload_dir_base = __DIR__ . '/../uploads/signatures/'; // Path fisik di server
                    $upload_dir_web_relative = 'uploads/signatures/'; // Path relatif untuk disimpan di DB

                    // Pastikan folder 'uploads' dan 'signatures' sudah ada dan punya izin tulis
                    if (!is_dir($upload_dir_base)) {
                        mkdir($upload_dir_base, 0777, true); // Buat folder jika belum ada
                    }

                    // Generate nama file unik
                    $file_name = uniqid('ttd_') . '.png';
                    $file_path_full = $upload_dir_base . $file_name;

                    // Data tanda tangan adalah Base64 (misal: data:image/png;base64,iVBORw0KGgoAAA...)
                    // Kita perlu menghapus bagian "data:image/png;base64,"
                    $data_base64 = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $signature_data_submitted));

                    if ($data_base64 === false) {
                        $message = '<div class="message error">Data tanda tangan tidak valid!</div>';
                        error_log("Invalid Base64 data for signature: " . $signature_data_submitted);
                    } else if (file_put_contents($file_path_full, $data_base64)) {
                        // Simpan path relatif dari folder dprd_website/ ke database
                        $tanda_tangan_path = $upload_dir_web_relative . $file_name;
                    } else {
                        $message = '<div class="message error">Gagal menyimpan file tanda tangan di server!</div>';
                        error_log("Failed to save signature file to: " . $file_path_full);
                    }
                }

                // Persiapkan query INSERT ke tabel daftar_hadir_rapat
                // Tambahkan kolom tanda_tangan_path
                $stmt_insert_peserta = $conn->prepare("INSERT INTO daftar_hadir_rapat (id_resume, nama_peserta, jabatan_instansi, keterangan, tanda_tangan_path) VALUES (?, ?, ?, ?, ?)");
                $stmt_insert_peserta->bind_param("issss", $id_resume_submitted, $nama_peserta_submitted, $jabatan_instansi_submitted, $keterangan_submitted, $tanda_tangan_path);

                if ($stmt_insert_peserta->execute()) {
                    $message = '<div class="message success">Peserta berhasil ditambahkan!</div>';
                    header("Location: daftar_hadir.php?id_resume=" . $id_resume_submitted . "&status=added");
                    exit();
                } else {
                    $message = '<div class="message error">Error menambahkan peserta: ' . $stmt_insert_peserta->error . '</div>';
                }
                $stmt_insert_peserta->close();
            }
        }

    } else {
        // Jika ID resume tidak ditemukan, redirect kembali ke halaman kategori resume
        header("Location: index.php?status=resume_not_found");
        exit();
    }
    $stmt_rapat_detail->close(); // Tutup statement setelah mengambil detail rapat

} else if ($kategori_dari_url) {
    // ===========================================
    // MODE: MENAMPILKAN DAFTAR RESUME UNTUK KATEGORI TERTENTU
    // ===========================================
    $page_title = "Pilih Rapat untuk Daftar Hadir - " . $kategori_dari_url;

    // Query untuk mengambil semua data resume dari tabel resume_rapat
    $stmt_resume_list = $conn->prepare("
        SELECT
            rr.id AS resume_id,
            rr.tanggal_agenda,
            rr.waktu_mulai,
            rr.waktu_selesai,
            rr.tempat,
            rr.kegiatan,
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
    $stmt_resume_list->bind_param("s", $kategori_dari_url);
    $stmt_resume_list->execute();
    $result_resume_list = $stmt_resume_list->get_result();
    // Biarkan $result_resume_list tetap terbuka, akan digunakan di bagian HTML
} else {
    // ===========================================
    // MODE: TIDAK ADA PARAMETER YANG VALID (redirect ke kategori resume)
    // ===========================================
    header("Location: index.php?status=no_category_selected");
    exit();
}

// ====================================================================
// LOKASI PENUTUPAN KONEKSI DATABASE YANG BENAR UNTUK KEDUA MODE
// Diletakkan setelah SEMUA operasi database (SELECT/INSERT) selesai.
// ====================================================================
// Penutupan koneksi hanya dilakukan jika $conn terdefinisi dan merupakan objek mysqli
// Ini harus menjadi bagian terakhir dari kode PHP sebelum HTML atau footer di-include


// Sertakan header global website
include '../includes/header.php';
?>

<style>
    /* Styling umum container */
    .hadir-container {
        background-color: var(--card-bg);
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 8px 25px var(--shadow-color);
        width: 100%;
        max-width: 900px;
        margin: 40px auto;
        box-sizing: border-box;
    }

    .hadir-container h2 {
        text-align: center;
        color: var(--primary-blue);
        margin-bottom: 30px;
        font-size: 2.2em;
        font-weight: 700;
    }

    /* Styling detail rapat (read-only info) */
    .rapat-details {
        background-color: var(--light-blue);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .rapat-details p {
        margin-bottom: 8px;
        font-size: 0.95em;
    }

    .rapat-details p strong {
        color: var(--dark-blue);
    }

    /* Styling form input peserta */
    .form-group-hadir {
        margin-bottom: 20px;
    }

    .form-group-hadir label {
        display: block;
        margin-bottom: 8px;
        color: var(--text-color);
        font-weight: 600;
        font-size: 0.95em;
    }

    .form-group-hadir input[type="text"],
    .form-group-hadir textarea {
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

    .form-group-hadir input[type="text"]:focus,
    .form-group-hadir textarea:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        outline: none;
    }

    .submit-button {
        background-color: #28a745; /* Hijau untuk tombol tambah */
        color: white;
        padding: 14px 25px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        width: 100%;
        font-size: 1.1em;
        font-weight: 600;
        margin-top: 20px;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .submit-button:hover {
        background-color: #218838;
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
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

    /* Styling tabel daftar peserta */
    .peserta-table-section {
        margin-top: 40px;
        border-top: 1px solid #eee;
        padding-top: 30px;
    }
    .peserta-table-section h3 {
        text-align: center;
        color: var(--primary-blue);
        margin-bottom: 25px;
        font-size: 1.8em;
    }
    .peserta-table {
        width: 100%;
        border-collapse: collapse;
    }
    .peserta-table th, .peserta-table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
        font-size: 0.9em;
    }
    .peserta-table th {
        background-color: var(--primary-blue);
        color: white;
        font-weight: 600;
    }
    .peserta-table tr:nth-child(even) {
        background-color: #f2f2f2;
    }
    .peserta-table .action-delete-btn {
        background-color: #dc3545; /* Merah */
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        text-decoration: none;
        transition: background-color 0.3s ease;
    }
    .peserta-table .action-delete-btn:hover {
        background-color: #c82333;
    }
    .text-center {
        text-align: center;
    }
    .table-responsive {
        overflow-x: auto; /* Untuk responsivitas tabel */
    }

    /* Styling untuk daftar resume saat mode kategori */
    .resume-list-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .resume-list-table th, .resume-list-table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
    }
    .resume-list-table th {
        background-color: var(--primary-blue);
        color: white;
        font-weight: 600;
    }
    .resume-list-table tr:nth-child(even) {
        background-color: #f2f2f2;
    }
    .resume-list-table .actions a {
        display: inline-block;
        padding: 6px 10px;
        margin: 3px;
        text-decoration: none;
        color: white;
        border-radius: 4px;
        transition: background-color 0.3s ease;
    }
    .resume-list-table .actions .select-rapat-btn {
        background-color: #17a2b8; /* Biru kehijauan */
    }
    .resume-list-table .actions .select-rapat-btn:hover {
        background-color: #138496;
    }

    /* Styling khusus untuk area tanda tangan */
    #signatureCanvas {
        border: 1px solid var(--border-color);
        border-radius: 6px;
        background-color: #fcfcfc; /* Latar belakang canvas */
        touch-action: none; /* Penting untuk menghindari scroll saat menggambar di mobile */
    }

    #clearSignatureBtn {
        background-color: #6c757d; /* Abu-abu */
        color: white;
        padding: 8px 15px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.9em;
        margin-top: 10px;
        transition: background-color 0.3s ease;
    }
    #clearSignatureBtn:hover {
        background-color: #5a6268;
    }

    /* Tambahan styling untuk kolom tanda tangan di tabel */
    .peserta-table .signature-cell img {
        max-width: 100px; /* Ukuran gambar tanda tangan di tabel */
        height: auto;
        display: block;
        margin: 0 auto; /* Tengah gambar */
        border: 1px solid #eee;
    }
</style>

<div class="hadir-container">
    <?php if ($id_resume) : ?>
        <h2>Daftar Hadir Rapat</h2>

        <?php echo $message; ?>

        <div class="rapat-details">
            <p><strong>Kategori:</strong> <?php echo htmlspecialchars($kategori_rapat_display); ?></p>
            <p><strong>Tanggal:</strong> <?php echo htmlspecialchars($tanggal_rapat); ?></p>
            <p><strong>Nomor Agenda:</strong> <?php echo htmlspecialchars($nomor_agenda_rapat); ?></p>
            <p><strong>Perihal:</strong> <?php echo htmlspecialchars($perihal_agenda_rapat); ?></p>
            <p><strong>Waktu:</strong> <?php echo htmlspecialchars($waktu_rapat); ?></p>
            <p><strong>Tempat:</strong> <?php echo htmlspecialchars($tempat_rapat); ?></p>
            <p><strong>Kegiatan:</strong> <?php echo htmlspecialchars($kegiatan_rapat); ?></p>
        </div>

        <form action="" method="POST">
            <input type="hidden" name="id_resume_hidden" value="<?php echo htmlspecialchars($id_resume); ?>">

            <div class="form-group-hadir">
                <label for="nama_peserta">Nama Peserta:</label>
                <input type="text" id="nama_peserta" name="nama_peserta" required value="<?php echo htmlspecialchars($nama_peserta_form); ?>">
            </div>

            <div class="form-group-hadir">
                <label for="jabatan_instansi">Jabatan / Instansi:</label>
                <input type="text" id="jabatan_instansi" name="jabatan_instansi" value="<?php echo htmlspecialchars($jabatan_instansi_form); ?>">
            </div>
            
            <div class="form-group-hadir">
                <label for="keterangan">Keterangan (Opsional):</label>
                <input type="text" id="keterangan" name="keterangan" value="<?php echo htmlspecialchars($keterangan_form); ?>">
            </div>

            <div class="form-group-hadir">
                <label>Tanda Tangan:</label>
                <canvas id="signatureCanvas" width="400" height="150"></canvas>
                <button type="button" id="clearSignatureBtn">Hapus Tanda Tangan</button>
                <input type="hidden" name="signature_data" id="signatureData">
            </div>

            <button type="submit" class="submit-button">Tambah Peserta</button>
        </form>

        <div class="peserta-table-section">
            <h3>Daftar Peserta Hadir</h3>
            <div class="table-responsive">
                <table class="peserta-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Peserta</th>
                            <th>Jabatan / Instansi</th>
                            <th>Waktu Hadir</th>
                            <th>Keterangan</th>
                            <th>Tanda Tangan</th> <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Query untuk mengambil semua peserta hadir untuk id_resume ini
                        // Tambahkan tanda_tangan_path di SELECT
                        $stmt_peserta = $conn->prepare("SELECT id, nama_peserta, jabatan_instansi, waktu_hadir, keterangan, tanda_tangan_path FROM daftar_hadir_rapat WHERE id_resume = ? ORDER BY waktu_hadir ASC");
                        $stmt_peserta->bind_param("i", $id_resume);
                        $stmt_peserta->execute();
                        $result_peserta = $stmt_peserta->get_result();

                        if ($result_peserta->num_rows > 0) {
                            $counter = 1;
                            while($row_peserta = $result_peserta->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $counter++ . "</td>";
                                echo "<td>" . htmlspecialchars($row_peserta['nama_peserta']) . "</td>";
                                echo "<td>" . htmlspecialchars($row_peserta['jabatan_instansi']) . "</td>";
                                echo "<td>" . htmlspecialchars(date('d F Y H:i', strtotime($row_peserta['waktu_hadir']))) . "</td>";
                                echo "<td>" . htmlspecialchars($row_peserta['keterangan']) . "</td>";
                                // Tampilan tanda tangan
                                echo "<td class='signature-cell'>";
                                if (!empty($row_peserta['tanda_tangan_path'])) {
                                    echo "<img src='/dprd_website/" . htmlspecialchars($row_peserta['tanda_tangan_path']) . "' alt='Tanda Tangan'>";
                                } else {
                                    echo "Tidak ada";
                                }
                                echo "</td>";
                                echo "<td class='text-center'>";
                                // Link Hapus Peserta
                                echo "<a href='delete_peserta.php?id=" . $row_peserta['id'] . "&id_resume=" . $id_resume . "' class='action-delete-btn' onclick='return confirm(\"Apakah Anda yakin ingin menghapus peserta ini?\");'>Hapus</a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center'>Belum ada peserta hadir.</td></tr>";
                        }
                        // Penting: Tutup statement setelah semua data diambil dan ditampilkan
                        $stmt_peserta->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <a href="index.php" style="display: block; text-align: center; margin-top: 30px; color: var(--primary-blue); text-decoration: none; font-weight: 500;">Kembali ke Kategori Rapat</a>

    <?php else : // Jika tidak ada id_resume, berarti tampilkan daftar resume untuk kategori ?>
        <h2>Pilih Rapat untuk Daftar Hadir Kategori: <?php echo htmlspecialchars($kategori_dari_url); ?></h2>

        <?php
        if ($result_resume_list->num_rows > 0) :
        ?>
        <div class="table-responsive">
            <table class="resume-list-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal Agenda</th>
                        <th>Nomor Agenda</th>
                        <th>Perihal Agenda</th>
                        <th>Waktu</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $counter_list = 1;
                    while($row_list = $result_resume_list->fetch_assoc()) :
                    ?>
                    <tr>
                        <td><?php echo $counter_list++; ?></td>
                        <td><?php echo htmlspecialchars(date('d F Y', strtotime($row_list["tanggal_agenda"]))); ?></td>
                        <td><?php echo htmlspecialchars($row_list["nomor_undangan"] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row_list["perihal"] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars(date('H:i', strtotime($row_list["waktu_mulai"]))) . ' - ' . htmlspecialchars(date('H:i', strtotime($row_list["waktu_selesai"]))); ?></td>
                        <td class="actions">
                            <a href="daftar_hadir.php?id_resume=<?php echo htmlspecialchars($row_list["resume_id"]); ?>" class="select-rapat-btn">Pilih Rapat Ini</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else : ?>
            <p style="text-align: center; margin-top: 20px; color: var(--light-text);">Belum ada resume rapat untuk kategori ini. <a href="input_resume.php?kategori=<?php echo htmlspecialchars($kategori_dari_url); ?>">Input resume baru</a>.</p>
        <?php endif; ?>
        <a href="index.php" style="display: block; text-align: center; margin-top: 30px; color: var(--primary-blue); text-decoration: none; font-weight: 500;">Kembali ke Kategori Rapat</a>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

<script>
    // Hanya inisialisasi Signature Pad jika elemen canvas ada (berarti di mode input daftar hadir)
    if (document.getElementById('signatureCanvas')) {
        const signatureCanvas = document.getElementById('signatureCanvas');
        // Penting: Sesuaikan ukuran canvas secara dinamis
        // https://github.com/szimek/signature_pad#handling-high-dpi-screens-and-resizing
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        signatureCanvas.width = signatureCanvas.offsetWidth * ratio;
        signatureCanvas.height = signatureCanvas.offsetHeight * ratio;
        signatureCanvas.getContext("2d").scale(ratio, ratio);

        const signaturePad = new SignaturePad(signatureCanvas, {
            backgroundColor: 'rgb(255, 255, 255)' // Latar belakang putih untuk tanda tangan
        });
        
        const clearSignatureBtn = document.getElementById('clearSignatureBtn');
        const signatureDataInput = document.getElementById('signatureData');
        const daftarHadirForm = document.querySelector('form'); // Ambil form input peserta

        // Event listener untuk tombol hapus tanda tangan
        clearSignatureBtn.addEventListener('click', () => {
            signaturePad.clear(); // Bersihkan tanda tangan di kanvas
            signatureDataInput.value = ''; // Mengosongkan hidden input
        });

        // Event listener saat form disubmit
        daftarHadirForm.addEventListener('submit', (event) => {
            if (signaturePad.isEmpty()) {
                // Jika tanda tangan kosong, set hidden input menjadi kosong string
                // Anda bisa tambahkan validasi di sini jika tanda tangan wajib
                // event.preventDefault(); alert("Tanda tangan wajib diisi!");
                signatureDataInput.value = '';
                console.log("Tanda tangan kosong, tidak akan dikirim.");
            } else {
                // Jika ada tanda tangan, konversi ke Base64 PNG dan simpan di hidden input
                signatureDataInput.value = signaturePad.toDataURL('image/png');
                console.log("Tanda tangan dikonversi ke Base64 dan disimpan di hidden input.");
            }
        });

        // Optional: Atur ulang ukuran canvas saat jendela di-resize (penting untuk responsivitas)
        function resizeCanvas() {
            // Simpan gambar yang ada sebelum di-resize
            const imageData = signaturePad.toDataURL('image/png');
            
            // Resize kanvas
            const currentRatio = signatureCanvas.width / signatureCanvas.offsetWidth;
            if (currentRatio !== ratio) { // Hanya resize jika rasio berubah atau perlu disesuaikan
                 const newRatio = Math.max(window.devicePixelRatio || 1, 1);
                 signatureCanvas.width = signatureCanvas.offsetWidth * newRatio;
                 signatureCanvas.height = signatureCanvas.offsetHeight * newRatio;
                 signatureCanvas.getContext("2d").scale(newRatio, newRatio);
            }

            signaturePad.clear();
            // Muat kembali gambar jika sebelumnya ada
            if (imageData) {
                signaturePad.fromDataURL(imageData);
            }
        }
        window.addEventListener('resize', resizeCanvas);
        resizeCanvas(); // Panggil saat pertama kali dimuat
    }
</script>

<?php
// Sertakan footer global website
include '../includes/footer.php';
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>