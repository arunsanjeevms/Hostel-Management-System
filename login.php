<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['type'])) {
        $user_type = $_POST['type'];
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['pass']) ? trim($_POST['pass']) : '';

        if (empty($email) || empty($password)) {
            echo "<script>alert('Please enter both email and password!'); window.location='login.php';</script>";
            exit;
        }

        switch ($user_type) {
            case 'student':
                $sql = "SELECT * FROM users WHERE username = ? AND role = 'student'";
                break;
            case 'faculty':
                $sql = "SELECT * FROM faculty WHERE email = ?";
                break;
            case 'admin':
                $sql = "SELECT * FROM users WHERE username = ? AND role = 'admin'";
                break;
            case 'mess_supervisor':
                $sql = "SELECT * FROM users WHERE username = ? AND role = 'mess_supervisor'";
                break;
            default:
                echo "<script>alert('Invalid user type!'); window.location='login.php';</script>";
                exit;
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            $enteredPassword = $password;
            $storedPassword = $user['password'];

            // ✅ Verify password (support both hashed and plain)
            $isValid = password_verify($enteredPassword, $storedPassword) || $enteredPassword === $storedPassword;

            if ($isValid) {
                $_SESSION['user_type'] = $user_type;

                if ($user_type === 'faculty') {
                    $_SESSION['faculty_id'] = $user['faculty_id'];
                    $_SESSION['faculty_name'] = $user['name'];
                    $_SESSION['faculty_department'] = $user['department'];
    
$_SESSION['department_id'] = $row['department_id'];

                    header("Location:Hostel-Management-System-faculty/leave_approve.php");
                    exit;
                } elseif ($user_type === 'student') {
                    $_SESSION['user_id'] = $user['user_id'];
                     $_SESSION['user_type'] = 'student'; 
                    header("Location: dashboard.php");
                    exit;
                } elseif ($user_type === 'admin') {
                    header("Location: dash_attend/dashboard.php");
                    exit;
                } elseif ($user_type === 'mess_supervisor') {
                    header("Location: Hostel-Management-System-mess/mess_index.php");
                    exit;
                }
            } else {
                echo "<script>alert('Invalid password!'); window.location='login.php';</script>";
                exit;
            }
        } else {
            echo "<script>alert('No user found with this email/ID.'); window.location='login.php';</script>";
            exit;
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <link href="css/bootstrap-5.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Debug script to check if SweetAlert is loaded -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal === 'undefined') {
                console.error('SweetAlert2 is not loaded!');
            } else {
                console.log('SweetAlert2 is loaded successfully');
            }
        });
    </script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KR CONNECT</title>

    <meta name="description" content="KR CONNECT is a smart college ERP for M.Kumarasamy College of Engineering. Manage students, faculty, attendance, exams, and administration in one platform.">
    <meta name="keywords" content="College ERP, Student Management System, Faculty Management, Academic ERP, Attendance Tracking, Examination Management, M.Kumarasamy College of Engineering, KR CONNECT">
    <meta name="author" content="Augmatics">
    <meta name="robots" content="index, follow">

    <meta property="og:title" content="KR CONNECT - College ERP & Student Management System">
    <meta property="og:description" content="Smart ERP software for M.Kumarasamy College of Engineering to manage students, faculty, attendance, exams, and administration efficiently.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://krconnect.mkce.ac.in">
    <meta property="og:image" content="images/erp7.png">

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "SoftwareApplication",
      "name": "KR CONNECT",
      "alternateName": "KR Connect College ERP",
      "operatingSystem": "Web-based",
      "applicationCategory": "BusinessApplication",
      "description": "KR CONNECT is a smart college ERP developed for M.Kumarasamy College of Engineering to manage students, faculty, attendance, exams, and administration efficiently.",
      "softwareVersion": "1.0",
      "author": {
        "@type": "Organization",
        "name": "Technology Innovation Hub - MKCE",
        "url": "https://www.tih.mkce.ac.in"
      },
      "publisher": {
        "@type": "Organization",
        "name": "M.Kumarasamy college of Engineering",
        "url": "https://www.mkce.ac.in"
      },
      "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "INR",
        "category": "ERP Software"
      },
      "url": "https://krconnect.mkce.ac.in",
      "image": "https://https://krconnect.mkce.ac.in/image/erp7.png"
    }
    </script>

    <link rel="icon" type="image/png" sizes="32x32" href="images/mkce_s.png">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <link href="css/bootstrap-5.css" rel="stylesheet">
    <style>
        /* Your existing CSS remains exactly the same */
        .split-screen {
            display: flex;
            min-height: 100vh;
        }

        .left {
            flex: 0 0 50%;
            background: linear-gradient(135deg, #1e4d92 0%, #1fb5ac 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
            padding: 2rem;
        }

        .right {
            flex: 0 0 50%;
            background: #f0f2f5;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        #particles-left,
        #particles-right {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
        }

        #particles-right {
            opacity: 0.3;
        }

        .transport-icon {
            font-size: 6rem;
            margin-bottom: 2rem;
            position: relative;
            z-index: 2;
        }

        .system-title {
            font-size: 2rem;
            text-align: center;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 2;
            font-weight: bold;
        }

        .login-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
        }

        .login-container {
            width: 100%;
            max-width: 700px;
            position: relative;
            z-index: 2;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1),
                0 1px 8px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(120deg, #1e4d92, #1fb5ac);
            padding: 2rem;
            text-align: center;
            position: relative;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: 0;
            right: 0;
            height: 50px;
            background: white;
            border-radius: 50% 50% 0 0;
        }

        .logo-img {
            max-width: 350px;
            margin-bottom: 1rem;
            border-radius: 15px;
            padding: 10px;
            background: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .login-form-container {
            padding: 2rem 3rem 3rem;
            position: relative;
            overflow: hidden;
            min-height: 400px;
        }

        .login-tabs-content {
            transition: transform 0.5s ease-in-out, opacity 0.5s ease-in-out;
            position: relative;
            width: 100%;
            background: white;
        }

        .input-group {
            margin-bottom: 1.5rem;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .input-group-text {
            background-color: #1e4d92;
            color: white;
            border: none;
            width: 50px;
            justify-content: center;
        }

        .kf {
            background: linear-gradient(135deg, #1fb5ac, #23c5bb);
        }

        .ka {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
        }

        .km {
            background: linear-gradient(135deg, #4ecdc4, #66d6cd);
        }

        .form-control {
            height: 50px;
            font-size: 1.1rem;
            border: none;
            padding-left: 15px;
        }

        .form-control:focus {
            box-shadow: none;
        }

        .nav-pills {
            margin-bottom: 2rem;
            padding: 4px;
            background: #f8f9fa;
            border-radius: 12px;
            display: flex;
            gap: 10px;
        }

        .nav-item.flex-fill {
            margin: 0 5px;
        }

        .nav-pills .nav-link {
            padding: 0.75rem 1.5rem;
            font-size: 1.1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .nav-pills .nav-link.active {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-student,
        .btn-faculty,
        .btn-admin,
        .btn-mess,
        .btn-lostfaculty {
            height: 50px;
            font-size: 1.1rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-student {
            background: linear-gradient(135deg, #1e4d92, #2c5aa0);
            color: white;
        }

        .btn-faculty {
            background: linear-gradient(135deg, #1fb5ac, #23c5bb);
            color: white;
        }

        .btn-admin {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
        }

        .btn-mess {
            background: linear-gradient(135deg, #4ecdc4, #66d6cd);
            color: white;
        }

        .btn-lostfaculty {
            background: linear-gradient(135deg, #1e4d92, #1fb5ac);
            color: white;
        }

        .btn-student:hover,
        .btn-faculty:hover,
        .btn-admin:hover,
        .btn-mess:hover,
        .btn-lostfaculty:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            color: white;
        }

        .recover-form {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            padding: 2rem;
            background: white;
            transform: translateY(100%);
            transition: transform 0.5s ease-in-out, opacity 0.5s ease-in-out;
            opacity: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .recover-form.active {
            transform: translateY(0);
            opacity: 1;
        }

        .login-tabs-content.hide {
            transform: translateY(-100%);
            opacity: 0;
        }

        .recover-title {
            color: #1e4d92;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .recover-description {
            color: #666;
            text-align: center;
            margin-bottom: 2rem;
        }

        .recover-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .recover-buttons button {
            flex: 1;
            height: 50px;
            font-size: 1.1rem;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .btn-back {
            background: #f0f2f5;
            color: #1e4d92;
            border: none;
        }

        .btn-recover {
            background: linear-gradient(135deg, #1e4d92, #1fb5ac);
            color: white;
            border: none;
        }

        .btn-back:hover,
        .btn-recover:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .footer {
            background: white;
            padding: 1rem 0;
            position: relative;
            z-index: 2;
            box-shadow: 0 -1px 10px rgba(0, 0, 0, 0.05);
        }

        @media (max-width: 991px) {
            .split-screen {
                flex-direction: column;
            }

            .left {
                flex: 0 0 auto;
                padding: 3rem 1rem;
            }

            .right {
                flex: 1 0 auto;
            }

            .system-title {
                font-size: 2.5rem;
            }

            .login-wrapper {
                padding: 2rem 1rem;
            }

            .login-form-container {
                padding: 2rem 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .system-title {
                font-size: 2rem;
            }

            .transport-icon {
                font-size: 4rem;
            }

            .login-wrapper {
                padding: 1.5rem 1rem;
            }

            .nav-pills .nav-link {
                padding: 0.5rem 1rem;
                font-size: 1rem;
            }

            .login-header {
                padding: 1.5rem;
            }

            .logo-img {
                max-width: 300px;
            }
        }

        @media (max-width: 768px) {
            .left {
                padding: 10px;
            }

            #particles-left {
                height: 100px;
            }

            .transport-icon {
                width: 50px;
                height: 50px;
            }

            .left img {
                max-width: 70%;
            }
        }
    </style>
</head>
<body>
    <div class="split-screen">
        <div class="left">
            <div id="particles-left"></div>
            <div class="transport-icon"></div>
            <div class="d-flex flex-column align-items-center gap-3">
                <img src="images/erp3.png" alt="MKCE Logo" class="img-fluid" style="width: 400px;">
                <img class="mt-2" src="images/erp7.png" alt="MKCE Logo" style="width: 500px; height: 250px;">
            </div>
        </div>

        <div class="right">
            <div id="particles-right"></div>
            <div class="login-wrapper">
                <div class="login-container">
                    <div class="login-header">
                        <img src="images/mkcenew.png" alt="MKCE Logo" class="logo-img">
                    </div>

                    <div class="login-form-container">
                        <div class="login-tabs-content">
                            <h2 class="text-center mb-4 fs-1 fw-bold"></h2>

                            <ul class="nav nav-pills mb-4" id="loginTabs" role="tablist">
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link active w-100 btn-student" data-bs-toggle="pill" data-bs-target="#student" type="button">Student</button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link w-100 btn-faculty" data-bs-toggle="pill" data-bs-target="#faculty" type="button">Faculty</button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link w-100 btn-admin" data-bs-toggle="pill" data-bs-target="#admin" type="button">Admin</button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link w-100 btn-mess" data-bs-toggle="pill" data-bs-target="#mess" type="button">Mess Supervisor</button>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <!-- Student Tab -->
                                <div class="tab-pane fade show active" id="student">
                                    <form action="login.php" method="post">
                                        <input type="hidden" name="type" value="student">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" name="email" placeholder="Student ID" required>
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" name="pass" placeholder="Password" required>
                                        </div>
                                        <button type="submit" class="btn btn-student w-100">Login as Student</button>
                                    </form>
                                </div>

                                <!-- Faculty Tab -->
                                <div class="tab-pane fade" id="faculty">
                                    <form action="login.php" method="post">
                                        <input type="hidden" name="type" value="faculty">
                                        <div class="input-group">
                                            <span class="input-group-text kf"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" name="email" placeholder="Faculty ID" required>
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text kf"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" name="pass" placeholder="Password" required>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <button type="submit" class="btn btn-faculty w-100">Login as Faculty</button>
                                            </div>
                                            <div class="col-md-6">
                                                <button type="button" id="to-recover" class="btn btn-lostfaculty w-100">Lost password</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Admin Tab -->
                                <div class="tab-pane fade" id="admin">
                                    <form action="login.php" method="post">
                                        <input type="hidden" name="type" value="admin">
                                        <div class="input-group">
                                            <span class="input-group-text ka"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" name="email" placeholder="Admin ID" required>
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text ka"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" name="pass" placeholder="Password" required>
                                        </div>
                                        <button type="submit" class="btn btn-admin w-100">Login as Admin</button>
                                    </form>
                                </div>

                                <!-- Mess Supervisor Tab -->
                                <div class="tab-pane fade" id="mess">
                                    <form action="login.php" method="post">
                                        <input type="hidden" name="type" value="mess_supervisor">
                                        <div class="input-group">
                                            <span class="input-group-text km"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" name="email" placeholder="Mess Supervisor ID" required>
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text km"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" name="pass" placeholder="Password" required>
                                        </div>
                                        <button type="submit" class="btn btn-mess w-100">Login as Mess Supervisor</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Password Recovery Form -->
                        <div class="recover-form">
                            <h3 class="recover-title">Password Recovery</h3>
                            <p class="recover-description">Enter your Faculty ID and email address below to recover your password.</p>

                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" name="fid" id="fid" placeholder="Faculty ID">
                            </div>

                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" name="email" id="email" placeholder="Email Address">
                            </div>

                            <div class="recover-buttons">
                                <button type="button" class="btn-back" id="to-login">Back to Login</button>
                                <button type="button" class="btn-recover" id="sendEmailButton">Recover Password</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="footer text-center">
                <div class="container">
                    <p class="mb-0">Copyright © 2025 Designed by Technology Innovation Hub - MKCE. All rights reserved.</p>
                </div>
            </footer>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/particles.min.js"></script>
    <script>
        // Particles configurations (same as your existing code)
        particlesJS('particles-left', {
            particles: {
                number: {
                    value: 60,
                    density: {
                        enable: true,
                        value_area: 800
                    }
                },
                color: {
                    value: '#ffffff'
                },
                opacity: {
                    value: 0.5,
                    random: false
                },
                size: {
                    value: 3,
                    random: true
                },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: '#ffffff',
                    opacity: 0.4,
                    width: 1
                },
                move: {
                    enable: true,
                    speed: 2,
                    direction: 'none',
                    random: false,
                    straight: false,
                    out_mode: 'out'
                }
            }
        });

        particlesJS('particles-right', {
            particles: {
                number: {
                    value: 40,
                    density: {
                        enable: true,
                        value_area: 800
                    }
                },
                color: {
                    value: '#1e4d92'
                },
                opacity: {
                    value: 0.3,
                    random: false
                },
                size: {
                    value: 2,
                    random: true
                },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: '#1e4d92',
                    opacity: 0.2,
                    width: 1
                },
                move: {
                    enable: true,
                    speed: 1,
                    direction: 'none',
                    random: false,
                    straight: false,
                    out_mode: 'out'
                }
            }
        });
    </script>
    <script src="js/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            $("#to-recover").click(function () {
                $(".login-tabs-content").addClass("hide");
                $(".recover-form").addClass("active");
            });

            $("#to-login").click(function () {
                $(".login-tabs-content").removeClass("hide");
                $(".recover-form").removeClass("active");
            });

            // AJAX code for password recovery
            $("#sendEmailButton").click(function () {
                var email = $("#email").val();
                var id = $("#fid").val();

                $.ajax({
                    type: "POST",
                    url: "mail.php",
                    data: {
                        email: email,
                        fid: id
                    },
                    dataType: "json",
                    success: function (response) {
                        if (response.status === 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                            }).then(function () {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        console.log(xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: "An error occurred. Please try again later.",
                        });
                    }
                });
            });
        });
    </script>
    <script>
        $(document).ready(function () {
            // Department dropdown functionality for faculty
            $("input[name='email']").on("input", function () {
                var facultyID = $(this).val();
                if (facultyID.length >= 2) {
                    $.ajax({
                        url: "get_dept_options.php",
                        type: "POST",
                        data: { faculty_id: facultyID },
                        success: function (response) {
                            try {
                                let res = JSON.parse(response);
                                let deptDropdown = $("#deptDropdown");
                                deptDropdown.empty();

                                if (res.status === 200) {
                                    deptDropdown.append('<option value="">Select Department</option>');
                                    res.data.forEach(function (dept) {
                                        deptDropdown.append('<option value="' + dept + '">' + dept + '</option>');
                                    });

                                    // Add required attribute and show dropdown
                                    deptDropdown.attr('required', true);
                                    deptDropdown.closest(".input-group").show();

                                    // Add validation styling
                                    deptDropdown.removeClass('is-valid').removeClass('is-invalid');
                                } else {
                                    deptDropdown.closest(".input-group").hide();
                                    deptDropdown.removeAttr('required');
                                }
                            } catch (err) {
                                console.error("Invalid JSON", err);
                                $("#deptDropdown").closest(".input-group").hide();
                                $("#deptDropdown").removeAttr('required');
                            }
                        }
                    });
                } else {
                    $("#deptDropdown").closest(".input-group").hide();
                    $("#deptDropdown").removeAttr('required');
                }
            });

            // Department dropdown validation
            $("#deptDropdown").on("change", function () {
                var selectedValue = $(this).val();
                if (selectedValue === "") {
                    $(this).addClass('is-invalid').removeClass('is-valid');
                    // Show error message
                    if (!$(this).siblings('.invalid-feedback').length) {
                        $(this).after('<div class="invalid-feedback">Please select a department.</div>');
                    }
                } else {
                    $(this).addClass('is-valid').removeClass('is-invalid');
                    $(this).siblings('.invalid-feedback').remove();
                }
            });

            // Form submission validation
            $("form").on("submit", function (e) {
                var deptDropdown = $("#deptDropdown");

                // Check if department dropdown is visible and required
                if (deptDropdown.is(':visible') && deptDropdown.attr('required')) {
                    var selectedValue = deptDropdown.val();

                    if (selectedValue === "" || selectedValue === null) {
                        e.preventDefault(); // Prevent form submission

                        // Add error styling
                        deptDropdown.addClass('is-invalid').removeClass('is-valid');

                        // Show error message if not already present
                        if (!deptDropdown.siblings('.invalid-feedback').length) {
                            deptDropdown.after('<div class="invalid-feedback">Please select a department.</div>');
                        }

                        // Focus on the dropdown
                        deptDropdown.focus();

                        // Show SweetAlert instead of alert
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Department Required!',
                                text: 'Please select a department before submitting the form.',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#1fb5ac'
                            });
                        } else {
                            alert('Please select a department before submitting the form.');
                        }

                        return false;
                    }
                }
            });

            // Initially hide the dropdown
            $("#deptDropdown").closest(".input-group").hide();
        });
    </script>

    <?php if (isset($login_success) && $login_success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Login Successful!',
                text: '<?php echo addslashes($_SESSION['login_message']); ?>',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                window.location.href = '<?php echo $_SESSION['redirect_to']; ?>';
            });
        });
    </script>
    <?php 
        // Clear the session variables after use
        unset($_SESSION['login_success']);
        unset($_SESSION['login_message']);
        unset($_SESSION['redirect_to']);
    endif; ?>

    <?php if (isset($error)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Login Failed!',
                text: '<?php echo addslashes($error); ?>',
                confirmButtonText: 'Try Again',
                confirmButtonColor: '#1e4d92',
                allowOutsideClick: true
            });
        });
    </script>
    <?php endif; ?>

</body>
</html>