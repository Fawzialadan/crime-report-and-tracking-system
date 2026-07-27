<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

$filters = [
    'keyword' => trim($_GET['keyword'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'district' => system_service_area_name(),
    'officer_id' => trim($_GET['officer_id'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'assign') {
    $reportId = (int) ($_POST['report_id'] ?? 0);
    $officerId = (int) ($_POST['officer_id'] ?? 0);
    $report = CrimeReport::find($reportId);
    $officer = User::find($officerId);

    if ($report && $officer) {
        Assignment::assign($reportId, $officerId, current_admin_id());
        CaseUpdate::create($reportId, current_admin_id(), current_user()['full_name'], 'admin', 'On Going', 'Admin assigned the case to ' . $officer['full_name'] . '. Status moved to On Going.');
        Notification::create($reportId, $officer['full_name'], 'officer', $officer['email'], 'You have been assigned case ' . $report['reference_no'] . '. Status is now On Going.');
        Notification::create($reportId, $report['citizen_name'], 'citizen', $report['citizen_email'], 'Your report ' . $report['reference_no'] . ' has been assigned to an investigating officer. Status: On Going.');
        flash('success', 'Officer assigned successfully. Case is now On Going.');
    } else {
        flash('error', 'Assignment failed. Report or officer not found.');
    }
    redirect('admin_reports.php');
}

$pageTitle = 'Manage Reports and Cases';
$reports = CrimeReport::searchAll($filters);
$officers = User::allOfficers();

require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --primary: #6366f1; /* Indigo */
        --primary-hover: #4f46e5;
        --accent-pink: #ec4899; /* Pink */
        --bg-main: #fcfaff; 
        --card-bg: #ffffff;
        --text-dark: #1e293b;
        --text-muted: #64748b;
        --border-color: #f1f5f9;
        --pending: #f59e0b;
        --ongoing: #3b82f6;
        --closed: #10b981;
    }

    body { background-color: var(--bg-main) !important; color: var(--text-dark); }

    /* Hero Styling - Halkan ayaan Pink ka dhigay */
    .hero-panel {
        background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%); /* Pink to Purple Gradient */
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

    /* Filter Form */
    .filter-card {
        background: white;
        padding: 25px;
        border-radius: 20px;
        border: 1px solid var(--border-color);
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .filter-grid label { font-size: 13px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 8px; }
    
    .filter-grid input, .filter-grid select {
        width: 100%;
        padding: 11px 14px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        font-size: 14px;
        background: #f8fafc;
        transition: 0.3s;
    }

    .filter-grid input:focus, .filter-grid select:focus {
        border-color: var(--accent-pink);
        outline: none;
        box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
    }

    /* Table Styling */
    .data-card {
        background: white;
        border-radius: 24px;
        border: 1px solid var(--border-color);
        overflow: hidden;
        box-shadow: 0 10px 20px rgba(0,0,0,0.01);
    }

    .table-wrap { overflow-x: auto; }
    
    table { width: 100%; border-collapse: collapse; text-align: left; }
    
    th {
        background: #fdf2f8; /* Very light pink header */
        padding: 16px 20px;
        font-size: 12px;
        font-weight: 700;
        color: #be185d; /* Pinkish text */
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    td { padding: 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; }

    .ref-no { font-weight: 800; color: var(--accent-pink); display: block; }
    .table-note { font-size: 12px; color: var(--text-muted); margin-top: 4px; }

    /* Badge Colors */
    .badge {
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
    }
    .badge-Pending { background: #fffbeb; color: #d97706; }
    .badge-On-Going { background: #eff6ff; color: #2563eb; }
    .badge-Closed { background: #f0fdf4; color: #16a34a; }
    .badge-ASSIGNED { background: #fdf2f8; color: #db2777; } /* Special style for assigned */

    /* Inline Form Styling */
    .assign-form { display: flex; gap: 8px; align-items: center; }
    .assign-form select { padding: 8px 12px; border-radius: 10px; border: 1px solid #e2e8f0; font-size: 13px; min-width: 160px; background: #fff; }

    /* Buttons */
    .btn-action {
        padding: 9px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
        border: none;
    }
    .btn-assign { background: var(--accent-pink); color: white; }
    .btn-assign:hover { background: #db2777; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(236, 72, 153, 0.2); }
    
    .btn-view { background: #f8fafc; color: var(--text-dark); text-decoration: none; border: 1px solid #e2e8f0; }
    .btn-view:hover { background: #f1f5f9; }

    .btn-filter { background: var(--primary); color: white; padding: 11px 25px; }
    .btn-filter:hover { background: var(--primary-hover); }

    .btn-clear { background: #f1f5f9; color: var(--text-muted); text-decoration: none; padding: 11px 25px; border-radius: 10px; display: inline-block; font-size: 14px; }

</style>

<div class="container" style="max-width: 1350px; margin: 40px auto; padding: 0 20px;">

    <section class="hero-panel">
        <div>
            <span class="section-label">Command Center</span>
            <h2 style="font-size: 32px; font-weight: 800; margin: 0; letter-spacing: -0.5px;">Manage Reports and Cases</h2>
            <p style="color: rgba(255,255,255,0.8); margin-top: 8px; font-size: 16px;">Monitor Bosaso reports only and dispatch investigating officers within the Bosaso service area.</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="admin_dashboard.php" style="background: white; color: var(--accent-pink); padding: 12px 24px; border-radius: 14px; text-decoration: none; font-weight: 700; font-size: 14px; transition: 0.3s;">Dashboard</a>
            <a href="criminals.php" style="background: rgba(255,255,255,0.16); color: white; padding: 12px 24px; border-radius: 14px; text-decoration: none; font-weight: 600; font-size: 14px; backdrop-filter: blur(10px);">Criminal Registry</a>
            <a href="index.php" style="background: rgba(255,255,255,0.2); color: white; padding: 12px 24px; border-radius: 14px; text-decoration: none; font-weight: 600; font-size: 14px; backdrop-filter: blur(10px);">Back</a>
        </div>
    </section>

    <div class="filter-card">
        <form method="GET" class="filter-grid">
            <div>
                <label for="keyword">Search Keyword</label>
                <input id="keyword" name="keyword" type="text" value="<?= e($filters['keyword']) ?>" placeholder="Ref #, Title, Name...">
            </div>
            <div>
                <label for="status">Case Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?= $filters['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="On Going" <?= $filters['status'] === 'On Going' ? 'selected' : '' ?>>On Going</option>
                    <option value="Closed" <?= $filters['status'] === 'Closed' ? 'selected' : '' ?>>Closed</option>
                </select>
            </div>
            <div>
                <label for="district">District</label>
                <input id="district" name="district" type="text" value="<?= e(system_service_area_name()) ?>" readonly>
            </div>
            <div>
                <label for="officer_id">Assigned Officer</label>
                <select id="officer_id" name="officer_id">
                    <option value="">All Officers</option>
                    <?php foreach ($officers as $officer): ?>
                        <option value="<?= e((string) $officer['id']) ?>" <?= $filters['officer_id'] === (string) $officer['id'] ? 'selected' : '' ?>>
                            <?= e($officer['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; align-items: flex-end; gap: 10px;">
                <button class="btn-filter btn-action" style="flex: 2;" type="submit">Filter Results</button>
                <a class="btn-clear" style="flex: 1; text-align: center;" href="admin_reports.php">Reset</a>
            </div>
        </form>
    </div>

    <div class="data-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Reference & Title</th>
                        <th>Citizen Details</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Current Officer</th>
                        <th>Dispatch / Reassign</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$reports): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 60px; color: var(--text-muted);">
                        <div style="font-size: 16px;">Ma jiro wax reports ah oo la helay.</div>
                    </td></tr>
                <?php endif; ?>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td>
                            <span class="ref-no">#<?= e($report['reference_no']) ?></span>
                            <div class="table-note"><?= e($report['title']) ?></div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #475569;"><?= e($report['citizen_name']) ?></div>
                            <div class="table-note"><?= e($report['citizen_email']) ?></div>
                        </td>
                        <td><span style="font-weight: 600; color: #1e293b;"><?= e($report['crime_type']) ?></span></td>
                        <td>
                            <span class="badge badge-<?= str_replace(' ', '-', e($report['status'])) ?>">
                                <?= strtoupper(e($report['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($report['officer_name']): ?>
                                <div style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 500;">
                                    <span style="color: var(--accent-pink);">👤</span> <?= e($report['officer_name']) ?>
                                </div>
                            <?php else: ?>
                                <span style="font-size: 12px; color: var(--pending); font-weight: 700;">NOT ASSIGNED</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form class="assign-form" method="POST">
                                <input type="hidden" name="action" value="assign">
                                <input type="hidden" name="report_id" value="<?= e((string) $report['id']) ?>">
                                <select name="officer_id" required>
                                    <option value="">Select Officer...</option>
                                    <?php foreach ($officers as $officer): ?>
                                        <option value="<?= e((string) $officer['id']) ?>">
                                            <?= e($officer['full_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn-action btn-assign" type="submit">Assign</button>
                            </form>
                        </td>
                        <td style="text-align: right;">
                            <a class="btn-action btn-view" href="report_view.php?id=<?= e((string) $report['id']) ?>">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
