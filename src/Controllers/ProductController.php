<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Product;
use App\Models\Category;
use App\Models\Color;
use App\Models\Size;
use App\Models\Type;
use App\Services\AuditService;
use App\Helpers\FileHelper;
use App\Exceptions\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $this->requirePermission(MODULE_PRODUCTS, ACTION_VIEW);
        [$page, $perPage] = $this->paginate($request);
        $filters = $request->only(['search', 'category_id', 'type_id', 'color_id', 'size_id', 'status', 'sort', 'order']);
        $result  = Product::search($filters, $page, $perPage);

        if ($request->isApi()) {
            return $this->success(['products' => $result['data'], 'pagination' => $result['pagination']]);
        }

        return $this->view('products/index', [
            'products'   => $result['data'],
            'pagination' => $result['pagination'],
            'categories' => Category::allActive(),
            'colors'     => Color::allActive(),
            'sizes'      => Size::allActive(),
            'filters'    => $filters,
                        'types'      => Type::allActive(),
            'title'      => 'Products | Dream Blanks POS',
        ]);
    }

    public function show(Request $request): Response
    {
        $this->requirePermission(MODULE_PRODUCTS, ACTION_VIEW);
        $id      = (int)$request->param('product_id');
        $product = Product::findWithDetails($id);
        if (!$product) return $this->error('Product not found', 404);
        return $request->isApi() ? $this->success($product) : $this->view('products/show', ['product' => $product, 'title' => $product['name']]);
    }

    public function store(Request $request): Response
    {
        $this->requirePermission(MODULE_PRODUCTS, ACTION_ADD);
        $data = $this->validate($request->all());

        if (Product::findBySku($data['sku'])) {
            throw new ValidationException(['sku' => ['SKU already exists']]);
        }

        // Handle image upload
        $imagePath = null;
        if (($file = $request->file('image')) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $imagePath = FileHelper::upload($file, 'products');
            $data['image_path'] = $imagePath;
        }

        $initialStock = (int)($data['initial_stock'] ?? 0);
        unset($data['initial_stock']);

        $data['current_stock'] = $initialStock;
        $productId = Product::create($data);

        // Initialize inventory record
        Product::db()->insert('inventory', [
            'product_id'      => $productId,
            'quantity_on_hand'=> $initialStock,
            'quantity_reserved'=> 0,
            'stock_status'    => $initialStock > 0 ? STOCK_IN_STOCK : STOCK_OUT,
            'updated_by'      => $this->currentUserId(),
        ]);

        AuditService::log(AUDIT_CREATE, MODULE_PRODUCTS, $productId, null, Product::find($productId), "Created product: {$data['name']}");
        return $this->success(['id' => $productId], 'Product created', 201);
    }

    public function update(Request $request): Response
    {
        $this->requirePermission(MODULE_PRODUCTS, ACTION_EDIT);
        $id  = (int)$request->param('product_id');
        $old = Product::findOrFail($id);

        $data = $request->only(['sku', 'name', 'description', 'category_id', 'color_id', 'size_id', 'cost_price', 'selling_price', 'unit_type', 'low_stock_alert', 'barcode', 'status']);

        if (($file = $request->file('image')) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $data['image_path'] = FileHelper::upload($file, 'products');
        }

        Product::update($id, array_filter($data, fn($v) => $v !== null));
        AuditService::log(AUDIT_UPDATE, MODULE_PRODUCTS, $id, $old, Product::find($id), "Updated product #{$id}");
        return $this->success(null, 'Product updated');
    }

    public function destroy(Request $request): Response
    {
        $this->requirePermission(MODULE_PRODUCTS, ACTION_DELETE);
        $id  = (int)$request->param('product_id');
        $old = Product::findOrFail($id);
        Product::delete($id);
        AuditService::log(AUDIT_DELETE, MODULE_PRODUCTS, $id, $old, null, "Deleted product #{$id}");
        return $this->success(null, 'Product deleted');
    }

    public function bulkImport(Request $request): Response
    {
        $this->requirePermission(MODULE_PRODUCTS, ACTION_ADD);
        $file = $request->file('file');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return $this->error('No file uploaded');
        }

        $fp      = fopen($file['tmp_name'], 'r');
        $headers = fgetcsv($fp);
        $created = 0;
        $errors  = [];

        while (($row = fgetcsv($fp)) !== false) {
            try {
                $data = array_combine($headers, $row);
                if (empty($data['sku']) || empty($data['name'])) continue;
                if (Product::findBySku($data['sku'])) { $errors[] = "Duplicate SKU: {$data['sku']}"; continue; }

                $stock = (int)($data['initial_stock'] ?? 0);
                $productId = Product::create([
                    'sku'           => $data['sku'],
                    'name'          => $data['name'],
                    'description'   => $data['description'] ?? null,
                    'cost_price'    => (float)($data['cost_price'] ?? 0),
                    'selling_price' => (float)($data['selling_price'] ?? 0),
                    'current_stock' => $stock,
                    'status'        => 'active',
                ]);
                Product::db()->insert('inventory', ['product_id' => $productId, 'quantity_on_hand' => $stock, 'stock_status' => $stock > 0 ? STOCK_IN_STOCK : STOCK_OUT]);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
        fclose($fp);

        return $this->success(['created' => $created, 'errors' => $errors], "{$created} products imported");
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['sku']))            $errors['sku'][]            = 'SKU is required';
        if (empty($data['name']))           $errors['name'][]           = 'Name is required';
        if (!isset($data['cost_price']))    $errors['cost_price'][]     = 'Cost price is required';
        if (!isset($data['selling_price'])) $errors['selling_price'][]  = 'Selling price is required';
        if (!empty($errors)) throw new ValidationException($errors);
        return $data;
    }
}
