<?php
require 'config.php';
require 'mailer.php';

if (!isset($_SESSION['verify_email'])) {
    header("Location: usignup.php");
    exit();
}

$msg = '';
$email = $_SESSION['verify_email'];

// --- HANDLE RESEND OTP ---
if (isset($_GET['action']) && $_GET['action'] === 'resend') {
    $pending = get_json_data(PENDING_DB_PATH);
    $found = false;

    foreach ($pending as &$p) {
        if ($p['email'] === $email) {
            $new_otp = rand(100000, 999999);
            $p['otp'] = $new_otp;
            $p['expires'] = time() + 600; // 10 minutes
            
            if (send_otp_email($email, $p['name'], $new_otp)) {
                // Set a session message so it survives the redirect
                $_SESSION['resend_msg'] = "<div class='alert alert-success'>A new stylish OTP has been sent!</div>";
            } else {
                $_SESSION['resend_msg'] = "<div class='alert alert-error'>Mailer error. Check SMTP settings.</div>";
            }
            $found = true;
            break;
        }
    }
    if ($found) {
        save_json_data(PENDING_DB_PATH, array_values($pending));
    }
    
    // REDIRECT back to verify.php to clear the "?action=resend" from the URL
    header("Location: verify.php");
    exit();
}

// Check if there is a resend message waiting in the session
if (isset($_SESSION['resend_msg'])) {
    $msg = $_SESSION['resend_msg'];
    unset($_SESSION['resend_msg']); // Clear it after showing
}

// --- HANDLE VERIFY POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $otp_input = trim($_POST['otp']); 
    $pending = get_json_data(PENDING_DB_PATH);
    $users = get_json_data(USERS_DB_PATH);
    
    $is_verified = false;
    $user_to_move = null;
    $remaining_pending = [];

    foreach ($pending as $p) {
        if ($p['email'] === $email) {
            if ((string)$p['otp'] === (string)$otp_input) {
                if (time() <= $p['expires']) {
                    $is_verified = true;
                    $user_to_move = $p;
                    continue; 
                } else {
                    $msg = "<div class='alert alert-error'>OTP Expired. Please Resend.</div>";
                }
            }
        }
        $remaining_pending[] = $p;
    }

    if ($is_verified) {
        $role = (count($users) === 0) ? 'admin' : 'user';
        $users[] = [
            'id' => uniqid(),
            'name' => $user_to_move['name'],
            'email' => $user_to_move['email'],
            'password' => $user_to_move['password'],
            'role' => $role,
            'profile_pic' => 'default.png',
            'joined' => time()
        ];
        save_json_data(USERS_DB_PATH, $users);
        save_json_data(PENDING_DB_PATH, $remaining_pending);
        unset($_SESSION['verify_email']);
        echo "<script>alert('Verified Successfully!'); window.location.href='ulogin.php';</script>";
        exit();
    } elseif (empty($msg)) {
        $msg = "<div class='alert alert-error'>Invalid OTP code. Please try again.</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Account - AnjPdF</title>
    <?php echo $theme_css; ?>
</head>
<body>
    <div class="auth-container">
        <h2>🔐 Verify Email</h2>
        <p style="text-align:center; font-size:0.9em; margin-bottom:15px;">
            OTP sent to <b><?php echo htmlspecialchars($email); ?></b>
        </p>
        
        <?php echo $msg; ?>
        
        <form method="POST">
            <input type="number" name="otp" placeholder="6-Digit OTP" required>
            <button type="submit">Verify Now</button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <p style="font-size:0.85em; color:#666;">Didn't get the code?</p>
            <a href="verify.php?action=resend" onclick="this.innerHTML='Sending...'; this.style.opacity='0.5';" style="color: #176B87; text-decoration: none; font-weight: bold;">Resend OTP</a>
        </div>
    </div>
</body>
</html>