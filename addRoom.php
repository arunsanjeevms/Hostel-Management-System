<?php
include 'db.php';
header('Content-Type: application/json');

// Get POST data safely and sanitize if needed
$hostel_id = isset($_POST['hostel_id']) ? intval($_POST['hostel_id']) : 0;
$room_number = trim($_POST['room_number'] ?? '');
$capacity = isset($_POST['capacity']) ? intval($_POST['capacity']) : 0;
$occupied = isset($_POST['occupied']) ? intval($_POST['occupied']) : 0;
$room_type = trim($_POST['room_type'] ?? '');

// Validate required fields
if ($hostel_id <= 0 || empty($room_number) || $capacity <= 0 || $occupied < 0 || empty($room_type)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required and must be valid']);
    exit;
}

// Check if hostel exists to prevent foreign key error
$checkHostel = $conn->prepare("SELECT hostel_id FROM hostels WHERE hostel_id = ?");
$checkHostel->bind_param("i", $hostel_id);
$checkHostel->execute();
$checkHostel->store_result();

if ($checkHostel->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Selected hostel does not exist']);
    exit;
}

// Insert new room
$stmt = $conn->prepare("INSERT INTO rooms (hostel_id, room_number, capacity, occupied, room_type) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("isiss", $hostel_id, $room_number, $capacity, $occupied, $room_type);

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
            'room_type' => $room_type
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
