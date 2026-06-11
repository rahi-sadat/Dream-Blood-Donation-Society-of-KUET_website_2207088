<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/admin.php';

ensureAdminSchema($pdo);

$profileInitial = isset($_SESSION['user_name']) ? strtoupper(substr(trim($_SESSION['user_name']), 0, 1)) : '';
$isAdmin = isset($_SESSION['user_id']) && currentUserIsAdmin($pdo);

$summaries = $pdo->query(
    'SELECT *
     FROM blood_summaries
     WHERE is_active = 1
     ORDER BY display_order ASC, id DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Donation Summary - DREAM KUET</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body>
    <header>
        <nav>
            <div class="logo-area">
                <img src="images/logo.png" alt="Dream logo" width="50">
                <div class="brand-text">
                    <span class="brand-name">DREAM</span>
                </div>
            </div>
            <ul class="nav-links">
                <li><a href="index.php#home">Home</a></li>
                <li><a href="index.php#about">About Us</a></li>
                <li><a href="join-us.php">Join Us</a></li>
                <li><a href="find-donors.php">Search Donor</a></li>
                <li><a href="blood-requests.php">Blood Requests</a></li>
                <li><a href="add-request.php">Add Blood Request</a></li>
                <li><a href="index.php#campaigns">Campaigns</a></li>
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
            <?php if ($isAdmin): ?>
                <a href="admin/dashboard.php">Admin Dashboard</a>
            <?php endif; ?>
            <a href="find-donors.php">Search Donors</a>
            <a href="blood-requests.php">Blood Requests</a>
            <a href="add-request.php">Add Blood Request</a>
            <a href="join-us.php">Join Us</a>
            <a class="sidebar-logout" href="logout.php">Logout</a>
        </aside>
    <?php endif; ?>

    <main>
        <section class="blood-summary-hero">
            <div class="blood-summary-hero-inner">
                <span class="eyebrow">DREAM KUET</span>
                <h1>Blood Donation Summary</h1>
                <p>Total donated blood bags by blood group from recent monthly reports.</p>
                <div class="people-page-actions">
                    <a class="btn-form-secondary" href="index.php#home">Back to Home</a>
                    <a class="btn-primary link-button" href="index.php#campaigns">Campaigns</a>
                </div>
            </div>
        </section>

        <section class="blood-summary-page-section">
            <div class="blood-summary-grid summary-page-grid">
                <?php if (!$summaries): ?>
                    <div class="empty-state">
                        <p>No blood donation summary image is available right now.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($summaries as $summary): ?>
                    <article class="blood-summary-card">
                        <a class="blood-summary-photo" href="<?php echo htmlspecialchars($summary['image_path']); ?>" target="_blank" rel="noopener">
                            <img src="<?php echo htmlspecialchars($summary['image_path']); ?>" alt="<?php echo htmlspecialchars($summary['alt_text']); ?>">
                        </a>
                        <div class="blood-summary-content">
                            <span class="status-tag"><?php echo htmlspecialchars($summary['summary_year']); ?></span>
                            <h3><?php echo htmlspecialchars($summary['month_label']); ?></h3>
                            <p>Total donated: <?php echo htmlspecialchars($summary['total_bags']); ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
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
                <a href="index.php#home">Home</a>
                <a href="find-donors.php">Search Donors</a>
                <a href="blood-requests.php">Blood Requests</a>
                <a href="add-request.php">Add Blood Request</a>
                <a href="join-us.php">Join Us</a>
                <a href="index.php#about">About Us</a>
                <a href="index.php#contact-rules">Contact &amp; Rules</a>
            </div>
            <div class="footer-column">
                <h4>About Blood</h4>
                <button class="footer-info-link" type="button" data-blood-answer="what">What is blood?</button>
                <button class="footer-info-link" type="button" data-blood-answer="donate">Who can donate?</button>
                <button class="footer-info-link" type="button" data-blood-answer="groups">Blood Groups</button>
                <button class="footer-info-link" type="button" data-blood-answer="faqs">FAQs</button>
                <div class="footer-answer-card" data-blood-answer-panel aria-live="polite" hidden>
                    <h5 data-blood-answer-title></h5>
                    <p data-blood-answer-text></p>
                </div>
            </div>
            <div class="footer-column footer-contact">
                <h4>Contact DREAM</h4>
                <a href="https://www.facebook.com/DreamKuet/" target="_blank" rel="noopener">
                    <span class="footer-social-icon">f</span>
                    Facebook Page
                </a>
                <a href="mailto:dreaminfo.kuet@gmail.com">
                    <span class="footer-social-icon">@</span>
                    dreaminfo.kuet@gmail.com
                </a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Dream-Voluntary Blood Donation Society of KUET. All Rights Reserved.</p>
        </div>
    </footer>
    <script src="script.js"></script>
</body>

</html>
