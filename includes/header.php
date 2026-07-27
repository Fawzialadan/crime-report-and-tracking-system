<?php

declare(strict_types=1);

$flash = get_flash();
$authUser = current_user();
$authPage = $authPage ?? false;
$currentScript = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

function nav_is_active(string $scriptName, string $currentScript): string
{
    return $scriptName === $currentScript ? 'is-active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Crime Report and Tracking System') ?></title>
    <link rel="stylesheet" href="assets/styles.css">
</head>

<body class="<?= $authPage ? 'auth-body' : 'app-body' ?>">
    <?php if ($authPage): ?>
        <div class="auth-screen">
            <div class="auth-background"></div>
            <div class="auth-shell">
                <?php if ($flash): ?>
                    <div class="flash flash-<?= e($flash['type']) ?>">
                        <?= e($flash['message']) ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="app-shell">
                    <aside class="sidebar">
                        <div class="sidebar-brand">
                            <div class="brand-mark">CS</div>
                            <div>
                                <strong>Crime Management System</strong>
                                <!-- <span>Crime Tracking Suite</span> -->
                            </div>
                        </div>

                        <nav class="sidebar-nav">
                            <!-- <a class="<?= nav_is_active('index.php', $currentScript) ?>" href="index.php">Dashboard</a> -->
                            <?php if ($authUser && $authUser['role'] === 'admin'): ?>
                                <a class="<?= nav_is_active('admin_dashboard.php', $currentScript) ?>" href="admin_dashboard.php">Admin Dashboard</a>
                                <a class="<?= nav_is_active('admin_reports.php', $currentScript) ?>" href="admin_reports.php">Admin Review</a>
                                <a class="<?= nav_is_active('manage_users.php', $currentScript) ?>" href="manage_users.php">Manage Users</a>
                                <a class="<?= in_array($currentScript, ['criminals.php', 'criminal_view.php'], true) ? 'is-active' : '' ?>" href="criminals.php">Criminal Registry</a>
                            <?php endif; ?>
                            <?php if ($authUser && $authUser['role'] === 'officer'): ?>
                                <a class="<?= nav_is_active('officer_portal.php', $currentScript) ?>" href="officer_portal.php">Officer Dashboard</a>
                                <a class="<?= nav_is_active('officer_files.php', $currentScript) ?>" href="officer_files.php">Private Files</a>
                                <a class="<?= in_array($currentScript, ['criminals.php', 'criminal_view.php'], true) ? 'is-active' : '' ?>" href="criminals.php">Criminal Registry</a>
                            <?php endif; ?>
                            <?php if ($authUser && $authUser['role'] === 'citizen'): ?>
                                <a class="<?= nav_is_active('citizen_dashboard.php', $currentScript) ?>" href="citizen_dashboard.php">Citizen Dashboard</a>
                                <a class="<?= nav_is_active('my_cases.php', $currentScript) ?>" href="my_cases.php">My Cases</a>
                            <?php endif; ?>
                            <a class="<?= nav_is_active('submit_report.php', $currentScript) ?>" href="submit_report.php">Submit Report</a>
                            <a class="<?= nav_is_active('profile.php', $currentScript) ?>" href="profile.php">Profile</a>
                        </nav>

                        <div class="sidebar-footer">
                            <div class="sidebar-user">
                                <span class="role-badge role-<?= e($authUser['role']) ?>"><?= e(ucfirst($authUser['role'])) ?></span>
                                <strong><?= e($authUser['full_name']) ?></strong>
                                <small><?= e($authUser['email']) ?></small>
                            </div>
                            <a class="sidebar-logout" href="logout.php">Logout</a>
                        </div>
                    </aside>

                    <div class="workspace">
                        <header class="workspace-topbar">
                            <div class="workspace-title">
                                <p class="eyebrow">Command Workspace</p>
                                <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
                            </div>
                            <div class="workspace-meta">
                                <strong><?= e(date('l, d M Y')) ?></strong>
                                <span class="status-chip">System Active</span>
                            </div>
                        </header>

                        <?php if ($flash): ?>
                            <div class="flash flash-<?= e($flash['type']) ?>">
                                <?= e($flash['message']) ?>
                            </div>
                        <?php endif; ?>

                        <main class="page-content">
                        <?php endif; ?>