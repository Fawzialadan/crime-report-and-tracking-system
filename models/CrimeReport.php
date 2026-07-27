<?php

declare(strict_types=1);

final class CrimeReport
{
    public static function create(array $data): int
    {
        $district = normalize_service_area((string) ($data['district'] ?? ''));
        if ($district === null) {
            throw new InvalidArgumentException('This system only accepts reports from Bosaso.');
        }

        $statement = db()->prepare(
            'INSERT INTO crime_reports (
                reference_no, citizen_name, citizen_email, citizen_phone, title, crime_type,
                description, district, incident_location, occurred_at, status
            ) VALUES (
                :reference_no, :citizen_name, :citizen_email, :citizen_phone, :title, :crime_type,
                :description, :district, :incident_location, :occurred_at, :status
            )'
        );

        $statement->execute([
            'reference_no' => $data['reference_no'],
            'citizen_name' => $data['citizen_name'],
            'citizen_email' => $data['citizen_email'],
            'citizen_phone' => $data['citizen_phone'],
            'title' => $data['title'],
            'crime_type' => $data['crime_type'],
            'description' => $data['description'],
            'district' => $district,
            'incident_location' => $data['incident_location'],
            'occurred_at' => $data['occurred_at'],
            'status' => $data['status'] ?? 'Pending',
        ]);

        return (int) db()->lastInsertId();
    }

    public static function all(): array
    {
        $statement = db()->query(
            'SELECT r.*, u.full_name AS officer_name
             FROM crime_reports r
             LEFT JOIN users u ON u.id = r.assigned_officer_id
             ORDER BY r.created_at DESC'
        );

        return $statement->fetchAll();
    }

    public static function summary(): array
    {
        return [
            'total_reports' => (int) db()->query('SELECT COUNT(*) FROM crime_reports')->fetchColumn(),
            'pending_reports' => (int) db()->query("SELECT COUNT(*) FROM crime_reports WHERE status = 'Pending'")->fetchColumn(),
            'on_going_reports' => (int) db()->query("SELECT COUNT(*) FROM crime_reports WHERE status = 'On Going'")->fetchColumn(),
            'closed_reports' => (int) db()->query("SELECT COUNT(*) FROM crime_reports WHERE status = 'Closed'")->fetchColumn(),
        ];
    }

    public static function summaryForCitizen(string $email): array
    {
        $statement = db()->prepare(
            "SELECT
                COUNT(*) AS total_reports,
                SUM(CASE WHEN status IN ('Pending', 'On Going') THEN 1 ELSE 0 END) AS open_reports,
                SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) AS closed_reports
             FROM crime_reports
             WHERE citizen_email = :email"
        );
        $statement->execute(['email' => $email]);
        $summary = $statement->fetch() ?: [];

        return [
            'total_reports' => (int) ($summary['total_reports'] ?? 0),
            'open_reports' => (int) ($summary['open_reports'] ?? 0),
            'closed_reports' => (int) ($summary['closed_reports'] ?? 0),
        ];
    }

    public static function summaryForOfficer(int $officerId): array
    {
        $statement = db()->prepare(
            "SELECT
                COUNT(*) AS total_reports,
                SUM(CASE WHEN status = 'On Going' THEN 1 ELSE 0 END) AS on_going_reports,
                SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) AS closed_reports
             FROM crime_reports
             WHERE assigned_officer_id = :officer_id"
        );
        $statement->execute(['officer_id' => $officerId]);
        $summary = $statement->fetch() ?: [];

        return [
            'total_reports' => (int) ($summary['total_reports'] ?? 0),
            'on_going_reports' => (int) ($summary['on_going_reports'] ?? 0),
            'closed_reports' => (int) ($summary['closed_reports'] ?? 0),
        ];
    }

    public static function find(int $id): ?array
    {
        $statement = db()->prepare(
            'SELECT r.*, u.full_name AS officer_name, u.email AS officer_email, u.phone AS officer_phone
             FROM crime_reports r
             LEFT JOIN users u ON u.id = r.assigned_officer_id
             WHERE r.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $report = $statement->fetch();

        return $report ?: null;
    }

    public static function updateStatus(int $reportId, string $status): void
    {
        $statement = db()->prepare('UPDATE crime_reports SET status = :status WHERE id = :id');
        $statement->execute([
            'status' => $status,
            'id' => $reportId,
        ]);
    }

    public static function assignedToOfficer(int $officerId): array
    {
        $statement = db()->prepare(
            'SELECT r.*, (
                SELECT COUNT(*)
                FROM evidences e
                WHERE e.report_id = r.id
            ) AS evidence_count
             FROM crime_reports r
             WHERE assigned_officer_id = :officer_id
             ORDER BY updated_at DESC, created_at DESC'
        );
        $statement->execute(['officer_id' => $officerId]);

        return $statement->fetchAll();
    }

    public static function forCitizenEmail(string $email): array
    {
        $statement = db()->prepare(
            'SELECT r.*, u.full_name AS officer_name
             FROM crime_reports r
             LEFT JOIN users u ON u.id = r.assigned_officer_id
             WHERE r.citizen_email = :email
             ORDER BY r.updated_at DESC, r.created_at DESC'
        );
        $statement->execute(['email' => $email]);

        return $statement->fetchAll();
    }

    public static function searchAll(array $filters = []): array
    {
        $sql = 'SELECT r.*, u.full_name AS officer_name
                FROM crime_reports r
                LEFT JOIN users u ON u.id = r.assigned_officer_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['keyword'])) {
            $sql .= ' AND (
                r.reference_no LIKE :keyword
                OR r.title LIKE :keyword
                OR r.citizen_name LIKE :keyword
                OR r.crime_type LIKE :keyword
                OR r.district LIKE :keyword
            )';
            $params['keyword'] = '%' . $filters['keyword'] . '%';
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND r.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['district'])) {
            $sql .= ' AND r.district LIKE :district';
            $params['district'] = '%' . $filters['district'] . '%';
        }

        if (!empty($filters['officer_id'])) {
            $sql .= ' AND r.assigned_officer_id = :officer_id';
            $params['officer_id'] = (int) $filters['officer_id'];
        }

        $sql .= ' ORDER BY r.updated_at DESC, r.created_at DESC';

        $statement = db()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public static function recentByStatus(string $status, int $limit = 6): array
    {
        $limit = max(1, $limit);
        $statement = db()->prepare(
            'SELECT r.*, u.full_name AS officer_name
             FROM crime_reports r
             LEFT JOIN users u ON u.id = r.assigned_officer_id
             WHERE r.status = :status
             ORDER BY r.updated_at DESC, r.created_at DESC
             LIMIT ' . $limit
        );
        $statement->execute(['status' => $status]);

        return $statement->fetchAll();
    }

    public static function officerWorkload(): array
    {
        $statement = db()->query(
            "SELECT
                u.id,
                u.full_name,
                u.badge_number,
                COUNT(r.id) AS total_cases,
                SUM(CASE WHEN r.status = 'On Going' THEN 1 ELSE 0 END) AS active_cases,
                SUM(CASE WHEN r.status = 'Closed' THEN 1 ELSE 0 END) AS closed_cases
             FROM users u
             LEFT JOIN crime_reports r ON r.assigned_officer_id = u.id
             WHERE u.role = 'officer'
             GROUP BY u.id, u.full_name, u.badge_number
             ORDER BY active_cases DESC, total_cases DESC, u.full_name ASC"
        );

        return $statement->fetchAll();
    }
}
