<?php
require_once __DIR__ . '/_bootstrap.php';

$pageTitle = 'Dashboard';
$activePage = 'dashboard';

$committeeCount = (int) $pdo->query("SELECT COUNT(*) FROM gallery_items WHERE group_key = 'committee'")->fetchColumn();
$volunteerCount = (int) $pdo->query("SELECT COUNT(*) FROM gallery_items WHERE group_key = 'volunteers'")->fetchColumn();
$campaignCount = (int) $pdo->query('SELECT COUNT(*) FROM campaigns')->fetchColumn();
$summaryCount = (int) $pdo->query('SELECT COUNT(*) FROM blood_summaries')->fetchColumn();
$activeCampaignCount = (int) $pdo->query('SELECT COUNT(*) FROM campaigns WHERE is_active = 1')->fetchColumn();
$activeSummaryCount = (int) $pdo->query('SELECT COUNT(*) FROM blood_summaries WHERE is_active = 1')->fetchColumn();

require __DIR__ . '/_header.php';
?>

<section class="admin-page-head">
    <div>
        <span class="eyebrow">Admin panel</span>
        <h1>Dashboard</h1>
        <p>Update DREAM committee photos, volunteer photos, campaign posts, and monthly blood donation summaries.</p>
    </div>
</section>

<section class="admin-stat-grid" aria-label="Content overview">
    <article class="admin-stat-card">
        <span>Committee Photos</span>
        <strong><?php echo $committeeCount; ?></strong>
    </article>
    <article class="admin-stat-card">
        <span>Volunteer Photos</span>
        <strong><?php echo $volunteerCount; ?></strong>
    </article>
    <article class="admin-stat-card">
        <span>Campaigns</span>
        <strong><?php echo $activeCampaignCount; ?> / <?php echo $campaignCount; ?></strong>
    </article>
    <article class="admin-stat-card">
        <span>Summary Images</span>
        <strong><?php echo $activeSummaryCount; ?> / <?php echo $summaryCount; ?></strong>
    </article>
</section>

<section class="admin-action-grid" aria-label="Admin actions">
    <a class="admin-action-card" href="gallery.php?type=committee">
        <span class="status-tag">People</span>
        <h2>Manage Committee</h2>
        <p>Add, edit, hide, or delete current committee list photos.</p>
    </a>
    <a class="admin-action-card" href="gallery.php?type=volunteers">
        <span class="status-tag">People</span>
        <h2>Manage Volunteers</h2>
        <p>Keep the public volunteer list photos updated.</p>
    </a>
    <a class="admin-action-card" href="campaigns.php">
        <span class="status-tag upcoming-tag">Campaigns</span>
        <h2>Manage Campaigns</h2>
        <p>Create upcoming or completed campaign posts with images.</p>
    </a>
    <a class="admin-action-card" href="summary.php">
        <span class="status-tag">Reports</span>
        <h2>Manage Summaries</h2>
        <p>Upload monthly donation summary images and totals.</p>
    </a>
</section>

<?php require __DIR__ . '/_footer.php'; ?>
