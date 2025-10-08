<?php
include 'db.php';
header('Content-Type: application/json');

$room_id = $_POST['room_id'] ?? '';
if (!$room_id) {
    echo json_encode(['success' => false, 'error' => 'Room ID is required.']);
    exit;
}

$stmt = $conn->prepare("SELECT room_id, hostel_id, room_number, capacity, occupied, room_type, status FROM rooms WHERE room_id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'data' => $row]);
} else {
    echo json_encode(['success' => false, 'error' => 'Room not found.']);
}

$stmt->close();
$conn->close();
