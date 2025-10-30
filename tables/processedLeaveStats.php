<?php include __DIR__ . '/../db.php';

// Get total processed count
$totalSql = "SELECT COUNT(*) as total FROM leave_applications 
             WHERE Status IN ('Rejected by HOD','Rejected by Admin','Rejected by Parents','Approved')";
$totalResult = mysqli_query($conn, $totalSql);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalCount = $totalRow['total'];

// Get approved count by leave type
$approvedSql = "SELECT lt.Leave_Type_Name, lt.LeaveType_ID, COUNT(la.Leave_ID) as count 
                FROM leave_types lt
                LEFT JOIN leave_applications la ON lt.LeaveType_ID = la.LeaveType_ID 
                    AND la.Status = 'Approved'
                GROUP BY lt.LeaveType_ID, lt.Leave_Type_Name
                ORDER BY lt.LeaveType_ID";
$approvedResult = mysqli_query($conn, $approvedSql);

// Get rejected count by leave type
$rejectedSql = "SELECT lt.Leave_Type_Name, lt.LeaveType_ID, COUNT(la.Leave_ID) as count 
                FROM leave_types lt
                LEFT JOIN leave_applications la ON lt.LeaveType_ID = la.LeaveType_ID 
                    AND la.Status IN ('Rejected by HOD','Rejected by Admin','Rejected by Parents')
                GROUP BY lt.LeaveType_ID, lt.Leave_Type_Name
                ORDER BY lt.LeaveType_ID";
$rejectedResult = mysqli_query($conn, $rejectedSql);

// Get total approved and rejected counts
$totalApprovedSql = "SELECT COUNT(*) as total FROM leave_applications WHERE Status = 'Approved'";
$totalApprovedResult = mysqli_query($conn, $totalApprovedSql);
$totalApprovedRow = mysqli_fetch_assoc($totalApprovedResult);
$totalApprovedCount = $totalApprovedRow['total'];

$totalRejectedSql = "SELECT COUNT(*) as total FROM leave_applications 
                     WHERE Status IN ('Rejected by HOD','Rejected by Admin','Rejected by Parents')";
$totalRejectedResult = mysqli_query($conn, $totalRejectedSql);
$totalRejectedRow = mysqli_fetch_assoc($totalRejectedResult);
$totalRejectedCount = $totalRejectedRow['total'];

// Store approved and rejected counts by leave type
$approvedCounts = [];
$rejectedCounts = [];

while($row = mysqli_fetch_assoc($approvedResult)) {
    $approvedCounts[$row['Leave_Type_Name']] = $row['count'];
}

while($row = mysqli_fetch_assoc($rejectedResult)) {
    $rejectedCounts[$row['Leave_Type_Name']] = $row['count'];
}

// Get all leave types
$leaveTypesSql = "SELECT Leave_Type_Name, LeaveType_ID FROM leave_types ORDER BY LeaveType_ID";
$leaveTypesResult = mysqli_query($conn, $leaveTypesSql);
$leaveTypes = [];
while($row = mysqli_fetch_assoc($leaveTypesResult)) {
    $leaveTypes[] = $row['Leave_Type_Name'];
}

// Define colors for each card
$colors = ['success', 'info', 'warning', 'danger', 'primary'];
$colorIndex = 0;

// Define icons for different leave types
$icons = [
    'Medical Leave' => 'fa-user-doctor',
    'Emergency Leave' => 'fa-triangle-exclamation',
    'Home Leave' => 'fa-house',
    'General Leave' => 'fa-calendar-days',
    'IVR Leave' => 'fa-phone',
];
?>

<!-- Store data for modal as JSON in hidden div -->
<div id="breakdownData" style="display:none;" 
     data-approved='<?php echo json_encode($approvedCounts); ?>'
     data-rejected='<?php echo json_encode($rejectedCounts); ?>'>
</div>

<div class="row mb-4">
    <!-- Total Processed Card -->
    <div class="col-xl col-lg-3 col-md-6 mb-4">
        <div class="gradient-card gradient-primary card-clickable" data-card-type="processed" data-title="Total Processed Breakdown">
            <div class="card-body text-center">
                <div class="icon-container">
                    <i class="fas fa-tasks text-white"></i>
                </div>
                <h4 class="card-title text-white">Total Processed</h4>
                <h2 class="card-value text-white font-weight-bold pulse-value"><?php echo $totalCount; ?></h2>
            </div>
        </div>
    </div>

    <!-- Total Approved Card -->
    <div class="col-xl col-lg-3 col-md-6 mb-4">
        <div class="gradient-card gradient-success card-clickable" data-card-type="approved" data-title="Total Approved Breakdown">
            <div class="card-body text-center">
                <div class="icon-container">
                    <i class="fas fa-check-circle text-white"></i>
                </div>
                <h4 class="card-title text-white">Total Approved</h4>
                <h2 class="card-value text-white font-weight-bold pulse-value"><?php echo $totalApprovedCount; ?></h2>
            </div>
        </div>
    </div>

    <!-- Total Rejected Card -->
    <div class="col-xl col-lg-3 col-md-6 mb-4">
        <div class="gradient-card gradient-danger card-clickable" data-card-type="rejected" data-title="Total Rejected Breakdown">
            <div class="card-body text-center">
                <div class="icon-container">
                    <i class="fas fa-times-circle text-white"></i>
                </div>
                <h4 class="card-title text-white">Total Rejected</h4>
                <h2 class="card-value text-white font-weight-bold pulse-value"><?php echo $totalRejectedCount; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Processed Breakdown Modal moved to leave_approve.php -->


