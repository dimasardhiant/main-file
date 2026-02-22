import React, { useState, useEffect } from 'react';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Label } from './ui/label';
import MediaLibraryModal from './MediaLibraryModal';
import { Image as ImageIcon, X, FileText } from 'lucide-react';
import { getImagePath } from '@/utils/helpers';

interface MediaPickerProps {
  label?: string;
  value?: string;
  onChange: (value: string) => void;
  multiple?: boolean;
  placeholder?: string;
  showPreview?: boolean;
  readOnly?: boolean;
}

export default function MediaPicker({
  label,
  value = '',
  onChange,
  multiple = false,
  placeholder = 'Select image...',
  showPreview = true,
  readOnly = false
}: MediaPickerProps) {
  const [isModalOpen, setIsModalOpen] = useState(false);


  //   const handleSelect = (selectedUrl: string) => {
  //   // Convert full URL to path by removing domain
  //   const path = selectedUrl.startsWith('http') ? new URL(selectedUrl).pathname : selectedUrl;
  //   onChange(path);
  // };
  const handleSelect = (selectedUrl: string) => {
    // Extract only the filename from the full path
    const filename = selectedUrl.split('/').pop() || selectedUrl;
    onChange(filename);
  };

  const handleClear = () => {
    onChange('');
  };

  // Ensure value is always a string, never null
  const safeValue = value || '';

  // Process the image URL for preview
  const getDisplayUrl = (url: string) => {
    if (!url) return '';

    // If it's already a full URL, use it as is
    if (url.startsWith('http')) {
      return url;
    }

    // If it starts with /, add the base URL
    if (url.startsWith('/')) {
      return getImagePath(url);
    }
    // Otherwise, prepend /storage/
    return getImagePath(url);
  };

  const imageUrls = safeValue ? [getDisplayUrl(safeValue)] : [];

  const getFileIcon = (url: string) => {
    if (!url) return null;
    const extension = url.split('.').pop()?.toLowerCase();

    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(extension || '')) {
      return null; // Return null for images to show actual image
    }

    if (extension === 'pdf') return <div className="h-16 w-16 bg-red-500 rounded text-white text-xs flex items-center justify-center font-bold">PDF</div>;
    if (['doc', 'docx'].includes(extension || '')) return <div className="h-16 w-16 bg-blue-500 rounded text-white text-xs flex items-center justify-center font-bold">DOC</div>;
    if (['xls', 'xlsx', 'csv'].includes(extension || '')) return <div className="h-16 w-16 bg-green-500 rounded text-white text-xs flex items-center justify-center font-bold">XLS</div>;
    if (['ppt', 'pptx'].includes(extension || '')) return <div className="h-16 w-16 bg-orange-500 rounded text-white text-xs flex items-center justify-center font-bold">PPT</div>;

    return <div className="h-16 w-16 bg-gray-500 rounded text-white text-xs flex items-center justify-center font-bold">FILE</div>;
  };

  return (
    <div className="space-y-2">
      {label && <Label>{label}</Label>}

      <div className="flex gap-2">
        <Input
          value={safeValue}
          onChange={(e) => onChange(e.target.value)}
          placeholder={placeholder}
          readOnly={readOnly || multiple}
        />
        <Button
          type="button"
          variant="outline"
          onClick={() => setIsModalOpen(true)}
          disabled={readOnly}
        >
          <ImageIcon className="h-4 w-4 mr-2" />
          Browse
        </Button>
        {safeValue && (
          <Button
            type="button"
            variant="outline"
            size="icon"
            onClick={handleClear}
            disabled={readOnly}
          >
            <X className="h-4 w-4" />
          </Button>
        )}
      </div>

      {/* Preview */}
      {showPreview && imageUrls.length > 0 && (
        <div className="grid grid-cols-4 gap-2 mt-2">
          {imageUrls.map((url, index) => {
            const fileIcon = getFileIcon(url);
            return (
              <div key={index} className="relative">
                {fileIcon ? (
                  <div className="w-full h-20 flex items-center justify-center rounded border bg-muted">
                    {fileIcon}
                  </div>
                ) : (
                  <img
                    src={url}
                    alt={`Preview ${index + 1}`}
                    className="w-full h-20 object-cover rounded border"
                    onError={(e) => {
                      // If image fails to load, show file icon
                      const target = e.target as HTMLImageElement;
                      const container = target.parentElement;
                      if (container) {
                        container.innerHTML = '<div class="w-full h-20 flex items-center justify-center rounded border bg-muted"><div class="h-16 w-16 bg-gray-500 rounded text-white text-xs flex items-center justify-center font-bold">FILE</div></div>';
                      }
                    }}
                  />
                )}
              </div>
            );
          })}
        </div>
      )}

      <MediaLibraryModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        onSelect={handleSelect}
        multiple={multiple}
      />
    </div>
  );
}