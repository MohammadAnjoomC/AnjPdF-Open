<?php
    echo '<link rel="icon" type="image/png" href="/img/logo.png">';
// Set the directory for storing contact submissions (relative to the script)
$submissions_file = './submissions/contact_submissions.txt';

// Ensure the submissions directory exists
if (!is_dir('./submissions')) {
    mkdir('./submissions', 0755, true);
}

$message_status = ''; // Used to display success or error messages

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic sanitization and validation
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $filename = trim($_POST['filename'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Simple validation
    if (empty($name) || empty($email) || empty($type) || empty($message)) {
        $message_status = '<p class="error-message">🚫 Please fill in all required fields.</p>';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message_status = '<p class="error-message">🚫 Please enter a valid email address.</p>';
    } else {
        // Prepare the submission data string
        $submission_data = json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'Pending', // New submissions are pending
            'name' => $name,
            'email' => $email,
            'type' => $type,
            'filename' => empty($filename) ? 'N/A' : $filename,
            'message' => $message
        ]) . "\n";

        // Append the data to the file
        if (file_put_contents($submissions_file, $submission_data, FILE_APPEND | LOCK_EX) !== false) {
            $message_status = '<p class="success-message">🎉 Your submission has been sent successfully!</p>';
        } else {
            $message_status = '<p class="error-message">🚫 There was an error saving your submission. Please try again.</p>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AnjPdF - Contact Us</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <style>
        /* ----------------------- THEME VARIABLES ----------------------- */
        :root {
            /* Light Theme Defaults */
            --primary-color: #176B87; /* Deep Blue/Teal */
            --accent-color: #64CCC5; /* Lighter Teal/Cyan */
            --background-color: #F8F9FA; /* Very light grey */
            --card-background: #FFFFFF; /* Pure white card */
            --text-color: #212529; /* Dark text */
            --light-text: #6C757D; /* Grey metadata/url text */
            --hover-color: #0A4D68;
            --upload-color: #28A745; 
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --card-hover-bg: #E9ECEF;
            --input-border: #DEE2E6;
        }

        /* Dark Theme Overrides */
        body.dark-theme {
            --primary-color: #64CCC5; /* Accent color becomes primary */
            --accent-color: #176B87;
            --background-color: #1A1A2E; /* Deep navy/purple-ish dark */
            --card-background: #232946; /* Slightly lighter card background */
            --text-color: #E9ECEF; /* Light text */
            --light-text: #A9B1D6; /* Lighter grey for metadata */
            --hover-color: #1A5E7F;
            --upload-color: #4CAF50; /* Adjust green for dark contrast */
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            --card-hover-bg: #2B3352;
            --input-border: #3A4750;
        }

        /* ----------------------- BASE STYLES & LAYOUT ----------------------- */
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            transition: background-color 0.4s, color 0.4s;
            display: flex; /* Centering */
            flex-direction: column;
            min-height: 100vh;
        }
        
        .main-content {
            flex-grow: 1; /* Allows content to take up available space */
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Start content near the top */
            padding-top: 40px; 
            padding-bottom: 40px;
        }

        .container {
            width: 100%;
            max-width: 700px; /* Reduced max-width for a form */
            margin: 0 auto;
            padding: 40px; 
            background: var(--card-background);
            border-radius: 20px; 
            box-shadow: var(--box-shadow);
            transition: box-shadow 0.4s, background-color 0.4s;
        }

        h2 {
            color: var(--primary-color);
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 1.8em;
            font-weight: 800;
            text-align: center;
        }
        
        /* Message Styles */
        .error-message {
            color: #DC3545;
            background-color: #F8D7DA;
            border: 1px solid #F5C6CB;
            padding: 10px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 20px;
        }

        .success-message {
            color: #155724;
            background-color: #D4EDDA;
            border: 1px solid #C3E6CB;
            padding: 10px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 20px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--input-border);
            border-radius: 8px;
            box-sizing: border-box;
            background-color: var(--card-hover-bg);
            color: var(--text-color);
            transition: border-color 0.3s, background-color 0.3s;
        }
        
        .form-group select {
            /* Fix for select arrow on some browsers */
            appearance: none; 
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%236C757D%22%20d%3D%22M287%20197.8L153.2%2064.1c-4.5-4.5-11.7-4.5-16.2%200L5.4%20197.8c-4.5%204.5-4.5%2011.7%200%2016.2s11.7%204.5%2016.2%200l123.8-123.8%20123.8%20123.8c4.5%204.5%2011.7%204.5%2016.2%200s4.6-11.7%200-16.2z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 12px top 50%;
            background-size: 0.65em auto;
            padding-right: 30px; /* Make space for the icon */
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(23, 107, 135, 0.2);
            outline: none;
        }

        .submit-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 700;
            transition: background-color 0.3s, transform 0.2s;
            margin-top: 30px;
        }

        .submit-btn:hover {
            background-color: var(--hover-color);
            transform: translateY(-2px);
        }
        
        /* ----------------------- FOOTER STYLES (Minimal) ----------------------- */
        .footer {
            background-color: var(--card-background);
            padding: 25px 5%;
            margin-top: auto; /* Push footer to the bottom */
            border-top: 1px solid var(--input-border);
            transition: background-color 0.4s;
        }
        
        .footer-content {
            max-width: 1080px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9em;
            color: var(--light-text);
        }

        .footer-logo-icon img {
            height: 40px;
            width: auto;
            object-fit: contain;
        }

        .footer-link-group {
            display: flex;
            gap: 20px;
        }

        .footer-link a, .footer-admin-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .footer-link a:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }

        /* ----------------------- RESPONSIVE DESIGN ----------------------- */
        @media (max-width: 768px) {
            .container {
                margin: 0 15px;
                padding: 20px;
            }
            .footer-content {
                flex-direction: column;
                gap: 20px;
            }
        }
    </style>
