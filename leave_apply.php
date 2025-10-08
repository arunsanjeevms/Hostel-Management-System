<?php
include_once "dbconnect.php"; // uses $pdo connection

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
        $sql = "INSERT INTO leave_applications (leave_type, from_date, to_date, reason, proof_path, final_status)
                VALUES (:leave_type, :from_date, :to_date, :reason, :proof_path, 'Pending')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':leave_type' => $leave_type,
            ':from_date' => $start_datetime,
            ':to_date' => $end_datetime,
            ':reason' => $reason,
            ':proof_path' => $proof_path
        ]);
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
        $sql = "UPDATE leave_applications 
                SET from_date = :from_date, to_date = :to_date, reason = :reason 
                WHERE leave_id = :leave_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':from_date' => $start_datetime,
            ':to_date' => $end_datetime,
            ':reason' => $edit_reason,
            ':leave_id' => $edit_leave_id
        ]);
        header("Location: " . $_SERVER['PHP_SELF'] . "?updated=1");
        exit();
    }
}

// ========== CANCEL LEAVE ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_leave'])) {
    $cancel_leave_id = $_POST['leave_id'] ?? '';
    if ($cancel_leave_id) {
        $stmt = $pdo->prepare("UPDATE leave_applications SET final_status = 'Cancelled' WHERE leave_id = :leave_id");
        $stmt->execute([':leave_id' => $cancel_leave_id]);
        header("Location: " . $_SERVER['PHP_SELF'] . "?deleted=1");
        exit();
    }
}

// ========== FETCH ALL LEAVES ==========
$stmt = $pdo->query("SELECT * FROM leave_applications ORDER BY applied_at DESC");
$rows = $stmt->fetchAll();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Apply</title>
    <link rel="icon" type="image/png" sizes="32x32" href="image/icons/mkce_s.png">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Table Header Gradient */
        .table thead th {
            background: linear-gradient(135deg, #4CAF50, #26C6DA, #2196F3);
            /* green → teal → blue */
            color: white;
            text-align: center;
        }

        /* Table Rows */
        .table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        /* Buttons */
        .btn-danger {
            background-color: #e74c3c;
            /* red */
            color: #fff;
            border: none;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .btn-warning {
            background-color: #f1c40f;
            /* yellow/gold */
            color: #fff;
            border: none;
        }

        .btn-warning:hover {
            background-color: #d4ac0d;
        }

        /* Card container */
        .card {
            background-color: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Tabs */
        .tab-item {
            border-radius: 8px;
            padding: 8px 15px;
            color: #fff;
            font-weight: 500;
            margin-right: 5px;
        }

        .tab-assessment {
            background-color: #28a745;
        }

        /* green */
        .tab-co {
            background-color: #ff2d55;
        }

        /* pink */
        .tab-extra {
            background-color: #6a5acd;
        }

        /* purple */
        .tab-international {
            background-color: #2ecc71;
        }

        /* teal */
        .tab-projects {
            background-color: #3498db;
        }

        /* blue */
        .tab-certifications {
            background-color: #4b59f5;
        }

        /* darker blue */
        .tab-internship {
            background-color: #f39c12;
        }

        /* orange */
        .tab-courses {
            background-color: #ff1493;
        }

        /* hot pink */
        .tab-placement {
            background-color: #e74c3c;
        }

        /* red */
    </style>
</head>


    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Leave Applications</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
        <!-- SweetAlert2 CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    </head>

    <body>
        <?php include 'index.php'; ?>
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
                                        <td class="text-center">
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
                                <button type="button" class="btn-close btn-close-white"
                                    data-bs-dismiss="modal"></button>
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

        </div>

        <script>
            // DataTable initialization
            $(document).ready(function () {
                $('#leaveTable').DataTable({ responsive: true });

                // Cancel leave with SweetAlert2
                $(document).on('click', '.cancel-btn', function () {
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

                // Success messages
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

                // Set minimum dates based on leave type
                const leaveType = document.getElementById('leaveType');
                const fromDate = document.getElementById('fromDate');
                const toDate = document.getElementById('toDate');

                function setMinDates() {
                    const today = new Date();
                    let minDate = today.toISOString().split('T')[0];
                    if (leaveType.value === "General" || leaveType.value === "Leave") {
                        const tomorrow = new Date(today);
                        tomorrow.setDate(today.getDate() + 1);
                        minDate = tomorrow.toISOString().split('T')[0];
                    }
                    fromDate.min = minDate;
                    toDate.min = minDate;
                }

                leaveType.addEventListener('change', setMinDates);

                $('#leaveModal').on('shown.bs.modal', function () {
                    setMinDates();
                });
            });
        </script>
    </body>

<<<<<<< HEAD
    </html>
=======
    </html>
>>>>>>> c67f5c22213cd193d5212587b4bab1c1173408eb
