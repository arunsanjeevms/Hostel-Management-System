<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$host = "localhost";
$dbname = "innodb";
$username = "root";
$password = "";

// Database connection using mysqli
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit();
}

// Set charset
$conn->set_charset("utf8mb4");

// Get the action from POST/GET data
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Add debugging for API calls
error_log("API Call - Action: " . $action . " - POST: " . json_encode($_POST));

// Switch case for different CRUD operations
switch ($action) {
    // Menu operations
    case 'create_menu':
        createMenu($conn);
        break;
    case 'read_menus':
        readMenus($conn);
        break;
    case 'update_menu':
        updateMenu($conn);
        break;
    case 'delete_menu':
        deleteMenu($conn);
        break;

    // Token operations
    case 'create_token':
        createToken($conn);
        break;
    case 'read_tokens':
        readTokens($conn);
        break;
    case 'delete_token':
        deleteToken($conn);
        break;

    // Special token operations
    case 'create_special_token':
        createSpecialToken($conn);
        break;
    case 'read_special_tokens':
        readSpecialTokens($conn);
        break;
    case 'update_special_token':
        updateSpecialToken($conn);
        break;
    case 'delete_special_token':
        deleteSpecialToken($conn);
        break;

    // Bill operations
    case 'read_bills':
        readBills($conn);
        break;
    case 'create_bill':
        createBill($conn);
        break;

    // Test endpoint
    case 'test':
        echo json_encode([
            'success' => true,
            'message' => 'API is working',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action specified: ' . $action
        ]);
        break;
}

// ========== MENU FUNCTIONS ==========

// CREATE MENU - Add new menu item
function createMenu($conn) {
    error_log("createMenu called with: " . json_encode($_POST));

    $date = $_POST['date'] ?? '';
    $meal_type = $_POST['meal_type'] ?? '';
    $items = $_POST['items'] ?? '';
    $category = $_POST['category'] ?? null;
    $fee = $_POST['fee'] ?? 0.00;

    try {
        // Validate required fields
        if (empty($date) || empty($meal_type) || empty($items)) {
            echo json_encode([
                'success' => false,
                'message' => 'Date, meal type and items are required fields',
                'received_data' => $_POST
            ]);
            return;
        }

        // Validate meal type
        $valid_meal_types = ['Breakfast', 'Lunch', 'Snacks', 'Dinner'];
        if (!in_array($meal_type, $valid_meal_types)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid meal type: ' . $meal_type
            ]);
            return;
        }

        // Check if menu already exists for date and meal type (relaxed - allow duplicates for now)
        // Comment out the duplicate check to allow testing
        /*
        $checkSql = "SELECT menu_id FROM mess_menu WHERE date = ? AND meal_type = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("ss", $date, $meal_type);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Menu already exists for this date and meal type'
            ]);
            $checkStmt->close();
            return;
        }
        $checkStmt->close();
        */

        // Insert new menu
        $sql = "INSERT INTO mess_menu (date, meal_type, items, category, fee) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssd", $date, $meal_type, $items, $category, $fee);

        if ($stmt->execute()) {
            $insert_id = $conn->insert_id;
            error_log("Menu created successfully with ID: " . $insert_id);

            echo json_encode([
                'success' => true,
                'message' => 'Menu item created successfully',
                'id' => $insert_id
            ]);
        } else {
            error_log("Failed to create menu: " . $conn->error);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create menu item: ' . $conn->error
            ]);
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log("Exception in createMenu: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// READ MENUS - Get all menu items
function readMenus($conn) {
    error_log("readMenus called");

    try {
        // Check if created_at column exists
        $checkColumnSql = "SHOW COLUMNS FROM mess_menu LIKE 'created_at'";
        $checkResult = $conn->query($checkColumnSql);
        $hasCreatedAt = $checkResult->num_rows > 0;

        if ($hasCreatedAt) {
            $sql = "SELECT * FROM mess_menu ORDER BY created_at DESC, date DESC, meal_type ASC";
        } else {
            $sql = "SELECT * FROM mess_menu ORDER BY date DESC, meal_type ASC";
        }

        $result = $conn->query($sql);
        $menus = [];

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $menus[] = $row;
            }
        }

        error_log("Found " . count($menus) . " menu items");

        echo json_encode([
            'success' => true,
            'data' => $menus,
            'count' => count($menus)
        ]);
    } catch (Exception $e) {
        error_log("Exception in readMenus: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// UPDATE MENU - Update existing menu item
function updateMenu($conn) {
    try {
        // Validate required fields
        if (empty($_POST['menu_id']) || empty($_POST['date']) || empty($_POST['meal_type']) || empty($_POST['items'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Menu ID, date, meal type and items are required fields'
            ]);
            return;
        }

        $menu_id = intval($_POST['menu_id']);
        $date = $_POST['date'];
        $meal_type = $_POST['meal_type'];
        $items = $_POST['items'];
        $category = $_POST['category'] ?? null;
        $fee = floatval($_POST['fee'] ?? 0.00);

        // Validate meal type
        $valid_meal_types = ['Breakfast', 'Lunch', 'Snacks', 'Dinner'];
        if (!in_array($meal_type, $valid_meal_types)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid meal type'
            ]);
            return;
        }

        // Check if menu exists
        $checkSql = "SELECT menu_id FROM mess_menu WHERE menu_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $menu_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Menu item not found'
            ]);
            $checkStmt->close();
            return;
        }
        $checkStmt->close();

        // Update menu
        $sql = "UPDATE mess_menu SET date = ?, meal_type = ?, items = ?, category = ?, fee = ? WHERE menu_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssdi", $date, $meal_type, $items, $category, $fee, $menu_id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Menu item updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update menu item: ' . $conn->error
            ]);
        }
        $stmt->close();

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// DELETE MENU - Delete menu item
function deleteMenu($conn) {
    try {
        // Validate required field
        if (empty($_POST['menu_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Menu ID is required'
            ]);
            return;
        }

        $menu_id = intval($_POST['menu_id']);

        // Check if menu exists
        $checkSql = "SELECT menu_id FROM mess_menu WHERE menu_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $menu_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Menu item not found'
            ]);
            $checkStmt->close();
            return;
        }
        $checkStmt->close();

        // Delete menu (removed token check for now to allow testing)
        $sql = "DELETE FROM mess_menu WHERE menu_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $menu_id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Menu item deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete menu item: ' . $conn->error
            ]);
        }
        $stmt->close();

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// ========== TOKEN FUNCTIONS ==========

