<?php

declare(strict_types=1);

namespace CoreFly\Models;

class Project extends BaseModel
{
    protected string $table = 'projects';
    protected string $primaryKey = 'id';

    // Project properties
    public string $id;
    public string $tenant_id;
    public string $title;
    public ?string $description = null;
    public string $project_type;
    public string $status;
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?string $manager_id = null;
    public ?string $department_id = null;
    public int $progress = 0;
    public ?string $budget = null;
    public ?string $priority = null;


    protected array $fillable = [
        'id', 'tenant_id', 'title', 'description', 'project_type', 'status',
        'start_date', 'end_date', 'manager_id', 'department_id', 'progress',
        'budget', 'priority'
    ];

    protected array $casts = [
        'progress' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function getManager(): ?User
    {
        return $this->manager_id ? User::find($this->manager_id) : null;
    }

    public function getDepartment(): ?Department
    {
        return $this->department_id ? Department::find($this->department_id) : null;
    }

    public function getTasks(): array
    {
        return Task::where('project_id', $this->id)->get();
    }

    public function getTeamMembers(): array
    {
        return User::where('department_id', $this->department_id)->get();
    }

    public function updateProgress(): void
    {
        $tasks = $this->getTasks();
        if (count($tasks) === 0) {
            $this->progress = 0;
            return;
        }

        $completedTasks = array_filter($tasks, fn($task) => $task->status === 'completed');
        $this->progress = (int)round((count($completedTasks) / count($tasks)) * 100);
    }

    public function isOverdue(): bool
    {
        if (!$this->end_date) {
            return false;
        }
        return new \DateTime($this->end_date) < new \DateTime() && $this->status !== 'completed';
    }

    public function getDaysRemaining(): ?int
    {
        if (!$this->end_date) {
            return null;
        }
        $now = new \DateTime();
        $end = new \DateTime($this->end_date);
        $diff = $now->diff($end);
        return $diff->days * ($diff->invert ? -1 : 1);
    }

    public function getBudgetFormatted(): string
    {
        if (!$this->budget) {
            return 'Belirtilmemiş';
        }
        return '₺' . number_format((float)$this->budget, 2, ',', '.');
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'planning' => 'gray',
            'active' => 'blue',
            'on_hold' => 'yellow',
            'completed' => 'green',
            'cancelled' => 'red',
            default => 'gray'
        };
    }

    public function getPriorityColor(): string
    {
        return match ($this->priority) {
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'orange',
            'urgent' => 'red',
            default => 'gray'
        };
    }
}