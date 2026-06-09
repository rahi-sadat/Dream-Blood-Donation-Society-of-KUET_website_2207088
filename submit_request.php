<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/districts.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$requiredFields = [
    'patient_name',
    'blood_group',
    'blood_bag',
    'needed_date',
    'district',
    'hospital',
    'address',
    'contact_name',
    'contact_phone',
    'relation',
    'urgency'
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Only POST requests are allowed.');
}

foreach ($requiredFields as $field) {
    if (empty(trim($_POST[$field] ?? ''))) {
        exit('Please fill all required fields.');
    }
}

$bloodGroups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
$bloodGroup = trim($_POST['blood_group']);

if (!in_array($bloodGroup, $bloodGroups, true)) {
    exit('Invalid blood group selected.');
}

$contactPhone = trim($_POST['contact_phone']);
if (!preg_match('/^01[0-9]{9}$/', $contactPhone)) {
    exit('Please enter a valid Bangladeshi mobile number.');
}

$bloodBag = (int) $_POST['blood_bag'];
if ($bloodBag < 1 || $bloodBag > 10) {
    exit('Blood bag quantity must be between 1 and 10.');
}

$neededDate = trim($_POST['needed_date']);
if ($neededDate < date('Y-m-d')) {
    exit('Please select today or a future date.');
}

$district = trim($_POST['district']);
if (!in_array($district, $districts, true)) {
    exit('Please select a valid district.');
}

$statement = $pdo->prepare(
    'INSERT INTO blood_requests
        (user_id, patient_name, blood_group, blood_bag, needed_date, district, hospital, address, contact_name, contact_phone, relation, urgency, details)
     VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);

$statement->execute([
    $_SESSION['user_id'],
    trim($_POST['patient_name']),
    $bloodGroup,
    $bloodBag,
    $neededDate,
    $district,
    trim($_POST['hospital']),
    trim($_POST['address']),
    trim($_POST['contact_name']),
    $contactPhone,
    trim($_POST['relation']),
    trim($_POST['urgency']),
    trim($_POST['details'] ?? '')
]);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Submitted - Dream KUET</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body>
    <main class="success-page">
        <section class="success-card">
            <span class="status-tag">Submitted</span>
            <h1>Blood request received</h1>
            <p>Thank you. DREAM volunteers can now review this request and contact the given number.</p>
            <div class="success-actions">
                <a class="btn-primary link-button" href="index.php">Back to Home</a>
                <a class="btn-form-secondary" href="add-request.php">Add Another Request</a>
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
</body>

</html>
