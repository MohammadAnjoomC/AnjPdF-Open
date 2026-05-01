<?php
// =======================================================================
// PHP Configuration for Terms and Conditions Page
// =======================================================================

// Define constants for easy updates and personalization
$website_name = "anj.ct.ws"; // Used as the site name and company reference
$website_url = "https://anj.ct.ws/";
$home_url = "index.php";
$upload_url = "upload.php";
$logo_path = "/img/logo.png"; // Path specified by user for Logo/Web Icon
$phone_number = "Null";
$email_address = "your-email@gmail.com";
$effective_date = date("F j, Y"); // Automatically set to the current date

// Define the Terms and Conditions file name for footer use
$terms_page = "terms_and_conditions.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions - <?php echo $website_name; ?></title>
    <link rel="icon" type="image/png" href="<?php echo $logo_path; ?>">
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
            --input-border: #3A4750;
        }

        /* ----------------------- BASE STYLES ----------------------- */
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.7; /* Slightly more space for readability */
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
            transition: background-color 0.4s, color 0.4s;
        }
        
        /* --- Container for T&C content --- */
        .container {
            max-width: 1080px; 
            margin: 40px auto;
            padding: 40px; 
            background: var(--card-background);
            border-radius: 20px; 
            box-shadow: var(--box-shadow);
            transition: box-shadow 0.4s, background-color 0.4s;
        }

        /* ----------------------- HEADER & BRANDING ----------------------- */
        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 30px;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--input-border);
        }

        .branding {
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .branding img {
            height: 50px;
            margin-right: 15px;
            filter: drop-shadow(0 0 5px rgba(0, 0, 0, 0.1));
        }

        .branding h1 {
            margin: 0;
            font-size: 2.2em;
            color: var(--primary-color);
            font-weight: 900;
            letter-spacing: -1px;
        }
        
        .nav {
            display: flex; /* Ensure buttons are side-by-side */
            align-items: center;
            gap: 15px; /* Space between buttons and switch */
        }
        
        /* Theme Switch Toggle CSS (Copied from initial index style) */
        .theme-switch-wrapper {
            display: flex;
            align-items: center;
            flex-shrink: 0; /* Prevents shrinking on smaller screens */
        }
        
        .theme-switch {
            display: inline-block;
            height: 24px;
            position: relative;
            width: 50px;
        }

        .theme-switch input {
            display: none;
        }

        .slider {
            background-color: var(--input-border);
            bottom: 0;
            cursor: pointer;
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
            transition: 0.4s;
            border-radius: 34px;
        }

        .slider:before {
            content: '☀️'; /* Sun icon for light mode */
            height: 18px;
            width: 18px;
            position: absolute;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 12px;
            line-height: 1;
        }

        input:checked + .slider {
            background-color: var(--primary-color);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
            content: '🌙'; /* Moon icon for dark mode */
            background-color: #212529;
            color: var(--primary-color);
        }

        /* Nav Button Styles */
        .nav .home-btn,
        .nav .upload-btn {
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1em;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s;
            white-space: nowrap; 
        }

        .nav .home-btn {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 8px rgba(23, 107, 135, 0.2);
        }

        .nav .home-btn:hover {
            background-color: var(--hover-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(23, 107, 135, 0.4);
        }
        
        .nav .upload-btn {
            background-color: var(--upload-color);
            color: white;
            box-shadow: 0 6px 15px rgba(23, 107, 135, 0.4);
        }

        .nav .upload-btn:hover {
            background-color: #1e7e34;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(23, 107, 135, 0.4);
        }

        /* ----------------------- T&C CONTENT STYLES ----------------------- */
        .content-heading {
            font-size: 2.8em;
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 10px;
            font-weight: 900;
        }

        h2 {
            color: var(--primary-color);
            margin-top: 40px;
            font-size: 1.6em;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid var(--accent-color);
            display: inline-block;
            padding-bottom: 5px;
            margin-bottom: 25px;
        }
        
        p, ul, ol {
            margin-bottom: 20px;
            font-size: 1.05em;
            color: var(--text-color);
        }
        
        li {
            margin-bottom: 12px;
            color: var(--text-color);
        }

        /* Contact Info Block */
        .contact-info {
            padding: 25px;
            background-color: var(--background-color); /* Lighter background for the block */
            border-left: 5px solid var(--primary-color);
            border-radius: 5px;
            margin-top: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: background-color 0.4s;
        }
        
        .contact-info p {
            margin: 5px 0;
            color: var(--text-color);
        }
        
        .contact-info a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .contact-info a:hover {
            text-decoration: underline;
        }

        /* ----------------------- FOOTER STYLES ----------------------- */
        .footer {
            background-color: var(--card-background);
            padding: 25px 5%;
            margin-top: 50px;
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
        
        .footer-admin-link a {
            font-size: 1em; 
            color: var(--light-text);
        }

        .footer-link a:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }
        
        .footer-admin-link a:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }


        /* ----------------------- RESPONSIVE DESIGN ----------------------- */
        @media (max-width: 1100px) {
            .container {
                margin: 30px 20px;
                padding: 30px;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 20px 15px;
                padding: 20px;
            }
            
            .main-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }

            .branding h1 {
                font-size: 2em;
            }
            
            .content-heading {
                font-size: 2.2em;
            }

            .footer-content {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .footer-link-group {
                flex-direction: column;
                gap: 10px;
            }
            
            .nav {
                width: 100%;
                justify-content: space-around;
            }
            
            .nav .home-btn,
            .nav .upload-btn {
                flex-grow: 1; /* Make buttons take up equal space */
                text-align: center;
            }
        }
    </style>
</head>
<body class="light-theme">

<div class="main-content">
    <div class="container">
        <div class="main-header">
            <a href="<?php echo $home_url; ?>" class="branding">
                <img src="<?php echo $logo_path; ?>" alt="<?php echo $website_name; ?> Logo">
                <h1>AnjPdF</h1>
            </a>
            <nav class="nav">
                <div class="theme-switch-wrapper">
                    <label class="theme-switch" for="checkbox">
                        <input type="checkbox" id="checkbox" />
                        <div class="slider round"></div>
                    </label>
                </div>
                <a href="<?php echo $home_url; ?>" class="home-btn">🏠 Home</a>
                <a href="<?php echo $upload_url; ?>" class="upload-btn">➕Upload</a>
            </nav>
        </div>

        <h1 class="content-heading">Terms and Conditions (T&C)</h1>
        <p style="text-align: center; color: var(--light-text); font-style: italic;">Last Updated: **<?php echo $effective_date; ?>**</p>

        <p>Please read these Terms and Conditions ("T&C", "Terms") carefully before using the website **<?php echo $website_url; ?>** (the "Service") operated by **<?php echo $website_name; ?>** ("us", "we", or "our").</p>

        <p>Your access to and use of the Service is conditioned on your acceptance of and compliance with these Terms. These Terms apply to all visitors, users, and others who access or use the Service.</p>

        <h2>1. Acceptance of Terms</h2>
        <p>By accessing or using the Service you agree to be bound by these Terms. If you disagree with any part of the terms, then you may not access the Service.</p>

        <h2>2. Intellectual Property</h2>
        <p>The Service and its original content, features, and functionality are and will remain the exclusive property of **<?php echo $website_name; ?>** and its licensors. The Service is protected by copyright, trademark, and other laws of the India and foreign countries. You are granted a limited license to access and use the Service for personal, non-commercial purposes only.</p>

        <h2>3. User-Provided Content (Uploads)</h2>
        <p>If the Service allows you to upload, post, link, store, share, or otherwise make available content (including via the **<a href="<?php echo $upload_url; ?>" style="color: var(--primary-color); text-decoration: none;">Upload page</a>**), you are solely responsible for the content and warrant that:</p>
        <ul>
            <li>You own or have the necessary rights and permissions to grant us a license to use the content.</li>
            <li>Your content does not violate any third party's intellectual property, privacy, or publicity rights.</li>
            <li>Your content complies with all applicable laws and does not contain any material which is defamatory, obscene, or harmful.</li>
        </ul>

        <h2>4. Links To Other Web Sites</h2>
        <p>Our Service may contain links to third-party web sites or services that are not owned or controlled by **<?php echo $website_name; ?>**.</p>
        <p>**<?php echo $website_name; ?>** has no control over, and assumes no responsibility for, the content, privacy policies, or practices of any third-party web sites or services. We strongly advise you to read the terms and conditions and privacy policies of any third-party web sites or services that you visit.</p>

        <h2>5. Termination</h2>
        <p>We may terminate or suspend access to our Service immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms.</p>
        <p>All provisions of the Terms which by their nature should survive termination shall survive termination, including, without limitation, ownership provisions, warranty disclaimers, indemnity, and limitations of liability.</p>

        <h2>6. Governing Law</h2>
        <p>These Terms shall be governed and construed in accordance with the laws of India, without regard to its conflict of law provisions.</p>

        <h2>7. Changes to Terms</h2>
        <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. We will try to provide notice prior to any new terms taking effect. By continuing to access or use our Service after those revisions become effective, you agree to be bound by the revised terms.</p>

        <h2>8. Contact Us</h2>
        <p>If you have any questions about these Terms, please contact us:</p>
        
        <div class="contact-info">
            <p><strong>By Email:</strong> <a href="mailto:<?php echo $email_address; ?>"><?php echo $email_address; ?></a></p>
            <p><strong>By Phone:</strong> <a href="tel:<?php echo $phone_number; ?>">+<?php echo $phone_number; ?></a></p>
            <p><strong>Website:</strong> <a href="<?php echo $website_url; ?>"><?php echo $website_url; ?></a></p>
        </div>

    </div>
</div>

<div class="footer">
    <div class="footer-content">
        <div class="footer-logo-icon">
            <img src="<?php echo $logo_path; ?>" alt="PDF Host Icon">
        </div>
        <p>&copy; <?php echo date("Y"); ?> AnjPdF - Host</p>
        
        <div class="footer-link-group">
            <p class="footer-link"><a href="<?php echo $terms_page; ?>">Terms & Conditions</a></p>
            <p class="footer-admin-link"><a href="login.php">🔑 Admin Panel Login</a></p>
        </div>
    </div>
</div>

<script>
    // 1. Theme Toggle Logic
    const themeCheckbox = document.getElementById('checkbox');
    const body = document.body;

    // Function to apply the theme
    function applyTheme(isDark) {
        if (isDark) {
            body.classList.add('dark-theme');
            body.classList.remove('light-theme');
            localStorage.setItem('theme', 'dark');
        } else {
            body.classList.add('light-theme');
            body.classList.remove('dark-theme');
            localStorage.setItem('theme', 'light');
        }
    }

    // Check for saved theme preference on load
    const savedTheme = localStorage.getItem('theme');
    
    // Check system preference if no saved theme
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (savedTheme === 'dark') {
        themeCheckbox.checked = true;
        applyTheme(true);
    } else if (savedTheme === null && prefersDark) {
        // If no preference is saved, default to system preference (dark)
        themeCheckbox.checked = true;
        applyTheme(true);
    } else {
        // Default to light if no preference or preference is 'light'
        themeCheckbox.checked = false;
        applyTheme(false);
    }

    // Add event listener for theme change
    themeCheckbox.addEventListener('change', (event) => {
        applyTheme(event.target.checked);
    });
</script>

</body>
</html>