<?php
include 'dbconnect.php';
session_start();
$roll_no = $_SESSION['roll_number'] ?? '927623bit027';
$errors = [];

// ================= APPLY / EDIT LEAVE =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_leave'])) {
    $leave_id = trim($_POST['leave_id'] ?? '');
    $leave_type_id = trim($_POST['leave_type_id'] ?? '');
    $from_date = trim($_POST['from_date'] ?? '');
    $from_time = trim($_POST['from_time'] ?? '');
    $to_date = trim($_POST['to_date'] ?? '');
    $to_time = trim($_POST['to_time'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    $start_datetime = "$from_date $from_time";
    $end_datetime = "$to_date $to_time";

    if ($leave_id === '' && $leave_type_id === '')
        $errors[] = 'Leave type is required.';
    if ($from_date === '' || $to_date === '')
        $errors[] = 'From and To dates are required.';
    if (strtotime($start_datetime) > strtotime($end_datetime))
        $errors[] = 'From datetime cannot be after To datetime.';

    $proof_file = '';
    if (!empty($_FILES['proof']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
        if (in_array($_FILES['proof']['type'], $allowed_types)) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir))
                mkdir($target_dir, 0777, true);
            $proof_file = $target_dir . time() . '_' . basename($_FILES["proof"]["name"]);
            move_uploaded_file($_FILES["proof"]["tmp_name"], $proof_file);
        } else {
            $errors[] = "Only JPG, PNG, and PDF files are allowed.";
        }
    }

    if (empty($errors)) {
        if (!empty($leave_id)) {
            // Edit existing leave
            $sql = "UPDATE leave_applications SET From_Date=?, To_Date=?, Reason=?";
            $params = [$start_datetime, $end_datetime, $reason];
            $types = "sss";
            if ($proof_file !== '') {
                $sql .= ", Proof=?";
                $types .= "s";
                $params[] = $proof_file;
            }
            $sql .= " WHERE Leave_ID=? AND Reg_No=?";
            $types .= "is";
            $params[] = $leave_id;
            $params[] = $roll_no;

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();
            header("Location: " . $_SERVER['PHP_SELF'] . "?updated=1");
            exit();
        } else {
            // New leave
            $stmt = $conn->prepare("INSERT INTO leave_applications 
                (Reg_No, LeaveType_ID, From_Date, To_Date, Reason, Proof, Status, Applied_Date) 
                VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())");
            $stmt->bind_param("sissss", $roll_no, $leave_type_id, $start_datetime, $end_datetime, $reason, $proof_file);
            $stmt->execute();
            $stmt->close();
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit();
        }
    }
}

// ================= CANCEL LEAVE =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_leave'])) {
    $leave_id = trim($_POST['leave_id'] ?? '');
    if (!empty($leave_id)) {
        $stmt = $conn->prepare("UPDATE leave_applications 
                                SET Status='Cancelled' 
                                WHERE Leave_ID=? AND Reg_No=? AND Status='Pending'");
        $stmt->bind_param("is", $leave_id, $roll_no);
        $stmt->execute();
        $stmt->close();
        echo "success";
        exit;
    }
}

// ================= FETCH LEAVES =================
$rows = [];
$sql = "SELECT la.Leave_ID, la.From_Date, la.To_Date, la.Reason, la.Proof, la.Status, la.Applied_Date,
               lt.Leave_Type_Name
        FROM leave_applications la
        LEFT JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
        WHERE la.Reg_No = ?
        ORDER BY la.Applied_Date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $roll_no);
$stmt->execute();
$result = $stmt->get_result();
while ($r = $result->fetch_assoc())
    $rows[] = $r;
$stmt->close();

