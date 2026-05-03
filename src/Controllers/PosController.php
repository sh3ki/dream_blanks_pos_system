<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Product;
use App\Models\Category;
use App\Models\Color;
use App\Models\Size;
use App\Models\Type;
use App\Services\PosService;

class PosController extends Controller
{
    private PosService $posService;

    public function __construct()
    {
        $this->posService = new PosService();
    }

    public function index(Request $request): Response
    {
        $this->requirePermission(MODULE_POS, ACTION_VIEW);
        return $this->view('pos/index', [
            'categories' => Category::allActive(),
            'types'      => Type::allActive(),
            'colors'     => Color::allActive(),
            'sizes'      => Size::allActive(),
            'title'      => 'Point of Sale',
            'pageTitle' => 'Point of Sale',
        ]);
    }

    public function products(Request $request): Response
    {
        $this->requirePermission(MODULE_POS, ACTION_VIEW);
        $filters  = $request->only(['search', 'category_id', 'type_id', 'color_id', 'size_id', 'limit']);
        $products = Product::forPos($filters);
        return $this->success(['products' => $products]);
    }

    public function checkout(Request $request): Response
    {
        $this->requirePermission(MODULE_POS, ACTION_ADD);
        $result = $this->posService->checkout($request->all(), $this->currentUserId());
        return $this->success($result, 'Checkout successful', 201);
    }
}
