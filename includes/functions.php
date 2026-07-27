<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function login_user(array $user): void
{
    $_SESSION['auth_user'] = [
        'id' => (int) $user['id'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'phone' => $user['phone'] ?? '',
        'role' => $user['role'],
        'badge_number' => $user['badge_number'] ?? null,
    ];
}

function logout_user(): void
{
    unset($_SESSION['auth_user']);
}

function current_user(): ?array
{
    return $_SESSION['auth_user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Please login to continue.');
        redirect('login.php');
    }
}

function require_role(string ...$roles): void
{
    require_login();
    $user = current_user();

    if (!$user || !in_array($user['role'], $roles, true)) {
        flash('error', 'You are not allowed to access that page.');
        redirect('index.php');
    }
}

function current_admin_id(): int
{
    $user = current_user();
    return $user ? (int) $user['id'] : 0;
}

function current_officer_id(): int
{
    $user = current_user();
    return $user ? (int) $user['id'] : 0;
}

function generate_reference(): string
{
    return 'CR-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

function old(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function can_access_report(array $user, array $report): bool
{
    if ($user['role'] === 'admin') {
        return true;
    }

    if ($user['role'] === 'officer') {
        return (int) ($report['assigned_officer_id'] ?? 0) === (int) $user['id'];
    }

    return ($report['citizen_email'] ?? '') === ($user['email'] ?? '');
}

function report_statuses(): array
{
    return ['Pending', 'On Going', 'Closed'];
}

function system_service_area_name(): string
{
    return 'Bosaso';
}

function normalize_service_area(string $value): ?string
{
    $normalized = strtolower(trim($value));
    $normalized = str_replace([' ', '-'], '', $normalized);

    if (in_array($normalized, ['bosaso', 'bosaaso', 'boosaaso'], true)) {
        return system_service_area_name();
    }

    return null;
}

function system_accepts_service_area(string $value): bool
{
    return normalize_service_area($value) !== null;
}

function default_dashboard_for_role(string $role): string
{
    if ($role === 'admin') {
        return 'admin_dashboard.php';
    }

    if ($role === 'officer') {
        return 'officer_portal.php';
    }

    return 'citizen_dashboard.php';
}

function back_dashboard_path(): string
{
    $user = current_user();
    if (!$user) {
        return 'index.php';
    }

    return default_dashboard_for_role($user['role']);
}

function normalise_report_status(string $status): string
{
    $map = [
        'Submitted' => 'Pending',
        'Under Review' => 'Pending',
        'Assigned' => 'On Going',
        'Investigating' => 'On Going',
        'Closed' => 'Closed',
        'Pending' => 'Pending',
        'On Going' => 'On Going',
    ];

    return $map[$status] ?? 'Pending';
}

function upload_base_path(string $folder): string
{
    return __DIR__ . '/../uploads/' . trim($folder, '/');
}

function normalize_uploaded_files(string $fieldName): array
{
    if (!isset($_FILES[$fieldName])) {
        return [];
    }

    $files = $_FILES[$fieldName];
    if (!is_array($files['name'] ?? null)) {
        return [$files];
    }

    $normalized = [];
    $total = count($files['name']);

    for ($index = 0; $index < $total; $index++) {
        $normalized[] = [
            'name' => $files['name'][$index] ?? '',
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];
    }

    return $normalized;
}

function normalize_uploaded_files_from_fields(array $fieldNames): array
{
    foreach ($fieldNames as $fieldName) {
        $files = normalize_uploaded_files($fieldName);
        if ($files) {
            return $files;
        }
    }

    return [];
}

function filename_without_extension(string $filename): string
{
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = trim(preg_replace('/[_-]+/', ' ', $name) ?? '');

    return $name !== '' ? $name : 'File';
}

function default_upload_title_from_files(array $files, string $multipleFallback = 'Evidence Files'): string
{
    if (!$files) {
        return '';
    }

    if (count($files) === 1) {
        return filename_without_extension((string) ($files[0]['name'] ?? 'File'));
    }

    return $multipleFallback;
}

function ini_size_to_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }

    $number = (float) $value;
    $unit = strtolower(substr($value, -1));

    switch ($unit) {
        case 'g':
            $number *= 1024;
            // no break
        case 'm':
            $number *= 1024;
            // no break
        case 'k':
            $number *= 1024;
    }

    return (int) round($number);
}

function effective_upload_limit_bytes(int $requestedMaxBytes): int
{
    $limits = [$requestedMaxBytes];

    foreach (['upload_max_filesize', 'post_max_size'] as $setting) {
        $bytes = ini_size_to_bytes((string) ini_get($setting));
        if ($bytes > 0) {
            $limits[] = $bytes;
        }
    }

    return min($limits);
}

