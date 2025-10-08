<?php
header('Content-Type: application/json');


include '../db.php';

$table = $_POST['table'] ?? '';

switch($table){

    case 'academic':
        $course      = $_POST['course'] ?? '';
        $institution = $_POST['institution'] ?? '';
        $board       = $_POST['board'] ?? '';
        $year        = $_POST['year'] ?? 0;
        $percentage  = $_POST['percentage'] ?? 0;
        $id          = $_POST['id'] ?? 0;

        
        if(!preg_match(
            '/^[A-Za-z\s]+$/',$course)){
            echo json_encode(["success"=>false,"message"=>"No Numeric is allowed"]);
            exit;
        }

        if(!preg_match(
            '/^[A-Za-z\s]+$/',$institution)){
            echo json_encode(["success"=>false,"message"=>"No Numeric is allowed"]);
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

        $sql = "UPDATE academics SET course=?, institution=?, board=?, year=?, percentage=? WHERE sno=?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("sssiii", $course, $institution, $board, $year, $percentage, $id);


        if($stmt->execute()){
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => $stmt->error]);
        }
        break;

    case 'leave':
        $type       = $_POST['type'] ?? '';
        $date       = $_POST['date'] ?? '';
        $reason     = $_POST['reason'] ?? '';
        $id         = $_POST['leaveId'];

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

        $sql = "UPDATE absent SET type=?, date=?, reason=? WHERE sno=?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("sssi", $type, $date, $reason, $id);

        if($stmt->execute()){
            // new proof 
            if (isset($_FILES['proof']) && $_FILES['proof']['error'] == 0) {
                $uploadDir = "../proofs/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $maxsize = 1 * 1024 * 1024; // 1MB
                if ($_FILES['proof']['size'] > $maxsize) {
                    echo json_encode(["status" => "error", "message" => "File too large. Max 1MB allowed"]);
                    exit;
                }

                $extension  = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
                $fileName   = "leave_" . $id . "." . $extension;
                $targetPath = $uploadDir . $fileName;
    
                if (move_uploaded_file($_FILES['proof']['tmp_name'], $targetPath)) {
                    $proofPath = "proofs/" . $fileName;
    
                    // Update path
                    $updateSql = "UPDATE absent SET proof=? WHERE sno=?";
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->bind_param("si", $proofPath, $id);
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
        $relation   = $_POST['relation'] ?? '';
        $mobile     = $_POST['mobile'] ?? '';
        $id         = $_POST['familyId'];
        
            if(!preg_match(
                '/^[A-Za-z\s]+$/',$name)){
                echo json_encode(["success"=>false,"message"=>"Enter a valid Name"]);
                exit;
            }

            if (empty(trim($gender))) {
                echo json_encode(["success" => false, "message" => "Gender is required"]);
                exit;
            }

            if (empty(trim($relation))) {
                echo json_encode(["success" => false, "message" => "Relationship is required"]);
                exit;
            }

            if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
                echo json_encode(["success" => false, "message" => "Enter a valid Mobile Number"]);
                exit;
            }
            
        
        $sql = "UPDATE family SET name=?, gender=?, relation=?, mobile=? WHERE sno=?";
        
        $stmt = $conn->prepare($sql);
        
        $stmt->bind_param("ssssi", $name, $gender, $relation, $mobile, $id);
        
        if($stmt->execute()){
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => $stmt->error]);
        }
        break;
}