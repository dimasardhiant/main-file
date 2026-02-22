import React, { useEffect, useRef, useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';
import QRCode from 'qrcode';

interface QRCodeModalProps {
  isOpen: boolean;
  onClose: () => void;
  url: string;
  title: string;
}

export function QRCodeModal({ isOpen, onClose, url, title }: QRCodeModalProps) {
  const { t } = useTranslation();
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const [qrUrl, setQrUrl] = useState('');

  useEffect(() => {
    if (isOpen) {
      // Generate QR code as data URL
      QRCode.toDataURL(url, {
        width: 300,
        margin: 2,
        color: {
          dark: '#000000',
          light: '#ffffff'
        }
      })
      .then(dataUrl => {
        setQrUrl(dataUrl);
      })
      .catch(err => {
        console.error('Error generating QR code:', err);
      });
    }
  }, [isOpen, url]);

  const handleDownload = () => {
    if (qrUrl) {
      const link = document.createElement('a');
      link.download = `${title.replace(/\s+/g, '-').toLowerCase()}-qrcode.png`;
      link.href = qrUrl;
      link.click();
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{t('QR Code for')} {title}</DialogTitle>
        </DialogHeader>
        <div className="flex flex-col items-center justify-center p-4">
          {qrUrl ? (
            <img src={qrUrl} alt="QR Code" className="border rounded-md w-[300px] h-[300px]" />
          ) : (
            <div className="border rounded-md w-[300px] h-[300px] flex items-center justify-center bg-gray-100">
              <p className="text-gray-500">Loading QR code...</p>
            </div>
          )}
          <p className="mt-4 text-sm text-center text-muted-foreground">
            {t('Scan this QR code to access the vCard')}
          </p>
        </div>
        <DialogFooter className="flex flex-col sm:flex-row sm:justify-between gap-2">
          <Button variant="outline" onClick={onClose}>
            {t('Close')}
          </Button>
          <Button onClick={handleDownload}>
            {t('Download QR Code')}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}