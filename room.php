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
                    <li class="breadcrumb-item active" aria-current="page">Room details</li>
                </ol>
            </nav>
        </div>
<div class="container-fluid">
            <div class="custom-tabs">
                <ul class="nav nav-tabs" role="tablist">
                    <!-- Center the main tabs -->
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" data-bs-toggle="tab" id="family-main-tab" href="#academics-content"
                            role="tab" aria-selected="true">
                            <span class="hidden-xs-down" style="font-size: 0.9em;"><i class="fa-solid fa-house"></i>
                                Room details </span>      
                        </a>
                    </li></ul><br>
<button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addRoomModal" style="margin-left: 800px !important;">Add Room</button>
  <br><br>
  <table id="dataTable" class="table table-striped table-bordered">
    <thead class="gradient-header" background: linear-gradient(135deg, #4e73df, #1cc88a);>
      <tr>
        <th>Room ID</th>
        <th>Hostel ID</th>
        <th>Room Number</th>
        <th>Capacity</th>
        <th>Occupied</th>
        <th>Room Type</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $res = mysqli_query($conn, "SELECT room_id, hostel_id, room_number, capacity, occupied, room_type FROM rooms ORDER BY room_id ASC");
    while ($r = mysqli_fetch_assoc($res)) {
        echo "<tr>
                <td>".htmlspecialchars($r['room_id'])."</td>
                <td>".htmlspecialchars($r['hostel_id'])."</td>
                <td>".htmlspecialchars($r['room_number'])."</td>
                <td>".htmlspecialchars($r['capacity'])."</td>
                <td>".htmlspecialchars($r['occupied'])."</td>
                <td>".htmlspecialchars($r['room_type'])."</td>
              </tr>";
    }
    ?>
    </tbody>
  </table>
</div>
<br><br>
<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addRoomForm" class="modal-content" autocomplete="off">
      <div class="modal-header">
        <h5 class="modal-title">Add New Room</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="hostel_id" class="form-label">Select Hostel</label>
          <select id="hostel_id" name="hostel_id" class="form-select" required>
            <option value="">-- Select --</option>
            <option value="1">Vedha Boys Hostel</option>
            <option value="2">ML Girls Hostel</option>
            <option value="3">Other Hostel</option>
          </select>
        </div>
        <div class="mb-3">
          <label for="room_number" class="form-label">Room Number</label>
          <input id="room_number" name="room_number" type="text" class="form-control" required />
        </div>
        <div class="mb-3">
          <label for="capacity" class="form-label">Capacity</label>
          <input id="capacity" name="capacity" type="number" min="1" value="3" class="form-control" required />
        </div>
        <div class="mb-3">
  <label for="occupied" class="form-label">Occupied</label>
  <input id="occupied" name="occupied" type="number" min="0" value="0" class="form-control" required />
</div>
        <div class="mb-3">
          <label for="room_type" class="form-label">Room Type</label>
          <select id="room_type" name="room_type" class="form-select" required>
            <option value="Non-AC">Non-AC</option>
            <option value="AC">AC</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button id="submitAddRoom" type="submit" class="btn btn-success">Add Room</button>
      </div>
    </form>
  </div>
  
</div></div></div>


<?php include 'footer.php'; ?>
<script>
$(function () {
  var table = $('#dataTable').DataTable({ pageLength: 10, lengthChange: false });

  $('#addRoomForm').on('submit', function (e) {
    e.preventDefault();

    var hostel_id = $('#hostel_id').val();
    var room_number = $('#room_number').val().trim();
    var capacity = $('#capacity').val();
    var occupied = $('#occupied').val();
    var room_type = $('#room_type').val();

    if (!hostel_id || !room_number || !capacity || !occupied || !room_type) {
      Swal.fire('Validation', 'Please complete all required fields.', 'warning');
      return;
    }

    $('#submitAddRoom').prop('disabled', true);

    $.ajax({
      url: 'addRoom.php',
      method: 'POST',
      data: { hostel_id, room_number, capacity,occupied, room_type },
      dataType: 'json',
      success: function(res) {
        if (res.success) {
          var room = res.data;
          table.row.add([
            room.room_id,
            room.hostel_id,
            room.room_number,
            room.capacity,
            room.occupied,
            room.room_type
          ]).draw(false);

          $('#addRoomModal').modal('hide');
          $('#addRoomForm')[0].reset();
          Swal.fire('Success', 'Room added!', 'success');
        } else {
          Swal.fire('Error', res.error || 'Server error', 'error');
        }
      },
      error: function(xhr, status, err) {
        console.error(xhr.responseText);
        Swal.fire('Error', 'AJAX error — check console', 'error');
      },
      complete: function() { $('#submitAddRoom').prop('disabled', false); }
    });
  });
});
</script>
</body>
</html>