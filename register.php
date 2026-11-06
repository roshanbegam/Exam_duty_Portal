<?php
session_start();
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// DB Connection
$db = new mysqli("localhost", "root", "duty_portal");

// Pre-fill email and exam_id from previous step
$emailFromDuty = $_GET['email'] ?? '';
$examIdFromDuty = $_GET['exam_id'] ?? '';

// Handle AJAX Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);

    if ($_GET['action'] === 'send-otp') {
        $email = $data['email'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email']);
            exit;
        }

        $otp = rand(100000, 999999);
        $_SESSION['otp'][$email] = $otp;

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'roshanb000b@gmail.com';  // Replace
            $mail->Password = 'jqrt gkfv wylk zhah';     // Replace
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('roshanb000b@gmail.com', 'Mail');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your OTP';
            $mail->Body = "Your OTP is <b>$otp</b>";
            $mail->send();

            echo json_encode(['success' => true, 'message' => 'OTP sent']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to send OTP']);
        }
        exit;
    }

    if ($_GET['action'] === 'verify-otp') {
        $email = $data['email'] ?? '';
        $otp = $data['otp'] ?? '';
        if (isset($_SESSION['otp'][$email]) && $_SESSION['otp'][$email] == $otp) {
            unset($_SESSION['otp'][$email]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }

    if ($_GET['action'] === 'submit-form') {
        $stmt = $db->prepare("INSERT INTO registered_users 
            (first_name, last_name, dob, email, contact1, contact2, state, district, bank_name, account_number, ifsc, aadhaar_number, pan_number, aadhaar_base64, photo_base64, pan_photo_base64, exam_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("sssssssssssssssss",
            $data['firstName'], $data['lastName'], $data['dob'], $data['email'],
            $data['contact1'], $data['contact2'], $data['state'], $data['district'],
            $data['bankName'], $data['accountNumber'], $data['ifsc'],
            $data['aadhaarNumber'], $data['panNumber'],
            $data['aadhaar'], $data['photo'], $data['panPhoto'], $data['examId']
        );

        echo json_encode(['success' => $stmt->execute()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>User Registration</title>
  <style>
    body { font-family:sans-serif;
      background:#f2f2f2;
      padding:20px;
     }
    .container { 
      max-width:700px;
      margin:auto;
      background:#fff;
      padding:20px;
      border-radius:10px;
      box-shadow:0 0 10px rgba(0,0,0,0.1); }
    h2 { 
      text-align:center;
       margin-bottom: 20px;
      }
    form { 
      display: grid; 
      grid-gap: 15px; }
    .row { 
      display: flex;
       gap: 15px; }
    .row > div { 
      flex: 1;
       display: flex;
       flex-direction: column;
       }
    label.required::after { 
      content:" *";
      color:red; }
    input, select, button { 
      padding: 8px;
       border: 1px solid #ccc;
       border-radius: 5px;
       font-size: 1rem; }
    button { 
      cursor: pointer;
     }
    button.send-btn {
       background:#007bff;
       color:white;
       border:none;
       max-width: 120px;
       align-self: flex-end;
      }
    button.verify-btn { background:#ffc107;
       color:black; border:none;
        max-width: 120px;
         align-self: flex-end;
        }
    button#submitBtn {
  background: #28a745;
  color: white;
  border: none;
  width: auto;
  max-width: 140px;
  font-weight: bold;
  font-size: 1rem;
  padding: 8px 16px;
  align-self: flex-end;
  margin-top: 10px;
  border-radius: 5px;
  box-shadow: 0 4px 10px rgba(40, 167, 69, 0.4);
}
button#submitBtn:hover {
  background-color: #218838;
}

    input[type="file"] { padding: 3px; }
  </style>
</head>
<body>
  <div class="container">
    <h2>User Registration with OTP</h2>
    <form id="registrationForm" autocomplete="off">

      <div class="row">
        <div><label class="required" for="firstName">First Name</label><input type="text" id="firstName" required></div>
        <div><label class="required" for="lastName">Last Name</label><input type="text" id="lastName" required></div>
      </div>

      <div class="row">
        <div><label class="required" for="dob">Date of Birth</label><input type="date" id="dob" required></div>
        <div><label class="required" for="photo">Upload Your Photo</label><input type="file" id="photo" accept="image/*" required></div>
      </div>

      <div class="row">
        <div><label class="required" for="state">State</label>
          <select id="state" required>
            <option value="">Select State</option>
            <option value="Karnataka">Karnataka</option>
            <option value="Maharashtra">Maharashtra</option>
            <option value="Tamil Nadu">Tamil Nadu</option>
            <option value="Delhi">Delhi</option>
            <!-- Add more states as needed -->
          </select>
        </div>
        <div><label class="required" for="district">District</label>
          <select id="district" required>
            <option value="">Select District</option>
            <option value="Bangalore Urban">Bangalore Urban</option>
            <option value="Mumbai">Mumbai</option>
            <option value="Chennai">Chennai</option>
            <option value="New Delhi">New Delhi</option>
            <!-- Add more districts as needed -->
          </select>
        </div>
      </div>

      <div class="row">
        <div><label class="required" for="aadhaarNumber">Aadhaar Number</label><input type="text" id="aadhaarNumber" required></div>
        <div><label class="required" for="aadhaarInput">Upload Aadhaar Photo</label><input type="file" id="aadhaarInput" accept="image/*" required></div>
      </div>

      <div class="row">
        <div><label class="required" for="panNumber">PAN Number</label><input type="text" id="panNumber" required></div>
        <div><label class="required" for="panPhoto">Upload PAN Photo</label><input type="file" id="panPhoto" accept="image/*" required></div>
      </div>

      <div class="row" style="align-items: flex-end;">
        <div style="flex:2;">
          <label class="required" for="email">Email ID</label>
          <input type="email" id="email" value="<?= htmlspecialchars($emailFromDuty) ?>" <?= $emailFromDuty ? 'readonly' : '' ?> required>
          <input type="hidden" id="examId" value="<?= htmlspecialchars($examIdFromDuty) ?>">
        </div>
        <div style="flex:1;">
          <label for="otp">Enter OTP</label>
          <input type="text" id="otp" placeholder="OTP">
        </div>
        <div>
          <button type="button" class="send-btn" onclick="sendOTP()">Send</button>
        </div>
        <div>
          <button type="button" class="verify-btn" onclick="verifyOTP()">Verify</button>
        </div>
      </div>

      <div class="row">
        <div><label class="required" for="contact1">Primary Contact</label><input type="text" id="contact1" required></div>
        <div><label class="required" for="contact2">Alternate Contact</label><input type="text" id="contact2" required></div>
      </div>

      <div class="row">
        <div><label class="required" for="bankName">Bank Name</label><input type="text" id="bankName" required></div>
        <div><label class="required" for="accountNumber">Account Number</label><input type="text" id="accountNumber" required></div>
        <div><label class="required" for="ifsc">IFSC Code</label><input type="text" id="ifsc" required></div>
      </div>

      <button id="submitBtn" type="submit">Submit</button>
    </form>
  </div>

<script>
let otpVerified = false;

async function sendOTP() {
  const email = document.getElementById('email').value.trim();
  if (!email) return alert("Enter email");
  const res = await fetch('?action=send-otp', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({email})
  });
  const data = await res.json();
  alert(data.message);
}

async function verifyOTP() {
  const email = document.getElementById('email').value.trim();
  const otp = document.getElementById('otp').value.trim();
  if (!otp) return alert("Enter OTP");
  const res = await fetch('?action=verify-otp', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({email, otp})
  });
  const data = await res.json();
  if (data.success) {
    alert("OTP Verified");
    otpVerified = true;
  } else alert("Invalid OTP");
}

function fileToBase64(file) {
  return new Promise((resolve,reject)=>{
    if (!file) resolve('');
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = e => reject(e);
    reader.readAsDataURL(file);
  });
}

document.getElementById('registrationForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  if (!otpVerified) return alert("Verify OTP first");

  try {
    const [aadhaar, photo, panPhoto] = await Promise.all([
      fileToBase64(document.getElementById('aadhaarInput').files[0]),
      fileToBase64(document.getElementById('photo').files[0]),
      fileToBase64(document.getElementById('panPhoto').files[0])
    ]);

    const payload = {
  firstName: document.getElementById('firstName').value.trim(),
  lastName: document.getElementById('lastName').value.trim(),
  dob: document.getElementById('dob').value,
  email: document.getElementById('email').value.trim(),
  contact1: document.getElementById('contact1').value.trim(),
  contact2: document.getElementById('contact2').value.trim(),
  state: document.getElementById('state').value,
  district: document.getElementById('district').value,
  bankName: document.getElementById('bankName').value.trim(),
  accountNumber: document.getElementById('accountNumber').value.trim(),
  ifsc: document.getElementById('ifsc').value.trim(),
  aadhaarNumber: document.getElementById('aadhaarNumber').value.trim(),
  panNumber: document.getElementById('panNumber').value.trim(),
  aadhaar, photo, panPhoto,
  examId: document.getElementById('examId').value
};


    const res = await fetch('?action=submit-form', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      alert("Form Submitted!");
      this.reset();
      otpVerified = false;
    } else alert("Submission failed");
  } catch (err) {
    alert("Please upload all required files.");
  }
});
</script>
</body>
</html>
