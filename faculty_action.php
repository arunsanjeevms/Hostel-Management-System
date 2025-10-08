<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['faculty_id'])) {
    header("Location: faculty_login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $leave_id = (int)($_POST['leave_id'] ?? 0);

    // ✅ Ensure valid input
    if ($leave_id <= 0 || ($action !== 'approve' && $action !== 'reject')) {
        echo "<script>alert('Invalid input'); window.location.href='index.php';</script>";
        exit;
    }

    // ✅ Update leave status
    $status = ($action === 'approve') ? 'Approved' : 'Rejected';

    $stmt = $conn->prepare("UPDATE leave_applications SET faculty_status = ? WHERE leave_id = ?");
    $stmt->bind_param("si", $status, $leave_id);
    if ($stmt->execute()) {
        echo "<script>
            alert('Leave has been $status successfully.');
            window.location.href='index.php';
        </script>";
    } else {
        echo "<script>
            alert('Error updating leave status!');
            window.location.href='index.php';
        </script>";
    }

    $stmt->close();
}
$conn->close();
?>
