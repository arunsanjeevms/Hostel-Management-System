<?php
// === ADD THESE TWO LINES TEMPORARILY ===
error_reporting(E_ALL);
ini_set('display_errors', 1);
// ======================================

// Set headers for JSON output
header('Content-Type: application/json');

// ... rest of your code ...
// <?php
// Set headers for JSON output
header('Content-Type: application/json');

// Include the database connection (assuming db.php is one level up, or adjust path if needed)
include '../db.php'; // Adjust path to db.php if it's not one level up

// Check if registration number is provided
if (!isset($_GET['reg_no']) || empty($_GET['reg_no'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing registration number.']);
    exit;
}

$reg_no = mysqli_real_escape_string($conn, $_GET['reg_no']);

// 1. Fetch all processed leaves for the student (Limit to 10 for performance)
$sql = "
    SELECT 
        la.From_Date, 
        la.To_Date, 
        la.Reason, 
        la.Status, 
        lt.Leave_Type_Name AS LeaveType_ID 
    FROM leave_applications la
    JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
    WHERE la.Reg_No = '{$reg_no}'
    AND la.Status IN ('Approved', 'Rejected by HOD', 'Rejected by Admin', 'Rejected by Parents')
    ORDER BY la.Applied_Date DESC
    LIMIT 10
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    // Database query failed
    echo json_encode(['status' => 'error', 'message' => 'Database query failed: ' . mysqli_error($conn)]);
    exit;
}

$leaves = [];
$total_leaves = 0;
$type_breakdown = [];

while ($row = mysqli_fetch_assoc($result)) {
    $leaves[] = $row;
    
    // Count total leaves and build breakdown
    if (in_array($row['Status'], ['Approved', 'Rejected by HOD', 'Rejected by Admin', 'Rejected by Parents'])) {
        $total_leaves++;
        $type = $row['LeaveType_ID'];
        $type_breakdown[$type] = ($type_breakdown[$type] ?? 0) + 1;
    }
}

// Check if any records were found
if (count($leaves) > 0) {
    echo json_encode([
        'status' => 'success',
        'total_leaves' => $total_leaves,
        'type_breakdown' => $type_breakdown,
        'leaves' => $leaves
    ]);
} else {
    echo json_encode(['status' => 'success', 'message' => 'No leave history found.', 'leaves' => [], 'total_leaves' => 0, 'type_breakdown' => []]);
}

mysqli_close($conn);
?>