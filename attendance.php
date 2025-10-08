<?php
session_start(); 

$host = "localhost";
$user = "root";
$pass = "";
$db = "innodb";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

                // Get roll number from session, fallback to default
               $default_roll_number = '927623bit027';
               $student_roll_number = $_SESSION['luser'] ?? $default_roll_number;


                // Get month & year
                $month = $_GET['month'] ?? date('m');
                $year = $_GET['year'] ?? date('Y');

                $month = (int) $month;
                $year = (int) $year;

                // Calculate previous and next months
                $prev_month = $month - 1;
                $prev_year = $year;
                if ($prev_month < 1) {
                    $prev_month = 12;
                    $prev_year--;
                }

                $next_month = $month + 1;
                $next_year = $year;
                if ($next_month > 12) {
                    $next_month = 1;
                    $next_year++;
                }

                // Fetch attendance data
                $sql = "SELECT date, status 
        FROM attendance 
        WHERE student_roll_number = ? 
          AND MONTH(date) = ? 
          AND YEAR(date) = ? 
        ORDER BY date";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sii", $student_roll_number, $month, $year);
                $stmt->execute();
                $result = $stmt->get_result();

                $attendance = [];
                while ($row = $result->fetch_assoc()) {
                    $attendance[$row['date']] = $row['status'];
                }

                // Color map
                function getColor($status)
                {
                    switch ($status) {
                        case 'Present':
                            return '#28a745'; // Green
                        case 'Absent':
                            return '#dc3545'; // Red
                        default:
                            return '#e9ecef'; // Gray for no record
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
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- Sidebar -->
    <?php include 'index.php'; ?>

    <!-- Main Content -->
    <div class="content">

        <div class="loader-container" id="loaderContainer">
            <div class="loader"></div>
        </div>

        <!-- Content Area -->
        <div class="container-fluid">
            <div class="custom-tabs">

                <!DOCTYPE html>
                <html>

                <head>
                    <title>Attendance Calendar</title>
                    <style>
                        body {
                            font-family: 'Segoe UI', sans-serif;
                            background: linear-gradient(135deg, #ece9e6, #ffffff);
                            margin: 0;
                            padding: 0;
                        }

                        h2 {
                            text-align: center;
                            margin: 20px 0;
                            color: #343a40;
                        }

                        .nav-bar {
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            width: 90%;
                            max-width: 900px;
                            margin: 0 auto 5px auto;
                        }

                        .nav-title {
                            font-size: 20px;
                            font-weight: bold;
                            color: #343a40;
                        }

                        .nav-bar a {
                            text-decoration: none;
                            padding: 8px 15px;
                            background: #4e73df;
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
                            position: relative;
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

                        @media (max-width: 768px) {
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
                    </style>
                </head>

                <body><h2>Attendance</h2>
                    <div class="nav-bar">
                        <a
                            href="?student_roll_number=<?php echo urlencode($student_roll_number); ?>&month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>">⟵
                            Previous</a>
                        <div class="nav-title"><?php echo date("F Y", strtotime("$year-$month-01")); ?></div>
                        <a
                            href="?student_roll_number=<?php echo urlencode($student_roll_number); ?>&month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>">Next
                            ⟶</a>
                    </div>

                    <div class="calendar">
                        <div class="day-header">Sun</div>
                        <div class="day-header">Mon</div>
                        <div class="day-header">Tue</div>
                        <div class="day-header">Wed</div>
                        <div class="day-header">Thu</div>
                        <div class="day-header">Fri</div>
                        <div class="day-header">Sat</div>

                        <?php
                        // Empty cells before 1st day
                        for ($i = 0; $i < $firstDayOfMonth; $i++) {
                            echo "<div></div>";
                        }

                        // Calendar days
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $date = "$year-" . str_pad($month, 2, "0", STR_PAD_LEFT) . "-" . str_pad($day, 2, "0", STR_PAD_LEFT);
                            $status = $attendance[$date] ?? "-";
                            $color = getColor($status);
                            $todayClass = ($date == $today) ? "today" : "";

                            echo "<div class='day $todayClass' style='background:$color;'>
                    <strong>$day</strong>
                    <small>$status</small>
                  </div>";
                        }
                        ?>
                    </div>

                    <div class="legend">
                        <div><span style="background:#28a745;"></span> Present</div>
                        <div><span style="background:#dc3545;"></span> Absent</div>
                        <div><span style="background:#e9ecef;"></span> No Record</div>
                    </div>
                </body>

                </html>


            </div>
        </div>
    </div>

    </div>
</body>

</html>
