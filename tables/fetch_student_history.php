<?php
// === fetch_student_history.php (FINAL CORRECTED VERSION) ===

// 1. Setup and Authentication
include __DIR__ . '/../db.php'; 

if (!isset($conn) || mysqli_connect_error()) {
    echo '<div class="alert alert-danger">Database connection failed.</div>';
    exit;
}

// 2. CRITICAL CHECK
if (!isset($_GET['reg_no']) || empty($_GET['reg_no'])) {
    echo '<div class="alert alert-danger">Internal Error: Missing student registration number.</div>';
    exit;
}

// 3. Sanitize input
$reg_no = mysqli_real_escape_string($conn, $_GET['reg_no']);

// 4. SQL Query to fetch history (FIXED: Removed internal comment)
$sql = "
    SELECT 
        la.Applied_Date,
        la.From_Date, 
        la.To_Date, 
        la.Reason, 
        la.Status,
        la.Remarks,  
        lt.Leave_Type_Name
    FROM leave_applications la
    JOIN leave_types lt ON la.LeaveType_ID = lt.LeaveType_ID
    WHERE la.Reg_No = '{$reg_no}'
    ORDER BY la.Applied_Date DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo '<div class="alert alert-danger">Database query failed: ' . mysqli_error($conn) . '</div>';
    exit;
}

// 5. Initialize Data Arrays and Counters
$pending_rows = '';
$processed_rows = '';
$count_approved = 0;
$count_rejected = 0;
$count_pending = 0;
$sno_pending = 1;
$sno_processed = 1;

// 6. Loop and Separate Data
while ($row = mysqli_fetch_assoc($result)) {
    $current_status = $row['Status'];
    
    // Determine status class and target table
    $is_pending = in_array($current_status, ['Pending', 'Forwarded to Admin']);
    $is_processed = (strpos($current_status, 'Rejected') !== false) || ($current_status == 'Approved');
    
    // Counters
    if ($current_status == 'Approved') {
        $count_approved++;
    } elseif (strpos($current_status, 'Rejected') !== false) {
        $count_rejected++;
    } elseif ($is_pending) {
        $count_pending++;
    }

    // Common row data formatting
    $applied_date = date('d-m-Y', strtotime($row['Applied_Date']));
    $from_date = date('d-m-Y h:i A', strtotime($row['From_Date']));
    $to_date = date('d-m-Y h:i A', strtotime($row['To_Date']));
    $reason_short = htmlspecialchars(substr($row['Reason'], 0, 50)) . '...';
    $leave_type = htmlspecialchars($row['Leave_Type_Name']);
    
    // Common table cells excluding S.No and last column
    $row_base_cells = '<td>' . $leave_type . '</td>';
    $row_base_cells .= '<td>' . $applied_date . '</td>';
    $row_base_cells .= '<td>' . $from_date . '</td>';
    $row_base_cells .= '<td>' . $to_date . '</td>';
    $row_base_cells .= '<td>' . $reason_short . '</td>';
    
    // Decide which table the row goes into
    if ($is_pending) {
        // Pending Table
        $row_html_final = '<tr>';
        $row_html_final .= '<td>' . $sno_pending++ . '</td>';
        $row_html_final .= $row_base_cells;
        $row_html_final .= '<td><span class="badge bg-warning text-dark">' . htmlspecialchars($current_status) . '</span></td>';
        $row_html_final .= '</tr>';
        $pending_rows .= $row_html_final;

    } elseif ($is_processed) {
        // Processed Table
        $status_class = ($current_status == 'Approved') ? 'success' : 'danger';
        $remarks_text = htmlspecialchars($row['Remarks'] ?? 'N/A');

        $row_html_final = '<tr>';
        $row_html_final .= '<td>' . $sno_processed++ . '</td>';
        $row_html_final .= $row_base_cells;
        
        // Combined Remarks & Status Column
        $row_html_final .= '<td>';
        $row_html_final .= '<div class="mb-1 small text-dark">'.htmlspecialchars($remarks_text).'</div>';
        $row_html_final .= '<span class="badge bg-' . $status_class . '">' . htmlspecialchars($current_status) . '</span>';
        $row_html_final .= '</td>';
        $row_html_final .= '</tr>';
        $processed_rows .= $row_html_final;
    }
}

// 7. Output the Stat Cards (Row 1)
// 7. Output the Stat Cards (Row 1)
echo '<div class="row mb-4">';

// --- APPROVED LEAVES CARD (SUCCESS / GREEN GRADIENT) ---
echo '  <div class="col-md-4 mb-3">';
echo '    <div class="card shadow-sm h-100 py-2 gradient-card gradient-success">'; // 🟢 Custom Gradient Class
echo '      <div class="card-body">';
echo '        <div class="text-xs font-weight-bold text-white text-uppercase mb-1">Approved Leaves</div>';
echo '        <div class="h5 mb-0 font-weight-bold text-white">' . $count_approved . '</div>';
echo '        <i class="fas fa-check-circle card-icon" style="color: white !important; opacity: 1 !important;"></i>'; // 🟢 Icon for Approved
echo '      </div>';
echo '    </div>';
echo '  </div>';

