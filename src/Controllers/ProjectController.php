<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Models\Project;
use CoreFly\Models\Task;

class ProjectController extends BaseController
{
    public function index(): string
    {
        $this->requirePermission('projects:read');
        $pageParams = $this->getPaginationParams();
        
        $query = Project::query();
        
        $role = $this->currentUser['role'] ?? '';
        
        if ($role === 'department-manager-role') {
            $deptId = $this->currentUser['department'] ?? null;
            if ($deptId) {
                $query->where('department_id', $deptId);
            }
        }
        // Normal User Scope
        elseif (!in_array($role, ['super-admin-role', 'tenant-admin-role'])) {
            // Can see:
            // 1. Projects they manage (manager_id)
            // 2. Projects they are members of (need project_members table check)
            // 3. Department projects? "Kendisinin dahil olduğu projelerdeki görevleri görebilir." -> implies project membership is key.
            // Requirement: "Üye olarak bulunduğu projeleri görür"
            
            // For MVP, if we don't have JOINs in BaseModel, we might need to fetch all and filter or use raw SQL.
            // Let's filter in PHP for simplicity.
        }
        
        $projects = $query->get();
        
        // Post-Filter for Normal User
        if (!in_array($role, ['super-admin-role', 'tenant-admin-role', 'department-manager-role'])) {
            $userId = $this->getCurrentUserId();
            // Fetch project memberships
            $memberships = \CoreFly\Models\ProjectMember::where('user_id', $userId)->get();
            $projectIds = array_map(fn($m) => $m->project_id, $memberships);
            
            $projects = array_filter($projects, function($p) use ($userId, $projectIds) {
                return $p->manager_id === $userId || in_array($p->id, $projectIds);
            });
            $projects = array_values($projects);
        }
        
        // Enhance with tasks for Gantt if needed, or just basic list
        // For full Gantt, we might need a separate endpoint or include tasks here
        // Let's include a simplified task list for Gantt visualization in index for now
        
        $data = array_map(function($p) {
            $arr = $p->toArray();
            // Mock Gantt tasks based on project dates or fetch real tasks
            // Ideally fetch real tasks linked to project
            $arr['tasks'] = Task::where('project_id', $p->id)->get(); // Assuming project_id exists on tasks
            return $arr;
        }, $projects);

        $data = $this->paginate($data, $pageParams['page'], $pageParams['per_page']);
        return $this->successResponse($data, 'Projeler getirildi');
    }

    public function getGanttData(): string
    {
        $this->requirePermission('projects:read');
        $id = $_GET['id'] ?? null;
        if (!$id) return $this->errorResponse('ID gerekli', 422);
        
        $project = Project::find($id);
        if (!$project) return $this->errorResponse('Proje bulunamadı', 404);
        
        // Fetch tasks linked to this project
        // We need to ensure Task model has project_id. If not, we need migration.
        // Assuming migration will be done or we use existing structure.
        // Let's check if Task has project_id column in next step.
        // For now, returning project structure compatible with Gantt libraries (e.g., Frappe Gantt)
        
        $tasks = Task::where('project_id', $id)->get();
        
        $ganttTasks = array_map(function($t) {
            return [
                'id' => $t->id,
                'name' => $t->title,
                'start' => $t->start_date ?? date('Y-m-d'),
                'end' => $t->due_date ?? date('Y-m-d', strtotime('+1 day')),
                'progress' => $t->progress ?? 0,
                'dependencies' => $t->dependencies ?? ''
            ];
        }, $tasks);

        return $this->successResponse([
            'project' => $project->toArray(),
            'tasks' => $ganttTasks
        ], 'Gantt verileri getirildi');
    }

    public function store(): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('projects:create');
        $data = $this->sanitizeInput($this->getRequestData());
        $required = ['title', 'project_type'];
        $errors = $this->validateRequired($data, $required);
        if ($errors) {
            return $this->errorResponse('Geçersiz veri', 422, $errors);
        }

        $deptId = $data['department_id'] ?? null;
        if (($this->currentUser['role'] ?? '') === 'department-manager-role') {
            $deptId = $this->currentUser['department'] ?? null;
            if (!$deptId) return $this->errorResponse('Departman eksik', 403);
        }

