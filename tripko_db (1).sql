-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Nov 08, 2025 at 03:53 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tripko_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `check_spots_missing_coordinates` ()   BEGIN
    SELECT 
        ts.spot_id,
        ts.name,
        t.name AS town,
        ts.category,
        '❌ No coordinates - Google Maps disabled' AS status
    FROM tourist_spots ts
    LEFT JOIN towns t ON ts.town_id = t.town_id
    LEFT JOIN geo_points gp ON gp.entity_type = 'tourist_spot' AND gp.entity_id = ts.spot_id
    WHERE ts.status = 'active' 
    AND gp.latitude IS NULL
    ORDER BY ts.spot_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_office_content` (IN `p_user_id` INT, IN `p_content_type` VARCHAR(50))   BEGIN
    DECLARE v_user_type_id INT;
    DECLARE v_office_id INT;
    
    -- Get user type
    SELECT user_type_id INTO v_user_type_id
    FROM user WHERE user_id = p_user_id;
    
    -- Get office ID if user is tourism officer
    SELECT office_id INTO v_office_id
    FROM tourism_office WHERE user_id = p_user_id;
    
    -- If super admin, show all content
    IF v_user_type_id = 1 THEN
        CASE p_content_type
            WHEN 'tourist_spot' THEN
                SELECT * FROM vw_tourist_spots_by_office;
            WHEN 'festival' THEN
                SELECT * FROM vw_festivals_by_office;
            WHEN 'itinerary' THEN
                SELECT * FROM vw_itineraries_by_office;
        END CASE;
    -- If tourism officer, show only their municipality's content
    ELSE
        CASE p_content_type
            WHEN 'tourist_spot' THEN
                SELECT * FROM vw_tourist_spots_by_office 
                WHERE office_id = v_office_id;
            WHEN 'festival' THEN
                SELECT * FROM vw_festivals_by_office 
                WHERE office_id = v_office_id;
            WHEN 'itinerary' THEN
                SELECT * FROM vw_itineraries_by_office 
                WHERE office_id = v_office_id;
        END CASE;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `update_itinerary_review_stats` (IN `p_itinerary_id` INT)   BEGIN
    DECLARE v_total_reviews INT;
    DECLARE v_avg_rating DECIMAL(3,2);
    
    -- Calculate statistics
    SELECT 
        COUNT(*),
        ROUND(AVG(rating), 2)
    INTO 
        v_total_reviews,
        v_avg_rating
    FROM reviews
    WHERE entity_type = 'itinerary'
      AND entity_id = p_itinerary_id
      AND status = 'active';
    
    -- Update itinerary record (update all rows with same itinerary_id)
    UPDATE itineraries
    SET 
        total_reviews = COALESCE(v_total_reviews, 0),
        average_rating = COALESCE(v_avg_rating, 0.00)
    WHERE itinerary_id = p_itinerary_id;
    
    -- Don't return result set (removed SELECT statement for trigger compatibility)
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `get_google_maps_url` (`p_spot_id` INT) RETURNS VARCHAR(500) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC READS SQL DATA BEGIN
    DECLARE v_url VARCHAR(500);
    
    SELECT CONCAT(
        'https://www.google.com/maps/dir/?api=1&destination=',
        gp.latitude, ',', gp.longitude
    )
    INTO v_url
    FROM geo_points gp
    WHERE gp.entity_type = 'tourist_spot'
    AND gp.entity_id = p_spot_id;
    
    RETURN COALESCE(v_url, NULL);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `barangay`
--

CREATE TABLE `barangay` (
  `barangay_id` int(11) NOT NULL,
  `barangay_name` varchar(255) NOT NULL,
  `town_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_travelers`
--

CREATE TABLE `booking_travelers` (
  `traveler_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `pricing_tier_id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `price_paid` decimal(10,2) NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Individual traveler details for bookings';

-- --------------------------------------------------------

--
-- Table structure for table `change_password_2fa_codes`
--

CREATE TABLE `change_password_2fa_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `change_password_2fa_codes`
--

INSERT INTO `change_password_2fa_codes` (`id`, `user_id`, `code`, `expires_at`, `verified`, `verified_at`, `created_at`) VALUES
(1, 10, '635633', '2025-11-08 10:01:40', 0, NULL, '2025-11-08 01:51:40');

-- --------------------------------------------------------

--
-- Table structure for table `email_verification_codes`
--

