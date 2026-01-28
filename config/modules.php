<?php

return [
    'core' => [
        'dashboard' => [
            'name' => 'Dashboard',
            'required' => true,
        ],
        'iam' => [
            'name' => 'Kullanıcı & Rol Yönetimi',
            'required' => true,
        ],
        'announcements' => [
            'name' => 'Duyurular & Haberler',
            'required' => true,
        ],
        'tasks' => [
            'name' => 'Görev & Proje Yönetimi',
            'required' => true,
        ],
        'calendar' => [
            'name' => 'Takvim & Etkinlik',
            'required' => true,
        ],
        'dms' => [
            'name' => 'Doküman Yönetimi',
            'required' => true,
        ],
        'chat' => [
            'name' => 'İç Mesajlaşma',
            'required' => true,
        ],
        'helpdesk' => [
            'name' => 'Destek Masası',
            'required' => true,
        ],
    ],
    'optional' => [
        'hr' => [
            'name' => 'İnsan Kaynakları',
            'default' => false,
        ],
    ],
    'enterprise' => [
        'crm' => [
            'name' => 'CRM',
            'default' => false,
        ],
        'accounting' => [
            'name' => 'Finans & Muhasebe',
            'default' => false,
        ],
        'inventory' => [
            'name' => 'Stok & Envanter',
            'default' => false,
        ],
        'donations' => [
            'name' => 'STK Bağış Yönetimi',
            'default' => false,
        ],
        'field' => [
            'name' => 'Saha Operasyonları',
            'default' => false,
        ],
        'politics' => [
            'name' => 'Siyasi Parti Yönetimi',
            'default' => false,
        ],
    ],
];
