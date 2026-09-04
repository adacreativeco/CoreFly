import React, { useCallback } from 'react';
import { UploadCloud } from 'lucide-react';
import clsx from 'clsx';

interface UploadZoneProps {
  onFileSelect: (file: File) => void;
  isUploading: boolean;
}

export const UploadZone: React.FC<UploadZoneProps> = ({ onFileSelect, isUploading }) => {
  const handleDragOver = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
  }, []);

  const handleDrop = useCallback(
    (e: React.DragEvent) => {
      e.preventDefault();
      e.stopPropagation();
      if (e.dataTransfer.files && e.dataTransfer.files[0]) {
        onFileSelect(e.dataTransfer.files[0]);
      }
    },
    [onFileSelect]
  );

  const handleFileInput = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      if (e.target.files && e.target.files[0]) {
        onFileSelect(e.target.files[0]);
      }
    },
    [onFileSelect]
  );

  return (
    <div
      onDragOver={handleDragOver}
      onDrop={handleDrop}
      className={clsx(
        'border-2 border-dashed border-gray-300 rounded-lg p-12 text-center hover:border-indigo-500 transition-colors cursor-pointer bg-gray-50',
        isUploading && 'opacity-50 pointer-events-none'
      )}
    >
      <input
        type="file"
        className="hidden"
        id="file-upload"
        onChange={handleFileInput}
        disabled={isUploading}
      />
      <label htmlFor="file-upload" className="cursor-pointer">
        <UploadCloud className="mx-auto h-12 w-12 text-gray-400" />
        <span className="mt-2 block text-sm font-medium text-gray-900">
          {isUploading ? 'Yükleniyor...' : 'Dosyayı buraya sürükleyin veya seçmek için tıklayın'}
        </span>
        <span className="mt-1 block text-xs text-gray-500">
          PNG, JPG, GIF, PDF (Maks. 10MB)
        </span>
      </label>
    </div>
  );
};
