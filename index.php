<?php
    echo '<link rel="icon" type="image/png" href="/img/logo.png">';
// Set the directory to scan for PDF files.
// The destination for public files is now the '/pdf' directory.
$directory = './pdf/';

// Ensure the directory exists
if (!is_dir($directory)) {
    // Attempt to create the directory if it doesn't exist
    mkdir($directory, 0755, true);
}

// Function to format file size
function format_bytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    // Calculate the size
    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}


// Scan the directory for files
$files = scandir($directory);

// Filter the list to include only PDF files and gather metadata
$pdf_files_data = [];
foreach ($files as $file) {
    $full_path = $directory . $file;
    // Check if the file ends with .pdf (case-insensitive) and is a regular file (not a directory)
    if (is_file($full_path) && preg_match('/\.pdf$/i', $file)) {
        $pdf_files_data[] = [
            'name' => $file,
            'size' => filesize($full_path),
            'modified_time' => filemtime($full_path),
        ];
    }
}

// --- LOGIC: Sort by modified time (newest first) but DO NOT limit the PHP array ---
// This ensures ALL files are passed to the HTML/JavaScript for searching.
// The display limit of 9 is enforced by JavaScript below.
// Sort the array by 'modified_time' in descending order
usort($pdf_files_data, function($a, $b) {
    return $b['modified_time'] - $a['modified_time'];
});
// --------------------------------------------------------------------------


// Helper function to build the full URL
function get_full_url($filename) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    // Assuming index.php is in the root, and files are in the public /pdf/ folder.
    $file_url_path = '/pdf/' . rawurlencode($filename); 
    return $protocol . '://' . $host . $file_url_path;
}

// Define the Terms and Conditions file name
$terms_page = "terms_and_conditions.php"; // Assuming you saved the T&C file with this name

// Get the base URL for Open Graph tags
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . '://' . $host . '/'; // Base URL of the site
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AnjPdF - Cloud PDF Hosting</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    
    <meta property="og:title" content="AnjPdF - Cloud PDF Hosting">
    <meta property="og:description" content="Your reliable Cloud PDF Hosting solution. Share and manage your files easily.">
    <meta property="og:image" content="<?php echo htmlspecialchars($base_url); ?>img/banner.png">
    <meta property="og:url" content="<?php echo htmlspecialchars($base_url); ?>">
    <meta property="og:type" content="website">
    <meta property="og:image:width" content="1200"> 
    <meta property="og:image:height" content="630"> 
    <style>
        /* Container for the file list */
.file-list {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: flex-start;
    padding: 20px;
}

/* Individual file card style */
.file-entry {
    background-color: #fff;
    border-radius: 12px;
    padding: 15px;
    /* Proper width calculation for desktop */
    width: calc(33.33% - 20px); 
    min-width: 280px;
    display: flex;
    flex-direction: column; /* Content stacked vertically */
    gap: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    box-sizing: border-box;
    overflow: hidden; /* Keeps everything inside the card */
}

/* Container for text info */
.file-info {
    width: 100%;
    overflow: hidden;
}

/* File name styling - prevents overflow */
.file-info h3 {
    margin: 0 0 5px 0;
    font-size: 16px;
    color: #333;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis; /* Adds "..." if name is too long */
}

.file-info p {
    margin: 0;
    font-size: 13px;
    color: #666;
}

/* Actions buttons container */
.file-actions {
    display: flex;
    gap: 10px;
    width: 100%;
    margin-top: auto; /* Pushes buttons to the bottom */
}

.file-actions a, .file-actions button {
    flex: 1; /* Equal width buttons */
    padding: 8px;
    font-size: 13px;
    text-align: center;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    text-decoration: none;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .file-entry {
        width: calc(50% - 20px); /* 2 cards per row */
    }
}