function upload_error_message(int $errorCode, int $maxBytes): string
{
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'Uploaded file is too large. Maximum allowed is ' . format_file_size($maxBytes) . '.';
        case UPLOAD_ERR_PARTIAL:
            return 'The file was only partially uploaded. If it is stored in the cloud, download it to this computer first, then try again.';
        case UPLOAD_ERR_NO_FILE:
            return 'Please choose a file to upload. If your file is stored in the cloud, download it to this computer first.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Upload failed because the server temporary folder is missing.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Upload failed because the server could not write the file.';
        case UPLOAD_ERR_EXTENSION:
            return 'Upload was stopped by a server extension.';
        default:
            return 'File upload failed. If the file is stored in OneDrive, Google Drive, iCloud, or another cloud location, download it to this computer first and then try again.';
    }
}

function allowed_upload_extensions(): array
{
    return [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp',
        'heic', 'heif',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'rtf', 'ppt', 'pptx', 'pps', 'ppsx', 'odt', 'ods', 'odp', 'json', 'xml', 'md', 'log',
        'mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac',
        'mp4', 'mov', 'avi', 'webm', 'mkv', '3gp', 'mpeg',
        'zip', 'rar', '7z',
    ];
}

function detect_upload_mime_type(string $path, ?string $fallback = null): string
{
    if (is_file($path) && function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $detected = finfo_file($finfo, $path);
            finfo_close($finfo);

            if (is_string($detected) && $detected !== '') {
                return $detected;
            }
        }
    }

    return $fallback ?: 'application/octet-stream';
}

function detect_file_category(?string $mimeType, string $filename): string
{
    $mimeType = strtolower((string) $mimeType);
    $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

    if (str_starts_with($mimeType, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic', 'heif'], true)) {
        return 'Image';
    }

    if (str_starts_with($mimeType, 'audio/') || in_array($extension, ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'], true)) {
        return 'Audio';
    }

    if (str_starts_with($mimeType, 'video/') || in_array($extension, ['mp4', 'mov', 'avi', 'webm', 'mkv', '3gp', 'mpeg'], true)) {
        return 'Video';
    }

    if (in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'rtf', 'ppt', 'pptx', 'pps', 'ppsx', 'odt', 'ods', 'odp', 'json', 'xml', 'md', 'log'], true)) {
        return 'Document';
    }

    if (in_array($extension, ['zip', 'rar', '7z'], true)) {
        return 'Archive';
    }

    return 'File';
}

function evidence_file_accept_attribute(): string
{
    return implode(',', [
        'image/*',
        'audio/*',
        'video/*',
        '.pdf',
        '.doc',
        '.docx',
        '.xls',
        '.xlsx',
        '.csv',
        '.txt',
        '.rtf',
        '.ppt',
        '.pptx',
        '.pps',
        '.ppsx',
        '.odt',
        '.ods',
        '.odp',
        '.json',
        '.xml',
        '.md',
        '.log',
        '.zip',
        '.rar',
        '.7z',
    ]);
}

function upload_preview_kind(?string $mimeType, ?string $filename = null): string
{
    $mimeType = strtolower((string) $mimeType);
    $extension = strtolower((string) pathinfo((string) $filename, PATHINFO_EXTENSION));

    if (str_starts_with($mimeType, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic', 'heif'], true)) {
        return 'image';
    }

    if (str_starts_with($mimeType, 'audio/') || in_array($extension, ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'], true)) {
        return 'audio';
    }

    if (str_starts_with($mimeType, 'video/') || in_array($extension, ['mp4', 'mov', 'avi', 'webm', 'mkv', '3gp', 'mpeg'], true)) {
        return 'video';
    }

    if ($mimeType === 'application/pdf' || $extension === 'pdf') {
        return 'pdf';
    }

    return 'none';
}

function should_stream_upload_inline(?string $mimeType, ?string $filename = null): bool
{
    return upload_preview_kind($mimeType, $filename) !== 'none';
}

function store_uploaded_file(array $file, string $folder, string $prefix, int $maxBytes): array
{
    $effectiveMaxBytes = effective_upload_limit_bytes($maxBytes);
    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($uploadError !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message($uploadError, $effectiveMaxBytes));
    }

    if ((int) ($file['size'] ?? 0) > $effectiveMaxBytes) {
        throw new RuntimeException('Uploaded file is too large. Maximum allowed is ' . format_file_size($effectiveMaxBytes) . '.');
    }

    $originalFileName = basename((string) ($file['name'] ?? 'file'));
    $extension = strtolower((string) pathinfo($originalFileName, PATHINFO_EXTENSION));

    if ($extension === '' || !in_array($extension, allowed_upload_extensions(), true)) {
        throw new RuntimeException('This file type is not allowed.');
    }

    $uploadDirectory = upload_base_path($folder);
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0777, true) && !is_dir($uploadDirectory)) {
        throw new RuntimeException('Unable to prepare upload storage.');
    }

    $storedFileName = uniqid($prefix, true) . '.' . $extension;
    $targetPath = $uploadDirectory . '/' . $storedFileName;

    if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Unable to save the uploaded file.');
    }

    $mimeType = detect_upload_mime_type($targetPath, (string) ($file['type'] ?? null));

    return [
        'original_file_name' => $originalFileName,
        'stored_file_name' => $storedFileName,
        'mime_type' => $mimeType,
        'file_category' => detect_file_category($mimeType, $originalFileName),
        'file_size' => (int) ($file['size'] ?? 0),
        'absolute_path' => $targetPath,
    ];
}

