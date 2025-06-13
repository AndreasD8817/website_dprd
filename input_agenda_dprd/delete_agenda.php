<?php
// Pastikan koneksi database tersedia
include '../config/koneksi.php'; // Path relatif dari input_agenda_dprd/ ke config/

// Ambil ID Agenda dari URL
$id_agenda_to_delete = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : null;

// Jika tidak ada ID agenda di URL, redirect kembali ke daftar agenda dengan pesan error
if (!$id_agenda_to_delete) {
    header("Location: daftar_agenda.php?status=invalid_delete_id");
    exit();
}

// Persiapkan query DELETE untuk menghapus agenda
$stmt_delete = $conn->prepare("DELETE FROM agenda_rapat WHERE id = ?");
$stmt_delete->bind_param("i", $id_agenda_to_delete); // i: integer (karena ID adalah angka)

// Eksekusi query
if ($stmt_delete->execute()) {
    // Redirect kembali ke daftar_agenda.php dengan pesan sukses
    header("Location: daftar_agenda.php?status=deleted");
} else {
    // Redirect kembali dengan pesan error
    header("Location: daftar_agenda.php?status=error_delete");
}
$stmt_delete->close();

// Tutup koneksi database setelah semua operasi selesai
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

exit(); // Penting: Hentikan eksekusi script setelah redirect
?>