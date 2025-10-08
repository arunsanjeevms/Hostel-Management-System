<?php
date_default_timezone_set('Asia/Kolkata');
include 'db_connect.php';

if (isset($_GET['token'])) {
    $token = trim(urldecode($_GET['token'])); // ⚡ Trim & decode

    // 1️⃣ Check token & expiry in DB
    $stmt = $conn->prepare("SELECT faculty_id FROM faculty WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // 2️⃣ If form submitted, update password
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $new_password = password_hash($_POST['password'], PASSWORD_BCRYPT);

            $update = $conn->prepare("UPDATE faculty SET password=?, reset_token=NULL, reset_expires=NULL WHERE reset_token=?");
            $update->bind_param("ss", $new_password, $token);
            $update->execute();

            echo "<script>alert('✅ Password reset successfully!'); window.location='faculty_login.php';</script>";
            exit;
        }
    } else {
        echo "<script>alert('⛔ Invalid or expired token!'); window.location='faculty_login.php';</script>";
        exit;
    }
} else {
    echo "<script>alert('⛔ No token provided!'); window.location='faculty_login.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
</head>
<body>
  <h2>Reset Your Password</h2>
  <form method="post">
    <label>New Password:</label>
    <input type="password" name="password" required>
    <button type="submit">Reset Password</button>
  </form>
</body>
</html>
