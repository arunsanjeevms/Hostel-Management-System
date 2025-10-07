<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    // ensure faculty exists
    $stmt = $conn->prepare("SELECT faculty_id FROM faculty WHERE email=? LIMIT 1");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo "<script>alert('Email not found'); window.location='forgot_password.php';</script>";
        exit;
    }

    // Generate secure token and expiry
    $token = bin2hex(random_bytes(32));
    $expires = date("Y-m-d H:i:s", strtotime("+30 minutes"));

    // Insert into password_resets table
    $stmt2 = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt2->bind_param("sss", $email, $token, $expires);
    $stmt2->execute();

    // Create reset link
    $resetLink = "http://localhost/faculty/newtemplate/reset_password.php?token=$token";

    // IN PRODUCTION: Send the reset link via email to $email here using mail() or PHPMailer
    // FOR TESTING ONLY: Display the link on the page
    echo "Reset link (testing): <a href='$resetLink'>$resetLink</a>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
</head>
<body>
    <form method="post">
        <input type="email" name="email" required placeholder="Your faculty email">
        <button type="submit">Send reset link</button>
    </form>
</body
