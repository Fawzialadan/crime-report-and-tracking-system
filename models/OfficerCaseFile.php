<?php

declare(strict_types=1);

final class OfficerCaseFile
{
    public static function create(array $data): void
    {
        $statement = db()->prepare(
            'INSERT INTO officer_case_files (
                report_id, officer_id, title, description, original_file_name,
                stored_file_name, mime_type, file_category, file_size
            ) VALUES (
                :report_id, :officer_id, :title, :description, :original_file_name,
                :stored_file_name, :mime_type, :file_category, :file_size
            )'
        );

        $statement->execute([
            'report_id' => $data['report_id'],
            'officer_id' => $data['officer_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'original_file_name' => $data['original_file_name'],
            'stored_file_name' => $data['stored_file_name'],
            'mime_type' => $data['mime_type'],
            'file_category' => $data['file_category'],
            'file_size' => $data['file_size'],
        ]);
    }

    public static function find(int $id): ?array
    {
        $statement = db()->prepare('SELECT * FROM officer_case_files WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $file = $statement->fetch();

        return $file ?: null;
    }

    public static function forOfficerCase(int $reportId, int $officerId): array
    {
        $statement = db()->prepare(
            'SELECT f.*, r.reference_no, r.title AS case_title
             FROM officer_case_files f
             INNER JOIN crime_reports r ON r.id = f.report_id
             WHERE f.report_id = :report_id AND f.officer_id = :officer_id
             ORDER BY f.created_at DESC'
        );
        $statement->execute([
            'report_id' => $reportId,
            'officer_id' => $officerId,
        ]);

        return $statement->fetchAll();
    }

    public static function forOfficer(int $officerId): array
    {
        $statement = db()->prepare(
            'SELECT f.*, r.reference_no, r.title AS case_title
             FROM officer_case_files f
             INNER JOIN crime_reports r ON r.id = f.report_id
             WHERE f.officer_id = :officer_id
             ORDER BY f.created_at DESC'
        );
        $statement->execute(['officer_id' => $officerId]);

        return $statement->fetchAll();
    }
}
