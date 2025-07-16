<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "", "duty_portal");
// For production, uncomment the line below and comment the localhost one:
//$conn = new mysqli("localhost", "shruthac", "VSE(8r[9kjNm29", "shruthac_duty_portal", 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if an admin already exists
$adminExists = false;
$result = $conn->query("SELECT COUNT(*) as count FROM admins");
if ($result) {
    $row = $result->fetch_assoc();
    $adminExists = ($row['count'] > 0);
}

$errorMsg = "";
$successMsg = "";

// Define the regex pattern for payment reference number.
// IMPORTANT: Adjust this regex based on the actual format of your transaction IDs.
// For UPI Transaction IDs (e.g., Year + 12-16 digits) you might use: /^\d{4}\d{12,16}$/
// For more general alphanumeric IDs with hyphens/underscores (6 to 20 characters):
$payment_ref_pattern = '/^[a-zA-Z0-9_-]{6,20}$/';
// This example for the placeholder should be plain text, no HTML
$payment_ref_example = 'e.g., TXN123456789, PAY-ABC-XYZ'; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST['fullname']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $payment_ref = trim($_POST['payment_ref']);

    if (!$email) {
        $errorMsg = "Please enter a valid email address.";
    } elseif (empty($fullname) || empty($password) || empty($payment_ref)) {
        $errorMsg = "Please fill all required fields.";
    }
    // --- Start Password Validation ---
    elseif (strlen($password) < 8) {
        $errorMsg = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errorMsg = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errorMsg = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errorMsg = "Password must contain at least one number.";
    } elseif (!preg_match('/[^a-zA-Z0-9\s]/', $password)) { // Matches any character that is NOT a letter, number, or whitespace
        $errorMsg = "Password must contain at least one special character.";
    }
    // --- End Password Validation ---
    // --- Start Payment Reference Validation ---
    elseif (!preg_match($payment_ref_pattern, $payment_ref)) {
        $errorMsg = "Invalid payment reference number format. Please enter a valid transaction ID. Example: " . $payment_ref_example;
    }
    // --- End Payment Reference Validation ---
    else {
        // Check if email already exists in either admins or users table
        $stmt = $conn->prepare("SELECT email FROM admins WHERE email = ? UNION SELECT email FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("ss", $email, $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $errorMsg = "User already registered with this email.";
            } else {
                // If fullname is 'admin' (case-insensitive) and no admin exists, create admin
                if (strtolower($fullname) === 'admin') {
                    if ($adminExists) {
                        $errorMsg = "Admin account already exists. Only one admin account can be created this way.";
                    } else {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmtInsert = $conn->prepare("INSERT INTO admins (fullname, email, password) VALUES (?, ?, ?)");
                        if ($stmtInsert) {
                            $stmtInsert->bind_param("sss", $fullname, $email, $hashedPassword);
                            if ($stmtInsert->execute()) {
                                $_SESSION['user'] = $fullname;
                                $_SESSION['is_admin'] = true;
                                header("Location: admin.php"); // Redirect to admin panel on successful admin signup
                                exit;
                            } else {
                                $errorMsg = "Something went wrong during admin registration, please try again.";
                            }
                            $stmtInsert->close();
                        } else {
                             $errorMsg = "Database prepare error for admin: " . $conn->error;
                        }
                    }
                } else {
                    // Regular user registration
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmtInsert = $conn->prepare("INSERT INTO users (fullname, email, password, payment_ref, is_paid, status, created_at) VALUES (?, ?, ?, ?, 'pending', 'pending', NOW())");
                    if ($stmtInsert) {
                        $stmtInsert->bind_param("ssss", $fullname, $email, $hashedPassword, $payment_ref);
                        if ($stmtInsert->execute()) {
                            $successMsg = "✅ Registration successful! Your account will be verified by admin soon.";
                        } else {
                            $errorMsg = "Something went wrong during user registration, please try again.";
                        }
                        $stmtInsert->close();
                    } else {
                        $errorMsg = "Database prepare error for user: " . $conn->error;
                    }
                }
            }
            $stmt->close();
        } else {
            $errorMsg = "Database prepare error for email check: " . $conn->error;
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
    <input type="text" name="fullname" placeholder="Full Name" value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>" required />
    <input type="email" name="email" placeholder="Email Address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required />
    <input type="password" name="password" placeholder="Password" required />
    <div class="payment-info">
      <p><strong>One-Time Registration Fee: ₹199</strong></p>
      <img src="scanner.png" alt="Pay ₹199 QR Code" />
      <input type="text" name="payment_ref" 
             placeholder="Payment Reference No. (<?php echo htmlspecialchars($payment_ref_example); ?>)" 
             value="<?php echo isset($_POST['payment_ref']) ? htmlspecialchars($_POST['payment_ref']) : ''; ?>" 
             required />
    </div>
    <button type="submit">Signup</button>
  </form>

  <p>Already have an account? <a href="login.php">Login here</a></p>
</div>

</body>
</html>