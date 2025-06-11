<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_agenda_dprd";

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
// echo "Koneksi berhasil"; // PASTIKAN BARIS INI DINONAKTIFKAN (ada // di depannya)
?>