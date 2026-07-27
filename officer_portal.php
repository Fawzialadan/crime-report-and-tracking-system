<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_role('officer');

$officerId = current_officer_id();
$officer = User::find($officerId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $officer) {
    $reportId = (int) ($_POST['report_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $evidenceTitle = trim($_POST['evidence_title'] ?? '');
    $evidenceDescription = trim($_POST['evidence_description'] ?? '');
    $report = CrimeReport::find($reportId);

    if ($report && (int) $report['assigned_officer_id'] === $officerId && $notes !== '' && in_array($status, ['On Going', 'Closed'], true)) {
        CrimeReport::updateStatus($reportId, $status);
        CaseUpdate::create($reportId, $officerId, $officer['full_name'], 'officer', $status, $notes);
        Notification::create($reportId, $report['citizen_name'], 'citizen', $report['citizen_email'], 'Status update for ' . $report['reference_no'] . ': ' . $status . '.');

        $evidenceFiles = array_values(array_filter(
            normalize_uploaded_files_from_fields(['evidence_files', 'evidence_file']),
            static fn(array $file): bool => (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
        ));

        if ($evidenceTitle === '' && $evidenceFiles) {
            $evidenceTitle = default_upload_title_from_files($evidenceFiles);
        }

        if ($evidenceTitle !== '' || $evidenceFiles) {
            if ($evidenceTitle === '') {
                flash('error', 'Evidence title is required when adding a note without files.');
                redirect('officer_portal.php');
            }

            if (!$evidenceFiles) {
                Evidence::create([
                    'report_id' => $reportId,
                    'submitted_by_user_id' => $officerId,
                    'submitted_by_role' => 'officer',
                    'title' => $evidenceTitle,
                    'description' => $evidenceDescription !== '' ? $evidenceDescription : null,
                    'original_file_name' => null,
                    'stored_file_name' => null,
                    'mime_type' => null,
                    'file_category' => 'Note',
                    'file_size' => 0,
                ]);
            } else {
                foreach ($evidenceFiles as $file) {
                    try {
                        $storedFile = store_uploaded_file($file, 'evidence', 'evidence_', 40 * 1024 * 1024);
                    } catch (RuntimeException $exception) {
                        flash('error', $exception->getMessage());
                        redirect('officer_portal.php');
                    }

                    $entryTitle = count($evidenceFiles) > 1
                        ? $evidenceTitle . ' - ' . $storedFile['original_file_name']
                        : $evidenceTitle;

                    Evidence::create([
                        'report_id' => $reportId,
                        'submitted_by_user_id' => $officerId,
                        'submitted_by_role' => 'officer',
                        'title' => $entryTitle,
                        'description' => $evidenceDescription !== '' ? $evidenceDescription : null,
                        'original_file_name' => $storedFile['original_file_name'],
                        'stored_file_name' => $storedFile['stored_file_name'],
                        'mime_type' => $storedFile['mime_type'],
                        'file_category' => $storedFile['file_category'],
                        'file_size' => $storedFile['file_size'],
                    ]);
                }
            }

            Notification::create($reportId, $report['citizen_name'], 'citizen', $report['citizen_email'], 'New evidence was added to case ' . $report['reference_no'] . '.');
        }

        if ($status === 'Closed') {
            Notification::create($reportId, $report['citizen_name'], 'citizen', $report['citizen_email'], 'Case ' . $report['reference_no'] . ' has been closed.');
        }

        flash('success', 'Case updated successfully.');
    } else {
        flash('error', 'Please provide a valid status and investigation note.');
    }

    redirect('officer_portal.php');
}

$pageTitle = 'Officer Dashboard';
$assignedReports = $officer ? CrimeReport::assignedToOfficer($officerId) : [];
$summary = CrimeReport::summaryForOfficer($officerId);
$updates = CaseUpdate::forOfficer($officerId);
$notifications = $officer ? Notification::forRecipient('officer', $officer['email']) : [];

require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --officer-blue: rgb(46, 9, 50);
        --accent-pink: #ec4899;
        --soft-bg: #f1f5f9;
    }

    .hero-panel {
        background: linear-gradient(135deg, var(--officer-blue) 0%, #3b82f6 100%);
        color: white;
        padding: 40px;
        border-radius: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 16px;
        border-bottom: 4px solid var(--officer-blue);
        transition: transform 0.3s ease;
    }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-card strong { color: var(--officer-blue); font-size: 1.8rem; }

    .case-card {
        background: white;
        border-radius: 18px;
        padding: 25px;
        border: 1px solid #e2e8f0;
        margin-bottom: 25px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }

    .update-form {
        background: #f8fafc;
        padding: 20px;
        border-radius: 12px;
        margin-top: 20px;
        border: 1px dashed #cbd5e1;
    }

    .update-form label { font-weight: 700; color: #475569; display: block; margin-bottom: 5px; }
    
    .update-form input, .update-form select, .update-form textarea {
        width: 100%;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        margin-bottom: 15px;
    }

    .badge {
        background: var(--accent-pink);
        color: white;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 0.75rem;
    }

    .btn-home {
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 10px 20px;
        border-radius: 10px;
        text-decoration: none;
        border: 1px solid rgba(255,255,255,0.4);
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-home:hover { background: rgba(255,255,255,0.3); }

</style>

<section class="hero-panel">
    <div>
        <p class="section-label" style="opacity: 0.8; text-transform: uppercase; font-weight: 700; font-size: 0.8rem;">Officer Workspace</p>
        <h2 style="font-size: 2.2rem; margin: 10px 0;">Officer Dashboard</h2>
        <p class="hero-text" style="opacity: 0.9;">Maamul kiisaska laguu xilsaaray, ku dar cadaymo nooc kasta ah, kaydi files gaar ah, oo cusboonaysii xaaladda baarista.</p>
    </div>
    <div class="hero-actions" style="display: flex; gap: 10px;">
        <a class="btn-home" href="index.php">🏠 Back to Home</a>
        <a class="button-secondary" style="background: white; color: var(--officer-blue); padding: 10px 20px; border-radius: 10px; font-weight: 600;" href="officer_files.php">Private Files</a>
        <a class="button-secondary" style="background: rgba(255,255,255,0.16); color: white; border: 1px solid rgba(255,255,255,0.35); padding: 10px 20px; border-radius: 10px; font-weight: 600;" href="criminals.php">Criminal Registry</a>
        <a class="button-secondary" style="background: white; color: var(--officer-blue); padding: 10px 20px; border-radius: 10px; font-weight: 600;" href="profile.php">My Profile</a>
    </div>
</section>

<section class="stats-grid dashboard-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
    <div class="stat-card"><span>Assigned Cases</span><strong><?= e((string) $summary['total_reports']) ?></strong></div>
    <div class="stat-card" style="border-bottom-color: #f59e0b;"><span>On Going</span><strong><?= e((string) $summary['on_going_reports']) ?></strong></div>
    <div class="stat-card" style="border-bottom-color: #10b981;"><span>Closed</span><strong><?= e((string) $summary['closed_reports']) ?></strong></div>
    <div class="stat-card" style="border-bottom-color: var(--accent-pink);"><span>Notifications</span><strong><?= e((string) count($notifications)) ?></strong></div>
</section>

<section class="content-grid content-grid-wide" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
    <article class="panel">
        <div class="panel-header" style="margin-bottom: 25px;">
            <p class="section-label">Active Investigations</p>
            <h2>Manage Assigned Cases</h2>
        </div>

        <?php if (!$officer): ?>
            <p class="empty-state">Officer not found.</p>
        <?php elseif (!$assignedReports): ?>
            <div style="text-align: center; padding: 50px; background: #f8fafc; border-radius: 20px;">
                <p style="font-size: 1.2rem; color: #94a3b8;">No cases assigned to you yet, <?= e($officer['full_name']) ?>.</p>
            </div>
        <?php else: ?>
            <div class="cards-grid">
                <?php foreach ($assignedReports as $report): ?>
                    <article class="case-card">
                        <div class="card-topline" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <strong style="color: var(--officer-blue); font-size: 1.1rem;"><?= e($report['reference_no']) ?></strong>
                            <span class="badge"><?= e($report['status']) ?></span>
                        </div>
                        <h3 style="margin-bottom: 10px;"><?= e($report['title']) ?></h3>
                        <p style="color: #64748b; margin-bottom: 20px;"><?= e($report['description']) ?></p>
                        
                        <div class="meta-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.9rem; background: #fff; padding: 15px; border-radius: 10px; border: 1px solid #f1f5f9;">
                            <span><strong>Citizen:</strong> <?= e($report['citizen_name']) ?></span>
                            <span><strong>District:</strong> <?= e($report['district']) ?></span>
                            <span><strong>Location:</strong> <?= e($report['incident_location']) ?></span>
                            <span><strong>Crime:</strong> <?= e($report['crime_type']) ?></span>
                        </div>

                        <div class="action-row" style="margin: 20px 0;">
                            <a class="button-secondary" style="color: var(--officer-blue); font-weight: 700;" href="report_view.php?id=<?= e((string) $report['id']) ?>">🔍 View Full Details</a>
                            <a class="button-secondary" style="color: var(--accent-pink); font-weight: 700;" href="criminals.php?report_id=<?= e((string) $report['id']) ?>">Add Criminal</a>
                        </div>

                        <form method="POST" enctype="multipart/form-data" class="update-form">
                            <h4 style="margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;">Update Progress</h4>
                            <input type="hidden" name="report_id" value="<?= e((string) $report['id']) ?>">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div>
                                    <label>New Status</label>
                                    <select name="status" required>
                                        <option value="On Going" <?= $report['status'] === 'On Going' ? 'selected' : '' ?>>On Going</option>
                                        <option value="Closed">Closed</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Evidence Title</label>
                                    <input name="evidence_title" type="text" placeholder="Auto-filled from file name if left empty">
                                </div>
                            </div>

                            <label>Investigation Notes</label>
                            <textarea name="notes" rows="3" placeholder="Describe the current progress..." required></textarea>

                            <label>Evidence Files</label>
                            <input name="evidence_files[]" type="file" accept="<?= e(evidence_file_accept_attribute()) ?>" multiple style="background: white;">
                            <small style="display:block; margin-top:-8px; margin-bottom:15px; color:#64748b;">Accepted: image, video, voice, PDF, Word, Excel, PowerPoint, notes, text, and archive files.</small>
                            <small style="display:block; margin-top:-8px; margin-bottom:15px; color:#92400e;">If a file is in OneDrive, Google Drive, iCloud, or another cloud folder, download it to this computer first before uploading.</small>

                            <label>Evidence Description</label>
                            <textarea name="evidence_description" rows="2" placeholder="Short evidence notes..."></textarea>

                            <button class="button-primary" type="submit" style="width: 100%; background: var(--officer-blue); padding: 12px; border-radius: 10px;">Save Progress Update</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <article class="panel panel-soft" style="background: #f8fafc; border: none;">
        <div class="panel-header" style="margin-bottom: 20px;">
            <p class="section-label">Alerts</p>
            <h2>Notifications</h2>
        </div>
        <div class="stack-list">
            <?php if (!$notifications): ?>
                <p class="empty-state">No notifications yet.</p>
            <?php endif; ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="list-card" style="padding: 15px; background: white; border-radius: 12px; margin-bottom: 10px; border-left: 4px solid var(--accent-pink);">
                    <strong style="color: var(--officer-blue);"><?= e($notification['reference_no']) ?></strong>
                    <p style="font-size: 0.9rem; margin: 5px 0;"><?= e($notification['message']) ?></p>
                    <small style="color: #94a3b8;"><?= e($notification['created_at']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<?php if ($updates): ?>
    <section class="panel" style="margin-top: 30px;">
        <div class="panel-header" style="margin-bottom: 30px;">
            <p class="section-label">Activity Log</p>
            <h2>Your Recent Investigation Activity</h2>
        </div>
        <div class="timeline" style="margin-left: 15px; border-left: 2px solid #e2e8f0; padding-left: 25px;">
            <?php foreach ($updates as $update): ?>
                <div class="timeline-item" style="position: relative; margin-bottom: 30px;">
                    <div style="position: absolute; left: -32px; top: 0; width: 12px; height: 12px; background: var(--officer-blue); border-radius: 50%;"></div>
                    <strong style="color: #1e293b;"><?= e($update['reference_no']) ?> | <span style="color: var(--accent-pink);"><?= e($update['status_after_update']) ?></span></strong>
                    <p style="margin: 8px 0; color: #475569; background: #f8fafc; padding: 15px; border-radius: 10px;"><?= e($update['notes']) ?></p>
                    <small style="color: #94a3b8; font-weight: 600;">Updated on <?= e($update['created_at']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
