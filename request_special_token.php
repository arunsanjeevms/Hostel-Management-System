<?php
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

// Set content type to JSON at the beginning for API responses
header('Content-Type: application/json');

// Always return JSON, even for errors
try {
    // Check if session is started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['roll_number'])) {
        // For admin API calls, we might not have roll_number but could have user_type
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
            echo json_encode(['status' => false, 'msg' => 'User not logged in']);
            exit;
        }
    }
    
    // Get action from request
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Handle different actions
    switch ($action) {
        case 'read_menus':
            readMenus($conn);
            break;
            
        case 'create_menu':
            createMenu($conn, $_POST);
            break;
            
        case 'update_menu':
            updateMenu($conn, $_POST);
            break;
            
        case 'delete_menu':
            deleteMenu($conn, $_POST);
            break;
            
        case 'read_special_tokens':
            readSpecialTokens($conn);
            break;
            
        case 'create_special_token':
            createSpecialToken($conn, $_POST);
            break;
            
        case 'update_special_token':
            updateSpecialToken($conn, $_POST);
            break;
            
        case 'delete_special_token':
            deleteSpecialToken($conn, $_POST);
            break;
            
        case 'read_tokens':
            readTokens($conn);
            break;
            
        case 'delete_token':
            deleteToken($conn, $_POST);
            break;
            
        default:
            // Original functionality for requesting special tokens
            requestSpecialToken($conn, $_SESSION['roll_number'] ?? null);
            break;
    }
} catch (Exception $e) {
    // Always return JSON even for exceptions
    echo json_encode(['status' => false, 'msg' => 'Server error: ' . $e->getMessage()]);
}

// ========== MENU FUNCTIONS ==========

function readMenus($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM mess_menu ORDER BY date DESC, meal_type ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $menus = [];
        while ($row = $result->fetch_assoc()) {
            $menus[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $menus]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to load menus: ' . $e->getMessage()]);
    }
}

function createMenu($conn, $data) {
    try {
        $stmt = $conn->prepare("INSERT INTO mess_menu (date, meal_type, items, category, fee) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssd", $data['date'], $data['meal_type'], $data['items'], $data['category'], $data['fee']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Menu created successfully', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create menu']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to create menu: ' . $e->getMessage()]);
    }
}

