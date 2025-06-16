<?php
$page_title = "Beranda - Website DPRD Kota Surabaya"; // Judul halaman ini
include 'config/koneksi.php'; // Termasuk file koneksi database
include 'includes/header.php'; // Termasuk header
?>

<div class="hero-section">
    <h1>Sistem Integrasi Sekwan (SIS) DPRD Kota Surabaya</h1>
    <p>Pusat Informasi Aduan, Rapat dan Aspirasi Masyarakat</p>
    <p class="jawa-text">꧋ꦱꦺꦏꦿꦺꦠꦫꦶꦪꦠ꧀ꦢꦺꦮꦤ꧀ꦮꦏꦶꦭ꧀ꦫꦏꦾꦠ꧀ꦭꦭꦢꦤ꧀ꦏꦶꦛꦱꦸꦫꦧꦪ</p>
    <a href="https://dprd.surabaya.go.id/" class="hero-button" target="_blank" rel="noopener noreferrer">Kunjungi Website DPRD</a>
</div>

<section class="features-section">
    <div class="feature-card">
        <h3>Input Agenda Rapat</h3>
        <p>Input Agenda Rapat DPRD dengan mudah.</p>
        <a href="/dprd_website/input_agenda_dprd/index.php" class="card-link">Lihat Selengkapnya</a>
    </div>
    
    <div class="feature-card">
        <h3>Resume Rapat</h3>
        <p>Lihat dan kelola resume atau notulen hasil rapat.</p>
        <a href="/dprd_website/resume_rapat/index.php" class="card-link">Lihat Selengkapnya</a>
    </div>

    <div class="feature-card">
        <h3>Daftar Agenda Rapat</h3>
        <p>Kelola dan masukkan detail agenda rapat DPRD dengan mudah.</p>
        <a href="/dprd_website/input_agenda_dprd/daftar_agenda.php" class="card-link">Lihat Selengkapnya</a>
    </div>
</section>

<style>
    .hero-section {
        background-color: var(--light-blue);
        padding: 80px 20px;
        text-align: center;
        margin-bottom: 40px;
        border-radius: 8px;
        box-shadow: 0 4px 15px var(--shadow-light);
        letter-spacing: 2px;
    }
    .hero-section h1 {
        font-size: 2.8em;
        color: var(--primary-blue);
        margin-bottom: 15px;
    }
    .hero-section p {
        font-size: 1.3em;
        color: var(--light-text);
        margin-bottom: 10px; /* Jarak default untuk paragraf umum */
    } 
    /* BARU: CSS untuk paragraf Aksara Jawa */
    .hero-section .jawa-text {
        margin-bottom: 40px; /* Jarak lebih besar untuk mendorong tombol ke bawah */
        font-size: 1.5em; /* Mungkin perlu disesuaikan agar lebih mudah dibaca */
        color: var(--text-color);
    }
    
    .hero-button {
        background-color: var(--primary-blue);
        color: white;
        padding: 15px 30px;
        border-radius: 5px;
        font-size: 1.1em;
        font-weight: 600;
        transition: background-color 0.3s ease, transform 0.2s ease;
        /* margin-top: 0; */ /* Hapus atau set ke 0 karena jarak diatur dari margin-bottom paragraf di atasnya */
    }
    .hero-button:hover {
        background-color: var(--dark-blue);
        transform: translateY(-2px);
    }

    .features-section {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 30px;
        padding-bottom: 40px;
    }
    .feature-card {
        background-color: var(--card-bg);
        border-radius: 10px;
        box-shadow: 0 4px 15px var(--shadow-light);
        padding: 30px;
        text-align: center;
        width: calc(33% - 20px); /* Untuk 3 kolom */
        min-width: 280px; /* Lebar minimum untuk responsivitas */
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px var(--shadow-medium);
    }
    .feature-card h3 {
        color: var(--primary-blue);
        font-size: 1.6em;
        margin-bottom: 15px;
    }
    .feature-card p {
        color: var(--light-text);
        margin-bottom: 25px;
        font-size: 0.95em;
    }
    .card-link {
        display: inline-block;
        background-color: var(--primary-blue);
        color: white;
        padding: 10px 20px;
        border-radius: 4px;
        font-weight: 500;
        transition: background-color 0.3s ease;
    }
    .card-link:hover {
        background-color: var(--dark-blue);
    }

    @media (max-width: 992px) {
        .feature-card {
            width: calc(50% - 20px); /* 2 kolom untuk tablet */
        }
    }

    @media (max-width: 600px) {
        .feature-card {
            width: 90%; /* 1 kolom untuk mobile */
        }
    }
</style>

<?php
include 'includes/footer.php'; // Termasuk footer
?>