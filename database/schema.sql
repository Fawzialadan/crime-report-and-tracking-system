CREATE DATABASE IF NOT EXISTS crime_tracking_system;
USE crime_tracking_system;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    phone VARCHAR(40) DEFAULT NULL,
    password_hash VARCHAR(255) DEFAULT NULL,
    role ENUM('citizen', 'admin', 'officer') NOT NULL,
    badge_number VARCHAR(60) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS crime_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_no VARCHAR(40) NOT NULL UNIQUE,
    citizen_name VARCHAR(120) NOT NULL,
    citizen_email VARCHAR(160) NOT NULL,
    citizen_phone VARCHAR(40) DEFAULT NULL,
    title VARCHAR(180) NOT NULL,
    crime_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    district VARCHAR(120) NOT NULL,
    incident_location VARCHAR(180) NOT NULL,
    occurred_at DATETIME NOT NULL,
    status ENUM('Pending', 'On Going', 'Closed') NOT NULL DEFAULT 'Pending',
    assigned_officer_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_report_officer FOREIGN KEY (assigned_officer_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    officer_id INT NOT NULL,
    admin_id INT DEFAULT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_assignment_report FOREIGN KEY (report_id) REFERENCES crime_reports(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignment_officer FOREIGN KEY (officer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignment_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS case_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    updated_by INT DEFAULT NULL,
    actor_name VARCHAR(120) NOT NULL,
    actor_role ENUM('citizen', 'admin', 'officer', 'system') NOT NULL DEFAULT 'system',
    status_after_update VARCHAR(80) NOT NULL,
    notes TEXT NOT NULL,
    citizen_visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_update_report FOREIGN KEY (report_id) REFERENCES crime_reports(id) ON DELETE CASCADE,
    CONSTRAINT fk_update_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    recipient_name VARCHAR(120) NOT NULL,
    recipient_role ENUM('citizen', 'admin', 'officer') NOT NULL,
    recipient_contact VARCHAR(160) DEFAULT NULL,
    message TEXT NOT NULL,
    channel VARCHAR(50) NOT NULL DEFAULT 'system',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_report FOREIGN KEY (report_id) REFERENCES crime_reports(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS evidences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    submitted_by_user_id INT DEFAULT NULL,
    submitted_by_role ENUM('citizen', 'officer', 'admin') NOT NULL,
    title VARCHAR(160) NOT NULL,
    description TEXT DEFAULT NULL,
    original_file_name VARCHAR(255) DEFAULT NULL,
    stored_file_name VARCHAR(255) DEFAULT NULL,
    mime_type VARCHAR(120) DEFAULT NULL,
    file_category VARCHAR(40) DEFAULT NULL,
    file_size INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evidence_report FOREIGN KEY (report_id) REFERENCES crime_reports(id) ON DELETE CASCADE,
    CONSTRAINT fk_evidence_user FOREIGN KEY (submitted_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS officer_case_files (
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
);

CREATE TABLE IF NOT EXISTS criminals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(160) NOT NULL,
    phone_number VARCHAR(40) DEFAULT NULL,
    national_id VARCHAR(80) NOT NULL UNIQUE,
    height_cm INT DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    identifying_marks TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    photo_original_file_name VARCHAR(255) DEFAULT NULL,
    photo_stored_file_name VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS criminal_case_links (
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
);
