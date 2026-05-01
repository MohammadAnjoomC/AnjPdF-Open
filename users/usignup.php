<?php
// users/usignup.php
require 'config.php';
require 'mailer.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $cpass = $_POST['cpassword'];

    $users = get_json_data(USERS_DB_PATH);
    
    // Check if email exists
    $email_exists = false;
    foreach ($users as $u) {
        if ($u['email'] === $email) { $email_exists = true; break; }
    }

    if ($email_exists) {
        $msg = "<div class='alert alert-error'>Email already registered.</div>";
    } elseif ($pass !== $cpass) {
        $msg = "<div class='alert alert-error'>Passwords do not match.</div>";
    } else {
        // Generate OTP
        $otp = rand(100000, 999999);
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        // Save to pending
        $pending = get_json_data(PENDING_DB_PATH);
        // Remove old pending for same email
        $pending = array_filter($pending, function($p) use ($email) { return $p['email'] !== $email; });
        
        $pending[] = [
            'name' => $name,
            'email' => $email,
            'password' => $hash,
            'otp' => $otp,
            'expires' => time() + 600 // 10 mins
        ];
        save_json_data(PENDING_DB_PATH, array_values($pending));

        if (send_otp_email($email, $name, $otp)) {
            $_SESSION['verify_email'] = $email;
            header("Location: verify.php");
            exit();
        } else {
            $msg = "<div class='alert alert-error'>Failed to send OTP.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - AnjPdF</title>
    <?php echo $theme_css; ?>
</head>
<body>
    <div class="auth-container">
        <h2>📝 Create Account</h2>
        <?php echo $msg; ?>
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="cpassword" placeholder="Confirm Password" required>
            <button type="submit">Send OTP</button>
        </form>
        <div class="link-text">
            Already have an account? <a href="ulogin.php">Login here</a>
        </div>
        <a href="../index.php" class="back-home">← Back to Home</a>
    </div>
</body>
</html>