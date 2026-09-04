import React, { useEffect, useState, useRef, useCallback } from 'react';
import { useAuthStore } from '@/store/authStore';
import { getMessages, sendMessage } from '@/services/messageService';
import { Conversation, Message } from '@/types/messaging';
import { Send, Trash2, PhoneCall, Video } from 'lucide-react';
import clsx from 'clsx';

interface ChatWindowProps {
  conversation: Conversation;
  onDelete?: (id: string) => void;
  onStartCall?: (conversation: Conversation, type: 'audio' | 'video') => void;
}

export const ChatWindow: React.FC<ChatWindowProps> = ({ conversation, onDelete, onStartCall }) => {
  const { user } = useAuthStore();
  const [messages, setMessages] = useState<Message[]>([]);
  const [newMessage, setNewMessage] = useState('');
  const [sending, setSending] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const pollingRef = useRef<NodeJS.Timeout | null>(null);

  const loadMessages = useCallback(async () => {
    try {
      if (!user?.tenant_id) return;
      const data = await getMessages(user.tenant_id, conversation.id);
      setMessages(data);
    } catch (error) {
      console.error('Failed to load messages:', error);
    }
  }, [user?.tenant_id, conversation.id]);

  useEffect(() => {
    loadMessages();
    // Poll every 5 seconds
    pollingRef.current = setInterval(loadMessages, 5000);

    return () => {
      if (pollingRef.current) clearInterval(pollingRef.current);
    };
  }, [loadMessages]);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  const handleSend = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!user?.tenant_id || !newMessage.trim()) return;

    setSending(true);
    try {
      await sendMessage(user.tenant_id, conversation.id, {
        content: newMessage,
        type: 'text',
      });
      setNewMessage('');
      loadMessages(); // Refresh immediately
    } catch (error) {
      console.error('Failed to send message:', error);
    } finally {
      setSending(false);
    }
  };

  return (
    <div className="flex flex-col h-full bg-gray-50 flex-1">
      {/* Header */}
      <div className="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center shadow-sm">
        <div>
          <h2 className="text-lg font-bold text-gray-900">
            {conversation.title || `Sohbet #${conversation.id}`}
          </h2>
          <p className="text-sm text-gray-500 capitalize">{conversation.type === 'group' ? 'Grup Sohbeti' : 'Özel Mesaj'}</p>
        </div>
        <div className="flex items-center gap-2">
          {onStartCall && (
            <>
              <button
                onClick={() => onStartCall(conversation, 'audio')}
                className="flex items-center gap-1.5 px-3 py-1.5 text-xs text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition font-medium"
                title="Sesli Arama Başlat"
              >
                <PhoneCall className="h-3.5 w-3.5" />
                <span>Sesli Ara</span>
              </button>
              <button
                onClick={() => onStartCall(conversation, 'video')}
                className="flex items-center gap-1.5 px-3 py-1.5 text-xs text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg border border-blue-200 transition font-medium"
                title="Görüntülü Arama Başlat"
              >
                <Video className="h-3.5 w-3.5" />
                <span>Görüntülü Ara</span>
              </button>
            </>
          )}
          {onDelete && (
            <button
              onClick={() => {
                if (window.confirm('Bu sohbeti silmek istediğinizden emin misiniz?')) {
                  onDelete(conversation.id);
                }
              }}
              className="flex items-center gap-1.5 px-3 py-1.5 text-xs text-red-600 hover:bg-red-50 rounded-md border border-red-200 transition"
              title="Sohbeti Sil"
            >
              <Trash2 className="h-4 w-4" />
              <span>Sohbeti Sil</span>
            </button>
          )}
        </div>
      </div>

      {/* Messages */}
      <div className="flex-1 overflow-y-auto p-6 space-y-4">
        {messages.map((msg) => {
          const isOwn = msg.sender_id === user?.id;
          return (
            <div
              key={msg.id}
              className={clsx('flex', isOwn ? 'justify-end' : 'justify-start')}
            >
              <div
                className={clsx(
                  'max-w-[70%] rounded-lg px-4 py-2 shadow-sm',
                  isOwn ? 'bg-indigo-600 text-white' : 'bg-white text-gray-900'
                )}
              >
                {!isOwn && (
                  <p className="text-xs font-medium text-gray-500 mb-1">Kullanıcı {msg.sender_id}</p>
                )}
                <p className="text-sm">{msg.content}</p>
                <p
                  className={clsx(
                    'text-[10px] mt-1 text-right',
                    isOwn ? 'text-indigo-200' : 'text-gray-400'
                  )}
                >
                  {new Date(msg.created_at).toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit',
                  })}
                </p>
              </div>
            </div>
          );
        })}
        <div ref={messagesEndRef} />
      </div>

      {/* Input */}
      <div className="bg-white border-t border-gray-200 p-4">
        <form onSubmit={handleSend} className="flex space-x-3">
          <input
            type="text"
            className="flex-1 border border-gray-300 rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            placeholder="Bir mesaj yazın..."
            value={newMessage}
            onChange={(e) => setNewMessage(e.target.value)}
          />
          <button
            type="submit"
            disabled={sending || !newMessage.trim()}
            className="bg-indigo-600 text-white rounded-full p-2 hover:bg-indigo-700 disabled:opacity-50 transition-colors"
          >
            <Send className="h-5 w-5" />
          </button>
        </form>
      </div>
    </div>
  );
};