<style>
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
    position: relative; /* needed for decorative corners */
}

.gradient-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
}

/* Decorative tilted corners like the template */
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

/* Dark top-right corner */
.gradient-card::before {
    top: -95px;
    right: -95px;
    width: 140px;
    height: 140px;
    background: rgba(0, 0, 0, 0.06);
   
}

/* Soft bottom-left sweep */
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
    z-index: 1; /* keep content above decorative corners */
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

/* Pulse Animation */
@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

.pulse-value {
    animation: pulse 2s infinite;
}

/* Click behavior hint */
.card-clickable { cursor: pointer; }

/* Responsive adjustments */
@media (max-width: 768px) {
    .gradient-card .card-value {
        font-size: 1.1rem;
    }
    
    .gradient-card .card-title {
        font-size: 1rem;
    }
    
    .gradient-card .icon-container {
        font-size: 30px;
    }
}
</style>

<script>
// Show modal with correct breakdown based on clicked card
$(document).ready(function() {
    $(document).on('click', '.card-clickable', function() {
        var type = $(this).data('card-type');
        var title = $(this).data('title');
        
        // Get the breakdown data from hidden div
        var approvedData = JSON.parse($('#breakdownData').attr('data-approved'));
        var rejectedData = JSON.parse($('#breakdownData').attr('data-rejected'));

        var $modal = $('#processedBreakdownModal');
        $modal.find('.modal-title').text(title || 'Leave Type Breakdown');
        
        // Clear all breakdown containers first
        $modal.find('#processed-breakdown tbody').empty();
        $modal.find('#approved-breakdown tbody').empty();
        $modal.find('#rejected-breakdown tbody').empty();
        
        // Build the appropriate breakdown table
        if (type === 'processed') {
            // Combine approved and rejected for total processed
            var allTypes = new Set([...Object.keys(approvedData), ...Object.keys(rejectedData)]);
            var processedRows = [];
            
            allTypes.forEach(function(leaveType) {
                var approved = parseInt(approvedData[leaveType]) || 0;
                var rejected = parseInt(rejectedData[leaveType]) || 0;
                var total = approved + rejected;
                if (total > 0) {
                    processedRows.push({
                        type: leaveType,
                        count: total
                    });
                }
            });
            
            // Sort by leave type name for consistency
            processedRows.sort(function(a, b) {
                return a.type.localeCompare(b.type);
            });
            
            // Append sorted rows
            processedRows.forEach(function(row) {
                $modal.find('#processed-breakdown tbody').append(
                    '<tr>' +
                    '<td>' + row.type + '</td>' +
                    '<td class="text-end fw-bold">' + row.count + '</td>' +
                    '</tr>'
                );
            });
            
            $modal.find('#processed-breakdown').show();
            $modal.find('#approved-breakdown').hide();
            $modal.find('#rejected-breakdown').hide();
        } else if (type === 'approved') {
            // Show only approved
            var approvedRows = [];
            Object.keys(approvedData).forEach(function(leaveType) {
                var count = parseInt(approvedData[leaveType]) || 0;
                if (count > 0) {
                    approvedRows.push({
                        type: leaveType,
                        count: count
                    });
                }
            });
            
            // Sort by leave type name
            approvedRows.sort(function(a, b) {
                return a.type.localeCompare(b.type);
            });
            
            // Append sorted rows
            approvedRows.forEach(function(row) {
                $modal.find('#approved-breakdown tbody').append(
                    '<tr>' +
                    '<td>' + row.type + '</td>' +
                    '<td class="text-end fw-bold text-success">' + row.count + '</td>' +
                    '</tr>'
                );
            });
            
            $modal.find('#approved-breakdown').show();
            $modal.find('#processed-breakdown').hide();
            $modal.find('#rejected-breakdown').hide();
        } else if (type === 'rejected') {
            // Show only rejected
            var rejectedRows = [];
            Object.keys(rejectedData).forEach(function(leaveType) {
                var count = parseInt(rejectedData[leaveType]) || 0;
                if (count > 0) {
                    rejectedRows.push({
                        type: leaveType,
                        count: count
                    });
                }
            });
            
            // Sort by leave type name
            rejectedRows.sort(function(a, b) {
                return a.type.localeCompare(b.type);
            });
            
            // Append sorted rows
            rejectedRows.forEach(function(row) {
                $modal.find('#rejected-breakdown tbody').append(
                    '<tr>' +
                    '<td>' + row.type + '</td>' +
                    '<td class="text-end fw-bold text-danger">' + row.count + '</td>' +
                    '</tr>'
                );
            });
            
            $modal.find('#rejected-breakdown').show();
            $modal.find('#processed-breakdown').hide();
            $modal.find('#approved-breakdown').hide();
        }

        var modal = new bootstrap.Modal(document.getElementById('processedBreakdownModal'));
        modal.show();
    });
});
</script>
