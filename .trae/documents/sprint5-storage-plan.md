# Sprint 5: File Management & Storage Plan

## Goal
Implement a secure file storage system to handle uploads, downloads, and attachments for various entities (Messages, Tasks, etc.).

## Scope

### 1. Database Schema
- **File Attachments Table** (`file_attachments`): Stores metadata for uploaded files, including path, size, mime type, and entity association.

### 2. Backend API
- **File Management**:
  - `POST /api/storage/{tenantId}/upload`: Upload a file (multipart/form-data).
  - `GET /api/storage/{tenantId}/files/{fileId}`: Download/View file.
  - `DELETE /api/storage/{tenantId}/files/{fileId}`: Delete file.
  - `GET /api/storage/{tenantId}/files`: List files (optional filters).

### 3. Storage Strategy
- **Local Filesystem** (for MVP): Store files in `storage/uploads/{tenantId}/{year}/{month}/`.
- **Security**:
  - Validate file types.
  - Serve files through PHP to check permissions (no direct public access).

### 4. Verification
- Create `scripts/test_storage.php` to verify:
  1. Login as User.
  2. Upload a text/image file.
  3. Verify database record created.
  4. Verify file exists on disk.
  5. Download file and compare content.
  6. Delete file.

## Tasks
1. [ ] Create migration SQL files.
2. [ ] Run migrations.
3. [ ] Implement `FileAttachment` model.
4. [ ] Implement `StorageController` (handle `$_FILES`).
5. [ ] Register routes in `public/index.php`.
6. [ ] Create and run verification script.
