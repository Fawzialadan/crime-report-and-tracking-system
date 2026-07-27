<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$reportId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$report = $reportId > 0 ? CrimeReport::find($reportId) : null;

if (!$report || !$user || !can_access_report($user, $report)) {
    flash('error', 'You are not allowed to access that report.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_evidence') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $files = array_values(array_filter(
        normalize_uploaded_files_from_fields(['evidence_files', 'evidence_file']),
        static fn(array $file): bool => (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
    ));

    if ($title === '' && $files) {
        $title = default_upload_title_from_files($files);
    }

    if (!$files) {
        if ($title === '') {
            flash('error', 'Evidence title is required when adding a note without files.');
            redirect('report_view.php?id=' . $reportId);
        }

        Evidence::create([
            'report_id' => $reportId,
            'submitted_by_user_id' => (int) $user['id'],
            'submitted_by_role' => $user['role'],
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'original_file_name' => null,
            'stored_file_name' => null,
            'mime_type' => null,
            'file_category' => 'Note',
            'file_size' => 0,
        ]);
    } else {
        foreach ($files as $file) {
            try {
                $storedFile = store_uploaded_file($file, 'evidence', 'evidence_', 40 * 1024 * 1024);
            } catch (RuntimeException $exception) {
                flash('error', $exception->getMessage());
                redirect('report_view.php?id=' . $reportId);
            }

            $entryTitle = count($files) > 1
                ? $title . ' - ' . $storedFile['original_file_name']
                : $title;

            Evidence::create([
                'report_id' => $reportId,
                'submitted_by_user_id' => (int) $user['id'],
                'submitted_by_role' => $user['role'],
                'title' => $entryTitle,
                'description' => $description !== '' ? $description : null,
                'original_file_name' => $storedFile['original_file_name'],
                'stored_file_name' => $storedFile['stored_file_name'],
                'mime_type' => $storedFile['mime_type'],
                'file_category' => $storedFile['file_category'],
                'file_size' => $storedFile['file_size'],
            ]);
        }
    }

    CaseUpdate::create(
        $reportId,
        (int) $user['id'],
        $user['full_name'],
        $user['role'],
        $report['status'],
        ucfirst($user['role']) . ' added new evidence: ' . $title . '.',
        true
    );

    if ($user['role'] === 'officer') {
        Notification::create($reportId, $report['citizen_name'], 'citizen', $report['citizen_email'], 'New evidence has been attached to your case ' . $report['reference_no'] . '.');
    } elseif ($user['role'] === 'citizen') {
        Notification::create($reportId, $report['officer_name'] ?: 'Assigned Officer', 'officer', $report['officer_email'] ?? null, 'Citizen uploaded new evidence for case ' . $report['reference_no'] . '.');
    }

    flash('success', $files ? 'Evidence files added successfully.' : 'Evidence note added successfully.');
    redirect('report_view.php?id=' . $reportId);
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'add_officer_file'
    && $user['role'] === 'officer'
    && (int) ($report['assigned_officer_id'] ?? 0) === (int) $user['id']
) {
    $title = trim($_POST['private_title'] ?? '');
    $description = trim($_POST['private_description'] ?? '');

    if ($title === '') {
        flash('error', 'Private file title is required.');
        redirect('report_view.php?id=' . $reportId);
    }

    if (!isset($_FILES['private_file']) || (int) ($_FILES['private_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        flash('error', 'Please choose a private officer file to upload.');
        redirect('report_view.php?id=' . $reportId);
    }

    try {
        $folder = 'officer_files/officer_' . (int) $user['id'] . '/case_' . $reportId;
        $storedFile = store_uploaded_file($_FILES['private_file'], $folder, 'officer_', 40 * 1024 * 1024);
    } catch (RuntimeException $exception) {
        flash('error', $exception->getMessage());
        redirect('report_view.php?id=' . $reportId);
    }

    OfficerCaseFile::create([
        'report_id' => $reportId,
        'officer_id' => (int) $user['id'],
        'title' => $title,
        'description' => $description !== '' ? $description : null,
        'original_file_name' => $storedFile['original_file_name'],
        'stored_file_name' => $storedFile['stored_file_name'],
        'mime_type' => $storedFile['mime_type'],
        'file_category' => $storedFile['file_category'],
        'file_size' => $storedFile['file_size'],
    ]);

    CaseUpdate::create(
        $reportId,
        (int) $user['id'],
        $user['full_name'],
        'officer',
        $report['status'],
        'Officer saved a private investigation file.',
        false
    );

    flash('success', 'Private officer file saved successfully.');
    redirect('report_view.php?id=' . $reportId);
}

$pageTitle = 'Case Detail';
$evidences = Evidence::forReport($reportId);
$updates = CaseUpdate::forReport($reportId, $user['role'] === 'citizen');
$canSeeOfficerVault = $user['role'] === 'officer' && (int) ($report['assigned_officer_id'] ?? 0) === (int) $user['id'];
$officerPrivateFiles = $canSeeOfficerVault ? OfficerCaseFile::forOfficerCase($reportId, (int) $user['id']) : [];
$caseCriminals = in_array($user['role'], ['admin', 'officer'], true) ? Criminal::forReport($reportId) : [];

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .evidence-preview {
        margin-top: 14px;
        border-radius: 18px;
        overflow: hidden;
        background: #f8fafc;
        border: 1px solid #dbeafe;
    }

    .evidence-preview img,
    .evidence-preview video,
    .evidence-preview iframe {
        display: block;
        width: 100%;
        height: 360px;
        max-height: 360px;
        border: 0;
        background: #0f172a;
    }

    .evidence-preview img {
        height: auto;
        object-fit: cover;
    }

    .evidence-preview audio {
        display: block;
        width: 100%;
    }

    .criminal-case-history {
        margin-top: 14px;
        display: grid;
        gap: 10px;
    }

    .criminal-case-history-item {
        padding: 12px 14px;
        border-radius: 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }
</style>

<section class="hero-panel">
    <div>
        <p class="section-label">Case Report</p>
        <h2><?= e($report['reference_no']) ?></h2>
        <p class="hero-text"><?= e($report['title']) ?> in <?= e($report['district']) ?>. Current status: <?= e($report['status']) ?>.</p>
    </div>
    <div class="hero-actions">
        <a class="button-secondary" href="<?= e(back_dashboard_path()) ?>">Back</a>
        <?php if (in_array($user['role'], ['admin', 'officer'], true)): ?>
            <a class="button-secondary" href="criminals.php?report_id=<?= e((string) $reportId) ?>">Criminal Registry</a>
        <?php endif; ?>
        <button class="button-primary" type="button" onclick="window.print()">Print / Save PDF</button>
    </div>
</section>

<section class="content-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="section-label">Case Summary</p>
                <h2>Report Details</h2>
            </div>
        </div>
        <div class="detail-grid">
            <div class="detail-item"><strong>Citizen</strong><span><?= e($report['citizen_name']) ?></span></div>
            <div class="detail-item"><strong>Email</strong><span><?= e($report['citizen_email']) ?></span></div>
            <div class="detail-item"><strong>Phone</strong><span><?= e($report['citizen_phone'] ?? '-') ?></span></div>
            <div class="detail-item"><strong>Crime Type</strong><span><?= e($report['crime_type']) ?></span></div>
            <div class="detail-item"><strong>Officer</strong><span><?= e($report['officer_name'] ?? 'Not assigned') ?></span></div>
            <div class="detail-item"><strong>Location</strong><span><?= e($report['incident_location']) ?></span></div>
            <div class="detail-item"><strong>Occurred At</strong><span><?= e($report['occurred_at']) ?></span></div>
            <div class="detail-item"><strong>Status</strong><span><?= e($report['status']) ?></span></div>
        </div>
        <div class="report-description">
            <strong>Description</strong>
            <p><?= nl2br(e($report['description'])) ?></p>
        </div>
    </article>

    <article class="panel panel-soft">
        <div class="panel-header">
            <div>
                <p class="section-label">Evidence</p>
                <h2>Add New Evidence</h2>
            </div>
        </div>
        <form method="POST" enctype="multipart/form-data" class="auth-form">
            <input type="hidden" name="action" value="add_evidence">
            <div>
                <label for="title">Evidence Title</label>
                <input id="title" name="title" type="text" placeholder="Auto-filled from file name if left empty">
            </div>
            <div>
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"></textarea>
            </div>
            <div>
                <label for="evidence_files">File Upload</label>
                <input id="evidence_files" name="evidence_files[]" type="file" accept="<?= e(evidence_file_accept_attribute()) ?>" multiple>
                <!-- <small>Accepted: images, video, voice, PDF, Word, Excel, PowerPoint, notes, text files, and common archives.</small> -->
                <!-- <small style="display:block; color:#92400e; margin-top:6px;">If a file is in OneDrive, Google Drive, iCloud, or another cloud folder, download it to this computer first before uploading.</small> -->
            </div>
            <div class="action-row">
                <button class="button-primary" type="submit">Add Evidence</button>
                <a class="button-secondary" href="<?= e(back_dashboard_path()) ?>">Back</a>
            </div>
        </form>
    </article>
</section>

<?php if ($canSeeOfficerVault): ?>
    <section class="content-grid">
        <article class="panel">
            <div class="panel-header">
                <div>
                    <p class="section-label">Officer Only</p>
                    <h2>Private Case Files</h2>
                </div>
                <a class="button-secondary" href="officer_files.php">Open My Vault</a>
            </div>
            <form method="POST" enctype="multipart/form-data" class="auth-form">
                <input type="hidden" name="action" value="add_officer_file">
                <div>
                    <label for="private_title">Private File Title</label>
                    <input id="private_title" name="private_title" type="text" required>
                </div>
                <div>
                    <label for="private_description">Private Notes</label>
                    <textarea id="private_description" name="private_description" rows="4"></textarea>
                </div>
                <div>
                    <label for="private_file">Upload Private File</label>
                    <input id="private_file" name="private_file" type="file" accept="<?= e(evidence_file_accept_attribute()) ?>" required>
                    <small>Only the assigned officer account can access this file.</small>
                    <small style="display:block; color:#92400e; margin-top:6px;">If the file is stored in a cloud folder, download it to this computer first before uploading.</small>
                </div>
                <button class="button-primary" type="submit">Save Private File</button>
            </form>
        </article>

        <article class="panel panel-soft">
            <div class="panel-header">
                <div>
                    <p class="section-label">Officer Only</p>
                    <h2>Saved Private Files</h2>
                </div>
            </div>
            <div class="stack-list">
                <?php if (!$officerPrivateFiles): ?>
                    <p class="empty-state">No private files saved for this case yet.</p>
                <?php endif; ?>
                <?php foreach ($officerPrivateFiles as $file): ?>
                    <div class="list-card">
                        <strong><?= e($file['title']) ?></strong>
                        <p><?= e((string) ($file['description'] ?? 'No private notes provided.')) ?></p>
                        <small><?= e((string) ($file['file_category'] ?? 'File')) ?> | <?= e(format_file_size((int) $file['file_size'])) ?> | <?= e($file['created_at']) ?></small>
                        <div class="action-row">
                            <a class="button-secondary" href="download_file.php?type=officer_file&id=<?= e((string) $file['id']) ?>" target="_blank">Open Private File</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
<?php endif; ?>

<?php if (in_array($user['role'], ['admin', 'officer'], true)): ?>
    <section class="panel">
        <style>
            .criminal-inline-card {
                display: grid;
                grid-template-columns: 90px 1fr;
                gap: 16px;
                align-items: start;
            }

            .criminal-inline-photo {
                width: 90px;
                height: 110px;
                object-fit: cover;
                border-radius: 16px;
                border: 2px solid #fff;
                box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08);
                background: #fff;
            }

            .criminal-inline-placeholder {
                width: 90px;
                height: 110px;
                border-radius: 16px;
                display: grid;
                place-items: center;
                background: linear-gradient(135deg, #fbcfe8, #ddd6fe);
                color: #831843;
                font-weight: 800;
            }
        </style>
        <div class="panel-header">
            <div>
                <p class="section-label">Criminal Linkage</p>
                <h2>Criminals Attached to This Case</h2>
            </div>
            <a class="button-primary" href="criminals.php?report_id=<?= e((string) $reportId) ?>">Add or Update Criminal</a>
        </div>
        <div class="stack-list">
            <?php if (!$caseCriminals): ?>
                <p class="empty-state">No criminal records linked to this case yet.</p>
            <?php endif; ?>
            <?php foreach ($caseCriminals as $criminal): ?>
                <?php $priorCases = Criminal::priorCaseCount((int) $criminal['id'], $reportId); ?>
                <?php $previousCases = Criminal::previousCases((int) $criminal['id'], $reportId, 5); ?>
                <div class="list-card">
                    <div class="criminal-inline-card">
                        <?php if (!empty($criminal['photo_stored_file_name'])): ?>
                            <img class="criminal-inline-photo" src="download_file.php?type=criminal_photo&id=<?= e((string) $criminal['id']) ?>" alt="<?= e($criminal['full_name']) ?>">
                        <?php else: ?>
                            <div class="criminal-inline-placeholder"><?= e(substr($criminal['full_name'], 0, 2)) ?></div>
                        <?php endif; ?>
                        <div>
                            <div class="card-topline">
                                <span class="badge"><?= e($criminal['record_status']) ?></span>
                                <strong><?= e($criminal['national_id']) ?></strong>
                            </div>
                            <h3><?= e($criminal['full_name']) ?></h3>
                            <p><?= e((string) ($criminal['case_notes'] ?: $criminal['notes'] ?: 'No criminal notes available.')) ?></p>
                            <small>Phone: <?= e((string) ($criminal['phone_number'] ?: '-')) ?> | Height: <?= e($criminal['height_cm'] ? $criminal['height_cm'] . ' cm' : '-') ?> | <?= $priorCases > 0 ? 'Has previous cases: ' . e((string) $priorCases) : 'No previous cases found' ?></small>

                            <?php if ($previousCases): ?>
                                <div class="criminal-case-history">
                                    <strong style="color:#1d4ed8;">Previous Case Details (Kiisaskii hore ee uu galay)</strong>
                                    <?php foreach ($previousCases as $previousCase): ?>
                                        <div class="criminal-case-history-item">
                                            <strong><?= e($previousCase['reference_no']) ?> | <?= e($previousCase['record_status']) ?></strong>
                                            <p style="margin:6px 0;"><?= e($previousCase['title']) ?></p>
                                            <small>Status: <?= e($previousCase['status']) ?> | Notes: <?= e($previousCase['case_notes'] ?: 'No case notes recorded.') ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="action-row" style="margin-top:12px;">
                                <a class="button-secondary" href="criminal_view.php?id=<?= e((string) $criminal['id']) ?>">View Criminal Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="content-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="section-label">Evidence Registry</p>
                <h2>Attached Evidence</h2>
            </div>
        </div>
        <div class="stack-list">
            <?php if (!$evidences): ?>
                <p class="empty-state">No evidence attached yet.</p>
            <?php endif; ?>
            <?php foreach ($evidences as $evidence): ?>
                <?php
                $fileUrl = 'download_file.php?type=evidence&id=' . e((string) $evidence['id']);
                $previewKind = upload_preview_kind($evidence['mime_type'] ?? null, $evidence['original_file_name'] ?? null);
                ?>
                <div class="list-card">
                    <div class="card-topline">
                        <span class="badge"><?= e((string) ($evidence['file_category'] ?: 'Evidence')) ?></span>
                        <strong><?= e($evidence['title']) ?></strong>
                    </div>
                    <p><?= e($evidence['description'] ?? 'No description provided.') ?></p>
                    <small><?= e($evidence['submitted_by_role']) ?> | <?= e($evidence['full_name'] ?? 'System User') ?> | <?= e($evidence['created_at']) ?></small>
                    <?php if (!empty($evidence['stored_file_name'])): ?>
                        <?php if ($previewKind === 'image'): ?>
                            <div class="evidence-preview">
                                <img src="<?= $fileUrl ?>" alt="<?= e($evidence['title']) ?>">
                            </div>
                        <?php elseif ($previewKind === 'video'): ?>
                            <div class="evidence-preview">
                                <video controls preload="metadata">
                                    <source src="<?= $fileUrl ?>" type="<?= e((string) ($evidence['mime_type'] ?? 'video/mp4')) ?>">
                                </video>
                            </div>
                        <?php elseif ($previewKind === 'audio'): ?>
                            <div class="evidence-preview">
                                <audio controls preload="metadata">
                                    <source src="<?= $fileUrl ?>" type="<?= e((string) ($evidence['mime_type'] ?? 'audio/mpeg')) ?>">
                                </audio>
                            </div>
                        <?php elseif ($previewKind === 'pdf'): ?>
                            <div class="evidence-preview">
                                <iframe src="<?= $fileUrl ?>" title="<?= e($evidence['title']) ?>"></iframe>
                            </div>
                        <?php endif; ?>
                        <div class="action-row">
                            <a class="button-secondary" href="<?= $fileUrl ?>" target="_blank">Open / Download File</a>
                            <span class="file-meta"><?= e((string) ($evidence['original_file_name'] ?? '')) ?> | <?= e(format_file_size((int) $evidence['file_size'])) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel panel-soft">
        <div class="panel-header">
            <div>
                <p class="section-label">Audit Trail</p>
                <h2>Case Updates</h2>
            </div>
        </div>
        <div class="timeline">
            <?php if (!$updates): ?>
                <p class="empty-state">No case updates yet.</p>
            <?php endif; ?>
            <?php foreach ($updates as $update): ?>
                <div class="timeline-item">
                    <strong><?= e($update['status_after_update']) ?></strong>
                    <p><?= e($update['notes']) ?></p>
                    <small><?= e($update['actor_role']) ?> | <?= e($update['actor_name']) ?> | <?= e($update['created_at']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>