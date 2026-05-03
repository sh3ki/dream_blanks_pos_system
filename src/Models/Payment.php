<?php

namespace App\Models;

class Payment extends Model
{
    protected static string $table = 'payments';

    public static function nextPaymentNumber(int $invoiceId): int
    {
        $row = static::db()->selectOne(
            "SELECT MAX(payment_number) as max_num FROM payments WHERE invoice_id = ?",
            [$invoiceId]
        );
        return (int)($row['max_num'] ?? 0) + 1;
    }
}
