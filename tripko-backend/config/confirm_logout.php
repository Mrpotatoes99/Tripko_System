<?php
session_start();

// If form was submitted:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        // Destroy session and redirect to login
        $_SESSION = [];
        session_destroy();
        header('Location: ../../tripko-frontend/file_html/SignUp_LogIn_Form.php');
        exit;
    } else {
        // User chose "No" -> redirect back to dashboard
        header('Location: ../../tripko-frontend/file_html/dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Logout - TripKo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
</head>
<body class="bg-[#F3F1E7] min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full mx-4">
        <div class="text-center mb-6">
            <i class="fas fa-sign-out-alt text-[#255D8A] text-4xl mb-4"></i>
            <h2 class="text-2xl font-semibold text-gray-800">Sign Out?</h2>
            <p class="text-gray-600 mt-2">Are you sure you want to sign out from TripKo?</p>
        </div>
        
        <form method="post" class="flex gap-4 justify-center">
            <button type="submit" name="confirm" value="yes" 
                class="px-6 py-2 bg-[#255D8A] text-white rounded-lg hover:bg-[#1e4d75] transition-colors duration-200">
                Yes, Sign Out
            </button>
            <button type="submit" name="confirm" value="no" 
                class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors duration-200">
                Cancel
            </button>
        </form>
    </div>
</body>
</html>
