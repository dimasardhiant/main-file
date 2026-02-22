import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useState, useEffect } from 'react';
import { Save, Key, AlertCircle } from 'lucide-react';
import { SettingsSection } from '@/components/settings-section';
import { Card, CardContent } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useTranslation } from 'react-i18next';
import { router, usePage } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';
import { hasPermission } from '@/utils/permissions';

interface ZektoSettingsProps {
  settings?: Record<string, any>;
}

export default function ZektoSettings({ settings = {} }: ZektoSettingsProps) {
  const { t } = useTranslation();
  const { globalSettings } = usePage().props as any;
  const canManageBiometric = hasPermission('manage-biomatric-attedance-settings');

  const [zektoSettings, setZektoSettings] = useState({
    zkteco_api_url: '',
    zkteco_username: '',
    zkteco_password: '',
    zkteco_auth_token: '',
  });

  const [isGeneratingToken, setIsGeneratingToken] = useState(false);

  useEffect(() => {
    setZektoSettings({
      zkteco_api_url: settings.zkteco_api_url || '',
      zkteco_username: settings.zkteco_username || '',
      zkteco_password: settings.zkteco_password || '',
      zkteco_auth_token: settings.zkteco_auth_token || '',
    });
  }, [settings]);

  const handleInputChange = (field: string, value: string) => {
    setZektoSettings(prev => ({
      ...prev,
      [field]: value
    }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (!globalSettings?.is_demo) {
      toast.loading(t('Saving Zekto settings...'));
    }

    router.post(route('settings.zekto.update'), zektoSettings, {
      preserveScroll: true,
      onSuccess: (page) => {
        if (!globalSettings?.is_demo) {
          toast.dismiss();
        }
        const successMessage = page.props.flash?.success;
        const errorMessage = page.props.flash?.error;

        if (successMessage) {
          toast.success(successMessage);
        } else if (errorMessage) {
          toast.error(errorMessage);
        } else {
          toast.success(t('Zekto settings saved successfully'));
        }
      },
      onError: (errors) => {
        if (!globalSettings?.is_demo) {
          toast.dismiss();
        }
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save Zekto settings');
        toast.error(errorMessage);
      }
    });
  };

  const handleGenerateToken = () => {
    if (!zektoSettings.zkteco_api_url || !zektoSettings.zkteco_username || !zektoSettings.zkteco_password) {
      toast.error(t('Please fill in API URL, Username, and Password before generating token'));
      return;
    }

    setIsGeneratingToken(true);
    if (!globalSettings?.is_demo) {
      toast.loading(t('Generating auth token...'));
    }

    router.post(route('settings.zekto.generate-token'), {
      zkteco_api_url: zektoSettings.zkteco_api_url,
      zkteco_username: zektoSettings.zkteco_username,
      zkteco_password: zektoSettings.zkteco_password,
    }, {
      preserveScroll: true,
      onSuccess: (page) => {
        setIsGeneratingToken(false);
        if (!globalSettings?.is_demo) {
          toast.dismiss();
        }

        const successMessage = page.props.flash?.success;
        const errorMessage = page.props.flash?.error;
        const token = page.props.flash?.token;

        if (successMessage && token) {
          setZektoSettings(prev => ({
            ...prev,
            zkteco_auth_token: token
          }));
          toast.success(successMessage);
        } else if (errorMessage) {
          toast.error(errorMessage);
        }
      },
      onError: (errors) => {
        setIsGeneratingToken(false);
        if (!globalSettings?.is_demo) {
          toast.dismiss();
        }
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to generate auth token');
        toast.error(errorMessage);
      }
    });
  };

  return (
    <SettingsSection
      title={t("Zekto Settings")}
      description={t("Configure ZKTeco biometric attendance system integration")}
      action={
        <Button type="submit" form="zekto-settings-form" size="sm">
          <Save className="h-4 w-4 mr-2" />
          {t("Save Changes")}
        </Button>
      }
    >
      <Card>
        <CardContent className="pt-6">
          <Alert className="mb-6 border-blue-200 bg-blue-50 text-blue-800">
            <AlertCircle className="h-4 w-4 text-blue-600 self-center" />
            <AlertDescription className="font-medium space-y-2 flex-1">
              <div>{t("Note that you can use the biometric attendance system only if you are using the ZKTeco machine for biometric attendance.")}</div>
              <div>{t("If an employee has multiple entries in a single day, the first entry will be considered as clock-in time and the last entry will be considered as clock-out time.")}</div>
            </AlertDescription>
          </Alert>

          <form id="zekto-settings-form" onSubmit={handleSubmit} className="space-y-6">
            <div className="grid grid-cols-1 gap-6">
              <div className="grid gap-2">
                <Label htmlFor="zkteco_api_url">
                  {t("ZKTeco Api URL")} <span className="text-red-500">*</span>
                </Label>
                <Input
                  id="zkteco_api_url"
                  type="text"
                  placeholder="http://110.78.645.123:8080"
                  value={zektoSettings.zkteco_api_url}
                  onChange={(e) => handleInputChange('zkteco_api_url', e.target.value)}
                  required
                />
                <p className="text-sm text-muted-foreground">
                  {t("Example")}: http://110.78.645.123:8080
                </p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="grid gap-2">
                  <Label htmlFor="zkteco_username">
                    {t("Username")} <span className="text-red-500">*</span>
                  </Label>
                  <Input
                    id="zkteco_username"
                    type="text"
                    placeholder="Zekto User Name"
                    value={zektoSettings.zkteco_username}
                    onChange={(e) => handleInputChange('zkteco_username', e.target.value)}
                    required
                  />
                </div>

                <div className="grid gap-2">
                  <Label htmlFor="zkteco_password">
                    {t("Password")} <span className="text-red-500">*</span>
                  </Label>
                  <Input
                    id="zkteco_password"
                    type="password"
                    placeholder="Zekto Password"
                    value={zektoSettings.zkteco_password}
                    onChange={(e) => handleInputChange('zkteco_password', e.target.value)}
                    required
                  />
                </div>
              </div>

              <div className="grid gap-2">
                <Label htmlFor="zkteco_auth_token">{t("Auth Token")}</Label>
                <Textarea
                  id="zkteco_auth_token"
                  placeholder="Token will be generated automatically using API credentials"
                  value={zektoSettings.zkteco_auth_token}
                  readOnly
                  disabled
                  rows={4}
                  className="resize-none bg-muted"
                />
                <p className="text-sm text-muted-foreground">
                  {t("This token is automatically generated using your API credentials above.")}
                </p>
              </div>

              <div className="flex justify-end">
                <Button
                  type="button"
                  onClick={handleGenerateToken}
                  disabled={isGeneratingToken || !zektoSettings.zkteco_api_url || !zektoSettings.zkteco_username || !zektoSettings.zkteco_password || !canManageBiometric}
                  className="bg-green-600 hover:bg-green-700"
                >
                  <Key className="h-4 w-4 mr-2" />
                  {isGeneratingToken ? t("Generating...") : t("Generate Token")}
                </Button>
              </div>
            </div>
          </form>
        </CardContent>
      </Card>
    </SettingsSection>
  );
}