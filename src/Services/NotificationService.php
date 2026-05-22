<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public static function notify(int $userId, string $type, string $title, string $message, ?int $relatedId = null): void
    {
        Notification::create([
            'user_id'           => $userId,
            'notification_type' => $type,
            'title'             => $title,
            'message'           => $message,
            'related_record_id' => $relatedId,
        ]);
    }

    public static function notifyAdmins(string $type, string $title, string $message, ?int $relatedId = null): void
    {
        $admins = User::db()->select(
            "SELECT u.id FROM users u
             INNER JOIN user_roles ur ON ur.user_id = u.id
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE r.name = 'Admin' AND u.status = 'active' AND u.deleted_at IS NULL"
        );

        foreach ($admins as $admin) {
            static::notify($admin['id'], $type, $title, $message, $relatedId);
        }
    }

    public static function lowStockAlert(int $stockProductId, string $stockProductName, int $qty): void
    {
        static::notifyAdmins(
            'low_stock',
            'Low Stock Alert',
            "Stock product '{$stockProductName}' is running low. Only {$qty} unit(s) remaining.",
            $stockProductId
        );
    }

    public static function paymentReceived(int $invoiceId, float $amount, string $invoiceNumber): void
    {
        static::notifyAdmins(
            'payment_received',
            'Payment Received',
            "Payment of ₱" . number_format($amount, 2) . " received for Invoice #{$invoiceNumber}.",
            $invoiceId
        );
    }

    public static function restockDelivered(int $restockId, string $orderNumber): void
    {
        static::notifyAdmins(
            'restock_delivered',
            'Restock Delivered',
            "Restock order #{$orderNumber} has been marked as delivered.",
            $restockId
        );
    }

    public static function lineupCreated(int $lineupId, string $invoiceNumber, string $brandName, string $clientName): void
    {
        static::notifyAdmins(
            'lineup_created',
            'New Project Lineup Created',
            "Project lineup for {$brandName} (Invoice #{$invoiceNumber}) has been added for client {$clientName}.",
            $lineupId
        );
    }

    public static function lineupDeadlineReminders(): void
    {
        $db = Notification::db();

        // Find non-completed lineups whose deadline is exactly tomorrow
        $lineups = $db->select(
            "SELECT pl.id, pl.brand_name, pl.deadline,
                    COALESCE(pl.client_name, c.full_name, 'Walk-in') AS client_name,
                    i.invoice_number
             FROM project_lineups pl
             LEFT JOIN invoices i ON i.id = pl.invoice_id
             LEFT JOIN clients c ON c.id = i.client_id
             WHERE pl.deleted_at IS NULL
               AND pl.project_status != 'completed'
               AND pl.deadline = DATE_ADD(CURDATE(), INTERVAL 1 DAY)"
        );

        foreach ($lineups as $lineup) {
            // Only send once per lineup per day (deduplication)
            $alreadySent = (int)($db->selectOne(
                "SELECT COUNT(*) AS cnt FROM notifications
                 WHERE notification_type = 'lineup_deadline'
                   AND related_record_id = ?
                   AND DATE(created_at) = CURDATE()",
                [$lineup['id']]
            )['cnt'] ?? 0);

            if ($alreadySent > 0) continue;

            static::notifyAdmins(
                'lineup_deadline',
                'Project Deadline Tomorrow',
                "Deadline reminder: Project for {$lineup['brand_name']} (Invoice #{$lineup['invoice_number']}, Client: {$lineup['client_name']}) is due tomorrow ({$lineup['deadline']}).",
                $lineup['id']
            );
        }
    }
}
