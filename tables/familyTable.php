<?php include '../db.php'; ?>

<table class="table table-bordered" id="family-table" width="100%" cellspacing="0">
    <thead class="gradient-header">
        <tr>
            <th>S.No</th>
            <th>Name</th>
            <th>Gender</th>
            <th>Relationship</th>
            <th>Mobile</th>
            <th>Action</th>

        </tr>
    </thead>
    <tbody>

        <?php

    $sql="select * from family";
    $result = mysqli_query($conn, $sql);
    $sno=1;
    while($row=mysqli_fetch_assoc($result)){
        echo "<tr>";
        echo "<td>".$sno++."</td>";
        echo "<td>".$row['name']."</td>";
        echo "<td>".$row['gender']."</td>";
        echo "<td>".$row['relation']."</td>";
        echo "<td>".$row['mobile']."</td>";
        echo "<td class='text-center align-middle'><button class='btn btn-warning btn-sm edit-academic edit_family' 
        data-id='".$row['sno']."'
        data-name='".$row['name']."'
        data-gender='".$row['gender']."'
        data-relation='".$row['relation']."'
        data-mobile='".$row['mobile']."'
        > <i class='fa-solid fa-pencil'></i> Edit</button> 
        <button class='text-center align-middle btn btn-danger btn-sm delete-academic del_family' data-id='".$row['sno']."'>
        <i class='fa-solid fa-trash'></i> Delete</button></td>";
        echo "</tr>";
    }

    ?>

    </tbody>
</table>