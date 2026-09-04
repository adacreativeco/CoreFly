<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Tenant;
use App\Services\AuthService;

class AuthController
{
    private $userModel;
    private $tenantModel;
    private $authService;

    public function __construct()
    {
        $this->userModel = new User();
        $this->tenantModel = new Tenant();
        $this->authService = new AuthService();
    }

    public function register()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Basic validation
        if (empty($data['email']) || empty($data['password'])) {
            $this->json(['error' => 'Email and password required'], 400);
            return;
        }

        // Check if user exists
        $existing = $this->userModel->findOne(['email' => $data['email']]);
        if ($existing) {
            $this->json(['error' => 'User already exists'], 400);
            return;
        }

        // Handle Tenant
        $tenantId = $data['tenant_id'] ?? null;
        if (!$tenantId && !empty($data['tenant_name'])) {
            // Create new tenant
            $slug = strtolower(str_replace(' ', '-', $data['tenant_name']));
            $tenant = $this->tenantModel->create([
                'name' => $data['tenant_name'],
                'slug' => $slug
            ]);
            $tenantId = $tenant['id'];
        } elseif (!$tenantId) {
            // Default tenant for MVP if not provided
            $tenantId = 'default-tenant';
             // Ensure default tenant exists
             $defaultTenant = $this->tenantModel->find($tenantId);
             if (!$defaultTenant) {
                 $this->tenantModel->create(['id' => 'default-tenant', 'name' => 'Default Tenant', 'slug' => 'default']);
             }
        }

        // Create User
        $user = $this->userModel->create([
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'full_name' => $data['full_name'] ?? '',
            'tenant_id' => $tenantId
        ]);

        $token = $this->authService->generateToken(['id' => $user['id'], 'tenant_id' => $tenantId]);
        unset($user['password_hash']);
        $user['name'] = $user['full_name'] ?? $user['name'] ?? '';

        $this->json(['token' => $token, 'user' => $user]);
    }

    public function login()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['email']) || empty($data['password'])) {
            $this->json(['error' => 'Email and password required'], 400);
            return;
        }

        $user = $this->userModel->findOne(['email' => $data['email']]);
        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            $this->json(['error' => 'Invalid credentials'], 401);
            return;
        }

        $token = $this->authService->generateToken(['id' => $user['id'], 'tenant_id' => $user['tenant_id']]);
        unset($user['password_hash']);
        $user['name'] = $user['full_name'] ?? $user['name'] ?? '';
        $this->json(['token' => $token, 'user' => $user]);
    }

    public function me()
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }

        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);
        
        $payload = $this->authService->verifyToken($token);
        if (!$payload) {
            $this->json(['error' => 'Unauthorized'], 401);
            return;
        }

        $user = $this->userModel->find($payload['id']);
        if ($user) {
            unset($user['password_hash']);
            $user['name'] = $user['full_name'] ?? $user['name'] ?? '';
        }
        $this->json(['user' => $user]);
    }

    public function changePassword()
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }

        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);
        
        $payload = $this->authService->verifyToken($token);
        if (!$payload) {
            $this->json(['error' => 'Unauthorized'], 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['current_password']) || empty($data['new_password'])) {
            $this->json(['error' => 'Mevcut şifre ve yeni şifre zorunludur.'], 400);
            return;
        }

        $user = $this->userModel->find($payload['id']);
        if (!$user || !password_verify($data['current_password'], $user['password_hash'])) {
            $this->json(['error' => 'Mevcut şifreniz hatalı.'], 400);
            return;
        }

        $newHash = password_hash($data['new_password'], PASSWORD_DEFAULT);
        $this->userModel->update($payload['id'], ['password_hash' => $newHash]);
        $this->json(['message' => 'Şifreniz başarıyla güncellendi.']);
    }

    public function updateProfile()
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }

        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);
        
        $payload = $this->authService->verifyToken($token);
        if (!$payload) {
            $this->json(['error' => 'Unauthorized'], 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim($data['name'] ?? $data['full_name'] ?? '');
        if (empty($name)) {
            $this->json(['error' => 'Ad Soyad alanı boş bırakılamaz.'], 400);
            return;
        }

        $this->userModel->update($payload['id'], ['full_name' => $name]);
        $updatedUser = $this->userModel->find($payload['id']);
        unset($updatedUser['password_hash']);
        $updatedUser['name'] = $updatedUser['full_name'];

        $this->json(['message' => 'Profiliniz başarıyla güncellendi.', 'user' => $updatedUser]);
    }

    public function getTenant()
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }

        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);
        
        $payload = $this->authService->verifyToken($token);
        if (!$payload) {
            $this->json(['error' => 'Unauthorized'], 401);
            return;
        }

        $tenantId = $payload['tenant_id'];
        $tenant = $this->tenantModel->find($tenantId);
        $this->json(['data' => $tenant]);
    }

    public function updateTenant()
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }

        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);
        
        $payload = $this->authService->verifyToken($token);
        if (!$payload) {
            $this->json(['error' => 'Unauthorized'], 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $tenantName = trim($data['name'] ?? '');
        if (empty($tenantName)) {
            $this->json(['error' => 'Şirket adı boş bırakılamaz.'], 400);
            return;
        }

        $tenantId = $payload['tenant_id'];
        $this->tenantModel->update($tenantId, ['name' => $tenantName]);
        $tenant = $this->tenantModel->find($tenantId);

        $this->json(['message' => 'Şirket bilgileri başarıyla güncellendi.', 'tenant' => $tenant]);
    }

    private function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
