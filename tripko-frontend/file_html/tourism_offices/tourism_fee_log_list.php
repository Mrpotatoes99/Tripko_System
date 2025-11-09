<?php
// Tourism Fee Log Report - Tourism Officer (central guard)
require_once '../../../tripko-backend/config/db.php';
require_once '../../../tripko-backend/config/auth_guard.php'; // sets $municipality_id

// Filters
$filter_month = $_GET['month'] ?? date('m');
$filter_year = $_GET['year'] ?? date('Y');
$filter_spot = isset($_GET['spot']) ? (int)$_GET['spot'] : 0;
$filter_recorder = isset($_GET['recorder']) ? (int)$_GET['recorder'] : 0;
// Pagination params
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
if (!in_array($per_page, [10,25,50,100], true)) { $per_page = 25; }

// 2. Fetch town name
$town_name = '';
if ($municipality_id) {
    if ($stmt = $conn->prepare("SELECT name FROM towns WHERE town_id = ? LIMIT 1")) {
        $stmt->bind_param('i', $municipality_id);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $town_name = $row['name'];
            }
        }
        $stmt->close();
    }
}

// Fetch spots for filter
$spots = [];
if ($municipality_id) {
    if ($st = $conn->prepare("SELECT spot_id, name FROM tourist_spots WHERE town_id = ? AND status='active' ORDER BY name ASC")) {
        $st->bind_param('i', $municipality_id);
        if ($st->execute()) {
            $rs = $st->get_result();
            while ($row = $rs->fetch_assoc()) { $spots[] = $row; }
        }
        $st->close();
    }
}

// Fetch distinct recorders (usernames) for filter (recent year scope to limit size)
$recorders = [];
if ($municipality_id) {
    $recSql = "SELECT DISTINCT u.user_id, u.username FROM tourism_fee_log l LEFT JOIN user u ON u.user_id = l.recorded_by_user_id WHERE l.municipality_id = ? AND u.user_id IS NOT NULL ORDER BY u.username ASC";
    if ($st = $conn->prepare($recSql)) {
        $st->bind_param('i', $municipality_id);
        if ($st->execute()) {
            $rs = $st->get_result();
            while ($row = $rs->fetch_assoc()) { $recorders[] = $row; }
        }
        $st->close();
    }
}

// Build base conditions & parameters for filters (reusable for count, aggregate, paged data)
$conditions = ["l.municipality_id = ?", "MONTH(l.visit_date) = ?", "YEAR(l.visit_date) = ?"];
$params = [$municipality_id, (int)$filter_month, (int)$filter_year];
$types = 'iii';
if ($filter_spot > 0) { $conditions[] = 'l.spot_id = ?'; $params[] = $filter_spot; $types .= 'i'; }
if ($filter_recorder > 0) { $conditions[] = 'l.recorded_by_user_id = ?'; $params[] = $filter_recorder; $types .= 'i'; }
$where = implode(' AND ', $conditions);
// 1. Total count for pagination
$count_sql = "SELECT COUNT(*) AS c FROM tourism_fee_log l WHERE $where"; // municipality + filters
if ($count_stmt = $conn->prepare($count_sql)) {
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_res = $count_stmt->get_result();
    $total_count = (int)($count_res->fetch_assoc()['c'] ?? 0);
    $count_stmt->close();
} else { $total_count = 0; }

// 2. Aggregate totals for filtered result (independent of pagination)
$agg_amount = 0; $agg_visitors = 0;
$agg_sql = "SELECT COALESCE(SUM(l.amount),0) AS sum_amount, COALESCE(SUM(l.num_tourists),0) AS sum_visitors FROM tourism_fee_log l WHERE $where";
if ($agg_stmt = $conn->prepare($agg_sql)) {
    $agg_stmt->bind_param($types, ...$params);
    $agg_stmt->execute();
    $agg_res = $agg_stmt->get_result();
    if ($row = $agg_res->fetch_assoc()) { $agg_amount = $row['sum_amount']; $agg_visitors = $row['sum_visitors']; }
    $agg_stmt->close();
}

// 3. Fetch paged logs
$total_pages = $total_count > 0 ? (int)ceil($total_count / $per_page) : 1;
if ($page > $total_pages) { $page = $total_pages; }
$offset = ($page - 1) * $per_page;
$sql = "SELECT l.*, s.name AS spot_name, u.username AS recorded_by FROM tourism_fee_log l JOIN tourist_spots s ON l.spot_id = s.spot_id LEFT JOIN user u ON u.user_id = l.recorded_by_user_id WHERE $where ORDER BY l.visit_date DESC, l.id DESC LIMIT $per_page OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$logs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 4. Page totals (subset)
$page_amount = 0; $page_visitors = 0;
foreach ($logs as $log) { $page_amount += $log['amount']; $page_visitors += $log['num_tourists']; }

