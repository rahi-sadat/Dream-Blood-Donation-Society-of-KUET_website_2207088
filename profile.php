<?php
// PHP setup and access control: only logged-in users can open the profile page.
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/districts.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$profileInitial = strtoupper(substr(trim($_SESSION['user_name']), 0, 1));
$activeSection = $_GET['section'] ?? 'info';
$activeSection = in_array($activeSection, ['info', 'requests'], true) ? $activeSection : 'info';
$errors = [];
$success = '';

// Profile update handler: saves edited account information.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $bloodGroup = trim($_POST['blood_group'] ?? '');
        $district = trim($_POST['district'] ?? '');

        if ($fullName === '' || $phone === '' || $bloodGroup === '' || $district === '') {
            $errors[] = 'Please fill all profile fields.';
        }

        if (!preg_match('/^01[0-9]{9}$/', $phone)) {
            $errors[] = 'Please enter a valid Bangladeshi mobile number.';
        }

        if (!in_array($bloodGroup, ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'], true)) {
            $errors[] = 'Please select a valid blood group.';
        }

        if (!in_array($district, $districts, true)) {
            $errors[] = 'Please select a valid district.';
        }

        if (!$errors) {
            $statement = $pdo->prepare(
                'UPDATE users SET full_name = ?, phone = ?, blood_group = ?, district = ? WHERE id = ?'
            );
            $statement->execute([$fullName, $phone, $bloodGroup, $district, $userId]);
            $_SESSION['user_name'] = $fullName;
            $success = 'Profile updated successfully.';
        }
    }
}

// Automatic status maintenance: unanswered past-date requests are no longer pending.
$pdo->exec("UPDATE blood_requests SET status = 'Expired' WHERE status = 'Pending' AND needed_date < CURDATE()");

// Load current user information for the profile form and sidebar.
$statement = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$statement->execute([$userId]);
$user = $statement->fetch();

// Load the logged-in user's own blood requests.
$statement = $pdo->prepare('SELECT * FROM blood_requests WHERE user_id = ? ORDER BY created_at DESC');
$statement->execute([$userId]);
$requests = $statement->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Dream KUET</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Main navigation: shared top menu with profile drawer trigger. -->
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
                <li><a href="index.php#search">Search Donor</a></li>
                <li><a href="blood-requests.php">Blood Requests</a></li>
                <li><a href="add-request.php">Add Blood Request</a></li>
                <li><a href="index.php#campaigns">Campaigns</a></li>
            </ul>

            <div class="nav-actions">
                <button class="profile-nav-link active-profile" type="button" data-profile-menu-toggle aria-label="Open profile menu">
                    <span class="profile-icon"><?php echo htmlspecialchars($profileInitial); ?></span>
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </button>
            </div>
        </nav>
    </header>

    <!-- Profile sidebar: quick account actions shown when the profile name is clicked. -->
    <div class="profile-menu-backdrop" data-profile-menu-close></div>
    <aside class="profile-sidebar" aria-label="Profile menu">
        <div class="profile-sidebar-head">
            <span class="profile-icon large"><?php echo htmlspecialchars($profileInitial); ?></span>
            <div>
                <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                <p><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
            </div>
        </div>
        <a href="profile.php?section=info">Profile Information</a>
        <a href="profile.php?section=requests">My Blood Requests</a>
        <a href="blood-requests.php">Blood Requests</a>
        <a href="add-request.php">Add Blood Request</a>
        <a class="sidebar-logout" href="logout.php">Logout</a>
    </aside>

    <main>
        <!-- Profile hero: page heading changes based on the selected sidebar section. -->
        <section class="profile-hero">
            <div class="request-hero-content">
                <span class="eyebrow">Account</span>
                <h1><?php echo $activeSection === 'requests' ? 'My Blood Requests' : 'My Profile'; ?></h1>
                <p>
                    <?php echo $activeSection === 'requests'
                        ? 'Track the blood requests you submitted and their current status.'
                        : 'Manage your donor information and keep your contact details updated.'; ?>
                </p>
            </div>
        </section>

        <!-- Profile content: only one section is shown at a time from the sidebar menu. -->
        <section class="profile-section">
            <div class="profile-layout single-profile-panel">
                <?php if ($activeSection === 'info'): ?>
                <article class="profile-panel" id="profile-info">
                    <h2>My Information</h2>

                    <?php if ($success): ?>
                        <div class="form-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <?php if ($errors): ?>
                        <div class="form-alert">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="profile.php?section=info" class="auth-form">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" pattern="01[0-9]{9}" required>
                        </div>

                        <div class="form-grid compact-grid">
                            <div class="form-group">
                                <label for="blood_group">Blood Group</label>
                                <select id="blood_group" name="blood_group" required>
                                    <?php foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $group): ?>
                                        <option value="<?php echo $group; ?>" <?php echo $user['blood_group'] === $group ? 'selected' : ''; ?>><?php echo $group; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="district">District</label>
                                <select id="district" name="district" required>
                                    <?php foreach ($districts as $districtName): ?>
                                        <option value="<?php echo htmlspecialchars($districtName); ?>" <?php echo $user['district'] === $districtName ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($districtName); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <button class="btn-primary auth-submit" type="submit">Update Profile</button>
                    </form>
                </article>
                <?php endif; ?>

                <?php if ($activeSection === 'requests'): ?>
                <article class="profile-panel" id="my-requests">
                    <h2>My Blood Requests</h2>

                    <?php if (!$requests): ?>
                        <div class="empty-state">
                            <p>You have not submitted any blood request yet.</p>
                            <a class="btn-primary link-button" href="add-request.php">Add Blood Request</a>
                        </div>
                    <?php endif; ?>

                    <div class="profile-request-list">
                        <?php foreach ($requests as $request): ?>
                            <div class="profile-request-card">
                                <div>
                                    <span class="blood-chip"><?php echo htmlspecialchars($request['blood_group']); ?></span>
                                    <h3><?php echo htmlspecialchars($request['patient_name']); ?></h3>
                                    <p><?php echo htmlspecialchars($request['hospital']); ?>, <?php echo htmlspecialchars($request['district']); ?></p>
                                    <p>Needed: <?php echo htmlspecialchars($request['needed_date']); ?> | Bags: <?php echo htmlspecialchars($request['blood_bag']); ?></p>
                                </div>

                                <div class="request-status-display <?php echo $request['status'] === 'Expired' ? 'expired' : ''; ?>">
                                    <span>Status</span>
                                    <strong><?php echo htmlspecialchars($request['status']); ?></strong>
                                </div>

                                <div class="donor-match-note">
                                    <?php if ($request['status'] === 'Expired'): ?>
                                        This request date has passed without a donor response.
                                    <?php else: ?>
                                        Donor responses will appear here after we add the donor response feature.
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <script src="script.js"></script>
</body>

</html>
