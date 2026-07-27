<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_role('citizen');

$user = current_user();
$pageTitle = 'My Cases';
$reports = CrimeReport::forCitizenEmail($user['email']);

require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --dark-bg: #121212;
        --card-dark: #1e1e1e;
        --primary-pink: #ec4899;
        --secondary-pink: #be185d;
        --text-gray: #1b1b1d;
        --border-color: #333;
    }

    /* Hero Section oo leh Madow iyo Pink Gradient */
    .hero-panel {
        background: linear-gradient(135deg, var(--dark-bg) 0%, #2d1b24 100%);
        color: white;
        padding: 40px;
        border-radius: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        border: 1px solid var(--border-color);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }

    .hero-text {
        color: var(--text-gray);
        margin-top: 10px;
    }

    .section-label {
        color: var(--primary-pink);
        text-transform: uppercase;
        font-weight: 800;
        letter-spacing: 1.5px;
        font-size: 0.8rem;
    }

    /* Table & Panel Styling */
    .panel {
        background: var(--card-dark);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 30px;
        overflow: hidden;
    }

    .table-wrap {
        border-radius: 12px;
        overflow: hidden;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        color: #070707;
    }

    th {
        background: #252525;
        color: var(--primary-pink);
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 1px;
        padding: 15px;
        text-align: left;
    }

    td {
        padding: 18px 15px;
        border-bottom: 1px solid var(--border-color);
    }

    tr:hover {
        background: rgba(236, 72, 153, 0.03);
    }

    /* Badges & Buttons */
    .badge {
        background: rgba(236, 72, 153, 0.15);
        color: var(--primary-pink);
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 700;
        border: 1px solid rgba(236, 72, 153, 0.3);
    }

    .button-primary {
        background: var(--primary-pink);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 700;
        transition: 0.3s;
    }

    .button-primary:hover {
        background: var(--secondary-pink);
        transform: translateY(-2px);
    }

    .button-secondary {
        background: transparent;
        color: white;
        border: 1px solid var(--border-color);
        padding: 12px 24px;
        border-radius: 12px;
        transition: 0.3s;
    }

    .button-secondary:hover {
        background: #252525;
        border-color: var(--primary-pink);
    }

    .table-button {
        color: var(--primary-pink) !important;
        border-color: var(--primary-pink) !important;
        padding: 6px 15px !important;
        font-size: 0.85rem;
    }

    .table-button:hover {
        background: var(--primary-pink) !important;
        color: white !important;
    }
</style>

<section class="hero-panel">
    <div>
        <p class="section-label">Citizen Records</p>
        <h2 style="font-size: 2.2rem; margin: 5px 0;">My Cases</h2>
        <p class="hero-text">La soco xaaladda warbixinnada dambiyada aad dirtay.</p>
    </div>
    <div class="hero-actions" style="display: flex; gap: 12px;">
        <a class="button-primary" href="submit_report.php">➕ Report a Crime</a>
        <a class="button-secondary" href="citizen_dashboard.php">⬅️ Back Dashboard</a>
    </div>
</section>

<section class="panel">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Title</th>
                    <th>Crime Type</th>
                    <th>Status</th>
                    <th>Assigned Officer</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$reports): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 50px; color: var(--text-gray);">
                            Ma jiraan wax warbixin ah oo aad dirtay weli.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td style="font-weight: 700; color: Black;"><?= e($report['reference_no']) ?></td>
                        <td><?= e($report['title']) ?></td>
                        <td><span style="color: #131313;"><?= e($report['crime_type']) ?></span></td>
                        <td><span class="badge"><?= e($report['status']) ?></span></td>
                        <td style="font-style: italic; color: var(--text-gray);">
                            <?= e($report['officer_name'] ?? 'Pending Assignment') ?>
                        </td>
                        <td>
                            <a class="button-secondary table-button" href="report_view.php?id=<?= e((string) $report['id']) ?>">Open Case</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>