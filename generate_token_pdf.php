<?php
include 'dbconnect.php';

// Get token ID from URL
$token_id = isset($_GET['token_id']) ? intval($_GET['token_id']) : 0;

// Fetch token info from mess_tokens and mess_menu
$stmt = $conn->prepare("
    SELECT t.token_id, t.from_date, t.to_date, t.special_fee, t.created_at,
           m.meal_type, m.items, t.student_roll_number
    FROM mess_tokens t
    JOIN mess_menu m ON t.menu_id = m.menu_id
    WHERE t.token_id = ?
");
$stmt->bind_param("i", $token_id);
$stmt->execute();
$token = $stmt->get_result()->fetch_assoc();

if (!$token) {
    die("Invalid token ID.");
}

// Calculate expiry (using to_date as expiry)
$expiry_date = $token['to_date'];

// --- PDF Headers ---
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="token_' . $token['token_id'] . '.pdf"');

$width = 595;  // A4 width in points
$height = 842; // A4 height in points

// Start PDF
$pdf = "%PDF-1.4\n";
$pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
$pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
$pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $width $height] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";

// Prepare content stream
$content = "";
$y = 750; // Starting Y position
$line_gap = 30; // Equal vertical gap between lines

// Title
$content .= "BT\n/F1 24 Tf\n100 $y Td\n(SPECIAL MEAL TOKEN) Tj\nET\n";

// Student Roll
$y -= $line_gap;
$content .= "BT\n/F1 14 Tf\n100 $y Td\n(Student Roll: " . $token['student_roll_number'] . ") Tj\nET\n";

// Meal Type
$y -= $line_gap;
$content .= "BT\n/F1 14 Tf\n100 $y Td\n(Meal Type: " . $token['meal_type'] . ") Tj\nET\n";

// Items
$y -= $line_gap;
$content .= "BT\n/F1 14 Tf\n100 $y Td\n(Items: " . $token['items'] . ") Tj\nET\n";

// Fee
$y -= $line_gap;
$content .= "BT\n/F1 14 Tf\n100 $y Td\n(Fee: Rs." . number_format($token['special_fee'], 2) . ") Tj\nET\n";

// Expiry (in red)
$y -= $line_gap;
$content .= "1 0 0 rg\n"; // Red color
$content .= "BT\n/F1 14 Tf\n100 $y Td\n(Expiry: " . $expiry_date . ") Tj\nET\n";
$content .= "0 0 0 rg\n"; // Reset color

// Requested At
$y -= $line_gap;
$content .= "BT\n/F1 12 Tf\n100 $y Td\n(Requested At: " . $token['created_at'] . ") Tj\nET\n";

$length = strlen($content);
$pdf .= "4 0 obj\n<< /Length $length >>\nstream\n$content\nendstream\nendobj\n";

// Font object
$pdf .= "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

// Cross-reference table
$pdf .= "xref\n0 6\n0000000000 65535 f \n";
$pdf .= "0000000010 00000 n \n0000000060 00000 n \n0000000110 00000 n \n0000000240 00000 n \n0000000400 00000 n \n";

// Trailer
$pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n" . strlen($pdf) . "\n%%EOF";

echo $pdf;
?>