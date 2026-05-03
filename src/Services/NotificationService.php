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

    public static function lowStockAlert(int $productId, string $productName, int $stock): void
    {
        static::notifyAdmins(
            'low_stock',
            'Low Stock Alert',
            "Product '{$productName}' is running low. Only {$stock} unit(s) remaining.",
            $productId
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
}
