<?php
include 'db_connect.php';

if (isset($_POST['action']) && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    if ($_POST['action'] === 'approve') {
        // Faculty forwards leave to admin
        $sql = "UPDATE leave_applications 
                SET faculty_status='Forwarded to Admin', admin_status='Pending'
                WHERE Leave_ID=$id";
        $conn->query($sql);

        // ✅ Include the extra files after DB update
        include 'leave_enable.php';   // whatever logic to enable the leave
        include 'leave_approve.php';  // whatever logic to send notification / further processing

        echo "Leave forwarded to Admin successfully.";
    } 
    elseif ($_POST['action'] === 'reject') {
        // Faculty rejects with reason
        $reason = $conn->real_escape_string($_POST['reason']);
        $sql = "UPDATE leave_applications 
                SET faculty_status='Rejected by HOD', Remarks='$reason', admin_status='Pending'
                WHERE Leave_ID=$id";
        $conn->query($sql);

        // ✅ Include the extra files (optional if rejection also triggers something)
        include 'leave_enable.php';
        include 'leave_approve.php';

        echo "Leave rejected with reason: $reason";
    }
}
?>