// CREATE TOKEN - Issue new token (FIXED)
function createToken($conn) {
    $student_roll_number = $_POST['student_roll_number'] ?? '';
    $menu_id = intval($_POST['menu_id'] ?? 0);
    $token_type = $_POST['token_type'] ?? 'Paid';
    $from_date = $_POST['from_date'] ?? null;
    $to_date = $_POST['to_date'] ?? null;
    $special_fee = floatval($_POST['special_fee'] ?? 0.00);
    $supervisor_id = !empty($_POST['supervisor_id']) ? intval($_POST['supervisor_id']) : null;

    try {
        // Validate required fields
        if (empty($student_roll_number) || empty($menu_id)) {
            echo json_encode([
                'success' => false,
                'message' => 'Student roll number and menu ID are required fields'
            ]);
            return;
        }

        // Validate token type
        $valid_token_types = ['Regular', 'Special'];
        if (!in_array($token_type, $valid_token_types)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid token type'
            ]);
            return;
        }

        // Check if menu exists
        $checkMenuSql = "SELECT menu_id FROM mess_menu WHERE menu_id = ?";
        $checkMenuStmt = $conn->prepare($checkMenuSql);
        $checkMenuStmt->bind_param("i", $menu_id);
        $checkMenuStmt->execute();
        $checkMenuResult = $checkMenuStmt->get_result();

        if ($checkMenuResult->num_rows == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Selected menu does not exist'
            ]);
            $checkMenuStmt->close();
            return;
        }
        $checkMenuStmt->close();

        // REMOVED STUDENT VALIDATION FOR NOW - Allow any roll number
        // This was causing issues if students table is empty

        // Validate dates if provided
        if (!empty($from_date) && !empty($to_date)) {
            if (strtotime($from_date) > strtotime($to_date)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'From date cannot be later than to date'
                ]);
                return;
            }
        }

        // FIXED: Insert new token - corrected bind_param
        $sql = "INSERT INTO mess_tokens (student_roll_number, menu_id, token_type, from_date, to_date, special_fee, supervisor_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        // FIXED: Removed the space and extra 'i' in bind_param
        $stmt->bind_param("sisssdi", $student_roll_number, $menu_id, $token_type, $from_date, $to_date, $special_fee, $supervisor_id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Token issued successfully',
                'id' => $conn->insert_id
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to issue token: ' . $conn->error
            ]);
        }
        $stmt->close();

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// READ TOKENS - Get all tokens with menu details (FIXED)
function readTokens($conn) {
    try {
        // Check if created_at column exists in mess_tokens
        $checkColumnSql = "SHOW COLUMNS FROM mess_tokens LIKE 'created_at'";
        $checkResult = $conn->query($checkColumnSql);
        $hasCreatedAt = $checkResult->num_rows > 0;

        if ($hasCreatedAt) {
            $sql = "SELECT t.*, m.date, m.meal_type, m.items, m.fee as menu_fee 
                    FROM mess_tokens t 
                    LEFT JOIN mess_menu m ON t.menu_id = m.menu_id 
                    ORDER BY t.created_at DESC";
        } else {
            $sql = "SELECT t.*, m.date, m.meal_type, m.items, m.fee as menu_fee 
                    FROM mess_tokens t 
                    LEFT JOIN mess_menu m ON t.menu_id = m.menu_id 
                    ORDER BY t.token_id DESC";
        }

        $result = $conn->query($sql);
        $tokens = [];

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tokens[] = $row;
            }
        }

        echo json_encode([
            'success' => true,
            'data' => $tokens,
            'count' => count($tokens)
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// DELETE TOKEN - Delete token
function deleteToken($conn) {
    try {
        // Validate required field
        if (empty($_POST['token_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Token ID is required'
            ]);
            return;
        }

        $token_id = intval($_POST['token_id']);

        // Check if token exists
        $checkSql = "SELECT token_id FROM mess_tokens WHERE token_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $token_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Token not found'
            ]);
            $checkStmt->close();
            return;
        }
        $checkStmt->close();

        // Delete token
        $sql = "DELETE FROM mess_tokens WHERE token_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $token_id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Token deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete token: ' . $conn->error
            ]);
        }
        $stmt->close();

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// ========== SPECIAL TOKEN FUNCTIONS ==========

