<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "", "duty_portal");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$messageType = "";
$valid_token = false;
$token = isset($_GET['token']) ? $_GET['token'] : '';
$user_id = 0;
$table_name = '';

if (!empty($token)) {
    // Check token validity in users table
    $stmt = $conn->prepare("SELECT id, reset_token_expires_at FROM users WHERE reset_token = ?");
    if ($stmt) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id, $expires_at);
            $stmt->fetch();
            if (strtotime($expires_at) > time()) {
                $valid_token = true;
                $table_name = 'users';
            } else {
                $message = "Password reset link has expired.";
                $messageType = "error";
            }
        }
        $stmt->close();
    }

    // Check token validity in admins table if not found in users
    if (!$valid_token) {
        $stmt = $conn->prepare("SELECT id, reset_token_expires_at FROM admins WHERE reset_token = ?");
        if ($stmt) {
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id, $expires_at);
                $stmt->fetch();
                if (strtotime($expires_at) > time()) {
                    $valid_token = true;
                    $table_name = 'admins';
                } else {
                    $message = "Password reset link has expired.";
                    $messageType = "error";
                }
            }
            $stmt->close();
        }
    }

    if (!$valid_token && empty($message)) { // If token wasn't found at all
        $message = "Invalid password reset link.";
        $messageType = "error";
    }

} else {
    $message = "No reset token provided.";
    $messageType = "error";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $valid_token) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || empty($confirm_password)) {
        $message = "Please fill in all password fields.";
        $messageType = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
        $messageType = "error";
    } elseif (strlen($new_password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $messageType = "error";
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $message = "Password must contain at least one uppercase letter.";
        $messageType = "error";
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $message = "Password must contain at least one lowercase letter.";
        $messageType = "error";
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $message = "Password must contain at least one number.";
        $messageType = "error";
    } elseif (!preg_match('/[^a-zA-Z0-9\s]/', $new_password)) { // Matches any character that is NOT a letter, number, or whitespace
        $message = "Password must contain at least one special character.";
        $messageType = "error";
    }
    else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password and clear reset token
        $stmt = $conn->prepare("UPDATE {$table_name} SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $hashed_password, $user_id);
            if ($stmt->execute()) {
                $message = "Your password has been successfully reset. You can now login with your new password.";
                $messageType = "success";
                $valid_token = false; // Invalidate the form for subsequent submissions
            } else {
                $message = "Error updating password. Please try again.";
                $messageType = "error";
            }
            $stmt->close();
        } else {
            $message = "Database error. Please try again.";
            $messageType = "error";
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reset Password</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f9f9f9;
      margin: 0;
      padding: 0;
      color: #333;
    }

    .header {
      background-color: #007BFF;
      padding: 15px 5%;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .header .logo {
      font-size: 1.8em;
      display: flex;
      align-items: center;
      font-weight: 700;
    }

    .header .logo i {
      margin-right: 10px;
      font-size: 1.2em;
    }

    .container {
      background: #ffffff;
      padding: 40px 30px;
      border-radius: 15px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
      width: 360px;
      text-align: center;
      margin: 60px auto;
    }

    h2 {
      margin-bottom: 20px;
      color: #333;
    }

    input[type="password"] {
      width: 100%;
      padding: 12px;
      margin: 12px 0;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 16px;
      outline: none;
      background-color: #fdfdfd;
      color: #333;
    }

    input::placeholder {
      color: #999;
    }

    button[type="submit"] {
      width: 100%;
      padding: 14px;
      background: linear-gradient(to right, #00bcd4, #2196f3);
      border: none;
      border-radius: 8px;
      font-weight: bold;
      font-size: 18px;
      color: white;
      cursor: pointer;
      transition: background 0.3s ease;
      box-shadow: 0 5px 12px rgba(0,0,0,0.1);
    }

    button[type="submit"]:hover {
      background: linear-gradient(to right, #2196f3, #00bcd4);
    }

    p {
      margin-top: 15px;
    }

    a {
      color: #007BFF;
      text-decoration: underline;
      font-weight: bold;
    }

    a:hover {
      color: #0056b3;
    }

    .message {
      margin-top: 15px;
      font-weight: 600;
      padding: 10px;
      border-radius: 8px;
    }
    .message.error {
      color: #c62828;
      background: #fddede;
    }
    .message.success {
      color: #2e7d32;
      background: #e8f5e9;
    }
  </style>
</head>
<body>

<header class="header">
  <div class="logo"><i class="fas fa-graduation-cap"></i> Exam Duty Portal</div>
</header>

<div class="container">
  <h2>Reset Password</h2>
  <?php if ($message): ?>
    <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <?php if ($valid_token): ?>
    <form method="POST" action="">
      <p>Enter your new password.</p>
      <input type="password" name="new_password" placeholder="New Password" required />
      <input type="password" name="confirm_password" placeholder="Confirm New Password" required />
      <button type="submit">Set New Password</button>
    </form>
  <?php elseif (empty($message)): // Only show if no token and no error message yet, implies invalid/missing token ?>
    <p>Please use the full password reset link sent to your email.</p>
  <?php endif; ?>

  <p><a href="login.php">Back to Login</a></p>
</div>

</body>
</html>