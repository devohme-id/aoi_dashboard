<?php
// api/start_new_cycle.php

session_start();
require_once 'db_config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
    exit;
}

// 1. Validasi Session
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['message' => 'Sesi tidak valid (username tidak ada). Harap login ulang.']);
    exit;
}
$analystUsername = $_SESSION['username'];
$analystFullName = $_SESSION['full_name'] ?? $analystUsername;
if (empty(trim($analystFullName))) {
    $analystFullName = $analystUsername;
}

// 2. Validasi Input
$line_id = filter_input(INPUT_POST, 'line_id', FILTER_VALIDATE_INT);
$assembly_name = filter_input(INPUT_POST, 'assembly_name');
$notes = filter_input(INPUT_POST, 'notes');

if (!$line_id || !$assembly_name || !$notes) {
    http_response_code(400);
    echo json_encode(['message' => 'Input tidak valid. Semua field wajib diisi.']);
    exit;
}

// 3. Inisialisasi ID
$localAnalystId = null;

try {
    $conn->begin_transaction();

    // --- LOGIKA FIND OR CREATE DENGAN DEBUGGING LEBIH KETAT ---

    // 4. CEK USER
    $stmt_check = $conn->prepare("SELECT UserID FROM Users WHERE Username = ?");
    if (!$stmt_check) throw new Exception("Gagal [Cek User]: " . $conn->error);

    $stmt_check->bind_param("s", $analystUsername);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // 5a. USER DITEMUKAN
        $row = $result_check->fetch_assoc();
        $localAnalystId = (int)$row['UserID'];
        $stmt_check->close();

        if ($localAnalystId <= 0) {
            // Ini seharusnya tidak mungkin terjadi jika data di DB benar
            throw new Exception("Gagal [User Ditemukan]: ID User tidak valid (<= 0). Data korup.");
        }

    } else {
        // 5b. USER TIDAK DITEMUKAN, BUAT BARU
        $stmt_check->close();

        $stmt_create = $conn->prepare(
            "INSERT INTO Users (Username, FullName, Role) VALUES (?, ?, 'Analyst')"
        );
        if (!$stmt_create) throw new Exception("Gagal [Buat User - Prepare]: " . $conn->error);

        $stmt_create->bind_param("ss", $analystUsername, $analystFullName);

        if (!$stmt_create->execute()) {
            // Jika INSERT gagal, lempar error spesifik
            throw new Exception("Gagal [Buat User - Execute]: " . $stmt_create->error);
        }

        // Cek apakah baris baru benar-benar dibuat
        if ($stmt_create->affected_rows === 0) {
            throw new Exception("Gagal [Buat User - AffectedRows]: Eksekusi berhasil tapi tidak ada baris yang dibuat.");
        }

        // Ambil ID baru
        $newId = (int)$conn->insert_id;
        if ($newId <= 0) {
            // Jika ID auto-increment 0, ini adalah masalah besar
            throw new Exception("Gagal [Buat User - InsertID]: ID user baru tidak valid (0).");
        }

        $localAnalystId = $newId;
        $stmt_create->close();
    }

    // 6. FINAL CHECK SEBELUM INSERT KE TUNINGCYCLES
    if ($localAnalystId === null || $localAnalystId <= 0) {
        throw new Exception("Gagal Total: ID User lokal tidak bisa didapatkan setelah semua proses.");
    }

    // 7. Cari versi siklus terakhir
    $stmt = $conn->prepare("SELECT MAX(CycleVersion) as max_version FROM TuningCycles WHERE LineID = ? AND Assembly = ?");
    $stmt->bind_param("is", $line_id, $assembly_name);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $last_version = $result['max_version'] ?? 0;
    $stmt->close();

    if ($last_version == 0) {
        $stmt_fallback = $conn->prepare("SELECT MAX(TuningCycleID) as max_version FROM Inspections WHERE LineID = ? AND Assembly = ?");
        $stmt_fallback->bind_param("is", $line_id, $assembly_name);
        $stmt_fallback->execute();
        $result_fallback = $stmt_fallback->get_result()->fetch_assoc();
        $last_version = $result_fallback['max_version'] ?? 0;
        $stmt_fallback->close();
    }
    $new_version = $last_version + 1;

    // 8. Masukkan log siklus baru
    $stmt_insert = $conn->prepare("INSERT INTO TuningCycles (LineID, Assembly, CycleVersion, StartedByUserID, Notes) VALUES (?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("isiss", $line_id, $assembly_name, $new_version, $localAnalystId, $notes);

    if (!$stmt_insert->execute()) {
        // Jika masih gagal di sini, $localAnalystId pasti valid, masalahnya di tempat lain
        throw new Exception("Gagal [Insert TuningCycle]: " . $stmt_insert->error);
    }

    $stmt_insert->close();
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "Success! New tuning cycle (Version {$new_version}) for {$assembly_name} on Line {$line_id} has been started."
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    // Kirim pesan error spesifik yang kita buat
    error_log("Start Cycle Error: " . $e->getMessage());
    echo json_encode(['message' => 'Database error occurred: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>