<?php
// Request page setup: only logged-in users can submit blood requests.
session_start();
require_once __DIR__ . '/config/districts.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$profileInitial = strtoupper(substr(trim($_SESSION['user_name']), 0, 1));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Blood Request - Dream KUET</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Main navigation: includes profile menu for the logged-in requester. -->
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
                <li><a href="add-request.php" class="active-link">Add Blood Request</a></li>
                <li><a href="index.php#campaigns">Campaigns</a></li>
            </ul>

            <div class="nav-actions">
                <button class="profile-nav-link" type="button" data-profile-menu-toggle aria-label="Open profile menu">
                    <span class="profile-icon"><?php echo htmlspecialchars($profileInitial); ?></span>
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </button>
            </div>
        </nav>
    </header>

    <!-- Profile sidebar: account links available while adding a request. -->
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

    <main>
        <!-- Blood request form: collects patient, location, and contact details. -->
        <section class="request-hero">
            <div class="request-hero-content">
                <span class="eyebrow">Emergency support</span>
                <h1>Add Blood Request</h1>
                <p>Submit patient and contact details clearly so DREAM volunteers can understand the need and respond faster.</p>
            </div>
        </section>

        <section class="request-form-section">
            <form class="blood-request-form" action="submit_request.php" method="POST" data-request-form>
                <div class="form-note">
                    <strong>Important:</strong> Please double-check phone number, location, and needed date before submitting.
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="patient_name">Patient Name</label>
                        <input type="text" id="patient_name" name="patient_name" placeholder="Enter patient name" required>
                    </div>

                    <div class="form-group">
                        <label for="blood_group">Blood Group</label>
                        <select id="blood_group" name="blood_group" required>
                            <option value="">Select blood group</option>
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

                    <div class="form-group">
                        <label for="blood_bag">Blood Bag Needed</label>
                        <input type="number" id="blood_bag" name="blood_bag" min="1" max="10" placeholder="Example: 2" required>
                    </div>

                    <div class="form-group">
                        <label for="needed_date">Needed Date</label>
                        <input type="date" id="needed_date" name="needed_date" required>
                    </div>

                    <div class="form-group">
                        <label for="district">District</label>
                        <select id="district" name="district" required>
                            <option value="">Select district</option>
                            <?php foreach ($districts as $districtName): ?>
                                <option value="<?php echo htmlspecialchars($districtName); ?>"><?php echo htmlspecialchars($districtName); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="hospital">Hospital / Clinic</label>
                        <input type="text" id="hospital" name="hospital" placeholder="Example: KUET Medical Center" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="address">Full Address</label>
                        <textarea id="address" name="address" rows="3" placeholder="Ward, building, room, road, area" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="contact_name">Contact Person</label>
                        <input type="text" id="contact_name" name="contact_name" placeholder="Requester name" required>
                    </div>

                    <div class="form-group">
                        <label for="contact_phone">Contact Number</label>
                        <input type="tel" id="contact_phone" name="contact_phone" placeholder="01XXXXXXXXX" pattern="01[0-9]{9}" required>
                    </div>

                    <div class="form-group">
                        <label for="relation">Relation With Patient</label>
                        <input type="text" id="relation" name="relation" placeholder="Example: Brother, Friend" required>
                    </div>

                    <div class="form-group">
                        <label for="urgency">Urgency</label>
                        <select id="urgency" name="urgency" required>
                            <option value="">Select urgency</option>
                            <option value="Emergency">Emergency</option>
                            <option value="Within 24 hours">Within 24 hours</option>
                            <option value="Scheduled">Scheduled</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="details">Additional Details</label>
                        <textarea id="details" name="details" rows="4" placeholder="Doctor advice, patient condition, or other useful information"></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <a class="btn-form-secondary" href="index.php">Cancel</a>
                    <button class="btn-primary" type="submit">Submit Request</button>
                </div>
            </form>
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
                <a href="blood-requests.php">Blood Requests</a>
                <a href="add-request.php">Add Blood Request</a>
                <a href="find-donors.php">Search Donors</a>
                <a href="index.php#about">About Us</a>
                <a href="index.php#contact-rules">Contact &amp; Rules</a>
            </div>
            <div class="footer-column">
                <h4>About Blood</h4>
                <a href="#">What is blood?</a>
                <a href="#">Who can donate?</a>
                <a href="#">Blood Groups</a>
                <a href="#">FAQs</a>
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
