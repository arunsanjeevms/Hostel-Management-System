<?php
// Include required files
include 'sidebar.php';
include 'topbar.php';

// Use sample data instead of database
$presentData = [
    ['name' => 'John Doe', 'roll_number' => '101', 'room_number' => 'A-101'],
    ['name' => 'Jane Smith', 'roll_number' => '102', 'room_number' => 'B-205'],
    ['name' => 'Robert Johnson', 'roll_number' => '103', 'room_number' => 'C-307'],
    ['name' => 'Emily Davis', 'roll_number' => '104', 'room_number' => 'A-108'],
    ['name' => 'Michael Wilson', 'roll_number' => '105', 'room_number' => 'B-210']
];

$absentData = [
    ['name' => 'David Brown', 'roll_number' => '106', 'room_number' => 'C-308'],
    ['name' => 'Sarah Wilson', 'roll_number' => '107', 'room_number' => 'A-112'],
    ['name' => 'James Miller', 'roll_number' => '108', 'room_number' => 'B-215'],
    ['name' => 'Lisa Taylor', 'roll_number' => '109', 'room_number' => 'C-310'],
    ['name' => 'Kevin Anderson', 'roll_number' => '110', 'room_number' => 'A-115']
];

$allStudentsData = [
    ['name' => 'John Doe', 'roll_number' => '101', 'room_number' => 'A-101'],
    ['name' => 'Jane Smith', 'roll_number' => '102', 'room_number' => 'B-205'],
    ['name' => 'Robert Johnson', 'roll_number' => '103', 'room_number' => 'C-307'],
    ['name' => 'Emily Davis', 'roll_number' => '104', 'room_number' => 'A-108'],
    ['name' => 'Michael Wilson', 'roll_number' => '105', 'room_number' => 'B-210'],
    ['name' => 'David Brown', 'roll_number' => '106', 'room_number' => 'C-308'],
    ['name' => 'Sarah Wilson', 'roll_number' => '107', 'room_number' => 'A-112'],
    ['name' => 'James Miller', 'roll_number' => '108', 'room_number' => 'B-215'],
    ['name' => 'Lisa Taylor', 'roll_number' => '109', 'room_number' => 'C-310'],
    ['name' => 'Kevin Anderson', 'roll_number' => '110', 'room_number' => 'A-115'],
    ['name' => 'Mark Lee', 'roll_number' => '111', 'room_number' => 'C-308'],
    ['name' => 'Olivia Martinez', 'roll_number' => '112', 'room_number' => 'A-112'],
    ['name' => 'Nathan Garcia', 'roll_number' => '113', 'room_number' => 'B-215']
];

$blockedData = [
    ['block_id' => '1', 'student_roll_number' => '20CS001', 'reason' => 'Late return to hostel', 'blocked_at' => '2025-10-05 22:30:00', 'unblocked_at' => null, 'attendance_id' => '101'],
    ['block_id' => '2', 'student_roll_number' => '20CS002', 'reason' => 'Unauthorized outing', 'blocked_at' => '2025-10-06 23:15:00', 'unblocked_at' => '2025-10-07 09:00:00', 'attendance_id' => '102'],
    ['block_id' => '3', 'student_roll_number' => '20CS003', 'reason' => 'Damaged hostel property', 'blocked_at' => '2025-10-07 21:45:00', 'unblocked_at' => null, 'attendance_id' => '103']
];

