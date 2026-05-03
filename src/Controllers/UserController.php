<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\Role;
use App\Services\AuditService;
use App\Helpers\FileHelper;
use App\Exceptions\ValidationException;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->requirePermission(MODULE_USERS, ACTION_VIEW);
        [$page, $perPage] = $this->paginate($request);
        $search = $request->query('search', '');
        $status = $request->query('status', '');

        if ($search) {
            $result = User::search($search, $page, $perPage, $status);
        } else {
            $where  = "deleted_at IS NULL";
            $params = [];
            if ($status) { $where .= " AND status = ?"; $params[] = $status; }
            $result = User::paginate($page, $perPage, $where, $params, 'created_at', 'DESC');
        }

        // Attach roles to each user
        foreach ($result['data'] as &$user) {
            $user['roles'] = User::getRoles($user['id']);
        }

        if ($request->isApi()) {
            return $this->success(['users' => $result['data'], 'pagination' => $result['pagination']]);
        }

        return $this->view('users/index', [
            'users'      => $result['data'],
            'pagination' => $result['pagination'],
            'search'     => $search,
            'status'     => $status,
            'roles'      => Role::all('name'),
            'title'      => 'Users | Dream Blanks POS',
        ]);
    }

    public function show(Request $request): Response
    {
        $this->requirePermission(MODULE_USERS, ACTION_VIEW);
        $id   = (int)$request->param('user_id');
        $user = User::findOrFail($id);
        $user['roles']       = User::getRoles($id);
        $user['permissions'] = array_map(
            fn($p) => "{$p['module']}.{$p['action']}",
            User::getPermissions($id)
        );

        return $this->success($user);
    }

    public function profile(Request $request): Response
    {
        $id   = $this->currentUserId();
        $user = User::findOrFail($id);
        $user['roles'] = User::getRoles($id);

        if ($request->isApi()) {
            return $this->success($user);
        }

        return $this->view('users/profile', [
            'user'  => $user,
            'title' => 'Profile Settings | Dream Blanks POS',
            'pageTitle' => 'Profile Settings',
        ]);
    }

    public function updateProfile(Request $request): Response
    {
        $id  = $this->currentUserId();
        $old = User::findOrFail($id);

        $data = $request->only(['first_name', 'middle_name', 'last_name', 'email']);

        if (!empty($data['email']) && $data['email'] !== $old['email']) {
            $existing = User::findByEmail($data['email']);
            if ($existing && $existing['id'] !== $id) {
                throw new ValidationException(['email' => ['Email already taken']]);
            }
        }

        if (!empty($request->input('current_password'))) {
            if (!password_verify($request->input('current_password'), $old['password_hash'])) {
                throw new ValidationException(['current_password' => ['Current password is incorrect']]);
            }
            $newPass = $request->input('new_password', '');
            if (strlen($newPass) < 8) {
                throw new ValidationException(['new_password' => ['New password must be at least 8 characters']]);
            }
            $data['password_hash'] = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
        }

        User::update($id, array_filter($data, fn($v) => $v !== null && $v !== ''));

        // Update session with new name/email
        $updated = User::find($id);
        $_SESSION['user']['first_name']    = $updated['first_name'];
        $_SESSION['user']['last_name']     = $updated['last_name'];
        $_SESSION['user']['email']         = $updated['email'];
        $_SESSION['user']['profile_image'] = $updated['profile_image'] ?? null;

        AuditService::log(AUDIT_UPDATE, MODULE_USERS, $id, $old, $updated, "Updated own profile");
        return $this->success(null, 'Profile updated successfully');
    }

    public function uploadProfileImage(Request $request): Response
    {
        $id  = $this->currentUserId();
        $old = User::findOrFail($id);

        $file = $request->file('profile_image');
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return $this->error('No image uploaded', 400);
        }

        $imagePath = FileHelper::upload($file, 'users');

        // Delete old image
        if (!empty($old['profile_image'])) {
            FileHelper::delete($old['profile_image']);
        }

        User::update($id, ['profile_image' => $imagePath]);
        $_SESSION['user']['profile_image'] = $imagePath;

        AuditService::log(AUDIT_UPDATE, MODULE_USERS, $id, $old, ['profile_image' => $imagePath], "Updated profile image");
        return $this->success(['profile_image' => $imagePath], 'Profile image updated');
    }

    public function store(Request $request): Response
    {
        $this->requirePermission(MODULE_USERS, ACTION_ADD);
        $data = $this->validate($request->all());

        // Check uniqueness
        if (User::findByEmail($data['email'])) {
            throw new ValidationException(['email' => ['Email already taken']]);
        }
        if (User::findByUsername($data['username'])) {
            throw new ValidationException(['username' => ['Username already taken']]);
        }

        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        // Handle optional profile image
        if (($file = $request->file('profile_image')) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $data['profile_image'] = FileHelper::upload($file, 'users');
        }

        $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        unset($data['password']);

        $userId = User::create($data);

        foreach ($roles as $roleId) {
            User::assignRole($userId, (int)$roleId);
        }

        AuditService::log(AUDIT_CREATE, MODULE_USERS, $userId, null, User::find($userId), "Created user: {$data['username']}");

        return $this->success(['id' => $userId, 'username' => $data['username'], 'email' => $data['email']], 'User created successfully', 201);
    }

    public function update(Request $request): Response
    {
        $this->requirePermission(MODULE_USERS, ACTION_EDIT);
        $id  = (int)$request->param('user_id');
        $old = User::findOrFail($id);

        $data  = $request->only(['first_name', 'middle_name', 'last_name', 'email', 'status']);
        $roles = $request->input('roles');

        if (!empty($data['email']) && $data['email'] !== $old['email']) {
            $existing = User::findByEmail($data['email']);
            if ($existing && $existing['id'] !== $id) {
                throw new ValidationException(['email' => ['Email already taken']]);
            }
        }

        if (!empty($request->input('password'))) {
            $data['password_hash'] = password_hash($request->input('password'), PASSWORD_BCRYPT, ['cost' => 12]);
        }

        // Handle optional profile image
        if (($file = $request->file('profile_image')) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            if (!empty($old['profile_image'])) FileHelper::delete($old['profile_image']);
            $data['profile_image'] = FileHelper::upload($file, 'users');
        }

        User::update($id, array_filter($data, fn($v) => $v !== null));

        if ($roles !== null) {
            User::removeAllRoles($id);
            foreach ((array)$roles as $roleId) {
                User::assignRole($id, (int)$roleId);
            }
        }

        AuditService::log(AUDIT_UPDATE, MODULE_USERS, $id, $old, User::find($id), "Updated user #{$id}");
        return $this->success(null, 'User updated successfully');
    }

    public function destroy(Request $request): Response
    {
        $this->requirePermission(MODULE_USERS, ACTION_DELETE);
        $id = (int)$request->param('user_id');
        if ($id === $this->currentUserId()) {
            return $this->error("Cannot delete your own account", 400);
        }
        $old = User::findOrFail($id);
        User::delete($id);
        AuditService::log(AUDIT_DELETE, MODULE_USERS, $id, $old, null, "Deleted user #{$id}");
        return $this->success(null, 'User deleted successfully');
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['username'])) $errors['username'][] = 'Username is required';
        if (empty($data['email']))    $errors['email'][]    = 'Email is required';
        if (empty($data['password'])) $errors['password'][] = 'Password is required';
        if (empty($data['first_name'])) $errors['first_name'][] = 'First name is required';
        if (empty($data['last_name']))  $errors['last_name'][]  = 'Last name is required';
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Invalid email format';
        }
        if (!empty($data['password']) && strlen($data['password']) < 8) {
            $errors['password'][] = 'Password must be at least 8 characters';
        }
        if (!empty($errors)) throw new ValidationException($errors);
        return $data;
    }
}
