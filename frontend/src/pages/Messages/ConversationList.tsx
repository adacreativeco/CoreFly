import React from 'react';
import { Conversation } from '@/types/messaging';
import clsx from 'clsx';
import { MessageSquare, Users, Trash2 } from 'lucide-react';

interface ConversationListProps {
  conversations: Conversation[];
  activeId: string | null;
  onSelect: (id: string) => void;
  onDelete?: (id: string) => void;
}

export const ConversationList: React.FC<ConversationListProps> = ({
  conversations,
  activeId,
  onSelect,
  onDelete,
}) => {
  return (
    <div className="flex flex-col h-full bg-white border-r border-gray-200 w-80">
      <div className="p-4 border-b border-gray-200">
        <h2 className="text-lg font-semibold text-gray-900">Mesajlar</h2>
      </div>
      <div className="flex-1 overflow-y-auto">
        {conversations.length === 0 ? (
          <div className="p-4 text-center text-gray-500 text-sm">Henüz sohbet yok</div>
        ) : (
          <ul className="divide-y divide-gray-100">
            {conversations.map((conv) => (
              <li
                key={conv.id}
                onClick={() => onSelect(conv.id)}
                className={clsx(
                  'group px-4 py-4 cursor-pointer hover:bg-gray-50 transition-colors relative flex items-center justify-between',
                  activeId === conv.id ? 'bg-indigo-50 hover:bg-indigo-50' : ''
                )}
              >
                <div className="flex items-center flex-1 min-w-0 pr-2">
                  <div className="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                    {conv.type === 'group' ? (
                      <Users className="h-5 w-5 text-gray-500" />
                    ) : (
                      <MessageSquare className="h-5 w-5 text-gray-500" />
                    )}
                  </div>
                  <div className="ml-3 flex-1 overflow-hidden">
                    <div className="flex justify-between items-baseline">
                      <p className="text-sm font-medium text-gray-900 truncate">
                        {conv.title || `Sohbet #${conv.id}`}
                      </p>
                      <span className="text-[11px] text-gray-400 ml-1 flex-shrink-0">
                        {new Date(conv.last_message_at).toLocaleDateString()}
                      </span>
                    </div>
                    <p className="text-xs text-gray-500 truncate">
                      {conv.description || (conv.type === 'direct' ? 'Özel Mesaj' : 'Grup Sohbeti')}
                    </p>
                  </div>
                </div>

                {onDelete && (
                  <button
                    type="button"
                    onClick={(e) => {
                      e.stopPropagation();
                      if (window.confirm('Bu sohbeti silmek istediğinizden emin misiniz?')) {
                        onDelete(conv.id);
                      }
                    }}
                    className="opacity-0 group-hover:opacity-100 p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-all"
                    title="Sohbeti Sil"
                  >
                    <Trash2 className="h-4 w-4" />
                  </button>
                )}
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
};
