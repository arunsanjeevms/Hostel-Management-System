<!DOCTYPE html>
<html lang="en">

<head>

    <?php include 'db.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIC - Student</title>
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
                    <li class="breadcrumb-item active" aria-current="page">Leave</li>
                </ol>
            </nav>
        </div>

        <!-- Content Area -->
        <div class="container-fluid">
            <div class="custom-tabs">
                <ul class="nav nav-tabs" role="tablist">
                    <!-- Center the main tabs -->
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" data-bs-toggle="tab" id="family-main-tab" href="#academics-content"
                            role="tab" aria-selected="true">
                            <span class="hidden-xs-down" style="font-size: 0.9em;"><i class="fas fa-clock-rotate-left tab-icon"></i>
                                Pending </span>
                        </a>
                    </li>

                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" id="family-main-tab" href="#leave-content" role="tab"
                            aria-selected="false">
                            <span class="hidden-xs-down" style="font-size: 0.9em;"><i
                                    class="fas fa-plane-departure tab-icon"></i> Approved </span>
                        </a>
                    </li>

                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" id="family-main-tab" href="#family-content" role="tab"
                            aria-selected="false">
                            <span class="hidden-xs-down" style="font-size: 0.9em;"><i
                                    class="fas fa-people-roof tab-icon"></i> Rejected</span>
                        </a>
                    </li>
                </ul>
                <div class="tab-content mt-3">
                    <!-- Academics Tab Content -->
                    <div class="tab-pane fade show active" id="academics-content" role="tabpanel"
                        aria-labelledby="academics-tab">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <center>
                                    <h6 class="m-0 font-weight-bold">Academic Information</h6>
                                </center>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" id="academicsTable">
                                    <!-- Academics Table Content from table/academicsTable.php -->
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center my-3">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                data-bs-target="#staticBackdrop1">
                                Add Academic Details
                            </button>
                        </div>
                    </div>

                    <!-- Leave/OD Tab Content -->
                    <div class="tab-pane fade" id="leave-content" role="tabpanel" aria-labelledby="leave-tab">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <center>
                                    <h6 class="m-0 font-weight-bold">Leave / OD Information</h6>
                                </center>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" id="leaveTable">
                                    <!-- Leave Table Content from table/leaveTable.php -->
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-center my-3">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                data-bs-target="#staticBackdrop2">
                                Add Leave / OD Details
                            </button>
                        </div>
                    </div>

                    <!-- Family Tab Content -->
                    <div class="tab-pane fade" id="family-content" role="tabpanel" aria-labelledby="family-tab">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <center>
                                    <h6 class="m-0 font-weight-bold">Family Members Information</h6>
                                </center>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" id="familyTable">
                                    <!-- Leave Table Content from table/leaveTable.php -->
                                </div>
                            </div>


                        </div>
                        <div class="d-flex justify-content-center my-3">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                data-bs-target="#staticBackdrop3">
                                Add Family Member Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    </div>
    </div>

    <!-- Insert Modals-->

    <!--Academic insert Modal-->

    <div class="modal fade" id="staticBackdrop1" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel">Academic Details</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">


                    <form id="academicDetailsForm">
                        <div class="mb-3">
                            <label for="course" class="form-label">Course</label>
                            <input type="text" class="form-control" id="course" name="course" required>
                        </div>
                        <div class="mb-3">
                            <label for="institution" class="form-label">Institution</label>
                            <input type="text" class="form-control" id="institution" name="institution" required>
                        </div>
                        <div class="mb-3">
                            <label for="board" class="form-label">Board</label>
                            <input type="text" class="form-control" id="board" name="board" required>
                        </div>
                        <div class="mb-3">
                            <label for="year" class="form-label">Year</label>
                            <input type="number" class="form-control" id="year" name="year" min="1900" max="2099"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="percentage" class="form-label">Percentage</label>
                            <input type="number" class="form-control" id="percentage" name="percentage" min="0"
                                max="100" step="0.01" required>
                        </div>
                    </form>


                </div>
                <div class="modal-footer">


                    <input type="hidden" id="academicId" name="academicId" value="0">

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="academicConfirm">Confirm</button>
                </div>
            </div>
        </div>
    </div>


    <!--leave Insert Modal-->


    <div class="modal fade" id="staticBackdrop2" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel">Leave / OD Details</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="leaveDetailsForm">
                        <div class="mb-3">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">Select Type</option>
                                <option value="Leave">Leave</option>
                                <option value="OD">OD</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" required>
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason</label>
                            <input type="text" class="form-control" id="reason" name="reason" required>
                        </div>
                        <div class="mb-3">
                            <label for="proof" class="form-label">Proof</label>
                            <input type="file" class="form-control" id="proof" name="proof"
                                accept=".jpg,.jpeg,.png,.pdf" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">

                    <input type="hidden" id="leaveId" name="leaveId" value="0">

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="leaveConfirm">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!--family insert Modal-->

    <div class="modal fade" id="staticBackdrop3" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel">Family Details</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="familyDetailsForm">
                        <div class="mb-3">
                            <label for="Name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="Gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="Relationship" class="form-label">Relationship</label>
                            <select class="form-select" id="relationship" name="relationship" required>
                                <option value="">Select Relationship</option>
                                <option value="Father">Father</option>
                                <option value="Mother">Mother</option>
                                <option value="Brother">Brother</option>
                                <option value="Sister">Sister</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="Mobile" class="form-label">Mobile</label>
                            <input type="tel" class="form-control" id="mobile" name="mobile" pattern="[0-9]{10}"
                                required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">

                    <input type="hidden" id="familyId" name="familyId" value="0">

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="familyConfirm">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modals-->

    <!--Academic edit Modals-->

    <div class="modal fade academicmodel_edit" id="staticBackdrop_edit1" data-bs-backdrop="static"
        data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel"> Edit Academic Details</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">


                    <form id="academicDetailsForm_edit">
                        <div class="mb-3">
                            <label for="course" class="form-label">Course</label>
                            <input type="text" class="form-control" id="edit_course" name="course" required>
                        </div>
                        <div class="mb-3">
                            <label for="institution" class="form-label">Institution</label>
                            <input type="text" class="form-control" id="edit_institution" name="institution" required>
                        </div>
                        <div class="mb-3">
                            <label for="board" class="form-label">Board</label>
                            <input type="text" class="form-control" id="edit_board" name="board" required>
                        </div>
                        <div class="mb-3">
                            <label for="year" class="form-label">Year</label>
                            <input type="number" class="form-control" id="edit_year" name="year" min="1900" max="2099"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="percentage" class="form-label">Percentage</label>
                            <input type="number" class="form-control" id="edit_percentage" name="percentage" min="0"
                                max="100" step="0.01" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <input type="hidden" id="academicId" name="academicId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="edit_academicConfirm">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!--leave edit Modals-->

    <div class="modal fade" id="staticBackdrop_edit2" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel">Leave / OD Details</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="leaveDetailsForm_edit">
                        <div class="mb-3">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-select" id="edit_type" name="type" required>
                                <option value="">Select Type</option>
                                <option value="Leave">Leave</option>
                                <option value="OD">OD</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="edit_date" name="date" required>
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason</label>
                            <input type="text" class="form-control" id="edit_reason" name="reason" required>
                        </div>
                        <div class="mb-3">
                            <label for="proof" class="form-label">Proof <small class="text-muted">(Upload new proof to
                                    replace existing)</small></label>
                            <input type="file" class="form-control" id="edit_proof" name="proof"
                                accept=".jpg,.jpeg,.png,.pdf">
                            <small class="text-muted">Leave empty to keep existing proof file</small>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">

                    <input type="hidden" id="leaveId" name="leaveId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="edit_leaveConfirm">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!--family edit Modals-->

    <div class="modal fade" id="staticBackdrop_edit3" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel">Family Details</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="familyDetailsForm_edit">
                        <div class="mb-3">
                            <label for="Name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="Gender" class="form-label">Gender</label>
                            <select class="form-select" id="edit_gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="Relationship" class="form-label">Relationship</label>
                            <select class="form-select" id="edit_relation" name="relation" required>
                                <option value="">Select Relationship</option>
                                <option value="Father">Father</option>
                                <option value="Mother">Mother</option>
                                <option value="Brother">Brother</option>
                                <option value="Sister">Sister</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="Mobile" class="form-label">Mobile</label>
                            <input type="tel" class="form-control" id="edit_mobile" name="mobile" pattern="[0-9]{10}"
                                required>
                            <span id="phoneError" class="error"></span><br>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">

                    <input type="hidden" id="familyId" name="familyId">

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="edit_familyConfirm">Confirm</button>
                </div>
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

    // Initialize DataTables for all tables
    $(document).ready(function() {
        $('#academics-table').DataTable({
            responsive: true
        });
        $('#leave-table').DataTable({
            responsive: true
        });
        $('#family-table').DataTable({
            responsive: true
        });
    });
    </script>

    <!--My Scripts-->

    <script>
    $(document).ready(function() {

        setInterval(function() {
            $("#academicsTable").load("./tables/academicsTable.php");
            $("#leaveTable").load("./tables/leaveTable.php");
            $("#familyTable").load("./tables/familyTable.php");
        }, 500);

        // Academic Details Form Submission
        $('#academicConfirm').click(function() {

            var academicId = $(this).data('id');
            var academicId = $('#academicId').val();

            console.log("Inserting in Academic Confirm");

            $.ajax({
                url: './cruds/insert.php',
                type: 'post',
                data: $("#academicDetailsForm").serialize() + "&action=academic",
                dataType: 'json',
                success: function(r) {
                    console.log(r);

                    if (r.status === "success") {
                        Swal.fire({
                            title: "Academic Info added Successfully",
                            icon: "success",
                            draggable: true,
                            timer: 3000
                        });
                        console.log("Data inserted successfully  " + r.status);
                        $('#academicDetailsForm')[0].reset();
                        $('#staticBackdrop1').modal('hide');
                        $("#academicsTable").load("./tables/academicsTable.php");
                    } else if (r.success === false) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: r.message
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: "Error inserting data: " + r.message
                        });
                    }
                },

            });
        });

        // Leave/OD Details Form Submission
        $('#leaveConfirm').click(function() {
            console.log("Clicked Leave Confirm");

            let formData = new FormData($("#leaveDetailsForm")[0]);
            formData.append("action", "leave");

            $.ajax({
                url: './cruds/insert.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(r) {
                    console.log(r);

                    if (r.status == "success") {

                        Swal.fire({
                            title: "Leave/OD Info added Successfully",
                            icon: "success",
                            draggable: true,
                            timer: 3000
                        });
                        console.log("Data inserted successfully  " + r.status);
                        $('#leaveDetailsForm')[0].reset();
                        $('#staticBackdrop2').modal('hide');
                        $("#leaveTable").load("./tables/leaveTable.php");


                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: r.message
                        });
                        console.log("Error inserting data: " + r.message);
                    }
                }
            });
        });


        // Family Details Form Submission
        $('#familyConfirm').click(function() {
            console.log("Clicked Family Confirm");

            $.ajax({
                url: './cruds/insert.php',
                type: 'post',
                data: $("#familyDetailsForm").serialize() + "&action=family",
                dataType: 'json',
                success: function(r) {
                    console.log(r);

                    if (r.status === "success") {
                        Swal.fire({
                            title: "Family Info added Successfully",
                            icon: "success",
                            draggable: true,
                            timer: 3000
                        });
                        console.log("Data inserted successfully  " + r.status);
                        $('#familyDetailsForm')[0].reset();
                        $('#staticBackdrop3').modal('hide');
                        $("#familyTable").load("./tables/familyTable.php");
                    } else if (r.success === false) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: r.message
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: "Error inserting data: " + r.message
                        });
                    }
                },

            });
        });

        // delete academic
        $(document).on("click", ".del_academic", function() {
            var id = $(this).data("id");

            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {

                    $.ajax({
                        url: './cruds/delete.php',
                        type: 'post',
                        data: {
                            id: id,
                            table: 'academics'
                        },
                        dataType: 'json',
                        success: function(r) {
                            console.log(r);

                            if (r.status === "success") {
                                console.log("Data deleted successfully  " + r
                                    .status);
                            } else {
                                console.log("Error deleting data: " + r.message);
                            }

                            $("#academicsTable").load(
                                "./tables/academicsTable.php");
                        },

                    });
                    console.log("Deleted Academic ID: " + id);

                    Swal.fire({
                        title: "Deleted!",
                        text: "Your file has been deleted.",
                        icon: "success"
                    });
                }
            });
        });

        // delete leave

        $(document).on("click", ".del_leave", function() {
            var id = $(this).data("id");

            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: './cruds/delete.php',
                        type: 'post',
                        data: {
                            id: id,
                            table: 'absent'
                        },
                        dataType: 'json',
                        success: function(r) {
                            console.log(r);

                            if (r.status === "success") {
                                console.log("Data deleted successfully  " + r
                                    .status);
                            } else {
                                console.log("Error deleting data: " + r.message);
                            }

                            $("#leaveTable").load("./tables/leaveTable.php");
                        },

                    });
                    console.log("Deleted Leave ID: " + id);

                    Swal.fire({
                        title: "Deleted!",
                        text: "Your file has been deleted.",
                        icon: "success"
                    });
                }
            });

        });

        // delete family

        $(document).on("click", ".del_family", function() {
            var id = $(this).data("id");

            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {

                    $.ajax({
                        url: './cruds/delete.php',
                        type: 'post',
                        data: {
                            id: id,
                            table: 'family'
                        },
                        dataType: 'json',
                        success: function(r) {
                            console.log(r);

                            if (r.status === "success") {
                                console.log("Data deleted successfully  " + r
                                    .status);
                            } else {
                                console.log("Error deleting data: " + r.message);
                            }

                            $("#familyTable").load("./tables/familyTable.php");
                        },

                    });
                    console.log("Deleted Family ID: " + id);
                    Swal.fire({
                        title: "Deleted!",
                        text: "Your record has been deleted.",
                        icon: "success"
                    });
                }
            });

        });


        //Edit Operationsss

        // Edit Academic

        $(document).on("click", ".edit_academic", function() {

            console.log("Clicked Edit Academic ID: " + $(this).data("id"));

            $('#edit_course').val($(this).data("course"));
            $('#edit_institution').val($(this).data("institution"));
            $('#edit_board').val($(this).data("board"));
            $('#edit_year').val($(this).data("year"));
            $('#edit_percentage').val($(this).data("percentage"));
            $('#academicId').val($(this).data("id"));

            $('#staticBackdrop_edit1').modal('show');
        });

        $(document).on("click", "#edit_academicConfirm", function() {
            console.log("Clicked Edit Academic Confirm");

            $.ajax({
                url: './cruds/update.php',
                type: 'POST',
                data: {
                    id: $('#academicId').val(),
                    course: $('#edit_course').val(),
                    institution: $('#edit_institution').val(),
                    board: $('#edit_board').val(),
                    year: $('#edit_year').val(),
                    percentage: $('#edit_percentage').val(),
                    table: 'academic'
                },
                dataType: 'json',
                success: function(r) {
                    console.log(r);

                    if (r.status === "success") {

                        $("#academics-table").load("./tables/academicsTable.php");
                        $('#staticBackdrop_edit1').modal('hide');
                        console.log("Updated successfully");

                        Swal.fire({
                            title: "Academic Info edited Successfully",
                            icon: "success",
                            draggable: true,
                            timer: 3000
                        });
                        console.log("Data updated successfully  " + r.status);
                        $('#academicDetailsForm')[0].reset();
                        $('##staticBackdrop_edit1').modal('hide');
                        $("#academics-Table").load("./tables/academicTable.php");
                    } else if (r.success === false) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: r.message
                        });
                    } else {
                        console.log("Error updating data: " + r.message);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: "Error inserting data: " + r.message
                        });
                    }


                },
            });
        });

        // Edit Leave

        $(document).on("click", ".edit_leave", function() {

            console.log("Clicked Edit Leave ID: " + $(this).data("id"));

            $('#edit_type').val($(this).data("type"));
            $('#edit_date').val($(this).data("date"));
            $('#edit_reason').val($(this).data("reason"));
            $('#leaveId').val($(this).data("id"));


            $('#staticBackdrop_edit2').modal('show');
        });

        $(document).on("click", "#edit_leaveConfirm", function() {
            console.log("Clicked Edit Leave Confirm");

            // Create FormData 
            var formData = new FormData();
            formData.append('leaveId', $('#leaveId').val());
            formData.append('type', $('#edit_type').val());
            formData.append('date', $('#edit_date').val());
            formData.append('reason', $('#edit_reason').val());
            formData.append('table', 'leave');

            // Append the file if a new one is selected
            if ($('#edit_proof')[0].files.length > 0) {
                formData.append('proof', $('#edit_proof')[0].files[0]);
            }

            $.ajax({
                url: './cruds/update.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(r) {
                    console.log(r);

                    if (r.status === "success") {
                        $("#leaveTable").load("./tables/leaveTable.php");
                        $('#staticBackdrop_edit2').modal('hide');
                        console.log("Updated successfully");

                        Swal.fire({
                            title: "Leave / OD Info edited Successfully",
                            icon: "success",
                            draggable: true,
                            timer: 3000
                        });
                        console.log("Data updated successfully  " + r.status);
                        $('#leaveDetailsForm_edit')[0].reset();
                    } else if (r.success === false) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: r.message
                        });
                    } else {
                        console.log("Error updating data: " + r.message);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: "Error updating data: " + r.message
                        });
                    }
                },
            });
        });

        //Edit Family

        $(document).on("click", ".edit_family", function() {

            console.log("Clicked Edit Family ID: " + $(this).data("id"));

            $('#edit_name').val($(this).data("name"));
            $('#edit_gender').val($(this).data("gender"));
            $('#edit_relation').val($(this).data("relation"));
            $('#edit_mobile').val($(this).data("mobile"));
            $('#familyId').val($(this).data("id"));
            $('#staticBackdrop_edit3').modal('show');
        });

        $(document).on("click", "#edit_familyConfirm", function() {
            console.log("Clicked Edit Family Confirm");

            $.ajax({
                url: './cruds/update.php',
                type: 'POST',
                data: {
                    familyId: $('#familyId').val(),
                    name: $('#edit_name').val(),
                    gender: $('#edit_gender').val(),
                    relation: $('#edit_relation').val(),
                    mobile: $('#edit_mobile').val(),

                    table: 'family'
                },
                dataType: 'json',
                success: function(r) {
                    console.log(r);

                    if (r.status === "success") {
                        $("#familyTable").load("./tables/familyTable.php");
                        $('#staticBackdrop_edit3').modal('hide');
                        console.log("Updated successfully");

                        Swal.fire({
                            title: "Family Info edited Successfully",
                            icon: "success",
                            draggable: true,
                            timer: 3000
                        });
                        console.log("Data updated successfully  " + r.status);
                        $('#familyDetailsForm_edit')[0].reset();
                    } else if (r.success === false) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: r.message
                        });
                    } else {
                        console.log("Error updating data: " + r.message);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: "Error updating data: " + r.message
                        });
                    }
                },
            });
        });

        //proof view

        $(document).on("click", ".view-proof", function() {
            let proofPath = $(this).data("proof");
            // Add cache-busting timestamp to prevent showing old file
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


        $(document).on("click", ".reasonView", function() {
            Swal.fire({
                title: "Rejection - Reason",
                text: $(this).data("reason"),
                icon: "error"
            });
        })
    })
    </script>


    <!-- Proof Viewing Modal -->
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


</body>

</html>