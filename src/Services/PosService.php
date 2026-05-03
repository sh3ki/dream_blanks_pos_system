<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Core\Database;
use App\Exceptions\ValidationException;

class PosService
{
    public function checkout(array $data, int $createdBy): array
    {
        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new ValidationException(['items' => ['Cart cannot be empty']]);
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // Validate stock
            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                if (!$product || (int)$product['current_stock'] < (int)$item['quantity']) {
                    throw new ValidationException([
                        'items' => ["Insufficient stock for: {$product['name']}"]
                    ]);
                }
            }

            // Create invoice
            $invoiceNumber = Invoice::generateNumber();
            $invoiceId = Invoice::create([
                'invoice_number'      => $invoiceNumber,
                'invoice_date'        => date('Y-m-d H:i:s'),
                'client_id'           => $data['client_id'] ?? null,
                'subtotal'            => $data['subtotal'],
                'discount_amount'     => $data['discount_amount'] ?? 0,
                'tax_amount'          => $data['tax_amount'] ?? 0,
                'additional_fee'      => $data['additional_fee'] ?? 0,
                'total_amount'        => $data['total_amount'],
                'total_paid'          => 0,
                'payment_status'      => PAYMENT_STATUS_UNPAID,
                'primary_payment_mode'=> $data['payment_mode'] ?? null,
                'notes'               => $data['notes'] ?? null,
                'created_by'          => $createdBy,
            ]);

            // Insert invoice items & deduct stock
            foreach ($items as $item) {
                $db->insert('invoice_items', [
                    'invoice_id'  => $invoiceId,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'line_total'  => $item['unit_price'] * $item['quantity'],
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);

                Product::decrementStock($item['product_id'], $item['quantity']);
                StockMovement::log($item['product_id'], MOVEMENT_SALE, -$item['quantity'], "Sale invoice #{$invoiceNumber}", $invoiceId, $createdBy);

                // Low stock check
                $product = Product::find($item['product_id']);
                $alert   = (int)($product['low_stock_alert'] ?? 10);
                if ((int)$product['current_stock'] <= $alert) {
                    NotificationService::lowStockAlert($item['product_id'], $product['name'], (int)$product['current_stock']);
                }
            }

            // Record initial payment if provided
            if (!empty($data['payment_mode']) && ($data['payment_status'] ?? '') !== PAYMENT_STATUS_UNPAID) {
                $payAmount = (float)$data['total_amount'];
                if (($data['payment_status'] ?? '') === PAYMENT_STATUS_PARTIALLY_PAID) {
                    $payAmount = (float)($data['initial_payment'] ?? $data['total_amount']);
                }

                $db->insert('payments', [
                    'invoice_id'      => $invoiceId,
                    'payment_number'  => 1,
                    'payment_date'    => date('Y-m-d'),
                    'payment_amount'  => $payAmount,
                    'payment_mode'    => $data['payment_mode'],
                    'recorded_by'     => $createdBy,
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ]);

                Invoice::updatePaymentStatus($invoiceId);
            }

            // Log transaction
            $txnNumber = Transaction::generateNumber();
            $db->insert('transactions', [
                'transaction_number' => $txnNumber,
                'transaction_date'   => date('Y-m-d H:i:s'),
                'transaction_type'   => TXN_SALE,
                'related_invoice_id' => $invoiceId,
                'amount'             => $data['total_amount'],
                'description'        => "POS Sale - Invoice #{$invoiceNumber}",
                'recorded_by'        => $createdBy,
                'created_at'         => date('Y-m-d H:i:s'),
            ]);

            $db->commit();

            return [
                'invoice_id'     => $invoiceId,
                'invoice_number' => $invoiceNumber,
                'total_amount'   => $data['total_amount'],
                'receipt_url'    => app_url('/api/v1/invoices/' . $invoiceId . '/print'),
            ];
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }
}
