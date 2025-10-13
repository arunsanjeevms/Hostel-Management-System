<?php
session_start();
try {
    require_once 'db.php';
} catch (Exception $e) {
    die("Database configuration error: " . $e->getMessage());
}

// DEMO mode fallback if user is not logged in
if (!isset($_SESSION['user_id'])) {
    $demo_mode = true;
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'S1001';  // Demo roll number
    $_SESSION['user_type'] = 'student';
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'student';
$roll_number = null;

// ✅ Get roll_number based on user type
try {
    if ($user_type === 'student') {
        $stmt = $conn->prepare("SELECT roll_number FROM students WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();

        if ($student) {
            $roll_number = $student['roll_number'];
        } elseif (isset($demo_mode)) {
            $roll_number = 'S1001'; // demo fallback
        } else {
            die("Student not found.");
        }

    } elseif ($user_type === 'admin' || $user_type === 'faculty') {
        if (isset($_GET['roll_number']) && $_GET['roll_number'] !== '') {
            $roll_number = $_GET['roll_number'];
        } else {
            die("Please provide a student's roll number to view attendance.");
        }
    } else {
        die("Invalid user type.");
    }
} catch (Exception $e) {
    die("Error fetching student roll number: " . $e->getMessage());
}

// ✅ Fetch student_id using roll_number
$student_id = null;
try {
    $stmt = $conn->prepare("SELECT student_id FROM students WHERE roll_number = ?");
    $stmt->bind_param("s", $roll_number);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $student_id = $row['student_id'];
    } elseif (isset($demo_mode)) {
        $student_id = 1;
    } else {
        die("Student not found in records.");
    }
    $stmt->close();
} catch (Exception $e) {
    die("Error fetching student_id: " . $e->getMessage());
}

// ✅ Month navigation
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$prev_month = $month - 1; $prev_year = $year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
$next_month = $month + 1; $next_year = $year;
if ($next_month > 12) { $next_month = 1; $next_year++; }

// ✅ Fetch attendance records
$attendance = [];
try {
    $stmt = $conn->prepare("
        SELECT date, status 
        FROM attendance 
        WHERE student_id = ? 
        AND MONTH(date) = ? AND YEAR(date) = ?
        ORDER BY date
    ");
    $stmt->bind_param("iii", $student_id, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $attendance[$row['date']] = $row['status'];
    }
    $stmt->close();
} catch (Exception $e) {
    die("Error fetching attendance records: " . $e->getMessage());
}

// ✅ Handle AJAX mark attendance
if (isset($_POST['ajax_mark'])) {
    $today = date('Y-m-d');
    $marked_by = $_SESSION['user_id'] ?? null;

    $check_stmt = $conn->prepare("SELECT * FROM attendance WHERE student_id=? AND date=?");
    $check_stmt->bind_param("is", $student_id, $today);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();

    if ($check_res->num_rows > 0) {
        echo json_encode(['status' => 'exists']);
    } else {
        $insert_stmt = $conn->prepare("
            INSERT INTO attendance (student_id, roll_number, date, status, marked_by) 
            VALUES (?, ?, ?, 'Present', ?)
        ");
        $insert_stmt->bind_param("issi", $student_id, $roll_number, $today, $marked_by);
        if ($insert_stmt->execute()) {
            echo json_encode(['status' => 'success', 'date' => $today]);
        } else {
            echo json_encode(['status' => 'error']);
        }
    }
    exit();
}

// ✅ Color function
function getColor($status) {
    switch ($status) {
        case 'Present': return '#28a745';
        case 'Absent': return '#dc3545';
        default: return '#e9ecef';
    }
}

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$firstDayOfMonth = date("w", strtotime("$year-$month-01"));
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance</title>
<link rel="icon" type="image/png" sizes="32x32" href="image/icons/mkce_s.png">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
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

body {
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #ece9e6, #ffffff);
    margin: 0; padding: 0;
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

.sidebar.collapsed + .content {
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

@media (max-width: 768px) {
    .content { margin-left: 0; padding-top: 80px; }
    .sidebar { transform: translateX(-100%); width: var(--sidebar-width) !important; }
    .sidebar.mobile-show { transform: translateX(0); }
    .topbar, .footer { left: 0 !important; }
    body.sidebar-open { overflow: hidden; }
    .day { min-height: 60px; font-size: 12px; }
    .day strong { font-size: 14px; }
    .nav-title { font-size: 16px; }
    .nav-bar a { padding: 6px 10px; font-size: 13px; }
    .content-nav ul { flex-wrap: nowrap; overflow-x: auto; padding-bottom: 5px; }
    .content-nav ul::-webkit-scrollbar { height: 4px; }
    .content-nav ul::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.3); border-radius: 2px; }
}

</style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>

<div class="content">
    <div class="text-center my-3">
        <h2>Attendance</h2>

        <?php if ($_SESSION['user_type'] === 'student'): ?>
        <button id="markBtn" class="btn btn-success mt-3">
            <i class="fa-solid fa-check"></i> Mark My Attendance
        </button>
        <div id="markMsg" class="mt-3"></div>
        <?php endif; ?>
    </div>

    <div class="nav-bar mb-3">
        <a href="?roll_number=<?= urlencode($roll_number) ?>&month=<?= $prev_month ?>&year=<?= $prev_year ?>">⟵ Previous</a>
        <div class="nav-title"><?= date("F Y", strtotime("$year-$month-01")) ?></div>
        <a href="?roll_number=<?= urlencode($roll_number) ?>&month=<?= $next_month ?>&year=<?= $next_year ?>">Next ⟶</a>
    </div>

    <div class="calendar" id="calendar">
        <?php
        $daysOfWeek = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        foreach ($daysOfWeek as $dayHeader) echo "<div class='day-header'>$dayHeader</div>";

        for ($i = 0; $i < $firstDayOfMonth; $i++) echo "<div></div>";

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = "$year-" . str_pad($month,2,'0',STR_PAD_LEFT) . "-" . str_pad($day,2,'0',STR_PAD_LEFT);
            $status = $attendance[$date] ?? "-";
            $color = getColor($status);
            $todayClass = ($date == $today) ? "today" : "";
            echo "<div class='day $todayClass' data-date='$date' style='background:$color;'>
                    <strong>$day</strong><small>$status</small>
                  </div>";
        }
        ?>
    </div>

    <div class="legend">
        <div><span style="background:#28a745;"></span> Present</div>
        <div><span style="background:#dc3545;"></span> Absent</div>
        <div><span style="background:#e9ecef;"></span> Not Record</div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script>
$(document).ready(function() {
    $("#markBtn").click(function() {
        $("#markBtn").prop("disabled", true).text("Marking...");
        $.post("attendance.php", { ajax_mark: 1 }, function(response) {
            try {
                let data = JSON.parse(response);
                if (data.status === 'success') {
                    $("#markMsg").html('<div class="alert alert-success">Attendance marked as Present!</div>');
                    let today = data.date;
                    let cell = $(".day[data-date='"+today+"']");
                    cell.css("background","#28a745").find("small").text("Present");
                } else if (data.status === 'exists') {
                    $("#markMsg").html('<div class="alert alert-info">Already marked today.</div>');
                } else {
                    $("#markMsg").html('<div class="alert alert-danger">Error marking attendance.</div>');
                }
            } catch (e) {
                $("#markMsg").html('<div class="alert alert-danger">Unexpected response.</div>');
            }
            $("#markBtn").prop("disabled", false).html('<i class="fa-solid fa-check"></i> Mark My Attendance');
        });
    });
});
</script>
</body>
</html>
