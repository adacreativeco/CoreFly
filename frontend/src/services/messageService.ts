import client from '@/api/client';
import { Conversation, Message, CreateConversationData, CreateMessageData } from '@/types/messaging';

export const getConversations = async (tenantId: string): Promise<Conversation[]> => {
  const response = await client.get<{ data: Conversation[] }>(`/conversations/${tenantId}`);
  return response.data.data;
};

export const createConversation = async (tenantId: string, data: CreateConversationData): Promise<Conversation> => {
  const response = await client.post<{ data: Conversation }>(`/conversations/${tenantId}`, data);
  return response.data.data;
};

export const getMessages = async (tenantId: string, conversationId: string): Promise<Message[]> => {
  const response = await client.get<{ data: Message[] }>(`/conversations/${tenantId}/${conversationId}/messages`);
  return response.data.data;
};

export const sendMessage = async (tenantId: string, conversationId: string, data: CreateMessageData): Promise<Message> => {
  const response = await client.post<{ data: Message }>(`/conversations/${tenantId}/${conversationId}/messages`, data);
  return response.data.data;
};

export const deleteConversation = async (tenantId: string, conversationId: string): Promise<void> => {
  await client.delete(`/conversations/${tenantId}/${conversationId}`);
};

