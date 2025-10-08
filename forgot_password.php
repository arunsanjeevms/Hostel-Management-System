
<?php
date_default_timezone_set('Asia/Kolkata');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
include 'db_connect.php';
include 'config.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);

    // 1️⃣ Check if faculty email exists
    $stmt = $conn->prepare("SELECT faculty_id FROM faculty WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // 2️⃣ Generate token & expiry
        $token = bin2hex(random_bytes(16)); // 32 chars
        $expires = date("Y-m-d H:i:s", strtotime("+30 minutes"));

        // 3️⃣ Store token & expiry in faculty table
        $update = $conn->prepare("UPDATE faculty SET reset_token=?, reset_expires=? WHERE email=?");
        $update->bind_param("sss", $token, $expires, $email);
        $update->execute();

        // 4️⃣ Create reset link (safe encoding)
        $reset_link = "http://localhost/Hostel-Management-System/reset_password.php?token=" . urlencode($token);

        // 5️⃣ Send email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME; // Your Gmail
            $mail->Password = MAIL_PASSWORD;      // App password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom(MAIL_USERNAME, 'Hostel Management');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = "Password Reset Request";
            $mail->Body = "
                <h3>Password Reset</h3>
                <p>Click the link below to reset your password (expires in 30 minutes):</p>
                <a href='$reset_link'>$reset_link</a>
                <p>If you didn't request this, ignore this email.</p>
            ";

            $mail->send();
            echo "<script>alert('✅ Reset link sent to your email!'); window.location='faculty_login.php';</script>";
            exit;
        } catch (Exception $e) {
            echo "<script>alert('❌ Email could not be sent. Error: {$mail->ErrorInfo}');</script>";
        }
    } else {
        echo "<script>alert('⚠️ No faculty found with that email');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
</head>
<body>
  <h2>Forgot Password</h2>
  <form method="post">
    <label>Email:</label>
    <input type="email" name="email" required>
    <button type="submit">Send Reset Link</button>
  </form>
</body>
</html>
