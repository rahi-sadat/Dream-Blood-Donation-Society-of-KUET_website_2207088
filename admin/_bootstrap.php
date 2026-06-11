<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/admin.php';

ensureAdminSchema($pdo);
requireAdmin($pdo);

$adminName = $_SESSION['user_name'] ?? 'Admin';
$adminInitial = strtoupper(substr(trim($adminName), 0, 1));
$csrfToken = adminCsrfToken();
