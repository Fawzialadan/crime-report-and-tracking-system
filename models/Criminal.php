<?php

declare(strict_types=1);

final class Criminal
{
    public static function create(array $data): int
    {
        $statement = db()->prepare(
            'INSERT INTO criminals (
                full_name, phone_number, national_id, date_of_birth, height_cm, address, identifying_marks,
                notes, photo_original_file_name, photo_stored_file_name
            ) VALUES (
                :full_name, :phone_number, :national_id, :date_of_birth, :height_cm, :address, :identifying_marks,
                :notes, :photo_original_file_name, :photo_stored_file_name
            )'
        );

        $statement->execute([
            'full_name' => $data['full_name'],
            'phone_number' => $data['phone_number'],
            'national_id' => $data['national_id'],
            'date_of_birth' => $data['date_of_birth'],
            'height_cm' => $data['height_cm'],
            'address' => $data['address'],
            'identifying_marks' => $data['identifying_marks'],
            'notes' => $data['notes'],
            'photo_original_file_name' => $data['photo_original_file_name'],
            'photo_stored_file_name' => $data['photo_stored_file_name'],
        ]);

        return (int) db()->lastInsertId();
    }

    public static function updateProfile(int $criminalId, array $data): void
    {
        $statement = db()->prepare(
            'UPDATE criminals
             SET full_name = :full_name,
                 phone_number = :phone_number,
                 national_id = :national_id,
                 date_of_birth = :date_of_birth,
                 height_cm = :height_cm,
                 address = :address,
                 identifying_marks = :identifying_marks,
                 notes = :notes,
                 photo_original_file_name = :photo_original_file_name,
                 photo_stored_file_name = :photo_stored_file_name
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $criminalId,
            'full_name' => $data['full_name'],
            'phone_number' => $data['phone_number'],
            'national_id' => $data['national_id'],
            'date_of_birth' => $data['date_of_birth'],
            'height_cm' => $data['height_cm'],
            'address' => $data['address'],
            'identifying_marks' => $data['identifying_marks'],
            'notes' => $data['notes'],
            'photo_original_file_name' => $data['photo_original_file_name'],
            'photo_stored_file_name' => $data['photo_stored_file_name'],
        ]);
    }

    public static function saveOrUpdateByNationalId(array $data): array
    {
        $existing = self::findByNationalId($data['national_id']);
        if ($existing) {
            self::updateProfile((int) $existing['id'], [
                'full_name' => $data['full_name'],
                'phone_number' => $data['phone_number'],
                'national_id' => $data['national_id'],
                'date_of_birth' => $data['date_of_birth'],
                'height_cm' => $data['height_cm'],
                'address' => $data['address'],
                'identifying_marks' => $data['identifying_marks'],
                'notes' => $data['notes'],
                'photo_original_file_name' => $data['photo_original_file_name'] ?: ($existing['photo_original_file_name'] ?? null),
                'photo_stored_file_name' => $data['photo_stored_file_name'] ?: ($existing['photo_stored_file_name'] ?? null),
            ]);

            return [
                'id' => (int) $existing['id'],
                'existing' => true,
            ];
        }

        return [
            'id' => self::create($data),
            'existing' => false,
        ];
    }

    public static function find(int $id): ?array
    {
        $statement = db()->prepare(
            'SELECT c.*,
                    COUNT(DISTINCT l.report_id) AS case_count
             FROM criminals c
             LEFT JOIN criminal_case_links l ON l.criminal_id = c.id
             WHERE c.id = :id
             GROUP BY c.id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $criminal = $statement->fetch();

        return $criminal ?: null;
    }

    public static function nationalIdInUseByAnother(string $nationalId, int $criminalId): bool
    {
        $statement = db()->prepare(
            'SELECT id
             FROM criminals
             WHERE national_id = :national_id
               AND id <> :id
             LIMIT 1'
        );
        $statement->execute([
            'national_id' => $nationalId,
            'id' => $criminalId,
        ]);

        return (bool) $statement->fetch();
    }

    public static function findByNationalId(string $nationalId): ?array
    {
        $statement = db()->prepare(
            'SELECT c.*,
                    COUNT(DISTINCT l.report_id) AS case_count
             FROM criminals c
             LEFT JOIN criminal_case_links l ON l.criminal_id = c.id
             WHERE c.national_id = :national_id
             GROUP BY c.id
             LIMIT 1'
        );
        $statement->execute(['national_id' => $nationalId]);
        $criminal = $statement->fetch();

        return $criminal ?: null;
    }

    public static function linkToReport(int $criminalId, int $reportId, ?int $linkedByUserId, string $recordStatus, ?string $caseNotes): bool
    {
        $existing = db()->prepare(
            'SELECT id FROM criminal_case_links WHERE criminal_id = :criminal_id AND report_id = :report_id LIMIT 1'
        );
        $existing->execute([
            'criminal_id' => $criminalId,
            'report_id' => $reportId,
        ]);

        if ($existing->fetch()) {
            $update = db()->prepare(
                'UPDATE criminal_case_links
                 SET record_status = :record_status,
                     case_notes = :case_notes,
                     linked_by_user_id = :linked_by_user_id
                 WHERE criminal_id = :criminal_id AND report_id = :report_id'
            );
            $update->execute([
                'record_status' => $recordStatus,
                'case_notes' => $caseNotes,
                'linked_by_user_id' => $linkedByUserId,
                'criminal_id' => $criminalId,
                'report_id' => $reportId,
            ]);

            return false;
        }

        $insert = db()->prepare(
            'INSERT INTO criminal_case_links (
                criminal_id, report_id, linked_by_user_id, record_status, case_notes
             ) VALUES (
                :criminal_id, :report_id, :linked_by_user_id, :record_status, :case_notes
             )'
        );
        $insert->execute([
            'criminal_id' => $criminalId,
            'report_id' => $reportId,
            'linked_by_user_id' => $linkedByUserId,
            'record_status' => $recordStatus,
            'case_notes' => $caseNotes,
        ]);

        return true;
    }

    public static function forReport(int $reportId): array
    {
        $statement = db()->prepare(
            'SELECT c.*, l.record_status, l.case_notes, l.created_at AS linked_at
             FROM criminal_case_links l
             INNER JOIN criminals c ON c.id = l.criminal_id
             WHERE l.report_id = :report_id
             ORDER BY l.created_at DESC'
        );
        $statement->execute(['report_id' => $reportId]);

        return $statement->fetchAll();
    }

    public static function recentCases(int $criminalId, int $limit = 5): array
    {
        $limit = max(1, $limit);
        $statement = db()->prepare(
            'SELECT l.record_status, l.case_notes, l.created_at AS linked_at, r.id AS report_id, r.reference_no, r.title, r.status
             FROM criminal_case_links l
             INNER JOIN crime_reports r ON r.id = l.report_id
             WHERE l.criminal_id = :criminal_id
             ORDER BY l.created_at DESC
             LIMIT ' . $limit
        );
        $statement->execute(['criminal_id' => $criminalId]);

        return $statement->fetchAll();
    }

    public static function priorCaseCount(int $criminalId, ?int $excludeReportId = null): int
    {
        $sql = 'SELECT COUNT(*) FROM criminal_case_links WHERE criminal_id = :criminal_id';
        $params = ['criminal_id' => $criminalId];

        if ($excludeReportId !== null && $excludeReportId > 0) {
            $sql .= ' AND report_id <> :exclude_report_id';
            $params['exclude_report_id'] = $excludeReportId;
        }

        $statement = db()->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    public static function statusCounts(int $criminalId): array
    {
        $statement = db()->prepare(
            'SELECT
                COUNT(*) AS total_cases,
                SUM(CASE WHEN record_status = "Convicted" THEN 1 ELSE 0 END) AS convicted_cases,
                SUM(CASE WHEN record_status = "Arrested" THEN 1 ELSE 0 END) AS arrested_cases,
                SUM(CASE WHEN record_status = "Wanted" THEN 1 ELSE 0 END) AS wanted_cases
             FROM criminal_case_links
             WHERE criminal_id = :criminal_id'
        );
        $statement->execute(['criminal_id' => $criminalId]);
        $stats = $statement->fetch() ?: [];

        return [
            'total_cases' => (int) ($stats['total_cases'] ?? 0),
            'convicted_cases' => (int) ($stats['convicted_cases'] ?? 0),
            'arrested_cases' => (int) ($stats['arrested_cases'] ?? 0),
            'wanted_cases' => (int) ($stats['wanted_cases'] ?? 0),
        ];
    }

    public static function previousCases(int $criminalId, ?int $excludeReportId = null, int $limit = 5): array
    {
        $limit = max(1, $limit);
        $sql = 'SELECT l.record_status, l.case_notes, l.created_at AS linked_at, r.id AS report_id, r.reference_no, r.title, r.status
                FROM criminal_case_links l
                INNER JOIN crime_reports r ON r.id = l.report_id
                WHERE l.criminal_id = :criminal_id';
        $params = ['criminal_id' => $criminalId];

        if ($excludeReportId !== null && $excludeReportId > 0) {
            $sql .= ' AND l.report_id <> :exclude_report_id';
            $params['exclude_report_id'] = $excludeReportId;
        }

        $sql .= ' ORDER BY l.created_at DESC LIMIT ' . $limit;

        $statement = db()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public static function searchAll(array $filters = []): array
    {
        $sql = 'SELECT c.*,
                       COUNT(DISTINCT l.report_id) AS case_count
                FROM criminals c
                LEFT JOIN criminal_case_links l ON l.criminal_id = c.id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['keyword'])) {
            $sql .= ' AND (
                c.full_name LIKE :keyword
                OR c.national_id LIKE :keyword
                OR c.phone_number LIKE :keyword
            )';
            $params['keyword'] = '%' . $filters['keyword'] . '%';
        }

        $sql .= ' GROUP BY c.id ORDER BY c.updated_at DESC, c.created_at DESC';

        $statement = db()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public static function delete(int $criminalId): void
    {
        $statement = db()->prepare('DELETE FROM criminals WHERE id = :id');
        $statement->execute(['id' => $criminalId]);
    }
}
