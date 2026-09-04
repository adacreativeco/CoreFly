import client from '@/api/client';
import {
  HelpdeskTicket,
  HelpdeskStats,
  TicketStatus,
  Announcement,
  CalendarEvent,
} from '@/types/support';

// --- HELPDESK ---

export const getHelpdeskStats = async (tenantId: string): Promise<HelpdeskStats> => {
  const response = await client.get<{ data: HelpdeskStats }>(`/helpdesk/${tenantId}/stats`);
  return response.data.data;
};

export const getTickets = async (
  tenantId: string,
  params?: { search?: string; status?: string; priority?: string }
): Promise<HelpdeskTicket[]> => {
  const query = new URLSearchParams();
  if (params?.search) query.append('search', params.search);
  if (params?.status) query.append('status', params.status);
  if (params?.priority) query.append('priority', params.priority);

  const response = await client.get<{ data: HelpdeskTicket[] }>(
    `/helpdesk/${tenantId}/tickets?${query.toString()}`
  );
  return response.data.data;
};

export const getTicketDetails = async (tenantId: string, ticketId: string): Promise<HelpdeskTicket> => {
  const response = await client.get<{ data: HelpdeskTicket }>(`/helpdesk/${tenantId}/tickets/${ticketId}`);
  return response.data.data;
};

export const createTicket = async (
  tenantId: string,
  data: Partial<HelpdeskTicket>
): Promise<HelpdeskTicket> => {
  const response = await client.post<{ data: HelpdeskTicket }>(`/helpdesk/${tenantId}/tickets`, data);
  return response.data.data;
};

export const addTicketMessage = async (
  tenantId: string,
  ticketId: string,
  message: string,
  userName?: string
): Promise<void> => {
  await client.post(`/helpdesk/${tenantId}/tickets/${ticketId}/messages`, { message, user_name: userName });
};

export const updateTicketStatus = async (
  tenantId: string,
  ticketId: string,
  status: TicketStatus
): Promise<void> => {
  await client.patch(`/helpdesk/${tenantId}/tickets/${ticketId}/status`, { status });
};

// --- ANNOUNCEMENTS ---

export const getAnnouncements = async (tenantId: string): Promise<Announcement[]> => {
  const response = await client.get<{ data: Announcement[] }>(`/announcements/${tenantId}`);
  return response.data.data;
};

export const createAnnouncement = async (
  tenantId: string,
  data: Partial<Announcement>
): Promise<Announcement> => {
  const response = await client.post<{ data: Announcement }>(`/announcements/${tenantId}`, data);
  return response.data.data;
};

export const deleteAnnouncement = async (tenantId: string, announcementId: string): Promise<void> => {
  await client.delete(`/announcements/${tenantId}/${announcementId}`);
};

// --- CALENDAR ---

export const getCalendarEvents = async (tenantId: string): Promise<CalendarEvent[]> => {
  const response = await client.get<{ data: CalendarEvent[] }>(`/calendar/${tenantId}/events`);
  return response.data.data;
};

export const createCalendarEvent = async (
  tenantId: string,
  data: Partial<CalendarEvent>
): Promise<CalendarEvent> => {
  const response = await client.post<{ data: CalendarEvent }>(`/calendar/${tenantId}/events`, data);
  return response.data.data;
};

export const deleteCalendarEvent = async (tenantId: string, eventId: string): Promise<void> => {
  await client.delete(`/calendar/${tenantId}/events/${eventId}`);
};
