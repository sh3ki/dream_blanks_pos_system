<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Color;
use App\Models\Inventory;
use App\Models\RestockOrder;
use App\Models\Size;
use App\Models\StockMovement;
use App\Models\StockProduct;
use App\Models\Type;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Core\Database;
use App\Exceptions\ValidationException;

class InventoryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->requirePermission(MODULE_INVENTORY, ACTION_VIEW);
        [$page, $perPage] = $this->paginate($request);

        $rawFilters = $request->only(['search', 'type_id', 'color_id', 'size_id', 'status', 'stock_status', 'sort', 'order']);
        $result = StockProduct::search($rawFilters, $page, $perPage);

        // History tab data
        $historyPage    = (int)($request->query('history_page') ?? 1);
        $histPerPage    = min(100, max(1, (int)$request->query('per_page', 20)));
        $histFilters    = $request->only(['history_search', 'movement_type', 'hist_type_id', 'hist_color_id', 'hist_size_id', 'hist_created_by', 'hist_date_from', 'hist_date_to']);
        $histFiltersMap = [
            'search'        => $histFilters['history_search']  ?? '',
            'movement_type' => $histFilters['movement_type']   ?? '',
            'type_id'       => $histFilters['hist_type_id']    ?? '',
            'color_id'      => $histFilters['hist_color_id']   ?? '',
            'size_id'       => $histFilters['hist_size_id']    ?? '',
            'created_by'    => $histFilters['hist_created_by'] ?? '',
            'date_from'     => $histFilters['hist_date_from']  ?? '',
            'date_to'       => $histFilters['hist_date_to']    ?? '',
        ];
        $historyResult  = StockMovement::getAll($historyPage, $histPerPage, $histFiltersMap);

        // Restock orders tab data
        $restockPage    = (int)($request->query('restock_page') ?? 1);
        $restockPerPage = min(100, max(1, (int)$request->query('restock_per_page', 10)));
        $restockFilters = $request->only(['restock_sort', 'restock_order', 'restock_status']);
        $restockResult  = RestockOrder::paginated($restockPage, $restockPerPage, $restockFilters);

        $db = Database::getInstance();
        $invStats = $db->selectOne(
            "SELECT
                SUM(CASE WHEN stock_status = 'in_stock'     THEN 1 ELSE 0 END) as in_stock_count,
                SUM(CASE WHEN stock_status = 'low_stock'    THEN 1 ELSE 0 END) as low_stock_count,
                SUM(CASE WHEN stock_status = 'out_of_stock' THEN 1 ELSE 0 END) as out_of_stock_count
             FROM stock_products WHERE deleted_at IS NULL"
        ) ?? [];
        $restockStats = $db->selectOne(
            "SELECT
                SUM(CASE WHEN delivery_status = 'ordered'   THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN delivery_status = 'delivered' THEN 1 ELSE 0 END) as delivered_count
             FROM restock_orders"
        ) ?? [];

        return $this->view('inventory/index', [
            'inventory'          => $result['data'],
            'pagination'         => $result['pagination'],
            'types'              => Type::allActive(),
            'colors'             => Color::allActive(),
            'sizes'              => Size::allActive(),
            'filters'            => $rawFilters,
            'low_stock'          => [],
            'active_tab'         => $request->query('tab', 'inventory'),
            'history'            => $historyResult['data'],
            'hist_pagination'    => $historyResult['pagination'],
            'hist_filters'       => $histFiltersMap,
            'restock_orders'     => $restockResult['data'],
            'restock_pagination' => $restockResult['pagination'],
            'restock_filters'    => $restockFilters,
            'inv_stats'          => $invStats,
            'restock_stats'      => $restockStats,
            'all_users'          => $db->select("SELECT id, CONCAT(first_name,' ',last_name) as name FROM users WHERE deleted_at IS NULL ORDER BY first_name", []),
        ]);
    }

    public function getRestock(Request $request): Response
    {
        $this->requirePermission(MODULE_INVENTORY, ACTION_VIEW);
        $id    = (int)$request->param('restock_id');
        $order = RestockOrder::getWithItems($id);
        if (!$order) {
            return $this->error('Restock order not found', 404);
        }
        return $this->success(['restock' => $order]);
    }

    public function importRestockCsv(Request $request): Response
    {
        $this->requirePermission(MODULE_INVENTORY, ACTION_IMPORT);
        $rows         = $request->input('items', []);
        $supplierName = $request->input('supplier_name', '');
        $notes        = $request->input('notes', '');

        if (empty($rows)) {
            throw new ValidationException(['items' => ['No items provided']]);
        }

        $db = Database::getInstance();

        // Resolve stock_product_id from code for each row
        $resolvedItems = [];
        foreach ($rows as $row) {
            $code = trim($row['code'] ?? '');
            $qty  = (int)($row['qty'] ?? 0);
            if ($code === '' || $qty <= 0) continue;
            $sp = $db->selectOne("SELECT id FROM stock_products WHERE code = ? AND deleted_at IS NULL LIMIT 1", [$code]);
            if ($sp) {
                $resolvedItems[] = [
                    'stock_product_id'   => (int)$sp['id'],
                    'quantity_requested' => $qty,
                    'code'               => $code,
                ];
            }
        }

        if (empty($resolvedItems)) {
            return $this->error('No matching stock products found for the provided codes', 422);
        }

        $db->beginTransaction();
        try {
            $orderNumber = RestockOrder::generateOrderNumber();
            $restockId   = RestockOrder::create([
                'order_number'    => $orderNumber,
                'order_date'      => date('Y-m-d'),
                'supplier_name'   => $supplierName ?: null,
                'delivery_status' => DELIVERY_ORDERED,
                'notes'           => $notes ?: null,
                'created_by'      => $this->currentUserId(),
            ]);

            foreach ($resolvedItems as $item) {
                $db->insert('restock_items', [
                    'restock_id'         => $restockId,
                    'stock_product_id'   => $item['stock_product_id'],
                    'quantity_requested' => $item['quantity_requested'],
                    'quantity_received'  => 0,
                    'created_at'         => date('Y-m-d H:i:s'),
                    'updated_at'         => date('Y-m-d H:i:s'),
                ]);
            }

            $db->commit();

            // Build audit items with names
            $auditItems = [];
            foreach ($resolvedItems as $ri) {
                $sp2 = StockProduct::find($ri['stock_product_id']);
                $auditItems[] = [
                    'code'               => $ri['code'],
                    'name'               => $sp2['name'] ?? "SP#{$ri['stock_product_id']}",
                    'quantity_requested' => $ri['quantity_requested'],
                ];
            }
            AuditService::log(AUDIT_RESTOCK, MODULE_INVENTORY, $restockId, null, [
                'source'           => 'csv_import',
                'order_number'     => $orderNumber,
                'supplier_name'    => $supplierName ?: null,
                'delivery_status'  => DELIVERY_ORDERED,
                'notes'            => $notes ?: null,
                'items'            => $auditItems,
                'total_items'      => count($auditItems),
            ], "Restock order #{$orderNumber} created via CSV import with " . count($auditItems) . " item(s)");

            return $this->success(['restock_id' => $restockId, 'order_number' => $orderNumber], 'Restock order created from CSV', 201);
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }

    public function createRestock(Request $request): Response
    {
        $this->requirePermission(MODULE_INVENTORY, ACTION_RESTOCK);
        $items = $request->input('items', []);
        if (empty($items)) {
            throw new ValidationException(['items' => ['At least one item is required']]);
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $orderNumber = RestockOrder::generateOrderNumber();
            $restockId   = RestockOrder::create([
                'order_number'    => $orderNumber,
                'order_date'      => date('Y-m-d'),
                'delivery_date'   => $request->input('delivery_date'),
                'supplier_name'   => $request->input('supplier_name'),
                'delivery_status' => in_array($request->input('delivery_status'), ['ordered', 'delivered', 'incomplete', 'problematic']) ? $request->input('delivery_status') : DELIVERY_ORDERED,
                'notes'           => $request->input('notes'),
                'created_by'      => $this->currentUserId(),
            ]);

            foreach ($items as $item) {
                $stockProductId = (int)($item['stock_product_id'] ?? 0);
                if ($stockProductId <= 0) {
                    continue; // skip invalid rows
                }

                $db->insert('restock_items', [
                    'restock_id'          => $restockId,
                    'stock_product_id'    => $stockProductId,
                    'quantity_requested'  => (int)($item['quantity_requested'] ?? 0),
                    'quantity_received'   => 0,
                    'created_at'          => date('Y-m-d H:i:s'),
                    'updated_at'          => date('Y-m-d H:i:s'),
                ]);
            }

            $db->commit();

            // Build audit items with names
            $auditItems2 = [];
            foreach ($items as $item2) {
                $spId2 = (int)($item2['stock_product_id'] ?? 0);
                if ($spId2 <= 0) continue;
                $sp2 = StockProduct::find($spId2);
                $auditItems2[] = [
                    'stock_product_id'   => $spId2,
                    'code'               => $sp2['code'] ?? '',
                    'name'               => $sp2['name'] ?? "SP#{$spId2}",
                    'quantity_requested' => (int)($item2['quantity_requested'] ?? 0),
                ];
            }
            AuditService::log(AUDIT_RESTOCK, MODULE_INVENTORY, $restockId, null, [
                'source'           => 'restock_order',
                'order_number'     => $orderNumber,
                'supplier_name'    => $request->input('supplier_name'),
                'delivery_status'  => $request->input('delivery_status', DELIVERY_ORDERED),
                'delivery_date'    => $request->input('delivery_date'),
                'notes'            => $request->input('notes'),
                'items'            => $auditItems2,
                'total_items'      => count($auditItems2),
            ], "Restock order #{$orderNumber} created with " . count($auditItems2) . " item(s)");

            return $this->success(['restock_id' => $restockId, 'order_number' => $orderNumber], 'Restock order created', 201);
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }

    public function updateRestock(Request $request): Response
    {
        $this->requirePermission(MODULE_INVENTORY, ACTION_EDIT);
        $id         = (int)$request->param('restock_id');
        $order      = RestockOrder::findOrFail($id);
        $newStatus  = $request->input('delivery_status');
        $prevStatus = $order['delivery_status'];

        $allowedStatuses = ['ordered', 'delivered', 'incomplete', 'problematic'];
        if (!in_array($newStatus, $allowedStatuses)) {
            $newStatus = $prevStatus;
        }

        RestockOrder::update($id, array_filter([
            'delivery_status' => $newStatus,
            'delivery_date'   => $request->input('delivery_date'),
            'notes'           => $request->input('notes'),
        ], fn($v) => $v !== null));

        $db = Database::getInstance();

        if ($prevStatus !== $newStatus) {
            $items = RestockOrder::getItems($id);

            if ($newStatus === DELIVERY_DELIVERED && $prevStatus !== DELIVERY_DELIVERED) {
                // Apply delivery: increment qty + log purchase movements
                foreach ($items as $item) {
                    $stockProductId = (int)($item['stock_product_id'] ?? 0);
                    if ($stockProductId <= 0) continue;

                    $qty = (int)$item['quantity_requested'];
                    $qtyBefore = (int)(StockProduct::find($stockProductId)['current_qty'] ?? 0);
                    StockProduct::incrementQty($stockProductId, $qty);
                    StockMovement::logForStockProduct(
                        $stockProductId,
                        MOVEMENT_PURCHASE,
                        $qty,
                        "Restock #{$order['order_number']} delivered",
                        $id,
                        $this->currentUserId(),
                        null,
                        $qtyBefore,
                        $qtyBefore + $qty
                    );
                    $db->update(
                        'restock_items',
                        ['quantity_received' => $qty],
                        'restock_id = ? AND stock_product_id = ?',
                        [$id, $stockProductId]
                    );
                }
                NotificationService::restockDelivered($id, $order['order_number']);

            } elseif ($prevStatus === DELIVERY_DELIVERED && $newStatus !== DELIVERY_DELIVERED) {
                // Reverse delivery: decrement qty + log negative adjustment
                foreach ($items as $item) {
                    $stockProductId = (int)($item['stock_product_id'] ?? 0);
                    if ($stockProductId <= 0) continue;

                    $received = (int)($item['quantity_received'] ?? 0);
                    if ($received > 0) {
                        $qtyBefore = (int)(StockProduct::find($stockProductId)['current_qty'] ?? 0);
                        StockProduct::decrementQty($stockProductId, $received);
                        StockMovement::logForStockProduct(
                            $stockProductId,
                            MOVEMENT_ADJUSTMENT,
                            -$received,
                            "Restock #{$order['order_number']} delivery reversed (changed to: {$newStatus})",
                            $id,
                            $this->currentUserId(),
                            null,
                            $qtyBefore,
                            $qtyBefore - $received
                        );
                        $db->update(
                            'restock_items',
                            ['quantity_received' => 0],
                            'restock_id = ? AND stock_product_id = ?',
                            [$id, $stockProductId]
                        );
                    }
                }
            } else {
                // Non-qty status change (e.g. ordered → incomplete/problematic): log audit entry per item
                foreach ($items as $item) {
                    $stockProductId = (int)($item['stock_product_id'] ?? 0);
                    if ($stockProductId <= 0) continue;
                    $currentQty = (int)(StockProduct::find($stockProductId)['current_qty'] ?? 0);
                    StockMovement::logForStockProduct(
                        $stockProductId,
                        MOVEMENT_ADJUSTMENT,
                        0,
                        "Restock #{$order['order_number']} status changed: {$prevStatus} → {$newStatus}",
                        $id,
                        $this->currentUserId(),
                        null,
                        $currentQty,
                        $currentQty
                    );
                }
            }
        }

        // Build audit items for update
        $allItems    = RestockOrder::getItems($id);
        $auditItemsU = array_map(fn($i) => [
            'code'                => $i['sp_code'] ?? $i['code'] ?? '',
            'name'                => $i['sp_name'] ?? $i['name'] ?? '',
            'quantity_requested'  => (int)$i['quantity_requested'],
            'quantity_received'   => (int)$i['quantity_received'],
        ], $allItems);
        AuditService::log(AUDIT_RESTOCK, MODULE_INVENTORY, $id, [
            'delivery_status' => $prevStatus,
        ], [
            'order_number'    => $order['order_number'],
            'delivery_status' => $newStatus,
            'delivery_date'   => $request->input('delivery_date'),
            'supplier_name'   => $order['supplier_name'],
            'notes'           => $request->input('notes'),
            'items'           => $auditItemsU,
            'total_items'     => count($auditItemsU),
        ], "Restock order #{$order['order_number']} status updated: {$prevStatus} → {$newStatus}");
        return $this->success(null, 'Restock updated');
    }
}


