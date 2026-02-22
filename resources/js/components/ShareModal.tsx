import React from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Facebook, Twitter, Linkedin, Mail, Link } from 'lucide-react';

interface ShareModalProps {
  isOpen: boolean;
  onClose: () => void;
  url: string;
  title: string;
}

export function ShareModal({ isOpen, onClose, url, title }: ShareModalProps) {
  const { t } = useTranslation();

  const handleCopyLink = () => {
    navigator.clipboard.writeText(url)
      .then(() => {
        toast.success(t('Link copied to clipboard'));
      })
      .catch(() => {
        // Fallback for browsers that don't support clipboard API
        const tempInput = document.createElement('input');
        tempInput.value = url;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        toast.success(t('Link copied to clipboard'));
      });
  };

  const handleShare = (platform: string) => {
    let shareUrl = '';
    const encodedUrl = encodeURIComponent(url);
    const encodedTitle = encodeURIComponent(title);

    switch (platform) {
      case 'facebook':
        shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;
        break;
      case 'twitter':
        shareUrl = `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`;
        break;
      case 'linkedin':
        shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`;
        break;
      case 'email':
        shareUrl = `mailto:?subject=${encodedTitle}&body=${encodedUrl}`;
        break;
      default:
        return;
    }

    window.open(shareUrl, '_blank', 'width=600,height=400');
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{t('Share')} {title}</DialogTitle>
        </DialogHeader>
        <div className="space-y-4 py-2">
          <div className="flex items-center space-x-2">
            <div className="grid flex-1 gap-2">
              <Label htmlFor="link" className="sr-only">
                Link
              </Label>
              <Input
                id="link"
                defaultValue={url}
                readOnly
                className="w-full"
              />
            </div>
            <Button type="button" size="sm" onClick={handleCopyLink}>
              <Link className="h-4 w-4 mr-2" />
              {t('Copy')}
            </Button>
          </div>
          <div className="flex justify-center space-x-4">
            <Button
              variant="outline"
              size="icon"
              className="h-10 w-10 rounded-full bg-blue-500 text-white hover:bg-blue-600"
              onClick={() => handleShare('facebook')}
            >
              <Facebook className="h-5 w-5" />
              <span className="sr-only">Facebook</span>
            </Button>
            <Button
              variant="outline"
              size="icon"
              className="h-10 w-10 rounded-full bg-sky-500 text-white hover:bg-sky-600"
              onClick={() => handleShare('twitter')}
            >
              <Twitter className="h-5 w-5" />
              <span className="sr-only">Twitter</span>
            </Button>
            <Button
              variant="outline"
              size="icon"
              className="h-10 w-10 rounded-full bg-blue-600 text-white hover:bg-blue-700"
              onClick={() => handleShare('linkedin')}
            >
              <Linkedin className="h-5 w-5" />
              <span className="sr-only">LinkedIn</span>
            </Button>
            <Button
              variant="outline"
              size="icon"
              className="h-10 w-10 rounded-full bg-gray-500 text-white hover:bg-gray-600"
              onClick={() => handleShare('email')}
            >
              <Mail className="h-5 w-5" />
              <span className="sr-only">Email</span>
            </Button>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={onClose}>
            {t('Close')}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}