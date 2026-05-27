<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: add-request.php');
    exit;
}

$errors = [];
$fullName = '';
$email = '';
$phone = '';
$bloodGroup = '';
$district = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $bloodGroup = trim($_POST['blood_group'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || $email === '' || $phone === '' || $bloodGroup === '' || $district === '' || $password === '') {
        $errors[] = 'Please fill all required fields.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!preg_match('/^01[0-9]{9}$/', $phone)) {
        $errors[] = 'Please enter a valid Bangladeshi mobile number.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password and confirm password do not match.';
    }

    if (!$errors) {
        $statement = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);

        if ($statement->fetch()) {
            $errors[] = 'This email is already registered. Please login instead.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $statement = $pdo->prepare(
                'INSERT INTO users (full_name, email, phone, blood_group, district, password_hash)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $statement->execute([$fullName, $email, $phone, $bloodGroup, $district, $passwordHash]);

            $_SESSION['user_id'] = (int) $pdo->lastInsertId();
            $_SESSION['user_name'] = $fullName;

            header('Location: add-request.php');
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
    <title>Register - Dream KUET</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body>
    <main class="auth-page">
        <section class="auth-card">
            <span class="eyebrow">Join DREAM</span>
            <h1>Create Account</h1>
            <p>Register first so every blood request can be connected with a real requester.</p>

            <?php if ($errors): ?>
                <div class="form-alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($fullName); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" pattern="01[0-9]{9}" required>
                </div>

                <div class="form-grid compact-grid">
                    <div class="form-group">
                        <label for="blood_group">Blood Group</label>
                        <select id="blood_group" name="blood_group" required>
                            <option value="">Select</option>
                            <?php foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $group): ?>
                                <option value="<?php echo $group; ?>" <?php echo $bloodGroup === $group ? 'selected' : ''; ?>><?php echo $group; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="district">District</label>
                        <input type="text" id="district" name="district" value="<?php echo htmlspecialchars($district); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn-primary auth-submit">Register</button>
            </form>

            <p class="auth-switch">Already have an account? <a href="login.php">Login</a></p>
            <a href="index.html" class="auth-back">Back to home</a>
        </section>
    </main>
</body>

</html>
