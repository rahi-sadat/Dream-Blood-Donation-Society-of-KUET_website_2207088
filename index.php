<?php
// Homepage setup: starts session so the navbar can detect login state.
session_start();
require_once __DIR__ . '/config/districts.php';
$profileInitial = isset($_SESSION['user_name']) ? strtoupper(substr(trim($_SESSION['user_name']), 0, 1)) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dream - Voluntary Blood Donation Society of KUET</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Main navigation: shows Login/Register for guests and profile menu for logged-in users. -->
    <header>
        <nav>
            <div class="logo-area">
                <img src="images/logo.png" alt="Dream logo" width="50">
                <div class="brand-text">
                    <span class="brand-name">DREAM</span>

                </div>

            </div>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About Us</a></li>
                <li><a href="find-donors.php">Search Donor</a></li>
                <li><a href="blood-requests.php">Blood Requests</a></li>
                <li><a href="add-request.php">Add Blood Request</a></li>
                <li><a href="#campaigns">Campaigns</a></li>
            </ul>

            <div class="nav-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button class="profile-nav-link" type="button" data-profile-menu-toggle aria-label="Open profile menu">
                        <span class="profile-icon"><?php echo htmlspecialchars($profileInitial); ?></span>
                        <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    </button>
                <?php else: ?>
                    <button type="button" class="btn-register" data-link="register.php">Register</button>
                    <button type="button" class="btn-login" data-link="login.php">Login</button>
                <?php endif; ?>

                

            </div>
        </nav>
    </header>

    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Profile sidebar: quick account links for logged-in users. -->
        <div class="profile-menu-backdrop" data-profile-menu-close></div>
        <aside class="profile-sidebar" aria-label="Profile menu">
            <div class="profile-sidebar-head">
                <span class="profile-icon large"><?php echo htmlspecialchars($profileInitial); ?></span>
                <div>
                    <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                    <p>Logged in</p>
                </div>
            </div>
            <a href="profile.php?section=info">Profile Information</a>
            <a href="profile.php?section=requests">My Blood Requests</a>
            <a href="find-donors.php">Search Donors</a>
            <a href="blood-requests.php">Blood Requests</a>
            <a href="add-request.php">Add Blood Request</a>
            <a class="sidebar-logout" href="logout.php">Logout</a>
        </aside>
    <?php endif; ?>

    <!-- Homepage content: hero, donor search, about section, and request preview area. -->
    <main id="main-content">
        <div id="home-page">
        <section id="home" class="hero-section">
            <div class="hero-content">
                <h1>Every Drop Counts. <br>Be the Pulse of Hope.</h1>
                <p>Join the Dream-Voluntary Blood Donation Society of KUET. We bridge the gap between donors and those
                    in need, right here on campus.</p>
                <div class="cta-group">
                    <button class="btn-primary" type="button" data-link="add-request.php">Blood Request</button>
                    <button class="btn-secondary" type="button" data-link="register.php">Become a Donor</button>
                </div>
            </div>
        </section>

       <section id="search">
    <h2>Search Donors</h2>
    <form class="search-container" action="find-donors.php" method="GET">
        <!-- Blood Group -->
        <div class="search-group">
            <label>Blood Group</label>
            <select name="blood_group">
                <option value="">Select</option>
                <option value="A+">A+</option>
                <option value="A-">A-</option>
                <option value="B+">B+</option>
                <option value="B-">B-</option>
                <option value="O+">O+</option>
                <option value="O-">O-</option>
                <option value="AB+">AB+</option>
                <option value="AB-">AB-</option>
            </select>
        </div>

        <!-- District -->
        <div class="search-group">
            <label>District</label>
            <select name="district">
                <option value="">Select</option>
                <?php foreach ($districts as $districtName): ?>
                    <option value="<?php echo htmlspecialchars($districtName); ?>"><?php echo htmlspecialchars($districtName); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Search Button -->
        <div class="search-group action-group">
            <button class="btn-search" type="submit">Search</button>
        </div>
    </form>
