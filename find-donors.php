<?php
// Donor search page: finds registered users who opted in as available donors.
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/districts.php';

$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $isLoggedIn ? (int) $_SESSION['user_id'] : 0;
$profileInitial = $isLoggedIn ? strtoupper(substr(trim($_SESSION['user_name']), 0, 1)) : '';

$bloodGroups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
$selectedBloodGroup = trim($_GET['blood_group'] ?? '');
$selectedDistrict = trim($_GET['district'] ?? '');
$showResults = isset($_GET['blood_group']) || isset($_GET['district']);

if (!in_array($selectedBloodGroup, $bloodGroups, true)) {
    $selectedBloodGroup = '';
}

if (!in_array($selectedDistrict, $districts, true)) {
    $selectedDistrict = '';
}

// Donor eligibility: opted-in, available, and not within 90 days of last donation.
$where = [
    'is_donor = 1',
    'available_to_donate = 1',
    '(last_donation_date IS NULL OR last_donation_date <= DATE_SUB(CURDATE(), INTERVAL 90 DAY))'
];
$params = [];

if ($currentUserId > 0) {
    $where[] = 'id <> ?';
    $params[] = $currentUserId;
}

if ($selectedBloodGroup !== '') {
    $where[] = 'blood_group = ?';
    $params[] = $selectedBloodGroup;
}

if ($selectedDistrict !== '') {
    $where[] = 'district = ?';
    $params[] = $selectedDistrict;
}

$donors = [];

if ($showResults) {
    $statement = $pdo->prepare(
        'SELECT id, full_name, phone, blood_group, district, last_donation_date
         FROM users
         WHERE ' . implode(' AND ', $where) . '
         ORDER BY district ASC, blood_group ASC, full_name ASC'
    );
    $statement->execute($params);
    $donors = $statement->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Donors - Dream KUET</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Main navigation: donor search is available to guests and logged-in users. -->
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
                <li><a href="find-donors.php" class="active-link">Search Donor</a></li>
                <li><a href="blood-requests.php">Blood Requests</a></li>
                <li><a href="add-request.php">Add Blood Request</a></li>
                <li><a href="index.php#campaigns">Campaigns</a></li>
            </ul>

            <div class="nav-actions">
                <?php if ($isLoggedIn): ?>
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

    <?php if ($isLoggedIn): ?>
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

    <main>
        <!-- Donor search hero: explains how donor results are selected. -->
        <section class="donor-search-hero">
            <div class="request-hero-content">
                <span class="eyebrow">Find donors</span>
                <h1>Search Available Donors</h1>
                <p>Find donors who registered, opted in, are currently available, and have not donated within the last 90 days.</p>
            </div>
        </section>

        <!-- Donor search filters: blood group and district are the main matching fields. -->
        <section class="donor-search-section">
            <form class="request-filter-form donor-filter-form" method="GET">
                <div class="form-group">
                    <label for="blood_group">Blood Group</label>
                    <select id="blood_group" name="blood_group">
                        <option value="">Any group</option>
                        <?php foreach ($bloodGroups as $group): ?>
                            <option value="<?php echo $group; ?>" <?php echo $selectedBloodGroup === $group ? 'selected' : ''; ?>><?php echo $group; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="district">District</label>
                    <select id="district" name="district">
                        <option value="">Any district</option>
                        <?php foreach ($districts as $districtName): ?>
                            <option value="<?php echo htmlspecialchars($districtName); ?>" <?php echo $selectedDistrict === $districtName ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($districtName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="btn-primary" type="submit">Find Donors</button>
                    <a class="btn-form-secondary" href="find-donors.php">Reset</a>
                </div>
            </form>

            <!-- Donor results: contact details are only shown after login. -->
            <?php if (!$showResults): ?>
                <div class="empty-state donor-search-note">
                    <p>Select a blood group or district to search the donor list.</p>
                </div>
            <?php elseif (!$donors): ?>
                <div class="empty-state donor-search-note">
                    <p>No available donors found for the selected filters.</p>
                    <a class="btn-form-secondary" href="blood-requests.php">Check Blood Requests</a>
                </div>
            <?php else: ?>
                <div class="donor-result-grid">
                    <?php foreach ($donors as $donor): ?>
                        <article class="donor-card">
                            <div class="request-card-head">
                                <span class="blood-chip"><?php echo htmlspecialchars($donor['blood_group']); ?></span>
                                <span class="request-status-pill">Available</span>
                            </div>
                            <h2><?php echo htmlspecialchars($donor['full_name']); ?></h2>
                            <p><strong>District:</strong> <?php echo htmlspecialchars($donor['district']); ?></p>
                            <p>
                                <strong>Last donation:</strong>
                                <?php echo $donor['last_donation_date'] ? htmlspecialchars($donor['last_donation_date']) : 'Not provided'; ?>
                            </p>

                            <div class="request-contact-box">
                                <?php if ($isLoggedIn): ?>
                                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($donor['phone']); ?></p>
                                <?php else: ?>
                                    <p>Login to view donor contact information.</p>
                                    <a class="btn-form-secondary" href="login.php">Login</a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script src="script.js"></script>
</body>

</html>
