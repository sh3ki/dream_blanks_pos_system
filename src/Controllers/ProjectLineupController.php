<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Category;
use App\Models\Client;
use App\Models\ProjectLineup;
use App\Models\Type;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Exceptions\ValidationException;
use App\Core\Database;

class ProjectLineupController extends Controller
{
    public function index(Request $request): Response
    {
        $this->requirePermission('project_lineup', ACTION_VIEW);
        [$page, $perPage] = $this->paginate($request);
        $filters = $request->only(['search', 'date_from', 'date_to', 'client_id', 'category', 'type', 'project_status', 'sort', 'order']);
        $result  = ProjectLineup::search($filters, $page, $perPage);

        // Prefill data: when forwarding from invoice page
        $prefillData = null;
        $prefillInvoiceId = (int)($request->query('prefill_invoice_id') ?? 0);
        if ($prefillInvoiceId > 0) {
            $prefillData = ProjectLineup::getInvoicePrefill($prefillInvoiceId);
        }

        $db = Database::getInstance();
        $clients = $db->select(
            "SELECT id, full_name FROM clients WHERE deleted_at IS NULL ORDER BY full_name"
        );
        $categories = Category::allActive();
        $types      = Type::allActive();

        return $this->view('project-lineup/index', [
            'title'        => 'Project Lineup',
            'pageTitle'    => 'Project Lineup',
            'lineups'      => $result['data'],
            'pagination'   => $result['pagination'],
            'filters'      => $filters,
            'clients'      => $clients,
            'categories'   => $categories,
            'types'        => $types,
            'prefill_data' => $prefillData,
        ]);
    }

    public function store(Request $request): Response
    {
        $this->requirePermission('project_lineup', ACTION_ADD);

        $invoiceId = (int)$request->input('invoice_id');
        $date      = $request->input('date');

        if (!$invoiceId) throw new ValidationException(['invoice_id' => ['Invoice is required']]);
        if (!$date)      throw new ValidationException(['date'       => ['Date is required']]);

        $categories = $request->input('categories', '');
        $types      = $request->input('types', '');

        $photoPath = null;
        $photoFile = $request->file('photo');
        if ($photoFile && $photoFile['error'] === UPLOAD_ERR_OK) {
            $photoPath = \App\Helpers\FileHelper::upload($photoFile, 'project-lineup');
        }

        $id = ProjectLineup::create([
            'invoice_id'            => $invoiceId,
            'client_name'           => $request->input('client_name', 'Walk-in') ?: 'Walk-in',
            'date'                  => $date,
            'brand_name'            => $request->input('brand_name'),
            'categories'            => $categories,
            'types'                 => $types,
            'qty'                   => (int)$request->input('qty', 0),
            'deadline'              => $request->input('deadline') ?: null,
            'link'                  => $request->input('link') ?: null,
            'notes'                 => $request->input('notes') ?: null,
            'photo'                 => $photoPath,
            'project_status'        => $request->input('project_status', 'pending'),
            'tshirt_status'         => $request->input('tshirt_status', 'pending'),
            'tags_status'           => $request->input('tags_status', 'pending'),
            'print_status'          => $request->input('print_status', 'pending'),
            'label_attached_status' => $request->input('label_attached_status', 'pending'),
            'qc_packing_status'     => $request->input('qc_packing_status', 'pending'),
            'authorized_approval'   => $request->input('authorized_approval', 'pending'),
            'created_by'            => $this->currentUserId(),
        ]);

        AuditService::log(AUDIT_CREATE, 'project_lineup', $id, null, ProjectLineup::find($id), "Created project lineup #{$id}");

        $inv = Database::getInstance()->selectOne("SELECT invoice_number FROM invoices WHERE id = ?", [$invoiceId]);
        NotificationService::lineupCreated(
            $id,
            $inv['invoice_number'] ?? "#{$invoiceId}",
            $request->input('brand_name') ?? 'N/A',
            $request->input('client_name', 'Walk-in') ?: 'Walk-in'
        );

        return $this->success(['id' => $id], 'Project lineup entry created', 201);
    }

    public function update(Request $request): Response
    {
        $this->requirePermission('project_lineup', ACTION_EDIT);
        $id  = (int)$request->param('lineup_id');
        $old = ProjectLineup::findOrFail($id);

        $allowed = [
            'date', 'brand_name', 'categories', 'types', 'qty', 'deadline',
            'link', 'notes',
            'project_status', 'tshirt_status', 'tags_status', 'print_status',
            'label_attached_status', 'qc_packing_status', 'authorized_approval',
        ];
        $data = array_intersect_key($request->all(), array_flip($allowed));

        // Convert empty deadline/link/notes to null
        foreach (['deadline', 'link', 'notes'] as $nullable) {
            if (array_key_exists($nullable, $data) && $data[$nullable] === '') {
                $data[$nullable] = null;
            }
        }

        // Handle photo upload
        $photoFile = $request->file('photo');
        if ($photoFile && $photoFile['error'] === UPLOAD_ERR_OK) {
            // Delete old photo if it exists
            $existing = $old['photo'] ?? null;
            if ($existing) \App\Helpers\FileHelper::delete($existing);
            $data['photo'] = \App\Helpers\FileHelper::upload($photoFile, 'project-lineup');
        } elseif ($request->input('existing_photo') === '' && isset($old['photo']) && $old['photo']) {
            // User explicitly cleared the photo
            \App\Helpers\FileHelper::delete($old['photo']);
            $data['photo'] = null;
        }

        ProjectLineup::update($id, $data);
        AuditService::log(AUDIT_UPDATE, 'project_lineup', $id, $old, ProjectLineup::find($id), "Updated project lineup #{$id}");
        return $this->success(null, 'Project lineup updated');
    }

    public function updateStatus(Request $request): Response
    {
        $this->requirePermission('project_lineup', ACTION_EDIT);
        $id    = (int)$request->param('lineup_id');
        $old   = ProjectLineup::findOrFail($id);

        $field = $request->input('field');
        $value = $request->input('value');

        $allowedFields = [
            'project_status', 'tshirt_status', 'tags_status', 'print_status',
            'label_attached_status', 'qc_packing_status', 'authorized_approval',
        ];
        if (!in_array($field, $allowedFields, true)) {
            return $this->error('Invalid field', 400);
        }

        ProjectLineup::update($id, [$field => $value]);
        AuditService::log(AUDIT_UPDATE, 'project_lineup', $id, $old, ProjectLineup::find($id), "Updated {$field} for lineup #{$id}");
        return $this->success(null, 'Status updated');
    }

    public function destroy(Request $request): Response
    {
        $this->requirePermission('project_lineup', ACTION_DELETE);
        $id  = (int)$request->param('lineup_id');
        $old = ProjectLineup::findOrFail($id);

        ProjectLineup::delete($id);
        AuditService::log(AUDIT_DELETE, 'project_lineup', $id, $old, null, "Deleted project lineup #{$id}");
        return $this->success(null, 'Project lineup deleted');
    }

    public function getInvoicePrefill(Request $request): Response
    {
        $this->requirePermission('project_lineup', ACTION_ADD);
        $invoiceId = (int)$request->param('invoice_id');
        $data = ProjectLineup::getInvoicePrefill($invoiceId);
        if (!$data) return $this->error('Invoice not found', 404);
        return $this->success($data);
    }
}
