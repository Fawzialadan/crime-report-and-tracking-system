<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin', 'officer');

$user = current_user();
$reportContextId = isset($_GET['report_id']) ? (int) $_GET['report_id'] : (int) ($_POST['report_id'] ?? 0);
$contextReport = $reportContextId > 0 ? CrimeReport::find($reportContextId) : null;
$editCriminalId = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : (int) ($_POST['edit_id'] ?? 0);
$editCriminal = $editCriminalId > 0 ? Criminal::find($editCriminalId) : null;

if ($reportContextId > 0 && (!$contextReport || !can_access_report($user, $contextReport))) {
    flash('error', 'You are not allowed to open that case context.');
    redirect('criminals.php');
}

if ($editCriminalId > 0 && !$editCriminal) {
    flash('error', 'Criminal record was not found for editing.');
    redirect($reportContextId > 0 ? 'criminals.php?report_id=' . $reportContextId : 'criminals.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_criminal') {
    $criminalId = (int) ($_POST['criminal_id'] ?? 0);
    $reportId = (int) ($_POST['report_id'] ?? 0);
    $criminal = $criminalId > 0 ? Criminal::find($criminalId) : null;

    if (!$criminal) {
        flash('error', 'Criminal record was not found.');
        redirect($reportId > 0 ? 'criminals.php?report_id=' . $reportId : 'criminals.php');
    }

    Criminal::delete($criminalId);
    flash('success', 'Criminal record deleted successfully.');
    redirect($reportId > 0 ? 'criminals.php?report_id=' . $reportId : 'criminals.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_criminal') {
    $editId = (int) ($_POST['edit_id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $nationalId = trim($_POST['national_id'] ?? '');
    $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
    $heightCm = trim($_POST['height_cm'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $identifyingMarks = trim($_POST['identifying_marks'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $recordStatus = trim($_POST['record_status'] ?? 'Suspect');
    $reportId = (int) ($_POST['report_id'] ?? 0);

    if ($fullName === '' || $nationalId === '') {
        flash('error', 'Criminal name and national ID are required.');
        redirect($reportId > 0 ? 'criminals.php?report_id=' . $reportId : 'criminals.php');
    }

    $editingCriminal = $editId > 0 ? Criminal::find($editId) : null;
    if ($editId > 0 && !$editingCriminal) {
        flash('error', 'Criminal record was not found for update.');
        redirect($reportId > 0 ? 'criminals.php?report_id=' . $reportId : 'criminals.php');
    }

    if ($editId > 0 && Criminal::nationalIdInUseByAnother($nationalId, $editId)) {
        flash('error', 'That national ID already belongs to another criminal record.');
        redirect($reportId > 0 ? 'criminals.php?report_id=' . $reportId . '&edit_id=' . $editId : 'criminals.php?edit_id=' . $editId);
    }

    $photoMeta = [
        'photo_original_file_name' => null,
        'photo_stored_file_name' => null,
    ];

    if (isset($_FILES['photo']) && (int) ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $storedPhoto = store_uploaded_file($_FILES['photo'], 'criminals', 'criminal_', 10 * 1024 * 1024);
            $photoMeta = [
                'photo_original_file_name' => $storedPhoto['original_file_name'],
                'photo_stored_file_name' => $storedPhoto['stored_file_name'],
            ];
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
            redirect($reportId > 0 ? 'criminals.php?report_id=' . $reportId : 'criminals.php');
        }
    }

    $payload = [
        'full_name' => $fullName,
        'phone_number' => $phoneNumber !== '' ? $phoneNumber : null,
        'national_id' => $nationalId,
        'date_of_birth' => $dateOfBirth !== '' ? $dateOfBirth : null,
        'height_cm' => $heightCm !== '' ? (int) $heightCm : null,
        'address' => $address !== '' ? $address : null,
        'identifying_marks' => $identifyingMarks !== '' ? $identifyingMarks : null,
        'notes' => $notes !== '' ? $notes : null,
        'photo_original_file_name' => $photoMeta['photo_original_file_name'] ?: ($editingCriminal['photo_original_file_name'] ?? null),
        'photo_stored_file_name' => $photoMeta['photo_stored_file_name'] ?: ($editingCriminal['photo_stored_file_name'] ?? null),
    ];

    if ($editingCriminal) {
        Criminal::updateProfile($editId, $payload);
        $criminalId = $editId;
        $message = 'Criminal record updated successfully.';
    } else {
        $saved = Criminal::saveOrUpdateByNationalId($payload);
        $criminalId = (int) $saved['id'];
        $message = $saved['existing']
            ? 'Existing criminal record updated successfully.'
            : 'Criminal record created successfully.';
    }

    $priorCases = Criminal::priorCaseCount($criminalId, $reportId > 0 ? $reportId : null);

    if ($reportId > 0) {
        $linked = Criminal::linkToReport($criminalId, $reportId, (int) $user['id'], $recordStatus, $notes !== '' ? $notes : null);
        $report = CrimeReport::find($reportId);

        if ($report) {
            CaseUpdate::create(
                $reportId,
                (int) $user['id'],
                $user['full_name'],
                $user['role'],
                $report['status'],
                'Criminal registry updated for ' . $fullName . ' (' . $recordStatus . ').',
                false
            );
        }

        $message .= $linked ? ' Case link added.' : ' Existing case link refreshed.';
    }

    if ($priorCases > 0) {
        $message .= ' This person has previous case history: ' . $priorCases . ' previous case(s).';
    }

    flash('success', $message);
    redirect($reportId > 0 ? 'criminals.php?report_id=' . $reportId : 'criminals.php');
}

$filters = [
    'keyword' => trim($_GET['keyword'] ?? ''),
];
$criminals = Criminal::searchAll($filters);
$pageTitle = 'Criminal Registry';
$formCriminal = $editCriminal;

require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --registry-bg: linear-gradient(135deg, #7c2d12 0%, #be123c 100%);
        --registry-soft: #fff7ed;
        --registry-line: #fed7aa;
    }

    .registry-hero {
        background: var(--registry-bg);
        color: white;
        padding: 40px;
        border-radius: 26px;
        margin-bottom: 28px;
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: center;
    }

    .registry-grid {
        display: grid;
        grid-template-columns: 1.4fr 0.9fr;
        gap: 24px;
        margin-bottom: 24px;
    }

    .registry-panel {
        background: white;
        border-radius: 24px;
        padding: 24px;
        border: 1px solid var(--registry-line);
    }

    .criminal-table {
        display: grid;
        gap: 14px;
    }

    .criminal-row {
        display: grid;
        grid-template-columns: 90px 1.3fr 1fr 1fr auto;
        gap: 16px;
        align-items: center;
        background: var(--registry-soft);
        border: 1px solid var(--registry-line);
        border-radius: 20px;
        padding: 16px;
    }

    .criminal-photo {
        width: 90px;
        height: 110px;
        object-fit: cover;
        border-radius: 16px;
        border: 3px solid white;
        box-shadow: 0 12px 20px rgba(124, 45, 18, 0.14);
        background: #fff;
    }

    .criminal-photo-placeholder {
        width: 90px;
        height: 110px;
        border-radius: 16px;
        display: grid;
        place-items: center;
        background: linear-gradient(135deg, #fed7aa, #fecaca);
        color: #9a3412;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .criminal-name {
        display: grid;
        gap: 6px;
    }

    .criminal-name strong {
        color: #be123c;
        font-size: 18px;
    }

    .criminal-stack {
        display: grid;
        gap: 6px;
        color: #7c2d12;
        font-size: 14px;
    }

    .criminal-actions {
        display: grid;
        gap: 10px;
        justify-items: end;
    }

    @media (max-width: 980px) {
        .registry-grid {
            grid-template-columns: 1fr;
        }

        .criminal-row {
            grid-template-columns: 1fr;
        }

        .criminal-actions {
            justify-items: stretch;
        }
    }
</style>

<section class="registry-hero">
    <div>
        <p class="section-label" style="color: white;">Registry</p>
        <h2 style="margin: 0;">Criminal Records</h2>
        <p style="margin: 10px 0 0; color: rgba(255,255,255,0.84);">View all criminal records in one place, then open full details for each person.</p>
    </div>
    <div class="hero-actions">
        <?php if ($contextReport): ?>
            <a class="button-secondary" href="report_view.php?id=<?= e((string) $contextReport['id']) ?>">Back to Case</a>
        <?php endif; ?>
    </div>
</section>

<section class="registry-grid">
    <article class="registry-panel">
        <div class="panel-header">
            <div>
                <p class="section-label" style="color:#be123c;">Record Intake</p>
                <h2><?= $contextReport ? 'Add Criminal to ' . e($contextReport['reference_no']) : 'Register or Update Criminal' ?></h2>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" class="form-grid">
            <input type="hidden" name="action" value="save_criminal">
            <input type="hidden" name="report_id" value="<?= e((string) ($contextReport['id'] ?? 0)) ?>">
            <input type="hidden" name="edit_id" value="<?= e((string) ($formCriminal['id'] ?? 0)) ?>">

            <div>
                <label for="full_name">Full Name</label>
                <input id="full_name" name="full_name" type="text" value="<?= e(old('full_name', (string) ($formCriminal['full_name'] ?? ''))) ?>" required>
            </div>
            <div>
                <label for="national_id">National ID</label>
                <input id="national_id" name="national_id" type="text" value="<?= e(old('national_id', (string) ($formCriminal['national_id'] ?? ''))) ?>" required>
            </div>
            <div>
                <label for="phone_number">Phone Number</label>
                <input id="phone_number" name="phone_number" type="text" value="<?= e(old('phone_number', (string) ($formCriminal['phone_number'] ?? ''))) ?>">
            </div>
            <div>
                <label for="date_of_birth">Date of Birth</label>
                <input id="date_of_birth" name="date_of_birth" type="date" value="<?= e(old('date_of_birth', (string) ($formCriminal['date_of_birth'] ?? ''))) ?>">
            </div>
            <div>
                <label for="height_cm">Height (cm)</label>
                <input id="height_cm" name="height_cm" type="number" min="0" value="<?= e(old('height_cm', (string) ($formCriminal['height_cm'] ?? ''))) ?>">
            </div>
            <div class="full-span">
                <label for="record_status">Status</label>
                <select id="record_status" name="record_status">
                    <?php $selectedRecordStatus = old('record_status', 'Suspect'); ?>
                    <option value="Suspect" <?= $selectedRecordStatus === 'Suspect' ? 'selected' : '' ?>>Suspect</option>
                    <option value="Arrested" <?= $selectedRecordStatus === 'Arrested' ? 'selected' : '' ?>>Arrested</option>
                    <option value="Wanted" <?= $selectedRecordStatus === 'Wanted' ? 'selected' : '' ?>>Wanted</option>
                    <option value="Released" <?= $selectedRecordStatus === 'Released' ? 'selected' : '' ?>>Released</option>
                    <option value="Convicted" <?= $selectedRecordStatus === 'Convicted' ? 'selected' : '' ?>>Convicted</option>
                </select>
            </div>
            <div>
                <label for="photo">Photo</label>
                <input id="photo" name="photo" type="file" accept="image/*">
            </div>
            <div class="full-span">
                <label for="address">Address</label>
                <input id="address" name="address" type="text" value="<?= e(old('address', (string) ($formCriminal['address'] ?? ''))) ?>">
            </div>
            <div class="full-span">
                <label for="identifying_marks">Identifying Marks</label>
                <textarea id="identifying_marks" name="identifying_marks" rows="3"><?= e(old('identifying_marks', (string) ($formCriminal['identifying_marks'] ?? ''))) ?></textarea>
            </div>
            <div class="full-span">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="5"><?= e(old('notes', (string) ($formCriminal['notes'] ?? ''))) ?></textarea>
            </div>
            <div class="full-span action-row">
                <button class="button-primary" type="submit"><?= $formCriminal ? 'Update Criminal Record' : 'Save Criminal Record' ?></button>
                <?php if ($formCriminal): ?>
                    <a class="button-secondary" href="<?= $contextReport ? 'criminals.php?report_id=' . e((string) $contextReport['id']) : 'criminals.php' ?>">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </article>

    <article class="registry-panel">
        <div class="panel-header">
            <div>
                <p class="section-label" style="color:#be123c;">Search</p>
                <h2>Find Existing Record</h2>
            </div>
        </div>

        <form method="GET" class="auth-form">
            <?php if ($contextReport): ?>
                <input type="hidden" name="report_id" value="<?= e((string) $contextReport['id']) ?>">
            <?php endif; ?>
            <div>
                <label for="keyword">Keyword</label>
                <input id="keyword" name="keyword" type="text" value="<?= e($filters['keyword']) ?>" placeholder="Name, ID, phone">
            </div>
            <button class="button-secondary" type="submit">Search Registry</button>
        </form>
    </article>
</section>

<section class="registry-panel">
    <div class="panel-header">
        <div>
            <p class="section-label" style="color:#be123c;">Registry Archive</p>
            <h2>Registered Criminals</h2>
        </div>
    </div>

    <?php if (!$criminals): ?>
        <p class="empty-state">No criminal records found yet.</p>
    <?php endif; ?>

    <div class="criminal-table">
    <?php foreach ($criminals as $criminal): ?>
        <article class="criminal-row">
            <div>
                <?php if (!empty($criminal['photo_stored_file_name'])): ?>
                    <img class="criminal-photo" src="download_file.php?type=criminal_photo&id=<?= e((string) $criminal['id']) ?>" alt="<?= e($criminal['full_name']) ?>">
                <?php else: ?>
                    <div class="criminal-photo-placeholder"><?= e(substr($criminal['full_name'], 0, 2)) ?></div>
                <?php endif; ?>
            </div>

            <div class="criminal-name">
                <strong><?= e($criminal['full_name']) ?></strong>
                <span style="font-weight:700; color:#7c2d12;">ID: <?= e($criminal['national_id']) ?></span>
                <span style="color:#9a3412;">DOB: <?= e(format_display_date($criminal['date_of_birth'] ?? null)) ?></span>
            </div>

            <div class="criminal-stack">
                <span>Phone: <?= e((string) ($criminal['phone_number'] ?: '-')) ?></span>
                <span>Height: <?= e($criminal['height_cm'] ? $criminal['height_cm'] . ' cm' : '-') ?></span>
                <span>Updated: <?= e($criminal['updated_at']) ?></span>
            </div>

            <div class="criminal-stack">
                <span>Total cases: <?= e((string) $criminal['case_count']) ?></span>
                <span><?= (int) $criminal['case_count'] > 1 ? 'Has previous records' : 'No previous records' ?></span>
                <span><?= e($criminal['address'] ?: 'No address recorded') ?></span>
            </div>

            <div class="criminal-actions">
                <span class="badge"><?= (int) $criminal['case_count'] > 1 ? 'Has Previous Cases' : 'Registry Record' ?></span>
                <a class="button-primary" href="criminal_view.php?id=<?= e((string) $criminal['id']) ?>">View Details</a>
                <a class="button-secondary" href="<?= $contextReport ? 'criminals.php?report_id=' . e((string) $contextReport['id']) . '&edit_id=' . e((string) $criminal['id']) : 'criminals.php?edit_id=' . e((string) $criminal['id']) ?>">Edit</a>
                <form method="POST" onsubmit="return confirm('Delete this criminal record? This will also remove its case links.');">
                    <input type="hidden" name="action" value="delete_criminal">
                    <input type="hidden" name="criminal_id" value="<?= e((string) $criminal['id']) ?>">
                    <input type="hidden" name="report_id" value="<?= e((string) ($contextReport['id'] ?? 0)) ?>">
                    <button class="button-secondary" type="submit" style="border-color:#dc2626; color:#dc2626;">Delete</button>
                </form>
                <?php if (!empty($criminal['photo_stored_file_name'])): ?>
                    <a class="button-secondary" href="download_file.php?type=criminal_photo&id=<?= e((string) $criminal['id']) ?>" target="_blank">Open Photo</a>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
