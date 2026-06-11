<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo admin_e($pageTitle ?? 'Admin Dashboard'); ?> - DREAM KUET</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>

<body class="admin-body">
    <header class="admin-topbar">
        <nav class="admin-nav">
            <a class="admin-brand" href="dashboard.php">
                <img src="../images/logo.png" alt="Dream logo" width="44">
                <span>DREAM Admin</span>
            </a>

            <div class="admin-nav-links">
                <a class="<?php echo ($activePage ?? '') === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">Dashboard</a>
                <a class="<?php echo ($activePage ?? '') === 'committee' ? 'active' : ''; ?>" href="gallery.php?type=committee">Committee</a>
                <a class="<?php echo ($activePage ?? '') === 'volunteers' ? 'active' : ''; ?>" href="gallery.php?type=volunteers">Volunteers</a>
                <a class="<?php echo ($activePage ?? '') === 'campaigns' ? 'active' : ''; ?>" href="campaigns.php">Campaigns</a>
                <a class="<?php echo ($activePage ?? '') === 'summary' ? 'active' : ''; ?>" href="summary.php">Summary</a>
            </div>

            <div class="admin-account">
                <span class="profile-icon"><?php echo admin_e($adminInitial); ?></span>
                <span><?php echo admin_e($adminName); ?></span>
                <a class="btn-form-secondary" href="../index.php">View Site</a>
                <a class="btn-primary link-button" href="../logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <main class="admin-main">
