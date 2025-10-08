<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "innodb";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance</title>
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



        <!-- Content Area -->
        <div class="container-fluid">
            <div class="custom-tabs">
               
                <?php


                // Get student_roll_number safely (default for testing)
                $student_roll_number = $_GET['student_roll_number'] ?? 'ROLL001';

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
                            width: 95%;
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

                <body><h2>Attendance</h2><br>

                    
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

    <!-- Footer -->
    <?php include 'footer.php'; ?>
    </div>
    <script>
        const loaderContainer = document.getElementById('loaderContainer');

        function showLoader() {
            loaderContainer.classList.add('show');
        }

        function hideLoader() {
            loaderContainer.classList.remove('show');
        }

        //    automatic loader
        document.addEventListener('DOMContentLoaded', function () {
            const loaderContainer = document.getElementById('loaderContainer');
            const contentWrapper = document.getElementById('contentWrapper');
            let loadingTimeout;

            function hideLoader() {
                loaderContainer.classList.add('hide');
                contentWrapper.classList.add('show');
            }

            function showError() {
                console.error('Page load took too long or encountered an error');
                // You can add custom error handling here
            }

            // Set a maximum loading time (10 seconds)
            loadingTimeout = setTimeout(showError, 10000);

            // Hide loader when everything is loaded
            window.onload = function () {
                clearTimeout(loadingTimeout);

                // Add a small delay to ensure smooth transition
                setTimeout(hideLoader, 500);
            };

            // Error handling
            window.onerror = function (msg, url, lineNo, columnNo, error) {
                clearTimeout(loadingTimeout);
                showError();
                return false;
            };
        });

        // Toggle Sidebar
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');
        const body = document.body;
        const mobileOverlay = document.getElementById('mobileOverlay');

        function toggleSidebar() {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('mobile-show');
                mobileOverlay.classList.toggle('show');
                body.classList.toggle('sidebar-open');
            } else {
                sidebar.classList.toggle('collapsed');
            }
        }
        hamburger.addEventListener('click', toggleSidebar);
        mobileOverlay.addEventListener('click', toggleSidebar);
        // Toggle User Menu
        const userMenu = document.getElementById('userMenu');
        const dropdownMenu = userMenu.querySelector('.dropdown-menu');

        userMenu.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', () => {
            dropdownMenu.classList.remove('show');
        });

        // Toggle Submenu
        const menuItems = document.querySelectorAll('.has-submenu');
        menuItems.forEach(item => {
            item.addEventListener('click', () => {
                const submenu = item.nextElementSibling;
                item.classList.toggle('active');
                submenu.classList.toggle('active');
            });
        });

        // Handle responsive behavior
        window.addEventListener('resize', () => {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('collapsed');
                sidebar.classList.remove('mobile-show');
                mobileOverlay.classList.remove('show');
                body.classList.remove('sidebar-open');
            } else {
                sidebar.style.transform = '';
                mobileOverlay.classList.remove('show');
                body.classList.remove('sidebar-open');
            }
        });
    </script>

</body>

</html>
