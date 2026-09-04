# CoreFly – API Requirements Specification (API Endpoint Blueprint)

## 1. Overview

CoreFly Enterprise platformu için RESTful API spesifikasyonu. Bu belge, 57 farklı modül için gereken tüm API endpoint'lerini, HTTP methodlarını, request/response yapılarını, permission gereksinimlerini ve error code standartlarını içermektedir.

## 2. API Standards

### 2.1 Base URL Structure
```
https://api.corefly.com/v1/{tenant_id}/{module}/{resource}
```

### 2.2 Authentication
- **Type**: Bearer Token
- **Header**: `Authorization: Bearer {access_token}`
- **Token TTL**: 60 minutes
- **Refresh Token**: 7 days

### 2.3 Request/Response Format
- **Content-Type**: `application/json`
- **Charset**: UTF-8
- **Pagination**: Cursor-based
- **Rate Limiting**: 1000 requests/hour per user

### 2.4 Common Headers
```http
Content-Type: application/json
Authorization: Bearer {token}
X-Request-ID: {uuid}
X-Tenant-ID: {tenant_id}
X-User-ID: {user_id}
```

## 3. Error Response Standards

### 3.1 Error Code Structure
```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable error message",
    "details": {
      "field": "additional context"
    },
    "request_id": "uuid",
    "timestamp": "2024-01-15T10:30:00Z"
  }
}
```

### 3.2 HTTP Status Codes & Error Codes

#### 2xx Success
| HTTP Code | Error Code | Description |
|-----------|------------|-------------|
| 200 | SUCCESS | General success |
| 201 | CREATED | Resource created successfully |
| 202 | ACCEPTED | Request accepted for processing |
| 204 | NO_CONTENT | No content to return |

#### 4xx Client Errors
| HTTP Code | Error Code | Description |
|-----------|------------|-------------|
| 400 | BAD_REQUEST | Invalid request parameters |
| 401 | UNAUTHORIZED | Authentication required |
| 403 | FORBIDDEN | Insufficient permissions |
| 404 | NOT_FOUND | Resource not found |
| 409 | CONFLICT | Resource conflict |
| 422 | VALIDATION_ERROR | Validation failed |
| 429 | RATE_LIMIT_EXCEEDED | Too many requests |

#### 5xx Server Errors
| HTTP Code | Error Code | Description |
|-----------|------------|-------------|
| 500 | INTERNAL_ERROR | Internal server error |
| 502 | BAD_GATEWAY | Invalid gateway response |
| 503 | SERVICE_UNAVAILABLE | Service temporarily unavailable |
| 504 | GATEWAY_TIMEOUT | Gateway timeout |

## 4. Permission Requirements

### 4.1 Permission Format
```
{module}.{resource}.{action}
```

### 4.2 Common Actions
- `create`: Yeni kayıt oluşturma
- `read`: Kayıt okuma
- `update`: Kayıt güncelleme
- `delete`: Kayıt silme
- `list`: Liste görüntüleme
- `export`: Dışa aktarma
- `admin`: Yönetici işlemleri

## 5. Module 1: Workspace & Communication APIs

### 5.1 Posts (Gönderiler)

#### List Posts
```http
GET /v1/{tenant_id}/workspace/posts
```

