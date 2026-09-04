import React, { useEffect, useState, useCallback } from 'react';
import { useAuthStore } from '@/store/authStore';
import { getConversations, createConversation, deleteConversation } from '@/services/messageService';
import {
  startCall,
  answerCall,
  endCall,
  pollIncomingCall,
  getCallHistory,
} from '@/services/callService';
import { Conversation } from '@/types/messaging';
import { CallRecord, IncomingCallData } from '@/types/call';
import { ConversationList } from './ConversationList';
import { ChatWindow } from './ChatWindow';
import { CallModal } from '@/components/CallModal';
import { Plus, PhoneCall, Video, Clock, X } from 'lucide-react';

interface ActiveCallState {
  callId: string;
  peerName: string;
  peerAvatar?: string;
  callType: 'audio' | 'video';
  status: 'calling' | 'ringing' | 'connected' | 'incoming';
}

export const ChatLayout: React.FC = () => {
  const { user } = useAuthStore();
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [activeId, setActiveId] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [newConvTitle, setNewConvTitle] = useState('');

  // VoIP & Video Calling States
  const [activeCall, setActiveCall] = useState<ActiveCallState | null>(null);
  const [incomingCall, setIncomingCall] = useState<IncomingCallData | null>(null);
  const [showHistoryModal, setShowHistoryModal] = useState(false);
  const [callHistory, setCallHistory] = useState<CallRecord[]>([]);

  const loadConversations = useCallback(async () => {
    try {
      if (!user?.tenant_id) return;
      const data = await getConversations(user.tenant_id);
      setConversations(data);
    } catch (error) {
      console.error('Failed to load conversations:', error);
    } finally {
      setLoading(false);
    }
  }, [user?.tenant_id]);

  useEffect(() => {
    if (user?.tenant_id) {
      loadConversations();
    }
  }, [user?.tenant_id, loadConversations]);

  // Background Poller for Incoming Calls
  useEffect(() => {
    if (!user?.tenant_id) return;

    const checkIncoming = async () => {
      try {
        const incoming = await pollIncomingCall(user.tenant_id);
        if (incoming && (!activeCall || activeCall.callId !== incoming.id)) {
          setIncomingCall(incoming);
          setActiveCall({
            callId: incoming.id,
            peerName: incoming.full_name || incoming.email || 'Personel',
            peerAvatar: incoming.avatar_url,
            callType: incoming.call_type || 'audio',
            status: 'incoming',
          });
        }
      } catch {
        // silent
      }
    };

    const interval = setInterval(checkIncoming, 4000);
    return () => clearInterval(interval);
  }, [user?.tenant_id, activeCall]);

  const handleStartCall = async (conversation: Conversation, type: 'audio' | 'video') => {
    if (!user?.tenant_id) return;

    // Pick target: if direct, other user; otherwise conversation creator or default target
    const targetId = conversation.created_by && conversation.created_by !== user.id
      ? conversation.created_by
      : 'user-finance';

    try {
      const res = await startCall(user.tenant_id, targetId, type);
      setActiveCall({
        callId: res.call_id,
        peerName: res.callee_name || conversation.title || 'Personel',
        callType: type,
        status: 'calling',
      });

      // Simulate peer connecting in demo/test environment
      setTimeout(() => {
        setActiveCall((prev) => (prev ? { ...prev, status: 'connected' } : null));
      }, 2500);
    } catch (err: any) {
      console.error('Call failed to start:', err);
      alert('Arama başlatılamadı: ' + (err.response?.data?.error || 'Hedef kullanıcıya ulaşılamıyor.'));
    }
  };

  const handleAnswerCall = async () => {
    if (!activeCall || !user?.tenant_id) return;
    try {
      await answerCall(user.tenant_id, activeCall.callId);
      setActiveCall((prev) => (prev ? { ...prev, status: 'connected' } : null));
      setIncomingCall(null);
    } catch (err) {
      console.error(err);
    }
  };

  const handleEndCall = async () => {
    if (!activeCall || !user?.tenant_id) return;
    try {
      await endCall(user.tenant_id, activeCall.callId, 'ended');
    } catch (err) {
      console.error(err);
    } finally {
      setActiveCall(null);
      setIncomingCall(null);
    }
  };

  const handleRejectCall = async () => {
    if (!activeCall || !user?.tenant_id) return;
    try {
      await endCall(user.tenant_id, activeCall.callId, 'rejected');
    } catch (err) {
      console.error(err);
    } finally {
      setActiveCall(null);
      setIncomingCall(null);
    }
  };

  const handleOpenHistory = async () => {
    if (!user?.tenant_id) return;
    setShowHistoryModal(true);
    try {
      const history = await getCallHistory(user.tenant_id);
      setCallHistory(history);
    } catch (err) {
      console.error('Failed to load call history:', err);
    }
  };

  const handleCreateConversation = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!user?.tenant_id || !newConvTitle.trim()) return;

    try {
      await createConversation(user.tenant_id, {
        title: newConvTitle,
        type: 'group',
        description: 'New Group Chat',
        participant_ids: [user.id],
      });
      setShowModal(false);
      setNewConvTitle('');
      loadConversations();
    } catch (error) {
      console.error('Failed to create conversation:', error);
    }
  };

  const handleDeleteConversation = async (id: string) => {
    if (!user?.tenant_id) return;
    try {
      await deleteConversation(user.tenant_id, id);
      if (activeId === id) {
        setActiveId(null);
      }
      loadConversations();
    } catch (error) {
      console.error('Failed to delete conversation:', error);
      alert('Sohbet silinirken bir hata oluştu.');
    }
  };

  if (loading) return <div className="p-8 text-center text-gray-500">Mesajlar yükleniyor...</div>;

  const activeConversation = conversations.find((c) => c.id === activeId);

  return (
    <div className="flex h-full border border-gray-200 rounded-lg overflow-hidden bg-white shadow-sm relative">
      {/* Sidebar */}
      <div className="flex flex-col border-r border-gray-200 w-80">
        <div className="p-2.5 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
          <span className="text-xs font-semibold text-gray-500 uppercase tracking-wider px-2">Sohbetler</span>
          <div className="flex items-center gap-1">
            <button
              onClick={handleOpenHistory}
              className="p-1.5 rounded-md hover:bg-gray-200 text-gray-600 transition"
              title="Çağrı Geçmişi"
            >
              <PhoneCall className="h-4 w-4 text-emerald-600" />
            </button>
            <button
              onClick={() => setShowModal(true)}
              className="p-1.5 rounded-md hover:bg-gray-200 text-gray-600 transition"
              title="Yeni Sohbet"
            >
              <Plus className="h-4 w-4" />
            </button>
          </div>
        </div>
        <ConversationList
          conversations={conversations}
          activeId={activeId}
          onSelect={setActiveId}
          onDelete={handleDeleteConversation}
        />
      </div>

      {/* Main Chat Area */}
      <div className="flex-1 flex flex-col">
        {activeConversation ? (
          <ChatWindow
            conversation={activeConversation}
            onDelete={handleDeleteConversation}
            onStartCall={handleStartCall}
          />
        ) : (
          <div className="flex-1 flex items-center justify-center bg-gray-50 text-gray-400">
            <div className="text-center">
              <p>Mesajlaşmaya veya aramaya başlamak için bir sohbet seçin</p>
            </div>
          </div>
        )}
      </div>

      {/* Active VoIP / Video Call Modal */}
      {activeCall && (
        <CallModal
          isOpen={true}
          callType={activeCall.callType}
          callStatus={activeCall.status}
          peerName={activeCall.peerName}
          peerAvatar={activeCall.peerAvatar}
          incomingCall={incomingCall}
          onAnswer={handleAnswerCall}
          onReject={handleRejectCall}
          onEndCall={handleEndCall}
        />
      )}

      {/* Call History Modal */}
      {showHistoryModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div className="bg-white rounded-2xl p-6 w-full max-w-lg shadow-2xl border border-gray-100 max-h-[85vh] flex flex-col">
            <div className="flex items-center justify-between pb-4 border-b border-gray-200">
              <div className="flex items-center gap-2">
                <PhoneCall className="h-5 w-5 text-emerald-600" />
                <h3 className="text-lg font-bold text-gray-900">Çağrı Geçmişi</h3>
              </div>
              <button
                onClick={() => setShowHistoryModal(false)}
                className="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            <div className="flex-1 overflow-y-auto py-4 space-y-3">
              {callHistory.length === 0 ? (
                <div className="text-center py-8 text-gray-400 text-sm">
                  Henüz kayıtlı bir arama geçmişi bulunmuyor.
                </div>
              ) : (
                callHistory.map((call) => (
                  <div
                    key={call.id}
                    className="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:bg-gray-50 transition"
                  >
                    <div className="flex items-center gap-3">
                      <div
                        className={`w-10 h-10 rounded-xl flex items-center justify-center ${
                          call.call_type === 'video' ? 'bg-blue-50 text-blue-600' : 'bg-emerald-50 text-emerald-600'
                        }`}
                      >
                        {call.call_type === 'video' ? <Video className="h-5 w-5" /> : <PhoneCall className="h-5 w-5" />}
                      </div>
                      <div>
                        <div className="text-sm font-semibold text-gray-900">
                          {call.caller_name || call.callee_name || 'Personel'}
                        </div>
                        <div className="text-xs text-gray-500 flex items-center gap-1">
                          <span>{new Date(call.created_at).toLocaleString('tr-TR')}</span>
                          <span>•</span>
                          <span className="capitalize">{call.status}</span>
                        </div>
                      </div>
                    </div>

                    <div className="flex items-center gap-1.5 text-xs text-gray-500 font-mono">
                      <Clock className="h-3.5 w-3.5" />
                      <span>{Math.floor(call.duration_seconds / 60)}m {call.duration_seconds % 60}s</span>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>
      )}

      {/* New Group Chat Modal */}
      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
          <div className="bg-white rounded-lg p-6 w-full max-w-md">
            <h2 className="text-xl font-bold mb-4">Yeni Grup Sohbeti</h2>
            <form onSubmit={handleCreateConversation}>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700">Başlık</label>
                  <input
                    type="text"
                    required
                    className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    value={newConvTitle}
                    onChange={(e) => setNewConvTitle(e.target.value)}
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