CREATE TABLE `email_verification_codes` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `code` char(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verification_codes`
--

INSERT INTO `email_verification_codes` (`id`, `email`, `code`, `expires_at`, `verified`, `verified_at`, `created_at`) VALUES
(1, 'secretqt000000001@gmail.com', '847491', '2025-09-28 21:51:10', 1, '2025-09-28 21:41:37', '2025-09-28 21:41:10'),
(2, 'balancleymark@gmail.com', '626060', '2025-10-31 10:30:26', 1, '2025-10-31 10:21:18', '2025-10-31 10:20:26'),
(3, 'test@gmail.com', '123710', '2025-09-28 15:47:32', 0, NULL, '2025-09-28 21:37:32'),
(4, 'test2@gmail.com', '458190', '2025-09-28 21:49:43', 1, '2025-09-28 21:39:54', '2025-09-28 21:39:43'),
(7, 'cleymark19@gmail.com', '656768', '2025-10-31 10:49:04', 1, '2025-10-31 10:39:36', '2025-10-31 10:39:04'),
(18, 'asdnakdjasn@gmail.com', '884744', '2025-11-04 22:06:37', 0, NULL, '2025-11-04 21:56:37'),
(19, 'allenmangrobang26@gmail.com', '690255', '2025-11-04 22:16:36', 0, NULL, '2025-11-04 22:06:36'),
(20, 'manoknapula749@gmail.com', '607051', '2025-11-04 22:14:20', 0, NULL, '2025-11-04 22:04:20'),
(22, 'dranrevsmith@gmail.com', '403557', '2025-11-04 22:27:18', 0, NULL, '2025-11-04 22:17:18');

-- --------------------------------------------------------

--
-- Table structure for table `fares`
--

CREATE TABLE `fares` (
  `fare_id` int(11) NOT NULL,
  `from_terminal_id` int(11) NOT NULL,
  `to_terminal_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fares`
--

INSERT INTO `fares` (`fare_id`, `from_terminal_id`, `to_terminal_id`, `category`, `amount`, `status`, `type_id`) VALUES
(1, 9, 8, 'Student', 98.00, 'active', 1),
(2, 9, 5, 'Regular', 160.00, 'active', 2);

-- --------------------------------------------------------

--
-- Table structure for table `festivals`
--

CREATE TABLE `festivals` (
  `festival_id` int(11) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `date` date DEFAULT NULL,
  `town_id` int(11) DEFAULT NULL,
  `image_path` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `festivals`
--

INSERT INTO `festivals` (`festival_id`, `name`, `description`, `date`, `town_id`, `image_path`, `status`, `created_at`) VALUES
(7, 'Binungey Festival', 'Wonderful', '2025-09-20', 5, '6823fae6b8c99_binongey-festival-3-300x450.jpg', 'active', '2025-09-09 13:23:12'),
(8, 'Pakwan Festival', '\r\nPakwan Festival 2017 (Calendar of Activities) - Pride of ...The Pakwan Festival is an annual watermelon festival held in the municipality of Bani, Pangasinan, celebrating its reputation as the \"Watermelon Capital of the Philippines\". The festival takes place around the last week of January and features activities like an agri-trade fair, watermelon carving, street dancing, a farmer\'s cookfest, and mass watermelon eating, all aimed at promoting Bani\'s sweet watermelons, its farmers, and its tourist attractions. ', '2010-01-24', 8, '68bfb9d8617a6_1757395416.jpg', 'active', '2025-09-09 13:23:36');

--
-- Triggers `festivals`
--
DELIMITER $$
CREATE TRIGGER `after_festival_insert` AFTER INSERT ON `festivals` FOR EACH ROW BEGIN
    INSERT INTO tourism_office_content (office_id, content_type, content_reference_id)
    SELECT toff.office_id, 'festival', NEW.festival_id
    FROM tourism_office toff
    WHERE toff.town_id = NEW.town_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `geo_points`
--

CREATE TABLE `geo_points` (
  `entity_type` enum('tourist_spot','festival','town','terminal') NOT NULL,
  `entity_id` int(10) UNSIGNED NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `accuracy` enum('exact','approximate','centroid','imported') DEFAULT 'exact',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `geo_points`
--

INSERT INTO `geo_points` (`entity_type`, `entity_id`, `latitude`, `longitude`, `accuracy`, `updated_at`) VALUES
('tourist_spot', 1, 16.3864000, 119.8894000, 'exact', '2025-10-20 02:02:41'),
('tourist_spot', 5, 16.1989000, 120.0108000, 'exact', '2025-10-20 02:02:41'),
('tourist_spot', 6, 16.3867000, 119.8905000, 'exact', '2025-10-20 02:02:41'),
('tourist_spot', 11, 16.0753000, 119.7997000, 'exact', '2025-10-20 02:02:41'),
('tourist_spot', 12, 16.3750000, 119.8944000, 'exact', '2025-10-20 02:02:41'),
('tourist_spot', 13, 15.8122000, 120.4558000, 'exact', '2025-10-20 02:02:42'),
('tourist_spot', 14, 16.1167000, 119.8000000, 'approximate', '2025-10-20 02:02:42'),
('tourist_spot', 15, 16.0800000, 119.8050000, 'approximate', '2025-10-20 02:02:42'),
('tourist_spot', 16, 16.1100000, 119.8100000, 'approximate', '2025-10-20 02:02:42'),
('tourist_spot', 17, 16.1167000, 119.8072000, 'exact', '2025-10-20 02:02:42'),
('tourist_spot', 18, 16.0431000, 120.4858000, 'exact', '2025-10-20 02:02:42'),
('tourist_spot', 19, 16.3914000, 119.9025000, 'exact', '2025-10-20 02:02:41'),
('tourist_spot', 20, 16.1731000, 119.8714000, 'exact', '2025-10-20 02:02:42'),
('tourist_spot', 21, 16.1650000, 119.8600000, 'approximate', '2025-10-20 02:02:42'),
('tourist_spot', 22, 16.1700000, 119.8550000, 'approximate', '2025-10-20 02:02:42'),
('tourist_spot', 24, 16.1800000, 119.8500000, 'approximate', '2025-10-20 02:02:42'),
('tourist_spot', 25, 16.1583000, 119.8639000, 'exact', '2025-10-20 02:02:42');

--
-- Triggers `geo_points`
--
DELIMITER $$
CREATE TRIGGER `validate_geo_point_before_insert` BEFORE INSERT ON `geo_points` FOR EACH ROW BEGIN
    -- Validate latitude range (-90 to 90)
    IF NEW.latitude < -90 OR NEW.latitude > 90 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid latitude: must be between -90 and 90';
    END IF;
    
    -- Validate longitude range (-180 to 180)
    IF NEW.longitude < -180 OR NEW.longitude > 180 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid longitude: must be between -180 and 180';
    END IF;
    
    -- Auto-set updated_at
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `validate_geo_point_before_update` BEFORE UPDATE ON `geo_points` FOR EACH ROW BEGIN
    -- Validate latitude range
    IF NEW.latitude < -90 OR NEW.latitude > 90 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid latitude: must be between -90 and 90';
    END IF;
    
    -- Validate longitude range
    IF NEW.longitude < -180 OR NEW.longitude > 180 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid longitude: must be between -180 and 180';
    END IF;
    
    -- Auto-update timestamp
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `itineraries`
--

CREATE TABLE `itineraries` (
  `id` int(11) NOT NULL,
  `itinerary_id` int(11) DEFAULT NULL,
  `creator_user_id` int(11) DEFAULT NULL,
  `visibility` enum('private','unlisted','public') NOT NULL DEFAULT 'public',
  `published_at` datetime DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `town_id` int(11) DEFAULT NULL,
  `destination_id` int(11) DEFAULT NULL,
  `environmental_fee` varchar(100) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  `day_number` int(11) DEFAULT NULL,
  `day_title` varchar(150) DEFAULT NULL,
  `day_description` text DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `spot_id` int(11) DEFAULT NULL,
  `custom_name` varchar(255) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `estimated_duration_minutes` int(11) DEFAULT NULL,
  `travel_minutes_from_prev` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `base_price` decimal(10,2) DEFAULT NULL COMMENT 'Starting price per person',
  `price_currency` varchar(3) DEFAULT 'PHP' COMMENT 'Currency code (PHP, USD, etc.)',
  `duration_hours` int(11) DEFAULT NULL COMMENT 'Total duration in hours',
  `mobile_ticket` tinyint(1) DEFAULT 1 COMMENT 'Mobile ticket available',
  `instant_confirmation` tinyint(1) DEFAULT 1 COMMENT 'Instant confirmation available',
  `free_cancellation` tinyint(1) DEFAULT 1 COMMENT 'Free cancellation available',
  `cancellation_hours` int(11) DEFAULT 24 COMMENT 'Hours before for free cancellation',
  `min_travelers` int(11) DEFAULT 1 COMMENT 'Minimum number of travelers',
  `max_travelers` int(11) DEFAULT 50 COMMENT 'Maximum number of travelers',
  `highlights` text DEFAULT NULL COMMENT 'Bullet points of tour highlights (JSON array)',
  `whats_included` text DEFAULT NULL COMMENT 'What is included (JSON array)',
  `whats_excluded` text DEFAULT NULL COMMENT 'What is excluded (JSON array)',
  `what_to_bring` text DEFAULT NULL COMMENT 'What travelers should bring (JSON array)',
  `accessibility_info` text DEFAULT NULL COMMENT 'Accessibility information',
  `additional_info` text DEFAULT NULL COMMENT 'Additional information (JSON array)',
  `meeting_point` text DEFAULT NULL COMMENT 'Meeting point description',
  `end_point` text DEFAULT NULL COMMENT 'End point description',
  `pickup_offered` tinyint(1) DEFAULT 0 COMMENT 'Hotel pickup available',
  `pickup_details` text DEFAULT NULL COMMENT 'Pickup details and locations',
  `total_reviews` int(11) DEFAULT 0 COMMENT 'Cached total review count',
  `average_rating` decimal(3,2) DEFAULT 0.00 COMMENT 'Cached average rating'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `itineraries`
--

INSERT INTO `itineraries` (`id`, `itinerary_id`, `creator_user_id`, `visibility`, `published_at`, `name`, `description`, `town_id`, `destination_id`, `environmental_fee`, `image_path`, `created_at`, `status`, `day_number`, `day_title`, `day_description`, `item_id`, `spot_id`, `custom_name`, `start_time`, `end_time`, `estimated_duration_minutes`, `travel_minutes_from_prev`, `notes`, `sort_order`, `base_price`, `price_currency`, `duration_hours`, `mobile_ticket`, `instant_confirmation`, `free_cancellation`, `cancellation_hours`, `min_travelers`, `max_travelers`, `highlights`, `whats_included`, `whats_excluded`, `what_to_bring`, `accessibility_info`, `additional_info`, `meeting_point`, `end_point`, `pickup_offered`, `pickup_details`, `total_reviews`, `average_rating`) VALUES
(1, 3, NULL, 'public', '2025-10-01 20:31:05', 'Hundred Islands Day Tour', 'A curated day trip exploring iconic islands, snorkeling spots, and scenic viewpoints within Hundred Islands National Park.', 3, 3, '300', '6813945d4ac34_hundred-island.jpg', '2025-10-01 12:31:05', 'active', 1, 'Day 1', 'Island Hopping Essentials', 1, 5, NULL, '08:00:00', '10:30:00', 150, NULL, 'Boat departure, first island stops, snorkeling.', 1, 1200.00, 'PHP', 8, 1, 1, 1, 24, 2, 20, '[\"Explore 124 islands at low tide\", \"Snorkeling in crystal-clear waters\", \"Visit Governor\'s Island viewpoint\", \"Professional boat and guide included\", \"Lunch on the island\", \"Safety equipment provided\"]', '[\"Boat transportation to islands\", \"Professional tour guide\", \"Environmental fee (₱300)\", \"Life jackets and safety equipment\", \"Snorkeling equipment\", \"Packed lunch or island meal\", \"Drinking water\", \"First aid kit\"]', '[\"Hotel pickup and drop-off\", \"Personal expenses\", \"Additional food and drinks\", \"Optional activities (kayaking, paddle boarding)\", \"Tips and gratuities\", \"Travel insurance\"]', '[\"Swimwear and extra clothes\", \"Sunscreen (reef-safe recommended)\", \"Towel\", \"Waterproof bag for electronics\", \"Hat and sunglasses\", \"Camera or GoPro\", \"Cash for optional activities\", \"Medications if needed\"]', 'Not wheelchair accessible. Moderate physical fitness required. Not recommended for travelers with back problems or pregnant travelers.', '[\"Confirmation received at time of booking\", \"Tour operates rain or shine\", \"Minimum 2 travelers required\", \"Children must be accompanied by an adult\", \"Please advise any specific dietary requirements\", \"Life jackets mandatory for all water activities\"]', 'Lucap Wharf, Alaminos City, Pangasinan. Look for the Hundred Islands National Park ticket booth.', 'Returns to Lucap Wharf, Alaminos City', 0, NULL, 1, 5.00),
(2, 3, NULL, 'public', '2025-10-01 20:31:05', 'Hundred Islands Day Tour', 'A curated day trip exploring iconic islands, snorkeling spots, and scenic viewpoints within Hundred Islands National Park.', 3, 3, '300', '6813945d4ac34_hundred-island.jpg', '2025-10-01 12:31:05', 'active', 1, 'Day 1', 'Island Hopping Essentials', 2, NULL, 'Lunch on Governor\'s Island', '08:00:00', '12:30:00', 60, 15, 'Packed or local vendor meal; hydration check.', 2, 1200.00, 'PHP', 8, 1, 1, 1, 24, 2, 20, '[\"Explore 124 islands at low tide\", \"Snorkeling in crystal-clear waters\", \"Visit Governor\'s Island viewpoint\", \"Professional boat and guide included\", \"Lunch on the island\", \"Safety equipment provided\"]', '[\"Boat transportation to islands\", \"Professional tour guide\", \"Environmental fee (₱300)\", \"Life jackets and safety equipment\", \"Snorkeling equipment\", \"Packed lunch or island meal\", \"Drinking water\", \"First aid kit\"]', '[\"Hotel pickup and drop-off\", \"Personal expenses\", \"Additional food and drinks\", \"Optional activities (kayaking, paddle boarding)\", \"Tips and gratuities\", \"Travel insurance\"]', '[\"Swimwear and extra clothes\", \"Sunscreen (reef-safe recommended)\", \"Towel\", \"Waterproof bag for electronics\", \"Hat and sunglasses\", \"Camera or GoPro\", \"Cash for optional activities\", \"Medications if needed\"]', 'Not wheelchair accessible. Moderate physical fitness required. Not recommended for travelers with back problems or pregnant travelers.', '[\"Confirmation received at time of booking\", \"Tour operates rain or shine\", \"Minimum 2 travelers required\", \"Children must be accompanied by an adult\", \"Please advise any specific dietary requirements\", \"Life jackets mandatory for all water activities\"]', 'Lucap Wharf, Alaminos City, Pangasinan. Look for the Hundred Islands National Park ticket booth.', 'Returns to Lucap Wharf, Alaminos City', 0, NULL, 1, 5.00),
(3, 3, NULL, 'public', '2025-10-01 20:31:05', 'Hundred Islands Day Tour', 'A curated day trip exploring iconic islands, snorkeling spots, and scenic viewpoints within Hundred Islands National Park.', 3, 3, '300', '6813945d4ac34_hundred-island.jpg', '2025-10-01 12:31:05', 'active', 1, 'Day 1', 'Island Hopping Essentials', 3, 5, 'View Deck & Photo Ops', '08:00:00', '13:30:00', 45, 10, 'Scenic lookout and guided interpretation.', 3, 1200.00, 'PHP', 8, 1, 1, 1, 24, 2, 20, '[\"Explore 124 islands at low tide\", \"Snorkeling in crystal-clear waters\", \"Visit Governor\'s Island viewpoint\", \"Professional boat and guide included\", \"Lunch on the island\", \"Safety equipment provided\"]', '[\"Boat transportation to islands\", \"Professional tour guide\", \"Environmental fee (₱300)\", \"Life jackets and safety equipment\", \"Snorkeling equipment\", \"Packed lunch or island meal\", \"Drinking water\", \"First aid kit\"]', '[\"Hotel pickup and drop-off\", \"Personal expenses\", \"Additional food and drinks\", \"Optional activities (kayaking, paddle boarding)\", \"Tips and gratuities\", \"Travel insurance\"]', '[\"Swimwear and extra clothes\", \"Sunscreen (reef-safe recommended)\", \"Towel\", \"Waterproof bag for electronics\", \"Hat and sunglasses\", \"Camera or GoPro\", \"Cash for optional activities\", \"Medications if needed\"]', 'Not wheelchair accessible. Moderate physical fitness required. Not recommended for travelers with back problems or pregnant travelers.', '[\"Confirmation received at time of booking\", \"Tour operates rain or shine\", \"Minimum 2 travelers required\", \"Children must be accompanied by an adult\", \"Please advise any specific dietary requirements\", \"Life jackets mandatory for all water activities\"]', 'Lucap Wharf, Alaminos City, Pangasinan. Look for the Hundred Islands National Park ticket booth.', 'Returns to Lucap Wharf, Alaminos City', 0, NULL, 1, 5.00),
(4, 3, NULL, 'public', '2025-10-01 20:31:05', 'Hundred Islands Day Tour', 'A curated day trip exploring iconic islands, snorkeling spots, and scenic viewpoints within Hundred Islands National Park.', 3, 3, '300', '6813945d4ac34_hundred-island.jpg', '2025-10-01 12:31:05', 'active', 1, 'Day 1', 'Island Hopping Essentials', 4, NULL, 'Kayaking / Optional Activity', '08:00:00', '14:30:00', 45, 15, 'Optional add-on depending on tides and conditions.', 4, 1200.00, 'PHP', 8, 1, 1, 1, 24, 2, 20, '[\"Explore 124 islands at low tide\", \"Snorkeling in crystal-clear waters\", \"Visit Governor\'s Island viewpoint\", \"Professional boat and guide included\", \"Lunch on the island\", \"Safety equipment provided\"]', '[\"Boat transportation to islands\", \"Professional tour guide\", \"Environmental fee (₱300)\", \"Life jackets and safety equipment\", \"Snorkeling equipment\", \"Packed lunch or island meal\", \"Drinking water\", \"First aid kit\"]', '[\"Hotel pickup and drop-off\", \"Personal expenses\", \"Additional food and drinks\", \"Optional activities (kayaking, paddle boarding)\", \"Tips and gratuities\", \"Travel insurance\"]', '[\"Swimwear and extra clothes\", \"Sunscreen (reef-safe recommended)\", \"Towel\", \"Waterproof bag for electronics\", \"Hat and sunglasses\", \"Camera or GoPro\", \"Cash for optional activities\", \"Medications if needed\"]', 'Not wheelchair accessible. Moderate physical fitness required. Not recommended for travelers with back problems or pregnant travelers.', '[\"Confirmation received at time of booking\", \"Tour operates rain or shine\", \"Minimum 2 travelers required\", \"Children must be accompanied by an adult\", \"Please advise any specific dietary requirements\", \"Life jackets mandatory for all water activities\"]', 'Lucap Wharf, Alaminos City, Pangasinan. Look for the Hundred Islands National Park ticket booth.', 'Returns to Lucap Wharf, Alaminos City', 0, NULL, 1, 5.00),
(5, 1, NULL, 'public', NULL, 'Talon ka sa falls', 'tatalon ka ngani', 14, NULL, '60', '68104fd27c4bc_Bolinao Falls.jfif', '2025-04-29 04:04:34', 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, 0.00),
(6, 2, NULL, 'public', NULL, 'Island Hopping', 'Super Cool Activity', 3, NULL, '3000', '682292461e199_5224f4bd-99fc-4970-b011-51253534a3a6-removebg-preview.png', '2025-05-13 00:28:54', 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, 0.00),
(8, NULL, NULL, 'public', NULL, 'Bolinao Beach Escape - 2 Days', 'Experience the pristine beaches and natural wonders of Bolinao! This 2-day adventure takes you to the famous white sand beaches, stunning rock formations, and hidden waterfalls. Perfect for families, couples, and solo travelers looking to relax and explore one of Pangasinan\'s most beautiful coastal destinations.', 14, NULL, NULL, 'bolinao-beach.jpg', '2025-10-20 03:14:30', 'active', NULL, NULL, NULL, NULL, NULL, NULL, '07:00:00', NULL, NULL, NULL, NULL, 0, 1500.00, 'PHP', 16, 1, 1, 1, 24, 2, 15, '[\"Visit the iconic Patar White Beach with powdery white sand\", \"Explore the stunning rock formations and natural pools\", \"Swim in the crystal-clear waters of Bolinao Falls\", \"Enjoy a traditional Filipino lunch with fresh seafood\", \"Watch the beautiful sunset at Cape Bolinao Lighthouse\", \"Comfortable accommodation near the beach\"]', '[\"Round-trip transportation from meeting point\", \"Professional tour guide\", \"All entrance fees to attractions\", \"2 lunches (Day 1 & Day 2)\", \"1 dinner (Day 1)\", \"1 breakfast (Day 2)\", \"Accommodation (1 night, double occupancy)\", \"Snorkeling equipment\", \"First aid kit\", \"Travel insurance\"]', '[\"Personal expenses\", \"Alcoholic beverages\", \"Tips and gratuities\", \"Souvenirs\", \"Additional meals not mentioned\", \"Optional water activities (jet ski, banana boat, etc.)\"]', '[\"Swimwear and extra clothes\", \"Towel and sunscreen\", \"Insect repellent\", \"Waterproof bag for electronics\", \"Camera or smartphone\", \"Personal medications\", \"Cash for personal expenses\"]', 'Not wheelchair accessible. Moderate fitness level required for hiking to waterfalls.', '[\"Minimum of 2 participants required\", \"Children must be accompanied by an adult\", \"Please inform us of any dietary restrictions\", \"Weather dependent - may be rescheduled in case of typhoon\"]', 'Bolinao Tourism Office, Poblacion, Bolinao, Pangasinan', 'Same as meeting point', 0, NULL, 0, 0.00),
(9, 4, NULL, 'public', '2025-10-20 11:22:52', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:22:52', 'active', 1, 'Day 1: Beaches & Landmarks', 'Explore Bolinao\'s most iconic beaches and historical lighthouse', 1, NULL, 'Departure & Travel', '07:00:00', '07:30:00', 30, NULL, 'Meet at Bolinao Tourism Office. Travel to Patar Beach (20 min drive).', 1, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(10, 4, NULL, 'public', '2025-10-20 11:22:52', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:22:52', 'active', 1, 'Day 1: Beaches & Landmarks', 'Explore Bolinao\'s most iconic beaches and historical lighthouse', 2, NULL, 'Patar Beach Swim & Relax', '07:30:00', '12:00:00', 180, 10, 'Enjoy the pristine white sand beach. Swim, sunbathe, and take photos.', 2, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(11, 4, NULL, 'public', '2025-10-20 11:22:52', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:22:52', 'active', 1, 'Day 1: Beaches & Landmarks', 'Explore Bolinao\'s most iconic beaches and historical lighthouse', 3, NULL, 'Lunch at Local Restaurant', '12:00:00', '13:00:00', 60, 5, 'Fresh seafood lunch featuring grilled fish, shrimp, and local vegetables.', 3, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(12, 4, NULL, 'public', '2025-10-20 11:22:52', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:22:52', 'active', 1, 'Day 1: Beaches & Landmarks', 'Explore Bolinao\'s most iconic beaches and historical lighthouse', 4, 6, 'Cape Bolinao Lighthouse', '13:30:00', '15:00:00', 90, 15, 'Climb the historic lighthouse for panoramic ocean views. Photo opportunity.', 4, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(13, 4, NULL, 'public', '2025-10-20 11:22:52', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:22:52', 'active', 1, 'Day 1: Beaches & Landmarks', 'Explore Bolinao\'s most iconic beaches and historical lighthouse', 5, NULL, 'Rock Formations Tour', '15:30:00', '16:30:00', 60, 10, 'Explore unique coastal rock formations and tide pools.', 5, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(14, 4, NULL, 'public', '2025-10-20 11:22:52', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:22:52', 'active', 1, 'Day 1: Beaches & Landmarks', 'Explore Bolinao\'s most iconic beaches and historical lighthouse', 6, NULL, 'Sunset Viewing', '17:00:00', '18:00:00', 60, 5, 'Watch the stunning Bolinao sunset from the beach.', 6, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(15, 4, NULL, 'public', '2025-10-20 11:22:52', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:22:52', 'active', 1, 'Day 1: Beaches & Landmarks', 'Explore Bolinao\'s most iconic beaches and historical lighthouse', 7, NULL, 'Dinner & Free Time', '18:30:00', '20:00:00', 90, 10, 'Seafood dinner at beachfront restaurant. Return to accommodation.', 7, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(16, 4, NULL, 'public', '2025-10-20 11:22:52', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:22:52', 'active', 2, 'Day 2: Caves & Waterfalls', 'Discover hidden caves, waterfalls, and rock formations', 8, 12, 'Enchanted Cave', '08:00:00', '10:00:00', 120, NULL, 'Swim in the mystical underground cave pool with natural lighting.', 1, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(17, 4, NULL, 'public', '2025-10-20 11:22:52', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:22:52', 'active', 2, 'Day 2: Caves & Waterfalls', 'Discover hidden caves, waterfalls, and rock formations', 9, 1, 'Bolinao Falls', '10:30:00', '13:00:00', 150, 20, 'Trek to Bolinao Falls. Swim in natural pools and enjoy the scenery.', 2, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(18, 4, NULL, 'public', '2025-10-20 11:22:52', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:22:52', 'active', 2, 'Day 2: Caves & Waterfalls', 'Discover hidden caves, waterfalls, and rock formations', 10, NULL, 'Lunch Break', '13:00:00', '14:00:00', 60, 5, 'Packed lunch near the falls or local eatery.', 3, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(19, 4, NULL, 'public', '2025-10-20 11:22:52', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:22:52', 'active', 2, 'Day 2: Caves & Waterfalls', 'Discover hidden caves, waterfalls, and rock formations', 11, NULL, 'Snorkeling Adventure', '14:30:00', '16:00:00', 90, 15, 'Snorkel in clear waters. See tropical fish and coral formations.', 4, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(20, 4, NULL, 'public', '2025-10-20 11:22:52', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:22:52', 'active', 2, 'Day 2: Caves & Waterfalls', 'Discover hidden caves, waterfalls, and rock formations', 12, NULL, 'Return Journey', '16:30:00', '17:00:00', 30, 10, 'Travel back to meeting point. End of tour.', 5, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(21, 5, NULL, 'public', '2025-10-20 11:28:51', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:28:51', 'active', 1, 'Day 1: Beaches & Landmarks', 'Explore Bolinao\'s most iconic beaches and historical lighthouse', 1, NULL, 'Departure & Travel', '07:00:00', '07:30:00', 30, NULL, 'Meet at Bolinao Tourism Office. Travel to Patar Beach (20 min drive).', 1, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(22, 5, NULL, 'public', '2025-10-20 11:28:51', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:28:51', 'active', 1, 'Day 1: Beaches & Landmarks', 'Explore Bolinao\'s most iconic beaches and historical lighthouse', 2, NULL, 'Patar Beach Swim & Relax', '07:30:00', '12:00:00', 180, 10, 'Enjoy the pristine white sand beach. Swim, sunbathe, and take photos.', 2, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(23, 5, NULL, 'public', '2025-10-20 11:28:51', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:28:51', 'active', 1, 'Day 1: Beaches & Landmarks', 'Explore Bolinao\'s most iconic beaches and historical lighthouse', 3, NULL, 'Lunch at Local Restaurant', '12:00:00', '13:00:00', 60, 5, 'Fresh seafood lunch featuring grilled fish, shrimp, and local vegetables.', 3, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(24, 5, NULL, 'public', '2025-10-20 11:28:51', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:28:51', 'active', 1, 'Day 1: Beaches & Landmarks', 'Explore Bolinao\'s most iconic beaches and historical lighthouse', 4, 6, 'Cape Bolinao Lighthouse', '13:30:00', '15:00:00', 90, 15, 'Climb the historic lighthouse for panoramic ocean views. Photo opportunity.', 4, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(25, 5, NULL, 'public', '2025-10-20 11:28:51', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:28:51', 'active', 1, 'Day 1: Beaches & Landmarks', 'Explore Bolinao\'s most iconic beaches and historical lighthouse', 5, NULL, 'Rock Formations Tour', '15:30:00', '16:30:00', 60, 10, 'Explore unique coastal rock formations and tide pools.', 5, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(26, 5, NULL, 'public', '2025-10-20 11:28:51', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:28:51', 'active', 1, 'Day 1: Beaches & Landmarks', 'Explore Bolinao\'s most iconic beaches and historical lighthouse', 6, NULL, 'Sunset Viewing', '17:00:00', '18:00:00', 60, 5, 'Watch the stunning Bolinao sunset from the beach.', 6, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(27, 5, NULL, 'public', '2025-10-20 11:28:51', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:28:51', 'active', 1, 'Day 1: Beaches & Landmarks', 'Explore Bolinao\'s most iconic beaches and historical lighthouse', 7, NULL, 'Dinner & Free Time', '18:30:00', '20:00:00', 90, 10, 'Seafood dinner at beachfront restaurant. Return to accommodation.', 7, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(28, 5, NULL, 'public', '2025-10-20 11:28:51', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:28:51', 'active', 2, 'Day 2: Caves & Waterfalls', 'Discover hidden caves, waterfalls, and rock formations', 8, 12, 'Enchanted Cave', '08:00:00', '10:00:00', 120, NULL, 'Swim in the mystical underground cave pool with natural lighting.', 1, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(29, 5, NULL, 'public', '2025-10-20 11:28:51', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:28:51', 'active', 2, 'Day 2: Caves & Waterfalls', 'Discover hidden caves, waterfalls, and rock formations', 9, 1, 'Bolinao Falls', '10:30:00', '13:00:00', 150, 20, 'Trek to Bolinao Falls. Swim in natural pools and enjoy the scenery.', 2, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(30, 5, NULL, 'public', '2025-10-20 11:28:51', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:28:51', 'active', 2, 'Day 2: Caves & Waterfalls', 'Discover hidden caves, waterfalls, and rock formations', 10, NULL, 'Lunch Break', '13:00:00', '14:00:00', 60, 5, 'Packed lunch near the falls or local eatery.', 3, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(31, 5, NULL, 'public', '2025-10-20 11:28:51', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:28:51', 'active', 2, 'Day 2: Caves & Waterfalls', 'Discover hidden caves, waterfalls, and rock formations', 11, NULL, 'Snorkeling Adventure', '14:30:00', '16:00:00', 90, 15, 'Snorkel in clear waters. See tropical fish and coral formations.', 4, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75),
(32, 5, NULL, 'public', '2025-10-20 11:28:51', 'Bolinao Beach Escape - 2 Days', 'Discover the pristine beaches, stunning rock formations, and crystal-clear waters of Bolinao. This 2-day adventure includes beach hopping, snorkeling, cave exploration, and visits to iconic landmarks like the Cape Bolinao Lighthouse and Patar Beach.', 14, NULL, '100', 'bolinao-beach.jpg', '2025-10-20 03:28:51', 'active', 2, 'Day 2: Caves & Waterfalls', 'Discover hidden caves, waterfalls, and rock formations', 12, NULL, 'Return Journey', '16:30:00', '17:00:00', 30, 10, 'Travel back to meeting point. End of tour.', 5, NULL, 'PHP', NULL, 1, 1, 1, 24, 1, 50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 4, 4.75);

--
-- Triggers `itineraries`
--
DELIMITER $$
CREATE TRIGGER `after_itinerary_insert` AFTER INSERT ON `itineraries` FOR EACH ROW BEGIN
  -- Insert office content once per itinerary_id
  IF NEW.itinerary_id IS NOT NULL THEN
    INSERT IGNORE INTO tourism_office_content (office_id, content_type, content_reference_id)
    SELECT toff.office_id, 'itinerary', NEW.itinerary_id
    FROM tourism_office AS toff
    WHERE toff.town_id = NEW.town_id;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `itineraries_items`
-- (See below for the actual view)
--
CREATE TABLE `itineraries_items` (
`itinerary_id` int(11)
,`itinerary_name` varchar(255)
,`itinerary_description` text
,`visibility` enum('private','unlisted','public')
,`published_at` datetime
,`image_path` varchar(255)
,`town_id` int(11)
,`town_name` varchar(100)
,`itinerary_status` enum('active','inactive')
,`day_id` binary(0)
,`day_number` int(11)
,`day_title` varchar(150)
,`day_description` text
,`item_id` int(11)
,`spot_id` int(11)
,`item_name` varchar(255)
,`custom_name` varchar(255)
,`start_time` time
,`end_time` time
,`estimated_duration_minutes` int(11)
,`travel_minutes_from_prev` int(11)
,`notes` text
,`sort_order` int(11)
,`item_created_at` timestamp
,`item_updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `itinerary_bookings`
--

CREATE TABLE `itinerary_bookings` (
  `booking_id` int(11) NOT NULL,
  `itinerary_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `booking_reference` varchar(20) NOT NULL,
  `booking_date` datetime NOT NULL DEFAULT current_timestamp(),
  `travel_date` date NOT NULL,
  `travelers_count` int(11) NOT NULL DEFAULT 1,
  `total_amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'PHP',
  `payment_status` enum('pending','paid','refunded','cancelled') DEFAULT 'pending',
  `booking_status` enum('confirmed','pending','cancelled','completed') DEFAULT 'pending',
  `contact_name` varchar(150) NOT NULL,
  `contact_email` varchar(150) NOT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `cancellation_date` datetime DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Booking records for itineraries';

-- --------------------------------------------------------

--
-- Table structure for table `itinerary_faqs`
--

CREATE TABLE `itinerary_faqs` (
  `faq_id` int(11) NOT NULL,
  `itinerary_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Frequently asked questions for itineraries';

--
-- Dumping data for table `itinerary_faqs`
--

INSERT INTO `itinerary_faqs` (`faq_id`, `itinerary_id`, `question`, `answer`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, 'What should I bring?', 'Please bring swimwear, towel, sunscreen, hat, sunglasses, and a waterproof bag for your electronics. We recommend bringing cash for optional activities and personal expenses.', 1, 'active', '2025-10-20 02:45:53', '2025-10-20 02:45:53'),
(2, 3, 'Is pickup included?', 'Hotel pickup is not included in the base price. The meeting point is at Lucap Wharf in Alaminos City. Transportation from Manila or other cities can be arranged separately.', 2, 'active', '2025-10-20 02:45:53', '2025-10-20 02:45:53'),
(3, 3, 'Can I cancel for free?', 'Yes! You can cancel up to 24 hours before the tour starts for a full refund. Cancellations within 24 hours are non-refundable.', 3, 'active', '2025-10-20 02:45:53', '2025-10-20 02:45:53'),
(4, 3, 'What is the group size?', 'This is a small group tour with a maximum of 20 travelers to ensure a personalized experience. Minimum 2 travelers required for the tour to proceed.', 4, 'active', '2025-10-20 02:45:53', '2025-10-20 02:45:53'),
(5, 3, 'Is food included?', 'Yes, lunch is included in the tour. Please let us know of any dietary restrictions when booking. Additional snacks and drinks can be purchased on the islands.', 5, 'active', '2025-10-20 02:45:53', '2025-10-20 02:45:53'),
(6, 3, 'What if the weather is bad?', 'The tour operates rain or shine. However, if weather conditions are deemed unsafe by our captain, we will reschedule your tour or provide a full refund.', 6, 'active', '2025-10-20 02:45:53', '2025-10-20 02:45:53'),
(7, 3, 'Is snorkeling equipment provided?', 'Yes, all snorkeling equipment (mask, snorkel, fins) and life jackets are provided free of charge. Equipment is sanitized between uses.', 7, 'active', '2025-10-20 02:45:53', '2025-10-20 02:45:53'),
(8, 3, 'Can children join this tour?', 'Yes! Children ages 4 and above are welcome. Children must be accompanied by an adult. Life jackets are mandatory for all children during boat rides and water activities.', 8, 'active', '2025-10-20 02:45:53', '2025-10-20 02:45:53');

-- --------------------------------------------------------

--
-- Table structure for table `itinerary_photos`
--

CREATE TABLE `itinerary_photos` (
  `photo_id` int(11) NOT NULL,
  `itinerary_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `photo_type` enum('official','traveler') DEFAULT 'official',
  `caption` varchar(255) DEFAULT NULL,
  `uploaded_by_user_id` int(11) DEFAULT NULL COMMENT 'User who uploaded (NULL for official)',
  `sort_order` int(11) DEFAULT 0,
  `is_hero` tinyint(1) DEFAULT 0 COMMENT 'Use in hero carousel',
  `status` enum('active','pending','hidden') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Official and traveler-uploaded photos for itineraries';

--
-- Dumping data for table `itinerary_photos`
--

INSERT INTO `itinerary_photos` (`photo_id`, `itinerary_id`, `image_path`, `photo_type`, `caption`, `uploaded_by_user_id`, `sort_order`, `is_hero`, `status`, `created_at`) VALUES
(1, 3, '6813945d4ac34_hundred-island.jpg', 'official', 'Hundred Islands National Park aerial view', NULL, 1, 1, 'active', '2025-10-20 02:45:53'),
(2, 3, '681394af174f6_abagatanen-beach.jpg', 'official', 'Crystal clear waters perfect for snorkeling', NULL, 2, 1, 'active', '2025-10-20 02:45:53'),
(3, 3, '6813945d4ac34_hundred-island.jpg', 'official', 'Island hopping boat tour', NULL, 3, 1, 'active', '2025-10-20 02:45:53');

-- --------------------------------------------------------

--
-- Table structure for table `itinerary_pricing_tiers`
--

CREATE TABLE `itinerary_pricing_tiers` (
  `tier_id` int(11) NOT NULL,
  `itinerary_id` int(11) NOT NULL,
  `tier_name` varchar(100) NOT NULL COMMENT 'Adult, Child, Senior, Student, Group',
  `tier_description` varchar(255) DEFAULT NULL COMMENT 'Age range or other criteria',
  `price_per_person` decimal(10,2) NOT NULL,
  `min_quantity` int(11) DEFAULT 1 COMMENT 'Minimum quantity for this tier',
  `max_quantity` int(11) DEFAULT NULL COMMENT 'Maximum quantity for this tier',
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Pricing tiers for different traveler types';

--
-- Dumping data for table `itinerary_pricing_tiers`
--

INSERT INTO `itinerary_pricing_tiers` (`tier_id`, `itinerary_id`, `tier_name`, `tier_description`, `price_per_person`, `min_quantity`, `max_quantity`, `sort_order`, `status`, `created_at`) VALUES
(1, 3, 'Adult', 'Ages 13-64', 1200.00, 1, NULL, 1, 'active', '2025-10-20 02:45:53'),
(2, 3, 'Child', 'Ages 4-12', 900.00, 0, NULL, 2, 'active', '2025-10-20 02:45:53'),
(3, 3, 'Senior Citizen', 'Ages 65+', 1000.00, 0, NULL, 3, 'active', '2025-10-20 02:45:53'),
(4, 3, 'Infant', 'Ages 0-3 (Free)', 0.00, 0, NULL, 4, 'active', '2025-10-20 02:45:53');

-- --------------------------------------------------------

--
-- Table structure for table `location`
--

CREATE TABLE `location` (
  `location_id` int(11) NOT NULL,
  `province_id` int(11) NOT NULL DEFAULT 1,
  `town_id` int(11) NOT NULL,
  `barangay_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_2fa_codes`
--

CREATE TABLE `login_2fa_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_2fa_codes`
--

INSERT INTO `login_2fa_codes` (`id`, `user_id`, `code`, `expires_at`, `verified`, `verified_at`, `created_at`) VALUES
(1, 5, '313684', '2025-11-08 09:58:11', 0, NULL, '2025-11-08 01:48:11'),
(2, 7, '541315', '2025-11-08 10:00:11', 0, NULL, '2025-11-08 01:50:11'),
(3, 7, '392296', '2025-11-08 10:00:53', 0, NULL, '2025-11-08 01:50:53'),
(4, 7, '037317', '2025-11-08 10:02:38', 0, NULL, '2025-11-08 01:52:38'),
(5, 7, '834678', '2025-11-08 10:14:46', 0, NULL, '2025-11-08 02:04:46');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `attempt_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username_input` varchar(150) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `reason` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_history`
--

CREATE TABLE `login_history` (
  `history_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('success','failed') NOT NULL,
  `reason` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `map_editor`
--

CREATE TABLE `map_editor` (
  `map_id` int(11) NOT NULL,
  `location_name` varchar(255) NOT NULL,
  `location_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(100) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `map_images`
--

CREATE TABLE `map_images` (
  `image_id` int(11) NOT NULL,
  `map_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset`
--

CREATE TABLE `password_reset` (
  `reset_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `expiry_time` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expiry_time` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `user_id`, `token_hash`, `expiry_time`, `used`, `used_at`, `created_at`) VALUES
(1, 10, '4b0f777fe5e4dbddba4bf2183ff8942f1b539d4164f84b96c8fcdda0cb3819fb', '2025-11-05 16:20:24', 0, NULL, '2025-11-05 14:50:24');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`permission_id`, `name`, `description`) VALUES
(1, 'manage_tourist_spots', 'Can create, edit, and view tourist spots'),
(2, 'manage_festivals', 'Can create, edit, and view festivals'),
(3, 'manage_itineraries', 'Can create, edit, and view itineraries'),
(4, 'view_reports', 'Can view tourism reports and analytics'),
(5, 'manage_all_content', 'Can manage content across all municipalities');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `spot_id` int(11) DEFAULT NULL,
  `entity_type` varchar(40) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reviewer_name` varchar(100) NOT NULL,
  `reviewer_email` varchar(150) DEFAULT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text NOT NULL,
  `review_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','pending','hidden') DEFAULT 'active',
  `helpful_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `traveler_type` enum('family','couple','solo','business','friends') DEFAULT NULL COMMENT 'Type of traveler',
  `travel_date` date DEFAULT NULL COMMENT 'Date of travel',
  `verified_purchase` tinyint(1) DEFAULT 0 COMMENT 'Verified booking',
  `has_photos` tinyint(1) DEFAULT 0 COMMENT 'Review includes photos'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `spot_id`, `entity_type`, `entity_id`, `user_id`, `reviewer_name`, `reviewer_email`, `rating`, `review_text`, `review_date`, `status`, `helpful_count`, `created_at`, `updated_at`, `traveler_type`, `travel_date`, `verified_purchase`, `has_photos`) VALUES
(1, 5, 'spot', 5, NULL, 'Sarah Johnson', 'sarah.j@email.com', 5, 'Absolutely stunning! The Hundred Islands is a must-visit destination. Crystal clear waters, beautiful scenery, and well-organized island hopping tours. Perfect for families and couples alike.', '2025-07-16 02:54:47', 'active', 0, '2025-07-16 02:54:47', '2025-10-01 14:07:10', NULL, NULL, 0, 0),
(2, 5, 'spot', 5, NULL, 'Mark Chen', 'markc@email.com', 4, 'Great experience overall. The boat ride was smooth and the islands are beautiful. Only downside was it got quite crowded during peak hours. Best to visit early morning.', '2025-06-27 02:54:47', 'active', 0, '2025-06-27 02:54:47', '2025-10-01 14:07:10', NULL, NULL, 0, 0),
(3, 5, 'spot', 5, NULL, 'Lisa Marie', 'lisamarie88@email.com', 5, 'One of the best trips I\'ve ever had! The staff were very accommodating and knowledgeable. The water is so clear you can see fish swimming around. Highly recommend the full day tour.', '2025-09-24 02:54:47', 'active', 0, '2025-09-24 02:54:47', '2025-10-01 14:07:10', NULL, NULL, 0, 0),
(4, 6, 'spot', 6, NULL, 'Robert Kim', 'robk@email.com', 4, 'Historic lighthouse with amazing panoramic views. The climb is a bit steep but totally worth it for the sunset views. Great spot for photography enthusiasts.', '2025-06-20 02:54:47', 'active', 0, '2025-06-20 02:54:47', '2025-10-01 14:07:10', NULL, NULL, 0, 0),
(5, 6, 'spot', 6, NULL, 'Amanda Torres', 'amanda.torres@email.com', 5, 'Breathtaking views from the top! The lighthouse is well-maintained and the surrounding area is clean. Perfect spot for a romantic sunset watching.', '2025-09-21 02:54:47', 'active', 0, '2025-09-21 02:54:47', '2025-10-01 14:07:10', NULL, NULL, 0, 0),
(6, 11, 'spot', 11, NULL, 'David Wilson', 'davidw@email.com', 5, 'Hidden gem! This beach is absolutely beautiful with soft white sand and clear blue waters. Not too crowded, perfect for a peaceful day by the sea.', '2025-06-24 02:54:47', 'active', 0, '2025-06-24 02:54:47', '2025-10-01 14:07:10', NULL, NULL, 0, 0),
(7, 11, 'spot', 11, NULL, 'Jennifer Lopez', 'jennylopez@email.com', 4, 'Beautiful beach with great swimming conditions. The water is clean and the beach is well-maintained. Could use more shade areas but overall a wonderful place.', '2025-06-23 02:54:47', 'active', 0, '2025-06-23 02:54:47', '2025-10-01 14:07:10', NULL, NULL, 0, 0),
(8, 14, 'spot', 14, NULL, 'Carlos Santos', 'carlos.santos@email.com', 3, 'Decent beach for a quick visit. The water is okay and there are some local food stalls nearby. Not the most pristine but good for locals and budget travelers.', '2025-07-12 02:54:47', 'active', 0, '2025-07-12 02:54:47', '2025-10-01 14:07:10', NULL, NULL, 0, 0),
(9, 14, 'spot', 14, NULL, 'Michelle Brown', 'mbrown@email.com', 4, 'Nice local beach with friendly people. Good for surfing when the waves are right. The sunset views are spectacular!', '2025-06-22 02:54:47', 'active', 0, '2025-06-22 02:54:47', '2025-10-01 14:07:10', NULL, NULL, 0, 0),
(10, 15, 'spot', 15, NULL, 'Tom Anderson', 'tanderson@email.com', 5, 'Unique rock formations that are perfect for photography. The natural rock umbrellas are fascinating geological features. Great for nature lovers!', '2025-09-11 02:54:47', 'active', 0, '2025-09-11 02:54:47', '2025-10-01 14:07:10', NULL, NULL, 0, 0),
(11, 15, 'spot', 15, NULL, 'Rachel Green', 'rgreen@email.com', 4, 'Interesting natural formations. The rocks are impressive and the area offers good photo opportunities. A bit difficult to access but worth the effort.', '2025-08-29 02:54:47', 'active', 0, '2025-08-29 02:54:47', '2025-10-01 14:07:10', NULL, NULL, 0, 0),
(12, 21, 'spot', 21, NULL, 'James Mitchell', 'jmitchell@email.com', 4, 'Nice quiet beach perfect for relaxation. The water is clean and there are decent facilities. Great place to escape the crowds and enjoy nature.', '2025-06-21 02:54:47', 'active', 0, '2025-06-21 02:54:47', '2025-10-01 14:07:10', NULL, NULL, 0, 0),
(13, 21, 'spot', 21, NULL, 'Sophie Taylor', 'sophie.taylor@email.com', 5, 'Love this hidden paradise! Clean beach, clear water, and very peaceful atmosphere. Perfect for meditation and swimming. Will definitely come back!', '2025-08-16 02:54:47', 'active', 0, '2025-08-16 02:54:47', '2025-10-01 14:07:10', NULL, NULL, 0, 0),
(14, 22, 'spot', 22, NULL, 'Kevin Lee', 'kevinlee@email.com', 5, 'Lives up to its name - truly a hidden paradise! The beach is pristine with crystal clear water. A bit secluded which makes it even more special.', '2025-08-19 02:54:47', 'active', 0, '2025-08-19 02:54:47', '2025-10-01 14:07:10', NULL, NULL, 0, 0),
(15, 22, 'spot', 22, NULL, 'Emma Davis', 'emma.davis@email.com', 4, 'Beautiful secluded beach. The water is incredibly clear and the sand is soft. Access can be a bit tricky but the payoff is worth it.', '2025-07-23 02:54:47', 'active', 0, '2025-07-23 02:54:47', '2025-10-01 14:07:10', NULL, NULL, 0, 0),
(16, 25, 'spot', 25, NULL, 'Alex Rodriguez', 'alexr@email.com', 4, 'Great beach for swimming and relaxing. The local community is very welcoming and the food nearby is delicious. Good value for money.', '2025-06-24 02:54:47', 'active', 0, '2025-06-24 02:54:47', '2025-10-01 14:07:10', NULL, NULL, 0, 0),
(17, 25, 'spot', 25, NULL, 'Grace Kim', 'gracekim@email.com', 5, 'Wonderful beach experience! Clean facilities, beautiful scenery, and friendly locals. The waves are perfect for beginners learning to surf.', '2025-08-20 02:54:47', 'active', 0, '2025-08-20 02:54:47', '2025-10-01 14:07:10', NULL, NULL, 0, 0),
(18, 11, 'spot', 11, 3, 'Cleymark', NULL, 5, 'Breathtaking view and I have a great experience!', '2025-09-27 03:47:45', 'active', 0, '2025-09-27 03:47:45', '2025-10-01 14:07:10', NULL, NULL, 0, 0),
(19, NULL, 'itinerary', 3, NULL, 'Demo Traveler', 'demo@example.com', 5, 'Fantastic Hundred Islands experience – crystal clear waters and smooth logistics!', '2025-10-01 14:09:55', 'active', 0, '2025-10-01 14:09:55', '2025-10-20 02:47:18', 'family', '2025-09-20', 1, 0),
(20, NULL, 'itinerary', 4, NULL, 'Maria Santos', 'maria.santos@email.com', 5, 'Amazing 2-day trip! Our guide was knowledgeable and friendly. Patar Beach was absolutely stunning. The Enchanted Cave was a highlight - so unique and beautiful. Worth every peso!', '2025-10-05 03:22:52', 'active', 0, '2025-10-20 03:22:52', '2025-10-20 03:22:52', NULL, NULL, 0, 0),
(21, NULL, 'itinerary', 4, NULL, 'John Reyes', 'jreyes@email.com', 5, 'Perfect itinerary for beach lovers! Well-organized, on time, and great value. The seafood meals were delicious. Highly recommend for couples or small groups.', '2025-09-28 03:22:52', 'active', 0, '2025-10-20 03:22:52', '2025-10-20 03:22:52', NULL, NULL, 0, 0),
(22, NULL, 'itinerary', 4, NULL, 'Linda Chen', 'linda.c@email.com', 4, 'Great experience overall. The lighthouse climb was tiring but the view was worth it. Only downside was we wished we had more time at Bolinao Falls. Would do it again!', '2025-10-12 03:22:52', 'active', 0, '2025-10-20 03:22:52', '2025-10-20 03:22:52', NULL, NULL, 0, 0),
(23, NULL, 'itinerary', 4, NULL, 'Miguel Torres', 'mtorres@email.com', 5, 'Best weekend getaway! The tour was perfectly paced - not rushed. Guide took amazing photos of us. Enchanted Cave swimming was unforgettable. 10/10 recommend!', '2025-09-20 03:22:52', 'active', 0, '2025-10-20 03:22:52', '2025-10-20 03:22:52', NULL, NULL, 0, 0),
(24, NULL, 'itinerary', 5, NULL, 'Maria Santos', 'maria.santos@email.com', 5, 'Amazing 2-day trip! Our guide was knowledgeable and friendly. Patar Beach was absolutely stunning. The Enchanted Cave was a highlight - so unique and beautiful. Worth every peso!', '2025-10-05 03:28:51', 'active', 0, '2025-10-20 03:28:51', '2025-10-20 03:28:51', NULL, NULL, 0, 0),
(25, NULL, 'itinerary', 5, NULL, 'John Reyes', 'jreyes@email.com', 5, 'Perfect itinerary for beach lovers! Well-organized, on time, and great value. The seafood meals were delicious. Highly recommend for couples or small groups.', '2025-09-28 03:28:51', 'active', 0, '2025-10-20 03:28:51', '2025-10-20 03:28:51', NULL, NULL, 0, 0),
(26, NULL, 'itinerary', 5, NULL, 'Linda Chen', 'linda.c@email.com', 4, 'Great experience overall. The lighthouse climb was tiring but the view was worth it. Only downside was we wished we had more time at Bolinao Falls. Would do it again!', '2025-10-12 03:28:51', 'active', 0, '2025-10-20 03:28:51', '2025-10-20 03:28:51', NULL, NULL, 0, 0),
(27, NULL, 'itinerary', 5, NULL, 'Miguel Torres', 'mtorres@email.com', 5, 'Best weekend getaway! The tour was perfectly paced - not rushed. Guide took amazing photos of us. Enchanted Cave swimming was unforgettable. 10/10 recommend!', '2025-09-20 03:28:51', 'active', 0, '2025-10-20 03:28:51', '2025-10-20 03:28:51', NULL, NULL, 0, 0);

--
-- Triggers `reviews`
--
DELIMITER $$
CREATE TRIGGER `after_itinerary_review_delete` AFTER DELETE ON `reviews` FOR EACH ROW BEGIN
    IF OLD.entity_type = 'itinerary' THEN
        CALL update_itinerary_review_stats(OLD.entity_id);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_itinerary_review_insert` AFTER INSERT ON `reviews` FOR EACH ROW BEGIN
    IF NEW.entity_type = 'itinerary' THEN
        CALL update_itinerary_review_stats(NEW.entity_id);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_itinerary_review_update` AFTER UPDATE ON `reviews` FOR EACH ROW BEGIN
    IF NEW.entity_type = 'itinerary' THEN
        CALL update_itinerary_review_stats(NEW.entity_id);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `review_photos`
--

CREATE TABLE `review_photos` (
  `photo_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Photos attached to reviews';

-- --------------------------------------------------------

--
-- Table structure for table `route_transport_types`
--

CREATE TABLE `route_transport_types` (
  `route_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `route_transport_types`
--

INSERT INTO `route_transport_types` (`route_id`, `type_id`, `created_at`) VALUES
(1, 1, '2025-05-18 21:39:32'),
(1, 4, '2025-05-18 21:39:32'),
(2, 1, '2025-05-18 21:39:32'),
(2, 2, '2025-05-18 21:39:32'),
(3, 3, '2025-05-18 21:39:32'),
(3, 4, '2025-05-18 21:39:32'),
(4, 4, '2025-05-18 21:39:32'),
(5, 5, '2025-05-18 21:39:32'),
(6, 5, '2025-05-18 21:39:32'),
(7, 1, '2025-09-15 02:40:49'),
(7, 2, '2025-09-15 02:40:49');

-- --------------------------------------------------------

--
-- Table structure for table `sms_verification_codes`
--

CREATE TABLE `sms_verification_codes` (
  `id` int(11) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `code` char(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `spot_images`
--

CREATE TABLE `spot_images` (
  `image_id` int(11) NOT NULL,
  `spot_id` int(11) NOT NULL,
  `image_file` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spot_images`
--

INSERT INTO `spot_images` (`image_id`, `spot_id`, `image_file`, `alt_text`, `position`, `is_primary`, `created_at`) VALUES
(1, 1, '68103c630ab13_Bolinao Falls.jfif', 'Bolinao Falls', 0, 1, '2025-10-06 02:57:12'),
(2, 5, '6813945d4ac34_hundred-island.jpg', 'Hundred Islands', 0, 1, '2025-10-06 02:57:12'),
(3, 6, '681394705bcd7_bolinao3.jpg', 'Bolinao Lighthouse', 0, 1, '2025-10-06 02:57:12'),
(4, 11, '681394af174f6_abagatanen-beach.jpg', 'Abagatanen Beach', 0, 1, '2025-10-06 02:57:12'),
(5, 12, '681b49ec44a60_bolinao.png', 'Enchanted Cave', 0, 1, '2025-10-06 02:57:12'),
(6, 13, '681b4bb708c82_st.vincent.jpg', 'Saint Vincent Prayer Park', 0, 1, '2025-10-06 02:57:12'),
(7, 14, '6820d5c10ded6_agnoooo.jpg', 'Agno Beach', 0, 1, '2025-10-06 02:57:12'),
(8, 15, '6820d5f59abe0_urocks.jpg', 'agno umbrella rocks', 0, 1, '2025-10-06 02:57:12'),
(9, 16, '6820d633d9a44_mcamp.jpg', 'mary hill youth camp', 0, 1, '2025-10-06 02:57:12'),
(10, 17, '6820d66d3b535_st ca.jpg', 'saint catherine ', 0, 1, '2025-10-06 02:57:12'),
(11, 18, '6820d77bc6d28_mi bi.jpg', 'minor basilica', 0, 1, '2025-10-06 02:57:12'),
(12, 19, '6820d7d4b6a54_st ja.jpg', 'saint james', 0, 1, '2025-10-06 02:57:12'),
(13, 20, '6829100d6ce4f_busay-falls-bani.jpg', 'Busay Falls', 0, 1, '2025-10-06 02:57:12'),
(14, 21, '68290f7be20c1_images (1).jfif', 'Olanen beach', 0, 1, '2025-10-06 02:57:12'),
(15, 22, '68290f54923c0_images (2).jfif', 'Hidden Paradise', 0, 1, '2025-10-06 02:57:12'),
(16, 24, '68290f9f01ede_download (1).jfif', 'Polipol Island', 0, 1, '2025-10-06 02:57:12'),
(17, 25, '68290fd4145c8_download (2).jfif', 'Surip Beach', 0, 1, '2025-10-06 02:57:12');

-- --------------------------------------------------------

--
-- Table structure for table `terminal_locations`
--

CREATE TABLE `terminal_locations` (
  `terminal_id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `terminal_locations`
--

INSERT INTO `terminal_locations` (`terminal_id`, `location_name`, `address`, `latitude`, `longitude`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Five Star Dagupan', 'Dagupan City', 16.04250000, 120.33250000, 'active', '2025-05-18 21:39:32', '2025-05-18 21:39:32'),
(2, 'Victory Liner Dagupan', 'Dagupan City', 16.04280000, 120.33270000, 'active', '2025-05-18 21:39:32', '2025-05-18 21:39:32'),
(3, 'Bolinao Terminal', 'Bolinao', 16.32610000, 119.88880000, 'active', '2025-05-18 21:39:32', '2025-05-18 21:39:32'),
(4, 'Alaminos Terminal', 'Alaminos City', 16.15500000, 119.98000000, 'active', '2025-05-18 21:39:32', '2025-05-18 21:39:32'),
(5, 'Victory Bolinao', 'Bolinao', NULL, NULL, 'active', '2025-09-15 02:22:23', '2025-09-15 02:22:23'),
(6, 'Victory Alaminos', 'Alaminos', NULL, NULL, 'active', '2025-09-15 02:22:23', '2025-09-15 02:22:23'),
(7, 'Five Star Alaminos', 'Alaminos', NULL, NULL, 'active', '2025-09-15 02:22:23', '2025-09-15 02:22:23'),
(8, 'Victory Dagupan', 'Dagupan', NULL, NULL, 'active', '2025-09-15 02:22:23', '2025-09-15 02:22:23'),
(9, 'Mel Bhen', 'dagupan', NULL, NULL, 'active', '2025-09-15 02:22:23', '2025-09-15 02:22:23');

-- --------------------------------------------------------

--
-- Table structure for table `tourism_fee_log`
--

CREATE TABLE `tourism_fee_log` (
  `id` int(11) NOT NULL,
  `municipality_id` int(11) NOT NULL,
  `spot_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `num_tourists` int(11) NOT NULL,
  `visit_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `recorded_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tourism_fee_log`
--

INSERT INTO `tourism_fee_log` (`id`, `municipality_id`, `spot_id`, `name`, `num_tourists`, `visit_date`, `amount`, `recorded_by_user_id`, `created_at`) VALUES
(1, 14, 1, 'Cleymark', 5, '2025-09-22', 250.00, 1, '2025-09-22 05:53:32');

-- --------------------------------------------------------

--
-- Table structure for table `tourism_office`
--

CREATE TABLE `tourism_office` (
  `office_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `office_name` varchar(100) DEFAULT NULL,
  `head_name` varchar(100) DEFAULT NULL,
  `town_id` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_info` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tourism_office`
--

INSERT INTO `tourism_office` (`office_id`, `user_id`, `office_name`, `head_name`, `town_id`, `address`, `contact_info`, `email`) VALUES
(1, 4, 'Alaminos Tourism Office', 'John Doe', 3, 'Alaminos City Hall, Pangasinan', '09123456789', 'alaminos.tourism@example.com'),
(2, 5, 'Bolinao Tourism Office', 'Jane Smith', 14, 'Bolinao Municipal Hall, Pangasinan', '09987654321', 'bolinao.tourism@example.com'),
(3, 7, 'Tourism Office', NULL, 8, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tourism_office_content`
--

CREATE TABLE `tourism_office_content` (
  `content_id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `content_type` enum('tourist_spot','itinerary','festival') NOT NULL,
  `content_reference_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tourism_office_content`
--

INSERT INTO `tourism_office_content` (`content_id`, `office_id`, `content_type`, `content_reference_id`, `created_at`) VALUES
(1, 1, 'tourist_spot', 5, '2025-05-03 04:05:11'),
(2, 2, 'tourist_spot', 1, '2025-05-03 04:05:11'),
(3, 2, 'tourist_spot', 6, '2025-05-03 04:05:11'),
(4, 1, 'festival', 5, '2025-05-03 04:05:22'),
(5, 2, 'festival', 4, '2025-05-03 04:05:22'),
(7, 2, 'itinerary', 1, '2025-05-03 04:05:28'),
(8, 3, 'tourist_spot', 25, '2025-05-17 06:14:06'),
(9, 3, 'festival', 8, '2025-09-09 05:23:36'),
(10, 1, 'itinerary', 3, '2025-10-01 12:31:05'),
(11, 2, 'itinerary', 4, '2025-10-20 03:22:52'),
(23, 2, 'itinerary', 5, '2025-10-20 03:28:51');

-- --------------------------------------------------------

--
-- Table structure for table `tourist_capacity`
--

CREATE TABLE `tourist_capacity` (
  `capacity_id` int(11) NOT NULL,
  `spot_id` int(11) NOT NULL,
  `current_capacity` int(11) DEFAULT 0,
  `max_capacity` int(11) NOT NULL DEFAULT 100,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tourist_capacity`
--

INSERT INTO `tourist_capacity` (`capacity_id`, `spot_id`, `current_capacity`, `max_capacity`, `last_updated`, `updated_by`) VALUES
(1, 11, 0, 100, '2025-09-15 03:18:57', NULL),
(2, 14, 0, 100, '2025-09-15 03:18:57', NULL),
(3, 15, 0, 100, '2025-09-15 03:18:57', NULL),
(4, 16, 0, 100, '2025-09-15 03:18:57', NULL),
(5, 17, 0, 100, '2025-09-15 03:18:57', NULL),
(6, 5, 0, 100, '2025-09-15 03:18:57', NULL),
(7, 20, 0, 100, '2025-09-15 03:18:57', NULL),
(8, 21, 0, 100, '2025-09-15 03:18:57', NULL),
(9, 22, 0, 100, '2025-09-15 03:18:57', NULL),
(10, 24, 0, 100, '2025-09-15 03:18:57', NULL),
(11, 25, 0, 100, '2025-09-15 03:18:57', NULL),
(12, 13, 0, 100, '2025-09-15 03:18:57', NULL),
(13, 1, 0, 100, '2025-09-15 03:18:57', NULL),
(14, 6, 0, 100, '2025-09-15 03:18:57', NULL),
(15, 12, 0, 100, '2025-09-15 03:18:57', NULL),
(16, 19, 0, 100, '2025-09-15 03:18:57', NULL),
(17, 18, 0, 100, '2025-09-15 03:18:57', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tourist_spots`
--

CREATE TABLE `tourist_spots` (
  `spot_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `town_id` int(11) DEFAULT NULL,
  `contact_info` varchar(100) DEFAULT NULL,
  `image_path` text DEFAULT NULL,
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tourist_spots`
--

INSERT INTO `tourist_spots` (`spot_id`, `name`, `description`, `category`, `town_id`, `contact_info`, `image_path`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Bolinao Falls', 'amazing ', 'Waterfalls', 14, '', '68103c630ab13_Bolinao Falls.jfif', 'active', '2025-04-29 02:41:39', '2025-05-17 23:01:45'),
(5, 'Hundred Islands', 'Famous tourist destination featuring 124 islands at low tide', 'Islands', 3, '+63 923 456 7890', '6813945d4ac34_hundred-island.jpg', 'active', '2025-04-29 10:03:51', '2025-05-01 15:33:49'),
(6, 'Bolinao Lighthouse', 'Historic lighthouse with panoramic views', 'Beach', 14, '+63 934 567 8901', '681394705bcd7_bolinao3.jpg', 'active', '2025-04-29 10:03:51', '2025-05-17 23:03:15'),
(11, 'Abagatanen Beach', 'beautiful and astonishing', 'Beach', 1, '0999634535', '681394af174f6_abagatanen-beach.jpg', 'active', '2025-05-01 15:35:11', '2025-05-18 21:33:11'),
(12, 'Enchanted Cave', 'na ay nasunog', 'Caves', 14, '', '681b49ec44a60_bolinao.png', 'active', '2025-05-07 11:54:20', '2025-05-07 11:54:20'),
(13, 'Saint Vincent Prayer Park', 'wow', 'Churches', 11, '', '681b4bb708c82_st.vincent.jpg', 'active', '2025-05-07 12:01:59', '2025-05-07 12:01:59'),
(14, 'Agno Beach', 'hds', 'Beach', 1, '', '6820d5c10ded6_agnoooo.jpg', 'active', '2025-05-11 16:52:17', '2025-05-17 23:01:36'),
(15, 'agno umbrella rocks', 'sdsa', 'Beach', 1, '', '6820d5f59abe0_urocks.jpg', 'active', '2025-05-11 16:53:09', '2025-05-17 23:01:38'),
(16, 'mary hill youth camp', 'dasda', 'Churches', 1, '', '6820d633d9a44_mcamp.jpg', 'active', '2025-05-11 16:54:11', '2025-05-11 16:54:11'),
(17, 'saint catherine ', 'fasda', 'Churches', 1, '', '6820d66d3b535_st ca.jpg', 'active', '2025-05-11 16:55:09', '2025-05-11 16:55:09'),
(18, 'minor basilica', 'jioj', 'Churches', 25, '', '6820d77bc6d28_mi bi.jpg', 'active', '2025-05-11 16:59:39', '2025-05-11 16:59:39'),
(19, 'saint james', 'dsaf', 'Churches', 14, '', '6820d7d4b6a54_st ja.jpg', 'active', '2025-05-11 17:01:08', '2025-05-11 17:01:08'),
(20, 'Busay Falls', 'wowwwwww', 'Waterfalls', 8, '', '6829100d6ce4f_busay-falls-bani.jpg', 'active', '2025-05-17 04:46:07', '2025-05-17 22:39:09'),
(21, 'Olanen beach', 'wowwwwww', 'Beach', 8, '', '68290f7be20c1_images (1).jfif', 'active', '2025-05-17 04:48:51', '2025-05-17 22:36:43'),
(22, 'Hidden Paradise', 'beach beach', 'Beach', 8, '', '68290f54923c0_images (2).jfif', 'active', '2025-05-17 04:54:12', '2025-05-17 22:36:04'),
(24, 'Polipol Island', 'deym', 'Islands', 8, '', '68290f9f01ede_download (1).jfif', 'active', '2025-05-17 06:08:34', '2025-05-17 22:37:19'),
(25, 'Surip Beach', 'wow surip', 'Beach', 8, '', '68290fd4145c8_download (2).jfif', 'active', '2025-05-17 06:14:06', '2025-05-17 22:38:12');

--
-- Triggers `tourist_spots`
--
DELIMITER $$
CREATE TRIGGER `after_tourist_spot_insert` AFTER INSERT ON `tourist_spots` FOR EACH ROW BEGIN
    INSERT INTO tourism_office_content (office_id, content_type, content_reference_id)
    SELECT toff.office_id, 'tourist_spot', NEW.spot_id
    FROM tourism_office toff
    WHERE toff.town_id = NEW.town_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `tourist_spot_capacity`
--

CREATE TABLE `tourist_spot_capacity` (
  `capacity_id` int(11) NOT NULL,
  `spot_id` int(11) NOT NULL,
  `current_capacity` int(11) NOT NULL DEFAULT 0,
  `max_capacity` int(11) NOT NULL DEFAULT 100,
  `capacity_percentage` decimal(5,2) GENERATED ALWAYS AS (`current_capacity` / `max_capacity` * 100) STORED,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `towns`
--

CREATE TABLE `towns` (
  `town_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `image_path` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `towns`
--

INSERT INTO `towns` (`town_id`, `name`, `image_path`, `status`) VALUES
(1, 'Agno', '', 'active'),
(2, 'Aguilar', NULL, 'active'),
(3, 'Alaminos', '', 'active'),
(4, 'Alcala', NULL, 'active'),
(5, 'Anda', NULL, 'active'),
(6, 'Asingan', NULL, 'active'),
(7, 'Balungao', NULL, 'active'),
(8, 'Bani', NULL, 'active'),
(9, 'Basista', NULL, 'active'),
(10, 'Bautista', NULL, 'active'),
(11, 'Bayambang', NULL, 'active'),
(12, 'Binalonan', NULL, 'active'),
(13, 'Binmaley', NULL, 'active'),
(14, 'Bolinao', '', 'active'),
(15, 'Bugallon', NULL, 'active'),
(16, 'Burgos', NULL, 'active'),
(17, 'Calasiao', NULL, 'active'),
(18, 'Dasol', NULL, 'active'),
(19, 'Dagupan', '', 'active'),
(20, 'Infanta', NULL, 'active'),
(21, 'Labrador', NULL, 'active'),
(22, 'Lingayen', '', 'active'),
(23, 'Mabini', NULL, 'active'),
(24, 'Malasiqui', NULL, 'active'),
(25, 'Manaoag', '', 'active'),
(26, 'Mangaldan', NULL, 'active'),
(27, 'Mangatarem', NULL, 'active'),
(28, 'Mapandan', NULL, 'active'),
(29, 'Natividad', NULL, 'active'),
(30, 'Pozorrubio', NULL, 'active'),
(31, 'Rosales', NULL, 'active'),
(32, 'San Carlos', NULL, 'active'),
(33, 'San Fabian', '682450db1d789_san-fabian-beach.jpg', 'active'),
(34, 'San Jacinto', '6820d7d4b6a54_st ja.jpg', 'active'),
(35, 'San Manuel', NULL, 'active'),
(36, 'San Nicolas', NULL, 'active'),
(37, 'San Quintin', NULL, 'active'),
(38, 'Santa Barbara', NULL, 'active'),
(39, 'Santa Maria', NULL, 'active'),
(40, 'Santo Tomas', NULL, 'active'),
(41, 'Sison', NULL, 'active'),
(42, 'Sual', '682450db1f901_sual-port.jpg', 'active'),
(43, 'Tayug', NULL, 'active'),
(44, 'Umingan', NULL, 'active'),
(45, 'Urbiztondo', NULL, 'active'),
(46, 'Urdaneta', '682450db1e890_urdaneta-cathedral.jpg', 'active'),
(47, 'Villasis', NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `transport_routes`
--

CREATE TABLE `transport_routes` (
  `route_id` int(11) NOT NULL,
  `from_terminal_id` int(11) DEFAULT NULL,
  `to_terminal_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transport_routes`
--

INSERT INTO `transport_routes` (`route_id`, `from_terminal_id`, `to_terminal_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'active', '2025-05-18 21:39:32', '2025-05-18 21:39:32'),
(2, 2, 1, 'active', '2025-05-18 21:39:32', '2025-05-18 21:39:32'),
(3, 1, 3, 'active', '2025-05-18 21:39:32', '2025-05-18 21:39:32'),
(4, 3, 1, 'active', '2025-05-18 21:39:32', '2025-05-18 21:39:32'),
(5, 1, 4, 'active', '2025-05-18 21:39:32', '2025-05-18 21:39:32'),
(6, 4, 1, 'active', '2025-05-18 21:39:32', '2025-05-18 21:39:32'),
(7, 1, 2, 'active', '2025-09-15 02:40:49', '2025-09-15 02:40:49');

-- --------------------------------------------------------

--
-- Table structure for table `transport_types`
--

CREATE TABLE `transport_types` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transport_types`
--

INSERT INTO `transport_types` (`type_id`, `type_name`, `created_at`, `updated_at`) VALUES
(1, 'Air-conditioned Bus', '2025-05-18 21:39:32', '2025-05-18 21:39:32'),
(2, 'Ordinary Bus', '2025-05-18 21:39:32', '2025-05-18 21:39:32'),
(3, 'Mini Bus', '2025-05-18 21:39:32', '2025-05-18 21:39:32'),
(4, 'Van', '2025-05-18 21:39:32', '2025-05-18 21:39:32'),
(5, 'Jeepney', '2025-05-18 21:39:32', '2025-05-18 21:39:32');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `is_email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified_at` datetime DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `failed_login_count` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `user_type_id` int(11) DEFAULT NULL,
  `user_status_id` int(11) DEFAULT NULL,
  `town_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `username`, `email`, `is_email_verified`, `email_verified_at`, `google_id`, `last_login_at`, `failed_login_count`, `locked_until`, `password`, `user_type_id`, `user_status_id`, `town_id`) VALUES
(1, 'admin', 'admin@tripko.com', 1, '2025-10-01 21:30:02', NULL, '2025-11-08 10:11:02', 0, NULL, '$2y$10$.6VOvU87JfeY2VsIlV4ZJ.eSsDfDAOVnEATyenmGMEdC8aQHbgtNO', 1, 1, NULL),
(3, 'user', '', 0, NULL, NULL, NULL, 0, NULL, '$2y$10$0/3EVjX4omCeS.sclaA8duA3lCYENDxW53yts2W/bubmZaGLGbiVq', 2, 1, NULL),
(4, 'alaminos_tourism', NULL, 1, '2025-10-01 21:30:02', NULL, NULL, 0, NULL, '$2y$10$pJSv4LCr02L0ZDSYVEbpwejTjdJweCC051wL2HxKJQlQIOD9Vxgla', 3, 1, 3),
(5, 'bolinao_tourism', NULL, 1, NULL, NULL, '2025-11-08 10:07:22', 0, NULL, '$2y$10$JyLD0t2.Ehc8Jnod.r.NXeaF5hZrms/tZ8U5UPufrmpNltDne8Hoy', 3, 1, 14),
(6, 'admin1', NULL, 1, '2025-10-01 21:30:02', NULL, NULL, 0, NULL, '$2y$10$vAEyK4a4MRuRrhocMfzaD.s3sCuTGYFf.KEJJGVXdaKT6jiViPgGa', 1, 1, NULL),
(7, 'bani_office', NULL, 1, '2025-10-01 21:30:02', NULL, '2025-11-05 22:51:34', 0, NULL, '$2y$10$FnkbR2Ai3lYfTxHdA6i7x.rk8Cky41pXhi8cRCSwCdyUI8/aaurdm', 3, 1, 8),
(8, 'Cleymark', NULL, 0, NULL, NULL, NULL, 0, NULL, '$2y$10$LnXODp8ja.ZUOQM9a46pHegI97.hlDD48LZ4bsmUk4EWH2ZMKuIpK', 2, 1, NULL),
(9, 'testuser2', 'test2@gmail.com', 1, '2025-09-28 21:39:54', NULL, NULL, 0, NULL, '$2y$10$ysJJTGK4wlcuBgXikdiuYuDrgN/n6GgutoOhYZyzgXczRPlgMzolm', 2, 1, NULL),
(10, 'Cleymark19', 'secretqt000000001@gmail.com', 1, '2025-09-28 21:41:37', NULL, '2025-11-08 10:32:08', 0, NULL, '$2y$10$lLDVEpsz89E.xjHSD9NodeiCKPvfn.8uSlqTlSeTumY3N9FLWSJOW', 2, 1, NULL),
(11, 'testuser_no1', 'balancleymark@gmail.com', 1, '2025-10-31 10:21:18', NULL, '2025-11-03 16:06:34', 0, NULL, '$2y$10$rJRdbHZXHFS1QhN8N5P.CuQvN4CqIxR0dSM6k4o15EfzCM5SeLTme', 2, 1, NULL),
(12, 'testuser_no3', 'cleymark19@gmail.com', 1, '2025-10-31 10:39:36', NULL, '2025-11-05 22:33:12', 0, NULL, '$2y$10$rGwaM1WU4uyIovYxQApP/e6lN1WMHESSst7BM2mCOR0H2aA7ZnIJW', 2, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_permissions`
--

INSERT INTO `user_permissions` (`user_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(4, 1),
(4, 2),
(4, 3),
(4, 4),
(5, 1),
(5, 2),
(5, 3),
(5, 4),
(6, 1),
(6, 2),
(6, 3),
(6, 4),
(6, 5);

-- --------------------------------------------------------

--
-- Table structure for table `user_profile`
--

CREATE TABLE `user_profile` (
  `profile_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `user_profile_dob` date DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `user_profile_photo` text DEFAULT NULL,
  `user_profile_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_profile_updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_profile`
--

INSERT INTO `user_profile` (`profile_id`, `user_id`, `first_name`, `last_name`, `user_profile_dob`, `email`, `contact_number`, `user_profile_photo`, `user_profile_created_at`, `user_profile_updated_at`) VALUES
(1, 1, 'System', 'Administrator', NULL, 'admin@tripko.com', '09123456789', NULL, '2025-05-01 13:06:43', '2025-05-01 13:06:43'),
(3, 3, 'Cleymark', 'Balan', '0000-00-00', '', '09111111111', '1758946192_uid3.jpg', '2025-05-02 02:00:44', '2025-09-28 12:09:32'),
(4, 6, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-14 02:53:10', '2025-05-14 02:53:10'),
(5, 7, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-16 13:47:17', '2025-05-16 13:47:17'),
(6, 3, 'Cleymark', 'Balan', '0000-00-00', '', '09111111111', '1758946192_uid3.jpg', '2025-09-27 04:09:52', '2025-09-28 12:09:32'),
(7, 3, 'Cleymark', 'Balan', '0000-00-00', '', '09111111111', NULL, '2025-09-28 12:09:32', '2025-09-28 12:09:32'),
(8, 3, 'Cleymark', 'Balan', '0000-00-00', '', '09111111111', NULL, '2025-09-28 12:09:33', '2025-09-28 12:09:33'),
(9, 9, NULL, NULL, NULL, 'test2@gmail.com', NULL, NULL, '2025-09-28 13:39:54', '2025-09-28 13:39:54'),
(10, 10, '', '', '0000-00-00', 'andreibalan109@gmail.com', '', NULL, '2025-09-28 13:41:37', '2025-11-05 14:42:28'),
(11, 11, NULL, NULL, NULL, 'balancleymark@gmail.com', NULL, NULL, '2025-10-31 02:21:18', '2025-10-31 02:21:18'),
(12, 12, NULL, NULL, NULL, 'cleymark19@gmail.com', NULL, NULL, '2025-10-31 02:39:36', '2025-10-31 02:39:36'),
(13, 10, '', '', '0000-00-00', 'andreibalan109@gmail.com', '', NULL, '2025-11-05 14:42:28', '2025-11-05 14:42:28');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `session_id` varchar(128) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_data` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_status`
--

CREATE TABLE `user_status` (
  `user_status_id` int(11) NOT NULL,
  `status_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_status`
--

INSERT INTO `user_status` (`user_status_id`, `status_name`) VALUES
(1, 'Active'),
(2, 'Inactive');

-- --------------------------------------------------------

--
-- Table structure for table `user_type`
--

CREATE TABLE `user_type` (
  `user_type_id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_type`
--

INSERT INTO `user_type` (`user_type_id`, `type_name`) VALUES
(1, 'Admin'),
(2, 'Regular'),
(3, 'tourism_officer');

-- --------------------------------------------------------

--
-- Table structure for table `verification_tokens`
--

CREATE TABLE `verification_tokens` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `token_hash` char(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry_time` datetime NOT NULL,
  `consumed_at` datetime DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `verification_tokens`
--

INSERT INTO `verification_tokens` (`token_id`, `user_id`, `token`, `token_hash`, `created_at`, `expiry_time`, `consumed_at`, `verified`) VALUES
(1, 10, '55465a22268f0b420067c954dee82820', '4af3966aa5d9e21b0a7aa0642ca72524351ddcb6abaa0199b46fc29d2b5dd2cb', '2025-11-05 14:42:28', '2025-11-05 16:42:28', NULL, 0),
(2, 10, '26f66513f062171f204becf3f87045eef3fe57aa31d0139ddb48d39831f306ab', 'ff4141aafb491046f06aedfb252d640ccdc5f0a7701fac3386ad0e7b6c18f793', '2025-11-05 14:48:46', '2025-11-05 16:18:46', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `visitors_tracking`
--

CREATE TABLE `visitors_tracking` (
  `tracking_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `visit_date` date DEFAULT NULL,
  `visitor_count` int(11) DEFAULT NULL,
  `spot_id` int(11) DEFAULT NULL,
  `festival_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visitors_tracking`
--

INSERT INTO `visitors_tracking` (`tracking_id`, `user_id`, `visit_date`, `visitor_count`, `spot_id`, `festival_id`) VALUES
(365, NULL, '2025-01-29', 64, 5, NULL),
(366, NULL, '2025-01-29', 74, 1, NULL),
(367, NULL, '2025-01-29', 99, 6, NULL),
(369, NULL, '2025-01-30', 50, 5, NULL),
(370, NULL, '2025-01-30', 98, 1, NULL),
(371, NULL, '2025-01-30', 32, 6, NULL),
(373, NULL, '2025-01-31', 99, 5, NULL),
(374, NULL, '2025-01-31', 23, 1, NULL),
(375, NULL, '2025-01-31', 28, 6, NULL),
(377, NULL, '2025-02-01', 110, 5, NULL),
(378, NULL, '2025-02-01', 127, 1, NULL),
(379, NULL, '2025-02-01', 51, 6, NULL),
(381, NULL, '2025-02-02', 190, 5, NULL),
(382, NULL, '2025-02-02', 115, 1, NULL),
(383, NULL, '2025-02-02', 121, 6, NULL),
(385, NULL, '2025-02-03', 91, 5, NULL),
(386, NULL, '2025-02-03', 62, 1, NULL),
(387, NULL, '2025-02-03', 90, 6, NULL),
(389, NULL, '2025-02-04', 37, 5, NULL),
(390, NULL, '2025-02-04', 75, 1, NULL),
(391, NULL, '2025-02-04', 85, 6, NULL),
(393, NULL, '2025-02-05', 62, 5, NULL),
(394, NULL, '2025-02-05', 49, 1, NULL),
(395, NULL, '2025-02-05', 94, 6, NULL),
(397, NULL, '2025-02-06', 75, 5, NULL),
(398, NULL, '2025-02-06', 60, 1, NULL),
(399, NULL, '2025-02-06', 64, 6, NULL),
(401, NULL, '2025-02-07', 48, 5, NULL),
(402, NULL, '2025-02-07', 70, 1, NULL),
(403, NULL, '2025-02-07', 32, 6, NULL),
(405, NULL, '2025-02-08', 123, 5, NULL),
(406, NULL, '2025-02-08', 136, 1, NULL),
(407, NULL, '2025-02-08', 60, 6, NULL),
(409, NULL, '2025-02-09', 141, 5, NULL),
(410, NULL, '2025-02-09', 122, 1, NULL),
(411, NULL, '2025-02-09', 51, 6, NULL),
(413, NULL, '2025-02-10', 47, 5, NULL),
(414, NULL, '2025-02-10', 33, 1, NULL),
(415, NULL, '2025-02-10', 77, 6, NULL),
(417, NULL, '2025-02-11', 95, 5, NULL),
(418, NULL, '2025-02-11', 41, 1, NULL),
(419, NULL, '2025-02-11', 37, 6, NULL),
(421, NULL, '2025-02-12', 92, 5, NULL),
(422, NULL, '2025-02-12', 79, 1, NULL),
(423, NULL, '2025-02-12', 23, 6, NULL),
(425, NULL, '2025-02-13', 95, 5, NULL),
(426, NULL, '2025-02-13', 40, 1, NULL),
(427, NULL, '2025-02-13', 32, 6, NULL),
(429, NULL, '2025-02-14', 62, 5, NULL),
(430, NULL, '2025-02-14', 66, 1, NULL),
(431, NULL, '2025-02-14', 44, 6, NULL),
(433, NULL, '2025-02-15', 83, 5, NULL),
(434, NULL, '2025-02-15', 50, 1, NULL),
(435, NULL, '2025-02-15', 157, 6, NULL),
(437, NULL, '2025-02-16', 84, 5, NULL),
(438, NULL, '2025-02-16', 108, 1, NULL),
(439, NULL, '2025-02-16', 151, 6, NULL),
(441, NULL, '2025-02-17', 30, 5, NULL),
(442, NULL, '2025-02-17', 70, 1, NULL),
(443, NULL, '2025-02-17', 47, 6, NULL),
(445, NULL, '2025-02-18', 48, 5, NULL),
(446, NULL, '2025-02-18', 49, 1, NULL),
(447, NULL, '2025-02-18', 75, 6, NULL),
(449, NULL, '2025-02-19', 21, 5, NULL),
(450, NULL, '2025-02-19', 69, 1, NULL),
(451, NULL, '2025-02-19', 89, 6, NULL),
(453, NULL, '2025-02-20', 49, 5, NULL),
(454, NULL, '2025-02-20', 51, 1, NULL),
(455, NULL, '2025-02-20', 27, 6, NULL),
(457, NULL, '2025-02-21', 55, 5, NULL),
(458, NULL, '2025-02-21', 76, 1, NULL),
(459, NULL, '2025-02-21', 36, 6, NULL),
(461, NULL, '2025-02-22', 150, 5, NULL),
(462, NULL, '2025-02-22', 193, 1, NULL),
(463, NULL, '2025-02-22', 171, 6, NULL),
(465, NULL, '2025-02-23', 185, 5, NULL),
(466, NULL, '2025-02-23', 76, 1, NULL),
(467, NULL, '2025-02-23', 179, 6, NULL),
(469, NULL, '2025-02-24', 59, 5, NULL),
(470, NULL, '2025-02-24', 70, 1, NULL),
(471, NULL, '2025-02-24', 38, 6, NULL),
(473, NULL, '2025-02-25', 40, 5, NULL),
(474, NULL, '2025-02-25', 33, 1, NULL),
(475, NULL, '2025-02-25', 52, 6, NULL),
(477, NULL, '2025-02-26', 24, 5, NULL),
(478, NULL, '2025-02-26', 63, 1, NULL),
(479, NULL, '2025-02-26', 97, 6, NULL),
(481, NULL, '2025-02-27', 70, 5, NULL),
(482, NULL, '2025-02-27', 81, 1, NULL),
(483, NULL, '2025-02-27', 46, 6, NULL),
(485, NULL, '2025-02-28', 52, 5, NULL),
(486, NULL, '2025-02-28', 42, 1, NULL),
(487, NULL, '2025-02-28', 55, 6, NULL),
(489, NULL, '2025-03-01', 176, 5, NULL),
(490, NULL, '2025-03-01', 194, 1, NULL),
(491, NULL, '2025-03-01', 297, 6, NULL),
(493, NULL, '2025-03-02', 86, 5, NULL),
(494, NULL, '2025-03-02', 233, 1, NULL),
(495, NULL, '2025-03-02', 138, 6, NULL),
(497, NULL, '2025-03-03', 32, 5, NULL),
(498, NULL, '2025-03-03', 96, 1, NULL),
(499, NULL, '2025-03-03', 72, 6, NULL),
(501, NULL, '2025-03-04', 71, 5, NULL),
(502, NULL, '2025-03-04', 122, 1, NULL),
(503, NULL, '2025-03-04', 111, 6, NULL),
(505, NULL, '2025-03-05', 117, 5, NULL),
(506, NULL, '2025-03-05', 92, 1, NULL),
(507, NULL, '2025-03-05', 63, 6, NULL),
(509, NULL, '2025-03-06', 39, 5, NULL),
(510, NULL, '2025-03-06', 36, 1, NULL),
(511, NULL, '2025-03-06', 30, 6, NULL),
(513, NULL, '2025-03-07', 74, 5, NULL),
(514, NULL, '2025-03-07', 107, 1, NULL),
(515, NULL, '2025-03-07', 131, 6, NULL),
(517, NULL, '2025-03-08', 180, 5, NULL),
(518, NULL, '2025-03-08', 258, 1, NULL),
(519, NULL, '2025-03-08', 89, 6, NULL),
(521, NULL, '2025-03-09', 143, 5, NULL),
(522, NULL, '2025-03-09', 113, 1, NULL),
(523, NULL, '2025-03-09', 285, 6, NULL),
(525, NULL, '2025-03-10', 74, 5, NULL),
(526, NULL, '2025-03-10', 69, 1, NULL),
(527, NULL, '2025-03-10', 144, 6, NULL),
(529, NULL, '2025-03-11', 144, 5, NULL),
(530, NULL, '2025-03-11', 44, 1, NULL),
(531, NULL, '2025-03-11', 51, 6, NULL),
(533, NULL, '2025-03-12', 132, 5, NULL),
(534, NULL, '2025-03-12', 66, 1, NULL),
(535, NULL, '2025-03-12', 119, 6, NULL),
(537, NULL, '2025-03-13', 143, 5, NULL),
(538, NULL, '2025-03-13', 114, 1, NULL),
(539, NULL, '2025-03-13', 48, 6, NULL),
(541, NULL, '2025-03-14', 41, 5, NULL),
(542, NULL, '2025-03-14', 45, 1, NULL),
(543, NULL, '2025-03-14', 68, 6, NULL),
(545, NULL, '2025-03-15', 185, 5, NULL),
(546, NULL, '2025-03-15', 83, 1, NULL),
(547, NULL, '2025-03-15', 86, 6, NULL),
(549, NULL, '2025-03-16', 222, 5, NULL),
(550, NULL, '2025-03-16', 299, 1, NULL),
(551, NULL, '2025-03-16', 213, 6, NULL),
(553, NULL, '2025-03-17', 74, 5, NULL),
(554, NULL, '2025-03-17', 122, 1, NULL),
(555, NULL, '2025-03-17', 65, 6, NULL),
(557, NULL, '2025-03-18', 95, 5, NULL),
(558, NULL, '2025-03-18', 110, 1, NULL),
(559, NULL, '2025-03-18', 74, 6, NULL),
(561, NULL, '2025-03-19', 63, 5, NULL),
(562, NULL, '2025-03-19', 113, 1, NULL),
(563, NULL, '2025-03-19', 89, 6, NULL),
(565, NULL, '2025-03-20', 138, 5, NULL),
(566, NULL, '2025-03-20', 129, 1, NULL),
(567, NULL, '2025-03-20', 141, 6, NULL),
(569, NULL, '2025-03-21', 140, 5, NULL),
(570, NULL, '2025-03-21', 60, 1, NULL),
(571, NULL, '2025-03-21', 42, 6, NULL),
(573, NULL, '2025-03-22', 237, 5, NULL),
(574, NULL, '2025-03-22', 239, 1, NULL),
(575, NULL, '2025-03-22', 192, 6, NULL),
(577, NULL, '2025-03-23', 95, 5, NULL),
(578, NULL, '2025-03-23', 218, 1, NULL),
(579, NULL, '2025-03-23', 165, 6, NULL),
(581, NULL, '2025-03-24', 47, 5, NULL),
(582, NULL, '2025-03-24', 143, 1, NULL),
(583, NULL, '2025-03-24', 122, 6, NULL),
(585, NULL, '2025-03-25', 45, 5, NULL),
(586, NULL, '2025-03-25', 89, 1, NULL),
(587, NULL, '2025-03-25', 38, 6, NULL),
(589, NULL, '2025-03-26', 98, 5, NULL),
(590, NULL, '2025-03-26', 147, 1, NULL),
(591, NULL, '2025-03-26', 62, 6, NULL),
(593, NULL, '2025-03-27', 83, 5, NULL),
(594, NULL, '2025-03-27', 56, 1, NULL),
(595, NULL, '2025-03-27', 104, 6, NULL),
(597, NULL, '2025-03-28', 120, 5, NULL),
(598, NULL, '2025-03-28', 104, 1, NULL),
(599, NULL, '2025-03-28', 140, 6, NULL),
(601, NULL, '2025-03-29', 84, 5, NULL),
(602, NULL, '2025-03-29', 143, 1, NULL),
(603, NULL, '2025-03-29', 299, 6, NULL),
(605, NULL, '2025-03-30', 165, 5, NULL),
(606, NULL, '2025-03-30', 177, 1, NULL),
(607, NULL, '2025-03-30', 93, 6, NULL),
(609, NULL, '2025-03-31', 80, 5, NULL),
(610, NULL, '2025-03-31', 63, 1, NULL),
(611, NULL, '2025-03-31', 42, 6, NULL),
(613, NULL, '2025-04-01', 122, 5, NULL),
(614, NULL, '2025-04-01', 72, 1, NULL),
(615, NULL, '2025-04-01', 111, 6, NULL),
(617, NULL, '2025-04-02', 57, 5, NULL),
(618, NULL, '2025-04-02', 96, 1, NULL),
(619, NULL, '2025-04-02', 102, 6, NULL),
(621, NULL, '2025-04-03', 147, 5, NULL),
(622, NULL, '2025-04-03', 113, 1, NULL),
(623, NULL, '2025-04-03', 135, 6, NULL),
(625, NULL, '2025-04-04', 116, 5, NULL),
(626, NULL, '2025-04-04', 69, 1, NULL),
(627, NULL, '2025-04-04', 116, 6, NULL),
(629, NULL, '2025-04-05', 242, 5, NULL),
(630, NULL, '2025-04-05', 95, 1, NULL),
(631, NULL, '2025-04-05', 266, 6, NULL),
(633, NULL, '2025-04-06', 122, 5, NULL),
(634, NULL, '2025-04-06', 159, 1, NULL),
(635, NULL, '2025-04-06', 161, 6, NULL),
(637, NULL, '2025-04-07', 111, 5, NULL),
(638, NULL, '2025-04-07', 33, 1, NULL),
(639, NULL, '2025-04-07', 149, 6, NULL),
(641, NULL, '2025-04-08', 99, 5, NULL),
(642, NULL, '2025-04-08', 89, 1, NULL),
(643, NULL, '2025-04-08', 129, 6, NULL),
(645, NULL, '2025-04-09', 71, 5, NULL),
(646, NULL, '2025-04-09', 56, 1, NULL),
(647, NULL, '2025-04-09', 90, 6, NULL),
(649, NULL, '2025-04-10', 36, 5, NULL),
(650, NULL, '2025-04-10', 65, 1, NULL),
(651, NULL, '2025-04-10', 78, 6, NULL),
(653, NULL, '2025-04-11', 131, 5, NULL),
(654, NULL, '2025-04-11', 95, 1, NULL),
(655, NULL, '2025-04-11', 114, 6, NULL),
(657, NULL, '2025-04-12', 282, 5, NULL),
(658, NULL, '2025-04-12', 173, 1, NULL),
(659, NULL, '2025-04-12', 99, 6, NULL),
(661, NULL, '2025-04-13', 92, 5, NULL),
(662, NULL, '2025-04-13', 221, 1, NULL),
(663, NULL, '2025-04-13', 222, 6, NULL),
(665, NULL, '2025-04-14', 132, 5, NULL),
(666, NULL, '2025-04-14', 54, 1, NULL),
(667, NULL, '2025-04-14', 80, 6, NULL),
(669, NULL, '2025-04-15', 150, 5, NULL),
(670, NULL, '2025-04-15', 140, 1, NULL),
(671, NULL, '2025-04-15', 48, 6, NULL),
(673, NULL, '2025-04-16', 68, 5, NULL),
(674, NULL, '2025-04-16', 41, 1, NULL),
(675, NULL, '2025-04-16', 57, 6, NULL),
(677, NULL, '2025-04-17', 110, 5, NULL),
(678, NULL, '2025-04-17', 90, 1, NULL),
(679, NULL, '2025-04-17', 143, 6, NULL),
(681, NULL, '2025-04-18', 72, 5, NULL),
(682, NULL, '2025-04-18', 89, 1, NULL),
(683, NULL, '2025-04-18', 143, 6, NULL),
(685, NULL, '2025-04-19', 153, 5, NULL),
(686, NULL, '2025-04-19', 300, 1, NULL),
(687, NULL, '2025-04-19', 242, 6, NULL),
(689, NULL, '2025-04-20', 80, 5, NULL),
(690, NULL, '2025-04-20', 204, 1, NULL),
(691, NULL, '2025-04-20', 273, 6, NULL),
(693, NULL, '2025-04-21', 120, 5, NULL),
(694, NULL, '2025-04-21', 116, 1, NULL),
(695, NULL, '2025-04-21', 125, 6, NULL),
(697, NULL, '2025-04-22', 80, 5, NULL),
(698, NULL, '2025-04-22', 72, 1, NULL),
(699, NULL, '2025-04-22', 80, 6, NULL),
(701, NULL, '2025-04-23', 47, 5, NULL),
(702, NULL, '2025-04-23', 125, 1, NULL),
(703, NULL, '2025-04-23', 150, 6, NULL),
(705, NULL, '2025-04-24', 122, 5, NULL),
(706, NULL, '2025-04-24', 36, 1, NULL),
(707, NULL, '2025-04-24', 150, 6, NULL),
(709, NULL, '2025-04-25', 149, 5, NULL),
(710, NULL, '2025-04-25', 92, 1, NULL),
(711, NULL, '2025-04-25', 138, 6, NULL),
(713, NULL, '2025-04-26', 263, 5, NULL),
(714, NULL, '2025-04-26', 281, 1, NULL),
(715, NULL, '2025-04-26', 140, 6, NULL),
(717, NULL, '2025-04-27', 224, 5, NULL),
(718, NULL, '2025-04-27', 191, 1, NULL),
(719, NULL, '2025-04-27', 182, 6, NULL),
(721, NULL, '2025-04-28', 87, 5, NULL),
(722, NULL, '2025-04-28', 135, 1, NULL),
(723, NULL, '2025-04-28', 78, 6, NULL),
(725, NULL, '2025-04-29', 48, 5, NULL),
(726, NULL, '2025-04-29', 119, 1, NULL),
(727, NULL, '2025-04-29', 57, 6, NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_festivals_by_office`
-- (See below for the actual view)
--
CREATE TABLE `vw_festivals_by_office` (
`festival_id` int(11)
,`name` varchar(150)
,`description` text
,`date` date
,`town_id` int(11)
,`image_path` text
,`status` enum('active','inactive')
,`office_id` int(11)
,`office_user_id` int(11)
,`town_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_itineraries_by_office`
-- (See below for the actual view)
--
CREATE TABLE `vw_itineraries_by_office` (
`itinerary_id` int(11)
,`name` varchar(255)
,`description` text
,`town_id` int(11)
,`environmental_fee` varchar(100)
,`image_path` varchar(255)
,`created_at` timestamp
,`office_id` int(11)
,`office_user_id` int(11)
,`town_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_itineraries_overview`
-- (See below for the actual view)
--
CREATE TABLE `vw_itineraries_overview` (
`itinerary_id` int(11)
,`name` varchar(255)
,`description` text
,`visibility` enum('private','unlisted','public')
,`published_at` datetime
,`image_path` varchar(255)
,`town_id` int(11)
,`town_name` varchar(100)
,`created_at` timestamp
,`status` enum('active','inactive')
,`day_count` bigint(21)
,`item_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_itinerary_booking_detail`
-- (See below for the actual view)
--
CREATE TABLE `vw_itinerary_booking_detail` (
`itinerary_id` int(11)
,`name` varchar(255)
,`description` text
,`image_path` varchar(255)
,`base_price` decimal(10,2)
,`price_currency` varchar(3)
,`duration_hours` int(11)
,`start_time` time
,`mobile_ticket` tinyint(1)
,`instant_confirmation` tinyint(1)
,`free_cancellation` tinyint(1)
,`cancellation_hours` int(11)
,`min_travelers` int(11)
,`max_travelers` int(11)
,`highlights` text
,`whats_included` text
,`whats_excluded` text
,`what_to_bring` text
,`accessibility_info` text
,`additional_info` text
,`meeting_point` text
,`end_point` text
,`pickup_offered` tinyint(1)
,`pickup_details` text
,`visibility` enum('private','unlisted','public')
,`status` enum('active','inactive')
,`town_id` int(11)
,`town_name` varchar(100)
,`total_reviews` int(11)
,`average_rating` decimal(3,2)
,`rating_5_count` decimal(22,0)
,`rating_4_count` decimal(22,0)
,`rating_3_count` decimal(22,0)
,`rating_2_count` decimal(22,0)
,`rating_1_count` decimal(22,0)
,`rating_5_percent` decimal(27,1)
,`rating_4_percent` decimal(27,1)
,`rating_3_percent` decimal(27,1)
,`rating_2_percent` decimal(27,1)
,`rating_1_percent` decimal(27,1)
,`family_reviews` decimal(22,0)
,`couple_reviews` decimal(22,0)
,`solo_reviews` decimal(22,0)
,`business_reviews` decimal(22,0)
,`friends_reviews` decimal(22,0)
,`reviews_with_photos` decimal(22,0)
,`day_count` bigint(21)
,`item_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_itinerary_detail`
-- (See below for the actual view)
--
CREATE TABLE `vw_itinerary_detail` (
`itinerary_id` int(11)
,`itinerary_name` varchar(255)
,`itinerary_description` text
,`visibility` enum('private','unlisted','public')
,`published_at` datetime
,`image_path` varchar(255)
,`town_id` int(11)
,`town_name` varchar(100)
,`status` enum('active','inactive')
,`day_number` int(11)
,`day_title` varchar(150)
,`day_description` text
,`item_id` int(11)
,`spot_id` int(11)
,`item_name` varchar(255)
,`custom_name` varchar(255)
,`start_time` time
,`end_time` time
,`estimated_duration_minutes` int(11)
,`travel_minutes_from_prev` int(11)
,`notes` text
,`sort_order` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_itinerary_items_flat`
-- (See below for the actual view)
--
CREATE TABLE `vw_itinerary_items_flat` (
`item_id` int(11)
,`itinerary_id` int(11)
,`day_id` binary(0)
,`day_number` int(11)
,`spot_id` int(11)
,`custom_name` varchar(255)
,`start_time` time
,`end_time` time
,`estimated_duration_minutes` int(11)
,`travel_minutes_from_prev` int(11)
,`notes` text
,`sort_order` int(11)
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_itinerary_review_breakdown`
-- (See below for the actual view)
--
CREATE TABLE `vw_itinerary_review_breakdown` (
`itinerary_id` int(11)
,`total_reviews` bigint(21)
,`average_rating` decimal(7,4)
,`rating_5_count` decimal(22,0)
,`rating_4_count` decimal(22,0)
,`rating_3_count` decimal(22,0)
,`rating_2_count` decimal(22,0)
,`rating_1_count` decimal(22,0)
,`rating_5_percent` decimal(27,1)
,`rating_4_percent` decimal(27,1)
,`rating_3_percent` decimal(27,1)
,`rating_2_percent` decimal(27,1)
,`rating_1_percent` decimal(27,1)
,`family_reviews` decimal(22,0)
,`couple_reviews` decimal(22,0)
,`solo_reviews` decimal(22,0)
,`business_reviews` decimal(22,0)
,`friends_reviews` decimal(22,0)
,`reviews_with_photos` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_spots_with_maps`
-- (See below for the actual view)
--
CREATE TABLE `vw_spots_with_maps` (
`spot_id` int(11)
,`spot_name` varchar(150)
,`category` varchar(100)
,`status` enum('active','inactive','maintenance')
,`town_name` varchar(100)
,`latitude` decimal(10,7)
,`longitude` decimal(10,7)
,`accuracy` enum('exact','approximate','centroid','imported')
,`google_maps_url` varchar(76)
,`maps_integration_status` varchar(8)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_tourist_spots_by_office`
-- (See below for the actual view)
--
CREATE TABLE `vw_tourist_spots_by_office` (
`spot_id` int(11)
,`name` varchar(150)
,`description` text
,`category` varchar(100)
,`town_id` int(11)
,`contact_info` varchar(100)
,`image_path` text
,`status` enum('active','inactive','maintenance')
,`created_at` timestamp
,`updated_at` timestamp
,`office_id` int(11)
,`office_user_id` int(11)
,`town_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_tourist_spot_capacities`
-- (See below for the actual view)
--
CREATE TABLE `vw_tourist_spot_capacities` (
`capacity_id` int(11)
,`spot_id` int(11)
,`current_capacity` int(11)
,`max_capacity` int(11)
,`capacity_percentage` decimal(5,2)
,`last_updated` timestamp
,`updated_by` int(11)
,`spot_name` varchar(150)
,`category` varchar(100)
,`town_name` varchar(100)
,`updated_by_user` varchar(100)
,`office_id` int(11)
);

-- --------------------------------------------------------

--
-- Structure for view `itineraries_items`
--
DROP TABLE IF EXISTS `itineraries_items`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY INVOKER VIEW `itineraries_items`  AS SELECT `i`.`itinerary_id` AS `itinerary_id`, `i`.`name` AS `itinerary_name`, `i`.`description` AS `itinerary_description`, `i`.`visibility` AS `visibility`, `i`.`published_at` AS `published_at`, `i`.`image_path` AS `image_path`, `i`.`town_id` AS `town_id`, `t`.`name` AS `town_name`, `i`.`status` AS `itinerary_status`, NULL AS `day_id`, `i`.`day_number` AS `day_number`, `i`.`day_title` AS `day_title`, `i`.`day_description` AS `day_description`, `i`.`item_id` AS `item_id`, `i`.`spot_id` AS `spot_id`, coalesce(`ts`.`name`,`i`.`custom_name`) AS `item_name`, `i`.`custom_name` AS `custom_name`, `i`.`start_time` AS `start_time`, `i`.`end_time` AS `end_time`, `i`.`estimated_duration_minutes` AS `estimated_duration_minutes`, `i`.`travel_minutes_from_prev` AS `travel_minutes_from_prev`, `i`.`notes` AS `notes`, `i`.`sort_order` AS `sort_order`, `i`.`created_at` AS `item_created_at`, `i`.`created_at` AS `item_updated_at` FROM ((`itineraries` `i` left join `towns` `t` on(`t`.`town_id` = `i`.`town_id`)) left join `tourist_spots` `ts` on(`ts`.`spot_id` = `i`.`spot_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_festivals_by_office`
--
DROP TABLE IF EXISTS `vw_festivals_by_office`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY INVOKER VIEW `vw_festivals_by_office`  AS SELECT `f`.`festival_id` AS `festival_id`, `f`.`name` AS `name`, `f`.`description` AS `description`, `f`.`date` AS `date`, `f`.`town_id` AS `town_id`, `f`.`image_path` AS `image_path`, `f`.`status` AS `status`, `toff`.`office_id` AS `office_id`, `toff`.`user_id` AS `office_user_id`, `t`.`name` AS `town_name` FROM ((`festivals` `f` join `towns` `t` on(`f`.`town_id` = `t`.`town_id`)) join `tourism_office` `toff` on(`toff`.`town_id` = `f`.`town_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_itineraries_by_office`
--
DROP TABLE IF EXISTS `vw_itineraries_by_office`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY INVOKER VIEW `vw_itineraries_by_office`  AS SELECT DISTINCT `i`.`itinerary_id` AS `itinerary_id`, `i`.`name` AS `name`, `i`.`description` AS `description`, `i`.`town_id` AS `town_id`, `i`.`environmental_fee` AS `environmental_fee`, `i`.`image_path` AS `image_path`, `i`.`created_at` AS `created_at`, `toff`.`office_id` AS `office_id`, `toff`.`user_id` AS `office_user_id`, `t`.`name` AS `town_name` FROM ((`itineraries` `i` join `towns` `t` on(`t`.`town_id` = `i`.`town_id`)) join `tourism_office` `toff` on(`toff`.`town_id` = `i`.`town_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_itineraries_overview`
--
DROP TABLE IF EXISTS `vw_itineraries_overview`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY INVOKER VIEW `vw_itineraries_overview`  AS SELECT `base`.`itinerary_id` AS `itinerary_id`, `base`.`name` AS `name`, `base`.`description` AS `description`, `base`.`visibility` AS `visibility`, `base`.`published_at` AS `published_at`, `base`.`image_path` AS `image_path`, `base`.`town_id` AS `town_id`, `t`.`name` AS `town_name`, `base`.`created_at` AS `created_at`, `base`.`status` AS `status`, coalesce(`cnt`.`day_count`,0) AS `day_count`, coalesce(`cnt`.`item_count`,0) AS `item_count` FROM (((select `itineraries`.`itinerary_id` AS `itinerary_id`,max(`itineraries`.`name`) AS `name`,max(`itineraries`.`description`) AS `description`,max(`itineraries`.`visibility`) AS `visibility`,max(`itineraries`.`published_at`) AS `published_at`,max(`itineraries`.`image_path`) AS `image_path`,max(`itineraries`.`town_id`) AS `town_id`,min(`itineraries`.`created_at`) AS `created_at`,max(`itineraries`.`status`) AS `status` from `itineraries` group by `itineraries`.`itinerary_id`) `base` left join `towns` `t` on(`t`.`town_id` = `base`.`town_id`)) left join (select `itineraries`.`itinerary_id` AS `itinerary_id`,count(distinct `itineraries`.`day_number`) AS `day_count`,count(`itineraries`.`item_id`) AS `item_count` from `itineraries` group by `itineraries`.`itinerary_id`) `cnt` on(`cnt`.`itinerary_id` = `base`.`itinerary_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_itinerary_booking_detail`
--
DROP TABLE IF EXISTS `vw_itinerary_booking_detail`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY INVOKER VIEW `vw_itinerary_booking_detail`  AS SELECT `i`.`itinerary_id` AS `itinerary_id`, `i`.`name` AS `name`, `i`.`description` AS `description`, `i`.`image_path` AS `image_path`, `i`.`base_price` AS `base_price`, `i`.`price_currency` AS `price_currency`, `i`.`duration_hours` AS `duration_hours`, `i`.`start_time` AS `start_time`, `i`.`mobile_ticket` AS `mobile_ticket`, `i`.`instant_confirmation` AS `instant_confirmation`, `i`.`free_cancellation` AS `free_cancellation`, `i`.`cancellation_hours` AS `cancellation_hours`, `i`.`min_travelers` AS `min_travelers`, `i`.`max_travelers` AS `max_travelers`, `i`.`highlights` AS `highlights`, `i`.`whats_included` AS `whats_included`, `i`.`whats_excluded` AS `whats_excluded`, `i`.`what_to_bring` AS `what_to_bring`, `i`.`accessibility_info` AS `accessibility_info`, `i`.`additional_info` AS `additional_info`, `i`.`meeting_point` AS `meeting_point`, `i`.`end_point` AS `end_point`, `i`.`pickup_offered` AS `pickup_offered`, `i`.`pickup_details` AS `pickup_details`, `i`.`visibility` AS `visibility`, `i`.`status` AS `status`, `t`.`town_id` AS `town_id`, `t`.`name` AS `town_name`, coalesce(`i`.`total_reviews`,0) AS `total_reviews`, coalesce(`i`.`average_rating`,0) AS `average_rating`, coalesce(`rb`.`rating_5_count`,0) AS `rating_5_count`, coalesce(`rb`.`rating_4_count`,0) AS `rating_4_count`, coalesce(`rb`.`rating_3_count`,0) AS `rating_3_count`, coalesce(`rb`.`rating_2_count`,0) AS `rating_2_count`, coalesce(`rb`.`rating_1_count`,0) AS `rating_1_count`, coalesce(`rb`.`rating_5_percent`,0) AS `rating_5_percent`, coalesce(`rb`.`rating_4_percent`,0) AS `rating_4_percent`, coalesce(`rb`.`rating_3_percent`,0) AS `rating_3_percent`, coalesce(`rb`.`rating_2_percent`,0) AS `rating_2_percent`, coalesce(`rb`.`rating_1_percent`,0) AS `rating_1_percent`, coalesce(`rb`.`family_reviews`,0) AS `family_reviews`, coalesce(`rb`.`couple_reviews`,0) AS `couple_reviews`, coalesce(`rb`.`solo_reviews`,0) AS `solo_reviews`, coalesce(`rb`.`business_reviews`,0) AS `business_reviews`, coalesce(`rb`.`friends_reviews`,0) AS `friends_reviews`, coalesce(`rb`.`reviews_with_photos`,0) AS `reviews_with_photos`, (select count(distinct `i2`.`day_number`) from `itineraries` `i2` where `i2`.`itinerary_id` = `i`.`itinerary_id`) AS `day_count`, (select count(0) from `itineraries` `i2` where `i2`.`itinerary_id` = `i`.`itinerary_id` and `i2`.`item_id` is not null) AS `item_count` FROM (((select distinct `itineraries`.`itinerary_id` AS `itinerary_id`,max(`itineraries`.`name`) AS `name`,max(`itineraries`.`description`) AS `description`,max(`itineraries`.`image_path`) AS `image_path`,max(`itineraries`.`base_price`) AS `base_price`,max(`itineraries`.`price_currency`) AS `price_currency`,max(`itineraries`.`duration_hours`) AS `duration_hours`,max(`itineraries`.`start_time`) AS `start_time`,max(`itineraries`.`mobile_ticket`) AS `mobile_ticket`,max(`itineraries`.`instant_confirmation`) AS `instant_confirmation`,max(`itineraries`.`free_cancellation`) AS `free_cancellation`,max(`itineraries`.`cancellation_hours`) AS `cancellation_hours`,max(`itineraries`.`min_travelers`) AS `min_travelers`,max(`itineraries`.`max_travelers`) AS `max_travelers`,max(`itineraries`.`highlights`) AS `highlights`,max(`itineraries`.`whats_included`) AS `whats_included`,max(`itineraries`.`whats_excluded`) AS `whats_excluded`,max(`itineraries`.`what_to_bring`) AS `what_to_bring`,max(`itineraries`.`accessibility_info`) AS `accessibility_info`,max(`itineraries`.`additional_info`) AS `additional_info`,max(`itineraries`.`meeting_point`) AS `meeting_point`,max(`itineraries`.`end_point`) AS `end_point`,max(`itineraries`.`pickup_offered`) AS `pickup_offered`,max(`itineraries`.`pickup_details`) AS `pickup_details`,max(`itineraries`.`visibility`) AS `visibility`,max(`itineraries`.`status`) AS `status`,max(`itineraries`.`town_id`) AS `town_id`,max(`itineraries`.`total_reviews`) AS `total_reviews`,max(`itineraries`.`average_rating`) AS `average_rating` from `itineraries` group by `itineraries`.`itinerary_id`) `i` left join `towns` `t` on(`i`.`town_id` = `t`.`town_id`)) left join `vw_itinerary_review_breakdown` `rb` on(`rb`.`itinerary_id` = `i`.`itinerary_id`)) WHERE `i`.`status` = 'active' ;

-- --------------------------------------------------------

--
-- Structure for view `vw_itinerary_detail`
--
DROP TABLE IF EXISTS `vw_itinerary_detail`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY INVOKER VIEW `vw_itinerary_detail`  AS SELECT `i`.`itinerary_id` AS `itinerary_id`, `i`.`name` AS `itinerary_name`, `i`.`description` AS `itinerary_description`, `i`.`visibility` AS `visibility`, `i`.`published_at` AS `published_at`, `i`.`image_path` AS `image_path`, `i`.`town_id` AS `town_id`, `t`.`name` AS `town_name`, `i`.`status` AS `status`, `i`.`day_number` AS `day_number`, `i`.`day_title` AS `day_title`, `i`.`day_description` AS `day_description`, `i`.`item_id` AS `item_id`, `i`.`spot_id` AS `spot_id`, coalesce(`ts`.`name`,`i`.`custom_name`) AS `item_name`, `i`.`custom_name` AS `custom_name`, `i`.`start_time` AS `start_time`, `i`.`end_time` AS `end_time`, `i`.`estimated_duration_minutes` AS `estimated_duration_minutes`, `i`.`travel_minutes_from_prev` AS `travel_minutes_from_prev`, `i`.`notes` AS `notes`, `i`.`sort_order` AS `sort_order` FROM ((`itineraries` `i` left join `towns` `t` on(`t`.`town_id` = `i`.`town_id`)) left join `tourist_spots` `ts` on(`ts`.`spot_id` = `i`.`spot_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_itinerary_items_flat`
--
DROP TABLE IF EXISTS `vw_itinerary_items_flat`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY INVOKER VIEW `vw_itinerary_items_flat`  AS SELECT `i`.`item_id` AS `item_id`, `i`.`itinerary_id` AS `itinerary_id`, NULL AS `day_id`, `i`.`day_number` AS `day_number`, `i`.`spot_id` AS `spot_id`, `i`.`custom_name` AS `custom_name`, `i`.`start_time` AS `start_time`, `i`.`end_time` AS `end_time`, `i`.`estimated_duration_minutes` AS `estimated_duration_minutes`, `i`.`travel_minutes_from_prev` AS `travel_minutes_from_prev`, `i`.`notes` AS `notes`, `i`.`sort_order` AS `sort_order`, `i`.`created_at` AS `created_at`, `i`.`created_at` AS `updated_at` FROM `itineraries` AS `i` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_itinerary_review_breakdown`
--
DROP TABLE IF EXISTS `vw_itinerary_review_breakdown`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY INVOKER VIEW `vw_itinerary_review_breakdown`  AS SELECT `r`.`entity_id` AS `itinerary_id`, count(0) AS `total_reviews`, avg(`r`.`rating`) AS `average_rating`, sum(case when `r`.`rating` = 5 then 1 else 0 end) AS `rating_5_count`, sum(case when `r`.`rating` = 4 then 1 else 0 end) AS `rating_4_count`, sum(case when `r`.`rating` = 3 then 1 else 0 end) AS `rating_3_count`, sum(case when `r`.`rating` = 2 then 1 else 0 end) AS `rating_2_count`, sum(case when `r`.`rating` = 1 then 1 else 0 end) AS `rating_1_count`, round(sum(case when `r`.`rating` = 5 then 1 else 0 end) / count(0) * 100,1) AS `rating_5_percent`, round(sum(case when `r`.`rating` = 4 then 1 else 0 end) / count(0) * 100,1) AS `rating_4_percent`, round(sum(case when `r`.`rating` = 3 then 1 else 0 end) / count(0) * 100,1) AS `rating_3_percent`, round(sum(case when `r`.`rating` = 2 then 1 else 0 end) / count(0) * 100,1) AS `rating_2_percent`, round(sum(case when `r`.`rating` = 1 then 1 else 0 end) / count(0) * 100,1) AS `rating_1_percent`, sum(case when `r`.`traveler_type` = 'family' then 1 else 0 end) AS `family_reviews`, sum(case when `r`.`traveler_type` = 'couple' then 1 else 0 end) AS `couple_reviews`, sum(case when `r`.`traveler_type` = 'solo' then 1 else 0 end) AS `solo_reviews`, sum(case when `r`.`traveler_type` = 'business' then 1 else 0 end) AS `business_reviews`, sum(case when `r`.`traveler_type` = 'friends' then 1 else 0 end) AS `friends_reviews`, sum(case when `r`.`has_photos` = 1 then 1 else 0 end) AS `reviews_with_photos` FROM `reviews` AS `r` WHERE `r`.`entity_type` = 'itinerary' AND `r`.`status` = 'active' GROUP BY `r`.`entity_id` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_spots_with_maps`
--
DROP TABLE IF EXISTS `vw_spots_with_maps`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY INVOKER VIEW `vw_spots_with_maps`  AS SELECT `ts`.`spot_id` AS `spot_id`, `ts`.`name` AS `spot_name`, `ts`.`category` AS `category`, `ts`.`status` AS `status`, `t`.`name` AS `town_name`, `gp`.`latitude` AS `latitude`, `gp`.`longitude` AS `longitude`, `gp`.`accuracy` AS `accuracy`, concat('https://www.google.com/maps/dir/?api=1&destination=',`gp`.`latitude`,',',`gp`.`longitude`) AS `google_maps_url`, CASE WHEN `gp`.`latitude` is not null THEN 'enabled' ELSE 'disabled' END AS `maps_integration_status` FROM ((`tourist_spots` `ts` left join `towns` `t` on(`ts`.`town_id` = `t`.`town_id`)) left join `geo_points` `gp` on(`gp`.`entity_type` = 'tourist_spot' and `gp`.`entity_id` = `ts`.`spot_id`)) WHERE `ts`.`status` = 'active' ;

-- --------------------------------------------------------

--
-- Structure for view `vw_tourist_spots_by_office`
--
DROP TABLE IF EXISTS `vw_tourist_spots_by_office`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY INVOKER VIEW `vw_tourist_spots_by_office`  AS SELECT `ts`.`spot_id` AS `spot_id`, `ts`.`name` AS `name`, `ts`.`description` AS `description`, `ts`.`category` AS `category`, `ts`.`town_id` AS `town_id`, `ts`.`contact_info` AS `contact_info`, `ts`.`image_path` AS `image_path`, `ts`.`status` AS `status`, `ts`.`created_at` AS `created_at`, `ts`.`updated_at` AS `updated_at`, `toff`.`office_id` AS `office_id`, `toff`.`user_id` AS `office_user_id`, `t`.`name` AS `town_name` FROM ((`tourist_spots` `ts` join `towns` `t` on(`ts`.`town_id` = `t`.`town_id`)) join `tourism_office` `toff` on(`toff`.`town_id` = `ts`.`town_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_tourist_spot_capacities`
--
DROP TABLE IF EXISTS `vw_tourist_spot_capacities`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY INVOKER VIEW `vw_tourist_spot_capacities`  AS SELECT `tsc`.`capacity_id` AS `capacity_id`, `tsc`.`spot_id` AS `spot_id`, `tsc`.`current_capacity` AS `current_capacity`, `tsc`.`max_capacity` AS `max_capacity`, `tsc`.`capacity_percentage` AS `capacity_percentage`, `tsc`.`last_updated` AS `last_updated`, `tsc`.`updated_by` AS `updated_by`, `ts`.`name` AS `spot_name`, `ts`.`category` AS `category`, `t`.`name` AS `town_name`, `u`.`username` AS `updated_by_user`, `toff`.`office_id` AS `office_id` FROM ((((`tourist_spot_capacity` `tsc` join `tourist_spots` `ts` on(`tsc`.`spot_id` = `ts`.`spot_id`)) join `towns` `t` on(`ts`.`town_id` = `t`.`town_id`)) join `user` `u` on(`tsc`.`updated_by` = `u`.`user_id`)) left join `tourism_office` `toff` on(`ts`.`town_id` = `toff`.`town_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barangay`
--
ALTER TABLE `barangay`
  ADD PRIMARY KEY (`barangay_id`),
  ADD KEY `town_id` (`town_id`);

--
-- Indexes for table `booking_travelers`
--
ALTER TABLE `booking_travelers`
  ADD PRIMARY KEY (`traveler_id`),
  ADD KEY `idx_travelers_booking` (`booking_id`),
  ADD KEY `idx_travelers_tier` (`pricing_tier_id`);

--
-- Indexes for table `change_password_2fa_codes`
--
ALTER TABLE `change_password_2fa_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `code` (`code`);

--
-- Indexes for table `email_verification_codes`
--
ALTER TABLE `email_verification_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_active_email` (`email`,`verified`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_code` (`code`);

--
-- Indexes for table `fares`
--
ALTER TABLE `fares`
  ADD PRIMARY KEY (`fare_id`),
  ADD KEY `from_terminal_id` (`from_terminal_id`),
  ADD KEY `to_terminal_id` (`to_terminal_id`),
  ADD KEY `idx_fares_type_id` (`type_id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `festivals`
--
ALTER TABLE `festivals`
  ADD PRIMARY KEY (`festival_id`),
  ADD KEY `town_id` (`town_id`);

--
-- Indexes for table `geo_points`
--
ALTER TABLE `geo_points`
  ADD PRIMARY KEY (`entity_type`,`entity_id`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_lat_lng` (`latitude`,`longitude`),
  ADD KEY `idx_geo_points_lookup` (`entity_type`,`entity_id`,`latitude`,`longitude`);

--
-- Indexes for table `itineraries`
--
ALTER TABLE `itineraries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_it_new_itinerary` (`itinerary_id`),
  ADD KEY `idx_it_new_creator` (`creator_user_id`),
  ADD KEY `idx_it_new_town` (`town_id`),
  ADD KEY `idx_it_new_destination` (`destination_id`),
  ADD KEY `idx_it_new_visibility` (`visibility`),
  ADD KEY `idx_it_new_published` (`published_at`),
  ADD KEY `idx_it_new_spot` (`spot_id`),
  ADD KEY `idx_it_new_day_order` (`day_number`,`sort_order`),
  ADD KEY `idx_it_new_created` (`created_at`),
  ADD KEY `idx_itineraries_booking` (`itinerary_id`,`status`,`visibility`);

--
-- Indexes for table `itinerary_bookings`
--
ALTER TABLE `itinerary_bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `uniq_booking_ref` (`booking_reference`),
  ADD KEY `idx_bookings_itinerary` (`itinerary_id`),
  ADD KEY `idx_bookings_user` (`user_id`),
  ADD KEY `idx_bookings_travel_date` (`travel_date`),
  ADD KEY `idx_bookings_status` (`booking_status`),
  ADD KEY `idx_bookings_payment` (`payment_status`);

--
-- Indexes for table `itinerary_faqs`
--
ALTER TABLE `itinerary_faqs`
  ADD PRIMARY KEY (`faq_id`),
  ADD KEY `idx_faq_itinerary` (`itinerary_id`),
  ADD KEY `idx_faq_status` (`status`);

--
-- Indexes for table `itinerary_photos`
--
ALTER TABLE `itinerary_photos`
  ADD PRIMARY KEY (`photo_id`),
  ADD KEY `idx_photos_itinerary` (`itinerary_id`),
  ADD KEY `idx_photos_type` (`photo_type`),
  ADD KEY `idx_photos_hero` (`is_hero`),
  ADD KEY `idx_photos_user` (`uploaded_by_user_id`);

--
-- Indexes for table `itinerary_pricing_tiers`
--
ALTER TABLE `itinerary_pricing_tiers`
  ADD PRIMARY KEY (`tier_id`),
  ADD KEY `idx_pricing_itinerary` (`itinerary_id`),
  ADD KEY `idx_pricing_status` (`status`);

--
-- Indexes for table `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`location_id`),
  ADD KEY `town_id` (`town_id`);

--
-- Indexes for table `login_2fa_codes`
--
ALTER TABLE `login_2fa_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `code` (`code`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD KEY `idx_attempts_user` (`user_id`),
  ADD KEY `idx_attempts_username_time` (`username_input`,`attempt_time`),
  ADD KEY `idx_attempts_ip_time` (`ip_address`,`attempt_time`);

--
-- Indexes for table `login_history`
--
ALTER TABLE `login_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `map_editor`
--
ALTER TABLE `map_editor`
  ADD PRIMARY KEY (`map_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `map_images`
--
ALTER TABLE `map_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `map_id` (`map_id`);

--
-- Indexes for table `password_reset`
--
ALTER TABLE `password_reset`
  ADD PRIMARY KEY (`reset_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `token_hash` (`token_hash`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `unique_permission_name` (`name`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `spot_id` (`spot_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_entity_type_id` (`entity_type`,`entity_id`),
  ADD KEY `idx_reviews_entity_status` (`entity_type`,`entity_id`,`status`);

--
-- Indexes for table `review_photos`
--
ALTER TABLE `review_photos`
  ADD PRIMARY KEY (`photo_id`),
  ADD KEY `idx_review_photos_review` (`review_id`);

--
-- Indexes for table `route_transport_types`
--
ALTER TABLE `route_transport_types`
  ADD PRIMARY KEY (`route_id`,`type_id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `sms_verification_codes`
--
ALTER TABLE `sms_verification_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_active_phone` (`phone`,`verified`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `spot_images`
--
ALTER TABLE `spot_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `idx_spot_images_spot` (`spot_id`),
  ADD KEY `idx_spot_images_primary` (`spot_id`,`is_primary`,`position`);

--
-- Indexes for table `terminal_locations`
--
ALTER TABLE `terminal_locations`
  ADD PRIMARY KEY (`terminal_id`);

--
-- Indexes for table `tourism_fee_log`
--
ALTER TABLE `tourism_fee_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `municipality_id` (`municipality_id`),
  ADD KEY `spot_id` (`spot_id`),
  ADD KEY `idx_fee_log_municipality_date` (`municipality_id`,`visit_date`),
  ADD KEY `idx_fee_log_spot` (`spot_id`),
  ADD KEY `idx_fee_log_recorded_by` (`recorded_by_user_id`);

--
-- Indexes for table `tourism_office`
--
ALTER TABLE `tourism_office`
  ADD PRIMARY KEY (`office_id`),
  ADD KEY `town_id` (`town_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tourism_office_content`
--
ALTER TABLE `tourism_office_content`
  ADD PRIMARY KEY (`content_id`),
  ADD UNIQUE KEY `uniq_office_content` (`office_id`,`content_type`,`content_reference_id`),
  ADD KEY `office_id` (`office_id`);

--
-- Indexes for table `tourist_capacity`
--
ALTER TABLE `tourist_capacity`
  ADD PRIMARY KEY (`capacity_id`),
  ADD KEY `spot_id` (`spot_id`);

--
-- Indexes for table `tourist_spots`
--
ALTER TABLE `tourist_spots`
  ADD PRIMARY KEY (`spot_id`),
  ADD KEY `town_id` (`town_id`);

--
-- Indexes for table `tourist_spot_capacity`
--
ALTER TABLE `tourist_spot_capacity`
  ADD PRIMARY KEY (`capacity_id`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_spot_capacity` (`spot_id`),
  ADD KEY `idx_capacity_updated` (`last_updated`);

--
-- Indexes for table `towns`
--
ALTER TABLE `towns`
  ADD PRIMARY KEY (`town_id`);

--
-- Indexes for table `transport_routes`
--
ALTER TABLE `transport_routes`
  ADD PRIMARY KEY (`route_id`),
  ADD KEY `from_terminal_id` (`from_terminal_id`),
  ADD KEY `to_terminal_id` (`to_terminal_id`);

--
-- Indexes for table `transport_types`
--
ALTER TABLE `transport_types`
  ADD PRIMARY KEY (`type_id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `unique_username` (`username`),
  ADD UNIQUE KEY `unique_user_email` (`email`),
  ADD UNIQUE KEY `unique_google_id` (`google_id`),
  ADD KEY `user_type_id` (`user_type_id`),
  ADD KEY `user_status_id` (`user_status_id`),
  ADD KEY `town_id` (`town_id`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`user_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `user_profile`
--
ALTER TABLE `user_profile`
  ADD PRIMARY KEY (`profile_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `user_status`
--
ALTER TABLE `user_status`
  ADD PRIMARY KEY (`user_status_id`),
  ADD UNIQUE KEY `unique_status_name` (`status_name`);

--
-- Indexes for table `user_type`
--
ALTER TABLE `user_type`
  ADD PRIMARY KEY (`user_type_id`),
  ADD UNIQUE KEY `unique_type_name` (`type_name`);

--
-- Indexes for table `verification_tokens`
--
ALTER TABLE `verification_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD UNIQUE KEY `uniq_token_hash` (`token_hash`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_tokens_user_verified` (`user_id`,`verified`);

--
-- Indexes for table `visitors_tracking`
--
ALTER TABLE `visitors_tracking`
  ADD PRIMARY KEY (`tracking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `spot_id` (`spot_id`),
  ADD KEY `festival_id` (`festival_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barangay`
--
ALTER TABLE `barangay`
  MODIFY `barangay_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking_travelers`
--
ALTER TABLE `booking_travelers`
  MODIFY `traveler_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `change_password_2fa_codes`
--
ALTER TABLE `change_password_2fa_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `email_verification_codes`
--
ALTER TABLE `email_verification_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `fares`
--
ALTER TABLE `fares`
  MODIFY `fare_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `festivals`
--
ALTER TABLE `festivals`
  MODIFY `festival_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `itineraries`
--
ALTER TABLE `itineraries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `itinerary_bookings`
--
ALTER TABLE `itinerary_bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `itinerary_faqs`
--
ALTER TABLE `itinerary_faqs`
  MODIFY `faq_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `itinerary_photos`
--
ALTER TABLE `itinerary_photos`
  MODIFY `photo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `itinerary_pricing_tiers`
--
ALTER TABLE `itinerary_pricing_tiers`
  MODIFY `tier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_2fa_codes`
--
ALTER TABLE `login_2fa_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `attempt_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_history`
--
ALTER TABLE `login_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `map_editor`
--
ALTER TABLE `map_editor`
  MODIFY `map_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `map_images`
--
ALTER TABLE `map_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset`
--
ALTER TABLE `password_reset`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `review_photos`
--
ALTER TABLE `review_photos`
  MODIFY `photo_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_verification_codes`
--
ALTER TABLE `sms_verification_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `spot_images`
--
ALTER TABLE `spot_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `terminal_locations`
--
ALTER TABLE `terminal_locations`
  MODIFY `terminal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tourism_fee_log`
--
ALTER TABLE `tourism_fee_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tourism_office`
--
ALTER TABLE `tourism_office`
  MODIFY `office_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tourism_office_content`
--
ALTER TABLE `tourism_office_content`
  MODIFY `content_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `tourist_capacity`
--
ALTER TABLE `tourist_capacity`
  MODIFY `capacity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `tourist_spots`
--
ALTER TABLE `tourist_spots`
  MODIFY `spot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `tourist_spot_capacity`
--
ALTER TABLE `tourist_spot_capacity`
  MODIFY `capacity_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `towns`
--
ALTER TABLE `towns`
  MODIFY `town_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `transport_routes`
--
ALTER TABLE `transport_routes`
  MODIFY `route_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `transport_types`
--
ALTER TABLE `transport_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `user_profile`
--
ALTER TABLE `user_profile`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_status`
--
ALTER TABLE `user_status`
  MODIFY `user_status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_type`
--
ALTER TABLE `user_type`
  MODIFY `user_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `verification_tokens`
--
ALTER TABLE `verification_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `visitors_tracking`
--
ALTER TABLE `visitors_tracking`
  MODIFY `tracking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=728;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barangay`
--
ALTER TABLE `barangay`
  ADD CONSTRAINT `barangay_ibfk_1` FOREIGN KEY (`town_id`) REFERENCES `towns` (`town_id`);

--
-- Constraints for table `booking_travelers`
--
ALTER TABLE `booking_travelers`
  ADD CONSTRAINT `fk_travelers_booking` FOREIGN KEY (`booking_id`) REFERENCES `itinerary_bookings` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_travelers_tier` FOREIGN KEY (`pricing_tier_id`) REFERENCES `itinerary_pricing_tiers` (`tier_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `fares`
--
ALTER TABLE `fares`
  ADD CONSTRAINT `fares_fk_from_terminal` FOREIGN KEY (`from_terminal_id`) REFERENCES `terminal_locations` (`terminal_id`),
  ADD CONSTRAINT `fares_fk_to_terminal` FOREIGN KEY (`to_terminal_id`) REFERENCES `terminal_locations` (`terminal_id`),
  ADD CONSTRAINT `fares_fk_type_id` FOREIGN KEY (`type_id`) REFERENCES `transport_types` (`type_id`);

--
-- Constraints for table `festivals`
--
ALTER TABLE `festivals`
  ADD CONSTRAINT `festivals_ibfk_1` FOREIGN KEY (`town_id`) REFERENCES `towns` (`town_id`);

--
-- Constraints for table `itineraries`
--
ALTER TABLE `itineraries`
  ADD CONSTRAINT `fk_itin_creator_user` FOREIGN KEY (`creator_user_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_itin_destination_town` FOREIGN KEY (`destination_id`) REFERENCES `towns` (`town_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_itin_spot` FOREIGN KEY (`spot_id`) REFERENCES `tourist_spots` (`spot_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_itin_town` FOREIGN KEY (`town_id`) REFERENCES `towns` (`town_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `itinerary_bookings`
--
ALTER TABLE `itinerary_bookings`
  ADD CONSTRAINT `fk_bookings_itinerary` FOREIGN KEY (`itinerary_id`) REFERENCES `itineraries` (`itinerary_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `itinerary_faqs`
--
ALTER TABLE `itinerary_faqs`
  ADD CONSTRAINT `fk_faq_itinerary` FOREIGN KEY (`itinerary_id`) REFERENCES `itineraries` (`itinerary_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `itinerary_photos`
--
ALTER TABLE `itinerary_photos`
  ADD CONSTRAINT `fk_photos_itinerary` FOREIGN KEY (`itinerary_id`) REFERENCES `itineraries` (`itinerary_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_photos_user` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `itinerary_pricing_tiers`
--
ALTER TABLE `itinerary_pricing_tiers`
  ADD CONSTRAINT `fk_pricing_itinerary` FOREIGN KEY (`itinerary_id`) REFERENCES `itineraries` (`itinerary_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `location`
--
ALTER TABLE `location`
  ADD CONSTRAINT `location_ibfk_1` FOREIGN KEY (`town_id`) REFERENCES `towns` (`town_id`);

--
-- Constraints for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD CONSTRAINT `login_attempts_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `login_history`
--
ALTER TABLE `login_history`
  ADD CONSTRAINT `login_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `map_editor`
--
ALTER TABLE `map_editor`
  ADD CONSTRAINT `map_editor_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `location` (`location_id`);

--
-- Constraints for table `map_images`
--
ALTER TABLE `map_images`
  ADD CONSTRAINT `map_images_ibfk_1` FOREIGN KEY (`map_id`) REFERENCES `map_editor` (`map_id`);

--
-- Constraints for table `password_reset`
--
ALTER TABLE `password_reset`
  ADD CONSTRAINT `password_reset_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`spot_id`) REFERENCES `tourist_spots` (`spot_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `review_photos`
--
ALTER TABLE `review_photos`
  ADD CONSTRAINT `fk_review_photos` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`review_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `route_transport_types`
--
ALTER TABLE `route_transport_types`
  ADD CONSTRAINT `route_transport_types_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `transport_routes` (`route_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `route_transport_types_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `transport_types` (`type_id`) ON DELETE CASCADE;

--
-- Constraints for table `spot_images`
--
ALTER TABLE `spot_images`
  ADD CONSTRAINT `fk_spot_images_spot` FOREIGN KEY (`spot_id`) REFERENCES `tourist_spots` (`spot_id`) ON DELETE CASCADE;

--
-- Constraints for table `tourism_fee_log`
--
ALTER TABLE `tourism_fee_log`
  ADD CONSTRAINT `fk_fee_log_user` FOREIGN KEY (`recorded_by_user_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `tourism_fee_log_ibfk_1` FOREIGN KEY (`municipality_id`) REFERENCES `towns` (`town_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tourism_fee_log_ibfk_2` FOREIGN KEY (`spot_id`) REFERENCES `tourist_spots` (`spot_id`) ON DELETE CASCADE;

--
-- Constraints for table `tourism_office`
--
ALTER TABLE `tourism_office`
  ADD CONSTRAINT `tourism_office_ibfk_1` FOREIGN KEY (`town_id`) REFERENCES `towns` (`town_id`),
  ADD CONSTRAINT `tourism_office_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `tourism_office_content`
--
ALTER TABLE `tourism_office_content`
  ADD CONSTRAINT `tourism_office_content_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `tourism_office` (`office_id`) ON DELETE CASCADE;

--
-- Constraints for table `tourist_capacity`
--
ALTER TABLE `tourist_capacity`
  ADD CONSTRAINT `tourist_capacity_ibfk_1` FOREIGN KEY (`spot_id`) REFERENCES `tourist_spots` (`spot_id`) ON DELETE CASCADE;

--
-- Constraints for table `tourist_spots`
--
ALTER TABLE `tourist_spots`
  ADD CONSTRAINT `tourist_spots_ibfk_1` FOREIGN KEY (`town_id`) REFERENCES `towns` (`town_id`);

--
-- Constraints for table `tourist_spot_capacity`
--
ALTER TABLE `tourist_spot_capacity`
  ADD CONSTRAINT `tourist_spot_capacity_ibfk_1` FOREIGN KEY (`spot_id`) REFERENCES `tourist_spots` (`spot_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tourist_spot_capacity_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `transport_routes`
--
ALTER TABLE `transport_routes`
  ADD CONSTRAINT `transport_routes_ibfk_1` FOREIGN KEY (`from_terminal_id`) REFERENCES `terminal_locations` (`terminal_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transport_routes_ibfk_2` FOREIGN KEY (`to_terminal_id`) REFERENCES `terminal_locations` (`terminal_id`) ON DELETE SET NULL;

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`user_type_id`) REFERENCES `user_type` (`user_type_id`),
  ADD CONSTRAINT `user_ibfk_2` FOREIGN KEY (`user_status_id`) REFERENCES `user_status` (`user_status_id`),
  ADD CONSTRAINT `user_ibfk_3` FOREIGN KEY (`town_id`) REFERENCES `towns` (`town_id`);

--
-- Constraints for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profile`
--
ALTER TABLE `user_profile`
  ADD CONSTRAINT `user_profile_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `verification_tokens`
--
ALTER TABLE `verification_tokens`
  ADD CONSTRAINT `verification_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `visitors_tracking`
--
ALTER TABLE `visitors_tracking`
  ADD CONSTRAINT `visitors_tracking_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`),
  ADD CONSTRAINT `visitors_tracking_ibfk_2` FOREIGN KEY (`spot_id`) REFERENCES `tourist_spots` (`spot_id`),
  ADD CONSTRAINT `visitors_tracking_ibfk_3` FOREIGN KEY (`festival_id`) REFERENCES `festivals` (`festival_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
