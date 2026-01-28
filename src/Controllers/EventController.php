<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Models\Event;

class EventController extends BaseController
{
    public function index(): string
    {
        try {
            $this->requirePermission('calendar:read');
            $page = $this->getPaginationParams();
            
            $start = $_GET['start'] ?? null;
            $end = $_GET['end'] ?? null;
            
            $query = Event::query();
            
            $role = $this->currentUser['role'] ?? '';
            if ($role === 'department-manager-role') {
                $deptId = $this->currentUser['department'] ?? null;
                if ($deptId) {
                     $query->where('department_id', $deptId);
                }
            }
            
            $all = $query->get();
            
            // Post-Filter for Normal User
            if (!in_array($role, ['super-admin-role', 'tenant-admin-role', 'department-manager-role'])) {
                $deptId = $this->currentUser['department'] ?? null;
                $userId = $this->getCurrentUserId();
                $all = array_filter($all, function($e) use ($deptId, $userId) {
                    return empty($e->department_id) || $e->department_id === $deptId || $e->user_id === $userId;
                });
                $all = array_values($all);
            }
            
            if ($start && $end) {
                $filtered = array_filter($all, function($e) use ($start, $end) {
                    return $e->start_date >= $start && $e->start_date <= $end;
                });
                $list = array_values($filtered);
            } else {
                $list = $all;
            }
    
            // Calendar View (all items in range) vs List View (paginated)
            if ($start && $end) {
                // Standardize structure even for full list to avoid "map is not a function"
                // Ideally, frontend should handle both, but let's wrap it in 'data' for consistency if possible,
                // OR frontend calendar usually expects array directly.
                // Let's return array directly for calendar range requests, assuming FullCalendar or similar.
                return $this->successResponse($list, 'Etkinlikler getirildi');
            }
    
            $data = $this->paginate($list, $page['page'], $page['per_page']);
            return $this->successResponse($data, 'Etkinlikler getirildi');
        } catch (\Exception $e) {
            return $this->errorResponse('Server Error: ' . $e->getMessage(), 500);
        }
    }

    public function create(): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('calendar:create');
        $data = $this->sanitizeInput($this->getRequestData());
        
        // Auto-assign user_id if not provided
        if (!isset($data['user_id'])) {
            $data['user_id'] = $this->getCurrentUserId();
        }

        $errors = $this->validateRequired($data, ['title','user_id','start_date']);
        if ($errors) return $this->errorResponse('Geçersiz veri', 422, $errors);

        $deptId = null;
        if (($this->currentUser['role'] ?? '') === 'department-manager-role') {
            $deptId = $this->currentUser['department'] ?? null;
            if (!$deptId) return $this->errorResponse('Departman eksik', 403);
        }

        $event = new Event([
            'tenant_id' => $this->getCurrentTenantId(),
            'department_id' => $deptId,
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'location' => $data['location'] ?? null,
            'type' => $data['type'] ?? null,
            'color' => $data['color'] ?? '#3b82f6',
            'all_day' => isset($data['all_day']) ? (int)$data['all_day'] : 0,
        ]);
        $event->save();
        $this->logActivity('event:create', 'event', $event->id);
        return $this->successResponse($event->toArray(), 'Etkinlik oluşturuldu');
    }

    public function update(?string $id = null): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('calendar:update');
        $data = $this->sanitizeInput($this->getRequestData());
        $id = $id ?? $_GET['id'] ?? $data['id'] ?? null;
        if (!$id) return $this->errorResponse('ID gerekli', 422);
        $event = Event::find($id);
        if (!$event) return $this->errorResponse('Etkinlik bulunamadı', 404);

        if (($this->currentUser['role'] ?? '') === 'department-manager-role') {
            if ($event->department_id !== ($this->currentUser['department'] ?? null)) {
                return $this->errorResponse('Yetkisiz işlem', 403);
            }
        }

        foreach (['title','description','start_date','end_date','location','type','user_id','color','all_day'] as $f) {
            if (array_key_exists($f, $data)) {
                $event->$f = $this->sanitizeInput($data[$f]);
            }
        }
        $event->save();
        $this->logActivity('event:update', 'event', $event->id);
        return $this->successResponse($event->toArray(), 'Etkinlik güncellendi');
    }

    public function delete(?string $id = null): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('calendar:delete');
        $id = $id ?? $_GET['id'] ?? null;
        if (!$id) return $this->errorResponse('ID gerekli', 422);
        $event = Event::find($id);
        if (!$event) return $this->errorResponse('Etkinlik bulunamadı', 404);

        if (($this->currentUser['role'] ?? '') === 'department-manager-role') {
            if ($event->department_id !== ($this->currentUser['department'] ?? null)) {
                return $this->errorResponse('Yetkisiz işlem', 403);
            }
        }

        $event->delete();
        $this->logActivity('event:delete', 'event', $id);
        return $this->successResponse(['id' => $id], 'Etkinlik silindi');
    }
}

