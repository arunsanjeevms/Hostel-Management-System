<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$host = "localhost";
$dbname = "hostel";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

$conn->set_charset("utf8mb4");
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
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
    case 'create_token':
        createToken($conn);
        break;
    case 'read_tokens':
        readTokens($conn);
        break;
    case 'delete_token':
        deleteToken($conn);
        break;
    case 'create_special_token':
        createSpecialToken($conn);
        break;
    case 'read_special_tokens':
        readSpecialTokens($conn);
        break;
    case 'read_inactive_special_tokens':
        readInactiveSpecialTokens($conn);
        break;
    case 'update_special_token':
        updateSpecialToken($conn);
        break;
    case 'end_special_token':
        endSpecialToken($conn);
        break;
    case 'delete_special_token':
        deleteSpecialToken($conn);
        break;
    case 'get_statistics':
        getStatistics($conn);
        break;
    case 'get_menu_history':
        getMenuHistory($conn);
        break;
    case 'get_token_history':
        getTokenHistory($conn);
        break;
    case 'get_special_token_history':
        getSpecialTokenHistory($conn);
        break;
    case 'get_all_history':
        getAllHistory($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// ========== STATISTICS ==========
function getStatistics($conn) {
    try {
        $stats = [];
        $result = $conn->query("SELECT COUNT(*) as count FROM specialtokenenable");
        $stats['total_special_tokens'] = $result->fetch_assoc()['count'];
        $result = $conn->query("SELECT COUNT(*) as count FROM mess_menu");
        $stats['total_menus'] = $result->fetch_assoc()['count'];
        $result = $conn->query("SELECT COUNT(*) as count FROM mess_tokens");
        $stats['total_tokens'] = $result->fetch_assoc()['count'];
        $result = $conn->query("SELECT COALESCE(SUM(fee), 0) as total FROM specialtokenenable");
        $stats['total_special_token_fees'] = $result->fetch_assoc()['total'];
        echo json_encode(['success' => true, 'data' => $stats]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ========== MENU HISTORY ==========
function getMenuHistory($conn) {
    try {
        $sql = "SELECT 'Menu' as type, menu_id as id, COALESCE(date, 'N/A') as date, COALESCE(meal_type, 'N/A') as details, COALESCE(items, 'No items') as description, COALESCE(fee, 0) as amount, created_at as timestamp FROM mess_menu ORDER BY created_at DESC";
        $result = $conn->query($sql);
        $history = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
        }
        echo json_encode(['success' => true, 'data' => $history, 'count' => count($history)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ========== TOKEN HISTORY ==========
function getTokenHistory($conn) {
    try {
        $sql = "SELECT 'Token' as type, t.token_id as id, COALESCE(DATE(t.created_at), 'N/A') as date, COALESCE(t.token_type, 'Regular') as details, CONCAT('Roll: ', COALESCE(t.roll_number, 'Unknown')) as description, 0 as amount, t.created_at as timestamp FROM mess_tokens t ORDER BY t.created_at DESC";
        $result = $conn->query($sql);
        $history = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
        }
        echo json_encode(['success' => true, 'data' => $history, 'count' => count($history)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ========== SPECIAL TOKEN HISTORY ==========
function getSpecialTokenHistory($conn) {
    try {
        $sql = "SELECT 'Special Token' as type, menu_id as id, COALESCE(token_date, 'N/A') as date, COALESCE(meal_type, 'N/A') as details, COALESCE(menu_items, 'No items') as description, COALESCE(fee, 0) as amount, created_at as timestamp FROM specialtokenenable WHERE status = 'ended' ORDER BY created_at DESC";
        $result = $conn->query($sql);
        $history = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
        }
        echo json_encode(['success' => true, 'data' => $history, 'count' => count($history)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ========== ALL HISTORY ==========
function getAllHistory($conn) {
    try {
        $allHistory = [];
        $sql = "SELECT 'Menu' as type, menu_id as id, COALESCE(date, 'N/A') as date, COALESCE(meal_type, 'N/A') as details, COALESCE(items, 'No items') as description, COALESCE(fee, 0) as amount, created_at as timestamp FROM mess_menu";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $allHistory[] = $row;
            }
        }
        $sql = "SELECT 'Token' as type, token_id as id, COALESCE(DATE(created_at), 'N/A') as date, COALESCE(token_type, 'Regular') as details, CONCAT('Roll: ', COALESCE(roll_number, 'Unknown')) as description, 0 as amount, created_at as timestamp FROM mess_tokens";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $allHistory[] = $row;
            }
        }
        $sql = "SELECT 'Special Token' as type, menu_id as id, COALESCE(token_date, 'N/A') as date, COALESCE(meal_type, 'N/A') as details, COALESCE(menu_items, 'No items') as description, COALESCE(fee, 0) as amount, created_at as timestamp FROM specialtokenenable WHERE status = 'ended'";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $allHistory[] = $row;
            }
        }
        usort($allHistory, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        echo json_encode(['success' => true, 'data' => $allHistory, 'count' => count($allHistory)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ========== MENUS ==========
function createMenu($conn) {
    $date = $_POST['date'] ?? '';
    $meal_type = $_POST['meal_type'] ?? '';
    $items = $_POST['items'] ?? '';
    $category = $_POST['category'] ?? null;
    $fee = $_POST['fee'] ?? 0.00;
    try {
        if (empty($date) || empty($meal_type) || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Required fields missing']);
            return;
        }
        $stmt = $conn->prepare("INSERT INTO mess_menu (date, meal_type, items, category, fee) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssd", $date, $meal_type, $items, $category, $fee);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Menu created', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function readMenus($conn) {
    try {
        $result = $conn->query("SELECT * FROM mess_menu ORDER BY created_at DESC");
        $menus = [];
        while ($row = $result->fetch_assoc()) {
            $menus[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $menus]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateMenu($conn) {
    try {
        $menu_id = intval($_POST['menu_id']);
        $date = $_POST['date'];
        $meal_type = $_POST['meal_type'];
        $items = $_POST['items'];
        $category = $_POST['category'] ?? null;
        $fee = floatval($_POST['fee']);
        $stmt = $conn->prepare("UPDATE mess_menu SET date=?, meal_type=?, items=?, category=?, fee=? WHERE menu_id=?");
        $stmt->bind_param("ssssdi", $date, $meal_type, $items, $category, $fee, $menu_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Menu updated']);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteMenu($conn) {
    try {
        $menu_id = intval($_POST['menu_id']);
        $stmt = $conn->prepare("DELETE FROM mess_menu WHERE menu_id=?");
        $stmt->bind_param("i", $menu_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Menu deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ========== SPECIAL TOKENS ==========
function createSpecialToken($conn) {
    $from_date = $_POST['from_date'] ?? '';
    $from_time = $_POST['from_time'] ?? '';
    $to_date = $_POST['to_date'] ?? '';
    $to_time = $_POST['to_time'] ?? '';
    $token_date = $_POST['token_date'] ?? '';
    $meal_type = $_POST['meal_type'] ?? '';
    $menu_items = $_POST['menu_items'] ?? '';
    $fee = floatval($_POST['fee'] ?? 0.00);
    try {
        $stmt = $conn->prepare("INSERT INTO specialtokenenable (from_date, from_time, to_date, to_time, token_date, meal_type, menu_items, fee, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("sssssssd", $from_date, $from_time, $to_date, $to_time, $token_date, $meal_type, $menu_items, $fee);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Special token created', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function readSpecialTokens($conn) {
    try {
        $sql = "SELECT * FROM specialtokenenable WHERE DATE_FORMAT(CONCAT(to_date, ' ', to_time), '%Y-%m-%d %H:%i:%s') > NOW() ORDER BY created_at DESC";
        $result = $conn->query($sql);
        $tokens = [];
        while ($row = $result->fetch_assoc()) {
            $tokens[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $tokens]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function readInactiveSpecialTokens($conn) {
    try {
        $sql = "SELECT * FROM specialtokenenable WHERE DATE_FORMAT(CONCAT(to_date, ' ', to_time), '%Y-%m-%d %H:%i:%s') <= NOW() AND status != 'ended' ORDER BY created_at DESC";
        $result = $conn->query($sql);
        $tokens = [];
        while ($row = $result->fetch_assoc()) {
            $tokens[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $tokens]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateSpecialToken($conn) {
    try {
        $menu_id = intval($_POST['menu_id']);
        $from_date = $_POST['from_date'] ?? null;
        $from_time = $_POST['from_time'] ?? null;
        $to_date = $_POST['to_date'];
        $to_time = $_POST['to_time'];
        $token_date = $_POST['token_date'] ?? null;
        $meal_type = $_POST['meal_type'] ?? null;
        $menu_items = $_POST['menu_items'] ?? null;
        $fee = $_POST['fee'] ?? null;

        if ($from_date && $from_time && $token_date && $meal_type && $menu_items && $fee) {
            $stmt = $conn->prepare("UPDATE specialtokenenable SET from_date=?, from_time=?, to_date=?, to_time=?, token_date=?, meal_type=?, menu_items=?, fee=?, status='active' WHERE menu_id=?");
            $stmt->bind_param("sssssssdi", $from_date, $from_time, $to_date, $to_time, $token_date, $meal_type, $menu_items, $fee, $menu_id);
        } else {
            $stmt = $conn->prepare("UPDATE specialtokenenable SET to_date=?, to_time=?, status='active' WHERE menu_id=?");
            $stmt->bind_param("ssi", $to_date, $to_time, $menu_id);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Token updated']);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function endSpecialToken($conn) {
    try {
        $menu_id = intval($_POST['menu_id']);
        $stmt = $conn->prepare("UPDATE specialtokenenable SET status='ended' WHERE menu_id=?");
        $stmt->bind_param("i", $menu_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Token ended and moved to history']);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteSpecialToken($conn) {
    try {
        $menu_id = intval($_POST['menu_id']);
        $stmt = $conn->prepare("DELETE FROM specialtokenenable WHERE menu_id=?");
        $stmt->bind_param("i", $menu_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Special token deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ========== TOKENS ==========
function createToken($conn) {
    $roll_number = $_POST['roll_number'] ?? '';
    $menu_id = intval($_POST['menu_id'] ?? 0);
    $token_type = $_POST['token_type'] ?? 'Regular';
    $from_date = $_POST['from_date'] ?? null;
    $to_date = $_POST['to_date'] ?? null;
    $special_fee = floatval($_POST['special_fee'] ?? 0.00);
    $supervisor_id = !empty($_POST['supervisor_id']) ? intval($_POST['supervisor_id']) : null;
    try {
        if (empty($roll_number) || empty($menu_id)) {
            echo json_encode(['success' => false, 'message' => 'Student roll number and menu ID are required']);
            return;
        }
        $valid_token_types = ['Regular', 'Special'];
        if (!in_array($token_type, $valid_token_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid token type']);
            return;
        }
        $checkMenuSql = "SELECT menu_id FROM mess_menu WHERE menu_id = ?";
        $checkMenuStmt = $conn->prepare($checkMenuSql);
        $checkMenuStmt->bind_param("i", $menu_id);
        $checkMenuStmt->execute();
        $checkMenuResult = $checkMenuStmt->get_result();
        if ($checkMenuResult->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Menu does not exist']);
            $checkMenuStmt->close();
            return;
        }
        $checkMenuStmt->close();
        if (!empty($from_date) && !empty($to_date)) {
            if (strtotime($from_date) > strtotime($to_date)) {
                echo json_encode(['success' => false, 'message' => 'From date cannot be later than to date']);
                return;
            }
        }
        $sql = "INSERT INTO mess_tokens (roll_number, menu_id, token_type, from_date, to_date, special_fee, supervisor_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisssdi", $roll_number, $menu_id, $token_type, $from_date, $to_date, $special_fee, $supervisor_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Token issued', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function readTokens($conn) {
    try {
        $sql = "SELECT t.token_id, t.roll_number, t.menu_id, t.token_type, t.token_date, t.meal_type, t.menu as menu_items, t.special_fee, t.created_at, s.name as student_name FROM mess_tokens t LEFT JOIN students s ON t.roll_number = s.roll_number ORDER BY t.created_at DESC";
        $result = $conn->query($sql);
        $tokens = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tokens[] = $row;
            }
        }
        echo json_encode(['success' => true, 'data' => $tokens, 'count' => count($tokens)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteToken($conn) {
    try {
        if (empty($_POST['token_id'])) {
            echo json_encode(['success' => false, 'message' => 'Token ID required']);
            return;
        }
        $token_id = intval($_POST['token_id']);
        $checkSql = "SELECT token_id FROM mess_tokens WHERE token_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $token_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Token not found']);
            $checkStmt->close();
            return;
        }
        $checkStmt->close();
        $sql = "DELETE FROM mess_tokens WHERE token_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $token_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Token deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

$conn->close();
?>