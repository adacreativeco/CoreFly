<?php

namespace CoreFly\Controllers;

use CoreFly\Models\CallHistory;
use CoreFly\Models\User;

class CallController extends BaseController
{
    public function startCall(): string
    {
        try {
            $this->requireAuth();
            $data = $this->sanitizeInput($this->getRequestData());
            
            if (empty($data['target_id']) || empty($data['call_type'])) {
                return $this->errorResponse('Hedef kullanıcı ve arama tipi gerekli', 422);
            }
            
            $callerId = $this->getCurrentUserId();
            $targetId = $data['target_id'];
            
            // Basic validation
            if ($callerId === $targetId) {
                return $this->errorResponse('Kendinizi arayamazsınız', 422);
            }

            // Create call record
            $call = new CallHistory([
                'caller_id' => $callerId,
                'callee_id' => $targetId,
                'call_type' => $data['call_type'],
                'status' => 'initiated',
                'started_at' => date('Y-m-d H:i:s')
            ]);
            
            $call->save();
            
            // In a real app with WS, we would trigger an event here.
            // For this PHP server, we rely on the callee polling /api/calls/poll
            
            return $this->successResponse([
                'call_id' => $call->id,
                'status' => 'initiated'
            ], 'Arama başlatıldı');
        } catch (\Throwable $e) {
            error_log("Start Call Error: " . $e->getMessage());
            return $this->errorResponse('Arama başlatılamadı: ' . $e->getMessage(), 500);
        }
    }
    
    public function handleSignaling(): string
    {
        try {
            $this->requireAuth();
            $data = $this->sanitizeInput($this->getRequestData());
            
            if (empty($data['call_id']) || empty($data['type']) || empty($data['payload'])) {
                return $this->errorResponse('Invalid signaling data', 422);
            }

            // Save signal to DB
            $signal = new \CoreFly\Models\CallSignal([
                'call_id' => $data['call_id'],
                'sender_id' => $this->getCurrentUserId(),
                'type' => $data['type'],
                'payload' => is_array($data['payload']) ? json_encode($data['payload'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $data['payload'],
                'is_processed' => 0
            ]);
            
            $signal->save();
            
            return $this->successResponse(['status' => 'forwarded'], 'Sinyal kaydedildi');
        } catch (\Throwable $e) {
            error_log("Signaling Error: " . $e->getMessage());
            return $this->errorResponse('Sinyal hatası: ' . $e->getMessage(), 500);
        }
    }

    public function pollSignals(): string
    {
        try {
            $this->requireAuth();
            $userId = $this->getCurrentUserId();
            $callId = $this->getQueryParam('call_id');

            if (empty($callId)) {
                return $this->errorResponse('Call ID required', 422);
            }

            // Fetch signals for this call, NOT sent by me, and NOT processed
            // Since we can't easily mark as processed in a GET request without side effects (and concurrency issues),
            // a better approach for polling is "give me signals created after timestamp X" or "give me unprocessed".
            // For simplicity, we will fetch unprocessed signals sent by the OTHER party.
            // And then mark them as processed? Or client should mark them?
            // "Mark as processed" is better done by the client acknowledging, but to keep it simple:
            // Server returns them and marks them as processed immediately (consume-once).

            $db = \CoreFly\Utils\Database::getInstance();
            $sql = "SELECT * FROM call_signals 
                    WHERE call_id = :callId 
                    AND sender_id != :userId 
                    AND is_processed = 0 
                    ORDER BY id ASC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([':callId' => $callId, ':userId' => $userId]);
            $signals = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Mark fetched signals as processed
            if (!empty($signals)) {
                $ids = array_column($signals, 'id');
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $updateSql = "UPDATE call_signals SET is_processed = 1 WHERE id IN ($placeholders)";
                $updateStmt = $db->prepare($updateSql);
                $updateStmt->execute($ids);
            }

            // Decode payloads
            foreach ($signals as &$sig) {
                $decoded = json_decode($sig['payload'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $sig['payload'] = $decoded;
                }
            }

            return $this->successResponse(['signals' => $signals], 'Sinyaller alındı');

        } catch (\Throwable $e) {
            error_log("Poll Signals Error: " . $e->getMessage());
            return $this->errorResponse('Sinyal okuma hatası', 500);
        }
    }

    public function endCall(): string
    {
        try {
            $this->requireAuth();
            $data = $this->sanitizeInput($this->getRequestData());
            if (empty($data['call_id'])) {
                return $this->errorResponse('Call ID required', 422);
            }

            $call = CallHistory::find($data['call_id']);
            if ($call) {
                $call->status = 'ended';
                $call->ended_at = date('Y-m-d H:i:s');
                // Calculate duration
                if (!empty($call->started_at)) {
                    $start = strtotime($call->started_at);
                    $end = time();
                    if ($start !== false && $end >= $start) {
                        $call->duration_seconds = $end - $start;
                    } else {
                        $call->duration_seconds = 0;
                    }
                } else {
                    $call->duration_seconds = 0;
                }
                $call->save();
            }

            return $this->successResponse(['status' => 'ended'], 'Arama sonlandırıldı');
        } catch (\Throwable $e) {
            error_log("End Call Error: " . $e->getMessage());
            return $this->errorResponse('Arama sonlandırılamadı: ' . $e->getMessage(), 500);
        }
    }

    // Polling endpoint for incoming calls
    public function pollIncoming(): string
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        // Find calls where I am callee, status is 'initiated', and created in last 30 seconds
        // Using raw SQL for date math
        $db = \CoreFly\Utils\Database::getInstance();
        $sql = "SELECT c.*, u.first_name, u.last_name, u.avatar 
                FROM call_history c
                JOIN users u ON c.caller_id = u.id
                WHERE c.callee_id = :userId 
                AND c.status = 'initiated' 
                AND c.started_at > datetime('now', '-30 seconds')
                ORDER BY c.started_at DESC LIMIT 1";
                
        $stmt = $db->prepare($sql);
        $stmt->execute([':userId' => $userId]);
        $call = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($call) {
            return $this->successResponse([
                'incoming_call' => $call
            ], 'Gelen arama var');
        }

        return $this->successResponse(['incoming_call' => null], 'Gelen arama yok');
    }

    public function getStatus(): string
    {
        $this->requireAuth();
        $callId = $this->getQueryParam('call_id');
        if (!$callId) return $this->errorResponse('Call ID required', 422);
        
        $call = CallHistory::find($callId);
        if (!$call) return $this->errorResponse('Call not found', 404);
        
        return $this->successResponse(['status' => $call->status], 'Call status');
    }
}
