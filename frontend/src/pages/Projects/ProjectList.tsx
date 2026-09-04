import React, { useEffect, useState, useCallback } from 'react';
import { useAuthStore } from '@/store/authStore';
import { getProjects, createProject } from '@/services/projectService';
import { Project } from '@/types/project';
import { Link } from 'react-router-dom';
import { Plus, FolderKanban, Calendar } from 'lucide-react';
import clsx from 'clsx';

export const ProjectList: React.FC = () => {
  const { user } = useAuthStore();
  const [projects, setProjects] = useState<Project[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [newProjectName, setNewProjectName] = useState('');
  const [newProjectDesc, setNewProjectDesc] = useState('');

  const loadProjects = useCallback(async () => {
    try {
      if (!user?.tenant_id) return;
      const data = await getProjects(user.tenant_id);
      setProjects(data);
    } catch (error) {
      console.error('Failed to load projects:', error);
    } finally {
      setLoading(false);
    }
  }, [user?.tenant_id]);

  useEffect(() => {
    if (user?.tenant_id) {
      loadProjects();
    }
  }, [loadProjects, user?.tenant_id]);

  const handleCreateProject = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!user?.tenant_id || !newProjectName.trim()) return;

    try {
      await createProject(user.tenant_id, {
        name: newProjectName,
        description: newProjectDesc,
        status: 'planning',
        priority: 'medium',
      });
      setShowModal(false);
      setNewProjectName('');
      setNewProjectDesc('');
      loadProjects();
    } catch (error) {
      console.error('Failed to create project:', error);
    }
  };

  if (loading) return <div className="p-8 text-center">Projeler yükleniyor...</div>;

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Projeler</h1>
        <button
          onClick={() => setShowModal(true)}
          className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
        >
          <Plus className="h-5 w-5 mr-2" />
          Yeni Proje
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {projects.map((project) => (
          <Link
            key={project.id}
            to={`/projects/${project.id}`}
            className="block bg-white rounded-lg shadow hover:shadow-md transition-shadow duration-200"
          >
            <div className="p-6">
              <div className="flex items-center justify-between mb-4">
                <div className="p-2 bg-indigo-100 rounded-lg">
                  <FolderKanban className="h-6 w-6 text-indigo-600" />
                </div>
                <span
                  className={clsx(
                    'px-2 py-1 text-xs font-medium rounded-full',
                    {
                      'bg-green-100 text-green-800': project.status === 'active',
                      'bg-yellow-100 text-yellow-800': project.status === 'planning',
                      'bg-gray-100 text-gray-800': project.status === 'completed',
                    }
                  )}
                >
                  {project.status.toUpperCase()}
                </span>
              </div>
              <h3 className="text-lg font-medium text-gray-900 mb-2">{project.name}</h3>
              <p className="text-gray-500 text-sm mb-4 line-clamp-2">
                {project.description || 'Açıklama yok'}
              </p>
              <div className="flex items-center text-sm text-gray-500">
                <Calendar className="h-4 w-4 mr-2" />
                <span>Oluşturuldu: {new Date(project.created_at).toLocaleDateString()}</span>
              </div>
            </div>
          </Link>
        ))}
      </div>

      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
          <div className="bg-white rounded-lg p-6 w-full max-w-md">
            <h2 className="text-xl font-bold mb-4">Yeni Proje Oluştur</h2>
            <form onSubmit={handleCreateProject}>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700">Proje Adı</label>
                  <input
                    type="text"
                    required
                    className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    value={newProjectName}
                    onChange={(e) => setNewProjectName(e.target.value)}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700">Açıklama</label>
                  <textarea
                    className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    rows={3}
                    value={newProjectDesc}
                    onChange={(e) => setNewProjectDesc(e.target.value)}
                  />
                </div>
                <div className="flex justify-end space-x-3 mt-6">
                  <button
                    type="button"
                    onClick={() => setShowModal(false)}
                    className="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                  >
                    İptal
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                  >
                    Oluştur
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
