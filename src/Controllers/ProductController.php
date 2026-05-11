<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Product;
use App\Models\Category;
use App\Models\Color;
use App\Models\Size;
use App\Models\Type;
use App\Models\ProductStockRequirement;
use App\Models\StockProduct;
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

        // Attach computed stock (from stock products) for each product on this page
        $ids        = array_column($result['data'], 'id');
        $stockMap   = StockProduct::computeMaxSellableForProducts($ids);
        $result['data'] = array_map(function ($p) use ($stockMap) {
            $info = $stockMap[$p['id']] ?? ['computed_stock' => 0, 'stock_status' => 'out_of_stock'];
            $p['computed_stock'] = $info['computed_stock'];
            $p['stock_status']   = $info['stock_status'];
            return $p;
        }, $result['data']);

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
            'title'      => 'Products',
            'pageTitle' => 'Products',
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
        if (($file = $request->file('image')) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $data['image_path'] = FileHelper::upload($file, 'products');
        }

        // Remove legacy stock fields — stock is managed through stock products
        unset($data['initial_stock'], $data['current_stock']);

        $productId = Product::create($data);

        // Save stock requirements if provided
        $requirements = $request->input('stock_requirements', []);
        if (!empty($requirements)) {
            ProductStockRequirement::saveForProduct($productId, $requirements);
        }

        AuditService::log(AUDIT_CREATE, MODULE_PRODUCTS, $productId, null, Product::find($productId), "Created product: {$data['name']}");
        return $this->success(['id' => $productId], 'Product created', 201);
    }

    public function update(Request $request): Response
    {
        $this->requirePermission(MODULE_PRODUCTS, ACTION_EDIT);
        $id  = (int)$request->param('product_id');
        $old = Product::findOrFail($id);

        $data = $request->only(['sku', 'name', 'description', 'category_id', 'color_id', 'size_id', 'cost_price', 'selling_price', 'unit_type', 'barcode', 'status']);

        if (($file = $request->file('image')) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $data['image_path'] = FileHelper::upload($file, 'products');
        }

        Product::update($id, array_filter($data, fn($v) => $v !== null));

        // Update stock requirements if provided
        $requirements = $request->input('stock_requirements');
        if ($requirements !== null) {
            ProductStockRequirement::saveForProduct($id, $requirements);
        }

        AuditService::log(AUDIT_UPDATE, MODULE_PRODUCTS, $id, $old, Product::find($id), "Updated product #{$id}");
        return $this->success(null, 'Product updated');
    }

    /** GET /api/v1/products/{id}/stock-requirements */
    public function getRequirements(Request $request): Response
    {
        $this->requirePermission(MODULE_PRODUCTS, ACTION_VIEW);
        $id           = (int)$request->param('product_id');
        $requirements = ProductStockRequirement::forProduct($id);
        $maxSellable  = StockProduct::computeMaxSellable($id);

        return $this->success([
            'requirements' => $requirements,
            'max_sellable' => $maxSellable,
        ]);
    }

    /** PUT /api/v1/products/{id}/stock-requirements */
    public function saveRequirements(Request $request): Response
    {
        $this->requirePermission(MODULE_PRODUCTS, ACTION_EDIT);
        $id = (int)$request->param('product_id');
        Product::findOrFail($id);

        $requirements = $request->input('requirements', []);
        ProductStockRequirement::saveForProduct($id, $requirements);

        AuditService::log(AUDIT_UPDATE, MODULE_PRODUCTS, $id, null, null, "Updated stock requirements for product #{$id}");
        return $this->success(null, 'Stock requirements saved');
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

    public function downloadTemplate(Request $request): Response
    {
        $this->requirePermission(MODULE_PRODUCTS, ACTION_VIEW);
        $xlsx = $this->generateImportTemplateXlsx();
        return (new Response())
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="products_import_template.xlsx"')
            ->setHeader('Content-Length', (string)mb_strlen($xlsx, '8bit'))
            ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->setHeader('Pragma', 'no-cache')
            ->setBody($xlsx);
    }

    private function generateImportTemplateXlsx(): string
    {
        $db = Product::db();
        $categories = $db->select("SELECT name FROM categories WHERE deleted_at IS NULL ORDER BY name");
        $types      = $db->select("SELECT name FROM types ORDER BY name");
        $colors     = $db->select("SELECT name FROM colors ORDER BY name");
        $sizes      = $db->select("SELECT name FROM sizes ORDER BY name");

        $catNames   = array_column($categories, 'name');
        $typeNames  = array_column($types,      'name');
        $colorNames = array_column($colors,     'name');
        $sizeNames  = array_column($sizes,      'name');

        $esc = fn($v) => htmlspecialchars((string)$v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        // Build Reference sheet rows (col A=Category, B=Type, C=Color, D=Size)
        $refRows  = max(count($catNames), count($typeNames), count($colorNames), count($sizeNames));
        $refXml   = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\r\n"
                  . "<worksheet xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\">"
                  . "<sheetData>"
                  // header row
                  . "<row r=\"1\"><c r=\"A1\" t=\"inlineStr\"><is><t>Category</t></is></c>"
                  . "<c r=\"B1\" t=\"inlineStr\"><is><t>Type</t></is></c>"
                  . "<c r=\"C1\" t=\"inlineStr\"><is><t>Color</t></is></c>"
                  . "<c r=\"D1\" t=\"inlineStr\"><is><t>Size</t></is></c></row>";
        for ($i = 0; $i < $refRows; $i++) {
            $r = $i + 2;
            $a = isset($catNames[$i])   ? "<c r=\"A{$r}\" t=\"inlineStr\"><is><t>{$esc($catNames[$i])}</t></is></c>"   : '';
            $b = isset($typeNames[$i])  ? "<c r=\"B{$r}\" t=\"inlineStr\"><is><t>{$esc($typeNames[$i])}</t></is></c>"  : '';
            $c = isset($colorNames[$i]) ? "<c r=\"C{$r}\" t=\"inlineStr\"><is><t>{$esc($colorNames[$i])}</t></is></c>" : '';
            $d = isset($sizeNames[$i])  ? "<c r=\"D{$r}\" t=\"inlineStr\"><is><t>{$esc($sizeNames[$i])}</t></is></c>"  : '';
            $refXml .= "<row r=\"{$r}\">{$a}{$b}{$c}{$d}</row>";
        }
        $refXml .= "</sheetData></worksheet>";

        // Build Products sheet
        $colHeaders = ['SKU*','Name*','Description','Cost Price*','Selling Price*','Initial Stock','Low Stock Alert','Category','Type','Color','Size','Status'];
        $colLetters = ['A','B','C','D','E','F','G','H','I','J','K','L'];
        $headerRow  = "<row r=\"1\">";
        foreach ($colHeaders as $idx => $h) {
            $cl = $colLetters[$idx];
            $headerRow .= "<c r=\"{$cl}1\" t=\"inlineStr\"><is><t>{$esc($h)}</t></is></c>";
        }
        $headerRow .= "</row>";

        // Sample row
        $sample = ['SKU001','Sample Product','Optional description','10.00','25.00','100','10',
            $catNames[0] ?? 'Plain T-Shirt', $typeNames[0] ?? 'Pro Club',
            $colorNames[0] ?? 'White', $sizeNames[0] ?? 'Small', 'active'];
        $sampleRow = "<row r=\"2\">";
        foreach ($sample as $idx => $val) {
            $cl = $colLetters[$idx];
            $sampleRow .= "<c r=\"{$cl}2\" t=\"inlineStr\"><is><t>{$esc($val)}</t></is></c>";
        }
        $sampleRow .= "</row>";

        // Data validations (rows 2..1001)
        $catCount   = max(count($catNames), 1);
        $typeCount  = max(count($typeNames), 1);
        $colorCount = max(count($colorNames), 1);
        $sizeCount  = max(count($sizeNames), 1);
        // Columns: A=SKU B=Name C=Desc D=Cost E=Sell F=Stock G=LowAlert H=Cat I=Type J=Color K=Size L=Status
        $dvCat   = "<dataValidation type=\"list\" showDropDown=\"0\" showErrorMessage=\"1\" errorTitle=\"Invalid Category\" error=\"Pick from the list\" sqref=\"H2:H1001\"><formula1>Reference!\$A\$2:\$A\$" . ($catCount   + 1) . "</formula1></dataValidation>";
        $dvType  = "<dataValidation type=\"list\" showDropDown=\"0\" showErrorMessage=\"1\" errorTitle=\"Invalid Type\"     error=\"Pick from the list\" sqref=\"I2:I1001\"><formula1>Reference!\$B\$2:\$B\$" . ($typeCount  + 1) . "</formula1></dataValidation>";
        $dvColor = "<dataValidation type=\"list\" showDropDown=\"0\" showErrorMessage=\"1\" errorTitle=\"Invalid Color\"    error=\"Pick from the list\" sqref=\"J2:J1001\"><formula1>Reference!\$C\$2:\$C\$" . ($colorCount + 1) . "</formula1></dataValidation>";
        $dvSize  = "<dataValidation type=\"list\" showDropDown=\"0\" showErrorMessage=\"1\" errorTitle=\"Invalid Size\"     error=\"Pick from the list\" sqref=\"K2:K1001\"><formula1>Reference!\$D\$2:\$D\$" . ($sizeCount  + 1) . "</formula1></dataValidation>";
        $dvStat  = "<dataValidation type=\"list\" showDropDown=\"0\" showErrorMessage=\"1\" errorTitle=\"Invalid Status\"   error=\"Pick from the list\" sqref=\"L2:L1001\"><formula1>&quot;active,inactive&quot;</formula1></dataValidation>";

        $prodXml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\r\n"
                 . "<worksheet xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\">"
                 . "<sheetData>{$headerRow}{$sampleRow}</sheetData>"
                 . "<dataValidations count=\"5\">{$dvCat}{$dvType}{$dvColor}{$dvSize}{$dvStat}</dataValidations>"
                 . "</worksheet>";

        // Build workbook XML
        $workbookXml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\r\n"
            . "<workbook xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\" "
            . "xmlns:r=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships\">"
            . "<sheets>"
            . "<sheet name=\"Products\" sheetId=\"1\" r:id=\"rId1\"/>"
            . "<sheet name=\"Reference\" sheetId=\"2\" r:id=\"rId2\"/>"
            . "</sheets>"
            . "</workbook>";

        $workbookRels = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\r\n"
            . "<Relationships xmlns=\"http://schemas.openxmlformats.org/package/2006/relationships\">"
            . "<Relationship Id=\"rId1\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet\" Target=\"worksheets/sheet1.xml\"/>"
            . "<Relationship Id=\"rId2\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet\" Target=\"worksheets/sheet2.xml\"/>"
            . "</Relationships>";

        $contentTypes = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\r\n"
            . "<Types xmlns=\"http://schemas.openxmlformats.org/package/2006/content-types\">"
            . "<Default Extension=\"rels\" ContentType=\"application/vnd.openxmlformats-package.relationships+xml\"/>"
            . "<Default Extension=\"xml\"  ContentType=\"application/xml\"/>"
            . "<Override PartName=\"/xl/workbook.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml\"/>"
            . "<Override PartName=\"/xl/worksheets/sheet1.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml\"/>"
            . "<Override PartName=\"/xl/worksheets/sheet2.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml\"/>"
            . "</Types>";

        $rootRels = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\r\n"
            . "<Relationships xmlns=\"http://schemas.openxmlformats.org/package/2006/relationships\">"
            . "<Relationship Id=\"rId1\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument\" Target=\"xl/workbook.xml\"/>"
            . "</Relationships>";

        // Create XLSX in-memory using ZipArchive
        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip     = new \ZipArchive();
        $zip->open($tmpFile, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml',             $contentTypes);
        $zip->addFromString('_rels/.rels',                     $rootRels);
        $zip->addFromString('xl/workbook.xml',                 $workbookXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels',      $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml',        $prodXml);
        $zip->addFromString('xl/worksheets/sheet2.xml',        $refXml);
        $zip->close();

        $contents = file_get_contents($tmpFile);
        unlink($tmpFile);
        return $contents;
    }

    public function bulkImport(Request $request): Response
    {
        $this->requirePermission(MODULE_PRODUCTS, ACTION_ADD);
        $file = $request->file('file');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return $this->error('No file uploaded');
        }

        $db = Product::db();
        $catMap   = array_column($db->select("SELECT id, name FROM categories WHERE deleted_at IS NULL"), 'id', 'name');
        $typeMap  = array_column($db->select("SELECT id, name FROM types"), 'id', 'name');
        $colorMap = array_column($db->select("SELECT id, name FROM colors"), 'id', 'name');
        $sizeMap  = array_column($db->select("SELECT id, name FROM sizes"), 'id', 'name');

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if ($ext === 'xlsx') {
            $rows = $this->parseXlsx($file['tmp_name']);
        } else {
            $rows = [];
            $fp   = fopen($file['tmp_name'], 'r');
            $headers = fgetcsv($fp);
            while (($row = fgetcsv($fp)) !== false) {
                $rows[] = array_combine($headers, $row);
            }
            fclose($fp);
        }

        if (empty($rows)) {
            return $this->error('No data rows could be read from the file. Make sure you are using the correct .xlsx template and your products start from Row 3 (Row 1 = headers, Row 2 = example).');
        }

        // Friendly column labels matching the import template layout
        $colLabel = [
            'sku'             => 'Col A — SKU',
            'name'            => 'Col B — Name',
            'description'     => 'Col C — Description',
            'cost_price'      => 'Col D — Cost Price',
            'selling_price'   => 'Col E — Selling Price',
            'initial_stock'   => 'Col F — Initial Stock',
            'low_stock_alert' => 'Col G — Low Stock Alert',
            'category'        => 'Col H — Category',
            'type'            => 'Col I — Type',
            'color'           => 'Col J — Color',
            'size'            => 'Col K — Size',
            'status'          => 'Col L — Status',
        ];

        $created = 0;
        $skipped = 0;
        $errors  = []; // each entry: {row, col, value, message}

        foreach ($rows as $rowIdx => $data) {
            $rowNum    = $rowIdx + 2; // header=row1, data starts row2
            $rowErrors = [];

            // Skip fully blank rows
            if (empty($data['sku']) && empty($data['name'])) continue;

            // --- Required field checks ---
            if (empty($data['sku'])) {
                $rowErrors[] = ['row' => $rowNum, 'col' => $colLabel['sku'], 'value' => '', 'message' => 'SKU is required'];
            }
            if (empty($data['name'])) {
                $rowErrors[] = ['row' => $rowNum, 'col' => $colLabel['name'], 'value' => $data['name'] ?? '', 'message' => 'Product name is required'];
            }
            if (!isset($data['cost_price']) || $data['cost_price'] === '') {
                $rowErrors[] = ['row' => $rowNum, 'col' => $colLabel['cost_price'], 'value' => '', 'message' => 'Cost price is required'];
            } elseif (!is_numeric($data['cost_price'])) {
                $rowErrors[] = ['row' => $rowNum, 'col' => $colLabel['cost_price'], 'value' => $data['cost_price'], 'message' => 'Cost price must be a number'];
            }
            if (!isset($data['selling_price']) || $data['selling_price'] === '') {
                $rowErrors[] = ['row' => $rowNum, 'col' => $colLabel['selling_price'], 'value' => '', 'message' => 'Selling price is required'];
            } elseif (!is_numeric($data['selling_price'])) {
                $rowErrors[] = ['row' => $rowNum, 'col' => $colLabel['selling_price'], 'value' => $data['selling_price'], 'message' => 'Selling price must be a number'];
            }

            // --- Duplicate SKU check ---
            if (!empty($data['sku']) && Product::findBySku(trim($data['sku']))) {
                $rowErrors[] = ['row' => $rowNum, 'col' => $colLabel['sku'], 'value' => $data['sku'], 'message' => "SKU already exists in the system"];
            }

            if (!empty($rowErrors)) {
                $errors  = array_merge($errors, $rowErrors);
                $skipped++;
                continue;
            }

            // --- Lookup resolution ---
            $lookupFailed = false;
            $resolveId    = function($val, array $map, string $field, string $col) use (&$errors, &$lookupFailed, $rowNum): ?int {
                if (empty($val)) return null;
                if (is_numeric($val)) return (int)$val;
                $id = $map[$val] ?? null;
                if ($id === null) {
                    $errors[]     = ['row' => $rowNum, 'col' => $col, 'value' => $val, 'message' => "\"$val\" not found — check the Reference sheet for valid values"];
                    $lookupFailed = true;
                }
                return $id;
            };

            $categoryId = $resolveId($data['category'] ?? ($data['category_id'] ?? ''), $catMap,   'Category', $colLabel['category']);
            $typeId     = $resolveId($data['type']     ?? ($data['type_id']     ?? ''), $typeMap,  'Type',     $colLabel['type']);
            $colorId    = $resolveId($data['color']    ?? ($data['color_id']    ?? ''), $colorMap, 'Color',    $colLabel['color']);
            $sizeId     = $resolveId($data['size']     ?? ($data['size_id']     ?? ''), $sizeMap,  'Size',     $colLabel['size']);

            // --- Status validation ---
            $rawStatus = strtolower($data['status'] ?? '');
            if ($rawStatus !== '' && !in_array($rawStatus, ['active', 'inactive'])) {
                $errors[]     = ['row' => $rowNum, 'col' => $colLabel['status'], 'value' => $data['status'], 'message' => "Must be \"active\" or \"inactive\""];
                $lookupFailed = true;
            }

            if ($lookupFailed) { $skipped++; continue; }

            // --- Insert ---
            $stock    = (int)($data['initial_stock'] ?? 0);
            $lowAlert = isset($data['low_stock_alert']) && $data['low_stock_alert'] !== '' ? (int)$data['low_stock_alert'] : 10;
            $status   = in_array($rawStatus, ['active', 'inactive']) ? $rawStatus : 'active';

            try {
                $productId = Product::create([
                    'sku'             => trim($data['sku']),
                    'name'            => trim($data['name']),
                    'description'     => $data['description'] ?? null,
                    'cost_price'      => (float)$data['cost_price'],
                    'selling_price'   => (float)$data['selling_price'],
                    'current_stock'   => $stock,
                    'low_stock_alert' => $lowAlert,
                    'category_id'     => $categoryId,
                    'type_id'         => $typeId,
                    'color_id'        => $colorId,
                    'size_id'         => $sizeId,
                    'status'          => $status,
                ]);
                Product::db()->insert('inventory', [
                    'product_id'       => $productId,
                    'quantity_on_hand' => $stock,
                    'stock_status'     => $stock > 0 ? STOCK_IN_STOCK : STOCK_OUT,
                ]);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = ['row' => $rowNum, 'col' => '—', 'value' => $data['sku'] ?? '', 'message' => 'Database error: ' . $e->getMessage()];
                $skipped++;
            }
        }

        $msg = $created ? "{$created} product(s) imported successfully" : "No products were imported";
        if ($skipped) $msg .= ", {$skipped} row(s) skipped due to errors";
        return $this->success(['created' => $created, 'skipped' => $skipped, 'errors' => $errors], $msg);
    }

    private function parseXlsx(string $filePath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) return [];

        $sheetXml  = $zip->getFromName('xl/worksheets/sheet1.xml');
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();
        if (!$sheetXml) return [];

        // Strip ALL xmlns declarations before parsing.
        // SimpleXML's ->children($ns) traversal fails silently when namespace URIs
        // don't match exactly (extra attributes, encoding, etc.).
        // Removing them lets us access every element with plain ->element syntax.
        $stripNs = static fn(string $xml): string =>
            preg_replace('/\s+xmlns(?::[a-zA-Z0-9_]+)?="[^"]*"/', '', $xml);

        // ── Shared strings ───────────────────────────────────────────────────────
        // Excel stores text as shared strings (t="s").
        // Bold/formatted cells use rich-text runs: <r><t>text</t></r>
        // Plain cells use direct: <t>text</t>
        $shared = [];
        if ($sharedXml) {
            $ss = @simplexml_load_string($stripNs($sharedXml));
            if ($ss !== false) {
                foreach ($ss->si as $si) {
                    $text = '';
                    if (isset($si->t)) $text = (string)$si->t;          // plain
                    foreach ($si->r as $r) {                             // rich-text runs
                        if (isset($r->t)) $text .= (string)$r->t;
                    }
                    $shared[] = $text;
                }
            }
        }

        // ── Sheet data ───────────────────────────────────────────────────────────
        $sheet = @simplexml_load_string($stripNs($sheetXml));
        if (!$sheet) return [];

        $rows    = [];
        $headers = []; // col-letter → normalized key

        foreach ($sheet->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $cell) {
                $ref       = (string)$cell['r'];
                $colLetter = rtrim($ref, '0123456789');
                $type      = (string)$cell['t'];

                if ($type === 's') {
                    $val = $shared[(int)(string)$cell->v] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = (string)$cell->is->t;
                } else {
                    $val = (string)$cell->v;  // number / bool / empty
                }
                $cells[$colLetter] = $val;
            }

            if (empty($headers)) {
                // First non-empty row is the header row
                foreach ($cells as $col => $val) {
                    $key = strtolower(str_replace(['*', ' '], ['', '_'], trim($val)));
                    if ($key !== '') $headers[$col] = $key;
                }
            } else {
                if (empty(array_filter($cells, fn($v) => trim($v) !== ''))) continue; // blank row
                $assoc = [];
                foreach ($headers as $col => $key) {
                    $assoc[$key] = trim($cells[$col] ?? '');
                }
                $rows[] = $assoc;
            }
        }

        return $rows;
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
