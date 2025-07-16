<?php
session_start();

// Authentication check
if (!isset($_SESSION['user']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "", "duty_portal");
//$conn = new mysqli("localhost", "shruthac", "VSE(8r[9kjNm29", "shruthac_duty_portal", 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$message = "";
$current_section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

// Handle admin creation
if ($_SERVER["REQUEST_METHOD"] === "POST" && $current_section === 'add_admin') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (strtolower($fullname) === 'admin') {
        $message = "Creating another admin with the name 'admin' is not allowed.";
    } else {
        // Check if email exists
        $stmtCheck = $conn->prepare("SELECT email FROM admins WHERE email = ? UNION SELECT email FROM users WHERE email = ?");
        $stmtCheck->bind_param("ss", $email, $email);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if ($stmtCheck->num_rows > 0) {
            $message = "User already registered with this email.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admins (fullname, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $fullname, $email, $hashedPassword);
            if ($stmt->execute()) {
                $message = "New admin added successfully!";
            } else {
                $message = "Error: " . $conn->error;
            }
            $stmt->close();
        }
        $stmtCheck->close();
    }
}

// Handle exam submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && $current_section === 'manage_exam') {
    if (isset($_POST['exams'])) {
        $exams = json_decode($_POST['exams'], true);
        
        // Delete all existing exams (optional - you might want to keep old ones)
        $conn->query("DELETE FROM exams");
        
        // Prepare statement for inserting exams
        $stmt = $conn->prepare("INSERT INTO exams (exam, center_code, center_name, exam_date, state, district, start_time, end_time, shift, duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($exams as $exam) {
            $stmt->bind_param("ssssssssss", 
                $exam['examName'],
                $exam['centerCode'],
                $exam['examCenter'],
                $exam['examDate'],
                $exam['state'],
                $exam['district'],
                $exam['shiftStart'],
                $exam['shiftEnd'],
                $exam['dutyShift'],
                $exam['duration']
            );
            $stmt->execute();
        }
        
        $message = "Exams saved successfully!";
        $stmt->close();
    }
}

