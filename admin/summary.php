<?php
require_once __DIR__ . '/_bootstrap.php';

$pageTitle = 'Blood Summary';
$activePage = 'summary';
$errors = [];
$successMessages = [
    'saved' => 'Summary saved successfully.',
    'deleted' => 'Summary deleted successfully.',
    'toggled' => 'Visibility updated successfully.',
];
$success = $successMessages[$_GET['message'] ?? ''] ?? '';
$formItem = [
    'id' => 0,
    'month_label' => '',
    'summary_year' => date('Y'),
    'total_bags' => '',
    'image_path' => '',
    'alt_text' => '',
    'display_order' => 0,
    'is_active' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!adminVerifyCsrf()) {
        $errors[] = 'Security check failed. Please submit the form again.';
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $statement = $pdo->prepare('DELETE FROM blood_summaries WHERE id = ?');
        $statement->execute([$id]);
        header('Location: summary.php?message=deleted');
        exit;
    } elseif ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $statement = $pdo->prepare('UPDATE blood_summaries SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?');
        $statement->execute([$id]);
        header('Location: summary.php?message=toggled');
        exit;
    } elseif ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $monthLabel = trim($_POST['month_label'] ?? '');
        $summaryYear = trim($_POST['summary_year'] ?? '');
        $totalBags = trim($_POST['total_bags'] ?? '');
        $altText = trim($_POST['alt_text'] ?? '');
        $displayOrder = (int) ($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $currentImagePath = trim($_POST['current_image_path'] ?? '');

        $formItem = [
            'id' => $id,
            'month_label' => $monthLabel,
            'summary_year' => $summaryYear,
            'total_bags' => $totalBags,
            'image_path' => $currentImagePath,
            'alt_text' => $altText,
            'display_order' => $displayOrder,
            'is_active' => $isActive,
        ];

        if ($monthLabel === '') {
            $errors[] = 'Month label is required.';
        }

        if ($summaryYear === '') {
            $errors[] = 'Year is required.';
        }

        if ($totalBags === '') {
            $errors[] = 'Total donated amount is required.';
        }

        if ($altText === '') {
            $errors[] = 'Image alt text is required.';
        }

        $imagePath = adminUploadImage('image', $currentImagePath, $errors);

        if ($imagePath === '') {
            $errors[] = 'Please upload an image.';
        }

        if (!$errors) {
            if ($id > 0) {
                $statement = $pdo->prepare(
                    'UPDATE blood_summaries
                     SET month_label = ?, summary_year = ?, total_bags = ?, image_path = ?, alt_text = ?, display_order = ?, is_active = ?
                     WHERE id = ?'
                );
                $statement->execute([$monthLabel, $summaryYear, $totalBags, $imagePath, $altText, $displayOrder, $isActive, $id]);
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO blood_summaries (month_label, summary_year, total_bags, image_path, alt_text, display_order, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $statement->execute([$monthLabel, $summaryYear, $totalBags, $imagePath, $altText, $displayOrder, $isActive]);
            }

            header('Location: summary.php?message=saved');
            exit;
        }
    }
}

$editId = (int) ($_GET['edit'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $editId > 0) {
    $statement = $pdo->prepare('SELECT * FROM blood_summaries WHERE id = ? LIMIT 1');
    $statement->execute([$editId]);
    $editItem = $statement->fetch();

    if ($editItem) {
        $formItem = $editItem;
    }
}

$items = $pdo->query('SELECT * FROM blood_summaries ORDER BY display_order ASC, id DESC')->fetchAll();

require __DIR__ . '/_header.php';
?>

<section class="admin-page-head">
    <div>
        <span class="eyebrow">Reports</span>
        <h1>Manage Blood Donation Summary Images</h1>
        <p>Upload monthly summary images and the total donated blood amount shown on the public summary page.</p>
    </div>
    <a class="btn-form-secondary" href="../blood-summary.php">View Public Page</a>
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
            <h2><?php echo (int) $formItem['id'] > 0 ? 'Edit Summary' : 'Add New Summary'; ?></h2>
            <?php if ((int) $formItem['id'] > 0): ?>
                <a class="btn-form-secondary" href="summary.php">Add New</a>
            <?php endif; ?>
        </div>

        <div class="form-grid compact-grid">
            <div class="form-group">
                <label for="month_label">Month Label</label>
                <input type="text" id="month_label" name="month_label" value="<?php echo admin_e($formItem['month_label']); ?>" placeholder="April 2026" required>
            </div>
            <div class="form-group">
                <label for="summary_year">Year</label>
                <input type="text" id="summary_year" name="summary_year" value="<?php echo admin_e($formItem['summary_year']); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="total_bags">Total Donated</label>
            <input type="text" id="total_bags" name="total_bags" value="<?php echo admin_e($formItem['total_bags']); ?>" placeholder="61 bags" required>
        </div>

        <div class="form-group">
            <label for="alt_text">Image Alt Text</label>
            <input type="text" id="alt_text" name="alt_text" value="<?php echo admin_e($formItem['alt_text']); ?>" required>
        </div>

        <div class="form-grid compact-grid">
            <div class="form-group">
                <label for="display_order">Display Order</label>
                <input type="number" id="display_order" name="display_order" value="<?php echo (int) $formItem['display_order']; ?>">
            </div>
            <label class="check-row admin-check-row">
                <input type="checkbox" name="is_active" value="1" <?php echo (int) $formItem['is_active'] === 1 ? 'checked' : ''; ?>>
                <span>Show on public page</span>
            </label>
        </div>

        <div class="form-group">
            <label for="image">Summary Image</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
        </div>

        <?php if ($formItem['image_path']): ?>
            <a class="admin-current-image" href="<?php echo admin_e(adminAssetUrl($formItem['image_path'])); ?>" target="_blank" rel="noopener">
                <img src="<?php echo admin_e(adminAssetUrl($formItem['image_path'])); ?>" alt="<?php echo admin_e($formItem['alt_text']); ?>">
            </a>
        <?php endif; ?>

        <button class="btn-primary admin-submit" type="submit">Save Summary</button>
    </form>

    <div class="admin-list-panel">
        <div class="admin-panel-head">
            <h2>Current Summaries</h2>
            <span><?php echo count($items); ?> total</span>
        </div>

        <div class="admin-item-list">
            <?php foreach ($items as $item): ?>
                <article class="admin-item-row">
                    <img src="<?php echo admin_e(adminAssetUrl($item['image_path'])); ?>" alt="<?php echo admin_e($item['alt_text']); ?>">
                    <div>
                        <span class="status-tag"><?php echo (int) $item['is_active'] === 1 ? 'Visible' : 'Hidden'; ?></span>
                        <h3><?php echo admin_e($item['month_label']); ?></h3>
                        <p><?php echo admin_e($item['total_bags']); ?> | Order: <?php echo (int) $item['display_order']; ?></p>
                    </div>
                    <div class="admin-row-actions">
                        <a class="btn-form-secondary" href="summary.php?edit=<?php echo (int) $item['id']; ?>">Edit</a>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo admin_e($csrfToken); ?>">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                            <button class="btn-form-secondary" type="submit"><?php echo (int) $item['is_active'] === 1 ? 'Hide' : 'Show'; ?></button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Delete this summary from the admin list?');">
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
