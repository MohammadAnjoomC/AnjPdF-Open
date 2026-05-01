<?php
    echo '<link rel="icon" type="image/png" href="/img/logo.png">';
session_start();

// Determine if the current user is an admin. The page is now PUBLIC by default.
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// File path for solved submissions
$solved_submissions_file = './submissions/solved_submissions.txt';
$message_status = '';

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

// Load all solved submissions
$solved_submissions = read_submissions($solved_submissions_file);

// --- Handle Publish/Hide Action (Admin-Only) ---
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['timestamp'])) {
    $timestamp_to_modify = $_POST['timestamp'];
    $action = $_POST['action'];
    $status_msg = '';

    // Find the submission to modify
    $found_key = null;
    foreach ($solved_submissions as $key => $sub) {
        if ($sub['timestamp'] === $timestamp_to_modify) {
            $found_key = $key;
            break;
        }
    }

    if ($found_key !== null) {
        if ($action === 'publish') {
            $solved_submissions[$found_key]['status'] = 'Published';
            $status_msg = '✅ Submission published successfully.';
        } elseif ($action === 'hide') {
            $solved_submissions[$found_key]['status'] = 'Solved'; // Reverting to 'Solved' means it's not public
            $status_msg = '🔒 Submission hidden successfully.';
        }

        // Save the modified list
        save_submissions($solved_submissions_file, $solved_submissions);

        // Redirect to avoid resubmission and show updated list
        header('Location: solved.php?status=success&msg=' . urlencode($status_msg));
        exit;
    } else {
        header('Location: solved.php?status=error&msg=' . urlencode('🚫 Submission not found.'));
        exit;
    }
}
// --- End Handle Actions ---

// Load messages from GET parameters
if (isset($_GET['status']) && isset($_GET['msg'])) {
    $status_type = $_GET['status'] === 'success' ? 'success-message' : 'error-message';
    $message_status = '<p class="' . $status_type . '">' . htmlspecialchars(urldecode($_GET['msg'])) . '</p>';
} 

