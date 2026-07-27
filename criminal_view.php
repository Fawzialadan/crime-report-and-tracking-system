<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'officer');

$criminalId = (int) ($_GET['id'] ?? 0);
$criminal = $criminalId > 0 ? Criminal::find($criminalId) : null;

if (!$criminal) {
    flash('error', 'Criminal record was not found.');
    redirect('criminals.php');
}

$pageTitle = 'Criminal Details';
$stats = Criminal::statusCounts($criminalId);
$caseHistory = Criminal::recentCases($criminalId, 50);

require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --criminal-bg: linear-gradient(135deg, #7c2d12 0%, #991b1b 100%);
        --criminal-soft: #fff7ed;
        --criminal-line: #fed7aa;
    }

    .criminal-hero {
        background: var(--criminal-bg);
        color: white;
        padding: 38px;
        border-radius: 26px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        gap: 20px;
        align-items: center;
    }

    .criminal-detail-grid {
        display: grid;
        grid-template-columns: 1.1fr 0.9fr;
        gap: 24px;
        margin-bottom: 24px;
    }

    .criminal-panel {
        background: white;
        border-radius: 24px;
        padding: 24px;
        border: 1px solid var(--criminal-line);
    }

    .criminal-profile {
        display: grid;
        grid-template-columns: 180px 1fr;
        gap: 24px;
        align-items: start;
    }

    .criminal-profile-photo {
        width: 180px;
        height: 220px;
        object-fit: cover;
        border-radius: 24px;
        border: 4px solid white;
        box-shadow: 0 18px 30px rgba(124, 45, 18, 0.14);
        background: #fff;
    }

    .criminal-profile-placeholder {
        width: 180px;
        height: 220px;
        border-radius: 24px;
        display: grid;
        place-items: center;
        background: linear-gradient(135deg, #fed7aa, #fecaca);
        color: #9a3412;
        font-size: 42px;
        font-weight: 800;
    }

    .criminal-facts {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-top: 18px;
    }

    .criminal-fact {
        background: var(--criminal-soft);
        border-radius: 16px;
        padding: 14px;
        border: 1px solid var(--criminal-line);
    }

    .criminal-fact span {
        display: block;
        color: #9a3412;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .criminal-stats {
        display: grid;
        gap: 14px;
    }

    .criminal-stat {
        background: var(--criminal-soft);
        border: 1px solid var(--criminal-line);
        border-radius: 18px;
        padding: 18px;
    }

    .criminal-stat strong {
        display: block;
        font-size: 28px;
        color: #991b1b;
    }

    .criminal-history-list {
        display: grid;
        gap: 14px;
    }

    .criminal-history-item {
        background: var(--criminal-soft);
        border: 1px solid var(--criminal-line);
        border-radius: 18px;
        padding: 18px;
    }

    @media (max-width: 980px) {
        .criminal-detail-grid,
        .criminal-profile,
        .criminal-facts {
            grid-template-columns: 1fr;
        }
    }
</style>

<section class="criminal-hero">
    <div>
        <p class="section-label" style="color: white;">Criminal Profile</p>
        <h2 style="margin: 0;"><?= e($criminal['full_name']) ?></h2>
        <p style="margin: 10px 0 0; color: rgba(255,255,255,0.84);">View the full registry profile, case history, and conviction summary for this criminal record.</p>
    </div>
    <div class="hero-actions">
        <a class="button-primary" href="criminals.php?edit_id=<?= e((string) $criminal['id']) ?>">Edit</a>
        <form method="POST" action="criminals.php" style="display:inline-block;" onsubmit="return confirm('Delete this criminal record? This will also remove its case links.');">
            <input type="hidden" name="action" value="delete_criminal">
            <input type="hidden" name="criminal_id" value="<?= e((string) $criminal['id']) ?>">
            <input type="hidden" name="report_id" value="0">
            <button class="button-secondary" type="submit" style="border-color:#fecaca; color:white; background:rgba(127,29,29,0.35);">Delete</button>
        </form>
        <a class="button-secondary" href="criminals.php">Back to Registry</a>
    </div>
</section>

<section class="criminal-detail-grid">
    <article class="criminal-panel">
        <div class="criminal-profile">
            <?php if (!empty($criminal['photo_stored_file_name'])): ?>
                <img class="criminal-profile-photo" src="download_file.php?type=criminal_photo&id=<?= e((string) $criminal['id']) ?>" alt="<?= e($criminal['full_name']) ?>">
            <?php else: ?>
                <div class="criminal-profile-placeholder"><?= e(substr($criminal['full_name'], 0, 2)) ?></div>
            <?php endif; ?>

            <div>
                <p class="section-label" style="color:#be123c;">Identity</p>
                <h2 style="margin:0; color:#7c2d12;"><?= e($criminal['full_name']) ?></h2>
                <p style="margin:10px 0 0; color:#44403c;"><?= e($criminal['notes'] ?: 'No notes recorded.') ?></p>

                <div class="criminal-facts">
                    <div class="criminal-fact">
                        <span>National ID</span>
                        <strong><?= e($criminal['national_id']) ?></strong>
                    </div>
                    <div class="criminal-fact">
                        <span>Phone Number</span>
                        <strong><?= e((string) ($criminal['phone_number'] ?: '-')) ?></strong>
                    </div>
                    <div class="criminal-fact">
                        <span>Date of Birth</span>
                        <strong><?= e(format_display_date($criminal['date_of_birth'] ?? null)) ?></strong>
                    </div>
                    <div class="criminal-fact">
                        <span>Height</span>
                        <strong><?= e($criminal['height_cm'] ? $criminal['height_cm'] . ' cm' : '-') ?></strong>
                    </div>
                    <div class="criminal-fact">
                        <span>Address</span>
                        <strong><?= e((string) ($criminal['address'] ?: '-')) ?></strong>
                    </div>
                    <div class="criminal-fact">
                        <span>Identifying Marks</span>
                        <strong><?= e((string) ($criminal['identifying_marks'] ?: '-')) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </article>

    <article class="criminal-panel">
        <p class="section-label" style="color:#be123c;">Record Summary</p>
        <h2 style="margin-top:0;">Criminal Details</h2>
        <div class="criminal-stats">
            <div class="criminal-stat">
                <span>Total Crimes / Cases</span>
                <strong><?= e((string) $stats['total_cases']) ?></strong>
                <small>This shows how many cases this criminal has been linked to.</small>
            </div>
            <div class="criminal-stat">
                <span>Found Guilty / Convicted</span>
                <strong><?= e((string) $stats['convicted_cases']) ?></strong>
                <small>This shows how many linked cases were marked as convicted.</small>
            </div>
            <div class="criminal-stat">
                <span>Arrested Cases</span>
                <strong><?= e((string) $stats['arrested_cases']) ?></strong>
                <small>Cases currently marked as arrested.</small>
            </div>
            <div class="criminal-stat">
                <span>Wanted Cases</span>
                <strong><?= e((string) $stats['wanted_cases']) ?></strong>
                <small>Cases currently marked as wanted.</small>
            </div>
        </div>
    </article>
</section>

<section class="criminal-panel">
    <div class="panel-header">
        <div>
            <p class="section-label" style="color:#be123c;">Case History</p>
            <h2>All Linked Crimes and Findings</h2>
        </div>
    </div>

    <div class="criminal-history-list">
        <?php if (!$caseHistory): ?>
            <p class="empty-state">No linked cases found for this criminal yet.</p>
        <?php endif; ?>

        <?php foreach ($caseHistory as $case): ?>
            <div class="criminal-history-item">
                <div class="card-topline">
                    <span class="badge"><?= e($case['record_status']) ?></span>
                    <strong><?= e($case['reference_no']) ?></strong>
                </div>
                <h3 style="margin-bottom:8px;"><?= e($case['title']) ?></h3>
                <p style="margin:0 0 8px; color:#44403c;">Case Status: <?= e($case['status']) ?></p>
                <p style="margin:0 0 8px; color:#7c2d12;"><?= e($case['case_notes'] ?: 'No case notes recorded.') ?></p>
                <div class="action-row">
                    <small>Linked on <?= e(format_display_date($case['linked_at'] ?? null, '-')) ?></small>
                    <a class="button-secondary" href="report_view.php?id=<?= e((string) $case['report_id']) ?>">Open Case</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