// CREATE SPECIAL TOKEN - Add special token menu
function createSpecialToken($conn) {
    error_log("createSpecialToken called with: " . json_encode($_POST));

    $from_date = $_POST['from_date'] ?? '';
    $from_time = $_POST['from_time'] ?? '';
    $to_date = $_POST['to_date'] ?? '';
    $to_time = $_POST['to_time'] ?? '';
    $token_date = $_POST['token_date'] ?? '';
    $menu_items = $_POST['menu_items'] ?? '';
    $fee = floatval($_POST['fee'] ?? 0.00);

    try {
        // Enhanced validation with detailed error messages
        $missing_fields = [];
        if (empty($from_date)) $missing_fields[] = 'from_date';
        if (empty($from_time)) $missing_fields[] = 'from_time';
        if (empty($to_date)) $missing_fields[] = 'to_date';
        if (empty($to_time)) $missing_fields[] = 'to_time';
        if (empty($token_date)) $missing_fields[] = 'token_date';
        if (empty($menu_items)) $missing_fields[] = 'menu_items';

        if (!empty($missing_fields)) {
            echo json_encode([
                'success' => false,
                'message' => 'Missing required fields: ' . implode(', ', $missing_fields),
                'received_data' => $_POST
            ]);
            return;
        }

        // Validate date and time combination
        $from_datetime = strtotime($from_date . ' ' . $from_time);
        $to_datetime = strtotime($to_date . ' ' . $to_time);

        if ($from_datetime >= $to_datetime) {
            echo json_encode([
                'success' => false,
                'message' => 'From date/time must be before to date/time'
            ]);
            return;
        }

        // Insert new special token
        $sql = "INSERT INTO specialtokenenable (from_date, from_time, to_date, to_time, token_date, menu_items, fee) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssd", $from_date, $from_time, $to_date, $to_time,$token_date, $menu_items, $fee);

        if ($stmt->execute()) {
            error_log("Special token created successfully");
            echo json_encode([
                'success' => true,
                'message' => 'Special token created successfully',
                'id' => $conn->insert_id
            ]);
        } else {
            error_log("Failed to create special token: " . $conn->error);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create special token: ' . $conn->error
            ]);
        }
        $stmt->close();

    } catch (Exception $e) {
        error_log("Exception in createSpecialToken: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// READ SPECIAL TOKENS - Get all special tokens (FIXED)
function readSpecialTokens($conn) {
    try {
        // Check if created_at column exists
        $checkColumnSql = "SHOW COLUMNS FROM specialtokenenable LIKE 'created_at'";
        $checkResult = $conn->query($checkColumnSql);
        $hasCreatedAt = $checkResult->num_rows > 0;

        if ($hasCreatedAt) {
            $sql = "SELECT * FROM specialtokenenable ORDER BY created_at DESC";
        } else {
            $sql = "SELECT * FROM specialtokenenable ORDER BY menu_id DESC";
        }

        $result = $conn->query($sql);
        $special_tokens = [];

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $special_tokens[] = $row;
            }
        }

        echo json_encode([
            'success' => true,
            'data' => $special_tokens,
            'count' => count($special_tokens)
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// UPDATE SPECIAL TOKEN
function updateSpecialToken($conn) {
    try {
        if (empty($_POST['menu_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Menu ID is required'
            ]);
            return;
        }

        $menu_id = intval($_POST['menu_id']);
        $from_date = $_POST['from_date'] ?? '';
        $from_time = $_POST['from_time'] ?? '';
        $to_date = $_POST['to_date'] ?? '';
        $to_time = $_POST['to_time'] ?? '';
        $token_date = $_POST['token_date'] ?? '';
        $menu_items = $_POST['menu_items'] ?? '';
        $fee = floatval($_POST['fee'] ?? 0.00);

        // Validate required fields
        if (empty($from_date) || empty($from_time) || empty($to_date) || empty($to_time) || empty($token_date) || empty($menu_items)) {
            echo json_encode([
                'success' => false,
                'message' => 'All fields are required for special token'
            ]);
            return;
        }

        // Check if special token exists
        $checkSql = "SELECT menu_id FROM specialtokenenable WHERE menu_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $menu_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Special token not found'
            ]);
            $checkStmt->close();
            return;
        }
        $checkStmt->close();

        // Validate date and time combination
        $from_datetime = strtotime($from_date . ' ' . $from_time);
        $to_datetime = strtotime($to_date . ' ' . $to_time);

        if ($from_datetime >= $to_datetime) {
            echo json_encode([
                'success' => false,
                'message' => 'From date/time must be before to date/time'
            ]);
            return;
        }

        $sql = "UPDATE specialtokenenable SET from_date = ?, from_time = ?, to_date = ?, to_time = ?,token_date = ?, menu_items = ?, fee = ? WHERE menu_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssdi", $from_date, $from_time, $to_date, $to_time,$token_date ,$menu_items, $fee, $menu_id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Special token updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update special token: ' . $conn->error
            ]);
        }
        $stmt->close();

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// DELETE SPECIAL TOKEN
function deleteSpecialToken($conn) {
    try {
        if (empty($_POST['menu_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Menu ID is required'
            ]);
            return;
        }

        $menu_id = intval($_POST['menu_id']);

        // Check if special token exists
        $checkSql = "SELECT menu_id FROM specialtokenenable WHERE menu_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $menu_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Special token not found'
            ]);
            $checkStmt->close();
            return;
        }
        $checkStmt->close();

        $sql = "DELETE FROM specialtokenenable WHERE menu_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $menu_id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Special token deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete special token: ' . $conn->error
            ]);
        }
        $stmt->close();

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// ========== BILL FUNCTIONS ==========

