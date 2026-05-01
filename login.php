<?php
    echo '<link rel="icon" type="image/png" href="/img/logo.png">';
session_start();

$username = 'YOUR ADMIN USER NAME';
$password = 'YOUR ADMIN PASSWORD';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_username = $_POST['username'] ?? '';
    $input_password = $_POST['password'] ?? '';

    // Simple check against hardcoded credentials
    if ($input_username === $username && $input_password === $password) {
        // Login successful
        $_SESSION['admin_logged_in'] = true;
        header('Location: adminfilemannage.php');
        exit;
    } else {
        $error_message = "🚫 Invalid Username or Password.";
    }
}

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: adminfilemannage.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AnjPdF - Admin Login</title>
    <style>
        /* Shared Style from index.php (Minimal version for login) */
        :root {
            --primary-color: #007BFF;
            --background-color: #F8F9FA;
            --card-background: #FFFFFF;
            --text-color: #343A40;
            --hover-color: #0056B3;
        }

        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        /* ----------------------- LOADER STYLES ----------------------- */
        /* Wrapper covers the entire screen and controls fade/visibility */
        #loader-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--card-background); /* White background for loader */
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.5s, visibility 0s 0.5s; /* Fade out effect */
            visibility: visible;
            opacity: 1;
        }

        /* Hides the wrapper after loading */
        .loaded #loader-wrapper {
            visibility: hidden;
            opacity: 0;
            transition: opacity 0.5s, visibility 0s 0.5s; 
        }

        /* Loader (The Logo Container) */
        #loader {
            width: 100px;
            height: 100px;
            /* Animation duration set for smooth loop, not controlling page load time */
            animation: flip3D 1.3s ease-in-out infinite; 
            transform-style: preserve-3d;
            perspective: 600px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        /* The actual logo image */
        #loader img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* 3D Premium Animation Keyframes (X and Y rotation) */
        @keyframes flip3D {
            0% {
                transform: rotateX(0deg) rotateY(0deg) scale(1);
            }
            50% {
                transform: rotateX(180deg) rotateY(180deg) scale(1.1);
            }
            100% {
                transform: rotateX(360deg) rotateY(360deg) scale(1);
            }
        }
        /* ------------------- END LOADER STYLES ----------------------- */

        .login-box {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            background: var(--card-background);
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            text-align: center;
            /* Initially hide the content until the page is "loaded" */
            visibility: hidden; 
        }
        
        /* Makes main content visible once the loader is gone */
        .loaded .login-box {
            visibility: visible;
        }

        .branding img {
            height: 40px; 
            margin-bottom: 15px;
        }

        .branding h2 {
            margin-top: 0;
            font-size: 1.8em;
            color: var(--primary-color);
            font-weight: 700;
        }
        
        .error-message {
            color: #DC3545;
            font-weight: bold;
            margin-bottom: 15px;
        }

        form label {
            display: block;
            text-align: left;
            margin-top: 10px;
            font-weight: 600;
        }

        form input[type="text"],
        form input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #CED4DA;
            border-radius: 4px;
            box-sizing: border-box;
        }

        form input[type="submit"] {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
            width: 100%;
            margin-top: 10px;
        }

        form input[type="submit"]:hover {
            background-color: var(--hover-color);
        }

        .back-btn {
            display: inline-block;
            margin-top: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .back-btn:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div id="loader-wrapper">
        <div id="loader">
            <img src="img/logo.png" alt="AnjPdF Loading Logo">
        </div>
    </div>
    <div class="login-box">
        <div class="branding">
            <img src="img/logo.png" alt="AnjPdF Logo">
            <h2>Admin Login</h2>
        </div>
        <p>🔑 Enter Credentials to Manage Files</p>

        <?php if (!empty($error_message)): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <form action="login.php" method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" value="Login">
        </form>

        <a href="index.php" class="back-btn">← Back to Home</a>
    </div>

    <script>
        // Set the duration for the animation to 1.5 seconds (1500ms)
        const LOADER_DURATION_MS = 1500; 

        // Loader Logic
        window.addEventListener('load', () => {
            // Wait for 1.5 seconds before adding the 'loaded' class to hide the loader
            setTimeout(() => {
                document.body.classList.add('loaded');
            }, LOADER_DURATION_MS);
        });
    </script>

</body>
</html>