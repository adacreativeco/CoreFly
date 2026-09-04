import React from 'react';
import { NavLink, Outlet, Navigate, useLocation } from 'react-router-dom';
import clsx from 'clsx';
import { User, Building, Shield } from 'lucide-react';

const tabs = [
  { name: 'Profil', path: '/settings/profile', icon: User },
  { name: 'Şirket Bilgileri', path: '/settings/tenant', icon: Building },
  { name: 'Güvenlik', path: '/settings/security', icon: Shield },
];

export const SettingsLayout: React.FC = () => {
  const location = useLocation();

  // Redirect to profile by default
  if (location.pathname === '/settings') {
    return <Navigate to="/settings/profile" replace />;
  }

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900">Ayarlar</h1>

      <div className="bg-white rounded-lg shadow">
        <div className="border-b border-gray-200">
          <nav className="-mb-px flex space-x-8 px-6" aria-label="Tabs">
            {tabs.map((tab) => (
              <NavLink
                key={tab.name}
                to={tab.path}
                className={({ isActive }) =>
                  clsx(
                    'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                    isActive
                      ? 'border-indigo-500 text-indigo-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  )
                }
              >
                <tab.icon
                  className={clsx(
                    '-ml-0.5 mr-2 h-5 w-5',
                    location.pathname === tab.path ? 'text-indigo-500' : 'text-gray-400 group-hover:text-gray-500'
                  )}
                />
                {tab.name}
              </NavLink>
            ))}
          </nav>
        </div>
        <div className="p-6">
          <Outlet />
        </div>
      </div>
    </div>
  );
};
