import { BrowserRouter as Router, Routes, Route, Navigate } from "react-router-dom";
import Home from "@/pages/Home";
import { Login } from "@/pages/Login";
import { Register } from "@/pages/Register";
import { useAuthStore } from "@/store/authStore";
import { DashboardLayout } from "@/components/layout/DashboardLayout";

import { ProjectList } from "@/pages/Projects/ProjectList";
import { ProjectDetail } from "@/pages/Projects/ProjectDetail";
import { EmployeeList } from "@/pages/HR/EmployeeList";
import { DepartmentList } from "@/pages/HR/DepartmentList";
import { ChatLayout } from "@/pages/Messages/ChatLayout";

import { FileList } from "@/pages/Files/FileList";
import { SettingsLayout } from "@/pages/Settings/SettingsLayout";
import { ProfileSettings } from "@/pages/Settings/ProfileSettings";
import { TenantSettings } from "@/pages/Settings/TenantSettings";
import { CrmDashboard } from "@/pages/CRM/CrmDashboard";
import { InventoryDashboard } from "@/pages/Inventory/InventoryDashboard";
import { AccountingDashboard } from "@/pages/Accounting/AccountingDashboard";
import { CalendarDashboard } from "@/pages/Calendar/CalendarDashboard";
import { AnnouncementDashboard } from "@/pages/Announcements/AnnouncementDashboard";
import { HelpdeskDashboard } from "@/pages/Helpdesk/HelpdeskDashboard";
import { FieldDashboard } from "@/pages/Field/FieldDashboard";
import { DonationDashboard } from "@/pages/Donations/DonationDashboard";
import { PoliticsDashboard } from "@/pages/Politics/PoliticsDashboard";
import { RootTenantsDashboard } from "@/pages/Admin/RootTenantsDashboard";
import { RoleManagement } from "@/pages/Admin/RoleManagement";
import { AuditLogDashboard } from "@/pages/Admin/AuditLogDashboard";
import { LeaveRequestList } from "@/pages/HR/LeaveRequestList";
import { PayrollList } from "@/pages/HR/PayrollList";
import { TaskList } from "@/pages/Tasks/TaskList";
import { SecuritySettings } from "@/pages/Settings/SecuritySettings";
import { ToastContainer } from "@/components/common/ToastContainer";

const ProtectedRoute = ({ children }: { children: JSX.Element }) => {
  const token = useAuthStore((state) => state.token);
  if (!token) {
    return <Navigate to="/login" replace />;
  }
  return children;
};

const AdminRoute = ({ children }: { children: JSX.Element }) => {
  const user = useAuthStore((state) => state.user);
  const isAdmin = user?.role === 'admin' || user?.role === 'superadmin' || user?.email === 'admin@corefly.com';
  if (!isAdmin) {
    return <Navigate to="/dashboard" replace />;
  }
  return children;
};

export default function App() {
  return (
    <Router>
      <ToastContainer />
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        
        <Route
          element={
            <ProtectedRoute>
              <DashboardLayout />
            </ProtectedRoute>
          }
        >
          <Route path="/dashboard" element={<Home />} />
          <Route path="/crm" element={<CrmDashboard />} />
          <Route path="/accounting" element={<AccountingDashboard />} />
          <Route path="/inventory" element={<InventoryDashboard />} />
          <Route path="/field" element={<FieldDashboard />} />
          <Route path="/donations" element={<DonationDashboard />} />
          <Route path="/politics" element={<PoliticsDashboard />} />
          <Route path="/admin/tenants" element={<AdminRoute><RootTenantsDashboard /></AdminRoute>} />
          <Route path="/admin/roles" element={<AdminRoute><RoleManagement /></AdminRoute>} />
          <Route path="/admin/logs" element={<AdminRoute><AuditLogDashboard /></AdminRoute>} />
          <Route path="/tasks" element={<TaskList />} />
          <Route path="/projects" element={<ProjectList />} />
          <Route path="/projects/:projectId" element={<ProjectDetail />} />
          
          <Route path="/hr" element={<Navigate to="/hr/employees" replace />} />
          <Route path="/hr/employees" element={<EmployeeList />} />
          <Route path="/hr/departments" element={<DepartmentList />} />
          <Route path="/hr/leaves" element={<LeaveRequestList />} />
          <Route path="/hr/payrolls" element={<PayrollList />} />
          
          <Route path="/calendar" element={<CalendarDashboard />} />
          <Route path="/announcements" element={<AnnouncementDashboard />} />
          <Route path="/helpdesk" element={<HelpdeskDashboard />} />
          
          <Route path="/messages" element={<ChatLayout />} />
          <Route path="/files" element={<FileList />} />

          
          <Route path="/settings" element={<SettingsLayout />}>
            <Route path="profile" element={<ProfileSettings />} />
            <Route path="tenant" element={<TenantSettings />} />
            <Route path="security" element={<SecuritySettings />} />
          </Route>
        </Route>

        <Route path="/" element={<Navigate to="/dashboard" replace />} />
      </Routes>
    </Router>
  );
}
