<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Models\Task;
use CoreFly\Models\Subtask;
use CoreFly\Models\Project;
use CoreFly\Models\Notification;
use CoreFly\Utils\Database;

class TaskController extends BaseController
{
    public function index(): string
    {
        try {
            $this->requirePermission('tasks:read');
            $page = $this->getPaginationParams();
            
            // Get Filters
            $status = $_GET['status'] ?? null;
            $priority = $_GET['priority'] ?? null;
            $assignee = $_GET['assigned_to'] ?? null;
            $search = $_GET['search'] ?? null;

            $query = Task::query();
            
            // Role-based Scope
            $role = $this->currentUser['role'] ?? '';
            
            // 1. Department Manager: See all in department
            if ($role === 'department-manager-role') {
                $deptId = $this->currentUser['department'] ?? null;
                if ($deptId) {
                     $query->where('department_id', $deptId);
                }
            }
            // 2. Normal User (not admin/manager): See ONLY assigned tasks OR tasks in their projects
            // For MVP simplicity: See only assigned tasks
            elseif (!in_array($role, ['super-admin-role', 'tenant-admin-role', 'department-manager-role'])) {
                $query->where('assigned_to', $this->getCurrentUserId());
            }
            
            // Add sorting to avoid random order
            $query->orderBy('created_at', 'DESC');
    
            $list = $query->get();
            
            // Filter Logic
            if ($status || $priority || $assignee || $search) {
                $list = array_filter($list, function($task) use ($status, $priority, $assignee, $search) {
                    if ($status && $status !== 'all' && $task->status !== $status) return false;
                    if ($priority && $priority !== 'all' && $task->priority !== $priority) return false;
                    if ($assignee && $task->assigned_to != $assignee) return false;
                    if ($search && stripos($task->title, $search) === false && stripos($task->description ?? '', $search) === false) return false;
                    return true;
                });
            }
    
            // Enhance data with assigned user names and subtasks count
            $users = [];
            $projects = [];
            
            $userIds = array_unique(array_map(fn($t) => $t->assigned_to, $list));
            $projectIds = array_unique(array_filter(array_map(fn($t) => $t->project_id, $list)));
            
            if (!empty($userIds)) {
                try {
                    $allUsers = \CoreFly\Models\User::all(); 
                    foreach ($allUsers as $u) {
                        $users[$u->id] = $u->getFullName();
                    }
                } catch (\Exception $e) {
                    error_log("Failed to load users for tasks: " . $e->getMessage());
                }
            }
            
            if (!empty($projectIds)) {
                try {
                    $allProjects = Project::all();
                    foreach ($allProjects as $p) {
                        $projects[$p->id] = $p->title;
                    }
                } catch (\Exception $e) {
                    error_log("Failed to load projects for tasks: " . $e->getMessage());
                }
            }
    
            $listArray = array_map(function($task) use ($users, $projects) {
                $arr = $task->toArray();
                $arr['assigned_user_name'] = $users[$task->assigned_to] ?? 'Bilinmeyen';
                $arr['project_name'] = isset($task->project_id) && isset($projects[$task->project_id]) ? $projects[$task->project_id] : null;
                
                // Fetch subtasks
                try {
                    $subtasks = Subtask::where('task_id', $task->id)->get();
                    $arr['subtasks'] = array_map(fn($s) => $s->toArray(), $subtasks);
                    $arr['subtasks_count'] = count($subtasks);
                    $arr['completed_subtasks_count'] = count(array_filter($subtasks, fn($s) => $s->is_completed));
                } catch (\Exception $e) {
                    $arr['subtasks'] = [];
                    $arr['subtasks_count'] = 0;
                    $arr['completed_subtasks_count'] = 0;
                }
                
                return $arr;
            }, $list);
    
            // Re-index array after filter
            $listArray = array_values($listArray);
    
            $data = $this->paginate($listArray, $page['page'], $page['per_page']);
            return $this->successResponse($data, 'Görevler getirildi');
        } catch (\Exception $e) {
            return $this->errorResponse('Server Error: ' . $e->getMessage(), 500);
        }
    }

