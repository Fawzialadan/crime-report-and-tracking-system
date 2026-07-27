<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$type = trim($_GET['type'] ?? '');
$id = (int) ($_GET['id'] ?? 0);

if (!$user || $id <= 0) {
    flash('error', 'Invalid file request.');
    redirect(back_dashboard_path());
}

$filePath = null;
$downloadName = null;
$mimeType = 'application/octet-stream';

if ($type === 'evidence') {
    $file = Evidence::find($id);
    $report = $file ? CrimeReport::find((int) $file['report_id']) : null;

    if (!$file || !$report || !can_access_report($user, $report)) {
        flash('error', 'You are not allowed to access that evidence file.');
        redirect(back_dashboard_path());
    }

    $filePath = upload_base_path('evidence') . '/' . $file['stored_file_name'];
    $downloadName = $file['original_file_name'];
    $mimeType = $file['mime_type'] ?: $mimeType;
} elseif ($type === 'officer_file') {
    require_role('officer');
    $file = OfficerCaseFile::find($id);

    if (!$file || (int) $file['officer_id'] !== (int) $user['id']) {
        flash('error', 'You are not allowed to access that private file.');
        redirect('officer_files.php');
    }

    $folder = 'officer_files/officer_' . (int) $file['officer_id'] . '/case_' . (int) $file['report_id'];
    $filePath = upload_base_path($folder) . '/' . $file['stored_file_name'];
    $downloadName = $file['original_file_name'];
    $mimeType = $file['mime_type'] ?: $mimeType;
} elseif ($type === 'criminal_photo') {
    require_role('admin', 'officer');
    $criminal = Criminal::find($id);

    if (!$criminal || empty($criminal['photo_stored_file_name'])) {
        flash('error', 'Criminal photo was not found.');
        redirect('criminals.php');
    }

    $filePath = upload_base_path('criminals') . '/' . $criminal['photo_stored_file_name'];
    $downloadName = $criminal['photo_original_file_name'] ?: ('criminal-' . $criminal['id'] . '.jpg');
    $mimeType = detect_upload_mime_type($filePath, 'image/jpeg');
} else {
    flash('error', 'Unsupported file request.');
    redirect(back_dashboard_path());
}

if (!$filePath || !is_file($filePath)) {
    flash('error', 'Requested file could not be found.');
    redirect(back_dashboard_path());
}

$disposition = should_stream_upload_inline($mimeType, $downloadName) ? 'inline' : 'attachment';

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . (string) filesize($filePath));
header('Content-Disposition: ' . $disposition . '; filename="' . addslashes((string) $downloadName) . '"');
readfile($filePath);
exit;
