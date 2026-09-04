import { User } from './auth';

export interface Conversation {
  id: string;
  tenant_id: string;
  title: string | null;
  type: 'direct' | 'group' | 'channel';
  description: string | null;
  created_by: string;
  last_message_at: string;
  created_at: string;
  updated_at: string;
}

export interface Message {
  id: string;
  tenant_id: string;
  conversation_id: string;
  sender_id: string;
  content: string;
  type: 'text' | 'image' | 'file' | 'system';
  attachments: string | null;
  is_read: boolean;
  created_at: string;
  updated_at: string;
  sender?: User; // Optional, typically joined on backend or resolved on frontend
}

export interface CreateConversationData {
  type: 'direct' | 'group' | 'channel';
  title?: string;
  recipient_id?: string; // For direct
  participant_ids?: string[]; // For group
  description?: string;
}

export interface CreateMessageData {
  content: string;
  type?: string;
  attachments?: unknown;
}
