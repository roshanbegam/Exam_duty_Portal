<?php
// contact.php - Simple PHP-enabled HTML contact page
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Contact Us</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f2f2f2;
      margin: 0;
      padding: 0;
    }

    .container {
      max-width: 800px;
      margin: 40px auto;
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    .map {
      margin-top: 40px;
    }

    iframe {
      border-radius: 10px;
    }

    .contact-table {
      margin-top: 30px;
      width: 100%;
      border-collapse: collapse;
    }

    .contact-table th, .contact-table td {
      padding: 12px 15px;
      border: 1px solid #ddd;
      text-align: left;
    }

    .contact-table th {
      background-color: #007BFF;
      color: white;
    }

    .contact-table tr:nth-child(even) {
      background-color: #f9f9f9;
    }
  </style>
</head>
<body>

  <div class="container">
    <h2>Contact Us</h2>

    <table class="contact-table">
      <tr>
        <th>Type</th>
        <th>Details</th>
      </tr>
      <tr>
        <td>Landline</td>
        <td>011-12345678</td>
      </tr>
      <tr>
        <td>Address</td>
        <td>123 Education Street, New Delhi, India</td>
      </tr>
      <tr>
        <td>Email</td>
        <td>support@examportal.com</td>
      </tr>
      <tr>
        <td>WhatsApp</td>
        <td>+91 9876543210</td>
      </tr>
      <tr>
        <td>Send Message</td>
        <td><a href="mailto:support@examportal.com?subject=Inquiry">Click to Send Email</a></td>
      </tr>
    </table>

    <div class="map">
      <h3>Our Location</h3>
      <iframe src="https://maps.google.com/maps?q=New%20Delhi&t=&z=13&ie=UTF8&iwloc=&output=embed"
              width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
    </div>
  </div>

</body>
</html>
