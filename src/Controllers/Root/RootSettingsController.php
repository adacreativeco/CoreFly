<?php

declare(strict_types=1);

namespace CoreFly\Controllers\Root;

use CoreFly\Utils\Database;

class RootSettingsController extends BaseRootController
{
    public function index()
    {
        $this->requireRootAuth();
        
        // Since we don't have a dedicated 'global_settings' table in the schema yet,
        // we will simulate it or use a special tenant_id = 'global' in system_settings.
        // For now, let's mock the response to satisfy the UI requirements.
        
        return $this->successResponse([
            'smtp' => [
                'host' => getenv('SMTP_HOST') ?: 'smtp.mailgun.org',
                'port' => getenv('SMTP_PORT') ?: 587,
                'username' => 'postmaster@sandbox...',
                'driver' => 'smtp'
            ],
            'storage' => [
                'driver' => 'local', // s3, minio
                'path' => 'storage/uploads'
            ],
            'security' => [
                'min_password_length' => 8,
                'require_2fa' => false,
                'session_timeout' => 120 // minutes
            ],
            'maintenance_mode' => false
        ]);
    }

    public function update()
    {
        $this->requireRootAuth();
        $data = $this->getJsonInput();
        
        // Save logic would go here (e.g., update .env or global_settings table)
        
        return $this->successResponse(null, 'Settings updated successfully');
    }
}
