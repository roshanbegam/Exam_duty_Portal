<?php
$conn = new mysqli("localhost", "root", "", "duty_portal");

if ($conn->connect_error) {
    die("DB connection failed");
}

$name = $_POST['name'];
$duty_date = $_POST['duty_date'];
$location = $_POST['location'];
$arrival_time = $_POST['arrival_time'];

// Handle file upload
$target_dir = "uploads/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}
$filename = time() . "_" . basename($_FILES["csr_image"]["name"]);
$target_path = $target_dir . $filename;

if (move_uploaded_file($_FILES["csr_image"]["tmp_name"], $target_path)) {
    $stmt = $conn->prepare("INSERT INTO attendance (name, duty_date, location, arrival_time, csr_image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $duty_date, $location, $arrival_time, $target_path);
    $stmt->execute();
    $stmt->close();

    echo "success";
} else {
    echo "error_uploading_image";
}

$conn->close();
?>
