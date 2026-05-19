<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Invoice;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->requirePermission(MODULE_INVOICES, ACTION_VIEW);
        [$page, $perPage] = $this->paginate($request);
        $filters = $request->only(['search', 'date_from', 'date_to', 'sort', 'order']);
        $result  = Invoice::searchItems($filters, $page, $perPage);

        return $this->view('transactions/index', [
            'transactions' => $result['data'],
            'pagination'   => $result['pagination'],
            'filters'      => $filters,
            'title'        => 'Transactions',
            'pageTitle'    => 'Transactions',
        ]);
    }
}
