<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Invoice;
use App\Core\Database;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->requirePermission(MODULE_INVOICES, ACTION_VIEW);
        [$page, $perPage] = $this->paginate($request);
        $filters = $request->only(['search', 'date_from', 'date_to', 'sort', 'order', 'status', 'method', 'processed_by']);
        $result  = Invoice::searchItems($filters, $page, $perPage);

        // Staff list for processed_by filter
        $staffList = Database::getInstance()->select(
            "SELECT id, CONCAT(first_name,' ',last_name) as name FROM users WHERE deleted_at IS NULL ORDER BY first_name ASC"
        );

        return $this->view('transactions/index', [
            'transactions' => $result['data'],
            'pagination'   => $result['pagination'],
            'filters'      => $filters,
            'staffList'    => $staffList,
            'title'        => 'Transactions',
            'pageTitle'    => 'Transactions',
        ]);
    }
}
