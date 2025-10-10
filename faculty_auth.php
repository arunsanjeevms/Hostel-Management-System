<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: faculty_login.php');
    exit;
}

$email = trim($_POST['email']);
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT faculty_id, name, password, department FROM faculty WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows === 1) {
    $row = $res->fetch_assoc();
    if (password_verify($password, $row['password'])) {
    $_SESSION['faculty_id'] = $row['faculty_id'];
    $_SESSION['faculty_name'] = $row['name'];
    $_SESSION['faculty_department'] = $row['department'];
    header("Location: index.php");
    exit;
} else {
    echo "<script>alert('Invalid password'); window.location='faculty_login.php';</script>";
    exit;
}
}