@media (max-width: 650px) {
    .file-list {
        justify-content: center;
    }
    .file-entry {
        width: 100%; /* Full width on mobile */
        max-width: 100%;
    }
        }
        
        
        /* ----------------------- THEME VARIABLES ----------------------- */
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
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            --card-hover-bg: #2B3352;
            --input-border: #3A4750;
        }

        /* ----------------------- BASE STYLES ----------------------- */
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            transition: background-color 0.4s, color 0.4s;
        }
        
        /* ----------------------- LOADER STYLES (Kept functionality, slight style update) ----------------------- */
        #loader-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--card-background);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.5s, visibility 0s 0.5s, background-color 0.4s;
            visibility: visible;
            opacity: 1;
        }

        .loaded #loader-wrapper {
            visibility: hidden;
            opacity: 0;
        }

        #loader {
            /* Using a simpler, non-3D spinner for a modern feel, but keeping the logo load */
            width: 80px; 
            height: 80px;
            position: relative;
        }
        
        #loader img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            animation: pulse 1.5s infinite ease-in-out;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 0.7; }
            50% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.7; }
        }
        /* ------------------- END LOADER STYLES ----------------------- */

        .main-content {
            min-height: calc(100vh - 100px); 
            padding-bottom: 40px;
        }

        .container {
            max-width: 1080px; /* Wider container for premium feel */
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

        /* ----------------------- HEADER & BRANDING ----------------------- */
        .header {
            display: flex;
            flex-direction: column; 
            align-items: center;
            padding-bottom: 30px;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--input-border); /* Subtle divider */
        }

        .header-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%; 
            margin-bottom: 15px;
        }

        .branding {
            display: flex;
            align-items: center;
        }

        .branding img {
            height: 50px; /* Slightly smaller logo */
            margin-right: 15px;
            filter: drop-shadow(0 0 5px rgba(0, 0, 0, 0.1)); /* Subtle logo shadow */
        }

        .branding h1 {
            margin: 0;
            font-size: 2.2em; 
            color: var(--primary-color);
            font-weight: 900; 
            letter-spacing: -1px;
        }
        
        /* UPDATED: Container for the buttons to stack them */
        .header-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-buttons {
            display: flex;
            flex-direction: column; /* Stack buttons vertically */
            gap: 10px; /* Space between the two buttons */
        }
        /* END UPDATED */
        
        /* Theme Switch Toggle */
        .theme-switch-wrapper {
            display: flex;
            align-items: center;
        }
        
        .theme-switch {
            display: inline-block;
            height: 24px;
            position: relative;
            width: 50px;
        }

        .theme-switch input {
            display: none;
        }

        .slider {
            background-color: var(--input-border);
            bottom: 0;
            cursor: pointer;
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
            transition: 0.4s;
            border-radius: 34px;
        }

        .slider:before {
            content: '☀️'; /* Sun icon for light mode */
            height: 18px;
            width: 18px;
            position: absolute;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 12px;
            line-height: 1;
        }

        input:checked + .slider {
            background-color: var(--primary-color);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
            content: '🌙'; /* Moon icon for dark mode */
            background-color: #212529;
            color: var(--primary-color);
        }

        /* ----------------------- HEADER TEXT AND BUTTON ----------------------- */
        .header-text-and-button {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .header-text-and-button p {
            font-size: 1.1em;
            color: var(--light-text);
            margin: 0;
            font-weight: 500;
        }
        
        /* Upload Button (Updated size property) */
        .header .upload-btn {
            background-color: var(--upload-color);
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
            box-shadow: 0 4px 8px rgba(100, 204, 197, 0.2);
            width: 100%; /* ENSURES BUTTONS ARE THE SAME SIZE */
        }

        .header .upload-btn:hover {
            background-color: #1e7e34;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.4);
        }
        
        /* All Files Button (Updated size property) */
        .all-files-btn {
            background-color: var(--accent-color); /* Use accent color for distinction */
            color: var(--text-color);
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1em;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s;
            white-space: nowrap; 
            box-shadow: 0 4px 8px rgba(100, 204, 197, 0.2);
            width: 100%; /* ENSURES BUTTONS ARE THE SAME SIZE */
        }

        .all-files-btn:hover {
            background-color: #49B2AA; /* Slightly darker accent */
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(100, 204, 197, 0.4);
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
        
        /* ----------------------- SEARCH BAR ----------------------- */
        .search-container {
            margin-bottom: 30px;
        }
        
        #fileSearch {
            width: 100%;
            padding: 15px 20px;
            border: 1px solid var(--input-border);
            border-radius: 12px;
            font-size: 1em;
            background-color: var(--card-background);
            color: var(--text-color);
            transition: border-color 0.3s, box-shadow 0.3s, background-color 0.4s, color 0.4s;
        }

        #fileSearch:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(23, 107, 135, 0.2);
            outline: none;
        }

        /* ----------------------- FILE LIST ----------------------- */
        .file-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); /* Grid for better desktop layout */
            gap: 20px;
            padding: 10px 0;
        }
        
        .file-entry {
            display: flex;
            flex-direction: column;
            padding: 20px;
            background-color: var(--card-hover-bg); /* Use the hover BG as the card BG for contrast */
            border-radius: 15px;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s;
            border: 1px solid var(--input-border);
        }

        .file-entry:hover {
             background-color: var(--card-background);
             transform: translateY(-3px);
             box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .file-name-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            flex-wrap: wrap; 
        }
        
        .file-name-link {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 700;
            font-size: 1.1em;
            flex-grow: 1;
            display: flex;
            align-items: center;
            min-width: 0; 
            margin-right: 10px;
        }

        .file-name-link .pdf-icon-image {
            height: 35px; 
            width: auto;
            margin-right: 10px;
            flex-shrink: 0; 
        }
        
        .file-metadata {
            display: flex;
            flex-direction: column; /* Stack metadata vertically */
            gap: 5px;
            font-size: 0.85em;
            color: var(--light-text);
            margin-top: 10px;
            width: 100%;
        }
        
        .metadata-item {
            display: flex;
            align-items: center;
        }
        
        .metadata-item::before {
            content: '•';
            margin-right: 8px;
            color: var(--accent-color);
            font-weight: bold;
        }
        
        .url-row {
            display: flex;
            align-items: center;
            padding-top: 10px;
            margin-top: 10px;
            border-top: 1px dashed var(--input-border);
            flex-wrap: wrap;
        }

        .url-text {
            font-size: 0.85em;
            color: var(--light-text); 
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex-grow: 1;
            padding-right: 10px;
            min-width: 0; 
            font-family: monospace;
        }

        .copy-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8em;
            font-weight: 600;
            transition: background-color 0.3s, transform 0.1s;
            flex-shrink: 0; 
            margin-top: 0;
        }

        .copy-btn:hover {
            background-color: var(--hover-color);
            transform: scale(1.02);
        }

        /* ----------------------- FOOTER STYLES ----------------------- */
        .footer {
            background-color: var(--card-background);
            padding: 25px 5%;
            margin-top: 50px;
            border-top: 1px solid var(--input-border);
            transition: background-color 0.4s;
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

        .footer-logo-icon img {
            height: 40px;
            width: auto;
            object-fit: contain;
        }

        .footer-link-group {
            display: flex;
            gap: 20px;
        }

        .footer-link a, .footer-admin-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .footer-admin-link a {
            font-size: 1em; 
            color: var(--light-text);
        }

        .footer-link a:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }
        
        .footer-admin-link a:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }


        /* ----------------------- RESPONSIVE DESIGN ----------------------- */
        
        @media (max-width: 1100px) {
            .container {
                margin: 30px 20px;
                padding: 30px;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 20px 15px;
                padding: 20px;
            }
            
            .header-top-row {
                flex-direction: column-reverse;
                align-items: flex-start;
            }
            
            .header-controls {
                margin-bottom: 15px;
            }

            .branding h1 {
                font-size: 2em;
            }
            
            .header-text-and-button {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .file-list {
                grid-template-columns: 1fr; /* Single column on mobile */
            }
            
            .file-metadata {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 10px;
            }

            .url-row {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }

            .url-text {
                white-space: normal; 
                word-break: break-all;
                padding-right: 0;
            }

            .copy-btn {
                width: 100%;
            }
            
            .footer-content {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .footer-link-group {
                flex-direction: column;
                gap: 10px;
            }
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
                <div class="header-top-row">
                    <div class="branding">
                        <img src="img/logo.png" alt="AnjPdF Logo">
                        <h1>AnjPdF</h1>
                    </div>
                    <div class="header-controls">
                        <div class="theme-switch-wrapper">
                            <label class="theme-switch" for="checkbox">
                                <input type="checkbox" id="checkbox" />
                                <div class="slider round"></div>
                            </label>
                        </div>
                        <div class="header-buttons">
                            <?php if(isset($_SESSION['user_email'])): ?>
    <a href="users/upanel.php" class="all-files-btn" style="padding:8px 15px; border-radius:8px; text-decoration:none; text-align:center;">👤 My Panel</a>
<?php else: ?>
    <a href="users/ulogin.php" class="all-files-btn" style="padding:8px 15px; border-radius:8px; text-decoration:none; text-align:center;">🔑 Login</a>
<?php endif; ?>
                            <a href="upload.php" class="upload-btn">➕ Upload</a>
                            <a href="allfiles.php" class="all-files-btn" onclick="showAllFiles()">📚 All Files</a>
                        </div>
                    </div>
                    </div>
                <div class="header-text-and-button">
                    <p>Your reliable Cloud PDF Hosting solution.</p>
                </div>
            </div>

            <h2>📚 Available PDF Files</h2>
            
            <div class="search-container">
                <input type="text" id="fileSearch" onkeyup="filterFiles()" placeholder="Search files by name..." aria-label="Search files">
            </div>

            <?php if (empty($pdf_files_data)): ?>
                <p style="text-align: center; padding: 20px;">No public PDF files found in the directory.</p>
            <?php else: ?>
                <div class="file-list">
                    <?php 
                    // Counter to track which files should be hidden initially
                    $file_count = 0;
                    foreach ($pdf_files_data as $file_data): 
                        $file_count++;
                        $file_name = $file_data['name'];
                        $full_url = get_full_url($file_name); 
                        $unique_id = 'url_' . md5($file_name);
                        $file_size = format_bytes($file_data['size']);
                        $modified_date = date("M d, Y", $file_data['modified_time']);
                        
                        // Set the display style to 'none' for files beyond the 9th
                        $initial_style = ($file_count > 9) ? 'style="display: none;"' : '';
                    ?>
                        <div class="file-entry" data-filename="<?php echo strtolower(htmlspecialchars($file_name)); ?>" data-file-index="<?php echo $file_count; ?>" <?php echo $initial_style; ?>>
                            <div class="file-name-row">
                                <a href="<?php echo htmlspecialchars($directory . $file_name); ?>" download="<?php echo htmlspecialchars($file_name); ?>" class="file-name-link">
                                    <img src="/img/pdf.png" alt="PDF Icon" class="pdf-icon-image">
                                    <span class="file-name-text"><?php echo htmlspecialchars($file_name); ?></span>
                                </a>
                            </div>
                            <div class="file-metadata">
                                <span class="metadata-item">Size: **<?php echo $file_size; ?>**</span>
                                <span class="metadata-item">Last Modified: <?php echo $modified_date; ?></span>
                            </div>
                            <div class="url-row">
                                 <span id="<?php echo $unique_id; ?>" class="url-text" title="<?php echo htmlspecialchars($full_url); ?>">
                                     <?php echo htmlspecialchars($full_url); ?>
                                </span>
                                <button class="copy-btn" onclick="copyUrl('<?php echo $unique_id; ?>')">📋 Copy URL</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <p id="noResults" style="display:none; text-align: center; color: var(--light-text); margin-top: 20px; grid-column: 1 / -1;">
                        No PDF files matched your search.
                    </p>
                </div>
            <?php endif; ?>

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
                <p class="footer-link"><a href="contact.php">Contact Us</a></p>
                <p class="footer-admin-link"><a href="login.php">🔑 Admin Panel Login</a></p>
            </div>
        </div>
    </div>
    <script>
        // Set the duration for the animation to 1.3 seconds
        const LOADER_DURATION_MS = 800; 
        const LOADER_FADE_OUT_DELAY = 100; // Delay before starting fade out

        // 1. Theme Toggle Logic
        const themeCheckbox = document.getElementById('checkbox');
        const body = document.body;

        // Function to apply the theme
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
            // Default to light if no preference or preference is 'light'
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

        // 3. Copy URL Button Logic
        function copyUrl(elementId) {
            const urlElement = document.getElementById(elementId);
            let urlText = urlElement.innerText.trim(); 
            
            const textarea = document.createElement('textarea');
            textarea.value = urlText;
            textarea.style.position = 'fixed'; 
            textarea.style.opacity = 0;
            document.body.appendChild(textarea);
            
            textarea.select();
            document.execCommand('copy');
            
            document.body.removeChild(textarea);
            
            const copyButton = urlElement.nextElementSibling;
            const originalText = copyButton.textContent;
            const originalBgColor = copyButton.style.backgroundColor;
            const originalPadding = copyButton.style.padding;
            
            copyButton.textContent = '✅ Copied!';
            copyButton.style.backgroundColor = 'var(--upload-color)'; 
            copyButton.style.padding = '8px 10px'; 
            
            setTimeout(() => {
                copyButton.textContent = originalText;
                copyButton.style.backgroundColor = originalBgColor || 'var(--primary-color)';
                copyButton.style.padding = originalPadding || '8px 12px';
            }, 2000);
        }
        
        // 4. Dedicated function to show all files
        function showAllFiles() {
            const files = document.querySelector('.file-list').getElementsByClassName('file-entry');
            const input = document.getElementById('fileSearch');
            const noResults = document.getElementById('noResults');

            // 1. Clear search input
            input.value = '';

            // 2. Show all file entries
            for (let i = 0; i < files.length; i++) {
                files[i].style.display = "flex";
            }
            
            // 3. Hide No Results message
            noResults.style.display = "none";
        }

        // 5. File Filtering/Search Logic (Updated to ensure correct initial state)
        function filterFiles() {
            const input = document.getElementById('fileSearch');
            const filter = input.value.toLowerCase();
            const files = document.querySelector('.file-list').getElementsByClassName('file-entry');
            let resultsFound = false;

            if (filter.length > 0) {
                // If there is a search filter, show all matching files (even those initially hidden)
                for (let i = 0; i < files.length; i++) {
                    const fileEntry = files[i];
                    const fileName = fileEntry.getAttribute('data-filename');
                    
                    if (fileName.includes(filter)) {
                        fileEntry.style.display = "flex";
                        resultsFound = true;
                    } else {
                        fileEntry.style.display = "none";
                    }
                }
            } else {
                // If the search filter is empty, revert to showing ONLY the top 9 files
                for (let i = 0; i < files.length; i++) {
                    const fileEntry = files[i];
                    const fileIndex = parseInt(fileEntry.getAttribute('data-file-index'));
                    
                    if (fileIndex <= 9) {
                        fileEntry.style.display = "flex";
                        resultsFound = true; 
                    } else {
                        fileEntry.style.display = "none";
                    }
                }
            }

            // Show 'No Results' message if no files matched the search filter
            const noResults = document.getElementById('noResults');
            if (filter.length > 0 && !resultsFound) {
                noResults.style.display = "block";
            } else {
                noResults.style.display = "none";
            }
        }
    </script>

</body>
</html>