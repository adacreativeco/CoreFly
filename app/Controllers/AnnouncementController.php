<?php

namespace App\Controllers;

use App\Models\Announcement;
use App\Services\AuthService;
use App\Core\Database;
use PDO;

class AnnouncementController
{
    private $announcementModel;
    private $authService;
    private $currentUser;

    public function __construct()
    {
        $this->announcementModel = new Announcement();
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
            echo json_encode(['error' => 'Unauthorized: No token']);
            exit;
        }

        $payload = $this->authService->verifyToken($token);
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: Invalid token']);
            exit;
        }
        $this->currentUser = $payload;
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
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM announcements WHERE tenant_id = ? ORDER BY is_pinned DESC, created_at DESC");
        $stmt->execute([$tenantId]);
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['data' => $announcements]);
    }

    public function create($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['title']) || empty($data['content'])) {
            $this->json(['error' => 'Title and content are required'], 400);
        }

        $id = $this->generateUuid();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO announcements (id, tenant_id, title, content, priority, is_pinned, created_by, author_name)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $id,
            $tenantId,
            $data['title'],
            $data['content'],
            $data['priority'] ?? 'normal',
            !empty($data['is_pinned']) ? 1 : 0,
            $user['sub'] ?? 'system',
            $data['author_name'] ?? 'Şirket Yönetimi'
        ]);

        $created = $this->announcementModel->find(['id' => $id, 'tenant_id' => $tenantId]);
        $this->json(['data' => $created], 201);
    }

    public function delete($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $id = $params['announcementId'];

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM announcements WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenantId]);

        $this->json(['success' => true]);
    }
}
