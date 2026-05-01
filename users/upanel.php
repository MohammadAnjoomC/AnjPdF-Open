<?php
// users/upanel.php
require 'config.php';
if (!is_logged_in()) redirect('ulogin.php');

$user_email = $_SESSION['user_email'];
$user_pic = $_SESSION['user_pic'];
$pic_path = ($user_pic == 'default.png') ? '../img/logo.png' : 'img/' . $user_pic;

// Get file Stats
$ownership = get_json_data(FILE_META_PATH);
$my_files = array_filter($ownership, function($o) use ($user_email) {
    return $o['owner'] === $user_email && $o['status'] === 'active';
});
$count = count($my_files);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - AnjPdF</title>
    <link rel="icon" type="image/png" href="/img/christmaslogo.png">
    
    <style>
        /* --- THEME VARIABLES (From Index) --- */
        :root {
            --primary-color: #176B87;
            --secondary-color: #053B50;
            --background-color: #f0f4f8;
            --card-background: #ffffff;
            --text-color: #333;
            --accent-color: #64CCC5;
            --error-color: #DC3545;
            --transition-speed: 0.3s;
        }

        .dark-theme {
            --primary-color: #64CCC5;
            --secondary-color: #176B87;
            --background-color: #053B50;
            --card-background: #176B87;
            --text-color: #eeeeee;
        }

        /* --- GLOBAL STYLES --- */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            transition: background-color var(--transition-speed), color var(--transition-speed);
            overflow-x: hidden;
        }

        /* --- ANIMATED BACKGROUND (From Index) --- */
        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 50% 50%, rgba(255,255,255,0.05) 0%, transparent 80%);
            z-index: -1;
            animation: pulse 8s infinite alternate;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.3; }
            100% { transform: scale(1.2); opacity: 0.6; }
        }

        /* --- DASHBOARD LAYOUT --- */
        .dashboard { 
            width: 95%; 
            max-width: 1100px; 
            display: grid; 
            grid-template-columns: 280px 1fr; 
            gap: 25px; 
            margin: 20px;
            z-index: 1;
        }

        .sidebar, .content { 
            background: var(--card-background); 
            padding: 30px; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(5px);
            transition: transform 0.3s ease;
        }

        .sidebar:hover, .content:hover { transform: translateY(-5px); }

        /* --- SIDEBAR ELEMENTS --- */
        .profile-img { 
            width: 100px; height: 100px; 
            border-radius: 50%; object-fit: cover; 
            border: 4px solid var(--primary-color);
            margin-bottom: 15px;
        }

        .nav-btn { 
            display: block; width: 100%; padding: 12px; margin: 10px 0; 
            text-align: left; text-decoration: none; color: var(--text-color); 
            border-radius: 10px; transition: 0.3s; font-weight: 500;
        }

        .nav-btn:hover { background: rgba(255,255,255,0.1); color: var(--accent-color); padding-left: 20px; }
        .nav-btn.active { background: var(--primary-color); color: white; }

        /* --- CONTENT ELEMENTS --- */
        .stat-box { 
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); 
            color: white; padding: 25px; border-radius: 15px; 
            display: inline-block; min-width: 200px; text-align: center; 
            margin-top: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .stat-box h1 { margin: 0; font-size: 2.5em; }

        /* --- THEME TOGGLE (From Index) --- */
        .theme-switch {
            position: fixed; top: 20px; right: 20px;
            display: flex; align-items: center; gap: 10px;
            background: var(--card-background);
            padding: 10px 15px; border-radius: 30px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            z-index: 100;
        }

        .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc; transition: .4s; border-radius: 24px;
        }
        .slider:before {
            position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 3px;
            background-color: white; transition: .4s; border-radius: 50%;
        }
        input:checked + .slider { background-color: #64CCC5; }
        input:checked + .slider:before { transform: translateX(26px); }

        @media (max-width: 850px) { 
            .dashboard { grid-template-columns: 1fr; } 
            .sidebar { margin-bottom: 10px; }
        }
    </style>
</head>
<body>

    <div class="theme-switch">
        <span>☀️</span>
        <label class="switch">
            <input type="checkbox" id="themeToggle">
            <span class="slider"></span>
        </label>
        <span>🌙</span>
    </div>

    <div class="dashboard">
        <div class="sidebar">
            <img src="<?php echo $pic_path; ?>" class="profile-img" alt="Profile">
            <h3><?php echo htmlspecialchars($_SESSION['user_name']); ?></h3>
            <p style="font-size: 0.85em; opacity: 0.7;"><?php echo $_SESSION['user_email']; ?></p>
            <hr style="border:0; border-top:1px solid rgba(255,255,255,0.1); margin: 20px 0;">
            
            <a href="upanel.php" class="nav-btn active">📊 Dashboard</a>
            <a href="umannagefiles.php" class="nav-btn">📂 My Files</a>
            <a href="uprofile.php" class="nav-btn">⚙️ Settings</a>
            <a href="../upload.php" class="nav-btn">➕ Upload New</a>
            <a href="logout.php" class="nav-btn" style="color: var(--error-color);">🚪 Logout</a>
        </div>
        
        <div class="content">
            <h2 style="margin-top:0;">Welcome Back, <?php echo explode(' ', $_SESSION['user_name'])[0]; ?>!</h2>
            <p>Your document management hub is ready.</p>
            
            <div class="stat-box">
                <p style="margin:0; text-transform:uppercase; font-size:0.8em; letter-spacing:1px;">Active Files</p>
                <h1><?php echo $count; ?></h1>
                <a href="umannagefiles.php" style="color:white; font-size:0.8em; text-decoration:none; opacity:0.8;">View All →</a>
            </div>

            <div style="margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                <h3>Quick Tips</h3>
                <ul style="font-size: 0.9em; opacity: 0.8; line-height: 1.6;">
                    <li>Keep your PDF files under 50MB.</li>
                    <li>Update your profile picture in settings.</li>
                    <li>Use the "My Files" tab to delete, hide, copy link etc... documents.</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;

        function applyTheme(isDark) {
            if (isDark) {
                body.classList.add('dark-theme');
                localStorage.setItem('theme', 'dark');
            } else {
                body.classList.remove('dark-theme');
                localStorage.setItem('theme', 'light');
            }
        }

        // Initialize theme from storage
        const savedTheme = localStorage.getItem('theme') || 'dark';
        if (savedTheme === 'dark') {
            themeToggle.checked = true;
            applyTheme(true);
        } else {
            themeToggle.checked = false;
            applyTheme(false);
        }

        themeToggle.addEventListener('change', (e) => applyTheme(e.target.checked));
    </script>
</body>
</html>