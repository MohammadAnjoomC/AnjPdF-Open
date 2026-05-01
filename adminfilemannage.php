<?php
session_start();

// Define the Terms and Admin Login pages for the footer
$terms_page = "terms_and_conditions.php";
$admin_login_page = "login.php";


// Check if admin is logged in, otherwise redirect to login page
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Define directories
$public_dir = './pdf/';
$quarantine_dir = './quarantine/';

// Ensure directories exist
if (!is_dir($public_dir)) {
    mkdir($public_dir, 0755, true);
}
if (!is_dir($quarantine_dir)) {
    mkdir($quarantine_dir, 0755, true);
}

$message = '';

// Handle actions (Quarantine, Publish, or Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['filename'])) {
    $action = $_POST['action'];
    $filename = basename($_POST['filename']); // Sanitize filename
    
    $success = false;
    $source_path = '';

    // --- MOVE/DELETE LOGIC ---
    if ($action === 'quarantine') {
        $source = $public_dir . $filename;
        $destination = $quarantine_dir . $filename;
        if (is_file($source) && rename($source, $destination)) {
            $message = "🛇 File **{$filename}** moved to Quarantine (Private).";
            $success = true;
        }
    } elseif ($action === 'public') {
        $source = $quarantine_dir . $filename;
        $destination = $public_dir . $filename;
        if (is_file($source) && rename($source, $destination)) {
            $message = "✅ File **{$filename}** moved to Public (/pdf).";
            $success = true;
        }
    } elseif ($action === 'delete') {
        // Determine if file is in public or quarantine and set source path
        if (is_file($public_dir . $filename)) {
            $source_path = $public_dir . $filename;
        } elseif (is_file($quarantine_dir . $filename)) {
            $source_path = $quarantine_dir . $filename;
        }

        if (is_file($source_path) && unlink($source_path)) {
            $message = "❌ File **{$filename}** has been permanently deleted.";
            $success = true;
        }
    }

    // Handle errors for move/delete actions
    if (!$success) {
        $message = "🚫 Error performing action: **{$action}** on **{$filename}**. File may not exist or permissions may be restricted.";
    }
}


// --- Functions to get file lists ---

function get_pdf_files($directory) {
    if (!is_dir($directory)) {
        return [];
    }
    $files = scandir($directory);
    return array_filter($files, function($file) use ($directory) {
        return is_file($directory . $file) && preg_match('/\.pdf$/i', $file);
    });
}

