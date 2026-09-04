import React, { useEffect, useState, useCallback } from 'react';
import { useAuthStore } from '@/store/authStore';
import { getFiles, uploadFile, deleteFile, getDownloadUrl } from '@/services/storageService';
import { FileAttachment } from '@/types/files';
import { UploadZone } from '@/components/files/UploadZone';
import { FileIcon, Trash2, Download, Image as ImageIcon } from 'lucide-react';

export const FileList: React.FC = () => {
  const { user } = useAuthStore();
  const [files, setFiles] = useState<FileAttachment[]>([]);
  const [loading, setLoading] = useState(true);
  const [isUploading, setIsUploading] = useState(false);

  const loadFiles = useCallback(async () => {
    try {
      if (!user?.tenant_id) return;
      const data = await getFiles(user.tenant_id);
      setFiles(data);
    } catch (error) {
      console.error('Failed to load files:', error);
    } finally {
      setLoading(false);
    }
  }, [user?.tenant_id]);

  useEffect(() => {
    if (user?.tenant_id) {
      loadFiles();
    }
  }, [user?.tenant_id, loadFiles]);

  const handleFileUpload = async (file: File) => {
    if (!user?.tenant_id) return;
    setIsUploading(true);
    try {
      await uploadFile(user.tenant_id, file);
      loadFiles();
    } catch (error) {
      console.error('Failed to upload file:', error);
      alert('Upload failed');
    } finally {
      setIsUploading(false);
    }
  };

  const handleDelete = async (fileId: string) => {
    if (!user?.tenant_id || !window.confirm('Bu dosyayı silmek istediğinize emin misiniz?')) return;
    try {
      await deleteFile(user.tenant_id, fileId);
      setFiles(files.filter(f => f.id !== fileId));
    } catch (error) {
      console.error('Failed to delete file:', error);
      alert('Silme işlemi başarısız oldu');
    }
  };

  const formatSize = (bytes: number) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  if (loading) return <div className="p-8 text-center">Dosyalar yükleniyor...</div>;

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900">Dosyalar</h1>

      <UploadZone onFileSelect={handleFileUpload} isUploading={isUploading} />

      <div className="bg-white shadow overflow-hidden sm:rounded-md">
        <ul className="divide-y divide-gray-200">
          {files.length === 0 ? (
            <li className="px-4 py-8 text-center text-gray-500">Henüz dosya yüklenmedi</li>
          ) : (
            files.map((file) => (
              <li key={file.id} className="px-4 py-4 sm:px-6 hover:bg-gray-50 transition-colors">
                <div className="flex items-center justify-between">
                  <div className="flex items-center flex-1 min-w-0">
                    <div className="flex-shrink-0 h-10 w-10 rounded bg-gray-100 flex items-center justify-center">
                      {file.mime_type.startsWith('image/') ? (
                        <ImageIcon className="h-6 w-6 text-gray-500" />
                      ) : (
                        <FileIcon className="h-6 w-6 text-gray-500" />
                      )}
                    </div>
                    <div className="ml-4 flex-1 min-w-0">
                      <h3 className="text-sm font-medium text-gray-900 truncate">
                        {file.original_name}
                      </h3>
                      <p className="text-sm text-gray-500">
                        {formatSize(file.file_size)} • {new Date(file.created_at).toLocaleDateString()} tarihinde yüklendi
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center space-x-4">
                    <a
                      href={user?.tenant_id ? getDownloadUrl(user.tenant_id, file.id) : '#'}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-gray-400 hover:text-gray-600"
                      title="İndir"
                    >
                      <Download className="h-5 w-5" />
                    </a>
                    <button
                      onClick={() => handleDelete(file.id)}
                      className="text-gray-400 hover:text-red-600"
                      title="Sil"
                    >
                      <Trash2 className="h-5 w-5" />
                    </button>
                  </div>
                </div>
              </li>
            ))
          )}
        </ul>
      </div>
    </div>
  );
};
