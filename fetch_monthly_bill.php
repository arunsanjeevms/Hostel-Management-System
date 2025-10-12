<?php
include 'db.php';
session_start();

$roll_number = $_SESSION['student_roll_number'] ?? '22XX001';
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year  = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Try to fetch existing bill from mess_token_bills
$stmt = $conn->prepare("
    SELECT month, amount, created_at 
    FROM mess_token_bills 
    WHERE roll_number = ? 
      AND MONTH(month) = ? 
      AND YEAR(month) = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("sii", $roll_number, $month, $year);
$stmt->execute();
$result = $stmt->get_result();

// If no existing bill, calculate from mess_tokens
if ($result->num_rows == 0) {

    // Calculate total fee and last requested token date for the month
    $calc_stmt = $conn->prepare("
        SELECT 
            SUM(special_fee) AS total_fee,
            MAX(created_at) AS last_requested
        FROM mess_tokens 
        WHERE roll_number = ? 
          AND token_type='Special'
          AND MONTH(token_date) = ? 
          AND YEAR(token_date) = ?
    ");
    $calc_stmt->bind_param("sii", $roll_number, $month, $year);
    $calc_stmt->execute();
    $calc_result = $calc_stmt->get_result()->fetch_assoc();

    $total_fee = $calc_result['total_fee'] ?? 0;
    $last_requested = $calc_result['last_requested'] ?? '-';

    if ($total_fee == 0) {
        echo '<tr><td colspan="3" class="text-center">No bills found for this month.</td></tr>';
    } else {
        $selected_date = $year.'-'.$month.'-01';
        echo '<tr>';
        echo '<td>'.date('F Y', strtotime($selected_date)).'</td>';
        echo '<td>'.number_format($total_fee, 2).'</td>';
        echo '<td>'.$last_requested.'</td>'; // Show last requested token date
        echo '</tr>';
    }

} else {
    // If bill exists in mess_token_bills, override last_requested with latest special token request
    while($row = $result->fetch_assoc()){
        $stmt_last = $conn->prepare("
            SELECT MAX(created_at) AS last_requested
            FROM mess_tokens
            WHERE roll_number = ?
              AND token_type='Special'
              AND MONTH(token_date) = ?
              AND YEAR(token_date) = ?
        ");
        $stmt_last->bind_param("sii", $roll_number, $month, $year);
        $stmt_last->execute();
        $last_requested = $stmt_last->get_result()->fetch_assoc()['last_requested'] ?? $row['created_at'];

        echo '<tr>';
        echo '<td>'.date('F Y', strtotime($row['month'])).'</td>';
        echo '<td>'.number_format($row['amount'], 2).'</td>';
        echo '<td>'.$last_requested.'</td>';
        echo '</tr>';
    }
}
?>
