<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Role;
use App\Models\Permission;
use App\Services\AuditService;
use App\Exceptions\ValidationException;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $this->requirePermission(MODULE_ROLES, ACTION_VIEW);
        $roles = Role::all('name');
        foreach ($roles as &$role) {
            $role['permissions'] = Role::getPermissions($role['id']);
        }
        if ($request->isApi()) {
            return $this->success($roles);
        }
        $permissions = Permission::allGrouped();
        return $this->view('roles/index', [
            'title'       => 'Roles & Permissions | Dream Blanks POS',
            'pageTitle'   => 'Roles & Permissions',
            'roles'       => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request): Response
    {
        $this->requirePermission(MODULE_ROLES, ACTION_ADD);
        $name = $request->input('name', '');
        if (!$name) throw new ValidationException(['name' => ['Name is required']]);
        if (Role::findByName($name)) throw new ValidationException(['name' => ['Role name already exists']]);

        $id = Role::create(['name' => $name, 'description' => $request->input('description', '')]);
        AuditService::log(AUDIT_CREATE, MODULE_ROLES, $id, null, Role::find($id), "Created role: {$name}");
        return $this->success(['id' => $id], 'Role created', 201);
    }

    public function update(Request $request): Response
    {
        $this->requirePermission(MODULE_ROLES, ACTION_EDIT);
        $id  = (int)$request->param('role_id');
        $old = Role::findOrFail($id);
        $data = $request->only(['name', 'description', 'status']);
        Role::update($id, array_filter($data, fn($v) => $v !== null));
        AuditService::log(AUDIT_UPDATE, MODULE_ROLES, $id, $old, Role::find($id), "Updated role #{$id}");
        return $this->success(null, 'Role updated');
    }

    public function destroy(Request $request): Response
    {
        $this->requirePermission(MODULE_ROLES, ACTION_DELETE);
        $id  = (int)$request->param('role_id');
        $old = Role::findOrFail($id);
        Role::delete($id);
        AuditService::log(AUDIT_DELETE, MODULE_ROLES, $id, $old, null, "Deleted role #{$id}");
        return $this->success(null, 'Role deleted');
    }

    public function updatePermissions(Request $request): Response
    {
        $this->requirePermission(MODULE_ROLES, ACTION_EDIT);
        $id            = (int)$request->param('role_id');
        Role::findOrFail($id);
        $permissionIds = (array)$request->input('permission_ids', []);
        Role::syncPermissions($id, array_map('intval', $permissionIds));
        AuditService::log(AUDIT_UPDATE, MODULE_ROLES, $id, null, null, "Updated permissions for role #{$id}");
        return $this->success(null, 'Permissions updated');
    }

    public function permissions(Request $request): Response
    {
        $this->requirePermission(MODULE_ROLES, ACTION_VIEW);
        return $this->success(Permission::allGrouped());
    }
}
