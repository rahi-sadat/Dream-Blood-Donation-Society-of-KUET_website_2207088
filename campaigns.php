<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/admin.php';

ensureAdminSchema($pdo);

$campaigns = $pdo->query(
    'SELECT *
     FROM campaigns
     WHERE is_active = 1
     ORDER BY is_featured DESC, display_order ASC, id DESC'
)->fetchAll();

$campaignImagesById = [];

if ($campaigns) {
    $campaignIds = array_map('intval', array_column($campaigns, 'id'));
    $placeholders = implode(',', array_fill(0, count($campaignIds), '?'));
    $statement = $pdo->prepare(
        "SELECT *
         FROM campaign_images
         WHERE is_active = 1 AND campaign_id IN ({$placeholders})
         ORDER BY is_primary DESC, display_order ASC, id ASC"
    );
    $statement->execute($campaignIds);

    foreach ($statement->fetchAll() as $image) {
        $campaignImagesById[(int) $image['campaign_id']][] = $image;
    }
}

$featuredCampaign = null;

foreach ($campaigns as $campaign) {
    if ((int) $campaign['is_featured'] === 1) {
        $featuredCampaign = $campaign;
        break;
    }
}

if (!$featuredCampaign && $campaigns) {
    $featuredCampaign = $campaigns[0];
}

$previousCampaigns = array_values(array_filter($campaigns, function ($campaign) use ($featuredCampaign) {
    return !$featuredCampaign || (int) $campaign['id'] !== (int) $featuredCampaign['id'];
}));

function campaignImagesFor(array $campaign, array $campaignImagesById)
{
    $campaignId = (int) $campaign['id'];

    if (!empty($campaignImagesById[$campaignId])) {
        return $campaignImagesById[$campaignId];
    }

    return [[
        'image_path' => $campaign['image_path'],
        'alt_text' => $campaign['alt_text'],
    ]];
}

function renderCampaignMedia(array $campaign, array $images, $singleClass, $showBadge = false)
{
    if (count($images) > 1): ?>
        <div class="campaign-slider" data-slider>
            <div class="slider-frame">
                <button class="slider-btn slider-prev" type="button" data-slider-prev aria-label="Previous campaign photo">&lt;</button>
                <div class="slider-track" data-slider-track>
                    <?php foreach ($images as $image): ?>
                        <img src="<?php echo admin_e($image['image_path']); ?>" alt="<?php echo admin_e($image['alt_text']); ?>">
                    <?php endforeach; ?>
                </div>
                <button class="slider-btn slider-next" type="button" data-slider-next aria-label="Next campaign photo">&gt;</button>
                <?php if ($showBadge && $campaign['badge_text']): ?>
                    <span class="campaign-badge"><?php echo admin_e($campaign['badge_text']); ?></span>
                <?php endif; ?>
            </div>
            <div class="slider-footer">
                <span data-slider-count>1 / <?php echo count($images); ?></span>
                <div class="slider-dots" data-slider-dots></div>
            </div>
        </div>
    <?php else: ?>
        <div class="<?php echo admin_e($singleClass); ?>">
            <img src="<?php echo admin_e($images[0]['image_path']); ?>" alt="<?php echo admin_e($images[0]['alt_text']); ?>">
            <?php if ($showBadge && $campaign['badge_text']): ?>
                <span class="campaign-badge"><?php echo admin_e($campaign['badge_text']); ?></span>
            <?php endif; ?>
        </div>
    <?php endif;
}
?>
<section class="campaign-hero">
    <div class="campaign-hero-content">
        <span class="eyebrow">DREAM KUET Campaigns</span>
        <h1>Our Campaigns</h1>
        <p>Organized drives, awareness programs, and student-led moments that keep voluntary blood donation active on campus.</p>
    </div>
</section>

<section class="campaign-container">
    <?php if ($featuredCampaign): ?>
        <div class="campaign-section-heading">
            <span class="eyebrow">Highlighted</span>
            <h2>Featured Campaign</h2>
        </div>

        <div class="campaign-feature-card">
            <?php renderCampaignMedia($featuredCampaign, campaignImagesFor($featuredCampaign, $campaignImagesById), 'campaign-image-wrap', true); ?>
            <div class="campaign-info">
                <span class="status-tag <?php echo $featuredCampaign['status_label'] === 'Upcoming' ? 'upcoming-tag' : ''; ?>">
                    <?php echo admin_e($featuredCampaign['status_label']); ?>
                </span>
                <h3><?php echo admin_e($featuredCampaign['title']); ?></h3>
                <p><?php echo admin_e($featuredCampaign['description']); ?></p>
                <div class="campaign-meta">
                    <?php if ($featuredCampaign['event_date']): ?>
                        <span><?php echo admin_e($featuredCampaign['event_date']); ?></span>
                    <?php endif; ?>
                    <?php if ($featuredCampaign['location']): ?>
                        <span><?php echo admin_e($featuredCampaign['location']); ?></span>
                    <?php endif; ?>
                    <?php if ($featuredCampaign['category']): ?>
                        <span><?php echo admin_e($featuredCampaign['category']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="campaign-section-heading">
        <span class="eyebrow">Memories</span>
        <h2>Previous Campaigns and Events</h2>
    </div>

    <?php if (!$previousCampaigns): ?>
        <div class="empty-state">
            <p>No previous campaign has been added yet.</p>
        </div>
    <?php else: ?>
        <div class="campaign-gallery-grid">
            <?php foreach ($previousCampaigns as $campaign): ?>
                <article class="campaign-card">
                    <?php renderCampaignMedia($campaign, campaignImagesFor($campaign, $campaignImagesById), 'campaign-photo-wrap'); ?>
                    <div class="card-content">
                        <span class="status-tag <?php echo $campaign['status_label'] === 'Upcoming' ? 'upcoming-tag' : ''; ?>">
                            <?php echo admin_e($campaign['status_label']); ?>
                        </span>
                        <h3><?php echo admin_e($campaign['title']); ?></h3>
                        <p><?php echo admin_e($campaign['description']); ?></p>
                        <div class="campaign-meta">
                            <?php if ($campaign['event_date']): ?>
                                <span><?php echo admin_e($campaign['event_date']); ?></span>
                            <?php endif; ?>
                            <?php if ($campaign['location']): ?>
                                <span><?php echo admin_e($campaign['location']); ?></span>
                            <?php endif; ?>
                            <?php if ($campaign['category']): ?>
                                <span><?php echo admin_e($campaign['category']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="campaign-summary-action">
        <div>
            <span class="eyebrow">Blood donation summary</span>
            <h2>Monthly Blood Donation Summary</h2>
            <p>Total donated blood bags by blood group from recent months.</p>
        </div>
        <a class="btn-primary link-button" href="blood-summary.php">View Summary</a>
    </div>
</section>
