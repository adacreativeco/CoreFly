import React from 'react';
import { Task } from '@/types/project';
import clsx from 'clsx';
import { ArrowRight, CheckCircle2, Clock } from 'lucide-react';

interface TaskBoardProps {
  tasks: Task[];
  onTaskClick?: (task: Task) => void;
  onStatusChange?: (taskId: string, newStatus: Task['status']) => void;
}

const columns: { id: Task['status']; title: string; color: string; border: string }[] = [
  { id: 'todo', title: 'Yapılacak', color: 'bg-gray-50', border: 'border-gray-200' },
  { id: 'in_progress', title: 'Devam Ediyor', color: 'bg-blue-50/60', border: 'border-blue-200' },
  { id: 'review', title: 'İnceleme', color: 'bg-amber-50/60', border: 'border-amber-200' },
  { id: 'done', title: 'Tamamlandı', color: 'bg-emerald-50/60', border: 'border-emerald-200' },
];

export const TaskBoard: React.FC<TaskBoardProps> = ({ tasks, onTaskClick, onStatusChange }) => {
  const getTasksByStatus = (status: string) => {
    return tasks.filter((task) => task.status === status);
  };

  const nextStatusMap: Record<string, Task['status']> = {
    todo: 'in_progress',
    in_progress: 'review',
    review: 'done',
  };

  const nextStatusLabels: Record<string, string> = {
    todo: 'Başlat ➔',
    in_progress: 'İncelemeye Al ➔',
    review: 'Tamamla ✓',
  };

  return (
    <div className="flex h-full space-x-4 overflow-x-auto pb-4">
      {columns.map((column) => {
        const colTasks = getTasksByStatus(column.id);

        return (
          <div
            key={column.id}
            className={clsx(
              'flex-shrink-0 w-80 rounded-xl flex flex-col border shadow-sm',
              column.color,
              column.border
            )}
          >
            <div className="p-4 font-bold text-gray-800 flex justify-between items-center border-b border-gray-200/50">
              <span className="text-sm">{column.title}</span>
              <span className="bg-white/80 px-2 py-0.5 rounded-full text-xs font-semibold text-gray-600 shadow-sm">
                {colTasks.length}
              </span>
            </div>

            <div className="flex-1 p-3 space-y-3 overflow-y-auto min-h-[350px]">
              {colTasks.length === 0 ? (
                <div className="h-32 flex items-center justify-center text-xs text-gray-400 border border-dashed border-gray-300/60 rounded-lg">
                  Görev bulunmuyor
                </div>
              ) : (
                colTasks.map((task) => (
                  <div
                    key={task.id}
                    className="bg-white p-4 rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition space-y-2.5"
                  >
                    <div className="flex justify-between items-start">
                      <span
                        className={clsx(
                          'px-2 py-0.5 text-[10px] rounded-full font-bold uppercase tracking-wider',
                          {
                            'bg-red-100 text-red-700': task.priority === 'critical',
                            'bg-amber-100 text-amber-800': task.priority === 'high',
                            'bg-blue-100 text-blue-700': task.priority === 'medium',
                            'bg-gray-100 text-gray-700': task.priority === 'low',
                          }
                        )}
                      >
                        {task.priority || 'Normal'}
                      </span>

                      {/* Hızlı Durum Değiştirme Seçicisi */}
                      <select
                        value={task.status}
                        onChange={(e) => onStatusChange?.(task.id, e.target.value as Task['status'])}
                        onClick={(e) => e.stopPropagation()}
                        className="text-[11px] font-medium border border-gray-200 rounded px-1.5 py-0.5 bg-gray-50 focus:ring-1 focus:ring-indigo-500"
                      >
                        <option value="todo">Yapılacak</option>
                        <option value="in_progress">Devam Ediyor</option>
                        <option value="review">İnceleme</option>
                        <option value="done">Tamamlandı</option>
                      </select>
                    </div>

                    <h4 
                      onClick={() => onTaskClick?.(task)}
                      className="text-sm font-semibold text-gray-900 cursor-pointer hover:text-indigo-600 transition"
                    >
                      {task.title}
                    </h4>

                    {task.description && (
                      <p className="text-xs text-gray-500 line-clamp-2">{task.description}</p>
                    )}

                    <div className="pt-2 border-t border-gray-100 flex items-center justify-between text-xs text-gray-400">
                      <span className="flex items-center text-[11px]">
                        <Clock className="w-3 h-3 mr-1" />
                        {task.due_date || new Date(task.created_at).toLocaleDateString()}
                      </span>

                      {/* Aşama İlerletme Aksiyonu */}
                      {nextStatusMap[task.status] && (
                        <button
                          onClick={() => onStatusChange?.(task.id, nextStatusMap[task.status])}
                          className="text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2 py-1 rounded-md transition flex items-center"
                        >
                          {nextStatusLabels[task.status]}
                        </button>
                      )}

                      {task.status === 'done' && (
                        <span className="flex items-center text-xs font-semibold text-emerald-600">
                          <CheckCircle2 className="w-3.5 h-3.5 mr-1" /> Bitti
                        </span>
                      )}
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        );
      })}
    </div>
  );
};
