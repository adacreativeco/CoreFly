# Sprint 3: Projects & Tasks Module Plan

## Goal
Implement the Project Management module to allow tenants to manage projects, assign teams, and track tasks.

## Scope

### 1. Database Schema
- **Projects Table** (`projects`): Stores project details, budget, status, and dates.
- **Project Members Table** (`project_members`): Links Users to Projects with roles (many-to-many).
- **Project Tasks Table** (`project_tasks`): Individual work items with assignees, status, and priority.

### 2. Backend API
- **Project Management**:
  - `GET /api/projects/{tenantId}`: List all projects.
  - `POST /api/projects/{tenantId}`: Create new project.
  - `GET /api/projects/{tenantId}/{projectId}`: Get details.
  - `PUT /api/projects/{tenantId}/{projectId}`: Update details.
  - `DELETE /api/projects/{tenantId}/{projectId}`: Delete project.

- **Project Members**:
  - `POST /api/projects/{tenantId}/{projectId}/members`: Add member to project.
  - `DELETE /api/projects/{tenantId}/{projectId}/members/{userId}`: Remove member.

- **Task Management**:
  - `GET /api/projects/{tenantId}/{projectId}/tasks`: List tasks for a project.
  - `POST /api/projects/{tenantId}/{projectId}/tasks`: Create task.
  - `PATCH /api/projects/{tenantId}/{projectId}/tasks/{taskId}`: Update task status/assignee.

### 3. Verification
- Create `scripts/test_projects.php` to verify:
  1. Create Project.
  2. Add Member to Project.
  3. Create Task assigned to Member.
  4. Update Task status.

## Tasks
1. [ ] Create migration SQL files.
2. [ ] Run migrations.
3. [ ] Implement `Project`, `ProjectMember`, `ProjectTask` models.
4. [ ] Implement `ProjectController`, `ProjectTaskController`.
5. [ ] Register routes in `public/index.php`.
6. [ ] Create and run verification script.
