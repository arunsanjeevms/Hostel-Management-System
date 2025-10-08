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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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

        body {
            background-color: #f8f9fa;
        }

        .breadcrumb-container {
            background: linear-gradient(to bottom, #b2e0ff, #ffe8dc);
            padding: 12px 25px;
            border-radius: 15px;
            margin: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .form-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin: 20px auto;
        }

        .section-header {
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            border-bottom: 3px solid #4361ee;
            padding-bottom: 8px;
        }

        .section-header i {
            background-color: #4361ee;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: rgb(240, 241, 248);
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 1px solid #ced4da;
            padding: 10px;
        }

        .form-label {
            font-weight: 500;
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
                    <li class="breadcrumb-item active" aria-current="page">Research</li>
                </ol>
            </nav>
        </div>

        <!-- Content Area -->

        <div class="container">
            <button type="button" class="btn btn-primary" style="margin-left: 30px;" data-bs-toggle="modal" data-bs-target="#includeModal">
                + Add Student
            </button>
        </div>
        <style>
            #grad-head {
                background: linear-gradient(135deg, #4CAF50, #2196F3) !important;

            }

            thead th {
                background: transparent !important;
                border: 1px solid #f3f3f3 !important;
                color: white !important;
                text-align: center !important;
                /* horizontal center */
                vertical-align: middle !important;
            }

            thead tr {
                border: 1px solid #f3f3f3 !important;
            }
        </style>
        <div class="table-responsive mx-auto" style="max-width: 80%;margin-top:20px;">
            <table width="100%" class="table table-striped table-hover">
                <thead id="grad-head">
                    <tr>
                        <th>Student Name</th>
                        <th>Roll Number</th>
                        <th>Department</th>
                        <th>Year</th>
                        <th>Student Phone</th>
                        <th>Parent Mobile</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody">

                </tbody>
            </table>
        </div>
    </div>




    <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </div>







    <div class="modal fade" id="includeModal" tabindex="-1" aria-labelledby="includeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">

            <div class="modal-body" style="border-radius: 10px;">

                <div class="section-header">
                    <i class="fas fa-user"></i> Student Registration
                </div>

                <div class="container">
                    <form id="studentForm" class="bg-white p-4 rounded shadow-sm">


                        <div id="responseMessage"></div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Roll Number *</label>
                                <input type="text" name="roll_number" class="form-control" placeholder="Enter roll number" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Department *</label>
                                <select name="department" class="form-select" required>
                                    <option value="">Select Department</option>
                                    <option>Computer Science & Engineering</option>
                                    <option>Electronics & Communication Engineering</option>
                                    <option>Mechanical Engineering</option>
                                    <option>Civil Engineering</option>
                                    <option>Information Technology</option>
                                    <option>Electronics and Electrical Engineering</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Academic Year *</label>
                                <select name="academic_year" class="form-select" required>
                                    <option value="">Select Year</option>
                                    <option>First Year</option>
                                    <option>Second Year</option>
                                    <option>Third Year</option>
                                    <option>Fourth Year</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Block *</label>
                                <select name="block" class="form-select" required>
                                    <option value="">Select Block</option>
                                    <option>Veda</option>
                                    <option>Octa</option>
                                    <option>Muthulakshmi</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Gender *</label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Select Gender</option>
                                    <option>Male</option>
                                    <option>Female</option>
                                    <option>Other</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Student Phone *</label>
                                <input type="tel" name="phone" class="form-control" placeholder="Enter phone number" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" placeholder="Enter email address" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Father Name *</label>
                                <input type="text" name="father_name" class="form-control" placeholder="Enter father name" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Mother Name *</label>
                                <input type="text" name="mother_name" class="form-control" placeholder="Enter mother name" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Parent Phone *</label>
                                <input type="tel" name="parent_phone" class="form-control" placeholder="Enter parent phone number" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Alternate Phone</label>
                                <input type="tel" name="alternate_phone" class="form-control" placeholder="Enter alternate phone">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end align-items-center mt-4" style="gap: 10px;">
                            <button type="submit" class="btn btn-primary px-4" id="button">Save</button>

                            <div id="buttonspin" class="spinner-border text-primary" style="margin-right:30px; display: none;" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                    </form>
                </div>

            </div>


        </div>



    </div>
   <script>
document.addEventListener("DOMContentLoaded", function() {
    fetchStudents();
});

// Fetch students and populate table
function fetchStudents() {
    fetch("fetch_students.php")
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById("studentTableBody");
            tbody.innerHTML = "";

            if (!data || data.length === 0) {
                tbody.innerHTML = "<tr><td colspan='7' class='text-center'>No students found</td></tr>";
                return;
            }

            data.forEach(student => {
                const row = document.createElement("tr");
                row.innerHTML = `
          <td>${student.name}</td>
          <td>${student.roll_number}</td>
          <td>${student.department}</td>
          <td>${student.academic_year}</td>
          <td>${student.phone}</td>
          <td>${student.parent_phone}</td>
          <td>
            <button class="btn btn-sm btn-primary view-more-btn">View More</button>
            <button class="btn btn-sm btn-warning edit-btn">Edit</button>
          </td>
        `;
                tbody.appendChild(row);

                // View More modal
                row.querySelector(".view-more-btn").addEventListener("click", () => {
                    openStudentModal(student);
                });

                // Edit modal
                row.querySelector(".edit-btn").addEventListener("click", () => {
                    openEditModal(student);
                });
            });
        })
        .catch(err => console.error("Error fetching students:", err));
}

