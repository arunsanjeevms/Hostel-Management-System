<?php include __DIR__ . '/../db.php'; ?>

<table class="table table-bordered" id="processed-leave-table" width="100%" cellspacing="0">

    <colgroup>
        <col style="width:3%;"> <col style="width:8%;"> <col style="width:12%;"> <col style="width:9%;"> <col style="width:13%;"> <col style="width:12%;"> <col style="width:12%;"> <col style="width:12%;"> <col style="width:8%;"> <col style="width:13%;"> </colgroup>


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
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php
          // Ensure db connection is available before running the query
          if (!isset($conn)) {
              echo "<tr><td colspan='10' class='text-danger text-center'>Database connection failed. Check db.php.</td></tr>";
              exit;
          }

          // Fetch only rows where final_status is one of the processed states
         // Updated Query for processedTable.php
$sql = "SELECT la.*, s.name AS student_name, s.roll_number AS Reg_No, lt.Leave_Type_Name 
        FROM leave_applications la
        JOIN students s ON la.Reg_No = s.roll_number
        JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
        WHERE la.Status IN ('Rejected by HOD','Rejected by Admin','Rejected by Parents','Approved', 'Forwarded to Admin') 
        ORDER BY la.Leave_ID DESC";
        $result = mysqli_query($conn, $sql);
        
        if (!$result) {
            echo "<tr><td colspan='10' class='text-danger text-center'>Query failed: " . mysqli_error($conn) . "</td></tr>";
        } else {
            $sno=1;
            while($row=mysqli_fetch_assoc($result)){
                echo "<tr>";
                echo "<td>".$sno++."</td>";
                
                // --- Reg No & Clickable Name ---
                $reg_no = htmlspecialchars($row['Reg_No']);
                $student_name = htmlspecialchars($row['student_name']);

                echo "<td>".$reg_no."</td>";
                
                // Button using btn-name-style class (for better visual appearance)
                echo "<td class='text-start'>";
                echo "<button type='button' class='btn btn-sm p-0 btn-name-style student-history-btn' 
                    data-regno='" . $reg_no . "' 
                    data-studentname='" . $student_name . "'
                    data-bs-toggle='modal' data-bs-target='#studentHistoryModal'>"; 
                echo $student_name;
                echo "</button>";
                echo "</td>";
                // --- END Clickable Name ---

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
                            data-proof='".$row['Proof']."' 
                            data-bs-toggle='modal' 
                            data-bs-target='#viewProofModal'>
                            <i class='fa-solid fa-eye'></i> View Proof
                        </button>
                    </td>";
                } else {
                    echo "<td class='text-center align-middle text-muted'>No Proof Uploaded </td>";
                }

                // --- START CORRECTED STATUS DISPLAY LOGIC ---
                // In ./tables/processedTable.php
$current_status = $row['Status'];
// CRITICAL FIX: Use ENT_QUOTES to ensure single quotes in remarks don't break the 'data-reason' attribute.
$remarks = htmlspecialchars($row['Remarks'] ?? 'No reason recorded', ENT_QUOTES, 'UTF-8');
                echo "<td class='text-center align-middle'>";

                // 1. Approved Status (Green Button)
                if($current_status == 'Approved') {
                    // Check if Remarks exists, if not, default to Parents. (Assuming Parent approval is the final step)
                    $approved_by = !empty($row['Remarks']) ? $row['Remarks'] : 'Parents'; 
                    echo "<button class='btn btn-success btn-sm' disabled>Leave Approved</button>";
                    echo "<br><span class='text-muted'> (Final Approval)</span>";
                }
                else if($current_status == 'Forwarded to Admin') {
    echo "<button class='btn btn-primary btn-sm' disabled>Forwarded To Admin</button>";
    echo "<br><span class='text-muted'> (Waiting for Admin)</span>";
}
                // 2. Rejected Status (Orange/Warning Button)
else if(strpos($current_status, 'Rejected') !== false) {
    // Determine who rejected it for the label
    $rejected_by = '';
    if ($current_status == 'Rejected by HOD') {
        $rejected_by = ' (Rejected by HOD)';
    } else if ($current_status == 'Rejected by Admin') {
        $rejected_by = ' (Rejected by Admin)';
    } else if ($current_status == 'Rejected by Parents') {
        $rejected_by = ' (Rejected by Parents)';
        $remarks = 'Rejected by Parents'; // Use status as reason if no remarks
    }

    // CRITICAL FIX: Add Bootstrap attributes and change class to be more descriptive
    echo "<button type='button' 
             style='background-color:#f1a460; color: #fff;' 
             class='btn btn-sm reject-reason-view' 
             data-bs-toggle='modal' 
             data-bs-target='#rejectReasonModal' 
             data-reason='{$remarks}'>
             <i class='fa-solid fa-question'></i> Rejected
          </button>";
    echo "<br><span class='text-muted'> {$rejected_by}</span>";
}
                
                // 3. Unexpected Processed Status (Shouldn't happen with the WHERE clause, but good for debug)
                else {
                    echo "<button class='btn btn-secondary btn-sm' disabled>".$current_status."</button>";
                }

                echo "</td>";
                // --- END CORRECTED STATUS DISPLAY LOGIC ---
                
                echo "</tr>";   
            }
        }
        ?>
    </tbody>
</table>

  <img id="pdfLogo" src="../image/kr.jpg" style="display:none;">

<script>
// ... (Your existing JavaScript/DataTables code remains here) ...
(function initProcessedDT() {
    if (typeof $ === 'undefined') return;
    if (!$.fn.DataTable) return;
    const sel = '#processed-leave-table';
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
                targets: [8, 9] // Proof, Status columns
            } 
        ],
        dom: 'Bfrtip',
        buttons: [
            // 🟢 EXPORT TO EXCEL BUTTON
            {
                extend: 'excelHtml5',
                title: 'Processed Leave Report', 
                text: '📊 Export to Excel',
                exportOptions: {
                    modifier: {
                        page: 'all' // Export all data
                    },
                    // Exclude the 'Proof' (8) and 'Status' (9) columns from the export
                    columns: [0, 1, 2, 3, 4, 5, 6, 7] 
                }
            },
            // 📄 EXPORT TO PDF BUTTON
            {
                extend: 'pdfHtml5',
                title: 'Processed Leave Report',
                text: '📄 Export to PDF',
                orientation: 'landscape', // Better fit for 10 columns
                pageSize: 'A4',         
                exportOptions: {
                    modifier: {
                        page: 'all' // Export all data
                    },
                    // Exclude the 'Proof' (8) and 'Status' (9) columns from the export
                    columns: [0, 1, 2, 3, 4, 5, 6, 7] 
                }
            } 
        ]
    });
})(); 
// **CRITICAL:** Ensure you only have one final closing parenthesis for the IIFE, no extra ones.
</script>