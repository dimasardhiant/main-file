import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Eye, EyeOff } from 'lucide-react';
import { usePage } from '@inertiajs/react';

interface PaymentInputFieldProps {
  id: string;
  label: string;
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  type?: 'text' | 'password';
  isSecret?: boolean;
  error?: string;
  className?: string;
}

export function PaymentInputField({
  id,
  label,
  value,
  onChange,
  placeholder,
  type = 'text',
  isSecret = false,
  error,
  className = ''
}: PaymentInputFieldProps) {
  const [showSecret, setShowSecret] = useState(false);
  
  const { props } = usePage();
  const isDemo = (props as any).globalSettings?.is_demo || false;
  
  // Show asterisks in demo mode for secret fields with values
  const displayValue = isDemo && value ? '************************************************' : value;
  const inputType = isSecret ? (showSecret ? 'text' : 'password') : type;

  return (
    <div className="space-y-2">
      <Label htmlFor={id}>{label}</Label>
      <div className="relative">
        <Input
          id={id}
          type={inputType}
          value={displayValue}
          onChange={(e) => onChange(e.target.value)}
          placeholder={placeholder}
          className={`font-mono text-sm ${isSecret ? 'pr-10' : ''} ${className}`}
          readOnly={isDemo && isSecret && value}
        />
        {isSecret && (
          <Button
            type="button"
            variant="ghost"
            size="icon"
            className="absolute right-0 top-0 h-full px-3 text-muted-foreground"
            onClick={() => {
              if (isDemo) return false;
              setShowSecret(!showSecret);
            }}
            disabled={isDemo}
          >
            {showSecret ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
          </Button>
        )}
      </div>
      {error && (
        <p className="text-sm text-destructive">{error}</p>
      )}
    </div>
  );
}