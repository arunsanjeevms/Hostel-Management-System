<?php session_start(); ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Faculty Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container vh-100 d-flex align-items-center">
  <div class="col-md-4 mx-auto">
    <div class="card shadow">
      <div class="card-header bg-primary text-white text-center">Faculty Login</div>
      <div class="card-body">
        <form action="faculty_auth.php" method="post">
          <div class="mb-3">
            <label>Email</label>
            <input name="email" type="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Password</label>
            <input name="password" type="password" class="form-control" required>
          </div>
          <div class="d-flex justify-content-between">
            <button class="btn btn-primary">Login</button>
            <a href="forgot_password.php">Forgot password?</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>
