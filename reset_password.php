<?php
include 'db_connect.php';
if (!isset($_GET['token'])) { echo "No token"; exit; }
$token = $_GET['token'];
$stmt = $conn->prepare("SELECT email, expires_at FROM password_resets WHERE token = ? LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute(); $res = $stmt->get_result();
if ($res->num_rows !== 1) { echo "Invalid token"; exit; }
$row = $res->fetch_assoc();
if (strtotime($row['expires_at']) < time()) { echo "Token expired"; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = $_POST['new_password'];
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $stmt2 = $conn->prepare("UPDATE faculty SET password = ? WHERE email = ?");
    $stmt2->bind_param("ss", $hash, $row['email']);
    $stmt2->execute();
    // remove token
    $stmt3 = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
    $stmt3->bind_param("s",$token); $stmt3->execute();
    echo "<script>alert('Password updated'); window.location='faculty_login.php';</script>";
    exit;
}
?>
<form method="post">
  <input name="new_password" type="password" required placeholder="New password">
  <button>Update password</button>
</form>
