<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP; // Make sure this is also included

// Adjust these paths based on where you placed the PHPMailer 'src' folder
// Assuming 'forgot_password.php' is in 'C:\xampp\htdocs\exam_duty\'
// and your PHPMailer 'src' is in 'C:\xampp\htdocs\exam_duty\vendor\phpmailer\phpmailer\src\'
require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php'; // Important for SMTP!

$conn = new mysqli("localhost", "root", "", "duty_portal");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$messageType = ""; // 'success' or 'error'

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = strtolower(trim($_POST['email']));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } else {
        // Check if email exists in users or admins table
        $user_found = false;
        $table_name = '';
        $user_id = 0;

        // Check in 'users' table
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id);
                $stmt->fetch();
                $user_found = true;
                $table_name = 'users';
            }
            $stmt->close();
        }

        // Check in 'admins' table if not found in 'users'
        if (!$user_found) {
            $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows === 1) {
                    $stmt->bind_result($user_id);
                    $stmt->fetch();
                    $user_found = true;
                    $table_name = 'admins';
                }
                $stmt->close();
            }
        }

        if ($user_found) {
            // Generate a unique token
            $token = bin2hex(random_bytes(32)); // Cryptographically secure token
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour

            // Store the token and its expiration in the database
            $stmt = $conn->prepare("UPDATE {$table_name} SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssi", $token, $expires, $user_id);
                if ($stmt->execute()) {
                    // --- PHPMailer Email Sending ---
                    $mail = new PHPMailer(true); // true enables exceptions

                    try {
                        // Server settings
                        $mail->isSMTP();                                            // Send using SMTP
                        $mail->Host       = 'smtp.gmail.com';                       // Set the SMTP server to send through
                        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
                        $mail->Username   = 'roshanb000b@gmail.com';                // SMTP username (YOUR GMAIL ADDRESS)
                        $mail->Password   = 'cdza mteg fknq wljw';                    // SMTP password (YOUR GMAIL APP PASSWORD)
                                                                                    // IMPORTANT: For Gmail, you MUST use an App Password, not your regular Gmail password.
                                                                                    // See https://support.google.com/accounts/answer/185833
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            // Enable implicit TLS encryption (use SMTPS for port 465)
                        $mail->Port       = 465;                                    // TCP port to connect to; use 587 if `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

                        // Recipients
                        $mail->setFrom('roshanb000b@gmail.com', 'Exam Duty Portal'); // Sender's email and name
                        $mail->addAddress($email);                                  // Add a recipient

                        // Content
                        $mail->isHTML(false);                                       // Set email format to plain text
                        $mail->Subject = 'Password Reset Request for Exam Duty Portal';
                        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/exam_duty/reset_password.php?token=" . $token;
                        $body = "Dear User,\n\n";
                        $body .= "You have requested to reset your password for the Exam Duty Portal.\n";
                        $body .= "Please click on the following link to reset your password:\n";
                        $body .= $reset_link . "\n\n";
                        $body .= "This link will expire in 1 hour. If you did not request this, please ignore this email.\n\n";
                        $body .= "Regards,\nExam Duty Portal Team";
                        $mail->Body = $body;

                        $mail->send();
                        $message = "A password reset link has been sent to your email address. Please check your inbox (and spam folder).";
                        $messageType = "success";
                    } catch (Exception $e) {
                        $message = "Failed to send reset email. Mailer Error: {$mail->ErrorInfo}";
                        $messageType = "error";
                        error_log("Failed to send password reset email to " . $email . ". Mailer Error: " . $mail->ErrorInfo);
                    }
                } else {
                    $message = "Error storing reset token. Please try again.";
                    $messageType = "error";
                }
                $stmt->close();
            } else {
                $message = "Database error. Please try again.";
                $messageType = "error";
                error_log("Forgot Password prepare statement failed: " . $conn->error);
            }
        } else {
            $message = "No account found with that email address.";
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
  <title>Forgot Password</title>
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

    input[type="email"] {
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
  <h2>Forgot Password</h2>
  <?php if ($message): ?>
    <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>
  <form method="POST" action="">
    <p>Enter your email address to receive a password reset link.</p>
    <input type="email" name="email" placeholder="Your Email Address" required />
    <button type="submit">Send Reset Link</button>
  </form>
  <p><a href="login.php">Back to Login</a></p>
</div>

</body>
</html>