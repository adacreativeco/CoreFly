<?php

declare(strict_types=1);

namespace CoreFly\Utils;

use CoreFly\Models\User;

class Auth
{
    /**
     * Get the currently authenticated user.
     *
     * @return User|null
     */
    public static function user(): ?User
    {
        // TODO: Implement actual JWT or Session authentication.
        // For now, we check for a simulated user ID in the session or headers.
        
        // 1. Check Session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user_id'])) {
            return User::find($_SESSION['user_id']);
        }

        // 2. Check Authorization Header (Mock implementation)
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            // In a real app, decode JWT here. 
            // For this fix, we'll assume the token might just be a user ID for simple testing if not a real JWT.
            // Or return null if we can't validate.
            
            // If we have a way to find a user by token, do it here.
            // For now, return null to be safe unless we have a valid mechanism.
            return null;
        }

        return null;
    }

    /**
     * Check if the current user has the given permission.
     *
     * @param string $permission
     * @return bool
     */
    public static function can(string $permission): bool
    {
        $user = self::user();
        
        if (!$user) {
            return false;
        }

        // TODO: Implement role/permission logic on the User model
        // return $user->hasPermission($permission);
        
        // Temporary bypass for admin or if method doesn't exist
        return true; 
    }

    /**
     * Log in a user (set session).
     *
     * @param User $user
     * @return void
     */
    public static function login(User $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $user->id;
    }

    /**
     * Log out the current user.
     *
     * @return void
     */
    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['user_id']);
    }
}
