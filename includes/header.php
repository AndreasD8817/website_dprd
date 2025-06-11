<?php
// Pastikan $page_title sudah diatur di file yang meng-include header ini
// Contoh: $page_title = "Input Agenda Rapat";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Website DPRD Kota Surabaya'; ?></title>
    <link rel="stylesheet" href="/dprd_website/css/main.css">
    </head>
<body>
    <header>
        <div class="header-logo">
            <img src="/dprd_website/images/logo_dprd.png" alt="Logo DPRD">
            <h1>DPRD Kota Surabaya</h1>
        </div>
        <nav>
            <ul>
                <li><a href="/dprd_website/index.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' && $_SERVER['REQUEST_URI'] == '/dprd_website/') ? 'active' : ''; ?>">Beranda</a></li>
                <!-- <li><a href="/dprd_website/input_agenda_dprd/index.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/input_agenda_dprd/') !== false) ? 'active' : ''; ?>">Input Agenda Rapat</a></li> -->
                <li><a href="/dprd_website/assets/background_video.mp4" target="_blank" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/input_agenda_dprd/daftar_agenda.php') !== false) ? 'active' : ''; ?>">Daftar Agenda Rapat</a></li>
                <!-- <li><a href="/dprd_website/resume_rapat/index.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/resume_rapat/') !== false) ? 'active' : ''; ?>">Resume Rapat</a></li> -->
                <!-- <li><a href="/dprd_website/daftar_hadir/index.php" class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/daftar_hadir/') !== false) ? 'active' : ''; ?>">Daftar Hadir</a></li> -->
                </ul>
        </nav>
    </header>
    <main>

