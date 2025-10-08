<?php
include 'db.php';
header('Content-Type: application/json');

$room_id = $_POST['room_id'] ?? '';
if (!$room_id) {
    echo json_encode(['success' => false, 'error' => 'Room ID is required.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM rooms WHERE room_id = ?");
$stmt->bind_param("i", $room_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
