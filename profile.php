<?php
session_start();
try {
    require_once 'dbconnect.php';
} catch (Exception $e) {
    die("Database configuration error: " . $e->getMessage());
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $user_id = $_SESSION['user_id'];

        // Get student roll number first
        $stmt = $pdo->prepare("SELECT roll_number FROM students WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            $roll_number = $student['roll_number'];

            // Update student basic information
            $stmt = $pdo->prepare("
                UPDATE students SET 
                    name = ?, email = ?, student_phone = ?, 
                    department = ?, academic_year = ?, block = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE roll_number = ?
            ");

            $stmt->execute([
                $_POST['name'],
                $_POST['email'],
                $_POST['student_phone'],
                $_POST['department'],
                $_POST['academic_year'],
                $_POST['block'],
                $roll_number
            ]);

            // Update parent information if provided
            if (isset($_POST['parent_name']) && is_array($_POST['parent_name'])) {
                foreach ($_POST['parent_name'] as $parent_id => $parent_name) {
                    if (isset($_POST['parent_phone'][$parent_id])) {
                        $stmt = $pdo->prepare("
                            UPDATE parents p
                            JOIN student_parents sp ON p.parent_id = sp.parent_id
                            SET p.name = ?, p.phone = ?, p.email = ?
                            WHERE sp.student_roll_number = ? AND p.parent_id = ?
                        ");

                        $stmt->execute([
                            $parent_name,
                            $_POST['parent_phone'][$parent_id],
                            $_POST['parent_email'][$parent_id] ?? '',
                            $roll_number,
                            $parent_id
                        ]);
                    }
                }
            }

            $_SESSION['success'] = "Profile updated successfully!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    } catch (Exception $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}

// Getting student data 
$user_id = $_SESSION['user_id'];
$student_data = [];
$parent_data = [];
$attendance_stats = [];
$leave_stats = [];
$outing_stats = [];

try {
    // Getting student basic info
    $stmt = $pdo->prepare("
        SELECT s.*, r.room_number, r.room_type, r.capacity, r.occupied, 
               h.hostel_name, h.hostel_code, h.address as hostel_address
        FROM students s 
        LEFT JOIN rooms r ON s.room_id = r.room_id 
        LEFT JOIN hostels h ON r.hostel_id = h.hostel_id 
        WHERE s.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $student_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get parent information
    if (!isset($demo_mode)) {
        $stmt = $pdo->prepare("
        SELECT p.*, sp.relation_enum, sp.is_primary_contact
        FROM student_parents sp
        JOIN parents p ON sp.parent_id = p.parent_id
        WHERE sp.student_roll_number = ?
        ORDER BY sp.is_primary_contact DESC, sp.relation_enum
    ");
        $stmt->execute([$student_data['roll_number']]);
        $parent_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process parent photos
        foreach ($parent_data as &$parent) {
            if (!empty($parent['photo'])) {
                $base64 = base64_encode($parent['photo']);
                $parent['photo_data'] = "data:image/jpeg;base64,$base64";
            } else {
                $parent['photo_data'] = null;
            }
        }
        unset($parent);
    }
    // Get attendance
    if (!isset($demo_mode)) {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
                ROUND((SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_percentage
            FROM attendance 
            WHERE student_roll_number = ? 
            AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$student_data['roll_number']]);
        $attendance_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get active leave count 
    if (!isset($demo_mode)) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as active_leaves 
            FROM leave_applications 
            WHERE student_roll_number = ? 
            AND final_status IN ('Pending', 'Approved')
            AND from_date <= CURDATE() 
            AND to_date >= CURDATE()
        ");
        $stmt->execute([$student_data['roll_number']]);
        $leave_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get outing count for current month 
    if (!isset($demo_mode)) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as monthly_outings 
            FROM outings 
            WHERE student_roll_number = ? 
            AND MONTH(date) = MONTH(CURDATE()) 
            AND YEAR(date) = YEAR(CURDATE())
        ");
        $stmt->execute([$student_data['roll_number']]);
        $outing_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    }



} catch (Exception $e) {
    $error = "Error loading profile data: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - Hostel Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --dark-bg: #1a1a2e;
            --primary: #3498db;
            --secondary: #2ecc71;
            --accent: #e74c3c;
            --light-bg: #f8f9fa;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --transition: all 0.3s ease;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #d7dcf3ff 0%, #f9f9faff 100%);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
        }

        .edit-mode {
            display: none;
        }

        .view-mode {
            display: inline;
        }

        .btn-success {
            background: linear-gradient(45deg, var(--success), #219653);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(45deg, var(--danger), #c0392b);
            color: white;
        }

        input:focus {
            outline: none;
            border-color: var(--primary) !important;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }


        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: var(--transition);
            padding: 20px;
            min-height: 100vh;
        }

        body.sidebar-collapsed .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }


        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .profile-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 25px;
        }


        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            padding: 30px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 30%;
            margin: 0 auto 20px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: white;
            overflow: hidden;
            border: 5px solid white;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--text-dark);
        }

        .profile-roll {
            color: var(--text-light);
            margin-bottom: 15px;
            font-size: 16px;
        }

        .profile-department {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            display: inline-block;
            margin-bottom: 25px;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .profile-stats {
            display: flex;
            justify-content: space-around;
            margin: 25px 0;
            padding: 20px 0;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .stat {
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-value.attendance {
            color: var(--success);
        }

        .stat-value.leaves {
            color: var(--warning);
        }

        .stat-value.outings {
            color: var(--primary);
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-light);
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary), #2980b9);
            color: white;
        }

        .btn-secondary {
            background: rgba(52, 152, 219, 0.1);
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .details-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            padding: 30px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(0, 0, 0, 0.1);
        }

        .card-title {
            font-size: 22px;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .edit-btn {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .edit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .info-section {
            background: white;
            padding: 20px;
            border-radius: 15px;

        }

        .info-grid .info-section:nth-child(1) {
            border-left: 4px solid #3498db !important;


        }

        .info-grid .info-section:nth-child(2) {
            border-left: 4px solid #2ecc71 !important;

        }

        .info-grid .info-section:nth-child(3) {
            border-left: 4px solid #e74c3c !important;

        }

        .info-grid .info-section:nth-child(4) {
            border-left: 4px solid #f39c12 !important;

        }

        .info-grid .info-section:nth-child(1):hover {
            box-shadow: 0 4px 20px rgba(52, 152, 219, 0.2) !important;
        }

        .info-grid .info-section:nth-child(2):hover {
            box-shadow: 0 4px 20px rgba(46, 204, 113, 0.2) !important;
        }

        .info-grid .info-section:nth-child(3):hover {
            box-shadow: 0 4px 20px rgba(231, 76, 60, 0.2) !important;
        }

        .info-grid .info-section:nth-child(4):hover {
            box-shadow: 0 4px 20px rgba(243, 156, 18, 0.2) !important;
        }


        .section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .section-title i {
            color: var(--primary);
            font-size: 20px;
        }

        .info-item {
            display: flex;
            margin-bottom: 15px;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .info-label {
            width: 150px;
            font-weight: 600;
            color: var(--text-light);
            font-size: 14px;
        }

        .info-value {
            flex: 1;
            font-weight: 500;
            color: var(--text-dark);
        }

        .parent-card {
            background: linear-gradient(135deg, #04012fff, #2b07bcff, #266bcbff);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 15px;
        }

        .parent-card .info-item {
            border-bottom-color: rgba(255, 255, 255, 0.3);
        }

        .parent-card .info-label {
            color: rgba(255, 255, 255, 0.8);
        }

        .parent-card .info-value {
            color: white;
        }

        .primary-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 10px;
        }

        @media (max-width: 1200px) {
            .profile-container {
                grid-template-columns: 1fr;
            }

            .profile-card {
                max-width: 500px;
                margin: 0 auto;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .profile-stats {
                flex-direction: column;
                gap: 15px;
            }
        }

        .error-message {
            background: var(--danger);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success-message {
            background: var(--success);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .parent-photos-section {
            margin: 25px 0;
            padding: 20px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            text-align: center;
        }

        .parent-photos-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 40px;
            flex-wrap: wrap;
        }

        .parent-photo-item {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .parent-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 12px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            overflow: hidden;
            border: 4px solid white;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            transition: var(--transition);
        }

        .parent-image:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .parent-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .parent-info {
            text-align: center;
            max-width: 150px;
        }

        .parent-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .parent-relation {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 6px;
            font-weight: 500;
        }

        .primary-badge-small {
            background: linear-gradient(45deg, var(--success), #219653);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            box-shadow: 0 2px 8px rgba(39, 174, 96, 0.3);
        }

        .parent-photos-section .section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(0, 0, 0, 0.1);
        }

        .parent-photos-section .section-title i {
            color: var(--primary);
            font-size: 20px;
        }

        @media (max-width: 768px) {
            .parent-photos-container {
                gap: 30px;
                flex-direction: column;
            }

            .parent-photo-item {
                width: auto;
            }

            .parent-image {
                width: 140px;
                height: 140px;
                font-size: 35px;
            }

            .parent-name {
                font-size: 15px;
            }

            .parent-relation {
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .parent-photos-container {
                gap: 25px;
            }

            .parent-image {
                width: 130px;
                height: 130px;
                font-size: 30px;
            }
        }

        @media print {
            @page {
                margin: 0.5cm;
                size: auto;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
            }

            .sidebar,
            .topbar,
            .demo-banner,
            #hamburger,
            #mobileOverlay,
            .action-buttons,
            .edit-btn,
            .btn {
                display: none !important;
            }

            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .profile-container {
                display: flex !important;
            }

            .profile-card,
            .details-card {
                box-shadow: none !important;
                border: 2px solid #000 !important;
                background: white !important;
            }


        }
    </style>
</head>

<body>

    <body>
        <?php include 'topbar.php'; ?>
        <?php include 'sidebar.php'; ?>
        <div class="main-content" id="mainContent" style="margin-top: <?php echo isset($demo_mode) ? '50px' : '0'; ?>;">

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="profileForm">
                <input type="hidden" name="update_profile" value="1">

                <div class="profile-container">
                    <!-- Profile Card -->
                    <div class="profile-card">
                        <div class="profile-image">
                            <?php if ($student_data && !empty($student_data['photo'])): ?>
                                <img src="data:image/jpeg;base64,<?php echo base64_encode($student_data['photo']); ?>"
                                    alt="Student Photo">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div class="profile-name"><?php echo htmlspecialchars($student_data['name'] ?? 'N/A'); ?></div>
                        <div class="profile-roll"><?php echo htmlspecialchars($student_data['roll_number'] ?? 'N/A'); ?>
                        </div>
                        <div class="profile-department">
                            <?php echo htmlspecialchars($student_data['department'] ?? 'N/A'); ?>
                        </div>

                        <?php if (!empty($parent_data)): ?>
                            <div class="parent-photos-section">
                                <div class="section-title"
                                    style="margin: 20px 0 15px 0; font-size: 16px; justify-content: center;">
                                    <i class="fas fa-users"></i> Parents
                                </div>
                                <div class="parent-photos-container">
                                    <?php foreach ($parent_data as $parent): ?>
                                        <div class="parent-photo-item">
                                            <div class="parent-image">
                                                <?php if (isset($parent['photo_data']) && $parent['photo_data']): ?>
                                                    <img src="<?php echo $parent['photo_data']; ?>"
                                                        alt="<?php echo htmlspecialchars($parent['name']); ?>">
                                                <?php else: ?>
                                                    <i class="fas fa-user"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="parent-info">
                                                <div class="parent-name"><?php echo htmlspecialchars($parent['name']); ?></div>
                                                <div class="parent-relation">
                                                    <?php echo htmlspecialchars($parent['relation_enum']); ?>
                                                </div>
                                                <?php if ($parent['is_primary_contact']): ?>
                                                    <div class="primary-badge-small">Primary</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="profile-stats">
                            <div class="stat">
                                <div class="stat-value attendance">
                                    <?php echo $attendance_stats['attendance_percentage'] ?? '0'; ?>%
                                </div>
                                <div class="stat-label">Attendance</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value leaves">
                                    <?php echo $leave_stats['active_leaves'] ?? '0'; ?>
                                </div>
                                <div class="stat-label">Active Leaves</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value outings">
                                    <?php echo $outing_stats['monthly_outings'] ?? '0'; ?>
                                </div>
                                <div class="stat-label">Monthly Outings</div>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button type="button" class="btn btn-primary" onclick="toggleEditMode()" id="editBtn">
                                <i class="fas fa-edit"></i> Edit Profile
                            </button>
                            <button type="submit" class="btn btn-success" id="saveBtn" style="display: none;">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="window.print()">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button type="button" class="btn btn-danger" onclick="cancelEdit()" id="cancelBtn"
                                style="display: none;">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </div>

                    <!-- Details Card -->
                    <div class="details-card">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-id-card"></i> Student Information</div>
                            <button type="button" class="edit-btn" onclick="toggleEditMode()" id="editHeaderBtn">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </div>


                        <div class="info-grid">
                            <!-- Personal Info -->
                            <div class="info-section">
                                <div class="section-title">
                                    <i class="fas fa-user-circle"></i> Personal Details
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Full Name:</div>
                                    <div class="info-value">
                                        <span
                                            class="view-mode"><?php echo htmlspecialchars($student_data['name'] ?? 'N/A'); ?></span>
                                        <input type="text" name="name"
                                            value="<?php echo htmlspecialchars($student_data['name'] ?? ''); ?>"
                                            class="edit-mode"
                                            style="display: none; width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Roll Number:</div>
                                    <div class="info-value">
                                        <?php echo htmlspecialchars($student_data['roll_number'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Gender:</div>
                                    <div class="info-value">
                                        <?php echo htmlspecialchars($student_data['gender'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Email:</div>
                                    <div class="info-value">
                                        <span
                                            class="view-mode"><?php echo htmlspecialchars($student_data['email'] ?? 'N/A'); ?></span>
                                        <input type="email" name="email"
                                            value="<?php echo htmlspecialchars($student_data['email'] ?? ''); ?>"
                                            class="edit-mode"
                                            style="display: none; width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Phone:</div>
                                    <div class="info-value">
                                        <span
                                            class="view-mode"><?php echo htmlspecialchars($student_data['student_phone'] ?? 'N/A'); ?></span>
                                        <input type="text" name="student_phone"
                                            value="<?php echo htmlspecialchars($student_data['student_phone'] ?? ''); ?>"
                                            class="edit-mode"
                                            style="display: none; width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                                    </div>
                                </div>
                            </div>

                            <!-- Academic Info-->
                            <div class="info-section">
                                <div class="section-title">
                                    <i class="fas fa-graduation-cap"></i> Academic Details
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Department:</div>
                                    <div class="info-value">
                                        <span
                                            class="view-mode"><?php echo htmlspecialchars($student_data['department'] ?? 'N/A'); ?></span>
                                        <input type="text" name="department"
                                            value="<?php echo htmlspecialchars($student_data['department'] ?? ''); ?>"
                                            class="edit-mode"
                                            style="display: none; width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Academic Year:</div>
                                    <div class="info-value">
                                        <span
                                            class="view-mode"><?php echo htmlspecialchars($student_data['academic_year'] ?? 'N/A'); ?></span>
                                        <input type="text" name="academic_year"
                                            value="<?php echo htmlspecialchars($student_data['academic_year'] ?? ''); ?>"
                                            class="edit-mode"
                                            style="display: none; width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Block:</div>
                                    <div class="info-value">
                                        <span
                                            class="view-mode"><?php echo htmlspecialchars($student_data['block'] ?? 'N/A'); ?></span>
                                        <input type="text" name="block"
                                            value="<?php echo htmlspecialchars($student_data['block'] ?? ''); ?>"
                                            class="edit-mode"
                                            style="display: none; width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                                    </div>
                                </div>
                            </div>

                            <!-- Hostel Info-->
                            <div class="info-section">
                                <div class="section-title">
                                    <i class="fas fa-bed"></i> Hostel Details
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Hostel Name:</div>
                                    <div class="info-value">
                                        <?php echo htmlspecialchars($student_data['hostel_name'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Hostel Code:</div>
                                    <div class="info-value">
                                        <?php echo htmlspecialchars($student_data['hostel_code'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Room Number:</div>
                                    <div class="info-value">
                                        <?php echo htmlspecialchars($student_data['room_number'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Room Type:</div>
                                    <div class="info-value">
                                        <?php echo htmlspecialchars($student_data['room_type'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Room Capacity:</div>
                                    <div class="info-value">
                                        <?php echo htmlspecialchars($student_data['capacity'] ?? 'N/A'); ?>
                                        (<?php echo htmlspecialchars($student_data['occupied'] ?? '0'); ?> occupied)
                                    </div>
                                </div>
                            </div>

                            <!-- Parent/Guardian Info -->
                            <div class="info-section">
                                <div class="section-title">
                                    <i class="fas fa-users"></i> Parent/Guardian Details
                                </div>
                                <?php if (!empty($parent_data)): ?>
                                    <?php foreach ($parent_data as $parent): ?>
                                        <div class="parent-card">
                                            <div class="info-item">
                                                <div class="info-label">Name:</div>
                                                <div class="info-value">
                                                    <span class="view-mode">
                                                        <?php echo htmlspecialchars($parent['name']); ?>
                                                        <?php if ($parent['is_primary_contact']): ?>
                                                            <span class="primary-badge">Primary Contact</span>
                                                        <?php endif; ?>
                                                    </span>
                                                    <div class="edit-mode" style="display: none;">
                                                        <input type="text"
                                                            name="parent_name[<?php echo $parent['parent_id']; ?>]"
                                                            value="<?php echo htmlspecialchars($parent['name']); ?>"
                                                            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 5px; font-size: 14px;">
                                                        <?php if ($parent['is_primary_contact']): ?>
                                                            <span class="primary-badge">Primary Contact</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="info-item">
                                                <div class="info-label">Relation:</div>
                                                <div class="info-value">
                                                    <?php echo htmlspecialchars($parent['relation_enum']); ?>
                                                </div>
                                            </div>
                                            <div class="info-item">
                                                <div class="info-label">Phone:</div>
                                                <div class="info-value">
                                                    <span
                                                        class="view-mode"><?php echo htmlspecialchars($parent['phone'] ?? 'N/A'); ?></span>
                                                    <input type="text" name="parent_phone[<?php echo $parent['parent_id']; ?>]"
                                                        value="<?php echo htmlspecialchars($parent['phone'] ?? ''); ?>"
                                                        class="edit-mode"
                                                        style="display: none; width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                                                </div>
                                            </div>
                                            <div class="info-item">
                                                <div class="info-label">Email:</div>
                                                <div class="info-value">
                                                    <span
                                                        class="view-mode"><?php echo htmlspecialchars($parent['email'] ?? 'N/A'); ?></span>
                                                    <input type="email" name="parent_email[<?php echo $parent['parent_id']; ?>]"
                                                        value="<?php echo htmlspecialchars($parent['email'] ?? ''); ?>"
                                                        class="edit-mode"
                                                        style="display: none; width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="info-item">
                                        <div class="info-value" style="color: var(--text-light);">No parent/guardian
                                            information available</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <script>
            // to toggle edit mode
            function toggleEditMode() {
                const isEditMode = document.getElementById('saveBtn').style.display !== 'none'
                if (!isEditMode) {
                    document.querySelectorAll('.view-mode').forEach(el => el.style.display = 'none');
                    document.querySelectorAll('.edit-mode').forEach(el => el.style.display = 'block');
                    document.getElementById('editBtn').style.display = 'none';
                    document.getElementById('saveBtn').style.display = 'flex';
                    document.getElementById('cancelBtn').style.display = 'flex';
                    document.getElementById('editHeaderBtn').style.display = 'none';
                    document.querySelector('.edit-btn').innerHTML = '<i class="fas fa-save"></i> Save';
                    document.querySelector('.edit-btn').setAttribute('onclick', 'document.getElementById(\'profileForm\').submit()');
                }
            }

            // to cancel edit mode
            function cancelEdit() {
                document.querySelectorAll('.view-mode').forEach(el => el.style.display = 'inline');
                document.querySelectorAll('.edit-mode').forEach(el => el.style.display = 'none');
                document.getElementById('editBtn').style.display = 'flex';
                document.getElementById('saveBtn').style.display = 'none';
                document.getElementById('cancelBtn').style.display = 'none';
                document.getElementById('editHeaderBtn').style.display = 'flex';
                document.querySelector('.edit-btn').innerHTML = '<i class="fas fa-edit"></i> Edit';
                document.querySelector('.edit-btn').setAttribute('onclick', 'toggleEditMode()');

                window.location.reload();
            }


            document.getElementById('profileForm').addEventListener('submit', function (e) {
                let isValid = true;
                const inputs = this.querySelectorAll('input[required]');

                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        isValid = false;
                        input.style.borderColor = 'var(--danger)';
                    } else {
                        input.style.borderColor = '';
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                }
            })
            document.addEventListener("DOMContentLoaded", function () {
                const hamburger = document.createElement('div');
                hamburger.id = 'hamburger';
                hamburger.innerHTML = '<i class="fas fa-bars"></i>';
                hamburger.style.cssText = `
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1100;
                background: var(--primary);
                color: white;
                width: 40px;
                height: 40px;
                border-radius: 5px;
                display: none;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                font-size: 20px;
            `;
                document.body.appendChild(hamburger);

                const sidebar = document.getElementById('sidebar');
                const mobileOverlay = document.createElement('div');
                mobileOverlay.id = 'mobileOverlay';
                mobileOverlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 999;
                display: none;
            `;
                document.body.appendChild(mobileOverlay);

                function handleSidebarToggle() {
                    if (window.innerWidth <= 768) {
                        sidebar.classList.toggle('mobile-show');
                        mobileOverlay.style.display = sidebar.classList.contains('mobile-show') ? 'block' : 'none';
                        document.body.style.overflow = sidebar.classList.contains('mobile-show') ? 'hidden' : '';
                    } else {
                        sidebar.classList.toggle('collapsed');
                        document.body.classList.toggle('sidebar-collapsed');
                    }
                }

                hamburger.addEventListener('click', handleSidebarToggle);
                mobileOverlay.addEventListener('click', handleSidebarToggle);

                function handleResize() {
                    if (window.innerWidth > 768) {
                        sidebar.classList.remove('mobile-show');
                        mobileOverlay.style.display = 'none';
                        document.body.style.overflow = '';
                    } else {
                        sidebar.classList.remove('collapsed');
                        document.body.classList.remove('sidebar-collapsed');
                    }

                    hamburger.style.display = window.innerWidth <= 768 ? 'flex' : 'none';
                }

                window.addEventListener('resize', handleResize);
                handleResize();
            });
        </script>
    </body>

</html>