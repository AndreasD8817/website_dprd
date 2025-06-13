<?php
// Sertakan file koneksi database
include '../config/koneksi.php';

// Pastikan ada parameter 'id' (ID peserta) dan 'id_resume' di URL
if (isset($_GET['id']) && !empty($_GET['id']) && isset($_GET['id_resume']) && !empty($_GET['id_resume'])) {
    $id_peserta = $_GET['id'];
    $id_resume_parent = $_GET['id_resume']; // ID resume induk untuk redirect

    // Menyiapkan statement SQL untuk DELETE peserta
    $stmt = $conn->prepare("DELETE FROM daftar_hadir_rapat WHERE id = ?");
    $stmt->bind_param("i", $id_peserta); // i: integer (karena ID peserta adalah angka)

    if ($stmt->execute()) {
        // Redirect kembali ke daftar_hadir.php dengan pesan sukses
        header("Location: daftar_hadir.php?id_resume=" . $id_resume_parent . "&status=peserta_deleted");
    } else {
        // Redirect kembali dengan pesan error
        header("Location: daftar_hadir.php?id_resume=" . $id_resume_parent . "&status=error_peserta_delete");
    }

    $stmt->close();
} else {
    // Jika tidak ada ID yang valid, redirect kembali ke halaman kategori resume
    header("Location: index.php?status=invalid_delete_request");
}

// Menutup koneksi database
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
exit(); // Penting: Hentikan eksekusi script setelah redirect
?>