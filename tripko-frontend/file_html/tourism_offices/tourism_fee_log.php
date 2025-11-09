<?php
// Tourism Fee Log - Municipality/Tourism Officer Desk
require_once '../../../tripko-backend/config/db.php';

// Fetch tourist spots for dropdown (only those belonging to this municipality)
$town_name = '';
if ($municipality_id) {
    $sql = "SELECT name FROM towns WHERE town_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $municipality_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $town_name = $row['name'];
    }
    $stmt->close();
}
$municipality_id = $_SESSION['municipality_id'] ?? 0; // Adjust session key as needed
$spots = [];
if ($municipality_id) {
    $sql = "SELECT spot_id, name FROM tourist_spots WHERE town_id = ? AND status = 'active' ORDER BY name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $municipality_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $spots[] = $row;
    }
    $stmt->close();
}

// Handle form submission
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $num_tourists = intval($_POST['num_tourists'] ?? 0);
    $visit_date = $_POST['visit_date'] ?? '';
    $spot_id = intval($_POST['spot_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);

    if ($num_tourists > 0 && $visit_date && $spot_id > 0 && $amount > 0) {
        $sql = "INSERT INTO tourism_fee_log (municipality_id, spot_id, name, num_tourists, visit_date, amount) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iisisd', $municipality_id, $spot_id, $name, $num_tourists, $visit_date, $amount);
        if ($stmt->execute()) {
            $success = true;
        } else {
            $error = 'Database error: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = 'Please fill in all required fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourism Fee Log</title>
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
    <!-- Navigation -->
    <nav class="bg-[#255D4F] text-white px-6 py-3 fixed w-full top-0 z-50 shadow-md">
        <div class="flex justify-between items-center max-w-7xl mx-auto">
            <div class="flex items-center space-x-4">
                <span class="text-2xl font-extrabold tracking-tight">TripKo Admin</span>
                <span class="hidden sm:inline text-lg font-medium opacity-80">| <?php echo htmlspecialchars($town_name); ?></span>
            </div>
            <div class="relative group">
                <button class="flex items-center space-x-2 focus:outline-none">
                    <span class="hidden md:inline font-semibold">Account</span>
                    <i class="fas fa-user-circle text-2xl"></i>
                    <i class="fas fa-chevron-down ml-1 text-xs"></i>
                </button>
                <div class="absolute right-0 mt-2 w-40 bg-white text-gray-800 rounded shadow-lg opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity duration-200 z-50">
                    <a href="#" class="block px-4 py-2 hover:bg-gray-100">Profile</a>
                    <a href="/tripko-system/tripko-backend/config/confirm_logout.php" class="block px-4 py-2 hover:bg-gray-100"><i class="fas fa-sign-out-alt mr-2"></i>Sign Out</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
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
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourism_fee_log.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold active">
                <i class="fas fa-cash-register mr-3"></i>Tourism Fee Log
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourism_fee_log_list.php" class="sidebar-link flex items-center py-2.5 px-4 rounded-lg font-semibold">
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
                    <h1 class="text-4xl md:text-5xl font-extrabold mb-4 tracking-tight">Tourism Fee Log</h1>
                    <h2 class="text-2xl md:text-3xl font-semibold mb-2">Municipality of <?php echo htmlspecialchars($town_name); ?></h2>
                    <p class="text-lg font-medium opacity-90 mb-6">Log and manage all tourism fee collections for your town's tourist spots.</p>
                </div>
                <img src="https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80" alt="Fee Log Hero" class="hero-img hidden md:block" />
            </section>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 mb-10">
                <!-- Log Fee Form -->
                <div class="card bg-white rounded-2xl shadow-lg p-7 flex flex-col items-start">
                    <h2 class="text-xl font-bold mb-5 text-[#255D4F]">Log Tourism Fee</h2>
                    <?php if ($success): ?>
                        <div class="success text-green-600 font-semibold text-center mb-2">Entry saved successfully!</div>
                    <?php elseif ($error): ?>
                        <div class="error text-red-600 font-semibold text-center mb-2"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="post" class="w-full flex flex-col gap-4">
                        <label class="font-medium">Name (optional):
                            <input type="text" name="name" maxlength="100" placeholder="Tourist Name (optional)" class="border rounded px-3 py-2">
                        </label>
                        <label class="font-medium">Number of Tourists:
                            <input type="number" name="num_tourists" min="1" required class="border rounded px-3 py-2">
                        </label>
                        <label class="font-medium">Date of Visit:
                            <input type="date" name="visit_date" required class="border rounded px-3 py-2">
                        </label>
                        <label class="font-medium">Tourist Spot:
                            <select name="spot_id" required class="border rounded px-3 py-2">
                                <option value="">Select a spot</option>
                                <?php foreach ($spots as $spot): ?>
                                    <option value="<?= $spot['spot_id'] ?>"><?= htmlspecialchars($spot['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="font-medium">Amount Paid (₱):
                            <input type="number" name="amount" min="1" step="0.01" required class="border rounded px-3 py-2">
                        </label>
                        <button type="submit" class="quick-action">Save Entry</button>
                    </form>
                </div>
                <!-- Fee Log Table -->
                <div class="card bg-white rounded-2xl shadow-lg p-7 flex flex-col items-start">
                    <h2 class="text-xl font-bold mb-5 text-[#255D4F]">Recent Fee Logs</h2>
                    <div class="overflow-x-auto w-full">
                        <table class="min-w-full divide-y divide-gray-200" id="feeLogTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Spot</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"># Tourists</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount (₱)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT l.*, s.name AS spot_name FROM tourism_fee_log l JOIN tourist_spots s ON l.spot_id = s.spot_id WHERE l.municipality_id = ? ORDER BY l.visit_date DESC, l.id DESC LIMIT 10";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param('i', $municipality_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                while ($log = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-3 text-sm text-gray-700"><?= htmlspecialchars($log['visit_date']) ?></td>
                                        <td class="px-6 py-3 text-sm text-gray-700"><?= htmlspecialchars($log['spot_name']) ?></td>
                                        <td class="px-6 py-3 text-sm text-gray-700"><?= htmlspecialchars($log['name']) ?></td>
                                        <td class="px-6 py-3 text-sm text-gray-700"><?= htmlspecialchars($log['num_tourists']) ?></td>
                                        <td class="px-6 py-3 text-sm text-gray-700">₱<?= number_format($log['amount'], 2) ?></td>
                                    </tr>
                                <?php endwhile; $stmt->close(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
