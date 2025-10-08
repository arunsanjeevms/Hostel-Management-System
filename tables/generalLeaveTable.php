<?php include '../db.php'; ?>

<table class="table table-bordered" id="generalLeave-table" width="100%" cellspacing="0">
    <thead class="gradient-header">
        <tr>
            <th>S.No</th>
            <th>Leave Name</th>
            <th>Created Date</th>
            <th>From</th>
            <th>To</th>
            <th>Instructions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sql="SELECT * FROM generalLeave WHERE status = 'Completed' ORDER BY id DESC";
        $result = mysqli_query($conn, $sql);
        $sno=1;
        while($row=mysqli_fetch_assoc($result)){
            echo "<tr>";
            echo "<td>".$sno++."</td>";
            echo "<td>".htmlspecialchars($row['leave_name'])."</td>";
       
            $createdDate = date('d-m-Y h:i A', strtotime($row['date']));
            $fromDate = date('d-m-Y h:i A', strtotime($row['from_date']));
            $toDate = date('d-m-Y h:i A', strtotime($row['to_date']));

            echo "<td>".$createdDate."</td>";
            echo "<td>".$fromDate."</td>";
            echo "<td>".$toDate."</td>";
       
            if (!empty($row['instructions'])) {
                echo "<td class='text-center align-middle'>
                    ".htmlspecialchars($row['instructions'])."
                </td>";
            } else {
                echo "<td class='text-center align-middle text-muted'>No Instructions</td>";
            }
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

<script>

(function initPendingDT() {
    if (typeof $ === 'undefined') return; // jQuery not loaded 
    if (!$.fn.DataTable) return; // DataTables library not present
    const sel = '#generalLeave-table';
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



</script>