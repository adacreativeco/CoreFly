<?php

return [
    'database' => [
        'default' => [
            'driver' => $_ENV['DB_DRIVER'] ?? 'sqlite',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_NAME'] ?? (__DIR__ . '/../storage/corefly.sqlite'),
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
        ],
    ],

    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? 'default-secret-key',
        'expiration' => (int)($_ENV['JWT_EXPIRATION'] ?? 3600),
        'refresh_expiration' => (int)($_ENV['JWT_REFRESH_EXPIRATION'] ?? 86400),
        'algorithm' => 'HS256',
    ],

    'app' => [
        'name' => $_ENV['APP_NAME'] ?? 'CoreFly',
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'url' => $_ENV['APP_URL'] ?? 'http://localhost',
        'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Europe/Istanbul',
        'locale' => $_ENV['APP_LOCALE'] ?? 'tr',
    ],

    'security' => [
        'csrf_protection' => filter_var($_ENV['CSRF_PROTECTION'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'session_lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 120),
        'password_min_length' => (int)($_ENV['PASSWORD_MIN_LENGTH'] ?? 8),
        'password_complexity' => filter_var($_ENV['PASSWORD_COMPLEXITY'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'max_login_attempts' => (int)($_ENV['MAX_LOGIN_ATTEMPTS'] ?? 5),
        'lockout_duration' => (int)($_ENV['LOCKOUT_DURATION'] ?? 3600),
    ],

    'mail' => [
        'driver' => $_ENV['MAIL_DRIVER'] ?? 'smtp',
        'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
        'port' => (int)($_ENV['MAIL_PORT'] ?? 587),
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'from' => [
            'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@corefly.com',
            'name' => $_ENV['MAIL_FROM_NAME'] ?? 'CoreFly Sistemi',
        ],
    ],

    'filesystem' => [
        'default' => $_ENV['FILESYSTEM_DISK'] ?? 'local',
        'max_file_size' => (int)($_ENV['MAX_FILE_SIZE'] ?? 10485760),
        'allowed_file_types' => explode(',', $_ENV['ALLOWED_FILE_TYPES'] ?? 'pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,zip,rar'),
        'upload_path' => $_ENV['UPLOAD_PATH'] ?? 'storage/uploads',
    ],

    'cache' => [
        'driver' => $_ENV['CACHE_DRIVER'] ?? 'file',
        'lifetime' => (int)($_ENV['CACHE_LIFETIME'] ?? 3600),
    ],

    'logging' => [
        'channel' => $_ENV['LOG_CHANNEL'] ?? 'daily',
        'level' => $_ENV['LOG_LEVEL'] ?? 'debug',
        'path' => $_ENV['LOG_PATH'] ?? 'storage/logs',
    ],

    'multi_tenant' => [
        'enabled' => filter_var($_ENV['MULTI_TENANT_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'identification' => $_ENV['TENANT_IDENTIFICATION'] ?? 'domain',
        'default_tenant' => $_ENV['DEFAULT_TENANT'] ?? 'default',
    ],

    'modules' => [
        'announcements' => filter_var($_ENV['ANNOUNCEMENTS_MODULE'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'documents' => filter_var($_ENV['DOCUMENTS_MODULE'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'tasks' => filter_var($_ENV['TASKS_MODULE'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'projects' => filter_var($_ENV['PROJECTS_MODULE'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'calendar' => filter_var($_ENV['CALENDAR_MODULE'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'messaging' => filter_var($_ENV['MESSAGING_MODULE'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'helpdesk' => filter_var($_ENV['HELPDESK_MODULE'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'field_management' => filter_var($_ENV['FIELD_MANAGEMENT_MODULE'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'ngo' => filter_var($_ENV['NGO_MODULE'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'inventory' => filter_var($_ENV['INVENTORY_MODULE'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'crm' => filter_var($_ENV['CRM_MODULE'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'hr' => filter_var($_ENV['HR_MODULE'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'accounting' => filter_var($_ENV['ACCOUNTING_MODULE'] ?? true, FILTER_VALIDATE_BOOLEAN),
    ],

    // Default role permissions (used until DB-driven permissions are implemented)
    'roles' => [
        'super-admin-role' => [ 'permissions' => ['*'] ],
        'tenant-admin-role' => [ 'permissions' => [
            'users:create','users:read','users:update','users:delete',
            'departments:create','departments:read','departments:update','departments:delete',
            'announcements:create','announcements:read','announcements:update','announcements:delete',
            'documents:create','documents:read','documents:update','documents:delete',
            'tasks:create','tasks:read','tasks:update','tasks:delete',
            'projects:create','projects:read','projects:update','projects:delete',
            'calendar:create','calendar:read','calendar:update','calendar:delete',
            'messaging:create','messaging:read','messaging:update','messaging:delete',
            'helpdesk:create','helpdesk:read','helpdesk:update','helpdesk:delete',
            'reports:read',
            'crm:read','crm:create','crm:update','crm:delete',
            'hr:read','hr:create','hr:update','hr:delete','hr:approve',
            'accounting.view','accounting.create','accounting.delete',
            'inventory:read','inventory:create','inventory:update','inventory:delete',
            'donations:read','donations:create','donations:update','donations:delete',
            'field:read','field:create','field:update','field:delete',
            'members:read','members:create','members:update','members:delete',
            'politics:read','politics:create','politics:update','politics:delete',
            'system_settings:read','system_settings:update'
        ] ],
        'department-manager-role' => [ 'permissions' => [
            'users:read','users:update',
            'announcements:read',
            'documents:read','documents:create','documents:update',
            'tasks:create','tasks:read','tasks:update','tasks:delete',
            'projects:create','projects:read','projects:update','projects:delete',
            'calendar:create','calendar:read','calendar:update','calendar:delete',
            'messaging:create','messaging:read','messaging:update','messaging:delete',
            'helpdesk:create','helpdesk:read','helpdesk:update',
            'reports:read',
            'politics:read','politics:create','politics:update','politics:delete'
        ] ],
        'user-role' => [ 'permissions' => [
            'announcements:read','documents:read','tasks:read','tasks:update','projects:read','projects:update','calendar:read','calendar:create','calendar:update','messaging:create','messaging:read','messaging:update','helpdesk:create','helpdesk:read','profile:read','profile:update',
            'politics:read'
        ] ],
    ],
];