        $project = new Project([
            'tenant_id' => $this->getCurrentTenantId(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'project_type' => $data['project_type'],
            'status' => $data['status'] ?? 'planning',
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'manager_id' => $data['manager_id'] ?? $this->getCurrentUserId(),
            'department_id' => $deptId,
            'budget' => $data['budget'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'progress' => 0
        ]);

        $project->save();
        $this->logActivity('project:create', 'project', $project->id);
        return $this->successResponse($project->toArray(), 'Proje oluşturuldu');
    }

    public function show(string $id): string
    {
        $this->requirePermission('projects:read');
        
        $project = Project::find($id);
        
        if (!$project || $project->tenant_id !== $this->getCurrentTenantId()) {
            return $this->errorResponse('Proje bulunamadı', 404);
        }
        
        return $this->successResponse($project->toArray(), 'Proje detayı getirildi');
    }

    public function update(?string $id = null): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('projects:update');
        $data = $this->sanitizeInput($this->getRequestData());
        
        // If ID is passed via argument (routing), use it; otherwise check GET/POST data
        if (!$id) {
            $id = $_GET['id'] ?? $data['id'] ?? null;
        }
        
        if (!$id) {
            return $this->errorResponse('ID gerekli', 422);
        }

        $project = Project::find($id);
        if (!$project || $project->tenant_id !== $this->getCurrentTenantId()) {
            return $this->errorResponse('Proje bulunamadı', 404);
        }

        if (($this->currentUser['role'] ?? '') === 'department-manager-role') {
            if ($project->department_id !== ($this->currentUser['department'] ?? null)) {
                return $this->errorResponse('Yetkisiz işlem', 403);
            }
        }

        $fields = ['title', 'description', 'project_type', 'status', 'start_date', 'end_date', 'manager_id', 'department_id', 'budget', 'priority'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $project->$field = $this->sanitizeInput($data[$field]);
            }
        }

        // Update progress based on tasks
        $project->updateProgress();
        $project->save();

        $this->logActivity('project:update', 'project', $project->id);
        return $this->successResponse($project->toArray(), 'Proje güncellendi');
    }

    public function delete(?string $id = null): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('projects:delete');
        
        if (!$id) {
            $id = $_GET['id'] ?? null;
        }
        
        if (!$id) {
            return $this->errorResponse('ID gerekli', 422);
        }

        $project = Project::find($id);
        if (!$project || $project->tenant_id !== $this->getCurrentTenantId()) {
            return $this->errorResponse('Proje bulunamadı', 404);
        }

        if (($this->currentUser['role'] ?? '') === 'department-manager-role') {
            if ($project->department_id !== ($this->currentUser['department'] ?? null)) {
                return $this->errorResponse('Yetkisiz işlem', 403);
            }
        }

        $project->delete();
        $this->logActivity('project:delete', 'project', $id);
        return $this->successResponse(['id' => $id], 'Proje silindi');
    }

    public function getTasks(): string
    {
        $this->requirePermission('projects:read');
        $id = $_GET['id'] ?? null;
        if (!$id) {
            return $this->errorResponse('Proje ID gerekli', 422);
        }

        $project = Project::find($id);
        if (!$project) {
            return $this->errorResponse('Proje bulunamadı', 404);
        }

        $tasks = $project->getTasks();
        return $this->successResponse(['tasks' => array_map(fn($task) => $task->toArray(), $tasks)], 'Proje görevleri getirildi');
    }

    public function getTeamMembers(): string
    {
        $this->requirePermission('projects:read');
        $id = $_GET['id'] ?? null;
        if (!$id) {
            return $this->errorResponse('Proje ID gerekli', 422);
        }

        $project = Project::find($id);
        if (!$project) {
            return $this->errorResponse('Proje bulunamadı', 404);
        }

        $teamMembers = $project->getTeamMembers();
        return $this->successResponse(['members' => array_map(fn($member) => $member->toArray(), $teamMembers)], 'Proje takım üyeleri getirildi');
    }

    public function updateProgress(): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('projects:update');
        $id = $_GET['id'] ?? null;
        if (!$id) {
            return $this->errorResponse('Proje ID gerekli', 422);
        }

        $project = Project::find($id);
        if (!$project) {
            return $this->errorResponse('Proje bulunamadı', 404);
        }

        $project->updateProgress();
        $project->save();

        return $this->successResponse([
            'progress' => $project->progress,
            'is_overdue' => $project->isOverdue()
        ], 'Proje ilerlemesi güncellendi');
    }

    public function getStatistics(): string
    {
        $this->requirePermission('projects:read');
        $tenantId = $this->getCurrentTenantId();
        
        $projects = Project::all();
        $totalProjects = count($projects);
        $activeProjects = count(array_filter($projects, fn($p) => $p->status === 'active'));
        $completedProjects = count(array_filter($projects, fn($p) => $p->status === 'completed'));
        $overdueProjects = count(array_filter($projects, fn($p) => $p->isOverdue()));

        return $this->successResponse([
            'total' => $totalProjects,
            'active' => $activeProjects,
            'completed' => $completedProjects,
            'overdue' => $overdueProjects
        ], 'Proje istatistikleri getirildi');
    }
}