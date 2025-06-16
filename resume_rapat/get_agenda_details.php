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
        // HAPUS BARIS INI: $existing_agendas_ids = isset($data['existing_agendas']) ? $data['existing_agendas'] : [];
        // HAPUS BARIS INI: $edit_resume_id = isset($data['edit_resume_id']) ? $data['edit_resume_id'] : null;

        // HAPUS BLOK KODE UNTUK MEMBANGUN NOT IN CLAUSE
        // $not_in_clause_sql = '';
        // $bind_types_dynamic = 'ss';
        // $bind_params_dynamic = [&$tanggal, &$kategori];
        // if (!empty($existing_agendas_ids)) {
        //     $existing_agendas_ids = array_map('intval', $existing_agendas_ids);
        //     $placeholders = implode(',', array_fill(0, count($existing_agendas_ids), '?'));
        //     $not_in_clause_sql = " AND id NOT IN ({$placeholders})"; // Pastikan nama kolom PK di agenda_rapat adalah 'id'
        //     $bind_types_dynamic .= str_repeat('i', count($existing_agendas_ids));
        //     foreach ($existing_agendas_ids as $key => $id_val) {
        //         $bind_params_dynamic[] = &$existing_agendas_ids[$key];
        //     }
        // }
        
        // Query untuk mengambil agenda berdasarkan tanggal dan kategori
        // KEMBALIKAN QUERY INI KE VERSI ASLI TANPA FILTER EXISTING_AGENDAS
        $sql = "SELECT id, nomor_undangan, perihal, jam FROM agenda_rapat WHERE tanggal = ? AND kategori = ? ORDER BY jam ASC";
        $stmt = $conn->prepare($sql);
        
        // Pastikan bind_param dipanggil dengan array referensi yang benar (hanya tanggal dan kategori)
        // call_user_func_array([$stmt, 'bind_param'], array_merge([$bind_types_dynamic], $bind_params_dynamic)); // HAPUS INI
        $stmt->bind_param("ss", $tanggal, $kategori); // GANTI DENGAN INI

        $stmt->execute();
        $result = $stmt->get_result();

        $agendas = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $agendas[] = $row;
            }
        }
        $response = $agendas;
        $stmt->close();
    }
    // --- LOGIKA UNTUK MENGAMBIL DETAIL AGENDA BERDASARKAN ID (untuk Jam Mulai) ---
    else if (isset($data['id'])) {
        $id_agenda = $data['id'];

        // Query untuk mengambil detail agenda berdasarkan ID
        $stmt = $conn->prepare("SELECT jam FROM agenda_rapat WHERE id = ?"); // Pastikan ini 'id' sesuai DB Anda
        $stmt->bind_param("i", $id_agenda);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response = $result->fetch_assoc();
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

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

echo json_encode($response);