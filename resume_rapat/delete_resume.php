<?php
include '../config/koneksi.php';

if (isset($_GET['id']) && !empty($_GET['id']) && isset($_GET['kategori']) && !empty($_GET['kategori'])) {
    $id_resume_to_delete = $_GET['id'];
    $kategori_redirect = $_GET['kategori'];

    // Gunakan Prepared Statement untuk keamanan
    $stmt = $conn->prepare("DELETE FROM resume_rapat WHERE id = ?");
    $stmt->bind_param("i", $id_resume_to_delete);

    if ($stmt->execute()) {
        header("Location: view_resume_by_category.php?kategori=" . htmlspecialchars($kategori_redirect) . "&status=deleted");
    } else {
        header("Location: view_resume_by_category.php?kategori=" . htmlspecialchars($kategori_redirect) . "&status=error_delete");
    }
    $stmt->close();
} else {
    // Jika parameter tidak lengkap, redirect ke halaman kategori utama
    header("Location: index.php?status=invalid_delete_request");
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
exit();
?>