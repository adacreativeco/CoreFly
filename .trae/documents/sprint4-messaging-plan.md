# Sprint 4: Messaging & Conversations Module Plan

## Goal
Implement a real-time ready messaging system allowing users to have direct (1-on-1) and group conversations within their tenant.

## Scope

### 1. Database Schema
- **Conversations Table** (`conversations`): Stores chat rooms (direct/group), metadata, and last message info.
- **Conversation Participants Table** (`conversation_participants`): Links Users to Conversations with read status.
- **Messages Table** (`messages`): Stores actual message content, attachments (JSON), and sender info.

### 2. Backend API
- **Conversation Management**:
  - `GET /api/conversations/{tenantId}`: List user's conversations (with last message).
  - `POST /api/conversations/{tenantId}`: Start a new conversation (Direct or Group).
  - `GET /api/conversations/{tenantId}/{conversationId}`: Get conversation details.

- **Message Management**:
  - `GET /api/conversations/{tenantId}/{conversationId}/messages`: List messages (paginated).
  - `POST /api/conversations/{tenantId}/{conversationId}/messages`: Send a message.
  - `PATCH /api/conversations/{tenantId}/{conversationId}/messages/{messageId}`: Edit/Delete (soft) message.

### 3. Verification
- Create `scripts/test_messaging.php` to verify:
  1. Create two users.
  2. Start a direct conversation between them.
  3. Send a message from User A.
  4. List messages for User B.
  5. Reply from User B.

## Tasks
1. [ ] Create migration SQL files.
2. [ ] Run migrations.
3. [ ] Implement `Conversation`, `ConversationParticipant`, `Message` models.
4. [ ] Implement `ConversationController`, `MessageController`.
5. [ ] Register routes in `public/index.php`.
6. [ ] Create and run verification script.
