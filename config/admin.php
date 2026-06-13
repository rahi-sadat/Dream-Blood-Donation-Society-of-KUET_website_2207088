<?php

function ensureAdminSchema(PDO $pdo)
{
    static $done = false;

    if ($done) {
        return;
    }

    $databaseName = $pdo->query('SELECT DATABASE()')->fetchColumn();

    $statement = $pdo->prepare(
        "SELECT COLUMN_NAME
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = ?
           AND TABLE_NAME = 'users'
           AND COLUMN_NAME = 'role'"
    );
    $statement->execute([$databaseName]);

    if (!$statement->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user' AFTER password_hash");
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS gallery_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_key VARCHAR(40) NOT NULL,
            title VARCHAR(160) NOT NULL,
            description TEXT NULL,
            image_path VARCHAR(255) NOT NULL,
            alt_text VARCHAR(255) NOT NULL,
            display_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS blood_summaries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            month_label VARCHAR(80) NOT NULL,
            summary_year VARCHAR(10) NOT NULL,
            total_bags VARCHAR(40) NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            alt_text VARCHAR(255) NOT NULL,
            display_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS campaigns (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(180) NOT NULL,
            description TEXT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            alt_text VARCHAR(255) NOT NULL,
            status_label VARCHAR(40) NOT NULL DEFAULT 'Completed',
            event_date VARCHAR(80) NULL,
            location VARCHAR(160) NULL,
            category VARCHAR(120) NULL,
            badge_text VARCHAR(60) NULL,
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            display_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS campaign_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            alt_text VARCHAR(255) NOT NULL,
            display_order INT NOT NULL DEFAULT 0,
            is_primary TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS site_settings (
            setting_key VARCHAR(80) PRIMARY KEY,
            setting_value TEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    );

    seedAdminContent($pdo);
    syncExistingCampaignImages($pdo);
    seedBloodDonorDayCampaign($pdo);
    $done = true;
}

function seedAdminContent(PDO $pdo)
{
    $statement = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1');
    $statement->execute(['admin_content_seeded']);

    if ($statement->fetchColumn() === '1') {
        return;
    }

    $gallerySeeds = [
        'committee' => [
            ['Current Committee Photo 1', 'DREAM current committee photo 1', 'images/Committee1.jpg', 1],
            ['Current Committee Photo 2', 'DREAM current committee photo 2', 'images/Committee2.jpg', 2],
            ['Current Committee Photo 3', 'DREAM current committee photo 3', 'images/Committee3.jpg', 3],
        ],
        'volunteers' => [
            ['Volunteers List Photo 1', 'DREAM volunteers list photo 1', 'images/volunteers1.jpg', 1],
            ['Volunteers List Photo 2', 'DREAM volunteers list photo 2', 'images/volunteers2.jpg', 2],
        ],
    ];

    foreach ($gallerySeeds as $groupKey => $items) {
        $statement = $pdo->prepare('SELECT COUNT(*) FROM gallery_items WHERE group_key = ?');
        $statement->execute([$groupKey]);

        if ((int) $statement->fetchColumn() > 0) {
            continue;
        }

        $insert = $pdo->prepare(
            'INSERT INTO gallery_items (group_key, title, description, image_path, alt_text, display_order)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        foreach ($items as $item) {
            $insert->execute([$groupKey, $item[0], null, $item[2], $item[1], $item[3]]);
        }
    }

    $statement = $pdo->query('SELECT COUNT(*) FROM blood_summaries');
    if ((int) $statement->fetchColumn() === 0) {
        $summaries = [
            ['April 2026', '2026', '61 bags', 'images/Blood_donation_Summary1.jpg', 'April 2026 blood donation summary by blood group', 1],
            ['February 2026', '2026', '24 bags', 'images/Blood_Donation_Summary2.jpg', 'February 2026 blood donation summary by blood group', 2],
            ['January 2026', '2026', '58 bags', 'images/Blood_Donation_Summary3.jpg', 'January 2026 blood donation summary by blood group', 3],
            ['December 2025', '2025', '50 bags', 'images/Blood_Donation_Summary4.jpg', 'December 2025 blood donation summary by blood group', 4],
            ['November 2025', '2025', '44 bags', 'images/Blood_Donation_Summary5.jpg', 'November 2025 blood donation summary by blood group', 5],
            ['October 2025', '2025', '68 bags', 'images/Blood_Donation_Summary6.jpg', 'October 2025 blood donation summary by blood group', 6],
        ];

        $insert = $pdo->prepare(
            'INSERT INTO blood_summaries (month_label, summary_year, total_bags, image_path, alt_text, display_order)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        foreach ($summaries as $summary) {
            $insert->execute($summary);
        }
    }

    $statement = $pdo->query('SELECT COUNT(*) FROM campaigns');
    if ((int) $statement->fetchColumn() === 0) {
        $campaigns = [
            [
                'KUET Mega Blood Drive 2026',
                'A campus-wide blood donation and donor registration program arranged for KUET students, teachers, and staff.',
                'images/campaign3.png',
                'KUET blood donation campaign',
                'Upcoming',
                'June 15, 2026',
                'Student Welfare Center',
                'Blood Drive',
                'Coming Soon',
                1,
                1,
            ],
            [
                'Free Blood Grouping Campaign 2025',
                'Held at SWC, KUET. The campaign helped 500+ students identify their blood group and learn the basics of safe donation.',
                'images/campaign1.jpg',
                'Free blood grouping campaign registration booth',
                'Completed',
                '2025',
                'SWC, KUET',
                'Blood Grouping',
                null,
                0,
                2,
            ],
            [
                'Free Blood Grouping Campaign Activity',
                'A continuation of the 2025 grouping campaign with student volunteers supporting registration and awareness.',
                'images/campaign2.jpg',
                'Free blood grouping campaign activity',
                'Completed',
                '2025',
                'SWC, KUET',
                'Blood Grouping',
                null,
                0,
                3,
            ],
            [
                'Poster Putting Campaign',
                'DREAM volunteers placed awareness posters around campus to share donation guidance and emergency donor information.',
                'images/campaign4.jpg',
                'DREAM KUET volunteers putting up a blood donation awareness poster',
                'Completed',
                null,
                'Campus Outreach',
                'Awareness Poster',
                null,
                0,
                4,
            ],
            [
                'Blood Grouping Campaign',
                'A blood grouping campaign arranged by DREAM to help students know their blood group and join the donor network.',
                'images/Campaign5.jpg',
                'DREAM KUET blood grouping campaign team photo',
                'Completed',
                null,
                'KUET Campus',
                'Blood Grouping',
                null,
                0,
                5,
            ],
            [
                'Farewell of Batch 2K19',
                'A farewell gathering honoring the 2K19 batch and welcoming the next DREAM volunteers into the society.',
                'images/Farewell.jpg',
                'Farewell program for KUET batch 2K19',
                'Completed',
                null,
                'Batch 2K19',
                'Farewell Program',
                null,
                0,
                6,
            ],
            [
                'Treasure Hunt 2025',
                'A student engagement event arranged by DREAM in 2025 with on-spot registration at SWC, KUET.',
                'images/TreasureHunt.jpg',
                'Treasure Hunt 2025 registration poster arranged by DREAM KUET',
                'Completed',
                '30 August 2025',
                'SWC, KUET',
                'Student Engagement',
                null,
                0,
                7,
            ],
        ];

        $insert = $pdo->prepare(
            'INSERT INTO campaigns
                (title, description, image_path, alt_text, status_label, event_date, location, category, badge_text, is_featured, display_order)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        foreach ($campaigns as $campaign) {
            $insert->execute($campaign);
        }
    }

    $statement = $pdo->prepare(
        'INSERT INTO site_settings (setting_key, setting_value)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $statement->execute(['admin_content_seeded', '1']);
}

function seedBloodDonorDayCampaign(PDO $pdo)
{
    $statement = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1');
    $statement->execute(['blood_donor_day_today_status_added']);

    if ($statement->fetchColumn() === '1') {
        return;
    }

    $campaign = [
        'title' => 'Blood Donor Day 2026',
        'description' => 'DREAM KUET observes Blood Donor Day to celebrate voluntary donors and encourage more students to stand beside patients in urgent need.',
        'image_path' => 'images/Blood_Donor_Day.jpg',
        'alt_text' => 'Blood Donor Day campaign poster',
        'status_label' => 'Today',
        'event_date' => 'June 14, 2026',
        'location' => 'KUET Campus',
        'category' => 'Awareness Campaign',
        'badge_text' => 'Today',
        'is_featured' => 1,
        'display_order' => 0,
        'is_active' => 1,
    ];

    $statement = $pdo->prepare('SELECT id FROM campaigns WHERE title = ? LIMIT 1');
    $statement->execute([$campaign['title']]);
    $campaignId = (int) $statement->fetchColumn();

    if ($campaignId > 0) {
        $statement = $pdo->prepare(
            'UPDATE campaigns
             SET description = ?, image_path = ?, alt_text = ?, status_label = ?, event_date = ?,
                 location = ?, category = ?, badge_text = ?, is_featured = ?, display_order = ?, is_active = ?
             WHERE id = ?'
        );
        $statement->execute([
            $campaign['description'],
            $campaign['image_path'],
            $campaign['alt_text'],
            $campaign['status_label'],
            $campaign['event_date'],
            $campaign['location'],
            $campaign['category'],
            $campaign['badge_text'],
            $campaign['is_featured'],
            $campaign['display_order'],
            $campaign['is_active'],
            $campaignId,
        ]);
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO campaigns
                (title, description, image_path, alt_text, status_label, event_date, location, category, badge_text, is_featured, display_order, is_active)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $campaign['title'],
            $campaign['description'],
            $campaign['image_path'],
            $campaign['alt_text'],
            $campaign['status_label'],
            $campaign['event_date'],
            $campaign['location'],
            $campaign['category'],
            $campaign['badge_text'],
            $campaign['is_featured'],
            $campaign['display_order'],
            $campaign['is_active'],
        ]);
        $campaignId = (int) $pdo->lastInsertId();
    }

    syncCampaignPrimaryImage($pdo, $campaignId, $campaign['image_path'], $campaign['alt_text']);

    $statement = $pdo->prepare('UPDATE campaigns SET is_featured = 1 WHERE title = ?');
    $statement->execute(['KUET Mega Blood Drive 2026']);

    $statement = $pdo->prepare(
        'INSERT INTO site_settings (setting_key, setting_value)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $statement->execute(['blood_donor_day_today_status_added', '1']);
}

function syncCampaignPrimaryImage(PDO $pdo, $campaignId, $imagePath, $altText)
{
    $statement = $pdo->prepare('SELECT id FROM campaign_images WHERE campaign_id = ? AND is_primary = 1 LIMIT 1');
    $statement->execute([(int) $campaignId]);
    $primaryImageId = $statement->fetchColumn();

    if ($primaryImageId) {
        $statement = $pdo->prepare(
            'UPDATE campaign_images
             SET image_path = ?, alt_text = ?, display_order = 0, is_active = 1
             WHERE id = ?'
        );
        $statement->execute([$imagePath, $altText, (int) $primaryImageId]);
        return;
    }

    $statement = $pdo->prepare(
        'INSERT INTO campaign_images (campaign_id, image_path, alt_text, display_order, is_primary, is_active)
         VALUES (?, ?, ?, 0, 1, 1)'
    );
    $statement->execute([(int) $campaignId, $imagePath, $altText]);
}

function syncExistingCampaignImages(PDO $pdo)
{
    $campaigns = $pdo->query('SELECT id, title, image_path, alt_text FROM campaigns')->fetchAll();

    foreach ($campaigns as $campaign) {
        $statement = $pdo->prepare('SELECT COUNT(*) FROM campaign_images WHERE campaign_id = ?');
        $statement->execute([(int) $campaign['id']]);

        if ((int) $statement->fetchColumn() === 0) {
            syncCampaignPrimaryImage($pdo, (int) $campaign['id'], $campaign['image_path'], $campaign['alt_text']);
        }
    }

    $statement = $pdo->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1');
    $statement->execute(['campaign_slider_seeded']);

    if ($statement->fetchColumn() === '1') {
        return;
    }

    $statement = $pdo->prepare("SELECT * FROM campaigns WHERE title = ? LIMIT 1");
    $statement->execute(['Free Blood Grouping Campaign 2025']);
    $mainCampaign = $statement->fetch();

    $statement->execute(['Free Blood Grouping Campaign Activity']);
    $extraCampaign = $statement->fetch();

    if ($mainCampaign && $extraCampaign) {
        $statement = $pdo->prepare(
            'SELECT COUNT(*)
             FROM campaign_images
             WHERE campaign_id = ? AND image_path = ?'
        );
        $statement->execute([(int) $mainCampaign['id'], $extraCampaign['image_path']]);

        if ((int) $statement->fetchColumn() === 0) {
            $statement = $pdo->prepare(
                'INSERT INTO campaign_images (campaign_id, image_path, alt_text, display_order, is_primary, is_active)
                 VALUES (?, ?, ?, 2, 0, 1)'
            );
            $statement->execute([
                (int) $mainCampaign['id'],
                $extraCampaign['image_path'],
                $extraCampaign['alt_text'],
            ]);
        }

        $statement = $pdo->prepare('UPDATE campaigns SET is_active = 0 WHERE id = ?');
        $statement->execute([(int) $extraCampaign['id']]);
    }

    $statement = $pdo->prepare(
        'INSERT INTO site_settings (setting_key, setting_value)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $statement->execute(['campaign_slider_seeded', '1']);
}

function currentUserIsAdmin(PDO $pdo)
{
    ensureAdminSchema($pdo);

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $statement = $pdo->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
    $statement->execute([(int) $_SESSION['user_id']]);
    $role = $statement->fetchColumn();

    if (!$role) {
        return false;
    }

    $_SESSION['user_role'] = $role;
    return $role === 'admin';
}

function requireAdmin(PDO $pdo, $loginUrl = '../login.php?next=admin')
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . $loginUrl);
        exit;
    }

    if (!currentUserIsAdmin($pdo)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Admin Only</title><link rel="stylesheet" href="../style.css"></head><body><main class="auth-page"><section class="auth-card"><span class="eyebrow">Restricted</span><h1>Admin access only</h1><p>Your account does not have permission to open the admin dashboard.</p><a class="btn-primary link-button" href="../index.php">Back to Home</a></section></main></body></html>';
        exit;
    }
}

function adminCsrfToken()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['admin_csrf_token'];
}

function adminVerifyCsrf()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    return isset($_POST['csrf_token'], $_SESSION['admin_csrf_token'])
        && hash_equals($_SESSION['admin_csrf_token'], $_POST['csrf_token']);
}

function adminUploadImage($fieldName, $currentPath, array &$errors)
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $currentPath;
    }

    $file = $_FILES[$fieldName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Image upload failed. Please choose the image again.';
        return $currentPath;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        $errors[] = 'Image size must be 5 MB or less.';
        return $currentPath;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowedTypes[$mimeType])) {
        $errors[] = 'Only JPG, PNG, or WebP images are allowed.';
        return $currentPath;
    }

    $targetDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'admin';

    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
        $errors[] = 'Could not create the admin image folder.';
        return $currentPath;
    }

    $fileName = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $allowedTypes[$mimeType];
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $errors[] = 'Could not save the uploaded image.';
        return $currentPath;
    }

    return 'images/admin/' . $fileName;
}

function adminAssetUrl($path)
{
    if ($path === '') {
        return '';
    }

    return '../' . ltrim($path, '/');
}

function admin_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