**Query Parameters:**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| type | string | No | post, announcement, news |
| status | string | No | draft, published, archived |
| author_id | uuid | No | Filter by author |
| search | string | No | Search in title/content |
| page | integer | No | Page number (default: 1) |
| limit | integer | No | Items per page (max: 100) |

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "title": "string",
      "content": "string",
      "type": "general",
      "status": "published",
      "author": {
        "id": "uuid",
        "name": "string",
        "avatar_url": "string"
      },
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-15T10:30:00Z"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 100,
    "has_next": true
  }
}
```

**Required Permission:** `workspace.posts.read`

#### Create Post
```http
POST /v1/{tenant_id}/workspace/posts
```

**Request Body:**
```json
{
  "title": "string (required)",
  "content": "string (required)",
  "type": "general|announcement|news|update",
  "summary": "string",
  "featured_image_url": "string",
  "visibility": "public|internal|private",
  "priority": "low|normal|high|urgent",
  "target_audience": {
    "departments": ["uuid"],
    "roles": ["uuid"],
    "users": ["uuid"]
  },
  "metadata": {}
}
```

**Response:**
```json
{
  "data": {
    "id": "uuid",
    "title": "string",
    "content": "string",
    "type": "general",
    "status": "draft",
    "author": {
      "id": "uuid",
      "name": "string"
    },
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

**Required Permission:** `workspace.posts.create`

#### Update Post
```http
PUT /v1/{tenant_id}/workspace/posts/{post_id}
```

**Request Body:**
```json
{
  "title": "string",
  "content": "string",
  "type": "general|announcement|news|update",
  "status": "draft|published|archived",
  "summary": "string",
  "featured_image_url": "string",
  "visibility": "public|internal|private",
  "priority": "low|normal|high|urgent",
  "target_audience": {
    "departments": ["uuid"],
    "roles": ["uuid"],
    "users": ["uuid"]
  },
  "metadata": {}
}
```

**Required Permission:** `workspace.posts.update`

#### Delete Post
```http
DELETE /v1/{tenant_id}/workspace/posts/{post_id}
```

**Required Permission:** `workspace.posts.delete`

### 5.2 Comments (Yorumlar)

#### List Comments
```http
GET /v1/{tenant_id}/workspace/posts/{post_id}/comments
```

**Query Parameters:**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| parent_id | uuid | No | Filter by parent comment |
| status | string | No | active, hidden, deleted |
| page | integer | No | Page number |
| limit | integer | No | Items per page |

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "content": "string",
      "author": {
        "id": "uuid",
        "name": "string",
        "avatar_url": "string"
      },
      "parent_id": "uuid",
      "likes_count": 0,
      "is_edited": false,
      "created_at": "2024-01-15T10:30:00Z"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 50
  }
}
```

**Required Permission:** `workspace.comments.read`

#### Create Comment
```http
POST /v1/{tenant_id}/workspace/posts/{post_id}/comments
```

**Request Body:**
```json
{
  "content": "string (required)",
  "parent_id": "uuid"
}
```

**Required Permission:** `workspace.comments.create`

### 5.3 Conversations (Sohbetler)

#### List Conversations
```http
GET /v1/{tenant_id}/workspace/conversations
```

**Query Parameters:**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| type | string | No | direct, group, channel |
| status | string | No | active, archived |
| search | string | No | Search in title |
| participant_id | uuid | No | Filter by participant |

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "title": "string",
      "type": "direct",
      "avatar_url": "string",
      "description": "string",
      "participant_count": 5,
      "last_message_at": "2024-01-15T10:30:00Z",
      "unread_count": 3,
      "participants": [
        {
          "id": "uuid",
          "name": "string",
          "avatar_url": "string",
          "role": "member"
        }
      ]
    }
  ]
}
```

**Required Permission:** `workspace.conversations.read`

#### Create Conversation
```http
POST /v1/{tenant_id}/workspace/conversations
```

**Request Body:**
```json
{
  "title": "string (required for group/channel)",
  "type": "direct|group|channel",
  "description": "string",
  "is_private": false,
  "participant_ids": ["uuid"]
}
```

**Required Permission:** `workspace.conversations.create`

### 5.4 Messages (Mesajlar)

#### List Messages
```http
GET /v1/{tenant_id}/workspace/conversations/{conversation_id}/messages
```

