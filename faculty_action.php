<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['faculty_id'])) {
    header("Location: faculty_login.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $leave_id = intval($_GET['id']);
    $action = $_GET['action'] == 'approve' ? 'Approved' : 'Rejected';

    $stmt = $conn->prepare("UPDATE leaves SET status=? WHERE id=?");
    $stmt->bind_param("si", $action, $leave_id);
    $stmt->execute();

    header("Location: index.php");
    exit();
}
?>
