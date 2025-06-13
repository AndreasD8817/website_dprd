<?php
$page_title = "Resume Rapat - DPRD Kota Surabaya"; // Judul halaman ini
include '../config/koneksi.php'; // Path relatif ke file koneksi (dari resume_rapat/ ke config/)
include '../includes/header.php'; // Path relatif ke file header (dari resume_rapat/ ke includes/)
?>

<style>
    .resume-container {
        background-color: var(--card-bg);
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 8px 25px var(--shadow-color);
        width: 100%;
        max-width: 800px; /* Atur lebar container sesuai kebutuhan */
        margin: 40px auto; /* Margin atas/bawah dan tengah otomatis */
        box-sizing: border-box;
    }

    .resume-container h2 {
        text-align: center;
        color: var(--primary-blue);
        margin-bottom: 30px;
        font-size: 2.2em;
        font-weight: 700;
    }

    .category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Kolom responsif */
        gap: 25px; /* Jarak antar kartu kategori */
        justify-content: center; /* Pusatkan grid */
    }

    .category-card {
        background-color: var(--light-blue); /* Latar belakang biru terang */
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 2px 10px var(--shadow-light);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 15px var(--shadow-medium);
        background-color: #dbeaff; /* Sedikit lebih gelap saat hover */
    }

    .category-card h3 {
        color: var(--primary-blue);
        font-size: 1.5em;
        margin-bottom: 15px;
    }

    .category-card a {
        display: inline-block;
        background-color: var(--primary-blue);
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        font-weight: 500;
        transition: background-color 0.3s ease;
    }

    .category-card a:hover {
        background-color: var(--dark-blue);
    }

    .category-card .action-buttons {
        margin-top: 15px; /* Jarak dari judul/deskripsi */
        display: flex;
        flex-direction: column; /* Tombol vertikal */
        gap: 10px; /* Jarak antar tombol */
    }

    .category-card .action-buttons a {
        display: block; /* Agar tombol mengisi lebar penuh */
        width: 100%;
        padding: 10px 15px;
        background-color: var(--primary-blue);
        color: white;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 500;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .category-card .action-buttons a:hover {
        background-color: var(--dark-blue);
        transform: translateY(-2px);
    }

    /* Warna khusus untuk tombol Daftar Hadir (Opsional) */
    .category-card .action-buttons .hadir-button {
        background-color: #28a745; /* Hijau */
    }
    .category-card .action-buttons .hadir-button:hover {
        background-color: #218838;
    }
</style>

<div class="resume-container">
    <h2>Daftar Kategori Rapat</h2> <div class="category-grid">
        <div class="category-card">
            <h3>Komisi A</h3>
            <div class="action-buttons">
                <a href="input_resume.php?kategori=Komisi A">Input Resume</a>
                <a href="daftar_hadir.php?kategori=Komisi A" class="hadir-button">Daftar Hadir</a>
                <a href="view_resume.php?kategori=Komisi A">Lihat Resume</a>
            </div>
        </div>

        <div class="category-card">
            <h3>Komisi B</h3>
            <div class="action-buttons">
                <a href="input_resume.php?kategori=Komisi B">Input Resume</a>
                <a href="daftar_hadir.php?kategori=Komisi B" class="hadir-button">Daftar Hadir</a>
                <a href="view_resume.php?kategori=Komisi B">Lihat Resume</a>
            </div>
        </div>

        <div class="category-card">
            <h3>Komisi C</h3>
            <div class="action-buttons">
                <a href="input_resume.php?kategori=Komisi C">Input Resume</a>
                <a href="daftar_hadir.php?kategori=Komisi C" class="hadir-button">Daftar Hadir</a>
                <a href="view_resume.php?kategori=Komisi C">Lihat Resume</a>
            </div>
        </div>

        <div class="category-card">
            <h3>Komisi D</h3>
            <div class="action-buttons">
                <a href="input_resume.php?kategori=Komisi D">Input Resume</a>
                <a href="daftar_hadir.php?kategori=Komisi D" class="hadir-button">Daftar Hadir</a>
                <a href="view_resume.php?kategori=Komisi D">Lihat Resume</a>
            </div>
        </div>

        <div class="category-card">
            <h3>Rapat Pimpinan</h3>
            <div class="action-buttons">
                <a href="input_resume.php?kategori=Rapat Pimpinan">Input Resume</a>
                <a href="daftar_hadir.php?kategori=Rapat Pimpinan" class="hadir-button">Daftar Hadir</a>
                <a href="view_resume.php?kategori=Rapat Pimpinan">Lihat Resume</a>
            </div>
        </div>

        <div class="category-card">
            <h3>Badan Anggaran</h3>
            <div class="action-buttons">
                <a href="input_resume.php?kategori=Badan Anggaran">Input Resume</a>
                <a href="daftar_hadir.php?kategori=Badan Anggaran" class="hadir-button">Daftar Hadir</a>
                <a href="view_resume.php?kategori=Badan Anggaran">Lihat Resume</a>
            </div>
        </div>

        <div class="category-card">
            <h3>Badan Musyawarah</h3>
            <div class="action-buttons">
                <a href="input_resume.php?kategori=Badan Musyawarah">Input Resume</a>
                <a href="daftar_hadir.php?kategori=Badan Musyawarah" class="hadir-button">Daftar Hadir</a>
                <a href="view_resume.php?kategori=Badan Musyawarah">Lihat Resume</a>
            </div>
        </div>

        <div class="category-card">
            <h3>Paripurna</h3>
            <div class="action-buttons">
                <a href="input_resume.php?kategori=Paripurna">Input Resume</a>
                <a href="daftar_hadir.php?kategori=Paripurna" class="hadir-button">Daftar Hadir</a>
                <a href="view_resume.php?kategori=Paripurna">Lihat Resume</a>
            </div>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php'; // Path relatif ke file footer (dari resume_rapat/ ke includes/)
?>