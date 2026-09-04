import client from '@/api/client';
import { FileAttachment } from '@/types/files';

export const uploadFile = async (tenantId: string, file: File): Promise<FileAttachment> => {
  const formData = new FormData();
  formData.append('file', file);

  const response = await client.post<{ data: FileAttachment }>(
    `/storage/${tenantId}/upload`,
    formData,
    {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    }
  );
  return response.data.data;
};

export const getFiles = async (tenantId: string): Promise<FileAttachment[]> => {
  const response = await client.get<{ data: FileAttachment[] }>(`/storage/${tenantId}/files`);
  return response.data.data;
};

export const deleteFile = async (tenantId: string, fileId: string): Promise<void> => {
  await client.delete(`/storage/${tenantId}/files/${fileId}`);
};

export const getDownloadUrl = (tenantId: string, fileId: string): string => {
  return `/api/storage/${tenantId}/files/${fileId}`;
};
