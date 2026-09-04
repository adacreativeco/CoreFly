export interface CallRecord {
  id: string;
  tenant_id: string;
  caller_id: string;
  callee_id: string;
  call_type: 'audio' | 'video';
  status: 'initiated' | 'ringing' | 'answered' | 'ended' | 'missed' | 'rejected';
  started_at: string | null;
  ended_at: string | null;
  duration_seconds: number;
  created_at: string;
  caller_name?: string;
  caller_avatar?: string;
  callee_name?: string;
  callee_avatar?: string;
}

export interface IncomingCallData {
  id: string;
  tenant_id: string;
  caller_id: string;
  callee_id: string;
  call_type: 'audio' | 'video';
  status: string;
  full_name?: string;
  email?: string;
  avatar_url?: string;
}

export interface WebRTCSignal {
  id: string;
  call_id: string;
  sender_id: string;
  type: 'offer' | 'answer' | 'ice-candidate' | 'end';
  payload: any;
  created_at: string;
}
