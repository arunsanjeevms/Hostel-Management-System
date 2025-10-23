<?php
session_start();
include 'db.php';
date_default_timezone_set('Asia/Kolkata'); 
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php");
    exit;
}

// Get roll number from session or database
$roll_no = $_SESSION['roll_number'] ?? null;
if (!$roll_no) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT roll_number FROM students WHERE user_id = ?");
    // Check if prepare succeeded
    if (!$stmt) {
        error_log("DB prepare error: " . $conn->error);
        session_destroy();
        header("Location: login.php?error=db_error");
        exit;
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();

    if ($student && !empty($student['roll_number'])) {
        $_SESSION['roll_number'] = $student['roll_number'];
        $roll_no = $student['roll_number'];
    } else {
        session_destroy();
        header("Location: login.php?error=student_not_found");
        exit;
    }
}

$errors = [];


$general_leave_setting = null;

// Find the *active* General Leave setting (Is_Enabled = 1)
$stmt_gl = $conn->prepare("SELECT GeneralLeave_ID, From_Date, To_Date, Is_Enabled FROM general_leave WHERE Is_Enabled = 1 LIMIT 1");

if ($stmt_gl) {
    $stmt_gl->execute();
    $result_gl = $stmt_gl->get_result();
    if ($result_gl->num_rows > 0) {
        $general_leave_setting = $result_gl->fetch_assoc();
    } else {
        // No general leave is currently enabled
        $general_leave_setting = ['Is_Enabled' => 0];
    }
    $stmt_gl->close();
} else {
    error_log("Database error: Failed to prepare general leave fetch: " . $conn->error);
    $general_leave_setting = ['Is_Enabled' => 0];
}