</section>

        <section id="about">
            <span class="eyebrow">About us</span>
            <h2>About Dream</h2>
            <p>Dream is a voluntary blood donation society at Khulna University of Engineering & Technology. We are
                dedicated to promoting voluntary donation, managing emergency supplies, and creating awareness through
                our regular campaigns.</p>
             <a href="#about" class="btn-learn-more">Learn More</a>
        </section>

        <section id="requests">
            <div class="section-title">
                <button class="emergency-request-button" type="button" data-link="add-request.php">
                    Emergency Blood Request
                </button>
                <span class="pulse-icon"></span>
            </div>
            <div class="request-grid">
            </div>
        </section>

            </div>
                        
        <div id="about-page" style="display: none;">
        <section class="about-hero">
            <div class="about-hero-inner">
                <span class="eyebrow">DREAM KUET</span>
                <h1>About Us</h1>
                <p>Dream connects voluntary blood donors with people who need urgent support around KUET and nearby communities.</p>
                <div class="about-hero-actions">
                    <button class="btn-primary" type="button" data-link="add-request.php">Request Blood</button>
                    <button class="btn-secondary" type="button" data-link="find-donors.php">Find Donors</button>
                </div>
            </div>
        </section>

        <section class="about-details">
            <div class="about-grid">
                <div class="left-col">
                    <span class="eyebrow">Our story</span>
                    <h2>Donate Blood, Save Life</h2>
                    <p>Dream is a voluntary blood donation society managed by KUET students. We work to encourage safe, humane blood donation and help people find donors when every minute matters.</p>
                    <h3>Why Dream?</h3>
                    <p>
                        In many critical medical emergencies, the difference between life and death is often a single bag of blood.
                        While the demand is constant, the supply often falls short because communication is scattered.
                        <strong>Dream</strong> was established at KUET to solve this problem.
                    </p>
                    <p>
                        We act as a digital bridge, connecting voluntary blood donors with those in urgent need.
                        By maintaining an active database of student and local donors, help stays close and reachable.
                    </p>
                </div>
                <div class="right-col">
                    <div class="about-info-card">
                        <h3>Vision</h3>
                        <p>Ensuring no more death just for the need of blood.</p>
                    </div>
                    <div class="about-info-card">
                        <h3>Mission</h3>
                        <p>Connecting blood searchers with voluntary blood donors quickly through technology.</p>
                    </div>
                    <div class="about-info-card">
                        <h3>Objectives</h3>
                        <ul>
                            <li>Encouraging voluntary blood donation</li>
                            <li>Creating awareness about safe blood transfer</li>
                            <li>Connecting voluntary blood donors with urgent requests</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </div>
                
    <div id="campaign-content" style="display: none;">
       
    </div>
    </main>

    <footer class="main-footer">
        <div class="footer-grid">
            <div class="footer-brand">
                <img src="images/logo.png" alt="Dream Logo" width="60">
                <h3>DREAM KUET</h3>
                <p>An automated blood service connecting seekers with donors in a moment.</p>
            </div>
            <div class="footer-column">
                <h4>Important Links</h4>
                <a href="#home">Home</a>
                <a href="find-donors.php">Search Donors</a>
                <a href="blood-requests.php">Blood Requests</a>
                <a href="add-request.php">Add Blood Request</a>
                <a href="#search">Search Donors</a>
                <a href="#about">About Us</a>
            </div>
            <div class="footer-column">
                <h4>About Blood</h4>
                <a href="#">What is blood?</a>
                <a href="#">Who can donate?</a>
                <a href="#">Blood Groups</a>
                <a href="#">FAQs</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Dream-Voluntary Blood Donation Society of KUET. All Rights Reserved.</p>
        </div>
    </footer>
    <script src="script.js"></script>
</body>

</html>
