<?php
session_start(); // MULAI SESSION DI SINI UNTUK FLASH MESSAGES

include '../config/koneksi.php';

$id_agenda_to_delete = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : null;

if (!$id_agenda_to_delete) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'ID agenda tidak valid untuk dihapus!'];
    header("Location: daftar_agenda.php");
    exit();
}

// Cek dulu apakah agenda memiliki resume terkait
$stmt_check_resume = $conn->prepare("SELECT COUNT(*) FROM resume_rapat WHERE id_agenda = ?");
$stmt_check_resume->bind_param("i", $id_agenda_to_delete);
$stmt_check_resume->execute();
$stmt_check_resume->bind_result($resume_count);
$stmt_check_resume->fetch();
$stmt_check_resume->close();

// Persiapkan query DELETE untuk menghapus agenda
$stmt_delete = $conn->prepare("DELETE FROM agenda_rapat WHERE id = ?");
$stmt_delete->bind_param("i", $id_agenda_to_delete);

if ($stmt_delete->execute()) {
    // Tambahkan pesan ke session
    if ($resume_count > 0) {
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Agenda dan ' . $resume_count . ' resume terkait berhasil dihapus!'];
    } else {
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Agenda berhasil dihapus!'];
    }
    header("Location: daftar_agenda.php");
} else {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Gagal menghapus agenda: ' . $stmt_delete->error];
    header("Location: daftar_agenda.php");
}
$stmt_delete->close();

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

exit();
?>