<?php
header('Content-Type: application/json'); // Memberi tahu browser bahwa respons ini adalah JSON

// Sertakan file koneksi database
include '../config/koneksi.php'; // Path relatif dari resume_rapat/ ke config/

$response = []; // Array untuk menyimpan respons yang akan dikirim kembali ke JavaScript

// Memastikan bahwa permintaan adalah POST dan menerima JSON
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
        echo json_encode(['error' => 'Invalid JSON data received.']);
        exit();
    }

    // --- LOGIKA UNTUK MENGAMBIL DAFTAR AGENDA BERDASARKAN TANGGAL DAN KATEGORI ---
    if (isset($data['tanggal']) && isset($data['kategori'])) {
        $tanggal = $data['tanggal'];
        $kategori = $data['kategori'];

        // Query untuk mengambil agenda berdasarkan tanggal dan kategori
        $stmt = $conn->prepare("SELECT id, nomor_undangan, perihal, jam FROM agenda_rapat WHERE tanggal = ? AND kategori = ? ORDER BY jam ASC");
        $stmt->bind_param("ss", $tanggal, $kategori);
        $stmt->execute();
        $result = $stmt->get_result();

        $agendas = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $agendas[] = $row;
            }
        }
        $response = $agendas; // Mengembalikan array agenda
        $stmt->close();
    }
    // --- LOGIKA UNTUK MENGAMBIL DETAIL AGENDA BERDASARKAN ID (untuk Jam Mulai) ---
    else if (isset($data['id'])) {
        $id_agenda = $data['id'];

        // Query untuk mengambil detail agenda berdasarkan ID
        $stmt = $conn->prepare("SELECT jam FROM agenda_rapat WHERE id = ?");
        $stmt->bind_param("i", $id_agenda);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response = $result->fetch_assoc(); // Mengembalikan detail agenda (jam)
        } else {
            $response = ['error' => 'Agenda not found.'];
        }
        $stmt->close();
    }
    else {
        $response = ['error' => 'Invalid parameters.'];
    }

} else {
    $response = ['error' => 'Invalid request method.'];
}

// Menutup koneksi database
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// Mengirim respons dalam format JSON
echo json_encode($response);
?>