// ========== APPLY / EDIT LEAVE (Server-Side Logic) ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_leave'])) {
    
    // Sanitize all inputs
    $leave_id = sanitize_input($_POST['leave_id'] ?? '');
    $leave_type_id = sanitize_input($_POST['leave_type_id'] ?? '');
    $from_date = sanitize_input($_POST['from_date'] ?? '');
    $from_time = sanitize_input($_POST['from_time'] ?? ''); // 1-12
    $from_ampm = strtoupper(sanitize_input($_POST['from_ampm'] ?? ''));
    $to_date = sanitize_input($_POST['to_date'] ?? '');
    $to_time = sanitize_input($_POST['to_time'] ?? ''); // 1-12
    $to_ampm = strtoupper(sanitize_input($_POST['to_ampm'] ?? ''));
    $from_minute = sanitize_input($_POST['from_minute'] ?? '00');
    $to_minute = sanitize_input($_POST['to_minute'] ?? '00');
    $reason = sanitize_input($_POST['reason'] ?? '');

    // Input Validation
    if (empty($leave_type_id) && empty($leave_id)) {
        $errors[] = 'Leave type is required.';
    }
    if (empty($from_date) || empty($to_date) || empty($from_time) || empty($to_time)) {
        $errors[] = 'All date and time fields are required.';
    }
    if (empty($reason)) {
        $errors[] = 'Reason for leave is required.';
    }
    
    $start_datetime_obj = null;
    $end_datetime_obj = null;
    $start_datetime = null;
    $end_datetime = null;
    
    // Combine date/time and convert to MySQL datetime format (YYYY-MM-DD HH:MM:SS)
    try {
        $from_datetime_str = "$from_date $from_time:$from_minute $from_ampm";
        $to_datetime_str = "$to_date $to_time:$to_minute $to_ampm";

        $start_datetime_obj = new DateTime($from_datetime_str);
        $end_datetime_obj = new DateTime($to_datetime_str);
        
        $start_datetime = $start_datetime_obj->format('Y-m-d H:i:s');
        $end_datetime = $end_datetime_obj->format('Y-m-d H:i:s');
        
        if ($start_datetime_obj >= $end_datetime_obj) {
            $errors[] = 'From datetime must be before To datetime.';
        }
    } catch (Exception $e) {
        $errors[] = 'Invalid date or time format received. Ensure all fields are valid.';
        error_log("DateTime parsing error: " . $e->getMessage());
    }
    
    //  CRITICAL: UPDATED Server-side check for General Leave restriction (Only for NEW applications)
    if (empty($leave_id) && !empty($leave_type_id) && !empty($start_datetime_obj) && !empty($end_datetime_obj)) { 
        
        // 1. Fetch the name of the selected leave type
        $stmt_check = $conn->prepare("SELECT Leave_Type_Name FROM leave_types WHERE LeaveType_ID = ?");
        $type_name = '';
        if ($stmt_check) {
            $stmt_check->bind_param("i", $leave_type_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $leave_type_data = $result_check->fetch_assoc();
            $stmt_check->close();

            if ($leave_type_data) {
                $type_name = strtolower($leave_type_data['Leave_Type_Name']);
            }
        }
        
        // 2. Check if the selected type is General Leave and validate against the active setting
        if (str_contains($type_name, 'general')) {
            
            // Check if General Leave is enabled AND a valid setting was fetched
            if (!($general_leave_setting['Is_Enabled'] ?? 0)) {
                $errors[] = 'General Leave applications are currently disabled by the Admin or no active period is set.';
            } else {
                // General Leave is enabled, now check if applied dates are within the allowed range
                try {
                    $allowed_from_db = new DateTime($general_leave_setting['From_Date']);
                    $allowed_to_db = new DateTime($general_leave_setting['To_Date']);
                    
                    // The student's application date range must be entirely contained within the allowed window:
                    // (Start >= Allowed_Start) AND (End <= Allowed_End)
                    if ($start_datetime_obj < $allowed_from_db || $end_datetime_obj > $allowed_to_db) {
                        $errors[] = sprintf(
                            'General Leave can only be applied between %s and %s. Your applied dates are outside this range.',
                            $allowed_from_db->format('Y-m-d h:i A'),
                            $allowed_to_db->format('Y-m-d h:i A')
                        );
                    }
                } catch (Exception $e) {
                     $errors[] = 'Internal error: Failed to parse General Leave period dates.';
                }
            }
        }
    }
    // END CRITICAL: UPDATED Server-side check
    

    $proof_file = '';
    // Handle File Upload (logic remains the same)
    if (!empty($_FILES['proof']['name'])) {
        $file = $_FILES['proof'];
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
        
        if (in_array($file['type'], $allowed_types) && $file['error'] === UPLOAD_ERR_OK) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            // Use a secure, unique filename
            $file_extension = pathinfo($file["name"], PATHINFO_EXTENSION);
            $proof_file = $target_dir . uniqid() . '_' . time() . '.' . $file_extension;
            
            // Move uploaded file
            if (!move_uploaded_file($file["tmp_name"], $proof_file)) {
                $errors[] = "Failed to upload proof file.";
                $proof_file = ''; // Clear file path if move fails
            }
        } else if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            $errors[] = "Uploaded file is too large.";
        } else if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
             $errors[] = "Only JPG, PNG, and PDF files are allowed or an unknown file error occurred.";
        }
    }

    if (empty($errors)) {
        if (!empty($leave_id)) {
            // Edit existing leave (logic remains the same)
            
            $sql_parts = ["UPDATE leave_applications SET From_Date=?, To_Date=?, Reason=?"];
            $params = [$start_datetime, $end_datetime, $reason];
            $types = "sss"; 

            if (!empty($leave_type_id)) {
                $sql_parts[] = "LeaveType_ID=?";
                $types .= "i";
                $params[] = (int)$leave_type_id;
            }

            if ($proof_file !== '') {
                $sql_parts[] = "Proof=?";
                $types .= "s";
                $params[] = $proof_file;
            }

            $sql = implode(', ', $sql_parts);
            $sql .= " WHERE Leave_ID=? AND Reg_No=? AND Status='Pending'";
            $types .= "is";
            $params[] = (int)$leave_id;
            $params[] = $roll_no;

            $stmt = $conn->prepare($sql);
            if ($stmt && $stmt->bind_param($types, ...$params) && $stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    header("Location: " . $_SERVER['PHP_SELF'] . "?updated=1");
                    exit();
                } else {
                    $errors[] = "Failed to update leave application. It might have been approved/rejected or the ID is incorrect.";
                }
            } else {
                $errors[] = "Failed to prepare/execute update: " . ($stmt ? $stmt->error : $conn->error);
            }
            if ($stmt) $stmt->close();
        } else {
            // New leave application (logic remains the same)
            if(empty($leave_type_id)) {
                $errors[] = "Leave type is required for a new application.";
            } else {
                $stmt = $conn->prepare("INSERT INTO leave_applications (Reg_No, LeaveType_ID, From_Date, To_Date, Reason, Proof, Status, Applied_Date) VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())");
                
                if (!$stmt) {
                    $errors[] = "Failed to prepare insert statement: " . $conn->error;
                } else {
                    $stmt->bind_param("sissss", $roll_no, $leave_type_id, $start_datetime, $end_datetime, $reason, $proof_file);
    
                    if ($stmt->execute()) {
                        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
                        exit();
                    } else {
                        $errors[] = "Failed to apply for leave: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// ========== CANCEL LEAVE (logic remains the same) ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_leave'])) {
    // ... (Cancel logic as before)
    $leave_id = filter_var($_POST['leave_id'] ?? 0, FILTER_VALIDATE_INT);

    if ($leave_id > 0) {
        $stmt = $conn->prepare("UPDATE leave_applications SET Status='Cancelled' WHERE Leave_ID=? AND Reg_No=? AND Status='Pending'");

        if ($stmt) {
            $stmt->bind_param("is", $leave_id, $roll_no);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                     echo "success";
                } else {
                    http_response_code(400); 
                    echo "error: application already processed or not found";
                }
            } else {
                http_response_code(500);
                echo "error: DB execution failed: " . $stmt->error;
            }
            $stmt->close();
        } else {
            http_response_code(500);
            echo "error: prepare statement failed";
        }
    } else {
        http_response_code(400);
        echo "error: invalid leave id";
    }
    exit;
}

// ========== FETCH LEAVES (logic remains the same) ==========
$rows = [];
$sql = "SELECT la.Leave_ID, la.From_Date, la.To_Date, la.Reason, la.Proof, la.Status, la.Applied_Date, lt.Leave_Type_Name, la.LeaveType_ID
        FROM leave_applications la
        LEFT JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
        WHERE la.Reg_No = ?
        ORDER BY la.Applied_Date DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $roll_no);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($r = $result->fetch_assoc()) {
        $rows[] = $r;
    }
    $stmt->close();
} else {
    error_log("Failed to prepare leave fetch: " . $conn->error);
    $errors[] = "Database error fetching leave history.";
}


