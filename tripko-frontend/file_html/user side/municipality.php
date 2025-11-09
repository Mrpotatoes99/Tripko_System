<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Database connection
$host = "localhost";     
$username = "root";      
$password = "";         
$database = "tripko_db"; 

$password = "";
$conn = new mysqli($host, $username, $password, $database, 3307);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get municipality ID and spot ID from URL
$town_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$spot_id = isset($_GET['spot']) ? (int)$_GET['spot'] : 0;

// Fetch municipality details and its tourist spots
$query = "SELECT t.town_name, ts.* 
          FROM towns t 
          LEFT JOIN tourist_spots ts ON t.town_id = ts.town_id AND ts.status = 'active'
          WHERE t.town_id = ? " . 
          ($spot_id ? "AND ts.spot_id = ?" : "") .
          " ORDER BY ts.name ASC";

$stmt = $conn->prepare($query);
if ($spot_id) {
    $stmt->bind_param("ii", $town_id, $spot_id);
} else {
    $stmt->bind_param("i", $town_id);
}
$stmt->execute();
$result = $stmt->get_result();

// Get town name directly
$town_query = "SELECT town_name FROM towns WHERE town_id = ?";
$town_stmt = $conn->prepare($town_query);
$town_stmt->bind_param("i", $town_id);
$town_stmt->execute();
$town_result = $town_stmt->get_result();
$town_name = $town_result->fetch_assoc()['town_name'] ?? 'Unknown Municipality';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourist Spots in <?php echo htmlspecialchars($town_name); ?></title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cedarville+Cursive&display=swap');
          * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'incolosoLata';
        }

        body {
            line-height: 1.6;
            overflow-x: hidden;
        }

        .header-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 100px;
            padding: 0 40px;
            position: relative;
        }
        
        .back-button {
            position: absolute;
            left: 40px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 10px 20px;
            background-color: #255D8A;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .back-button.clicked {
            background-color: #1a4361;
        }

        .hero_title {
        color: #255D8A;
        font-size: 2.0em;
        font-family: 'Cedarville Cursive', cursive;
        text-align: center;
        margin: 0; 
        padding: 20px;
        position: relative;
        z-index: 1;
        }
        
        .navbar {
            background-color: rgba(37, 93, 138, 0.95);
            padding: 0%;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            backdrop-filter: blur(5px);
        }

        .nav-content {
            max-width: 1500px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo i {
            font-size: 1.8rem;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            flex-wrap: nowrap;
            padding: 0 2rem;
            margin-right: 15px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            white-space: nowrap;
        }

        .nav-links a:hover {
            color: #ffd700;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .menu-btn {
            display: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Dropdown styles */
        .nav-dropdown {
            position: relative;
            display: inline-block;
        }

        .nav-dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: rgba(37, 93, 138, 0.95);
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-radius: 4px;
            z-index: 1001;
        }

        .nav-dropdown-content.show {
            display: block;
        }

        .nav-dropdown-content a {
            color: white;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: all 0.3s;
        }

        .nav-dropdown-content a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #ffd700;
        }

        /* Dropdown arrow */
        .nav-dropdown > a::after {
            content: '▼';
            font-size: 0.8em;
            margin-left: 5px;
            display: inline-block;
            transition: transform 0.3s;
        }

        .nav-dropdown.active > a::after {
            transform: rotate(180deg);
        }

        @media (max-width: 1024px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: #255D8A;
                flex-direction: column;
                padding: 1rem;
                text-align: center;
                gap: 0.5rem;
            }

            .nav-links.active {
                display: flex;
            }

            .nav-links a {
                width: 100%;
                padding: 0.75rem;
            }

            .menu-btn {
                display: block;
            }
        }

        .scroll-container {
            display: flex;
            overflow-x: scroll;
            gap: 20px;
            padding: 20px 40px;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            margin: 20px auto;
            max-width: 1500px;
            flex-wrap: nowrap;
        }

        .scroll-container::-webkit-scrollbar {
            height: 8px;
        }

        .scroll-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .scroll-container::-webkit-scrollbar-thumb {
            background-color: #255D8A;
            border-radius: 20px;
        }

        .card {
            flex: 0 0 300px; /* This is important */
            width: 300px;
            min-width: 300px; /* This ensures fixed width */
            border: 2px solid #255D8A;
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s ease;
            margin-right: 20px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0,1);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            display:block;
        }

        .card-content {
            padding: 15px;
        }

        .spot-name {
            font-size: 1.2em;
            color: #255D8A;
            margin: 0;
            text-align: center;
        }

