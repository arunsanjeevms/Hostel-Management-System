<?php include '../db.php'; ?>

<table class="table table-bordered" id="processed-leave-table" width="100%" cellspacing="0">
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
            <th>Action</th>

        </tr>
    </thead>
    <tbody>
        <?php
         // Fetch only rows where status is not in pending
         $sql = "SELECT * FROM absent WHERE status NOT IN ('Pending', 'IVR Approved','IVR Rejected') ORDER BY sno DESC";
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

            // Display status buttons for processed leaves
            if($row['status'] == 'Approved for IVR') {
                echo "<td class='text-center align-middle'>
                    <button class='btn btn-success btn-sm' disabled>".$row['status']."</button>
                </td>";
            }
            else {
                echo "<td class='text-center align-middle'>
                    <button type='button' style='background-color:#f1a460' class='btn btn btn-sm reasonView' data-reason='".$row['status']."'><i class='fa-solid fa-question'></i> Rejected</button>
                </td>";
            }

            echo "</tr>";   
    
    }

        ?>
    </tbody>
</table>

<script>
(function initProcessedDT() {
    if (typeof $ === 'undefined') return; // jQuery not loaded 
    if (!$.fn.DataTable) return; // DataTables plugin not loaded
    const sel = '#processed-leave-table';
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
            } // Proof, Action/Status
        ]
    });
})();
</script>