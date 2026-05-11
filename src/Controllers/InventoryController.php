<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Inventory;
use App\Models\RestockOrder;
use App\Models\StockProduct;
use App\Models\StockMovement;
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
        $filters = $request->only(['search', 'status', 'type_id', 'color_id', 'size_id', 'sort', 'order']);
        $result  = Inventory::getAll($filters, $page, $perPage);

        if ($request->isApi()) {
            return $this->success(['inventory' => $result['data'], 'pagination' => $result['pagination']]);
        }

        $historyPage    = (int)($request->query('history_page') ?? 1);
        $histFilters    = $request->only(['history_search', 'movement_type']);
        $histFiltersMap = ['search' => $histFilters['history_search'] ?? '', 'movement_type' => $histFilters['movement_type'] ?? ''];
        $historyResult  = StockMovement::getAll($historyPage, 20, $histFiltersMap);

        $restockOrders = RestockOrder::getRecent(20);

        return $this->view('inventory/index', [
            'inventory'      => $result['data'],
            'pagination'     => $result['pagination'],
            'low_stock'      => Inventory::getLowStock(),
            'filters'        => $filters,
            'history'        => $historyResult['data'],
            'hist_pagination'=> $historyResult['pagination'],
            'hist_filters'   => $histFiltersMap,
            'active_tab'     => $request->query('tab') ?? 'inventory',
            'restock_orders' => $restockOrders,
            'types'          => \App\Models\Type::allActive(),
            'colors'         => \App\Models\Color::allActive(),
            'sizes'          => \App\Models\Size::allActive(),
            'title'          => 'Inventory',
            'pageTitle'      => 'Inventory',
        ]);
    }

    public function createRestock(Request $request): Response
    {
        $this->requirePermission(MODULE_INVENTORY, ACTION_ADD);
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
            AuditService::log(AUDIT_CREATE, MODULE_INVENTORY, $restockId, null, null, "Created restock order #{$orderNumber}");
            return $this->success(['restock_id' => $restockId, 'order_number' => $orderNumber], 'Restock order created', 201);
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }

    public function updateRestock(Request $request): Response
    {
        $this->requirePermission(MODULE_INVENTORY, ACTION_EDIT);
        $id     = (int)$request->param('restock_id');
        $order  = RestockOrder::findOrFail($id);
        $status = $request->input('delivery_status');

        RestockOrder::update($id, array_filter([
            'delivery_status' => $status,
            'delivery_date'   => $request->input('delivery_date'),
            'notes'           => $request->input('notes'),
        ], fn($v) => $v !== null));

        // When delivered: increment stock products and log movements
        if ($status === DELIVERY_DELIVERED) {
            $items = RestockOrder::getItems($id);
            foreach ($items as $item) {
                $stockProductId = (int)($item['stock_product_id'] ?? 0);
                if ($stockProductId <= 0) {
                    continue;
                }

                $qty = (int)$item['quantity_requested'];
                StockProduct::incrementQty($stockProductId, $qty);
                StockMovement::logForStockProduct(
                    $stockProductId,
                    MOVEMENT_PURCHASE,
                    $qty,
                    "Restock #{$order['order_number']}",
                    $id,
                    $this->currentUserId()
                );

                // Update restock_item quantity_received
                Database::getInstance()->update(
                    'restock_items',
                    ['quantity_received' => $qty],
                    'restock_id = ? AND stock_product_id = ?',
                    [$id, $stockProductId]
                );
            }

            NotificationService::restockDelivered($id, $order['order_number']);
        }

        AuditService::log(AUDIT_UPDATE, MODULE_INVENTORY, $id, $order, RestockOrder::find($id), "Updated restock #{$id}");
        return $this->success(null, 'Restock updated');
    }
}


