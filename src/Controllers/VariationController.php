<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Services\AuditService;

class VariationController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** GET /variations — render the variations management page */
    public function index(Request $request): Response
    {
        $categories = $this->db->select(
            "SELECT * FROM categories WHERE deleted_at IS NULL ORDER BY name ASC"
        );
        $colors = $this->db->select(
            "SELECT * FROM colors WHERE deleted_at IS NULL ORDER BY name ASC"
        );
        $sizes = $this->db->select(
            "SELECT * FROM sizes WHERE deleted_at IS NULL ORDER BY name ASC"
        );
        $types = $this->db->select(
            "SELECT * FROM types WHERE deleted_at IS NULL ORDER BY name ASC"
        );

        return $this->view('variations/index', [
            'title'      => 'Product Variations | Dream Blanks POS',
            'pageTitle'  => 'Product Variations',
            'categories' => $categories,
            'colors'     => $colors,
            'sizes'      => $sizes,
            'types'      => $types,
        ]);
    }

    // ── CATEGORIES ──────────────────────────────────────────────────────────

    public function storeCategory(Request $request): Response
    {
        $name = trim($request->input('name', ''));
        if (!$name) return $this->error('Name is required', 422);

        $code = strtoupper(trim($request->input('code', '')));
        if (!$code) return $this->error('Code is required', 422);

        $existing = $this->db->selectOne("SELECT id FROM categories WHERE name = ? AND deleted_at IS NULL", [$name]);
        if ($existing) return $this->error('Category already exists', 409);

        $existingCode = $this->db->selectOne("SELECT id FROM categories WHERE code = ? AND deleted_at IS NULL", [$code]);
        if ($existingCode) return $this->error('Category code already in use', 409);

        $id = $this->db->insert('categories', [
            'name'        => $name,
            'code'        => $code,
            'description' => trim($request->input('description', '')),
            'status'      => $request->input('status', 'active'),
        ]);

        AuditService::log('create', 'categories', $id, null, ['name' => $name]);
        return $this->success(['id' => $id, 'name' => $name], 'Category created', 201);
    }

    public function updateCategory(Request $request): Response
    {
        $id   = (int)$request->param('id');
        $name = trim($request->input('name', ''));
        if (!$name) return $this->error('Name is required', 422);

        $code = strtoupper(trim($request->input('code', '')));
        if (!$code) return $this->error('Code is required', 422);

        $existing = $this->db->selectOne(
            "SELECT id FROM categories WHERE name = ? AND id != ? AND deleted_at IS NULL",
            [$name, $id]
        );
        if ($existing) return $this->error('Category name already in use', 409);

        $existingCode = $this->db->selectOne(
            "SELECT id FROM categories WHERE code = ? AND id != ? AND deleted_at IS NULL",
            [$code, $id]
        );
        if ($existingCode) return $this->error('Category code already in use', 409);

        $this->db->update('categories', [
            'name'        => $name,
            'code'        => $code,
            'description' => trim($request->input('description', '')),
            'status'      => $request->input('status', 'active'),
        ], 'id = ?', [$id]);

        AuditService::log('update', 'categories', $id, null, ['name' => $name]);
        return $this->success(null, 'Category updated');
    }

    public function destroyCategory(Request $request): Response
    {
        $id = (int)$request->param('id');
        $this->db->delete('categories', 'id = ?', [$id]);
        AuditService::log('delete', 'categories', $id);
        return $this->success(null, 'Category deleted');
    }

    // ── COLORS ──────────────────────────────────────────────────────────────

    public function storeColor(Request $request): Response
    {
        $name = trim($request->input('name', ''));
        if (!$name) return $this->error('Name is required', 422);

        $hexCode = strtoupper(trim($request->input('hex_code', '')));
        if (!$hexCode) return $this->error('Hex code is required', 422);
        if (!preg_match('/^#[0-9A-F]{6}$/', $hexCode)) return $this->error('Hex code must be in #RRGGBB format', 422);

        $existing = $this->db->selectOne("SELECT id FROM colors WHERE name = ? AND deleted_at IS NULL", [$name]);
        if ($existing) return $this->error('Color already exists', 409);

        $id = $this->db->insert('colors', [
            'name'     => $name,
            'hex_code' => $hexCode,
            'status'   => $request->input('status', 'active'),
        ]);

        AuditService::log('create', 'colors', $id, null, ['name' => $name]);
        return $this->success(['id' => $id, 'name' => $name], 'Color created', 201);
    }

    public function updateColor(Request $request): Response
    {
        $id   = (int)$request->param('id');
        $name = trim($request->input('name', ''));
        if (!$name) return $this->error('Name is required', 422);

        $hexCode = strtoupper(trim($request->input('hex_code', '')));
        if (!$hexCode) return $this->error('Hex code is required', 422);
        if (!preg_match('/^#[0-9A-F]{6}$/', $hexCode)) return $this->error('Hex code must be in #RRGGBB format', 422);

        $existing = $this->db->selectOne(
            "SELECT id FROM colors WHERE name = ? AND id != ? AND deleted_at IS NULL",
            [$name, $id]
        );
        if ($existing) return $this->error('Color name already in use', 409);

        $this->db->update('colors', [
            'name'     => $name,
            'hex_code' => $hexCode,
            'status'   => $request->input('status', 'active'),
        ], 'id = ?', [$id]);

        AuditService::log('update', 'colors', $id, null, ['name' => $name]);
        return $this->success(null, 'Color updated');
    }

    public function destroyColor(Request $request): Response
    {
        $id = (int)$request->param('id');
        $this->db->delete('colors', 'id = ?', [$id]);
        AuditService::log('delete', 'colors', $id);
        return $this->success(null, 'Color deleted');
    }

    // ── SIZES ────────────────────────────────────────────────────────────────

    public function storeSize(Request $request): Response
    {
        $name = trim($request->input('name', ''));
        if (!$name) return $this->error('Name is required', 422);

        $code = trim($request->input('code', ''));
        if (!$code) return $this->error('Code is required', 422);

        $existing = $this->db->selectOne("SELECT id FROM sizes WHERE name = ? AND deleted_at IS NULL", [$name]);
        if ($existing) return $this->error('Size already exists', 409);

        $existingCode = $this->db->selectOne("SELECT id FROM sizes WHERE code = ? AND deleted_at IS NULL", [$code]);
        if ($existingCode) return $this->error('Size code already in use', 409);

        $id = $this->db->insert('sizes', [
            'name'   => $name,
            'code'   => $code,
            'status' => $request->input('status', 'active'),
        ]);

        AuditService::log('create', 'sizes', $id, null, ['name' => $name]);
        return $this->success(['id' => $id, 'name' => $name], 'Size created', 201);
    }

    public function updateSize(Request $request): Response
    {
        $id   = (int)$request->param('id');
        $name = trim($request->input('name', ''));
        if (!$name) return $this->error('Name is required', 422);

        $code = trim($request->input('code', ''));
        if (!$code) return $this->error('Code is required', 422);

        $existing = $this->db->selectOne(
            "SELECT id FROM sizes WHERE name = ? AND id != ? AND deleted_at IS NULL",
            [$name, $id]
        );
        if ($existing) return $this->error('Size name already in use', 409);

        $existingCode = $this->db->selectOne(
            "SELECT id FROM sizes WHERE code = ? AND id != ? AND deleted_at IS NULL",
            [$code, $id]
        );
        if ($existingCode) return $this->error('Size code already in use', 409);

        $this->db->update('sizes', [
            'name'   => $name,
            'code'   => $code,
            'status' => $request->input('status', 'active'),
        ], 'id = ?', [$id]);

        AuditService::log('update', 'sizes', $id, null, ['name' => $name]);
        return $this->success(null, 'Size updated');
    }

    public function destroySize(Request $request): Response
    {
        $id = (int)$request->param('id');
        $this->db->delete('sizes', 'id = ?', [$id]);
        AuditService::log('delete', 'sizes', $id);
        return $this->success(null, 'Size deleted');
    }

    // ── LIST ENDPOINTS (JSON) ─────────────────────────────────────────────

    public function listCategories(Request $request): Response
    {
        $rows = $this->db->select("SELECT * FROM categories WHERE deleted_at IS NULL ORDER BY name ASC");
        return $this->success($rows);
    }

    public function listColors(Request $request): Response
    {
        $rows = $this->db->select("SELECT * FROM colors WHERE deleted_at IS NULL ORDER BY name ASC");
        return $this->success($rows);
    }

    public function listSizes(Request $request): Response
    {
        $rows = $this->db->select("SELECT * FROM sizes WHERE deleted_at IS NULL ORDER BY name ASC");
        return $this->success($rows);
    }

    // ── TYPES ──────────────────────────────────────────────────────────────

    public function storeType(Request $request): Response
    {
        $name = trim($request->input('name', ''));
        if (!$name) return $this->error('Name is required', 422);

        $code = strtoupper(trim($request->input('code', '')));
        if (!$code) return $this->error('Code is required', 422);

        $existing = $this->db->selectOne("SELECT id FROM types WHERE name = ? AND deleted_at IS NULL", [$name]);
        if ($existing) return $this->error('Type already exists', 409);

        $existingCode = $this->db->selectOne("SELECT id FROM types WHERE code = ? AND deleted_at IS NULL", [$code]);
        if ($existingCode) return $this->error('Type code already in use', 409);

        $id = $this->db->insert('types', [
            'name'   => $name,
            'code'   => $code,
            'status' => $request->input('status', 'active'),
        ]);

        AuditService::log('create', 'types', $id, null, ['name' => $name]);
        return $this->success(['id' => $id, 'name' => $name], 'Type created', 201);
    }

    public function updateType(Request $request): Response
    {
        $id   = (int)$request->param('id');
        $name = trim($request->input('name', ''));
        if (!$name) return $this->error('Name is required', 422);

        $code = strtoupper(trim($request->input('code', '')));
        if (!$code) return $this->error('Code is required', 422);

        $existing = $this->db->selectOne(
            "SELECT id FROM types WHERE name = ? AND id != ? AND deleted_at IS NULL",
            [$name, $id]
        );
        if ($existing) return $this->error('Type name already in use', 409);

        $existingCode = $this->db->selectOne(
            "SELECT id FROM types WHERE code = ? AND id != ? AND deleted_at IS NULL",
            [$code, $id]
        );
        if ($existingCode) return $this->error('Type code already in use', 409);

        $this->db->update('types', [
            'name'   => $name,
            'code'   => $code,
            'status' => $request->input('status', 'active'),
        ], 'id = ?', [$id]);

        AuditService::log('update', 'types', $id, null, ['name' => $name]);
        return $this->success(null, 'Type updated');
    }

    public function destroyType(Request $request): Response
    {
        $id = (int)$request->param('id');
        $this->db->delete('types', 'id = ?', [$id]);
        AuditService::log('delete', 'types', $id);
        return $this->success(null, 'Type deleted');
    }

    public function listTypes(Request $request): Response
    {
        $rows = $this->db->select("SELECT * FROM types WHERE deleted_at IS NULL ORDER BY name ASC");
        return $this->success($rows);
    }
}
