<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Models\User;

class PoliticsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // Tables are now managed by migrations
    }

    public function getAnalytics(): string
    {
        $this->requirePermission('politics:read');
        $tenantId = $this->getCurrentTenantId();
        $db = \CoreFly\Utils\Database::getInstance();

        // Total Voters
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM politics_voters WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
        $totalVoters = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];

        // Status Distribution
        $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM politics_voters WHERE tenant_id = ? GROUP BY status");
        $stmt->execute([$tenantId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $distribution = [];
        foreach ($rows as $row) {
            $distribution[$row['status']] = $row['count'];
        }

        // Calculate percentages
        $analytics = [
            'total_voters' => $totalVoters,
            'target_audience_percentage' => $totalVoters > 0 ? round((($distribution['decided'] ?? 0) / $totalVoters) * 100) : 0,
            'undecided_percentage' => $totalVoters > 0 ? round((($distribution['undecided'] ?? 0) / $totalVoters) * 100) : 0,
            'potential_percentage' => $totalVoters > 0 ? round((($distribution['potential'] ?? 0) / $totalVoters) * 100) : 0,
            'opponent_percentage' => $totalVoters > 0 ? round((($distribution['opponent'] ?? 0) / $totalVoters) * 100) : 0,
            'distribution' => $distribution
        ];

        return $this->successResponse($analytics, 'Siyasi analiz verileri getirildi');
    }

    // --- Voters ---

    public function indexVoters(): string
    {
        $this->requirePermission('politics:read');
        $tenantId = $this->getCurrentTenantId();
        
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM politics_voters WHERE tenant_id = ? ORDER BY created_at DESC");
        $stmt->execute([$tenantId]);
        $voters = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return $this->successResponse($voters, 'Seçmenler getirildi');
    }

    public function storeVoter(): string
    {
        $this->requirePermission('politics:create');
        $tenantId = $this->getCurrentTenantId();
        $data = $this->getJsonInput();
        
        $this->validateRequired($data, ['full_name', 'status']);
        
        $db = Database::getInstance();
        $id = $this->generateUUID();
        
        $stmt = $db->prepare("INSERT INTO politics_voters (
            id, tenant_id, full_name, tc_no, phone, address, neighborhood, box_id, status, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $id,
            $tenantId,
            $data['full_name'],
            $data['tc_no'] ?? null,
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['neighborhood'] ?? null,
            $data['box_id'] ?? null,
            $data['status'],
            $data['notes'] ?? null
        ]);
        
        return $this->successResponse(['id' => $id], 'Seçmen eklendi', 201);
    }

    public function updateVoter(string $id): string
    {
        $this->requirePermission('politics:update');
        $tenantId = $this->getCurrentTenantId();
        $data = $this->getJsonInput();
        
        $db = Database::getInstance();
        
        // Verify ownership
        $stmt = $db->prepare("SELECT id FROM politics_voters WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenantId]);
        if (!$stmt->fetch()) {
            return $this->errorResponse('Seçmen bulunamadı', 404);
        }
        
        $fields = ['full_name', 'tc_no', 'phone', 'address', 'neighborhood', 'box_id', 'status', 'notes'];
        $updates = [];
        $params = [];
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return $this->successResponse(['id' => $id], 'Değişiklik yok');
        }
        
        $params[] = $id;
        $sql = "UPDATE politics_voters SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $this->successResponse(['id' => $id], 'Seçmen güncellendi');
    }

    public function deleteVoter(string $id): string
    {
        $this->requirePermission('politics:delete');
        $tenantId = $this->getCurrentTenantId();
        
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM politics_voters WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenantId]);
        
        if ($stmt->rowCount() === 0) {
            return $this->errorResponse('Seçmen bulunamadı', 404);
        }
        
        return $this->successResponse(null, 'Seçmen silindi');
    }

    // --- Boxes ---

    public function getBoxes(): string
    {
        $this->requirePermission('politics:read');
        $tenantId = $this->getCurrentTenantId();
        
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM politics_boxes WHERE tenant_id = ? ORDER BY box_number ASC");
        $stmt->execute([$tenantId]);
        
        return $this->successResponse($stmt->fetchAll(\PDO::FETCH_ASSOC), 'Sandıklar getirildi');
    }

    public function storeBox(): string
    {
        $this->requirePermission('politics:create');
        $tenantId = $this->getCurrentTenantId();
        $userId = $this->getCurrentUserId();
        
        $data = $this->getRequestData();
        
        if (empty($data['box_number'])) {
            return $this->errorResponse('Sandık numarası zorunludur', 400);
        }

        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO politics_boxes (
                id, tenant_id, box_number, region, neighborhood, school_name, 
                official_name, official_phone, observer_name, observer_phone, 
                voter_count, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $id = uniqid();
        $stmt->execute([
            $id,
            $tenantId,
            $data['box_number'],
            $data['region'] ?? null,
            $data['neighborhood'] ?? null,
            $data['school_name'] ?? null,
            $data['official_name'] ?? null,
            $data['official_phone'] ?? null,
            $data['observer_name'] ?? null,
            $data['observer_phone'] ?? null,
            $data['voter_count'] ?? 0,
            $userId
        ]);

        return $this->successResponse(['id' => $id], 'Sandık oluşturuldu', 201);
    }

    public function updateBox(string $id): string
    {
        $this->requirePermission('politics:update');
        $tenantId = $this->getCurrentTenantId();
        
        $data = $this->getRequestData();
        $db = \CoreFly\Utils\Database::getInstance();
        
        // Build update query dynamically
        $fields = [];
        $params = [];
        
        $allowedFields = ['box_number', 'region', 'neighborhood', 'school_name', 'official_name', 'official_phone', 'observer_name', 'observer_phone', 'voter_count', 'valid_votes', 'invalid_votes'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return $this->errorResponse('Güncellenecek veri yok', 400);
        }
        
        $fields[] = "updated_at = datetime('now')";
        $params[] = $id;
        $params[] = $tenantId;
        
        $sql = "UPDATE politics_boxes SET " . implode(', ', $fields) . " WHERE id = ? AND tenant_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $this->successResponse(null, 'Sandık güncellendi');
    }

    public function deleteBox(string $id): string
    {
        $this->requirePermission('politics:delete');
        $tenantId = $this->getCurrentTenantId();
        
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("DELETE FROM politics_boxes WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenantId]);
        
        return $this->successResponse(null, 'Sandık silindi');
    }

    // --- Volunteers ---

    public function getVolunteers(): string
    {
        $this->requirePermission('politics:read');
        $tenantId = $this->getCurrentTenantId();
        
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("
            SELECT v.*, b.box_number as assigned_box_number 
            FROM politics_volunteers v 
            LEFT JOIN politics_boxes b ON v.assigned_box_id = b.id 
            WHERE v.tenant_id = ? 
            ORDER BY v.full_name ASC
        ");
        $stmt->execute([$tenantId]);
        
        return $this->successResponse($stmt->fetchAll(\PDO::FETCH_ASSOC), 'Gönüllüler getirildi');
    }

    public function storeVolunteer(): string
    {
        $this->requirePermission('politics:create');
        $tenantId = $this->getCurrentTenantId();
        $userId = $this->getCurrentUserId();
        
        $data = $this->getRequestData();
        
        if (empty($data['full_name'])) {
            return $this->errorResponse('Ad Soyad zorunludur', 400);
        }

        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO politics_volunteers (
                id, tenant_id, full_name, phone, email, role, 
                assigned_box_id, status, notes, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $id = uniqid();
        $stmt->execute([
            $id,
            $tenantId,
            $data['full_name'],
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['role'] ?? 'volunteer',
            $data['assigned_box_id'] ?? null,
            $data['status'] ?? 'active',
            $data['notes'] ?? null,
            $userId
        ]);

        return $this->successResponse(['id' => $id], 'Gönüllü eklendi', 201);
    }

    public function updateVolunteer(string $id): string
    {
        $this->requirePermission('politics:update');
        $tenantId = $this->getCurrentTenantId();
        
        $data = $this->getRequestData();
        $db = \CoreFly\Utils\Database::getInstance();
        
        $fields = [];
        $params = [];
        
        $allowedFields = ['full_name', 'phone', 'email', 'role', 'assigned_box_id', 'status', 'notes'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return $this->errorResponse('Güncellenecek veri yok', 400);
        }
        
        $fields[] = "updated_at = datetime('now')";
        $params[] = $id;
        $params[] = $tenantId;
        
        $sql = "UPDATE politics_volunteers SET " . implode(', ', $fields) . " WHERE id = ? AND tenant_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $this->successResponse(null, 'Gönüllü güncellendi');
    }

    public function deleteVolunteer(string $id): string
    {
        $this->requirePermission('politics:delete');
        $tenantId = $this->getCurrentTenantId();
        
        $db = \CoreFly\Utils\Database::getInstance();
        $stmt = $db->prepare("DELETE FROM politics_volunteers WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenantId]);
        
        return $this->successResponse(null, 'Gönüllü silindi');
    }
}