**Query Parameters:**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| before | datetime | No | Messages before this time |
| after | datetime | No | Messages after this time |
| limit | integer | No | Max messages (max: 100) |

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "content": "string",
      "content_type": "text|file|image|system",
      "sender": {
        "id": "uuid",
        "name": "string",
        "avatar_url": "string"
      },
      "attachments": [
        {
          "file_id": "uuid",
          "file_name": "string",
          "file_size": 1024,
          "file_type": "pdf"
        }
      ],
      "is_read": true,
      "created_at": "2024-01-15T10:30:00Z"
    }
  ]
}
```

**Required Permission:** `workspace.messages.read`

#### Send Message
```http
POST /v1/{tenant_id}/workspace/conversations/{conversation_id}/messages
```

**Request Body:**
```json
{
  "content": "string (required)",
  "content_type": "text|file|image",
  "attachments": [
    {
      "file_id": "uuid",
      "file_name": "string"
    }
  ],
  "reply_to_id": "uuid"
}
```

**Required Permission:** `workspace.messages.create`

### 5.5 Notifications (Bildirimler)

#### List Notifications
```http
GET /v1/{tenant_id}/workspace/notifications
```

**Query Parameters:**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| type | string | No | Filter by notification type |
| is_read | boolean | No | Filter by read status |
| priority | string | No | low, normal, high, urgent |
| page | integer | No | Page number |

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "type": "task_assigned",
      "title": "string",
      "content": "string",
      "action_url": "string",
      "action_text": "View Task",
      "priority": "normal",
      "is_read": false,
      "created_at": "2024-01-15T10:30:00Z"
    }
  ],
  "unread_count": 5
}
```

**Required Permission:** `workspace.notifications.read`

#### Mark as Read
```http
PUT /v1/{tenant_id}/workspace/notifications/{notification_id}/read
```

**Required Permission:** `workspace.notifications.update`

## 6. Module 2: HR Core APIs

### 6.1 Employees (Çalışanlar)

#### List Employees
```http
GET /v1/{tenant_id}/hr/employees
```

**Query Parameters:**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| department_id | uuid | No | Filter by department |
| status | string | No | active, inactive, terminated |
| search | string | No | Search by name/employee_id |
| page | integer | No | Page number |

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "employee_number": "EMP001",
      "user": {
        "id": "uuid",
        "name": "string",
        "email": "string",
        "avatar_url": "string"
      },
      "department": {
        "id": "uuid",
        "name": "string"
      },
      "position": {
        "id": "uuid",
        "title": "string"
      },
      "manager": {
        "id": "uuid",
        "name": "string"
      },
      "hire_date": "2023-01-15",
      "status": "active",
      "employment_type": "full_time"
    }
  ]
}
```

**Required Permission:** `hr.employees.read`

#### Create Employee
```http
POST /v1/{tenant_id}/hr/employees
```

**Request Body:**
```json
{
  "user_id": "uuid (required)",
  "employee_number": "string",
  "hire_date": "2024-01-15",
  "department_id": "uuid",
  "position_id": "uuid",
  "manager_id": "uuid",
  "employment_type": "full_time|part_time|contract|intern",
  "work_location": "string",
  "salary": 50000,
  "currency": "USD",
  "benefits": {},
  "emergency_contact": {
    "name": "string",
    "phone": "string",
    "relationship": "string"
  }
}
```

**Required Permission:** `hr.employees.create`

### 6.2 Departments (Departmanlar)

#### List Departments
```http
GET /v1/{tenant_id}/hr/departments
```

**Query Parameters:**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| parent_id | uuid | No | Filter by parent department |
| is_active | boolean | No | Filter by status |

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Engineering",
      "code": "ENG",
      "description": "string",
      "parent": {
        "id": "uuid",
        "name": "string"
      },
      "manager": {
        "id": "uuid",
        "name": "string"
      },
      "employee_count": 25,
      "is_active": true,
      "created_at": "2024-01-15T10:30:00Z"
    }
  ]
}
```

**Required Permission:** `hr.departments.read`

## 7. Module 3: Projects APIs

### 7.1 Projects (Projeler)

#### List Projects
```http
GET /v1/{tenant_id}/projects/projects
```

