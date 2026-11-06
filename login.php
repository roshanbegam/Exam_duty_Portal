<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$conn = new mysqli("localhost", "root","", "duty_portal");
//$conn = new mysqli("localhost", "shruthac", "VSE(8r[9kjNm29", "shruthac_duty_portal", 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errorMsg = "";
$captcha_string_for_display = ''; // Initialize for clarity

// --- "Remember Me" auto-login check ---
// Check if user is already remembered via cookie
if (!isset($_SESSION['user']) && isset($_COOKIE['remember_me'])) {
    list($user_id, $token) = explode(':', $_COOKIE['remember_me']);

    // Try to auto-login admin
    $stmt = $conn->prepare("SELECT fullname, remember_token, remember_token_expires_at FROM admins WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($fullname, $db_token, $expires_at);
            $stmt->fetch();
            if (hash_equals($db_token, $token) && strtotime($expires_at) > time()) {
                $_SESSION['user'] = $fullname;
                $_SESSION['is_admin'] = true;
                // Optionally, generate a new token for enhanced security (token rotation)
                // updateRememberMeToken($conn, 'admins', $user_id);
                header("Location: admin.php");
                exit;
            }
        }
        $stmt->close();
    }

    // Try to auto-login regular user if not an admin
    if (!isset($_SESSION['user'])) {
        $stmt = $conn->prepare("SELECT fullname, remember_token, remember_token_expires_at, is_paid, status FROM users WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 1) {
                $stmt->bind_result($fullname, $db_token, $expires_at, $is_paid, $status);
                $stmt->fetch();
                if (hash_equals($db_token, $token) && strtotime($expires_at) > time() && strtolower($is_paid) === 'paid' && strtolower($status) === 'accepted') {
                    $_SESSION['user'] = $fullname;
                    $_SESSION['is_admin'] = false;
                    // Optionally, generate a new token for enhanced security (token rotation)
                    // updateRememberMeToken($conn, 'users', $user_id);
                    header("Location: index.php");
                    exit;
                }
            }
            $stmt->close();
        }
    }

    // If cookie was invalid or expired, clear it
    if (!isset($_SESSION['user'])) {
        setcookie('remember_me', '', time() - 3600, '/', '', true, true); // Secure and HttpOnly
    }
}
// Always generate a fresh captcha on first load or after failed login
if (!isset($_SESSION['captcha']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    $captcha_string = substr(md5(uniqid(mt_rand(), true)), 0, 6);
    $_SESSION['captcha'] = $captcha_string;
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_login'])) {
    $email = strtolower(trim($_POST['email']));
    $password = $_POST['password'];
    $captcha_input = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';
    $remember_me = isset($_POST['remember_me']);

    // CAPTCHA verification
    if (empty($captcha_input) || strtolower($captcha_input) !== strtolower($_SESSION['captcha'])) {
        $errorMsg = "Invalid CAPTCHA. Please try again.";
        // Regenerate captcha for next attempt
        $captcha_string = substr(md5(uniqid(mt_rand(), true)), 0, 6);
        $_SESSION['captcha'] = $captcha_string;
    } else {
        // CAPTCHA is correct, proceed with login
        unset($_SESSION['captcha']); // Clear CAPTCHA after successful check

        // Check if user is admin
        $stmt = $conn->prepare("SELECT id, fullname, password FROM admins WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id, $fullname, $hashedPassword);
                $stmt->fetch();
                if (password_verify($password, $hashedPassword)) {
                    $_SESSION['user'] = $fullname;
                    $_SESSION['is_admin'] = true;
                    // Set "Remember Me" cookie for admin
                    if ($remember_me) {
                        setRememberMeCookie($conn, 'admins', $user_id);
                    } else {
                        // Clear cookie if not remembered
                        setcookie('remember_me', '', time() - 3600, '/', '', true, true);
                    }
                    header("Location: admin.php");
                    exit;
                } else {
                    $errorMsg = "Invalid email or password.";
                }
            } else {
                // Check if user is regular user and verified
                $stmt->close(); // Close previous statement before new one
                $stmt = $conn->prepare("SELECT id, fullname, password, is_paid, status FROM users WHERE email = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $stmt->store_result();

                    if ($stmt->num_rows === 1) {
                        $stmt->bind_result($user_id, $fullname, $hashedPassword, $is_paid, $status);
                        $stmt->fetch();

                        if (!password_verify($password, $hashedPassword)) {
                            $errorMsg = "Invalid email or password.";
                        } elseif (strtolower($is_paid) !== 'paid') {
                            $errorMsg = "Your payment is pending verification by the admin.";
                        } elseif (strtolower($status) !== 'accepted') {
                            $errorMsg = "Your registration is not yet approved.";
                        } else {
                            $_SESSION['user'] = $fullname;
                            $_SESSION['is_admin'] = false;

                            // Set "Remember Me" cookie for regular user
                            if ($remember_me) {
                                setRememberMeCookie($conn, 'users', $user_id);
                            } else {
                                // Clear cookie if not remembered
                                setcookie('remember_me', '', time() - 3600, '/', '', true, true);
                            }
                            header("Location: index.php");
                            exit;
                        }
                    } else {
                        $errorMsg = "Invalid email or password.";
                    }
                } else {
                    $errorMsg = "Database error: " . $conn->error;
                }
            }
            $stmt->close();
        } else {
            $errorMsg = "Database error: " . $conn->error;
        }
    } // End of CAPTCHA verification check
}
$conn->close();

