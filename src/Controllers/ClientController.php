<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Client;
use App\Models\ClientAddress;
use App\Models\ClientContact;
use App\Services\AuditService;
use App\Exceptions\ValidationException;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->requirePermission(MODULE_CLIENTS, ACTION_VIEW);
        [$page, $perPage] = $this->paginate($request);
        $search    = $request->query('search', '');
        $status    = $request->query('status', '');
        $sortField = $request->query('sort', 'created_at');
        $sortDir   = strtoupper($request->query('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $allowedSorts = ['first_name', 'last_name', 'email', 'status', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'created_at';
        $orderBy = "c.{$sortField}";

        if ($search) {
            $result = Client::search($search, $page, $perPage, $status, $orderBy, $sortDir);
        } else {
            $where  = "1=1";
            $params = [];
            if ($status) { $where .= " AND c.status=?"; $params[] = $status; }
            $result = Client::paginateWithDetails($page, $perPage, $where, $params, $orderBy, $sortDir);
        }

        if ($request->isApi()) {
            return $this->success(['clients' => $result['data'], 'pagination' => $result['pagination']]);
        }

        return $this->view('clients/index', [
            'clients'    => $result['data'],
            'pagination' => $result['pagination'],
            'search'     => $search,
            'status'     => $status,
            'sort'       => $sortField,
            'order'      => $sortDir,
            'title'      => 'Clients',
            'pageTitle' => 'Clients',
        ]);
    }

    public function show(Request $request): Response
    {
        $this->requirePermission(MODULE_CLIENTS, ACTION_VIEW);
        $id     = (int)$request->param('client_id');
        $client = Client::getWithDetails($id);
        if (!$client) return $this->error('Client not found', 404);
        return $request->isApi() ? $this->success($client) : $this->view('clients/show', ['client' => $client, 'title' => 'Client Details']);
    }

    public function store(Request $request): Response
    {
        $this->requirePermission(MODULE_CLIENTS, ACTION_ADD);
        $this->validateClient($request->all());

        $data = $request->only(['first_name', 'middle_name', 'last_name', 'email', 'status']);
        $id   = Client::create($data);

        // Save addresses (up to 3)
        foreach (array_slice((array)$request->input('addresses', []), 0, 3) as $addr) {
            if (!empty($addr['street_address']) && !empty($addr['city'])) {
                ClientAddress::create(array_merge($addr, ['client_id' => $id]));
            }
        }

        // Save contacts (up to 5)
        foreach (array_slice((array)$request->input('contacts', []), 0, 5) as $contact) {
            if (!empty($contact['contact_number'])) {
                ClientContact::create(array_merge($contact, ['client_id' => $id]));
            }
        }

        AuditService::log(AUDIT_CREATE, MODULE_CLIENTS, $id, null, Client::find($id), "Created client #{$id}");
        return $this->success(['id' => $id], 'Client created', 201);
    }

    public function update(Request $request): Response
    {
        $this->requirePermission(MODULE_CLIENTS, ACTION_EDIT);
        $id  = (int)$request->param('client_id');
        $old = Client::findOrFail($id);

        $data = $request->only(['first_name', 'middle_name', 'last_name', 'email', 'status']);
        Client::update($id, array_filter($data, fn($v) => $v !== null));

        AuditService::log(AUDIT_UPDATE, MODULE_CLIENTS, $id, $old, Client::find($id), "Updated client #{$id}");
        return $this->success(null, 'Client updated');
    }

    public function destroy(Request $request): Response
    {
        $this->requirePermission(MODULE_CLIENTS, ACTION_DELETE);
        $id  = (int)$request->param('client_id');
        $old = Client::findOrFail($id);
        Client::delete($id);
        AuditService::log(AUDIT_DELETE, MODULE_CLIENTS, $id, $old, null, "Deleted client #{$id}");
        return $this->success(null, 'Client deleted');
    }

    private function validateClient(array $data): void
    {
        $errors = [];
        if (empty($data['first_name'])) $errors['first_name'][] = 'First name is required';
        if (empty($data['last_name']))  $errors['last_name'][]  = 'Last name is required';
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Invalid email format';
        }
        if (!empty($errors)) throw new ValidationException($errors);
    }
}
