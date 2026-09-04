# Sprint 8: Workspace & Projects UI

## Goal
Implement the core UI layout and the functional modules for **Workspaces** (Activity Feed) and **Projects** (List & Kanban Board) in the React frontend, integrating with the existing PHP backend APIs.

## Scope
1.  **Layout System**
    *   `DashboardLayout`: Main wrapper with Sidebar and Header.
    *   `Sidebar`: Navigation menu (Dashboard, Projects, HR, Messages, etc.).
    *   `Header`: User profile dropdown, notifications icon, theme toggle.
2.  **Workspace Module (Home/Feed)**
    *   `WorkspaceFeed`: Main dashboard view showing activity posts.
    *   `CreatePost`: Component to post new updates.
    *   `PostItem`: Component to display a single post with comments.
3.  **Project Module**
    *   `ProjectList`: Grid/List view of available projects.
    *   `CreateProjectModal`: Form to create a new project.
    *   `ProjectDetail`: View showing project details and tasks.
    *   `TaskBoard`: Kanban-style board (To Do, In Progress, Done).

## Technical Tasks
1.  **Layout Implementation**
    *   Create `src/components/layout/DashboardLayout.tsx`.
    *   Create `src/components/layout/Sidebar.tsx`.
    *   Create `src/components/layout/Header.tsx`.
    *   Update `App.tsx` to use `DashboardLayout` for protected routes.
2.  **Workspace Feature**
    *   Create `src/types/workspace.ts` (Post, Comment interfaces).
    *   Create `src/services/workspaceService.ts` (API calls).
    *   Create `src/pages/Workspace/Feed.tsx`.
3.  **Project Feature**
    *   Create `src/types/project.ts` (Project, Task interfaces).
    *   Create `src/services/projectService.ts` (API calls).
    *   Create `src/pages/Projects/ProjectList.tsx`.
    *   Create `src/pages/Projects/ProjectDetail.tsx`.
    *   Create `src/components/projects/TaskBoard.tsx`.

## Verification
*   User can log in and see the Dashboard layout.
*   User can navigate between "Home" (Feed) and "Projects".
*   User can view the project list and open a project board.
