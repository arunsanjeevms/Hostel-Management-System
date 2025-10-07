<!DOCTYPE html>
<html lang="en">

<head>

    <?php include 'db.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Management</title>
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


    <style>
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

    /* General Styles with Enhanced Typography */

    /* Content Area Styles */
    .content {
        margin-left: var(--sidebar-width);
        padding-top: var(--topbar-height);
        transition: all 0.3s ease;
        min-height: 100vh;
    }

    /* Content Navigation */
    .content-nav {
        background: linear-gradient(45deg, #4e73df, #1cc88a);
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .content-nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        gap: 20px;
        overflow-x: auto;
    }

    .content-nav li a {
        color: white;
        text-decoration: none;
        padding: 8px 15px;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .content-nav li a:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .sidebar.collapsed+.content {
        margin-left: var(--sidebar-collapsed-width);
    }

    .breadcrumb-area {
        background: white;
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



    /* Table Styles */



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
        /* For vertical alignment */
    }

    /* Responsive Styles */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            width: var(--sidebar-width) !important;
        }

        .sidebar.mobile-show {
            transform: translateX(0);
        }

        .topbar {
            left: 0 !important;
        }

        .mobile-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .mobile-overlay.show {
            display: block;
        }

        .content {
            margin-left: 0 !important;
        }

        .brand-logo {
            display: block;
        }

        .user-profile {
            margin-left: 0;
        }

        .sidebar .logo {
            justify-content: center;
        }

        .sidebar .menu-item span,
        .sidebar .has-submenu::after {
            display: block !important;
        }

        body.sidebar-open {
            overflow: hidden;
        }

        .footer {
            left: 0 !important;
        }

        .content-nav ul {
            flex-wrap: nowrap;
            overflow-x: auto;
            padding-bottom: 5px;
        }

        .content-nav ul::-webkit-scrollbar {
            height: 4px;
        }

        .content-nav ul::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
        }
    }

    .container-fluid {
        padding: 20px;
    }


    /* loader */
    .loader-container {
        position: fixed;
        left: var(--sidebar-width);
        right: 0;
        top: var(--topbar-height);
        bottom: var(--footer-height);
        background: rgba(255, 255, 255, 0.95);
        display: flex;
        /* Changed from 'none' to show by default */
        justify-content: center;
        align-items: center;
        z-index: 1000;
        transition: left 0.3s ease;
    }

    .sidebar.collapsed+.content .loader-container {
        left: var(--sidebar-collapsed-width);
    }

    @media (max-width: 768px) {
        .loader-container {
            left: 0;
        }
    }

    /* Hide loader when done */
    .loader-container.hide {
        display: none;
    }

    /* Loader Animation */
    .loader {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-radius: 50%;
        border-top: 5px solid var(--primary-color);
        border-right: 5px solid var(--success-color);
        border-bottom: 5px solid var(--primary-color);
        border-left: 5px solid var(--success-color);
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
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



    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }

    /* Hide default HTML checkbox */
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    /* The slider */
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }

    input:checked+.slider {
        background-color: #2196F3;
    }

    input:focus+.slider {
        box-shadow: 0 0 1px #2196F3;
    }

    input:checked+.slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }

    /* Rounded sliders */
    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }
    </style>
</head>

