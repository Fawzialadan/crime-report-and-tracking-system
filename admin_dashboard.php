<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

$pageTitle = 'Admin Dashboard';
$summary = CrimeReport::summary();
$roleSummary = User::summaryByRole();
$pendingReports = CrimeReport::recentByStatus('Pending');
$activeReports = CrimeReport::recentByStatus('On Going');
$closedReports = CrimeReport::recentByStatus('Closed');
$officerWorkload = CrimeReport::officerWorkload();

require_once __DIR__ . '/includes/header.php';
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

    :root {
        --glass-bg: rgba(255, 255, 255, 0.9);
        --glass-border: rgba(255, 255, 255, 0.2);
        --accent-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        --text-dark: #0f172a;
        --text-muted: #64748b;
        --bg-soft: #f8fafc;
    }

    body {
        background: radial-gradient(at top left, #eff6ff, #f8fafc) !important;
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: var(--text-dark);
        margin: 0;
        padding: 0;
    }

    .dashboard-container {
        max-width: 1300px;
        margin: 30px auto;
        padding: 0 25px;
    }

    /* Modern Hero Panel */
    .hero-panel {
        background: var(--accent-gradient);
        padding: 50px;
        border-radius: 30px;
        color: white;
        margin-bottom: 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 20px 40px -15px rgba(99, 102, 241, 0.35);
        position: relative;
        overflow: hidden;
    }

    .hero-panel::after {
        content: '';
        position: absolute;
        top: -50px;
        right: -50px;
        width: 200px;
        height: 200px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
    }

    .section-label {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(5px);
        padding: 6px 16px;
        border-radius: 100px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-bottom: 15px;
        display: inline-block;
    }

    .hero-panel h2 { font-size: 34px; font-weight: 800; margin: 0; }
    .hero-text { color: rgba(255, 255, 255, 0.8); margin-top: 10px; font-size: 16px; }

    /* Action Tiles - Modern Grid */
    .dashboard-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .action-tile {
        background: var(--glass-bg);
        border: 1px solid var(--border);
        padding: 25px;
        border-radius: 24px;
        text-decoration: none;
        color: var(--text-dark);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .action-tile:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        background: white;
        border-color: #6366f1;
    }

    .action-tile strong { font-size: 16px; font-weight: 700; margin-top: 8px; }
    .action-tile span { font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }

    /* Advanced Stats Grid */
    .stats-section-title { font-weight: 800; font-size: 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 25px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: white;
        padding: 30px;
        border-radius: 24px;
        position: relative;
        overflow: hidden;
        border: 1px solid #f1f5f9;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.02);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        left: 0; top: 0; width: 5px; height: 100%;
        background: var(--accent-gradient);
    }

    .stat-card span { color: var(--text-muted); font-size: 13px; font-weight: 600; }
    .stat-card strong { display: block; font-size: 36px; font-weight: 800; color: var(--text-dark); margin-top: 5px; }

    /* Content Panels */
    .content-grid {
        display: grid;
        grid-template-columns: 1.8fr 1.2fr;
        gap: 30px;
    }

    .panel {
        background: white;
        padding: 35px;
        border-radius: 30px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
    }

    .panel-title { font-size: 22px; font-weight: 800; margin-bottom: 25px; }

    /* Case Columns */
    .triple-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    .mini-panel {
        background: #f8fafc;
        padding: 20px;
        border-radius: 20px;
    }

    .mini-panel h3 { font-size: 13px; font-weight: 800; margin-bottom: 15px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }

    .compact-case {
        background: white;
        padding: 15px;
        border-radius: 15px;
        margin-bottom: 12px;
        border: 1px solid #edf2f7;
        transition: 0.3s;
    }

    .compact-case:hover { border-color: #6366f1; transform: scale(1.02); }
    .compact-case strong { color: #6366f1; font-size: 12px; display: block; margin-bottom: 4px; }
    .compact-case span { font-size: 13px; font-weight: 600; display: block; color: var(--text-dark); }

    /* Officer Cards */
    .officer-list { display: flex; flex-direction: column; gap: 15px; }
    .officer-card {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 18px;
        background: #f8fafc;
        border-radius: 20px;
        border: 1px solid #f1f5f9;
    }

    .officer-avatar {
        width: 45px;
        height: 45px;
        background: var(--accent-gradient);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 800;
    }

    .officer-info strong { display: block; font-size: 14px; }
    .officer-info small { color: var(--text-muted); font-size: 12px; }

    .badge-pills {
        display: flex;
        gap: 8px;
        margin-top: 5px;
    }

    .pill {
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 100px;
        font-weight: 700;
        background: white;
        color: #6366f1;
        border: 1px solid #e2e8f0;
    }

    /* Buttons */
    .btn-wrap { display: flex; gap: 12px; }
    .btn-main {
        background: white;
        color: var(--text-dark);
        padding: 12px 24px;
        border-radius: 14px;
        text-decoration: none;
        font-weight: 700;
        font-size: 14px;
        transition: 0.3s;
    }

    .btn-main:hover { background: #f1f5f9; transform: translateY(-2px); }

    @media (max-width: 1024px) {
        .stats-grid { grid-template-columns: 1fr 1fr; }
        .content-grid { grid-template-columns: 1fr; }
        .hero-panel { flex-direction: column; text-align: center; gap: 20px; }
    }
</style>

<div class="dashboard-container">
    
    <section class="hero-panel">
        <div>
            <span class="section-label">Command Center</span>
            <h2>Admin Panel</h2>
            <p class="hero-text">Control and monitor the safety ecosystem from a single interface.</p>
        </div>
        <div class="btn-wrap">
            <a class="btn-main" href="admin_reports.php">Manage Reports</a>
            <a class="btn-main" href="index.php" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2);">General View</a>
        </div>
    </section>

    <section class="dashboard-actions">
        <a class="action-tile" href="admin_reports.php"><span>System</span><strong>Reports Hub</strong></a>
        <a class="action-tile" href="manage_users.php?role=citizen"><span>Users</span><strong>Citizens</strong></a>
        <a class="action-tile" href="manage_users.php?role=officer"><span>Staff</span><strong>Officers</strong></a>
        <a class="action-tile" href="admin_reports.php?status=Pending"><span>Task</span><strong>Assignments</strong></a>
        <a class="action-tile" href="criminals.php"><span>Records</span><strong>Criminal Registry</strong></a>
        <a class="action-tile" href="profile.php"><span>Account</span><strong>Profile</strong></a>
    </section>

    <h3 class="stats-section-title">📊 Incident Metrics</h3>
    <section class="stats-grid">
        <div class="stat-card"><span>Total Reports</span><strong><?= e((string) $summary['total_reports']) ?></strong></div>
        <div class="stat-card"><span>Pending Review</span><strong><?= e((string) $summary['pending_reports']) ?></strong></div>
        <div class="stat-card"><span>Active Investigations</span><strong><?= e((string) $summary['on_going_reports']) ?></strong></div>
        <div class="stat-card"><span>Resolved Cases</span><strong><?= e((string) $summary['closed_reports']) ?></strong></div>
    </section>

    <h3 class="stats-section-title">👥 Population Metrics</h3>
    <section class="stats-grid">
        <div class="stat-card"><span>System Users</span><strong><?= e((string) $roleSummary['total_users']) ?></strong></div>
        <div class="stat-card"><span>Administrators</span><strong><?= e((string) $roleSummary['admins']) ?></strong></div>
        <div class="stat-card"><span>Field Officers</span><strong><?= e((string) $roleSummary['officers']) ?></strong></div>
        <div class="stat-card"><span>Verified Citizens</span><strong><?= e((string) $roleSummary['citizens']) ?></strong></div>
    </section>

    <section class="content-grid">
        <article class="panel">
            <h2 class="panel-title">Real-time Case Queue</h2>
            <div class="triple-grid">
                <div class="mini-panel">
                    <h3>Pending</h3>
                    <?php foreach ($pendingReports as $report): ?>
                        <div class="compact-case">
                            <strong>#<?= e($report['reference_no']) ?></strong>
                            <span><?= e($report['title']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mini-panel" style="background: #eff6ff;">
                    <h3 style="color: #3b82f6;">Processing</h3>
                    <?php foreach ($activeReports as $report): ?>
                        <div class="compact-case" style="border-left: 3px solid #3b82f6;">
                            <strong>#<?= e($report['reference_no']) ?></strong>
                            <span><?= e($report['title']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mini-panel" style="background: #f0fdf4;">
                    <h3 style="color: #22c55e;">Success</h3>
                    <?php foreach ($closedReports as $report): ?>
                        <div class="compact-case" style="border-left: 3px solid #22c55e;">
                            <strong>#<?= e($report['reference_no']) ?></strong>
                            <span><?= e($report['title']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>

        <article class="panel">
            <h2 class="panel-title">Officer Workload</h2>
            <div class="officer-list">
                <?php foreach ($officerWorkload as $officer): ?>
                    <div class="officer-card">
                        <div class="officer-avatar">
                            <?= strtoupper(substr($officer['full_name'], 0, 1)) ?>
                        </div>
                        <div class="officer-info">
                            <strong><?= e($officer['full_name']) ?></strong>
                            <small>Badge: <?= e($officer['badge_number'] ?? 'N/A') ?></small>
                            <div class="badge-pills">
                                <div class="pill">Active: <?= e((string) $officer['active_cases']) ?></div>
                                <div class="pill" style="color: #22c55e;">Total: <?= e((string) $officer['total_cases']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
