# Sprint 2: HR Core Module Plan

## Goal
Implement the core Human Resources structure: Departments, Positions, and Employee profiles. This forms the organizational backbone of the CoreFly Enterprise platform.

## Scope

### 1. Database Schema
- **Departments Table** (`departments`): Hierarchical structure for organization units.
- **Positions Table** (`positions`): Job titles and definitions.
- **Employees Table** (`employees`): Extended profile for Users with HR specific data.

### 2. Backend API
- **Department Management**:
  - `GET /api/hr/{tenantId}/departments`: List all departments (tree structure optional).
  - `POST /api/hr/{tenantId}/departments`: Create new department.
  - `GET /api/hr/{tenantId}/departments/{id}`: Get details.
  - `PUT /api/hr/{tenantId}/departments/{id}`: Update details.
  - `DELETE /api/hr/{tenantId}/departments/{id}`: Delete (check for children/employees).

- **Position Management**:
  - `GET /api/hr/{tenantId}/positions`: List positions.
  - `POST /api/hr/{tenantId}/positions`: Create position.

- **Employee Management**:
  - `GET /api/hr/{tenantId}/employees`: List employees.
  - `POST /api/hr/{tenantId}/employees`: Create employee profile (link to existing User).
  - `GET /api/hr/{tenantId}/employees/{id}`: Get profile.

### 3. Verification
- Create `scripts/test_hr.php` to verify the complete flow:
  1. Create Departments (Parent -> Child).
  2. Create Positions linked to Departments.
  3. Create Employee linked to User, Department, and Position.
  4. Verify data integrity.

## Tasks
1. [ ] Create migration SQL files.
2. [ ] Run migrations.
3. [ ] Implement `Department`, `Position`, `Employee` models.
4. [ ] Implement `DepartmentController`, `PositionController`, `EmployeeController`.
5. [ ] Register routes in `public/index.php`.
6. [ ] Create and run verification script.
