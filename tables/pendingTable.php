<?php include __DIR__ . '/../db.php'; 
// Start session if not already started (needed for AJAX includes)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../db.php';

$department_id = mysqli_real_escape_string($conn, $_SESSION['department_id'] ?? '');

$result = false;
// Only run the query if department ID is available
if (!empty($department_id)) {
    $sql = "SELECT la.*, s.name AS student_name, lt.Leave_Type_Name 
            FROM leave_applications la
            JOIN students s ON la.Reg_No = s.roll_number
            JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
            WHERE la.Status IN ('Pending', 'Forwarded to Admin') 
            AND s.department_id = '{$department_id}'
            ORDER BY la.Applied_Date DESC";
    $result = mysqli_query($conn, $sql);
}
?>
<table class="table table-bordered" id="ivr-pending-leave-table" width="100%" cellspacing="0">

<colgroup>
    <col style="width:2%;">   <!-- S.No -->
    <col style="width:8%;">  <!-- Reg No -->
    <col style="width:12%;">  <!-- Name -->
    <col style="width:9%;">  <!-- Leave Type -->
    <col style="width:13%;">  <!-- Applied Date -->
    <col style="width:12%;">  <!-- From -->
    <col style="width:12%;">  <!-- To -->
    <col style="width:10%;">  <!-- Reason -->
    <col style="width:8%;">   <!-- Proof -->
    <col style="width:13%;">  <!-- Status -->
    <col style="width:3%;">  <!-- Action -->
</colgroup>

    <thead class="gradient-header">
        <tr>
            <th>S.No</th>
            <th>Reg No </th>
            <th>Name</th>
            <th>Leave Type</th>
            <th>Applied Date</th>
            <th>From</th>
            <th>To</th>
            <th>Reason</th>
            <th>Proof</th>
        
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
                
        $sql = "SELECT la.*, s.name AS student_name, lt.Leave_Type_Name 
        FROM leave_applications la
        JOIN students s ON la.Reg_No = s.roll_number
        JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
        WHERE la.Status IN ('Pending')
        AND la.LeaveType_ID <> 1
        ORDER BY la.Applied_Date DESC";


        $result = mysqli_query($conn, $sql);
        $sno=1;
        while($row=mysqli_fetch_assoc($result)){
            echo "<tr>";
            echo "<td>".$sno++."</td>";
            echo "<td>".$row['Reg_No']."</td>";
            echo "<td>".$row['student_name']."</td>";
            echo "<td>".$row['Leave_Type_Name']."</td>";

            $appliedDate = date('d-m-Y h:i A', strtotime($row['Applied_Date']));
            $fromDate = date('d-m-Y h:i A', strtotime($row['From_Date']));
            $toDate = date('d-m-Y h:i A', strtotime($row['To_Date']));

            echo "<td>".$appliedDate."</td>";
            echo "<td>".$fromDate."</td>";
            echo "<td>".$toDate."</td>";
            echo "<td>".$row['Reason']."</td>";
            if (!empty($row['Proof'])) {
                echo "<td class='text-center align-middle'>
                    <button type='button' class='btn btn-info btn-sm view-proof' 
                        data-proof='".$row['proof_path']."' 
                        data-bs-toggle='modal' 
                        data-bs-target='#viewProofModal'>
                        <i class='fa-solid fa-eye'></i> View Proof
                    </button>
                </td>";
            } else {
                echo "<td class='text-center align-middle text-muted'>No Proof Uploaded </td>";
            }
         

            echo "<td class='text-center align-middle'>
                <button class='btn btn-success btn-sm approve-leave-btn' data-id='".$row['Leave_ID']."' >
                    <i class='fa-solid fa-check'></i> 
                </button>

                <button type='button' class='btn btn-danger btn-sm reject-leave-btn' data-bs-toggle='modal' data-bs-target='#leaveRejectModal' data-id='".$row['Leave_ID']."' >
                    <i class='fa-solid fa-xmark'></i> 
                </button>
            </td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>


<script>
(function initIVRPendingDT() {
    if (typeof $ === 'undefined') return; // jQuery not loaded 
    if (!$.fn.DataTable) return; // DataTables library not present
    const sel = '#ivr-pending-leave-table';
    if ($.fn.DataTable.isDataTable(sel)) {
        $(sel).DataTable().destroy();
    }
    $(sel).DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        order: [
            [0, 'asc']
        ],
        columnDefs: [{
                orderable: false,
                targets: [8, 9]
            } // Proof, Status
        ]
    });
})();

