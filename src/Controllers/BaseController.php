<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Utils\Database;
use CoreFly\Services\JwtService;
use CoreFly\Services\PermissionsService;
use CoreFly\Utils\Logger;
use Exception;

abstract class BaseController
{
    protected Database $db;
    protected JwtService $jwtService;
    protected PermissionsService $permissionsService;
    protected array $config;
    protected ?array $currentUser = null;
    protected ?string $currentTenant = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->jwtService = new JwtService();
        $this->permissionsService = new PermissionsService();
        
        // Check if config file exists before requiring
        $configPath = __DIR__ . '/../../config/app.php';
        if (file_exists($configPath)) {
            $this->config = require $configPath;
        } else {
            // Fallback or throw exception
            $this->config = ['app' => ['env' => 'development'], 'security' => ['password_min_length' => 8, 'password_complexity' => false]];
        }
    }

    protected function jsonResponse(array $data, int $statusCode = 200): string
    {
        // Clear any previous output (e.g. warnings, notices)
        if (ob_get_length()) ob_clean();
        
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function successResponse(mixed $data = null, string $message = 'Success'): string
    {
        return $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    protected function errorResponse(string $message, int $statusCode = 400, mixed $errors = null): string
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if ($statusCode >= 500) {
            Logger::error($message);
        }
        return $this->jsonResponse($response, $statusCode);
    }

    protected function validateRequired(array $data, array $requiredFields): array
    {
        $errors = [];

        foreach ($requiredFields as $field) {
            $value = $data[$field] ?? null;
            if ($value === null || $value === '' || (is_string($value) && trim($value) === '')) {
                $errors[$field] = "{$field} alanı zorunludur";
            }
        }

        return $errors;
    }

    protected function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validatePassword(string $password): array
    {
        $errors = [];
        $config = $this->config['security'];

        if (strlen($password) < $config['password_min_length']) {
            $errors[] = "Şifre en az {$config['password_min_length']} karakter olmalıdır";
        }

        if ($config['password_complexity']) {
            if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = "Şifre en az bir büyük harf içermelidir";
            }
            if (!preg_match('/[a-z]/', $password)) {
                $errors[] = "Şifre en az bir küçük harf içermelidir";
            }
            if (!preg_match('/[0-9]/', $password)) {
                $errors[] = "Şifre en az bir rakam içermelidir";
            }
            if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
                $errors[] = "Şifre en az bir özel karakter içermelidir";
            }
        }

        return $errors;
    }

    protected function sanitizeInput(mixed $input): mixed
    {
        if (is_string($input)) {
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }

        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }

        return $input;
    }

    protected function getRequestData(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $rawData = '';
        if (in_array($method, ['POST','PUT','PATCH'])) {
            $rawData = file_get_contents('php://input') ?: '';
        }
        \CoreFly\Utils\Logger::info(sprintf('REQ method=%s contentType=%s rawLen=%d', $method, $contentType, strlen($rawData)));
        $decoded = [];
        if ($rawData !== '') {
            $trim = ltrim($rawData);
            if (str_contains($contentType, 'application/json') || ($trim !== '' && ($trim[0] === '{' || $trim[0] === '['))) {
                $tmp = json_decode($rawData, true);
                if (is_array($tmp)) {
                    $decoded = $tmp;
                }
            } else {
                $form = [];
                parse_str($rawData, $form);
                if (is_array($form) && !empty($form)) {
                    $decoded = $form;
                }
            }
        }
        $post = $_POST ?? [];
        if (!empty($decoded)) {
            \CoreFly\Utils\Logger::info('REQ decodedKeys=' . implode(',', array_keys($decoded)));
        }
        if (!empty($post)) {
            \CoreFly\Utils\Logger::info('REQ postKeys=' . implode(',', array_keys($post)));
        }
        if (!empty($decoded)) {
            return array_merge($post, $decoded);
        }
        return $post;
    }

    protected function getBearerToken(): ?string
    {
        $headers = $this->getAuthorizationHeader();
        
        if (!$headers) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $headers, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected function getAuthorizationHeader(): ?string
    {
        $headers = null;

        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        return $headers;
    }

    protected function authenticate(): bool
    {
        $token = $this->getBearerToken();
        
        if (!$token) {
            return false;
        }

        try {
            $payload = $this->jwtService->validateToken($token);
            
            // Check if user actually exists in DB
            if (!isset($payload['user_id'])) {
                return false;
            }
            
            // Use withoutTenant() because we haven't set the tenant context yet
            // and the user might be in a specific tenant
            $user = \CoreFly\Models\User::query()->withoutTenant()->where('id', $payload['user_id'])->first();
            if (!$user) {
                return false;
            }

            $this->currentUser = $payload;
            // Support Active Tenant Context (for Super Admin Switching)
            // Fallback to home tenant_id if active_tenant_id is not set
            $this->currentTenant = $payload['active_tenant_id'] ?? ($payload['tenant_id'] ?? 'default');
            
            $this->db->setCurrentTenant($this->currentTenant);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    protected function requireAuth(): void
    {
        if (!$this->authenticate()) {
            http_response_code(401);
            // Log details to help debugging
            error_log("AUTH DEBUG: 401 Unauthorized triggered. URL: " . $_SERVER['REQUEST_URI']);
            echo $this->errorResponse('Authentication required', 401);
            exit;
        }
    }

    protected function requirePermission(string $permission): void
    {
        $this->requireAuth();
        
        if (!$this->hasPermission($permission)) {
            $role = $this->currentUser['role'] ?? 'unknown';
            $perms = json_encode($this->permissionsService->getPermissionsForRole($role));
            error_log("PERM DEBUG: Access Denied. UserRole='{$role}' Required='{$permission}' UserPerms={$perms}");
            
            http_response_code(403);
            echo $this->errorResponse('Insufficient permissions', 403);
            exit;
        }
    }

    protected function hasPermission(string $permission): bool
    {
        if (!$this->currentUser) {
            return false;
        }

        $userRole = $this->currentUser['role'] ?? 'user';
        $rolePermissions = $this->permissionsService->getPermissionsForRole($userRole);
        return in_array($permission, $rolePermissions) || in_array('*', $rolePermissions);
    }

    protected function getCurrentUserId(): ?string
    {
        return $this->currentUser['user_id'] ?? null;
    }

    protected function getCurrentUserRole(): ?string
    {
        return $this->currentUser['role'] ?? null;
    }

    protected function getCurrentTenantId(): ?string
    {
        return $this->currentTenant;
    }

    protected function getJsonInput(): array
    {
        return $this->getRequestData();
    }

    protected function json(array $data, int $statusCode = 200): string
    {
        return $this->jsonResponse($data, $statusCode);
    }

    protected function generateUUID(): string
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

    protected function logActivity(string $action, ?string $entityType = null, ?string $entityId = null, array $data = []): void
    {
        // Handle legacy call (action, data)
        if (is_array($entityType)) {
            $data = $entityType;
            $entityType = null;
            $entityId = null;
        }

        $userId = $this->getCurrentUserId();
        $tenantId = $this->getCurrentTenantId();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        try {
            // Check if table supports entity_type/id
            // For now, we will just put them in the data if explicitly passed and not in data
            if ($entityType && $entityId) {
                $data['entity_type'] = $entityType;
                $data['entity_id'] = $entityId;
            }

            $sql = "INSERT INTO audit_logs (id, tenant_id, user_id, action, old_values, new_values, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $this->generateUUID(),
                $tenantId,
                $userId,
                $action,
                json_encode($data['old_values'] ?? null),
                json_encode($data['new_values'] ?? $data), // Use whole data as new_values if not structured
                $ipAddress,
                $userAgent
            ]);
        } catch (Exception $e) {
            // Log activity failed, but don't throw exception
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }

    protected function paginate(array $data, int $page = 1, int $perPage = 20): array
    {
        $total = count($data);
        $totalPages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        
        $items = array_slice($data, $offset, $perPage);

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ];
    }

    protected function validateCsrfToken(string $token): bool
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        return hash_equals($sessionToken, $token);
    }

    protected function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function requireCsrfIfProd(): void
    {
        $env = $this->config['app']['env'] ?? 'development';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($env !== 'production') {
            return;
        }
        if (in_array($method, ['POST','PUT','DELETE'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!$this->validateCsrfToken($token)) {
                http_response_code(419);
                echo $this->errorResponse('Invalid CSRF token', 419);
                exit;
            }
        }
    }

    protected function renderTemplate(string $template, array $data = []): string
    {
        extract($data);
        ob_start();
        
        $templatePath = __DIR__ . '/../../templates/' . $template . '.php';
        
        if (!file_exists($templatePath)) {
            throw new Exception("Template not found: {$template}");
        }

        include $templatePath;
        return ob_get_clean();
    }

    protected function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: {$url}", true, $statusCode);
        exit;
    }

    protected function getPaginationParams(): array
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
        
        return [
            'page' => max(1, $page),
            'per_page' => max(1, min(100, $perPage))
        ];
    }

    protected function getQueryParam(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }
}