</style>
</head>
<body>
<!-- Navigation -->
<nav class="navbar">
        <div class="nav-content">
            <div class="logo">
                TripKo Pangasinan
            </div>
            <div class="nav-links">
                <a href="../homepage.php"><i class='bx bxs-home'></i> Home</a>
                <div class="nav-dropdown">
                    <a href="#"><i class='bx bxs-map-alt'></i> Places to Go</a>
                    <div class="nav-dropdown-content">
                        <a href="places-to-go.php">Beaches</a>
                        <a href="islands-to-go.php">Islands</a>
                        <a href="waterfalls-to-go.php">Waterfalls</a>
                        <a href="caves-to-go.php">Caves</a>
                        <a href="churches-to-go.php">Churches</a>
                        <a href="festivals-to-go.php">Festivals</a>
                    </div>
                </div>
                <a href="../things-to-do.php"><i class='bx bxs-calendar-star'></i> Things to Do</a>
                <a href="../routeFinder.php"><i class='bx bxs-bus'></i> Route Finder</a>
                <a href="tourist_capacity.php"><i class='bx bxs-check-circle'></i> Tourist Spot Status</a>
                <a href="#"><i class='bx bxs-book-content'></i> Directory</a>
                <a href="#"><i class='bx bxs-info-circle'></i> About Us</a>
                <a href="#"><i class='bx bxs-phone'></i> Contact Us</a>
                <a href="/tripko-system/tripko-backend/logout.php"><i class='bx bx-log-out'></i> Logout</a>
            </div>
            <div class="menu-btn">
                <i class='bx bx-menu'></i>
            </div>
        </div>
    </nav>

       <div class="header-container">
            <a href="javascript:history.back()" class="back-button">
                <i class='bx bx-arrow-back'></i> Back
            </a>
            <h1 class="hero_title">Tourist Spots in <?php echo htmlspecialchars($town_name); ?></h1>
        </div>
    
    <?php
    if ($result) {
    echo "<!-- Number of rows: " . $result->num_rows . " -->";
} else {
    echo "<!-- Query failed -->";
}
?>

    <div class="scroll-container">    <?php 
    if ($result && $result->num_rows > 0): 
        while ($row = $result->fetch_assoc()): 
            if ($row['spot_id']): // Only show if there's actually a tourist spot
    ?>
        <div class="card">
            <img src="<?php 
                $imagePath = $row['image_path'];
                if (!$imagePath || $imagePath === 'placeholder.jpg') {
                    echo '../../../assets/images/placeholder.jpg';
                } else {
                    echo '../../../uploads/' . htmlspecialchars($imagePath);
                }
            ?>" 
            alt="<?php echo htmlspecialchars($row['name']); ?>"
            onerror="this.src='../../../assets/images/placeholder.jpg'">
            <div class="card-content">
                <h3 class="spot-name"><?php echo htmlspecialchars($row['name']); ?></h3>
            </div>
        </div>
    <?php 
            endif;
        endwhile;
    endif; 
    ?>
</div>

    <?php if ($result->num_rows === 0): ?>
        <div style="text-align: center; padding: 20px;">
            <p>No tourist spots found for this municipality.</p>
        </div>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const menuBtn = document.querySelector('.menu-btn');
        const navLinks = document.querySelector('.nav-links');
        const dropdownBtn = document.querySelector('.nav-dropdown > a');
        const dropdownContent = document.querySelector('.nav-dropdown-content');
        const dropdown = document.querySelector('.nav-dropdown');
        
        menuBtn?.addEventListener('click', () => {
            navLinks?.classList.toggle('active');
        });

        // Handle dropdown click
        dropdownBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            dropdownContent?.classList.toggle('show');
            dropdown?.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.nav-dropdown')) {
                dropdownContent?.classList.remove('show');
                dropdown?.classList.remove('active');
            }
            if (!e.target.closest('.nav-content')) {
                navLinks?.classList.remove('active');
            }
        });
    });
</script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>