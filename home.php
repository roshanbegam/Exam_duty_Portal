<?php
// index.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Exam Duty Portal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
  <style>
    :root {
      --primary: #007BFF;
      --secondary: #6c757d;
      --success: #28a745;
      --danger: #dc3545;
      --warning: #ffc107;
      --info: #17a2b8;
      --light: #f8f9fa;
      --dark: #343a40;
      --gradient-blue: linear-gradient(135deg, #0072ff 0%, #00c6ff 100%);
      --gradient-green: linear-gradient(135deg, #28a745 0%, #7bed9f 100%);
    }
   
    * {
      margin: 0; padding: 0; box-sizing: border-box;
      scroll-behavior: smooth;
    }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(to right,rgb(200, 209, 223), #e4e8ed);
      color: #333;
      line-height: 1.6;
    }

    .header {
      background-color: var(--primary);
      padding: 15px 5%;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    .header .logo {
      font-size: 1.8em;
      display: flex;
      align-items: center;
      font-weight: 700;
    }
    .header .logo i {
      margin-right: 10px;
      font-size: 1.2em;
    }
    
    /* Search Container Styles */
    .search-container {
      flex: 1;
      max-width: 500px;
      margin: 0 20px;
      position: relative;
    }
    .search-form {
      display: flex;
      width: 100%;
    }
    .search-input {
      flex: 1;
      padding: 12px 20px;
      border: none;
      border-radius: 30px 0 0 30px;
      font-size: 1rem;
      outline: none;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
    }
    .search-input:focus {
      box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }
    .search-button {
      background: var(--gradient-blue);
      color: white;
      border: none;
      padding: 0 20px;
      border-radius: 0 30px 30px 0;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .search-button:hover {
      background: linear-gradient(135deg, #0062cc 0%, #00a8e8 100%);
    }
    .search-button i {
      font-size: 1.2rem;
    }
    
    /* Search Results Dropdown */
    .search-results {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      background: white;
      border-radius: 0 0 10px 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      max-height: 300px;
      overflow-y: auto;
      z-index: 1000;
      display: none;
    }
    .search-result-item {
      padding: 12px 20px;
      border-bottom: 1px solid #eee;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
    }
    .search-result-item:hover {
      background-color: #f8f9fa;
    }
    .search-result-item i {
      margin-right: 10px;
      color: var(--primary);
    }
    
    .navbar {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
    }
    .navbar button {
      background: var(--gradient-blue);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 30px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .navbar button:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    }
    .navbar button i {
      font-size: 1.1em;
    }

    .home {
      display: flex;
      flex-wrap: wrap;
      padding: 80px 5%;
      align-items: center;
      justify-content: center;
      gap: 60px;
      min-height: calc(100vh - 120px);
    }
    .home .image {
      flex: 1 1 400px;
      position: relative;
    }
    .home .image img {
      width: 100%;
      max-width: 550px;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
      transition: transform 0.5s ease;
    }
    .home .image:hover img {
      transform: scale(1.03);
    }
    .home .image::after {
      content: '';
      position: absolute;
      width: 100%;
      height: 100%;
      top: 15px;
      left: 15px;
      border: 3px solid var(--primary);
      border-radius: 20px;
      z-index: -1;
      opacity: 0.3;
    }
    .home .content {
      flex: 1 1 500px;
    }
    .home .content h3 {
      font-size: 2.5rem;
      margin-bottom: 20px;
      line-height: 1.2;
    }
    .home .content p {
      font-size: 1.1rem;
      margin-bottom: 30px;
      color: var(--secondary);
    }
    .highlight {
      color: var(--primary);
      font-weight: 700;
      position: relative;
      display: inline-block;
    }
    .highlight::after {
      content: '';
      position: absolute;
      bottom: 5px;
      left: 0;
      width: 100%;
      height: 10px;
      background-color: rgba(0, 123, 255, 0.2);
      z-index: -1;
      border-radius: 5px;
    }
    .btn-group {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
    }
    .btn {
      padding: 12px 28px;
      color: white;
      border-radius: 30px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      border: none;
      cursor: button;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    }
    .btn-primary {
      background: var(--gradient-blue);
    }
    .btn-primary:hover {
      background: linear-gradient(135deg, #0062cc 0%, #00a8e8 100%);
    }
    .btn-success {
      background: var(--gradient-green);
    }
    .btn-success:hover {
      background: linear-gradient(135deg, #218838 0%,rgb(45, 144, 73) 100%);
    }
    .btn i {
      font-size: 1.1em;
    }

    /* New Roles Slideshow Section */
    .roles-section {
      padding: 80px 5%;
      background: #f0f5ff;
    }
    .section-title {
      text-align: center;
      margin-bottom: 50px;
    }
    .section-title h2 {
      font-size: 2.5rem;
      color: var(--dark);
      position: relative;
      display: inline-block;
    }
    .section-title h2::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 4px;
      background: var(--gradient-blue);
      border-radius: 2px;
    }
    
    /* Roles Carousel */
    .roles-carousel {
      position: relative;
      max-width: 1300px;
      padding-bottom: 2rem;
      margin: 0 auto;
      overflow: hidden;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    .carousel-container {
      display: flex;
      transition: transform 0.5s ease-in-out;
    }
    .role-slide {
      min-width: 100%;
      display: flex;
      flex-direction: column;
      background: white;
    }
    .slide-content {
      display: flex;
      flex-wrap: wrap;
    }
    .slide-image {
      flex: 1;
      min-width: 300px;
      max-height: 400px;
      overflow: hidden;
    }
    .slide-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    .slide-image:hover img {
      transform: scale(1.05);
    }
    .slide-details {
      flex: 1;
      padding: 40px;
      min-width: 300px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .slide-details h3 {
      font-size: 2rem;
      margin-bottom: 20px;
      color: var(--primary);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .slide-details h3 i {
      background: var(--gradient-blue);
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.5rem;
    }
    .slide-details ul {
      padding-left: 20px;
      margin: 15px 0;
    }
    .slide-details ul li {
      margin-bottom: 10px;
      position: relative;
    }
    .slide-details ul li::before {
      content: "•";
      color: var(--primary);
      font-weight: bold;
      position: absolute;
      left: -15px;
    }
    
    /* Carousel Navigation */
    .carousel-nav {
      display: flex;
      justify-content: center;
      margin-top: 30px;
      gap: 10px;
    }
    .carousel-btn {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: var(--gradient-blue);
      color: white;
      border: none;
      font-size: 1.2rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .carousel-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 12px rgba(0,0,0,0.15);
      background: linear-gradient(135deg, #0062cc 0%, #00a8e8 100%);
    }
    .carousel-indicators {
      display: flex;
      justify-content: center;
      margin-top: 20px;
      gap: 10px;
    }
    .indicator {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background-color: #ccc;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .indicator.active {
      background-color: var(--primary);
      transform: scale(1.2);
    }

    .features {
      padding: 80px 5%;
      background-color: white;
    }
    .features-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 30px;
    }
    .feature-card {
      background: white;
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      transition: all 0.3s ease;
      text-align: center;
    }
    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }
    .feature-icon {
      width: 80px;
      height: 80px;
      background: var(--gradient-blue);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      color: white;
      font-size: 1.8rem;
    }
    .feature-card h3 {
      margin-bottom: 15px;
      color: var(--dark);
    }
    .feature-card p {
      color: var(--secondary);
    }

    .divider {
      position: relative;
      margin-top: 50px;
    }
    .divider svg {
      display: block;
      width: 100%;
      height: 100px;
    }

    .marquee {
      width: 100%;
      overflow: hidden;
      background: var(--dark);
      padding: 15px 0;
    }
    .marquee-content {
      display: inline-block;
      font-weight: bold;
      color: var(--light);
      font-size: 1.1rem;
      white-space: nowrap;
      animation: scroll-left 20s linear infinite;
    }
    @keyframes scroll-left {
      from { transform: translateX(100%); }
      to { transform: translateX(-100%); }
    }

    .cta {
      padding: 80px 5%;
      text-align: center;
      background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
      background-size: cover;
      background-position: center;
      color: white;
    }
    .cta h2 {
      font-size: 2.5rem;
      margin-bottom: 20px;
    }
    .cta p {
      max-width: 700px;
      margin: 0 auto 30px;
      font-size: 1.1rem;
    }

    .footer {
      background: var(--dark);
      color: white;
      padding: 60px 5% 30px;
    }
    .footer-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 40px;
      margin-bottom: 40px;
    }
    .footer-logo {
      font-size: 1.8em;
      font-weight: 700;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .footer-logo i {
      color: var(--primary);
    }
    .footer-about p {
      opacity: 0.8;
      margin-bottom: 20px;
    }
    .social-links {
      display: flex;
      gap: 15px;
    }
    .social-links a {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: rgba(255,255,255,0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      transition: all 0.3s ease;
    }
    .social-links a:hover {
      background: var(--primary);
      transform: translateY(-3px);
    }
    .footer-links h3, .footer-contact h3 {
      font-size: 1.3rem;
      margin-bottom: 20px;
      position: relative;
      padding-bottom: 10px;
    }
    .footer-links h3::after, .footer-contact h3::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 50px;
      height: 3px;
      background: var(--primary);
    }
    .footer-links ul {
      list-style: none;
    }
    .footer-links li {
      margin-bottom: 10px;
    }
    .footer-links a {
      color: rgba(255,255,255,0.8);
      text-decoration: none;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .footer-links a:hover {
      color: white;
      transform: translateX(5px);
    }
    .footer-links a i {
      font-size: 0.8em;
    }
    .footer-contact p {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 15px;
      opacity: 0.8;
    }
    .footer-contact i {
      color: var(--primary);
      font-size: 1.2em;
    }
    .copyright {
      text-align: center;
      padding-top: 30px;
      border-top: 1px solid rgba(255,255,255,0.1);
      opacity: 0.7;
    }

    /* Animation classes */
    .animate-pop {
      animation: popIn 0.6s ease-out forwards;
    }
    @keyframes popIn {
      0% { opacity: 0; transform: scale(0.8); }
      80% { transform: scale(1.05); }
      100% { opacity: 1; transform: scale(1); }
    }
    .delay-1 { animation-delay: 0.2s; }
    .delay-2 { animation-delay: 0.4s; }
    .delay-3 { animation-delay: 0.6s; }

    /* Responsive styles */
    @media (max-width: 992px) {
      .home {
        padding: 60px 5%;
      }
      .home .content h3 {
        font-size: 2rem;
      }
      .slide-details {
        padding: 30px;
      }
      .search-container {
        order: 3;
        max-width: 100%;
        margin: 15px 0 0 0;
      }
    }
    @media (max-width: 768px) {
      .header {
        flex-direction: column;
        gap: 15px;
        padding: 15px;
      }
      .navbar {
        justify-content: center;
        width: 100%;
      }
      .home {
        text-align: center;
        padding: 40px 5%;
      }
      .home .image::after {
        display: none;
      }
      .btn-group {
        justify-content: center;
      }
      .section-title h2 {
        font-size: 2rem;
      }
      .slide-content {
        flex-direction: column;
      }
      .slide-image {
        max-height: 300px;
      }
      .search-container {
        width: 100%;
        margin: 10px 0;
      }
    }
    @media (max-width: 576px) {
      .navbar button {
        padding: 8px 15px;
        font-size: 0.9rem;
      }
      .home .content h3 {
        font-size: 1.8rem;
      }
      .feature-card {
        padding: 20px;
      }
      .slide-details h3 {
        font-size: 1.5rem;
      }
      .carousel-btn {
        width: 40px;
        height: 40px;
        font-size: 1rem;
      }
      .search-input {
        padding: 10px 15px;
      }
    }
  </style>
</head>
<body>

<header class="header">
  <div class="logo"><i class="fas fa-graduation-cap"></i> Exam Duty Portal</div>
  
  <!-- Search Container -->
  <div class="search-container">
    <form class="search-form" id="searchForm">
      <input type="text" class="search-input" id="searchInput" placeholder="Search roles, features, help..." autocomplete="off">
      <button type="submit" class="search-button">
        <i class="fas fa-search"></i>
      </button>
    </form>
    <div class="search-results" id="searchResults"></div>
  </div>
  
  <nav class="navbar">
    <button onclick="location.href='home.php'"><i class="fas fa-home"></i> Home</button>
    <button onclick="location.href='about.php'"><i class="fas fa-info-circle"></i> About</button>
    <button onclick="location.href='login.php'"><i class="fas fa-sign-in-alt"></i> Login</button>
    <button onclick="location.href='signup.php'"><i class="fas fa-user-plus"></i> Signup</button>
  </nav>
</header>

<section class="home">
  <div class="image animate_animated animate_fadeInLeft">
    <img src="img1.png" alt="Exam Duty">
  </div>
  <div class="content animate_animated animate_fadeInRight">
    <h3>Streamline Your <span class="highlight">Exam Duty Management</span></h3>
    <p>Comprehensive exam duty management system for invigilators and staff. Efficient scheduling, real-time updates, and seamless coordination for all your examination needs.</p>
    <div class="btn-group">
      <a href="signup.php" class="btn btn-primary"><i class="fas fa-rocket"></i> Get Started</a>
      <a href="about.php" class="btn btn-success"><i class="fas fa-play-circle"></i> Watch Demo</a>
    </div>
  </div>
</section>

<!-- New Roles Slideshow Section -->
<section class="roles-section">
  <div class="section-title">
    <h2>Key Roles in Exam Management</h2>
  </div>
  
  <div class="roles-carousel">
    <div class="carousel-container">
      <!-- Role 1: Frisking -->
      <div class="role-slide">
        <div class="slide-content">
          <div class="slide-image">
            <img src="https://images.unsplash.com/photo-1581092580497-e0d23cbdf1dc?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Frisking">
          </div>
          <div class="slide-details">
            <h3><i class="fas fa-search"></i> Frisking Team</h3>
            <p>The frisking team ensures examination integrity by preventing unauthorized items from entering exam halls. Their responsibilities include:</p>
            <ul>
              <li>Checking students for prohibited electronic devices</li>
              <li>Verifying student identification documents</li>
              <li>Ensuring no study materials enter the examination hall</li>
              <li>Maintaining security protocols at entry points</li>
              <li>Reporting any suspicious behavior</li>
            </ul>
            <p>Frisking staff play a crucial role in maintaining the credibility of examination processes.</p>
          </div>
        </div>
      </div>
      
      <!-- Role 2: Invigilators -->
      <div class="role-slide">
        <div class="slide-content">
          <div class="slide-image">
            <img src="https://images.unsplash.com/photo-1543269865-cbf427effbad?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Invigilators">
          </div>
          <div class="slide-details">
            <h3><i class="fas fa-user-graduate"></i> Invigilators</h3>
            <p>Invigilators are responsible for supervising examinations and ensuring fair conduct. Their duties include:</p>
            <ul>
              <li>Distributing exam papers and materials</li>
              <li>Monitoring students during examinations</li>
              <li>Answering procedural questions</li>
              <li>Collecting completed answer scripts</li>
              <li>Reporting any irregularities or malpractices</li>
            </ul>
            <p>Effective invigilation ensures the integrity of the examination process and provides equal opportunity for all students.</p>
          </div>
        </div>
      </div>
      
      <!-- Role 3: Hall Coordinator -->
      <div class="role-slide">
        <div class="slide-content">
          <div class="slide-image">
            <img src="https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Hall Coordinator">
          </div>
          <div class="slide-details">
            <h3><i class="fas fa-tasks"></i> Hall Coordinator</h3>
            <p>Hall coordinators manage all activities within a specific examination venue. Their responsibilities include:</p>
            <ul>
              <li>Managing invigilator assignments within their hall</li>
              <li>Coordinating distribution and collection of exam papers</li>
              <li>Resolving issues during examinations</li>
              <li>Ensuring proper seating arrangements</li>
              <li>Acting as liaison between invigilators and chief superintendent</li>
            </ul>
            <p>Hall coordinators ensure smooth operations and timely resolution of issues during exams.</p>
          </div>
        </div>
      </div>
      
      <!-- Role 4: Supervisor -->
      <div class="role-slide">
        <div class="slide-content">
          <div class="slide-image">
            <img src="https://images.unsplash.com/photo-1580894908361-967195033215?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Supervisor">
          </div>
          <div class="slide-details">
            <h3><i class="fas fa-user-tie"></i> Chief Supervisor</h3>
            <p>The chief supervisor oversees the entire examination process at the institution level. Key responsibilities include:</p>
            <ul>
              <li>Overall management of examination centers</li>
              <li>Coordinating with all hall coordinators</li>
              <li>Managing security arrangements</li>
              <li>Handling emergencies and critical situations</li>
              <li>Ensuring compliance with examination regulations</li>
            </ul>
            <p>The chief supervisor ensures that all examination processes follow institutional guidelines and regulations.</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Navigation buttons -->
    <div class="carousel-nav">
      <button class="carousel-btn prev-btn"><i class="fas fa-chevron-left"></i></button>
      <button class="carousel-btn next-btn"><i class="fas fa-chevron-right"></i></button>
    </div>
    
    <!-- Indicators -->
    <div class="carousel-indicators">
      <div class="indicator active" data-index="0"></div>
      <div class="indicator" data-index="1"></div>
      <div class="indicator" data-index="2"></div>
      <div class="indicator" data-index="3"></div>
    </div>
  </div>
</section>

<section class="features">
  <div class="section-title">
    <h2>Why Choose Our Portal</h2>
  </div>
  <div class="features-container">
    <div class="feature-card animate-pop">
      <div class="feature-icon">
        <i class="fas fa-calendar-alt"></i>
      </div>
      <h3>Easy Scheduling</h3>
      <p>Automated duty scheduling with conflict detection to ensure fair distribution of exam responsibilities.</p>
    </div>
    <div class="feature-card animate-pop delay-1">
      <div class="feature-icon">
        <i class="fas fa-bell"></i>
      </div>
      <h3>Real-time Notifications</h3>
      <p>Instant alerts for duty assignments, changes, and important announcements through multiple channels.</p>
    </div>
    <div class="feature-card animate-pop delay-2">
      <div class="feature-icon">
        <i class="fas fa-chart-line"></i>
      </div>
      <h3>Performance Analytics</h3>
      <p>Track and analyze invigilator performance and attendance with comprehensive dashboards.</p>
    </div>
    <div class="feature-card animate-pop delay-3">
      <div class="feature-icon">
        <i class="fas fa-file-alt"></i>
      </div>
      <h3>Digital Reporting</h3>
      <p>Submit incident reports and exam documentation digitally with our streamlined forms.</p>
    </div>
  </div>
</section>

<div class="divider">
  <svg viewBox="0 0 1440 100" preserveAspectRatio="none">
    <path fill="#ffffff" d="M0,0 C600,100 800,0 1440,100 L1440,0 L0,0 Z"></path>
  </svg>
</div>

<div class="marquee">
  <div class="marquee-content">
    <i class="fas fa-exclamation-circle"></i> Important: All invigilators must complete the mandatory training module before accepting duties. Next training session: June 15, 2023.
    <span style="margin: 0 50px;">|</span>
    <i class="fas fa-calendar-check"></i> Upcoming Exams: Semester Finals (June 20-30), Professional Certification (July 5), Entrance Exams (July 15-20)
    <span style="margin: 0 50px;">|</span>
    <i class="fas fa-clock"></i> Reminder: Arrive 45 minutes before exam start time for briefing and setup.
  </div>
</div>

<section class="cta">
  <h2>Ready to Transform Your Exam Management?</h2>
  <p>Join hundreds of institutions that have streamlined their examination processes with our comprehensive solution.</p>
  <div class="btn-group" style="justify-content: center;">
    <a href="signup.php" class="btn btn-primary" style="padding: 15px 30px;"><i class="fas fa-rocket"></i> Get Started</a>
    <a href="about.php" class="btn btn-success" style="padding: 15px 30px;"><i class="fas fa-play-circle"></i> Watch Demo</a>
  </div>
</section>

<footer class="footer">
  <div class="footer-container">
    <div class="footer-about">
      <div class="footer-logo">
        <i class="fas fa-graduation-cap"></i> Exam Duty Portal
      </div>
      <p>Revolutionizing exam management through technology. Our platform ensures fairness, efficiency, and transparency in examination duty allocation and coordination.</p>
      <div class="social-links">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-linkedin-in"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
      </div>
    </div>
    <div class="footer-links">
      <h3>Quick Links</h3>
      <ul>
        <li><a href="home.php"><i class="fas fa-chevron-right"></i> Home</a></li>
        <li><a href="about.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
        <li><a href="features.php"><i class="fas fa-chevron-right"></i> Features</a></li>
        <li><a href="pricing.php"><i class="fas fa-chevron-right"></i> Pricing</a></li>
        <li><a href="contact.php"><i class="fas fa-chevron-right"></i> Contact</a></li>
      </ul>
    </div>
    <div class="footer-links">
      <h3>Resources</h3>
      <ul>
        <li><a href="blog.php"><i class="fas fa-chevron-right"></i> Blog</a></li>
        <li><a href="faq.php"><i class="fas fa-chevron-right"></i> FAQ</a></li>
        <li><a href="help.php"><i class="fas fa-chevron-right"></i> Help Center</a></li>
        <li><a href="webinars.php"><i class="fas fa-chevron-right"></i> Webinars</a></li>
        <li><a href="docs.php"><i class="fas fa-chevron-right"></i> Documentation</a></li>
      </ul>
    </div>
    <div class="footer-contact">
      <h3>Contact Us</h3>
      <p><i class="fas fa-map-marker-alt"></i> Canara Records private Limited
                                                      G8,Green City,  Behind Naganakatte
                                                      Thokkottu ,Mangalore - 575017</p>
      <p><i class="fas fa-envelope"></i> support@examdutyportal.com</p>
      <p><i class="fas fa-phone-alt"></i> +91 11 2345 6789</p>
      <p><i class="fas fa-clock"></i> Mon-Sat: 9:30AM-6PM</p>
    </div>
  </div>
  <div class="copyright">
    <p>&copy; 2023 Exam Duty Portal. All Rights Reserved. | <a href="privacy.php" style="color: var(--primary);">Privacy Policy</a> | <a href="terms.php" style="color: var(--primary);">Terms of Service</a></p>
  </div>
</footer>

<script>
  // Simple animation trigger on scroll
  document.addEventListener('DOMContentLoaded', function() {
    const animateElements = document.querySelectorAll('.animate-pop');
    
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = 1;
          entry.target.style.transform = 'scale(1)';
        }
      });
    }, {threshold: 0.1});
    
    animateElements.forEach(el => {
      el.style.opacity = 0;
      el.style.transform = 'scale(0.8)';
      el.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
      observer.observe(el);
    });
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
          behavior: 'smooth'
        });
      });
    });
    
    // Roles Carousel Functionality
    const carousel = document.querySelector('.carousel-container');
    const slides = document.querySelectorAll('.role-slide');
    const indicators = document.querySelectorAll('.indicator');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    
    let currentIndex = 0;
    const totalSlides = slides.length;
    
    // Update carousel position
    function updateCarousel() {
      carousel.style.transform = translateX(-${currentIndex * 100}%);
      
      // Update indicators
      indicators.forEach((indicator, index) => {
        indicator.classList.toggle('active', index === currentIndex);
      });
    }
    
    // Next slide
    function nextSlide() {
      currentIndex = (currentIndex + 1) % totalSlides;
      updateCarousel();
    }
    
    // Previous slide
    function prevSlide() {
      currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
      updateCarousel();
    }
    
    // Auto slide every 5 seconds
    let slideInterval = setInterval(nextSlide, 5000);
    
    // Event listeners
    nextBtn.addEventListener('click', () => {
      clearInterval(slideInterval);
      nextSlide();
      slideInterval = setInterval(nextSlide, 5000);
    });
    
    prevBtn.addEventListener('click', () => {
      clearInterval(slideInterval);
      prevSlide();
      slideInterval = setInterval(nextSlide, 5000);
    });
    
    // Indicator clicks
    indicators.forEach(indicator => {
      indicator.addEventListener('click', () => {
        clearInterval(slideInterval);
        currentIndex = parseInt(indicator.getAttribute('data-index'));
        updateCarousel();
        slideInterval = setInterval(nextSlide, 5000);
      });
    });
    
    // Pause auto slide on hover
    const carouselContainer = document.querySelector('.roles-carousel');
    carouselContainer.addEventListener('mouseenter', () => {
      clearInterval(slideInterval);
    });
    
    carouselContainer.addEventListener('mouseleave', () => {
      slideInterval = setInterval(nextSlide, 5000);
    });
    
    // Search Functionality
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    
    // Searchable content
    const searchableContent = [
      {
        title: "Frisking Team",
        category: "Roles",
        icon: "fas fa-search",
        description: "Team responsible for checking students for prohibited items before exams",
        link: "#"
      },
      {
        title: "Invigilators",
        category: "Roles",
        icon: "fas fa-user-graduate",
        description: "Staff who supervise exams and ensure fair conduct",
        link: "#"
      },
      {
        title: "Hall Coordinator",
        category: "Roles",
        icon: "fas fa-tasks",
        description: "Manages all activities within a specific examination venue",
        link: "#"
      },
      {
        title: "Chief Supervisor",
        category: "Roles",
        icon: "fas fa-user-tie",
        description: "Oversees the entire examination process at the institution level",
        link: "#"
      },
      {
        title: "Easy Scheduling",
        category: "Features",
        icon: "fas fa-calendar-alt",
        description: "Automated duty scheduling with conflict detection",
        link: "#"
      },
      {
        title: "Real-time Notifications",
        category: "Features",
        icon: "fas fa-bell",
        description: "Instant alerts for duty assignments and changes",
        link: "#"
      },
      {
        title: "Performance Analytics",
        category: "Features",
        icon: "fas fa-chart-line",
        description: "Track and analyze invigilator performance",
        link: "#"
      },
      {
        title: "Digital Reporting",
        category: "Features",
        icon: "fas fa-file-alt",
        description: "Submit incident reports digitally",
        link: "#"
      },
      {
        title: "Help Center",
        category: "Resources",
        icon: "fas fa-question-circle",
        description: "Get assistance with the portal",
        link: "help.php"
      },
      {
        title: "Training Modules",
        category: "Resources",
        icon: "fas fa-book",
        description: "Mandatory training for all exam staff",
        link: "training.php"
      }
    ];
    
    // Search function
    function performSearch(query) {
      if (!query.trim()) {
        searchResults.style.display = 'none';
        return;
      }
      
      const lowerQuery = query.toLowerCase();
      const results = searchableContent.filter(item => 
        item.title.toLowerCase().includes(lowerQuery) || 
        item.description.toLowerCase().includes(lowerQuery) ||
        item.category.toLowerCase().includes(lowerQuery)
      );
      
      displayResults(results);
    }
    
    // Display search results
    function displayResults(results) {
      searchResults.innerHTML = '';
      
      if (results.length === 0) {
        searchResults.innerHTML = '<div class="search-result-item">No results found</div>';
        searchResults.style.display = 'block';
        return;
      }
      
      results.forEach(result => {
        const item = document.createElement('div');
        item.className = 'search-result-item';
        item.innerHTML = `
          <i class="${result.icon}"></i>
          <div>
            <strong>${result.title}</strong>
            <div style="font-size:0.9rem;color:var(--secondary);">${result.description}</div>
            <small style="color:var(--primary);">${result.category}</small>
          </div>
        `;
        item.addEventListener('click', () => {
          window.location.href = result.link;
        });
        searchResults.appendChild(item);
      });
      
      searchResults.style.display = 'block';
    }
    
    // Event listeners for search
    searchForm.addEventListener('submit', (e) => {
      e.preventDefault();
      performSearch(searchInput.value);
    });
    
    searchInput.addEventListener('input', () => {
      performSearch(searchInput.value);
    });
    
    // Close search results when clicking outside
    document.addEventListener('click', (e) => {
      if (!searchForm.contains(e.target)) {
        searchResults.style.display = 'none';
      }
    });
    
    // Focus on search input when pressing Ctrl+K
    document.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        searchInput.focus();
      }
    });
    
    // Clear search when pressing Escape
    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        searchInput.value = '';
        searchResults.style.display = 'none';
      }
    });
  });
</script>

</body>
</html>