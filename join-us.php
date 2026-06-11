<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/districts.php';
require_once __DIR__ . '/config/member_schema.php';
require_once __DIR__ . '/config/admin.php';

ensureMemberColumns($pdo);
ensureAdminSchema($pdo);

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? (int) $_SESSION['user_id'] : 0;
$errors = [];
$success = isset($_GET['joined']) ? 'Your DREAM club membership information has been saved.' : '';

$bloodGroups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
$fullName = '';
$email = '';
$phone = '';
$bloodGroup = '';
$district = '';
$isDonor = '1';
$availableToDonate = '1';
$lastDonationDate = '';
$kuetBatch = '';
$department = '';
$rollNo = '';

if ($isLoggedIn) {
    $statement = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $statement->execute([$userId]);
    $currentUser = $statement->fetch();

    if (!$currentUser) {
        session_destroy();
        header('Location: login.php');
        exit;
    }

    $fullName = $currentUser['full_name'] ?? '';
    $email = $currentUser['email'] ?? '';
    $phone = $currentUser['phone'] ?? '';
    $bloodGroup = $currentUser['blood_group'] ?? '';
    $district = $currentUser['district'] ?? '';
    $isDonor = (int) ($currentUser['is_donor'] ?? 1) === 1 ? '1' : '0';
    $availableToDonate = (int) ($currentUser['available_to_donate'] ?? 1) === 1 ? '1' : '0';
    $lastDonationDate = $currentUser['last_donation_date'] ?? '';
    $kuetBatch = $currentUser['kuet_batch'] ?? '';
    $department = $currentUser['department'] ?? '';
    $rollNo = $currentUser['roll_no'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = $isLoggedIn ? $email : trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $bloodGroup = trim($_POST['blood_group'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $isDonor = isset($_POST['is_donor']) ? '1' : '0';
    $availableToDonate = $isDonor === '1' && isset($_POST['available_to_donate']) ? '1' : '0';
    $lastDonationDate = trim($_POST['last_donation_date'] ?? '');
    $kuetBatch = trim($_POST['kuet_batch'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $rollNo = trim($_POST['roll_no'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || $email === '' || $phone === '' || $bloodGroup === '' || $district === '' || $kuetBatch === '' || $department === '' || $rollNo === '') {
        $errors[] = 'Please fill all required fields.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!preg_match('/^01[0-9]{9}$/', $phone)) {
        $errors[] = 'Please enter a valid Bangladeshi mobile number.';
    }

    if (!in_array($bloodGroup, $bloodGroups, true)) {
        $errors[] = 'Please select a valid blood group.';
    }

    if (!in_array($district, $districts, true)) {
        $errors[] = 'Please select a valid district.';
    }

    if ($lastDonationDate !== '' && $lastDonationDate > date('Y-m-d')) {
        $errors[] = 'Last donation date cannot be in the future.';
    }

    if (strlen($kuetBatch) > 20) {
        $errors[] = 'Batch must be 20 characters or less.';
    }

    if (strlen($department) > 120) {
        $errors[] = 'Department must be 120 characters or less.';
    }

    if (!preg_match('/^[A-Za-z0-9-]{1,30}$/', $rollNo)) {
        $errors[] = 'Roll must use only letters, numbers, or hyphen.';
    }

    if (!$isLoggedIn) {
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Password and confirm password do not match.';
        }
    }

    if (!$errors) {
        if ($isLoggedIn) {
            $statement = $pdo->prepare(
                'UPDATE users
                 SET full_name = ?, phone = ?, blood_group = ?, district = ?, is_donor = ?, available_to_donate = ?,
                     last_donation_date = ?, is_member = 1, kuet_batch = ?, department = ?, roll_no = ?,
                     member_joined_at = COALESCE(member_joined_at, NOW())
                 WHERE id = ?'
            );
            $statement->execute([
                $fullName,
                $phone,
                $bloodGroup,
                $district,
                (int) $isDonor,
                (int) $availableToDonate,
                $lastDonationDate !== '' ? $lastDonationDate : null,
                $kuetBatch,
                $department,
                $rollNo,
                $userId
            ]);

            $_SESSION['user_name'] = $fullName;
            header('Location: join-us.php?joined=1');
            exit;
        }

        $statement = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);

        if ($statement->fetch()) {
            $errors[] = 'This email already has an account. Please login first and join from the Join Us page.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $statement = $pdo->prepare(
                'INSERT INTO users
                    (full_name, email, phone, blood_group, district, is_donor, available_to_donate, last_donation_date, password_hash,
                     is_member, kuet_batch, department, roll_no, member_joined_at)
                 VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, NOW())'
            );
            $statement->execute([
                $fullName,
                $email,
                $phone,
                $bloodGroup,
                $district,
                (int) $isDonor,
                (int) $availableToDonate,
                $lastDonationDate !== '' ? $lastDonationDate : null,
                $passwordHash,
                $kuetBatch,
                $department,
                $rollNo
            ]);

            $_SESSION['user_id'] = (int) $pdo->lastInsertId();
            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_role'] = 'user';

            header('Location: join-us.php?joined=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Us - Dream KUET</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body>
    <main class="auth-page join-page">
        <section class="auth-card join-card">
            <span class="eyebrow">Join DREAM</span>
            <h1>Join Us</h1>
            <p>Become a DREAM club member with your regular account and KUET student information.</p>

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
                <div class="form-grid">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($fullName); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <?php if ($isLoggedIn): ?>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($email); ?>" disabled>
                        <?php else: ?>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" pattern="01[0-9]{9}" required>
                    </div>

                    <div class="form-group">
                        <label for="blood_group">Blood Group</label>
                        <select id="blood_group" name="blood_group" required>
                            <option value="">Select</option>
                            <?php foreach ($bloodGroups as $group): ?>
                                <option value="<?php echo $group; ?>" <?php echo $bloodGroup === $group ? 'selected' : ''; ?>><?php echo $group; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="district">District</label>
                        <select id="district" name="district" required>
                            <option value="">Select</option>
                            <?php foreach ($districts as $districtName): ?>
                                <option value="<?php echo htmlspecialchars($districtName); ?>" <?php echo $district === $districtName ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($districtName); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="last_donation_date">Last Donation Date</label>
                        <input type="date" id="last_donation_date" name="last_donation_date" value="<?php echo htmlspecialchars($lastDonationDate ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="kuet_batch">Batch</label>
                        <input type="text" id="kuet_batch" name="kuet_batch" value="<?php echo htmlspecialchars($kuetBatch); ?>" placeholder="Example: 2K23" required>
                    </div>

                    <div class="form-group">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($department); ?>" placeholder="Example: CSE" required>
                    </div>

                    <div class="form-group">
                        <label for="roll_no">Roll</label>
                        <input type="text" id="roll_no" name="roll_no" value="<?php echo htmlspecialchars($rollNo); ?>" required>
                    </div>
                </div>

                <div class="auth-check-grid">
                    <label class="check-row">
                        <input type="checkbox" name="is_donor" value="1" <?php echo $isDonor === '1' ? 'checked' : ''; ?>>
                        <span>Register me as a blood donor</span>
                    </label>

                    <label class="check-row">
                        <input type="checkbox" name="available_to_donate" value="1" <?php echo $availableToDonate === '1' ? 'checked' : ''; ?>>
                        <span>I am currently available to donate</span>
                    </label>
                </div>

                <?php if (!$isLoggedIn): ?>
                    <div class="form-grid compact-grid">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn-primary auth-submit">
                    <?php echo $isLoggedIn ? 'Save Membership' : 'Join Us'; ?>
                </button>
            </form>

            <?php if (!$isLoggedIn): ?>
                <p class="auth-switch">Already have an account? <a href="login.php">Login</a></p>
            <?php endif; ?>
            <a href="index.php" class="auth-back">Back to home</a>
        </section>
    </main>
</body>

</html>