// Sort solved submissions by timestamp (newest first)
usort($solved_submissions, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Separate into Published (for public) and Hidden/Solved (for admin)
$published_submissions = array_filter($solved_submissions, function($sub) {
    // Only show "Review" and "Suggestion" types publicly
    return $sub['type'] !== 'Complaint' && $sub['type'] !== 'File Report' && (isset($sub['status']) && $sub['status'] === 'Published');
});

$hidden_submissions = array_filter($solved_submissions, function($sub) {
    return !isset($sub['status']) || $sub['status'] === 'Solved';
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AnjPdF - Solved Submissions <?php echo $is_admin ? '(Admin)' : '(Public)'; ?></title>
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
        
        /* ----------------------- ADMIN SPECIFIC STYLES ----------------------- */
        <?php if ($is_admin): ?>
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
        <?php endif; ?>
        
        /* ----------------------- SHARED STYLES ----------------------- */
        h1.public-title {
            text-align: center;
            font-size: 2.5em;
            font-weight: 900;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        p.public-subtitle {
            text-align: center;
            color: var(--light-text);
            font-size: 1.1em;
            margin-bottom: 40px;
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
        
        .submission-entry:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .solved {
            background-color: var(--card-hover-bg);
        }

        .published {
            background-color: #E6F7F0; /* Light green/teal */
            border-left: 5px solid var(--upload-color);
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
        
        .status-solved {
            background-color: #FFC107;
            color: var(--text-color);
        }
        
        .status-published {
            background-color: var(--upload-color);
            color: white;
        }
        
        /* Hide details for public view */
        .public-hide {
            display: none;
        }
        
        .submission-message {
            background-color: var(--card-background);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--input-border);
            white-space: pre-wrap;
        }

        .submission-actions {
            margin-top: 15px;
            text-align: right;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .publish-btn {
            background-color: var(--primary-color);
            color: white;
        }
        
        .publish-btn:hover {
            background-color: var(--hover-color);
        }
        
        .hide-btn {
            background-color: #FFC107; 
            color: var(--text-color);
        }
        
        .hide-btn:hover {
            background-color: #e0a800;
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
            .submission-actions {
                justify-content: flex-start;
                gap: 10px;
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
            <?php if ($is_admin): ?>
                <div class="admin-header">
                    <h1>🔑 Admin Panel</h1>
                    <div class="admin-nav">
                        <a href="admin.php" class="nav-link-default">👤 Profile</a>
                        <a href="adminfilemannage.php" class="nav-link-default">📁 Files</a>
                        <a href="admincontact.php" class="nav-link-default">📢 Submissions</a>
                        <a href="logout.php" class="nav-link-logout">🚪 Logout</a>
                    </div>
                </div>
                
                <div class="sub-nav">
                    <a href="admincontact.php">Pending Submissions</a>
                    <a href="solved.php" class="active">Solved Submissions Manager</a>
                </div>

                <?php echo $message_status; ?>

                <h2>⭐ Published Submissions (<?php echo count($published_submissions); ?>)</h2>
                <p style="margin-top: -20px; color: var(--light-text);">These submissions are publicly visible. Only Reviews/Suggestions are eligible for publishing.</p>

                <div class="submission-list">
                    <?php if (empty($published_submissions)): ?>
                        <p class="no-submissions">No submissions are currently marked as Published.</p>
                    <?php else: ?>
                        <?php foreach ($published_submissions as $submission): ?>
                            <div class="submission-entry published">
                                <div class="submission-header">
                                    <h3><?php echo htmlspecialchars($submission['type']); ?> from **<?php echo htmlspecialchars($submission['name']); ?>**</h3>
                                    <span class="submission-status status-published">Published</span>
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
                                    <form action="solved.php" method="post" onsubmit="return confirm('Are you sure you want to HIDE this from public view?');">
                                        <input type="hidden" name="action" value="hide">
                                        <input type="hidden" name="timestamp" value="<?php echo htmlspecialchars($submission['timestamp']); ?>">
                                        <button type="submit" class="action-btn hide-btn">🔒 Hide (Solved Only)</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <h2>📦 Hidden/Solved Submissions (<?php echo count($hidden_submissions); ?>)</h2>
                <p style="margin-top: -20px; color: var(--light-text);">These are solved or non-publishable submissions (Complaints/Reports).</p>

                <div class="submission-list">
                    <?php if (empty($hidden_submissions)): ?>
                        <p class="no-submissions">No hidden solved submissions found.</p>
                    <?php else: ?>
                        <?php foreach ($hidden_submissions as $submission): ?>
                            <div class="submission-entry solved">
                                <div class="submission-header">
                                    <h3><?php echo htmlspecialchars($submission['type']); ?> from **<?php echo htmlspecialchars($submission['name']); ?>**</h3>
                                    <span class="submission-status status-solved">Solved</span>
                                </div>
                                <div class="submission-details">
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($submission['email']); ?></p>
                                    <p><strong>Timestamp:</strong> <?php echo htmlspecialchars($submission['timestamp']); ?></p>
                                    <p><strong>File Name:</strong> <?php echo htmlspecialchars($submission['filename']); ?></p>
                                </div>
                                <p><strong>Message:</strong></p>
                                <div class="submission-message" style="background-color: var(--card-background);">
                                    <?php echo nl2br(htmlspecialchars($submission['message'])); ?>
                                </div>
                                <div class="submission-actions">
                                    <?php if ($submission['type'] === 'Review' || $submission['type'] === 'Suggestion'): ?>
                                        <form action="solved.php" method="post" onsubmit="return confirm('Are you sure you want to PUBLISH this submission?');">
                                            <input type="hidden" name="action" value="publish">
                                            <input type="hidden" name="timestamp" value="<?php echo htmlspecialchars($submission['timestamp']); ?>">
                                            <button type="submit" class="action-btn publish-btn">⭐ Publish Submission</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #DC3545; font-weight: 600;">(Non-publishable type)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <h1 class="public-title">🏆 Solved & Published Submissions</h1>
                <p class="public-subtitle">See what our users are saying! These submissions have been reviewed and solved by the admin team.</p>

                <div class="submission-list">
                    <?php if (empty($published_submissions)): ?>
                        <p class="no-submissions">No submissions are currently published for public view.</p>
                    <?php else: ?>
                        <?php foreach ($published_submissions as $submission): ?>
                            <div class="submission-entry published">
                                <div class="submission-header">
                                    <h3><?php echo htmlspecialchars($submission['type']); ?> from **<?php echo htmlspecialchars($submission['name']); ?>**</h3>
                                    <span class="submission-status status-published">Solved</span>
                                </div>
                                <p><strong>Date:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($submission['timestamp']))); ?></p>
                                <p style="font-size: 1.1em; margin-top: 15px;">
                                    "<?php echo nl2br(htmlspecialchars($submission['message'])); ?>"
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <p style="text-align: center; margin-top: 20px; font-size: 0.9em;">
                    <a href="contact.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">👋 Have a suggestion or review? Submit it here!</a>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="footer">
        <div class="footer-content">
            <p>&copy; <?php echo date("Y"); ?> AnjPdF - <?php echo $is_admin ? 'Admin' : 'Host'; ?></p>
            <div class="footer-link-group">
                <?php if (!$is_admin): ?>
                    <p class="footer-link"><a href="login.php">🔑 Admin Login</a></p>
                <?php endif; ?>
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