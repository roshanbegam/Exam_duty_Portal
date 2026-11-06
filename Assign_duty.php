<?php
session_start();

// --- START DEBUGGING SETTINGS ---
// IMPORTANT: Set display_errors to 0 in production environments for security.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- END DEBUGGING SETTINGS ---

// PHPMailer autoload via Composer. Ensure 'vendor/autoload.php' path is correct.
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Database connection details
 $db_host = "localhost";
 $db_user = "root"; // Your database username
 $db_pass = ""; // Your database password
 $db_name = "duty_portal"; // Your database name
 $db_port = 3306; // Your database port

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

// Handle database connection errors immediately for both AJAX and direct access
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        // For AJAX requests, send JSON error response
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed. Please try again later.']);
        exit;
    } else {
        // For direct browser access, display error and terminate
        http_response_code(500);
        die("Database connection failed. Please try again later. (Error: " . $conn->connect_error . ")");
    }
}

// Initialize $exam variable for the HTML form's initial display
$exam = null;

// Fetch exam details if 'exam_id' is provided in the URL (for initial page load)
// Assuming exam_id in URL corresponds to 'center_code' in the 'exams' table
if (isset($_GET['exam_id'])) {
    $examIdFromUrl = $_GET['exam_id'];
    $stmt = $conn->prepare("SELECT * FROM exams WHERE center_code = ?"); 
    if ($stmt) {
        $stmt->bind_param("s", $examIdFromUrl); // 's' for string (center_code)
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $exam = $result->fetch_assoc();
        }
        $stmt->close();
    } else {
        error_log("Failed to prepare exam lookup statement: " . $conn->error);
    }
}

// --- LOGIC FOR HANDLING FORM SUBMISSION (AJAX POST REQUESTS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_registration') {
    // Set Content-Type header to application/json for all AJAX responses
    header('Content-Type: application/json');

    // 1. Sanitize and validate input data from the form
    $fullname = trim($_POST['fullname'] ?? '');      // Full Name from the form
    $phoneNumber = trim($_POST['phoneNumber'] ?? ''); // Phone Number from the form

    // Exam details from hidden/readonly fields
    $examIdSubmitted = trim($_POST['examId'] ?? ''); // Assuming this is center_code
    $examName = trim($_POST['examName'] ?? '');
    $centerCode = trim($_POST['centerCode'] ?? '');
    $centerName = trim($_POST['centerName'] ?? '');
    $examDate = trim($_POST['examDate'] ?? '');
    $examShift = trim($_POST['examShift'] ?? '');
    $role = trim($_POST['role'] ?? '');

    // Basic server-side validation for all required fields
    if (empty($fullname) || empty($phoneNumber) || empty($examIdSubmitted) || 
        empty($examName) || empty($centerCode) || empty($centerName) || 
        empty($examDate) || empty($examShift) || empty($role)) {
        echo json_encode(['status' => 'error', 'message' => 'Please ensure all personal, exam, and duty fields are filled.']);
        $conn->close();
        exit;
    }
    
    // Validate phone number format (optional, but good practice)
    if (!preg_match('/^[0-9]{10}$/', $phoneNumber)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter a valid 10-digit phone number.']);
        $conn->close();
        exit;
    }

    // Convert exam_date to a proper SQL DATE format (YYYY-MM-DD)
    $examDateFormatted = date('Y-m-d', strtotime($examDate));

    // 2. Insert registration into the 'registrations' table
    // Note: 'user_email' column is excluded as per your request.
    $insertSql = "INSERT INTO duty_registrations (exam_id, user_name, phone_number, role, registration_date, center_code, center_name, exam_date, exam_shift) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertSql);

    if ($stmt) {
        // Bind parameters: 'ssssssss' for 8 string parameters
        // (exam_id, user_name, phone_number, role, center_code, center_name, exam_date, exam_shift)
        $stmt->bind_param("ssssssss", $examIdSubmitted, $fullname, $phoneNumber, $role, $centerCode, $centerName, $examDateFormatted, $examShift);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Registration successfully recorded in the database, now send admin notification
            
            // Fetch admin emails dynamically from the 'admins' table
            $adminEmails = [];
            $adminEmailResult = $conn->query("SELECT email FROM admins");
            if ($adminEmailResult && $adminEmailResult->num_rows > 0) {
                while ($row = $adminEmailResult->fetch_assoc()) {
                    $adminEmails[] = $row['email'];
                }
            }

            // Send email to admin(s) if any admin emails are found
            if (!empty($adminEmails)) {
                $mail = new PHPMailer(true); // Passing 'true' enables exceptions for error handling
                try {
                    // Configure SMTP server settings
                    $mail->isSMTP();                                            // Send using SMTP
                    $mail->Host       = 'mail.shrutha.com';                     // Gmail SMTP server
                    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
                    // !!! IMPORTANT: Replace with your ACTUAL sending Gmail address and App Password !!!
                    // Generate an App Password in your Google Account security settings.
                    $mail->Username   = 'info@shrutha.com';          
                    $mail->Password   = 'WelcomeAbh#1234';                  
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            // Use SMTPS (SSL/TLS)
                    $mail->Port       = 465;                                    // Port for SMTPS

                    // Set sender and recipients
                    $mail->setFrom('info@shrutha.com', 'Exam Duty Portal'); // Sender's email and name
                    foreach ($adminEmails as $adminRecipientEmail) {
                        $mail->addAddress($adminRecipientEmail); // Add each admin email as a recipient
                    }

                    // Set email content
                    $mail->isHTML(true);                                        // Set email format to HTML
                    $mail->Subject = 'New Exam Duty Registration: ' . $fullname . ' for ' . $examName;
                    $mail->Body    = "A new user has registered for exam duty:<br><br>"
                                    . "<b>Applicant Name:</b> " . htmlspecialchars($fullname) . "<br>"
                                    . "<b>Contact Number:</b> " . htmlspecialchars($phoneNumber) . "<br><br>" 
                                    . "<b>Exam Name:</b> " . htmlspecialchars($examName) . "<br>"
                                    . "<b>Center Code:</b> " . htmlspecialchars($centerCode) . "<br>"
                                    . "<b>Center Name:</b> " . htmlspecialchars($centerName) . "<br>"
                                    . "<b>Exam Date:</b> " . htmlspecialchars($examDate) . "<br>"
                                    . "<b>Shift:</b> " . htmlspecialchars($examShift) . "<br>"
                                    . "<b>Role:</b> " . htmlspecialchars($role) . "<br><br>"
                                    . "Please log in to the admin panel for more details and approval.";
                    $mail->AltBody = "A new user has registered for exam duty:\n\n"
                                    . "Applicant Name: " . $fullname . "\n"
                                    . "Contact Number: " . $phoneNumber . "\n\n"
                                    . "Exam Name: " . $examName . "\n"
                                    . "Center Code: " . $centerCode . "\n"
                                    . "Center Name: " . $centerName . "\n"
                                    . "Exam Date: " . $examDate . "\n"
                                    . "Shift: " . $examShift . "\n"
                                    . "Role: " . $role . "\n\n"
                                    . "Please log in to the admin panel for more details and approval.";

                    $mail->send();
                    echo json_encode(['status' => 'success', 'message' => 'Registration successful and admin notified!']);
                } catch (Exception $e) {
                    // Log the PHPMailer error and send success message with notification failure
                    error_log("Failed to send admin notification email. Mailer Error: {$mail->ErrorInfo}");
                    echo json_encode(['status' => 'success', 'message' => 'Registration successful, but failed to send admin notification.']);
                }
            } else {
                // If no admin emails are found in the database
                error_log("No admin email found in the database to send new exam duty registration notification.");
                echo json_encode(['status' => 'success', 'message' => 'Registration successful, but no admin found to notify via email.']);
            }
        } else {
            // If database insertion failed
            echo json_encode(['status' => 'error', 'message' => 'Failed to record registration in the database.']);
        }
        $stmt->close();
    } else {
        // If prepared statement creation failed
        error_log("Failed to prepare registration insert statement: " . $conn->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error during registration preparation.']);
    }
    $conn->close();
    exit; // Crucial: Stop script execution after sending AJAX response
}
// --- END LOGIC FOR HANDLING FORM SUBMISSION ---

