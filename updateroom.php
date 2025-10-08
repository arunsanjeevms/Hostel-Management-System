<?php
include 'db.php';
header('Content-Type: application/json');

$room_id = $_POST['room_id'] ?? '';
$hostel_id = $_POST['hostel_id'] ?? '';
$room_number = $_POST['room_number'] ?? '';
$capacity = $_POST['capacity'] ?? '';
$occupied = $_POST['occupied'] ?? '';
$room_type = $_POST['room_type'] ?? '';
$status = $_POST['status'] ?? '';

if (!$room_id || !$hostel_id || !$room_number || !$capacity || !$occupied || !$room_type || !$status) {
    echo json_encode(['success' => false, 'error' => 'All fields are required.']);
    exit;
}

$stmt = $conn->prepare("UPDATE rooms SET hostel_id=?, room_number=?, capacity=?, occupied=?, room_type=?, status=?, updated_at=NOW() WHERE room_id=?");
$stmt->bind_param("isisssi", $hostel_id, $room_number, $capacity, $occupied, $room_type, $status, $room_id);

if ($stmt->execute()) {
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
