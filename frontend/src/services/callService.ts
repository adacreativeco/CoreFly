import client from '@/api/client';
import { CallRecord, IncomingCallData, WebRTCSignal } from '@/types/call';

export const startCall = async (
  tenantId: string,
  targetId: string,
  callType: 'audio' | 'video' = 'audio'
): Promise<{
  call_id: string;
  caller_id: string;
  callee_id: string;
  callee_name: string;
  call_type: 'audio' | 'video';
  status: string;
}> => {
  const res = await client.post<{ data: any }>(`/calls/${tenantId}/start`, {
    target_id: targetId,
    call_type: callType,
  });
  return res.data.data;
};

export const sendCallSignal = async (
  tenantId: string,
  callId: string,
  type: string,
  payload: any
): Promise<{ status: string }> => {
  const res = await client.post<{ status: string }>(`/calls/${tenantId}/signal`, {
    call_id: callId,
    type,
    payload,
  });
  return res.data;
};

export const pollCallSignals = async (
  tenantId: string,
  callId: string
): Promise<WebRTCSignal[]> => {
  const res = await client.get<{ signals: WebRTCSignal[] }>(
    `/calls/${tenantId}/signals?call_id=${callId}`
  );
  return res.data.signals || [];
};

export const pollIncomingCall = async (
  tenantId: string
): Promise<IncomingCallData | null> => {
  const res = await client.get<{ incoming_call: IncomingCallData | null }>(
    `/calls/${tenantId}/incoming`
  );
  return res.data.incoming_call;
};

export const answerCall = async (
  tenantId: string,
  callId: string
): Promise<{ status: string }> => {
  const res = await client.post<{ status: string }>(`/calls/${tenantId}/answer`, {
    call_id: callId,
  });
  return res.data;
};

export const endCall = async (
  tenantId: string,
  callId: string,
  reason: 'ended' | 'rejected' | 'missed' = 'ended'
): Promise<{ status: string; duration_seconds: number }> => {
  const res = await client.post<{ status: string; duration_seconds: number }>(
    `/calls/${tenantId}/end`,
    {
      call_id: callId,
      reason,
    }
  );
  return res.data;
};

export const getCallHistory = async (tenantId: string): Promise<CallRecord[]> => {
  const res = await client.get<{ data: CallRecord[] }>(`/calls/${tenantId}/history`);
  return res.data.data || [];
};
