<?php
session_start();
try {
    require_once 'db.php';
} catch (Exception $e) {
    die("Database configuration error: " . $e->getMessage());
}

if (!isset($_SESSION['user_id'])) {
    $demo_mode = true;
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = '';
    $_SESSION['role'] = '';
}

$user_id = $_SESSION['user_id'];

$student_data = [];
$parent_data = [];
$attendance_stats = ['attendance_percentage' => 0, 'total_days' => 0, 'present_days' => 0];
$leave_stats = ['active_leaves' => 0];

try {
    // Get student data
    $stmt = $conn->prepare("
        SELECT s.*, r.room_number, r.room_type, r.capacity, r.occupied, 
               h.hostel_name, h.hostel_code, h.address, r.created_at
        FROM students s 
        LEFT JOIN rooms r ON s.room_id = r.room_id 
        LEFT JOIN hostels h ON r.hostel_id = h.hostel_id 
        WHERE s.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student_data = $result->fetch_assoc();
    $stmt->close();

    // Get parent info
    if ($student_data && !isset($demo_mode)) {
        $stmt = $conn->prepare("
            SELECT p.*, sp.relation_enum, sp.is_primary_contact
            FROM student_parents sp
            JOIN parents p ON sp.parent_id = p.parent_id
            WHERE sp.roll_number = ?
            ORDER BY sp.is_primary_contact DESC, sp.relation_enum
        ");
        $stmt->bind_param("s", $student_data['roll_number']);
        $stmt->execute();
        $result = $stmt->get_result();
        $parent_data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        foreach ($parent_data as &$parent) {
            if (!empty($parent['photo'])) {
                $base64 = base64_encode($parent['photo']);
                $parent['photo_data'] = "data:image/jpeg;base64,$base64";
            } else {
                $parent['photo_data'] = null;
            }
        }
        unset($parent);
    } else if (isset($demo_mode)) {
        $parent_data = [
            [
                'parent_id' => 1,
                'name' => '',
                'relation_enum' => '',
                'is_primary_contact' => 1,
                'phone' => '',
                'email' => '',
                'photo_data' => null
            ],
            [
                'parent_id' => 2,
                'name' => '',
                'relation_enum' => '',
                'is_primary_contact' => 0,
                'phone' => '',
                'email' => '',
                'photo_data' => null
            ]
        ];
    }

    // Get attendance stats and absent days
    if ($student_data && !isset($demo_mode)) {
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
                ROUND((SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_percentage
            FROM attendance 
            WHERE roll_number = ? 
            AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->bind_param("s", $student_data['roll_number']);
        $stmt->execute();
        $result = $stmt->get_result();
        $attendance_result = $result->fetch_assoc();
        $stmt->close();
        
        if ($attendance_result) {
            $attendance_stats = $attendance_result;
        }

        $stmt = $conn->prepare("
            SELECT 
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days
            FROM attendance 
            WHERE roll_number = ? 
            AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->bind_param("s", $student_data['roll_number']);
        $stmt->execute();
        $result = $stmt->get_result();
        $absent_result = $result->fetch_assoc();
        $stmt->close();
        
        if ($absent_result) {
            $leave_stats = ['active_leaves' => $absent_result['absent_days'] ?? 0];
        }
    } else if (isset($demo_mode)) {
        $attendance_stats = ['attendance_percentage' => '85.5', 'present_days' => '25', 'total_days' => '30'];
        $leave_stats = ['active_leaves' => '3'];
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
    <title>Hostel Management</title>
        <link rel="icon" type="image/png" sizes="32x32" href="image/icons/mkce_s.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        :root {
    --sidebar-width: 250px;
    --sidebar-collapsed-width: 70px;
    --topbar-height: 60px;
    --footer-height: 60px;
    --primary-color: #4e73df;
    --secondary-color: #858796;
    --success-color: #1cc88a;
    --dark-bg:#1a1c23;
    --light-bg: #f8f9fc;
    --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --dark-bg: #1a1a2e;
    --primary: #3498db;
    --secondary: #2ecc71;
    --accent: #e74c3c;
    --light-bg: #f8f9fa;
    --text-dark: #2c3e50;
    --text-light: #7f8c8d;
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
    background: linear-gradient(135deg, #e7e9f3ff 0%, #f9f9faff 100%);
    color: var(--text-dark);
    min-height: 100vh;
    display: flex;
}


.main-content {
    flex: 1;
    margin-left: var(--sidebar-width);
    padding: 20px;
    min-height: 100vh;
    transition: var(--transition);
}

body.sidebar-collapsed .main-content {
    margin-left: var(--sidebar-collapsed-width);
}


.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 20px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    box-shadow: var(--card-shadow);
    backdrop-filter: blur(10px);
}

.header h1 {
    color: var(--text-dark);
    font-size: 28px;
    font-weight: 600;
    background: linear-gradient(45deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
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


.breadcrumb-area {
    background-image: linear-gradient(to top, #fff1eb 0%, #ace0f9 100%);
    border-radius: 10px;
    box-shadow: var(--card-shadow);
    margin: 80px 0 30px 0;
    padding: 18px 20px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.breadcrumb {
    margin: 0;
    padding: 0;
    background: transparent;
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
}

.breadcrumb-item {
    display: flex;
    align-items: center;
    white-space: nowrap;
}

.breadcrumb-item a {
    color: var(--primary);
    text-decoration: none;
    transition: var(--transition);
    font-weight: 400;
}

.breadcrumb-item a:hover {
    color: #224abe;
}

.breadcrumb-item.active {
    color: var(--text-light);
    font-weight: 500;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "/";
    color: var(--text-light);
    padding: 0 10px;
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

.btn-success {
    background: linear-gradient(45deg, var(--success), #219653);
    color: white;
}

.btn-danger {
    background: linear-gradient(45deg, var(--danger), #c0392b);
    color: white;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

/* Details Card */
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

/* Info Grid */
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

/* Different border colors for info sections */
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
    display: block;
}

/* Parent Cards */
.parent-card {
    background: white;
    color: var(--text-dark);
    padding: 20px;
    border-radius: 15px;
    margin-bottom: 15px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.parent-card .info-item {
    border-bottom-color: rgba(0, 0, 0, 0.1);
}

.parent-card .info-label {
    color: var(--text-light);
}

.parent-card .info-value {
    color: var(--text-dark);
}

.primary-badge {
    background: rgba(57, 199, 10, 0.86);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    margin-left: 0;
    margin-top: 5px;
    white-space: nowrap;
    display: inline-block;
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

/* Messages */
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

/* Parent Photos */
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

/* Loader Styles */
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

.sidebar.collapsed + .content .loader-container {
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
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Attendance Calendar Styles */
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
    font-size: 18px;
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

/* PDF-specific styles */
.pdf-container {
    width: 100%;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: white;
    font-family: 'Times New Roman', Times, serif;
    font-size: 12pt;
    line-height: 1.4;
    color: #000;
}

.pdf-profile-container {
    display: block;
}

.pdf-profile-card {
    background: white;
    box-shadow: none;
    border: none;
    border-radius: 0;
    padding: 0;
    margin-bottom: 20px;
    text-align: left;
}

.pdf-profile-name {
    font-size: 18pt;
    font-weight: bold;
    margin-bottom: 5px;
    margin-left: 260px;
}

.pdf-profile-roll {
    font-size: 14pt;
    margin-bottom: 10px;
    margin-left: 300px;
}

.pdf-profile-department {
    background: none;
    color: #000;
    padding: 0;
    font-size: 12pt;
    box-shadow: none;
    margin-bottom: 15px;
    margin-left: 350px;
}

.pdf-profile-stats {
    display: flex;
    justify-content: space-around;
    margin: 15px 0;
    padding: 10px 0;
    border-top: 1px solid #000;
    border-bottom: 1px solid #000;
}

.pdf-stat-value {
    font-size: 16pt;
}

.pdf-stat-label {
    font-size: 10pt;
}

.pdf-details-card {
    background: white;
    box-shadow: none;
    border: none;
    border-radius: 0;
    padding: 0;
    margin-bottom: 20px;
}

.pdf-card-header {
    border-bottom: 2px solid #000;
    margin-bottom: 15px;
    padding-bottom: 10px;
}

.pdf-card-title {
    font-size: 16pt;
    background: none;
    -webkit-text-fill-color: #000;
    color: #000;
}

.pdf-info-grid {
    display: block;
}

.pdf-info-section {
    background: white;
    border: 1px solid #000;
    border-radius: 0;
    padding: 15px;
    margin-bottom: 15px;
    page-break-inside: avoid;
}

.pdf-section-title {
    font-size: 14pt;
    border-bottom: 1px solid #000;
    margin-bottom: 10px;
}

.pdf-info-item {
    display: flex;
    margin-bottom: 8px;
    padding: 5px 0;
    border-bottom: 1px solid #ddd;
}

.pdf-info-label {
    width: 150px;
    font-weight: bold;
    color: #000;
}

.pdf-info-value {
    color: #000;
}

.pdf-parent-card {
    background: white;
    color: #000;
    border: 1px solid #000;
    border-radius: 0;
    padding: 10px;
    margin-bottom: 10px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.pdf-primary-badge {
    background: #22e109ff;
    color: #000;
    border: 1px solid #000;
    padding: 2px 6px;
    font-size: 9pt;
}

/* Responsive Styles */
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

    .header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }

    .profile-stats {
        flex-direction: column;
        gap: 15px;
    }

    .action-buttons {
        flex-direction: column;
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

    .loader-container {
        left: 0;
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
}

.container-fluid {
    padding: 20px;
}
    </style>
</head>

<body>
    <?php include 'topbar.php'; ?>
    <?php include 'sidebar.php'; ?>
            
   <div class="main-content" id="mainContent" style="margin-top: <?php echo isset($demo_mode) ? '50px' : '0'; ?>;">
    <?php if (isset($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="breadcrumb-area mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="leave_Apply.php"></i> Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Profile</li>
            </ol>
        </nav>
    </div>
    
    <div id="pdfContent" style="display: none;">
        <div class="pdf-container">
            <div class="pdf-profile-container">
                <div class="pdf-profile-card">
                    <div class="pdf-profile-name"><?php echo htmlspecialchars($student_data['name'] ?? 'N/A'); ?></div>
                    <div class="pdf-profile-roll"><?php echo htmlspecialchars($student_data['roll_number'] ?? 'N/A'); ?></div>
                    <div class="pdf-profile-department">
                        <?php echo htmlspecialchars($student_data['department'] ?? 'N/A'); ?>
                    </div>

                    <div class="pdf-profile-stats">
                        <div class="stat">
                            <div class="pdf-stat-value">
                                <?php echo $attendance_stats['attendance_percentage'] ?? '0'; ?>%
                            </div>
                            <div class="pdf-stat-label">Attendance</div>
                        </div>
                        <div class="stat">
                            <div class="pdf-stat-value">
                                <?php echo $leave_stats['active_leaves'] ?? '0'; ?>
                            </div>
                            <div class="pdf-stat-label">Active Leaves</div>
                        </div>
                    </div>
                </div>

                <!-- Details Card -->
                <div class="pdf-details-card">
                    <div class="pdf-card-header">
                        <div class="pdf-card-title">Student Information</div>
                    </div>

                    <div class="pdf-info-grid">
                        <!-- Personal Info -->
                        <div class="pdf-info-section">
                            <div class="pdf-section-title">
                                Personal Details
                            </div>
                            <div class="pdf-info-item">
                                <div class="pdf-info-label">Full Name:</div>
                                <div class="pdf-info-value">
                                    <?php echo htmlspecialchars($student_data['name'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="pdf-info-item">
                                <div class="pdf-info-label">Date of Birth</div>
                                <div class="pdf-info-value">
                                    <?php echo htmlspecialchars($student_data['date_of_birth'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="pdf-info-item">
                                <div class="pdf-info-label">Gender:</div>
                                <div class="pdf-info-value">
                                    <?php echo htmlspecialchars($student_data['gender'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="pdf-info-item">
                                <div class="pdf-info-label">Email:</div>
                                <div class="pdf-info-value">
                                    <?php echo htmlspecialchars($student_data['email'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="pdf-info-item">
                                <div class="pdf-info-label">Phone:</div>
                                <div class="pdf-info-value">
                                    <?php echo htmlspecialchars($student_data['student_phone'] ?? 'N/A'); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Academic Info-->
                        <div class="pdf-info-section">
                            <div class="pdf-section-title">
                                Academic Details
                            </div>
                            <div class="pdf-info-item">
                                <div class="pdf-info-label">Roll Number:</div>
                                <div class="pdf-info-value">
                                    <?php echo htmlspecialchars($student_data['roll_number'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="pdf-info-item">
                                <div class="pdf-info-label">Department:</div>
                                <div class="pdf-info-value">
                                    <?php echo htmlspecialchars($student_data['department'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="pdf-info-item">
                                <div class="pdf-info-label">Academic Year:</div>
                                <div class="pdf-info-value">
                                    <?php echo htmlspecialchars($student_data['academic_year'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="pdf-info-item">
                                <div class="pdf-info-label">Year of Study</div>
                                <div class="pdf-info-value">
                                    <?php echo htmlspecialchars($student_data['Year_of_study'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="pdf-info-item">
                                <div class="pdf-info-label">Batch</div>
                                <div class="pdf-info-value">
                                    <?php echo htmlspecialchars($student_data['batch'] ?? 'N/A'); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Hostel Info-->
                        <div class="pdf-info-section">
                            <div class="pdf-section-title">
                                Hostel Details
                            </div>
                            <div class="pdf-info-item">
                                <div class="pdf-info-label">Hostel Name:</div>
                                <div class="pdf-info-value">
                                    <?php echo htmlspecialchars($student_data['hostel_name'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="pdf-info-item">
                                <div class="pdf-info-label">Hostel Code:</div>
                                <div class="pdf-info-value">
                                    <?php echo htmlspecialchars($student_data['hostel_code'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="pdf-info-item">
                                <div class="pdf-info-label">Hostel Address:</div>
                                <div class="pdf-info-value">
                                    <?php echo htmlspecialchars($student_data['address'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="pdf-info-item">
                                <div class="pdf-info-label">Admission Date</div>
                                <div class="pdf-info-value">
                                    <?php
                                    echo isset($student_data['created_at'])
                                        ? date('Y-m-d', strtotime($student_data['created_at']))
                                        : 'N/A';
                                    ?>
                                </div>
                            </div>
                            <div class="pdf-info-item">
                                <div class="pdf-info-label">Block</div>
                                <div class="pdf-info-value">
                                    <?php echo htmlspecialchars($student_data['block'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="pdf-info-item">
                                <div class="pdf-info-label">Room Number:</div>
                                <div class="pdf-info-value">
                                    <?php echo htmlspecialchars($student_data['room_number'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="pdf-info-item">
                                <div class="pdf-info-label">Room Type:</div>
                                <div class="pdf-info-value">
                                    <?php echo htmlspecialchars($student_data['room_type'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            <div class="pdf-info-item">
                                <div class="pdf-info-label">Room Status: </div>
                                <div class="pdf-info-value">
                                    <?php
                                    echo isset($student_data['occupied']) && $student_data['occupied'] == 1 ? 'Occupied' : 'Not Occupied';
                                    ?>
                                </div>
                            </div>

                            <div class="pdf-info-item">
                                <div class="pdf-info-label">Room Capacity:</div>
                                <div class="pdf-info-value">
                                    <?php echo htmlspecialchars($student_data['capacity'] ?? 'N/A'); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Parent Info -->
                        <div class="pdf-info-section">
                            <div class="pdf-section-title">
                                Parent/Guardian Details
                            </div>
                            <?php if (!empty($parent_data)): ?>
                                <?php foreach ($parent_data as $parent): ?>
                                    <div class="pdf-parent-card">
                                        <div class="pdf-info-item">
                                            <div class="pdf-info-label">Name:</div>
                                            <div class="pdf-info-value">
                                                <?php echo htmlspecialchars($parent['name']); ?>

                                                <?php if ($parent['is_primary_contact']): ?>
                                                    <span class="pdf-primary-badge">Primary Contact</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="pdf-info-item">
                                            <div class="pdf-info-label">Relation:</div>
                                            <div class="pdf-info-value">
                                                <?php echo htmlspecialchars($parent['relation_enum']); ?>
                                            </div>
                                        </div>
                                        <div class="pdf-info-item">
                                            <div class="pdf-info-label">Phone:</div>
                                            <div class="pdf-info-value">
                                                <?php echo htmlspecialchars($parent['phone'] ?? 'N/A'); ?>
                                            </div>
                                        </div>
                                        <div class="pdf-info-item">
                                            <div class="pdf-info-label">Email:</div>
                                            <div class="pdf-info-value">
                                                <?php echo htmlspecialchars($parent['email'] ?? 'N/A'); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="pdf-info-item">
                                    <div class="pdf-info-value">No parent/guardian information available</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="profile-container">
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
                
            </div>

            <div class="action-buttons">
                <button type="button" class="btn btn-primary" id="downloadPdf">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </button>
            
            </div>
        </div>

        <!-- Details Card -->
        <div class="details-card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-id-card"></i> Student Information</div>
            </div>
<br>
            <div class="info-grid">
                <!-- Personal Info -->
                <div class="info-section">
                    <div class="section-title">
                        <i class="fas fa-user-circle"></i> Personal Details
                    </div>
                    <div class="info-item">
                        <div class="info-label">Full Name:</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($student_data['name'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Date of Birth</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($student_data['date_of_birth'] ?? 'N/A'); ?>
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
                            <?php echo htmlspecialchars($student_data['email'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone:</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($student_data['student_phone'] ?? 'N/A'); ?>
                        </div>
                    </div>
                </div>

                <!-- Academic Info-->
                <div class="info-section">
                    <div class="section-title">
                        <i class="fas fa-graduation-cap"></i> Academic Details
                    </div>
                    <div class="info-item">
                        <div class="info-label">Roll Number:</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($student_data['roll_number'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Department:</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($student_data['department'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Academic Year:</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($student_data['academic_year'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Year of Study</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($student_data['Year_of_study'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Batch</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($student_data['batch'] ?? 'N/A'); ?>
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
                        <div class="info-label">Hostel Address:</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($student_data['address'] ?? 'N/A'); ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Admission Date</div>
                        <div class="info-value">
                            <?php
                            echo isset($student_data['created_at'])
                                ? date('Y-m-d', strtotime($student_data['created_at']))
                                : 'N/A';
                            ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Block</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($student_data['block'] ?? 'N/A'); ?>
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
                        <div class="info-label">Room Status: </div>
                        <div class="info-value">
                            <?php
                            echo isset($student_data['occupied']) && $student_data['occupied'] == 1 ? 'Occupied' : 'Not Occupied';
                            ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Room Capacity:</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($student_data['capacity'] ?? 'N/A'); ?>
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
                                        <?php echo htmlspecialchars($parent['name']); ?>
                                        <?php if ($parent['is_primary_contact']): ?>
                                            <span class="primary-badge">Primary Contact</span>
                                        <?php endif; ?>
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
                                        <?php echo htmlspecialchars($parent['phone'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Email:</div>
                                    <div class="info-value">
                                        <?php echo htmlspecialchars($parent['email'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="info-item">
                            <div class="info-value" style="color: var(--text-light);">No parent/guardian information
                                available</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

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
        document.getElementById('downloadPdf').addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
            this.disabled = true;
            const { jsPDF } = window.jspdf;
            const pdfContent = document.getElementById('pdfContent');
            pdfContent.style.display = 'block';
            const pdf = new jsPDF('p', 'mm', 'a4');
            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();
            html2canvas(pdfContent, {
                scale: 3, //for giving clarity
                useCORS: true,
                logging: false
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const imgWidth = pageWidth;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);
                pdf.save('student_profile_' + new Date().getTime() + '.pdf');
                pdfContent.style.display = 'none';

                this.innerHTML = originalText;
                this.disabled = false;
            }).catch(error => {
                console.error('Error generating PDF:', error);
                alert('Error generating PDF. Please try again.');
                pdfContent.style.display = 'none';
                this.innerHTML = originalText;
                this.disabled = false;
            });
        });
    });
</script>
</body>
</html>
