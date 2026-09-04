<?php

namespace App\Controllers;

use App\Models\FileAttachment;
use App\Services\AuthService;
use App\Core\Uuid;

class StorageController
{
    private $fileModel;
    private $authService;
    private $currentUser;
    private $storageRoot;

    public function __construct()
    {
        $this->fileModel = new FileAttachment();
        $this->authService = new AuthService();
        $this->storageRoot = __DIR__ . '/../../storage/uploads';
        
        if (!is_dir($this->storageRoot)) {
            mkdir($this->storageRoot, 0777, true);
        }
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

    public function upload($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];

        if ($tenantId !== $user['tenant_id']) {
            $this->json(['error' => 'Forbidden'], 403);
            return;
        }

        if (empty($_FILES['file'])) {
            $this->json(['error' => 'No file uploaded'], 400);
            return;
        }

        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'Upload failed with code ' . $file['error']], 500);
            return;
        }

        $originalName = $file['name'];
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $fileName = Uuid::uuid4() . '.' . $extension;
        $relativePath = $tenantId . '/' . date('Y/m');
        $uploadDir = $this->storageRoot . '/' . $relativePath;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $targetPath = $uploadDir . '/' . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $fileRecord = $this->fileModel->create([
                'tenant_id' => $tenantId,
                'uploaded_by' => $user['id'],
                'original_name' => $originalName,
                'file_name' => $fileName,
                'file_path' => $relativePath . '/' . $fileName,
                'file_size' => $file['size'],
                'mime_type' => $file['type'],
                'entity_type' => $_POST['entity_type'] ?? null,
                'entity_id' => $_POST['entity_id'] ?? null,
                'is_public' => isset($_POST['is_public']) ? 1 : 0
            ]);

            $this->json(['data' => $fileRecord], 201);
        } else {
            $this->json(['error' => 'Failed to move uploaded file'], 500);
        }
    }

    public function download($params)
    {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $fileId = $params['fileId'];

        $file = $this->fileModel->find($fileId);

        if (!$file || $file['tenant_id'] !== $tenantId) {
            $this->json(['error' => 'File not found'], 404);
            return;
        }

        // Check permissions? For now, if tenant matches, allow access.

        $fullPath = $this->storageRoot . '/' . $file['file_path'];

        if (!file_exists($fullPath)) {
            $this->json(['error' => 'File missing from disk'], 404);
            return;
        }

        header('Content-Type: ' . $file['mime_type']);
        header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
    }
    
    public function listFiles($params) {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        
        if ($tenantId !== $user['tenant_id']) {
            $this->json(['error' => 'Forbidden'], 403);
            return;
        }
        
        $files = $this->fileModel->findAll(['tenant_id' => $tenantId]);
        $this->json(['data' => $files]);
    }
    
    public function delete($params) {
        $user = $this->authenticate();
        $tenantId = $params['tenantId'];
        $fileId = $params['fileId'];
        
        $file = $this->fileModel->find($fileId);
        
        if (!$file || $file['tenant_id'] !== $tenantId) {
            $this->json(['error' => 'File not found'], 404);
            return;
        }
        
        // Only uploader or admin can delete? For now, just tenant check.
        
        $fullPath = $this->storageRoot . '/' . $file['file_path'];
        
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        
        $this->fileModel->delete($fileId);
        $this->json(['message' => 'File deleted']);
    }

    private function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
