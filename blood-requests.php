<?php

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/districts.php';

$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $isLoggedIn ? (int) $_SESSION['user_id'] : 0;
$profileInitial = $isLoggedIn ? strtoupper(substr(trim($_SESSION['user_name']), 0, 1)) : '';
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';

$bloodGroups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
$urgencies = ['Emergency', 'Within 24 hours', 'Scheduled'];
$statusOptions = ['active', 'all', 'Pending', 'Donor Interested', 'Accepted', 'Fulfilled', 'Expired', 'Cancelled'];

$selectedBloodGroup = trim($_GET['blood_group'] ?? '');
$selectedDistrict = trim($_GET['district'] ?? '');
$selectedUrgency = trim($_GET['urgency'] ?? '');
$selectedStatus = trim($_GET['status'] ?? 'active');
$donationMessage = $_SESSION['donation_message'] ?? '';
$donationMessageType = $_SESSION['donation_message_type'] ?? 'success';
unset($_SESSION['donation_message'], $_SESSION['donation_message_type']);


$pdo->exec(
    "CREATE TABLE IF NOT EXISTS donation_responses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        donor_id INT NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'Interested',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_request_donor (request_id, donor_id),
        FOREIGN KEY (request_id) REFERENCES blood_requests(id) ON DELETE CASCADE,
        FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE
    )"
);

if (!in_array($selectedBloodGroup, $bloodGroups, true)) {
    $selectedBloodGroup = '';
}

if (!in_array($selectedDistrict, $districts, true)) {
    $selectedDistrict = '';
}

if (!in_array($selectedUrgency, $urgencies, true)) {
    $selectedUrgency = '';
}

if (!in_array($selectedStatus, $statusOptions, true)) {
    $selectedStatus = 'active';
}


$pdo->exec("UPDATE blood_requests SET status = 'Expired' WHERE status = 'Pending' AND needed_date < CURDATE()");


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'donate') {
    if (!$isLoggedIn) {
        header('Location: login.php');
        exit;
    }

    $requestId = (int) ($_POST['request_id'] ?? 0);
    $message = '';
    $messageType = 'success';

    $statement = $pdo->prepare(
        "SELECT br.*, u.full_name AS requester_name
         FROM blood_requests br
         JOIN users u ON u.id = br.user_id
         WHERE br.id = ?
         LIMIT 1"
    );
    $statement->execute([$requestId]);
    $targetRequest = $statement->fetch();

    $statement = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $statement->execute([$currentUserId]);
    $currentUser = $statement->fetch();

    if (!$targetRequest) {
        $message = 'Blood request was not found.';
        $messageType = 'error';
    } elseif ((int) $targetRequest['user_id'] === $currentUserId) {
        $message = 'You cannot donate to your own blood request.';
        $messageType = 'error';
    } elseif (!in_array($targetRequest['status'], ['Pending', 'Donor Interested'], true) || $targetRequest['needed_date'] < date('Y-m-d')) {
        $message = 'This blood request is no longer accepting donor responses.';
        $messageType = 'error';
    } elseif (!$currentUser || (int) $currentUser['is_donor'] !== 1) {
        $message = 'Your profile is not registered as a donor. Please update your profile first.';
        $messageType = 'error';
    } elseif ((int) $currentUser['available_to_donate'] !== 1) {
        $message = 'Your profile says you are not currently available to donate.';
        $messageType = 'error';
    } elseif ($currentUser['blood_group'] !== $targetRequest['blood_group']) {
        $message = 'Your blood group does not match this request.';
        $messageType = 'error';
    } elseif ($currentUser['last_donation_date'] !== null && $currentUser['last_donation_date'] > date('Y-m-d', strtotime('-90 days'))) {
        $nextEligibleDate = date('Y-m-d', strtotime($currentUser['last_donation_date'] . ' +90 days'));
        $message = 'You are not eligible yet. Your next possible donation date is ' . $nextEligibleDate . '.';
        $messageType = 'error';
    } else {
        try {
            $statement = $pdo->prepare('INSERT INTO donation_responses (request_id, donor_id) VALUES (?, ?)');
            $statement->execute([$requestId, $currentUserId]);

            $statement = $pdo->prepare("UPDATE blood_requests SET status = 'Donor Interested' WHERE id = ? AND status = 'Pending'");
            $statement->execute([$requestId]);

            $message = 'Your donation interest has been sent to the requester.';
        } catch (PDOException $error) {
            if ($error->getCode() === '23000') {
                $message = 'You have already responded to this blood request.';
            } else {
                $message = 'Could not save your donor response. Please try again.';
            }
            $messageType = 'error';
        }
    }

    $_SESSION['donation_message'] = $message;
    $_SESSION['donation_message_type'] = $messageType;
    header('Location: blood-requests.php');
    exit;
}