// --- REJECTED LEAVES CARD (DANGER / RED GRADIENT) ---
echo '  <div class="col-md-4 mb-3">';
echo '    <div class="card shadow-sm h-100 py-2 gradient-card gradient-danger">'; // 🔴 Custom Gradient Class
echo '      <div class="card-body">';
echo '        <div class="text-xs font-weight-bold text-white text-uppercase mb-1">Rejected Leaves</div>';
echo '        <div class="h5 mb-0 font-weight-bold text-white">' . $count_rejected . '</div>';
echo '        <i class="fas fa-times-circle card-icon" style="color: white !important; opacity: 1 !important;"></i>'; // 🔴 Icon for Rejected
echo '      </div>';
echo '    </div>';
echo '  </div>';

// --- PENDING LEAVES CARD (WARNING / YELLOW GRADIENT) ---
echo '  <div class="col-md-4 mb-3">';
echo '    <div class="card shadow-sm h-100 py-2 gradient-card gradient-warning">'; // 🟡 Custom Gradient Class
echo '      <div class="card-body">';
echo '        <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">Pending/Forwarded</div>'; // Text is dark for yellow background
echo '        <div class="h5 mb-0 font-weight-bold text-dark">' . $count_pending . '</div>'; // Text is dark
echo '        <i class="fas fa-clock card-icon text-dark" style="color:white !important; opacity: 1 !important;"></i>';// 🟡 Icon for Pending
echo '      </div>';
echo '    </div>';
echo '  </div>';

echo '</div>';


// 8. Output the Processed Leaves Table (The main processed history)
echo '<h6 class="text-dark fw-bold mb-3 border-bottom pb-2">Processed Leaves History</h6>';

if (empty($processed_rows)) {
    // 🛑 If empty, display message in a simple div/p, NOT a table structure
    echo '<div class="text-center text-muted py-4"><i class="fas fa-exclamation-circle me-2"></i>No approved or rejected leaves found.</div>';
} else {
    // 🟢 If data exists, output the table structure
    echo '<div class="table-responsive mb-5">';
    echo '<table class="table table-bordered table-striped" id="processed-history-dt" width="100%" cellspacing="0">';
    echo '    <thead class="gradient-header bg-success text-white">';
    echo '        <tr>';
    echo '            <th style="width:5%;">S.No</th>';
    echo '            <th style="width:15%;">Leave Type</th>';
    echo '            <th style="width:15%;">Applied Date</th>';
    echo '            <th style="width:20%;">From Date</th>';
    echo '            <th style="width:20%;">To Date</th>';
    echo '            <th style="width:20%;">Reason</th>';
    echo '            <th style="width:15%;">Remarks & Status</th>'; 
    echo '        </tr>';
    echo '    </thead>';
    echo '    <tbody>';
    echo $processed_rows;
    echo '    </tbody>';
    echo '</table>';
    echo '</div>';
}


// 9. Output the Pending Leaves Table (Separate table for pending status)
// 9. Output the Pending Leaves Table (Separate table for pending status)
echo '<h6 class="text-dark fw-bold mb-3 border-bottom pb-2">Pending / Forwarded Leaves</h6>';

if (empty($pending_rows)) {
    // 🛑 If empty, display message in a simple div/p, NOT a table structure
    echo '<div class="text-center text-muted py-4"><i class="fas fa-check-circle me-2"></i>No pending or forwarded leaves found.</div>';
} else {
    // 🟢 If data exists, output the table structure
    echo '<div class="table-responsive">';
    echo '<table class="table table-bordered table-striped" id="pending-history-dt" width="100%" cellspacing="0">';
    echo '    <thead class="gradient-header bg-warning text-dark">';
    echo '        <tr>';
    echo '            <th style="width:5%;">S.No</th>';
    echo '            <th style="width:15%;">Leave Type</th>';
    echo '            <th style="width:15%;">Applied Date</th>';
    echo '            <th style="width:20%;">From Date</th>';
    echo '            <th style="width:20%;">To Date</th>';
    echo '            <th style="width:20%;">Reason</th>';
    echo '            <th style="width:15%;">Status</th>';
    echo '        </tr>';
    echo '    </thead>';
    echo '    <tbody>';
    echo $pending_rows;
    echo '    </tbody>';
    echo '</table>';
    echo '</div>';
}
// 10. DataTables Initialization Script (Now initializes TWO tables)
// 10. DataTables Initialization Script (Now initializes TWO tables)
?>
<script>
    // Ensure jQuery and DataTables are loaded before executing
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        
        function initializeTable(sel) {
            // 🛑 CRITICAL FIX: Only initialize if the table element exists 🛑
            if ($(sel).length === 0) {
                return; // Exit if table element is not present (because it was empty)
            }
            
            // Destroy existing instance if any
            if ($.fn.DataTable.isDataTable(sel)) {
                $(sel).DataTable().destroy(); 
            }
            
            // Initialize the new instance
            $(sel).DataTable({
                responsive: true,
                pageLength: 5, 
                lengthMenu: [5, 10, 20],
                order: [[0, "desc"]], 
                dom: "tip" // Show only Table, Info, and Pagination
            });
        };

        // Call the initialization function for both tables
        initializeTable("#processed-history-dt");
        initializeTable("#pending-history-dt");
    }
</script>