<?php
// users/umannagefiles.php
require 'config.php';
if (!is_logged_in()) redirect('ulogin.php');

// Ensure Quarantine directory exists
if (!is_dir('../quarantine/')) mkdir('../quarantine/', 0755, true);

$user_email = $_SESSION['user_email'];
$msg = '';

// --- 1. HANDLE ACTIONS (Delete, Hide, Show) ---
if (isset($_GET['action']) && isset($_GET['file'])) {
    $target_file = $_GET['file'];
    $ownership = get_json_data(FILE_META_PATH);
    $found_index = -1;

    foreach ($ownership as $idx => $meta) {
        if ($meta['file'] === $target_file && $meta['owner'] === $user_email) {
            $found_index = $idx;
            break;
        }
    }

    if ($found_index > -1) {
        $public_path = '../pdf/' . $target_file;
        $quarantine_path = '../quarantine/' . $target_file;

        if ($_GET['action'] === 'delete') {
            // Delete from whichever folder it exists in
            if (file_exists($public_path)) unlink($public_path);
            if (file_exists($quarantine_path)) unlink($quarantine_path);
            
            array_splice($ownership, $found_index, 1);
            $msg = "<div class='alert alert-success'>🗑️ File deleted permanently.</div>";
        } 
        elseif ($_GET['action'] === 'hide') {
            // Move from public to quarantine
            if (file_exists($public_path)) {
                rename($public_path, $quarantine_path);
                $ownership[$found_index]['status'] = 'hidden';
                $msg = "<div class='alert alert-success'>🚫 File hidden and moved to quarantine.</div>";
            }
        } 
        elseif ($_GET['action'] === 'show') {
            // Move from quarantine to public
            if (file_exists($quarantine_path)) {
                rename($quarantine_path, $public_path);
                $ownership[$found_index]['status'] = 'active';
                $msg = "<div class='alert alert-success'>✅ File restored to public list.</div>";
            }
        }
        save_json_data(FILE_META_PATH, $ownership);
    }
}

