import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { X } from 'lucide-react';

interface TagInputProps {
  value: string[];
  onChange: (tags: string[]) => void;
  placeholder?: string;
  className?: string;
}

export function TagInput({ value, onChange, placeholder = 'Type and press Enter', className = '' }: TagInputProps) {
  const [inputValue, setInputValue] = useState('');

  const addTag = (tag: string) => {
    if (tag.trim() && !value.includes(tag.trim())) {
      onChange([...value, tag.trim()]);
    }
    setInputValue('');
  };

  const removeTag = (indexToRemove: number) => {
    const newTags = value.filter((_, index) => index !== indexToRemove);
    onChange(newTags);
  };

  const handleKeyPress = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      addTag(inputValue);
    }
  };

  return (
    <div className={`border rounded-md pl-2 min-h-[40px] flex flex-wrap gap-2 items-center ${className}`}>
      {value.map((tag, index) => (
        <Badge key={`${tag}-${index}`} variant="secondary" className="flex items-center gap-1">
          {tag}
          <button
            type="button"
            className="ml-1 h-3 w-3 cursor-pointer hover:text-red-500 flex items-center justify-center"
            onClick={() => removeTag(index)}
          >
            <X className="h-3 w-3" />
          </button>
        </Badge>
      ))}
      <Input
        value={inputValue}
        onChange={(e) => setInputValue(e.target.value)}
        onKeyPress={handleKeyPress}
        onBlur={() => inputValue.trim() && addTag(inputValue)}
        placeholder={value.length === 0 ? placeholder : ''}
        className="border-0 flex-1 min-w-[200px] focus-visible:ring-0 shadow-none"
      />
    </div>
  );
}