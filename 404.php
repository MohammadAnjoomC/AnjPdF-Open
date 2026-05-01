<?php
// Set the HTTP response code to 404 Not Found
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found - Redirecting...</title>
    <style>
        /* Define the requested color scheme */
        :root {
            --red: #E74C3C; /* Red */
            --blue: #3498DB; /* Blue */
            --yellow: #F1C40F; /* Yellow */
            --text-color: #343A40;
            --background-color: #F8F9FA;
        }

        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
        }
        
        .container {
            max-width: 600px;
            padding: 40px;
            background: #FFFFFF;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
            border-top: 5px solid var(--red); /* Accent line */
        }

        .logo-container {
            margin-bottom: 30px;
        }

        .logo-container img {
            /* Big Logo - Increased size significantly */
            width: 200px; 
            height: auto;
            animation: pulse 1.5s infinite alternate;
        }
        
        @keyframes pulse {
            from { transform: scale(1); }
            to { transform: scale(1.05); }
        }

        h1 {
            font-size: 4em;
            color: var(--red); /* Main error color */
            margin: 0 0 10px 0;
            font-weight: 900;
        }

        h2 {
            font-size: 1.8em;
            color: var(--blue); /* Secondary text color */
            margin-top: 0;
            margin-bottom: 20px;
        }

        p {
            font-size: 1.1em;
            color: var(--text-color);
            margin-bottom: 30px;
        }
        
        .redirect-message {
            color: var(--yellow); /* Highlight redirection with yellow */
            font-weight: bold;
            border: 2px solid var(--yellow);
            padding: 10px;
            border-radius: 8px;
            display: inline-block;
            background-color: #FFFBEA;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="logo-container">
            <img src="/img/logo.png" alt="AnjPdF Logo">
        </div>
        
        <h1>404</h1>
        <h2>Not Found</h2>
        <p>The page you were looking for could not be found!</p>
        
        <p class="redirect-message">
            Redirecting to the homepage in <span id="countdown">5</span> seconds...
        </p>
    </div>

    <script>
        let countdownElement = document.getElementById('countdown');
        let timeLeft = 5;

        function updateCountdown() {
            if (timeLeft > 0) {
                timeLeft--;
                countdownElement.textContent = timeLeft;
                setTimeout(updateCountdown, 1000);
            } else {
                // Redirect to index.php (the root page)
                window.location.href = 'https://anj.ct.ws/';
            }
        }

        // Start the countdown
        setTimeout(updateCountdown, 1000); 
    </script>

</body>
</html>