// Handle user verification and payment status
if ($_SERVER["REQUEST_METHOD"] === "POST" && $current_section === 'verify_users') {
    if (isset($_POST['user_id'], $_POST['action'])) {
        $user_id = intval($_POST['user_id']);
        $action = $_POST['action'];

        if (in_array($action, ['paid', 'not_paid', 'accepted', 'rejected'])) {
            if ($action === 'accepted' || $action === 'rejected') {
                $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
            } else {
                $stmt = $conn->prepare("UPDATE users SET is_paid = ? WHERE id = ?");
            }
            $stmt->bind_param("si", $action, $user_id);
            if ($stmt->execute()) {
                $message = "User status updated successfully!";
            } else {
                $message = "Error updating user status: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Fetch existing exams for display
$exams = [];
if ($current_section === 'manage_exam') {
    $result = $conn->query("SELECT exam as examName, center_code as centerCode, center_name as examCenter, exam_date as examDate, state, district, start_time as shiftStart, end_time as shiftEnd, shift as dutyShift, duration FROM exams");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $exams[] = $row;
        }
    }
}

// Fetch users for verification section
$users = [];
if ($current_section === 'verify_users') {
    $result = $conn->query("SELECT * FROM users ORDER BY id DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --danger: #f72585;
            --success: #4cc9f0;
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --sidebar-bg: linear-gradient(135deg, #2a2a72 0%, #009ffd 100%);
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            overflow-x: hidden;
            min-height: 100vh;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: var(--sidebar-bg);
            color: white;
            position: fixed;
            height: 100vh;
            z-index: 10;
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .sidebar-menu {
            padding: 15px 0;
            height: calc(100vh - 100px);
            overflow-y: auto;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            margin: 5px 0;
        }

        .menu-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .menu-item.active {
            background: rgba(255, 255, 255, 0.15);
            border-left: 4px solid var(--accent);
        }

        .menu-item i {
            margin-right: 12px;
            font-size: 1.1rem;
        }

        .menu-item .badge {
            margin-left: auto;
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 25px;
            transition: all 0.3s ease;
            margin-left: 280px;
            width: calc(100% - 280px);
            min-height: 100vh;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .time-display {
            background: rgba(67, 97, 238, 0.1);
            padding: 8px 15px;
            border-radius: 30px;
            font-size: 0.9rem;
            color: var(--primary);
            font-weight: 500;
        }

        /* Content Sections */
        .content-section {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .content-section.active {
            display: block;
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary);
            font-weight: 600;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: var(--dark);
            font-weight: 600;
        }

        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            outline: none;
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s;
            text-align: center;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        /* Messages */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(76, 201, 240, 0.1);
            color: #0a9396;
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background-color: rgba(247, 37, 133, 0.1);
            color: #ae2012;
            border-left: 4px solid var(--danger);
        }

        /* Windows Activation Notice */
        .windows-activate {
            padding: 15px;
            background-color: rgba(72, 149, 239, 0.1);
            border-left: 4px solid var(--accent);
            color: var(--primary);
            margin-top: 30px;
            border-radius: 0 8px 8px 0;
            font-size: 0.9rem;
        }

        /* Exam Management Styles */
        .exam-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 0.9rem;
        }
        
        .exam-table th, .exam-table td {
            border: 1px solid #e0e0e0;
            padding: 10px;
            text-align: left;
        }
        
        .exam-table th {
            background-color:rgb(96, 91, 194);
            font-weight: 600;
            color: rgb(252, 252, 253);
        }
        
        .exam-table input, .exam-table select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        
        .exam-table input:focus, .exam-table select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.1);
            outline: none;
        }
        
        .exam-table input[type="time"], .exam-table input[type="date"] {
            padding: 7px 10px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-danger {
            background-color: var(--danger);
        }
        
        .btn-danger:hover {
            background-color: #e5177e;
        }
        
        .btn-success {
            background-color: var(--success);
        }
        
        .btn-success:hover {
            background-color: #3ab4d8;
        }
        
        .btn-secondary {
            background-color: var(--gray);
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }

        /* Enhanced User Verification Styles */
        .user-table-container {
            overflow-x: auto;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            margin: 20px 0;
        }

        .user-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            min-width: 800px;
        }

        .user-table th {
            background-color: #4361ee;
            color: white;
            font-weight: 500;
            padding: 15px;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .user-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            text-align: center;
            vertical-align: middle;
            transition: background 0.2s ease;
        }

        .user-table tr:not(:last-child) td {
            border-bottom: 1px solid #f0f0f0;
        }

        .user-table tr:hover td {
            background-color: #f8f9ff;
        }

        .user-table tr:last-child td {
            border-bottom: none;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-paid {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .status-not-paid {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .status-pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .status-accepted {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .status-rejected {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        /* Action Buttons */
        .user-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
        }

        .user-form {
            display: inline-block;
            margin: 0;
        }

        .user-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            color: white;
            min-width: 80px;
        }

        .user-btn i {
            margin-right: 5px;
            font-size: 0.7rem;
        }

        .user-btn-paid {
            background-color: #28a745;
        }

        .user-btn-paid:hover {
            background-color: #218838;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(40, 167, 69, 0.2);
        }

        .user-btn-not-paid {
            background-color: #dc3545;
        }

        .user-btn-not-paid:hover {
            background-color: #c82333;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(220, 53, 69, 0.2);
        }

        .user-btn-accept {
            background-color: #17a2b8;
        }

        .user-btn-accept:hover {
            background-color: #138496;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(23, 162, 184, 0.2);
        }

        .user-btn-reject {
            background-color: #6c757d;
        }

        .user-btn-reject:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(108, 117, 125, 0.2);
        }

        /* Empty State */
        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 2rem;
            color: #dee2e6;
            margin-bottom: 15px;
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                overflow: hidden;
            }
            
            .sidebar-header h2, .sidebar-header p, .menu-item span {
                display: none;
            }
            
            .menu-item {
                justify-content: center;
                padding: 15px 0;
            }
            
            .menu-item i {
                margin-right: 0;
                font-size: 1.3rem;
            }
            
            .main-content {
                margin-left: 80px;
                width: calc(100% - 80px);
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .user-info {
                width: 100%;
                justify-content: space-between;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-buttons .btn {
                width: 100%;
            }
            
            .user-table th, .user-table td {
                padding: 10px 8px;
                font-size: 0.85rem;
            }
            
            .user-btn {
                padding: 6px 8px;
                min-width: 70px;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 576px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .user-actions {
                flex-direction: column;
                gap: 5px;
            }
            
            .user-btn {
                width: 100%;
            }
            
            .status-badge {
                padding: 3px 8px;
                font-size: 0.7rem;
            }
        }

        /* Animation for status changes */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .status-updated {
            animation: pulse 0.5s ease;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?></p>
            </div>
            
            <div class="sidebar-menu">
                <a href="?section=dashboard" class="menu-item <?php echo $current_section == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="?section=add_admin" class="menu-item <?php echo $current_section == 'add_admin' ? 'active' : ''; ?>">
                    <i class="fas fa-user-plus"></i>
                    <span>Add New Admin</span>
                </a>
                <a href="?section=manage_exam" class="menu-item <?php echo $current_section == 'manage_exam' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Manage Exam</span>
                </a>
                <a href="?section=verify_users" class="menu-item <?php echo $current_section == 'verify_users' ? 'active' : ''; ?>">
                    <i class="fas fa-user-check"></i>
                    <span>Verify Users</span>
                    <?php 
                    $pending_count = 0;
                    foreach ($users as $user) {
                        if ($user['status'] === 'pending' || empty($user['status'])) {
                            $pending_count++;
                        }
                    }
                    if ($pending_count > 0): ?>
                        <span class="badge"><?php echo $pending_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?section=view_attendance" class="menu-item <?php echo $current_section == 'view_attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-info-circle"></i>
                    <span>View Attendance</span>
                </a>
                <a href="?section=contact" class="menu-item <?php echo $current_section == 'contact' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i>
                    <span>Contact</span>
                </a>
                <a href="logout.php" class="menu-item" onclick="return confirm('Are you sure you want to log out?')">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content Area -->
        <div class="main-content">
            <div class="header">
                <h1><?php 
                    $sectionTitles = [
                        'dashboard' => 'Dashboard',
                        'add_admin' => 'Add New Admin',
                        'manage_exam' => 'Exam Management',
                        'verify_users' => 'User Verification',
                        'view_attendance' => 'Attendance Submissions',
                        'contact' => 'Contact Support'
                    ];
                    echo $sectionTitles[$current_section] ?? 'Admin Panel';
                ?></h1>
                <div class="user-info">
                    <div class="time-display" id="current-time">
                        <?php echo date("h:i A, F j, Y"); ?>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="content-section <?php echo $current_section == 'dashboard' ? 'active' : ''; ?>">
                <div class="card">
                    <h2 class="card-title">Welcome to Admin Dashboard</h2>
                    <p>You have full administrative privileges to manage the system and its users.</p>
                    
                    <div class="stats-container" style="display: flex; gap: 20px; margin-top: 20px;">
                        <div class="stat-card" style="flex: 1; background: #f0f8ff; padding: 15px; border-radius: 8px;">
                            <h3 style="color: var(--primary); margin-bottom: 10px;">Pending Verifications</h3>
                            <?php
                            $pending_count = 0;
                            foreach ($users as $user) {
                                if ($user['status'] === 'pending' || empty($user['status'])) {
                                    $pending_count++;
                                }
                            }
                            ?>
                            <p style="font-size: 1.5rem; font-weight: 600;"><?php echo $pending_count; ?></p>
                        </div>
                        <div class="stat-card" style="flex: 1; background: #fff0f6; padding: 15px; border-radius: 8px;">
                            <h3 style="color: var(--danger); margin-bottom: 10px;">Upcoming Exams</h3>
                            <p style="font-size: 1.5rem; font-weight: 600;"><?php echo count($exams); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="windows-activate">
                    <i class="fas fa-exclamation-circle"></i> Activate Windows
                    <p>Go to Settings to activate Windows.</p>
                </div>
            </div>
            
            <!-- Add Admin Section -->
            <div id="add-admin-section" class="content-section <?php echo $current_section == 'add_admin' ? 'active' : ''; ?>">
                <div class="card">
                    <?php if ($message): ?>
                        <div class="alert <?php echo strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-danger'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="fullname" class="form-label">Full Name</label>
                            <input type="text" id="fullname" name="fullname" class="form-control" placeholder="Enter full name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="Enter email address" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-block">
                            <i class="fas fa-user-plus"></i> Add New Admin
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Manage Exam Section -->
            <div id="manage-exam-section" class="content-section <?php echo $current_section == 'manage_exam' ? 'active' : ''; ?>">
                <div class="card">
                    <?php if ($message): ?>
                        <div class="alert <?php echo strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-danger'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <table class="exam-table" id="examTable">
                        <thead>
                            <tr>
                                <th>Exam</th>
                                <th>Center Code</th>
                                <th>Center Name</th>
                                <th>Exam Date</th>
                                <th>State</th>
                                <th>District</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Shift</th>
                                <th>Duration</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    
                    <div class="action-buttons">
                        <button class="btn" onclick="addRow()">
                            <i class="fas fa-plus"></i> Add Row
                        </button>
                        <button class="btn btn-success" onclick="submitAll()">
                            <i class="fas fa-save"></i> Save All
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- User Verification Section -->
            <div id="verify-users-section" class="content-section <?php echo $current_section == 'verify_users' ? 'active' : ''; ?>">
                <div class="card">
                    <?php if ($message): ?>
                        <div class="alert <?php echo strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-danger'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="user-table-container">
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Payment Ref</th>
                                    <th>Payment Status</th>
                                    <th>Approval Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <i class="fas fa-user-slash"></i>
                                                <p>No users found</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['id']) ?></td>
                                            <td><?= htmlspecialchars($user['fullname']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><?= htmlspecialchars($user['payment_ref'] ?? 'N/A') ?></td>
                                            <td>
                                                <span class="status-badge <?= 
                                                    ($user['is_paid'] === 'paid' ? 'status-paid' : 
                                                    ($user['is_paid'] === 'not_paid' ? 'status-not-paid' : 'status-pending')) 
                                                ?>">
                                                    <?= htmlspecialchars($user['is_paid'] ?? 'pending') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge <?= 
                                                    ($user['status'] === 'accepted' ? 'status-accepted' : 
                                                    ($user['status'] === 'rejected' ? 'status-rejected' : 'status-pending')) 
                                                ?>">
                                                    <?= htmlspecialchars($user['status'] ?? 'pending') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="user-actions">
                                                    <!-- Payment Update -->
                                                    <form method="post" class="user-form">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <button type="submit" name="action" value="paid" class="user-btn user-btn-paid">
                                                            <i class="fas fa-check"></i> Paid
                                                        </button>
                                                        <button type="submit" name="action" value="not_paid" class="user-btn user-btn-not-paid">
                                                            <i class="fas fa-times"></i> Not Paid
                                                        </button>
                                                    </form>
                                                    <!-- Approval Update -->
                                                    <form method="post" class="user-form">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <button type="submit" name="action" value="accepted" class="user-btn user-btn-accept">
                                                            <i class="fas fa-check-circle"></i> Accept
                                                        </button>
                                                        <button type="submit" name="action" value="rejected" class="user-btn user-btn-reject" 
                                                            onclick="return confirm('Are you sure you want to reject this user?')">
                                                            <i class="fas fa-times-circle"></i> Reject
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Attendance Submissions Section -->
            <div id="attendance-section" class="content-section <?php echo $current_section == 'view_attendance' ? 'active' : ''; ?>">
                <div class="card">
                    <table class="exam-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Time</th>
                                <th>CSR Image</th>
                                <th>Submitted At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if($current_section == 'view_attendance') {
                                $conn = new mysqli("localhost", "root", "", "duty_portal");
                                //$conn = new mysqli("localhost", "shruthac", "VSE(8r[9kjNm29", "shruthac_duty_portal", 3306);
                                $res = $conn->query("SELECT * FROM attendance ORDER BY id DESC");
                                while ($row = $res->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= $row['duty_date'] ?></td>
                                <td><?= $row['location'] ?></td>
                                <td><?= $row['arrival_time'] ?></td>
                                <td><a href="<?= htmlspecialchars($row['csr_image']) ?>" target="_blank">View</a></td>
                                <td><?= $row['timestamp'] ?></td>
                            </tr>
                            <?php 
                                endwhile;
                                $conn->close();
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Contact Section -->
            <div id="contact-section" class="content-section <?php echo $current_section == 'contact' ? 'active' : ''; ?>">
                <div class="card">
                    <h2 class="card-title">Contact Information</h2>
                    <p><i class="fas fa-envelope" style="color: var(--accent); margin-right: 10px;"></i> admin@example.com</p>
                    <p><i class="fas fa-phone" style="color: var(--accent); margin-right: 10px;"></i> (123) 456-7890</p>
                    <p><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 10px;"></i> 123 Admin Street, Suite 100</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Update time dynamically
        function updateTime() {
            const now = new Date();
            const options = { 
                hour: '2-digit', 
                minute: '2-digit', 
                hour12: true,
                month: 'long',
                day: 'numeric',
                year: 'numeric'
            };
            document.getElementById('current-time').textContent = now.toLocaleString('en-US', options);
        }
        
        // Update time immediately and then every minute
        updateTime();
        setInterval(updateTime, 60000);
        
        // Add active class to clicked menu items
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Exam management functions
        function addRow(data = {}) {
            const table = document.querySelector('#examTable tbody');
            const row = document.createElement('tr');
            
            row.innerHTML = `
                <td><input value="${data.examName || ''}" required></td>
                <td><input value="${data.centerCode || ''}" required></td>
                <td><input value="${data.examCenter || ''}" required></td>
                <td><input type="date" value="${data.examDate || ''}" required></td>
                <td><input value="${data.state || ''}" required></td>
                <td><input value="${data.district || ''}" required></td>
                <td><input type="time" value="${data.shiftStart || ''}"></td>
                <td><input type="time" value="${data.shiftEnd || ''}"></td>
                <td>
                    <select>
                        <option value="1" ${data.dutyShift === "1" ? 'selected' : ''}>1</option>
                        <option value="2" ${data.dutyShift === "2" ? 'selected' : ''}>2</option>
                        <option value="3" ${data.dutyShift === "3" ? 'selected' : ''}>3</option>
                    </select>
                </td>
                <td><input value="${data.duration || ''}" placeholder="e.g. 3 hours"></td>
                <td><button class="btn btn-danger" onclick="this.closest('tr').remove()" style="padding: 5px 10px;"><i class="fas fa-trash"></i> Delete</button></td>
            `;
            
            table.appendChild(row);
        }
        
        async function submitAll() {
            const rows = document.querySelectorAll('#examTable tbody tr');
            const exams = [];
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                exams.push({
                    examName: cells[0].querySelector('input').value.trim(),
                    centerCode: cells[1].querySelector('input').value.trim(),
                    examCenter: cells[2].querySelector('input').value.trim(),
                    examDate: cells[3].querySelector('input').value,
                    state: cells[4].querySelector('input').value.trim(),
                    district: cells[5].querySelector('input').value.trim(),
                    shiftStart: cells[6].querySelector('input').value,
                    shiftEnd: cells[7].querySelector('input').value,
                    dutyShift: cells[8].querySelector('select').value,
                    duration: cells[9].querySelector('input').value.trim()
                });
            });
            
            try {
                const formData = new FormData();
                formData.append('exams', JSON.stringify(exams));
                
                const response = await fetch('?section=manage_exam', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    location.reload(); // Refresh to show success message
                } else {
                    throw new Error('Failed to save exams');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }
        
        // Load existing exams on page load
        document.addEventListener('DOMContentLoaded', function() {
            const exams = <?php echo json_encode($exams); ?>;
            
            if (exams.length > 0) {
                exams.forEach(exam => {
                    addRow({
                        examName: exam.examName,
                        centerCode: exam.centerCode,
                        examCenter: exam.examCenter,
                        examDate: exam.examDate,
                        state: exam.state,
                        district: exam.district,
                        shiftStart: exam.shiftStart,
                        shiftEnd: exam.shiftEnd,
                        dutyShift: exam.dutyShift,
                        duration: exam.duration
                    });
                });
            } else {
                addRow(); // Add one empty row if no exams exist
            }
        });

        // Add animation to status badges when updated
        document.addEventListener('submit', function(e) {
            if (e.target.closest('.user-form')) {
                const form = e.target.closest('.user-form');
                const row = form.closest('tr');
                const statusBadges = row.querySelectorAll('.status-badge');
                
                statusBadges.forEach(badge => {
                    badge.classList.add('status-updated');
                    setTimeout(() => {
                        badge.classList.remove('status-updated');
                    }, 500);
                });
            }
        });
    </script>
</body>
</html>