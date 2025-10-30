<?php
// === TEMPORARY DEBUGGING LINES ===
error_reporting(E_ALL);
ini_set('display_errors', 1);
// =================================
 include __DIR__ . '/../db.php';

// ... rest of your action logic ...
header('Content-Type: application/json');

include '../db.php';

$action = $_POST['action'] ?? '';

switch($action){

    case 'approve':
        $id = $_POST['id'] ?? '';

        if($id){
            $status = "Forwarded to Admin";
            $remarks = "Approved by HOD";
            
            $update_sql = "UPDATE leave_applications SET Status=?, Remarks=? WHERE leave_id=?";
            $stmt = mysqli_prepare($conn, $update_sql);
            if($stmt){
                mysqli_stmt_bind_param($stmt, "sss", $status, $remarks, $id);
                if(mysqli_stmt_execute($stmt)){
                    echo json_encode(['status' => 'success', 'message' => 'Leave forwarded to Admin successfully.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Database error: '.mysqli_stmt_error($stmt)]);
                }
                mysqli_stmt_close($stmt);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error: '.mysqli_error($conn)]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
        }
        break;


       case 'reject':
        $id = $_POST['id'] ?? '';
        $rejectionreason = $_POST['rejectionreason'] ?? '';

        // 1. Basic validation
        if (empty($id) || empty($rejectionreason)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing Leave ID or Rejection Reason.']);
            break;
        }

        // 2. Define the new status and sanitize inputs
        // Assuming the current user is the HOD
        $status = 'Rejected by HOD'; 
        
        // Use mysqli_real_escape_string for security (even with prepared statements, it's a good habit)
        $leave_id = mysqli_real_escape_string($conn, $id);
        $remarks = mysqli_real_escape_string($conn, $rejectionreason);

        // 3. Prepare the update statement
        // Set the Status and the Remarks (reason) for the rejection
        $update_sql = "UPDATE leave_applications SET Status=?, Remarks=? WHERE Leave_ID=?";
        $stmt = mysqli_prepare($conn, $update_sql);

        if ($stmt) {
            // The 'ssi' represents: string (Status), string (Remarks), integer (Leave_ID)
            mysqli_stmt_bind_param($stmt, "ssi", $status, $remarks, $leave_id); 
            
            if (mysqli_stmt_execute($stmt)) {
                // Success Response
                echo json_encode(['status' => 'success', 'message' => 'Leave successfully rejected and moved to processed list.']);
            } else {
                // Execution Error Response
                error_log("Reject SQL Execute Error: " . mysqli_stmt_error($stmt));
                echo json_encode(['status' => 'error', 'message' => 'Database error: Failed to execute rejection update.']);
            }
            mysqli_stmt_close($stmt);
        } else {
            // Prepare Statement Error Response
            error_log("Reject SQL Prepare Error: " . mysqli_error($conn));
            echo json_encode(['status' => 'error', 'message' => 'Database error: Failed to prepare rejection statement.']);
        }
        break;      




                // General Leave Enable/Disable Actions

                case 'enable':
                    $leave_name = $_POST['leave_name'] ?? '';
                    $from_date = $_POST['from_date'] ?? '';
                    $to_date = $_POST['to_date'] ?? '';
                    $instructions = $_POST['instructions'] ?? '';
                    
                    // Validate required fields
                    if(empty($leave_name) || empty($from_date) || empty($to_date)) {
                        echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
                        exit;
                    }
                    
                    // Validate date range
                    if(strtotime($from_date) >= strtotime($to_date)) {
                        echo json_encode(['success' => false, 'message' => 'From date must be earlier than To date.']);
                        exit;
                    }
                    
                    // Check if there's already an active general leave
                    $check_sql = "SELECT * FROM general_Leave WHERE Is_Enabled = ?";
                    $check_stmt = mysqli_prepare($conn, $check_sql);
                    if(!$check_stmt) {
                        echo json_encode(['success' => false, 'message' => 'Database error: Failed to prepare statement.']);
                        exit;
                    }
                    
                    $active_status = 1;
                    mysqli_stmt_bind_param($check_stmt, "i", $active_status);
                    mysqli_stmt_execute($check_stmt);
                    $check_result = mysqli_stmt_get_result($check_stmt);
                    
                    if(mysqli_num_rows($check_result) > 0) {
                        mysqli_stmt_close($check_stmt);
                        echo json_encode(['success' => false, 'message' => 'There is already an active general leave. Please disable it first.']);
                        exit;
                    }
                    mysqli_stmt_close($check_stmt);
                    
                    // Insert new general leave
                    $insert_sql = "INSERT INTO general_Leave (Leave_Name, From_Date, To_Date, Instructions, Is_Enabled, Created_Date) 
                                   VALUES (?, ?, ?, ?, 1, NOW())";
                    $insert_stmt = mysqli_prepare($conn, $insert_sql);
                    
                    if($insert_stmt) {
                        mysqli_stmt_bind_param($insert_stmt, "ssss", $leave_name, $from_date, $to_date, $instructions);
                        if(mysqli_stmt_execute($insert_stmt)) {
                            echo json_encode(['success' => true, 'message' => 'General Leave has been enabled successfully.']);
                        } else {
                            $error = mysqli_stmt_error($insert_stmt);
                            error_log("SQL Error: $error");
                            echo json_encode(['success' => false, 'message' => 'Database error: Failed to enable general leave.']);
                        }
                        mysqli_stmt_close($insert_stmt);
                    } else {
                        $error = mysqli_error($conn);
                        error_log("SQL Error: $error");
                        echo json_encode(['success' => false, 'message' => 'Database error: Failed to prepare statement.']);
                    }
                    break;
                    
                case 'disable':
                    $leave_id = $_POST['leave_id'] ?? '';
                    
                    if(empty($leave_id)) {
                        echo json_encode(['success' => false, 'message' => 'Invalid request: Missing leave ID.']);
                        exit;
                    }
                    
                    // Update the general leave status to 0 (disabled)
                    $update_sql = "UPDATE general_Leave SET Is_Enabled = 0 WHERE GeneralLeave_ID = ? AND Is_Enabled = 1";
                    $update_stmt = mysqli_prepare($conn, $update_sql);
                    
                    if($update_stmt) {
                        mysqli_stmt_bind_param($update_stmt, "i", $leave_id);
                        if(mysqli_stmt_execute($update_stmt)) {
                            if(mysqli_stmt_affected_rows($update_stmt) > 0) {
                                echo json_encode(['success' => true, 'message' => 'General Leave has been disabled successfully.']);
                            } else {
                                echo json_encode(['success' => false, 'message' => 'No active general leave found with the specified ID.']);
                            }
                        } else {
                            $error = mysqli_stmt_error($update_stmt);
                            error_log("SQL Error: $error");
                            echo json_encode(['success' => false, 'message' => 'Database error: Failed to disable general leave.']);
                        }
                        mysqli_stmt_close($update_stmt);
                    } else {
                        $error = mysqli_error($conn);
                        error_log("SQL Error: $error");
                        echo json_encode(['success' => false, 'message' => 'Database error: Failed to prepare statement.']);
                    }
                    break;
                    
                case 'get_active':
                    // Get currently active general leave
                    $sql = "SELECT * FROM general_Leave WHERE Is_Enabled = ? ORDER BY GeneralLeave_ID DESC LIMIT 1";
                    $stmt = mysqli_prepare($conn, $sql);
                    
                    if($stmt) {
                        $active_status = 1;
                        mysqli_stmt_bind_param($stmt, "i", $active_status);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        
                        if($result && mysqli_num_rows($result) > 0) {
                            $row = mysqli_fetch_assoc($result);
                            echo json_encode(['success' => true, 'data' => $row]);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'No active general leave found.']);
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        $error = mysqli_error($conn);
                        error_log("SQL Error: $error");
                        echo json_encode(['success' => false, 'message' => 'Database error: Failed to prepare statement.']);
                    }
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
                    break;


    }
       