// Re-fetch files for display
$ownership = get_json_data(FILE_META_PATH);
$my_files = array_filter($ownership, function($o) use ($user_email) {
    return $o['owner'] === $user_email;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Files - AnjPdF</title>
    <link rel="icon" type="image/png" href="/img/christmaslogo.png">
    <style>
        /* --- INTEGRATED INDEX THEME --- */
        :root {
            --primary-color: #176B87;
            --secondary-color: #053B50;
            --background-color: #f0f4f8;
            --card-background: #ffffff;
            --text-color: #333;
            --accent-color: #64CCC5;
            --error-color: #DC3545;
        }

        .dark-theme {
            --primary-color: #64CCC5;
            --secondary-color: #176B87;
            --background-color: #053B50;
            --card-background: #176B87;
            --text-color: #eeeeee;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            margin: 0; padding: 20px;
            min-height: 100vh;
            display: flex; flex-direction: column; align-items: center;
            transition: 0.3s;
        }

        /* Animated Pulse Background */
        body::before {
            content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 50% 50%, rgba(255,255,255,0.05) 0%, transparent 80%);
            z-index: -1; animation: pulse 8s infinite alternate;
        }
        @keyframes pulse { 0% { transform: scale(1); opacity: 0.3; } 100% { transform: scale(1.1); opacity: 0.5; } }

        .container { width: 100%; max-width: 900px; z-index: 1; }

        .header-area {
            background: var(--card-background);
            padding: 20px 30px; border-radius: 20px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin-bottom: 25px;
        }

        .file-card {
            background: var(--card-background);
            padding: 20px; border-radius: 15px; margin-bottom: 15px;
            display: flex; flex-direction: column; gap: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .file-info h4 { margin: 0; color: var(--accent-color); word-break: break-all; }
        .file-meta { font-size: 0.8em; opacity: 0.7; }

        .status-badge { font-size: 0.7em; padding: 3px 8px; border-radius: 10px; text-transform: uppercase; font-weight: bold; margin-left: 10px; }
        .status-active { background: #28a745; color: white; }
        .status-hidden { background: #fd7e14; color: white; }

        .action-bar { display: flex; gap: 10px; flex-wrap: wrap; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 15px; }
        
        .btn {
            padding: 8px 15px; border-radius: 8px; text-decoration: none;
            font-size: 0.85em; font-weight: 600; cursor: pointer; transition: 0.2s;
            border: none; display: flex; align-items: center; gap: 5px; color: white;
        }
        .btn-copy { background: var(--primary-color); }
        .btn-view { background: #6c757d; }
        .btn-hide { background: #fd7e14; }
        .btn-show { background: #28a745; }
        .btn-del { background: var(--error-color); }
        .btn:hover { opacity: 0.8; transform: translateY(-1px); }

        /* Theme Switch UI */
        .theme-switch { position: fixed; top: 20px; right: 20px; display: flex; align-items: center; gap: 10px; background: var(--card-background); padding: 10px 15px; border-radius: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); z-index: 100; }
        .switch { position: relative; display: inline-block; width: 40px; height: 20px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 20px; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--accent-color); }
        input:checked + .slider:before { transform: translateX(20px); }

        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body class="dark-theme">

    <div class="theme-switch">
        <span>☀️</span>
        <label class="switch"><input type="checkbox" id="themeToggle" checked><span class="slider"></span></label>
        <span>🌙</span>
    </div>

    <div class="container">
        <div class="header-area">
            <h2 style="margin:0;">📂 My Files</h2>
            <a href="upanel.php" class="btn btn-view" style="background:#444;">← Dashboard</a>
        </div>

        <?php echo $msg; ?>

        <?php if (empty($my_files)): ?>
            <div class="file-card" style="text-align:center;">
                <p>No files found. <a href="../upload.php" style="color:var(--accent-color);">Upload now?</a></p>
            </div>
        <?php else: ?>
            <?php foreach ($my_files as $f): 
                $is_hidden = (isset($f['status']) && $f['status'] === 'hidden');
                $file_url = "https://anj.ct.ws/pdf/" . urlencode($f['file']);
            ?>
                <div class="file-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div class="file-info">
                            <h4><?php echo htmlspecialchars($f['file']); ?></h4>
                            <span class="file-meta">Uploaded: <?php echo date("d M Y", $f['date']); ?></span>
                        </div>
                        <span class="status-badge <?php echo $is_hidden ? 'status-hidden' : 'status-active'; ?>">
                            <?php echo $is_hidden ? 'Hidden' : 'Public'; ?>
                        </span>
                    </div>

                    <div class="action-bar">
                        <?php if (!$is_hidden): ?>
                            <a href="<?php echo $file_url; ?>" target="_blank" class="btn btn-view">👁️ View</a>
                            <button class="btn btn-copy" onclick="copyLink('<?php echo $file_url; ?>')">🔗 Copy Link</button>
                            <a href="?action=hide&file=<?php echo urlencode($f['file']); ?>" class="btn btn-hide">🚫 Hide</a>
                        <?php else: ?>
                            <a href="?action=show&file=<?php echo urlencode($f['file']); ?>" class="btn btn-show">✅ Show / Restore</a>
                        <?php endif; ?>
                        
                        <a href="?action=delete&file=<?php echo urlencode($f['file']); ?>" class="btn btn-del" onclick="return confirm('Delete permanently?');">🗑️ Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        // Copy Link Function
        function copyLink(url) {
            navigator.clipboard.writeText(url).then(() => {
                alert("Link copied: " + url);
            });
        }

        // Theme Toggle
        const toggle = document.getElementById('themeToggle');
        toggle.addEventListener('change', () => {
            document.body.classList.toggle('dark-theme', toggle.checked);
            localStorage.setItem('theme', toggle.checked ? 'dark' : 'light');
        });

        // Initialize theme
        if (localStorage.getItem('theme') === 'light') {
            toggle.checked = false;
            document.body.classList.remove('dark-theme');
        }
    </script>
</body>
</html>