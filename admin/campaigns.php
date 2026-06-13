<?php
require_once __DIR__ . '/_bootstrap.php';

$pageTitle = 'Campaigns';
$activePage = 'campaigns';
$errors = [];
$successMessages = [
    'saved' => 'Campaign saved successfully.',
    'deleted' => 'Campaign deleted successfully.',
    'toggled' => 'Visibility updated successfully.',
    'image_added' => 'Slider photo added successfully.',
    'image_deleted' => 'Slider photo deleted successfully.',
    'image_toggled' => 'Slider photo visibility updated successfully.',
];
$success = $successMessages[$_GET['message'] ?? ''] ?? '';
$campaignStatusOptions = ['Today', 'Upcoming', 'Completed', 'Draft'];
$formItem = [
    'id' => 0,
    'title' => '',
    'description' => '',
    'image_path' => '',
    'alt_text' => '',
    'status_label' => 'Completed',
    'event_date' => '',
    'location' => '',
    'category' => '',
    'badge_text' => '',
    'is_featured' => 0,
    'display_order' => 0,
    'is_active' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!adminVerifyCsrf()) {
        $errors[] = 'Security check failed. Please submit the form again.';
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $statement = $pdo->prepare('DELETE FROM campaigns WHERE id = ?');
        $statement->execute([$id]);
        header('Location: campaigns.php?message=deleted');
        exit;
    } elseif ($action === 'add_image') {
        $campaignId = (int) ($_POST['campaign_id'] ?? 0);
        $altText = trim($_POST['slider_alt_text'] ?? '');
        $displayOrder = (int) ($_POST['slider_display_order'] ?? 0);

        if ($campaignId <= 0) {
            $errors[] = 'Please save the campaign before adding slider photos.';
        }

        if ($altText === '') {
            $errors[] = 'Slider image alt text is required.';
        }

        $imagePath = adminUploadImage('slider_image', '', $errors);

        if ($imagePath === '') {
            $errors[] = 'Please upload a slider image.';
        }

        if (!$errors) {
            $statement = $pdo->prepare(
                'INSERT INTO campaign_images (campaign_id, image_path, alt_text, display_order, is_primary, is_active)
                 VALUES (?, ?, ?, ?, 0, 1)'
            );
            $statement->execute([$campaignId, $imagePath, $altText, $displayOrder]);
            header('Location: campaigns.php?edit=' . $campaignId . '&message=image_added');
            exit;
        }
    } elseif ($action === 'delete_image') {
        $campaignId = (int) ($_POST['campaign_id'] ?? 0);
        $imageId = (int) ($_POST['image_id'] ?? 0);
        $statement = $pdo->prepare('DELETE FROM campaign_images WHERE id = ? AND campaign_id = ? AND is_primary = 0');
        $statement->execute([$imageId, $campaignId]);
        header('Location: campaigns.php?edit=' . $campaignId . '&message=image_deleted');
        exit;
    } elseif ($action === 'toggle_image') {
        $campaignId = (int) ($_POST['campaign_id'] ?? 0);
        $imageId = (int) ($_POST['image_id'] ?? 0);
        $statement = $pdo->prepare('UPDATE campaign_images SET is_active = IF(is_active = 1, 0, 1) WHERE id = ? AND campaign_id = ? AND is_primary = 0');
        $statement->execute([$imageId, $campaignId]);
        header('Location: campaigns.php?edit=' . $campaignId . '&message=image_toggled');
        exit;
    } elseif ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $statement = $pdo->prepare('UPDATE campaigns SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?');
        $statement->execute([$id]);
        header('Location: campaigns.php?message=toggled');
        exit;
    } elseif ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $altText = trim($_POST['alt_text'] ?? '');
        $statusLabel = trim($_POST['status_label'] ?? '');
        $eventDate = trim($_POST['event_date'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $badgeText = trim($_POST['badge_text'] ?? '');
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $displayOrder = (int) ($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $currentImagePath = trim($_POST['current_image_path'] ?? '');

        $formItem = [
            'id' => $id,
            'title' => $title,
            'description' => $description,
            'image_path' => $currentImagePath,
            'alt_text' => $altText,
            'status_label' => $statusLabel,
            'event_date' => $eventDate,
            'location' => $location,
            'category' => $category,
            'badge_text' => $badgeText,
            'is_featured' => $isFeatured,
            'display_order' => $displayOrder,
            'is_active' => $isActive,
        ];

        if ($title === '') {
            $errors[] = 'Campaign title is required.';
        }

        if ($description === '') {
            $errors[] = 'Campaign description is required.';
        }

        if ($altText === '') {
            $errors[] = 'Image alt text is required.';
        }

        if ($statusLabel === '') {
            $errors[] = 'Status label is required.';
        } elseif (!in_array($statusLabel, $campaignStatusOptions, true)) {
            $errors[] = 'Please select a valid campaign status.';
        }

        $imagePath = adminUploadImage('image', $currentImagePath, $errors);

        if ($imagePath === '') {
            $errors[] = 'Please upload a campaign image.';
        }

        if (!$errors) {
            $savedCampaignId = $id;

            if ($id > 0) {
                $statement = $pdo->prepare(
                    'UPDATE campaigns
                     SET title = ?, description = ?, image_path = ?, alt_text = ?, status_label = ?, event_date = ?,
                         location = ?, category = ?, badge_text = ?, is_featured = ?, display_order = ?, is_active = ?
                     WHERE id = ?'
                );
                $statement->execute([
                    $title,
                    $description,
                    $imagePath,
                    $altText,
                    $statusLabel,
                    $eventDate !== '' ? $eventDate : null,
                    $location !== '' ? $location : null,
                    $category !== '' ? $category : null,
                    $badgeText !== '' ? $badgeText : null,
                    $isFeatured,
                    $displayOrder,
                    $isActive,
                    $id,
                ]);
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO campaigns
                        (title, description, image_path, alt_text, status_label, event_date, location, category, badge_text, is_featured, display_order, is_active)
                     VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $statement->execute([
                    $title,
                    $description,
                    $imagePath,
                    $altText,
                    $statusLabel,
                    $eventDate !== '' ? $eventDate : null,
                    $location !== '' ? $location : null,
                    $category !== '' ? $category : null,
                    $badgeText !== '' ? $badgeText : null,
                    $isFeatured,
                    $displayOrder,
                    $isActive,
                ]);
                $savedCampaignId = (int) $pdo->lastInsertId();
            }

            syncCampaignPrimaryImage($pdo, $savedCampaignId, $imagePath, $altText);
            header('Location: campaigns.php?edit=' . $savedCampaignId . '&message=saved');
            exit;
        }
    }
}

$editId = (int) ($_GET['edit'] ?? ($_POST['campaign_id'] ?? 0));

if ($editId > 0 && !($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save')) {
    $statement = $pdo->prepare('SELECT * FROM campaigns WHERE id = ? LIMIT 1');
    $statement->execute([$editId]);
    $editItem = $statement->fetch();

    if ($editItem) {
        $formItem = $editItem;
    }
}

$campaignImages = [];

if ((int) $formItem['id'] > 0) {
    $statement = $pdo->prepare(
        'SELECT *
         FROM campaign_images
         WHERE campaign_id = ?
         ORDER BY is_primary DESC, display_order ASC, id ASC'
    );
    $statement->execute([(int) $formItem['id']]);
    $campaignImages = $statement->fetchAll();
}

$items = $pdo->query('SELECT * FROM campaigns ORDER BY is_featured DESC, display_order ASC, id DESC')->fetchAll();

require __DIR__ . '/_header.php';
?>

<section class="admin-page-head">
    <div>
        <span class="eyebrow">Campaigns</span>
        <h1>Manage Campaigns</h1>
        <p>Create upcoming and completed campaign posts for the public campaign section.</p>
    </div>
    <a class="btn-form-secondary" href="../index.php#campaigns">View Public Section</a>
</section>

<?php if ($success): ?>
    <div class="admin-message success"><?php echo admin_e($success); ?></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="admin-message error">
        <?php foreach ($errors as $error): ?>
            <p><?php echo admin_e($error); ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<section class="admin-editor-layout">
    <form class="admin-form-panel" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo admin_e($csrfToken); ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?php echo (int) $formItem['id']; ?>">
        <input type="hidden" name="current_image_path" value="<?php echo admin_e($formItem['image_path']); ?>">

        <div class="admin-panel-head">
            <h2><?php echo (int) $formItem['id'] > 0 ? 'Edit Campaign' : 'Add New Campaign'; ?></h2>
            <?php if ((int) $formItem['id'] > 0): ?>
                <a class="btn-form-secondary" href="campaigns.php">Add New</a>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="title">Campaign Title</label>
            <input type="text" id="title" name="title" value="<?php echo admin_e($formItem['title']); ?>" required>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" required><?php echo admin_e($formItem['description']); ?></textarea>
        </div>

        <div class="form-grid compact-grid">
            <div class="form-group">
                <label for="status_label">Status</label>
                <select id="status_label" name="status_label" required>
                    <?php foreach ($campaignStatusOptions as $statusOption): ?>
                        <option value="<?php echo admin_e($statusOption); ?>" <?php echo $formItem['status_label'] === $statusOption ? 'selected' : ''; ?>>
                            <?php echo admin_e($statusOption); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="event_date">Date</label>
                <input type="text" id="event_date" name="event_date" value="<?php echo admin_e($formItem['event_date']); ?>" placeholder="June 15, 2026">
            </div>
        </div>

        <div class="form-grid compact-grid">
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" value="<?php echo admin_e($formItem['location']); ?>">
            </div>
            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" value="<?php echo admin_e($formItem['category']); ?>">
            </div>
        </div>

        <div class="form-grid compact-grid">
            <div class="form-group">
                <label for="badge_text">Image Badge</label>
                <input type="text" id="badge_text" name="badge_text" value="<?php echo admin_e($formItem['badge_text']); ?>" placeholder="Coming Soon">
            </div>
            <div class="form-group">
                <label for="display_order">Display Order</label>
                <input type="number" id="display_order" name="display_order" value="<?php echo (int) $formItem['display_order']; ?>">
            </div>
        </div>

        <div class="auth-check-grid">
            <label class="check-row">
                <input type="checkbox" name="is_featured" value="1" <?php echo (int) $formItem['is_featured'] === 1 ? 'checked' : ''; ?>>
                <span>Use as featured campaign</span>
            </label>
            <label class="check-row">
                <input type="checkbox" name="is_active" value="1" <?php echo (int) $formItem['is_active'] === 1 ? 'checked' : ''; ?>>
                <span>Show on public page</span>
            </label>
        </div>

        <div class="form-group">
            <label for="alt_text">Image Alt Text</label>
            <input type="text" id="alt_text" name="alt_text" value="<?php echo admin_e($formItem['alt_text']); ?>" required>
        </div>

        <div class="form-group">
            <label for="image">Campaign Image</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
        </div>

        <?php if ($formItem['image_path']): ?>
            <a class="admin-current-image" href="<?php echo admin_e(adminAssetUrl($formItem['image_path'])); ?>" target="_blank" rel="noopener">
                <img src="<?php echo admin_e(adminAssetUrl($formItem['image_path'])); ?>" alt="<?php echo admin_e($formItem['alt_text']); ?>">
            </a>
        <?php endif; ?>

        <button class="btn-primary admin-submit" type="submit">Save Campaign</button>
    </form>

    <div class="admin-list-panel">
        <?php if ((int) $formItem['id'] > 0): ?>
            <div class="admin-slider-manager">
                <div class="admin-panel-head">
                    <h2>Slider Photos</h2>
                    <span><?php echo count($campaignImages); ?> photo<?php echo count($campaignImages) === 1 ? '' : 's'; ?></span>
                </div>

                <form class="admin-inline-upload" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo admin_e($csrfToken); ?>">
                    <input type="hidden" name="action" value="add_image">
                    <input type="hidden" name="campaign_id" value="<?php echo (int) $formItem['id']; ?>">

                    <div class="form-group">
                        <label for="slider_image">Add Slider Photo</label>
                        <input type="file" id="slider_image" name="slider_image" accept="image/jpeg,image/png,image/webp" required>
                    </div>

                    <div class="form-grid compact-grid">
                        <div class="form-group">
                            <label for="slider_alt_text">Alt Text</label>
                            <input type="text" id="slider_alt_text" name="slider_alt_text" required>
                        </div>
                        <div class="form-group">
                            <label for="slider_display_order">Order</label>
                            <input type="number" id="slider_display_order" name="slider_display_order" value="<?php echo count($campaignImages) + 1; ?>">
                        </div>
                    </div>

                    <button class="btn-primary admin-submit" type="submit">Add Slider Photo</button>
                </form>

                <div class="admin-slider-image-list">
                    <?php foreach ($campaignImages as $image): ?>
                        <article class="admin-item-row admin-image-row">
                            <img src="<?php echo admin_e(adminAssetUrl($image['image_path'])); ?>" alt="<?php echo admin_e($image['alt_text']); ?>">
                            <div>
                                <span class="status-tag">
                                    <?php echo (int) $image['is_primary'] === 1 ? 'Main Image' : ((int) $image['is_active'] === 1 ? 'Visible' : 'Hidden'); ?>
                                </span>
                                <h3><?php echo admin_e($image['alt_text']); ?></h3>
                                <p>Order: <?php echo (int) $image['display_order']; ?></p>
                            </div>
                            <div class="admin-row-actions">
                                <?php if ((int) $image['is_primary'] === 1): ?>
                                    <span class="admin-muted-note">Edit main image from the campaign form.</span>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo admin_e($csrfToken); ?>">
                                        <input type="hidden" name="action" value="toggle_image">
                                        <input type="hidden" name="campaign_id" value="<?php echo (int) $formItem['id']; ?>">
                                        <input type="hidden" name="image_id" value="<?php echo (int) $image['id']; ?>">
                                        <button class="btn-form-secondary" type="submit"><?php echo (int) $image['is_active'] === 1 ? 'Hide' : 'Show'; ?></button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('Delete this slider photo?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo admin_e($csrfToken); ?>">
                                        <input type="hidden" name="action" value="delete_image">
                                        <input type="hidden" name="campaign_id" value="<?php echo (int) $formItem['id']; ?>">
                                        <input type="hidden" name="image_id" value="<?php echo (int) $image['id']; ?>">
                                        <button class="admin-danger-button" type="submit">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="admin-panel-head">
            <h2>Current Campaigns</h2>
            <span><?php echo count($items); ?> total</span>
        </div>

        <div class="admin-item-list">
            <?php foreach ($items as $item): ?>
                <article class="admin-item-row">
                    <img src="<?php echo admin_e(adminAssetUrl($item['image_path'])); ?>" alt="<?php echo admin_e($item['alt_text']); ?>">
                    <div>
                        <span class="status-tag"><?php echo (int) $item['is_active'] === 1 ? admin_e($item['status_label']) : 'Hidden'; ?></span>
                        <h3><?php echo admin_e($item['title']); ?></h3>
                        <p>
                            <?php echo (int) $item['is_featured'] === 1 ? 'Featured | ' : ''; ?>
                            Order: <?php echo (int) $item['display_order']; ?>
                        </p>
                    </div>
                    <div class="admin-row-actions">
                        <a class="btn-form-secondary" href="campaigns.php?edit=<?php echo (int) $item['id']; ?>">Edit</a>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo admin_e($csrfToken); ?>">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                            <button class="btn-form-secondary" type="submit"><?php echo (int) $item['is_active'] === 1 ? 'Hide' : 'Show'; ?></button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Delete this campaign from the admin list?');">
                            <input type="hidden" name="csrf_token" value="<?php echo admin_e($csrfToken); ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                            <button class="admin-danger-button" type="submit">Delete</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
