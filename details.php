<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//DB Connection with error handling
$dbHost = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "duty_portal";
//  $dbHost = "localhost";
//  $dbUser = "shruthac"; // Your database username
//  $dbPass = "VSE(8r[9kjNm29"; // Your database password
//  $dbName = "shruthac_duty_portal"; // Your database name
//  $dbPort = 3306; // Your database port
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    header('Content-Type: application/json');
    die(json_encode([
        'success' => false, 
        'message' => "Connection to database failed. Please try again later."
    ]));
}

// Set charset to utf8mb4 for full Unicode support
$conn->set_charset("utf8mb4");

// Initialize variables
$emailFromDuty = $_GET['email'] ?? '';
$examIdFromDuty = $_GET['exam_id'] ?? '';
$editMode = false;
$existingData = [];

// Check for edit mode
if (isset($_GET['edit'])) {
    $email = filter_var($_GET['email'] ?? '', FILTER_SANITIZE_EMAIL);
    if (!empty($email)) {
        $stmt = $conn->prepare("SELECT * FROM registered_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $existingData = $result->fetch_assoc();
            $editMode = true;
            $emailFromDuty = $email;
        }
        $stmt->close();
    }
}

// Handle AJAX Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    try {
        $jsonInput = file_get_contents('php://input');
        if (empty($jsonInput)) {
            echo json_encode(['success' => false, 'message' => "No data received"]);
            exit;
        }

        $data = json_decode($jsonInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'message' => "Invalid JSON data received: " . json_last_error_msg()]);
            exit;
        }

        if ($_GET['action'] === 'send-otp') {
            $email = $data['email'] ?? '';
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email address']);
                exit;
            }

            // Generate a 6-digit OTP
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Store OTP in session with timestamp
            $_SESSION['otp'][$email] = [
                'code' => $otp,
                'timestamp' => time()
            ];

            // Send OTP via email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'rakshithaachari08@gmail.com';
                $mail->Password = 'yias vlqf rkes ffig';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('rakshithaachari08@gmail.com', 'Duty Portal');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Your OTP for Registration';
                $mail->Body = "Your OTP for registration is: <strong>$otp</strong>. This OTP is valid for 5 minutes.";

                $mail->send();
                echo json_encode(['success' => true, 'message' => 'OTP sent successfully']);
            } catch (Exception $e) {
                error_log("Failed to send OTP email: " . $mail->ErrorInfo);
                echo json_encode(['success' => false, 'message' => 'Failed to send OTP. Please try again.']);
            }
            exit;
        }

        if ($_GET['action'] === 'verify-otp') {
            $email = $data['email'] ?? '';
            $otp = $data['otp'] ?? '';
            
            if (!isset($_SESSION['otp'][$email])) {
                echo json_encode(['success' => false, 'message' => 'No OTP request found for this email. Please request a new OTP.']);
                exit;
            }

            $storedOtp = $_SESSION['otp'][$email]['code'] ?? null;
            $storedTimestamp = $_SESSION['otp'][$email]['timestamp'] ?? 0;
            $expiryTime = 300; // 5 minutes

            if (!$storedOtp) {
                echo json_encode(['success' => false, 'message' => 'OTP verification failed. Please request a new OTP.']);
                exit;
            }

            if (time() - $storedTimestamp >= $expiryTime) {
                unset($_SESSION['otp'][$email]);
                echo json_encode(['success' => false, 'message' => 'OTP expired. Please request a new one.']);
                exit;
            }

            if ($storedOtp != $otp) {
                echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please try again.']);
                exit;
            }

            // If we get here, verification is successful
            unset($_SESSION['otp'][$email]);
            echo json_encode(['success' => true, 'message' => 'OTP Verified successfully!']);
            exit;
        }

        if ($_GET['action'] === 'submit-form') {
            // Validate and sanitize all inputs
            $errors = [];
            $requiredFields = [
                'firstName', 'lastName', 'dob', 'email', 
                'contact1', 'state', 'district', 'bankName',
                'accountNumber', 'ifsc', 'aadhaarNumber', 'panNumber'
            ];

            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $errors[] = ucfirst($field) . " is required";
                }
            }

            if (!empty($errors)) {
                echo json_encode(['success' => false, 'message' => implode(", ", $errors)]);
                exit;
            }

            // Process the form data
            $firstName = htmlspecialchars($data['firstName']);
            $lastName = htmlspecialchars($data['lastName']);
            $dob = htmlspecialchars($data['dob']);
            $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
            $contact1 = htmlspecialchars($data['contact1']);
            $contact2 = htmlspecialchars($data['contact2'] ?? '');
            $state = htmlspecialchars($data['state']);
            $district = htmlspecialchars($data['district']);
            $bankName = htmlspecialchars($data['bankName']);
            $accountNumber = htmlspecialchars($data['accountNumber']);
            $ifsc = htmlspecialchars($data['ifsc']);
            $aadhaarNumber = htmlspecialchars($data['aadhaarNumber']);
            $panNumber = htmlspecialchars($data['panNumber']);
            $examId = htmlspecialchars($data['examId'] ?? '');
            $isEdit = $data['isEdit'] ?? false;

            // Handle file uploads
            $photoBase64 = $data['photo'] ?? '';
            $aadhaarBase64 = $data['aadhaar'] ?? '';
            $panPhotoBase64 = $data['panPhoto'] ?? '';

            try {
                if ($isEdit) {
                    $stmt = $conn->prepare("UPDATE registered_users SET 
                        first_name = ?, last_name = ?, dob = ?, contact1 = ?, contact2 = ?,
                        state = ?, district = ?, bank_name = ?, account_number = ?, ifsc = ?,
                        aadhaar_number = ?, pan_number = ?, exam_id = ?,
                        photo_base64 = IFNULL(?, photo_base64), 
                        aadhaar_base64 = IFNULL(?, aadhaar_base64), 
                        pan_photo_base64 = IFNULL(?, pan_photo_base64)
                        WHERE email = ?");
                    
                    $stmt->bind_param("sssssssssssssssss",
                        $firstName, $lastName, $dob, $contact1, $contact2,
                        $state, $district, $bankName, $accountNumber, $ifsc,
                        $aadhaarNumber, $panNumber, $examId,
                        $photoBase64, $aadhaarBase64, $panPhotoBase64,
                        $email
                    );
                } else {
                    $stmt = $conn->prepare("INSERT INTO registered_users (
                        first_name, last_name, dob, email, contact1, contact2,
                        state, district, bank_name, account_number, ifsc,
                        aadhaar_number, pan_number, photo_base64, aadhaar_base64,
                        pan_photo_base64, exam_id, registration_date
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    
                    $stmt->bind_param("sssssssssssssssss",
                        $firstName, $lastName, $dob, $email, $contact1, $contact2,
                        $state, $district, $bankName, $accountNumber, $ifsc,
                        $aadhaarNumber, $panNumber, $photoBase64, $aadhaarBase64,
                        $panPhotoBase64, $examId
                    );
                }

                if ($stmt->execute()) {
                    $newUserId = $isEdit ? null : $conn->insert_id;
                    $uniqueRegistrationId = $isEdit ? '' : "REG-" . str_pad($newUserId, 6, '0', STR_PAD_LEFT);

                    // Send confirmation email
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'rakshithaachari08@gmail.com';
                        $mail->Password = 'yias vlqf rkes ffig';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        $mail->setFrom('rakshithaachari08@gmail.com', 'Duty Portal');
                        $mail->addAddress($email);
                        $mail->isHTML(true);
                        $mail->Subject = $isEdit ? 'Your Registration Details Have Been Updated' : 'Your Registration Details';

                        $emailBody = "
                            <h2 style='color: #4361ee;'>Hello $firstName $lastName,</h2>
                            <p>" . ($isEdit ? "Your registration details have been updated successfully." : "Thank you for registering for the exam duty portal.") . "</p>
                            " . (!$isEdit ? "<p>Your unique Registration ID is: <strong>$uniqueRegistrationId</strong></p>" : "") . "
                            
                            <h3 style='color: #3f37c9; margin-top: 20px;'>Your " . ($isEdit ? "Updated " : "") . "Details:</h3>
                            <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                                <tr><td style='padding: 8px; border: 1px solid #ddd;'>Full Name:</td><td style='padding: 8px; border: 1px solid #ddd;'>$firstName $lastName</td></tr>
                                <tr><td style='padding: 8px; border: 1px solid #ddd;'>Date of Birth:</td><td style='padding: 8px; border: 1px solid #ddd;'>$dob</td></tr>
                                <tr><td style='padding: 8px; border: 1px solid #ddd;'>Email:</td><td style='padding: 8px; border: 1px solid #ddd;'>$email</td></tr>
                                <tr><td style='padding: 8px; border: 1px solid #ddd;'>Primary Contact:</td><td style='padding: 8px; border: 1px solid #ddd;'>$contact1</td></tr>
                                <tr><td style='padding: 8px; border: 1px solid #ddd;'>Alternate Contact:</td><td style='padding: 8px; border: 1px solid #ddd;'>$contact2</td></tr>
                                <tr><td style='padding: 8px; border: 1px solid #ddd;'>State:</td><td style='padding: 8px; border: 1px solid #ddd;'>$state</td></tr>
                                <tr><td style='padding: 8px; border: 1px solid #ddd;'>District:</td><td style='padding: 8px; border: 1px solid #ddd;'>$district</td></tr>
                                <tr><td style='padding: 8px; border: 1px solid #ddd;'>Aadhaar Number:</td><td style='padding: 8px; border: 1px solid #ddd;'>$aadhaarNumber</td></tr>
                                <tr><td style='padding: 8px; border: 1px solid #ddd;'>PAN Number:</td><td style='padding: 8px; border: 1px solid #ddd;'>$panNumber</td></tr>
                                <tr><td style='padding: 8px; border: 1px solid #ddd;'>Bank Name:</td><td style='padding: 8px; border: 1px solid #ddd;'>$bankName</td></tr>
                                <tr><td style='padding: 8px; border: 1px solid #ddd;'>Account Number:</td><td style='padding: 8px; border: 1px solid #ddd;'>$accountNumber</td></tr>
                                <tr><td style='padding: 8px; border: 1px solid #ddd;'>IFSC Code:</td><td style='padding: 8px; border: 1px solid #ddd;'>$ifsc</td></tr>
                                <tr><td style='padding: 8px; border: 1px solid #ddd;'>Exam ID:</td><td style='padding: 8px; border: 1px solid #ddd;'>$examId</td></tr>
                            </table>

                            <p style='margin-top: 20px;'>If you have any questions, please contact us.</p>
                            <p style='font-size: 0.9em; color: #777;'>Best regards,<br>The Duty Portal Team</p>
                        ";
                        
                        $mail->Body = $emailBody;
                        $mail->send();

                        echo json_encode([
                            'success' => true, 
                            'message' => $isEdit 
                                ? 'Registration updated successfully! Confirmation email sent.' 
                                : 'Registration successful! Your details have been saved and confirmation email sent.',
                            'registrationId' => $uniqueRegistrationId
                        ]);
                    } catch (Exception $e) {
                        error_log("Failed to send confirmation email: " . $mail->ErrorInfo);
                        echo json_encode([
                            'success' => true, 
                            'message' => $isEdit 
                                ? 'Registration updated successfully! However, we couldn\'t send the confirmation email.' 
                                : 'Registration successful! However, we couldn\'t send the confirmation email.',
                            'registrationId' => $uniqueRegistrationId
                        ]);
                    }
                } else {
                    throw new Exception("Database error: " . $stmt->error);
                }
            } catch (Exception $e) {
                error_log("Error processing form: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request. Please try again.']);
            } finally {
                if (isset($stmt)) $stmt->close();
            }
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editMode ? 'Edit Registration' : 'User Registration' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --warning-color: #f8961e;
            --info-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --border-radius: 8px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            max-width: 900px;
            width: 100%;
            margin: 30px auto;
            background: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            animation: fadeInUp 0.5s ease-out;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .progress-bar {
            display: flex;
            background: #f8f9fa;
            padding: 20px 0;
            position: relative;
            border-bottom: 1px solid #e9ecef;
        }
        
        .step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            background: #e9ecef;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-weight: bold;
            transition: var(--transition);
            position: relative;
            z-index: 2;
            border: 3px solid #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .step.active .step-number {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.2);
        }
        
        .step.completed .step-number {
            background: var(--success-color);
            color: white;
        }
        
        .step-title {
            margin-top: 10px;
            font-size: 14px;
            color: #6c757d;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .step.active .step-title {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .step.completed .step-title {
            color: var(--success-color);
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            left: calc(50% + 20px);
            right: calc(-50% + 20px);
            height: 3px;
            background: #e9ecef;
            z-index: 0;
            transition: var(--transition);
        }
        
        .step.completed:not(:last-child)::after {
            background: var(--success-color);
        }
        
        .form-step {
            padding: 40px;
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0.5; }
            to { opacity: 1; }
        }
        
        .form-step.active {
            display: block;
        }
        
        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
            font-size: 28px;
            font-weight: 600;
            position: relative;
            padding-bottom: 15px;
        }
        
        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 3px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: #495057;
            font-size: 15px;
        }
        
        label.required::after {
            content: " *";
            color: var(--danger-color);
        }
        
        input:not([type="file"]), select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: var(--border-radius);
            font-size: 15px;
            transition: var(--transition);
            background-color: #f8f9fa;
        }
        
        input:not([type="file"]):focus, select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            outline: none;
            background-color: #fff;
        }
        
        .row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .row > div {
            flex: 1;
            min-width: 200px;
        }
        
        .upload-container {
            border: 1px solid #ced4da;
            border-radius: var(--border-radius);
            padding: 12px 15px;
            text-align: center;
            cursor: pointer;
            background: #f8f9fa;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .upload-container:hover {
            background: #fff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            transform: none;
        }
        
        .upload-container:active {
            transform: translateY(1px);
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);
        }
        
        .upload-icon {
            font-size: 20px;
            color: var(--primary-color);
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }
        
        .upload-container:hover .upload-icon {
            transform: scale(1.05);
            color: var(--secondary-color);
        }
        
        .upload-text {
            font-weight: 500;
            margin-bottom: 0;
            color: #6c757d;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .upload-container:hover .upload-text {
            color: var(--primary-color);
        }
        
        .file-name {
            font-size: 13px;
            color: #555;
            margin-top: 8px;
            padding: 6px 10px;
            background: #e9ecef;
            border-radius: 4px;
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .upload-container.has-file {
            border-color: var(--success-color);
            background: rgba(76, 201, 240, 0.08);
        }
        
        .upload-container.has-file .upload-icon {
            color: var(--success-color);
        }
        
        .upload-container.has-file .upload-text {
            color: var(--success-color);
        }
        
        .btn-group {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            gap: 15px;
            width: 100%;
        }
        
        .btn-group button {
            flex: 1;
            min-width: 120px;
            padding: 12px 20px;
            border-radius: var(--border-radius);
            font-size: 15px;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            text-align: center;
        }
        
        .btn-prev {
            background: #6c757d;
            color: white;
            order: 1;
        }
        
        .btn-next {
            background: var(--primary-color);
            color: white;
            order: 2;
        }
        
        .btn-submit {
            background: var(--success-color);
            color: white;
            order: 3;
            flex: 2;
        }
        
        .btn-otp {
            background: var(--warning-color);
            color: white;
            padding: 10px 15px;
            white-space: nowrap;
            min-width: auto;
            flex-shrink: 0;
            border-radius: var(--border-radius);
        }
        .btn-otp.send-btn {
            background: var(--primary-color);
        }
        .btn-otp.verify-btn {
            background: #28a745;
        }
        
        button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        button i {
            font-size: 14px;
        }
        
        .otp-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .otp-group input {
            flex-grow: 1;
        }

        .hidden {
            display: none !important;
        }
        
        .error-message {
            color: var(--danger-color);
            font-size: 13px;
            margin-top: 5px;
            display: none;
        }
        
        .input-error {
            border-color: var(--danger-color) !important;
        }
        
        .success-message {
            color: var(--success-color);
            font-size: 14px;
            margin-top: 10px;
            text-align: center;
            font-weight: 500;
        }
        
        .otp-timer {
            font-size: 13px;
            color: #6c757d;
            margin-top: 5px;
            text-align: right;
        }

        .custom-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .custom-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .custom-modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: var(--box-shadow);
            text-align: center;
            max-width: 400px;
            width: 90%;
            transform: translateY(-20px);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        .custom-modal-overlay.active .custom-modal-content {
            transform: translateY(0);
            opacity: 1;
        }
        .custom-modal-content h3 {
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        .custom-modal-content p {
            margin-bottom: 20px;
            color: #555;
            line-height: 1.5;
        }
        .custom-modal-content button {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .custom-modal-content button:hover {
            background-color: var(--secondary-color);
        }

        .edit-mode-indicator {
            background-color: var(--warning-color);
            color: white;
            padding: 10px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
        }

        .document-note {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
            font-style: italic;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
                align-items: flex-start;
            }
            .container {
                margin: 15px auto;
                border-radius: 0;
            }
            
            .form-step {
                padding: 25px;
            }
            
            .row {
                flex-direction: column;
                gap: 15px;
            }
            /* Bank details container styles */
.bank-details-container {
    margin-top: 20px;
    border: 1px solid #e0e0e0;
    border-radius: var(--border-radius);
    padding: 15px;
    background-color: #f9f9f9;
}

.bank-details-header h4 {
    margin-bottom: 15px;
    color: var(--primary-color);
    font-size: 16px;
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 8px;
}

.bank-details-row {
    display: flex;
    margin-bottom: 8px;
    line-height: 1.4;
}

.bank-details-label {
    font-weight: 500;
    color: #555;
    min-width: 80px;
    display: inline-block;
}

.bank-details-value {
    flex: 1;
    color: #333;
}
            .btn-group {
                flex-direction: column;
            }
            
            .btn-group button {
                width: 100%;
                order: initial;
            }
            
            .btn-submit {
                order: 3;
            }
            
            .step-title {
                font-size: 12px;
            }
            
            .step-number {
                width: 35px;
                height: 35px;
            }
            
            .otp-group {
                flex-direction: column;
                align-items: stretch;
            }
            
            .otp-group button {
                width: 100%;
                margin-top: 10px;
                margin-left: 0;
            }
            
            .upload-container {
                padding: 15px;
            }
            
            .upload-icon {
                font-size: 18px;
            }
            .upload-text {
                font-size: 13px;
            }
        }
        
        .form-step[data-step="1"] .btn-group {
            justify-content: flex-end;
        }
        
        .form-step[data-step="5"] .btn-submit {
            flex: 3;
            font-size: 16px;
            padding: 14px 20px;
        } 
    </style>
</head>
<body>
    <div class="container">
        <?php if ($editMode): ?>
            <div class="edit-mode-indicator">
                <i class="fas fa-edit"></i> You are editing your existing registration
            </div>
        <?php endif; ?>

        <!-- Progress Bar -->
        <div class="progress-bar">
            <div class="step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-title">Personal Info</div>
            </div>
            <div class="step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-title">Contact & Bank</div>
            </div>
            <div class="step" data-step="3">
                <div class="step-number">3</div>
                <div class="step-title">ID Details</div>
            </div>
            <div class="step" data-step="4">
                <div class="step-number">4</div>
                <div class="step-title">Upload Documents</div>
            </div>
            <div class="step" data-step="5">
                <div class="step-number">5</div>
                <div class="step-title">Verification</div>
            </div>
        </div>

        <!-- Form Steps -->
        <form id="multi-step-form" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="isEdit" name="isEdit" value="<?= $editMode ? 'true' : 'false' ?>">

            <!-- Step 1: Personal Information -->
            <div class="form-step active" data-step="1">
                <h2>Personal Information</h2>
                <div class="row">
                    <div class="form-group">
                        <label for="firstName" class="required">First Name</label>
                        <input type="text" id="firstName" name="firstName" value="<?= htmlspecialchars($existingData['first_name'] ?? '') ?>" required>
                        <div id="firstNameError" class="error-message"></div>
                    </div>
                    <div class="form-group">
                        <label for="lastName" class="required">Last Name</label>
                        <input type="text" id="lastName" name="lastName" value="<?= htmlspecialchars($existingData['last_name'] ?? '') ?>" required>
                        <div id="lastNameError" class="error-message"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="dob" class="required">Date of Birth</label>
                    <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($existingData['dob'] ?? '') ?>" required>
                    <div id="dobError" class="error-message"></div>
                </div>
                <div class="form-group">
                    <label for="state" class="required">State</label>
                    <select id="state" name="state" required>
                        <option value="">Select State</option>
                        <option value="Karnataka" <?= ($existingData['state'] ?? '') == 'Karnataka' ? 'selected' : '' ?>>Karnataka</option>
                        <option value="Maharashtra" <?= ($existingData['state'] ?? '') == 'Maharashtra' ? 'selected' : '' ?>>Maharashtra</option>
                        <option value="Tamil Nadu" <?= ($existingData['state'] ?? '') == 'Tamil Nadu' ? 'selected' : '' ?>>Tamil Nadu</option>
                        <option value="Delhi" <?= ($existingData['state'] ?? '') == 'Delhi' ? 'selected' : '' ?>>Delhi</option>
                    </select>
                    <div id="stateError" class="error-message"></div>
                </div>
                <div class="form-group">
                    <label for="district" class="required">District</label>
                    <select id="district" name="district" required>
                        <option value="">Select District</option>
                        <option value="Bangalore Urban" <?= ($existingData['district'] ?? '') == 'Bangalore Urban' ? 'selected' : '' ?>>Bangalore Urban</option>
                        <option value="Mumbai" <?= ($existingData['district'] ?? '') == 'Mumbai' ? 'selected' : '' ?>>Mumbai</option>
                        <option value="Chennai" <?= ($existingData['district'] ?? '') == 'Chennai' ? 'selected' : '' ?>>Chennai</option>
                        <option value="New Delhi" <?= ($existingData['district'] ?? '') == 'New Delhi' ? 'selected' : '' ?>>New Delhi</option>
                    </select>
                    <div id="districtError" class="error-message"></div>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn-next">Next <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <!-- Step 2: Contact & Bank Details -->
            <div class="form-step" data-step="2">
                <h2>Contact & Bank Details</h2>
                <div class="row">
                    <div class="form-group">
                        <label for="contact1" class="required">Primary Contact</label>
                        <input type="text" id="contact1" name="contact1" value="<?= htmlspecialchars($existingData['contact1'] ?? '') ?>" required>
                        <div id="contact1Error" class="error-message"></div>
                    </div>
                    <div class="form-group">
                        <label for="contact2">Alternate Contact</label>
                        <input type="text" id="contact2" name="contact2" value="<?= htmlspecialchars($existingData['contact2'] ?? '') ?>">
                        <div id="contact2Error" class="error-message"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="bankName" class="required">Bank Name</label>
                    <input type="text" id="bankName" name="bankName" value="<?= htmlspecialchars($existingData['bank_name'] ?? '') ?>" required>
                    <div id="bankNameError" class="error-message"></div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label for="accountNumber" class="required">Account Number</label>
                        <input type="text" id="accountNumber" name="accountNumber" value="<?= htmlspecialchars($existingData['account_number'] ?? '') ?>" required>
                        <div id="accountNumberError" class="error-message"></div>
                    </div>
                    <div class="row">
    <div class="form-group">
        <label for="ifsc" class="required">IFSC Code</label>
        <input type="text" id="ifsc" name="ifsc" value="<?= htmlspecialchars($existingData['ifsc'] ?? '') ?>" required>
        <div id="ifscError" class="error-message"></div>
    </div>
</div>

<!-- Add this bank details container -->
<div id="bankDetailsContainer" class="bank-details-container hidden">
    <div class="bank-details-header">
        <h4>Bank Details</h4>
    </div>
    <div class="bank-details-content">
        <div class="bank-details-row">
            <span class="bank-details-label">Bank:</span>
            <span id="bankDetailName" class="bank-details-value"></span>
        </div>
        <div class="bank-details-row">
            <span class="bank-details-label">Branch:</span>
            <span id="bankDetailBranch" class="bank-details-value"></span>
        </div>
        <div class="bank-details-row">
            <span class="bank-details-label">Address:</span>
            <span id="bankDetailAddress" class="bank-details-value"></span>
        </div>
        <div class="bank-details-row">
            <span class="bank-details-label">City:</span>
            <span id="bankDetailCity" class="bank-details-value"></span>
        </div>
        <div class="bank-details-row">
            <span class="bank-details-label">State:</span>
            <span id="bankDetailState" class="bank-details-value"></span>
        </div>
    </div>
</div>
                <div class="btn-group">
                    <button type="button" class="btn-prev"><i class="fas fa-arrow-left"></i> Previous</button>
                    <button type="button" class="btn-next">Next <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <!-- Step 3: ID Details -->
            <div class="form-step" data-step="3">
                <h2>Identity Document Details</h2>
                <div class="form-group">
                    <label for="aadhaarNumber" class="required">Aadhaar Number</label>
                    <input type="text" id="aadhaarNumber" name="aadhaarNumber" value="<?= htmlspecialchars($existingData['aadhaar_number'] ?? '') ?>" required>
                    <div id="aadhaarNumberError" class="error-message"></div>
                </div>
                <div class="form-group">
                    <label for="panNumber" class="required">PAN Number</label>
                    <input type="text" id="panNumber" name="panNumber" value="<?= htmlspecialchars($existingData['pan_number'] ?? '') ?>" required>
                    <div id="panNumberError" class="error-message"></div>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn-prev"><i class="fas fa-arrow-left"></i> Previous</button>
                    <button type="button" class="btn-next">Next <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <!-- Step 4: Document Upload -->
            <div class="form-step" data-step="4">
                <h2>Upload Documents</h2>
                <div class="form-group">
                    <label for="photo" class="<?= $editMode ? '' : 'required' ?>">Upload Your Photo</label>
                    <div class="upload-container" id="photoUpload">
                        <input type="file" id="photo" name="photo" accept="image/*" <?= $editMode ? '' : 'required' ?>>
                        <i class="fas fa-user-circle upload-icon"></i>
                        <span class="upload-text">Click to upload your photo</span>
                        <span class="file-name"><?= $editMode ? 'Previously uploaded file will be used if not changed' : 'No file selected' ?></span>
                    </div>
                    <div id="photoError" class="error-message"></div>
                    <?php if ($editMode): ?>
                        <div class="document-note">Leave blank to keep existing document</div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="aadhaarInput" class="<?= $editMode ? '' : 'required' ?>">Upload Aadhaar Photo</label>
                    <div class="upload-container" id="aadhaarUpload">
                        <input type="file" id="aadhaarInput" name="aadhaarInput" accept="image/*,.pdf" <?= $editMode ? '' : 'required' ?>>
                        <i class="fas fa-id-card upload-icon"></i>
                        <span class="upload-text">Click to upload Aadhaar photo</span>
                        <span class="file-name"><?= $editMode ? 'Previously uploaded file will be used if not changed' : 'No file selected' ?></span>
                    </div>
                    <div id="aadhaarInputError" class="error-message"></div>
                    <?php if ($editMode): ?>
                        <div class="document-note">Leave blank to keep existing document</div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="panPhoto" class="<?= $editMode ? '' : 'required' ?>">Upload PAN Photo</label>
                    <div class="upload-container" id="panPhotoUpload">
                        <input type="file" id="panPhoto" name="panPhoto" accept="image/*,.pdf" <?= $editMode ? '' : 'required' ?>>
                        <i class="fas fa-credit-card upload-icon"></i>
                        <span class="upload-text">Click to upload PAN photo</span>
                        <span class="file-name"><?= $editMode ? 'Previously uploaded file will be used if not changed' : 'No file selected' ?></span>
                    </div>
                    <div id="panPhotoError" class="error-message"></div>
                    <?php if ($editMode): ?>
                        <div class="document-note">Leave blank to keep existing document</div>
                    <?php endif; ?>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn-prev"><i class="fas fa-arrow-left"></i> Previous</button>
                    <button type="button" class="btn-next">Next <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <!-- Step 5: Email Verification -->
            <div class="form-step" data-step="5">
                <h2>Email Verification</h2>
                <div class="form-group">
                    <label for="email" class="required">Email ID</label>
                    <input type="email" id="email" name="email" 
                           value="<?= htmlspecialchars($existingData['email'] ?? $emailFromDuty) ?>" 
                           <?= ($editMode || !empty($emailFromDuty)) ? 'readonly' : '' ?> required>
                    <div id="emailError" class="error-message"></div>
                    <input type="hidden" id="examId" name="examId" value="<?= htmlspecialchars($examIdFromDuty) ?>">
                </div>
                <div class="form-group">
                    <label for="otp">Enter OTP</label>
                    <div class="otp-group">
                        <input type="text" id="otp" name="otp" placeholder="Enter 6-digit OTP" maxlength="6">
                        <button type="button" id="sendOtpBtn" class="btn-otp">Send OTP</button>
                        <button type="button" id="verifyOtpBtn" class="btn-otp verify-btn">Verify</button>
                    </div>
                    <div id="otpError" class="error-message"></div>
                    <div class="otp-timer hidden" id="otpTimer">Resend OTP in 60s</div>
                </div>
                <div class="success-message hidden" id="otpSuccessMessage"></div>
                <div class="btn-group">
                    <button type="button" class="btn-prev"><i class="fas fa-arrow-left"></i> Previous</button>
                    <button type="submit" class="btn-submit" name="submit" id="finalSubmitBtn">
                        <?= $editMode ? 'Update Registration' : 'Submit Application' ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Custom Modal -->
    <div class="custom-modal-overlay" id="customModal">
        <div class="custom-modal-content">
            <h3 id="modalTitle"></h3>
            <p id="modalMessage"></p>
            <button id="modalCloseBtn">OK</button>
        </div>
    </div>

    <script>
    const form = document.getElementById('multi-step-form');
    const formSteps = document.querySelectorAll('.form-step');
    const progressSteps = document.querySelectorAll('.step');
    let currentStep = 1;
    let otpVerified = false;
    const isEditMode = <?= $editMode ? 'true' : 'false' ?>;

    const customModal = document.getElementById('customModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalCloseBtn = document.getElementById('modalCloseBtn');

    // IFSC and Bank Details Elements
    const ifscInput = document.getElementById('ifsc');
    const bankNameInput = document.getElementById('bankName');
    const ifscError = document.getElementById('ifscError');
    const bankDetailsContainer = document.getElementById('bankDetailsContainer');

    function showCustomModal(title, message, isSuccess = true) {
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        if (isSuccess) {
            modalTitle.style.color = '#4cc9f0';
        } else {
            modalTitle.style.color = '#f72585';
        }
        customModal.classList.add('active');
    }

    modalCloseBtn.addEventListener('click', () => {
        customModal.classList.remove('active');
        if (modalTitle.textContent.includes("Submitted") || modalTitle.textContent.includes("Updated")) {
            if (!isEditMode) {
                form.reset();
                currentStep = 1;
                updateFormStep();
                otpVerified = false;
                
                const sendOtpBtn = document.getElementById('sendOtpBtn');
                if (sendOtpBtn) {
                    sendOtpBtn.disabled = false;
                    sendOtpBtn.textContent = 'Send';
                }
                
                const verifyOtpBtn = document.getElementById('verifyOtpBtn');
                if (verifyOtpBtn) {
                    verifyOtpBtn.disabled = false;
                    verifyOtpBtn.textContent = 'Verify';
                }
                
                const emailInputForOtp = document.getElementById('email');
                if (emailInputForOtp) {
                    emailInputForOtp.removeAttribute('readonly');
                }
                
                const otpInput = document.getElementById('otp');
                if (otpInput) {
                    otpInput.removeAttribute('readonly');
                }

                const finalSubmitBtn = document.getElementById('finalSubmitBtn');
                if (finalSubmitBtn) {
                    finalSubmitBtn.disabled = false;
                    finalSubmitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Submit Application';
                }
            }
        }
    });

    function updateFormStep() {
        formSteps.forEach(step => {
            step.classList.remove('active');
            if (parseInt(step.dataset.step) === currentStep) {
                step.classList.add('active');
            }
        });

        progressSteps.forEach(step => {
            const stepNum = parseInt(step.dataset.step);
            step.classList.remove('active', 'completed');
            if (stepNum < currentStep) {
                step.classList.add('completed');
            } else if (stepNum === currentStep) {
                step.classList.add('active');
            }
        });
    }

    function validateStep(stepNum) {
        let isValid = true;
        const inputsToValidate = [];
        const errorMessages = {};

        if (stepNum === 1) {
            inputsToValidate.push(
                { id: 'firstName', pattern: /.+/, msg: 'First name is required' },
                { id: 'lastName', pattern: /.+/, msg: 'Last name is required' },
                { id: 'dob', pattern: /.+/, msg: 'Date of birth is required' },
                { id: 'state', pattern: /.+/, msg: 'State is required' },
                { id: 'district', pattern: /.+/, msg: 'District is required' }
            );
        } else if (stepNum === 2) {
            inputsToValidate.push(
                { id: 'contact1', pattern: /^\d{10,15}$/, msg: 'Valid 10-15 digit phone number required' },
                { id: 'bankName', pattern: /.+/, msg: 'Bank name is required' },
                { id: 'accountNumber', pattern: /^\d{9,18}$/, msg: 'Valid 9-18 digit account number required' },
                { id: 'ifsc', pattern: /^[A-Z]{4}0[A-Z0-9]{6}$/, msg: 'Valid IFSC code required' }
            );
        } else if (stepNum === 3) {
            inputsToValidate.push(
                { id: 'aadhaarNumber', pattern: /^\d{12}$/, msg: '12 digit Aadhaar number required' },
                { id: 'panNumber', pattern: /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/, msg: 'Valid PAN number required' }
            );
        } else if (stepNum === 4 && !isEditMode) {
            inputsToValidate.push(
                { id: 'photo', type: 'file', msg: 'Photo is required' },
                { id: 'aadhaarInput', type: 'file', msg: 'Aadhaar document is required' },
                { id: 'panPhoto', type: 'file', msg: 'PAN document is required' }
            );
        }

        inputsToValidate.forEach(input => {
            const element = document.getElementById(input.id);
            if (!element) return;

            let errorElement = document.getElementById(`${input.id}Error`);
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.id = `${input.id}Error`;
                errorElement.className = 'error-message';
                element.parentNode.appendChild(errorElement);
            }

            let isFieldValid = true;
            
            if (input.type === 'file') {
                isFieldValid = element.files.length > 0;
            } else {
                const value = element.value.trim();
                isFieldValid = input.pattern.test(value);
            }

            if (!isFieldValid) {
                element.classList.add('input-error');
                errorElement.textContent = input.msg;
                errorElement.style.display = 'block';
                isValid = false;
                
                if (isValid === false) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            } else {
                element.classList.remove('input-error');
                errorElement.style.display = 'none';
            }
        });

        return isValid;
    }

    // IFSC Validation and Bank Details Fetch
    ifscInput.addEventListener('blur', async function() {
        const ifscCode = this.value.trim().toUpperCase();
        
        if (!/^[A-Z]{4}0[A-Z0-9]{6}$/.test(ifscCode)) {
            ifscError.textContent = 'Please enter a valid IFSC code (e.g., SBIN0001234)';
            ifscError.style.display = 'block';
            ifscInput.classList.add('input-error');
            bankDetailsContainer.classList.add('hidden');
            return;
        }
        
        try {
            ifscInput.disabled = true;
            ifscError.textContent = 'Fetching bank details...';
            ifscError.style.display = 'block';
            ifscError.style.color = '#4361ee';
            
            const response = await fetch(`https://ifsc.razorpay.com/${ifscCode}`);
            
            if (!response.ok) {
                throw new Error('Bank details not found for this IFSC code');
            }
            
            const bankDetails = await response.json();
            
            bankNameInput.value = bankDetails.BANK || '';
            
            document.getElementById('bankDetailName').textContent = bankDetails.BANK || 'N/A';
            document.getElementById('bankDetailBranch').textContent = bankDetails.BRANCH || 'N/A';
            document.getElementById('bankDetailAddress').textContent = bankDetails.ADDRESS || 'N/A';
            document.getElementById('bankDetailCity').textContent = bankDetails.CITY || 'N/A';
            document.getElementById('bankDetailState').textContent = bankDetails.STATE || 'N/A';
            
            bankDetailsContainer.classList.remove('hidden');
            ifscError.style.display = 'none';
            ifscInput.classList.remove('input-error');
            
        } catch (error) {
            console.error('Error:', error);
            ifscError.textContent = error.message || 'Failed to fetch details. Please enter manually.';
            ifscError.style.display = 'block';
            ifscError.style.color = '#f72585';
            ifscInput.classList.add('input-error');
            bankDetailsContainer.classList.add('hidden');
        } finally {
            ifscInput.disabled = false;
        }
    });

    // Age validation
    document.getElementById('dob').addEventListener('change', function() {
        if (!validateAge(this.value)) {
            showCustomModal('Age Restriction', 'You must be at least 18 years old to register.', false);
            this.value = '';
            document.getElementById('dobError').style.display = 'block';
            document.getElementById('dobError').textContent = 'You must be at least 18 years old.';
        } else {
            document.getElementById('dobError').style.display = 'none';
        }
    });

    function validateAge(dob) {
        if (!dob) return false;
        const today = new Date();
        const birthDate = new Date(dob);
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        return age >= 18;
    }

    // Navigation
    function goToStep(step) {
        if (step < 1 || step > 5) return;
        
        if (step > currentStep && !validateStep(currentStep)) {
            return;
        }
        
        currentStep = step;
        updateFormStep();
    }

    document.querySelectorAll('.btn-next').forEach(btn => {
        btn.addEventListener('click', () => goToStep(currentStep + 1));
    });

    document.querySelectorAll('.btn-prev').forEach(btn => {
        btn.addEventListener('click', () => goToStep(currentStep - 1));
    });

    // OTP Handling
    let otpTimerInterval;
    const otpTimerDisplay = document.getElementById('otpTimer');
    const sendOtpBtn = document.getElementById('sendOtpBtn');
    const verifyOtpBtn = document.getElementById('verifyOtpBtn');
    const otpSuccessMessage = document.getElementById('otpSuccessMessage');
    const emailInputForOtp = document.getElementById('email');

    sendOtpBtn.addEventListener('click', async () => {
        const emailValue = emailInputForOtp.value.trim();
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailPattern.test(emailValue)) {
            document.getElementById('emailError').textContent = 'Please enter a valid email address to send OTP.';
            document.getElementById('emailError').style.display = 'block';
            emailInputForOtp.classList.add('input-error');
            return;
        } else {
            document.getElementById('emailError').style.display = 'none';
            emailInputForOtp.classList.remove('input-error');
        }

        sendOtpBtn.disabled = true;
        sendOtpBtn.textContent = 'Sending...';
        otpSuccessMessage.classList.remove('hidden');
        otpSuccessMessage.textContent = 'Sending OTP...';
        otpTimerDisplay.classList.add('hidden');

        let timeLeft = 60;
        
        try {
            const response = await fetch('?action=send-otp', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email: emailValue })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                otpSuccessMessage.textContent = data.message;
                otpTimerDisplay.textContent = `Resend OTP in ${timeLeft}s`;
                otpTimerDisplay.classList.remove('hidden');

                clearInterval(otpTimerInterval);
                otpTimerInterval = setInterval(() => {
                    timeLeft--;
                    otpTimerDisplay.textContent = `Resend OTP in ${timeLeft}s`;
                    if (timeLeft <= 0) {
                        clearInterval(otpTimerInterval);
                        sendOtpBtn.disabled = false;
                        sendOtpBtn.textContent = 'Resend OTP';
                        otpTimerDisplay.classList.add('hidden');
                    }
                }, 1000);
            } else {
                throw new Error(data.message || 'Failed to send OTP');
            }
        } catch (error) {
            console.error('Error sending OTP:', error);
            showCustomModal('Error', error.message || 'Failed to send OTP. Please try again.', false);
            sendOtpBtn.disabled = false;
            sendOtpBtn.textContent = 'Send OTP';
            otpSuccessMessage.classList.add('hidden');
        }
    });

    verifyOtpBtn.addEventListener('click', async () => {
        const email = emailInputForOtp.value.trim();
        const otp = document.getElementById('otp').value.trim();

        const otpPattern = /^\d{6}$/;
        if (!otpPattern.test(otp)) {
            document.getElementById('otpError').textContent = 'Please enter a valid 6-digit OTP.';
            document.getElementById('otpError').style.display = 'block';
            document.getElementById('otp').classList.add('input-error');
            return;
        } else {
            document.getElementById('otpError').style.display = 'none';
            document.getElementById('otp').classList.remove('input-error');
        }

        if (!email) {
            showCustomModal('Error', 'Email is required to verify OTP.', false);
            return;
        }
        
        verifyOtpBtn.disabled = true;
        verifyOtpBtn.textContent = 'Verifying...';

        try {
            const response = await fetch('?action=verify-otp', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email, otp })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();

            if (data.success) {
                otpVerified = true;
                emailInputForOtp.setAttribute('readonly', true);
                document.getElementById('otp').setAttribute('readonly', true);
                sendOtpBtn.disabled = true;
                verifyOtpBtn.disabled = true;
                otpSuccessMessage.textContent = 'OTP verified successfully!';
                otpSuccessMessage.classList.remove('hidden');
            } else {
                throw new Error(data.message || 'OTP verification failed');
            }
        } catch (error) {
            console.error('OTP Verification Error:', error);
            document.getElementById('otpError').textContent = error.message;
            document.getElementById('otpError').style.display = 'block';
            document.getElementById('otp').classList.add('input-error');
        } finally {
            verifyOtpBtn.disabled = false;
            verifyOtpBtn.textContent = 'Verify';
        }
    });

    // File Upload Handling
    document.querySelectorAll('.upload-container').forEach(container => {
        const fileInput = container.querySelector('input[type="file"]');
        const fileNameSpan = container.querySelector('.file-name');

        container.addEventListener('click', () => {
            fileInput.click();
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                fileNameSpan.textContent = fileInput.files[0].name;
                container.classList.add('has-file');
                const errorElement = document.getElementById(fileInput.id + 'Error');
                if (errorElement) {
                    errorElement.style.display = 'none';
                    fileInput.classList.remove('input-error');
                }
            } else {
                fileNameSpan.textContent = isEditMode ? 'Previously uploaded file will be used if not changed' : 'No file selected';
                container.classList.remove('has-file');
            }
        });

        container.addEventListener('dragover', (e) => {
            e.preventDefault();
            container.style.borderColor = '#3f37c9';
            container.style.backgroundColor = 'rgba(67, 97, 238, 0.05)';
        });

        container.addEventListener('dragleave', () => {
            container.style.borderColor = '#ced4da';
            container.style.backgroundColor = '#f8f9fa';
        });

        container.addEventListener('drop', (e) => {
            e.preventDefault();
            container.style.borderColor = '#ced4da';
            container.style.backgroundColor = '#f8f9fa';

            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                fileNameSpan.textContent = fileInput.files[0].name;
                container.classList.add('has-file');
                const errorElement = document.getElementById(fileInput.id + 'Error');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            }
        });
    });

    // Form Submission
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!validateStep(currentStep)) {
            return;
        }

        if (ifscInput.disabled) {
            showCustomModal('Please Wait', 'Bank details are still being fetched. Please wait...', false);
            return;
        }

        const dob = document.getElementById('dob').value;
        if (dob && !validateAge(dob)) {
            showCustomModal('Age Restriction', 'You must be at least 18 years old to register.', false);
            return;
        }

        if (!otpVerified) {
            showCustomModal('Verification Required', 'Please verify your email with OTP before submitting.', false);
            return;
        }

        const finalSubmitBtn = document.getElementById('finalSubmitBtn');
        finalSubmitBtn.disabled = true;
        finalSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (isEditMode ? 'Updating...' : 'Submitting...');

        const formData = {
            firstName: document.getElementById('firstName').value,
            lastName: document.getElementById('lastName').value,
            dob: document.getElementById('dob').value,
            email: document.getElementById('email').value,
            contact1: document.getElementById('contact1').value,
            contact2: document.getElementById('contact2').value,
            state: document.getElementById('state').value,
            district: document.getElementById('district').value,
            bankName: document.getElementById('bankName').value,
            accountNumber: document.getElementById('accountNumber').value,
            ifsc: document.getElementById('ifsc').value,
            aadhaarNumber: document.getElementById('aadhaarNumber').value,
            panNumber: document.getElementById('panNumber').value,
            examId: document.getElementById('examId').value,
            otp: document.getElementById('otp').value,
            isEdit: isEditMode
        };

        const photoFile = document.getElementById('photo').files[0];
        const aadhaarFile = document.getElementById('aadhaarInput').files[0];
        const panFile = document.getElementById('panPhoto').files[0];

        try {
            const fileToBase64 = (file) => {
                return new Promise((resolve, reject) => {
                    if (!file) {
                        resolve('');
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = () => resolve(reader.result);
                    reader.onerror = error => reject(error);
                    reader.readAsDataURL(file);
                });
            };

            const [photoBase64, aadhaarBase64, panBase64] = await Promise.all([
                fileToBase64(photoFile),
                fileToBase64(aadhaarFile),
                fileToBase64(panFile)
            ]);

            if (photoBase64) formData.photo = photoBase64;
            if (aadhaarBase64) formData.aadhaar = aadhaarBase64;
            if (panBase64) formData.panPhoto = panBase64;

            const response = await fetch('?action=submit-form', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                showCustomModal(
                    isEditMode ? 'Registration Updated!' : 'Application Submitted!', 
                    data.message + (data.registrationId ? `Your Registration ID: ${data.registrationId}` : ''), 
                    true
                );
            } else {
                throw new Error(data.message || 'Submission failed');
            }
        } catch (error) {
            console.error('Error submitting form:', error);
            showCustomModal(
                'Error', 
                error.message || 'An error occurred while ' + (isEditMode ? 'updating' : 'submitting') + ' the form. Please try again.', 
                false
            );
            finalSubmitBtn.disabled = false;
            finalSubmitBtn.innerHTML = isEditMode ? '<i class="fas fa-edit"></i> Update Registration' : '<i class="fas fa-check-circle"></i> Submit Application';
        }
    });

    // Initialize the form
    updateFormStep();
</script>
</body>
</html>