$where = [];
$params = [];

if ($selectedStatus === 'active') {
    $where[] = "br.status NOT IN ('Fulfilled', 'Expired', 'Cancelled')";
} elseif ($selectedStatus !== 'all') {
    $where[] = 'br.status = ?';
    $params[] = $selectedStatus;
}

if ($selectedBloodGroup !== '') {
    $where[] = 'br.blood_group = ?';
    $params[] = $selectedBloodGroup;
}

if ($selectedDistrict !== '') {
    $where[] = 'br.district = ?';
    $params[] = $selectedDistrict;
}

if ($selectedUrgency !== '') {
    $where[] = 'br.urgency = ?';
    $params[] = $selectedUrgency;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$statement = $pdo->prepare(
    "SELECT br.*, u.full_name AS requester_name
     FROM blood_requests br
     JOIN users u ON u.id = br.user_id
     {$whereSql}
     ORDER BY
        CASE br.urgency
            WHEN 'Emergency' THEN 1
            WHEN 'Within 24 hours' THEN 2
            ELSE 3
        END,
        br.needed_date ASC,
        br.created_at DESC"
);
$statement->execute($params);
$bloodRequests = $statement->fetchAll();

$respondedRequestIds = [];
if ($isLoggedIn) {
    $statement = $pdo->prepare('SELECT request_id FROM donation_responses WHERE donor_id = ?');
    $statement->execute([$currentUserId]);
    $respondedRequestIds = array_map('intval', array_column($statement->fetchAll(), 'request_id'));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Requests - Dream KUET</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Main navigation: public request list is available to guests and logged-in users. -->
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
                <li><a href="blood-requests.php" class="active-link">Blood Requests</a></li>
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
            <?php if ($isAdmin): ?>
                <a href="admin/dashboard.php">Admin Dashboard</a>
            <?php endif; ?>
            <a href="find-donors.php">Search Donors</a>
            <a href="add-request.php">Add Blood Request</a>
            <a href="join-us.php">Join Us</a>
            <a class="sidebar-logout" href="logout.php">Logout</a>
        </aside>
    <?php endif; ?>

    <main>
        <!-- Page hero: introduces the public request board. -->
        <section class="blood-requests-hero">
            <div class="request-hero-content">
                <span class="eyebrow">Request board</span>
                <h1>Blood Requests</h1>
                <p>Browse active blood needs, filter by group and location, and respond when your donor profile is eligible.</p>
            </div>
        </section>

        <!-- Filters: narrow down requests without exposing unsafe input to SQL. -->
        <section class="blood-requests-section">
            <?php if ($donationMessage): ?>
                <div class="donation-alert <?php echo $donationMessageType === 'error' ? 'error' : ''; ?>">
                    <?php echo htmlspecialchars($donationMessage); ?>
                </div>
            <?php endif; ?>

            <form class="request-filter-form" method="GET">
                <div class="form-group">
                    <label for="blood_group">Blood Group</label>
                    <select id="blood_group" name="blood_group">
                        <option value="">All groups</option>
                        <?php foreach ($bloodGroups as $group): ?>
                            <option value="<?php echo $group; ?>" <?php echo $selectedBloodGroup === $group ? 'selected' : ''; ?>><?php echo $group; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="district">District</label>
                    <select id="district" name="district">
                        <option value="">All districts</option>
                        <?php foreach ($districts as $districtName): ?>
                            <option value="<?php echo htmlspecialchars($districtName); ?>" <?php echo $selectedDistrict === $districtName ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($districtName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="urgency">Urgency</label>
                    <select id="urgency" name="urgency">
                        <option value="">Any urgency</option>
                        <?php foreach ($urgencies as $urgency): ?>
                            <option value="<?php echo $urgency; ?>" <?php echo $selectedUrgency === $urgency ? 'selected' : ''; ?>><?php echo $urgency; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="active" <?php echo $selectedStatus === 'active' ? 'selected' : ''; ?>>Active only</option>
                        <option value="all" <?php echo $selectedStatus === 'all' ? 'selected' : ''; ?>>All requests</option>
                        <option value="Pending" <?php echo $selectedStatus === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Donor Interested" <?php echo $selectedStatus === 'Donor Interested' ? 'selected' : ''; ?>>Donor Interested</option>
                        <option value="Accepted" <?php echo $selectedStatus === 'Accepted' ? 'selected' : ''; ?>>Accepted</option>
                        <option value="Fulfilled" <?php echo $selectedStatus === 'Fulfilled' ? 'selected' : ''; ?>>Fulfilled</option>
                        <option value="Expired" <?php echo $selectedStatus === 'Expired' ? 'selected' : ''; ?>>Expired</option>
                        <option value="Cancelled" <?php echo $selectedStatus === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="btn-primary" type="submit">Apply Filters</button>
                    <a class="btn-form-secondary" href="blood-requests.php">Reset</a>
                </div>
            </form>

            <!-- Request cards: guests can browse, logged-in users can see contacts for coordination. -->
            <?php if (!$bloodRequests): ?>
                <div class="empty-state requests-empty">
                    <p>No blood requests found for the selected filters.</p>
                    <a class="btn-primary link-button" href="add-request.php">Add Blood Request</a>
                </div>
            <?php else: ?>
                <div class="public-request-grid">
                    <?php foreach ($bloodRequests as $request): ?>
                        <?php
                            $isOwnRequest = $isLoggedIn && (int) $request['user_id'] === $currentUserId;
                            $isExpired = $request['status'] === 'Expired';
                            $hasResponded = in_array((int) $request['id'], $respondedRequestIds, true);
                            $canRespond = !$isOwnRequest
                                && !$isExpired
                                && in_array($request['status'], ['Pending', 'Donor Interested'], true)
                                && $request['needed_date'] >= date('Y-m-d');
                        ?>
                        <article class="public-request-card">
                            <div class="request-card-head">
                                <span class="blood-chip"><?php echo htmlspecialchars($request['blood_group']); ?></span>
                                <span class="request-status-pill <?php echo $isExpired ? 'expired' : ''; ?>">
                                    <?php echo $isExpired ? 'Past date' : htmlspecialchars($request['status']); ?>
                                </span>
                            </div>

                            <h2><?php echo htmlspecialchars($request['patient_name']); ?></h2>
                            <div class="request-meta-list">
                                <span><?php echo htmlspecialchars($request['blood_bag']); ?> bag needed</span>
                                <span><?php echo htmlspecialchars($request['urgency']); ?></span>
                                <span><?php echo htmlspecialchars($request['needed_date']); ?></span>
                            </div>

                            <p class="request-location">
                                <?php echo htmlspecialchars($request['hospital']); ?>, <?php echo htmlspecialchars($request['district']); ?>
                            </p>
                            <p><?php echo htmlspecialchars($request['address']); ?></p>

                            <?php if ($request['details']): ?>
                                <p class="request-details"><?php echo htmlspecialchars($request['details']); ?></p>
                            <?php endif; ?>

                            <div class="request-contact-box">
                                <?php if (!$isLoggedIn): ?>
                                    <p>Login to view contact details and check donor eligibility.</p>
                                    <a class="btn-form-secondary" href="login.php">Login</a>
                                <?php elseif ($isOwnRequest): ?>
                                    <p>This is your request. Track donor responses from your profile.</p>
                                    <a class="btn-form-secondary" href="profile.php?section=requests">View My Requests</a>
                                <?php elseif ($hasResponded): ?>
                                    <p><strong>Requester:</strong> <?php echo htmlspecialchars($request['requester_name']); ?></p>
                                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($request['contact_name']); ?>, <?php echo htmlspecialchars($request['contact_phone']); ?></p>
                                    <button class="btn-primary" type="button" disabled>Already responded</button>
                                <?php elseif (!$canRespond): ?>
                                    <p><strong>Requester:</strong> <?php echo htmlspecialchars($request['requester_name']); ?></p>
                                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($request['contact_name']); ?>, <?php echo htmlspecialchars($request['contact_phone']); ?></p>
                                    <button class="btn-primary" type="button" disabled>Not accepting donors</button>
                                <?php else: ?>
                                    <p><strong>Requester:</strong> <?php echo htmlspecialchars($request['requester_name']); ?></p>
                                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($request['contact_name']); ?>, <?php echo htmlspecialchars($request['contact_phone']); ?></p>
                                    <form class="donate-action-form" method="POST" data-donate-form>
                                        <input type="hidden" name="action" value="donate">
                                        <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">
                                        <button class="btn-primary" type="submit">Donate</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