function updateMenu($conn, $data) {
    try {
        $stmt = $conn->prepare("UPDATE mess_menu SET date=?, meal_type=?, items=?, category=?, fee=? WHERE menu_id=?");
        $stmt->bind_param("ssssdi", $data['date'], $data['meal_type'], $data['items'], $data['category'], $data['fee'], $data['menu_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Menu updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update menu']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update menu: ' . $e->getMessage()]);
    }
}

function deleteMenu($conn, $data) {
    try {
        $stmt = $conn->prepare("DELETE FROM mess_menu WHERE menu_id=?");
        $stmt->bind_param("i", $data['menu_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Menu deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete menu']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete menu: ' . $e->getMessage()]);
    }
}

// ========== SPECIAL TOKEN FUNCTIONS ==========

function readSpecialTokens($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM specialtokenenable ORDER BY from_date DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tokens = [];
        while ($row = $result->fetch_assoc()) {
            $tokens[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $tokens]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to load special tokens: ' . $e->getMessage()]);
    }
}

function createSpecialToken($conn, $data) {
    try {
        $stmt = $conn->prepare("INSERT INTO specialtokenenable (from_date, from_time, to_date, to_time, token_date, meal_type, menu_items, fee) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssd", $data['from_date'], $data['from_time'], $data['to_date'], $data['to_time'], $data['token_date'], $data['meal_type'], $data['menu_items'], $data['fee']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Special token created successfully', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create special token']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to create special token: ' . $e->getMessage()]);
    }
}

function updateSpecialToken($conn, $data) {
    try {
        $stmt = $conn->prepare("UPDATE specialtokenenable SET from_date=?, from_time=?, to_date=?, to_time=?, token_date=?, meal_type=?, menu_items=?, fee=? WHERE menu_id=?");
        $stmt->bind_param("ssssssssi", $data['from_date'], $data['from_time'], $data['to_date'], $data['to_time'], $data['token_date'], $data['meal_type'], $data['menu_items'], $data['fee'], $data['menu_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Special token updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update special token']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update special token: ' . $e->getMessage()]);
    }
}

function deleteSpecialToken($conn, $data) {
    try {
        $stmt = $conn->prepare("DELETE FROM specialtokenenable WHERE menu_id=?");
        $stmt->bind_param("i", $data['menu_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Special token deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete special token']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete special token: ' . $e->getMessage()]);
    }
}

// ========== TOKEN FUNCTIONS ==========

function readTokens($conn) {
    try {
        $stmt = $conn->prepare("SELECT t.*, s.name as student_name FROM mess_tokens t LEFT JOIN students s ON t.roll_number = s.roll_number ORDER BY t.created_at DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tokens = [];
        while ($row = $result->fetch_assoc()) {
            $tokens[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $tokens]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to load tokens: ' . $e->getMessage()]);
    }
}

function deleteToken($conn, $data) {
    try {
        $stmt = $conn->prepare("DELETE FROM mess_tokens WHERE token_id=?");
        $stmt->bind_param("i", $data['token_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Token deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete token']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete token: ' . $e->getMessage()]);
    }
}

// ========== ORIGINAL FUNCTIONALITY ==========

function requestSpecialToken($conn, $roll_number) {
    if (!$roll_number) {
        echo json_encode(['status' => false, 'msg' => 'User not logged in']);
        exit;
    }

    // Validate and sanitize input
    $menu_id = isset($_POST['menu_id']) ? intval($_POST['menu_id']) : 0;

    if ($menu_id <= 0) {
        echo json_encode(['status' => false, 'msg' => 'Invalid menu ID']);
        exit;
    }

    // Get special meal details
    $stmt = $conn->prepare("SELECT token_date, meal_type, menu_items, fee FROM specialtokenenable WHERE menu_id = ?");
    $stmt->bind_param("i", $menu_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo json_encode(['status' => false, 'msg' => 'Special meal not found']);
        exit;
    }

    $meal = $result->fetch_assoc();

    // Check if already requested - improved check to be more specific
    $stmt = $conn->prepare("SELECT t.token_id FROM mess_tokens t 
                            JOIN mess_menu m ON t.menu_id = m.menu_id 
                            WHERE t.roll_number=? AND m.meal_type=? AND m.category='Special' AND DATE(m.date) = DATE(?)");
    $stmt->bind_param("sss", $roll_number, $meal['meal_type'], $meal['token_date']);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        // Already requested, return the existing token_id
        $existing_token = $res->fetch_assoc();
        
        // Fetch the existing token data to return with the response
        $stmt_fetch = $conn->prepare("SELECT t.*, s.name as student_name FROM mess_tokens t 
                                     LEFT JOIN students s ON t.roll_number = s.roll_number
                                     WHERE t.token_id=?");
        $stmt_fetch->bind_param("i", $existing_token['token_id']);
        $stmt_fetch->execute();
        $result = $stmt_fetch->get_result();
        $token_data = $result->fetch_assoc();
        
        echo json_encode([
            'status' => true, 
            'msg' => 'Already requested', 
            'token_id' => $existing_token['token_id'],
            'token_data' => $token_data
        ]);
        exit;
    }

    // Due to the unique constraint (date, meal_type, category), we can only have one special meal per meal type per day
    // So we'll use the existing special meal entry in mess_menu for this meal type, or create one if none exists
    // Use the token_date from the special meal, not the current date
    $token_date = $meal['token_date'] ?? date('Y-m-d');
    
    // First, try to get the existing menu_id
    $stmt = $conn->prepare("SELECT menu_id FROM mess_menu WHERE date=? AND meal_type=? AND category='Special'");
    $stmt->bind_param("ss", $token_date, $meal['meal_type']);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        // Use existing menu_id from mess_menu
        $mess_menu_row = $res->fetch_assoc();
        $mess_menu_id = $mess_menu_row['menu_id'];
    } else {
        // Insert the special meal into mess_menu first to satisfy the foreign key constraint
        // We use the details from the specialtokenenable entry
        $stmt = $conn->prepare("INSERT INTO mess_menu (date, meal_type, items, fee, category) VALUES (?, ?, ?, ?, 'Special')");
        $stmt->bind_param("sssd", $token_date, $meal['meal_type'], $meal['menu_items'], $meal['fee']);
        
        if ($stmt->execute()) {
            $mess_menu_id = $conn->insert_id;
        } else {
            // Check if the error is due to a duplicate entry
            if ($conn->errno == 1062) {
                // Duplicate entry, try to fetch the existing menu_id
                $stmt = $conn->prepare("SELECT menu_id FROM mess_menu WHERE date=? AND meal_type=? AND category='Special'");
                $stmt->bind_param("ss", $token_date, $meal['meal_type']);
                $stmt->execute();
                $res = $stmt->get_result();
                $mess_menu_row = $res->fetch_assoc();
                $mess_menu_id = $mess_menu_row['menu_id'];
            } else {
                echo json_encode(['status' => false, 'msg' => 'Error inserting into mess_menu: ' . $conn->error]);
                exit;
            }
        }
    }

    // Insert request using the mess_menu_id to satisfy the foreign key constraint
    $token_type = 'Special';
    $token_date = $meal['token_date'] ?? date('Y-m-d');
    // Fixed the parameter binding - removed the extra parameter for NOW() which is handled directly in SQL
    $stmt = $conn->prepare("INSERT INTO mess_tokens (roll_number, menu_id, meal_type, menu, token_type, token_date, special_fee, created_at) VALUES (?,?,?,?,?,?,?,NOW())");
    $stmt->bind_param("sisssss", $roll_number, $mess_menu_id, $meal['meal_type'], $meal['menu_items'], $token_type, $token_date, $meal['fee']);

    if($stmt->execute()){
        $token_id = $conn->insert_id;
        
        // Fetch the inserted token data to return with the response
        $stmt_fetch = $conn->prepare("SELECT t.*, s.name as student_name FROM mess_tokens t 
                                     LEFT JOIN students s ON t.roll_number = s.roll_number
                                     WHERE t.token_id=?");
        $stmt_fetch->bind_param("i", $token_id);
        $stmt_fetch->execute();
        $result = $stmt_fetch->get_result();
        $token_data = $result->fetch_assoc();
        
        echo json_encode([
            'status' => true, 
            'msg' => 'Requested successfully', 
            'token_id' => $token_id,
            'token_data' => $token_data
        ]);
    } else {
        echo json_encode(['status' => false, 'msg' => 'Error in request: ' . $conn->error]);
    }
}
?>
