<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_role('citizen');

$user = current_user();
$pageTitle = 'Citizen Dashboard';
$summary = CrimeReport::summaryForCitizen($user['email']);
$reports = CrimeReport::forCitizenEmail($user['email']);
$updates = CaseUpdate::forCitizen($user['email']);
$notifications = Notification::forRecipient('citizen', $user['email']);

require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --primary: #6366f1;
        --accent-pink: #ec4899;
        --bg-main: #fcfaff;
        --text-dark: #1e293b;
        --text-muted: #64748b;
        --border-color: #f1f5f9;
    }

    body { background-color: var(--bg-main) !important; color: var(--text-dark); }

    /* Hero Styling - Pink Gradient */
    .hero-panel {
        background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
        padding: 40px;
        border-radius: 24px;
        color: white;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 10px 30px -10px rgba(236, 72, 153, 0.3);
    }

    .section-label {
        background: rgba(255, 255, 255, 0.2);
        padding: 6px 14px;
        border-radius: 100px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: white;
        display: inline-block;
        margin-bottom: 12px;
    }

    /* Action Tiles */
    .dashboard-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .action-tile {
        background: white;
        padding: 20px;
        border-radius: 20px;
        text-decoration: none;
        display: flex;
        flex-direction: column;
        border: 1px solid var(--border-color);
        transition: 0.3s;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }

    .action-tile:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
    
    .action-tile span { font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--text-muted); margin-bottom: 5px; }
    .action-tile strong { font-size: 18px; color: var(--text-dark); }

    .tile-pink { border-left: 5px solid var(--accent-pink); }
    .tile-indigo { border-left: 5px solid var(--primary); }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 18px;
        text-align: center;
        border: 1px solid var(--border-color);
    }

    .stat-card span { font-size: 13px; color: var(--text-muted); display: block; }
    .stat-card strong { font-size: 28px; color: var(--accent-pink); }

    /* Table & Panel Styling */
    .panel {
        background: white;
        border-radius: 24px;
        padding: 25px;
        border: 1px solid var(--border-color);
        margin-bottom: 30px;
    }

    .table-wrap { overflow-x: auto; margin-top: 15px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #fdf2f8; padding: 15px; text-align: left; font-size: 12px; color: #be185d; text-transform: uppercase; }
    td { padding: 15px; border-bottom: 1px solid #f8fafc; font-size: 14px; }

    .badge {
        padding: 5px 12px;
        border-radius: 8px;
        background: #fdf2f8;
        color: #db2777;
        font-weight: 700;
        font-size: 11px;
    }

    /* Buttons */
    .btn-pink { background: var(--accent-pink); color: white; padding: 12px 24px; border-radius: 14px; text-decoration: none; font-weight: 700; transition: 0.3s; display: inline-block; }
    .btn-pink:hover { background: #db2777; transform: translateY(-2px); }

    .btn-outline { background: rgba(255,255,255,0.2); color: white; padding: 12px 24px; border-radius: 14px; text-decoration: none; font-weight: 600; border: 1px solid rgba(255,255,255,0.3); }

    /* Notifications List */
    .list-card {
        padding: 15px;
        border-bottom: 1px solid #f1f5f9;
        transition: 0.2s;
    }
    .list-card:hover { background: #fdf2f8; border-radius: 12px; }

</style>

<div class="container" style="max-width: 1300px; margin: 40px auto; padding: 0 20px;">
    
    <section class="hero-panel">
        <div>
            <span class="section-label">Citizen Experience</span>
            <h2 style="font-size: 32px; font-weight: 800; margin: 0;">Welcome, <?= e($user['full_name']) ?></h2>
            <p style="color: rgba(255,255,255,0.8); margin-top: 8px; font-size: 16px;">Track your reported cases and stay updated with live notifications.</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <a class="btn-pink" style="background: white; color: var(--accent-pink);" href="submit_report.php">Report a Crime</a>
            <!-- <a class="btn-outline" href="index.php">General Dashboard</a> -->
        </div>
    </section>

    <section class="dashboard-actions">
        <a class="action-tile tile-pink" href="submit_report.php"><span>New Intake</span><strong>Report a Crime</strong></a>
        <a class="action-tile tile-indigo" href="my_cases.php"><span>History</span><strong>My Cases</strong></a>
        <a class="action-tile tile-pink" href="profile.php"><span>Account</span><strong>Profile</strong></a>
        <a class="action-tile tile-indigo" href="index.php"><span>Portal</span><strong>Back Home</strong></a>
    </section>

    <section class="stats-grid">
        <div class="stat-card"><span>My Total Reports</span><strong><?= e((string) $summary['total_reports']) ?></strong></div>
        <div class="stat-card"><span>Active Cases</span><strong><?= e((string) $summary['open_reports']) ?></strong></div>
        <div class="stat-card"><span>Resolved</span><strong><?= e((string) $summary['closed_reports']) ?></strong></div>
        <div class="stat-card"><span>Notifications</span><strong><?= e((string) count($notifications)) ?></strong></div>
    </section>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
        <article class="panel">
            <div class="panel-header">
                <span class="section-label" style="color: var(--accent-pink); background: #fdf2f8;">Track Progress</span>
                <h2 style="margin: 10px 0 20px 0;">Latest Case Status</h2>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Officer</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$reports): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">No reports found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--primary);">#<?= e($report['reference_no']) ?></td>
                                <td><?= e($report['title']) ?></td>
                                <td><span class="badge"><?= e($report['status']) ?></span></td>
                                <td><span style="font-size: 13px;"><?= e($report['officer_name'] ?? 'Awaiting...') ?></span></td>
                                <td style="text-align: right;"><a href="report_view.php?id=<?= e((string) $report['id']) ?>" style="color: var(--accent-pink); font-weight: 600; text-decoration: none;">View Details</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="panel">
            <div class="panel-header">
                <span class="section-label" style="color: var(--primary); background: #eff6ff;">Recent Alerts</span>
                <h2 style="margin: 10px 0 20px 0;">Notifications</h2>
            </div>
            <div class="stack-list">
                <?php if (!$notifications): ?>
                    <p style="text-align: center; color: var(--text-muted); padding: 20px;">Nadiif! No new alerts.</p>
                <?php endif; ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="list-card">
                        <strong style="font-size: 13px; color: var(--accent-pink);">#<?= e($notification['reference_no']) ?></strong>
                        <p style="margin: 5px 0; font-size: 14px; color: var(--text-dark);"><?= e($notification['message']) ?></p>
                        <small style="color: var(--text-muted); font-size: 11px;"><?= e($notification['created_at']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </div>

    <?php if ($updates): ?>
        <section class="panel">
            <div class="panel-header">
                <span class="section-label">Journal</span>
                <h2 style="margin: 10px 0 20px 0;">Recent Case Updates</h2>
            </div>
            <div style="border-left: 2px dashed #e2e8f0; margin-left: 10px; padding-left: 30px;">
                <?php foreach ($updates as $update): ?>
                    <div style="position: relative; margin-bottom: 25px;">
                        <div style="position: absolute; left: -37px; top: 0; width: 12px; height: 12px; background: var(--accent-pink); border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 4px #fdf2f8;"></div>
                        <strong style="color: var(--primary);">#<?= e($update['reference_no']) ?> | <?= e($update['status_after_update']) ?></strong>
                        <p style="margin: 5px 0; font-size: 15px; color: #475569;"><?= e($update['notes']) ?></p>
                        <small style="color: var(--text-muted);"><?= e($update['actor_name']) ?> • <?= e($update['created_at']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>