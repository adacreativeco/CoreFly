<?php

return [
    'task_summary' => [
        'id' => 'task_summary',
        'title' => 'Görev Özeti',
        'component' => 'TaskSummaryWidget',
        'endpoint' => '/api/dashboard/widgets/tasks',
        'permission' => 'tasks:read',
        'refresh_strategy' => 'polling', // 30s
        'refresh_interval' => 30000,
        'size' => '1x1',
    ],
    'announcements_latest' => [
        'id' => 'announcements_latest',
        'title' => 'Son Duyurular',
        'component' => 'AnnouncementsWidget',
        'endpoint' => '/api/dashboard/widgets/announcements',
        'permission' => 'announcements:read',
        'refresh_strategy' => 'event',
        'size' => '2x1',
    ],
    'upcoming_events' => [
        'id' => 'upcoming_events',
        'title' => 'Yaklaşan Etkinlikler',
        'component' => 'EventsWidget',
        'endpoint' => '/api/dashboard/widgets/events',
        'permission' => 'events:read',
        'refresh_strategy' => 'polling',
        'refresh_interval' => 60000,
        'size' => '1x1',
    ],
    'pending_leaves' => [
        'id' => 'pending_leaves',
        'title' => 'Bekleyen İzinler',
        'component' => 'PendingLeavesWidget',
        'endpoint' => '/api/dashboard/widgets/leaves',
        'permission' => 'hr:read', // Depends on HR module
        'module' => 'hr',
        'refresh_strategy' => 'polling',
        'refresh_interval' => 60000,
        'size' => '1x1',
    ],
    'active_tickets' => [
        'id' => 'active_tickets',
        'title' => 'Açık Destek Talepleri',
        'component' => 'TicketsWidget',
        'endpoint' => '/api/dashboard/widgets/tickets',
        'permission' => 'helpdesk:read',
        'refresh_strategy' => 'polling',
        'refresh_interval' => 45000,
        'size' => '1x1',
    ],
];
