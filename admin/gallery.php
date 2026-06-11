<?php
require_once __DIR__ . '/_bootstrap.php';

$allowedTypes = [
    'committee' => [
        'label' => 'Committee',
        'heading' => 'Manage Committee List Photos',
        'description' => 'Add and update the public committee list photos shown on the committee page.',
    ],
    'volunteers' => [
        'label' => 'Volunteers',
        'heading' => 'Manage Volunteer List Photos',
        'description' => 'Add and update the public volunteer list photos shown on the volunteers page.',
    ],
];

$type = $_GET['type'] ?? 'committee';

if (!isset($allowedTypes[$type])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = $allowedTypes[$type]['label'];
$activePage = $type;
$errors = [];
$successMessages = [
    'saved' => 'Item saved successfully.',
    'deleted' => 'Item deleted successfully.',
    'toggled' => 'Visibility updated successfully.',
];
$success = $successMessages[$_GET['message'] ?? ''] ?? '';
$formItem = [
    'id' => 0,
    'title' => '',
    'description' => '',
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
        $statement = $pdo->prepare('DELETE FROM gallery_items WHERE id = ? AND group_key = ?');
        $statement->execute([$id, $type]);
        header('Location: gallery.php?type=' . urlencode($type) . '&message=deleted');
        exit;
    } elseif ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $statement = $pdo->prepare('UPDATE gallery_items SET is_active = IF(is_active = 1, 0, 1) WHERE id = ? AND group_key = ?');
        $statement->execute([$id, $type]);
        header('Location: gallery.php?type=' . urlencode($type) . '&message=toggled');
        exit;
    } elseif ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $altText = trim($_POST['alt_text'] ?? '');
        $displayOrder = (int) ($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $currentImagePath = trim($_POST['current_image_path'] ?? '');

        $formItem = [
            'id' => $id,
            'title' => $title,
            'description' => $description,
            'image_path' => $currentImagePath,
            'alt_text' => $altText,
            'display_order' => $displayOrder,
            'is_active' => $isActive,
        ];

        if ($title === '') {
            $errors[] = 'Title is required.';
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
                    'UPDATE gallery_items
                     SET title = ?, description = ?, image_path = ?, alt_text = ?, display_order = ?, is_active = ?
                     WHERE id = ? AND group_key = ?'
                );
                $statement->execute([$title, $description !== '' ? $description : null, $imagePath, $altText, $displayOrder, $isActive, $id, $type]);
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO gallery_items (group_key, title, description, image_path, alt_text, display_order, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $statement->execute([$type, $title, $description !== '' ? $description : null, $imagePath, $altText, $displayOrder, $isActive]);
            }

            header('Location: gallery.php?type=' . urlencode($type) . '&message=saved');
            exit;
        }
    }
}

$editId = (int) ($_GET['edit'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $editId > 0) {
    $statement = $pdo->prepare('SELECT * FROM gallery_items WHERE id = ? AND group_key = ? LIMIT 1');
    $statement->execute([$editId, $type]);
    $editItem = $statement->fetch();

    if ($editItem) {
        $formItem = $editItem;
    }
}

$statement = $pdo->prepare('SELECT * FROM gallery_items WHERE group_key = ? ORDER BY display_order ASC, id ASC');
$statement->execute([$type]);
$items = $statement->fetchAll();

require __DIR__ . '/_header.php';
?>

<section class="admin-page-head">
    <div>
        <span class="eyebrow">People</span>
        <h1><?php echo admin_e($allowedTypes[$type]['heading']); ?></h1>
        <p><?php echo admin_e($allowedTypes[$type]['description']); ?></p>
    </div>
    <a class="btn-form-secondary" href="../people.php?type=<?php echo admin_e($type); ?>">View Public Page</a>
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
            <h2><?php echo (int) $formItem['id'] > 0 ? 'Edit Photo' : 'Add New Photo'; ?></h2>
            <?php if ((int) $formItem['id'] > 0): ?>
                <a class="btn-form-secondary" href="gallery.php?type=<?php echo admin_e($type); ?>">Add New</a>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?php echo admin_e($formItem['title']); ?>" required>
        </div>

        <div class="form-group">
            <label for="description">Short Description</label>
            <textarea id="description" name="description" rows="3"><?php echo admin_e($formItem['description']); ?></textarea>
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
            <label for="image">Image</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
        </div>

        <?php if ($formItem['image_path']): ?>
            <a class="admin-current-image" href="<?php echo admin_e(adminAssetUrl($formItem['image_path'])); ?>" target="_blank" rel="noopener">
                <img src="<?php echo admin_e(adminAssetUrl($formItem['image_path'])); ?>" alt="<?php echo admin_e($formItem['alt_text']); ?>">
            </a>
        <?php endif; ?>

        <button class="btn-primary admin-submit" type="submit">Save Photo</button>
    </form>

    <div class="admin-list-panel">
        <div class="admin-panel-head">
            <h2>Current Items</h2>
            <span><?php echo count($items); ?> total</span>
        </div>

        <?php if (!$items): ?>
            <div class="empty-state">
                <p>No items have been added yet.</p>
            </div>
        <?php endif; ?>

        <div class="admin-item-list">
            <?php foreach ($items as $item): ?>
                <article class="admin-item-row">
                    <img src="<?php echo admin_e(adminAssetUrl($item['image_path'])); ?>" alt="<?php echo admin_e($item['alt_text']); ?>">
                    <div>
                        <span class="status-tag"><?php echo (int) $item['is_active'] === 1 ? 'Visible' : 'Hidden'; ?></span>
                        <h3><?php echo admin_e($item['title']); ?></h3>
                        <p>Order: <?php echo (int) $item['display_order']; ?></p>
                    </div>
                    <div class="admin-row-actions">
                        <a class="btn-form-secondary" href="gallery.php?type=<?php echo admin_e($type); ?>&edit=<?php echo (int) $item['id']; ?>">Edit</a>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo admin_e($csrfToken); ?>">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                            <button class="btn-form-secondary" type="submit"><?php echo (int) $item['is_active'] === 1 ? 'Hide' : 'Show'; ?></button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Delete this item from the admin list?');">
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
