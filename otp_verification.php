<?php
// verify_otp.php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "duty_portal";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $otp = $_POST['otp'];

    $stmt = $conn->prepare("SELECT otp FROM registrations WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($storedOtp);
    $stmt->fetch();
    $stmt->close();

    if ($storedOtp == $otp) {
        $update = $conn->prepare("UPDATE registrations SET otp_verified = 1 WHERE email = ?");
        $update->bind_param("s", $email);
        $update->execute();
        echo "<p>OTP verified successfully. Your application is now pending approval.</p>";
    } else {
        echo "<p>Invalid OTP.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>OTP Verification</title>
</head>
<body>
<h2>Verify OTP</h2>
<form action="verify_otp.php" method="post">
    <label>Email:</label><input type="email" name="email" required><br>
    <label>OTP:</label><input type="text" name="otp" required><br>
    <input type="submit" value="Verify">
</form>
</body>
</html>
