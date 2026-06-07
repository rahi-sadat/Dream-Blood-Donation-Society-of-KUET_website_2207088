<?php
session_start();

$profileInitial = isset($_SESSION['user_name']) ? strtoupper(substr(trim($_SESSION['user_name']), 0, 1)) : '';
$type = $_GET['type'] ?? 'committee';

$galleries = [
    'committee' => [
        'title' => 'Current Committee',
        'description' => 'The current DREAM committee from the latest uploaded photos.',
        'images' => [
            ['src' => 'images/Committee1.jpg', 'alt' => 'DREAM current committee photo 1'],
            ['src' => 'images/Committee2.jpg', 'alt' => 'DREAM current committee photo 2'],
            ['src' => 'images/Committee3.jpg', 'alt' => 'DREAM current committee photo 3'],
        ],
    ],
    'volunteers' => [
        'title' => 'Volunteers List',
        'description' => 'The current DREAM volunteers list from the latest uploaded photos.',
        'images' => [
            ['src' => 'images/volunteers1.jpg', 'alt' => 'DREAM volunteers list photo 1'],
            ['src' => 'images/volunteers2.jpg', 'alt' => 'DREAM volunteers list photo 2'],
        ],
    ],
];

if (!isset($galleries[$type])) {
    $type = 'committee';
}

$gallery = $galleries[$type];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($gallery['title']); ?> - DREAM KUET</title>
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
            <a href="find-donors.php">Search Donors</a>
            <a href="blood-requests.php">Blood Requests</a>
            <a href="add-request.php">Add Blood Request</a>
            <a class="sidebar-logout" href="logout.php">Logout</a>
        </aside>
    <?php endif; ?>

    <main>
        <section class="people-page-hero">
            <div class="people-page-hero-inner">
                <span class="eyebrow">DREAM KUET</span>
                <h1><?php echo htmlspecialchars($gallery['title']); ?></h1>
                <p><?php echo htmlspecialchars($gallery['description']); ?></p>
                <div class="people-page-actions">
                    <a class="btn-form-secondary" href="index.php#people">Back to Home</a>
                    <a class="<?php echo $type === 'committee' ? 'btn-primary' : 'btn-form-secondary'; ?>" href="people.php?type=committee">Committee</a>
                    <a class="<?php echo $type === 'volunteers' ? 'btn-primary' : 'btn-form-secondary'; ?>" href="people.php?type=volunteers">Volunteers</a>
                </div>
            </div>
        </section>

        <section class="people-gallery-section">
            <div class="people-gallery people-page-gallery">
                <?php foreach ($gallery['images'] as $image): ?>
                    <a class="people-photo" href="<?php echo htmlspecialchars($image['src']); ?>" target="_blank" rel="noopener">
                        <img src="<?php echo htmlspecialchars($image['src']); ?>" alt="<?php echo htmlspecialchars($image['alt']); ?>">
                    </a>
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
