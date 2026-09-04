import React, { useState, useEffect, useRef } from 'react';
import {
  Phone,
  PhoneOff,
  Video,
  VideoOff,
  Mic,
  MicOff,
  Volume2,
  VolumeX,
  User as UserIcon,
} from 'lucide-react';
import { IncomingCallData } from '@/types/call';

interface CallModalProps {
  isOpen: boolean;
  callType: 'audio' | 'video';
  callStatus: 'calling' | 'ringing' | 'connected' | 'incoming';
  peerName: string;
  peerAvatar?: string;
  incomingCall?: IncomingCallData | null;
  onAnswer?: () => void;
  onReject?: () => void;
  onEndCall: () => void;
}

export const CallModal: React.FC<CallModalProps> = ({
  isOpen,
  callType,
  callStatus,
  peerName,
  peerAvatar,
  incomingCall,
  onAnswer,
  onReject,
  onEndCall,
}) => {
  const [isMuted, setIsMuted] = useState(false);
  const [isVideoEnabled, setIsVideoEnabled] = useState(callType === 'video');
  const [isSpeakerOn, setIsSpeakerOn] = useState(true);
  const [duration, setDuration] = useState(0);
  const localVideoRef = useRef<HTMLVideoElement | null>(null);
  const streamRef = useRef<MediaStream | null>(null);

  // Timer when connected
  useEffect(() => {
    let timer: NodeJS.Timeout | null = null;
    if (callStatus === 'connected') {
      setDuration(0);
      timer = setInterval(() => {
        setDuration((prev) => prev + 1);
      }, 1000);
    }
    return () => {
      if (timer) clearInterval(timer);
    };
  }, [callStatus]);

  // Handle local camera stream if video enabled
  useEffect(() => {
    if (isOpen && isVideoEnabled && callStatus === 'connected') {
      navigator.mediaDevices?.getUserMedia({ video: true, audio: true })
        .then((stream) => {
          streamRef.current = stream;
          if (localVideoRef.current) {
            localVideoRef.current.srcObject = stream;
          }
        })
        .catch((err) => {
          console.warn('Camera/Mic permission not granted or unavailable:', err);
        });
    }

    return () => {
      if (streamRef.current) {
        streamRef.current.getTracks().forEach((track) => track.stop());
        streamRef.current = null;
      }
    };
  }, [isOpen, isVideoEnabled, callStatus]);

  if (!isOpen) return null;

  const formatDuration = (secs: number) => {
    const mins = Math.floor(secs / 60);
    const remaining = secs % 60;
    return `${mins.toString().padStart(2, '0')}:${remaining.toString().padStart(2, '0')}`;
  };

  const displayName = incomingCall
    ? incomingCall.full_name || incomingCall.email || 'Arayan Personel'
    : peerName || 'Personel';

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md p-4 animate-in fade-in duration-300">
      <div className="relative w-full max-w-lg bg-slate-900 border border-slate-700/80 rounded-3xl shadow-2xl overflow-hidden text-white flex flex-col items-center p-8">
        {/* Background glow effects */}
        <div className="absolute -top-24 -left-24 w-60 h-60 bg-blue-500/20 rounded-full blur-3xl pointer-events-none" />
        <div className="absolute -bottom-24 -right-24 w-60 h-60 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none" />

        {/* Call Type Badge */}
        <div className="flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-800/80 border border-slate-700 text-xs font-semibold uppercase tracking-wider text-slate-300 mb-6">
          {callType === 'video' ? <Video className="w-3.5 h-3.5 text-blue-400" /> : <Phone className="w-3.5 h-3.5 text-emerald-400" />}
          <span>{callType === 'video' ? 'Görüntülü Arama' : 'Sesli Arama'}</span>
        </div>

        {/* Video Screen or Avatar */}
        <div className="relative w-44 h-44 my-4 flex items-center justify-center">
          {isVideoEnabled && callStatus === 'connected' ? (
            <div className="w-full h-full rounded-2xl overflow-hidden bg-slate-950 border-2 border-blue-500/50 shadow-lg relative">
              <video
                ref={localVideoRef}
                autoPlay
                playsInline
                muted
                className="w-full h-full object-cover mirror"
              />
              <div className="absolute bottom-2 left-2 px-2 py-0.5 bg-black/60 rounded text-[10px] text-slate-300">
                Siz
              </div>
            </div>
          ) : (
            <div className="relative flex items-center justify-center">
              {/* Pulsing rings for calling or ringing */}
              {(callStatus === 'calling' || callStatus === 'ringing' || callStatus === 'incoming') && (
                <>
                  <div className="absolute w-44 h-44 rounded-full border-2 border-emerald-500/30 animate-ping duration-1000" />
                  <div className="absolute w-36 h-36 rounded-full border border-blue-500/40 animate-pulse duration-700" />
                </>
              )}
              <div className="w-28 h-28 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white text-3xl font-bold shadow-2xl border-4 border-slate-800 overflow-hidden">
                {peerAvatar ? (
                  <img src={peerAvatar} alt={displayName} className="w-full h-full object-cover" />
                ) : (
                  <span>{displayName.slice(0, 2).toUpperCase() || <UserIcon className="w-12 h-12" />}</span>
                )}
              </div>
            </div>
          )}
        </div>

        {/* User Info */}
        <div className="text-center mt-2 mb-6">
          <h3 className="text-2xl font-bold tracking-tight text-white">{displayName}</h3>
          <p className="text-sm font-medium text-slate-400 mt-1">
            {callStatus === 'incoming' && 'Gelen Çağrı...'}
            {callStatus === 'calling' && 'Aranıyor...'}
            {callStatus === 'ringing' && 'Çalıyor...'}
            {callStatus === 'connected' && (
              <span className="text-emerald-400 font-mono font-semibold tracking-wider">
                {formatDuration(duration)}
              </span>
            )}
          </p>
        </div>

        {/* Audio Visualizer Wave (When Connected and Audio Active) */}
        {callStatus === 'connected' && (
          <div className="flex items-center gap-1.5 h-6 mb-6">
            {[40, 75, 100, 60, 85, 30, 90, 50, 70, 45].map((h, i) => (
              <div
                key={i}
                className="w-1 bg-emerald-400/80 rounded-full animate-pulse"
                style={{
                  height: `${isMuted ? 4 : h}%`,
                  animationDelay: `${i * 90}ms`,
                  transition: 'height 0.2s ease',
                }}
              />
            ))}
          </div>
        )}

        {/* Call Controls */}
        <div className="flex items-center gap-4 mt-2">
          {callStatus === 'incoming' ? (
            /* Incoming Call Actions: Accept or Reject */
            <div className="flex items-center gap-8">
              <button
                onClick={onReject}
                className="flex flex-col items-center gap-2 group"
              >
                <div className="w-16 h-16 rounded-full bg-red-600 hover:bg-red-500 text-white flex items-center justify-center shadow-lg transition-all transform group-hover:scale-110 active:scale-95">
                  <PhoneOff className="w-7 h-7" />
                </div>
                <span className="text-xs font-semibold text-slate-400">Reddet</span>
              </button>

              <button
                onClick={onAnswer}
                className="flex flex-col items-center gap-2 group"
              >
                <div className="w-16 h-16 rounded-full bg-emerald-600 hover:bg-emerald-500 text-white flex items-center justify-center shadow-lg transition-all transform group-hover:scale-110 active:scale-95 animate-bounce">
                  <Phone className="w-7 h-7" />
                </div>
                <span className="text-xs font-semibold text-emerald-400">Cevapla</span>
              </button>
            </div>
          ) : (
            /* Active Call Controls */
            <div className="flex items-center gap-3">
              {/* Mute Mic */}
              <button
                onClick={() => setIsMuted(!isMuted)}
                className={`p-3.5 rounded-2xl border transition-all ${
                  isMuted
                    ? 'bg-red-500/20 border-red-500/40 text-red-400'
                    : 'bg-slate-800 border-slate-700 hover:bg-slate-700 text-slate-200'
                }`}
                title={isMuted ? 'Mikrofonu Aç' : 'Mikrofonu Kapat'}
              >
                {isMuted ? <MicOff className="w-5 h-5" /> : <Mic className="w-5 h-5" />}
              </button>

              {/* Toggle Video */}
              {callType === 'video' && (
                <button
                  onClick={() => setIsVideoEnabled(!isVideoEnabled)}
                  className={`p-3.5 rounded-2xl border transition-all ${
                    !isVideoEnabled
                      ? 'bg-red-500/20 border-red-500/40 text-red-400'
                      : 'bg-slate-800 border-slate-700 hover:bg-slate-700 text-slate-200'
                  }`}
                  title={isVideoEnabled ? 'Kamerayı Kapat' : 'Kamerayı Aç'}
                >
                  {isVideoEnabled ? <Video className="w-5 h-5" /> : <VideoOff className="w-5 h-5" />}
                </button>
              )}

              {/* Speaker Toggle */}
              <button
                onClick={() => setIsSpeakerOn(!isSpeakerOn)}
                className={`p-3.5 rounded-2xl border transition-all ${
                  !isSpeakerOn
                    ? 'bg-red-500/20 border-red-500/40 text-red-400'
                    : 'bg-slate-800 border-slate-700 hover:bg-slate-700 text-slate-200'
                }`}
                title={isSpeakerOn ? 'Hoparlörü Kapat' : 'Hoparlörü Aç'}
              >
                {isSpeakerOn ? <Volume2 className="w-5 h-5" /> : <VolumeX className="w-5 h-5" />}
              </button>

              {/* End Call Button */}
              <button
                onClick={onEndCall}
                className="w-14 h-14 rounded-2xl bg-red-600 hover:bg-red-500 text-white flex items-center justify-center shadow-lg transition-all transform hover:scale-105 active:scale-95 ml-2"
                title="Aramayı Sonlandır"
              >
                <PhoneOff className="w-6 h-6" />
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
