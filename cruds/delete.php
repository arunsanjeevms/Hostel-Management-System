<?php
header('Content-Type: application/json');

include '../db.php';

$table = $_POST['table'] ?? '';
$id = $_POST['id'] ?? 0;

if(empty($table) || empty($id)) {
    echo json_encode(["status" => "error", "message" => "Missing required parameters"]);
    exit;
}

$sql = "DELETE FROM $table WHERE sno = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if($stmt->affected_rows > 0) {
        echo json_encode(["status" => "success", "message" => "Record deleted successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "No record found with that ID"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}

$stmt->close();
$conn->close();