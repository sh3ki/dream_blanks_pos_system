<?php

namespace App\Models;

class InvoiceItem extends Model
{
    protected static string $table = 'invoice_items';
    protected static bool $timestamps = false;

    public static function forInvoice(int $invoiceId): array
    {
        return static::db()->select(
            "SELECT * FROM invoice_items WHERE invoice_id = ?",
            [$invoiceId]
        );
    }
}