// Close connection if it's a GET request (initial page load)
if ($conn) {
    $conn->close(); 
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Duty Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your CSS styles */
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2ecc71;
            --secondary-dark: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --gray: #95a5a6;
            --light-gray: #f8f9fa;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--dark);
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 900px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
        }

        h2 {
            color: var(--primary);
            margin-bottom: 25px;
            font-weight: 600;
            text-align: center;
            position: relative;
            padding-bottom: 10px;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--secondary);
        }

        h3 {
            color: var(--primary-dark);
            margin: 20px 0 15px;
            font-weight: 500;
            font-size: 1.2rem;
        }

        .section {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .section:last-child {
            border-bottom: none;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 15px;
        }

        .row > div {
            flex: 1;
            min-width: 200px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        label.required::after {
            content: " *";
            color: var(--danger);
        }

        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background-color: var(--light-gray);
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        button {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        button:hover {
            transform: translateY(-2px);
        }

        button:active {
            transform: translateY(0);
        }

        .send-btn {
            background: var(--primary);
            color: white;
        }

        .send-btn:hover {
            background: var(--primary-dark);
        }

        .verify-btn {
            background: var(--warning);
            color: white;
        }

        .verify-btn:hover {
            background: #e67e22;
        }

        #submitBtn {
            background: var(--secondary);
            color: white;
            font-weight: 600;
            width: 100%;
            padding: 15px;
            margin-top: 10px;
            font-size: 1.1rem;
        }

        #submitBtn:hover {
            background: var(--secondary-dark);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.4);
        }

        .otp-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .otp-container input {
            flex: 1;
        }

        .otp-container button {
            white-space: nowrap;
        }

        .exam-details p {
            margin-bottom: 8px;
        }
        .exam-details strong {
            color: var(--primary-dark);
            min-width: 120px;
            display: inline-block;
        }

        input[type="file"] {
            padding: 12px 15px;
            border: 1px dashed #ccc;
            background-color: var(--light-gray);
            cursor: pointer;
        }
        input[type="file"]::file-selector-button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        input[type="file"]::file-selector-button:hover {
            background-color: var(--primary-dark);
        }

        .error-message {
            color: var(--danger);
            font-size: 0.85rem;
            margin-top: 5px;
            display: block;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .row {
                flex-direction: column;
                gap: 15px;
            }

            .otp-container {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Exam Duty Registration</h2>

        <form id="registrationForm">
            <!-- Personal Information Section -->
            <div class="section">
                <h3>Personal Information</h3>
                <div class="row">
                    <div>
                        <label class="required">Full Name</label>
                        <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" required>
                    </div>
                    <div>
                        <label class="required">Contact Number</label>
                        <input type="tel" id="phoneNumber" name="phoneNumber" placeholder="Enter your contact number" pattern="[0-9]{10}" title="Please enter a 10-digit phone number" required>
                    </div>
                </div>
            </div>

            <div class="section">
                <h3>Exam Details</h3>
                <!-- Hidden input for examId, value set to center_code from fetched exam data -->
                <input type="hidden" id="examId" name="examId" value="<?= htmlspecialchars($exam['center_code'] ?? '') ?>" required> 

                <div class="row">
                    <div><label class="required">Exam Name</label><input type="text" id="examName" name="examName" value="<?= htmlspecialchars($exam['exam'] ?? '') ?>" <?= $exam ? 'readonly' : 'required' ?>></div>
                    <div><label class="required">Center Code</label><input type="text" id="centerCode" name="centerCode" value="<?= htmlspecialchars($exam['center_code'] ?? '') ?>" <?= $exam ? 'readonly' : 'required' ?>></div>
                </div>
                <div class="row">
                    <div><label class="required">Center Name</slabel><input type="text" id="centerName" name="centerName" value="<?= htmlspecialchars($exam['center_name'] ?? '') ?>" <?= $exam ? 'readonly' : 'required' ?>></div>
                    <div><label class="required">Exam Date</label><input type="date" id="examDate" name="examDate" value="<?= htmlspecialchars($exam['exam_date'] ?? '') ?>" <?= $exam ? 'readonly' : 'required' ?>></div>
                </div>
                <div class="row">
                    <div><label class="required">Shift</label><input type="text" id="examShift" name="examShift" value="<?= htmlspecialchars($exam['shift'] ?? '') ?>" <?= $exam ? 'readonly' : 'required' ?>></div>
                </div>
            </div>

            <div class="section">
                <h3>Duty Information</h3>
                <div class="row">
                    <div>
                        <label class="required">Role</label>
                        <select id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="Frisking">Frisking</option>
                            <option value="Invigilation">Invigilation</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Hall Coordinator">Hall Coordinator</option>
                        </select>
                    </div>
                </div>
            </div>
            <button type="button" id="submitBtn" onclick="submitForm()">Submit Registration</button>
            <div id="responseMessage" style="margin-top: 15px; text-align: center; font-weight: bold;"></div>
        </form>
    </div>

<script>
    async function submitForm() {
        const form = document.getElementById('registrationForm');
        // Client-side validation using HTML5 built-in validation
        if (!form.checkValidity()) {
            form.reportValidity(); // Shows browser's native validation messages for missing fields
            return; // Stop if form is invalid
        }

        const formData = new FormData(form);
        formData.append('action', 'submit_registration'); // Add an action to differentiate POST requests

        const responseMessageDiv = document.getElementById('responseMessage');
        responseMessageDiv.textContent = 'Submitting...';
        responseMessageDiv.style.color = 'var(--primary)';

        try {
            const response = await fetch('<?php echo $_SERVER['PHP_SELF']; ?>', { // Post back to the same script
                method: 'POST',
                body: formData
            });

            // Always attempt to read the response as text first, then try JSON parsing
            const responseText = await response.text();
            let result;

            try {
                result = JSON.parse(responseText); // Attempt to parse the response as JSON
            } catch (jsonError) {
                // If parsing fails, it indicates non-JSON output or corrupted JSON from server
                console.error('JSON parsing error:', jsonError);
                responseMessageDiv.textContent = 'A server error occurred. Raw response: ' + responseText.substring(0, 200) + '... (check console)';
                responseMessageDiv.style.color = 'var(--danger)';
                return; // Stop execution
            }

            // If we successfully parsed JSON, process the result
            if (result.status === 'success') {
                responseMessageDiv.textContent = result.message;
                responseMessageDiv.style.color = 'var(--secondary)';
                form.reset(); // Clear the form on successful submission
                // Optional: You might want to redirect the user or show a confirmation modal here
            } else {
                responseMessageDiv.textContent = result.message || 'An unknown error occurred.';
                responseMessageDiv.style.color = 'var(--danger)';
            }
        } catch (error) {
            // Catch network errors or unhandled client-side exceptions
            console.error('Fetch error:', error);
            responseMessageDiv.textContent = 'Network error or unhandled client-side error. Please check your connection and try again.';
            responseMessageDiv.style.color = 'var(--danger)';
        }
    }
</script>
</body>
</html>
