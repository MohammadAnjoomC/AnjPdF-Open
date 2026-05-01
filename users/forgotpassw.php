<?php
require 'config.php';
require 'mailer.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $users = get_json_data(USERS_DB_PATH);
    $user_found = false;
    $user_name = '';

    foreach ($users as $u) {
        if ($u['email'] === $email) {
            $user_found = true;
            $user_name = $u['name'];
            break;
        }
    }

    if ($user_found) {
        $token = bin2hex(random_bytes(32));
        $resets = get_json_data(RESET_DB_PATH);
        
        // Filter out old requests
        $resets = array_filter($resets, function($r) use ($email) { 
            return isset($r['email']) && $r['email'] !== $email; 
        });
        
        $resets[] = [
            'email' => $email,
            'token' => $token,
            'expires' => time() + 1800 
        ];
        save_json_data(RESET_DB_PATH, array_values($resets));

        // Generate absolute URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $link = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/resetpass.php?token=" . $token;

        if (send_reset_link_email($email, $user_name, $link)) {
            $msg = "<div class='alert alert-success'>Check your email! The reset link has been sent.</div>";
        } else {
            $msg = "<div class='alert alert-error'>Mail failed to send. Check SMTP settings or Spam folder.</div>";
        }
    } else {
        $msg = "<div class='alert alert-error'>No account registered with this email.</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <?php echo $theme_css; ?>
</head>
<body>
    <div class="auth-container">
        <h2>🔄 Reset Password</h2>
        <?php echo $msg; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit">Send Reset Link</button>
        </form>
        <div class="link-text"><a href="ulogin.php">Back to Login</a></div>
    </div>
</body>
</html>