// ================= FETCH LEAVE TYPES =================
$leave_types = [];
$lt_res = $conn->query("SELECT LeaveType_ID, Leave_Type_Name FROM leave_types ORDER BY Priority ASC, Leave_Type_Name ASC");
if ($lt_res && $lt_res->num_rows > 0) {
    while ($lt = $lt_res->fetch_assoc())
        $leave_types[] = $lt;
} else {
    $errors[] = "No leave types found. Please contact admin.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Applications</title>

    <!-- ======= STYLES ======= -->
    <link rel="icon" type="image/png" sizes="32x32" href="image/icons/mkce_s.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* (Insert your provided CSS here exactly as given in your message) */
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

        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #ece9e6, #ffffff);
            margin: 0;
            padding: 0;
        }

        .content {
            margin-left: var(--sidebar-width);
            padding-top: var(--topbar-height);
            transition: var(--transition);
            min-height: 100vh;
        }

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
            transition: var(--transition);
            white-space: nowrap;
        }

        .content-nav li a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .sidebar.collapsed+.content {
            margin-left: var(--sidebar-collapsed-width);
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
            background: linear-gradient(135deg, #4CAF50, #2196F3) !important;
            text-align: center;
            font-size: 0.9em;
        }

        td {
            text-align: left;
            font-size: 0.9em;
            vertical-align: middle;
        }

        .nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 90%;
            max-width: 900px;
            margin: 0 auto 10px auto;
        }

        .nav-title {
            font-size: 20px;
            font-weight: bold;
            color: #343a40;
        }

        .nav-bar a {
            text-decoration: none;
            padding: 8px 15px;
            background: var(--primary-color);
            color: white;
            border-radius: 6px;
            transition: 0.3s;
        }

        .nav-bar a:hover {
            background: #2e59d9;
        }

        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            width: 95%;
            max-width: 900px;
            margin: auto;
        }

        .day-header {
            background: #343a40;
            color: white;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            border-radius: 6px;
        }

        .day {
            min-height: 80px;
            padding: 5px;
            border-radius: 8px;
            text-align: center;
            font-size: 14px;
            color: #000;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease-in-out;
        }

        .day strong {
            font-size: 16px;
        }

        .day:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }

        .today {
            border: 3px solid #ff9800;
            box-shadow: 0 0 12px #ff9800;
        }

        .legend {
            width: 95%;
            max-width: 900px;
            margin: 20px auto;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
        }

        .legend div {
            display: flex;
            align-items: center;
            margin: 5px 10px;
        }

        .legend span {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 6px;
            border-radius: 4px;
        }

        .loader-container {
            position: fixed;
            left: var(--sidebar-width);
            right: 0;
            top: var(--topbar-height);
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            transition: left 0.3s ease;
        }

        .sidebar.collapsed+.content .loader-container {
            left: var(--sidebar-collapsed-width);
        }

        .loader-container.hide {
            display: none;
        }

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

        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                padding-top: 80px;
            }

            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width) !important;
            }

            .sidebar.mobile-show {
                transform: translateX(0);
            }

            .topbar,
            .footer {
                left: 0 !important;
            }

            body.sidebar-open {
                overflow: hidden;
            }

            .day {
                min-height: 60px;
                font-size: 12px;
            }

            .day strong {
                font-size: 14px;
            }

            .nav-title {
                font-size: 16px;
            }

            .nav-bar a {
                padding: 6px 10px;
                font-size: 13px;
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

        <?php echo file_get_contents("style.css"); ?>
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <?php include 'topbar.php'; ?>
<div class="content">
    <div class="container my-5">
        <div class="card shadow-lg rounded-4">
            <div class="card-header gradient-header text-white text-center">
                <h3>Leave Applications</h3>
            </div>
            <div class="card-body">

                <!-- Display Errors -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <!-- Apply Leave Button -->
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#leaveModal">
        <i class="fa fa-plus me-1"></i> Apply Leave
    </button>

    <!-- Search Bar Placeholder (will be moved here by DataTables) -->
    <div id="tableSearch"></div>
</div>

                    <div class="table-responsive">
                        <table id="leaveTable" class="table table-bordered table-hover align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Leave Type</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Proof</th>
                                    <th>Applied Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr>
                                        <td colspan="9" class="text-muted">No leave records found</td>
                                    </tr>
                                <?php else:
                                    foreach ($rows as $r): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($r['Leave_ID']) ?></td>
                                            <td><?= htmlspecialchars($r['Leave_Type_Name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($r['From_Date']))) ?></td>
                                            <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($r['To_Date']))) ?></td>
                                            <td><?= htmlspecialchars($r['Reason'] ?? '-') ?></td>
                                            <td>
                                                <?php
                                                $status = $r['Status'] ?? 'Pending';
                                                $badge = match ($status) {
                                                    'Pending' => 'warning',
                                                    'Approved' => 'success',
                                                    'Rejected' => 'danger',
                                                    'Cancelled' => 'secondary',
                                                    default => 'info'
                                                };
                                                ?>
                                                <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($status) ?></span>
                                            </td>
                                            <td>
                                                <?php if (!empty($r['Proof'])): ?>
                                                    <button class="btn btn-sm btn-info view-proof-btn"
                                                        data-proof="<?= htmlspecialchars($r['Proof']) ?>">View</button>
                                                <?php else: ?>-<?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($r['Applied_Date']))) ?></td>
                                            <td>
                                                <?php if ($status === 'Pending'): ?>
                                                    <button class="btn btn-outline-primary btn-sm edit-btn"
                                                        data-id="<?= $r['Leave_ID'] ?>"
                                                        data-from="<?= date('Y-m-d', strtotime($r['From_Date'])) ?>"
                                                        data-fromtime="<?= date('H:i', strtotime($r['From_Date'])) ?>"
                                                        data-to="<?= date('Y-m-d', strtotime($r['To_Date'])) ?>"
                                                        data-totime="<?= date('H:i', strtotime($r['To_Date'])) ?>"
                                                        data-reason="<?= htmlspecialchars($r['Reason'], ENT_QUOTES) ?>">Edit</button>

                                                    <form method="post" class="d-inline cancel-form">
                                                        <input type="hidden" name="leave_id"
                                                            value="<?= htmlspecialchars($r['Leave_ID']) ?>">
                                                        <input type="hidden" name="cancel_leave" value="1">
                                                        <button type="button"
                                                            class="btn btn-outline-danger btn-sm cancel-btn">Cancel</button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary btn-sm"
                                                        disabled><?= htmlspecialchars($status) ?></button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- APPLY / EDIT LEAVE MODAL -->
                    <div class="modal fade" id="leaveModal" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form method="post" enctype="multipart/form-data" id="leaveForm">
                                    <div class="modal-header bg-success text-white">
                                        <h5 class="modal-title">Apply / Edit Leave</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="leave_id" id="leave_id">

                                        <div class="mb-3" id="leaveTypeWrapper">
                                            <label>Leave Type</label>
                                            <select name="leave_type_id" class="form-select" id="leave_type_id">
                                                <option value="">Select Leave Type</option>
                                                <?php foreach ($leave_types as $lt): ?>
                                                    <option value="<?= $lt['LeaveType_ID'] ?>">
                                                        <?= htmlspecialchars($lt['Leave_Type_Name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col"><label>From Date</label><input type="date" name="from_date"
                                                    class="form-control" id="from_date" required></div>
                                            <div class="col"><label>From Time</label><input type="time" name="from_time"
                                                    class="form-control" id="from_time" required></div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col"><label>To Date</label><input type="date" name="to_date"
                                                    class="form-control" id="to_date" required></div>
                                            <div class="col"><label>To Time</label><input type="time" name="to_time"
                                                    class="form-control" id="to_time" required></div>
                                        </div>

                                        <div class="mb-3">
                                            <label>Reason</label>
                                            <textarea name="reason" class="form-control" rows="3" id="reason"
                                                required></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label>Proof File (optional)</label>
                                            <input type="file" name="proof" class="form-control">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" name="apply_leave" class="btn btn-success">Submit</button>
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- PROOF MODAL -->
                    <div class="modal fade" id="proofModal" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-info text-white">
                                    <h5 class="modal-title">Leave Proof</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center" id="proofContent"></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        $(function () {
            $('#leaveTable').DataTable({ "order": [[7, "desc"]] });

// Cancel Leave
$(document).on('click', '.cancel-btn', function () {
    let form = $(this).closest('form');
    let row = $(this).closest('tr');
    Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to cancel this leave?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, cancel it!',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
            // Add ajax flag
            form.append('<input type="hidden" name="ajax" value="1">');

            $.post(window.location.href, form.serialize(), function (res) {
                if (res.trim() === "success") {
                    row.find('td:nth-child(6)').html('<span class="badge bg-secondary">Cancelled</span>');
                    row.find('td:last').html('<button class="btn btn-secondary btn-sm" disabled>Cancelled</button>');
                    Swal.fire('Cancelled!', 'Your leave has been cancelled.', 'success');
                } else {
                    Swal.fire('Error', 'Unable to cancel leave.', 'error');
                }
            });
        }
    });
});


            // Edit Leave
            $(document).on('click', '.edit-btn', function () {
                $('#leave_id').val($(this).data('id'));
                $('#from_date').val($(this).data('from'));
                $('#from_time').val($(this).data('fromtime'));
                $('#to_date').val($(this).data('to'));
                $('#to_time').val($(this).data('totime'));
                $('#reason').val($(this).data('reason'));
                $('#leaveTypeWrapper').hide();
                $('#leaveModal').modal('show');
            });

            $('#leaveModal').on('show.bs.modal', function () {
                if (!$('#leave_id').val()) $('#leaveTypeWrapper').show();
            });

            // Proof viewer
            $(document).on('click', '.view-proof-btn', function () {
                let file = $(this).data('proof');
                let ext = file.split('.').pop().toLowerCase();
                let content = '';
                if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) content = '<img src="' + file + '" class="img-fluid rounded">';
                else if (ext === 'pdf') content = '<iframe src="' + file + '" width="100%" height="500px"></iframe>';
                else content = '<p>Cannot preview. <a href="' + file + '" target="_blank">Download</a></p>';
                $('#proofContent').html(content);
                $('#proofModal').modal('show');
            });

            // SweetAlert notifications
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) Swal.fire('Success', 'Leave applied successfully', 'success');
            if (urlParams.has('updated')) Swal.fire('Success', 'Leave updated successfully', 'success');
            if (urlParams.has('success') || urlParams.has('updated')) history.replaceState(null, '', window.location.pathname);

            // Reset form
            $('#leaveModal').on('hidden.bs.modal', function () {
                $(this).find('form')[0].reset();
                $('#leave_id').val('');
                $('#leaveTypeWrapper').show();
            });
        });
    </script>
</body>

</html>