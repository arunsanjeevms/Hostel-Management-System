<?php
session_start();
include 'db_connect.php';

// ✅ Faculty authentication check
if (!isset($_SESSION['faculty_id'])) {
    header("Location: faculty_login.php");
    exit();
}

$faculty_name = $_SESSION['faculty_name'];
$department = $_SESSION['faculty_department'];

// ✅ Fetch only students from same department
$sql = "SELECT 
            s.name AS student_name, 
            s.department, 
            l.leave_type, 
            l.from_date, 
            l.to_date, 
            l.reason, 
            l.faculty_status AS status, 
            l.leave_id AS id
        FROM leave_applications l
        JOIN students s ON l.student_roll_number = s.roll_number
        WHERE s.department = ?
        ORDER BY l.applied_at DESC";


$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();
?>





<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIC</title>
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
        /* ----------- FINAL CONTENT AREA FIX ----------- */
.content {
  position: relative;
  margin-left: var(--sidebar-width); /* or margin-left: 250px; */
  padding: 40px 30px;
  background: #f8f9fc;
  min-height: 100vh;
  transition: margin-left 0.3s ease, padding 0.3s ease;
  z-index: 0;
}


/* when sidebar is collapsed */
.sidebar.collapsed + .content {
  margin-left: var(--sidebar-collapsed-width);
}


/* small screens (mobile view) */
@media (max-width: 992px) {
  .content {
    margin-left: 0 !important;
    padding: 30px 20px;
  }
}



/* Clean professional table style */
.table {
  background: white;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

.table thead th {
  background-color: #4e73df !important;
  color: white !important;
  font-weight: 600;
}

.table-hover tbody tr:hover {
  background-color: #f1f5ff;
  transition: background-color 0.3s ease;
}

.btn-success, .btn-danger {
  width: 85px;
  font-size: 0.85rem;
  border: none;
}

.btn-success:hover { background-color: #198754; }
.btn-danger:hover { background-color: #c82333; }



    </style>
</head>

<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
<div class="content" id="contentWrapper">
  <div class="tab-pane p-20 active" id="faculty" role="tabpanel">
    <div class="card shadow">
      <!-- your table etc. -->

    <!-- Main Content -->
   <?php
// fetch leaves for this faculty's department
$sql = "SELECT l.leave_id,
               s.roll_number,
               s.name AS student_name,
               s.department,
               l.leave_type,
               l.from_date,
               l.to_date,
               l.reason,
               l.faculty_status
        FROM leave_applications l
        JOIN students s ON l.student_roll_number = s.roll_number
        WHERE s.department = ?
        ORDER BY l.applied_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $department);
$stmt->execute();
$leaves = $stmt->get_result();
?>
<div class="tab-pane p-20 active" id="faculty" role="tabpanel">
  <div class="card shadow">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Leave Requests — <?= htmlspecialchars($department) ?></h5>
      <div>Welcome, <?= htmlspecialchars($faculty_name) ?> &nbsp; <a href="logout.php" class="btn btn-sm btn-light">Logout</a></div>
    </div>
    <div class="card-body">
  <div class="table-responsive">
    <table id="facultyTable" class="table table-bordered table-hover align-middle">
      <thead class="table-primary text-center">
        <tr>
          <th>S.No</th>
          <th>Reg No</th>
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
          $i = 1;
          while ($row = $leaves->fetch_assoc()): 
        ?>
        <tr>
          <td class="text-center"><?= $i++ ?></td>
          <td><?= htmlspecialchars($row['roll_number']) ?></td>
          <td><?= htmlspecialchars($row['student_name']) ?></td>
          <td><?= htmlspecialchars($row['leave_type']) ?></td>
          <td><?= date('d-m-Y h:i A', strtotime($row['applied_at'])) ?></td>
          <td><?= date('d-m-Y h:i A', strtotime($row['from_date'])) ?></td>
          <td><?= date('d-m-Y h:i A', strtotime($row['to_date'])) ?></td>
          <td><?= htmlspecialchars($row['reason']) ?></td>
          <td class="text-center">
            <?php if (!empty($row['proof'])): ?>
              <a href="uploads/<?= htmlspecialchars($row['proof']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">View</a>
            <?php else: ?>
              <span class="text-muted">No Proof</span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <?php if ($row['faculty_status'] === 'Pending'): ?>
              <form method="post" action="faculty_action.php" style="display:inline;">
                <input type="hidden" name="leave_id" value="<?= (int)$row['leave_id'] ?>">
                <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
              </form>
              <form method="post" action="faculty_action.php" style="display:inline;">
                <input type="hidden" name="leave_id" value="<?= (int)$row['leave_id'] ?>">
                <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
              </form>
            <?php else: ?>
              <?php 
                $statusColor = ($row['faculty_status'] === 'Approved') ? 'success' :
                               (($row['faculty_status'] === 'Rejected') ? 'danger' : 'secondary');
              ?>
              <span class="badge bg-<?= $statusColor ?>"><?= htmlspecialchars($row['faculty_status']) ?></span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

  </div>
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
    </script>

</body>

</html>