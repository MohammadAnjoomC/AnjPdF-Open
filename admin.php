<?php
    echo '<link rel="icon" type="image/png" href="/img/logo.png">';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Define storage directory for admin profile data
$admin_data_dir = './img/adm/';
$profile_data_file = $admin_data_dir . 'profile.json';
$message_status = '';

// --- Initialize/Load Admin Data ---
if (!is_dir($admin_data_dir)) {
    mkdir($admin_data_dir, 0755, true);
}

$admin_profile = [
    'name' => 'Default Admin',
    'image' => 'default_admin.png' // A default image name
];

if (file_exists($profile_data_file)) {
    $loaded_data = json_decode(file_get_contents($profile_data_file), true);
    if (is_array($loaded_data)) {
        $admin_profile = array_merge($admin_profile, $loaded_data);
    }
}

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Handle Name Change
    if (isset($_POST['new_name'])) {
        $new_name = trim($_POST['new_name']);
        if (!empty($new_name)) {
            $admin_profile['name'] = htmlspecialchars($new_name);
            $message_status .= '<p class="success-message">👤 Profile name updated successfully.</p>';
        } else {
            $message_status .= '<p class="error-message">🚫 Profile name cannot be empty.</p>';
        }
    }

    // 2. Handle Image Upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['profile_image']['tmp_name'];
        $file_name = $_FILES['profile_image']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file_ext, $allowed_ext)) {
            $message_status .= '<p class="error-message">🚫 Invalid file type. Only JPG, PNG, and GIF are allowed.</p>';
        } else if ($_FILES['profile_image']['size'] > $max_size) {
            $message_status .= '<p class="error-message">🚫 File size exceeds 5MB limit.</p>';
        } else {
            // New unique file name (using time to ensure uniqueness and prevent caching)
            $new_image_name = 'admin_profile_' . time() . '.' . $file_ext;
            $destination = $admin_data_dir . $new_image_name;

            if (move_uploaded_file($file_tmp, $destination)) {
                // Delete old custom image if it wasn't the default one
                if ($admin_profile['image'] !== 'default_admin.png' && file_exists($admin_data_dir . $admin_profile['image'])) {
                    unlink($admin_data_dir . $admin_profile['image']);
                }
                
                $admin_profile['image'] = $new_image_name;
                $message_status .= '<p class="success-message">🖼️ Profile image updated successfully.</p>';
            } else {
                $message_status .= '<p class="error-message">🚫 Error uploading file.</p>';
            }
        }
    }

    // Save updated profile data
    file_put_contents($profile_data_file, json_encode($admin_profile, JSON_PRETTY_PRINT), LOCK_EX);

    // Redirect to prevent form resubmission (Post/Redirect/Get pattern)
    if (strpos($message_status, '🚫') === false) { // Only redirect on success
        header('Location: admin.php?status=updated');
        exit;
    }
}

// Check for successful update via GET parameter
if (isset($_GET['status']) && $_GET['status'] === 'updated') {
     $message_status .= '<p class="success-message">💾 Settings saved successfully.</p>';
}

