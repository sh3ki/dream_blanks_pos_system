<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductStockRequirement;
use App\Models\StockProduct;
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
            // -------------------------------------------------------------------
            // 1. Pre-flight: load stock requirements for all cart items and
            //    aggregate deductions per stock product before touching any data.
            // -------------------------------------------------------------------
            // Map: stock_product_id => total qty to deduct
            $deductions = [];

            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $soldQty   = (int)$item['quantity'];

                $product = Product::find($productId);
                if (!$product) {
                    throw new ValidationException(['items' => ["Product not found (id: {$productId})"]]);
                }

                $requirements = ProductStockRequirement::forProduct($productId);
                if (empty($requirements)) {
                    throw new ValidationException([
                        'items' => ["Product '{$product['name']}' has no stock requirements assigned. Contact an administrator."]
                    ]);
                }

                foreach ($requirements as $req) {
                    $spId       = (int)$req['stock_product_id'];
                    $perUnit    = (float)$req['qty_required_per_unit'];
                    $waste      = (float)($req['waste_percent'] ?? 0);
                    $effective  = $perUnit * (1 + $waste / 100);
                    $deductQty  = (int)ceil($soldQty * $effective);

                    $deductions[$spId] = ($deductions[$spId] ?? 0) + $deductQty;
                }
            }

            // -------------------------------------------------------------------
            // 2. Validate all stock products have enough quantity.
            // -------------------------------------------------------------------
            foreach ($deductions as $spId => $needed) {
                $sp = StockProduct::find($spId);
                if (!$sp || (int)$sp['current_qty'] < $needed) {
                    $spName = $sp['name'] ?? "Stock Product #{$spId}";
                    throw new ValidationException([
                        'items' => ["Insufficient stock for: {$spName} (need {$needed}, have " . (int)($sp['current_qty'] ?? 0) . ")"]
                    ]);
                }
            }

            // -------------------------------------------------------------------
            // 3. Create invoice.
            // -------------------------------------------------------------------
            $invoiceNumber = Invoice::generateNumber();
            $invoiceId = Invoice::create([
                'invoice_number'       => $invoiceNumber,
                'invoice_date'         => date('Y-m-d H:i:s'),
                'client_id'            => $data['client_id'] ?? null,
                'subtotal'             => $data['subtotal'],
                'discount_amount'      => $data['discount_amount'] ?? 0,
                'tax_amount'           => $data['tax_amount'] ?? 0,
                'additional_fee'       => $data['additional_fee'] ?? 0,
                'total_amount'         => $data['total_amount'],
                'total_paid'           => 0,
                'payment_status'       => PAYMENT_STATUS_UNPAID,
                'primary_payment_mode' => $data['payment_mode'] ?? null,
                'notes'                => $data['notes'] ?? null,
                'created_by'           => $createdBy,
            ]);

            // -------------------------------------------------------------------
            // 4. Insert invoice items.
            // -------------------------------------------------------------------
            foreach ($items as $item) {
                $db->insert('invoice_items', [
                    'invoice_id'  => $invoiceId,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'line_total'  => $item['unit_price'] * $item['quantity'],
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
            }

            // -------------------------------------------------------------------
            // 5. Deduct stock products and log movements.
            //    We do this after insert so reference_id (invoiceId) exists.
            // -------------------------------------------------------------------
            // Build a per-cart-item breakdown for movement reason detail
            $itemReasonMap = [];
            foreach ($items as $item) {
                $productId    = (int)$item['product_id'];
                $soldQty      = (int)$item['quantity'];
                $requirements = ProductStockRequirement::forProduct($productId);
                $product      = Product::find($productId);

                foreach ($requirements as $req) {
                    $spId      = (int)$req['stock_product_id'];
                    $perUnit   = (float)$req['qty_required_per_unit'];
                    $waste     = (float)($req['waste_percent'] ?? 0);
                    $effective = $perUnit * (1 + $waste / 100);
                    $deductQty = (int)ceil($soldQty * $effective);

                    $itemReasonMap[$spId][] = [
                        'product_id'  => $productId,
                        'product_name'=> $product['name'] ?? '',
                        'qty'         => $deductQty,
                    ];
                }
            }

            foreach ($deductions as $spId => $totalDeduct) {
                $spBefore = StockProduct::find($spId);
                $qtyBefore = (int)($spBefore['current_qty'] ?? 0);
                StockProduct::decrementQty($spId, $totalDeduct);

                // Build reason string
                $parts = array_map(fn($r) => "{$r['product_name']} x{$r['qty']}", $itemReasonMap[$spId] ?? []);
                $reason = "Sale invoice #{$invoiceNumber}: " . implode(', ', $parts);

                // Log movement — link to primary product for traceability (first product that uses this sp)
                $primaryProductId = ($itemReasonMap[$spId][0]['product_id'] ?? null);
                StockMovement::logForStockProduct(
                    $spId,
                    MOVEMENT_SALE,
                    -$totalDeduct,
                    $reason,
                    $invoiceId,
                    $createdBy,
                    $primaryProductId,
                    $qtyBefore,
                    $qtyBefore - $totalDeduct
                );

                // Low stock notification
                $sp = StockProduct::find($spId);
                if ($sp && (int)$sp['current_qty'] <= (int)($sp['low_stock_alert'] ?? 10)) {
                    NotificationService::lowStockAlert($spId, $sp['name'], (int)$sp['current_qty']);
                }
            }

            // -------------------------------------------------------------------
            // 6. Record initial payment if provided.
            // -------------------------------------------------------------------
            if (!empty($data['payment_mode']) && ($data['payment_status'] ?? '') !== PAYMENT_STATUS_UNPAID) {
                $payAmount = (float)$data['total_amount'];
                if (($data['payment_status'] ?? '') === PAYMENT_STATUS_PARTIALLY_PAID) {
                    $payAmount = (float)($data['initial_payment'] ?? $data['total_amount']);
                }

                $db->insert('payments', [
                    'invoice_id'     => $invoiceId,
                    'payment_number' => 1,
                    'payment_date'   => date('Y-m-d'),
                    'payment_amount' => $payAmount,
                    'payment_mode'   => $data['payment_mode'],
                    'recorded_by'    => $createdBy,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s'),
                ]);

                Invoice::updatePaymentStatus($invoiceId);
            }

            // -------------------------------------------------------------------
            // 7. Log transaction.
            // -------------------------------------------------------------------
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


