<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Models\User;
use CoreFly\Models\Announcement;
use CoreFly\Models\Task;
use CoreFly\Models\Event;
use CoreFly\Models\Notification;
use DateTime;

class DashboardController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): string
    {
        try {
            $this->requireAuth();
            
            $userId = $this->getCurrentUserId();
            $user = User::find($userId);
            
            if (!$user) {
                return $this->errorResponse('Kullanıcı bulunamadı', 404);
            }

            // Get Tenant Info
            $tenantId = $this->getCurrentTenantId();
            $tenant = \CoreFly\Models\Tenant::find($tenantId);

            $dashboardData = [
                'user' => [
                    'id' => $user->id,
                    'full_name' => $user->getFullName(),
                    'email' => $user->email,
                    'avatar' => $user->getAvatarUrl(),
                    'department' => $user->department_id,
                    'role' => $user->role_id,
                    'tenant' => $tenant ? [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'active_modules' => $tenant->active_modules,
                        'settings' => $tenant->settings
                    ] : null
                ],
                'stats' => $this->getUserStats($user),
                'sidebar_counts' => $this->getSidebarCounts($user),
                'announcements' => $this->getRecentAnnouncements(),
                'tasks' => $this->getRecentTasks($user),
                'events' => $this->getUpcomingEvents($user),
                'notifications' => $this->getUnreadNotifications($user)
            ];

            return $this->successResponse($dashboardData, 'Dashboard verileri başarıyla getirildi');

        } catch (\Throwable $e) {
            error_log("Dashboard error: " . $e->getMessage());
            return $this->errorResponse('Dashboard error: ' . $e->getMessage(), 500);
        }
    }

    public function widgets(): string
    {
        try {
            $this->requireAuth();
            
            $userId = $this->getCurrentUserId();
            $user = User::find($userId);
            
            if (!$user) {
                return $this->errorResponse('Kullanıcı bulunamadı', 404);
            }

            // Load widget registry
            $widgetConfig = require __DIR__ . '/../../config/widgets.php';
            $availableWidgets = [];

            foreach ($widgetConfig as $key => $widget) {
                // Check permission
                if (isset($widget['permission']) && !$this->hasPermission($widget['permission'])) {
                    continue;
                }

                // Check module availability (if widget depends on a module)
                if (isset($widget['module'])) {
                    // This check should ideally match the middleware logic
                    // For now, we can skip or assume enabled if not strictly enforced here
                    // But let's enforce it
                    $tenantId = $this->getCurrentTenantId();
                    if (!$this->isModuleEnabledForTenant($tenantId, $widget['module'])) {
                        continue;
                    }
                }

                // Fetch data for the widget
                $data = [];
                switch ($key) {
                    case 'task_summary':
                        $data = $this->getTaskSummaryWidget($user);
                        break;
                    case 'announcements_latest':
                        $data = $this->getAnnouncementsWidget();
                        break;
                    case 'upcoming_events':
                    case 'calendar': // legacy mapping
                        $data = $this->getCalendarWidget($user);
                        break;
                    case 'notifications':
                        $data = $this->getNotificationsWidget($user);
                        break;
                    case 'pending_leaves':
                        // Only if HR module is active
                        if (class_exists(\CoreFly\Controllers\HrController::class)) {
                             // We might need a dedicated method or service for this
                             $data = ['count' => 0]; // Placeholder
                        }
                        break;
                    case 'active_tickets':
                         $data = ['count' => 0]; // Placeholder
                        break;
                }
                
                $widget['data'] = $data;
                $availableWidgets[$key] = $widget;
            }

            // Add legacy quick actions if not in config
            if (!isset($availableWidgets['quick_actions'])) {
                $availableWidgets['quick_actions'] = [
                    'id' => 'quick_actions',
                    'title' => 'Hızlı İşlemler',
                    'component' => 'QuickActionsWidget',
                    'size' => '2x1',
                    'data' => $this->getQuickActionsWidget($user)
                ];
            }

            return $this->successResponse($availableWidgets, 'Widget verileri başarıyla getirildi');

        } catch (\Throwable $e) {
            error_log("Dashboard widgets error: " . $e->getMessage());
            return $this->errorResponse('Widget error: ' . $e->getMessage(), 500);
        }
    }

    private function isModuleEnabledForTenant(string $tenantId, string $module): bool
    {
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("SELECT active_modules FROM tenants WHERE id = ?");
        $stmt->execute([$tenantId]);
        $row = $stmt->fetch();

        if (!$row) return false;

        $activeModules = json_decode($row['active_modules'] ?? '[]', true);
        if (!is_array($activeModules)) return false;

        return in_array($module, $activeModules);
    }

    public function activityFeed(): string
    {
        try {
            $this->requireAuth();
            
            $data = $this->getRequestData();
            $limit = (int)($data['limit'] ?? 20);
            $offset = (int)($data['offset'] ?? 0);

            $activities = $this->getRecentActivities($limit, $offset);

            return $this->successResponse([
                'activities' => $activities,
                'has_more' => count($activities) === $limit
            ], 'Aktivite akışı başarıyla getirildi');

        } catch (\Exception $e) {
            error_log("Activity feed error: " . $e->getMessage());
            return $this->errorResponse('Aktivite akışı getirilirken hata oluştu', 500);
        }
    }

    private function getUserStats(User $user): array
    {
        // Get real task statistics
        $tasks = Task::where('assigned_to', $user->id)->get();
        $total = count($tasks);
        $completed = count(array_filter($tasks, fn($t) => $t->status === 'completed'));
        $inProgress = count(array_filter($tasks, fn($t) => $t->status === 'in_progress'));
        $overdue = count(array_filter($tasks, fn($t) => $t->due_date && new DateTime($t->due_date) < new DateTime() && $t->status !== 'completed'));

        $taskStats = [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'overdue' => $overdue
        ];

        // Get unread notifications count
        $notifications = Notification::where('user_id', $user->id)->get();
        if (!is_array($notifications)) {
            $notifications = [];
        }
        $unreadNotifications = count(array_filter(
            $notifications,
            fn($n) => !$n->is_read
        ));

        // Get upcoming events count
        $events = Event::where('user_id', $user->id)->get();
        $upcomingEvents = count(array_filter($events, fn($e) => new DateTime($e->start_date) > new DateTime()));

        return [
            'tasks' => $taskStats,
            'unread_notifications' => $unreadNotifications,
            'upcoming_events' => $upcomingEvents,
            'last_login' => $user->last_login_at
        ];
    }

    private function getRecentAnnouncements(): array
    {
        $tenant = $this->getCurrentTenantId();
        $items = \CoreFly\Models\Announcement::all();
        usort($items, fn($a, $b) => ($b->is_pinned <=> $a->is_pinned) ?: strcmp((string)$b->created_at, (string)$a->created_at));
        return array_map(fn($a) => [
            'id' => $a->id,
            'title' => $a->title,
            'content' => $a->content,
            'author' => $a->author_id,
            'created_at' => $a->created_at,
            'is_pinned' => $a->is_pinned,
            'priority' => $a->priority,
        ], array_slice($items, 0, 5));
    }

    private function getRecentTasks(User $user): array
    {
        $tasks = \CoreFly\Models\Task::where('assigned_to', $user->id)->get();
        usort($tasks, fn($a, $b) => strcmp((string)$a->due_date, (string)$b->due_date));
        return array_map(fn($t) => [
            'id' => $t->id,
            'title' => $t->title,
            'description' => $t->description,
            'status' => $t->status,
            'priority' => $t->priority,
            'due_date' => $t->due_date,
            'is_overdue' => $t->due_date ? (new DateTime($t->due_date) < new DateTime()) : false,
            'progress' => $t->progress ?? 0
        ], array_slice($tasks, 0, 5));
    }

    private function getUpcomingEvents(User $user): array
    {
        $events = \CoreFly\Models\Event::where('user_id', $user->id)->get();
        $future = array_values(array_filter($events, fn($e) => new DateTime($e->start_date) > new DateTime()));
        usort($future, fn($a, $b) => strcmp((string)$a->start_date, (string)$b->start_date));
        return array_map(fn($e) => [
            'id' => $e->id,
            'title' => $e->title,
            'description' => $e->description,
            'start_date' => $e->start_date,
            'end_date' => $e->end_date,
            'location' => $e->location,
            'type' => $e->type,
            'priority' => $this->getEventPriority($e->start_date)
        ], array_slice($future, 0, 5));
    }

    private function getUnreadNotifications(User $user): array
    {
        // Get real unread notifications for the user
        $unreadNotifications = Notification::getUnreadForUser($user->id);
        
        return array_map(function($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'created_at' => $notification->created_at,
                'icon' => $notification->getTypeIcon(),
                'time_ago' => $notification->getTimeAgo(),
                'is_read' => $notification->is_read
            ];
        }, array_slice($unreadNotifications, 0, 5));
    }

    private function getDashboardStats(User $user): array
    {
        // Get tenant-specific statistics
        $tenantId = $this->getCurrentTenantId();
        
        // Count total users in tenant
        $allUsers = User::all();
        $totalUsers = count($allUsers);
        
        // Count active tasks (not completed)
        $allTasks = Task::all();
        $activeTasks = count(array_filter($allTasks, fn($t) => $t->status !== 'completed'));
        
        // Count completed tasks
        $completedTasks = count(array_filter($allTasks, fn($t) => $t->status === 'completed'));
        
        // Count announcements
        $announcements = Announcement::all();
        $totalAnnouncements = count($announcements);

        return [
            'total_users' => $totalUsers,
            'active_tasks' => $activeTasks,
            'completed_tasks' => $completedTasks,
            'total_announcements' => $totalAnnouncements
        ];
    }

    private function getTaskSummaryWidget(User $user): array
    {
        // Get real task statistics for widget
        $tasks = Task::where('assigned_to', $user->id)->get();
        $total = count($tasks);
        $completed = count(array_filter($tasks, fn($t) => $t->status === 'completed'));
        $inProgress = count(array_filter($tasks, fn($t) => $t->status === 'in_progress'));
        $todo = count(array_filter($tasks, fn($t) => $t->status === 'todo'));
        $overdue = count(array_filter($tasks, fn($t) => $t->due_date && new DateTime($t->due_date) < new DateTime() && $t->status !== 'completed'));

        return [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'todo' => $todo,
            'overdue' => $overdue
        ];
    }

    private function getAnnouncementsWidget(): array
    {
        return $this->getRecentAnnouncements();
    }

    private function getCalendarWidget(User $user): array
    {
        $events = Event::where('user_id', $user->id)->get();
        $todayEvents = [];
        $thisWeekEvents = [];
        $thisMonthEvents = [];

        $today = new DateTime();
        $thisWeek = new DateTime('+7 days');
        $thisMonth = new DateTime('+30 days');

        foreach ($events as $event) {
            $eventDate = new DateTime($event->start_date);
            
            if ($eventDate->format('Y-m-d') === $today->format('Y-m-d')) {
                $todayEvents[] = $event;
            } elseif ($eventDate <= $thisWeek) {
                $thisWeekEvents[] = $event;
            } elseif ($eventDate <= $thisMonth) {
                $thisMonthEvents[] = $event;
            }
        }

        return [
            'today' => count($todayEvents),
            'this_week' => count($thisWeekEvents),
            'this_month' => count($thisMonthEvents),
            'upcoming_events' => array_map(function($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start_date' => $event->start_date,
                    'end_date' => $event->end_date,
                    'location' => $event->location,
                    'type' => $event->type
                ];
            }, array_slice($thisWeekEvents, 0, 3))
        ];
    }

    private function getNotificationsWidget(User $user): array
    {
        return $this->getUnreadNotifications($user);
    }

    private function getQuickActionsWidget(User $user): array
    {
        return [
            [
                'id' => 'new_task',
                'title' => 'Yeni Görev',
                'icon' => 'fas fa-plus',
                'color' => 'indigo',
                'action' => 'create_task'
            ],
            [
                'id' => 'new_announcement',
                'title' => 'Duyuru Oluştur',
                'icon' => 'fas fa-bullhorn',
                'color' => 'green',
                'action' => 'create_announcement'
            ],
            [
                'id' => 'upload_document',
                'title' => 'Doküman Yükle',
                'icon' => 'fas fa-upload',
                'color' => 'yellow',
                'action' => 'upload_document'
            ],
            [
                'id' => 'create_event',
                'title' => 'Etkinlik Oluştur',
                'icon' => 'fas fa-calendar-plus',
                'color' => 'red',
                'action' => 'create_event'
            ]
        ];
    }

    private function getEventPriority(string $startDate): string
    {
        $eventDate = new DateTime($startDate);
        $today = new DateTime();
        $diff = $today->diff($eventDate)->days;

        if ($diff === 0) {
            return 'high';
        } elseif ($diff <= 7) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    private function getRecentActivities(int $limit, int $offset): array
    {
        // Mock data - will be replaced with actual AuditLog model
        return [
            [
                'id' => '1',
                'type' => 'login',
                'description' => 'Sisteme giriş yaptı',
                'user' => 'Ahmet Yılmaz',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                'icon' => 'fas fa-sign-in-alt',
                'color' => 'green'
            ],
            [
                'id' => '2',
                'type' => 'task_completed',
                'description' => 'Proje raporunu tamamladı',
                'user' => 'Ayşe Kaya',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'icon' => 'fas fa-check-circle',
                'color' => 'blue'
            ],
            [
                'id' => '3',
                'type' => 'document_uploaded',
                'description' => 'Yeni doküman yükledi',
                'user' => 'Mehmet Demir',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-3 hours')),
                'icon' => 'fas fa-file-upload',
                'color' => 'purple'
            ]
        ];
    }

    private function getSidebarCounts(User $user): array
    {
        // Tasks: Assigned to user and not completed
        $tasks = \CoreFly\Models\Task::where('assigned_to', $user->id)->get();
        $activeTasks = count(array_filter($tasks, fn($t) => $t->status !== 'completed'));

        // Messages: Receiver is user and is_read is 0
        $messages = \CoreFly\Models\Message::where('receiver_id', $user->id)->get();
        $unreadMessages = count(array_filter($messages, fn($m) => $m->is_read === 0));

        // Announcements: Total count
        $announcements = \CoreFly\Models\Announcement::all();
        $announcementCount = count($announcements);

        return [
            'tasks' => $activeTasks,
            'messages' => $unreadMessages,
            'announcements' => $announcementCount
        ];
    }
}