// Current profile image URL
$profile_image_url = $admin_data_dir . $admin_profile['image'];
if (!file_exists($profile_image_url) || $admin_profile['image'] === 'default_admin.png') {
    // Fallback to a conceptual default image URL if necessary
    $profile_image_url = 'img/logo.png'; // Using the main logo as a default placeholder
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AnjPdF - Admin Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <style>
        /* ----------------------- THEME VARIABLES ----------------------- */
        :root {
            /* Light Theme Defaults */
            --primary-color: #176B87; 
            --accent-color: #64CCC5; 
            --background-color: #F8F9FA; 
            --card-background: #FFFFFF; 
            --text-color: #212529; 
            --light-text: #6C757D; 
            --hover-color: #0A4D68;
            --upload-color: #28A745; 
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --card-hover-bg: #E9ECEF;
            --input-border: #DEE2E6;
        }

        /* Dark Theme Overrides */
        body.dark-theme {
            --primary-color: #64CCC5; 
            --accent-color: #176B87;
            --background-color: #1A1A2E; 
            --card-background: #232946; 
            --text-color: #E9ECEF; 
            --light-text: #A9B1D6; 
            --hover-color: #1A5E7F;
            --upload-color: #4CAF50; 
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            --card-hover-bg: #2B3352;
            --input-border: #3A4750;
        }

        /* ----------------------- BASE STYLES & LAYOUT ----------------------- */
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            transition: background-color 0.4s, color 0.4s;
            min-height: 100vh;
        }
        
        .main-content {
            padding: 40px 0;
            min-height: calc(100vh - 100px); 
        }

        .container {
            max-width: 900px; 
            margin: 0 auto;
            padding: 40px; 
            background: var(--card-background);
            border-radius: 20px; 
            box-shadow: var(--box-shadow);
            transition: box-shadow 0.4s, background-color 0.4s;
        }

        /* ----------------------- ADMIN HEADER ----------------------- */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 30px;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--input-border);
        }

        .admin-header h1 {
            margin: 0;
            font-size: 2.2em; 
            color: var(--primary-color);
            font-weight: 900; 
        }
        
        .admin-nav {
            display: flex;
            gap: 15px;
        }
        
        .admin-nav a {
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s, color 0.3s;
            white-space: nowrap;
        }

        .nav-link-default {
            background-color: var(--card-hover-bg);
            color: var(--text-color);
        }
        
        .nav-link-default:hover {
            background-color: var(--input-border);
        }

        .nav-link-active {
            background-color: var(--accent-color);
            color: var(--text-color);
        }
        
        .nav-link-logout {
            background-color: #DC3545;
            color: white;
        }
        
        .nav-link-logout:hover {
            background-color: #C82333;
        }

        h2 {
            color: var(--primary-color);
            margin-top: 20px;
            font-size: 1.6em;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid var(--accent-color);
            display: inline-block;
            padding-bottom: 5px;
            margin-bottom: 25px;
        }
        
        /* Message Styles */
        .error-message {
            color: #DC3545;
            background-color: #F8D7DA;
            border: 1px solid #F5C6CB;
            padding: 10px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 20px;
        }

        .success-message {
            color: #155724;
            background-color: #D4EDDA;
            border: 1px solid #C3E6CB;
            padding: 10px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 20px;
        }
        
        /* ----------------------- PROFILE CONTENT ----------------------- */
        .profile-card {
            display: flex;
            align-items: center;
            padding: 20px;
            background-color: var(--card-hover-bg);
            border-radius: 12px;
            margin-bottom: 40px;
            border: 1px solid var(--input-border);
        }
        
        .profile-image-container {
            width: 100px;
            height: 100px;
            margin-right: 25px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--primary-color);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }
        
        .profile-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-info h3 {
            margin: 0;
            font-size: 1.8em;
            color: var(--primary-color);
        }
        
        .profile-info p {
            margin: 5px 0 0 0;
            color: var(--light-text);
            font-weight: 500;
        }
        
        /* Form Sections */
        .form-section {
            padding: 25px;
            border: 1px solid var(--input-border);
            border-radius: 12px;
            margin-bottom: 30px;
            background-color: var(--card-hover-bg);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
        }

        .form-group input[type="text"],
        .form-group input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--input-border);
            border-radius: 8px;
            box-sizing: border-box;
            background-color: var(--card-background);
            color: var(--text-color);
        }
        
        .form-group input[type="file"] {
            padding: 8px 12px; /* Adjust padding for file input */
        }

        .submit-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: var(--upload-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 700;
            transition: background-color 0.3s, transform 0.2s;
            margin-top: 10px;
        }

        .submit-btn:hover {
            background-color: #1e7e34;
            transform: translateY(-2px);
        }
        
        /* ----------------------- FOOTER STYLES (Minimal) ----------------------- */
        .footer {
            background-color: var(--card-background);
            padding: 25px 5%;
            margin-top: auto; 
            border-top: 1px solid var(--input-border);
        }
        
        .footer-content {
            max-width: 1080px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9em;
            color: var(--light-text);
        }
        
        /* ----------------------- RESPONSIVE DESIGN ----------------------- */
        @media (max-width: 768px) {
            .container {
                margin: 0 15px;
                padding: 20px;
            }
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .admin-nav {
                margin-top: 15px;
                flex-wrap: wrap;
            }
            .profile-card {
                flex-direction: column;
                text-align: center;
            }
            .profile-image-container {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body class="light-theme">

    <div class="main-content">
        <div class="container">
            <div class="admin-header">
                <h1>🔑 Admin Panel</h1>
                <div class="admin-nav">
                    <a href="admin.php" class="nav-link-active">👤 Profile</a>
                    <a href="adminfilemannage.php" class="nav-link-default">📁 Files</a>
                    <a href="admincontact.php" class="nav-link-default">📢 Submissions</a>
                    <a href="logout.php" class="nav-link-logout">🚪 Logout</a>
                </div>
            </div>

            <?php echo $message_status; ?>

            <h2>⭐ Premium Profile</h2>
            <div class="profile-card">
                <div class="profile-image-container">
                    <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Admin Profile Image">
                </div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($admin_profile['name']); ?></h3>
                    <p>Admin Access Level</p>
                </div>
            </div>

            <div class="form-section">
                <h2>✍️ Change Profile Name</h2>
                <form action="admin.php" method="post">
                    <div class="form-group">
                        <label for="new_name">New Premium Profile Name:</label>
                        <input type="text" id="new_name" name="new_name" value="<?php echo htmlspecialchars($admin_profile['name']); ?>" required>
                    </div>
                    <button type="submit" class="submit-btn" style="background-color: var(--primary-color);">Save Name</button>
                </form>
            </div>
            
            <div class="form-section">
                <h2>🖼️ Upload Profile Image</h2>
                <p style="margin-top: -15px; margin-bottom: 20px; color: var(--light-text);">Max 5MB. Accepts JPG, PNG, GIF.</p>
                <form action="admin.php" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="profile_image">Select New Image:</label>
                        <input type="file" id="profile_image" name="profile_image" accept="image/*" required>
                    </div>
                    <button type="submit" class="submit-btn">Upload & Save Image</button>
                </form>
            </div>

        </div>
    </div>

    <div class="footer">
        <div class="footer-content">
            <p>&copy; <?php echo date("Y"); ?> AnjPdF - Admin</p>
            <div class="footer-link-group">
                <p>Logged in as: <?php echo htmlspecialchars($admin_profile['name']); ?></p>
            </div>
        </div>
    </div>
    <script>
        // Simple client-side theme application from local storage
        const body = document.body;
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            body.classList.add('dark-theme');
            body.classList.remove('light-theme');
        } else {
            body.classList.add('light-theme');
            body.classList.remove('dark-theme');
        }
    </script>
</body>
</html>