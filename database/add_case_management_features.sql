ALTER TABLE evidences
    ADD COLUMN file_category VARCHAR(40) DEFAULT NULL AFTER mime_type,
    ADD COLUMN file_size INT DEFAULT NULL AFTER file_category;

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
