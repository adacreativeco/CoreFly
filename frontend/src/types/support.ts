export type TicketPriority = 'low' | 'medium' | 'high' | 'urgent';
export type TicketStatus = 'open' | 'in_progress' | 'resolved' | 'closed';

export interface HelpdeskMessage {
  id: string;
  tenant_id: string;
  ticket_id: string;
  user_id: string;
  user_name?: string;
  message: string;
  created_at: string;
}

export interface HelpdeskTicket {
  id: string;
  tenant_id: string;
  title: string;
  description: string;
  priority: TicketPriority;
  status: TicketStatus;
  category: string;
  assigned_to?: string;
  created_by: string;
  messages?: HelpdeskMessage[];
  created_at: string;
  updated_at: string;
}

export interface HelpdeskStats {
  total_tickets: number;
  open_tickets: number;
  in_progress_tickets: number;
  resolved_tickets: number;
}

export type AnnouncementPriority = 'low' | 'normal' | 'high' | 'urgent';

export interface Announcement {
  id: string;
  tenant_id: string;
  title: string;
  content: string;
  priority: AnnouncementPriority;
  is_pinned: boolean;
  author_name?: string;
  created_by: string;
  created_at: string;
}

export type CalendarEventType = 'meeting' | 'deadline' | 'holiday' | 'event';

export interface CalendarEvent {
  id: string;
  tenant_id: string;
  title: string;
  description?: string;
  event_type: CalendarEventType;
  start_date: string;
  end_date?: string;
  location?: string;
  created_by: string;
  created_at: string;
}