/**
 * Generates and sets a secure "Remember Me" cookie.
 * @param mysqli $conn Database connection.
 * @param string $table_name 'users' or 'admins'.
 * @param int $user_id User's ID.
 */
function setRememberMeCookie($conn, $table_name, $user_id) {
    $token = bin2hex(random_bytes(32)); // Generate a cryptographically secure random token
    $expires = time() + (30 * 24 * 60 * 60); // 30 days from now
    $expires_db = date('Y-m-d H:i:s', $expires);

    // Store token and expiration in database
    $stmt = $conn->prepare("UPDATE {$table_name} SET remember_token = ?, remember_token_expires_at = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ssi", $token, $expires_db, $user_id);
        $stmt->execute();
        $stmt->close();

        // Set cookie (value: user_id:token, httponly for security, secure if on HTTPS)
        setcookie('remember_me', $user_id . ':' . $token, [
            'expires' => $expires,
            'path' => '/',
            'domain' => '', // Use your domain, e.g., 'yourdomain.com'
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // Only send over HTTPS
            'httponly' => true, // Prevents JavaScript access
            'samesite' => 'Lax' // Or 'Strict' for stricter security
        ]);
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login</title>
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

    input[type="email"],
    input[type="password"],
    input[type="text"] { /* Specific for CAPTCHA input */
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

    .error {
      margin-top: 15px;
      font-weight: 600;
      color: #c62828;
      background: #fddede;
      padding: 10px;
      border-radius: 8px;
    }
    /* CAPTCHA container */
    .captcha-container {
        display: flex;
        align-items: center; /* Align items vertically in the center */
        margin: 12px 0; /* Adjusted margin to fit inputs */
        gap: 10px; /* Space between items */
        flex-wrap: nowrap; /* ! IMPORTANT: Forces items onto a single line */
        width: 100%; /* Take full width of parent */
    }

    /* Wrapper for CAPTCHA image and refresh button */
    .captcha-image-wrapper {
        position: relative;
        display: flex; /* Flex to contain image and button */
        align-items: center; /* Center content vertically */
        border: 1px solid #ccc; /* Match input border */
        border-radius: 8px; /* Match input border-radius */
        background-color: #fdfdfd; /* Match input background */
        height: 50px; /* Fixed height for consistency */
        overflow: hidden; /* Hide anything outside */
        flex-shrink: 0; /* Prevent shrinking */
    }

    .captcha-image-wrapper img {
        display: block;
        height: 100%; /* Fill the wrapper height */
        width: 150px; /* Fixed width for the image */
        object-fit: cover; /* Ensure image covers the area */
    }

    .captcha-refresh {
        display: flex; /* Use flex to center icon */
        align-items: center;
        justify-content: center;
        width: 40px; /* Width of the button */
        height: 100%; /* Fill height of wrapper */
        background: #e0e0e0; /* Light grey background */
        color: #555; /* Darker icon color */
        font-size: 1.2em;
        cursor: pointer;
        transition: background 0.3s ease, color 0.3s ease;
        /* No border-radius here as it's part of the wrapper now */
    }

    .captcha-refresh:hover {
        background: #d0d0d0; /* Darker grey on hover */
        color: #333; /* Slightly darker icon on hover */
    }

    .captcha-container input[type="text"] {
        flex-grow: 1; /* Allow input to take remaining space */
        max-width: none; /* Remove max-width constraint */
        margin: 0; /* Remove margin from general input style, as it's handled by gap */
    }

    .form-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: -5px; /* Adjust if needed to align with input */
        margin-bottom: 20px;
        font-size: 0.9em;
    }
    .form-options label {
        display: flex;
        align-items: center;
        cursor: pointer;
        color: #555;
    }
    .form-options input[type="checkbox"] {
        width: auto; /* Override 100% width */
        margin-right: 5px;
        cursor: pointer;
    }
    .form-options a {
        color: #007BFF;
        text-decoration: none;
        font-weight: normal;
    }
    .form-options a:hover {
        text-decoration: underline;
    }
  </style>
</head>
<body>

<header class="header">
  <div class="logo"><i class="fas fa-graduation-cap"></i> Exam Duty Portal</div>
</header>

<div class="container">
  <h2>Login</h2>
  <?php if ($errorMsg): ?>
    <div class="error"><?php echo htmlspecialchars($errorMsg); ?></div>
  <?php endif; ?>
  <form method="POST" action="">
    <input type="email" name="email" placeholder="Email Address" required />
    <input type="password" name="password" placeholder="Password" required />

    <div class="captcha-container">
        <div class="captcha-image-wrapper">
            <img id="captcha-img" src="captcha.php" alt="CAPTCHA Image">
            <i class="fas fa-sync-alt captcha-refresh" title="Refresh CAPTCHA" onclick="refreshCaptcha()"></i>
        </div>
        <input type="text" name="captcha" placeholder="Enter CAPTCHA" required autocomplete="off" />
    </div>

    <div class="form-options">
        <label>
            <input type="checkbox" name="remember_me"> Remember me
        </label>
        <a href="forgot_password.php">Forgot Password?</a>
    </div>

    <button type="submit" name="submit_login">Login</button>
  </form>
  <p>Don't have an account? <a href="signup.php">Signup here</a></p>
</div>

<script>
    function refreshCaptcha() {
        const img = document.getElementById('captcha-img');
        img.src = 'captcha.php?t=' + new Date().getTime();
    }
</script>

</body>
</html>