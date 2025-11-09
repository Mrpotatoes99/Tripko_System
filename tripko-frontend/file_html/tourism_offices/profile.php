<?php
require_once('../../../tripko-backend/config/Database.php');
require_once('../../../tripko-backend/config/check_session.php');
checkTourismOfficerSession();

// Establish database connection
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Database connection failed");
}

// Get the tourism officer's info
$user_id = $_SESSION['user_id'];
$query = "SELECT u.*, t.name as town_name, t.town_id 
          FROM user u
          LEFT JOIN towns t ON u.town_id = t.town_id 
          WHERE u.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

$username = $user_data['username'];
$email = $user_data['email'];
$first_name = $user_data['first_name'] ?? '';
$last_name = $user_data['last_name'] ?? '';
$town_name = $user_data['town_name'] ?? 'Not Assigned';
$town_id = $user_data['town_id'];
$created_at = $user_data['created_at'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Tourism Officer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/tourism-dashboard.css" />
    <style>
        .profile-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .profile-header {
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 2rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 600;
            flex-shrink: 0;
        }

        .profile-info h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text);
        }

        .profile-meta {
            display: flex;
            gap: 1.5rem;
            margin-top: 0.75rem;
            flex-wrap: wrap;
        }

        .profile-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .profile-meta-item i {
            color: var(--text-muted);
        }

        .section-card {
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
            color: var(--text);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 0.9375rem;
            background: var(--bg-elevated);
            color: var(--text);
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .form-group input:disabled {
            background: var(--bg);
            color: var(--text-muted);
            cursor: not-allowed;
        }

        .btn {
            padding: 0.75rem 1.25rem;
            border-radius: var(--radius);
            font-weight: 500;
            font-size: 0.9375rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-danger {
            background: var(--error);
            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-secondary {
            background: var(--bg);
            border: 1px solid var(--border);
            color: var(--text);
        }

        .btn-secondary:hover {
            background: var(--bg-elevated);
            border-color: var(--border-dark);
        }

        .alert {
            padding: 0.875rem 1rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            font-size: 0.9375rem;
            display: none;
        }

        .alert.show {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: #f0fdf4;
            color: var(--success);
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fef2f2;
            color: var(--error);
            border: 1px solid #fecaca;
        }

        .info-grid {
            display: grid;
            gap: 1rem;
        }

        .info-item {
            display: flex;
            padding: 0.875rem;
            background: var(--bg);
            border-radius: var(--radius);
        }

        .info-label {
            font-weight: 500;
            color: var(--text-secondary);
            min-width: 140px;
            font-size: 0.875rem;
        }

        .info-value {
            color: var(--text);
            font-size: 0.9375rem;
        }

        .password-strength {
            height: 4px;
            background: var(--border);
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }

        .password-strength-bar.weak { width: 33%; background: var(--error); }
        .password-strength-bar.medium { width: 66%; background: var(--warn); }
        .password-strength-bar.strong { width: 100%; background: var(--success); }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: var(--bg-elevated);
            border-radius: var(--radius-lg);
            padding: 2rem;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius);
        }

        .modal-close:hover {
            background: var(--bg);
            color: var(--text);
        }

        @media (max-width: 640px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-meta {
                justify-content: center;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="tourism-nav">
        <div class="nav-brand">
            <h1>TripKo Tourism</h1>
            <span class="nav-location">| <?php echo htmlspecialchars($town_name); ?></span>
        </div>
        <div class="nav-actions">
            <button id="darkModeToggle" class="dark-mode-toggle" aria-label="Toggle dark mode" title="Toggle theme">
                <i class="bx bxs-moon"></i>
            </button>
            <div class="user-menu">
                <button class="user-menu-button">
                    <i class="fas fa-user-circle"></i>
                    <span>Account</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="user-menu-dropdown">
                    <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="/tripko-system/tripko-backend/config/confirm_logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside class="tourism-sidebar">
        <nav class="sidebar-nav">
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/dashboard.php" class="sidebar-link">
                <i class="fas fa-home"></i>Dashboard
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourist_spots.php" class="sidebar-link">
                <i class="fas fa-umbrella-beach"></i>Tourist Spots
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/festivals.php" class="sidebar-link">
                <i class="fas fa-calendar-alt"></i>Festivals
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/itineraries.php" class="sidebar-link">
                <i class="fas fa-map-marked-alt"></i>Itineraries
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/update_capacity.php" class="sidebar-link">
                <i class="fas fa-users-cog"></i>Update Capacity
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourism_fee_log.php" class="sidebar-link">
                <i class="fas fa-cash-register"></i>Tourism Fee Log
            </a>
            <a href="/tripko-system/tripko-frontend/file_html/tourism_offices/tourism_fee_log_list.php" class="sidebar-link">
                <i class="fas fa-file-alt"></i>Fee Log Report
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="tourism-main">
        <div class="profile-container">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($first_name ?: $username, 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($first_name . ' ' . $last_name) ?: htmlspecialchars($username); ?></h1>
                    <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Tourism Officer</p>
                    <div class="profile-meta">
                        <div class="profile-meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($town_name); ?></span>
                        </div>
                        <div class="profile-meta-item">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($email); ?></span>
                        </div>
                        <div class="profile-meta-item">
                            <i class="fas fa-calendar"></i>
                            <span>Joined <?php echo date('F Y', strtotime($created_at)); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Information -->
            <div class="section-card">
                <h2 class="section-title">Account Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Username:</span>
                        <span class="info-value"><?php echo htmlspecialchars($username); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($email); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Municipality:</span>
                        <span class="info-value"><?php echo htmlspecialchars($town_name); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Account Type:</span>
                        <span class="info-value">Tourism Officer</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Account Created:</span>
                        <span class="info-value"><?php echo date('F d, Y', strtotime($created_at)); ?></span>
                    </div>
                </div>
            </div>

            <!-- Update Profile -->
            <div class="section-card">
                <h2 class="section-title">Update Profile</h2>
                <div id="profileAlert" class="alert"></div>
                <form id="updateProfileForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="firstName">First Name</label>
                            <input type="text" id="firstName" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="lastName">Last Name</label>
                            <input type="text" id="lastName" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>

            <!-- Security Settings -->
            <div class="section-card">
                <h2 class="section-title">Security Settings</h2>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button class="btn btn-secondary" onclick="openChangePasswordModal()">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                    <button class="btn btn-secondary" onclick="viewLoginActivity()">
                        <i class="fas fa-history"></i> Login Activity
                    </button>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="section-card" style="border-color: var(--error);">
                <h2 class="section-title" style="color: var(--error);">Danger Zone</h2>
                <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.9375rem;">
                    Deactivating your account will temporarily disable access. Contact the system administrator to reactivate.
                </p>
                <button class="btn btn-danger" onclick="confirmDeactivate()">
                    <i class="fas fa-exclamation-triangle"></i> Deactivate Account
                </button>
            </div>
        </div>
    </main>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Change Password</h3>
                <button class="modal-close" onclick="closeChangePasswordModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="passwordAlert" class="alert"></div>
            <form id="changePasswordForm">
                <div class="form-group">
                    <label for="currentPassword">Current Password</label>
                    <input type="password" id="currentPassword" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" name="new_password" required>
                    <div class="password-strength">
                        <div id="passwordStrengthBar" class="password-strength-bar"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm New Password</label>
                    <input type="password" id="confirmPassword" name="confirm_password" required>
                </div>
                <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Update Password
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeChangePasswordModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Dark Mode Toggle
        function initDarkMode() {
            const toggle = document.getElementById('darkModeToggle');
            const savedTheme = localStorage.getItem('tripko-tourism-theme');
            
            if (savedTheme === 'dark') {
                document.body.classList.add('dark');
                toggle.innerHTML = '<i class="bx bxs-sun"></i>';
            }
            
            toggle.addEventListener('click', () => {
                document.body.classList.toggle('dark');
                const isDark = document.body.classList.contains('dark');
                toggle.innerHTML = isDark ? '<i class="bx bxs-sun"></i>' : '<i class="bx bxs-moon"></i>';
                localStorage.setItem('tripko-tourism-theme', isDark ? 'dark' : 'light');
            });
        }

        // Update Profile Form
        document.getElementById('updateProfileForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const alert = document.getElementById('profileAlert');

            try {
                const response = await fetch('/tripko-system/tripko-backend/api/tourism_officers/update_profile.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const data = await response.json();
                
                alert.className = 'alert show ' + (data.success ? 'alert-success' : 'alert-error');
                alert.innerHTML = `<i class="fas fa-${data.success ? 'check-circle' : 'exclamation-circle'}"></i> ${data.message}`;
                
                if (data.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            } catch (error) {
                alert.className = 'alert show alert-error';
                alert.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error updating profile';
            }
        });

        // Change Password Modal
        function openChangePasswordModal() {
            document.getElementById('changePasswordModal').classList.add('show');
            document.getElementById('changePasswordForm').reset();
            document.getElementById('passwordAlert').classList.remove('show');
        }

        function closeChangePasswordModal() {
            document.getElementById('changePasswordModal').classList.remove('show');
        }

        // Password Strength Indicator
        document.getElementById('newPassword').addEventListener('input', (e) => {
            const password = e.target.value;
            const strengthBar = document.getElementById('passwordStrengthBar');
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength-bar';
            if (strength >= 3) strengthBar.classList.add('strong');
            else if (strength >= 2) strengthBar.classList.add('medium');
            else if (strength >= 1) strengthBar.classList.add('weak');
        });

        // Change Password Form
        document.getElementById('changePasswordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const alert = document.getElementById('passwordAlert');

            // Check if passwords match
            if (formData.get('new_password') !== formData.get('confirm_password')) {
                alert.className = 'alert show alert-error';
                alert.innerHTML = '<i class="fas fa-exclamation-circle"></i> Passwords do not match';
                return;
            }

            try {
                const response = await fetch('/tripko-system/tripko-backend/api/tourism_officers/change_password.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const data = await response.json();
                
                alert.className = 'alert show ' + (data.success ? 'alert-success' : 'alert-error');
                alert.innerHTML = `<i class="fas fa-${data.success ? 'check-circle' : 'exclamation-circle'}"></i> ${data.message}`;
                
                if (data.success) {
                    setTimeout(() => closeChangePasswordModal(), 1500);
                    e.target.reset();
                }
            } catch (error) {
                alert.className = 'alert show alert-error';
                alert.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error changing password';
            }
        });

        // Login Activity
        function viewLoginActivity() {
            alert('Login activity tracking coming soon!');
        }

        // Deactivate Account
        function confirmDeactivate() {
            if (confirm('Are you sure you want to deactivate your account? You will need to contact an administrator to reactivate it.')) {
                alert('Account deactivation feature coming soon. Please contact your system administrator.');
            }
        }

        // Close modal on outside click
        document.getElementById('changePasswordModal').addEventListener('click', (e) => {
            if (e.target.id === 'changePasswordModal') {
                closeChangePasswordModal();
            }
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', initDarkMode);
    </script>
</body>
</html>
