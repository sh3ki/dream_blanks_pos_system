<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Models\StockProduct;
use App\Models\StockMovement;
use App\Models\RestockOrder;
use App\Models\ProductStockRequirement;
use App\Models\Color;
use App\Models\Size;
use App\Models\Type;
use App\Helpers\FileHelper;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Exceptions\ValidationException;

class StockProductController extends Controller
{
    // -------------------------------------------------------------------------
    // Web
    // -------------------------------------------------------------------------

    public function index(Request $request): Response
    {
        $this->requirePermission(MODULE_STOCK_PRODUCTS, ACTION_VIEW);
        [$page, $perPage] = $this->paginate($request);
        $filters = $request->only(['search', 'type_id', 'color_id', 'size_id', 'status', 'stock_status', 'sort', 'order']);
        $result  = StockProduct::search($filters, $page, $perPage);

        if ($request->isApi()) {
            return $this->success(['stock_products' => $result['data'], 'pagination' => $result['pagination']]);
        }

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

        $spStats = $db->selectOne(
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

        return $this->view('stock-products/index', [
            'stock_products'     => $result['data'],
            'pagination'         => $result['pagination'],
            'types'              => Type::allActive(),
            'colors'             => Color::allActive(),
            'sizes'              => Size::allActive(),
            'filters'            => $filters,
            'active_tab'         => $request->query('tab', 'stock-products'),
            'history'            => $historyResult['data'],
            'hist_pagination'    => $historyResult['pagination'],
            'hist_filters'       => $histFiltersMap,
            'restock_orders'     => $restockResult['data'],
            'restock_pagination' => $restockResult['pagination'],
            'restock_filters'    => $restockFilters,
            'sp_stats'           => $spStats,
            'restock_stats'      => $restockStats,
            'all_users'          => $db->select("SELECT id, CONCAT(first_name,' ',last_name) as name FROM users WHERE deleted_at IS NULL ORDER BY first_name", []),
            'title'              => 'Stock Products',
            'pageTitle'          => 'Stock Products',
        ]);
    }

    // -------------------------------------------------------------------------
    // API
    // -------------------------------------------------------------------------

    /** GET /api/v1/stock-products/:id */
    public function show(Request $request): Response
    {
        $this->requirePermission(MODULE_STOCK_PRODUCTS, ACTION_VIEW);
        $id = (int)$request->param('stock_product_id');
        $sp = StockProduct::findWithDetails($id);
        if (!$sp) {
            return $this->error('Stock product not found', 404);
        }

        // Include which sellable products use this stock product
        $usedBy = ProductStockRequirement::productsUsing($id);

        return $this->success(array_merge($sp, ['used_by_products' => $usedBy]));
    }

    /** POST /api/v1/stock-products */
    public function store(Request $request): Response
    {
        $this->requirePermission(MODULE_STOCK_PRODUCTS, ACTION_ADD);

        $data = $request->only(['code', 'name', 'description', 'type_id', 'color_id', 'size_id', 'low_stock_alert', 'status']);

        if (empty($data['code']) || empty($data['name'])) {
            throw new ValidationException(['code' => ['Code and name are required']]);
        }

        // Ensure unique code
        $existing = StockProduct::db()->selectOne(
            "SELECT id FROM stock_products WHERE code = ? AND deleted_at IS NULL",
            [$data['code']]
        );
        if ($existing) {
            throw new ValidationException(['code' => ['Stock product code already exists']]);
        }

        $data['current_qty']      = 0;
        $data['low_stock_alert']  = (int)($data['low_stock_alert'] ?? 10);
        $data['status']           = $data['status'] ?? 'active';

        if (($file = $request->file('image')) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $data['image_path'] = FileHelper::upload($file, 'stock-products');
        }

        $id = StockProduct::create($data);

        // Initialise inventory record at zero
        StockProduct::syncInventoryStatus($id);

        AuditService::log(AUDIT_CREATE, MODULE_STOCK_PRODUCTS, $id, null, StockProduct::find($id), "Created stock product: {$data['name']}");
        return $this->success(['id' => $id], 'Stock product created', 201);
    }

    /** PUT /api/v1/stock-products/:id */
    public function update(Request $request): Response
    {
        $this->requirePermission(MODULE_STOCK_PRODUCTS, ACTION_EDIT);
        $id  = (int)$request->param('stock_product_id');
        $old = StockProduct::findOrFail($id);

        $data = $request->only(['code', 'name', 'description', 'type_id', 'color_id', 'size_id', 'low_stock_alert', 'status']);

        if (($file = $request->file('image')) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $data['image_path'] = FileHelper::upload($file, 'stock-products');
        }

        // If code changed, enforce uniqueness
        if (!empty($data['code']) && $data['code'] !== $old['code']) {
            $existing = StockProduct::db()->selectOne(
                "SELECT id FROM stock_products WHERE code = ? AND deleted_at IS NULL AND id != ?",
                [$data['code'], $id]
            );
            if ($existing) {
                throw new ValidationException(['code' => ['Stock product code already exists']]);
            }
        }

        StockProduct::update($id, array_filter($data, fn($v) => $v !== null));

        // Re-sync inventory status in case low_stock_alert changed
        StockProduct::syncInventoryStatus($id);

        AuditService::log(AUDIT_UPDATE, MODULE_STOCK_PRODUCTS, $id, $old, StockProduct::find($id), "Updated stock product #{$id}");
        return $this->success(null, 'Stock product updated');
    }

    /** DELETE /api/v1/stock-products/:id */
    public function destroy(Request $request): Response
    {
        $this->requirePermission(MODULE_STOCK_PRODUCTS, ACTION_DELETE);
        $id  = (int)$request->param('stock_product_id');
        $old = StockProduct::findOrFail($id);

        // Prevent deletion if products are still assigned to this stock product
        $usedBy = ProductStockRequirement::productsUsing($id);
        if (!empty($usedBy)) {
            $names = implode(', ', array_column($usedBy, 'name'));
            throw new ValidationException(['id' => ["Cannot delete — still used by: {$names}"]]);
        }

        StockProduct::delete($id);
        AuditService::log(AUDIT_DELETE, MODULE_STOCK_PRODUCTS, $id, $old, null, "Deleted stock product #{$id}");
        return $this->success(null, 'Stock product deleted');
    }

    /** POST /api/v1/stock-products/:id/adjust */
    public function adjust(Request $request): Response
    {
        $this->requirePermission(MODULE_STOCK_PRODUCTS, ACTION_EDIT);
        $id       = (int)$request->param('stock_product_id');
        $sp       = StockProduct::findOrFail($id);
        $type     = $request->input('type');       // 'add' or 'deduct'
        $qty      = (int)$request->input('quantity', 0);
        $reason   = trim($request->input('reason', 'Manual adjustment'));

        if (!in_array($type, ['add', 'deduct'], true) || $qty <= 0) {
            throw new ValidationException(['quantity' => ['Provide a positive quantity and a valid type (add/deduct)']]);
        }

        if ($type === 'deduct') {
            if ((int)$sp['current_qty'] < $qty) {
                throw new ValidationException(['quantity' => ['Cannot deduct more than current stock (' . $sp['current_qty'] . ')']]);
            }
            StockProduct::decrementQty($id, $qty);
            $movement = -$qty;
            $movementType = MOVEMENT_ADJUSTMENT;
        } else {
            StockProduct::incrementQty($id, $qty);
            $movement = $qty;
            $movementType = MOVEMENT_ADJUSTMENT;
        }

        StockMovement::logForStockProduct($id, $movementType, $movement, $reason, null, $this->currentUserId());

        // Low stock notification
        $updated = StockProduct::find($id);
        if ($updated && (int)$updated['current_qty'] <= (int)($updated['low_stock_alert'] ?? 10)) {
            NotificationService::lowStockAlert($id, $sp['name'], (int)$updated['current_qty']);
        }

        AuditService::log(AUDIT_UPDATE, MODULE_STOCK_PRODUCTS, $id, $sp, StockProduct::find($id), "Manual {$type} {$qty} for stock product #{$id}: {$reason}");
        return $this->success(['current_qty' => (int)$updated['current_qty']], 'Stock adjusted');
    }

    /** GET /api/v1/stock-products/:id/movements */
    public function movements(Request $request): Response
    {
        $this->requirePermission(MODULE_STOCK_PRODUCTS, ACTION_VIEW);
        $id       = (int)$request->param('stock_product_id');
        StockProduct::findOrFail($id);
        $movements = StockMovement::forStockProduct($id);
        return $this->success(['movements' => $movements]);
    }

    /** GET /api/v1/stock-products (dropdown list — no pagination) */
    public function list(Request $request): Response
    {
        $this->requirePermission(MODULE_STOCK_PRODUCTS, ACTION_VIEW);
        return $this->success(['stock_products' => StockProduct::allActive()]);
    }

    /** GET /api/v1/stock-products/all — full list with qty for pickers */
    public function allForSelect(Request $request): Response
    {
        $this->requirePermission(MODULE_STOCK_PRODUCTS, ACTION_VIEW);
        $sps = StockProduct::db()->select(
            "SELECT sp.id, sp.code, sp.name, sp.current_qty, sp.low_stock_alert,
                    sp.type_id, sp.color_id, sp.size_id,
                    t.name as type_name, c.name as color_name, s.name as size_name
             FROM stock_products sp
             LEFT JOIN types t  ON t.id  = sp.type_id
             LEFT JOIN colors c ON c.id  = sp.color_id
             LEFT JOIN sizes s  ON s.id  = sp.size_id
             WHERE sp.status = 'active' AND sp.deleted_at IS NULL
             ORDER BY sp.name ASC"
        );
        return $this->success(['stock_products' => $sps]);
    }

    /** POST /api/v1/stock-products/bulk-adjust */
    public function bulkAdjust(Request $request): Response
    {
        $this->requirePermission(MODULE_STOCK_PRODUCTS, ACTION_EDIT);
        $items  = $request->input('items', []);
        $type   = $request->input('type');
        $reason = trim($request->input('reason', 'Manual adjustment'));

        if (!in_array($type, ['add', 'deduct'], true)) {
            throw new ValidationException(['type' => ['Type must be add or deduct']]);
        }
        if (empty($items)) {
            throw new ValidationException(['items' => ['At least one item is required']]);
        }

        $adjusted = 0;
        foreach ($items as $item) {
            $id  = (int)($item['id'] ?? 0);
            $qty = (int)($item['quantity'] ?? 0);
            if ($id <= 0 || $qty <= 0) continue;
            $sp = StockProduct::find($id);
            if (!$sp) continue;

            if ($type === 'deduct') {
                if ((int)$sp['current_qty'] < $qty) continue;
                StockProduct::decrementQty($id, $qty);
                StockMovement::logForStockProduct($id, MOVEMENT_ADJUSTMENT, -$qty, $reason, null, $this->currentUserId());
            } else {
                StockProduct::incrementQty($id, $qty);
                StockMovement::logForStockProduct($id, MOVEMENT_ADJUSTMENT, $qty, $reason, null, $this->currentUserId());
            }
            $updated = StockProduct::find($id);
            if ($updated && (int)$updated['current_qty'] <= (int)($updated['low_stock_alert'] ?? 10)) {
                NotificationService::lowStockAlert($id, $sp['name'], (int)$updated['current_qty']);
            }
            AuditService::log(AUDIT_UPDATE, MODULE_STOCK_PRODUCTS, $id, $sp, StockProduct::find($id), "Bulk {$type} {$qty} for stock product #{$id}: {$reason}");
            $adjusted++;
        }

        return $this->success(null, "Stock adjusted for {$adjusted} item(s)");
    }

    /** GET /api/v1/stock-products/import-template */
    public function downloadTemplate(Request $request): Response
    {
        $this->requirePermission(MODULE_STOCK_PRODUCTS, ACTION_VIEW);
        $headers = ['Code', 'Name', 'Description', 'Type', 'Color', 'Size', 'Low Stock Alert', 'Initial Qty', 'Status'];
        $sample  = ['SP-001', 'Sample Stock Product', 'Optional description', 'DTF', 'Black', 'Small', '10', '0', 'active'];
        $csv     = implode(',', $headers) . "\r\n" . implode(',', $sample) . "\r\n";
        return (new \App\Core\Response())
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="stock_products_import_template.csv"')
            ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->setBody($csv);
    }

    /** POST /api/v1/stock-products/bulk-import */
    public function bulkImport(Request $request): Response
    {
        $this->requirePermission(MODULE_STOCK_PRODUCTS, ACTION_ADD);
        $file = $request->file('file');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return $this->error('No file uploaded');
        }

        $db       = StockProduct::db();
        $typeMap  = array_column($db->select("SELECT id, name FROM types"), 'id', 'name');
        $colorMap = array_column($db->select("SELECT id, name FROM colors"), 'id', 'name');
        $sizeMap  = array_column($db->select("SELECT id, name FROM sizes"), 'id', 'name');

        $fp      = fopen($file['tmp_name'], 'r');
        $rawHdrs = fgetcsv($fp);
        if (!$rawHdrs) { fclose($fp); return $this->error('Could not read CSV headers.'); }
        $headers = array_map('strtolower', array_map('trim', $rawHdrs));

        $rows = [];
        while (($row = fgetcsv($fp)) !== false) {
            if (count($row) >= count($headers)) {
                $rows[] = array_combine($headers, array_slice($row, 0, count($headers)));
            }
        }
        fclose($fp);

        if (empty($rows)) return $this->error('No data rows found in the file.');

        $created = 0; $skipped = 0; $errors = [];

        foreach ($rows as $idx => $data) {
            $rowNum = $idx + 2;
            if (empty(trim($data['code'] ?? '')) && empty(trim($data['name'] ?? ''))) continue;

            $rowErr = [];
            if (empty(trim($data['code'] ?? ''))) $rowErr[] = ['row' => $rowNum, 'message' => 'Code is required'];
            if (empty(trim($data['name'] ?? ''))) $rowErr[] = ['row' => $rowNum, 'message' => 'Name is required'];

            if (!empty($rowErr)) { $errors = array_merge($errors, $rowErr); $skipped++; continue; }

            $resolveId = function($val, array $map) use (&$errors, $rowNum, &$skipped): ?int {
                if (empty(trim($val ?? ''))) return null;
                if (is_numeric($val)) return (int)$val;
                $id = $map[trim($val)] ?? null;
                if ($id === null) {
                    $errors[] = ['row' => $rowNum, 'message' => "\"$val\" not found"];
                    $skipped++;
                }
                return $id;
            };

            $typeId  = $resolveId($data['type']  ?? '', $typeMap);
            $colorId = $resolveId($data['color'] ?? '', $colorMap);
            $sizeId  = $resolveId($data['size']  ?? '', $sizeMap);
            if ($skipped > 0 && end($errors)['row'] === $rowNum) continue;

            $initQty = max(0, (int)($data['initial qty'] ?? $data['initial_qty'] ?? 0));
            $alert   = max(0, (int)($data['low stock alert'] ?? $data['low_stock_alert'] ?? 10));
            $status  = in_array(strtolower(trim($data['status'] ?? '')), ['active','inactive']) ? strtolower(trim($data['status'])) : 'active';

            $id = $db->insert('stock_products', [
                'code'            => trim($data['code']),
                'name'            => trim($data['name']),
                'description'     => trim($data['description'] ?? ''),
                'type_id'         => $typeId,
                'color_id'        => $colorId,
                'size_id'         => $sizeId,
                'current_qty'     => $initQty,
                'low_stock_alert' => $alert,
                'status'          => $status,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);

            if ($id && $initQty > 0) {
                StockProduct::syncInventoryStatus((int)$id);
                \App\Models\StockMovement::logForStockProduct((int)$id, MOVEMENT_RESTOCK, $initQty, 'Initial stock (import)', null, $this->currentUserId());
            }
            $created++;
        }

        $msg = "Imported {$created} stock product(s)." . ($skipped ? " {$skipped} row(s) skipped." : '');
        return $this->success(['created' => $created, 'skipped' => $skipped, 'errors' => $errors], $msg);
    }
}
