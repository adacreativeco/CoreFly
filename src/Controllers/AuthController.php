<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Models\User;
use CoreFly\Models\UserSession;
use CoreFly\Models\PasswordReset;
use CoreFly\Models\Tenant;
use CoreFly\Services\JwtService;
use Exception;

class AuthController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function login(): string
    {
        try {
            $data = $this->getRequestData();
            error_log("DEBUG: Login attempt. Data: " . json_encode($data));
            
            $errors = $this->validateRequired($data, ['login', 'password']);
            if (!empty($errors)) {
                // Try to fallback to 'username' if 'login' is missing (frontend might send username)
                if (!isset($data['login']) && isset($data['username'])) {
                    $data['login'] = $data['username'];
                    // Clear error
                    $errors = [];
                } else {
                    error_log("DEBUG: Validation failed: " . json_encode($errors));
                    return $this->errorResponse('Validation failed', 400, $errors);
                }
            }

            $login = $this->sanitizeInput($data['login']);
            $password = $data['password'];
            $remember = filter_var($data['remember'] ?? false, FILTER_VALIDATE_BOOLEAN);

            error_log("DEBUG: Searching user for: " . $login);
            $user = User::findByEmailOrUsername($login);
            
            if (!$user) {
                error_log("DEBUG: User not found for: " . $login);
                return $this->errorResponse('Geçersiz kullanıcı adı veya şifre', 401);
            }

            error_log("DEBUG: User found: " . $user->email . " (ID: " . $user->id . ")");
            error_log("DEBUG: Stored hash: " . $user->password);
            error_log("DEBUG: Checking password: " . $password);

            if (!$user->isActive()) {
                error_log("DEBUG: User not active");
                return $this->errorResponse('Hesabınız askıya alınmış', 403);
            }

            if ($user->isLocked()) {
                error_log("DEBUG: User locked");
                return $this->errorResponse('Hesabınız kilitli. Lütfen daha sonra tekrar deneyin', 403);
            }

            if (!$user->verifyPassword($password)) {
                error_log("DEBUG: Password verification failed");
                $user->incrementLoginAttempts();
                
                $config = $this->config['security'];
                if ($user->login_attempts >= $config['max_login_attempts']) {
                    $user->lockAccount($config['lockout_duration']);
                }
                
                $user->save();
                
                return $this->errorResponse('Geçersiz kullanıcı adı veya şifre', 401);
            }

            $user->resetLoginAttempts()
                 ->unlockAccount()
                 ->updateLastLogin()
                 ->save();
            \CoreFly\Utils\Logger::info('LOGIN passwordVerify=success tokenizing for user=' . $user->username);

            // Set tenant context for logging and further operations
            $this->currentTenant = $user->tenant_id;
            $this->db->setCurrentTenant($user->tenant_id);

            $this->logActivity('user_login', 'user', $user->id, [
                'username' => $user->username,
                'email' => $user->email
            ]);

            if ($user->isTwoFactorEnabled()) {
                $sessionToken = bin2hex(random_bytes(32));
                $_SESSION['2fa_user_id'] = $user->id;
                $_SESSION['2fa_session_token'] = $sessionToken;
                $_SESSION['2fa_remember'] = $remember;
                
                return $this->successResponse([
                    'two_factor_required' => true,
                    'session_token' => $sessionToken,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'full_name' => $user->getFullName()
                    ]
                ], 'İki faktörlü doğrulama gerekli');
            }

            $tokenData = $this->generateAuthTokens($user, $remember);
            
            // Fetch Tenant details
            $tenant = Tenant::find($user->tenant_id);
            $tenantData = null;
            if ($tenant) {
                $tenantData = [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                    'active_modules' => $tenant->active_modules,
                    'settings' => $tenant->settings ?? []
                ];
            }

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'full_name' => $user->getFullName(),
                    'role' => $user->role_id,
                    'department' => $user->department_id,
                    'avatar' => $user->getAvatarUrl(),
                    'tenant' => $tenantData
                ],
                'token' => $tokenData['token'],
                'refresh_token' => $tokenData['refresh_token'],
                'expires_at' => $tokenData['expires_at']
            ], 'Giriş başarılı');

        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return $this->errorResponse('Giriş işlemi sırasında bir hata oluştu', 500);
        }
    }

    public function verifyTwoFactor(): string
    {
        try {
            $data = $this->getRequestData();
            
            $errors = $this->validateRequired($data, ['code', 'session_token']);
            if (!empty($errors)) {
                return $this->errorResponse('Validation failed', 400, $errors);
            }

            $code = $this->sanitizeInput($data['code']);
            $sessionToken = $this->sanitizeInput($data['session_token']);

            if (!isset($_SESSION['2fa_user_id']) || !isset($_SESSION['2fa_session_token'])) {
                return $this->errorResponse('İki faktörlü doğrulama oturumu bulunamadı', 400);
            }

            if ($_SESSION['2fa_session_token'] !== $sessionToken) {
                return $this->errorResponse('Geçersiz oturum token\'ı', 400);
            }

            $userId = $_SESSION['2fa_user_id'];
            $user = User::find($userId);

            if (!$user) {
                return $this->errorResponse('Kullanıcı bulunamadı', 404);
            }

            if (!$user->verifyTwoFactorCode($code)) {
                $this->logActivity('2fa_failed', 'user', $user->id, [
                    'username' => $user->username
                ]);
                
                return $this->errorResponse('Geçersiz doğrulama kodu', 401);
            }

            $remember = $_SESSION['2fa_remember'] ?? false;
            $tokenData = $this->generateAuthTokens($user, $remember);

            unset($_SESSION['2fa_user_id']);
            unset($_SESSION['2fa_session_token']);
            unset($_SESSION['2fa_remember']);

            $this->logActivity('2fa_success', 'user', $user->id, [
                'username' => $user->username
            ]);

            // Fetch Tenant details
            $tenant = Tenant::find($user->tenant_id);
            $tenantData = null;
            if ($tenant) {
                $tenantData = [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                    'active_modules' => $tenant->active_modules,
                    'settings' => $tenant->settings ?? []
                ];
            }

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'full_name' => $user->getFullName(),
                    'role' => $user->role_id,
                    'department' => $user->department_id,
                    'avatar' => $user->getAvatarUrl(),
                    'tenant' => $tenantData
                ],
                'token' => $tokenData['token'],
                'refresh_token' => $tokenData['refresh_token'],
                'expires_at' => $tokenData['expires_at']
            ], 'İki faktörlü doğrulama başarılı');

        } catch (Exception $e) {
            error_log("2FA verification error: " . $e->getMessage());
            return $this->errorResponse('İki faktörlü doğrulama sırasında bir hata oluştu', 500);
        }
    }

    public function refreshToken(): string
    {
        try {
            $data = $this->getRequestData();
            
            $errors = $this->validateRequired($data, ['refresh_token']);
            if (!empty($errors)) {
                return $this->errorResponse('Validation failed', 400, $errors);
            }

            $refreshToken = $this->sanitizeInput($data['refresh_token']);
            $tokenData = $this->jwtService->refreshToken($refreshToken);

            return $this->successResponse([
                'token' => $tokenData['token'],
                'refresh_token' => $tokenData['refresh_token'],
                'expires_at' => $tokenData['expires_at']
            ], 'Token başarıyla yenilendi');

        } catch (Exception $e) {
            return $this->errorResponse('Token yenileme başarısız: ' . $e->getMessage(), 401);
        }
    }

    public function logout(): string
    {
        try {
            $token = $this->getBearerToken();
            
            if ($token) {
                $this->jwtService->revokeToken($token);
            }

            if ($this->currentUser) {
                $this->logActivity('user_logout', 'user', $this->currentUser['user_id']);
            }

            session_destroy();

            return $this->successResponse(null, 'Çıkış başarılı');

        } catch (Exception $e) {
            return $this->errorResponse('Çıkış işlemi sırasında bir hata oluştu', 500);
        }
    }

    public function forgotPassword(): string
    {
        try {
            $data = $this->getRequestData();
            
            $errors = $this->validateRequired($data, ['email']);
            if (!empty($errors)) {
                return $this->errorResponse('Validation failed', 400, $errors);
            }

            $email = $this->sanitizeInput($data['email']);

            if (!$this->validateEmail($email)) {
                return $this->errorResponse('Geçersiz email adresi', 400);
            }

            $user = User::findByEmail($email);
            
            if (!$user) {
                return $this->successResponse(null, 'Şifre sıfırlama bağlantısı email adresinize gönderildi');
            }

            $resetToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $passwordReset = new PasswordReset([
                'user_id' => $user->id,
                'token' => $resetToken,
                'expires_at' => $expiresAt
            ]);
            $passwordReset->save();

            $this->sendPasswordResetEmail($user, $resetToken);

            $this->logActivity('password_reset_requested', 'user', $user->id, [
                'email' => $user->email
            ]);

            return $this->successResponse(null, 'Şifre sıfırlama bağlantısı email adresinize gönderildi');

        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return $this->errorResponse('Şifre sıfırlama işlemi sırasında bir hata oluştu', 500);
        }
    }

    public function resetPassword(): string
    {
        try {
            $data = $this->getRequestData();
            
            $errors = $this->validateRequired($data, ['token', 'password', 'password_confirmation']);
            if (!empty($errors)) {
                return $this->errorResponse('Validation failed', 400, $errors);
            }

            $token = $this->sanitizeInput($data['token']);
            $password = $data['password'];
            $passwordConfirmation = $data['password_confirmation'];

            if ($password !== $passwordConfirmation) {
                return $this->errorResponse('Şifreler eşleşmiyor', 400);
            }

            $passwordErrors = $this->validatePassword($password);
            if (!empty($passwordErrors)) {
                return $this->errorResponse('Şifre doğrulama hatası', 400, $passwordErrors);
            }

            $passwordReset = PasswordReset::findByToken($token);
            
            if (!$passwordReset || $passwordReset->isExpired() || $passwordReset->isUsed()) {
                return $this->errorResponse('Geçersiz veya süresi dolmuş token', 400);
            }

            $user = User::find($passwordReset->user_id);
            if (!$user) {
                return $this->errorResponse('Kullanıcı bulunamadı', 404);
            }

            $user->setPassword($password);
            $user->save();

            $passwordReset->markAsUsed();
            $passwordReset->save();

            $this->logActivity('password_reset_completed', 'user', $user->id, [
                'email' => $user->email
            ]);

            return $this->successResponse(null, 'Şifreniz başarıyla sıfırlandı');

        } catch (Exception $e) {
            error_log("Password reset completion error: " . $e->getMessage());
            return $this->errorResponse('Şifre sıfırlama işlemi sırasında bir hata oluştu', 500);
        }
    }

    public function getProfile(): string
    {
        try {
            $this->requireAuth();
            
            $userId = $this->getCurrentUserId();
            $user = User::find($userId);

            if (!$user) {
                return $this->errorResponse('Kullanıcı bulunamadı', 404);
            }

            // Fetch Tenant details (Active Tenant)
            // Use currentTenant which is set by BaseController from token (active) or user (home)
            $activeTenantId = $this->currentTenant ?? $user->tenant_id;
            $tenant = Tenant::find($activeTenantId);
            
            $tenantData = null;
            if ($tenant) {
                $tenantData = [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                    'active_modules' => $tenant->active_modules,
                    'settings' => $tenant->settings ?? []
                ];
            }

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->getFullName(),
                    'phone' => $user->phone,
                    'avatar' => $user->getAvatarUrl(),
                    'department' => $user->department_id,
                    'role' => $user->role_id,
                    'status' => $user->status,
                    'email_verified' => $user->email_verified,
                    'two_factor_enabled' => $user->two_factor_enabled,
                    'preferences' => $user->preferences,
                    'last_login_at' => $user->last_login_at,
                    'created_at' => $user->created_at,
                    'tenant' => $tenantData, // Active Tenant
                    'home_tenant_id' => $user->tenant_id // Original Home Tenant
                ]
            ]);

        } catch (Exception $e) {
            error_log("Get profile error: " . $e->getMessage());
            return $this->errorResponse('Profil bilgileri alınırken bir hata oluştu', 500);
        }
    }

    public function updateProfile(): string
    {
        try {
            $this->requireAuth();
            
            $data = $this->getRequestData();
            $userId = $this->getCurrentUserId();
            $user = User::find($userId);

            if (!$user) {
                return $this->errorResponse('Kullanıcı bulunamadı', 404);
            }

            $allowedFields = ['first_name', 'last_name', 'phone', 'preferences'];
            $updateData = [];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $this->sanitizeInput($data[$field]);
                }
            }

            if (!empty($updateData)) {
                $user->fill($updateData);
                $user->save();

                $this->logActivity('profile_updated', 'user', $user->id, [
                    'updated_fields' => array_keys($updateData)
                ]);
            }

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->getFullName(),
                    'phone' => $user->phone,
                    'avatar' => $user->getAvatarUrl()
                ]
            ], 'Profil başarıyla güncellendi');

        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            return $this->errorResponse('Profil güncellenirken bir hata oluştu', 500);
        }
    }

    public function csrfToken(): string
    {
        try {
            $token = $this->generateCsrfToken();
            return $this->successResponse(['csrf_token' => $token], 'CSRF token');
        } catch (Exception $e) {
            return $this->errorResponse('CSRF token oluşturulamadı', 500);
        }
    }

    public function switchTenant(): string
    {
        try {
            $this->requireAuth();
            
            // 1. Security Check: Only Super Admin can switch
            if ($this->getCurrentUserRole() !== 'super-admin-role') {
                return $this->errorResponse('Unauthorized access', 403);
            }

            $data = $this->getRequestData();
            $errors = $this->validateRequired($data, ['tenant_id']);
            if (!empty($errors)) {
                return $this->errorResponse('Validation failed', 400, $errors);
            }

            $targetTenantId = $this->sanitizeInput($data['tenant_id']);
            
            // 2. Verify Tenant Exists
            // We use Tenant model which is global (no tenant filter in constructor)
            $tenant = Tenant::find($targetTenantId);
            if (!$tenant) {
                return $this->errorResponse('Target tenant not found', 404);
            }

            // 3. Get Current User (Super Admin)
            $userId = $this->getCurrentUserId();
            $user = User::query()->withoutTenant()->where('id', $userId)->first();
            
            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }

            // 4. Generate New Token with Active Tenant Context
            $tokenData = $this->generateAuthTokens($user, false, $targetTenantId);

            // Log the switch
            $this->logActivity('tenant_switch', 'tenant', $targetTenantId, [
                'from_tenant' => $this->currentTenant,
                'to_tenant' => $targetTenantId
            ]);

            return $this->successResponse([
                'token' => $tokenData['token'],
                'refresh_token' => $tokenData['refresh_token'],
                'expires_at' => $tokenData['expires_at'],
                'active_tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain
                ]
            ], 'Tenant context switched successfully');

        } catch (Exception $e) {
            error_log("Tenant switch error: " . $e->getMessage());
            return $this->errorResponse('Tenant değiştirme işlemi başarısız', 500);
        }
    }

    private function generateAuthTokens(User $user, bool $remember = false, ?string $activeTenantId = null): array
    {
        $expiration = $remember ? $this->config['jwt']['refresh_expiration'] : $this->config['jwt']['expiration'];
        
        // Use provided active tenant or fallback to user's home tenant
        $targetTenantId = $activeTenantId ?? $user->tenant_id;

        return $this->jwtService->generateToken([
            'user_id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role_id,
            'department' => $user->department_id,
            'tenant_id' => $user->tenant_id,       // Home Tenant (Fixed)
            'active_tenant_id' => $targetTenantId  // Current Context (Mutable)
        ]);
    }


    private function sendPasswordResetEmail(User $user, string $token): void
    {
        // Email gönderme işlemi burada yapılacak
        // Bu bir örnek implementasyon, gerçek ortamda PHPMailer veya benzeri bir kütüphane kullanılmalı
        
        $resetUrl = $this->config['app']['url'] . "/reset-password?token={$token}";
        
        error_log("Password reset email sent to {$user->email} with token: {$resetUrl}");
    }
}
