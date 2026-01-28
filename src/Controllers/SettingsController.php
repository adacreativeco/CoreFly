<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Models\User;
use CoreFly\Utils\Validator;
use CoreFly\Utils\Auth;

class SettingsController extends BaseController
{
    /**
     * Update user profile
     */
    public function updateProfile()
    {
        $user = Auth::user();
        if (!$user) {
            $this->json(['error' => 'Unauthorized'], 401);
            return;
        }

        $data = $this->getJsonInput();
        
        $validator = new Validator($data);
        if (isset($data['email'])) $validator->email('email');
        
        if (!$validator->isValid()) {
            $this->json(['errors' => $validator->getErrors()], 422);
            return;
        }

        // Update fields if present
        if (isset($data['first_name'])) $user->first_name = $data['first_name'];
        if (isset($data['last_name'])) $user->last_name = $data['last_name'];
        if (isset($data['phone'])) $user->phone = $data['phone'];
        
        // Email update (check uniqueness if changed)
        if (isset($data['email']) && $data['email'] !== $user->email) {
            if (User::where('email', $data['email'])->exists()) {
                $this->json(['errors' => ['email' => 'Email already taken']], 422);
                return;
            }
            $user->email = $data['email'];
            $user->email_verified = false; // Require re-verification
        }

        $user->save();

        $this->json(['message' => 'Profile updated successfully', 'data' => $user]);
    }

    /**
     * Change password
     */
    public function changePassword()
    {
        $user = Auth::user();
        if (!$user) {
            $this->json(['error' => 'Unauthorized'], 401);
            return;
        }

        $data = $this->getJsonInput();
        
        $validator = new Validator($data);
        $validator->required('current_password', 'new_password', 'confirm_password');
        
        if (!$validator->isValid()) {
            $this->json(['errors' => $validator->getErrors()], 422);
            return;
        }

        if ($data['new_password'] !== $data['confirm_password']) {
            $this->json(['errors' => ['confirm_password' => 'Passwords do not match']], 422);
            return;
        }

        if (!$user->verifyPassword($data['current_password'])) {
            $this->json(['errors' => ['current_password' => 'Incorrect current password']], 422);
            return;
        }

        try {
            $user->setPassword($data['new_password']);
            $user->save();
            $this->json(['message' => 'Password changed successfully']);
        } catch (\Exception $e) {
            $this->json(['errors' => ['new_password' => $e->getMessage()]], 422);
        }
    }
}
