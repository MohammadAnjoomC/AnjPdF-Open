<?php
// users/uprofile.php
require 'config.php';
if (!is_logged_in()) redirect('ulogin.php');

$msg = '';
$user_id = $_SESSION['user_id'];
$users = get_json_data(USERS_DB_PATH);

// Find current user index
$u_idx = -1;
foreach($users as $k => $u) { if($u['id'] === $user_id) { $u_idx = $k; break; } }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['name']);
    
    // Update Name
    if (!empty($new_name)) {
        $users[$u_idx]['name'] = $new_name;
        $_SESSION['user_name'] = $new_name;
    }

    // Handle Image Upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $new_filename = $user_id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], USER_IMG_DIR . $new_filename)) {
                // Remove old pic if not default
                if ($users[$u_idx]['profile_pic'] !== 'default.png') {
                    @unlink(USER_IMG_DIR . $users[$u_idx]['profile_pic']);
                }
                $users[$u_idx]['profile_pic'] = $new_filename;
                $_SESSION['user_pic'] = $new_filename;
            }
        } else {
            $msg = "<div class='alert alert-error'>Only JPG/PNG allowed.</div>";
        }
    }
    
    // Password Change
    if (!empty($_POST['password'])) {
        $users[$u_idx]['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }

    save_json_data(USERS_DB_PATH, $users);
    $msg = "<div class='alert alert-success'>Profile Updated!</div>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile Settings</title>
    <?php echo $theme_css; ?>
</head>
<body>
    <div class="auth-container">
        <h2>⚙️ Edit Profile</h2>
        <?php echo $msg; ?>
        <form method="POST" enctype="multipart/form-data">
            <label>Change Name:</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>">
            
            <label>Change Profile Picture:</label>
            <input type="file" name="profile_pic" accept="image/*" style="border:none; padding:5px;">
            
            <label>New Password (leave blank to keep):</label>
            <input type="password" name="password">
            
            <button type="submit">Save Changes</button>
        </form>
        <a href="upanel.php" class="back-home">Cancel & Go Back</a>
    </div>
</body>
</html>