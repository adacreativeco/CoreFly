# Sprint 9: HR & Messaging UI

## Goal
Implement the user interfaces for the **HR Module** (Employees, Departments) and the **Messaging Module** (Real-time Chat) in the React frontend, integrating with the existing PHP backend APIs.

## Scope
1.  **HR Module**
    *   `HRLayout`: Navigation for HR sub-modules (Employees, Departments).
    *   `EmployeeList`: List view of employees with search/filter.
    *   `EmployeeDetail`: Modal or page to view/edit employee details.
    *   `DepartmentList`: Hierarchical or list view of departments.
2.  **Messaging Module**
    *   `ChatLayout`: Two-pane layout (Conversation List | Chat Window).
    *   `ConversationList`: List of active conversations (DMs, Groups).
    *   `ChatWindow`: Message history, input area, and send functionality.
    *   **Real-time Strategy**: Polling (e.g., every 5 seconds) for new messages since WebSocket is not yet implemented.

## Technical Tasks
1.  **HR Feature**
    *   Create `src/types/hr.ts` (Employee, Department interfaces).
    *   Create `src/services/hrService.ts` (API calls).
    *   Create `src/pages/HR/EmployeeList.tsx`.
    *   Create `src/pages/HR/DepartmentList.tsx`.
    *   Create `src/pages/HR/HRDashboard.tsx`.
2.  **Messaging Feature**
    *   Create `src/types/messaging.ts` (Conversation, Message interfaces).
    *   Create `src/services/messageService.ts` (API calls).
    *   Create `src/pages/Messages/ChatLayout.tsx`.
    *   Create `src/pages/Messages/ConversationList.tsx`.
    *   Create `src/pages/Messages/ChatWindow.tsx`.
    *   Implement polling hook for auto-refresh.

## Verification
*   User can view the list of employees and departments.
*   User can create a new employee (basic).
*   User can open the Messages page and see conversations.
*   User can send a message and see it appear in the chat window.
