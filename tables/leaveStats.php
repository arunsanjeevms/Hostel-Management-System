<?php include __DIR__ . '/../db.php'; 

// Get counts for each leave type (pending status only)
$sql = "SELECT lt.Leave_Type_Name, lt.LeaveType_ID, COUNT(la.Leave_ID) as count 
        FROM leave_types lt
        LEFT JOIN leave_applications la ON lt.LeaveType_ID = la.LeaveType_ID 
            AND la.Status IN ('Pending', 'Forwarded to Admin')
        WHERE lt.LeaveType_ID <> 1
        GROUP BY lt.LeaveType_ID, lt.Leave_Type_Name
        ORDER BY lt.LeaveType_ID";

$result = mysqli_query($conn, $sql);

// Get total pending count
$totalSql = "SELECT COUNT(*) as total FROM leave_applications 
             WHERE Status IN ('Pending', 'Forwarded to Admin') 
             AND LeaveType_ID <> 1";
$totalResult = mysqli_query($conn, $totalSql);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalCount = $totalRow['total'];

// Define colors for each card
$colors = [
    'primary' => ['bg' => '#4e73df', 'icon' => '#2e59d9'],
    'success' => ['bg' => '#1cc88a', 'icon' => '#17a673'],
    'info' => ['bg' => '#36b9cc', 'icon' => '#2c9faf'],
    'warning' => ['bg' => '#faa319', 'icon' => '#ff891a'],
    'danger' => ['bg' => '#e94b58', 'icon' => '#de1f40'],
    'secondary'=>['bg' => '#e94b58', 'icon' => '#de1f40']
];

$colorKeys = array_keys($colors);
$colorIndex = 0;
?>

<div class="row mb-4">
    <!-- Total Pending Card -->
    <div class="col-xl col-lg-3 col-md-6 mb-4">
        <div class="gradient-card gradient-primary">
            <div class="card-body text-center">
                <div class="icon-container">
                    <i class="fas fa-clipboard-list text-white"></i>
                </div>
                <h4 class="card-title text-white">Total Pending</h4>
                <h2 class="card-value text-white font-weight-bold pulse-value"><?php echo $totalCount; ?></h2>
            </div>
        </div>
    </div>

    <?php
    // Define gradient classes for different leave types
    $gradients = ['success', 'info', 'warning', 'danger', 'secondary'];
    $gradientIndex = 0;
    
    while($row = mysqli_fetch_assoc($result)) {
        $gradientClass = $gradients[$gradientIndex % count($gradients)];
        $gradientIndex++;
        
        // Define icons for different leave types
        $icons = [
            'Medical Leave' => 'fa-user-doctor',
            'Emergency Leave' => 'fa-triangle-exclamation',
            'Leave' => 'fa-house',
            'On Duty' => 'fa-solid fa-book',
            'Outing' => 'fa-solid fa-suitcase'
        ];
        
        $icon = isset($icons[$row['Leave_Type_Name']]) ? $icons[$row['Leave_Type_Name']] : 'fa-file-alt';
    ?>
    
    <div class="col-xl col-lg-3 col-md-6 mb-4">
        <div class="gradient-card gradient-<?php echo $gradientClass; ?>">
            <div class="card-body text-center">
                <div class="icon-container">
                    <i class="fas <?php echo $icon; ?> text-white"></i>
                </div>
                <h4 class="card-title text-white"><?php echo htmlspecialchars($row['Leave_Type_Name']); ?></h4>
                <h2 class="card-value text-white font-weight-bold pulse-value"><?php echo $row['count']; ?></h2>
            </div>
        </div>
    </div>
    
    <?php } ?>
</div>

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