// HOD Approve Leave - Forward to Admin
$(document).on("click", ".approve-leave-btn", function() {
    let leaveId = $(this).data("id");
    
    Swal.fire({
        title: 'Confirm Approval',
        text: "Are you sure you want to forward this leave to the Admin?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#1cc88a', // Success green
        cancelButtonColor: '#6c757d', // Secondary gray
        confirmButtonText: 'Yes, Forward to Admin'
    }).then((result) => {
        if (result.isConfirmed) {
            // User confirmed, proceed with AJAX
            $.ajax({
                url: "./cruds/action.php",
                type: "POST",
                data: {
                    action: "approve", // This action should update status to 'Forwarded to Admin'
                    id: leaveId
                },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        // Show success message using SweetAlert
                        Swal.fire(
                            'Forwarded!',
                            response.message,
                            'success'
                        );
                        
                        // Reload tables and stats
                        reloadAllTables();
                    } else {
                        Swal.fire(
                            'Error!',
                            'Error: ' + response.message,
                            'error'
                        );
                    }
                },
                error: function() {
                    Swal.fire(
                        'Error!',
                        'Error: Failed to process approval request.',
                        'error'
                    );
                }
            });
        }
    });
});

$('#leaveRejectModal').on('show.bs.modal', function (event) {
    // Get the reject icon button that triggered the modal (must use class like .reject-leave-btn in HTML)
    var button = $(event.relatedTarget); 
    var leaveId = button.data('id'); 
    
    // Store the ID in the hidden input field inside the modal
    $('#leaveIdToReject').val(leaveId);
    
    // Clear the reason input for a fresh start
    $('#rejectionReason').val(''); 
});


/**
 * Handler 3b: Confirms rejection and performs the AJAX request.
 * CRITICAL FIX: It retrieves the ID from the HIDDEN INPUT FIELD.
 * (Your original code was trying to read the ID from the confirm button itself, which was empty).
 */
$(document).on('show.bs.modal', '#leaveRejectModal', function (event) {
    // 1. Get the button that opened the modal (the 'Reject' button from the table)
    const button = $(event.relatedTarget); 
    
    // 2. Extract the Leave_ID from the button's data-id attribute
    const leaveId = button.data('id'); 
    
    // 3. CRITICAL: Store the ID in the hidden input field for the #confirmReject handler to use
    $("#leaveIdToReject").val(leaveId);
    
    // Optional: Update the modal header for user clarity
    if (leaveId) {
        $("#leaveRejectModalLabel").text("Reject Leave Application (ID: " + leaveId + ")");
    } else {
        $("#leaveRejectModalLabel").text("Reject Leave Application (Error)");
    }
});
$(document).on("click", "#confirmReject", function() {
    // *** CORRECTED: Get the ID from the HIDDEN INPUT FIELD ***
    let id = $("#leaveIdToReject").val(); 
    let rejectionreason = $("#rejectionReason").val().trim();

    // Basic Validation
    if (!id) {
        Swal.fire('Error', 'Leave ID not found. Please try again.', 'error');
        $("#leaveRejectModal").modal("hide");
        return;
    }
    if (!rejectionreason) {
        Swal.fire('Warning', 'Please enter a mandatory rejection reason!', 'warning');
        return;
    }

    $.ajax({
        url: "./cruds/action.php",
        type: "POST",
        data: {
            action: "reject",
            id: id,
            rejectionreason: rejectionreason
        },
        dataType: "json",
        success: function(response) {
            $("#leaveRejectModal").modal("hide"); // Hide the modal first
            $("#rejectionReason").val(''); // Clear input
            
            if (response.status === "success") {
    Swal.fire({
        title: 'Rejected!',
        text: response.message,
        icon: 'warning' // 'warning' is better for a successful rejection
    });
    reloadAllTables();
} else {
                Swal.fire('Error!', 'Error: ' + response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error!', 'Error: Failed to process rejection request.', 'error');
        }
    });
});
</script>