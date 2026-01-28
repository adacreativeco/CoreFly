<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Models\HrEmployee;
use CoreFly\Models\HrLeaveRequest;
use CoreFly\Utils\Database;

class HrController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    // --- Departments ---

    public function indexDepartments(): string
    {
        $this->requirePermission('hr:read');
        $tenantId = $this->getCurrentTenantId();
        
        // Debug
        error_log("DEBUG_HR: indexDepartments tenantId={$tenantId}");

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT d.*, e.first_name as manager_first, e.last_name as manager_last 
                              FROM hr_departments d 
                              LEFT JOIN hr_employees e ON d.manager_id = e.id 
                              WHERE d.tenant_id = ? 
                              ORDER BY d.name ASC");
        $stmt->execute([$tenantId]);
        $departments = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        error_log("DEBUG_HR: Found " . count($departments) . " departments for tenant {$tenantId}");
        if (count($departments) > 0) {
            error_log("DEBUG_HR: First dept: " . json_encode($departments[0]));
        }

        // Add employee count
        foreach ($departments as &$dept) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM hr_employees WHERE department_id = ? AND status = 'active'");
            $stmt->execute([$dept['id']]);
            $dept['employee_count'] = $stmt->fetchColumn();
        }

        return $this->successResponse($departments, 'Departmanlar getirildi');
    }

    public function storeDepartment(): string
    {
        $this->requirePermission('hr:create');
        $tenantId = $this->getCurrentTenantId();
        $data = $this->getJsonInput();

        $this->validateRequired($data, ['name']);

        $db = Database::getInstance();
        $id = $this->generateUUID();
        
        $stmt = $db->prepare("INSERT INTO hr_departments (id, tenant_id, name, manager_id, description) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([
            $id,
            $tenantId,
            $data['name'],
            $data['manager_id'] ?? null,
            $data['description'] ?? null
        ])) {
            return $this->successResponse(['id' => $id], 'Departman oluşturuldu', 201);
        }

        return $this->errorResponse('Departman oluşturulamadı', 500);
    }

    public function updateDepartment($id): string
    {
        $this->requirePermission('hr:update');
        $tenantId = $this->getCurrentTenantId();
        $data = $this->getJsonInput();

        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE hr_departments SET name = ?, manager_id = ?, description = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?");
        
        if ($stmt->execute([
            $data['name'],
            $data['manager_id'] ?? null,
            $data['description'] ?? null,
            $id,
            $tenantId
        ])) {
            return $this->successResponse(null, 'Departman güncellendi');
        }

        return $this->errorResponse('Departman güncellenemedi', 500);
    }

    public function destroyDepartment($id): string
    {
        $this->requirePermission('hr:delete');
        $tenantId = $this->getCurrentTenantId();
        $db = Database::getInstance();

        // Check if has employees
        $stmt = $db->prepare("SELECT COUNT(*) FROM hr_employees WHERE department_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            return $this->errorResponse('Bu departmanda çalışanlar var, önce onları taşıyın veya silin.', 400);
        }

        $stmt = $db->prepare("DELETE FROM hr_departments WHERE id = ? AND tenant_id = ?");
        if ($stmt->execute([$id, $tenantId])) {
            return $this->successResponse(null, 'Departman silindi');
        }

        return $this->errorResponse('Departman silinemedi', 500);
    }

    // --- Employees ---

    public function index(): string
    {
        $this->requirePermission('hr:read');
        $tenantId = $this->getCurrentTenantId();
        
        $page = (int)($this->getQueryParam('page', 1));
        $limit = (int)($this->getQueryParam('limit', 20));
        $offset = ($page - 1) * $limit;
        $search = $this->getQueryParam('search', '');

        $query = HrEmployee::where('tenant_id', $tenantId);

        if (!empty($search)) {
            $query->where('(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search)');
            $query->setBindings(['search' => "%{$search}%"]);
        }

        $total = $query->count();
        $employees = $query->orderBy('first_name', 'ASC')->limit($limit)->offset($offset)->get();

        return $this->successResponse([
            'items' => $employees,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ], 'Personel listesi getirildi');
    }

    public function store(): string
    {
        $this->requirePermission('hr:create');
        $tenantId = $this->getCurrentTenantId();
        $userId = $this->getCurrentUserId();
        $data = $this->getJsonInput();

        $this->validateRequired($data, ['first_name', 'last_name', 'email']);

        $employee = new HrEmployee([
            'id' => $this->generateUUID(),
            'tenant_id' => $tenantId,
            'user_id' => $data['user_id'] ?? null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'position' => $data['position'] ?? null,
            'salary' => (float)($data['salary'] ?? 0),
            'hire_date' => $data['hire_date'] ?? date('Y-m-d'),
            'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'] ?? null,
            'address' => $data['address'] ?? null,
            'emergency_contact' => $data['emergency_contact'] ?? null,
            'iban' => $data['iban'] ?? null,
            'status' => $data['status'] ?? 'active',
            'created_by' => $userId,
            'updated_by' => $userId
        ]);

        if ($employee->save()) {
            $this->logActivity('hr_employee_created', 'hr', $employee->id, ['name' => $employee->getFullName()]);
            return $this->successResponse($employee, 'Personel oluşturuldu', 201);
        }

        return $this->errorResponse('Personel oluşturulamadı', 500);
    }

    public function update(?string $id = null): string
    {
        $this->requirePermission('hr:update');
        $tenantId = $this->getCurrentTenantId();
        $employee = HrEmployee::find($id);

        if (!$employee || $employee->tenant_id !== $tenantId) {
            return $this->errorResponse('Personel bulunamadı', 404);
        }

        $data = $this->getJsonInput();
        $employee->fill($data);
        $employee->updated_by = $this->getCurrentUserId();

        if ($employee->save()) {
            return $this->successResponse($employee, 'Personel güncellendi');
        }

        return $this->errorResponse('Personel güncellenemedi', 500);
    }

    public function destroy(?string $id = null): string
    {
        $this->requirePermission('hr:delete');
        $tenantId = $this->getCurrentTenantId();
        $employee = HrEmployee::find($id);

        if (!$employee || $employee->tenant_id !== $tenantId) {
            return $this->errorResponse('Personel bulunamadı', 404);
        }

        if ($employee->delete()) {
            return $this->successResponse(null, 'Personel silindi');
        }

        return $this->errorResponse('Personel silinemedi', 500);
    }

    // --- Leave Requests ---

    public function getLeaveRequests(): string
    {
        $this->requirePermission('hr:read');
        $tenantId = $this->getCurrentTenantId();
        
        $query = HrLeaveRequest::where('tenant_id', $tenantId);
        $status = $this->getQueryParam('status', '');
        
        if (!empty($status)) {
            $query->where('status', $status);
        }

        $requests = $query->orderBy('created_at', 'DESC')->get();
        
        foreach ($requests as $req) {
            $emp = HrEmployee::find($req->employee_id);
            $req->employee_name = $emp ? $emp->getFullName() : 'Unknown';
        }

        return $this->successResponse($requests, 'İzin talepleri getirildi');
    }

    public function storeLeaveRequest(): string
    {
        $this->requirePermission('hr:create'); // Or self request logic
        $tenantId = $this->getCurrentTenantId();
        $userId = $this->getCurrentUserId();
        $data = $this->getJsonInput();

        $this->validateRequired($data, ['employee_id', 'start_date', 'end_date', 'type']);

        $start = new \DateTime($data['start_date']);
        $end = new \DateTime($data['end_date']);
        $diff = $start->diff($end);
        $days = $diff->days + 1;

        $request = new HrLeaveRequest([
            'id' => $this->generateUUID(),
            'tenant_id' => $tenantId,
            'employee_id' => $data['employee_id'],
            'type' => $data['type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days_count' => $days,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
            'created_by' => $userId,
            'updated_by' => $userId
        ]);

        if ($request->save()) {
            return $this->successResponse($request, 'İzin talebi oluşturuldu', 201);
        }

        return $this->errorResponse('İzin talebi oluşturulamadı', 500);
    }

    public function updateLeaveStatus(?string $id = null): string
    {
        $this->requirePermission('hr:approve');
        $tenantId = $this->getCurrentTenantId();
        $request = HrLeaveRequest::find($id);

        if (!$request || $request->tenant_id !== $tenantId) {
            return $this->errorResponse('Talep bulunamadı', 404);
        }

        $data = $this->getJsonInput();
        if (!isset($data['status']) || !in_array($data['status'], ['approved', 'rejected'])) {
            return $this->errorResponse('Geçersiz durum', 400);
        }

        $request->status = $data['status'];
        if ($data['status'] === 'rejected') {
            $request->rejection_reason = $data['rejection_reason'] ?? null;
        }
        $request->approved_by = $this->getCurrentUserId();
        $request->updated_by = $this->getCurrentUserId();

        if ($request->save()) {
            // TODO: Update employee leave balance if approved
            return $this->successResponse($request, 'İzin durumu güncellendi');
        }

        return $this->errorResponse('Güncelleme başarısız', 500);
    }

    public function getStatistics(): string
    {
        $this->requirePermission('hr:read');
        $tenantId = $this->getCurrentTenantId();
        $db = Database::getInstance();

        $stmt = $db->prepare("SELECT COUNT(*) FROM hr_employees WHERE tenant_id = ? AND status = 'active'");
        $stmt->execute([$tenantId]);
        $totalEmployees = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM hr_leave_requests WHERE tenant_id = ? AND status = 'pending'");
        $stmt->execute([$tenantId]);
        $pendingLeaves = $stmt->fetchColumn();

        return $this->successResponse([
            'total_employees' => $totalEmployees,
            'pending_leaves' => $pendingLeaves
        ], 'İstatistikler getirildi');
    }

    // --- Payrolls ---

    public function indexPayrolls(): string
    {
        $this->requirePermission('hr:read');
        $tenantId = $this->getCurrentTenantId();
        
        $period = $this->getQueryParam('period', date('Y-m'));
        $db = Database::getInstance();
        
        $stmt = $db->prepare("SELECT p.*, e.first_name, e.last_name, e.iban 
                              FROM hr_payrolls p 
                              JOIN hr_employees e ON p.employee_id = e.id 
                              WHERE p.tenant_id = ? AND p.period = ? 
                              ORDER BY e.first_name ASC");
        $stmt->execute([$tenantId, $period]);
        $payrolls = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->successResponse($payrolls, 'Bordrolar getirildi');
    }

    public function storePayroll(): string
    {
        try {
            $this->requirePermission('hr:create');
            $tenantId = $this->getCurrentTenantId();
            $userId = $this->getCurrentUserId();
            $data = $this->getJsonInput();

            $this->validateRequired($data, ['employee_id', 'period', 'base_salary']);

            $db = Database::getInstance();
            
            // Check if payroll exists for this employee and period
            $stmt = $db->prepare("SELECT id FROM hr_payrolls WHERE tenant_id = ? AND employee_id = ? AND period = ?");
            $stmt->execute([$tenantId, $data['employee_id'], $data['period']]);
            if ($stmt->fetch()) {
                return $this->errorResponse('Bu personel için bu dönemde zaten bordro oluşturulmuş.', 400);
            }

            $id = $this->generateUUID();
            $base = (float)$data['base_salary'];
            $bonus = (float)($data['bonus'] ?? 0);
            $deductions = (float)($data['deductions'] ?? 0);
            $net = $base + $bonus - $deductions;

            $stmt = $db->prepare("INSERT INTO hr_payrolls (
                id, tenant_id, employee_id, period, base_salary, bonus, deductions, net_salary, status, notes, created_by, updated_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if ($stmt->execute([
                $id, $tenantId, $data['employee_id'], $data['period'],
                $base, $bonus, $deductions, $net,
                'pending', $data['notes'] ?? null,
                $userId, $userId
            ])) {
                return $this->successResponse(['id' => $id], 'Bordro oluşturuldu', 201);
            }

            return $this->errorResponse('Bordro oluşturulamadı (DB Execute False)', 500);
        } catch (\Throwable $e) {
            file_put_contents('e:/corfly/debug_payroll.txt', "Payroll Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
            return $this->errorResponse('Internal Error: ' . $e->getMessage(), 500);
        }
    }

    public function updatePayrollStatus($id): string
    {
        $this->requirePermission('hr:update');
        $tenantId = $this->getCurrentTenantId();
        $data = $this->getJsonInput();
        
        $status = $data['status'] ?? 'pending';
        $paymentDate = $status === 'paid' ? date('Y-m-d') : null;

        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE hr_payrolls SET status = ?, payment_date = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?");
        
        if ($stmt->execute([$status, $paymentDate, $id, $tenantId])) {
            return $this->successResponse(null, 'Ödeme durumu güncellendi');
        }

        return $this->errorResponse('Güncelleme başarısız', 500);
    }

    public function destroyPayroll($id): string
    {
        $this->requirePermission('hr:delete');
        $tenantId = $this->getCurrentTenantId();
        
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM hr_payrolls WHERE id = ? AND tenant_id = ?");
        
        if ($stmt->execute([$id, $tenantId])) {
            return $this->successResponse(null, 'Bordro silindi');
        }

        return $this->errorResponse('Silme başarısız', 500);
    }
}
