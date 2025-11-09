<?php
// Fee Log Integrity & Health Report (restricted)
require_once '../config/db.php';
require_once '../config/auth_guard.php'; // ensures auth & $municipality_id

header('Content-Type: text/html; charset=UTF-8');

echo '<!DOCTYPE html><html><head><title>Fee Log Integrity</title><meta charset="utf-8">';
echo '<style>body{font-family:Arial, sans-serif; margin:40px; background:#f7f9fb;color:#223c50}h1{margin-top:0}table{border-collapse:collapse;margin:20px 0;width:100%;max-width:900px;background:#fff;box-shadow:0 2px 6px rgba(0,0,0,.08);}th,td{padding:10px 14px;border:1px solid #d9e2ea;font-size:14px}th{background:#255D4F;color:#fff;text-align:left}code{background:#eef;padding:2px 5px;border-radius:4px;font-size:12px} .ok{color:#0a7b46;font-weight:600}.warn{color:#b58900;font-weight:600}.bad{color:#b00020;font-weight:600}</style>';
echo '</head><body>';

echo '<h1>Tourism Fee Log Integrity</h1>';

// 1. Null recorded_by_user_id count
$nullCount = 0; $stmt = $conn->query("SELECT COUNT(*) AS c FROM tourism_fee_log WHERE recorded_by_user_id IS NULL");
if ($stmt) { $nullCount = (int)$stmt->fetch_assoc()['c']; }

// 2. Orphan references (should be 0 if FK exists)
$orphanCount = 0; $stmt2 = $conn->query("SELECT COUNT(*) AS c FROM tourism_fee_log l LEFT JOIN user u ON u.user_id = l.recorded_by_user_id WHERE l.recorded_by_user_id IS NOT NULL AND u.user_id IS NULL");
if ($stmt2) { $orphanCount = (int)$stmt2->fetch_assoc()['c']; }

// 3. Total rows
$totalRows = 0; $stmt3 = $conn->query("SELECT COUNT(*) AS c FROM tourism_fee_log");
if ($stmt3) { $totalRows = (int)$stmt3->fetch_assoc()['c']; }

// 4. Oldest & newest dates
$oldest = $newest = '-'; $stmt4 = $conn->query("SELECT MIN(visit_date) AS mi, MAX(visit_date) AS mx FROM tourism_fee_log");
if ($stmt4) { $r = $stmt4->fetch_assoc(); $oldest = $r['mi'] ?? '-'; $newest = $r['mx'] ?? '-'; }

// 5. Amount sanity (negative / huge)
$negCount = 0; $hugeCount = 0; $stmt5 = $conn->query("SELECT SUM(amount < 0) AS negs, SUM(amount > 100000) AS huge FROM tourism_fee_log");
if ($stmt5) { $r = $stmt5->fetch_assoc(); $negCount = (int)$r['negs']; $hugeCount = (int)$r['huge']; }

// 6. Daily gaps last 30 days (days with zero logs)
$gapDays = 0; $stmt6 = $conn->query("SELECT SUM(d.cnt=0) AS g FROM (SELECT DATE_SUB(CURDATE(), INTERVAL seq DAY) AS dte FROM (SELECT 0 seq UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL SELECT 20 UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24 UNION ALL SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29) t LEFT JOIN (SELECT visit_date, COUNT(*) cnt FROM tourism_fee_log WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY visit_date) l ON l.visit_date = DATE_SUB(CURDATE(), INTERVAL seq DAY)) q;");
if ($stmt6) { $gapDays = (int)$stmt6->fetch_row()[0]; }

$health = 'OK';
if ($orphanCount > 0 || $negCount > 0) { $health = 'ATTENTION'; }
if ($hugeCount > 0) { $health = 'REVIEW'; }

echo '<table><tbody>';
echo '<tr><th>Metric</th><th>Value</th><th>Status</th></tr>';
echo '<tr><td>Total Fee Log Rows</td><td>'.number_format($totalRows).'</td><td class="ok">'.($totalRows>0?'OK':'EMPTY').'</td></tr>';
echo '<tr><td>Null Recorded By</td><td>'.number_format($nullCount).'</td><td class="'.($nullCount>0?'warn':'ok').'">'.($nullCount>0?'FILL / EXPECTED OLD':'OK').'</td></tr>';
echo '<tr><td>Orphan Recorded By</td><td>'.number_format($orphanCount).'</td><td class="'.($orphanCount>0?'bad':'ok').'">'.($orphanCount>0?'FIX FK':'OK').'</td></tr>';
echo '<tr><td>Oldest Visit Date</td><td>'.htmlspecialchars($oldest).'</td><td class="ok">OK</td></tr>';
echo '<tr><td>Newest Visit Date</td><td>'.htmlspecialchars($newest).'</td><td class="ok">OK</td></tr>';
echo '<tr><td>Negative Amount Rows</td><td>'.number_format($negCount).'</td><td class="'.($negCount>0?'bad':'ok').'">'.($negCount>0?'INVESTIGATE':'OK').'</td></tr>';
echo '<tr><td>Huge Amount >100k Rows</td><td>'.number_format($hugeCount).'</td><td class="'.($hugeCount>0?'warn':'ok').'">'.($hugeCount>0?'CHECK POLICY':'OK').'</td></tr>';
echo '<tr><td>Zero-Log Days (30d)</td><td>'.number_format($gapDays).'</td><td class="ok">INFO</td></tr>';
echo '<tr><td>Overall Health</td><td colspan="2" class="'.($health==='OK'?'ok':'warn').'">'.$health.'</td></tr>';
echo '</tbody></table>';

echo '<p><a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourism_fee_log_list.php" style="text-decoration:none;color:#255D4F;font-weight:600">&larr; Back to Fee Log Report</a></p>';

echo '</body></html>';
