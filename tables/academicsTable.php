<?php include '../db.php'; ?>



<h1>


</h1>

<table class="table table-bordered" id="academics-table" width="100%" cellspacing="0">
    <thead class="gradient-header">
        <tr>
            <th>S.No</th>
            <th>Course</th>
            <th>Institution</th>
            <th>Board</th>
            <th>Year</th>
            <th>Percentage</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>

        <?php

    $sql="select * from academics"; 
    $result = mysqli_query($conn, $sql);
    $sno=1;
    while($row=mysqli_fetch_assoc($result)){
        echo "<tr>";
        echo "<td>".$sno++."</td>";
        echo "<td>".$row['course']."</td>";
        echo "<td>".$row['institution']."</td>";
        echo "<td>".$row['board']."</td>";
        echo "<td>".$row['year']."</td>";
        echo "<td>".$row['percentage']."</td>";
        echo "<td class='text-center align-middle'>
        <button class='btn btn-warning btn-sm edit_academic'
            data-id='".$row['sno']."'
            data-course='".$row['course']."'
            data-institution='".$row['institution']."'
            data-board='".$row['board']."'
            data-year='".$row['year']."'
            data-percentage='".$row['percentage']."'>
            <i class='fa-solid fa-pencil'></i> Edit
        </button>
        <button class='btn btn-danger btn-sm del_academic' data-id='".$row['sno']."'>
            <i class='fa-solid fa-trash'></i> Delete
        </button>
    </td>";
    echo "</tr>";
    
    }

    ?>

    </tbody>
</table>