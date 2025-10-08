<?php
include 'db.php';
header('Content-Type: application/json');

$hostel_id = $_POST['hostel_id'] ?? '';
$room_number = $_POST['room_number'] ?? '';
$capacity = $_POST['capacity'] ?? '';
$occupied = $_POST['occupied'] ?? '';
$room_type = $_POST['room_type'] ?? '';
$status = $_POST['status'] ?? '';

if (!$hostel_id || !$room_number || !$capacity || !$occupied || !$room_type || !$status) {
    echo json_encode(['success' => false, 'error' => 'All fields are required.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO rooms (hostel_id, room_number, capacity, occupied, room_type, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
$stmt->bind_param("isisss", $hostel_id, $room_number, $capacity, $occupied, $room_type, $status);

if ($stmt->execute()) {
    $room_id = $stmt->insert_id;
    echo json_encode([
        'success' => true,
        'data' => [
            'room_id' => $room_id,
            'hostel_id' => $hostel_id,
            'room_number' => $room_number,
            'capacity' => $capacity,
            'occupied' => $occupied,
            'room_type' => $room_type,
            'status' => $status
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
