<?php
// CSV Export for Tourism Fee Logs
require_once '../../../tripko-backend/config/db.php';
require_once '../../../tripko-backend/config/auth_guard.php'; // ensures $municipality_id & auth

// Inputs (month/year) default to current if not provided
$filter_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$filter_year  = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

if ($filter_month < 1 || $filter_month > 12) { $filter_month = (int)date('m'); }
if ($filter_year < 2000 || $filter_year > (int)date('Y')) { $filter_year = (int)date('Y'); }

// Fetch data
$sql = "SELECT l.visit_date, s.name AS spot_name, l.name, l.num_tourists, l.amount, u.username AS recorded_by
    FROM tourism_fee_log l
    JOIN tourist_spots s ON l.spot_id = s.spot_id
    LEFT JOIN user u ON u.user_id = l.recorded_by_user_id
    WHERE l.municipality_id = ? AND MONTH(l.visit_date) = ? AND YEAR(l.visit_date) = ?
    ORDER BY l.visit_date ASC, l.id ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iii', $municipality_id, $filter_month, $filter_year);
$stmt->execute();
$result = $stmt->get_result();

// Prepare CSV headers
$filename = 'tourism_fee_logs_' . $municipality_id . '_' . $filter_year . '_' . str_pad($filter_month,2,'0',STR_PAD_LEFT) . '.csv';
if (!headers_sent()) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
}

$out = fopen('php://output', 'w');
fputcsv($out, ['Date','Tourist Spot','Name','Number of Tourists','Amount (PHP)','Recorded By']);
$total_amount = 0; $total_visitors = 0;
while ($row = $result->fetch_assoc()) {
    $total_visitors += (int)$row['num_tourists'];
    $total_amount += (float)$row['amount'];
    fputcsv($out, [
        $row['visit_date'],
        $row['spot_name'],
        $row['name'],
        $row['num_tourists'],
        number_format($row['amount'], 2, '.', ''),
        $row['recorded_by'] ?? ''
    ]);
}
// Totals row
fputcsv($out, ['TOTAL','','',$total_visitors, number_format($total_amount, 2, '.', ''), '']);

fclose($out);
$stmt->close();
exit;
