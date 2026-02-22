import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useState, useEffect } from 'react';
import { Save, Search, Upload, X } from 'lucide-react';
import { SettingsSection } from '@/components/settings-section';
import { useTranslation } from 'react-i18next';
import { router, usePage } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';

interface SeoSettingsProps {
  settings?: Record<string, string>;
}

export default function SeoSettings({ settings = {} }: SeoSettingsProps) {
  const { t } = useTranslation();
  const pageProps = usePage().props as any;
  
  // Default settings
  const defaultSettings = {
    metaKeywords: '',
    metaDescription: '',
    metaImage: ''
  };
  
  // Combine settings from props and page props
  const settingsData = Object.keys(settings).length > 0 
    ? settings 
    : (pageProps.settings || {});
  
  // Initialize state with merged settings
  const [seoSettings, setSeoSettings] = useState(() => ({
    metaKeywords: settingsData.metaKeywords || defaultSettings.metaKeywords,
    metaDescription: settingsData.metaDescription || defaultSettings.metaDescription,
    metaImage: settingsData.metaImage || defaultSettings.metaImage
  }));

  // State for image upload
  const [imagePreview, setImagePreview] = useState<string | null>(null);
  const [imageError, setImageError] = useState(false);
  
  // Update state when settings change
  useEffect(() => {
    if (Object.keys(settingsData).length > 0) {
      const mergedSettings = Object.keys(defaultSettings).reduce((acc, key) => {
        acc[key] = settingsData[key] || defaultSettings[key];
        return acc;
      }, {} as Record<string, string>);
      
      setSeoSettings(prevSettings => ({
        ...prevSettings,
        ...mergedSettings
      }));
    }
  }, [settingsData]);

  // Handle SEO settings form changes
  const handleSeoSettingsChange = (field: string, value: string) => {
    setSeoSettings(prev => ({
      ...prev,
      [field]: value
    }));
  };

  // Handle file upload for meta image
  const handleImageUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    // Validate file type
    if (!file.type.startsWith('image/')) {
      toast.error('Please select a valid image file');
      return;
    }

    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      toast.error('Image size should be less than 5MB');
      return;
    }

    // Create a URL for the uploaded file
    const fileUrl = URL.createObjectURL(file);
    
    // Reset error state
    setImageError(false);
    setImagePreview(fileUrl);
    
    // Update settings state
    setSeoSettings(prev => ({
      ...prev,
      metaImage: fileUrl
    }));
  };

  // Remove uploaded image
  const removeImage = () => {
    setImagePreview(null);
    setImageError(false);
    setSeoSettings(prev => ({
      ...prev,
      metaImage: ''
    }));
  };

  // Handle SEO settings form submission
  const submitSeoSettings = (e: React.FormEvent) => {
    e.preventDefault();
    
    // Client-side validation
    if (!seoSettings.metaKeywords.trim()) {
      toast.error(t('Meta Keywords is required'));
      return;
    }
    
    if (!seoSettings.metaDescription.trim()) {
      toast.error(t('Meta Description is required'));
      return;
    }
    
    if (!seoSettings.metaImage.trim()) {
      toast.error(t('Meta Image is required'));
      return;
    }
    
    // Submit to backend using Inertia
    router.post(route('settings.seo.update'), seoSettings, {
      preserveScroll: true,
      onSuccess: (page) => {
        const successMessage = page.props.flash?.success;
        const errorMessage = page.props.flash?.error;
        
        if (successMessage) {
          toast.success(successMessage);
        } else if (errorMessage) {
          toast.error(errorMessage);
        }
      },
      onError: (errors) => {
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to update SEO settings');
        toast.error(errorMessage);
      }
    });
  };

  return (
    <SettingsSection
      title={t("SEO Settings")}
      description={t("Configure SEO settings to improve your website's search engine visibility")}
      action={
        <Button type="submit" form="seo-settings-form" size="sm">
          <Save className="h-4 w-4 mr-2" />
          {t("Save Changes")}
        </Button>
      }
    >
      <form id="seo-settings-form" onSubmit={submitSeoSettings} className="space-y-6">
        <div className="grid grid-cols-1 gap-6">
          <div className="grid gap-2">
            <Label htmlFor="metaKeywords">{t("Meta Keywords")} <span className="text-red-500">*</span></Label>
            <Input
              id="metaKeywords"
              type="text"
              value={seoSettings.metaKeywords}
              onChange={(e) => handleSeoSettingsChange('metaKeywords', e.target.value)}
              placeholder={t("Enter keywords separated by commas")}
              required
            />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="metaDescription">{t("Meta Description")} <span className="text-red-500">*</span></Label>
            <Textarea
              id="metaDescription"
              value={seoSettings.metaDescription}
              onChange={(e) => handleSeoSettingsChange('metaDescription', e.target.value)}
              placeholder={t("Enter a brief description for search engines (max 160 characters)")}
              maxLength={160}
              rows={3}
              required
            />
            <div className="text-sm text-muted-foreground text-right">
              {seoSettings.metaDescription.length}/160
            </div>
          </div>

          <div className="grid gap-2">
            <Label htmlFor="metaImage">{t("Meta Image")} <span className="text-red-500">*</span></Label>
            <div className="space-y-4">
              {/* Image Preview */}
              {(seoSettings.metaImage || imagePreview) && (
                <div className="border rounded-md p-4 bg-muted/30">
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-sm font-medium">{t("Current Image")}</span>
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      onClick={removeImage}
                      className="h-6 w-6 p-0"
                    >
                      <X className="h-4 w-4" />
                    </Button>
                  </div>
                  <div className="flex items-center justify-center h-32 border rounded">
                    {!imageError ? (
                      <img
                        src={imagePreview || seoSettings.metaImage}
                        alt="Meta Image Preview"
                        className="max-h-full max-w-full object-contain"
                        onError={() => setImageError(true)}
                      />
                    ) : (
                      <div className="text-muted-foreground text-sm">
                        Failed to load image
                      </div>
                    )}
                  </div>
                </div>
              )}

              {/* File Upload */}
              <div className="flex gap-3">
                <div className="flex-1">
                  <div className="relative">
                    <Input
                      id="metaImageUpload"
                      type="file"
                      accept="image/*"
                      className="absolute inset-0 opacity-0 cursor-pointer"
                      onChange={handleImageUpload}
                    />
                    <Button
                      type="button"
                      variant="outline"
                      className="w-full justify-start"
                    >
                      <Upload className="h-4 w-4 mr-2" />
                      {seoSettings.metaImage ? t("Change Image") : t("Upload Image")}
                    </Button>
                  </div>
                </div>
              </div>
              
              <p className="text-xs text-muted-foreground">
                {t("Recommended size: 1200x630px for optimal social media sharing. Max file size: 5MB")}
              </p>
            </div>
          </div>
        </div>
      </form>
    </SettingsSection>
  );
}