**Query Parameters:**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| status | string | No | planning, active, on_hold, completed, cancelled |
| priority | string | No | low, medium, high, critical |
| type | string | No | internal, external, client, research |
| client_id | uuid | No | Filter by client |
| project_manager_id | uuid | No | Filter by project manager |
| search | string | No | Search by name/code |

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "code": "PROJ001",
      "name": "Website Redesign",
      "description": "string",
      "type": "internal",
      "status": "active",
      "priority": "high",
      "client": {
        "id": "uuid",
        "name": "string"
      },
      "project_manager": {
        "id": "uuid",
        "name": "string"
      },
      "progress": 65.5,
      "start_date": "2024-01-01",
      "end_date": "2024-03-31",
      "budget": 100000,
      "actual_cost": 65000,
      "member_count": 8,
      "task_count": 25
    }
  ]
}
```

**Required Permission:** `projects.projects.read`

#### Create Project
```http
POST /v1/{tenant_id}/projects/projects
```

**Request Body:**
```json
{
  "code": "PROJ001",
  "name": "string (required)",
  "description": "string",
  "type": "internal|external|client|research",
  "category_id": "uuid",
  "client_id": "uuid",
  "project_manager_id": "uuid",
  "start_date": "2024-01-01",
  "end_date": "2024-03-31",
  "estimated_hours": 500,
  "budget": 100000,
  "currency": "USD",
  "milestones": [
    {
      "name": "string",
      "due_date": "2024-02-01",
      "description": "string"
    }
  ],
  "deliverables": [
    {
      "name": "string",
      "description": "string",
      "due_date": "2024-03-01"
    }
  ],
  "tags": ["string"]
}
```

**Required Permission:** `projects.projects.create`

### 7.2 Project Tasks (Proje Görevleri)

#### List Project Tasks
```http
GET /v1/{tenant_id}/projects/{project_id}/tasks
```

**Query Parameters:**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| parent_id | uuid | No | Filter by parent task |
| status | string | No | todo, in_progress, review, done, cancelled |
| assignee_id | uuid | No | Filter by assignee |
| priority | string | No | low, medium, high, critical |

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "code": "TASK001",
      "title": "string",
      "description": "string",
      "type": "task|milestone|deliverable|bug|feature",
      "status": "in_progress",
      "priority": "high",
      "assignee": {
        "id": "uuid",
        "name": "string"
      },
      "progress": 75.5,
      "start_date": "2024-01-01",
      "due_date": "2024-01-31",
      "estimated_hours": 40,
      "actual_hours": 30,
      "subtasks_count": 3,
      "completed_subtasks_count": 2
    }
  ]
}
```

**Required Permission:** `projects.tasks.read`

#### Create Project Task
```http
POST /v1/{tenant_id}/projects/{project_id}/tasks
```

**Request Body:**
```json
{
  "parent_id": "uuid",
  "title": "string (required)",
  "description": "string",
  "type": "task|milestone|deliverable|bug|feature",
  "assignee_id": "uuid",
  "priority": "low|medium|high|critical",
  "estimated_hours": 40,
  "start_date": "2024-01-01",
  "due_date": "2024-01-31",
  "dependencies": ["uuid"],
  "tags": ["string"],
  "is_billable": true
}
```

**Required Permission:** `projects.tasks.create`

## 8. System APIs

### 8.1 File Upload

#### Upload File
```http
POST /v1/{tenant_id}/system/files/upload
```

**Request:**
```http
Content-Type: multipart/form-data
```

**Form Data:**
- `file`: Binary file data
- `entity_type`: Type of entity this file belongs to
- `entity_id`: ID of the entity
- `is_public`: Whether file is publicly accessible

**Response:**
```json
{
  "data": {
    "id": "uuid",
    "file_name": "string",
    "original_name": "string",
    "file_size": 1024000,
    "mime_type": "application/pdf",
    "file_url": "string",
    "download_url": "string"
  }
}
```

**Required Permission:** `system.files.upload`

### 8.2 Audit Logs

#### List Audit Logs
```http
GET /v1/{tenant_id}/system/audit-logs
```

