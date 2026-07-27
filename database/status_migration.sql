USE crime_tracking_system;

ALTER TABLE crime_reports
    MODIFY COLUMN status ENUM('Submitted', 'Under Review', 'Assigned', 'Investigating', 'Pending', 'On Going', 'Closed') NOT NULL DEFAULT 'Pending';

UPDATE crime_reports
SET status = CASE
    WHEN status IN ('Submitted', 'Under Review') THEN 'Pending'
    WHEN status IN ('Assigned', 'Investigating') THEN 'On Going'
    WHEN status = 'Closed' THEN 'Closed'
    ELSE 'Pending'
END;

ALTER TABLE crime_reports
    MODIFY COLUMN status ENUM('Pending', 'On Going', 'Closed') NOT NULL DEFAULT 'Pending';
