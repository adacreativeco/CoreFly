<?php

declare(strict_types=1);

namespace CoreFly\Models;

use CoreFly\Utils\Database;
use Exception;
use DateTime;

class User extends BaseModel
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    
    // Model properties
    public string $id;
    public string $tenant_id;
    public string $username;
    public string $email;
    public string $password;
    public string $first_name;
    public string $last_name;
    public ?string $phone = null;
    public ?string $avatar = null;
    public ?string $department_id = null;
    public string $role_id;
    public string $status;
    public bool $email_verified = false;
    public bool $two_factor_enabled = false;
    public ?string $two_factor_secret = null;
    public array|null $preferences = null;
    public ?string $last_login_at = null;
    public ?string $locked_until = null;
    public int $login_attempts = 0;
    public ?string $created_at = null;
    public ?string $updated_at = null;
    
    protected array $fillable = [
        'id', 'tenant_id', 'username', 'email', 'password', 'first_name', 
        'last_name', 'phone', 'avatar', 'department_id', 'role_id', 
        'status', 'email_verified', 'two_factor_enabled', 'two_factor_secret',
        'preferences', 'login_attempts', 'last_login_at', 'locked_until'
    ];
    
    protected array $hidden = ['password', 'two_factor_secret'];
    
    protected array $casts = [
        'email_verified' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'login_attempts' => 'integer',
        'preferences' => 'array',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function __construct(?array $attributes = null)
    {
        parent::__construct($attributes);
    }

    public function setPassword(string $password): self
    {
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }

        $this->password = password_hash($password, PASSWORD_BCRYPT);
        return $this;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getInitials(): string
    {
        $first = substr($this->first_name, 0, 1);
        $last = substr($this->last_name, 0, 1);
        return strtoupper($first . $last);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isEmailVerified(): bool
    {
        return $this->email_verified === true;
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled === true;
    }

    public function isLocked(): bool
    {
        if (!$this->locked_until) {
            return false;
        }

        $lockedUntil = new DateTime($this->locked_until);
        $now = new DateTime();
        
        return $now < $lockedUntil;
    }

    public function lockAccount(int $duration = 3600): self
    {
        $lockedUntil = new DateTime();
        $lockedUntil->add(new \DateInterval('PT' . $duration . 'S'));
        
        $this->locked_until = $lockedUntil->format('Y-m-d H:i:s');
        return $this;
    }

    public function unlockAccount(): self
    {
        $this->locked_until = null;
        $this->login_attempts = 0;
        return $this;
    }

    public function incrementLoginAttempts(): self
    {
        $this->login_attempts = ($this->login_attempts ?? 0) + 1;
        return $this;
    }

    public function resetLoginAttempts(): self
    {
        $this->login_attempts = 0;
        return $this;
    }

    public function updateLastLogin(): self
    {
        $this->last_login_at = date('Y-m-d H:i:s');
        return $this;
    }

    public function generateTwoFactorSecret(): string
    {
        $this->two_factor_secret = base32_encode(random_bytes(20));
        return $this->two_factor_secret;
    }

    public function getTwoFactorQrCodeUrl(): string
    {
        if (!$this->two_factor_secret) {
            $this->generateTwoFactorSecret();
        }

        $issuer = 'CoreFly';
        $label = $this->email;
        $secret = $this->two_factor_secret;

        return "otpauth://totp/{$issuer}:{$label}?secret={$secret}&issuer={$issuer}";
    }

    public function verifyTwoFactorCode(string $code): bool
    {
        if (!$this->two_factor_secret) {
            return false;
        }

        $timeSlice = intdiv(time(), 30);
        
        for ($i = -1; $i <= 1; $i++) {
            $calculatedCode = $this->generateTOTP($this->two_factor_secret, $timeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    private function generateTOTP(string $secret, int $timeSlice): string
    {
        $secretKey = base32_decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        
        $offset = ord($hash[19]) & 0xf;
        $otp = (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            ((ord($hash[$offset + 3]) & 0xff))
        ) % 1000000;

        return str_pad((string)$otp, 6, '0', STR_PAD_LEFT);
    }

    public function getAvatarUrl(): string
    {
        if ($this->avatar) {
            return $this->avatar;
        }

        return '/images/default-avatar.svg';
    }

    public function getPreferences(?string $key = null, mixed $default = null): mixed
    {
        $preferences = $this->preferences ?? [];
        
        if ($key === null) {
            return $preferences;
        }

        return $preferences[$key] ?? $default;
    }

    public function setPreferences(string $key, mixed $value): self
    {
        $preferences = $this->preferences ?? [];
        $preferences[$key] = $value;
        $this->preferences = $preferences;
        return $this;
    }

    public function hasPermission(string $permission): bool
    {
        // Try to check against database role first
        $role = $this->getRole();
        if ($role) {
            return $role->hasPermission($permission);
        }

        // Fallback to config file (legacy support)
        $config = require __DIR__ . '/../../config/app.php';
        $rolePermissions = $config['roles'][$this->role_id]['permissions'] ?? [];
        
        return in_array($permission, $rolePermissions) || in_array('*', $rolePermissions);
    }

    public function hasRole(string $role): bool
    {
        return $this->role_id === $role;
    }

    public function getDepartment(): ?Department
    {
        if (!$this->department_id) {
            return null;
        }

        return Department::find($this->department_id);
    }

    public function getRole(): ?Role
    {
        if (!$this->role_id) {
            return null;
        }

        return Role::find($this->role_id);
    }

    public static function findByEmail(string $email): ?self
    {
        return static::query()->withoutTenant()->where('email', $email)->first();
    }

    public static function findByUsername(string $username): ?self
    {
        return static::query()->withoutTenant()->where('username', $username)->first();
    }

    public static function findByEmailOrUsername(string $login): ?self
    {
        $user = static::findByEmail($login);
        if (!$user) {
            $user = static::findByUsername($login);
        }
        return $user;
    }

    public function getUnreadNotifications(): array
    {
        $notifications = Notification::where('user_id', $this->id)->get();
        return array_values(array_filter($notifications, fn($notification) => !$notification->is_read));
    }

    public function getTasks(): array
    {
        return Task::where('assigned_to', $this->id)->get();
    }

    public function getUpcomingEvents(): array
    {
        $events = Event::where('user_id', $this->id)->get();
        return array_values(array_filter($events, fn($event) => new DateTime($event->start_date) > new DateTime()));
    }
}

if (!function_exists('CoreFly\Models\base32_decode')) {
    function base32_decode(string $input): string
    {
        $map = [
            'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7,
            'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15,
            'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23,
            'Y' => 24, 'Z' => 25, '2' => 26, '3' => 27, '4' => 28, '5' => 29, '6' => 30, '7' => 31
        ];

        $input = strtoupper($input);
        $output = '';
        $length = strlen($input);
        $bits = 0;
        $value = 0;

        for ($i = 0; $i < $length; $i++) {
            $char = $input[$i];
            if (isset($map[$char])) {
                $value = ($value << 5) | $map[$char];
                $bits += 5;
                if ($bits >= 8) {
                    $output .= chr(($value >> ($bits - 8)) & 0xFF);
                    $bits -= 8;
                }
            }
        }

        return $output;
    }
}

if (!function_exists('CoreFly\Models\base32_encode')) {
    function base32_encode(string $input): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $buffer = 0;
        $bitsLeft = 0;
        $len = strlen($input);
        for ($i = 0; $i < $len; $i++) {
            $buffer = ($buffer << 8) | ord($input[$i]);
            $bitsLeft += 8;
            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $index = ($buffer >> $bitsLeft) & 31;
                $output .= $alphabet[$index];
            }
        }
        if ($bitsLeft > 0) {
            $index = ($buffer << (5 - $bitsLeft)) & 31;
            $output .= $alphabet[$index];
        }
        return $output;
    }
}
