<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hostel";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die(json_encode(['success'=>false,'error'=>$conn->connect_error]));

$id = $_POST['edit_student_id'];
$name = $_POST['edit_name'];
$roll = $_POST['edit_roll_number'];
$dept = $_POST['edit_department'];
$year = $_POST['edit_academic_year'];
$phone = $_POST['edit_phone'];
$parent = $_POST['edit_parent_phone'];

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("UPDATE students SET name=?, roll_number=?, department=?, academic_year=?, phone=?, parent_phone=? WHERE id=?");
$stmt->bind_param("ssssssi", $name, $roll, $dept, $year, $phone, $parent, $id);

if($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'error'=>$stmt->error]);
}

$stmt->close();
$conn->close();
?>