// Function to open the "View More" modal
function openStudentModal(student) {
    const modalTitle = document.getElementById("studentModalTitle");
    const modalBody = document.getElementById("studentModalBody");

    modalTitle.textContent = student.name;
    modalBody.innerHTML = `
    <table class="table table-bordered">
      <tr><th>Roll Number</th><td>${student.roll_number}</td></tr>
      <tr><th>Department</th><td>${student.department}</td></tr>
      <tr><th>Year</th><td>${student.academic_year}</td></tr>
      <tr><th>Block</th><td>${student.block}</td></tr>
      <tr><th>Gender</th><td>${student.gender}</td></tr>
      <tr><th>Phone</th><td>${student.phone}</td></tr>
      <tr><th>Email</th><td>${student.email}</td></tr>
      <tr><th>Father Name</th><td>${student.father_name}</td></tr>
      <tr><th>Mother Name</th><td>${student.mother_name}</td></tr>
      <tr><th>Parent Phone</th><td>${student.parent_phone}</td></tr>
      <tr><th>Alternate Phone</th><td>${student.alternate_phone}</td></tr>
      <tr><th>Created At</th><td>${student.created_at}</td></tr>
    </table>
  `;

    const modal = new bootstrap.Modal(document.getElementById('studentModal'));
    modal.show();
}

// Function to open the "Edit" modal and prefill form
// Function to open Edit Modal and prefill all fields
function openEditModal(student) {
    document.getElementById("edit_student_id").value = student.id;
    document.getElementById("edit_name").value = student.name;
    document.getElementById("edit_roll_number").value = student.roll_number;
    document.getElementById("edit_department").value = student.department;
    document.getElementById("edit_academic_year").value = student.academic_year;
    document.getElementById("edit_block").value = student.block;
    document.getElementById("edit_gender").value = student.gender;
    document.getElementById("edit_phone").value = student.phone;
    document.getElementById("edit_email").value = student.email;
    document.getElementById("edit_father_name").value = student.father_name;
    document.getElementById("edit_mother_name").value = student.mother_name;
    document.getElementById("edit_parent_phone").value = student.parent_phone;
    document.getElementById("edit_alternate_phone").value = student.alternate_phone;

    const editModal = new bootstrap.Modal(document.getElementById("editStudentModal"));
    editModal.show();
}

