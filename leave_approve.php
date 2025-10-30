<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Faculty Authentication and Session Variables
// Ensures only authenticated faculty members can access this page
if (!isset($_SESSION['faculty_id']) || $_SESSION['user_type'] != 'faculty') {
    header("Location: login.php");
    exit;
}

// 2. Retrieve the department ID and Name from the session
// This department_id is used by the included files (leaveStats.php, processedLeaveStats.php, tables)
$department_id = $_SESSION['department_id'] ?? null;
$department_name = $_SESSION['department_name'] ?? 'Unknown Department'; 
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <?php include 'db.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Management - Faculty Leave Approval | <?php echo htmlspecialchars($department_name); ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="image/icons/mkce_s.png">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-5/bootstrap-5.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css" rel="stylesheet">

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <style>
    /* Custom CSS Variables */
    :root {
        --sidebar-width: 250px;
        --sidebar-collapsed-width: 70px;
        --topbar-height: 60px;
        --footer-height: 60px;
        --primary-color: #4e73df;
        --secondary-color: #858796;
        --success-color: #1cc88a;
        --dark-bg: #1a1c23;
        --light-bg: #f8f9fc;
        --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Gradient Card Styles */
    .gradient-card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
        height: 100%;
        min-height: 150px;
        max-height: 180px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        position: relative;
    }

    .gradient-card:hover {
        transform: translateY(-7px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    }

    /* Decorative tilted corners */
    .gradient-card::before,
    .gradient-card::after {
        content: "";
        position: absolute;
        pointer-events: none;
        border-radius: 5px;
        transform: rotate(45deg);
        transition: transform 0.35s ease, opacity 0.35s ease;
        z-index: 0;
    }

    .gradient-card::before {
        top: -95px;
        right: -95px;
        width: 140px;
        height: 140px;
        background: rgba(0, 0, 0, 0.06);
    }

    .gradient-card::after {
        bottom: -105px;
        left: -105px;
        width: 200px;
        height: 140px;
        background: rgba(0, 0, 0, 0.06);
    }

    .gradient-card:hover::before {
        transform: translate(-4px, 4px) rotate(45deg) scale(1.03);
        opacity: 0.14;
    }

    .gradient-card:hover::after {
        transform: translate(4px, -4px) rotate(45deg) scale(1.03);
        opacity: 0.22;
    }

    /* Gradient Backgrounds */
    .gradient-primary {
        background: linear-gradient(135deg, #566eee 0%, #4e28b0 100%);
    }

    .gradient-success {
        background: linear-gradient(135deg, #42cbbd 0% , #21d9ab  100%);
    }

    .gradient-info {
        background: linear-gradient(135deg, #ffa41a 0%, #ff8a1a 100%);
    }

    .gradient-warning {
        background: linear-gradient(135deg, #f45a67ff 0%, #e84956 100%);
    }

    .gradient-danger {
        background: linear-gradient(135deg, #96a1b4 0%, #6e788c 100%);
    }

    .gradient-secondary {
        background: linear-gradient(135deg, #5ecdf2 0%, #539bfc 100%);
    }

    /* Card Content */
    .gradient-card .card-body {
        padding: 1.25rem 1rem;
        position: relative;
        z-index: 1;
    }

    /* Icon Container */
    .gradient-card .icon-container {
        font-size: 40px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
        opacity: 0.9;
    }

    .gradient-card:hover .icon-container {
        transform: scale(1.2);
    }

    /* Card Title */
    .gradient-card .card-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    /* Card Value */
    .gradient-card .card-value {
        font-size: 1.3rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    .pulse-value {
        animation: pulse 2s infinite;
    }

    .card-clickable { cursor: pointer; }

    /* Other styles... */
    .content {
        margin-left: var(--sidebar-width);
        padding-top: var(--topbar-height);
        transition: all 0.3s ease;
        min-height: 100vh;
    }

    .breadcrumb-area {
        background-image: linear-gradient(to top, #fff1eb 0%, #ace0f9 100%);
        border-radius: 10px;
        box-shadow: var(--card-shadow);
        margin: 20px;
        padding: 15px 20px;
    }

    .breadcrumb-item a {
        color: var(--primary-color);
        text-decoration: none;
        transition: var(--transition);
    }

    .breadcrumb-item a:hover {
        color: #224abe;
    }
    
    .gradient-header {
        --bs-table-bg: transparent;
        --bs-table-color: white;
        background: linear-gradient(135deg, #4CAF50, #2196F3) !important;
        text-align: center;
        font-size: 0.9em;
    }

    td {
        text-align: left;
        font-size: 0.9em;
        vertical-align: middle;
    }

    /* Loader */
    .loader-container {
        position: fixed;
        left: var(--sidebar-width);
        right: 0;
        top: var(--topbar-height);
        bottom: var(--footer-height);
        background: rgba(255, 255, 255, 0.95);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        transition: left 0.3s ease;
        display: none; /* Hide by default, show on load */
    }
    .loader-container.show {
        display: flex;
    }
    .loader-container.hide {
        display: none;
    }


    /* Custom style for the clickable name button */
    .btn-name-style {
        color: #333; /* Darker text color */
        text-decoration: none; /* No underline */
        font-weight: 500;
        text-align: left !important;
        background:none ;
        border: none;
        padding: 0;
        margin: 0;
    }
    .btn-name-style:hover {
        color: var(--primary-color); /* Subtle hover color change */
        text-decoration: underline; /* Add underline on hover for clickability */
        background: none;
    }
    </style>
</head>

<body>


    <?php include './assets/sidebar.php'; ?>

    <div class="content">

        <div class="loader-container" id="loaderContainer">
            <div class="loader"></div>
        </div>

        <?php include './assets/topbar.php'; ?>

        <div class="breadcrumb-area custom-gradient">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Leave Approval (Faculty HOD - <?php echo htmlspecialchars($department_name); ?>)</li>
                </ol>
            </nav>
        </div>

        <div class="container-fluid">
    <div class="custom-tabs">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" 
                   id="family-main-tab" 
                   data-bs-toggle="tab" 
                   href="#pending-content" 
                   role="tab">Pending</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" 
                   id="processed-main-tab" 
                   data-bs-toggle="tab" 
                   href="#processed-content" 
                   role="tab">Processed</a>
            </li>
        </ul>
                <div class="tab-content mt-3">
                    <!-- Pending Tab Content -->
                    <div class="tab-pane fade show active" id="pending-content" role="tabpanel">
                        <div id="leaveStatsCards">
                            <?php include './tables/leaveStats.php'; ?>
                        </div>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3"><center><h6 class="m-0 font-weight-bold">Pending Leave Applications (Faculty)</h6></center></div>
                            <div class="card-body"><div class="table-responsive" id="pendingTable"><?php include './tables/PendingTable.php'; ?></div></div>
                        </div>
                    </div>

                    <!-- Processed Tab Content -->
                    <div class="tab-pane fade" id="processed-content" role="tabpanel">
                        <div id="processedLeaveStatsCards">
                            <?php include './tables/ProcessedLeaveStats.php'; ?>
                        </div>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3"><center><h6 class="m-0 font-weight-bold">Processed Leave Applications (Faculty)</h6></center></div>
                            <div class="card-body"><div class="table-responsive" id="processedTable"><?php include './tables/ProcessedTable.php'; ?></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include './assets/footer.php'; ?>
    </div>
    <script>
    // General JS for loader and sidebar toggle...
    document.addEventListener('DOMContentLoaded', function() {
        const loaderContainer = document.getElementById('loaderContainer');
        let loadingTimeout;

        function hideLoader() {
            loaderContainer.classList.add('hide');
        }

        function showError() {
            console.error('Page load took too long or encountered an error');
        }

        loaderContainer.classList.add('show'); // Show loader immediately
        loadingTimeout = setTimeout(showError, 10000);

        window.onload = function() {
            clearTimeout(loadingTimeout);
            setTimeout(hideLoader, 500);
        };

        window.onerror = function(msg, url, lineNo, columnNo, error) {
            clearTimeout(loadingTimeout);
            showError();
            return false;
        };

        // ... (Sidebar and user menu toggles would go here) ...
    });

    $(document).ready(function() {

        // Load content using AJAX (These files must contain the department filter)
        $("#leaveStatsCards").load("./tables/leaveStats.php");
        $("#processedLeaveStatsCards").load("./tables/processedLeaveStats.php");
        const facultyDept = "<?php echo $_SESSION['department_name']; ?>";

$("#pendingTable").load("./tables/pendingTable.php?department=" + encodeURIComponent(facultyDept));
$("#processedTable").load("./tables/processedTable.php?department=" + encodeURIComponent(facultyDept));


        // Proof View Modal Handler
        $(document).on("click", ".view-proof", function() {
            let proofPath = $(this).data("proof");
            let timestamp = new Date().getTime();
            let cacheBustedPath = proofPath + "?t=" + timestamp;
            let ext = proofPath.split('.').pop().toLowerCase();

            let html = "";
            if (["jpg", "jpeg", "png", "gif"].includes(ext)) {
                html = `<img src="${cacheBustedPath}" class="img-fluid" alt="Proof">`;
            } else if (ext === "pdf") {
                html =
                    `<iframe src="${cacheBustedPath}" width="100%" height="600px" style="border:none;"></iframe>`;
            } else {
                html = `<p class="text-danger">Unsupported file format</p>`;
            }

            $("#proofContainer").html(html);
        });


        // Rejection Reason View Handler
        $(document).on("click", ".reasonView", function() {
            Swal.fire({
                title: "Rejection - Reason",
                text: $(this).data("reason"),
                icon: "error"
            });
        })
        
        // SCRIPT TO HANDLE PROCESSED BREAKDOWN MODAL
        $(document).on('click', '.card-clickable', function() {
            var type = $(this).data('card-type');
            var title = $(this).data('title');
            
            // Get the breakdown data from the hidden div populated by processedLeaveStats.php
            var breakdownDataElement = $('#processedLeaveStatsCards').find('#breakdownData');
            if (breakdownDataElement.length === 0 || !breakdownDataElement.attr('data-approved')) {
                // If data isn't loaded yet, show a warning and return
                Swal.fire('Loading Error', 'Please wait for the data to fully load before clicking.', 'warning');
                return;
            }

            var approvedData = JSON.parse(breakdownDataElement.attr('data-approved'));
            var rejectedData = JSON.parse(breakdownDataElement.attr('data-rejected'));

            var $modal = $('#processedBreakdownModal');
            $modal.find('.modal-title').text(title || 'Leave Type Breakdown');
            
            // Clear all breakdown containers first
            $modal.find('.breakdown-container').hide().find('tbody').empty();
            
            // Function to generate and append rows
            function appendRows(data, containerId, countClass) {
                var rows = [];
                Object.keys(data).forEach(function(leaveType) {
                    var count = parseInt(data[leaveType]) || 0;
                    if (count > 0) {
                        rows.push({ type: leaveType, count: count });
                    }
                });
                
                rows.sort(function(a, b) { return a.type.localeCompare(b.type); });
                
                rows.forEach(function(row) {
                    $modal.find(`#${containerId} tbody`).append(
                        '<tr>' +
                        '<td>' + row.type + '</td>' +
                        '<td class="text-end fw-bold ' + countClass + '">' + row.count + '</td>' +
                        '</tr>'
                    );
                });
                $modal.find(`#${containerId}`).show();
            }

            // Build the appropriate breakdown table
            if (type === 'processed') {
                var allTypes = new Set([...Object.keys(approvedData), ...Object.keys(rejectedData)]);
                var processedRows = [];
                
                allTypes.forEach(function(leaveType) {
                    var approved = parseInt(approvedData[leaveType]) || 0;
                    var rejected = parseInt(rejectedData[leaveType]) || 0;
                    var total = approved + rejected;
                    if (total > 0) {
                        processedRows.push({ type: leaveType, count: total });
                    }
                });
                
                processedRows.sort(function(a, b) { return a.type.localeCompare(b.type); });
                
                processedRows.forEach(function(row) {
                    $modal.find('#processed-breakdown tbody').append(
                        '<tr>' +
                        '<td>' + row.type + '</td>' +
                        '<td class="text-end fw-bold">' + row.count + '</td>' +
                        '</tr>'
                    );
                });
                
                $modal.find('#processed-breakdown').show();
            } else if (type === 'approved') {
                appendRows(approvedData, 'approved-breakdown', 'text-success');
            } else if (type === 'rejected') {
                appendRows(rejectedData, 'rejected-breakdown', 'text-danger');
            }

            var modal = new bootstrap.Modal(document.getElementById('processedBreakdownModal'));
            modal.show();
        });
        
    })
    </script>





    <div class="modal fade" id="viewProofModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Leave Proof</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="proofContainer"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectReasonModal" tabindex="-1" aria-labelledby="rejectReasonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectReasonModalLabel">
                    <i class="fas fa-times-circle me-2"></i> Rejection Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Reason for Rejection:</strong></p>
                <div class="alert alert-light border p-3 mt-2" id="modalRejectionReason" style="white-space: pre-wrap; word-break: break-word;">
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


  
    <div class="modal fade" id="processedBreakdownModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Leave Type Breakdown</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="processed-breakdown" class="breakdown-container">
                        <h6 class="text-center mb-3">Processed by Leave Type</h6>
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                    </div>

                    <div id="approved-breakdown" class="breakdown-container">
                        <h6 class="text-center mb-3">Approved by Leave Type</h6>
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                    </div>

                    <div id="rejected-breakdown" class="breakdown-container">
                        <h6 class="text-center mb-3">Rejected by Leave Type</h6>
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<!-- Student Leave History Modal -->
<div class="modal fade" id="studentHistoryModal" tabindex="-1" aria-labelledby="studentHistoryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="studentHistoryModalLabel">Student Leave History</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="studentHistoryContent">
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!--leave reject reason model in pending table-->
<div class="modal fade" id="leaveRejectModal" tabindex="-1" aria-labelledby="leaveRejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="leaveRejectModalLabel">Reject Leave Application</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Confirm to **REJECT** this leave application?</p>
                <div class="mb-3">
                    <label for="rejectionReason" class="form-label fw-bold">Rejection Reason <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="rejectionReason" rows="3" placeholder="Enter a mandatory reason for rejection..."></textarea>
                </div>
                <input type="hidden" id="leaveIdToReject"> 
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmReject">Reject Leave</button>
            </div>
        </div>
    </div>
</div>
<script>

function reloadAllTables() {
    console.log('Reloading all tables and stats...');

    // 1. Reload the Pending Table
    $.ajax({
        url: 'pendingTable.php',
        type: 'GET',
        success: function(data) {
            // Assume you have a container for the pending table (e.g., in a tab)
            $('#pendingTableContainer').html(data);
            // The initPendingDT() function is expected to run automatically from the loaded data
            // Since pendingTable.php contains the <script> block with initPendingDT()
        },
        error: function(xhr) {
            console.error("Error reloading Pending Table:", xhr.responseText);
            // Fallback: Display an error message
            $('#pendingTableContainer').html('<div class="alert alert-danger">Failed to load Pending Table.</div>');
        }
    });

    // 2. Reload the Processed Table
    $.ajax({
        url: 'processedTable.php',
        type: 'GET',
        success: function(data) {
            // Assume you have a container for the processed table (e.g., in another tab)
            $('#processedTableContainer').html(data);
            // The initProcessedDT() function is expected to run automatically from the loaded data
            // Since processedTable.php contains the <script> block with initProcessedDT()
        },
        error: function(xhr) {
            console.error("Error reloading Processed Table:", xhr.responseText);
            // Fallback: Display an error message
            $('#processedTableContainer').html('<div class="alert alert-danger">Failed to load Processed Table.</div>');
        }
    });

    // 3. Reload the Statistics (Optional, but highly recommended)
    // You likely have a stats component (leaveStats.php and processedLeaveStats.php)
    reloadStats(); 
}

// OPTIONAL: Function to reload the stats cards (needs to be defined in leave_approve.php)
function reloadStats() {
    // Reload Pending Stats (e.g., the top cards for Pending)
    $('#pendingStatsContainer').load('leaveStats.php');
    
    // Reload Processed Stats (e.g., the top cards for Approved/Rejected)
    $('#processedStatsContainer').load('processedLeaveStats.php');
}
// --- In leave_approve.php, inside a <script> block ---

// --- In leave_approve.php, inside a <script> block ---

// --- In leave_approve.php, inside a <script> block ---

$(document).on('click', '.student-history-btn', function(e) {
    e.preventDefault();

    // CRITICAL: Try the most reliable ways to read the data attribute
    let $button = $(this);
    // .data('regno') is the preferred jQuery way; .attr('data-regno') is the universal fallback.
    let reg_no = $button.data('regno') || $button.attr('data-regno'); 
    let name = $button.data('studentname') || 'Unknown Student'; 
    
    // This alert will show you what the browser is trying to send
    // alert("Attempting to send Reg No: " + reg_no); 
    
    if (!reg_no) {
        Swal.fire({
            icon: 'error',
            title: 'Client-Side Failure',
            text: 'The button did not contain a valid registration number. Check the generated HTML source for "data-regno".',
        });
        return;
    }

    // Modal setup...
    $('#studentHistoryModalLabel').text('Leave History for: ' + name + ' (' + reg_no + ')');
    $('#studentHistoryContent').html('<div class="text-muted text-center py-5"><i class="fas fa-spinner fa-spin me-2"></i>Fetching details...</div>');
    $('#studentHistoryModal').modal('show');


    // AJAX Call
    $.ajax({
        url: './tables/fetch_student_history.php', // Ensure this path is correct
        type: 'GET',
        dataType: 'html', // It must be 'html' if it outputs the table HTML/script
        data: { 
            // The key 'reg_no' MUST match the PHP check: $_GET['reg_no']
            reg_no: reg_no 
        },
        success: function(response) {
            $('#studentHistoryContent').html(response);
        },
        error: function(xhr, status, error) {
            $('#studentHistoryContent').html('<div class="text-danger py-5"><i class="fas fa-exclamation-triangle me-2"></i>AJAX Error: ' + status + '</div>');
        }
    });
});
</script>
<script>
    // In leave_approve.php, inside a <script> block
$(document).ready(function() {
    // Listener for when the #rejectReasonModal is about to be shown
    $('#rejectReasonModal').on('show.bs.modal', function (event) {
        // 1. Get the button that triggered the modal (the Rejected status button)
        var button = $(event.relatedTarget);
        
        // 2. Extract the rejection reason from the custom data-reason attribute
        // The .data() method automatically handles HTML escaping
        var rejectionReason = button.data('reason');
        
        // 3. Update the modal's content
        var modal = $(this);
        
        // Set the reason text
        // Use .text() for security against XSS, and to display raw text/line breaks correctly
        modal.find('#modalRejectionReason').text(rejectionReason);
    });
});
</script>

</body>

</html>