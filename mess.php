<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hostel";

try {
    // Create connection using MySQLi with exception handling
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error loading character set utf8mb4: " . $conn->error);
    }
    
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}

// Handle AJAX request for monthly bill data
if (isset($_POST['action']) && $_POST['action'] == 'get_monthly_bill') {
    header('Content-Type: application/json');
    
    try {
        // Check if session is started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in
        if (!isset($_SESSION['roll_number'])) {
            echo json_encode(['status' => false, 'msg' => 'User not logged in']);
            exit;
        }
        
        $roll_number = $_SESSION['roll_number'];
        
        // Get month and year from POST data
        $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
        $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
        
        // Validate month and year
        if ($month < 1 || $month > 12) {
            echo json_encode(['status' => false, 'msg' => 'Invalid month']);
            exit;
        }
        
        if ($year < 2020 || $year > (date('Y') + 1)) {
            echo json_encode(['status' => false, 'msg' => 'Invalid year']);
            exit;
        }
        
        // Prepare query to fetch special tokens for the specified month and year
        $stmt = $conn->prepare("SELECT *, DATE_FORMAT(created_at, '%d %b %Y') as formatted_date FROM mess_tokens 
                                WHERE roll_number = ? 
                                AND token_type = 'Special' 
                                AND MONTH(created_at) = ? 
                                AND YEAR(created_at) = ? 
                                ORDER BY created_at ASC");
        $stmt->bind_param("sii", $roll_number, $month, $year);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tokens = [];
        while ($row = $result->fetch_assoc()) {
            $tokens[] = $row;
        }
        
        echo json_encode(['status' => true, 'data' => $tokens]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'msg' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['roll_number'])) {
    // Redirect to login if not logged in
    header('Location: login.php');
    exit();
}

// Get user information
$roll_number = $_SESSION['roll_number'];
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'student'; // Default to student

// Debug: Print session information
// error_log("Session roll_number: " . $roll_number);
// error_log("Session user_type: " . $user_type);
// error_log("Session data: " . print_r($_SESSION, true));

// For student interface
if ($user_type === 'student') {
    // -------------------------
    // Fetch Today's Menu (Both Regular and Special)
    // -------------------------
    $today_date = date('Y-m-d');

    // Fetch all regular meals for today (exclude special meals)
    $stmt = $conn->prepare("SELECT meal_type, items AS menu_items, category, fee 
                            FROM mess_menu 
                            WHERE date = ? AND category = 'Regular'
                            ORDER BY FIELD(meal_type, 'Breakfast', 'Lunch', 'Snacks', 'Dinner')");
    $stmt->bind_param("s", $today_date);
    $stmt->execute();
    $today_menu = $stmt->get_result();

    // -------------------------
    // Fetch Available Special Meals
    // Modified to fetch ALL special meals from specialtokenenable table
    // And properly check if already requested for the same meal type on current date
    // -------------------------
    $current_date = date('Y-m-d');
    $current_time = date('H:i:s');

    // Fetch ALL special meals from specialtokenenable table with proper request status checking
    // This query properly checks if a token has been requested for the same meal type on the specific token date
    $stmt = $conn->prepare("SELECT 
        s.*,
        (SELECT t.token_id 
         FROM mess_tokens t 
         JOIN mess_menu m ON t.menu_id = m.menu_id 
         WHERE t.roll_number = ? 
         AND m.meal_type = s.meal_type 
         AND m.category = 'Special' 
         AND DATE(m.date) = DATE(s.token_date) 
         AND t.token_type = 'Special' 
         LIMIT 1) AS requested_token
    FROM specialtokenenable s
    ORDER BY s.from_date, s.from_time");
    $stmt->bind_param("s", $roll_number);
    $stmt->execute();
    $special_menus_all = $stmt->get_result();
    
    // Debug: Print the number of rows and some sample data
    // if (isset($_GET['debug'])) {
    //     echo "<pre>";
    //     echo "Roll number: " . $roll_number . "\n";
    //     echo "Current date: " . $current_date . "\n";
    //     echo "Special menus count: " . $special_menus_all->num_rows . "\n";
    //     while ($row = $special_menus_all->fetch_assoc()) {
    //         echo "Menu ID: " . $row['menu_id'] . ", Meal Type: " . $row['meal_type'] . ", Requested: " . ($row['requested_token'] ? 'Yes (' . $row['requested_token'] . ')' : 'No') . "\n";
    //     }
    //     echo "</pre>";
    //     exit;
    // }

    // -------------------------
    // Fetch Special Token History
    // -------------------------
    $special_token_history_array = [];
    $stmt = $conn->prepare("SELECT * FROM mess_tokens 
                            WHERE roll_number = ? AND token_type='Special' 
                            ORDER BY created_at DESC");
    $stmt->bind_param("s", $roll_number);
    $stmt->execute();
    $history_res = $stmt->get_result();
    while($h = $history_res->fetch_assoc()) {
        $special_token_history_array[] = $h;
    }

    // -------------------------
    // Fetch Monthly Bill
    // Calculate total special fees for current month
    // -------------------------
    $current_month = date('Y-m');
    $stmt = $conn->prepare("SELECT COALESCE(SUM(special_fee), 0) AS total_special FROM mess_tokens 
                            WHERE roll_number = ? AND token_type='Special' 
                            AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->bind_param("ss", $roll_number, $current_month);
    $stmt->execute();
    $monthly_bill = $stmt->get_result()->fetch_assoc();
    $total_special_fee = $monthly_bill['total_special'];
}

// For admin/management interface
if ($user_type === 'admin') {
    // Admin specific data fetching would go here
    // For now, we'll just set up the interface structure
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIC - Mess Management</title>
    <link rel="icon" type="image/png" sizes="32x32" href="image/icons/mkce_s.png">
    <link rel="stylesheet" href="css/stu-mess.css">

    <!-- Bootstrap 5 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <!-- Custom JS -->
    <script src="js/mess.js"></script>
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
            --table-header-gradient: linear-gradient(135deg, #4CAF50, #2196F3);
            --today-menu-gradient: linear-gradient(135deg, #f39c12, #e74c3c);
            --special-meals-gradient: linear-gradient(135deg, #3498db, #2c3e50);
            --monthly-bill-gradient: linear-gradient(135deg, #2ecc71, #27ae60);
            --load-btn-gradient: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
        }

        /* Larger request buttons with spacing */
        .request-btn {
            padding: 0.5rem 1rem !important;
            font-size: 0.9rem !important;
            min-width: 120px;
        }

        .request-btn i {
            margin-right: 0.5rem;
        }

        .content {
            margin-left: var(--sidebar-width);
            padding-top: var(--topbar-height);
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .sidebar.collapsed~.content {
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

        .container-fluid {
            padding: 20px;
        }

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

        .sidebar.collapsed~.content .loader-container {
            left: var(--sidebar-collapsed-width);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width) !important;
            }

            .sidebar.mobile-show {
                transform: translateX(0);
            }

            .content {
                margin-left: 0 !important;
            }

            .loader-container {
                left: 0;
            }
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

        .table-responsive {
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-top: 20px;
            background: white;
            padding: 20px;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: var(--table-header-gradient);
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px 10px;
        }

        .table tbody td {
            padding: 12px 10px;
            vertical-align: middle;
            border-bottom: 1px solid #e3e6f0;
        }

        .table tbody tr:hover {
            background-color: #f8f9fc;
        }

        .btn-group-sm .btn {
            margin: 0 2px;
        }

        .badge {
            font-size: 0.75em;
            padding: 0.5em 0.75em;
        }
        
        /* Custom tabs styling to match your design */
        .custom-tabs .nav-tabs {
            border-bottom: 2px solid #e3e6f0;
        }
        
        .custom-tabs .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 500;
            color: #555;
            padding: 12px 20px;
            transition: all 0.3s ease;
            background-color: #f8f9fc;
            margin-right: 5px;
            border-radius: 0;
        }
        
        .custom-tabs .nav-tabs .nav-link.active {
            color: #4e73df;
            border-bottom: 3px solid #4e73df;
            background-color: transparent;
        }
        
        .custom-tabs .nav-tabs .nav-link:hover {
            color: #1cc88a;
            border-bottom: 3px solid #1cc88a;
        }
        
        .tab-icon {
            margin-right: 5px;
        }
        
        /* Consistent tab styling */
        .nav-pills .nav-link {
            border-radius: 50px;
            padding: 10px 20px;
            margin-right: 8px;
            transition: all 0.3s ease;
            color: #555;
            background-color: #f8f9fc;
            border: 1px solid #e3e6f0;
        }

        .nav-pills .nav-link.active {
            background: var(--table-header-gradient);
            color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
            border: 1px solid transparent;
        }

        .nav-pills .nav-link:hover {
            background: #f8f9fc;
            color: #555;
        }
        
        /* Load button styling */
        #loadBillBtn {
            background: var(--load-btn-gradient);
            border: none;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        #loadBillBtn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        /* Unique gradients for each tab button */
        #today-menu-tab.nav-link.active {
            background: var(--today-menu-gradient) !important;
            color: white !important;
        }
        
        #special-meals-tab.nav-link.active {
            background: var(--special-meals-gradient) !important;
            color: white !important;
        }
        
        #monthly-bill-tab.nav-link.active {
            background: var(--monthly-bill-gradient) !important;
            color: white !important;
        }
        
        /* Ensure text color stays white on hover for active tabs */
        #today-menu-tab.nav-link.active:hover {
            color: white !important;
            background: var(--today-menu-gradient) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
        }
        
        #special-meals-tab.nav-link.active:hover {
            color: white !important;
            background: var(--special-meals-gradient) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
        }
        
        #monthly-bill-tab.nav-link.active:hover {
            color: white !important;
            background: var(--monthly-bill-gradient) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
        }
        
        /* Keep inactive tabs text color consistent on hover */
        #today-menu-tab.nav-link:not(.active):hover,
        #special-meals-tab.nav-link:not(.active):hover,
        #monthly-bill-tab.nav-link:not(.active):hover {
            color: #555 !important;
            background: #f8f9fc !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="content">
        <div class="loader-container hide" id="loaderContainer">
            <div class="loader"></div>
        </div>

        <!-- Topbar -->
        <?php include 'topbar.php'; ?>

        <!-- Breadcrumb -->
        <div class="breadcrumb-area custom-gradient">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="#">Mess Menu</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Management</li>
                </ol>
            </nav>
        </div>

        <!-- Content Area -->
        <div class="container-fluid">
            <?php if ($user_type === 'student'): ?>
                <!-- Student Interface -->
                <!-- Tabs Navigation -->
                <div class="main-container">

                
                    <ul class="nav nav-pills mb-3" id="menuTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="today-menu-tab" data-bs-toggle="pill" data-bs-target="#today-menu" type="button" role="tab" aria-controls="today-menu" aria-selected="true">
                                <i class="fas fa-utensils tab-icon"></i> Today's Menu
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="special-meals-tab" data-bs-toggle="pill" data-bs-target="#special-meals" type="button" role="tab" aria-controls="special-meals" aria-selected="false">
                                <i class="fas fa-mug-hot tab-icon"></i> Special Meals
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="monthly-bill-tab" data-bs-toggle="pill" data-bs-target="#monthly-bill" type="button" role="tab" aria-controls="monthly-bill" aria-selected="false">
                                <i class="fas fa-file-invoice-dollar tab-icon"></i> Monthly Bill
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="menuTabsContent">
                        <div class="tab-pane fade show active" id="today-menu" role="tabpanel" aria-labelledby="today-menu-tab">
                            <div class="table-container">
                                <h5 class="table-title">Today's Menu - <?php echo date('d M Y'); ?></h5>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead class="gradient-header">
                                            <tr>
                                                <th>Meal Type</th>
                                                <th>Items</th>
                                                <th>Fee (₹)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($today_menu->num_rows === 0): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">No menu available for today.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php while ($row = $today_menu->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($row['meal_type']) ?></td>
                                                        <td><?= nl2br(htmlspecialchars($row['menu_items'])) ?></td>
                                                        <td><?= number_format($row['fee'], 2) ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- ======================= -->
                        <!-- ✅ SPECIAL MEALS TAB -->
                        <!-- ======================= -->
                        <div class="tab-pane fade" id="special-meals" role="tabpanel" aria-labelledby="special-meals-tab">
                            <div class="combined-table-container">
                                <!-- Available Special Meals -->
                                <div class="table-container">
                                    <h5 class="table-title">Available Special Meals</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered">
                                            <thead class="gradient-header">
                                                <tr>
                                                    <th>Special Meal Date</th>
                                                    <th>From Date</th>
                                                    <th>From Time</th>
                                                    <th>To Date</th>
                                                    <th>To Time</th>
                                                    <th>Meal Type</th>
                                                    <th>Items</th>
                                                    <th>Fee (₹)</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if($special_menus_all->num_rows > 0): ?>
                                                    <?php while ($row = $special_menus_all->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($row['token_date']) ?></td>
                                                            <td><?= htmlspecialchars($row['from_date']) ?></td>
                                                            <td><?= substr($row['from_time'],0,5) ?></td>
                                                            <td><?= htmlspecialchars($row['to_date']) ?></td>
                                                            <td><?= substr($row['to_time'],0,5) ?></td>
                                                            <td><?= htmlspecialchars($row['meal_type']) ?></td>
                                                            <td><?= nl2br(htmlspecialchars($row['menu_items'])) ?></td>
                                                            <td><?= number_format($row['fee'],2) ?></td>
                                                            <td>
                                                                <?php if (!empty($row['requested_token'])): ?>
                                                                    <button class="btn btn-warning" disabled style="padding: 0.5rem 1rem; font-size: 0.9rem; min-width: 120px;">
                                                                        <i class="fas fa-check"></i> Special Token Taken
                                                                    </button>
                                                                <?php else: ?>
                                                                    <button class="btn btn-success request-btn" data-menu-id="<?= $row['menu_id'] ?>">
                                                                        <i class="fas fa-plus-circle"></i> Request
                                                                    </button>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="9" class="text-center text-muted">No special meals available.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Special Token History -->
                                <div class="table-container">
                                    <h5 class="table-title">Special Token History</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered">
                                            <thead class="gradient-header">
                                                <tr>
                                                    <th>S.no</th>
                                                    <th>Token Date</th>
                                                    <th>Meal Type</th>
                                                    <th>Menu Items</th>
                                                    <th>Fee (₹)</th>
                                                    <th>Requested At</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(empty($special_token_history_array)): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">No special tokens requested yet.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php 
                                                    $serial_number = 1;
                                                    foreach($special_token_history_array as $h): ?>
                                                        <tr>
                                                            <td><?= $serial_number++ ?></td>
                                                            <td><?= htmlspecialchars($h['token_date']) ?></td>
                                                            <td><?= htmlspecialchars($h['meal_type']) ?></td>
                                                            <td><?= nl2br(htmlspecialchars($h['menu'])) ?></td>
                                                            <td><?= number_format($h['special_fee'],2) ?></td>
                                                            <td><?= htmlspecialchars($h['created_at']) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ======================= -->
                        <!-- ✅ MONTHLY BILL TAB -->
                        <!-- ======================= -->
                        <div class="tab-pane fade" id="monthly-bill" role="tabpanel" aria-labelledby="monthly-bill-tab">
                            <div class="combined-table-container">
                                <!-- Bill Details Section -->
                                <div class="table-container">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="table-title mb-0">Monthly Bill Details</h5>
                                        <div class="d-flex gap-2">
                                            <select id="billMonth" class="form-select form-select-sm" style="width: auto;">
                                                <?php 
                                                $months = range(1, 12);
                                                $current_month = date('n');
                                                foreach($months as $month): 
                                                    $month_name = date('F', mktime(0, 0, 0, $month, 10));
                                                    $selected = ($month == $current_month) ? 'selected' : '';
                                                ?>
                                                    <option value="<?= $month ?>" <?= $selected ?>><?= $month_name ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select id="billYear" class="form-select form-select-sm" style="width: auto;">
                                                <?php 
                                                $years = range(2020, date('Y') + 1);
                                                $current_year = date('Y');
                                                foreach($years as $year): 
                                                    $selected = ($year == $current_year) ? 'selected' : '';
                                                ?>
                                                    <option value="<?= $year ?>" <?= $selected ?>><?= $year ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button id="loadBillBtn" class="btn btn-primary btn-sm">Load</button>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered" id="monthlyBillTable">
                                            <thead class="gradient-header">
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Date</th>
                                                    <th>Meal Type</th>
                                                    <th>Items</th>
                                                    <th>Fee (₹)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $monthly_total = 0;
                                                $serial_number = 1;
                                                foreach($special_token_history_array as $h): 
                                                    $monthly_total += $h['special_fee'];
                                                ?>
                                                    <tr>
                                                        <td><?= $serial_number++ ?></td>
                                                        <td><?= htmlspecialchars(date("d M Y", strtotime($h['created_at']))) ?></td>
                                                        <td><?= htmlspecialchars($h['meal_type']) ?></td>
                                                        <td><?= nl2br(htmlspecialchars($h['menu'])) ?></td>
                                                        <td><?= number_format($h['special_fee'], 2) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-primary">
                                                    <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                                    <td><strong>₹<?= number_format($monthly_total, 2) ?></strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Admin/Management Interface -->
                <div class="main-container">
                    <div class="custom-tabs">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" id="dailymenu-main-tab" href="#dailymenu" role="tab" aria-selected="true">
                                    <span style="font-size: 0.9em;"><i class="fas fa-utensils tab-icon"></i> Daily Menu</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" id="specialtoken-main-tab" href="#specialtoken" role="tab" aria-selected="false">
                                    <span style="font-size: 0.9em;"><i class="fas fa-mug-hot tab-icon"></i> Special Token</span>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" id="tokens-main-tab" href="#tokens" role="tab" aria-selected="false">
                                    <span style="font-size: 0.9em;"><i class="fas fa-ticket-alt tab-icon"></i> View Tokens</span>
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- Daily Menu Tab -->
                            <div class="tab-pane fade show active" id="dailymenu" role="tabpanel">
                                <div class="table-container">
                                    <h5 class="table-title">Daily Menu Management</h5>
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <button type="button" class="btn w-100" id="breakfast" data-bs-toggle="modal" data-bs-target="#breakfastModal">
                                                <i class="fas fa-coffee"></i> Breakfast
                                            </button>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <button type="button" class="btn w-100" id="lunch" data-bs-toggle="modal" data-bs-target="#lunchModal">
                                                <i class="fas fa-hamburger"></i> Lunch
                                            </button>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <button type="button" class="btn w-100" id="snacks" data-bs-toggle="modal" data-bs-target="#snacksModal">
                                                <i class="fas fa-cookie"></i> Snacks
                                            </button>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <button type="button" class="btn  w-100" id="dinner" data-bs-toggle="modal" data-bs-target="#dinnerModal">
                                                <i class="fas fa-utensils"></i> Dinner
                                            </button>
                                        </div>
                                    </div>
                                    <!-- View Menu -->

                                    <div class="table-responsive">
                                        <table class="table table-striped" id="messMenuTable">
                                            <thead class="gradient-header">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Meal Type</th>
                                                    <th>Items</th>
                                                    <th>Category</th>
                                                    <th>Fee</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be populated by DataTables -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Special Token Tab -->
                            <div class="tab-pane fade" id="specialtoken" role="tabpanel">
                                <div class="table-container">
                                    <h5 class="table-title">Special Token Management</h5>
                                    <button type="button" class="btn " id="enableSpecialToken" data-bs-toggle="modal" data-bs-target="#specialtokenModal">
                                        <i class="fas fa-plus"></i> Enable Special Token
                                    </button>
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="specialtokenEnableTable">
                                            <thead class="gradient-header">
                                                <tr>
                                                    <th>From Date</th>
                                                    <th>From Time</th>
                                                    <th>To Date</th>
                                                    <th>To Time</th>
                                                    <th>Token Date</th>
                                                    <th>Meal Type</th>
                                                    <th>Items</th>
                                                    <th>Fee</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be populated by DataTables -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>



                            <!-- View Tokens Tab -->
                            <div class="tab-pane fade" id="tokens" role="tabpanel">
                                <div class="table-container">
                                    <h5 class="table-title">View All Tokens</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="messTokensTable">
                                            <thead class="gradient-header">
                                                <tr>
                                                    <th>Student Name</th>
                                                    <th>Roll Number</th>
                                                    <th>Meal Type</th>
                                                    <th>Menu</th>
                                                    <th>Date</th>
                                                    <th>Token Type</th>
                                                    <th>Special Fee</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be populated by DataTables -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be populated by DataTables -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($user_type !== 'student'): ?>
        <!-- Breakfast Modal -->
        <div class="modal fade" id="breakfastModal" tabindex="-1" aria-labelledby="breakfastModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="breakfastModalLabel">Breakfast Menu</h5>
                        <button type="submit" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="breakfastMenuForm" method="post">
                            <div class="mb-3">
                                <label for="breakfastDate" class="form-label">Date</label>
                                <input type="date" class="form-control" id="breakfastDate" name="date" required>
                            </div>
                            <div class="mb-3">
                                <label for="breakfastItems" class="form-label">Menu Items</label>
                                <textarea class="form-control" id="breakfastItems" name="items" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="breakfastCategory" class="form-label">Category</label>
                                <input type="text" class="form-control" id="breakfastCategory" name="category" placeholder="Regular/Special">
                            </div>
                            <div class="mb-3">
                                <label for="breakfastFee" class="form-label">Fee</label>
                                <input type="number" step="0.01" class="form-control" id="breakfastFee" name="fee" required>
                            </div>
                            <input type="hidden" name="meal_type" value="Breakfast">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" data-save-form="breakfastMenuForm">Save changes</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lunch Modal -->
        <div class="modal fade" id="lunchModal" tabindex="-1" aria-labelledby="lunchModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="lunchModalLabel">Lunch Menu</h5>
                        <button type="submit" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="lunchMenuForm" method="post">
                            <div class="mb-3">
                                <label for="lunchDate" class="form-label">Date</label>
                                <input type="date" class="form-control" id="lunchDate" name="date" required>
                            </div>
                            <div class="mb-3">
                                <label for="lunchItems" class="form-label">Menu Items</label>
                                <textarea class="form-control" id="lunchItems" name="items" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="lunchCategory" class="form-label">Category</label>
                                <input type="text" class="form-control" id="lunchCategory" name="category" placeholder="Regular/Special">
                            </div>
                            <div class="mb-3">
                                <label for="lunchFee" class="form-label">Fee</label>
                                <input type="number" step="0.01" class="form-control" id="lunchFee" name="fee" required>
                            </div>
                            <input type="hidden" name="meal_type" value="Lunch">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" data-save-form="lunchMenuForm">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Snacks model -->
        <div class="modal fade" id="snacksModal" tabindex="-1" aria-labelledby="snacksModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="snacksModalLabel">Snacks Menu</h5>
                        <button type="submit" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="snacksMenuForm" method="post">
                            <div class="mb-3">
                                <label for="snacksDate" class="form-label">Date</label>
                                <input type="date" class="form-control" id="snacksDate" name="date" required>
                            </div>
                            <div class="mb-3">
                                <label for="snacksItems" class="form-label">Menu Items</label>
                                <textarea class="form-control" id="snacksItems" name="items" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="snacksCategory" class="form-label">Category</label>
                                <input type="text" class="form-control" id="snacksCategory" name="category" placeholder="Regular/Special">
                            </div>
                            <div class="mb-3">
                                <label for="snacksFee" class="form-label">Fee (₹)</label>
                                <input type="number" step="0.01" class="form-control" id="snacksFee" name="fee" required>
                            </div>
                            <input type="hidden" name="meal_type" value="Snacks">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" form="snacksMenuForm">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Dinner Modal -->
        <div class="modal fade" id="dinnerModal" tabindex="-1" aria-labelledby="dinnerModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dinnerModalLabel">Dinner Menu</h5>
                        <button type="submit" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="dinnerMenuForm" method="post">
                            <div class="mb-3">
                                <label for="dinnerDate" class="form-label">Date</label>
                                <input type="date" class="form-control" id="dinnerDate" name="date" required>
                            </div>
                            <div class="mb-3">
                                <label for="dinnerItems" class="form-label">Menu Items</label>
                                <textarea class="form-control" id="dinnerItems" name="items" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="dinnerCategory" class="form-label">Category</label>
                                <input type="text" class="form-control" id="dinnerCategory" name="category" placeholder="Regular/Special">
                            </div>
                            <div class="mb-3">
                                <label for="dinnerFee" class="form-label">Fee (₹)</label>
                                <input type="number" step="0.01" class="form-control" id="dinnerFee" name="fee" required>
                            </div>
                            <input type="hidden" name="meal_type" value="Dinner">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" form="dinnerMenuForm">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
        <!--Special Token-->
        <!-- Special Token Modal - FIXED -->
        <div class="modal fade" id="specialtokenModal" tabindex="-1" aria-labelledby="specialtokenModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="specialtokenModalLabel">Special Token</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="tokenenableForm" method="post">
                            <div class="mb-3">
                                <label for="tokenfromDate" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="tokenfromDate" name="date" required>
                            </div>
                            <div class="mb-3">
                                <label for="tokenfromTime" class="form-label">From Time</label>
                                <input type="time" class="form-control" id="tokenfromTime" name="time" required>
                            </div>
                            <div class="mb-3">
                                <label for="tokentoDate" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="tokentoDate" name="date" required>
                            </div>
                            <div class="mb-3">
                                <label for="tokentoTime" class="form-label">To Time</label>
                                <input type="time" class="form-control" id="tokentoTime" name="time" required>
                            </div>
                            <div class="mb-3">
                                <label for="tokenDate" class="form-label">Token Date</label>
                                <input type="date" class="form-control" id="tokenDate" name="date" required>
                            </div>
                            <!-- FIXED: Dedicated Menu Items field for Special Token -->
                             <div class="mb-3">
                                <label for="mealType" class="form-label">Meal Type</label>
                                <textarea class="form-control" id="mealType" name="items" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="specialMenuItems" class="form-label">Menu Items *</label>
                                <textarea class="form-control" id="specialMenuItems" name="items" rows="3"
                                    placeholder="Enter special menu items (e.g., Special lunch, Biryani, Dessert)" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="specialtokenFee" class="form-label">Fee</label>
                                <input type="number" step="0.01" class="form-control" id="specialtokenFee" name="fee" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary">Enable token</button>
                    </div>
                </div>
            </div>
        </div>
        <!--Special Token-->
        <!-- Special Token Modal - FIXED -->
        <div class="modal fade" id="specialtokenModal" tabindex="-1" aria-labelledby="specialtokenModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="specialtokenModalLabel">Special Token</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="tokenenableForm" method="post">
                            <div class="mb-3">
                                <label for="tokenfromDate" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="tokenfromDate" name="date" required>
                            </div>
                            <div class="mb-3">
                                <label for="tokenfromTime" class="form-label">From Time</label>
                                <input type="time" class="form-control" id="tokenfromTime" name="time" required>
                            </div>
                            <div class="mb-3">
                                <label for="tokentoDate" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="tokentoDate" name="date" required>
                            </div>
                            <div class="mb-3">
                                <label for="tokentoTime" class="form-label">To Time</label>
                                <input type="time" class="form-control" id="tokentoTime" name="time" required>
                            </div>
                            <div class="mb-3">
                                <label for="tokenDate" class="form-label">Token Date</label>
                                <input type="date" class="form-control" id="tokenDate" name="date" required>
                            </div>
                            <!-- FIXED: Dedicated Menu Items field for Special Token -->
                             <div class="mb-3">
                                <label for="mealType" class="form-label">Meal Type</label>
                                <textarea class="form-control" id="mealType" name="items" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="specialMenuItems" class="form-label">Menu Items *</label>
                                <textarea class="form-control" id="specialMenuItems" name="items" rows="3"
                                    placeholder="Enter special menu items (e.g., Special lunch, Biryani, Dessert)" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="specialtokenFee" class="form-label">Fee</label>
                                <input type="number" step="0.01" class="form-control" id="specialtokenFee" name="fee" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary">Enable token</button>
                    </div>
                </div>
            </div>
        </div>


        <!-- Edit Menu Modal -->
        <div class="modal fade" id="editMenuModal" tabindex="-1" aria-labelledby="editMenuModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editMenuModalLabel">Edit Menu Item</h5>
                        <button type="submit" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editMenuForm">
                            <input type="hidden" id="editMenuId" name="menu_id">
                            <div class="mb-3">
                                <label for="editMenuDate" class="form-label">Date</label>
                                <input type="date" class="form-control" id="editMenuDate" name="date" required>
                            </div>
                            <div class="mb-3">
                                <label for="editMenuMealType" class="form-label">Meal Type</label>
                                <select class="form-control" id="editMenuMealType" name="meal_type" required>
                                    <option value="Breakfast">Breakfast</option>
                                    <option value="Lunch">Lunch</option>
                                    <option value="Snacks">Snacks</option>
                                    <option value="Dinner">Dinner</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="editMenuItems" class="form-label">Menu Items</label>
                                <textarea class="form-control" id="editMenuItems" name="items" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="editMenuCategory" class="form-label">Category</label>
                                <input type="text" class="form-control" id="editMenuCategory" name="category" placeholder="Regular/Special">
                            </div>
                            <div class="mb-3">
                                <label for="editMenuFee" class="form-label">Fee</label>
                                <input type="number" step="0.01" class="form-control" id="editMenuFee" name="fee" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" onclick="updateMenu()">Update Menu</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Token Details Modal -->
        <div class="modal fade" id="tokenDetailsModal" tabindex="-1" aria-labelledby="tokenDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tokenDetailsModalLabel">Token Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="tokenDetails">
                        <!-- Token details will be populated here -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <!-- Special token edit modal-->
        <div class="modal fade" id="editSpecialTokenModal" tabindex="-1" aria-labelledby="editSpecialTokenLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editSpecialTokenLabel">Edit Special Token</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editSpecialTokenForm">
                            <input type="hidden" id="editSpecialMenuId" />

                            <div class="mb-3">
                                <label for="editSpecialFromDate" class="form-label">From Date</label>
                                <input type="date" id="editSpecialFromDate" class="form-control" />
                            </div>

                            <div class="mb-3">
                                <label for="editSpecialFromTime" class="form-label">From Time</label>
                                <input type="time" id="editSpecialFromTime" class="form-control" />
                            </div>

                            <div class="mb-3">
                                <label for="editSpecialToDate" class="form-label">To Date</label>
                                <input type="date" id="editSpecialToDate" class="form-control" />
                            </div>

                            <div class="mb-3">
                                <label for="editSpecialToTime" class="form-label">To Time</label>
                                <input type="time" id="editSpecialToTime" class="form-control" />
                            </div>

                            <div class="mb-3">
                                <label for="editSpecialTokenDate" class="form-label">Token Date</label>
                                <input type="date" id="editSpecialTokenDate" class="form-control" />
                            </div>
                            
                            <div class="mb-3">
                                <label for="editSpecialMealType" class="form-label">Meal Type</label>
                                <textarea id="editSpecialMealType" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="editSpecialMenuItems" class="form-label">Menu Items</label>
                                <textarea id="editSpecialMenuItems" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="editSpecialFee" class="form-label">Fee (₹)</label>
                                <input type="number" id="editSpecialFee" step="0.01" class="form-control" />
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="updateSpecialToken()">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php include 'footer.php'; ?>

    <script>
        let allMenus = [];
        let allTokens = [];
        let allSpecialTokens = [];

        $(document).ready(function() {
            console.log("=== SIMPLE MESS MANAGEMENT SYSTEM ===");
            setupSystem();
        });

        function setupSystem() {
            console.log("Setting up system...");
            setupModalHandlers();
            loadAllData();
        }

        function loadAllData() {
            console.log("Loading all data...");
            loadMenus();
            loadTokens();
            loadSpecialTokens();
        }

        // ========== MENU FUNCTIONS ==========

        function loadMenus() {
            console.log("Loading menus...");
            $.ajax({
                url: 'api.php',
                type: 'POST',
                data: {
                    action: 'read_menus'
                },
                dataType: 'json'
            }).done(function(response) {
                console.log("✅ Menu Response:", response);
                if (response && response.success && Array.isArray(response.data)) {
                    allMenus = response.data;
                    console.log(`✅ Loaded ${allMenus.length} menus`);
                } else {
                    console.log("No menu data or error");
                    allMenus = [];
                }
                displayMenus();
            }).fail(function(xhr, status, error) {
                console.log("❌ Menu loading failed:", error);
                allMenus = [];
                displayMenus();
            });
        }

        function displayMenus() {
            console.log(`Displaying ${allMenus.length} menus`);
            const tableBody = $('#messMenuTable tbody');

            if (tableBody.length === 0) {
                console.log("❌ Menu table not found");
                return;
            }

            tableBody.empty();

            if (allMenus.length === 0) {
                tableBody.append('<tr><td colspan="6" class="text-center">No menu items found</td></tr>');
                return;
            }

            allMenus.forEach(function(menu) {
                const badge = getMealTypeBadge(menu.meal_type);
                const row = `
                <tr data-menu-id="${menu.menu_id}">
                    <td>${menu.date || 'N/A'}</td>
                    <td><span class="badge ${badge}">${menu.meal_type || 'N/A'}</span></td>
                    <td>${menu.items || 'N/A'}</td>
                    <td>${menu.category || 'N/A'}</td>
                    <td>₹${parseFloat(menu.fee || 0).toFixed(2)}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-warning" onclick="editMenu(${menu.menu_id})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger" onclick="deleteMenu(${menu.menu_id})" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
                tableBody.append(row);
            });

            console.log("✅ Menu table updated");
        }

        function saveMenuForm(mealType) {
            console.log(`Saving ${mealType} menu...`);

            const prefix = mealType.toLowerCase();
            const data = {
                action: 'create_menu',
                date: $(`#${prefix}Date`).val(),
                meal_type: mealType,
                items: $(`#${prefix}Items`).val(),
                category: $(`#${prefix}Category`).val(),
                fee: $(`#${prefix}Fee`).val()
            };

            console.log("Form data:", data);

            // Validation
            if (!data.date || !data.items || !data.fee) {
                showMessage('error', 'Please fill Date, Items, and Fee');
                return;
            }

            $.post('api.php', data, function(response) {
                console.log("Save response:", response);

                if (response && response.success) {
                    showMessage('success', response.message || 'Menu saved');
                    $(`#${prefix}Modal`).modal('hide');
                    clearForm(mealType);

                    // INSTANT UPDATE - Add new item to table
                    const newMenu = {
                        menu_id: response.id,
                        date: data.date,
                        meal_type: data.meal_type,
                        items: data.items,
                        category: data.category,
                        fee: data.fee
                    };

                    allMenus.unshift(newMenu);
                    displayMenus();
                    console.log("✅ Table updated instantly");

                } else {
                    showMessage('error', response.message || 'Save failed');
                }
            }, 'json').fail(function() {
                showMessage('error', 'Connection failed');
            });
        }

        function editMenu(menuId) {
            console.log("Edit menu:", menuId);

            const menu = allMenus.find(m => m.menu_id == menuId);
            if (!menu) {
                showMessage('error', 'Menu not found');
                return;
            }

            console.log("Found menu:", menu);

            // Populate edit form
            $('#editMenuId').val(menu.menu_id);
            $('#editMenuDate').val(menu.date);
            $('#editMenuMealType').val(menu.meal_type);
            $('#editMenuItems').val(menu.items);
            $('#editMenuCategory').val(menu.category || '');
            $('#editMenuFee').val(menu.fee);

            // Show edit modal
            $('#editMenuModal').modal('show');
        }

        function updateMenu() {
            console.log("Updating menu...");

            const data = {
                action: 'update_menu',
                menu_id: $('#editMenuId').val(),
                date: $('#editMenuDate').val(),
                meal_type: $('#editMenuMealType').val(),
                items: $('#editMenuItems').val(),
                category: $('#editMenuCategory').val(),
                fee: $('#editMenuFee').val()
            };

            console.log("Update data:", data);

            // Validation
            if (!data.menu_id || !data.date || !data.meal_type || !data.items || !data.fee) {
                showMessage('error', 'Please fill all required fields');
                return;
            }

            $.post('api.php', data, function(response) {
                console.log("Update response:", response);

                if (response && response.success) {
                    showMessage('success', response.message || 'Menu updated');
                    $('#editMenuModal').modal('hide');

                    // INSTANT UPDATE - Update item in array
                    const menuIndex = allMenus.findIndex(m => m.menu_id == data.menu_id);
                    if (menuIndex !== -1) {
                        allMenus[menuIndex] = {
                            menu_id: data.menu_id,
                            date: data.date,
                            meal_type: data.meal_type,
                            items: data.items,
                            category: data.category,
                            fee: data.fee
                        };
                    }

                    displayMenus();
                    console.log("✅ Menu updated instantly in table");

                } else {
                    showMessage('error', response.message || 'Update failed');
                }
            }, 'json').fail(function() {
                showMessage('error', 'Update connection failed');
            });
        }

        function deleteMenu(menuId) {
            console.log("Delete menu:", menuId);

            if (!confirm('Are you sure you want to delete this menu item?')) {
                return;
            }

            $.post('api.php', {
                action: 'delete_menu',
                menu_id: menuId
            }, function(response) {
                console.log("Delete response:", response);

                if (response && response.success) {
                    showMessage('success', response.message || 'Menu deleted');

                    // INSTANT UPDATE - Remove from array
                    allMenus = allMenus.filter(m => m.menu_id != menuId);
                    displayMenus();
                    console.log("✅ Menu deleted instantly from table");

                } else {
                    showMessage('error', response.message || 'Delete failed');
                }
            }, 'json').fail(function() {
                showMessage('error', 'Delete connection failed');
            });
        }

        // ========== SPECIAL TOKEN FUNCTIONS ==========


        // Load special tokens data from API and display in table
        function loadSpecialTokens() {
            console.log("Loading special tokens...");

            $.post('api.php', {
                action: 'read_special_tokens'
            }, function(response) {
                console.log("Special token response:", response);

                if (response && response.success && Array.isArray(response.data)) {
                    allSpecialTokens = response.data;
                } else {
                    allSpecialTokens = [];
                }

                displaySpecialTokens();

            }, 'json').fail(function() {
                console.log("Special token loading failed");
                allSpecialTokens = [];
                displaySpecialTokens();
            });
        }

        // Display special tokens in the table with Token Date column and Actions (Edit/Delete)
        function displaySpecialTokens() {
            console.log(`Displaying ${allSpecialTokens.length} special tokens`);
            const tableBody = $('#specialtokenEnableTable tbody');

            if (tableBody.length === 0) {
                console.log("Special token table not found");
                return;
            }

            tableBody.empty();

            if (allSpecialTokens.length === 0) {
                tableBody.append('<tr><td colspan="8" class="text-center">No special tokens found</td></tr>');
                return;
            }

            allSpecialTokens.forEach(function(token) {
                const row = `
        <tr data-menu-id="${token.menu_id}">
            <td>${token.from_date || 'N/A'}</td>
            <td>${token.from_time || 'N/A'}</td>
            <td>${token.to_date || 'N/A'}</td>
            <td>${token.to_time || 'N/A'}</td>
            <td>${token.token_date || 'N/A'}</td>
            <td>${token.meal_type || 'N/A'}</td>
            <td>${token.menu_items || 'N/A'}</td>
            <td>₹${parseFloat(token.fee || 0).toFixed(2)}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-warning" onclick="editSpecialToken(${token.menu_id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger" onclick="deleteSpecialToken(${token.menu_id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
        `;
                tableBody.append(row);
            });

            console.log("✅ Special token table updated");
        }

        // Save new special token from modal form (including Token Date)
        function saveSpecialToken() {
            console.log("=== SAVING SPECIAL TOKEN ===");

            const fromDate = $('#tokenfromDate').val();
            const fromTime = $('#tokenfromTime').val();
            const toDate = $('#tokentoDate').val();
            const toTime = $('#tokentoTime').val();
            const tokenDate = $('#tokenDate').val(); // Token Date field
            const mealType = $('#mealType').val();
            const menuItems = $('#specialMenuItems').val();
            const fee = $('#specialtokenFee').val();

            console.log("Form field values:");
            console.log("- From Date:", fromDate);
            console.log("- From Time:", fromTime);
            console.log("- To Date:", toDate);
            console.log("- To Time:", toTime);
            console.log("- Token Date:", tokenDate);
            console.log("- Meal Type:",mealType);
            console.log("- Menu Items:", menuItems);
            console.log("- Fee:", fee);

            const data = {
                action: 'create_special_token',
                from_date: fromDate,
                from_time: fromTime,
                to_date: toDate,
                to_time: toTime,
                token_date: tokenDate,
                meal_type: mealType,
                menu_items: menuItems,
                fee: fee
            };

            // Validation
            const missing = [];
            if (!data.from_date) missing.push('From Date');
            if (!data.from_time) missing.push('From Time');
            if (!data.to_date) missing.push('To Date');
            if (!data.to_time) missing.push('To Time');
            if (!data.token_date) missing.push('Token Date');
            if (!data.meal_type) missing.push('Meal Type');
            if (!data.menu_items) missing.push('Menu Items');
            if (!data.fee) missing.push('Fee');

            if (missing.length > 0) {
                console.log("❌ Missing fields:", missing);
                showMessage('error', `Please fill: ${missing.join(', ')}`);
                return;
            }

            console.log("✅ Validation passed. Sending data:", data);

            $.post('api.php', data, function(response) {
                console.log("Special token response:", response);

                if (response && response.success) {
                    showMessage('success', response.message || 'Special token saved');
                    $('#specialtokenModal').modal('hide');
                    clearSpecialTokenForm();

                    // Instant update: prepend new record and refresh table
                    const newToken = {
                        menu_id: response.id,
                        from_date: data.from_date,
                        from_time: data.from_time,
                        to_date: data.to_date,
                        to_time: data.to_time,
                        token_date: data.token_date,
                        meal_type : data.meal_type,
                        menu_items: data.menu_items,
                        fee: data.fee
                    };

                    allSpecialTokens.unshift(newToken);
                    displaySpecialTokens();
                    console.log("✅ Special token table updated instantly");

                } else {
                    showMessage('error', response.message || 'Save failed');
                }
            }, 'json').fail(function(xhr, status, error) {
                console.log("❌ Special token save failed:", error);
                showMessage('error', 'Connection failed');
            });
        }

        function clearSpecialTokenForm() {
            $('#tokenfromDate, #tokenfromTime, #tokentoDate, #tokentoTime, #tokenDate,#mealType #specialMenuItems, #specialtokenFee').val('');
        }

        // Edit special token modal pop-up with current data (including Token Date)
        function editSpecialToken(menuId) {
            const token = allSpecialTokens.find(t => t.menu_id == menuId);
            if (!token) {
                showMessage('error', 'Special Token not found');
                return;
            }

            $('#editSpecialMenuId').val(token.menu_id);
            $('#editSpecialFromDate').val(token.from_date);
            $('#editSpecialFromTime').val(token.from_time);
            $('#editSpecialToDate').val(token.to_date);
            $('#editSpecialToTime').val(token.to_time);
            $('#editSpecialTokenDate').val(token.token_date);
            $('editSpecialMealType').val(token.meal_type);
            $('#editSpecialMenuItems').val(token.menu_items);
            $('#editSpecialFee').val(token.fee);

            $('#editSpecialTokenModal').modal('show');
        }

        // Update special token after editing (including Token Date)
        function updateSpecialToken() {
            const data = {
                action: 'update_special_token',
                menu_id: $('#editSpecialMenuId').val(),
                from_date: $('#editSpecialFromDate').val(),
                from_time: $('#editSpecialFromTime').val(),
                to_date: $('#editSpecialToDate').val(),
                to_time: $('#editSpecialToTime').val(),
                token_date: $('#editSpecialTokenDate').val(),
                meal_type: $('#editSpecialMealType').val(),
                menu_items: $('#editSpecialMenuItems').val(),
                fee: $('#editSpecialFee').val()
            };

            // Optional: add validation before sending

            $.post('api.php', data, function(response) {
                if (response && response.success) {
                    showMessage('success', response.message || 'Special token updated');
                    $('#editSpecialTokenModal').modal('hide');
                    loadSpecialTokens();
                } else {
                    showMessage('error', response.message || 'Update failed');
                }
            }, 'json').fail(function() {
                showMessage('error', 'Connection failed');
            });
        }

        // Delete special token by id
        function deleteSpecialToken(menuId) {
            if (!confirm('Are you sure you want to delete this special token?')) return;

            $.post('api.php', {
                action: 'delete_special_token',
                menu_id: menuId
            }, function(response) {
                if (response && response.success) {
                    showMessage('success', response.message || 'Special token deleted');
                    loadSpecialTokens();
                } else {
                    showMessage('error', response.message || 'Delete failed');
                }
            }, 'json').fail(function() {
                showMessage('error', 'Connection failed');
            });
        }

        // Expose to global scope
        window.loadSpecialTokens = loadSpecialTokens;
        window.displaySpecialTokens = displaySpecialTokens;
        window.saveSpecialToken = saveSpecialToken;
        window.clearSpecialTokenForm = clearSpecialTokenForm;
        window.editSpecialToken = editSpecialToken;
        window.updateSpecialToken = updateSpecialToken;
        window.deleteSpecialToken = deleteSpecialToken;


        // ========== TOKEN FUNCTIONS ==========

        function loadTokens() {
            console.log("Loading tokens...");

            $.post('api.php', {
                action: 'read_tokens'
            }, function(response) {
                console.log("Token response:", response);

                if (response && response.success && Array.isArray(response.data)) {
                    allTokens = response.data;
                } else {
                    allTokens = [];
                }

                displayTokens();

            }, 'json').fail(function() {
                console.log("Token loading failed");
                allTokens = [];
                displayTokens();
            });
        }

        function displayTokens() {
            console.log(`Displaying ${allTokens.length} tokens`);

            const tableBody = $('#messTokensTable tbody');

            if (tableBody.length === 0) {
                console.log("Token table not found");
                return;
            }

            tableBody.empty();

            if (allTokens.length === 0) {
                tableBody.append('<tr><td colspan="7" class="text-center">No tokens found</td></tr>');
                return;
            }

            allTokens.forEach(function(token) {
                const row = `
                <tr>
                    <td>Student ${token.student_roll_number || 'N/A'}</td>
                    <td>${token.student_roll_number || 'N/A'}</td>
                    <td><span class="badge bg-info">${token.meal_type || 'N/A'}</span></td>
                    <td>${token.date || 'N/A'}</td>
                    <td><span class="badge bg-success">${token.token_type || 'N/A'}</span></td>
                    <td>₹${parseFloat(token.special_fee || 0).toFixed(2)}</td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="deleteToken(${token.token_id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
                tableBody.append(row);
            });

            console.log("✅ Token table updated");
        }

        function deleteToken(tokenId) {
            console.log("Delete token:", tokenId);

            if (!confirm('Delete this token?')) {
                return;
            }

            $.post('api.php', {
                action: 'delete_token',
                token_id: tokenId
            }, function(response) {
                if (response && response.success) {
                    showMessage('success', 'Token deleted');
                    allTokens = allTokens.filter(t => t.token_id != tokenId);
                    displayTokens();
                } else {
                    showMessage('error', 'Delete failed');
                }
            }, 'json');
        }

        // ========== MODAL HANDLERS ==========

        function setupModalHandlers() {
            console.log("Setting up handlers...");

            // Breakfast
            $(document).on('click', 'button[data-save-form="breakfastMenuForm"]', function() {
                console.log("Breakfast save clicked");
                saveMenuForm('Breakfast');
            });

            // Lunch
            $(document).on('click', 'button[data-save-form="lunchMenuForm"]', function() {
                console.log("Lunch save clicked");
                saveMenuForm('Lunch');
            });

            // Snacks
            $('#snacksModal .btn-primary').click(function() {
                console.log("Snacks save clicked");
                saveMenuForm('Snacks');
            });

            // Dinner
            $('#dinnerModal .btn-primary').click(function() {
                console.log("Dinner save clicked");
                saveMenuForm('Dinner');
            });

            // Special Token
            $('#specialtokenModal .btn-primary').click(function() {
                console.log("Special token save clicked");
                saveSpecialToken();
            });

            // Global functions
            window.editMenu = editMenu;
            window.updateMenu = updateMenu;
            window.deleteMenu = deleteMenu;
            window.deleteToken = deleteToken;

            console.log("✅ Handlers setup complete");
        }

        // ========== UTILITY FUNCTIONS ==========

        function clearForm(mealType) {
            const prefix = mealType.toLowerCase();
            $(`#${prefix}Date`).val('');
            $(`#${prefix}Items`).val('');
            $(`#${prefix}Category`).val('');
            $(`#${prefix}Fee`).val('');
        }

        function clearSpecialTokenForm() {
            $('#tokenfromDate').val('');
            $('#tokenfromTime').val('');
            $('#tokentoDate').val('');
            $('#tokentoTime').val('');
            $('#tokenDate').val('');
            $('#specialMenuItems').val(''); // FIXED: Clear correct field
            $('#specialtokenFee').val('');
        }

        function getMealTypeBadge(mealType) {
            switch (mealType) {
                case 'Breakfast':
                    return 'bg-warning text-dark';
                case 'Lunch':
                    return 'bg-success';
                case 'Snacks':
                    return 'bg-info text-dark';
                case 'Dinner':
                    return 'bg-primary';
                default:
                    return 'bg-secondary';
            }
        }

        function showMessage(type, message) {
            console.log(`${type.toUpperCase()}: ${message}`);

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: type === 'success' ? 'success' : 'error',
                    title: type.charAt(0).toUpperCase() + type.slice(1),
                    text: message,
                    timer: type === 'success' ? 1500 : 0,
                    showConfirmButton: type !== 'success'
                });
            } else {
                alert(`${type.toUpperCase()}: ${message}`);
            }
        }

        // Manual refresh function (if needed)
        window.refreshData = function() {
            console.log("Manual refresh triggered");
            loadAllData();
        };
        
        // Initialize DataTables
        $(document).ready(function() {
            if ($('#messMenuTable').length > 0) {
                $('#messMenuTable').DataTable();
            }
            if ($('#specialtokenEnableTable').length > 0) {
                $('#specialtokenEnableTable').DataTable();
            }
            if ($('#messTokensTable').length > 0) {
                $('#messTokensTable').DataTable();
            }
        });
    </script>
</body>
</html>