$public_files = get_pdf_files($public_dir);
$quarantine_files = get_pdf_files($quarantine_dir);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AnjPdF - Admin File Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="/img/logo.png">
    
    <style>
        /* ----------------------- THEME VARIABLES (Copied from index.php) ----------------------- */
        :root {
            /* Light Theme Defaults */
            --primary-color: #176B87; /* Deep Blue/Teal */
            --accent-color: #64CCC5; /* Lighter Teal/Cyan */
            --background-color: #F8F9FA; /* Very light grey */
            --card-background: #FFFFFF; /* Pure white card */
            --text-color: #212529; /* Dark text */
            --light-text: #6C757D; /* Grey metadata/url text */
            --hover-color: #0A4D68;
            --upload-color: #28A745; 
            --danger-color: #DC3545;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --card-hover-bg: #E9ECEF;
            --input-border: #DEE2E6;
        }

        /* Dark Theme Overrides */
        body.dark-theme {
            --primary-color: #64CCC5; /* Accent color becomes primary */
            --accent-color: #176B87;
            --background-color: #1A1A2E; /* Deep navy/purple-ish dark */
            --card-background: #232946; /* Slightly lighter card background */
            --text-color: #E9ECEF; /* Light text */
            --light-text: #A9B1D6; /* Lighter grey for metadata */
            --hover-color: #1A5E7F;
            --upload-color: #4CAF50; /* Adjust green for dark contrast */
            --danger-color: #C82333; /* Darker red for contrast */
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            --card-hover-bg: #2B3352;
            --input-border: #3A4750;
        }

        /* ----------------------- BASE STYLES (Copied from index.php) ----------------------- */
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            transition: background-color 0.4s, color 0.4s;
        }
        
        /* Loader styles (minimal copy) */
        #loader-wrapper {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: var(--card-background); z-index: 1000;
            display: flex; justify-content: center; align-items: center;
            transition: opacity 0.5s, visibility 0s 0.5s, background-color 0.4s;
            visibility: visible; opacity: 1;
        }
        .loaded #loader-wrapper { visibility: hidden; opacity: 0; }
        #loader { width: 80px; height: 80px; position: relative; }
        #loader img { width: 100%; height: 100%; object-fit: contain; animation: pulse 1.5s infinite ease-in-out; }
        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 0.7; }
            50% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.7; }
        }

        .main-content {
            min-height: calc(100vh - 100px); 
            padding-bottom: 40px;
        }

        .container {
            max-width: 1200px; /* Wider for admin */
            margin: 40px auto;
            padding: 40px; 
            background: var(--card-background);
            border-radius: 20px; 
            box-shadow: var(--box-shadow);
            transition: box-shadow 0.4s, background-color 0.4s;
            visibility: hidden; 
        }
        
        .loaded .container {
            visibility: visible;
        }

        /* ----------------------- ADMIN HEADER & CONTROLS ----------------------- */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 20px;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--input-border);
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-to-admin-btn {
            background-color: var(--primary-color); 
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1em;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s;
            white-space: nowrap; 
            box-shadow: 0 4px 8px rgba(23, 107, 135, 0.2);
        }
        
        .back-to-admin-btn:hover {
            background-color: var(--hover-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(23, 107, 135, 0.4);
        }

        /* Theme Switch Toggle (Copied from index.php) */
        .theme-switch-wrapper { display: flex; align-items: center; }
        .theme-switch { display: inline-block; height: 24px; position: relative; width: 50px; }
        .theme-switch input { display: none; }
        .slider { background-color: var(--input-border); bottom: 0; cursor: pointer; left: 0; position: absolute; right: 0; top: 0; transition: 0.4s; border-radius: 34px; }
        .slider:before { content: '☀️'; height: 18px; width: 18px; position: absolute; left: 3px; bottom: 3px; background-color: white; transition: 0.4s; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 12px; line-height: 1; }
        input:checked + .slider { background-color: var(--primary-color); }
        input:checked + .slider:before { transform: translateX(26px); content: '🌙'; background-color: #212529; color: var(--primary-color); }
        /* END Theme Switch Toggle */

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

        /* --- ADMIN SPECIFIC LAYOUT STYLES --- */
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }

        .file-box {
            background: var(--card-hover-bg); /* Use slight contrast for box background */
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--input-border);
            height: fit-content;
        }

        .file-box h3 {
            font-size: 1.4em;
            color: var(--primary-color);
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 5px;
            margin-top: 0;
            margin-bottom: 20px;
        }

        .file-list {
            list-style: none;
            padding: 0;
        }

        .file-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px dashed var(--input-border);
            flex-wrap: wrap;
            font-size: 0.95em;
            transition: background-color 0.2s;
        }
        
        .file-list li:hover {
            background-color: var(--input-border);
            margin: 0 -10px;
            padding: 12px 10px;
            border-radius: 5px;
        }

        .file-list li:last-child {
            border-bottom: none;
        }

        .filename {
            font-weight: 500;
            color: var(--text-color);
            flex-grow: 1;
            min-width: 50%;
        }

        .actions {
            display: flex;
            gap: 8px;
            margin-top: 5px; 
        }

        /* Action Buttons using theme colors */
        .action-form { display: inline-block; }

        .action-form button {
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.8em;
            transition: background-color 0.3s, transform 0.1s;
            white-space: nowrap;
        }

        .btn-publish {
            background-color: var(--upload-color); /* Green for success */
        }
        .btn-publish:hover {
            background-color: #1e7e34;
        }

        .btn-quarantine {
            background-color: #E67E22; /* Orange for warning */
        }
        .btn-quarantine:hover {
            background-color: #D35400;
        }
        
        .btn-delete {
            background-color: var(--danger-color); /* Red for danger */
        }
        .btn-delete:hover {
            background-color: #C82333;
        }

        /* Message Styling adaptation */
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
            border-left: 5px solid;
            white-space: pre-wrap;
        }
        .message-success { background-color: #D4EDDA !important; color: #155724 !important; border-color: var(--upload-color) !important; }
        .message-error { background-color: #F8D7DA !important; color: #721C24 !important; border-color: var(--danger-color) !important; }
        .message-warning { background-color: #FFF3CD !important; color: #856404 !important; border-color: #E67E22 !important; }
        
        body.dark-theme {
            .message-success { background-color: #0A3622 !important; color: #72B095 !important; border-color: var(--upload-color) !important; }
            .message-error { background-color: #58151C !important; color: #FFB3BB !important; border-color: var(--danger-color) !important; }
            .message-warning { background-color: #5C4505 !important; color: #FFDA89 !important; border-color: #E67E22 !important; }
        }

        /* Footer styles (Copied from index.php) */
        .footer { background-color: var(--card-background); padding: 25px 5%; margin-top: 50px; border-top: 1px solid var(--input-border); transition: background-color 0.4s; }
        .footer-content { max-width: 1080px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; font-size: 0.9em; color: var(--light-text); }
        .footer-logo-icon img { height: 40px; width: auto; object-fit: contain; }
        .footer-link-group { display: flex; gap: 20px; }
        .footer-link a, .footer-admin-link a { color: var(--primary-color); text-decoration: none; font-weight: 600; transition: color 0.3s; }
        .footer-admin-link a { font-size: 1em; color: var(--light-text); }
        .footer-link a:hover { color: var(--accent-color); text-decoration: underline; }
        .footer-admin-link a:hover { color: var(--primary-color); text-decoration: underline; }

        /* Responsive adjustments for Admin */
        @media (max-width: 768px) {
             .container { margin: 20px 15px; padding: 20px; }
             .header { flex-direction: column; align-items: flex-start; gap: 15px; }
             .admin-grid { grid-template-columns: 1fr; }
             .actions { margin-top: 10px; justify-content: space-between; width: 100%; gap: 5px; }
             .action-form button { flex-grow: 1; }
        }

    </style>
</head>
<body class="light-theme">

    <div id="loader-wrapper">
        <div id="loader">
            <img src="img/logo.png" alt="AnjPdF Loading Logo">
        </div>
    </div>
    
    <div class="main-content">
        <div class="container">
            <div class="header">
                <h2>Admin File Manager</h2>
                <div class="header-controls">
                    <div class="theme-switch-wrapper">
                        <label class="theme-switch" for="checkbox">
                            <input type="checkbox" id="checkbox" />
                            <div class="slider round"></div>
                        </label>
                    </div>
                    <a href="admin.php" class="back-to-admin-btn">⬅️ Admin Panel</a>
                </div>
            </div>

            <?php 
            // Display messages using the theme's message classes
            if (!empty($message)): 
                $class = 'message-warning';
                if (strpos($message, '✅') !== false) {
                    $class = 'message-success';
                } elseif (strpos($message, '🚫') !== false || strpos($message, '❌') !== false) {
                    $class = 'message-error';
                } elseif (strpos($message, '🛇') !== false) {
                    $class = 'message-warning';
                }
            ?>
                <p class="message <?php echo $class; ?>">
                    <?php echo str_replace('**', '<b>', str_replace('**', '</b>', $message)); ?>
                </p>
            <?php endif; ?>

            <div class="admin-grid">
                
                <div class="file-box">
                    <h3>🌐 Public Files (in /pdf/) - <?php echo count($public_files); ?></h3>
                    <?php if (empty($public_files)): ?>
                        <p>No public PDF files.</p>
                    <?php else: ?>
                        <ul class="file-list">
                            <?php foreach ($public_files as $file): ?>
                                <li>
                                    <span class="filename"><?php echo htmlspecialchars($file); ?></span>
                                    <div class="actions">
                                        <form method="post" class="action-form">
                                            <input type="hidden" name="action" value="quarantine">
                                            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($file); ?>">
                                            <button type="submit" class="btn-quarantine" title="Move to Private Folder">🛇 Quarantine</button>
                                        </form>
                                        <form method="post" class="action-form" onsubmit="return confirm('Are you sure you want to PERMANENTLY delete this file?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($file); ?>">
                                            <button type="submit" class="btn-delete" title="Permanently Delete">❌ Delete</button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="file-box">
                    <h3>🚨 Quarantine Files (in /quarantine/) - <?php echo count($quarantine_files); ?></h3>
                    <p style="color: var(--light-text); margin-top: -10px; margin-bottom: 20px; font-size: 0.95em;">Private files removed from public view.</p>
                    <?php if (empty($quarantine_files)): ?>
                        <p>No files in quarantine.</p>
                    <?php else: ?>
                        <ul class="file-list">
                            <?php foreach ($quarantine_files as $file): ?>
                                <li>
                                    <span class="filename"><?php echo htmlspecialchars($file); ?></span>
                                    <div class="actions">
                                        <form method="post" class="action-form">
                                            <input type="hidden" name="action" value="public">
                                            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($file); ?>">
                                            <button type="submit" class="btn-publish" title="Move back to Public /pdf/">✅ Publish</button>
                                        </form>
                                        <form method="post" class="action-form" onsubmit="return confirm('Are you sure you want to PERMANENTLY delete this file?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($file); ?>">
                                            <button type="submit" class="btn-delete" title="Permanently Delete">❌ Delete</button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

            </div>
            
            <hr style="border: 0; height: 1px; background: var(--input-border); margin-top: 40px; margin-bottom: 0;">

        </div>
    </div>
    
    <div class="footer">
        <div class="footer-content">
            <div class="footer-logo-icon">
                <img src="/img/logo.png" alt="PDF Host Icon">
            </div>
            <p>&copy; <?php echo date("Y"); ?> AnjPdF - Host</p>
            
            <div class="footer-link-group">
                <p class="footer-link"><a href="<?php echo $terms_page; ?>">Terms & Conditions</a></p>
                <p class="footer-admin-link"><a href="<?php echo $admin_login_page; ?>">🔑 Admin Panel Login</a></p>
            </div>
        </div>
    </div>

    <script>
        // Set the duration for the animation to 0.8 seconds
        const LOADER_DURATION_MS = 800; 
        const LOADER_FADE_OUT_DELAY = 100;

        // 1. Theme Toggle Logic
        const themeCheckbox = document.getElementById('checkbox');
        const body = document.body;

        function applyTheme(isDark) {
            if (isDark) {
                body.classList.add('dark-theme');
                body.classList.remove('light-theme');
                localStorage.setItem('theme', 'dark');
            } else {
                body.classList.add('light-theme');
                body.classList.remove('dark-theme');
                localStorage.setItem('theme', 'light');
            }
        }

        // Check for saved theme preference on load
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            themeCheckbox.checked = true;
            applyTheme(true);
        } else {
            applyTheme(false);
        }

        // Add event listener for theme change
        themeCheckbox.addEventListener('change', (event) => {
            applyTheme(event.target.checked);
        });

        // 2. Loader Logic
        window.addEventListener('load', () => {
            // Ensure the loader stays visible for a minimum duration
            setTimeout(() => {
                document.body.classList.add('loaded');
            }, LOADER_DURATION_MS + LOADER_FADE_OUT_DELAY);
        });
    </script>

</body>
</html>