<?php
    echo '<link rel="icon" type="image/png" href="/img/logo.png">';
// Set the directory to scan for PDF files.
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
$now = time(); // Current timestamp
foreach ($files as $file) {
    $full_path = $directory . $file;
    // Check if the file ends with .pdf (case-insensitive) and is a regular file (not a directory)
    if (is_file($full_path) && preg_match('/\.pdf$/i', $file)) {
        $file_size_bytes = filesize($full_path);
        $file_modified_time = filemtime($full_path);

        $pdf_files_data[] = [
            'name' => $file,
            'size_bytes' => $file_size_bytes, // Store raw bytes for JS filtering
            'size_formatted' => format_bytes($file_size_bytes),
            'modified_time' => $file_modified_time, // Store timestamp for JS filtering
            'modified_date' => date("M d, Y", $file_modified_time),
        ];
    }
}

// Default PHP sort: By modified time, newest first (for initial display)
usort($pdf_files_data, function($a, $b) {
    return $b['modified_time'] - $a['modified_time'];
});


// Get the total count of PDF files
$total_pdf_count = count($pdf_files_data);


// Helper function to build the full URL
function get_full_url($filename) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    // Assuming this file is in the root, and files are in the public /pdf/ folder.
    $file_url_path = '/pdf/' . rawurlencode($filename); 
    return $protocol . '://' . $host . $file_url_path;
}

