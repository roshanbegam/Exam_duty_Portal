<?php
// overview.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Exam Portal Overview</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #2f80ed;
      --primary-dark: #2567c0;
      --secondary-color: #56ccf2;
      --dark-color: #2d3436;
      --light-color: #f5f6fa;
      --white: #ffffff;
      --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      --transition: all 0.3s ease;
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      background: linear-gradient(135deg, #f3f4f6, #e2e8f0);
      color: var(--dark-color);
      line-height: 1.6;
      padding-top: 80px; /* To account for fixed navbar */
    }

    /* Enhanced Navbar */
    .navbar {
      background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
      color: var(--white);
      padding: 0 5%;
      height: 80px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: var(--shadow);
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      transition: var(--transition);
    }
    
    .navbar.scrolled {
      height: 70px;
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    .navbar-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 700;
      font-size: 1.8rem;
      color: var(--white);
      text-decoration: none;
    }
    
    .navbar-brand i {
      font-size: 1.5em;
      transition: var(--transition);
    }
    
    .navbar-brand:hover i {
      transform: rotate(-15deg);
    }

    .navbar-links {
      display: flex;
      align-items: center;
      gap: 5px;
    }
    
    .nav-link {
      color: var(--white);
      text-decoration: none;
      padding: 12px 20px;
      border-radius: 30px;
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 500;
      transition: var(--transition);
      position: relative;
      overflow: hidden;
    }
    
    .nav-link::before {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 0;
      height: 2px;
      background-color: var(--white);
      transition: var(--transition);
    }
    
    .nav-link:hover {
      background-color: rgba(255, 255, 255, 0.15);
    }
    
    .nav-link:hover::before {
      width: 60%;
    }
    
    .nav-link.active {
      background-color: var(--white);
      color: var(--primary-color);
      box-shadow: var(--shadow);
    }
    
    .nav-link.active::before {
      display: none;
    }
    
    .nav-link i {
      font-size: 1.1em;
    }
    
    .mobile-menu-btn {
      display: none;
      background: none;
      border: none;
      color: var(--white);
      font-size: 1.5rem;
      cursor: pointer;
    }
    
    /* Headings */
    h2 {
      color: var(--primary-color);
      margin-bottom: 15px;
      font-weight: 700;
      position: relative;
      padding-bottom: 10px;
    }
    
    h2::after {
      content: '';
      position: absolute;
      left: 0;
      bottom: 0;
      width: 60px;
      height: 3px;
      background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
    }

    h3 {
      color: var(--dark-color);
      margin-bottom: 8px;
    }

    p {
      line-height: 1.7;
      font-size: 1rem;
      color: #555;
    }

    /* Section styling */
    .section {
      background: white;
      margin: 30px auto;
      padding: 30px;
      max-width: 1000px;
      border-radius: 16px;
      box-shadow: var(--shadow);
      transition: var(--transition);
    }

    .section:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    /* List */
    .steps ul {
      padding-left: 20px;
    }
    
    .steps li {
      margin-bottom: 12px;
      position: relative;
      padding-left: 30px;
      list-style-type: none;
    }
    
    .steps li::before {
      content: '\f00c';
      font-family: 'Font Awesome 5 Free';
      font-weight: 900;
      position: absolute;
      left: 0;
      color: #27ae60;
    }
    
    .steps li.warning::before {
      content: '\f071';
      color: #f39c12;
    }

    /* Admin Section */
    .admin {
      display: flex;
      gap: 30px;
      align-items: center;
    }

    .admin img {
      width: 120px;
      height: 150px;
      object-fit: cover;
      border-radius: 12px;
      border: 3px solid var(--primary-color);
      box-shadow: var(--shadow);
    }
    
    .admin-info {
      flex: 1;
    }
    
    .admin-contact {
      display: flex;
      gap: 15px;
      margin-top: 15px;
    }
    
    .admin-contact a {
      color: var(--primary-color);
      background: #e6f0ff;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: var(--transition);
    }
    
    .admin-contact a:hover {
      background: var(--primary-color);
      color: white;
      transform: translateY(-3px);
    }

    /* Map */
    .map iframe {
      border: 0;
      width: 100%;
      height: 350px;
      border-radius: 12px;
      margin-top: 15px;
      box-shadow: var(--shadow);
    }

    /* Contact */
    .contact-info {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }
    
    .contact-card {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 10px;
      transition: var(--transition);
    }
    
    .contact-card:hover {
      background: #e6f0ff;
      transform: translateY(-5px);
    }
    
    .contact-card i {
      font-size: 1.5em;
      color: var(--primary-color);
      margin-bottom: 10px;
    }
    
    .contact-card h3 {
      margin-bottom: 10px;
    }
    
    .contact-form {
      margin-top: 30px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
    }
    
    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-family: 'Poppins', sans-serif;
      transition: var(--transition);
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
      border-color: var(--primary-color);
      outline: none;
      box-shadow: 0 0 0 3px rgba(47, 128, 237, 0.2);
    }
    
    .form-group textarea {
      min-height: 120px;
      resize: vertical;
    }
    
    .submit-btn {
      background: var(--primary-color);
      color: white;
      border: none;
      padding: 12px 30px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      box-shadow: var(--shadow);
    }
    
    .submit-btn:hover {
      background: var(--secondary-color);
      transform: translateY(-3px);
    }

    /* Footer */
    footer {
      background: var(--dark-color);
      color: white;
      padding: 40px 5%;
      margin-top: 50px;
    }
    
    .footer-content {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 30px;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .footer-column h3 {
      color: white;
      margin-bottom: 20px;
      position: relative;
      padding-bottom: 10px;
    }
    
    .footer-column h3::after {
      content: '';
      position: absolute;
      left: 0;
      bottom: 0;
      width: 40px;
      height: 2px;
      background: var(--primary-color);
    }
    
    .footer-column p {
      color: #b2bec3;
      margin-bottom: 15px;
    }
    
    .footer-links {
      list-style: none;
    }
    
    .footer-links li {
      margin-bottom: 10px;
    }
    
    .footer-links a {
      color: #b2bec3;
      text-decoration: none;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .footer-links a:hover {
      color: white;
      transform: translateX(5px);
    }
    
    .footer-links i {
      font-size: 0.8em;
    }
    
    .social-links {
      display: flex;
      gap: 15px;
      margin-top: 20px;
    }
    
    .social-links a {
      color: white;
      background: rgba(255, 255, 255, 0.1);
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: var(--transition);
    }
    
    .social-links a:hover {
      background: var(--primary-color);
      transform: translateY(-3px);
    }
    
    .copyright {
      text-align: center;
      padding-top: 30px;
      margin-top: 30px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      color: #b2bec3;
      font-size: 0.9rem;
    }

    /* Responsive */
    @media (max-width: 992px) {
      .mobile-menu-btn {
        display: block;
      }
      
      .navbar-links {
        position: fixed;
        top: 80px;
        left: -100%;
        width: 80%;
        height: calc(100vh - 80px);
        background: linear-gradient(to bottom, var(--primary-dark), var(--primary-color));
        flex-direction: column;
        align-items: flex-start;
        padding: 30px;
        gap: 15px;
        transition: var(--transition);
        z-index: 999;
      }
      
      .navbar-links.active {
        left: 0;
      }
      
      .nav-link {
        width: 100%;
        padding: 15px;
        border-radius: 8px;
      }
    }
    
    @media (max-width: 768px) {
      .admin {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .contact-info {
        grid-template-columns: 1fr;
      }
    }
    
    @media (max-width: 480px) {
      .section {
        padding: 25px 20px;
        margin: 20px 15px;
      }
      
      .navbar {
        padding: 0 15px;
      }
      
      .navbar-brand {
        font-size: 1.5rem;
      }
    }
  </style>
</head>

<body>

<!-- Enhanced Navbar -->
<nav class="navbar">
  <a href="home.php" class="navbar-brand">
    <i class="fas fa-graduation-cap"></i>
    <span>Exam Portal</span>
  </a>
  
  <button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fas fa-bars"></i>
  </button>
  
  <div class="navbar-links" id="navbarLinks">
    <a href="home.php" class="nav-link">
      <i class="fas fa-home"></i>
      <span>Home</span>
    </a>
    <a href="#about" class="nav-link">
      <i class="fas fa-info-circle"></i>
      <span>About</span>
    </a>
    <a href="#duties" class="nav-link">
      <i class="fas fa-clipboard-list"></i>
      <span>Duties</span>
    </a>
    <a href="#contact" class="nav-link">
      <i class="fas fa-envelope"></i>
      <span>Contact</span>
    </a>
  </div>
</nav>

<!-- About Section -->
<div class="section" id="about">
  <h2>About Exam Portal</h2>
  <p>The Exam Portal is an all-in-one platform designed to revolutionize the examination process for educational institutions, educators, and students. Our system ensures secure, efficient, and transparent exam operations for a seamless experience.</p>
  <p>With advanced features like automated scheduling, real-time monitoring, and comprehensive reporting, we're committed to making examination management easier and more effective than ever before.</p>
  
  <div style="display: flex; gap: 20px; margin-top: 30px;">
    <div style="flex: 1; background: #f8f9fa; padding: 20px; border-radius: 10px;">
      <h3><i class="fas fa-shield-alt" style="color: var(--primary-color);"></i> Secure</h3>
      <p>Advanced security measures to prevent malpractice and ensure exam integrity.</p>
    </div>
    <div style="flex: 1; background: #f8f9fa; padding: 20px; border-radius: 10px;">
      <h3><i class="fas fa-tachometer-alt" style="color: var(--primary-color);"></i> Efficient</h3>
      <p>Streamlined processes that save time for both administrators and students.</p>
    </div>
    <div style="flex: 1; background: #f8f9fa; padding: 20px; border-radius: 10px;">
      <h3><i class="fas fa-chart-line" style="color: var(--primary-color);"></i> Insightful</h3>
      <p>Comprehensive analytics to track performance and improve outcomes.</p>
    </div>
  </div>
</div>

<!-- Exam Duties Section -->
<div class="section steps" id="duties">
  <h2>Examination Duties & Rules</h2>
  <ul>
    <li>Arrive at least 30 minutes before the scheduled exam start time for proper setup and preparation.</li>
    <li>Thoroughly verify student identity using government-issued ID along with examination admit cards.</li>
    <li>Maintain strict vigilance throughout the examination period, monitoring for any suspicious activity.</li>
    <li class="warning">Report any irregularities, suspected cheating, or technical issues immediately to the examination controller.</li>
    <li>Ensure all electronic devices are properly stored and not accessible to students during the exam.</li>
    <li>Submit accurate attendance records and collect all answer sheets promptly at the conclusion of the exam.</li>
    <li>Familiarize yourself with emergency procedures and evacuation routes for the examination venue.</li>
  </ul>
</div>

<!-- Admin Details -->
<div class="section admin" id="admin">
  <img src="https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?ixlib=rb-1.2.1&auto=format&fit=crop&w=334&q=80" alt="Admin Photo" />
  <div class="admin-info">
    <h3>Mr. Ajay Kumar</h3>
    <p><strong>Exam Controller</strong></p>
    <p>With over 15 years of experience in academic administration, Mr. Kumar oversees all examination processes to ensure fairness, transparency, and efficiency in our evaluation systems.</p>
    
    <div class="admin-contact">
      <a href="mailto:ajay.kumar@examportal.edu" title="Email"><i class="fas fa-envelope"></i></a>
      <a href="tel:+919876543210" title="Call"><i class="fas fa-phone"></i></a>
      <a href="#" title="Message"><i class="fas fa-comment-alt"></i></a>
      <a href="#" title="Schedule Meeting"><i class="fas fa-calendar-check"></i></a>
    </div>
  </div>
</div>

<!-- Google Map -->
<div class="section map">
  <h2>Our Location</h2>
  <p>Visit our headquarters for any examination-related inquiries or support:</p>
  <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3502.566636752012!2d77.2098653150822!3d28.62864498242497!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390cfd37b741d057%3A0xcdee88e47393c3f1!2sConnaught%20Place%2C%20New%20Delhi%2C%20Delhi!5e0!3m2!1sen!2sin!4v1624456789015!5m2!1sen!2sin" allowfullscreen loading="lazy"></iframe>
</div>

<!-- Contact Details -->
<div class="section contact" id="contact">
  <h2>Contact Us</h2>
  <p>Have questions or need support? Reach out to our team through any of the following channels:</p>
  
  <div class="contact-info">
    <div class="contact-card">
      <i class="fas fa-envelope"></i>
      <h3>Email</h3>
      <p>examportal@gmail.com</p>
      <p>support@examportal.edu</p>
    </div>
    <div class="contact-card">
      <i class="fas fa-phone"></i>
      <h3>Phone</h3>
      <p>+91 9876543210</p>
      <p>011-23456789</p>
    </div>
    <div class="contact-card">
      <i class="fas fa-map-marker-alt"></i>
      <h3>Address</h3>
      <p>123, Education Lane</p>
      <p>New Delhi, India - 110001</p>
    </div>
    <div class="contact-card">
      <i class="fas fa-clock"></i>
      <h3>Working Hours</h3>
      <p>Monday - Friday: 9AM - 6PM</p>
      <p>Saturday: 10AM - 4PM</p>
    </div>
  </div>
  
  <div class="contact-form">
    <h3>Send Us a Message</h3>
    <form id="contactForm">
      <div class="form-group">
        <label for="name">Your Name</label>
        <input type="text" id="name" name="name" required>
      </div>
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" required>
      </div>
      <div class="form-group">
        <label for="subject">Subject</label>
        <input type="text" id="subject" name="subject" required>
      </div>
      <div class="form-group">
        <label for="message">Your Message</label>
        <textarea id="message" name="message" required></textarea>
      </div>
      <button type="submit" class="submit-btn">Send Message</button>
    </form>
  </div>
</div>

<!-- Footer -->
<footer>
  <div class="footer-content">
    <div class="footer-column">
      <h3>Exam Portal</h3>
      <p>Comprehensive examination management system for educational institutions, providing secure and efficient assessment solutions.</p>
      <div class="social-links">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-linkedin-in"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
      </div>
    </div>
    <div class="footer-column">
      <h3>Quick Links</h3>
      <ul class="footer-links">
        <li><a href="home.php"><i class="fas fa-chevron-right"></i> Home</a></li>
        <li><a href="#about"><i class="fas fa-chevron-right"></i> About Us</a></li>
        <li><a href="#duties"><i class="fas fa-chevron-right"></i> Duties</a></li>
        <li><a href="#contact"><i class="fas fa-chevron-right"></i> Contact</a></li>
      </ul>
    </div>
    <div class="footer-column">
      <h3>Resources</h3>
      <ul class="footer-links">
        <li><a href="#"><i class="fas fa-chevron-right"></i> Help Center</a></li>
        <li><a href="#"><i class="fas fa-chevron-right"></i> Documentation</a></li>
        <li><a href="#"><i class="fas fa-chevron-right"></i> FAQ</a></li>
      </ul>
    </div>
    <div class="footer-column">
      <h3>Newsletter</h3>
      <p>Subscribe to our newsletter for the latest updates and important announcements.</p>
      <form style="margin-top: 15px;">
        <div class="form-group" style="margin-bottom: 10px;">
          <input type="email" placeholder="Your Email" style="width: 100%; padding: 10px; border-radius: 4px; border: none;">
        </div>
        <button type="submit" style="background: var(--primary-color); color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">Subscribe</button>
      </form>
    </div>
  </div>
  <div class="copyright">
    &copy; 2025 Exam Portal. All Rights Reserved.
  </div>
</footer>

<script>
  // Mobile menu toggle
  const mobileMenuBtn = document.getElementById('mobileMenuBtn');
  const navbarLinks = document.getElementById('navbarLinks');
  
  mobileMenuBtn.addEventListener('click', () => {
    navbarLinks.classList.toggle('active');
    mobileMenuBtn.innerHTML = navbarLinks.classList.contains('active') 
      ? '<i class="fas fa-times"></i>' 
      : '<i class="fas fa-bars"></i>';
  });

  // Close mobile menu when clicking on a link
  document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
      if (navbarLinks.classList.contains('active')) {
        navbarLinks.classList.remove('active');
        mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
      }
    });
  });

  // Smooth scrolling
  function scrollToSection(id) {
    document.getElementById(id).scrollIntoView({ behavior: 'smooth' });
  }
  
  // Form submission
  document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    alert('Thank you for your message! We will get back to you soon.');
    this.reset();
  });
  
  // Navbar scroll effect
  window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  });
  
  // Set active nav link based on scroll position
  const sections = document.querySelectorAll('.section');
  const navLinks = document.querySelectorAll('.nav-link');
  
  window.addEventListener('scroll', () => {
    let current = '';
    
    sections.forEach(section => {
      const sectionTop = section.offsetTop;
      const sectionHeight = section.clientHeight;
      
      if (pageYOffset >= (sectionTop - 100)) {
        current = section.getAttribute('id');
      }
    });
    
    navLinks.forEach(link => {
      link.classList.remove('active');
      if (link.getAttribute('href').includes(current)) {
        link.classList.add('active');
      }
    });
  });
</script>

</body>
</html>