    public function updateStatus(): string
    {
        $this->requirePermission('tasks:update');
        $data = $this->getRequestData(); // Don't sanitize yet, handle manually
        $id = $data['id'] ?? null;
        $status = $data['status'] ?? null;
        $position = $data['position'] ?? null;

        if (!$id) return $this->errorResponse('ID gerekli', 422);
        $task = Task::find($id);
        if (!$task) return $this->errorResponse('Görev bulunamadı', 404);

        if (($this->currentUser['role'] ?? '') === 'department-manager-role') {
            if ($task->department_id !== ($this->currentUser['department'] ?? null)) {
                return $this->errorResponse('Yetkisiz işlem', 403);
            }
        }

        // Update status or position
        if ($status) $task->status = $status;
        if ($position !== null) $task->position = (int)$position;
        
        // Also update progress if status is completed
        if ($status === 'completed') {
            $task->progress = 100;
        } elseif ($status === 'todo') {
            $task->progress = 0;
        }
        
        $task->save();
        $this->logActivity('task:update_status', 'task', $task->id, ['status' => $status]);
        return $this->successResponse($task->toArray(), 'Durum güncellendi');
    }

    public function addSubtask(): string
    {
        $this->requirePermission('tasks:update');
        $data = $this->sanitizeInput($this->getRequestData());
        
        if (empty($data['task_id']) || empty($data['title'])) {
            return $this->errorResponse('Eksik veri', 422);
        }

        $subtask = new Subtask([
            'tenant_id' => $this->getCurrentTenantId(),
            'task_id' => $data['task_id'],
            'title' => $data['title'],
            'is_completed' => 0
        ]);
        $subtask->save();
        
        return $this->successResponse($subtask->toArray(), 'Alt görev eklendi');
    }

    public function toggleSubtask(): string
    {
        $this->requirePermission('tasks:update');
        $data = $this->getRequestData();
        $id = $data['id'] ?? null;
        
        if (!$id) return $this->errorResponse('ID gerekli', 422);
        $subtask = Subtask::find($id);
        if (!$subtask) return $this->errorResponse('Alt görev bulunamadı', 404);
        
        $subtask->is_completed = $subtask->is_completed ? 0 : 1;
        $subtask->save();

        // Update parent task progress
        // Logic: (completed / total) * 100
        $all = Subtask::where('task_id', $subtask->task_id)->get();
        $total = count($all);
        if ($total > 0) {
            $completed = count(array_filter($all, fn($s) => $s->is_completed));
            $task = Task::find($subtask->task_id);
            $task->progress = (int)round(($completed / $total) * 100);
            $task->save();
        }

        return $this->successResponse($subtask->toArray(), 'Durum değiştirildi');
    }

    public function create(): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('tasks:create');
        $data = $this->sanitizeInput($this->getRequestData());
        
        // DEBUG LOG
        error_log("Task Create Request Data: " . json_encode($data));
        error_log("Current Tenant ID: " . $this->getCurrentTenantId());
        error_log("Current User ID: " . $this->getCurrentUserId());

        // Check for required fields
        if (empty($data['title'])) {
            return $this->errorResponse('Başlık alanı zorunludur', 422);
        }

        // Default assigned_to if empty (e.g. self)
        if (empty($data['assigned_to'])) {
            $data['assigned_to'] = $this->getCurrentUserId();
        }

        $deptId = null;
        if (($this->currentUser['role'] ?? '') === 'department-manager-role') {
            $deptId = $this->currentUser['department'] ?? null;
            if (!$deptId) return $this->errorResponse('Departman eksik', 403);
        }
        
        $taskId = $this->generateUUID();
        $tenantId = $this->getCurrentTenantId();