// Monthly summary (12 months rolling for municipality)
$summary = [];
if ($municipality_id) {
    $sumSql = "SELECT DATE_FORMAT(visit_date,'%Y-%m') AS ym, SUM(num_tourists) AS visitors, SUM(amount) AS amount FROM tourism_fee_log WHERE municipality_id = ? GROUP BY ym ORDER BY ym DESC LIMIT 12";
    if ($st = $conn->prepare($sumSql)) {
        $st->bind_param('i', $municipality_id);
        if ($st->execute()) {
            $rs = $st->get_result();
            while ($row = $rs->fetch_assoc()) { $summary[] = $row; }
        }
        $st->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourism Fee Log Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kameron:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Kameron', serif; background: linear-gradient(120deg, #f3f1e8 60%, #e0f7fa 100%); }
        .hero-img { border-radius: 2rem; box-shadow: 0 8px 32px 0 rgba(34, 60, 80, 0.18); }
        .quick-action { background: linear-gradient(120deg, #255D4F 60%, #1e4d70 100%); color: #fff; border-radius: 1rem; font-weight: 600; box-shadow: 0 2px 8px 0 rgba(34, 60, 80, 0.10); transition: background 0.2s, box-shadow 0.2s; }
        .quick-action:hover { background: linear-gradient(120deg, #1e4d70 60%, #255D4F 100%); box-shadow: 0 8px 32px 0 rgba(34, 60, 80, 0.18); }
    </style>
    <script>
    // Profile dropdown toggle
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.querySelector('.profile-btn');
        var dropdown = document.querySelector('.profile-dropdown');
        if (btn && dropdown) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('open');
            });
            document.addEventListener('click', function() {
                dropdown.classList.remove('open');
            });
        }
    });
    </script>
</head>
<body>
    <aside class="fixed left-0 top-16 h-full w-64 bg-[#255D4F] text-white py-6 px-3 flex flex-col shadow-lg z-40">
        <nav class="flex-1 space-y-2">
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/dashboard.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold">
                <i class="fas fa-home mr-3"></i>Dashboard
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourist_spots.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold">
                <i class="fas fa-umbrella-beach mr-3"></i>Tourist Spots
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/festivals.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold">
                <i class="fas fa-calendar-alt mr-3"></i>Festivals
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/itineraries.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold">
                <i class="fas fa-map-marked-alt mr-3"></i>Itineraries
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/update_capacity.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold">
                <i class="fas fa-users-cog mr-3"></i>Update Capacity
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourism_fee_log.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold">
                <i class="fas fa-cash-register mr-3"></i>Tourism Fee Log
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourism_fee_log_list.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold active">
                <i class="fas fa-file-alt mr-3"></i>Fee Log Report
            </a>
        </nav>
        <div class="mt-auto pt-6 border-t border-[#1e4d70]">
            <a href="/tripko-system/tripko-backend/config/confirm_logout.php" class="flex items-center px-4 py-2 text-sm hover:text-[#ffd700] transition-colors">
                <i class="fas fa-sign-out-alt mr-2"></i>Sign Out
            </a>
        </div>
    </aside>
    <main class="ml-64 mt-16 px-6 py-10 min-h-screen bg-[#F3F1E8]">
        <div class="max-w-7xl mx-auto">
            <!-- Hero Section -->
            <section class="hero flex flex-col md:flex-row items-center justify-between mb-12 relative">
                <div class="z-10">
                    <h1 class="text-4xl md:text-5xl font-extrabold mb-4 tracking-tight">Tourism Fee Log Report</h1>
                    <h2 class="text-2xl md:text-3xl font-semibold mb-2">Municipality of <?php echo htmlspecialchars($town_name); ?></h2>
                    <p class="text-lg font-medium opacity-90 mb-6">View and analyze all tourism fee collections for your town's tourist spots. Export, filter, and gain insights for better management.</p>
                </div>
                <img src="https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80" alt="Fee Log Report Hero" class="hero-img hidden md:block rounded-2xl shadow-lg" />
            </section>

            <div class="card p-7 flex flex-col items-start">
                <h2 class="text-xl font-bold mb-5 text-[#255D4F]">Fee Log Report</h2>
                <form class="flex flex-wrap gap-4 mb-6 items-center" method="get">
                    <label class="font-medium">Month:
                        <select name="month" class="border rounded px-3 py-2">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m == $filter_month ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                            <?php endfor; ?>
                        </select>
                    </label>
                    <label class="font-medium">Year:
                        <input type="number" name="year" value="<?= $filter_year ?>" min="2020" max="<?= date('Y') ?>" class="border rounded px-3 py-2">
                    </label>
                    <label class="font-medium">Spot:
                        <select name="spot" class="border rounded px-3 py-2">
                            <option value="0">All</option>
                            <?php foreach ($spots as $sp): ?>
                                <option value="<?= $sp['spot_id'] ?>" <?= $filter_spot == $sp['spot_id'] ? 'selected' : '' ?>><?= htmlspecialchars($sp['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="font-medium">Recorded By:
                        <select name="recorder" class="border rounded px-3 py-2">
                            <option value="0">All</option>
                            <?php foreach ($recorders as $rec): ?>
                                <option value="<?= $rec['user_id'] ?>" <?= $filter_recorder == $rec['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($rec['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="font-medium">Per Page:
                        <select name="per_page" class="border rounded px-3 py-2">
                            <?php foreach ([10,25,50,100] as $pp): ?>
                                <option value="<?= $pp ?>" <?= $pp==$per_page?'selected':'' ?>><?= $pp ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="submit" class="quick-action">Apply</button>
                    <button type="button" class="quick-action export-btn" onclick="window.print()"><i class="fas fa-print mr-1"></i>Print</button>
                    <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourism_fee_log_export.php?month=<?= urlencode($filter_month) ?>&year=<?= urlencode($filter_year) ?>&spot=<?= urlencode($filter_spot) ?>&recorder=<?= urlencode($filter_recorder) ?>" class="quick-action inline-flex items-center px-4 py-2"><i class="fas fa-file-csv mr-2"></i>CSV</a>
                </form>
                <div class="overflow-x-auto w-full">
                    <table class="min-w-full divide-y divide-gray-200" id="feeLogReportTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Spot</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"># Tourists</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount (₱)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-6 text-center text-sm text-gray-500 italic">No fee log entries found for the selected month and year.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td class="px-6 py-3 text-sm text-gray-700"><?= htmlspecialchars($log['visit_date']) ?></td>
                                        <td class="px-6 py-3 text-sm text-gray-700"><?= htmlspecialchars($log['spot_name']) ?></td>
                                        <td class="px-6 py-3 text-sm text-gray-700"><?= htmlspecialchars($log['name']) ?></td>
                                        <td class="px-6 py-3 text-sm text-gray-700"><?= htmlspecialchars($log['num_tourists']) ?></td>
                                        <td class="px-6 py-3 text-sm text-gray-700">₱<?= number_format($log['amount'], 2) ?></td>
                                        <td class="px-6 py-3 text-sm text-gray-700"><?= htmlspecialchars($log['recorded_by'] ?? '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="font-bold bg-[#e0e7ef]">
                                    <td colspan="3">Page Total</td>
                                    <td><?= $page_visitors ?></td>
                                    <td>₱<?= number_format($page_amount, 2) ?></td>
                                    <td></td>
                                </tr>
                                <tr class="font-bold bg-[#d4dde7]">
                                    <td colspan="3">All (Filtered) Total</td>
                                    <td><?= $agg_visitors ?></td>
                                    <td>₱<?= number_format($agg_amount, 2) ?></td>
                                    <td></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($logs) && $total_pages>1): ?>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <?php
                        // Build base query string preserving filters & per_page
                        $base_q = http_build_query([
                            'month'=>$filter_month,
                            'year'=>$filter_year,
                            'spot'=>$filter_spot,
                            'recorder'=>$filter_recorder,
                            'per_page'=>$per_page
                        ]);
                        $show_pages = [];
                        $window = 2; // pages around current
                        $start = max(1, $page-$window);
                        $end = min($total_pages, $page+$window);
                        if ($start>1) { $show_pages[] = 1; if ($start>2) $show_pages[] = '...'; }
                        for ($p=$start;$p<=$end;$p++) { $show_pages[] = $p; }
                        if ($end<$total_pages) { if ($end<$total_pages-1) $show_pages[] = '...'; $show_pages[] = $total_pages; }
                    ?>
                    <span class="text-sm text-gray-600 mr-2">Page <?= $page ?> of <?= $total_pages ?> (<?= number_format($total_count) ?> rows)</span>
                    <div class="flex items-center flex-wrap gap-1">
                        <?php if ($page>1): ?>
                            <a class="px-3 py-1 bg-[#255D4F] text-white rounded text-sm" href="?<?= $base_q ?>&page=<?= $page-1 ?>">Prev</a>
                        <?php endif; ?>
                        <?php foreach ($show_pages as $p): ?>
                            <?php if ($p==='...'): ?>
                                <span class="px-2 text-gray-500">...</span>
                            <?php elseif ($p==$page): ?>
                                <span class="px-3 py-1 bg-[#1e4d70] text-white rounded text-sm font-semibold"><?= $p ?></span>
                            <?php else: ?>
                                <a class="px-3 py-1 bg-[#255D4F] text-white rounded text-sm" href="?<?= $base_q ?>&page=<?= $p ?>"><?= $p ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($page<$total_pages): ?>
                            <a class="px-3 py-1 bg-[#255D4F] text-white rounded text-sm" href="?<?= $base_q ?>&page=<?= $page+1 ?>">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($summary)): ?>
                <div class="w-full mt-10">
                    <h3 class="text-lg font-semibold mb-3 text-[#255D4F]">Last 12 Months Summary</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Visitors</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount (₱)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($summary as $row): ?>
                                    <tr>
                                        <td class="px-6 py-3 text-sm text-gray-700"><?= htmlspecialchars($row['ym']) ?></td>
                                        <td class="px-6 py-3 text-sm text-gray-700"><?= number_format($row['visitors']) ?></td>
                                        <td class="px-6 py-3 text-sm text-gray-700">₱<?= number_format($row['amount'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
