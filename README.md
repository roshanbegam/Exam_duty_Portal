# Exam Duty Portal

A web-based portal for managing exam duty assignments, staff registration, attendance tracking, and admin verification — built with PHP and MySQL.

---

## Features

- Staff registration with OTP email verification
- Admin panel to verify users, manage exams, and assign duties
- Payment status tracking per user
- Attendance submission with file uploads
- Forgot password / reset password via email
- CAPTCHA on login for security
- Remember Me functionality

---

## Requirements

- PHP 7.4 or higher (with GD and mysqli extensions)
- MySQL / MariaDB
- Composer
- A Gmail account with an [App Password](https://support.google.com/accounts/answer/185833) for SMTP

---

## Local Setup (XAMPP)

1. **Clone the repository** into your XAMPP `htdocs` folder:
   ```bash
   git clone https://github.com/roshanbegam/exam-duty-portal.git
   cd exam-duty-portal
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Configure credentials:**
   ```bash
   cp config.sample.php config.php
   ```
   Then edit `config.php` and fill in your Gmail SMTP credentials.

4. **Set up the database:**
   - Start XAMPP (Apache + MySQL)
   - Open `http://localhost/phpmyadmin`
   - Create a database named `duty_portal`
   - Import the SQL file (if provided) or let the app create tables on first run

5. **Database connection:**  
   By default the app connects with:
   - Host: `localhost`
   - User: `root`
   - Password: *(empty)*
   - Database: `duty_portal`

   To use different credentials, update the connection settings in each PHP file.

6. **Visit the app:**  
   `http://localhost/exam_duty/login.php`

---

## Admin Access

After setup, insert an admin account via phpMyAdmin or run:

```sql
INSERT INTO admins (fullname, email, password)
VALUES ('Your Name', 'your@email.com', 'YOUR_BCRYPT_HASH');
```

To generate a bcrypt hash:
```bash
php -r "echo password_hash('your_password', PASSWORD_DEFAULT);"
```

---

## Security Notes

- `config.php` is excluded from version control — never commit it
- All passwords are stored as bcrypt hashes
- CAPTCHA is used on login to prevent brute force
- File uploads are stored in `/uploads/` which is also excluded from git

---

## Project Structure

```
exam_duty/
├── admin.php             # Admin dashboard
├── login.php             # Login page with CAPTCHA
├── signup.php            # Staff registration
├── index.php             # Home page (after login)
├── Assign_duty.php       # Duty assignment logic
├── details.php           # User details handling
├── personal_details.php  # Personal info form
├── submit_attendance.php # Attendance submission
├── forgot_password.php   # Password reset request
├── reset_password.php    # Password reset form
├── captcha.php           # CAPTCHA image generator
├── config.sample.php     # Credentials template (copy to config.php)
├── uploads/              # Uploaded files (excluded from git)
└── vendor/               # Composer dependencies (excluded from git)
```

---

## License

This project is for educational/institutional use.
