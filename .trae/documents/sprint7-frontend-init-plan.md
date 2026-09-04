# Sprint 7: Frontend Initialization Plan

## Goal
Initialize the React-based frontend application to serve as the user interface for the CoreFly platform, connecting to the existing PHP backend.

## Scope

### 1. Project Setup
- **Framework**: React + TypeScript (via Vite).
- **Styling**: Tailwind CSS + Shadcn/UI (optional, or raw Tailwind).
- **State Management**: Zustand.
- **Routing**: React Router DOM.
- **HTTP Client**: Axios.

### 2. Directory Structure
- `frontend/`: Root of the frontend app.
  - `src/api`: API client and endpoints.
  - `src/components`: Reusable UI components.
  - `src/pages`: Route views (Login, Register, Dashboard).
  - `src/hooks`: Custom hooks.
  - `src/store`: Zustand stores (AuthStore).

### 3. Integration
- Configure Vite `server.proxy` to forward `/api` requests to `http://localhost:8001`.

### 4. Features
- **Authentication**:
  - Login Page (`/login`).
  - Register Page (`/register`).
  - Protected Routes (require valid JWT).
- **Dashboard Skeleton**:
  - Basic layout with Sidebar and Header.

## Tasks
1. [ ] Initialize `frontend` directory with `react-ts` template.
2. [ ] Install core dependencies.
3. [ ] Configure Tailwind CSS.
4. [ ] Setup Vite Proxy.
5. [ ] Implement `AuthService` and `useAuthStore`.
6. [ ] Create Login and Register forms.
7. [ ] Verify login flow.
