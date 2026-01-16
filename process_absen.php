<?php
require_once __DIR__ . '/config/session_handler.php';
require_once 'config/database.php';

header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['is_login']) || $_SESSION['is_login'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

// Get input (JSON or POST)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$event_id = (int) ($input['event_id'] ?? 0);
$user_id = $_SESSION['user_id'];
$status = $input['status'] ?? null; // null = auto-detect, or 'Izin'/'Sakit' for manual

// Validate
if (!$event_id) {
    echo json_encode(['success' => false, 'message' => 'Event ID tidak valid.']);
    exit;
}

// Get event details
$stmt = $conn->prepare("SELECT id, nama_kegiatan, tanggal, jam_mulai, jam_selesai, tolerance_time, batas_pulang FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    echo json_encode(['success' => false, 'message' => 'Kegiatan tidak ditemukan.']);
    exit;
}

// Check if event is today
if ($event['tanggal'] !== date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'Kegiatan ini tidak dijadwalkan untuk hari ini.']);
    exit;
}

// Check existing attendance FIRST
$stmt = $conn->prepare("SELECT id, status, waktu_absen, clock_out FROM attendance WHERE user_id = ? AND event_id = ?");
$stmt->bind_param("ii", $user_id, $event_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

$currentTime = date('H:i:s');
$endTime = $event['jam_selesai'];

// Check if event has already ended - ONLY for new check-ins (not for checkout)
if (!$existing && strtotime($currentTime) > strtotime($endTime)) {
    echo json_encode([
        'success' => false,
        'message' => 'Kegiatan sudah selesai pada pukul ' . date('H:i', strtotime($endTime)) . '. Absensi tidak dapat dilakukan.'
    ]);
    exit;
}

$currentTime = date('H:i:s');
$toleranceTime = $event['tolerance_time'];

// If tolerance_time is empty, default to 15 minutes after start time
if (empty($toleranceTime)) {
    $toleranceTime = date('H:i:s', strtotime($event['jam_mulai'] . ' +15 minutes'));
}

// Convert to timestamps for proper comparison
$currentTimestamp = strtotime($currentTime);
$toleranceTimestamp = strtotime($toleranceTime);

// ============================================
// SCENARIO A: CHECK-IN (No existing record)
// ============================================
if (!$existing) {
    // Manual status (Izin/Sakit)
    if ($status === 'Izin' || $status === 'Sakit') {
        $finalStatus = $status;
        $message = "Absensi $status berhasil dicatat.";
    } else {
        // Auto-detect based on time comparison
        if ($currentTimestamp <= $toleranceTimestamp) {
            $finalStatus = 'Hadir';
            $message = 'Absen masuk berhasil! Selamat beraktivitas.';
        } else {
            $finalStatus = 'Terlambat';
            $message = 'Absen masuk tercatat TERLAMBAT. Waktu toleransi: ' . date('H:i', $toleranceTimestamp);
        }
    }

    $stmt = $conn->prepare("INSERT INTO attendance (user_id, event_id, status, waktu_absen) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $user_id, $event_id, $finalStatus);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'type' => 'checkin',
            'status' => $finalStatus,
            'message' => $message,
            'event_name' => $event['nama_kegiatan'],
            'time' => date('H:i:s')
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan absensi.']);
    }
    $stmt->close();
    exit;
}

// ============================================
// SCENARIO B: CHECK-OUT (Already has record)
// ============================================
if ($existing && !$existing['clock_out']) {
    // Anti-Cheating: Check if checkout time has been reached
    $currentCheckoutTime = date('H:i:s');
    $batasPulang = $event['batas_pulang'] ?? $event['jam_selesai']; // Fallback to jam_selesai if not set

    if (strtotime($currentCheckoutTime) < strtotime($batasPulang)) {
        // BLOCK - Checkout time not reached yet
        echo json_encode([
            'success' => false,
            'type' => 'checkout_blocked',
            'message' => 'Absen Pulang DITOLAK! Belum waktunya pulang (Batas Pulang: ' . date('H:i', strtotime($batasPulang)) . ').',
            'event_name' => $event['nama_kegiatan'],
            'checkout_start_time' => date('H:i', strtotime($batasPulang))
        ]);
        exit;
    }

    // ALLOW - Event has ended, proceed with checkout
    $stmt = $conn->prepare("UPDATE attendance SET clock_out = NOW() WHERE id = ?");
    $stmt->bind_param("i", $existing['id']);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'type' => 'checkout',
            'status' => $existing['status'],
            'message' => 'Absen pulang berhasil! Hati-hati di jalan.',
            'event_name' => $event['nama_kegiatan'],
            'checkin_time' => date('H:i', strtotime($existing['waktu_absen'])),
            'checkout_time' => date('H:i:s')
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan absen pulang.']);
    }
    $stmt->close();
    exit;
}

// ============================================
// SCENARIO C: Already checked-out
// ============================================
if ($existing && $existing['clock_out']) {
    echo json_encode([
        'success' => false,
        'message' => 'Anda sudah absen masuk dan pulang untuk kegiatan ini.',
        'checkin_time' => date('H:i', strtotime($existing['waktu_absen'])),
        'checkout_time' => date('H:i', strtotime($existing['clock_out']))
    ]);
    exit;
}
?>