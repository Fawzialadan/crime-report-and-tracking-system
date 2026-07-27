USE crime_tracking_system;

INSERT INTO users (full_name, email, phone, role, badge_number)
VALUES
('System Admin', 'admin@gmail.com', '+252-61-555-0101', 'admin', NULL),
('Officer Asha Abdi', 'aisha@gmail.com', '+252-61-555-0102', 'officer', 'PO-102'),
('Officer Adnan Yasin', 'Adnan@gmail.com', '+252-61-555-0103', 'officer', 'PO-118'),
('Officer Najax Waberi', 'Najax@gmail.com', '+252-61-555-0104', 'officer', 'PO-126'),
('Naima Cali', 'naima@crime.local', '+252-61-555-0105', 'citizen', NULL)
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);