// Calculate statistics with fixed counts as requested
$presentCount = 5;
$absentCount = 5;
$blockedCount = 3;
$totalStudents = 13;
$presentPercentage = round(($presentCount / $totalStudents) * 100, 1);
$absentPercentage = round(($absentCount / $totalStudents) * 100, 1);
$blockedPercentage = round(($blockedCount / $totalStudents) * 100, 1);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIC - Hostel Attendance</title>
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
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
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
        }

        /* Enhanced Room Details Styles */
        .room-details-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            margin-bottom: 30px;
        }

        .room-details-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(78, 115, 223, 0.2);
        }

        .room-details-header h2 {
            color: #4e73df;
            font-weight: 600;
            margin-bottom: 0;
        }

        .nav-tabs {
            border: none;
            gap: 10px;
            padding: 6px;
            background: #f8f9fd;
            border-radius: 12px;
        }

        .nav-link {
            border: none !important;
            border-radius: 10px !important;
            padding: 12px 20px !important;
            font-weight: 600 !important;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            position: relative;
            overflow: hidden;
            transition: all 0.2s ease !important;
            z-index: 1;
            color: #4e73df;
            background-color: #fff;
        }

        .nav-link::before {
            display: none;
        }

        .nav-link:hover {
            color: #4e73df !important;
            background-color: #e9ecef !important;
            transform: none;
        }

        .nav-link.active {
            background-color: #4e73df !important;
            color: white !important;
            transform: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .tab-content {
            padding: 25px;
            margin-top: 20px;
            background: #fff;
            border-radius: 12px;
            min-height: 300px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); 
            position: relative;
        }

        .table {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead th {
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            color: white;
            font-weight: 600;
            border: none;
        }

        .table-bordered td, .table-bordered th {
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .btn-download {
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(78, 115, 223, 0.3);
        }

        .btn-download:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(78, 115, 223, 0.4);
            color: white;
        }

        .btn-download:active {
            transform: translateY(-1px);
        }

        .btn-block-custom {
            background: linear-gradient(135deg, #e74a3b, #f6c23e);
            border: none;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-block-custom:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(231, 74, 59, 0.3);
            color: white;
        }

        /* Dashboard Stats Cards */
        .stats-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
            height: 100%;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card.present {
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            color: white;
        }

        .stats-card.absent {
            background: linear-gradient(135deg, #f6c23e, #e74a3b);
            color: white;
        }

        .stats-card.blocked {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }

        .stats-card.total {
            background: linear-gradient(135deg, #36b9cc, #1cc88a);
            color: white;
        }

        .stats-card .card-body {
            padding: 20px;
            text-align: center;
        }

        .stats-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0;
        }

        .stats-card .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }

        .stats-card .stat-percent {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Summary Cards */
        .summary-card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .summary-card .card-header {
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
        }

        .summary-card .card-body {
            padding: 20px;
        }

        .summary-card .list-group-item {
            border: none;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .summary-card .list-group-item:last-child {
            border-bottom: none;
        }

        .summary-card .alert {
            border-radius: 8px;
            margin-bottom: 10px;
            padding: 12px 15px;
        }

        /* Progress Bars */
        .progress {
            height: 20px;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .progress-bar {
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
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
            
            .room-details-container {
                padding: 15px;
            }
            
            .tab-content {
                padding: 15px;
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

<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="content">

        <div class="loader-container" id="loaderContainer">
            <div class="loader"></div>
        </div>

        <!-- Topbar -->
        <?php include 'topbar.php'; ?>

        <!-- Breadcrumb -->
        <div class="breadcrumb-area custom-gradient">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Hostel Dashboard</li>
                </ol>
            </nav>
        </div>

        <!-- Content Area -->
        <div class="container-fluid">
            <div class="room-details-container">
                <div class="room-details-header">
                    <h2><i class="fas fa-tachometer-alt me-2"></i>HOSTEL DASHBOARD</h2>
                </div>
                
                <!-- Dashboard Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stats-card present">
                            <div class="card-body text-center">
                                <i class="fas fa-user-check fa-2x mb-2"></i>
                                <div class="stat-number"><?php echo $presentCount; ?></div>
                                <div class="stat-label">Present Students</div>
                                <div class="stat-percent"><?php echo $presentPercentage; ?>% of total</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card absent">
                            <div class="card-body text-center">
                                <i class="fas fa-user-times fa-2x mb-2"></i>
                                <div class="stat-number"><?php echo $absentCount; ?></div>
                                <div class="stat-label">Absent Students</div>
                                <div class="stat-percent"><?php echo $absentPercentage; ?>% of total</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card blocked">
                            <div class="card-body text-center">
                                <i class="fas fa-user-lock fa-2x mb-2"></i>
                                <div class="stat-number"><?php echo $blockedCount; ?></div>
                                <div class="stat-label">Blocked Students</div>
                                <div class="stat-percent"><?php echo $blockedPercentage; ?>% of total</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card total">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x mb-2"></i>
                                <div class="stat-number"><?php echo $totalStudents; ?></div>
                                <div class="stat-label">Total Students</div>
                                <div class="stat-percent">100% of records</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="summary-card">
                            <div class="card-header bg-primary text-white">
                                <h5><i class="fas fa-chart-bar me-2"></i>Attendance Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $presentPercentage; ?>%" aria-valuenow="<?php echo $presentPercentage; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $presentPercentage; ?>% Present</div>
                                </div>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $absentPercentage; ?>%" aria-valuenow="<?php echo $absentPercentage; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $absentPercentage; ?>% Absent</div>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-secondary" role="progressbar" style="width: <?php echo $blockedPercentage; ?>%" aria-valuenow="<?php echo $blockedPercentage; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $blockedPercentage; ?>% Blocked</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-card">
                            <div class="card-header bg-info text-white">
                                <h5><i class="fas fa-info-circle me-2"></i>Quick Stats</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Present Today
                                        <span class="badge bg-success rounded-pill"><?php echo $presentCount; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Absent Today
                                        <span class="badge bg-danger rounded-pill"><?php echo $absentCount; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Currently Blocked
                                        <span class="badge bg-secondary rounded-pill"><?php echo $blockedCount; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Total Students
                                        <span class="badge bg-primary rounded-pill"><?php echo $totalStudents; ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-card">
                            <div class="card-header bg-warning text-dark">
                                <h5><i class="fas fa-exclamation-triangle me-2"></i>Alerts</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($absentCount > 0): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <?php echo $absentCount; ?> students are absent today
                                    </div>
                                <?php endif; ?>
                                <?php if ($blockedCount > 0): ?>
                                    <div class="alert alert-secondary">
                                        <i class="fas fa-user-lock me-2"></i>
                                        <?php echo $blockedCount; ?> students are currently blocked
                                    </div>
                                <?php endif; ?>
                                <?php if ($presentCount == $totalStudents): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i>
                                        All students are present today!
                                    </div>
                                <?php elseif ($presentCount > ($totalStudents * 0.8)): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-thumbs-up me-2"></i>
                                        Good attendance rate: <?php echo $presentPercentage; ?>%
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Data Tables Section -->
                <ul class="nav nav-tabs" id="attendanceTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="present-tab" data-bs-toggle="tab" data-bs-target="#present" type="button" role="tab" aria-selected="true">
                            <i class="fas fa-user-check me-2"></i>PRESENT (5)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="absent-tab" data-bs-toggle="tab" data-bs-target="#absent" type="button" role="tab" aria-selected="false">
                            <i class="fas fa-user-times me-2"></i>ABSENT (5)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="block-tab" data-bs-toggle="tab" data-bs-target="#block" type="button" role="tab" aria-selected="false">
                            <i class="fas fa-user-lock me-2"></i>BLOCK (13)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="manage-blocked-tab" data-bs-toggle="tab" data-bs-target="#manage-blocked" type="button" role="tab" aria-selected="false">
                            <i class="fas fa-ban me-2"></i>MANAGE BLOCKED (3)
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content mt-3" id="attendanceTabsContent">
                    <!-- PRESENT TAB -->
                    <div class="tab-pane fade show active" id="present" role="tabpanel" aria-labelledby="present-tab">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="presentTable">
                                <thead class="gradient-header">
                                    <tr>
                                        <th>NAME</th>
                                        <th>ROLL NO</th>
                                        <th>ROOM NO</th>
                                        <th>STATUS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($presentData)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No present students found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($presentData as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['roll_number']); ?></td>
                                                <td><?php echo htmlspecialchars($row['room_number']); ?></td>
                                                <td><span class="badge bg-success">PRESENT</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- ABSENT TAB -->
                    <div class="tab-pane fade" id="absent" role="tabpanel" aria-labelledby="absent-tab">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="absentTable">
                                <thead class="gradient-header">
                                    <tr>
                                        <th>NAME</th>
                                        <th>ROLL NO</th>
                                        <th>ROOM NO</th>
                                        <th>STATUS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($absentData)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No absent students found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($absentData as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['roll_number']); ?></td>
                                                <td><?php echo htmlspecialchars($row['room_number']); ?></td>
                                                <td><span class="badge bg-danger">ABSENT</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- BLOCK TAB -->
                    <div class="tab-pane fade" id="block" role="tabpanel" aria-labelledby="block-tab">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="blockTable">
                                <thead class="gradient-header">
                                    <tr>
                                        <th>NAME</th>
                                        <th>ROLL NO</th>
                                        <th>ROOM NO</th>
                                        <th>REASON</th>
                                        <th>ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($allStudentsData)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No students found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php 
                                        $reasons = ["Late attendance", "Late in time", "More absents"];
                                        $index = 0;
                                        ?>
                                        <?php foreach ($allStudentsData as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['roll_number']); ?></td>
                                                <td><?php echo htmlspecialchars($row['room_number']); ?></td>
                                                <td><?php echo $reasons[$index % count($reasons)]; ?></td>
                                                <td><button class="btn btn-block-custom block-btn" data-roll="<?php echo htmlspecialchars($row['roll_number']); ?>">BLOCK</button></td>
                                            </tr>
                                            <?php $index++; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- MANAGE BLOCKED TAB -->
                    <div class="tab-pane fade" id="manage-blocked" role="tabpanel" aria-labelledby="manage-blocked-tab">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Block New Student</h6>
                            </div>
                            <div class="card-body">
                                <form id="blockStudentForm">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="student_roll_number" class="form-label">Student Roll Number</label>
                                            <input type="text" class="form-control" id="student_roll_number" name="student_roll_number" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="attendance_id" class="form-label">Attendance ID</label>
                                            <input type="number" class="form-control" id="attendance_id" name="attendance_id">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="reason" class="form-label">Reason for Blocking</label>
                                        <textarea class="form-control" id="reason" name="reason" rows="3" required placeholder="e.g., Late attendance, Late in time, More absents"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-ban"></i> Block Student
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Blocked Students List</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="blockedStudentsTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Block ID</th>
                                                <th>Student Roll Number</th>
                                                <th>Reason</th>
                                                <th>Blocked At</th>
                                                <th>Unblocked At</th>
                                                <th>Attendance ID</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($blockedData)): ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">No blocked students found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($blockedData as $row): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($row['block_id']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['student_roll_number']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                                        <td><?php echo date('d M Y, h:i A', strtotime($row['blocked_at'])); ?></td>
                                                        <td><?php echo $row['unblocked_at'] ? date('d M Y, h:i A', strtotime($row['unblocked_at'])) : '-'; ?></td>
                                                        <td><?php echo $row['attendance_id'] ? htmlspecialchars($row['attendance_id']) : '-'; ?></td>
                                                        <td><?php echo $row['unblocked_at'] ? '<span class="badge bg-success">Unblocked</span>' : '<span class="badge bg-danger">Blocked</span>'; ?></td>
                                                        <td><?php echo $row['unblocked_at'] ? '' : '<button class="btn btn-sm btn-warning unblock-btn" data-id="' . htmlspecialchars($row['block_id']) . '"><i class="fas fa-unlock"></i> Unblock</button>'; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <?php include 'footer.php'; ?>
    </div>
    
    <script>
        // Initialize all charts with fallback to static visualizations
        $(document).ready(function() {
            // Initialize DataTables with proper error handling
            function initializeDataTable(tableId, options = {}) {
                const defaultOptions = {
                    "pageLength": 10,
                    "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    "responsive": true,
                    "dom": '<"row"<"col-md-6"B><"col-md-6"f>>rt<"row"<"col-md-6"l><"col-md-6"p>>',
                    "buttons": [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ]
                };
                
                const finalOptions = Object.assign(defaultOptions, options);
                
                try {
                    if ($(tableId).length) {
                        // Check if table has data rows (not just error or empty message rows)
                        const hasDataRows = $(tableId + ' tbody tr').filter(function() {
                            return $(this).find('td[colspan]').length === 0;
                        }).length > 0;
                        
                        if (hasDataRows) {
                            // Destroy existing DataTable if it exists
                            if ($.fn.DataTable.isDataTable(tableId)) {
                                $(tableId).DataTable().destroy();
                            }
                            
                            // Initialize new DataTable
                            $(tableId).DataTable(finalOptions);
                            console.log('DataTables initialized successfully for ' + tableId);
                        }
                    }
                } catch (error) {
                    console.log('DataTables initialization error for ' + tableId + ':', error);
                }
            }
            
            // Initialize all DataTables with delays
            setTimeout(function() {
                initializeDataTable('#presentTable');
                setTimeout(function() {
                    initializeDataTable('#absentTable');
                    setTimeout(function() {
                        initializeDataTable('#blockTable');
                        setTimeout(function() {
                            initializeDataTable('#blockedStudentsTable', {
                                "order": [[ 3, "desc" ]]
                            });
                        }, 150);
                    }, 150);
                }, 150);
            }, 300);
            
            // Handle form submission
            $('#blockStudentForm').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: 'cruds/insert.php',
                    type: 'POST',
                    data: {
                        action: 'block_student',
                        student_roll_number: $('#student_roll_number').val(),
                        reason: $('#reason').val(),
                        attendance_id: $('#attendance_id').val()
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message || 'Failed to block student.',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Something went wrong.',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });

            // Handle block button click
            $(document).on('click', '.block-btn', function() {
                var rollNumber = $(this).data('roll');
                var reason = $(this).closest('tr').find('td:eq(3)').text(); // Get reason from the 4th column (index 3)
                
                $.ajax({
                    url: 'cruds/insert.php',
                    type: 'POST',
                    data: {
                        action: 'block_student',
                        student_roll_number: rollNumber,
                        reason: reason
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message || 'Failed to block student.',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Something went wrong.',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });

            // Handle unblock button click
            $(document).on('click', '.unblock-btn', function() {
                var blockId = $(this).data('id');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to unblock this student!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, unblock!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'cruds/update.php',
                            type: 'POST',
                            data: {
                                action: 'unblock_student',
                                block_id: blockId
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire(
                                        'Unblocked!',
                                        response.message,
                                        'success'
                                    ).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire(
                                        'Error!',
                                        response.message || 'Failed to unblock student.',
                                        'error'
                                    );
                                }
                            },
                            error: function() {
                                Swal.fire(
                                    'Error!',
                                    'Something went wrong.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });
            
            // Initialize all charts with fallback to static visualizations
        });
    </script>
</body>

</html>