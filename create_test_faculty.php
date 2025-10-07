<?php
// create_test_faculty.php  (run once)
include 'db_connect.php';

$faculty_code = 'FAC001';
$name = 'Keerthi';
$email = 'keerthi@gmail.com';
$password_plain = '12345';
$department = 'CSE';
$role = 'Faculty';

$hash = password_hash($password_plain, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO faculty (faculty_code, name, email, password, department, role) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $faculty_code, $name, $email, $hash, $department, $role);
if ($stmt->execute()) {
    echo "Inserted faculty: $email with hashed password.";
} else {
    echo "Error: " . $stmt->error;
}
$stmt->close();
$conn->close();