        try {
            $task = new Task([
                'id' => $taskId,
                'tenant_id' => $tenantId,
                'department_id' => $deptId,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'assigned_to' => $data['assigned_to'],
                'status' => $data['status'] ?? 'todo',
                'priority' => $data['priority'] ?? 'medium',
                'due_date' => !empty($data['due_date']) ? $data['due_date'] : null,
                'progress' => isset($data['progress']) ? (int)$data['progress'] : 0,
                'project_id' => !empty($data['project_id']) ? $data['project_id'] : null,
                'tags' => $data['tags'] ?? null,
                'estimated_hours' => isset($data['estimated_hours']) ? (float)$data['estimated_hours'] : null
            ]);
            
            $saveResult = $task->save();
            error_log("Task Save Result: " . ($saveResult ? 'Success' : 'Failed'));
            error_log("Task ID Created: " . $taskId);
            
            if (!$saveResult) {
                return $this->errorResponse('Veritabanına kayıt yapılamadı', 500);
            }

            // Verify persistence
            try {
                $check = Task::find($taskId);
                if (!$check) {
                    error_log("CRITICAL: Task saved but not found immediately after! ID: $taskId");
                    // Force direct insert via PDO as fallback
                    $db = Database::getInstance();
                    $db->exec("INSERT INTO tasks (id, tenant_id, title, assigned_to, status, created_at, updated_at) VALUES ('$taskId', '$tenantId', '{$data['title']}', '{$data['assigned_to']}', 'todo', datetime('now'), datetime('now'))");
                    $task = Task::find($taskId); // Try again
                } else {
                    $task = $check; // Use fresh data
                }
            } catch (\Exception $ex) {
                error_log("Verification failed: " . $ex->getMessage());
            }

            $this->logActivity('task:create', 'task', $task->id);

            // Send Notification if assigned to someone else
            if ($task->assigned_to && $task->assigned_to !== $this->getCurrentUserId()) {
                Notification::createForUser(
                    $task->assigned_to,
                    'task',
                    'Yeni Görev Atandı',
                    "Size yeni bir görev atandı: {$task->title}",
                    ['task_id' => $task->id]
                );
            }

            return $this->successResponse($task->toArray(), 'Görev oluşturuldu');

        } catch (\Exception $e) {
            error_log("Task Create Error: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            return $this->errorResponse('Kayıt sırasında hata: ' . $e->getMessage(), 500);
        }
    }

    public function update(?string $id = null): string
    {
        try {
            $this->requireCsrfIfProd();
            $this->requirePermission('tasks:update');
            $data = $this->sanitizeInput($this->getRequestData());
            $id = $id ?? $_GET['id'] ?? $data['id'] ?? null;
            if (!$id) return $this->errorResponse('ID gerekli', 422);
            $task = Task::find($id);
            if (!$task) return $this->errorResponse('Görev bulunamadı', 404);

            if (($this->currentUser['role'] ?? '') === 'department-manager-role') {
                if ($task->department_id !== ($this->currentUser['department'] ?? null)) {
                    return $this->errorResponse('Yetkisiz işlem', 403);
                }
            }
            
            $oldAssignedTo = $task->assigned_to;

            $fields = ['title','description','status','priority','due_date','progress','assigned_to', 'project_id', 'tags', 'estimated_hours'];
            foreach ($fields as $f) {
                if (array_key_exists($f, $data)) {
                    if ($f === 'progress') $task->$f = (int)$data[$f];
                    elseif ($f === 'estimated_hours') $task->$f = (float)$data[$f];
                    else $task->$f = $this->sanitizeInput($data[$f]);
                }
            }
            
            if (!$task->save()) {
                return $this->errorResponse('Güncelleme başarısız', 500);
            }
            
            $this->logActivity('task:update', 'task', $task->id);

            // Notify new assignee
            if ($task->assigned_to && $task->assigned_to !== $oldAssignedTo && $task->assigned_to !== $this->getCurrentUserId()) {
                 Notification::createForUser(
                    $task->assigned_to, 
                    'task', 
                    'Görev Size Atandı', 
                    "Görev size devredildi: {$task->title}", 
                    ['task_id' => $task->id]
                );
            }

            return $this->successResponse($task->toArray(), 'Görev güncellendi');
        } catch (\Exception $e) {
            error_log("Task Update Error: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            return $this->errorResponse('Sunucu hatası: ' . $e->getMessage(), 500);
        }
    }

    public function delete(?string $id = null): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('tasks:delete');
        $id = $id ?? $_GET['id'] ?? null;
        if (!$id) return $this->errorResponse('ID gerekli', 422);
        $task = Task::find($id);
        if (!$task) return $this->errorResponse('Görev bulunamadı', 404);

        if (($this->currentUser['role'] ?? '') === 'department-manager-role') {
            if ($task->department_id !== ($this->currentUser['department'] ?? null)) {
                return $this->errorResponse('Yetkisiz işlem', 403);
            }
        }

        $task->delete();
        $this->logActivity('task:delete', 'task', $id);
        return $this->successResponse(['id' => $id], 'Görev silindi');
    }
}

