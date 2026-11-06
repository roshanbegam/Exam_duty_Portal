<?php
session_start();

// Database connection
// Using provided connection details
$conn = new mysqli("localhost", "root","","duty_portal");
//$conn = new mysqli("localhost", "shruthac", "VSE(8r[9kjNm29", "shruthac_duty_portal", 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Include PHPMailer via Composer autoload
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Check if an admin already exists
$adminExists = false;
$result = $conn->query("SELECT COUNT(*) as count FROM admins");
if ($result) {
    $row = $result->fetch_assoc();
    $adminExists = ($row['count'] > 0);
}

$errorMsg = "";
$successMsg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST['fullname']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $payment_ref = trim($_POST['payment_ref']);

    if (!$email) {
        $errorMsg = "Please enter a valid email address.";
    } elseif (empty($fullname) || empty($password) || empty($payment_ref)) {
        $errorMsg = "Please fill all required fields.";
    } else {
        // Check if email already exists in either admins or users table
        $stmt = $conn->prepare("SELECT email FROM admins WHERE email = ? UNION SELECT email FROM users WHERE email = ?");
        $stmt->bind_param("ss", $email, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errorMsg = "User already registered with this email.";
        } else {
            // Admin registration flow (if 'fullname' is 'admin')
            if (strtolower($fullname) === 'admin') {
                if ($adminExists) {
                    $errorMsg = "An admin account already exists. Only one admin account can be created.";
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmtInsert = $conn->prepare("INSERT INTO admins (fullname, email, password) VALUES (?, ?, ?)");
                    $stmtInsert->bind_param("sss", $fullname, $email, $hashedPassword);
                    if ($stmtInsert->execute()) {
                        // Admin successfully registered, display success message on the same page
                        $successMsg = "✅ Registration successful! You are now the administrator. Please proceed to login.";
                        // For the registering admin, we do NOT redirect here, so the message is visible.
                    } else {
                        $errorMsg = "Something went wrong during admin registration, please try again.";
                    }
                    $stmtInsert->close();
                }
            } else {
                // Regular user registration flow
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmtInsert = $conn->prepare("INSERT INTO users (fullname, email, password, payment_ref, is_paid, status, created_at) VALUES (?, ?, ?, ?, 'pending', 'pending', NOW())");
                $stmtInsert->bind_param("ssss", $fullname, $email, $hashedPassword, $payment_ref);
                if ($stmtInsert->execute()) {
                    // Fetch admin emails dynamically from the database
                    $adminEmails = [];
                    $adminEmailResult = $conn->query("SELECT email FROM admins");
                    if ($adminEmailResult && $adminEmailResult->num_rows > 0) {
                        while ($row = $adminEmailResult->fetch_assoc()) {
                            $adminEmails[] = $row['email'];
                        }
                    }

                    // Send email to admin(s) if any exist
                    if (!empty($adminEmails)) {
                        $mail = new PHPMailer(true); // Passing true enables exceptions
                        try {
                            //Server settings
                            $mail->isSMTP();                                            // Send using SMTP
                            $mail->Host       = 'mail.shrutha.com';                     // Set the SMTP server to send through
                            $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
                            $mail->Username   = 'info@shrutha.com';          // Your sending email address (e.g., from where notifications are sent)
                            $mail->Password   = 'WelcomeAbh#1234';                  // Your App Password for the sending email
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            // Enable implicit TLS encryption
                            $mail->Port       = 465;                                    // TCP port to connect to

                            // Recipients (add all fetched admin emails)
                            $mail->setFrom('roshanb000b@gmail.com', 'Exam Duty Portal'); // Your email address and name
                            foreach ($adminEmails as $adminRecipientEmail) {
                                $mail->addAddress($adminRecipientEmail); 
                            }

                            // Content
                            $mail->isHTML(true);                                        // Set email format to HTML
                            $mail->Subject = 'New User Registration: ' . $fullname;
                            $mail->Body    = "A new user has registered for the Exam Duty Portal.<br><br>"
                                            . "<b>Full Name:</b> " . htmlspecialchars($fullname) . "<br>"
                                            . "<b>Email:</b> " . htmlspecialchars($email) . "<br>"
                                            . "<b>Payment Reference:</b> " . htmlspecialchars($payment_ref) . "<br><br>"
                                            . "Please log in to the admin panel to verify the payment and activate the account.";
                            $mail->AltBody = "A new user has registered for the Exam Duty Portal.\n\n"
                                            . "Full Name: " . $fullname . "\n"
                                            . "Email: " . $email . "\n"
                                            . "Payment Reference: " . $payment_ref . "\n\n"
                                            . "Please log in to the admin panel to verify the payment and activate the account.";

                            $mail->send();
                            $successMsg = "✅ Registration successful! Your account will be verified by admin soon.";
                        } catch (Exception $e) {
                            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
                            $successMsg = "✅ Registration successful! Your account will be verified by admin soon (Admin notification email failed to send, but registration was successful).";
                        }
                    } else {
                        // Handle case where no admin email is found in the database
                        error_log("No admin email found in the database to send new user notification.");
                        $successMsg = "✅ Registration successful! Your account will be verified by admin soon (No admin found to notify via email).";
                    }
                } else {
                    $errorMsg = "Something went wrong during user registration, please try again.";
                }
                $stmtInsert->close();
            }
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Signup</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg,rgb(245, 245, 245), #ffffff);
            height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
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
            width: 100%;
        }
        .header .logo {
            font-size: 1.8em;
            display: flex;
            align-items: center;
            font-weight: 700;
            flex: 1;
        }
        .header .logo i {
            margin-right: 12px;
            font-size: 1.2em;
        }
        .container {
            background: #ffffff;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            width: 360px;
            text-align: center;
            margin: 60px auto;
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        input {
            width: 100%;
            padding: 12px;
            margin: 12px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
            color: #333;
            background-color: #f9f9f9;
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
        .success {
            margin-top: 15px;
            font-weight: 600;
            color: #2e7d32;
            background: #e0f2f1;
            padding: 10px;
            border-radius: 8px;
        }
        .payment-info {
            margin-top: 10px;
            background-color: #f1f1f1;
            padding: 12px;
            border-radius: 8px;
            color: #444;
        }
        .payment-info img {
            width: 180px;
            margin: 10px 0;
            border-radius: 10px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>

<header class="header">
    <div class="logo"><i class="fas fa-graduation-cap"></i> Exam Duty Portal</div>
</header>

<div class="container">
    <h2>Signup</h2>

    <?php if ($errorMsg): ?>
        <div class="error"><?php echo htmlspecialchars($errorMsg); ?></div>
    <?php endif; ?>

    <?php if ($successMsg): ?>
        <div class="success"><?php echo htmlspecialchars($successMsg); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="text" name="fullname" placeholder="Full Name" required />
        <input type="email" name="email" placeholder="Email Address" required />
        <input type="password" name="password" placeholder="Password" required />
        <div class="payment-info">
            <p><strong>One-Time Registration Fee: ₹199</strong></p>
            <img src="scan.png" alt="Pay ₹199 QR Code" />
            <input type="text" name="payment_ref" placeholder="Enter Payment Reference Number" required />
        </div>
        <button type="submit">Signup</button>
    </form>

    <p>Already have an account? <a href="login.php">Login here</a></p>
</div>

</body>
</html>
