<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Models\Department;
use CoreFly\Models\User;

class DepartmentController extends BaseController
{
    public function index(): string
    {
        $this->requirePermission('users:read');
        
        $departments = Department::all();
        
        // Enrich with manager info
        foreach ($departments as $dept) {
            if ($dept->manager_id) {
                $manager = User::find($dept->manager_id);
                $dept->manager_name = $manager ? $manager->getFullName() : null;
            }
            $dept->user_count = count($dept->getUsers());
        }
        
        return $this->successResponse(['items' => $departments], 'Departmanlar getirildi');
    }

    public function store(): string
    {
        $this->requirePermission('users:create'); // Assuming department management falls under user management scope for now
        $data = $this->getJsonInput();
        
        if (empty($data['name'])) {
            return $this->errorResponse('Departman adı gerekli', 422);
        }
        
        $dept = new Department();
        // $dept->id = $this->generateUUID(); // Let Model handle ID generation to avoid save() thinking it's an update
        $dept->tenant_id = $this->getCurrentTenantId();
        $dept->name = $data['name'];
        $dept->description = $data['description'] ?? '';
        $dept->manager_id = $data['manager_id'] ?? null;
        $dept->parent_id = $data['parent_id'] ?? null;
        $dept->status = 'active';
        $dept->save();
        
        return $this->successResponse($dept, 'Departman oluşturuldu', 201);
    }

    public function show(string $id): string
    {
        $this->requirePermission('users:read');
        $dept = Department::find($id);
        
        if (!$dept) {
            return $this->errorResponse('Departman bulunamadı', 404);
        }
        
        return $this->successResponse($dept, 'Departman detayı getirildi');
    }

    public function update(string $id): string
    {
        $this->requirePermission('users:update');
        $dept = Department::find($id);
        
        if (!$dept) {
            return $this->errorResponse('Departman bulunamadı', 404);
        }
        
        $data = $this->getJsonInput();
        if (isset($data['name'])) $dept->name = $data['name'];
        if (isset($data['description'])) $dept->description = $data['description'];
        if (isset($data['manager_id'])) $dept->manager_id = $data['manager_id'];
        if (isset($data['parent_id'])) $dept->parent_id = $data['parent_id'];
        if (isset($data['status'])) $dept->status = $data['status'];
        
        $dept->save();
        
        return $this->successResponse($dept, 'Departman güncellendi');
    }

    public function destroy(string $id): string
    {
        $this->requirePermission('users:delete');
        $dept = Department::find($id);
        
        if (!$dept) {
            return $this->errorResponse('Departman bulunamadı', 404);
        }
        
        // Check if users assigned
        if (count($dept->getUsers()) > 0) {
            return $this->errorResponse('Kullanıcısı olan departman silinemez', 400);
        }
        
        $dept->delete();
        return $this->successResponse(null, 'Departman silindi');
    }
}
