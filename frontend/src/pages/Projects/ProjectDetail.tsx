import React, { useEffect, useState, useCallback } from 'react';
import { useParams } from 'react-router-dom';
import { useAuthStore } from '@/store/authStore';
import { getProject, getProjectTasks, createProjectTask, updateProjectTask } from '@/services/projectService';
import { Project, Task } from '@/types/project';
import { TaskBoard } from '@/components/projects/TaskBoard';
import { Plus } from 'lucide-react';

export const ProjectDetail: React.FC = () => {
  const { projectId } = useParams<{ projectId: string }>();
  const { user } = useAuthStore();
  const [project, setProject] = useState<Project | null>(null);
  const [tasks, setTasks] = useState<Task[]>([]);
  const [loading, setLoading] = useState(true);
  const [showTaskModal, setShowTaskModal] = useState(false);
  const [newTaskTitle, setNewTaskTitle] = useState('');
  const [newTaskStatus, setNewTaskStatus] = useState('todo');

  const loadData = useCallback(async () => {
    try {
      if (!user?.tenant_id || !projectId) return;
      const [projectData, tasksData] = await Promise.all([
        getProject(user.tenant_id, projectId),
        getProjectTasks(user.tenant_id, projectId),
      ]);
      setProject(projectData);
      setTasks(tasksData);
    } catch (error) {
      console.error('Failed to load project details:', error);
    } finally {
      setLoading(false);
    }
  }, [user?.tenant_id, projectId]);

  useEffect(() => {
    if (user?.tenant_id && projectId) {
      loadData();
    }
  }, [user?.tenant_id, projectId, loadData]);

  const handleCreateTask = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!user?.tenant_id || !projectId || !newTaskTitle.trim()) return;

    try {
      await createProjectTask(user.tenant_id, projectId, {
        title: newTaskTitle,
        status: newTaskStatus as Task['status'],
        priority: 'medium',
      });
      setShowTaskModal(false);
      setNewTaskTitle('');
      loadData(); // Reload tasks
    } catch (error) {
      console.error('Failed to create task:', error);
    }
  };

  const handleStatusChange = async (taskId: string, newStatus: Task['status']) => {
    if (!user?.tenant_id || !projectId) return;
    try {
      setTasks(prev => prev.map(t => t.id === taskId ? { ...t, status: newStatus } : t));
      await updateProjectTask(user.tenant_id, projectId, taskId, { status: newStatus });
    } catch (err) {
      console.error('Görev durumu güncellenemedi:', err);
      loadData();
    }
  };

  if (loading) return <div className="p-8 text-center">Proje detayları yükleniyor...</div>;
  if (!project) return <div className="p-8 text-center">Proje bulunamadı</div>;

  return (
    <div className="flex flex-col h-full">
      <div className="flex justify-between items-start mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{project.name}</h1>
          <p className="text-gray-500 mt-1">{project.description}</p>
        </div>
        <button
          onClick={() => setShowTaskModal(true)}
          className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
        >
          <Plus className="h-5 w-5 mr-2" />
          Görev Ekle
        </button>
      </div>

      <div className="flex-1 overflow-hidden">
        <TaskBoard tasks={tasks} onStatusChange={handleStatusChange} />
      </div>

      {showTaskModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
          <div className="bg-white rounded-lg p-6 w-full max-w-md">
            <h2 className="text-xl font-bold mb-4">Yeni Görev Ekle</h2>
            <form onSubmit={handleCreateTask}>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700">Görev Başlığı</label>
                  <input
                    type="text"
                    required
                    className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    value={newTaskTitle}
                    onChange={(e) => setNewTaskTitle(e.target.value)}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700">Durum</label>
                  <select
                    className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    value={newTaskStatus}
                    onChange={(e) => setNewTaskStatus(e.target.value)}
                  >
                    <option value="todo">Yapılacak</option>
                    <option value="in_progress">Devam Ediyor</option>
                    <option value="review">İnceleme</option>
                    <option value="done">Tamamlandı</option>
                  </select>
                </div>
                <div className="flex justify-end space-x-3 mt-6">
                  <button
                    type="button"
                    onClick={() => setShowTaskModal(false)}
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
