<?php
    echo '<link rel="icon" type="image/png" href="/img/logo.png">';
$message = ''; 
$upload_dir = './pdf/'; 
$max_file_size = 52428800; // 50 MB limit.

// reCAPTCHA Keys
$recaptcha_site_key = "YOUR_RECAPTCHA_SITE_KEY";
$recaptcha_secret_key = "YOUR_RECAPTCHA_SECRET_KEY";

$terms_page = "terms_and_conditions.php";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file'])) {
    
    // --- 0. reCAPTCHA Verification ---
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    
    $verify_url = "https://www.google.com/recaptcha/api/siteverify";
    $response = file_get_contents($verify_url . "?secret=" . $recaptcha_secret_key . "&response=" . $recaptcha_response);
    $response_data = json_decode($response);

    if (!$response_data->success) {
        $message = "🚫 Please complete the reCAPTCHA verification correctly.";
    } else {
        // --- 1. Server-Side PHP Validation and Upload Logic ---
        $file = $_FILES['pdf_file'];
        $file_name = basename($file['name']);
        $file_name = preg_replace('/[^a-zA-Z0-9\-\.]/', '_', $file_name); 
        $target_file = $upload_dir . $file_name;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $message = "🚫 File upload failed with error code: " . $file['error'];
        }

        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if ($file_extension !== 'pdf') {
            $message = "🚫 Only PDF files are allowed.";
        }

        if ($file['size'] > $max_file_size) {
            $max_mb = $max_file_size / (1024 * 1024);
            $formatted_max_mb = number_format($max_mb, 1);
            $message = "🚫 File is too large. Maximum allowed size is {$formatted_max_mb} MB.";
        }

        if (file_exists($target_file)) {
            $message = "⚠️ Sorry, a file with that name already exists. Please rename your file and try again.";
        }

        if (empty($message)) {
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];
                $file_url_path = '/pdf/' . rawurlencode($file_name); 
                $full_url = $protocol . '://' . $host . $file_url_path;
                
                $message = "✅ The file **". htmlspecialchars($file_name) . "** has been uploaded successfully.<br><br>
                    <div class='url-container'>
                        <span id='fileUrl' class='url-text'>" . htmlspecialchars($full_url) . "</span>
                        <button class='copy-btn' onclick='copyUrl(\"fileUrl\")'>📋 Copy URL</button>
                    </div>";
            } else {
                $message = "🚫 Error uploading file. Check permissions.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AnjPdF - Upload PDF</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        /* [Keeping your original CSS variables and styles...] */
        :root {
            --primary-color: #176B87; --accent-color: #64CCC5; --background-color: #F8F9FA; 
            --card-background: #FFFFFF; --text-color: #212529; --light-text: #6C757D; 
            --hover-color: #0A4D68; --upload-color: #28A745; --success-color: #28A745;
            --error-color: #DC3545; --warning-color: #FFC107; --input-border: #DEE2E6;
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        body.dark-theme {
            --primary-color: #64CCC5; --accent-color: #176B87; --background-color: #1A1A2E; 
            --card-background: #232946; --text-color: #E9ECEF; --light-text: #A9B1D6; 
            --hover-color: #1A5E7F; --upload-color: #4CAF50; --success-color: #4CAF50;
            --error-color: #EF476F; --warning-color: #FFEB3B; --input-border: #3A4750;
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }
        .all-files-btn { background-color: var(--accent-color) !important; color: white !important; }
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 0; background-color: var(--background-color); color: var(--text-color); line-height: 1.6; transition: background-color 0.4s, color 0.4s; }
        .main-content { min-height: calc(100vh - 100px); padding-bottom: 40px; }
        #loader-wrapper { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: var(--card-background); z-index: 1000; display: flex; justify-content: center; align-items: center; transition: opacity 0.5s, visibility 0s 0.5s; visibility: visible; opacity: 1; }
        .loaded #loader-wrapper { visibility: hidden; opacity: 0; }
        #loader { width: 80px; height: 80px; position: relative; }
        #loader img { width: 100%; height: 100%; object-fit: contain; animation: pulse 1.5s infinite ease-in-out; }
        @keyframes pulse { 0% { transform: scale(0.95); opacity: 0.7; } 50% { transform: scale(1.05); opacity: 1; } 100% { transform: scale(0.95); opacity: 0.7; } }
        .container { max-width: 750px; margin: 40px auto; padding: 40px; background: var(--card-background); border-radius: 20px; box-shadow: var(--box-shadow); transition: 0.4s; visibility: hidden; }
        .loaded .container { visibility: visible; }
        .header { display: flex; flex-direction: column; align-items: center; padding-bottom: 30px; margin-bottom: 30px; border-bottom: 1px solid var(--input-border); }
        .header-top-row { display: flex; align-items: center; justify-content: space-between; width: 100%; margin-bottom: 15px; }
        .branding { display: flex; align-items: center; }
        .branding img { height: 50px; margin-right: 15px; }
        .branding h1 { margin: 0; font-size: 2.2em; color: var(--primary-color); font-weight: 900; letter-spacing: -1px; }
        .theme-switch-wrapper { display: flex; align-items: center; }
        .theme-switch { display: inline-block; height: 24px; position: relative; width: 50px; }
        .theme-switch input { display: none; }
        .slider { background-color: var(--input-border); bottom: 0; cursor: pointer; left: 0; position: absolute; right: 0; top: 0; transition: 0.4s; border-radius: 34px; }
        .slider:before { content: '☀️'; height: 18px; width: 18px; position: absolute; left: 3px; bottom: 3px; background-color: white; transition: 0.4s; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 12px; }
        input:checked + .slider { background-color: var(--primary-color); }
        input:checked + .slider:before { transform: translateX(26px); content: '🌙'; background-color: #212529; color: var(--primary-color); }
        .message { padding: 20px; border-radius: 12px; margin-bottom: 25px; font-weight: 600; border-left: 6px solid; white-space: pre-wrap; transition: 0.4s; }
        .message-success { background-color: var(--success-bg, #D4EDDA); color: var(--success-text, #155724); border-color: var(--success-color); }
        .dark-theme .message-success { --success-bg: #155724; --success-text: #D4EDDA; }
        .message-error { background-color: var(--error-bg, #F8D7DA); color: var(--error-text, #721C24); border-color: var(--error-color); }
        .dark-theme .message-error { --error-bg: #721C24; --error-text: #F8D7DA; }
        .url-container { display: flex; flex-direction: column; background-color: var(--background-color); border: 1px solid var(--input-border); border-radius: 10px; padding: 10px; margin-top: 15px; }
        .url-text { flex-grow: 1; padding: 5px 10px; word-break: break-all; color: var(--primary-color); font-family: monospace; }
        .copy-btn { background-color: var(--primary-color); color: white; padding: 10px 15px; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; margin-top: 10px; }
        #uploadForm { background: var(--background-color); padding: 30px; border-radius: 15px; border: 1px solid var(--input-border); }
        label { display: block; margin-bottom: 8px; font-weight: 700; }
        input[type="file"] { display: block; margin-bottom: 20px; padding: 12px; border: 2px solid var(--input-border); border-radius: 8px; width: 100%; box-sizing: border-box; background: var(--card-background); color: var(--text-color); }
        input[type="submit"] { background-color: var(--upload-color); color: white; padding: 15px 25px; border: none; border-radius: 10px; cursor: pointer; font-weight: 700; width: 100%; box-shadow: 0 4px 10px rgba(40,167,69,0.3); margin-top: 20px; }
        .back-btn { background-color: var(--light-text); color: white; padding: 12px 20px; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 30px; font-weight: 600; }
        #progress-container { display: none; margin-bottom: 25px; padding: 20px; background: var(--card-background); border-radius: 15px; border: 1px solid var(--input-border); }
        #progress-bar { width: 100%; background-color: var(--input-border); border-radius: 5px; height: 30px; margin-top: 10px; overflow: hidden; }
        #progress-bar-fill { height: 100%; width: 0%; background-color: var(--accent-color); text-align: center; line-height: 30px; font-weight: 700; }
        .footer { background-color: var(--card-background); padding: 25px 5%; margin-top: 50px; border-top: 1px solid var(--input-border); }
        .footer-content { max-width: 1080px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; font-size: 0.9em; color: var(--light-text); }
        .footer-link a { color: var(--primary-color); text-decoration: none; font-weight: 600; }
        
        /* Centering reCAPTCHA */
        .captcha-wrapper {
            display: flex;
            justify-content: center;
            margin-bottom: 15px;
        }

        @media (max-width: 600px) {
            .container { margin: 20px 15px; padding: 20px; }
            .footer-content { flex-direction: column; gap: 15px; }
        }
    </style>
</head>
<body class="light-theme">

    <div id="loader-wrapper">
        <div id="loader"><img src="img/logo.png" alt="AnjPdF Loading Logo"></div>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="header">
                <div class="header-top-row">
                    <div class="branding"><img src="img/logo.png" alt="AnjPdF Logo"><h1>AnjPdF</h1></div>
                    <div class="header-controls">
                        <div class="theme-switch-wrapper">
                            <?php if(isset($_SESSION['user_email'])): ?>
    <a href="users/upanel.php" class="all-files-btn" style="padding:8px 15px; border-radius:8px; text-decoration:none; text-align:center;">👤 My Panel</a>
<?php else: ?>
    <a href="users/ulogin.php" class="all-files-btn" style="padding:8px 15px; border-radius:8px; text-decoration:none; text-align:center;">🔑 Login</a>
<?php endif; ?>
                            <label class="theme-switch" for="checkbox">
                                <input type="checkbox" id="checkbox" />
                                <div class="slider round"></div>
                            </label>
                        </div>
                    </div>
                </div>
                <p class="sub-text">Your reliable Cloud PDF Hosting solution.</p>
            </div>

            <h1>📤 Upload a PDF</h1>

            <div id="php-message-area">
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo (strpos($message, '✅') !== false) ? 'message-success' : ((strpos($message, '⚠️') !== false) ? 'message-warning' : 'message-error'); ?>">
                        <?php echo str_replace('**', '', $message); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div id="progress-container">
                <div id="progress-text">Uploading... 0%</div>
                <div id="progress-bar"><div id="progress-bar-fill"></div></div>
            </div>

            <form id="uploadForm" action="upload.php" method="post" enctype="multipart/form-data">
                <label for="pdf_file">Select PDF (Max 50.0 MB):</label>
                <input type="file" name="pdf_file" id="pdf_file" accept=".pdf" required>
                
                <div class="captcha-wrapper">
                    <div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_site_key; ?>"></div>
                </div>

                <input type="submit" value="Upload PDF">
            </form>

            <a href="index.php" class="back-btn">← Back to Home Page</a>
        </div>
    </div>

    <div class="footer">
        <div class="footer-content">
            <img src="img/logo.png" alt="PDF Host Icon" style="height:40px">
            <p>&copy; <?php echo date("Y"); ?> AnjPdF - Host</p>
            <div class="footer-link-group">
                <a href="<?php echo $terms_page; ?>">Terms</a> | <a href="login.php">🔑 Admin</a>
            </div>
        </div>
    </div>

    <script>
        const LOADER_DURATION_MS = 800; 
        const themeCheckbox = document.getElementById('checkbox');
        
        function applyTheme(isDark) {
            document.body.classList.toggle('dark-theme', isDark);
            document.body.classList.toggle('light-theme', !isDark);
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }

        if (localStorage.getItem('theme') === 'dark') {
            themeCheckbox.checked = true;
            applyTheme(true);
        }

        themeCheckbox.addEventListener('change', (e) => applyTheme(e.target.checked));

        window.addEventListener('load', () => {
            setTimeout(() => document.body.classList.add('loaded'), LOADER_DURATION_MS);
        });

        function copyUrl(elementId) {
            const urlText = document.getElementById(elementId).innerText.trim();
            const textarea = document.createElement('textarea');
            textarea.value = urlText;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = '✅ Copied!';
            btn.style.backgroundColor = 'var(--success-color)';
            setTimeout(() => {
                btn.textContent = originalText;
                btn.style.backgroundColor = '';
            }, 2000);
        }

        // AJAX Upload Handler
        const uploadForm = document.getElementById('uploadForm');
        const phpMessageArea = document.getElementById('php-message-area');
        const progressContainer = document.getElementById('progress-container');
        const progressBarFill = document.getElementById('progress-bar-fill');

        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Check if reCAPTCHA is filled
            if (grecaptcha.getResponse() == "") {
                phpMessageArea.innerHTML = '<div class="message message-error">🚫 Please complete the reCAPTCHA.</div>';
                return;
            }

            phpMessageArea.innerHTML = '';
            uploadForm.style.display = 'none';
            progressContainer.style.display = 'block';

            const xhr = new XMLHttpRequest();
            const formData = new FormData(uploadForm);
            
            xhr.open('POST', 'upload.php', true);
            
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percent = (e.loaded / e.total) * 100;
                    progressBarFill.style.width = percent + '%';
                    document.getElementById('progress-text').textContent = `Uploading... ${Math.floor(percent)}%`;
                }
            });

            xhr.onload = function() {
                setTimeout(() => {
                    progressContainer.style.display = 'none';
                    uploadForm.style.display = 'block';
                    
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(xhr.responseText, 'text/html');
                    const newMsg = doc.querySelector('#php-message-area');
                    
                    if (newMsg) phpMessageArea.innerHTML = newMsg.innerHTML;
                    
                    // Reset reCAPTCHA for next attempt
                    grecaptcha.reset();
                }, 800);
            };

            xhr.send(formData);
        });
    </script>
</body>
</html>