<?php
require 'config.php';

$msg = '';
$token = $_GET['token'] ?? '';
$email_to_reset = '';
$token_valid = false;

if (!empty($token)) {
    $resets = get_json_data(RESET_DB_PATH);
    foreach ($resets as $r) {
        if ($r['token'] === $token && time() < $r['expires']) {
            $token_valid = true;
            $email_to_reset = $r['email'];
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $pass = $_POST['password'];
    $cpass = $_POST['cpassword'];

    if ($pass === $cpass && strlen($pass) >= 6) {
        $users = get_json_data(USERS_DB_PATH);
        foreach ($users as &$u) {
            if ($u['email'] === $email_to_reset) {
                $u['password'] = password_hash($pass, PASSWORD_DEFAULT);
                break;
            }
        }
        save_json_data(USERS_DB_PATH, $users);

        // Clear tokens
        $resets = array_filter($resets, function($r) use ($token) { return $r['token'] !== $token; });
        save_json_data(RESET_DB_PATH, array_values($resets));

        echo "<script>alert('Password updated! Please login.'); window.location.href='ulogin.php';</script>";
        exit();
    } else {
        $msg = "<div class='alert alert-error'>Passwords must match and be at least 6 characters.</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Set New Password</title>
    <?php echo $theme_css; ?>
</head>
<body>
    <div class="auth-container">
        <h2>🔐 New Password</h2>
        <?php if ($token_valid): ?>
            <?php echo $msg; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="New Password" required>
                <input type="password" name="cpassword" placeholder="Confirm New Password" required>
                <button type="submit">Update Password</button>
            </form>
        <?php else: ?>
            <div class='alert alert-error'>This link is invalid or has expired.</div>
            <div class="link-text"><a href="forgotpassw.php">Try again</a></div>
        <?php endif; ?>
    </div>
</body>
</html>