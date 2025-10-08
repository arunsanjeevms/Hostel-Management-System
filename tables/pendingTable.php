<?php include '../db.php'; ?>

<table class="table table-bordered" id="pending-leave-table" width="100%" cellspacing="0">
    <thead class="gradient-header">
        <tr>
            <th>S.No</th>
            <th>Reg No </th>
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
        $sql="SELECT * FROM absent WHERE status = 'Pending'";
        $result = mysqli_query($conn, $sql);
        $sno=1;
        while($row=mysqli_fetch_assoc($result)){
            echo "<tr>";
            echo "<td>".$sno++."</td>";
            echo "<td> 927623BCS011 </td>";
            echo "<td>".$row['type']."</td>";

            $formattedDate = date('d-m-Y h:i A', strtotime($row['date']));

            echo "<td>".$formattedDate."</td>";
            echo "<td>".$formattedDate."</td>";
            echo "<td>".$formattedDate."</td>";
            echo "<td>".$row['reason']."</td>";
            if (!empty($row['proof'])) {
                echo "<td class='text-center align-middle'>
                    <button type='button' class='btn btn-info btn-sm view-proof' 
                        data-proof='".$row['proof']."' 
                        data-bs-toggle='modal' 
                        data-bs-target='#viewProofModal'>
                        <i class='fa-solid fa-eye'></i> View Proof
                    </button>
                </td>";
            } else {
                echo "<td class='text-center align-middle text-muted'>No Proof Uploaded </td>";
            }
            echo "<td class='text-center align-middle'>
                <button  class='btn btn-success btn-sm approve_leave' data-id='".$row['sno']."' >
                    <i class='fa-solid fa-check'></i> Approve
                </button>

                <button type='button' data-bs-toggle='modal' data-bs-target='#leaveRejectModal' class='btn btn-danger btn-sm reject_leave' data-id='".$row['sno']."' >
                    <i class='fa-solid fa-xmark'></i> Reject
                </button>
            </td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

<script>

(function initPendingDT() {
    if (typeof $ === 'undefined') return; // jQuery not loaded 
    if (!$.fn.DataTable) return; // DataTables library not present
    const sel = '#pending-leave-table';
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
                targets: [4, 5]
            } // Proof, Status
        ]
    });
})();

// Approve Leave
$(document).on("click", ".approve_leave", function() {
    let id = $(this).data("id");
    $.ajax({
        url: "./cruds/action.php",
        type: "POST",
        data: {
            action: "approve",
            id: id
        },
        dataType: "json",
        success: function(response) {
            if (response.status === "success") {
                $("#pendingTable").load("./tables/pendingTable.php", function() {
                    initPendingDT();
                });
                $("#processedTable").load("./tables/processedTable.php");
            } else {
                console.log(response.message);
            }
        }
    });
});

// reject modal
$(document).on("click", ".reject_leave", function() {
    let id = $(this).data("id");
    $("#confirmReject").data("id", id);
});

// Confirm reject
$(document).on("click", "#confirmReject", function() {
    let id = $(this).data("id");
    let rejectionreason = $("#rejectionReason").val();
    $.ajax({
        url: "./cruds/action.php",
        type: "POST",
        data: {
            action: "reject",
            id: id,
            rejectionreason: rejectionreason
        },
        dataType: "json",
        success: function(response) {
            if (response.status === "success") {
                $("#leaveRejectModal").modal("hide");
                $("#rejectionReason").val('');
                $("#pendingTable").load("./tables/pendingTable.php", function() {
                    initPendingDT();
                });
                $("#processedTable").load("./tables/processedTable.php");
            } else {
                console.log(response.message);
            }
        }
    });
});
</script>