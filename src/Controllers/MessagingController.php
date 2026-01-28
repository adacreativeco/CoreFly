<?php

declare(strict_types=1);

namespace CoreFly\Controllers;

use CoreFly\Models\Message;
use CoreFly\Models\MessageGroup;
use CoreFly\Models\MessageGroupMember;
use CoreFly\Models\User;

class MessagingController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): string
    {
        try {
            $this->requirePermission('messaging:read');
            
            // Filter by target_id and type (frontend compatible)
            $targetId = $_GET['target_id'] ?? null;
            $type = $_GET['type'] ?? null;

            if ($targetId && $type) {
                $target = null;
                $messages = [];

                if ($type === 'group') {
                    $group = MessageGroup::find($targetId);
                    if ($group) {
                        $target = [
                            'id' => $group->id,
                            'name' => $group->name,
                            'avatar' => '/assets/images/default-group.png',
                            'type' => 'group'
                        ];
                        $messages = Message::where('group_id', $targetId)->orderBy('created_at', 'ASC')->get();
                    }
                } elseif ($type === 'user' || $type === 'direct') {
                    $user = User::find($targetId);
                    if ($user) {
                         $target = [
                            'id' => $user->id,
                            'name' => $user->getFullName(),
                            'avatar' => $user->avatar,
                            'type' => 'user'
                        ];
                        // DM Logic
                        $myId = $this->getCurrentUserId();
                        $sql = "SELECT * FROM messages 
                                WHERE (sender_id = ? AND receiver_id = ?) 
                                   OR (sender_id = ? AND receiver_id = ?)
                                ORDER BY created_at ASC";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute([$myId, $targetId, $targetId, $myId]);
                        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                            $messages[] = new Message($row);
                        }
                    }
                }

                // Enhance messages
                $users = [];
                try {
                    $userIds = array_unique(array_map(fn($m) => $m->sender_id, $messages));
                    if (!empty($userIds)) {
                        $allUsers = User::all();
                        foreach ($allUsers as $u) {
                            if (in_array($u->id, $userIds)) {
                                $users[$u->id] = [
                                    'name' => $u->getFullName(),
                                    'avatar' => $u->avatar
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {}

                $messagesArray = array_map(function($msg) use ($users) {
                    $arr = $msg->toArray();
                    $arr['sender_name'] = $users[$msg->sender_id]['name'] ?? 'Unknown';
                    $arr['sender_avatar'] = $users[$msg->sender_id]['avatar'] ?? null;
                    return $arr;
                }, $messages);

                return $this->successResponse([
                    'target' => $target,
                    'messages' => $messagesArray
                ], 'Sohbet getirildi');
            }
            
            // Legacy/Original Logic for filters (kept for backward compatibility if needed)
            $groupId = $_GET['group_id'] ?? null;
            $otherUserId = $_GET['user_id'] ?? null;
            
            if ($groupId) {
                $list = Message::where('group_id', $groupId)->orderBy('created_at', 'ASC')->get();
            } elseif ($otherUserId) {
                // Direct Messages between current user and otherUserId
                $myId = $this->getCurrentUserId();
                // Since our simple ORM doesn't support complex OR clauses easily in one go, we might need raw SQL
                // Or fetch both directions and merge.
                // Let's use raw SQL for reliability here.
                $sql = "SELECT * FROM messages 
                        WHERE (sender_id = ? AND receiver_id = ?) 
                           OR (sender_id = ? AND receiver_id = ?)
                        ORDER BY created_at ASC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$myId, $otherUserId, $otherUserId, $myId]);
                $list = [];
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $list[] = new Message($row);
                }
            } else {
                // Conversations List (Inbox)
                $myId = $this->getCurrentUserId();
                
                // 1. Groups
                $myId = $this->getCurrentUserId();
                
                // Fetch groups where user is a member
                $sql = "SELECT g.* FROM message_groups g 
                        JOIN message_group_members m ON g.id = m.group_id 
                        WHERE m.user_id = ? 
                        ORDER BY g.updated_at DESC";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$myId]);
                
                $groups = [];
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $groups[] = new MessageGroup($row);
                }

                $conversations = [];
                
                foreach ($groups as $g) {
                    // Get last message for this group (optional optimization)
                    $conversations[] = [
                        'id' => $g->id,
                        'name' => $g->name,
                        'type' => 'group',
                        'avatar' => null, // Could be a group icon
                        'last_message' => 'Grup mesajları',
                        'unread' => 0,
                        'updated_at' => $g->updated_at
                    ];
                }
                
                // 2. Direct Conversations
                // Find unique users we talked to
                $sql = "SELECT 
                            CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as other_user_id,
                            MAX(created_at) as last_msg_time,
                            COUNT(CASE WHEN is_read = 0 AND receiver_id = ? THEN 1 END) as unread_count
                        FROM messages
                        WHERE (sender_id = ? OR receiver_id = ?) AND group_id IS NULL
                        GROUP BY other_user_id
                        ORDER BY last_msg_time DESC";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$myId, $myId, $myId, $myId]);
                
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $otherId = $row['other_user_id'];
                    if (!$otherId) continue;
                    
                    $user = User::find($otherId);
                    if ($user) {
                        $conversations[] = [
                            'id' => $user->id,
                            'name' => $user->getFullName(),
                            'type' => 'direct',
                            'avatar' => $user->avatar,
                            'last_message' => 'Sohbeti görüntüle', // Could fetch actual text if query selected it
                            'unread' => $row['unread_count'],
                            'updated_at' => $row['last_msg_time']
                        ];
                    }
                }
                
                // Sort merged list by updated_at
                usort($conversations, function($a, $b) {
                    $timeA = !empty($a['updated_at']) ? strtotime($a['updated_at']) : 0;
                    $timeB = !empty($b['updated_at']) ? strtotime($b['updated_at']) : 0;
                    return $timeB - $timeA;
                });
                
                return $this->successResponse(['conversations' => $conversations], 'Sohbetler getirildi');
            }
            
            // Enhance messages with sender names (for chat view)
            $users = [];
            try {
                $userIds = array_unique(array_map(fn($m) => $m->sender_id, $list));
                if (!empty($userIds)) {
                    $allUsers = User::all(); 
                    foreach ($allUsers as $u) {
                        if (in_array($u->id, $userIds)) {
                            $users[$u->id] = [
                                'name' => $u->getFullName(),
                                'avatar' => $u->avatar
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                error_log("Failed to load users for messages: " . $e->getMessage());
            }

            $listArray = array_map(function($msg) use ($users) {
                $arr = $msg->toArray();
                $arr['sender_name'] = $users[$msg->sender_id]['name'] ?? 'Unknown';
                $arr['sender_avatar'] = $users[$msg->sender_id]['avatar'] ?? null;
                return $arr;
            }, $list);

            return $this->successResponse($listArray, 'Mesajlar getirildi');
        } catch (\Throwable $e) {
            error_log("MessagingController::index Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->errorResponse('Internal Server Error: ' . $e->getMessage(), 500);
        }
    }

    public function getUsers(): string
    {
        try {
            $this->requirePermission('messaging:read');
            $tenantId = $this->getCurrentTenantId();
            $currentUserId = $this->getCurrentUserId();
            $search = $_GET['search'] ?? '';

            $sql = "SELECT id, first_name, last_name, avatar, email FROM users WHERE tenant_id = ? AND id != ? AND status = 'active'";
            $params = [$tenantId, $currentUserId];

            if (!empty($search)) {
                $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
                $term = "%$search%";
                $params[] = $term;
                $params[] = $term;
                $params[] = $term;
            }

            $sql .= " ORDER BY first_name ASC LIMIT 50";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $users = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $users[] = [
                    'id' => $row['id'],
                    'name' => $row['first_name'] . ' ' . $row['last_name'],
                    'avatar' => $row['avatar'],
                    'email' => $row['email']
                ];
            }

            return $this->successResponse(['users' => $users], 'Kullanıcılar getirildi');
        } catch (\Throwable $e) {
            return $this->errorResponse('Kullanıcılar getirilemedi: ' . $e->getMessage(), 500);
        }
    }

    public function createGroup(): string
    {
        try {
            $this->requirePermission('messaging:create');
            $data = $this->sanitizeInput($this->getRequestData());
            
            if (empty($data['name'])) {
                return $this->errorResponse('Grup adı gerekli', 422);
            }

            $group = new MessageGroup([
                'tenant_id' => $this->getCurrentTenantId(),
                'name' => $data['name'],
                'type' => 'public',
                'created_by' => $this->getCurrentUserId()
            ]);
            $group->save();

            // Add creator as admin
            $member = new MessageGroupMember([
                'group_id' => $group->id,
                'user_id' => $this->getCurrentUserId(),
                'joined_at' => date('Y-m-d H:i:s'),
                'is_admin' => 1
            ]);
            $member->save();
            
            // Add other members by email
            if (!empty($data['members']) && is_array($data['members'])) {
                foreach ($data['members'] as $email) {
                    $email = trim($email);
                    if (empty($email)) continue;
                    
                    // Find user by email
                    $users = User::where('email', $email)->get();
                    if (!empty($users)) {
                        $user = $users[0];
                        // Avoid duplicates
                        if ($user->id !== $this->getCurrentUserId()) {
                             $m = new MessageGroupMember([
                                'group_id' => $group->id,
                                'user_id' => $user->id,
                                'joined_at' => date('Y-m-d H:i:s'),
                                'is_admin' => 0
                            ]);
                            $m->save();
                        }
                    }
                }
            }
            
            return $this->successResponse($group->toArray(), 'Grup oluşturuldu');
        } catch (\Throwable $e) {
            error_log("Create Group Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->errorResponse('Grup oluşturulurken hata: ' . $e->getMessage(), 500);
        }
    }

    public function send(): string
    {
        try {
            $this->requirePermission('messaging:create');
            $data = $this->sanitizeInput($this->getRequestData());
            
            if (empty($data['content']) && empty($data['message']) && empty($_FILES['file'])) {
                return $this->errorResponse('Mesaj veya dosya gerekli', 422);
            }
            
            // Normalize 'content' to 'message' as model expects 'message' but frontend sends 'content'
            $messageContent = $data['content'] ?? $data['message'] ?? '';

            $filePath = null;
            $fileType = null;

            // Handle File Upload
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['file'];
                $config = $this->config;
                $uploadPath = $config['filesystem']['upload_path'] ?? 'public/storage/uploads'; // Fallback
                $targetDir = __DIR__ . '/../../' . $uploadPath;
                if (!is_dir($targetDir)) mkdir($targetDir, 0775, true);
                
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
                $target = $targetDir . '/' . $safeName;
                
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $filePath = $uploadPath . '/' . $safeName;
                    $fileType = in_array($ext, ['jpg','jpeg','png','gif']) ? 'image' : 'file';
                }
            }

            // Determine Receiver or Group
            $receiverId = null;
            $groupId = null;
            
            if (isset($data['type'])) {
                if ($data['type'] === 'group') {
                    $groupId = $data['target_id'] ?? $data['group_id'];
                } else {
                    $receiverId = $data['target_id'] ?? $data['receiver_id'];
                }
            } else {
                // Fallback
                $receiverId = $data['receiver_id'] ?? null;
                $groupId = $data['group_id'] ?? null;
            }

            $msg = new Message([
                'tenant_id' => $this->getCurrentTenantId(),
                'sender_id' => $this->getCurrentUserId(),
                'receiver_id' => $receiverId,
                'group_id' => $groupId,
                'message' => $messageContent,
                'file_path' => $filePath,
                'file_type' => $fileType,
                'is_read' => 0
            ]);
            $msg->save();
            
            return $this->successResponse($msg->toArray(), 'Mesaj gönderildi');
        } catch (\Throwable $e) {
            error_log("Send Message Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->errorResponse('Mesaj gönderilirken hata: ' . $e->getMessage(), 500);
        }
    }

    public function getChatInfo(): string
    {
        $this->requirePermission('messaging:read');
        $chatId = $_GET['chat_id'] ?? null;
        $type = $_GET['type'] ?? null;

        if (!$chatId || !$type) {
            return $this->errorResponse('Chat ID ve Tipi gerekli', 422);
        }

        $info = [
            'id' => $chatId,
            'type' => $type,
            'participants' => [],
            'settings' => ['notifications' => true]
        ];

        if ($type === 'group') {
            $group = MessageGroup::find($chatId);
            if ($group) {
                $info['name'] = $group->name;
                $info['avatar'] = '/assets/images/default-group.png';
                // Fetch members
                $db = \CoreFly\Utils\Database::getInstance();
                $stmt = $db->prepare("
                    SELECT u.id, u.first_name, u.last_name, u.avatar 
                    FROM message_group_members m
                    JOIN users u ON m.user_id = u.id
                    WHERE m.group_id = ?
                ");
                $stmt->execute([$chatId]);
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $info['participants'][] = [
                        'id' => $row['id'],
                        'name' => $row['first_name'] . ' ' . $row['last_name'],
                        'avatar' => $row['avatar']
                    ];
                }
            }
        } elseif ($type === 'user' || $type === 'direct') {
            $user = User::find($chatId);
            if ($user) {
                $info['name'] = $user->getFullName();
                $info['avatar'] = $user->avatar;
                // For DM, participants are me and them
                $me = User::find($this->getCurrentUserId());
                $info['participants'][] = [
                    'id' => $user->id,
                    'name' => $user->getFullName(),
                    'avatar' => $user->avatar
                ];
                if ($me) {
                    $info['participants'][] = [
                        'id' => $me->id,
                        'name' => $me->getFullName(),
                        'avatar' => $me->avatar
                    ];
                }
            }
        }

        return $this->successResponse($info, 'Sohbet bilgileri getirildi');
    }

    public function markRead(): string
    {
        $this->requirePermission('messaging:update');
        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        if (!$id) return $this->errorResponse('ID gerekli', 422);
        $msg = Message::find($id);
        if (!$msg) return $this->errorResponse('Mesaj bulunamadı', 404);
        $msg->read_at = date('Y-m-d H:i:s');
        $msg->is_read = 1;
        $msg->save();
        return $this->successResponse($msg->toArray(), 'Mesaj okundu işaretlendi');
    }
}
