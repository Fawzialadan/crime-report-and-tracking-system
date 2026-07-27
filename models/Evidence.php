<?php

declare(strict_types=1);

final class Evidence
{
    public static function create(array $data): void
    {
        $columns = [
            'report_id',
            'submitted_by_user_id',
            'submitted_by_role',
            'title',
            'description',
            'original_file_name',
            'stored_file_name',
            'mime_type',
        ];

        $params = [
            'report_id' => $data['report_id'],
            'submitted_by_user_id' => $data['submitted_by_user_id'],
            'submitted_by_role' => $data['submitted_by_role'],
            'title' => $data['title'],
            'description' => $data['description'],
            'original_file_name' => $data['original_file_name'],
            'stored_file_name' => $data['stored_file_name'],
            'mime_type' => $data['mime_type'],
        ];

        if (db_table_has_column('evidences', 'file_category')) {
            $columns[] = 'file_category';
            $params['file_category'] = $data['file_category'] ?? null;
        }

        if (db_table_has_column('evidences', 'file_size')) {
            $columns[] = 'file_size';
            $params['file_size'] = $data['file_size'] ?? null;
        }

        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);

        $statement = db()->prepare(
            'INSERT INTO evidences (' . implode(', ', $columns) . ')
             VALUES (' . implode(', ', $placeholders) . ')'
        );

        $statement->execute($params);
    }

    public static function find(int $id): ?array
    {
        $statement = db()->prepare('SELECT * FROM evidences WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $evidence = $statement->fetch();

        return $evidence ?: null;
    }

    public static function forReport(int $reportId): array
    {
        $statement = db()->prepare(
            'SELECT e.*, u.full_name
             FROM evidences e
             LEFT JOIN users u ON u.id = e.submitted_by_user_id
             WHERE e.report_id = :report_id
             ORDER BY e.created_at DESC'
        );
        $statement->execute(['report_id' => $reportId]);

        return $statement->fetchAll();
    }
}
