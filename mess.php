<?php
include 'db.php';
session_start();

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Get student roll number from session
$roll_number = $_SESSION['student_roll_number'] ?? '927623bit027';
$now = date('Y-m-d H:i:s');
$today = date('Y-m-d');

// ---------- FETCH TODAY'S MESS MENU ----------
$stmt_menu_today = $conn->prepare("
    SELECT menu_id, meal_type, items, fee, category
    FROM mess_menu
    WHERE date = ?
    ORDER BY FIELD(category, 'Regular', 'Special'),
             FIELD(meal_type, 'Breakfast', 'Lunch', 'Snacks', 'Dinner')
");
$stmt_menu_today->bind_param("s", $today);
$stmt_menu_today->execute();
$today_menu = $stmt_menu_today->get_result();

// ---------- HANDLE SPECIAL TOKEN REQUEST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['menu_id'])) {
    $menu_id = intval($_POST['menu_id']);

    // Check if already requested
    $check_stmt = $conn->prepare("
        SELECT token_id 
        FROM mess_tokens 
        WHERE roll_number = ? 
          AND menu_id = ? 
          AND token_type = 'Special'
    ");
    $check_stmt->bind_param('si', $roll_number, $menu_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows == 0) {
        // Fetch special meal details
        $stmt_menu = $conn->prepare("
            SELECT menu_items, fee, from_date, from_time, to_date, to_time, meal_type, token_date
            FROM specialtokenenable 
            WHERE menu_id = ?
        ");
        $stmt_menu->bind_param('i', $menu_id);
        $stmt_menu->execute();
        $menu = $stmt_menu->get_result()->fetch_assoc();

        if ($menu) {
            $from_datetime = $menu['from_date'] . ' ' . $menu['from_time'];
            $to_datetime   = $menu['to_date'] . ' ' . $menu['to_time'];
            $special_fee   = $menu['fee'];

            // ---------- INSERT INTO MESS_TOKENS ----------
            $stmt_insert = $conn->prepare("
                INSERT INTO mess_tokens 
                (roll_number, menu_id, meal_type, menu, token_type, token_date, special_fee, created_at)
                VALUES (?, ?, ?, ?, 'Special', ?, ?, NOW())
            ");
            $stmt_insert->bind_param(
                "sisssd", 
                $roll_number, 
                $menu_id, 
                $menu['meal_type'], 
                $menu['menu_items'], 
                $menu['token_date'], 
                $special_fee
            );
            $stmt_insert->execute();
        }
    }

    // Redirect and stay on Special Meals tab
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=special-meals");
    exit();
}

// ---------- FETCH CURRENTLY ACTIVE SPECIAL MEALS ONLY ----------
// ---------- FETCH CURRENTLY ACTIVE SPECIAL MEALS ONLY ----------
$sql_special_enabled = "
    SELECT 
        s.menu_id,
        s.menu_items,
        s.fee,
        s.from_date,
        s.from_time,
        s.to_date,
        s.to_time,
        s.token_date,
        s.meal_type,
        t.token_id AS requested_token
    FROM specialtokenenable s
    LEFT JOIN mess_tokens t 
        ON s.menu_id = t.menu_id
        AND t.roll_number = ?
        AND t.token_type = 'Special'
    WHERE STR_TO_DATE(CONCAT(s.from_date, ' ', s.from_time), '%Y-%m-%d %H:%i:%s') <= ?
      AND STR_TO_DATE(CONCAT(s.to_date, ' ', s.to_time), '%Y-%m-%d %H:%i:%s') >= ?
    ORDER BY s.from_date, s.from_time
";

$stmt_special_enabled = $conn->prepare($sql_special_enabled);
$stmt_special_enabled->bind_param('sss', $roll_number, $now, $now);
$stmt_special_enabled->execute();
$special_menus_enabled = $stmt_special_enabled->get_result();

// ---------- FETCH SPECIAL TOKEN HISTORY ----------
$sql_history = "
    SELECT 
        token_date,
        meal_type,
        menu AS menu_items,
        special_fee,
        created_at
    FROM mess_tokens
    WHERE roll_number = ?
      AND token_type = 'Special'
    ORDER BY created_at DESC
";

$stmt_history = $conn->prepare($sql_history);
$stmt_history->bind_param('s', $roll_number);
$stmt_history->execute();
$special_token_history = $stmt_history->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Mess Menu & Special Meals</title>
<link rel="icon" type="image/png" href="image/icons/mkce_s.png" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet" />
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
<style>
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --topbar-height: 60px;
            --footer-height: 40px;
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
        .nav-pills .nav-link {
    border-radius: 50px;
    padding: 10px 20px;
    margin-right: 8px;
    transition: 0.3s;
    color: #555;
}
.nav-pills .nav-link.active {
    background: linear-gradient(135deg, #4e73df, #1cc88a);
    color: white;
    box-shadow: 0 4px 6px rgba(0,0,0,0.15);
}
.nav-pills .nav-link i {
    font-size: 1em;
}
.nav-pills .nav-link:hover {
    background: rgba(78,115,223,0.2);
    color: #4e73df;
}
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="content">
<?php include 'topbar.php'; ?>

<div class="container-fluid mt-4">
    <!-- Tabs -->
    <ul class="nav nav-pills mb-3" id="menuTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="today-menu-tab" data-bs-toggle="pill" data-bs-target="#today-menu">Today's Menu</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="special-meals-tab" data-bs-toggle="pill" data-bs-target="#special-meals">Special Meals</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="monthly-bill-tab" data-bs-toggle="pill" data-bs-target="#monthly-bill">Monthly Bill</button>
        </li>
    </ul>

   <div class="tab-content" id="menuTabsContent">

   <!-- ✅ TODAY'S MENU TAB -->
<div class="tab-pane fade show active" id="today-menu" role="tabpanel">
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white text-dark">
            <h5 class="mb-0">Mess Menu for <?= date('d M Y') ?></h5>
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered">
                <thead class="gradient-header">
                    <tr>
                        <th>Meal Type</th>
                        <th>Items</th>
                        <th>Category</th>
                        <th>Fee (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($today_menu->num_rows == 0): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No menu available for today.</td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = $today_menu->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['meal_type']) ?></td>
                                <td><?= nl2br(htmlspecialchars($row['items'])) ?></td>
                                <td><?= htmlspecialchars($row['category'] ?? '—') ?></td>
                                <td><?= number_format($row['fee'], 2) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

  <!-- ✅ SPECIAL MEALS TAB -->
<div class="tab-pane fade" id="special-meals" role="tabpanel">
    <!-- ✅ Available Special Meals -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white text-dark">
            <h5 class="mb-0">Available Special Meals</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered">
                <thead class="gradient-header">
                    <tr>
                        <th>Special Food Date</th>
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
                    <?php
                    // ✅ Fetch all enabled special meals
                    $special_menus_enabled = $conn->query("
                        SELECT menu_id, token_date, from_date, from_time, to_date, to_time, meal_type, menu_items, fee 
                        FROM specialtokenenable
                        ORDER BY token_date ASC
                    ");

                    if ($special_menus_enabled->num_rows == 0): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No special meals available.</td>
                        </tr>
                    <?php else:
                        // ✅ Get logged-in student's roll number
                        $student_roll_number = $_SESSION['student_roll_number'] ?? '';

                        // ✅ Get student’s requested tokens
                        $requested_ids = [];
                        $req_sql = $conn->prepare("
                            SELECT menu_id, token_id 
                            FROM mess_tokens 
                            WHERE roll_number = ? 
                              AND token_type = 'Special'
                        ");
                        $req_sql->bind_param("s", $student_roll_number);
                        $req_sql->execute();
                        $req_res = $req_sql->get_result();
                        while ($r = $req_res->fetch_assoc()) {
                            $requested_ids[$r['menu_id']] = $r['token_id'];
                        }

                        // ✅ Display special meals
                        while ($row = $special_menus_enabled->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($row['token_date']) ?></td>
                            <td><?= htmlspecialchars($row['from_date']) ?></td>
                            <td><?= htmlspecialchars(substr($row['from_time'], 0, 5)) ?></td>
                            <td><?= htmlspecialchars($row['to_date']) ?></td>
                            <td><?= htmlspecialchars(substr($row['to_time'], 0, 5)) ?></td>
                            <td><?= htmlspecialchars($row['meal_type']) ?></td>
                            <td><?= nl2br(htmlspecialchars($row['menu_items'])) ?></td>
                            <td><?= number_format($row['fee'], 2) ?></td>
                            <td class="text-center">
                                <?php if (isset($requested_ids[$row['menu_id']])): 
                                    $tokenId = $requested_ids[$row['menu_id']];
                                ?>
                                    <a href="generate_token_pdf.php?token_id=<?= $tokenId ?>" 
                                       class="btn btn-outline-primary btn-sm" download>
                                        Download
                                    </a>
                                <?php else: ?>
                                    <form class="request-form d-inline" method="POST" action="request_special_token.php">
                                        <input type="hidden" name="menu_id" value="<?= $row['menu_id'] ?>">
                                        <button type="submit" 
                                                class="btn btn-success btn-sm request-btn" 
                                                data-menu-id="<?= $row['menu_id'] ?>">
                                            Request
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ✅ My Special Token History -->
    <div class="card mb-4 shadow-sm mt-4">
        <div class="card-header bg-white text-dark">
            <h5 class="mb-0">My Special Token History</h5>
        </div>
        <div class="card-body">
            <table id="historyTable" class="table table-striped table-bordered">
                <thead class="gradient-header">
                    <tr>
                        <th>Special Food Date</th>
                        <th>Meal Type</th>
                        <th>Items</th>
                        <th>Fee (₹)</th>
                        <th>Requested At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // ✅ Fetch user’s token history
                    $history_sql = $conn->prepare("
                        SELECT 
                            t.token_date, 
                            t.meal_type, 
                            s.menu_items, 
                            t.special_fee, 
                            t.created_at 
                        FROM mess_tokens t
                        JOIN specialtokenenable s ON t.menu_id = s.menu_id
                        WHERE t.roll_number = ?
                          AND t.token_type = 'Special'
                        ORDER BY t.created_at DESC
                    ");
                    $history_sql->bind_param("s", $student_roll_number);
                    $history_sql->execute();
                    $special_token_history = $history_sql->get_result();

                    if ($special_token_history->num_rows == 0): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No token history found.</td>
                        </tr>
                    <?php else: while ($row = $special_token_history->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['token_date']) ?></td>
                            <td><?= htmlspecialchars($row['meal_type']) ?></td>
                            <td><?= nl2br(htmlspecialchars($row['menu_items'])) ?></td>
                            <td><?= number_format($row['special_fee'], 2) ?></td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                        </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- ✅ MONTHLY BILL TAB -->
<div class="tab-pane fade" id="monthly-bill" role="tabpanel">
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white text-dark d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Monthly Mess Bill Summary</h5>
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-secondary btn-sm" id="prevMonth">&lt;</button>
                <select id="monthSelect" class="form-select form-select-sm mx-1"></select>
                <select id="yearSelect" class="form-select form-select-sm mx-1"></select>
                <button class="btn btn-outline-secondary btn-sm" id="nextMonth">&gt;</button>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered">
                <thead class="gradient-header">
                    <tr>
                        <th>Month</th>
                        <th>Amount (₹)</th>
                        <th>Generated At</th>
                    </tr>
                </thead>
                <tbody id="monthlyBillBody">
                    <!-- ✅ Loaded dynamically via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>


</div>


<?php include 'footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTables
    var historyTable = $('#specialHistoryTable').DataTable();
    $('#historyTable').DataTable();

    // Month/Year selectors
    const monthSelect = document.getElementById('monthSelect');
    const yearSelect = document.getElementById('yearSelect');
    const monthlyBillBody = document.getElementById('monthlyBillBody');

    const monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
    monthNames.forEach((m,i)=>{
        let o = document.createElement('option'); 
        o.value = i+1; 
        o.text = m; 
        monthSelect.add(o); 
    });

    const currentYear = new Date().getFullYear();
    for(let y=currentYear; y>currentYear-20; y--){
        let o = document.createElement('option'); 
        o.value = y; 
        o.text = y; 
        yearSelect.add(o);
    }

    monthSelect.value = new Date().getMonth()+1;
    yearSelect.value = currentYear;

    loadBills(monthSelect.value, yearSelect.value);

    document.getElementById('prevMonth').addEventListener('click', ()=>changeMonth(-1));
    document.getElementById('nextMonth').addEventListener('click', ()=>changeMonth(1));
    monthSelect.addEventListener('change', ()=>loadBills(monthSelect.value, yearSelect.value));
    yearSelect.addEventListener('change', ()=>loadBills(monthSelect.value, yearSelect.value));

    function changeMonth(delta){
        let month = parseInt(monthSelect.value);
        let year = parseInt(yearSelect.value);
        month += delta;
        if(month < 1){ month = 12; year -= 1; }
        if(month > 12){ month = 1; year += 1; }
        if(year > currentYear) year = currentYear;
        if(year <= currentYear-20) year = currentYear-19;
        monthSelect.value = month;
        yearSelect.value = year;
        loadBills(month, year);
    }

    function loadBills(month, year){
        fetch(`fetch_monthly_bill.php?month=${month}&year=${year}`)
            .then(res => res.text())
            .then(data => monthlyBillBody.innerHTML = data)
            .finally(() => attachRequestHandlers()); // Attach handlers after content loads
    }

    function attachRequestHandlers(){
        document.querySelectorAll('.request-btn').forEach(function(button){
            // Avoid adding multiple listeners
            if(button.getAttribute('data-bound') === 'true') return;
            button.setAttribute('data-bound', 'true');

            button.addEventListener('click', function(e){
                e.preventDefault();
                var menuId = this.getAttribute('data-menu-id');
                var btn = this;

                fetch('request_special_token.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'menu_id=' + menuId
                })
                .then(res => res.json())
                .then(response => {
                    if(response.token_id){
                        // 1️⃣ Replace Request button with Download link
                        btn.outerHTML = `<a href="generate_token_pdf.php?token_id=${response.token_id}" class="btn btn-outline-primary btn-sm" download>Download</a>`;

                        // 2️⃣ Add new row to Special Token History table immediately
                        historyTable.row.add([
                            response.token_date,
                            response.meal_type,
                            response.menu_items,
                            parseFloat(response.special_fee).toFixed(2),
                            response.created_at
                        ]).draw(false);
                    } else {
                        alert('Failed to request special token.');
                    }
                })
                .catch(err => {
                    console.error('Error requesting token:', err);
                    alert('Failed to request special token.');
                });
            });
        });
    }

    // Initial attachment for any pre-existing request buttons
    attachRequestHandlers();
});

</script>


</body>
</html>
