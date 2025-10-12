<?php
include 'db.php';
session_start();

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Get student roll number from session
$roll_number = $_SESSION['student_roll_number'] ?? '22XX001';
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

    if ($check_result->num_rows === 0) {
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

// ---------- FETCH CURRENTLY ACTIVE SPECIAL MEALS ----------
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
    WHERE ? BETWEEN CONCAT(s.from_date, ' ', s.from_time)
                AND CONCAT(s.to_date, ' ', s.to_time)
    ORDER BY s.from_date, s.from_time
";

$stmt_special_enabled = $conn->prepare($sql_special_enabled);
$stmt_special_enabled->bind_param('ss', $roll_number, $now);
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