// Handle Edit Form Submission
document.getElementById("editStudentForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch("update_student.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(response => {
        if(response.success){
            alert("Student updated successfully!");
            fetchStudents(); // Refresh table
            bootstrap.Modal.getInstance(document.getElementById("editStudentModal")).hide();
        } else {
            alert("Update failed: " + response.error);
        }
    })
    .catch(err => console.error(err));
});

</script>



    <script>
        // form submission script
        $("#studentForm").on("submit", function(e) {
            e.preventDefault();
            document.getElementById("button").style.display = "none";
            document.getElementById("buttonspin").style.display = "block";
            $.ajax({
                url: "student_register.php",
                type: "POST",
                data: $(this).serialize(),
                dataType: "json",
                success: function(response) {

                    if (response.status === "success") {
                        $("#responseMessage").html(
                            `<div class="alert alert-success">${response.message}</div>`
                        );
                        setTimeout(() => {
                            $("#studentForm")[0].reset();
                            $("#responseMessage").empty();
                            // if it's inside modal, you can close modal here:
                            // $('#studentModal').modal('hide');
                        }, 1500);
                    } else {
                        document.getElementById("buttonspin").style.display = "none";
                        document.getElementById("button").style.display = "block";

                        $("#responseMessage").html(
                            `<div class="alert alert-danger">${response.message}</div>`
                        );
                    }
                },
                error: function() {
                    document.getElementById("buttonspin").style.display = "none";
                    document.getElementById("button").style.display = "block";
                    $("#responseMessage").html(
                        `<div class="alert alert-danger">Something went wrong. Please try again.</div>`
                    );
                },
            });
        });
    </script>
    <!-- Edit Modal -->
<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Student Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="editStudentForm" class="bg-white p-4 rounded shadow-sm">
          <input type="hidden" name="id" id="edit_student_id">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name *</label>
              <input type="text" name="name" class="form-control" id="edit_name" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Roll Number *</label>
              <input type="text" name="roll_number" class="form-control" id="edit_roll_number" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Department *</label>
              <select name="department" class="form-select" id="edit_department" required>
                <option value="">Select Department</option>
                <option>Computer Science & Engineering</option>
                <option>Electronics & Communication Engineering</option>
                <option>Mechanical Engineering</option>
                <option>Civil Engineering</option>
                <option>Information Technology</option>
                <option>Electronics and Electrical Engineering</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Academic Year *</label>
              <select name="academic_year" class="form-select" id="edit_academic_year" required>
                <option value="">Select Year</option>
                <option>First Year</option>
                <option>Second Year</option>
                <option>Third Year</option>
                <option>Fourth Year</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Block *</label>
              <select name="block" class="form-select" id="edit_block" required>
                <option value="">Select Block</option>
                <option>Veda</option>
                <option>Octa</option>
                <option>Muthulakshmi</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Gender *</label>
              <select name="gender" class="form-select" id="edit_gender" required>
                <option value="">Select Gender</option>
                <option>Male</option>
                <option>Female</option>
                <option>Other</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Student Phone *</label>
              <input type="tel" name="phone" class="form-control" id="edit_phone" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Email *</label>
              <input type="email" name="email" class="form-control" id="edit_email" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Father Name *</label>
              <input type="text" name="father_name" class="form-control" id="edit_father_name" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Mother Name *</label>
              <input type="text" name="mother_name" class="form-control" id="edit_mother_name" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Parent Phone *</label>
              <input type="tel" name="parent_phone" class="form-control" id="edit_parent_phone" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Alternate Phone</label>
              <input type="tel" name="alternate_phone" class="form-control" id="edit_alternate_phone">
            </div>
          </div>

          <div class="d-flex justify-content-end align-items-center mt-4" style="gap: 10px;">
            <button type="submit" class="btn btn-primary px-4">Update</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


    <div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentModalTitle">Student Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="studentModalBody">
                    <!-- Details will be injected here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
