<?php
// users/ulogin.php
require 'config.php';

if (is_logged_in()) redirect('upanel.php');

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    $users = get_json_data(USERS_DB_PATH);
    $user_found = false;

    foreach ($users as $u) {
        if ($u['email'] === $email) {
            if (password_verify($pass, $u['password'])) {
                $_SESSION['user_id'] = $u['id'];
                $_SESSION['user_name'] = $u['name'];
                $_SESSION['user_email'] = $u['email'];
                $_SESSION['user_role'] = $u['role'];
                $_SESSION['user_pic'] = $u['profile_pic'];
                redirect('upanel.php');
            } else {
                $msg = "<div class='alert alert-error'>Incorrect Password.</div>";
            }
            $user_found = true;
            break;
        }
    }
    if (!$user_found) $msg = "<div class='alert alert-error'>User not found.</div>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - AnjPdF</title>
    <?php echo $theme_css; ?>
</head>
<body>
    <div class="auth-container">
        <h2>🔑 Login</h2>
        <?php echo $msg; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <div style="text-align: right; margin-bottom: 10px; font-size: 0.9em;">
    <a href="forgotpassw.php" style="color: var(--primary-color); text-decoration: none;">Forgot Password?</a>
</div>
            <button type="submit">Login</button>
        </form>
        <div class="link-text">
            New here? <a href="usignup.php">Create Account</a>
        </div>
        <a href="../index.php" class="back-home">← Back to Home</a>
    </div>
</body>
</html>