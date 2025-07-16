<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful!</title>
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2ecc71;
            --dark: #2c3e50;
            --light-gray: #f8f9fa;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e0f2f7 0%, #c3cfe2 100%);
            color: var(--dark);
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .container {
            max-width: 700px;
            margin: 30px auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            color: var(--secondary);
            margin-bottom: 20px;
            font-weight: 700;
            font-size: 2.2rem;
        }

        .icon {
            font-size: 4rem;
            color: var(--secondary);
            margin-bottom: 25px;
        }

        p {
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        p strong {
            color: var(--primary);
            min-width: 150px;
            display: inline-block;
            text-align: left;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
            margin: 25px auto;
            max-width: 400px;
            text-align: left;
        }

        .details-grid p {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px dashed var(--light-gray);
        }

        .details-grid p:last-child {
            border-bottom: none;
        }

        .button-group {
            margin-top: 30px;
        }

        .button-group button {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
        }

        .button-group button:hover {
            background: #2980b9;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
        }

        .button-group button:active {
            transform: translateY(0);
        }
        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }
            .details-grid {
                padding: 0 10px;
            }
            .details-grid p strong {
                min-width: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">&#10004;</div> <h2>Registration Successful!</h2>
        <p>Thank you for registering for exam duty. Your details have been submitted successfully.</p>

        <h3>Your Exam Details:</h3>
        <div class="details-grid">
            <?php
            // Retrieve and sanitize data from GET parameters
            $exam_id = htmlspecialchars($_GET['exam_id'] ?? 'N/A');
            $exam_name = htmlspecialchars($_GET['exam_name'] ?? 'N/A');
            $center_id = htmlspecialchars($_GET['center_id'] ?? 'N/A');
            $center_name = htmlspecialchars($_GET['center_name'] ?? 'N/A');
            $exam_date = htmlspecialchars($_GET['exam_date'] ?? 'N/A');
            $shift = htmlspecialchars($_GET['shift'] ?? 'N/A');
            $registered_email = htmlspecialchars($_GET['registered_email'] ?? 'N/A');
            ?>
            <p><strong>Exam ID:</strong> <span><?= $exam_id ?></span></p>
            <p><strong>Exam Name:</strong> <span><?= $exam_name ?></span></p>
            <p><strong>Center ID:</strong> <span><?= $center_id ?></span></p>
            <p><strong>Center Name:</strong> <span><?= $center_name ?></span></p>
            <p><strong>Date of Exam:</strong> <span><?= $exam_date ?></span></p>
            <p><strong>Shift:</strong> <span><?= $shift ?></span></p>
            <p><strong>Registered Email:</strong> <span><?= $registered_email ?></span></p>
        </div>

        <div class="button-group">
            <button onclick="window.location.href='Assign_duty.php'">Register for Another Exam</button>
        </div>
    </div>
</body>
</html>