# Sprint 10: Files & Settings UI

## Goal
Implement the user interfaces for the **Files Module** (Document Management) and the **Settings Module** (Profile, Tenant Config) in the React frontend, integrating with the existing PHP backend APIs.

## Scope
1.  **Files Module**
    *   `FilesLayout`: Main wrapper for file management.
    *   `FileList`: Grid/List view of uploaded files with thumbnails/icons.
    *   `UploadZone`: Drag-and-drop area for uploading files.
    *   `FilePreview`: Modal to view image details or download links.
2.  **Settings Module**
    *   `SettingsLayout`: Navigation for settings sub-pages (Profile, Security, Tenant).
    *   `ProfileSettings`: Form to update user name/email (UI only unless API supports it).
    *   `TenantSettings`: Read-only view of tenant info (or update if API allows).

## Technical Tasks
1.  **Files Feature**
    *   Create `src/types/files.ts` (FileAttachment interface).
    *   Create `src/services/storageService.ts` (API calls: upload, list, delete).
    *   Create `src/pages/Files/FileList.tsx`.
    *   Create `src/components/files/UploadZone.tsx`.
2.  **Settings Feature**
    *   Create `src/pages/Settings/SettingsLayout.tsx`.
    *   Create `src/pages/Settings/ProfileSettings.tsx`.
    *   Create `src/pages/Settings/TenantSettings.tsx`.

## Verification
*   User can upload a file using the upload button or drag-and-drop.
*   User can see the list of uploaded files.
*   User can download or delete a file.
*   User can navigate to Settings and view Profile/Tenant info.
