<?php
include_once('../../../tripko-backend/config/Database.php');
include_once('../../../tripko-backend/models/TouristSpot.php');

$spot_id = isset($_GET['id']) ? $_GET['id'] : die('ERROR: Missing ID.');

$database = new Database();
$db = $database->getConnection();
$spot = new TouristSpot($db);
$spot->spot_id = $spot_id;
$result = $spot->read_single();

if (!$result) {
    die('ERROR: Tourist spot not found.');
}

// If spot is inactive, only show to admin users
if ($result['status'] === 'inactive' && !isset($_SESSION['is_admin'])) {
    die('ERROR: This tourist spot is currently not available.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($result['name']); ?> - Tripko</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="homepage.php" class="flex items-center">
                        <i class="fas fa-compass text-2xl text-[#255D8A]"></i>
                        <span class="ml-2 text-xl font-semibold text-gray-900">Tripko</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="homepage.php" class="text-gray-600 hover:text-[#255D8A]">Home</a>
                    <a href="tourist_spots.php" class="text-gray-600 hover:text-[#255D8A]">Tourist Spots</a>
                    <a href="#" class="text-gray-600 hover:text-[#255D8A]">About</a>
                    <a href="#" class="text-gray-600 hover:text-[#255D8A]">Contact</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="relative h-96">
                <img src="/TripKo-System/uploads/<?php echo htmlspecialchars($result['image_path']); ?>" 
                     alt="<?php echo htmlspecialchars($result['name']); ?>"
                     class="w-full h-full object-cover"
                     onerror="this.src='../images/placeholder.jpg'">
                <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-t from-black/60 to-transparent"></div>                <div class="absolute bottom-0 left-0 p-6 text-white">
                    <h1 class="text-4xl font-bold mb-2"><?php echo htmlspecialchars($result['name']); ?></h1>
                    <div class="flex items-center space-x-4">
                        <span class="px-3 py-1 bg-[#255D8A] rounded-full text-sm font-semibold">
                            <?php echo htmlspecialchars($result['category']); ?></span>
                        <?php if ($result['status'] === 'inactive'): ?>
                            <span class="px-3 py-1 bg-red-600 rounded-full text-sm font-semibold">Inactive</span>
                        <?php endif; ?>
                        </span>
                        <span class="flex items-center">
                            <i class="fas fa-map-marker-alt mr-2"></i>
                            <?php echo htmlspecialchars($result['town_name']); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <div class="mb-6">
                    <h2 class="text-2xl font-semibold mb-4">About this place</h2>
                    <p class="text-gray-700 leading-relaxed">
                        <?php echo htmlspecialchars($result['description']); ?>
                    </p>
                </div>

                <?php if (!empty($result['contact_info'])): ?>
                <div class="mb-6">
                    <h2 class="text-2xl font-semibold mb-4">Contact Information</h2>
                    <div class="flex items-center text-gray-700">
                        <i class="fas fa-phone-alt mr-2"></i>
                        <?php echo htmlspecialchars($result['contact_info']); ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="flex justify-between items-center mt-8">
                    <a href="tourist_spots.php" class="inline-flex items-center text-[#255D8A] hover:text-[#1e4d70]">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Tourist Spots
                    </a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
