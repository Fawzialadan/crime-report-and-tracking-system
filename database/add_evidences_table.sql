USE crime_tracking_system;

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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evidence_report FOREIGN KEY (report_id) REFERENCES crime_reports(id) ON DELETE CASCADE,
    CONSTRAINT fk_evidence_user FOREIGN KEY (submitted_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);
