<?php
header('Content-Type: application/json');


include '../db.php';

$action = $_POST['action'] ?? '';

switch($action){
    case 'academic':
        $course      = $_POST['course'] ?? '';
        $institution = $_POST['institution'] ?? '';
        $board       = $_POST['board'] ?? '';
        $year        = $_POST['year'] ?? 0;
        $percentage  = $_POST['percentage'] ?? 0;
        
        if(!preg_match(
            '/^[A-Za-z\s]+$/',$course)){
            echo json_encode(["success"=>false,"message"=>"Please enter Valid Course"]);
            exit;
        }

        if(!preg_match(
            '/^[A-Za-z\s]+$/',$institution)){
            echo json_encode(["success"=>false,"message"=>"Please enter Valid Institution"]);
            exit;
        }
        if(!preg_match(
            '/^[A-Za-z\s]+$/',$board)){
            echo json_encode(["success"=>false,"message"=>"Please enter Valid Institution"]);
            exit;
        }

        if(!preg_match('/^(202[0-9]|2030)$/', $year)){
            echo json_encode(["success"=>false,"message"=>"Please enter a valid year (Between 2020–2030)"]);
            exit;
        }

        if(!preg_match('/^(100|[0-9]{1,2})$/', $percentage)){
            echo json_encode(["success"=>false,"message"=>"Please enter valid percentage (0–100)"]);
            exit;
        }
        
      

    
        $sql = "INSERT INTO academics (course,institution,board,year,percentage) VALUES (?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
    
        $stmt->bind_param("sssii", $course, $institution, $board, $year, $percentage);

        if($stmt->execute()){
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => $stmt->error]);
        }
        break;


//without file upload

    // case 'leave':
    //         $type       = $_POST['type'] ?? '';
    //         $date       = $_POST['date'] ?? '';
    //         $reason     = $_POST['reason'] ?? '';
            
    //         $sql = "INSERT INTO absent (type, date, reason) VALUES (?, ?, ?)";
    //         $stmt = $conn->prepare($sql);

    //         $stmt->bind_param("sss", $type, $date, $reason);

    //         if($stmt->execute()){
    //             echo json_encode(["status" => "success"]);
    //         } else {
    //             echo json_encode(["status" => "error", "message" => $stmt->error]);
    //         }
            
    //         break;


//with file upload

    case 'leave':
        $type   = $_POST['type'] ?? '';
        $date   = $_POST['date'] ?? '';
        $reason = $_POST['reason'] ?? '';
        $proofPath = "";


        if (empty(trim($type))) {
            echo json_encode(["success" => false, "message" => "Leave/OD is required"]);
            exit;
        }
        
        if (empty(trim($date))) {
            echo json_encode(["success" => false, "message" => "Date is required"]);
            exit;
        }
        
        if (empty(trim($reason))) {
            echo json_encode(["success" => false, "message" => "Reason is required"]);
            exit;
        }

        $sql = "INSERT INTO absent (type, date, reason) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $type, $date, $reason);

        if ($stmt->execute()) {
            $leaveId = $conn->insert_id; 
    
     
            if (isset($_FILES['proof']) && $_FILES['proof']['error'] == 0) {
                $uploadDir = "../proofs/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $maxsize = 1 * 1024 * 1024; 
                if ($_FILES['proof']['size'] > $maxsize) {
                    echo json_encode(["status" => "error", "message" => "File too large. Max 1MB allowed"]);
                    exit;
                }

                $extension  = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
                $fileName   = "leave_" . $leaveId . "." . $extension;
                $targetPath = $uploadDir . $fileName;
    
                if (move_uploaded_file($_FILES['proof']['tmp_name'], $targetPath)) {
                    $proofPath = "proofs/" . $fileName;
    
              
                    $updateSql = "UPDATE absent SET proof=? WHERE sno=?";
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->bind_param("si", $proofPath, $leaveId);
                    $updateStmt->execute();
                } else {
                    echo json_encode(["status" => "error", "message" => "File upload failed"]);
                    exit;
                }
            }
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => $stmt->error]);
        }
        break;
    
     case 'family':
            $name       = $_POST['name'] ?? '';
            $gender     = $_POST['gender'] ?? '';
            $relationship= $_POST['relationship'] ?? '';
            $mobile     = $_POST['mobile'] ?? '';

            if(!preg_match(
                '/^[A-Za-z\s]+$/',$name)){
                echo json_encode(["success"=>false,"message"=>"Enter a valid Name"]);
                exit;
            }

            if (empty(trim($gender))) {
                echo json_encode(["success" => false, "message" => "Gender is required"]);
                exit;
            }
            if (empty(trim($relationship))) {
                echo json_encode(["success" => false, "message" => "Relationship is required"]);
                exit;
            }
            if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
                echo json_encode(["success" => false, "message" => "Enter a valid Mobile Number"]);
                exit;
            }
            

            $sql = "INSERT INTO family (name, gender, relation, mobile) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            $stmt->bind_param("ssss", $name, $gender, $relationship, $mobile);

            if($stmt->execute()){
                echo json_encode(["status" => "success"]);
            } else {
                echo json_encode(["status" => "error", "message" => $stmt->error]);
            }
            break;
}