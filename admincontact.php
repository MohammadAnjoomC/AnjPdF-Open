<?php
    echo '<link rel="icon" type="image/png" href="/img/logo.png">';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// File path for pending and solved submissions
$submissions_file = './submissions/contact_submissions.txt';
$solved_submissions_file = './submissions/solved_submissions.txt';

// Ensure the directory exists
if (!is_dir('./submissions')) {
    mkdir('./submissions', 0755, true);
}

// Function to safely read and parse submissions from a file
function read_submissions($filepath) {
    if (!file_exists($filepath)) {
        return [];
    }
    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $submissions = [];
    foreach ($lines as $line) {
        $data = json_decode($line, true);
        if ($data !== null) {
            $submissions[] = $data;
        }
    }
    return $submissions;
}

// Function to save submissions back to the file
function save_submissions($filepath, $submissions) {
    $lines = array_map('json_encode', $submissions);
    file_put_contents($filepath, implode("\n", $lines) . (empty($lines) ? '' : "\n"), LOCK_EX);
}


// Load all pending and solved submissions (needed for the move operation)
$pending_submissions = read_submissions($submissions_file);
$solved_submissions = read_submissions($solved_submissions_file);

// --- Handle Solve Action ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'solve') {
    $timestamp_to_solve = $_POST['timestamp'] ?? '';
    
    // Find the submission to move
    $found_key = null;
    foreach ($pending_submissions as $key => $sub) {
        if ($sub['timestamp'] === $timestamp_to_solve) {
            $found_key = $key;
            break;
        }
    }

    if ($found_key !== null) {
        $submission_to_move = $pending_submissions[$found_key];
        $submission_to_move['status'] = 'Solved'; // Initial status in the solved file

        // 1. Remove from pending list
        unset($pending_submissions[$found_key]);

        // 2. Add to solved list and save solved submissions
        $solved_submissions[] = $submission_to_move;

        // Rebuild and save pending submissions file
        save_submissions($submissions_file, $pending_submissions);

        // Rebuild and save solved submissions file
        save_submissions($solved_submissions_file, $solved_submissions);

        // Redirect to avoid resubmission and show updated list
        header('Location: admincontact.php?status=solved');
        exit;
    }
}
// --- End Handle Solve Action ---

// After potential modification, reload pending submissions for display
$pending_submissions = read_submissions($submissions_file);

// Sort pending submissions by timestamp (newest first)
usort($pending_submissions, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AnjPdF - Admin Contact Panel</title>
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
            max-width: 1080px; 
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

        /* Sub Navigation for Contact */
        .sub-nav {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--input-border);
        }
        .sub-nav a {
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            transition: background-color 0.3s, color 0.3s;
        }
        .sub-nav a.active {
            background-color: var(--primary-color);
            color: white;
        }
        .sub-nav a:hover:not(.active) {
            background-color: var(--primary-color);
            color: white;
            opacity: 0.8;
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
        
        .solved-message {
            color: #155724;
            background-color: #D4EDDA;
            border: 1px solid #C3E6CB;
            padding: 10px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 20px;
        }

        /* ----------------------- SUBMISSIONS LIST ----------------------- */
        .submission-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 40px;
        }

        .submission-entry {
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--input-border);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }
        
        .submission-entry:hover:not(.solved) {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .pending {
            background-color: var(--card-background);
        }

        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed var(--input-border);
        }
        
        .submission-header h3 {
            margin: 0;
            font-size: 1.3em;
            color: var(--primary-color);
        }
        
        .submission-status {
            font-weight: 700;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9em;
        }
        
        .status-pending {
            background-color: #FFC107;
            color: var(--text-color);
        }

        .submission-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 0.95em;
        }
        
        .submission-details p {
            margin: 0;
        }
        
        .submission-details strong {
            color: var(--primary-color);
            margin-right: 5px;
        }

        .submission-message {
            background-color: var(--card-hover-bg);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--input-border);
            white-space: pre-wrap;
        }

        .submission-actions {
            margin-top: 15px;
            text-align: right;
        }

        .solve-btn {
            background-color: var(--upload-color);
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .solve-btn:hover {
            background-color: #1e7e34;
        }

        .no-submissions {
            text-align: center;
            color: var(--light-text);
            padding: 30px;
            border: 2px dashed var(--input-border);
            border-radius: 12px;
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
            .submission-details {
                grid-template-columns: 1fr;
            }
            .submission-actions {
                text-align: left;
            }
            .sub-nav {
                flex-wrap: wrap;
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
                    <a href="admin.php" class="nav-link-default">👤 Profile</a>
                    <a href="adminfilemannage.php" class="nav-link-default">📁 Files</a>
                    <a href="admincontact.php" class="nav-link-active">📢 Submissions</a>
                    <a href="logout.php" class="nav-link-logout">🚪 Logout</a>
                </div>
            </div>

            <div class="sub-nav">
                <a href="admincontact.php" class="active">Pending Submissions</a>
                <a href="solved.php">Solved Submissions Manager</a>
            </div>

            <?php if (isset($_GET['status']) && $_GET['status'] === 'solved'): ?>
                <div class="solved-message">✅ Submission marked as solved and moved to solved submissions manager!</div>
            <?php endif; ?>

            <h2>🔔 Pending Submissions (<?php echo count($pending_submissions); ?>)</h2>

            <div class="submission-list">
                <?php if (empty($pending_submissions)): ?>
                    <p class="no-submissions">No pending submissions found. Great job!</p>
                <?php else: ?>
                    <?php foreach ($pending_submissions as $submission): ?>
                        <div class="submission-entry pending">
                            <div class="submission-header">
                                <h3><?php echo htmlspecialchars($submission['type']); ?> from **<?php echo htmlspecialchars($submission['name']); ?>**</h3>
                                <span class="submission-status status-pending">Pending</span>
                            </div>
                            <div class="submission-details">
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($submission['email']); ?></p>
                                <p><strong>Timestamp:</strong> <?php echo htmlspecialchars($submission['timestamp']); ?></p>
                                <p><strong>File Name:</strong> <?php echo htmlspecialchars($submission['filename']); ?></p>
                            </div>
                            <p><strong>Message:</strong></p>
                            <div class="submission-message">
                                <?php echo nl2br(htmlspecialchars($submission['message'])); ?>
                            </div>
                            <div class="submission-actions">
                                <form action="admincontact.php" method="post" onsubmit="return confirm('Are you sure you want to mark this submission as SOLVED? It will be moved to the Solved Submissions Manager.');">
                                    <input type="hidden" name="action" value="solve">
                                    <input type="hidden" name="timestamp" value="<?php echo htmlspecialchars($submission['timestamp']); ?>">
                                    <button type="submit" class="solve-btn">✅ Mark as Solved</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            </div>
    </div>

    <div class="footer">
        <div class="footer-content">
            <p>&copy; <?php echo date("Y"); ?> AnjPdF - Admin</p>
            <div class="footer-link-group">
                <p>Logged in as Admin</p>
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