function format_file_size(?int $bytes): string
{
    $bytes = max(0, (int) $bytes);

    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / (1024 * 1024), 1) . ' MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }

    return $bytes . ' B';
}

function format_display_date(?string $value, string $fallback = '-'): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return $fallback;
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('d M Y', $timestamp);
}

function db_table_has_column(string $table, string $column): bool
{
    static $cache = [];
    $cacheKey = $table . '.' . $column;

    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
        $cache[$cacheKey] = false;
        return false;
    }

    $statement = db()->prepare(
        'SELECT COUNT(*)
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name'
    );
    $statement->execute([
        'table_name' => $table,
        'column_name' => $column,
    ]);

    $cache[$cacheKey] = (int) $statement->fetchColumn() > 0;
    return $cache[$cacheKey];
}

function db_table_exists(string $table): bool
{
    static $cache = [];

    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        $cache[$table] = false;
        return false;
    }

    $statement = db()->prepare(
        'SELECT COUNT(*)
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name'
    );
    $statement->execute(['table_name' => $table]);

    $cache[$table] = (int) $statement->fetchColumn() > 0;
    return $cache[$table];
}

function ensure_case_management_schema(): void
{
    static $ensured = false;

    if ($ensured) {
        return;
    }

    $pdo = db();

    if (db_table_exists('evidences')) {
        if (!db_table_has_column('evidences', 'file_category')) {
            $pdo->exec("ALTER TABLE evidences ADD COLUMN file_category VARCHAR(40) DEFAULT NULL AFTER mime_type");
        }

        if (!db_table_has_column('evidences', 'file_size')) {
            $pdo->exec("ALTER TABLE evidences ADD COLUMN file_size INT DEFAULT NULL AFTER file_category");
        }
    }

    if (!db_table_exists('officer_case_files')) {
        $pdo->exec(
            "CREATE TABLE officer_case_files (
                id INT AUTO_INCREMENT PRIMARY KEY,
                report_id INT NOT NULL,
                officer_id INT NOT NULL,
                title VARCHAR(160) NOT NULL,
                description TEXT DEFAULT NULL,
                original_file_name VARCHAR(255) NOT NULL,
                stored_file_name VARCHAR(255) NOT NULL,
                mime_type VARCHAR(120) DEFAULT NULL,
                file_category VARCHAR(40) DEFAULT NULL,
                file_size INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_officer_file_report FOREIGN KEY (report_id) REFERENCES crime_reports(id) ON DELETE CASCADE,
                CONSTRAINT fk_officer_file_officer FOREIGN KEY (officer_id) REFERENCES users(id) ON DELETE CASCADE
            )"
        );
    }

    if (!db_table_exists('criminals')) {
        $pdo->exec(
            "CREATE TABLE criminals (
                id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(160) NOT NULL,
                phone_number VARCHAR(40) DEFAULT NULL,
                national_id VARCHAR(80) NOT NULL UNIQUE,
                date_of_birth DATE DEFAULT NULL,
                height_cm INT DEFAULT NULL,
                address VARCHAR(255) DEFAULT NULL,
                identifying_marks TEXT DEFAULT NULL,
                notes TEXT DEFAULT NULL,
                photo_original_file_name VARCHAR(255) DEFAULT NULL,
                photo_stored_file_name VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        );
    } else {
        if (!db_table_has_column('criminals', 'date_of_birth')) {
            $pdo->exec("ALTER TABLE criminals ADD COLUMN date_of_birth DATE DEFAULT NULL AFTER national_id");
        }

        if (!db_table_has_column('criminals', 'photo_original_file_name')) {
            $pdo->exec("ALTER TABLE criminals ADD COLUMN photo_original_file_name VARCHAR(255) DEFAULT NULL AFTER notes");
        }

        if (!db_table_has_column('criminals', 'photo_stored_file_name')) {
            $pdo->exec("ALTER TABLE criminals ADD COLUMN photo_stored_file_name VARCHAR(255) DEFAULT NULL AFTER photo_original_file_name");
        }
    }

    if (!db_table_exists('criminal_case_links')) {
        $pdo->exec(
            "CREATE TABLE criminal_case_links (
                id INT AUTO_INCREMENT PRIMARY KEY,
                criminal_id INT NOT NULL,
                report_id INT NOT NULL,
                linked_by_user_id INT DEFAULT NULL,
                record_status VARCHAR(80) NOT NULL DEFAULT 'Suspect',
                case_notes TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_criminal_case (criminal_id, report_id),
                CONSTRAINT fk_criminal_link_criminal FOREIGN KEY (criminal_id) REFERENCES criminals(id) ON DELETE CASCADE,
                CONSTRAINT fk_criminal_link_report FOREIGN KEY (report_id) REFERENCES crime_reports(id) ON DELETE CASCADE,
                CONSTRAINT fk_criminal_link_user FOREIGN KEY (linked_by_user_id) REFERENCES users(id) ON DELETE SET NULL
            )"
        );
    }

    $ensured = true;
}
