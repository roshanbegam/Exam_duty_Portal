<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "duty_portal");
//$conn = new mysqli("localhost", "shruthac", "VSE(8r[9kjNm29", "shruthac_duty_portal", 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$exams = [];
$sql = "SELECT center_code as exam_id, exam, center_code, center_name, exam_date, state, district, start_time, end_time, shift, duration FROM exams ORDER BY exam";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $exams[] = $row;
    }
} else {
    $exam_error = "Error fetching exams: " . $conn->error;
}

$conn->close();

$section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';
$username = isset($_SESSION['user']) ? $_SESSION['user'] : 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>User Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary: #4361ee;
        --primary-dark: #3a56d4;
        --primary-light: #ebf5ff;
        --secondary: #3f37c9;
        --accent: #4895ef;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --dark: #1a1f36;
        --light: #f8fafc;
        --gray: #94a3b8;
        --white: #ffffff;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0; 
        padding: 0;
        background-color: var(--light);
        color: #2c3e50;
        line-height: 1.6;
    }

    .container {
        display: flex;
        min-height: 100vh;
    }

    /* Sidebar Styles */
    .sidebar {
        width: 280px;
        background-color: var(--dark);
        color: var(--white);
        display: flex;
        flex-direction: column;
        padding-top: 20px;
        transition: all 0.3s ease;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    }

    .sidebar-header {
        padding: 0 20px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        margin-bottom: 10px;
    }

    .sidebar-menu {
        padding: 10px 0;
        flex-grow: 1;
    }

    .menu-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 25px;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
        margin: 5px 10px;
        border-radius: 8px;
    }

    .menu-item:hover,
    .menu-item.active {
        background-color: var(--primary);
        color: var(--white);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .menu-item i {
        width: 20px;
        text-align: center;
    }

    /* Main Content Styles */
    .main-content {
        flex: 1;
        padding: 25px;
        background-color: #f1f5f9;
        transition: all 0.3s ease;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 25px;
        background-color: var(--white);
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 25px;
        border-radius: 12px;
    }

    .weather-info,
    .language-time {
        display: flex;
        align-items: center;
        font-size: 14px;
        color: var(--gray);
        gap: 8px;
    }

    .dashboard-title {
        font-size: 28px;
        margin-bottom: 25px;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
    }

    .dashboard-title i {
        color: var(--primary);
    }

    /* Card Styles */
    .card {
        background-color: var(--white);
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        margin-bottom: 25px;
        border: 1px solid rgba(0,0,0,0.03);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    }

    .card h2 {
        margin-top: 0;
        color: var(--dark);
        font-size: 22px;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding-bottom: 15px;
        margin-bottom: 20px;
    }

    /* Table Styles */
    .scroll-container {
        overflow-x: auto;
        width: 100%;
        border-radius: 8px;
        margin: 20px 0;
    }

    .exam-table {
        width: 100%;
        min-width: 100%;
        border-collapse: collapse;
        background-color: var(--white);
        border-radius: 8px;
        overflow: hidden;
    }

    .exam-table th,
    .exam-table td {
        padding: 14px 18px;
        border: 1px solid rgba(0,0,0,0.05);
        text-align: center;
        white-space: nowrap;
    }

    .exam-table th {
        background-color: var(--primary);
        color: var(--white);
        font-weight: 500;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 0.5px;
    }

    .exam-table tr:nth-child(even) {
        background-color: rgba(67, 97, 238, 0.03);
    }

    .exam-table tr:hover {
        background-color: rgba(67, 97, 238, 0.08);
    }

    /* Button Styles */
    .apply-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background-color: var(--primary);
        color: var(--white);
        padding: 10px 16px;
        border: none;
        border-radius: 8px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(67, 97, 238, 0.2);
    }

    .apply-btn:hover {
        background-color: var(--primary-dark);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
    }

    .action-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background-color: var(--primary);
        color: var(--white);
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        text-decoration: none;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(67, 97, 238, 0.2);
    }

    .action-button:hover {
        background-color: var(--primary-dark);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
    }

    /* Content Section Styles */
    .content-section {
        display: none;
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .content-section.active {
        display: block;
    }

    .error-message {
        color: var(--danger);
        text-align: center;
        padding: 15px;
        background-color: rgba(239, 68, 68, 0.1);
        border-radius: 8px;
        margin: 20px 0;
    }

    /* User Profile Styles */
    .user-profile {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 0 20px;
    }

    .profile-icon {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background-color: var(--primary-light);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 18px;
    }

    .profile-name {
        font-weight: 600;
        color: var(--white);
    }

    /* Duties Page Specific Styles */
    .duty-role {
        background-color: var(--white);
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 10px;
        border-left: 4px solid var(--primary);
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        transition: transform 0.3s ease;
    }

    .duty-role:hover {
        transform: translateY(-2px);
    }

    .duty-role h3 {
        margin-top: 0;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 18px;
    }

    .duty-role ul {
        margin: 15px 0 0 0;
        padding-left: 20px;
    }

    .duty-role li {
        margin-bottom: 10px;
        position: relative;
        padding-left: 25px;
    }

    .duty-role li i {
        position: absolute;
        left: 0;
        top: 4px;
    }

    .duty-notes {
        background-color: var(--primary-light);
        padding: 20px;
        border-radius: 10px;
        margin-top: 30px;
        border-left: 4px solid var(--accent);
    }

    .duty-notes h3 {
        margin-top: 0;
        color: var(--secondary);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .duty-notes ul {
        margin: 15px 0 0 0;
        padding-left: 20px;
    }

    .duty-notes li {
        margin-bottom: 10px;
        position: relative;
        padding-left: 25px;
    }

    .stat-card {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 20px;
        background-color: var(--white);
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: transform 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-3px);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        background-color: var(--primary-light);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        font-size: 20px;
    }

    .stat-card h3 {
        margin: 0 0 5px 0;
        font-size: 16px;
        color: var(--gray);
        font-weight: 500;
    }

    .stat-card p {
        margin: 0;
        font-size: 24px;
        font-weight: 600;
        color: var(--dark);
    }

    .quick-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-top: 25px;
    }

    .role-icon {
        color: var(--primary);
        font-size: 20px;
    }

    /* Profile Section Specific Styles */
    .profile-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .profile-info div {
        padding: 20px;
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: 10px;
        background-color: var(--white);
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    }

    .profile-info strong {
        display: block;
        margin-bottom: 8px;
        color: var(--gray);
        font-weight: 500;
        font-size: 14px;
    }

    .profile-info span {
        font-size: 16px;
        color: var(--dark);
    }

    .profile-section-welcome {
        font-size: 1.2em;
        margin-bottom: 25px;
        color: var(--gray);
        line-height: 1.6;
    }

    /* Attendance Form Styles */
    #successMessage {
        display: none;
        padding: 15px;
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--success);
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 500;
    }

    #attendanceForm {
        max-width: 600px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    #attendanceForm label {
        font-weight: 500;
        color: var(--dark);
        margin-bottom: 8px;
        display: block;
    }

    #attendanceForm input,
    #attendanceForm select {
        width: 100%;
        padding: 12px 15px;
        border-radius: 8px;
        border: 1px solid rgba(0,0,0,0.1);
        font-size: 16px;
        transition: all 0.3s ease;
    }

    #attendanceForm input:focus,
    #attendanceForm select:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
    }

    #attendanceForm button {
        background-color: var(--primary);
        color: var(--white);
        padding: 14px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 10px;
    }

    #attendanceForm button:hover {
        background-color: var(--primary-dark);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
    }

    /* Scrollbar Styles */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    ::-webkit-scrollbar-track {
        background: rgba(0,0,0,0.05);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: var(--primary);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--primary-dark);
    }

    /* Responsive Adjustments */
    @media (max-width: 992px) {
        .container {
            flex-direction: column;
        }
        
        .sidebar {
            width: 100%;
            padding: 15px;
        }
        
        .sidebar-menu {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .menu-item {
            padding: 10px 15px;
            margin: 0;
        }
    }

    @media (max-width: 768px) {
        .header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
        
        .quick-stats {
            grid-template-columns: 1fr;
        }
        
        .profile-info {
            grid-template-columns: 1fr;
        }
    }
</style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="user-profile">
                    <div class="profile-icon"><i class="fas fa-user"></i></div>
                    <div class="profile-name"><?= htmlspecialchars($username); ?></div>
                </div>
            </div>
            <div class="sidebar-menu">
                <a href="?section=dashboard" class="menu-item <?= $section == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="?section=profile" class="menu-item <?= $section == 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i> Profile
                </a>
                <a href="?section=duties" class="menu-item <?= $section == 'duties' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i> Duties
                </a>
                <a href="?section=exam_schedule" class="menu-item <?= $section == 'exam_schedule' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i> Exam Schedule
                </a>
                <a href="?section=Attendance" class="menu-item <?= $section == 'Attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-info-circle"></i> Attendance
                </a>
                <a href="?section=contact" class="menu-item <?= $section == 'contact' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> Contact
                </a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <div class="weather-info">
                    <i class="fas fa-cloud-sun"></i> 23°C Partly cloudy
                </div>
                <div class="language-time" style="margin-left: 20px;">
                    <i class="fas fa-globe"></i> ENG US
                    <span style="margin: 0 10px;">|</span>
                    <i class="fas fa-clock"></i>
                    <span id="current-time"><?= date("h:i A m/d/Y"); ?></span>
                </div>
            </div>

            <div id="dashboard-section" class="content-section <?= $section == 'dashboard' ? 'active' : ''; ?>">
                <h1 class="dashboard-title">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </h1>
                <div class="card">
                    <h2>Welcome to Your Dashboard</h2>
                    <p>Here you can view your exam schedule, duties, and other important information.</p>
                    
                    <div class="quick-stats">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div>
                                <h3>Upcoming Exams</h3>
                                <p><?= count($exams); ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div>
                                <h3>Pending Duties</h3>
                                <p>3</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <h3>Messages</h3>
                                <p>1</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="profile-section" class="content-section <?= $section == 'profile' ? 'active' : ''; ?>">
                <h1 class="dashboard-title">
                    <i class="fas fa-user-circle"></i> User Profile
                </h1>
                <div class="card">
                    <p class="profile-section-welcome">Welcome, <?= htmlspecialchars($username); ?>! Click the button below to view or edit your personal details.</p>
                    
                    <a href="personal_details.php" class="action-button">
                        <i class="fas fa-info-circle"></i> View/Edit Personal Details
                    </a>
                </div>
            </div>

            <div id="duties-section" class="content-section <?= $section == 'duties' ? 'active' : ''; ?>">
                <h1 class="dashboard-title">
                    <i class="fas fa-clipboard-list"></i> Examination Duties and Responsibilities
                </h1>
                <div class="card">
                    <?php
                    // Define roles with their icons and duties
                    $roles = [
                        "Frisking Staff" => [
                            'icon' => 'fas fa-user-shield',
                            'duties' => [
                                "Ensure no prohibited items (e.g., phones, notes, electronic devices) enter the exam hall.",
                                "Conduct thorough but respectful checks on candidates before entry.",
                                "Report any suspicious activity to the Chief Superintendent.",
                                "Maintain order at entry points to prevent crowding."
                            ]
                        ],
                        "Invigilator" => [
                            'icon' => 'fas fa-user-tie',
                            'duties' => [
                                "Distribute exam materials (question papers, answer sheets) correctly.",
                                "Ensure candidates follow exam rules (no cheating, no talking).",
                                "Monitor the exam hall for irregularities and report issues immediately.",
                                "Record attendance and manage extra time (if applicable).",
                                "Collect answer sheets securely after the exam."
                            ]
                        ],
                        "Supervisor" => [
                            'icon' => 'fas fa-user-graduate',
                            'duties' => [
                                "Oversee multiple exam halls/invigilators to ensure compliance.",
                                "Verify that exam materials are correctly distributed and collected.",
                                "Resolve minor issues or escalate major concerns to the Hall Coordinator.",
                                "Ensure exam start/end times are strictly followed."
                            ]
                        ],
                        "Hall Coordinator" => [
                            'icon' => 'fas fa-user-cog',
                            'duties' => [
                                "Manage overall exam hall arrangements (seating, lighting, ventilation).",
                                "Coordinate between invigilators, supervisors, and the Chief Superintendent.",
                                "Handle emergencies (e.g., disruptions, medical issues).",
                                "Ensure answer sheets are securely transported to the evaluation center."
                            ]
                        ],
                        "Chief Superintendent" => [
                            'icon' => 'fas fa-user-tie',
                            'duties' => [
                                "Act as the highest authority for the examination center.",
                                "Ensure all staff (invigilators, frisking, support) perform duties properly.",
                                "Address major violations (e.g., cheating, paper leaks) decisively.",
                                "Submit a final report on exam conduct to higher authorities."
                            ]
                        ],
                        "Support Staff" => [
                            'icon' => 'fas fa-users',
                            'duties' => [
                                "Set up exam halls (desks, signage, cleanliness) before the exam.",
                                "Assist with logistics (water, seating, emergency supplies).",
                                "Provide technical support (if electronic systems are used).",
                                "Help maintain discipline outside exam halls."
                            ]
                        ]
                    ];

                    // Display each role and its duties
                    foreach ($roles as $role => $data) {
                        echo '<div class="duty-role">';
                        echo '<h3><i class="' . $data['icon'] . ' role-icon"></i> ' . $role . '</h3>';
                        echo '<ul>';
                        foreach ($data['duties'] as $duty) {
                            echo '<li><i class="fas fa-check-circle" style="color: #4CAF50; margin-right: 8px;"></i>' . $duty . '</li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                    }
                    ?>

                    <div class="duty-notes">
                        <h3><i class="fas fa-exclamation-circle"></i> General Notes:</h3>
                        <ul>
                            <li><i class="fas fa-clock" style="color: #FF9800; margin-right: 8px;"></i> All staff must arrive **30-60 minutes early** for briefing.</li>
                            <li><i class="fas fa-lock" style="color: #9C27B0; margin-right: 8px;"></i> Strict **confidentiality** must be maintained regarding exam content.</li>
                            <li><i class="fas fa-exclamation-triangle" style="color: #F44336; margin-right: 8px;"></i> Any misconduct or negligence should be reported immediately.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div id="exam-schedule-section" class="content-section <?= $section == 'exam_schedule' ? 'active' : ''; ?>">
                <h1 class="dashboard-title">
                    <i class="fas fa-calendar-alt"></i> Exam Duty Schedule
                </h1>
                <div class="card">
                    <?php if (isset($exam_error)): ?>
                        <p class="error-message"><i class="fas fa-exclamation-circle"></i> <?= $exam_error; ?></p>
                    <?php elseif (empty($exams)): ?>
                        <p><i class="fas fa-info-circle"></i> No exams scheduled yet.</p>
                    <?php else: ?>
                        <div class="scroll-container">
                            <table class="exam-table">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-book"></i> Exam</th>
                                        <th><i class="fas fa-hashtag"></i> Center Code</th>
                                        <th><i class="fas fa-building"></i> Center Name</th>
                                        <th><i class="fas fa-calendar-day"></i> Exam Date</th>
                                        <th><i class="fas fa-map-marker-alt"></i> State</th>
                                        <th><i class="fas fa-map-pin"></i> District</th>
                                        <th><i class="fas fa-clock"></i> Start Time</th>
                                        <th><i class="fas fa-clock"></i> End Time</th>
                                        <th><i class="fas fa-exchange-alt"></i> Shift</th>
                                        <th><i class="fas fa-hourglass-half"></i> Duration</th>
                                        <th><i class="fas fa-tasks"></i> Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($exams as $exam): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($exam['exam'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($exam['center_code'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($exam['center_name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($exam['exam_date'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($exam['state'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($exam['district'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($exam['start_time'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($exam['end_time'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($exam['shift'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($exam['duration'] ?? 'N/A') ?></td>
                                            <td><a href="Assign_duty.php?exam_id=<?= urlencode($exam['exam_id']) ?>" class="apply-btn"><i class="fas fa-pen"></i> Apply</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="attendance" class="content-section <?= $section == 'Attendance' ? 'active' : ''; ?>">
                <h1 class="dashboard-title">
                    <i class="fas fa-user-check"></i> Attendance
                </h1>

                <div id="successMessage" style="display: none; padding: 12px; background-color: #d1fae5; color: #065f46; border-radius: 8px; margin-bottom: 16px;">
                    ✅ Attendance marked successfully!
                </div>

                <div class="card">
                    <form id="attendanceForm" method="POST" enctype="multipart/form-data" style="max-width: 600px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px;">

                        <div>
                            <label for="name" style="font-weight: 600;">Name:</label>
                            <input type="text" id="name" name="name" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc;" />
                        </div>


                        <div>
                            <label for="duty_date" style="font-weight: 600;">Duty Date:</label>
                            <input type="date" id="duty_date" name="duty_date" value="<?= date('Y-m-d') ?>" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc;" />
                        </div>

                        <div>
                            <label for="location" style="font-weight: 600;">Duty Location:</label>
                            <select name="location" id="location" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc;">
                                <option value="">-- Select Location --</option>
                                <option value="Campus A">Campus A</option>
                                <option value="Campus B">Campus B</option>
                                <option value="Campus C">Campus C</option>
                            </select>
                        </div>

                        <div>
                            <label for="arrival_time" style="font-weight: 600;">Arrival Time:</label>
                            <input type="time" id="arrival_time" name="arrival_time" value="<?= date('H:i') ?>" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc;" />
                        </div>

                        <div>
                            <label for="csr_image" style="font-weight: 600;">Upload CSR (Selfie at Campus):</label>
                            <input type="file" name="csr_image" id="csr_image" accept="image/*" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc;" />
                            <small style="color: #555;">Accepted: JPG, PNG. Max size: 5MB.</small>
                        </div>

                        <button type="submit" style="background-color: #3b82f6; color: white; padding: 12px; border-radius: 10px; border: none; font-size: 16px; cursor: pointer;">
                            Mark Attendance
                        </button>

                    </form>
                </div>
            </div>

            <div id="contact-section" class="content-section <?= $section == 'contact' ? 'active' : ''; ?>">
                <h1 class="dashboard-title">
                    <i class="fas fa-envelope"></i> Contact Us
                </h1>
                <div class="card">
                    <h2><i class="fas fa-address-card"></i> Contact Information</h2>
                    <p><i class="fas fa-envelope"></i> Email: student.support@example.com</p>
                    <p><i class="fas fa-phone"></i> Phone: (123) 456-7890</p>
                    <p><i class="fas fa-map-marker-alt"></i> Address: 123 Campus Street, Building A</p>
                </div>
            </div>
        </div>
    </div>

<script>
    function updateTime() {
        const now = new Date();
        const options = {
            hour: '2-digit', minute: '2-digit', hour12: true,
            month: 'numeric', day: 'numeric', year: 'numeric'
        };
        document.getElementById('current-time').textContent = now.toLocaleString('en-US', options);
    }
    updateTime();
    setInterval(updateTime, 60000);

    // JavaScript for AJAX Submission (for attendance form)
    document.getElementById('attendanceForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);

        fetch('submit_attendance.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(result => {
            if (result.trim() === 'success') {
                document.getElementById('successMessage').style.display = 'block';
                form.reset();
            } else {
                alert('Error: ' + result);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Submission failed.');
        });
    });
</script>
</body>
</html>