</head>
<body class="light-theme">

    <div class="main-content">
        <div class="container">
            <h2>📢 Contact AnjPdF</h2>
            <p style="text-align: center; color: var(--light-text); margin-bottom: 30px;">
                We value your feedback! Use the form below to submit a suggestion, review, complaint, or report a file.
            </p>
            
            <?php echo $message_status; ?>

            <form action="contact.php" method="post">
                <div class="form-group">
                    <label for="name">Your Name/Alias:</label>
                    <input type="text" id="name" name="name" required placeholder="e.g., Jane Doe">
                </div>
                <div class="form-group">
                    <label for="email">Your Email (Optional for reply):</label>
                    <input type="email" id="email" name="email" required placeholder="e.g., example@mail.com">
                </div>
                <div class="form-group">
                    <label for="type">Submission Type:</label>
                    <select id="type" name="type" required>
                        <option value="">-- Select Type --</option>
                        <option value="Suggestion">Suggestion for Improvement</option>
                        <option value="Review">Service Review/Feedback</option>
                        <option value="Complaint">Complaint</option>
                        <option value="File Report">Report a File (DMCA/Abuse)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filename">File Name (Required for 'File Report'):</label>
                    <input type="text" id="filename" name="filename" placeholder="e.g., dangerous_file.pdf">
                </div>
                <div class="form-group">
                    <label for="message">Message/Details:</label>
                    <textarea id="message" name="message" required placeholder="Please provide detailed information..."></textarea>
                </div>

                <button type="submit" class="submit-btn">📩 Send Submission</button>
                <p style="text-align: center; margin-top: 20px; font-size: 0.9em;">
                    <a href="index.php" style="color: var(--primary-color); text-decoration: none;">← Back to Home</a>
                </p>
            </form>
        </div>
    </div>

    <div class="footer">
        <div class="footer-content">
            <p>&copy; <?php echo date("Y"); ?> AnjPdF - Host</p>
            <div class="footer-link-group">
                <p class="footer-link"><a href="terms_and_conditions.php">Terms & Conditions</a></p>
                <p class="footer-link"><a href="solved.php">Solved Submissions</a></p>
                <p class="footer-link"><a href="login.php">🔑 Admin Login</a></p>
            </div>
        </div>
    </div>
    <script>
        // Simple client-side theme application from local storage
        const body = document.body;
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            body.classList.add('dark-theme');
            body.classList.remove('light-theme');
        } else {
            body.classList.add('light-theme');
            body.classList.remove('dark-theme');
        }
    </script>

</body>
</html>