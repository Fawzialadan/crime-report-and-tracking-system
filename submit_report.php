<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();

// ... (Koodhkaaga POST Logic-ga ah sidiisii ha u ahaado)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $citizenName = trim($_POST['citizen_name'] ?? '');
    $citizenEmail = trim($_POST['citizen_email'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $crimeType = trim($_POST['crime_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $rawDistrict = trim($_POST['district'] ?? system_service_area_name());
    $district = normalize_service_area($rawDistrict) ?? '';
    $incidentLocation = trim($_POST['incident_location'] ?? '');
    $occurredAt = trim($_POST['occurred_at'] ?? '');

    if (!system_accepts_service_area($rawDistrict)) {
        flash('error', 'This system accepts crime reports from Bosaso only.');
        redirect('submit_report.php');
    }

    if ($citizenName === '' || $citizenEmail === '' || $title === '' || $crimeType === '' || $description === '' || $district === '' || $incidentLocation === '' || $occurredAt === '') {
        flash('error', 'Please complete all required report fields.');
        redirect('submit_report.php');
    }

    $reference = generate_reference();
    $reportId = CrimeReport::create([
        'reference_no' => $reference,
        'citizen_name' => $citizenName,
        'citizen_email' => $citizenEmail,
        'citizen_phone' => trim($_POST['citizen_phone'] ?? ''),
        'title' => $title,
        'crime_type' => $crimeType,
        'description' => $description,
        'district' => $district,
        'incident_location' => $incidentLocation,
        'occurred_at' => $occurredAt,
        'status' => 'Pending',
    ]);

    CaseUpdate::create($reportId, $user ? (int) $user['id'] : null, $citizenName, 'citizen', 'Pending', 'Citizen submitted a new crime report and the case is now pending review.');
    Notification::create($reportId, $citizenName, 'citizen', $citizenEmail, 'Report submitted successfully. Reference: ' . $reference . '. Current status: Pending.');
    Notification::create($reportId, 'System Admin', 'admin', 'admin@crime.local', 'A new pending report requires review. Reference: ' . $reference);

    flash('success', 'Crime report submitted successfully. Reference: ' . $reference . '. Status: Pending.');
    redirect('submit_report.php');
}

$pageTitle = 'Submit Crime Report';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --dark-bg: #0f172a;
        --card-bg: #1e293b;
        --accent-pink: #ec4899;
        --pink-hover: #db2777;
        --text-gray: #94a3b8;
        --input-border: #334155;
    }

    /* Hero Section */
    .hero-panel {
        background: linear-gradient(135deg, #121212 0%, #2d1b24 100%);
        padding: 45px;
        border-radius: 25px;
        color: white;
        margin-bottom: 30px;
        border: 1px solid var(--input-border);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
    }

    .section-label {
        color: var(--accent-pink);
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 2px;
        margin-bottom: 10px;
    }

    /* Panel & Form */
    .panel {
        background: var(--card-bg);
        border: 1px solid var(--input-border);
        border-radius: 20px;
        padding: 40px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 25px;
    }

    .full-span {
        grid-column: span 3;
    }

    .two-span {
        grid-column: span 2;
    }

    label {
        display: block;
        color: #f1f5f9;
        font-size: 0.9rem;
        margin-bottom: 10px;
        font-weight: 600;
    }

    input,
    textarea,
    select {
        width: 100%;
        padding: 14px;
        background: #0f172a;
        border: 1px solid var(--input-border);
        border-radius: 12px;
        color: white;
        font-size: 0.95rem;
        transition: 0.3s ease;
    }

    input:focus,
    textarea:focus {
        border-color: var(--accent-pink);
        outline: none;
        box-shadow: 0 0 0 4px rgba(236, 72, 153, 0.15);
    }

    textarea {
        resize: vertical;
    }

    /* Buttons */
    .button-primary {
        background: var(--accent-pink);
        color: white;
        padding: 15px 35px;
        border-radius: 12px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: 0.3s;
    }

    .button-primary:hover {
        background: var(--pink-hover);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(236, 72, 153, 0.4);
    }

    .button-secondary {
        background: transparent;
        color: white;
        border: 1px solid var(--input-border);
        padding: 15px 35px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        transition: 0.3s;
    }

    .button-secondary:hover {
        border-color: var(--accent-pink);
        color: var(--accent-pink);
    }

    .action-row {
        margin-top: 20px;
        display: flex;
        gap: 15px;
    }
</style>

<section class="hero-panel">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <p class="section-label">Incident Intake</p>
            <h2 style="font-size: 2.3rem; margin: 10px 0;">Submit a New Crime Report</h2>
            <p style="color: var(--text-gray); font-size: 1.1rem;">Fadlan buuxi foomka si aad u gudbiso dacwadaada. System-kan wuxuu u shaqeeyaa Bosaso keliya.</p>
        </div>
        <div class="hero-actions">
            <a class="button-secondary" href="<?= e(back_dashboard_path()) ?>">⬅️ Back Dashboard</a>
        </div>
    </div>
</section>

<section class="panel">
    <div class="panel-header" style="margin-bottom: 35px;">
        <p class="section-label">Case Form</p>
        <h2 style="color: white;">New Case Submission Form</h2>
        <p style="color: var(--text-gray); font-size: 0.9rem;">Warbixintaadu waxay noqon doontaa mid qarsoodi ah oo ammaan ah. Adeeggan waxaa lagu xaddiday magaalada Bosaso.</p>
    </div>

    <form class="form-grid" method="POST">
        <div>
            <label for="citizen_name">Citizen Name</label>
            <input id="citizen_name" name="citizen_name" type="text" value="<?= e(old('citizen_name', $user['full_name'] ?? '')) ?>" required>
        </div>
        <div>
            <label for="citizen_email">Citizen Email</label>
            <input id="citizen_email" name="citizen_email" type="email" value="<?= e(old('citizen_email', $user['email'] ?? '')) ?>" required>
        </div>
        <div>
            <label for="citizen_phone">Citizen Phone</label>
            <input id="citizen_phone" name="citizen_phone" type="text" value="<?= e(old('citizen_phone', $user['phone'] ?? '')) ?>">
        </div>

        <div class="two-span">
            <label for="title">Case Title (Cinwaanka Dacwada)</label>
            <input id="title" name="title" type="text" placeholder="Tusaale: Mobile la xaday" value="<?= e(old('title')) ?>" required>
        </div>
        <div>
            <label for="crime_type">Crime Type</label>
            <input id="crime_type" name="crime_type" type="text" placeholder="Robbery, Assault, Fraud..." value="<?= e(old('crime_type')) ?>" required>
        </div>

        <div>
            <label for="district">District</label>
            <input id="district" name="district" type="text" value="<?= e(system_service_area_name()) ?>" readonly required>
            <small style="display:block; margin-top:8px; color: var(--text-gray);">This reporting system accepts incidents from Bosaso only.</small>
        </div>
        <div class="two-span">
            <label for="incident_location">Incident Location (Goobta saxda ah)</label>
            <input id="incident_location" name="incident_location" type="text" placeholder="Jidka, Xaafadda, ama Calaamad gaar ah" value="<?= e(old('incident_location')) ?>" required>
        </div>

        <div class="full-span">
            <label for="occurred_at">Date & Time Occurred</label>
            <input id="occurred_at" name="occurred_at" type="datetime-local" style="width: 31%;" value="<?= e(old('occurred_at')) ?>" required>
        </div>

        <div class="full-span">
            <label for="description">Detailed Description</label>
            <textarea id="description" name="description" rows="6" placeholder="Fadlan si faahfaahsan u qeex waxa dhacay..." required><?= e(old('description')) ?></textarea>
        </div>

        <div class="full-span action-row">
            <button class="button-primary" type="submit">🚀 Submit Report</button>
            <a class="button-secondary" href="<?= e(back_dashboard_path()) ?>">Cancel</a>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>