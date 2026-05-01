<?php
session_start();
// Security: Redirect if not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Backend handler for the upload test
if (isset($_GET['action']) && $_GET['action'] == 'upload') {
    // Simply receive the data and discard it to measure speed
    $data = file_get_contents('php://input');
    echo strlen($data);
    exit;
}

// Backend handler for the download test (generates 10MB of dummy data)
if (isset($_GET['action']) && $_GET['action'] == 'download') {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="test.bin"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Output 10MB of random data
    $chunkSize = 1024 * 1024; // 1MB chunks
    for ($i = 0; $i < 10; $i++) {
        echo str_repeat('0', $chunkSize);
        flush();
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AnjPdF - Real Speed Test</title>
    <link rel="icon" type="image/png" href="/img/logo.png">
    <style>
        /* Reusing login.php CSS Variables & Themes */
        :root {
            --primary-color: #007BFF;
            --background-color: #F8F9FA;
            --card-background: #FFFFFF;
            --text-color: #343A40;
            --hover-color: #0056B3;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        /* 3D Loader Animation from login.php */
        #loader-wrapper {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: var(--card-background);
            z-index: 1000;
            display: flex; justify-content: center; align-items: center;
            transition: opacity 0.5s, visibility 0s 0.5s;
        }
        .loaded #loader-wrapper { visibility: hidden; opacity: 0; }
        #loader { width: 80px; height: 80px; animation: flip3D 1.5s ease-in-out infinite; transform-style: preserve-3d; }
        #loader img { width: 100%; height: 100%; }
        @keyframes flip3D {
            0% { transform: rotateY(0deg); }
            100% { transform: rotateY(360deg); }
        }

        .container {
            background: var(--card-background);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            width: 100%;
            max-width: 400px;
        }

        .branding img { width: 60px; margin-bottom: 10px; }
        .branding h2 { margin: 0; color: var(--primary-color); }

        .speed-display { margin: 30px 0; }
        #main-speed { font-size: 4rem; font-weight: bold; margin: 0; }
        .unit { color: #888; font-size: 1.2rem; }

        .stats {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .stat-box span { display: block; font-size: 0.9rem; color: #666; }
        .stat-box strong { font-size: 1.2rem; color: var(--primary-color); }

        .btn-test {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 30px;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            width: 100%;
            margin-top: 20px;
        }

        .btn-test:hover { background: var(--hover-color); }
        .btn-test:disabled { background: #ccc; cursor: not-allowed; }

        /* Theme Toggle Button Styling */
        .btn-theme-toggle {
            background: #6c757d; /* Grey button */
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 15px; /* Spacing from other elements */
        }
        .btn-theme-toggle:hover { background: #5a6268; }

        /* Dark Theme Variables */
        body.dark-theme {
            --primary-color: #66B3FF; /* Lighter blue for dark theme */
            --background-color: #2C2C2C; /* Dark background */
            --card-background: #3A3A3A; /* Darker card background */
            --text-color: #E0E0E0; /* Light text */
            --hover-color: #4DA6FF; /* Lighter hover */
        }
    </style>
</head>
<body>

    <div id="loader-wrapper">
        <div id="loader"><img src="img/logo.png" alt="Logo"></div>
    </div>

    <div class="container">
        <div class="branding">
            <img src="img/logo.png" alt="AnjPdF">
            <h2>System Speed</h2>
        </div>

        <div class="speed-display">
            <p id="status">Ready to test</p>
            <h1 id="main-speed">0.0</h1>
            <span class="unit">Mbps</span>
        </div>

        <div class="stats">
            <div class="stat-box">
                <span>PING</span>
                <strong id="ping-val">--</strong> <small>ms</small>
            </div>
            <div class="stat-box">
                <span>UPLOAD</span>
                <strong id="upload-val">--</strong> <small>Mbps</small>
            </div>
        </div>

        <button class="btn-test" id="start-btn" onclick="startTest()">Run Speed Test</button>
        <button class="btn-theme-toggle" onclick="toggleTheme()">Toggle Dark/Light Theme</button>
        <a href="adminfilemannage.php" style="display:block; margin-top:15px; text-decoration:none; color: #666; font-size: 0.8rem;">← Return to Admin</a>
    </div>

    <script>
        // Page Loader
        window.addEventListener('load', () => {
            setTimeout(() => document.body.classList.add('loaded'), 500); // Reduced timeout for faster loading
        });

        // Theme Toggler
        function toggleTheme() {
            document.body.classList.toggle('dark-theme');
            localStorage.setItem('theme', document.body.classList.contains('dark-theme') ? 'dark' : 'light');
        }

        // Apply saved theme on load
        window.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('theme') === 'dark') {
                document.body.classList.add('dark-theme');
            }
        });

        async function startTest() {
            const btn = document.getElementById('start-btn');
            const status = document.getElementById('status');
            const mainSpeed = document.getElementById('main-speed');
            const pingVal = document.getElementById('ping-val');
            const uploadVal = document.getElementById('upload-val');

            btn.disabled = true;
            btn.innerText = "Testing...";

            // 1. PING TEST
            status.innerText = "Checking Latency...";
            const startPing = Date.now();
            await fetch('speedtest.php?action=upload', { method: 'POST', body: 'ping' });
            pingVal.innerText = Date.now() - startPing;

            // 2. DOWNLOAD TEST (10MB)
            status.innerText = "Testing Download...";
            const startDown = Date.now();
            const response = await fetch('speedtest.php?action=download');
            const reader = response.body.getReader();
            let receivedLength = 0;
            
            while(true) {
                const {done, value} = await reader.read();
                if (done) break;
                receivedLength += value.length;
                const duration = (Date.now() - startDown) / 1000;
                const bps = (receivedLength * 8) / duration;
                const mbps = (bps / 1048576).toFixed(1);
                mainSpeed.innerText = mbps;
            }

            // 3. UPLOAD TEST (1MB generated client-side)
            status.innerText = "Testing Upload...";
            const uploadData = new Blob([new Uint8Array(1024 * 1024 * 2)]); // 2MB dummy file
            const startUp = Date.now();
            await fetch('speedtest.php?action=upload', { method: 'POST', body: uploadData });
            const upDuration = (Date.now() - startUp) / 1000;
            const upMbps = ((uploadData.size * 8) / upDuration / 1048576).toFixed(1);
            uploadVal.innerText = upMbps;

            status.innerText = "Test Complete";
            btn.disabled = false;
            btn.innerText = "Restart Test";
        }
    </script>
</body>
</html>