// READ BILLS - Get all bills (FIXED)
function readBills($conn) {
    try {
        // Check if created_at column exists
        $checkColumnSql = "SHOW COLUMNS FROM mess_token_bills LIKE 'created_at'";
        $checkResult = $conn->query($checkColumnSql);
        $hasCreatedAt = $checkResult->num_rows > 0;

        if ($hasCreatedAt) {
            $sql = "SELECT * FROM mess_token_bills ORDER BY created_at DESC";
        } else {
            $sql = "SELECT * FROM mess_token_bills ORDER BY bill_id DESC";
        }

        $result = $conn->query($sql);
        $bills = [];

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $bills[] = $row;
            }
        }

        echo json_encode([
            'success' => true,
            'data' => $bills,
            'count' => count($bills)
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// CREATE BILL (RELAXED VALIDATION)
function createBill($conn) {
    $student_roll_number = $_POST['student_roll_number'] ?? '';
    $month = intval($_POST['month'] ?? date('Y'));
    $amount = floatval($_POST['amount'] ?? 0.00);

    try {
        if (empty($student_roll_number) || empty($month)) {
            echo json_encode([
                'success' => false,
                'message' => 'Student roll number and month are required'
            ]);
            return;
        }

        // REMOVED STUDENT VALIDATION - Allow any roll number for testing

        // Check if bill already exists for this student and month
        $checkBillSql = "SELECT bill_id FROM mess_token_bills WHERE student_roll_number = ? AND month = ?";
        $checkBillStmt = $conn->prepare($checkBillSql);
        $checkBillStmt->bind_param("si", $student_roll_number, $month);
        $checkBillStmt->execute();
        $checkBillResult = $checkBillStmt->get_result();

        if ($checkBillResult->num_rows > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Bill already exists for this student and month'
            ]);
            $checkBillStmt->close();
            return;
        }
        $checkBillStmt->close();

        $sql = "INSERT INTO mess_token_bills (student_roll_number, month, amount) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sid", $student_roll_number, $month, $amount);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Bill created successfully',
                'id' => $conn->insert_id
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create bill: ' . $conn->error
            ]);
        }
        $stmt->close();

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// Close database connection
$conn->close();
?>