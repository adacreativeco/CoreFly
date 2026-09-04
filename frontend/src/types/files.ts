export interface FileAttachment {
  id: string;
  tenant_id: string;
  uploaded_by: string;
  original_name: string;
  file_name: string;
  file_path: string;
  file_size: number;
  mime_type: string;
  entity_type: string | null;
  entity_id: string | null;
  is_public: number;
  created_at: string;
}
