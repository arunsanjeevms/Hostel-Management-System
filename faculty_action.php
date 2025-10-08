<?php
header('Content-Type: application/json');


include '../db.php';

$action = $_POST['action'] ?? '';

switch($action){
    case 'approve':
        $id = $_POST['id'] ?? '';

        if($id){
            $update_sql = "UPDATE absent SET status='Approved' WHERE sno='$id'";
            if(mysqli_query($conn, $update_sql)){
                echo json_encode(['status' => 'success', 'message' => 'Leave approved successfully.']);
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
        
        if(empty($id)){
            echo json_encode(['status' => 'error', 'message' => 'Invalid request: Missing ID']);
            break;
        }

        error_log("Reject Leave - ID: $id, Reason: $rejectionreason");

        $rejectionreason = mysqli_real_escape_string($conn, $rejectionreason);
    
        $status = $rejectionreason;
        if (strlen($status) > 55) {
            $status = "Rejected";
        }
        
        $update_sql = "UPDATE absent SET status='$status' WHERE sno='$id'";

        if(mysqli_query($conn, $update_sql)){
            echo json_encode(['status' => 'success', 'message' => 'Leave rejected successfully.']);
        } else {
            $error = mysqli_error($conn);
            error_log("SQL Error: $error");
            echo json_encode(['status' => 'error', 'message' => 'Database error: '.$error]);
        }
        break;


    }
       