<body>


    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="content">

        <div class="loader-container" id="loaderContainer">
            <div class="loader"></div>
        </div>

        <!-- Topbar -->
        <?php include 'topbar.php'; ?>

        <!-- Breadcrumb -->
        <div class="breadcrumb-area custom-gradient">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">General Leave Management</li>
                </ol>
            </nav>
        </div>

        <!-- Content Area -->
        <div class="container-fluid">
            <div class="custom-tabs">
                <ul class="nav nav-tabs" role="tablist">
                    <!-- Center the main tabs -->
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" data-bs-toggle="tab" id="family-main-tab" href="#enableLeave-content"
                            role="tab" aria-selected="true">
                            <span class="hidden-xs-down" style="font-size: 0.9em;"><i
                                    class="fas fa-repeat tab-icon"></i>
                                Enable / Disable Leave </span>
                        </a>
                    </li>

                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" id="processed-main-tab" href="#processed-content"
                            role="tab" aria-selected="false">
                            <span class="hidden-xs-down" style="font-size: 0.9em;"><i
                                    class="fas fa-clock-rotate-left tab-icon"></i> History </span>
                        </a>
                    </li>
                </ul>


                <div class="tab-content mt-3">
                    <!-- enableLeave Tab Content -->
                    <div class="tab-pane fade show active" id="enableLeave-content" role="tabpanel"
                        aria-labelledby="family-main-tab">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <center>
                                    <h6 class="m-0 font-weight-bold">Enable / Disable General Leave</h6>
                                </center>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" id="enableLeaveTable">
                                    <?php
                                    // Check if there's an active/enabled leave
                                    $sql = "SELECT * FROM generalLeave WHERE status = 'Enabled' ORDER BY id DESC LIMIT 1";
                                    $result = mysqli_query($conn, $sql);
                                    $activeLeave = mysqli_fetch_assoc($result);
                                    ?>
                                    
                                    <div class="text-center">
                                        <?php if ($activeLeave): ?>
                                            <!-- Show current active leave info and disable button -->
                                            <div class="alert alert-success mb-3" role="alert">
                                                <h5 class="alert-heading"><i class="fas fa-calendar-check me-2"></i>Active Leave Period</h5>
                                                <p class="mb-2"><strong>Leave Name:</strong> <?php echo htmlspecialchars($activeLeave['leave_name']); ?></p>
                                                <p class="mb-2"><strong>From:</strong> <?php echo date('d-m-Y h:i A', strtotime($activeLeave['from_date'])); ?></p>
                                                <p class="mb-2"><strong>To:</strong> <?php echo date('d-m-Y h:i A', strtotime($activeLeave['to_date'])); ?></p>
                                                <?php if (!empty($activeLeave['instructions'])): ?>
                                                    <p class="mb-0"><strong>Instructions:</strong> <?php echo htmlspecialchars($activeLeave['instructions']); ?></p>
                                                <?php endif; ?>
                                                <hr>
                                                <p class="mb-0 text-muted">Students can currently apply for leave during this period.</p>
                                            </div>
                                            <button type="button" class="btn btn-danger" id="disableLeaveBtn" 
                                                    data-leave-id="<?php echo $activeLeave['id']; ?>">
                                                <i class="fas fa-times-circle me-1"></i>Disable Leave
                                            </button>
                                        <?php else: ?>
                                            <!-- Show enable button when no active leave -->
                                            <div class="alert alert-info mb-3" role="alert">
                                                <i class="fas fa-info-circle me-2"></i>
                                                No active leave period. Click the button below to enable general leave for students.
                                            </div>
                                            <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                                   id="enableLeaveBtn" data-bs-target="#leaveModal">
                                                <i class="fas fa-calendar-plus me-1"></i>Enable Leave
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                   <!-- History Tab Content -->
                    <div class="tab-pane fade" id="processed-content" role="tabpanel"
                        aria-labelledby="processed-main-tab">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <center>
                                    <h6 class="m-0 font-weight-bold">General Leave History</h6>
                                </center>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" id="processedTable">
                                   <!-- Table for processed leave history -->
                                </div>
                            </div>
                        </div> 

                </div>
            </div>
        </div>

    </div>
    </div>
    </div>

    <!--Leave Modal-->
    <div class="modal fade" id="leaveModal" tabindex="-1" aria-labelledby="leaveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="leaveModalLabel">
                        Enable General Leave
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="leaveForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="leaveName" class="form-label">
                                    Leave Name 
                                </label>
                                <input type="text" class="form-control" id="leaveName" name="leave_name"
                                    placeholder="Enter Leave name" required>

                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fromDateTime" class="form-label">
                                    From Date & Time 
                                </label>
                                <input type="datetime-local" class="form-control" id="fromDateTime" name="from_date"
                                    required>

                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="toDateTime" class="form-label">
                                    To Date & Time 
                                </label>
                                <input type="datetime-local" class="form-control" id="toDateTime" name="to_date"
                                    required>

                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="leaveDescription" class="form-label">
                                    Insturctions
                                </label>
                                <textarea class="form-control" id="leaveDescription" name="description" rows="1"
                                    placeholder="Enter Instructions / Description"></textarea>
                            </div>
                        </div>

                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> Once enabled, students will be able to apply for General leave during
                            the specified period.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-1"></i> Enable Leave Period
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

 

    <!-- Footer -->
    <?php include 'footer.php'; ?>
    </div>
    <script>
    const loaderContainer = document.getElementById('loaderContainer');

    function showLoader() {
        loaderContainer.classList.add('show');
    }

    function hideLoader() {
        loaderContainer.classList.remove('show');
    }

    //    automatic loader
    document.addEventListener('DOMContentLoaded', function() {
        const loaderContainer = document.getElementById('loaderContainer');
        const contentWrapper = document.getElementById('contentWrapper');
        let loadingTimeout;

        function hideLoader() {
            loaderContainer.classList.add('hide');
            contentWrapper.classList.add('show');
        }

        function showError() {
            console.error('Page load took too long or encountered an error');
            // You can add custom error handling here
        }

        // Set a maximum loading time (10 seconds)
        loadingTimeout = setTimeout(showError, 10000);

        // Hide loader when everything is loaded
        window.onload = function() {
            clearTimeout(loadingTimeout);

            // Add a small delay to ensure smooth transition
            setTimeout(hideLoader, 500);
        };

        // Error handling
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            clearTimeout(loadingTimeout);
            showError();
            return false;
        };
    });

    // Toggle Sidebar
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    const body = document.body;
    const mobileOverlay = document.getElementById('mobileOverlay');

    function toggleSidebar() {
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('mobile-show');
            mobileOverlay.classList.toggle('show');
            body.classList.toggle('sidebar-open');
        } else {
            sidebar.classList.toggle('collapsed');
        }
    }
    hamburger.addEventListener('click', toggleSidebar);
    mobileOverlay.addEventListener('click', toggleSidebar);
    // Toggle User Menu
    const userMenu = document.getElementById('userMenu');
    const dropdownMenu = userMenu.querySelector('.dropdown-menu');

    userMenu.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdownMenu.classList.toggle('show');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', () => {
        dropdownMenu.classList.remove('show');
    });

    // Toggle Submenu
    const menuItems = document.querySelectorAll('.has-submenu');
    menuItems.forEach(item => {
        item.addEventListener('click', () => {
            const submenu = item.nextElementSibling;
            item.classList.toggle('active');
            submenu.classList.toggle('active');
        });
    });

    // Handle responsive behavior
    window.addEventListener('resize', () => {
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('collapsed');
            sidebar.classList.remove('mobile-show');
            mobileOverlay.classList.remove('show');
            body.classList.remove('sidebar-open');
        } else {
            sidebar.style.transform = '';
            mobileOverlay.classList.remove('show');
            body.classList.remove('sidebar-open');
        }
    });
 

    </script>

    <!--My Scripts-->


    <script>
    $(document).ready(function() {

        $("#processedTable").load("./tables/generalLeaveTable.php");

    });

    // Handle Disable Leave Button (if alrdy enabled)
    $(document).on('click', '#disableLeaveBtn', function() {
        const leaveId = $(this).data('leave-id');
        
        Swal.fire({
            title: 'Disable Leave Period?',
            text: 'This will prevent students from applying for leave. Are you sure?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Disable It',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'xxxxx.php',
                    type: 'POST',
                    data: {
                        leave_id: leaveId,
                        action: 'disable'
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Leave period has been disabled successfully.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload(); // Refresh the page to update the UI
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message || 'Failed to disable leave period.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while processing the request.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    });

    // Leave Form Validation
    document.getElementById('leaveForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const leaveName = document.getElementById('leaveName').value.trim();
        const fromDateTime = document.getElementById('fromDateTime').value;
        const toDateTime = document.getElementById('toDateTime').value;
        const instructions = document.getElementById('leaveDescription').value.trim();
        
        // Validate required fields
        if (!leaveName || !fromDateTime || !toDateTime) {
            Swal.fire({
                title: 'Validation Error!',
                text: 'Please fill all required fields.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        // Validate date range
        const fromDate = new Date(fromDateTime);
        const toDate = new Date(toDateTime);
        const currentDate = new Date();
        
        if (fromDate >= toDate) {
            Swal.fire({
                title: 'Invalid Date Range!',
                text: 'From date must be earlier than To date.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        // Show confirmation dialog
        Swal.fire({
            title: 'Confirm Leave Enable',
            html: `
                <div class="text-start">
                    <strong>Leave Name:</strong> ${leaveName}<br>
                    <strong>From:</strong> ${new Date(fromDateTime).toLocaleString()}<br>
                    <strong>To:</strong> ${new Date(toDateTime).toLocaleString()}<br>
                    ${instructions ? `<strong>Instructions:</strong> ${instructions}` : ''}
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Enable Leave',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit via AJAX
                $.ajax({
                    url: 'xxxxxxx',
                    type: 'POST',
                    data: {
                        leave_name: leaveName,
                        from_date: fromDateTime,
                        to_date: toDateTime,
                        instructions: instructions,
                        action: 'enable'
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Leave period has been enabled successfully.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                $('#leaveModal').modal('hide');
                                location.reload(); // Refresh the page to update the UI
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message || 'Failed to enable leave period.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while processing the request.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    });
    
    // Set minimum date to current date for from date
    document.addEventListener('DOMContentLoaded', function() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const currentDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
        
        document.getElementById('fromDateTime').min = currentDateTime;
        
        // Update to date minimum when from date changes
        document.getElementById('fromDateTime').addEventListener('change', function() {
            document.getElementById('toDateTime').min = this.value;
        });
    });
    </script>
    
</body>

</html>