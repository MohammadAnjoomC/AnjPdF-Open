<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user'])) {
    header("Location: ulogin.php");
    exit();
}

$email = $_SESSION['user'];
$userFile = "users/datas/$email.json";

// Load user data for header (Name and Profile Picture)
if (file_exists($userFile)) {
    $userData = json_decode(file_get_contents($userFile), true);
} else {
    die("User data not found.");
}

$message = ''; 
$upload_dir = './uploads/'; // Standardized to your requested directory
$max_file_size = 52428800; // 50 MB limit

// reCAPTCHA Keys (Using the keys from your provided code)
$recaptcha_site_key = "YOUR_RECAPTCHA_SITE_KEY";
$recaptcha_secret_key = "YOUR_RECAPTCHA_SECRET_KEY";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Logic for handling the upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file'])) {
    
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    $verify_url = "https://www.google.com/recaptcha/api/siteverify";
    $response = file_get_contents($verify_url . "?secret=" . $recaptcha_secret_key . "&response=" . $recaptcha_response);
    $response_data = json_decode($response);

    if (!$response_data->success) {
        $message = "🚫 Please complete the reCAPTCHA verification correctly.";
    } else {
        $file = $_FILES['pdf_file'];
        $file_name = preg_replace('/[^a-zA-Z0-9\-\.]/', '_', basename($file['name'])); 
        $target_file = $upload_dir . $file_name;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $message = "🚫 Upload failed. Error: " . $file['error'];
        } elseif (strtolower(pathinfo($file_name, PATHINFO_EXTENSION)) !== 'pdf') {
            $message = "🚫 Only PDF files are allowed.";
        } elseif ($file['size'] > $max_file_size) {
            $message = "🚫 File exceeds 50MB limit.";
        } elseif (file_exists($target_file)) {
            $message = "⚠️ A file with that name already exists.";
        } else {
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                // Update user JSON data to include the new file
                if (!isset($userData['files'])) { $userData['files'] = []; }
                $userData['files'][] = [
                    'name' => $file_name,
                    'hidden' => false,
                    'date' => date("Y-m-d H:i:s")
                ];
                file_put_contents($userFile, json_encode($userData));

                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $full_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/uploads/' . rawurlencode($file_name);
                
                $message = "✅ File uploaded successfully.<br><br>
                    <div class='url-container'>
                        <span id='fileUrl' class='url-text'>" . htmlspecialchars($full_url) . "</span>
                        <button class='copy-btn' onclick='copyUrl(\"fileUrl\")'>📋 Copy URL</button>
                    </div>";
            } else {
                $message = "🚫 Server error moving file.";
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
    <title>Upload PDF - SMPS</title>
    <link rel="stylesheet" href="style.css"> <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        /* Specific additions for this page */
        .user-badge { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; padding: 10px; background: rgba(255,255,255,0.05); border-radius: 10px; }
        .user-badge img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); }
        .url-container { background: #0f172a; padding: 10px; border-radius: 8px; margin-top: 10px; }
        .url-text { font-size: 0.8em; color: #94a3b8; word-break: break-all; }
        #progress-container { display: none; margin: 20px 0; }
        #progress-bar { width: 100%; background: #334155; height: 10px; border-radius: 5px; overflow: hidden; }
        #progress-bar-fill { height: 100%; width: 0%; background: var(--primary); transition: width 0.2s; }
    </style>
</head>
<body>

    <div class="container" style="max-width: 500px;">
        <div class="user-badge">
            <img src="users/img/<?php echo $userData['img']; ?>" alt="Profile">
            <div>
                <strong><?php echo htmlspecialchars($userData['name']); ?></strong><br>
                <small>User Dashboard</small>
            </div>
        </div>

        <div class="nav">
            <a href="upanel.php">Dashboard</a> | <a href="umanagefiles.php">My Files</a>
        </div>

        <h2>📤 Upload PDF</h2>

        <div id="php-message-area">
            <?php if (!empty($message)): ?>
                <div class="message" style="padding:15px; border-radius:8px; background:rgba(255,255,255,0.1); margin-bottom:15px;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="progress-container">
            <div id="progress-text">Uploading... 0%</div>
            <div id="progress-bar"><div id="progress-bar-fill"></div></div>
        </div>

        <form id="uploadForm" action="uupload.php" method="post" enctype="multipart/form-data">
            <label>Select PDF Document:</label>
            <input type="file" name="pdf_file" id="pdf_file" accept=".pdf" required>
            
            <div style="display:flex; justify-content:center; margin: 15px 0;">
                <div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_site_key; ?>"></div>
            </div>

            <button type="submit">Upload to Cloud</button>
        </form>

        <a href="upanel.php" style="display:block; text-align:center; margin-top:20px; color:#94a3b8; text-decoration:none;">← Back to Panel</a>
    </div>

    <script>
        // AJAX Progress Bar Logic
        const uploadForm = document.getElementById('uploadForm');
        const phpMessageArea = document.getElementById('php-message-area');
        const progressContainer = document.getElementById('progress-container');
        const progressBarFill = document.getElementById('progress-bar-fill');

        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (grecaptcha.getResponse() == "") {
                alert("Please complete the reCAPTCHA.");
                return;
            }

            phpMessageArea.innerHTML = '';
            uploadForm.style.display = 'none';
            progressContainer.style.display = 'block';

            const xhr = new XMLHttpRequest();
            const formData = new FormData(uploadForm);
            
            xhr.open('POST', 'uupload.php', true);
            
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percent = (e.loaded / e.total) * 100;
                    progressBarFill.style.width = percent + '%';
                    document.getElementById('progress-text').textContent = `Uploading... ${Math.floor(percent)}%`;
                }
            });

            xhr.onload = function() {
                // Return to normal view and update message
                progressContainer.style.display = 'none';
                uploadForm.style.display = 'block';
                const parser = new DOMParser();
                const doc = parser.parseFromString(xhr.responseText, 'text/html');
                const newMsg = doc.querySelector('#php-message-area');
                if (newMsg) phpMessageArea.innerHTML = newMsg.innerHTML;
                grecaptcha.reset();
            };

            xhr.send(formData);
        });

        function copyUrl(id) {
            const text = document.getElementById(id).innerText;
            navigator.clipboard.writeText(text).then(() => {
                alert("URL Copied!");
            });
        }
    </script>
</body>
</html>