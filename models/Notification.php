<?php

declare(strict_types=1);

final class Notification
{
    public static function create(int $reportId, string $recipientName, string $recipientRole, ?string $recipientContact, string $message): void
    {
        $statement = db()->prepare(
            'INSERT INTO notifications (report_id, recipient_name, recipient_role, recipient_contact, message)
             VALUES (:report_id, :recipient_name, :recipient_role, :recipient_contact, :message)'
        );

        $statement->execute([
            'report_id' => $reportId,
            'recipient_name' => $recipientName,
            'recipient_role' => $recipientRole,
            'recipient_contact' => $recipientContact,
            'message' => $message,
        ]);
    }

    public static function latest(int $limit = 8): array
    {
        $limit = max(1, $limit);
        $statement = db()->query(
            'SELECT n.*, r.reference_no
             FROM notifications n
             INNER JOIN crime_reports r ON r.id = n.report_id
             ORDER BY n.created_at DESC
             LIMIT ' . $limit
        );

        return $statement->fetchAll();
    }

    public static function forRecipient(string $role, string $email, int $limit = 8): array
    {
        $limit = max(1, $limit);
        $statement = db()->prepare(
            'SELECT n.*, r.reference_no
             FROM notifications n
             INNER JOIN crime_reports r ON r.id = n.report_id
             WHERE n.recipient_role = :recipient_role AND (n.recipient_contact = :recipient_contact OR r.citizen_email = :recipient_contact)
             ORDER BY n.created_at DESC
             LIMIT ' . $limit
        );
        $statement->execute([
            'recipient_role' => $role,
            'recipient_contact' => $email,
        ]);

        return $statement->fetchAll();
    }
}
