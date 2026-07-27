<?php

declare(strict_types=1);

final class CaseUpdate
{
    public static function create(
        int $reportId,
        ?int $updatedBy,
        string $actorName,
        string $actorRole,
        string $statusAfterUpdate,
        string $notes,
        bool $citizenVisible = true
    ): void {
        $statement = db()->prepare(
            'INSERT INTO case_updates (report_id, updated_by, actor_name, actor_role, status_after_update, notes, citizen_visible)
             VALUES (:report_id, :updated_by, :actor_name, :actor_role, :status_after_update, :notes, :citizen_visible)'
        );

        $statement->execute([
            'report_id' => $reportId,
            'updated_by' => $updatedBy,
            'actor_name' => $actorName,
            'actor_role' => $actorRole,
            'status_after_update' => $statusAfterUpdate,
            'notes' => $notes,
            'citizen_visible' => $citizenVisible ? 1 : 0,
        ]);
    }

    public static function latest(int $limit = 10): array
    {
        $limit = max(1, $limit);
        $statement = db()->query(
            'SELECT c.*, r.reference_no
             FROM case_updates c
             INNER JOIN crime_reports r ON r.id = c.report_id
             ORDER BY c.created_at DESC
             LIMIT ' . $limit
        );

        return $statement->fetchAll();
    }

    public static function forReport(int $reportId, bool $citizenVisibleOnly = false): array
    {
        $sql = 'SELECT * FROM case_updates WHERE report_id = :report_id';
        if ($citizenVisibleOnly) {
            $sql .= ' AND citizen_visible = 1';
        }

        $sql .= ' ORDER BY created_at DESC';

        $statement = db()->prepare($sql);
        $statement->execute(['report_id' => $reportId]);

        return $statement->fetchAll();
    }

    public static function forOfficer(int $officerId, int $limit = 10): array
    {
        $limit = max(1, $limit);
        $statement = db()->prepare(
            'SELECT c.*, r.reference_no
             FROM case_updates c
             INNER JOIN crime_reports r ON r.id = c.report_id
             WHERE r.assigned_officer_id = :officer_id
             ORDER BY c.created_at DESC
             LIMIT ' . $limit
        );
        $statement->execute(['officer_id' => $officerId]);

        return $statement->fetchAll();
    }

    public static function forCitizen(string $email, int $limit = 10): array
    {
        $limit = max(1, $limit);
        $statement = db()->prepare(
            'SELECT c.*, r.reference_no
             FROM case_updates c
             INNER JOIN crime_reports r ON r.id = c.report_id
             WHERE r.citizen_email = :email AND c.citizen_visible = 1
             ORDER BY c.created_at DESC
             LIMIT ' . $limit
        );
        $statement->execute(['email' => $email]);

        return $statement->fetchAll();
    }
}
