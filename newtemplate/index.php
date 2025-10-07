<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'hostel';

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}

$errors = [];

// ========== APPLY LEAVE ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_leave'])) {
    $leave_type = trim($_POST['leave_type'] ?? '');
    $from_date = trim($_POST['from_date'] ?? '');
    $from_time = trim($_POST['from_time'] ?? '');
    $to_date = trim($_POST['to_date'] ?? '');
    $to_time = trim($_POST['to_time'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    $start_datetime = "$from_date $from_time";
    $end_datetime = "$to_date $to_time";

    if ($leave_type === '')
        $errors[] = 'Leave Type is required.';
    if ($from_date === '' || $to_date === '')
        $errors[] = 'From and To date are required.';
    if (strtotime($start_datetime) > strtotime($end_datetime))
        $errors[] = 'Start date cannot be after end date.';

    $proof_path = null;
    if (!empty($_FILES['proof']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir))
            mkdir($targetDir, 0777, true);
        $filename = time() . "_" . basename($_FILES["proof"]["name"]);
        $targetFile = $targetDir . $filename;
        if (move_uploaded_file($_FILES["proof"]["tmp_name"], $targetFile)) {
            $proof_path = $targetFile;
        } else {
            $errors[] = "Failed to upload proof file.";
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO leave_applications (leave_type, from_date, to_date, reason, proof_path, final_status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("sssss", $leave_type, $start_datetime, $end_datetime, $reason, $proof_path);
        $stmt->execute();
        $stmt->close();

        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit();
    }
}

// ========== EDIT LEAVE ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_leave'])) {
    $edit_leave_id = $_POST['edit_leave_id'] ?? '';
    $edit_from_date = $_POST['edit_from_date'] ?? '';
    $edit_from_time = $_POST['edit_from_time'] ?? '';
    $edit_to_date = $_POST['edit_to_date'] ?? '';
    $edit_to_time = $_POST['edit_to_time'] ?? '';
    $edit_reason = $_POST['edit_reason'] ?? '';

    $start_datetime = "$edit_from_date $edit_from_time";
    $end_datetime = "$edit_to_date $edit_to_time";

    if (strtotime($start_datetime) > strtotime($end_datetime))
        $errors[] = 'Start date cannot be after end date.';

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE leave_applications SET from_date=?, to_date=?, reason=? WHERE leave_id=?");
        $stmt->bind_param("sssi", $start_datetime, $end_datetime, $edit_reason, $edit_leave_id);
        $stmt->execute();
        $stmt->close();

        header("Location: " . $_SERVER['PHP_SELF'] . "?updated=1");
        exit();
    }
}

// ========== CANCEL LEAVE ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_leave'])) {
    $cancel_leave_id = $_POST['leave_id'] ?? '';
    if ($cancel_leave_id) {
        $stmt = $conn->prepare("UPDATE leave_applications SET final_status='Cancelled' WHERE leave_id=?");
        $stmt->bind_param("i", $cancel_leave_id);
        $stmt->execute();
        $stmt->close();

        header("Location: " . $_SERVER['PHP_SELF'] . "?deleted=1");
        exit();
    }
}