// ========== FETCH LEAVE TYPES (logic remains the same) ==========
$leave_types = [];
$stmt_lt = $conn->prepare("SELECT LeaveType_ID, Leave_Type_Name FROM leave_types ORDER BY Priority ASC, Leave_Type_Name ASC");
if($stmt_lt) {
    $stmt_lt->execute();
    $lt_res = $stmt_lt->get_result();
    while ($lt = $lt_res->fetch_assoc()) {
        $leave_types[] = $lt;
    }
    $stmt_lt->close();
} else {
    $errors[] = "Database error: Could not fetch leave types. Please contact admin.";
}

$js_general_leave_setting = json_encode($general_leave_setting ?: ['Is_Enabled' => 0]);


?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Management</title>
    <link rel="icon" type="image/png" sizes="32x32" href="image/icons/mkce_s.png">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-5/bootstrap-5.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>


    <style>
        :root {

            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --topbar-height: 60px;
            --footer-height: 60px;
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --dark-bg: #1a1c23;
            --light-bg: #f8f9fc;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

        }

        /* General Styles with Enhanced Typography */

        /* Content Area Styles */
        .content {
            margin-left: var(--sidebar-width);
            padding-top: var(--topbar-height);
            transition: all 0.3s ease;
            min-height: 100vh;
        }
        body {
            background: linear-gradient(135deg, #e7e9f3ff 0%, #f9f9faff 100%);
           
        }

        /* Content Navigation */
        .content-nav {
            background: linear-gradient(45deg, #4e73df, #1cc88a);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .content-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            gap: 20px;
            overflow-x: auto;
        }

        .content-nav li a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .content-nav li a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .sidebar.collapsed+.content {
            margin-left: var(--sidebar-collapsed-width);
        }

        .breadcrumb-area {
            background: white;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin: 20px;
            padding: 15px 20px;
        }

        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb-item a:hover {
            color: #224abe;
        }



        /* Table Styles */



        .gradient-header {
            --bs-table-bg: transparent;
            --bs-table-color: white;
            background: linear-gradient(135deg, #4CAF50, #2196F3) !important;

            text-align: center;
            font-size: 0.9em;


        }


        td {
            text-align: left;
            font-size: 0.9em;
            vertical-align: middle;
            /* For vertical alignment */
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width) !important;
            }

            .sidebar.mobile-show {
                transform: translateX(0);
            }

            .topbar {
                left: 0 !important;
            }

            .mobile-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
                display: none;
            }

            .mobile-overlay.show {
                display: block;
            }

            .content {
                margin-left: 0 !important;
            }

            .brand-logo {
                display: block;
            }

            .user-profile {
                margin-left: 0;
            }

            .sidebar .logo {
                justify-content: center;
            }

            .sidebar .menu-item span,
            .sidebar .has-submenu::after {
                display: block !important;
            }

            body.sidebar-open {
                overflow: hidden;
            }

            .footer {
                left: 0 !important;
            }

            .content-nav ul {
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: 5px;
            }

            .content-nav ul::-webkit-scrollbar {
                height: 4px;
            }

            .content-nav ul::-webkit-scrollbar-thumb {
                background: rgba(255, 255, 255, 0.3);
                border-radius: 2px;
            }
        }

        .container-fluid {
            padding: 20px;
        }
        
        /* NEW: Custom modal styles for consistent size and reduced width */
        #leaveModal .modal-title {
            font-size: 1.15rem; /* Ensure title is clear */
        }

        #leaveModal .modal-body label,
        #leaveModal .modal-body .form-label,
        #leaveModal .modal-body .form-control,
        #leaveModal .modal-body .form-select,
        #leaveModal .modal-body small {
            font-size: 0.875rem; /* Standard smaller font size (Bootstrap small) for uniformity */
        }

        #leaveModal .modal-body h6 {
            font-size: 1rem; /* Reduce h6 size slightly */
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
        }

        /* loader */
        .loader-container {
            position: fixed;
            left: var(--sidebar-width);
            right: 0;
            top: var(--topbar-height);
            bottom: var(--footer-height);
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            /* Changed from 'none' to show by default */
            justify-content: center;
            align-items: center;
            z-index: 1000;
            transition: left 0.3s ease;
        }

        .sidebar.collapsed+.content .loader-container {
            left: var(--sidebar-collapsed-width);
        }

        @media (max-width: 768px) {
            .loader-container {
                left: 0;
            }
        }

        /* Hide loader when done */
        .loader-container.hide {
            display: none;
        }

        /* Loader Animation */
        .loader {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-radius: 50%;
            border-top: 5px solid var(--primary-color);
            border-right: 5px solid var(--success-color);
            border-bottom: 5px solid var(--primary-color);
            border-left: 5px solid var(--success-color);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .breadcrumb-area {
            background-image: linear-gradient(to top, #fff1eb 0%, #ace0f9 100%);
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin: 20px;
            padding: 15px 20px;
        }

        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb-item a:hover {
            color: #224abe;
        }

        .gradient-header {
            /* Overrides Bootstrap's table background/color */
            --bs-table-bg: transparent;
            --bs-table-color: white;

            /* The actual gradient background */
            background: linear-gradient(135deg, #4CAF50, #2196F3) !important;

            text-align: center;
            font-size: 0.9em;
        }

        td,
        th {
            padding-top: 20px;
            padding-bottom: 10px;
        }

        btn-group-sm>.btn,
        .btn-sm {
            padding: .25rem .5rem;
            font-size: .875rem;
            line-height: 1.5;
            border-radius: .2rem;
        }
    </style>
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="content">

        <div class="loader-container" id="loaderContainer">
            <div class="loader"></div>
        </div>

        <?php include 'topbar.php'; ?>

        <div class="breadcrumb-area custom-gradient">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Leave</li>
                </ol>
            </nav>
        </div>

        <div class="container-fluid">
            <div class="card shadow mb-4">
                <div class="card-header py-3" style="background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0;">
                    <h6 class="m-0 font-weight-bold text-dark">
                        <i class="fas fa-calendar-alt me-2"></i> My Leave Applications
                    </h6>
                </div>
                <div class="card-body">

                    <div class="d-flex justify-content-end mb-3">
                        <button id="openApplyLeave" class="btn btn-secondary shadow-sm">
                            <i class="fas fa-plus me-1"></i> Apply Leave
                        </button>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger w-100 mb-3" role="alert">
                            <strong>Errors:</strong>
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['success'])): ?>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                Swal.fire('Success', 'Leave application submitted successfully!', 'success');
                                const urlParams = new URLSearchParams(window.location.search);
                                urlParams.delete('success');
                                window.history.replaceState({}, document.title, window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : ''));
                            });
                        </script>
                    <?php endif; ?>
                    <?php if (isset($_GET['updated'])): ?>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                Swal.fire('Success', 'Leave application updated successfully!', 'success');
                                const urlParams = new URLSearchParams(window.location.search);
                                urlParams.delete('updated');
                                window.history.replaceState({}, document.title, window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : ''));
                            });
                        </script>
                    <?php endif; ?>

                    <div class="p-3 border rounded shadow-sm">
                        <br>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">

                                <thead class="gradient-header">
                                    <tr>
                                        <th>S.No</th>
                                        <th>Type</th>
                                        <th>From Date/Time</th>
                                        <th>To Date/Time</th>
                                        <th>Reason</th>
                                        <th>Proof</th>
                                        <th>Applied On</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($rows as $row):
                                        // Simplified variable assignment and date formatting
                                        $leaveID = htmlspecialchars($row['Leave_ID']); // Still used for data attributes
                                        $fromDateDisplay = date('Y-m-d h:i A', strtotime($row['From_Date']));
                                        $toDateDisplay = date('Y-m-d h:i A', strtotime($row['To_Date']));
                                        $appliedDateDisplay = date('Y-m-d', strtotime($row['Applied_Date']));
                                        $status = htmlspecialchars($row['Status']);
                                        $proofUrl = htmlspecialchars($row['Proof'] ?? '');

                                        // Status badge logic using a cleaner ternary or match statement
                                        $statusClass = match ($status) {
                                            'Approved' => 'bg-success',
                                            'Rejected by parents', 'Rejected by Admin', 'Rejected by HOD' => 'bg-danger',
                                            'Cancelled' => 'bg-secondary',
                                            'forwarded to admin' => 'bg-info',
                                            default => 'bg-warning',
                                        };
                                        ?>

                                        <tr data-leave-id="<?= $leaveID ?>">
                                            <td class="small-text"><?= $leaveID ?></td>
                                            <td class="small-text"><?= htmlspecialchars($row['Leave_Type_Name']) ?></td>
                                            <td class="small-text"
                                                data-from-date="<?= htmlspecialchars($row['From_Date']) ?>">
                                                <?= $fromDateDisplay ?>
                                            </td>
                                            <td class="small-text" data-to-date="<?= htmlspecialchars($row['To_Date']) ?>">
                                                <?= $toDateDisplay ?>
                                            </td>
                                            <td class="small-text text-truncate" style="max-width: 150px;">
                                                <?= htmlspecialchars($row['Reason']) ?>
                                            </td>
                                            <td class="small-text">
                                                <?php if (!empty($proofUrl)): ?>
                                                    <button type="button" class="btn btn-primary btn-sm view-proof"
                                                        data-proof-url="<?= $proofUrl ?>">
                                                        View
                                                    </button>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>

                                            <td class="small-text"><?= $appliedDateDisplay ?></td>
                                            <td class="small-text">
                                                <span class="badge <?= $statusClass ?>"><?= $status ?></span>
                                            </td>
                                            <td class="small-text">
                                                <?php if ($status == 'Pending'): ?>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button class="btn btn-sm btn-info edit-leave" data-id="<?= $leaveID ?>"
                                                            data-type-id="<?= htmlspecialchars($row['LeaveType_ID']) ?>"
                                                            data-reason="<?= htmlspecialchars($row['Reason']) ?>"
                                                            title="Edit Leave">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger cancel-leave"
                                                            data-id="<?= $leaveID ?>" title="Cancel Leave">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    No action available
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>

                            </table>
                        </div>

                        <div class="modal fade" id="leaveModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-md modal-dialog-centered">
                                <div class="modal-content form-model-style">
                                    <form id="leaveForm" class="p-0 needs-validation" novalidate
                                        enctype="multipart/form-data" method="POST">

                                        <div class="modal-header bg-primary text-white p-3 rounded-top">
                                            <h5 class="modal-title" id="leaveModalTitle">Apply for Leave</h5>
                                            <button type="button" class="btn-close btn-close-white"
                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body p-4">
                                            <input type="hidden" id="leave_id" name="leave_id" value="">
                                            <input type="hidden" name="apply_leave" value="1">

                                            <div class="mb-3">
                                                <label for="leave_type_id" class="form-label required-label">Leave
                                                    Type</label>
                                                <select name="leave_type_id" id="leave_type_id"
                                                    class="form-select" required>
                                                    <option value="">Select Leave Type</option>
                                                    <?php foreach ($leave_types as $lt): 
                                                        $lt_name_lower = htmlspecialchars(strtolower($lt['Leave_Type_Name']));
                                                        $is_general = str_contains($lt_name_lower, 'general');
                                                        $disabled_attr = ($is_general && !($general_leave_setting['Is_Enabled'] ?? 0)) ? 'disabled data-general-disabled="true"' : '';
                                                        $title_attr = ($is_general && !($general_leave_setting['Is_Enabled'] ?? 0)) ? 'title="General Leave is currently disabled by the Admin."' : '';
                                                    ?>
                                                        <option value="<?php echo htmlspecialchars($lt['LeaveType_ID']); ?>"
                                                            data-type-name="<?php echo $lt_name_lower; ?>"
                                                            <?= $disabled_attr ?> <?= $title_attr ?>>
                                                            <?php echo htmlspecialchars($lt['Leave_Type_Name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="invalid-feedback">Please select a leave type.</div>
                                                <div id="general_leave_info"></div> 
                                            </div>

                                            <h6>Leave Duration</h6>

                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="p-3 border rounded h-100">
                                                        <label for="from_date" class="form-label required-label">From
                                                            Date</label>
                                                        <input type="date" name="from_date" id="from_date"
                                                            class="form-control" required>
                                                        <div class="invalid-feedback">Please select a start date.</div>

                                                        <label class="form-label mt-3 required-label">From Time</label>
                                                        <div class="input-group">
                                                            <select name="from_time" id="from_time" class="form-select"
                                                                required>
                                                                <?php for ($h = 1; $h <= 12; $h++): ?>
                                                                    <option
                                                                        value="<?php echo str_pad($h, 2, '0', STR_PAD_LEFT); ?>">
                                                                        <?php echo str_pad($h, 2, '0', STR_PAD_LEFT); ?>
                                                                    </option>
                                                                <?php endfor; ?>
                                                            </select>
                                                            <select name="from_minute" id="from_minute"
                                                                class="form-select">
                                                                <?php for ($m = 0; $m < 60; $m += 5): ?>
                                                                    <option
                                                                        value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>">
                                                                        <?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>
                                                                    </option>
                                                                <?php endfor; ?>
                                                            </select>
                                                            <select name="from_ampm" id="from_ampm" class="form-select"
                                                                required>
                                                                <option>AM</option>
                                                                <option selected>PM</option>
                                                            </select>
                                                            <div class="invalid-feedback">Please select a valid start
                                                                time.</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="p-3 border rounded h-100">
                                                        <label for="to_date" class="form-label required-label">To
                                                            Date</label>
                                                        <input type="date" name="to_date" id="to_date"
                                                            class="form-control" required>
                                                        <div class="invalid-feedback">Please select an end date.</div>

                                                        <label class="form-label mt-3 required-label">To Time</label>
                                                        <div class="input-group">
                                                            <select name="to_time" id="to_time" class="form-select"
                                                                required>
                                                                <?php for ($h = 1; $h <= 12; $h++): ?>
                                                                    <option
                                                                        value="<?php echo str_pad($h, 2, '0', STR_PAD_LEFT); ?>">
                                                                        <?php echo str_pad($h, 2, '0', STR_PAD_LEFT); ?>
                                                                    </option>
                                                                <?php endfor; ?>
                                                            </select>
                                                            <select name="to_minute" id="to_minute" class="form-select">
                                                                <?php for ($m = 0; $m < 60; $m += 5): ?>
                                                                    <option
                                                                        value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>">
                                                                        <?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>
                                                                    </option>
                                                                <?php endfor; ?>
                                                            </select>
                                                            <select name="to_ampm" id="to_ampm" class="form-select"
                                                                required>
                                                                <option>AM</option>
                                                                <option selected>PM</option>
                                                            </select>
                                                            <div class="invalid-feedback">Please select a valid end
                                                                time.</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <label for="reason" class="form-label required-label">Reason</label>
                                                <textarea name="reason" id="reason" class="form-control" rows="3"
                                                    required></textarea>
                                                <div class="invalid-feedback">Please enter the reason for your leave.
                                                </div>
                                            </div>

                                            <div class="mt-4 p-3 border rounded">
                                                <label for="proof" class="form-label">Upload Proof (Optional)</label>
                                                <div id="current_proof_preview" class="mb-2"></div>
                                                <input type="file" name="proof" id="proof" class="form-control">
                                                <small id="proof-help" class="form-text text-muted">Upload proof if
                                                    required (PDF, JPG, PNG).</small>
                                            </div>
                                        </div>
                                        <div
                                            class="modal-footer d-flex justify-content-end bg-light p-3 rounded-bottom">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-success" id="submitLeaveBtn">Submit
                                                Application</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="proofModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Leave Proof</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body" id="proofModalBody">
                                        <p class="text-center">Loading proof</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php include 'footer.php'; ?>

                        <script>
                            
                            // 🔑 Get General Leave Setting from PHP
                            const GENERAL_LEAVE_SETTING = <?php echo $js_general_leave_setting; ?>;

                            // CRITICAL FIX: Robust, universal date/time parsing function (copied from previous step)
                            function parseDateTimeString(dateTimeStr) {
                                // Split YYYY-MM-DD and HH:MM:SS
                                const [datePart, timePart] = dateTimeStr.split(' ');
                                // Ensure datePart is not empty before splitting
                                if (!datePart || !timePart) {
                                    console.error("Invalid datetime string received:", dateTimeStr);
                                    return { date: '', hour12: '12', minute: '00', ampm: 'AM' };
                                }
                                const [year, month, day] = datePart.split('-').map(Number);
                                const [hour24, minute] = timePart.split(':').map(Number); 

                                // Convert 24-hour to 12-hour format
                                const ampm = hour24 >= 12 ? 'PM' : 'AM';
                                const hour12 = hour24 % 12 || 12; 

                                return {
                                    date: `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`,
                                    hour12: String(hour12).padStart(2, '0'),
                                    minute: String(minute).padStart(2, '0'),
                                    ampm: ampm
                                };
                            }
                            
                            $(document).ready(function () {
                                
                                // Function to format Date objects for display
                                const formatDateDisplay = (date) => date.toLocaleDateString(undefined, {
                                    year: 'numeric', 
                                    month: 'short', 
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: true
                                });

                                // 🔑 UPDATED: Function to check General Leave Restriction and update UI
                                function checkGeneralLeaveRestriction(isEditing = false) {
                                    const leaveTypeSelect = $('#leave_type_id');
                                    const generalLeaveOption = leaveTypeSelect.find('option[data-type-name*="general"]');
                                    const isGeneralLeaveEnabled = GENERAL_LEAVE_SETTING && GENERAL_LEAVE_SETTING.Is_Enabled == 1;
                                    
                                    // Get or create info div
                                    let infoDiv = $('#general_leave_info');
                                    infoDiv.empty().hide();

                                    // 1. Client-Side Disabling Logic
                                    // Disable the option for NEW applications if the admin has not enabled it.
                                    if (!isEditing) { 
                                        if (!isGeneralLeaveEnabled) {
                                            generalLeaveOption.prop('disabled', true).attr('title', 'General Leave is currently disabled by the Admin.');
                                            if (leaveTypeSelect.val() === generalLeaveOption.val()) {
                                                leaveTypeSelect.val(''); // Reset selection if disabled option was selected
                                            }
                                        } else {
                                            generalLeaveOption.prop('disabled', false).removeAttr('title');
                                        }
                                    } else {
                                        // For editing, ensure the selected option is not disabled even if it is General Leave
                                        leaveTypeSelect.find('option[data-general-disabled="true"]').prop('disabled', false);
                                    }
                                    
                                    // 2. Display Restriction Range and set date attributes if General Leave is Selected
                                    if (isGeneralLeaveEnabled && leaveTypeSelect.val() === generalLeaveOption.val()) {
                                        const fromDate = new Date(GENERAL_LEAVE_SETTING.From_Date);
                                        const toDate = new Date(GENERAL_LEAVE_SETTING.To_Date);
                                        
                                        infoDiv.html(`
                                            <i class="fas fa-info-circle text-info me-1"></i> 
                                            <strong>General Leave is active!</strong> 
                                            You can only apply for leave entirely between:<br>
                                            <strong>From:</strong> ${formatDateDisplay(fromDate)}<br>
                                            <strong>To:</strong> ${formatDateDisplay(toDate)}
                                        `).show();
                                        
                                        // Set min/max dates on date inputs for user experience
                                        const allowedMinDateStr = fromDate.toISOString().split('T')[0];
                                        const allowedMaxDateStr = toDate.toISOString().split('T')[0];

                                        // Set min date to TODAY (or the allowed start date, whichever is later)
                                        const today = new Date();
                                        today.setHours(0, 0, 0, 0);
                                        const minDateEffective = fromDate > today ? fromDate : today;
                                        
                                        $('#from_date').attr('min', minDateEffective.toISOString().split('T')[0]);
                                        $('#to_date').attr('max', allowedMaxDateStr);
                                        
                                    } else {
                                        // Clear min/max constraints when not General Leave
                                        $('#from_date').removeAttr('min'); 
                                        $('#to_date').removeAttr('max'); 
                                        setDateRestrictions(); // Re-apply default restrictions
                                    }
                                }
                                
                                // Initialize DataTables for the Leave History
                                $('#dataTable').DataTable({
                                    "order": [
                                        [0, "desc"]
                                    ], 
                                    "pageLength": 10,
                                    "responsive": true
                                });

                                const leaveModal = new bootstrap.Modal(document.getElementById('leaveModal'));
                                const proofModal = new bootstrap.Modal(document.getElementById('proofModal'));


                                // Function to set the minimum date for date inputs based on leave type (Default logic)
                                function setDateRestrictions() {
                                    const leaveTypeSelect = $('#leave_type_id').find('option:selected');
                                    const leaveType = leaveTypeSelect.data('type-name') ? leaveTypeSelect.data('type-name').toLowerCase() : '';
                                    const fromDateInput = $('#from_date');
                                    const toDateInput = $('#to_date');

                                    // Clear existing custom min/max from General Leave checks
                                    fromDateInput.removeAttr('min');
                                    toDateInput.removeAttr('max'); 
                                    
                                    const today = new Date();
                                    today.setHours(0, 0, 0, 0);
                                    const todayStr = today.toISOString().split('T')[0];

                                    const tomorrow = new Date(today);
                                    tomorrow.setDate(today.getDate() + 1);
                                    const tomorrowStr = tomorrow.toISOString().split('T')[0];

                                    // Logic for setting min date restriction
                                    if (leaveType.includes('emergency') || leaveType.includes('od')) {
                                        fromDateInput.attr('min', todayStr);
                                    } else if (leaveType.includes('general')) {
                                        // Do nothing here, as checkGeneralLeaveRestriction handles the min/max dates for general leave
                                        // But if General Leave is NOT active, it falls to the next else.
                                    } else {
                                        // Default: minimum start is tomorrow
                                        fromDateInput.attr('min', tomorrowStr);
                                    }

                                    // Make sure To Date cannot be before From Date
                                    fromDateInput.off('change').on('change', function () {
                                        const selectedFromDate = this.value;
                                        const currentToDate = toDateInput.val();

                                        toDateInput.attr('min', selectedFromDate);

                                        if (currentToDate && selectedFromDate > currentToDate) {
                                            toDateInput.val(selectedFromDate);
                                        }
                                    }).trigger('change');
                                }
                                
                                // Re-run restrictions when leave type changes
                                $('#leave_type_id').on('change', function() {
                                    checkGeneralLeaveRestriction($('#leave_id').val() !== '');
                                });


                                // 1. OPEN APPLY MODAL (NEW LEAVE)
                                $('#openApplyLeave').on('click', function () {
                                    $('#leaveModalTitle').text('Apply for Leave');
                                    $('#leaveForm')[0].reset();
                                    $('#leave_id').val(''); // Critical: Empty leave_id for new application
                                    $('#submitLeaveBtn').text('Submit Application').prop('disabled', false).removeClass('disabled');
                                    $('#leaveForm').find('.is-invalid').removeClass('is-invalid');
                                    $('#leaveForm').removeClass('was-validated');
                                    $('#current_proof_preview').empty();
                                    $('#proof-help').text('Upload proof if required (PDF, JPG, PNG).');
                                    
                                    // Set default time to 06:00 PM
                                    $('#from_time').val('06');
                                    $('#to_time').val('06');
                                    $('#from_ampm').val('PM');
                                    $('#to_ampm').val('PM');
                                    $('#from_minute').val('00');
                                    $('#to_minute').val('00');
                                    
                                    checkGeneralLeaveRestriction(false); // Check restriction for NEW leave
                                    setDateRestrictions(); // Apply default date restrictions (may be overridden by checkGeneralLeaveRestriction)
                                    
                                    leaveModal.show();
                                });

                                // 2. OPEN EDIT MODAL (EDIT LEAVE)
                                $('#dataTable').on('click', '.edit-leave', function () {
                                    const id = $(this).data('id');
                                    const typeId = $(this).data('type-id');
                                    const reason = $(this).data('reason');
                                    const row = $(this).closest('tr');

                                    const fromDateTime = row.find('td[data-from-date]').data('from-date');
                                    const toDateTime = row.find('td[data-to-date]').data('to-date');
                                    const proofUrl = row.find('.view-proof').data('proof-url');
                                    
                                    const fromParts = parseDateTimeString(fromDateTime);
                                    const toParts = parseDateTimeString(toDateTime);

                                    // Populate form
                                    $('#leaveModalTitle').text('Edit Leave Application #' + id);
                                    $('#leaveForm')[0].reset();
                                    $('#leave_id').val(id); // Critical: Set leave_id for edit application
                                    $('#leave_type_id').val(typeId);
                                    
                                    $('#from_date').val(fromParts.date); 
                                    $('#to_date').val(toParts.date); 
                                    
                                    $('#from_time').val(fromParts.hour12); 
                                    $('#to_time').val(toParts.hour12);
                                    
                                    $('#from_minute').val(fromParts.minute);
                                    $('#to_minute').val(toParts.minute);
                                    
                                    $('#from_ampm').val(fromParts.ampm);
                                    $('#to_ampm').val(toParts.ampm);
                                    
                                    $('#reason').val(reason);
                                    $('#submitLeaveBtn').text('Update Application').prop('disabled', false).removeClass('disabled');
                                    $('#leaveForm').find('.is-invalid').removeClass('is-invalid');
                                    $('#leaveForm').removeClass('was-validated');


                                    // Handle proof preview
                                    $('#current_proof_preview').empty();
                                    if (proofUrl) {
                                        $('#proof-help').text('Upload a new file to replace the existing proof. Leave blank to keep the current proof.');
                                        let previewHtml = '';
                                        const finalProofUrl = proofUrl; 
                                        
                                        if (finalProofUrl.toLowerCase().endsWith('.pdf')) {
                                            previewHtml = '<p class="text-info"><i class="fas fa-file-pdf"></i> <strong>Existing PDF Proof Attached.</strong></p>';
                                        } else if (/\.(jpe?g|png|webp)$/i.test(finalProofUrl)) {
                                            previewHtml = '<img src="' + finalProofUrl + '" alt="Current Proof" class="img-thumbnail" style="max-height: 100px;">';
                                        } else {
                                            previewHtml = '<p class="text-info"><strong>Existing Proof File Attached.</strong></p>';
                                        }
                                        $('#current_proof_preview').html(previewHtml);
                                    } else {
                                        $('#proof-help').text('Upload proof if required (PDF, JPG, PNG).');
                                    }

                                    checkGeneralLeaveRestriction(true); // Ignore restriction for EDITING
                                    setDateRestrictions(); // Re-apply default date restrictions
                                    leaveModal.show();
                                });

                                // 3. CANCEL LEAVE
                                $('#dataTable').on('click', '.cancel-leave', function () {
                                    const leaveId = $(this).data('id');

                                    Swal.fire({
                                        title: 'Are you sure?',
                                        text: "Do you want to cancel this leave application? This cannot be undone.",
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonColor: '#d33',
                                        confirmButtonText: 'Yes, cancel it!',
                                        cancelButtonText: 'No, keep it'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            
                                            Swal.fire({
                                                title: 'Cancelling...',
                                                text: 'Attempting to cancel application...',
                                                allowOutsideClick: false,
                                                didOpen: () => {
                                                    Swal.showLoading();
                                                    $.ajax({
                                                        type: "POST",
                                                        url: "<?php echo $_SERVER['PHP_SELF']; ?>", 
                                                        data: {
                                                            cancel_leave: 1,
                                                            leave_id: leaveId
                                                        },
                                                        success: function (response) {
                                                            Swal.close();
                                                        
                                                            if (response.trim() === "success") {
                                                                Swal.fire('Cancelled!', 'Your leave application has been cancelled.', 'success').then(() => {
                                                                    location.reload(); 
                                                                });
                                                            } else {
                                                            
                                                                Swal.fire('Error!', 'Failed to cancel application. Server message: ' + response, 'error');
                                                            }
                                                        },
                                                        error: function (xhr) {
                                                            Swal.close();
                                                            let errorMessage = xhr.responseText || 'An unknown error occurred.';
                                                            Swal.fire('Error!', 'An error occurred while attempting to cancel the application. Status: ' + xhr.status + ', Message: ' + errorMessage, 'error');
                                                        }
                                                    });
                                                }
                                            });
                                        }
                                    });
                                });

                                // 4. VIEW PROOF MODAL
                                $('#dataTable').on('click', '.view-proof', function () {
                                    const proofUrl = $(this).data('proof-url');
                                    const modalBody = $('#proofModalBody');
                                    modalBody.empty();
                                    modalBody.html('<p class="text-center">Loading proof...</p>');
                                    
                                    const finalProofUrl = proofUrl; 
                                    
                                    let contentHtml = '';
                                    if (!finalProofUrl) {
                                        contentHtml = '<div class="alert alert-warning text-center">No proof document found for this application.</div>';
                                    } else if (finalProofUrl.toLowerCase().endsWith('.pdf')) {
                                        contentHtml = `<iframe src="${finalProofUrl}" style="width: 100%; height: 75vh;" frameborder="0"></iframe>`;
                                    } else if (/\.(jpe?g|png|webp)$/i.test(finalProofUrl)) {
                                        contentHtml = `<div class="text-center"><img src="${finalProofUrl}" alt="Leave Proof" class="img-fluid border rounded shadow-sm" style="max-height: 80vh;"></div>`;
                                    } else {
                                        contentHtml = `<div class="alert alert-info text-center">Unsupported file type. <a href="${finalProofUrl}" target="_blank">Download file</a> to view.</div>`;
                                    }

                                    modalBody.html(contentHtml);
                                    proofModal.show();
                                });

                                // 5. FORM SUBMISSION VALIDATION (Bootstrap native)
                                const form = document.getElementById('leaveForm');
                                form.addEventListener('submit', function (event) {
                                    
                                    // General Leave front-end check for better UX
                                    const leaveId = $('#leave_id').val();
                                    const leaveTypeSelect = $('#leave_type_id').find('option:selected');
                                    const leaveType = leaveTypeSelect.data('type-name') ? leaveTypeSelect.data('type-name').toLowerCase() : '';
                                    
                                    if (!leaveId && leaveType.includes('general')) {
                                        const isGeneralLeaveEnabled = GENERAL_LEAVE_SETTING && GENERAL_LEAVE_SETTING.Is_Enabled == 1;
                                        
                                        if (!isGeneralLeaveEnabled) {
                                            event.preventDefault();
                                            event.stopPropagation();
                                            Swal.fire('Restricted', 'General Leave applications are currently disabled by the Admin.', 'error');
                                            $('#leave_type_id').val('').addClass('is-invalid');
                                            return;
                                        }
                                        
                                        // Optional Client-Side Date Range Check (The server side is the authoritative check)
                                        if (isGeneralLeaveEnabled) {
                                            const fromDateInput = new Date($('#from_date').val() + " " + $('#from_time').val() + ":" + $('#from_minute').val() + " " + $('#from_ampm').val());
                                            const toDateInput = new Date($('#to_date').val() + " " + $('#to_time').val() + ":" + $('#to_minute').val() + " " + $('#to_ampm').val());
                                            const allowedFrom = new Date(GENERAL_LEAVE_SETTING.From_Date);
                                            const allowedTo = new Date(GENERAL_LEAVE_SETTING.To_Date);

                                            if (fromDateInput < allowedFrom || toDateInput > allowedTo) {
                                                event.preventDefault();
                                                event.stopPropagation();
                                                Swal.fire('Date Error', `General Leave must be entirely between ${formatDateDisplay(allowedFrom)} and ${formatDateDisplay(allowedTo)}.`, 'error');
                                                $('#from_date').addClass('is-invalid');
                                                $('#to_date').addClass('is-invalid');
                                                return;
                                            }
                                        }
                                    }
                                    
                                    if (!form.checkValidity()) {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        if (!$('#submitLeaveBtn').prop('disabled')) {
                                            Swal.fire('Validation Error', 'Please fill out all required fields correctly.', 'error');
                                        }
                                    } else {
                                        $('#submitLeaveBtn').text($('#leave_id').val() ? 'Updating...' : 'Submitting...').prop('disabled', true).addClass('disabled');
                                    }
                                    form.classList.add('was-validated');
                                }, false);
                                
                                // Hide loader once all JS is ready and executed
                                $('#loaderContainer').addClass('hide');
                            });
                        </script>

                    </div>
                </div>
            </div>
        </div>
</body>
</html>

