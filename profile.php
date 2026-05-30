<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/districts.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$errors = [];
$success = '';

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

    if ($action === 'update_request_status') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $allowedStatuses = ['Pending', 'Fulfilled', 'Cancelled'];

        if ($requestId > 0 && in_array($status, $allowedStatuses, true)) {
            $statement = $pdo->prepare('UPDATE blood_requests SET status = ? WHERE id = ? AND user_id = ?');
            $statement->execute([$status, $requestId, $userId]);
            $success = 'Request status updated.';
        }
    }
}

$statement = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$statement->execute([$userId]);
$user = $statement->fetch();

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
                <li><a href="add-request.php">Add Blood Request</a></li>
                <li><a href="index.php#campaigns">Campaigns</a></li>
            </ul>

            <div class="nav-actions">
                <a class="profile-nav-link active-profile" href="profile.php" aria-label="Open profile">
                    <span class="profile-icon">P</span>
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </a>
                <a class="btn-login nav-link-button" href="logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="profile-hero">
            <div class="request-hero-content">
                <span class="eyebrow">Account</span>
                <h1>My Profile</h1>
                <p>Manage your donor information and track the blood requests you submitted.</p>
            </div>
        </section>

        <section class="profile-section">
            <div class="profile-layout">
                <article class="profile-panel">
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

                    <form method="POST" class="auth-form">
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

                <article class="profile-panel">
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

                                <form method="POST" class="request-status-form">
                                    <input type="hidden" name="action" value="update_request_status">
                                    <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">
                                    <label for="status-<?php echo (int) $request['id']; ?>">Status</label>
                                    <select id="status-<?php echo (int) $request['id']; ?>" name="status">
                                        <?php foreach (['Pending', 'Fulfilled', 'Cancelled'] as $status): ?>
                                            <option value="<?php echo $status; ?>" <?php echo $request['status'] === $status ? 'selected' : ''; ?>><?php echo $status; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn-search" type="submit">Update</button>
                                </form>

                                <div class="donor-match-note">
                                    Donor responses will appear here after we add the donor response feature.
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            </div>
        </section>
    </main>
</body>

</html>
