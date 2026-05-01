<?php
    if (!is_dir('../quarantine/')) mkdir('../quarantine/', 0755, true);
// users/config.php
session_start();

// Paths
define('USER_DATA_DIR', __DIR__ . '/datas/');
define('USER_IMG_DIR', __DIR__ . '/img/');
define('PDF_DIR', dirname(__DIR__) . '/pdf/');
define('FILE_META_PATH', USER_DATA_DIR . 'file_ownership.json');
define('USERS_DB_PATH', USER_DATA_DIR . 'users.json');
define('PENDING_DB_PATH', USER_DATA_DIR . 'pending_users.json');
// Add this line under your other define() statements
define('RESET_DB_PATH', USER_DATA_DIR . 'password_resets.json');
define('QUARANTINE_DIR', '../quarantine/');

// Ensure directories exist
if (!is_dir(USER_DATA_DIR)) mkdir(USER_DATA_DIR, 0755, true);
if (!is_dir(USER_IMG_DIR)) mkdir(USER_IMG_DIR, 0755, true);

// Protect data directory
if (!file_exists(USER_DATA_DIR . '.htaccess')) {
    file_put_contents(USER_DATA_DIR . '.htaccess', "Deny from all");
}

// SMTP Settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'hourctws@gmail.com');
define('SMTP_PASS', 'ohxx skrc lymp cfzo');
define('SMTP_PORT', 587);

// --- HELPER FUNCTIONS ---

function get_json_data($path) {
    if (!file_exists($path)) return [];
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function save_json_data($path, $data) {
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
}

function is_logged_in() {
    return isset($_SESSION['user_email']);
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

// Common Theme CSS to be included in head
$theme_css = '
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
<style>
    :root {
        --primary-color: #176B87; --accent-color: #64CCC5; --background-color: #F8F9FA;
        --card-background: #FFFFFF; --text-color: #212529; --error-color: #DC3545; --success-color: #28A745;
    }
    body.dark-theme {
        --primary-color: #64CCC5; --accent-color: #176B87; --background-color: #1A1A2E;
        --card-background: #232946; --text-color: #E9ECEF;
    }
    body { font-family: "Inter", sans-serif; background: var(--background-color); color: var(--text-color); margin: 0; padding: 0; display:flex; justify-content:center; align-items:center; min-height:100vh; }
    .auth-container { background: var(--card-background); padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
    h2 { color: var(--primary-color); text-align: center; margin-bottom: 20px; }
    input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
    button { width: 100%; padding: 12px; background: var(--primary-color); color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; }
    button:hover { background: var(--accent-color); }
    .alert { padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.9em; text-align: center; }
    .alert-error { background: #F8D7DA; color: #721C24; }
    .alert-success { background: #D4EDDA; color: #155724; }
    .link-text { text-align: center; margin-top: 15px; font-size: 0.9em; }
    .link-text a { color: var(--primary-color); text-decoration: none; font-weight: 600; }
    .back-home { display: block; text-align: center; margin-top: 20px; color: var(--text-color); text-decoration: none; font-size: 0.8em; }
</style>
';
?>