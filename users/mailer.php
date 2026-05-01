<?php
/**
 * users/mailer.php
 * Handles SMTP email logic using PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/libs/PHPMailer/src/Exception.php';
require __DIR__ . '/libs/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/libs/PHPMailer/src/SMTP.php';

function send_reset_link_email($to, $name, $link) {
    $mail = new PHPMailer(true);
    try {
        // --- SERVER SETTINGS ---
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Uncomment this line to see exact errors
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your-email@gmail.com';
        $mail->Password   = 'your app password'; // App Password
        
        // Try SSL on 465 if TLS on 587 fails
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465; 

        // --- RECIPIENTS ---
        $mail->setFrom('your-email@gmail.com', 'AnjPdF Security');
        $mail->addAddress($to, $name);

        // --- CONTENT ---
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; background: #f4f4f4;'>
                <div style='max-width: 500px; margin: auto; background: white; padding: 30px; border-radius: 10px; border: 1px solid #ddd;'>
                    <h2 style='color: #176B87; text-align: center;'>AnjPdF Password Reset</h2>
                    <p>Hi $name,</p>
                    <p>Click the button below to set a new password. This link is valid for 30 minutes.</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$link' style='background: #176B87; color: white; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>Reset Password</a>
                    </div>
                    <p style='word-break: break-all; font-size: 0.8em; color: #666;'>If the button doesn't work, copy this: $link</p>
                </div>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // If it fails, you can check $mail->ErrorInfo
        return false;
    }
}

// Keep your existing registration function here as well
function send_otp_email($to, $name, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your-email@gmail.com';
        $mail->Password   = 'your app password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('your-email@gmail.com', 'AnjPdF Security');
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Verify your AnjPdF Account';

        // Stylish HTML Body
        $mail->Body = "
        <div style='font-family: Helvetica, Arial, sans-serif; min-width: 1000px; overflow: auto; line-height: 2'>
          <div style='margin: 50px auto; width: 70%; padding: 20px 0'>
            <div style='border-bottom: 1px solid #eee'>
              <a href='' style='font-size: 1.4em; color: #176B87; text-decoration: none; font-weight: 600'>AnjPdF</a>
            </div>
            <p style='font-size: 1.1em'>Hi $name,</p>
            <p>Thank you for choosing AnjPdF. Use the following OTP to complete your Sign Up procedures. OTP is valid for 10 minutes</p>
            <h2 style='background: #176B87; margin: 0 auto; width: max-content; padding: 0 10px; color: #fff; border-radius: 4px;'>$otp</h2>
            <p style='font-size: 0.9em;'>Regards,<br />AnjPdF Security Team</p>
            <hr style='border: none; border-top: 1px solid #eee' />
            <div style='float: right; padding: 8px 0; color: #aaa; font-size: 0.8em; line-height: 1; font-weight: 300'>
              <p>AnjPdF Inc</p>
              <p>Secure Document Management</p>
            </div>
          </div>
        </div>";

        $mail->send();
        return true;
    } catch (Exception $e) { 
        return false; 
    }
}
?>