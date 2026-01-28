<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Models\FieldActivity;
use CoreFly\Models\Member;
use CoreFly\Models\FieldZone;
use CoreFly\Models\FieldTeam;

class FieldController extends BaseController
{
    // --- Zones ---

    public function indexZones(): string
    {
        $this->requirePermission('field:read');
        $list = FieldZone::all();
        return $this->successResponse(['items' => $list], 'Bölgeler getirildi');
    }

    public function createZone(): string
    {
        try {
            $this->requireCsrfIfProd();
            $this->requirePermission('field:create');
            $data = $this->sanitizeInput($this->getRequestData());
            $errors = $this->validateRequired($data, ['name', 'status']);
            if ($errors) return $this->errorResponse('Geçersiz veri', 422, $errors);

            $zone = new FieldZone([
                'tenant_id' => $this->getCurrentTenantId(),
                'name' => $data['name'],
                'manager' => $data['manager'] ?? null,
                'target' => $data['target'] ?? null,
                'status' => $data['status']
            ]);
            $zone->save();
            return $this->successResponse($zone->toArray(), 'Bölge oluşturuldu');
        } catch (\Throwable $e) {
            file_put_contents('debug_error.txt', "FieldZone Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
            return $this->errorResponse('Internal Error: ' . $e->getMessage(), 500);
        }
    }

    public function updateZone(string $id): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('field:update');
        $data = $this->sanitizeInput($this->getRequestData());
        
        $zone = FieldZone::find($id);
        if (!$zone) return $this->errorResponse('Bölge bulunamadı', 404);

        $fillable = ['name', 'manager', 'target', 'status'];
        foreach ($fillable as $field) {
            if (isset($data[$field])) $zone->$field = $data[$field];
        }
        $zone->save();
        return $this->successResponse($zone->toArray(), 'Bölge güncellendi');
    }

    public function deleteZone(string $id): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('field:delete');
        $zone = FieldZone::find($id);
        if (!$zone) return $this->errorResponse('Bölge bulunamadı', 404);
        $zone->delete();
        return $this->successResponse(['id' => $id], 'Bölge silindi');
    }

    // --- Teams ---

    public function indexTeams(): string
    {
        try {
            $this->requirePermission('field:read');
            $list = FieldTeam::all();
            return $this->successResponse($list, 'Ekipler getirildi');
        } catch (\Throwable $e) {
            file_put_contents('debug_error.txt', "FieldTeam Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
            return $this->errorResponse('Internal Error: ' . $e->getMessage(), 500);
        }
    }

    // --- Field Activities ---

    public function indexActivities(): string
    {
        $this->requirePermission('field:read');
        $page = $this->getPaginationParams();
        $list = FieldActivity::all(); // In real scenario, filter by date/status
        $data = $this->paginate($list, $page['page'], $page['per_page']);
        return $this->successResponse($data, 'Saha aktiviteleri getirildi');
    }

    public function createActivity(): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('field:create');
        $data = $this->sanitizeInput($this->getRequestData());
        $errors = $this->validateRequired($data, ['title', 'type', 'date', 'location']);
        if ($errors) return $this->errorResponse('Geçersiz veri', 422, $errors);

        $activity = new FieldActivity([
            'tenant_id' => $this->getCurrentTenantId(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'],
            'type' => $data['type'],
            'status' => $data['status'] ?? 'planned',
            'date' => $data['date'],
            'organizer_id' => $this->getCurrentUserId(),
            'metadata' => $data['metadata'] ?? []
        ]);
        $activity->save();
        $this->logActivity('field_activity:create', 'field_activity', $activity->id);
        return $this->successResponse($activity->toArray(), 'Aktivite oluşturuldu');
    }

    public function updateActivity(string $id): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('field:update');
        $data = $this->sanitizeInput($this->getRequestData());
        
        $activity = FieldActivity::find($id);
        if (!$activity) return $this->errorResponse('Aktivite bulunamadı', 404);

        $fillable = ['title', 'description', 'location', 'type', 'status', 'date', 'metadata'];
        foreach ($fillable as $field) {
            if (isset($data[$field])) {
                $activity->$field = $data[$field];
            }
        }

        $activity->save();
        $this->logActivity('field_activity:update', 'field_activity', $activity->id);
        return $this->successResponse($activity->toArray(), 'Aktivite güncellendi');
    }

    public function deleteActivity(string $id): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('field:delete');
        
        $activity = FieldActivity::find($id);
        if (!$activity) return $this->errorResponse('Aktivite bulunamadı', 404);

        $activity->delete();
        $this->logActivity('field_activity:delete', 'field_activity', $id);
        return $this->successResponse(['id' => $id], 'Aktivite silindi');
    }

    // --- Member Management (Voter/Member) ---

    public function indexMembers(): string
    {
        $this->requirePermission('members:read');
        $page = $this->getPaginationParams();
        $list = Member::all();
        $data = $this->paginate($list, $page['page'], $page['per_page']);
        return $this->successResponse($data, 'Üyeler getirildi');
    }

    public function createMember(): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('members:create');
        $data = $this->sanitizeInput($this->getRequestData());
        $errors = $this->validateRequired($data, ['first_name', 'last_name']);
        if ($errors) return $this->errorResponse('Geçersiz veri', 422, $errors);

        $member = new Member([
            'tenant_id' => $this->getCurrentTenantId(),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'tc_no' => $data['tc_no'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'city' => $data['city'] ?? null,
            'district' => $data['district'] ?? null,
            'neighborhood' => $data['neighborhood'] ?? null,
            'ballot_box_no' => $data['ballot_box_no'] ?? null,
            'membership_status' => $data['membership_status'] ?? 'active',
            'membership_date' => $data['membership_date'] ?? date('Y-m-d')
        ]);
        $member->save();
        $this->logActivity('member:create', 'member', $member->id);
        return $this->successResponse($member->toArray(), 'Üye kaydedildi');
    }

    public function updateMember(string $id): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('members:update');
        $data = $this->sanitizeInput($this->getRequestData());
        
        $member = Member::find($id);
        if (!$member) return $this->errorResponse('Üye bulunamadı', 404);

        $fillable = ['first_name', 'last_name', 'tc_no', 'phone', 'email', 'city', 'district', 'neighborhood', 'ballot_box_no', 'membership_status', 'membership_date'];
        foreach ($fillable as $field) {
            if (isset($data[$field])) {
                $member->$field = $data[$field];
            }
        }

        $member->save();
        $this->logActivity('member:update', 'member', $member->id);
        return $this->successResponse($member->toArray(), 'Üye güncellendi');
    }

    public function deleteMember(string $id): string
    {
        $this->requireCsrfIfProd();
        $this->requirePermission('members:delete');
        
        $member = Member::find($id);
        if (!$member) return $this->errorResponse('Üye bulunamadı', 404);

        $member->delete();
        $this->logActivity('member:delete', 'member', $id);
        return $this->successResponse(['id' => $id], 'Üye silindi');
    }
    
    public function getStats(): string
    {
        $this->requirePermission('field:read');
        
        // Mock stats for now or simple counts
        $totalMembers = Member::query()->count();
        $activities = FieldActivity::query()->count();
        $districts = []; // Distinct districts count logic would go here
        
        return $this->successResponse([
            'total_members' => $totalMembers,
            'total_activities' => $activities,
            'active_volunteers' => 0 // Placeholder
        ], 'İstatistikler getirildi');
    }
}
