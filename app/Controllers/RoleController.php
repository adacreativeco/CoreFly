<?php

namespace App\Controllers;

use App\Models\Role;
use App\Services\AuthService;
use App\Core\Database;
use PDO;

class RoleController
{
    private $roleModel;
    private $authService;

    public function __construct()
    {
        $this->roleModel = new Role();
        $this->authService = new AuthService();
    }

    private function authenticate()
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }

        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);

        if (empty($token)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $payload = $this->authService->verifyToken($token);
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        return $payload;
    }

    private function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function generateUuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function index($params)
    {
        $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM roles WHERE tenant_id = ? ORDER BY created_at ASC");
        $stmt->execute([$tenantId]);
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($roles as &$role) {
            $stmtPerms = $db->prepare("SELECT permission FROM role_permissions WHERE role_id = ?");
            $stmtPerms->execute([$role['id']]);
            $role['permissions'] = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);
        }

        $this->json(['data' => $roles]);
    }

    public function create($params)
    {
        $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name'])) {
            $this->json(['error' => 'Rol adı zorunludur.'], 400);
            return;
        }

        $id = $this->generateUuid();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO roles (id, tenant_id, name, description, is_system) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id, $tenantId, $data['name'], $data['description'] ?? '', 0]);

        if (!empty($data['permissions']) && is_array($data['permissions'])) {
            $stmtPerm = $db->prepare("INSERT INTO role_permissions (role_id, permission) VALUES (?, ?)");
            foreach ($data['permissions'] as $perm) {
                $stmtPerm->execute([$id, $perm]);
            }
        }

        $this->json(['data' => ['id' => $id, 'name' => $data['name']]], 201);
    }

    public function updatePermissions($params)
    {
        $this->authenticate();
        $tenantId = $params['tenantId'];
        $roleId = $params['roleId'];
        $data = json_decode(file_get_contents('php://input'), true);

        $db = Database::getInstance()->getConnection();
        // Clear existing
        $stmtDel = $db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $stmtDel->execute([$roleId]);

        // Insert new
        if (!empty($data['permissions']) && is_array($data['permissions'])) {
            $stmtPerm = $db->prepare("INSERT INTO role_permissions (role_id, permission) VALUES (?, ?)");
            foreach ($data['permissions'] as $perm) {
                $stmtPerm->execute([$roleId, $perm]);
            }
        }

        $this->json(['message' => 'İzinler güncellendi']);
    }

    public function delete($params)
    {
        $this->authenticate();
        $tenantId = $params['tenantId'];
        $roleId = $params['roleId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM roles WHERE id = ? AND tenant_id = ? AND is_system = 0");
        $stmt->execute([$roleId, $tenantId]);

        $this->json(['message' => 'Rol silindi']);
    }
}
