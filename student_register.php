<?php
header('Content-Type: application/json');

// DB connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "hostel"; // change to your database name

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed!"]);
    exit();
}

// collect and sanitize data
$name = $_POST['name'] ?? '';
$roll_number = $_POST['roll_number'] ?? '';
$department = $_POST['department'] ?? '';
$academic_year = $_POST['academic_year'] ?? '';
$block = $_POST['block'] ?? '';
$gender = $_POST['gender'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$father_name = $_POST['father_name'] ?? '';
$mother_name = $_POST['mother_name'] ?? '';
$parent_phone = $_POST['parent_phone'] ?? '';
$alternate_phone = $_POST['alternate_phone'] ?? '';

if (!$name || !$roll_number || !$department || !$academic_year || !$block || !$gender || !$phone || !$email) {
    echo json_encode(["status" => "error", "message" => "Please fill all required fields!"]);
    exit();
}

// insert query
$stmt = $conn->prepare("INSERT INTO students (name, roll_number, department, academic_year, block, gender, phone, email, father_name, mother_name, parent_phone, alternate_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssssssss", $name, $roll_number, $department, $academic_year, $block, $gender, $phone, $email, $father_name, $mother_name, $parent_phone, $alternate_phone);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Student registered successfully!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
