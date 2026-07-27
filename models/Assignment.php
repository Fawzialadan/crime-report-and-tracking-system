<?php

declare(strict_types=1);

final class Assignment
{
    public static function assign(int $reportId, int $officerId, int $adminId): void
    {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $insert = $pdo->prepare(
                'INSERT INTO assignments (report_id, officer_id, admin_id) VALUES (:report_id, :officer_id, :admin_id)'
            );
            $insert->execute([
                'report_id' => $reportId,
                'officer_id' => $officerId,
                'admin_id' => $adminId,
            ]);

            $updateReport = $pdo->prepare(
                "UPDATE crime_reports SET assigned_officer_id = :officer_id, status = 'On Going' WHERE id = :report_id"
            );
            $updateReport->execute([
                'officer_id' => $officerId,
                'report_id' => $reportId,
            ]);

            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }
}