// Define the Terms and Conditions file name
$terms_page = "terms_and_conditions.php"; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AnjPdF - All Hosted Files</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
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
            --count-color: #0A4D68; /* New color for the file count badge */
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
            --count-color: #176B87;
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
        
        /* ----------------------- LOADER STYLES ----------------------- */
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
            max-width: 1080px; 
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
            border-bottom: 1px solid var(--input-border); 
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
            height: 50px; 
            margin-right: 15px;
            filter: drop-shadow(0 0 5px rgba(0, 0, 0, 0.1)); 
        }

        .branding h1 {
            margin: 0;
            font-size: 2.2em; 
            color: var(--primary-color);
            font-weight: 900; 
            letter-spacing: -1px;
        }
        
        .header-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        /* Theme Switch Toggle (Styles remain the same) */
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
            content: '☀️'; 
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
            content: '🌙'; 
            background-color: #212529;
            color: var(--primary-color);
        }

        /* ----------------------- HEADER TEXT AND BUTTONS ----------------------- */
        .header-action-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }

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
        
        /* Home and Upload Buttons */
        .action-btn {
            background-color: var(--upload-color); /* Green for Upload */
            color: white;
            padding: 10px 15px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1em;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s;
            white-space: nowrap; 
            box-shadow: 0 4px 8px rgba(100, 204, 197, 0.2);
        }
        
        .home-btn {
            background-color: var(--accent-color); /* Accent color for Home */
            color: var(--text-color);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.4);
        }
        
        .upload-btn:hover {
            background-color: #1e7e34;
        }
        
        .home-btn:hover {
            background-color: #49B2AA;
            color: var(--text-color);
        }
        
        /* File Count Badge */
        .file-count-badge {
            background-color: var(--count-color);
            color: white;
            padding: 8px 15px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1em;
            white-space: nowrap;
            display: inline-block;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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
        
        /* ----------------------- FILTER & SEARCH BAR ----------------------- */
        .control-panel {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid var(--input-border);
            border-radius: 12px;
            background-color: var(--card-hover-bg);
        }
        
        #fileSearch {
            width: 100%;
            padding: 15px 20px;
            border: 1px solid var(--input-border);
            border-radius: 10px;
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
        
        .filter-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .filter-controls label {
            font-size: 0.9em;
            font-weight: 600;
            color: var(--text-color);
            display: block;
            margin-bottom: 5px;
        }
        
        .filter-controls select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--input-border);
            border-radius: 8px;
            font-size: 1em;
            background-color: var(--card-background);
            color: var(--text-color);
            appearance: none; /* Remove default arrow */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Cpath fill='%236C757D' d='M10 12l-6-6h12z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 12px;
            cursor: pointer;
        }
        
        /* ----------------------- FILE LIST ----------------------- */
        .file-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
            gap: 20px;
            padding: 10px 0;
        }
        
        .file-entry {
            display: flex;
            flex-direction: column;
            padding: 20px;
            background-color: var(--card-hover-bg); 
            border-radius: 15px;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s;
            border: 1px solid var(--input-border);
        }
        /* ... (rest of .file-entry and related styles remain the same) ... */
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
            flex-direction: column; 
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

            .filter-controls {
                grid-template-columns: 1fr;
            }

            .file-list {
                grid-template-columns: 1fr; 
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
                        <div class="header-action-buttons">
                            <a href="index.php" class="action-btn home-btn">🏠 Home</a>
                            <a href="upload.php" class="action-btn upload-btn">➕ Upload</a>
                        </div>
                    </div>
                    </div>
                <div class="header-text-and-button">
                    <p>Your reliable Cloud PDF Hosting solution.</p>
                    </div>
                    <div class="header-text-and-button">
                    <p>AnjPDF have <?php echo $total_pdf_count; ?> files.</p>
                </div>
            </div>

            <h2>🔎 File Management & Search</h2>
            
            <div class="control-panel">
                <input type="text" id="fileSearch" onkeyup="applyFiltersAndSort()" placeholder="Search files by name..." aria-label="Search files">
                
                <div class="filter-controls">
                    <div>
                        <label for="sortBy">Sort By:</label>
                        <select id="sortBy" onchange="applyFiltersAndSort()">
                            <option value="date_desc">Date (Newest First)</option>
                            <option value="date_asc">Date (Oldest First)</option>
                            <option value="size_desc">Size (Largest First)</option>
                            <option value="size_asc">Size (Smallest First)</option>
                            <option value="name_asc">Name (A-Z)</option>
                            <option value="name_desc">Name (Z-A)</option>
                        </select>
                    </div>

                    <div>
                        <label for="filterSize">Filter By Size (MB):</label>
                        <select id="filterSize" onchange="applyFiltersAndSort()">
                            <option value="none">None</option>
                            <option value="small">&lt; 1 MB</option>
                            <option value="medium">1 MB - 5 MB</option>
                            <option value="large">&gt; 5 MB</option>
                        </select>
                    </div>

                    <div>
                        <label for="filterDate">Filter By Date:</label>
                        <select id="filterDate" onchange="applyFiltersAndSort()">
                            <option value="none">None</option>
                            <option value="7days">Last 7 Days</option>
                            <option value="30days">Last 30 Days</option>
                            <option value="1year">Last Year</option>
                        </select>
                    </div>
                </div>
            </div>

            <?php if (empty($pdf_files_data)): ?>
                <p style="text-align: center; padding: 20px;">No public PDF files found in the directory.</p>
            <?php else: ?>
                <div class="file-list" id="fileListContainer">
                    <?php 
                    foreach ($pdf_files_data as $file_data): 
                        $file_name = $file_data['name'];
                        $full_url = get_full_url($file_name); 
                        $unique_id = 'url_' . md5($file_name);
                        $file_size = $file_data['size_formatted'];
                        $modified_date = $file_data['modified_date'];
                        
                        // Pass raw data attributes for JavaScript filtering/sorting
                    ?>
                        <div class="file-entry" 
                             data-filename="<?php echo strtolower(htmlspecialchars($file_name)); ?>"
                             data-size-bytes="<?php echo $file_data['size_bytes']; ?>"
                             data-modified-time="<?php echo $file_data['modified_time']; ?>">
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
                        No PDF files matched your current criteria.
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
                <p class="footer-admin-link"><a href="login.php">🔑 Admin Panel Login</a></p>
            </div>
        </div>
    </div>
    <script>
        // Set the duration for the animation to 1.3 seconds
        const LOADER_DURATION_MS = 800; 
        const LOADER_FADE_OUT_DELAY = 100; // Delay before starting fade out
        
        // Define size constants (in bytes)
        const MB = 1024 * 1024;
        const SIZE_RANGES = {
            'small': { min: 0, max: 1 * MB - 1 },
            'medium': { min: 1 * MB, max: 5 * MB },
            'large': { min: 5 * MB, max: Infinity },
            'none': { min: 0, max: Infinity }
        };

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
        
        // 4. Combined Filtering and Sorting Logic
        function applyFiltersAndSort() {
            const container = document.getElementById('fileListContainer');
            if (!container) return; 

            const files = Array.from(container.getElementsByClassName('file-entry'));
            const searchFilter = document.getElementById('fileSearch').value.toLowerCase();
            const sortBy = document.getElementById('sortBy').value;
            const sizeFilter = document.getElementById('filterSize').value;
            const dateFilter = document.getElementById('filterDate').value;
            const noResults = document.getElementById('noResults');
            
            let resultsFound = 0;
            const now = Date.now() / 1000; // Current time in seconds

            // --- 4.1 Filter Logic ---
            const filteredFiles = files.filter(fileEntry => {
                const fileName = fileEntry.getAttribute('data-filename');
                const fileSize = parseInt(fileEntry.getAttribute('data-size-bytes'));
                const fileTime = parseInt(fileEntry.getAttribute('data-modified-time'));

                // Search Filter
                const matchesSearch = fileName.includes(searchFilter);

                // Size Filter
                let matchesSize = true;
                if (sizeFilter !== 'none') {
                    const range = SIZE_RANGES[sizeFilter];
                    matchesSize = (fileSize >= range.min && fileSize <= range.max);
                }

                // Date Filter
                let matchesDate = true;
                if (dateFilter !== 'none') {
                    let timeLimit = 0; // The oldest timestamp to accept
                    
                    if (dateFilter === '7days') {
                        timeLimit = now - (7 * 24 * 60 * 60);
                    } else if (dateFilter === '30days') {
                        timeLimit = now - (30 * 24 * 60 * 60);
                    } else if (dateFilter === '1year') {
                        timeLimit = now - (365 * 24 * 60 * 60);
                    }
                    
                    matchesDate = (fileTime >= timeLimit);
                }

                return matchesSearch && matchesSize && matchesDate;
            });
            
            // --- 4.2 Sort Logic ---
            filteredFiles.sort((a, b) => {
                const aName = a.getAttribute('data-filename');
                const bName = b.getAttribute('data-filename');
                const aSize = parseInt(a.getAttribute('data-size-bytes'));
                const bSize = parseInt(b.getAttribute('data-size-bytes'));
                const aTime = parseInt(a.getAttribute('data-modified-time'));
                const bTime = parseInt(b.getAttribute('data-modified-time'));

                if (sortBy === 'name_asc') {
                    return aName.localeCompare(bName);
                } else if (sortBy === 'name_desc') {
                    return bName.localeCompare(aName);
                } else if (sortBy === 'size_asc') {
                    return aSize - bSize;
                } else if (sortBy === 'size_desc') {
                    return bSize - aSize;
                } else if (sortBy === 'date_asc') {
                    return aTime - bTime;
                } else if (sortBy === 'date_desc') {
                    return bTime - aTime; // Newest first (default)
                }
                return 0;
            });

            // --- 4.3 Re-render Logic ---
            // First, hide all existing files
            files.forEach(file => file.style.display = 'none');
            
            // Append the sorted, filtered files to the container
            filteredFiles.forEach(file => {
                file.style.display = 'flex';
                container.appendChild(file);
                resultsFound++;
            });

            // --- 4.4 Update Results Message ---
            if (resultsFound === 0) {
                noResults.style.display = 'block';
            } else {
                noResults.style.display = 'none';
            }
        }
        
        // Ensure initial sort/filter is applied on load (optional, as PHP provides initial sort)
        // window.addEventListener('load', applyFiltersAndSort);
    </script>

</body>
</html>