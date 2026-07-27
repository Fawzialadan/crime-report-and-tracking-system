<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_role('officer');

$officer = current_user();
$pageTitle = 'Officer Private Files';
$files = OfficerCaseFile::forOfficer((int) $officer['id']);

require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --vault-bg: linear-gradient(135deg, #111827 0%, #1d4ed8 100%);
        --vault-accent: #f59e0b;
        --vault-line: #dbeafe;
    }

    .vault-hero {
        background: var(--vault-bg);
        color: white;
        padding: 38px;
        border-radius: 24px;
        margin-bottom: 28px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
    }

    .vault-grid {
        display: grid;
        gap: 18px;
    }

    .vault-card {
        background: white;
        border: 1px solid var(--vault-line);
        border-radius: 20px;
        padding: 22px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }

    .vault-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin: 12px 0 16px;
        color: #64748b;
        font-size: 14px;
    }

    .vault-badge {
        display: inline-flex;
        padding: 6px 12px;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 800;
        font-size: 12px;
    }
</style>

<section class="vault-hero">
    <div>
        <p class="section-label" style="color: white;">Private Vault</p>
        <h2 style="margin: 0;">Officer Case Files</h2>
        <p style="margin: 10px 0 0; color: rgba(255,255,255,0.85);">Files are stored by assigned case and only your officer account can open them.</p>
    </div>
    <div class="hero-actions">
        <a class="button-secondary" href="officer_portal.php">Back Dashboard</a>
    </div>
</section>

<section class="vault-grid">
    <?php if (!$files): ?>
        <article class="vault-card">
            <p class="empty-state">No private files uploaded yet.</p>
        </article>
    <?php endif; ?>

    <?php foreach ($files as $file): ?>
        <article class="vault-card">
            <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                <div>
                    <span class="vault-badge"><?= e($file['file_category'] ?: 'File') ?></span>
                    <h3 style="margin: 12px 0 4px;"><?= e($file['title']) ?></h3>
                    <strong style="color:#1d4ed8;"><?= e($file['reference_no']) ?></strong>
                </div>
                <a class="button-primary" href="download_file.php?type=officer_file&id=<?= e((string) $file['id']) ?>" target="_blank">Open File</a>
            </div>
            <p style="color:#475569;"><?= e($file['description'] ?: 'No private notes provided.') ?></p>
            <div class="vault-meta">
                <span>Case: <?= e($file['case_title']) ?></span>
                <span>Size: <?= e(format_file_size((int) $file['file_size'])) ?></span>
                <span>Saved: <?= e($file['created_at']) ?></span>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