// ========== FETCH ALL LEAVES ==========
$stmt = $conn->prepare("SELECT * FROM leave_applications ORDER BY applied_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIC</title>
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
    </style>
</head>
<div class="container-fluid">
        <div class="custom-tabs"></div>
<body>
    
        <?php include 'sidebar.php'; ?>
        <div class="content">

            <?php include 'topbar.php'; ?>
            <div class="container py-5">
                <h2 class="mb-4 text-center">Leave Applications</h2>

                <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#leaveModal">Apply
                    Leave</button>

                <div class="table-responsive">
                    <table id="leaveTable" class="table table-bordered table-striped">
                        <thead class="table-success text-center">
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Applied At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No records found</td>
                                </tr>
                            <?php else:
                                foreach ($rows as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['leave_id']); ?></td>
                                        <td><?= htmlspecialchars($r['leave_type']); ?></td>
                                        <td><?= date('M d, Y h:i A', strtotime($r['from_date'])); ?></td>
                                        <td><?= date('M d, Y h:i A', strtotime($r['to_date'])); ?></td>
                                        <td><?= htmlspecialchars($r['reason']); ?></td>
                                        <td>
                                            <?php
                                            $status = $r['final_status'];
                                            $badge = match ($status) {
                                                'Pending' => 'warning',
                                                'Approved' => 'success',
                                                'Rejected' => 'danger',
                                                'Cancelled' => 'secondary',
                                                default => 'info'
                                            };
                                            ?>
                                            <span class="badge bg-<?= $badge ?>"><?= $status ?></span>
                                        </td>
                                        <td><?= date('M d, Y h:i A', strtotime($r['applied_at'])); ?></td>
                                        <td>
                                            <?php if ($r['final_status'] === 'Pending'): ?>
                                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#editModal<?= $r['leave_id']; ?>">Edit</button>

                                                <form method="post" class="d-inline cancel-form">
                                                    <input type="hidden" name="leave_id" value="<?= $r['leave_id']; ?>">
                                                    <input type="hidden" name="cancel_leave" value="1">
                                                    <button type="button" class="btn btn-danger btn-sm cancel-btn">Cancel</button>
                                                </form>

                                                <!-- Edit Modal -->
                                                <div class="modal fade" id="editModal<?= $r['leave_id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <form method="post">
                                                                <input type="hidden" name="edit_leave_id"
                                                                    value="<?= $r['leave_id']; ?>">
                                                                <div class="modal-header bg-primary text-white">
                                                                    <h5 class="modal-title">Edit Leave</h5>
                                                                    <button type="button" class="btn-close btn-close-white"
                                                                        data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="row mb-3">
                                                                        <div class="col">
                                                                            <label>From Date</label>
                                                                            <input type="date" name="edit_from_date"
                                                                                class="form-control"
                                                                                value="<?= date('Y-m-d', strtotime($r['from_date'])); ?>"
                                                                                required>
                                                                        </div>
                                                                        <div class="col">
                                                                            <label>From Time</label>
                                                                            <input type="time" name="edit_from_time"
                                                                                class="form-control"
                                                                                value="<?= date('H:i', strtotime($r['from_date'])); ?>"
                                                                                required>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row mb-3">
                                                                        <div class="col">
                                                                            <label>To Date</label>
                                                                            <input type="date" name="edit_to_date"
                                                                                class="form-control"
                                                                                value="<?= date('Y-m-d', strtotime($r['to_date'])); ?>"
                                                                                required>
                                                                        </div>
                                                                        <div class="col">
                                                                            <label>To Time</label>
                                                                            <input type="time" name="edit_to_time"
                                                                                class="form-control"
                                                                                value="<?= date('H:i', strtotime($r['to_date'])); ?>"
                                                                                required>
                                                                        </div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label>Reason</label>
                                                                        <textarea name="edit_reason" class="form-control" rows="3"
                                                                            required><?= htmlspecialchars($r['reason']); ?></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="submit" name="update_leave"
                                                                        class="btn btn-primary">Update</button>
                                                                    <button type="button" class="btn btn-secondary"
                                                                        data-bs-dismiss="modal">Close</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

           <!-- Apply Leave Modal -->
<div class="modal fade" id="leaveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data" id="applyLeaveForm">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Apply Leave</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Leave Type</label>
                        <select name="leave_type" id="leaveType" class="form-select" required>
                            <option value="">-- Select --</option>
                            <option value="General">General</option>
                            <option value="Leave">Leave</option>
                            <option value="Emergency">Emergency</option>
                            <option value="OD">OD</option>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label>From Date</label>
                            <input type="date" name="from_date" id="fromDate" class="form-control" required>
                        </div>
                        <div class="col">
                            <label>From Time</label>
                            <input type="time" name="from_time" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label>To Date</label>
                            <input type="date" name="to_date" id="toDate" class="form-control" required>
                        </div>
                        <div class="col">
                            <label>To Time</label>
                            <input type="time" name="to_time" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Reason</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Proof File (optional)</label>
                        <input type="file" name="proof" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="apply_leave" class="btn btn-success">Submit</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function setMinDates(leaveTypeSelect, fromDateInput, toDateInput) {
        const today = new Date();
        const dd = String(today.getDate()).padStart(2, '0');
        const mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
        const yyyy = today.getFullYear();

        let minDate = '';

        if (leaveTypeSelect.value === "General" || leaveTypeSelect.value === "Leave") {
            // Cannot select past or current date → start from tomorrow
            const tomorrow = new Date(today);
            tomorrow.setDate(today.getDate() + 1);
            minDate = tomorrow.toISOString().split('T')[0];
        } else if (leaveTypeSelect.value === "Emergency" || leaveTypeSelect.value === "OD") {
            // Cannot select past date → start from today
            minDate = today.toISOString().split('T')[0];
        } else {
            minDate = today.toISOString().split('T')[0];
        }

        fromDateInput.min = minDate;
        toDateInput.min = minDate;
    }

    // Apply Leave Modal
    const leaveType = document.getElementById('leaveType');
    const fromDate = document.getElementById('fromDate');
    const toDate = document.getElementById('toDate');

    leaveType.addEventListener('change', () => {
        setMinDates(leaveType, fromDate, toDate);
    });

    // Set initial value when modal opens
    $('#leaveModal').on('shown.bs.modal', function () {
        setMinDates(leaveType, fromDate, toDate);
    });
</script>


            <script>
                $(document).ready(function () {
                    $('#leaveTable').DataTable({ responsive: true });

                    $('.cancel-btn').on('click', function () {
                        const form = $(this).closest('form');
                        Swal.fire({
                            title: 'Are you sure?',
                            text: 'Do you want to cancel this leave?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Yes, cancel it!'
                        }).then((result) => {
                            if (result.isConfirmed) form.submit();
                        });
                    });

                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.has('success')) {
                        Swal.fire({ icon: 'success', title: 'Success', text: 'Leave applied successfully!', timer: 2000, showConfirmButton: false });
                        window.history.replaceState({}, document.title, window.location.pathname);
                    } else if (urlParams.has('updated')) {
                        Swal.fire({ icon: 'info', title: 'Updated', text: 'Leave updated successfully!', timer: 2000, showConfirmButton: false });
                        window.history.replaceState({}, document.title, window.location.pathname);
                    } else if (urlParams.has('deleted')) {
                        Swal.fire({ icon: 'error', title: 'Cancelled', text: 'Leave cancelled successfully!', timer: 2000, showConfirmButton: false });
                        window.history.replaceState({}, document.title, window.location.pathname);
                    }
                });
            </script>
</body>

</html>