**Query Parameters:**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| entity_type | string | No | Filter by entity type |
| entity_id | uuid | No | Filter by entity ID |
| user_id | uuid | No | Filter by user |
| action | string | No | Filter by action |
| start_date | date | No | Filter by date range |
| end_date | date | No | Filter by date range |

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "user": {
        "id": "uuid",
        "name": "string"
      },
      "entity_type": "project",
      "entity_id": "uuid",
      "action": "update",
      "changes": {
        "status": {
          "old": "planning",
          "new": "active"
        }
      },
      "ip_address": "192.168.1.1",
      "created_at": "2024-01-15T10:30:00Z"
    }
  ]
}
```

**Required Permission:** `system.audit.read`

## 9. Authentication APIs

### 9.1 Login

#### User Login
```http
POST /v1/auth/login
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "string",
  "remember_me": false,
  "device_info": {
    "user_agent": "string",
    "ip_address": "string"
  }
}
```

**Response:**
```json
{
  "data": {
    "access_token": "string",
    "refresh_token": "string",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "id": "uuid",
      "name": "string",
      "email": "string",
      "avatar_url": "string",
      "roles": ["string"],
      "permissions": ["string"]
    },
    "tenant": {
      "id": "uuid",
      "name": "string",
      "plan": "enterprise"
    }
  }
}
```

### 9.2 Refresh Token

#### Refresh Access Token
```http
POST /v1/auth/refresh
```

**Request Body:**
```json
{
  "refresh_token": "string"
}
```

**Response:**
```json
{
  "data": {
    "access_token": "string",
    "refresh_token": "string",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

### 9.3 Logout

#### User Logout
```http
POST /v1/auth/logout
```

**Headers:**
```http
Authorization: Bearer {access_token}
```

**Response:**
```json
{
  "message": "Successfully logged out"
}
```

## 10. Workflow Engine APIs

### 10.1 Workflow Definitions

#### List Workflow Definitions
```http
GET /v1/{tenant_id}/workflows/definitions
```

**Required Permission:** `workflows.definitions.read`

#### Create Workflow Definition
```http
POST /v1/{tenant_id}/workflows/definitions
```

**Request Body:**
```json
{
  "name": "Leave Request Approval",
  "description": "string",
  "definition": {
    "states": [
      {
        "id": "draft",
        "name": "Draft",
        "type": "initial"
      }
    ],
    "transitions": [
      {
        "from": "draft",
        "to": "pending_approval",
        "condition": "submitted === true"
      }
    ]
  }
}
```

**Required Permission:** `workflows.definitions.create`

### 10.2 Workflow Instances

#### Start Workflow Instance
```http
POST /v1/{tenant_id}/workflows/instances
```

**Request Body:**
```json
{
  "definition_id": "uuid",
  "title": "string",
  "data": {
    "requester_id": "uuid",
    "leave_type": "annual",
    "start_date": "2024-02-01",
    "end_date": "2024-02-05"
  }
}
```

**Required Permission:** `workflows.instances.create`

## 11. Rule Engine APIs

### 11.1 Rule Definitions

#### List Rules
```http
GET /v1/{tenant_id}/rules/definitions
```

**Query Parameters:**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| category | string | No | Filter by category |
| is_active | boolean | No | Filter by status |

**Required Permission:** `rules.definitions.read`

#### Create Rule
```http
POST /v1/{tenant_id}/rules/definitions
```

**Request Body:**
```json
{
  "name": "Expense Limit Rule",
  "description": "string",
  "category": "expense",
  "priority": 1,
  "conditions": [
    {
      "fact": "user.role",
      "operator": "equals",
      "value": "employee"
    },
    {
      "fact": "expense.amount",
      "operator": "greater_than",
      "value": 1000
    }
  ],
  "actions": [
    {
      "type": "require_approval",
      "params": {
        "approver": "manager"
      }
    }
  ]
}
```

**Required Permission:** `rules.definitions.create`

### 11.2 Rule Evaluation

#### Evaluate Rules
```http
POST /v1/{tenant_id}/rules/evaluate
```

**Request Body:**
```json
{
  "facts": {
    "user.role": "employee",
    "expense.amount": 1500,
    "expense.category": "travel"
  }
}
```

**Response:**
```json
{
  "data": {
    "matched_rules": [
      {
        "id": "uuid",
        "name": "string",
        "actions": [
          {
            "type": "require_approval",
            "params": {}
          }
        ]
      }
    ],
    "actions": [
      {
        "type": "require_approval",
        "params": {
          "approver": "manager"
        }
      }
    ]
  }
}
```

**Required Permission:** `rules.evaluate`

## 12. Rate Limiting

### 12.1 Rate Limit Headers
All API responses include rate limiting headers:

```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1642248000
X-RateLimit-Reset-After: 3600
```

### 12.2 Rate Limit Exceeded Response
When rate limit is exceeded:

```http
HTTP/1.1 429 Too Many Requests
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1642248000
Retry-After: 3600
```

```json
{
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "API rate limit exceeded. Please retry after 3600 seconds.",
    "details": {
      "limit": 1000,
      "reset_at": "2024-01-15T12:00:00Z"
    }
  }
}
```

## 13. API Versioning

### 13.1 Version Strategy
- URL-based versioning: `/v1/`, `/v2/`
- Backward compatibility maintained for 6 months
- Deprecation notices in API responses

### 13.2 Deprecation Headers
```http
X-API-Deprecation: true
X-API-Deprecation-Date: 2024-06-01
X-API-Sunset-Date: 2024-12-01
X-API-Alternative: /v2/{path}
```

## 14. Webhook APIs

### 14.1 Webhook Endpoints

#### Register Webhook
```http
POST /v1/{tenant_id}/webhooks
```

**Request Body:**
```json
{
  "url": "https://example.com/webhook",
  "events": ["project.created", "task.updated"],
  "secret": "string",
  "is_active": true
}
```

**Required Permission:** `webhooks.manage`

#### Webhook Payload
```json
{
  "id": "uuid",
  "event": "project.created",
  "timestamp": "2024-01-15T10:30:00Z",
  "tenant_id": "uuid",
  "data": {
    "id": "uuid",
    "name": "string",
    "created_by": "uuid"
  },
  "signature": "sha256=..."
}
```

## 15. Export APIs

### 15.1 Data Export

#### Export Data
```http
POST /v1/{tenant_id}/export
```

**Request Body:**
```json
{
  "module": "projects",
  "format": "csv|xlsx|pdf",
  "filters": {
    "status": "active",
    "start_date": "2024-01-01"
  },
  "columns": ["id", "name", "status", "progress"]
}
```

**Response:**
```json
{
  "data": {
    "export_id": "uuid",
    "status": "processing",
    "estimated_completion": "2024-01-15T11:00:00Z",
    "download_url": "string (when completed)"
  }
}
```

**Required Permission:** `{module}.export`

## 16. Search APIs

### 16.1 Global Search

#### Search Across Modules
```http
GET /v1/{tenant_id}/search
```

**Query Parameters:**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| q | string | Yes | Search query |
| modules | array | No | projects, tasks, users, etc. |
| limit | integer | No | Max results per module |

**Response:**
```json
{
  "data": {
    "projects": [
      {
        "id": "uuid",
        "name": "Website Redesign",
        "type": "project",
        "highlight": "...<em>website</em> redesign..."
      }
    ],
    "tasks": [
      {
        "id": "uuid",
        "title": "Design homepage",
        "type": "task",
        "highlight": "...<em>design</em> homepage..."
      }
    ],
    "users": [
      {
        "id": "uuid",
        "name": "John Designer",
        "type": "user",
        "highlight": "John <em>Designer</em>"
      }
    ]
  },
  "total_count": 25
}
```

**Required Permission:** `search.global`

Bu API spesifikasyonu, CoreFly Enterprise platformunun tüm modülleri için kapsamlı bir API referansı sunmaktadır. Her endpoint, gerekli permission kontrolleri ile birlikte detaylı olarak dokümante edilmiştir.