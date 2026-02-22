import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { useState, useEffect } from 'react';
import { Save, Clock } from 'lucide-react';
import { SettingsSection } from '@/components/settings-section';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from 'react-i18next';
import { router, usePage } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';

interface WorkingDaysSettingsProps {
  settings?: Record<string, any>;
}

export default function WorkingDaysSettings({ settings = {} }: WorkingDaysSettingsProps) {
  const { t } = useTranslation();
  const { globalSettings } = usePage().props as any;


  const [workingDays, setWorkingDays] = useState({
    monday: false,
    tuesday: false,
    wednesday: false,
    thursday: false,
    friday: false,
    saturday: false,
    sunday: false,
  });

  useEffect(() => {
    fetch(route('settings.working-days.get'))
      .then(response => response.json())
      .then(data => {
        setWorkingDays({
          monday: data.working_day_monday || false,
          tuesday: data.working_day_tuesday || false,
          wednesday: data.working_day_wednesday || false,
          thursday: data.working_day_thursday || false,
          friday: data.working_day_friday || false,
          saturday: data.working_day_saturday || false,
          sunday: data.working_day_sunday || false,
        });
      })
      .catch(() => {
        // If no working days found in database, keep all disabled (default false)
        setWorkingDays({
          monday: false,
          tuesday: false,
          wednesday: false,
          thursday: false,
          friday: false,
          saturday: false,
          sunday: false,
        });
      });
  }, []);

  const handleWorkingDayChange = (day: string, checked: boolean) => {
    setWorkingDays(prev => ({
      ...prev,
      [day]: checked
    }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (!globalSettings?.is_demo) {
      toast.loading(t('Saving working days settings...'));
    }

    const selectedDays = Object.entries(workingDays)
      .filter(([day, isSelected]) => isSelected)
      .map(([day]) => day);

    router.post(route('settings.working-days.update'), {
      working_days: selectedDays,
    }, {
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
          toast.success(t('Working days settings saved successfully'));
        }
      },
      onError: (errors) => {
        if (!globalSettings?.is_demo) {
          toast.dismiss();
        }
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save working days settings');
        toast.error(errorMessage);
      }
    });
  };

  const days = [
    { key: 'monday', label: t('Monday') },
    { key: 'tuesday', label: t('Tuesday') },
    { key: 'wednesday', label: t('Wednesday') },
    { key: 'thursday', label: t('Thursday') },
    { key: 'friday', label: t('Friday') },
    { key: 'saturday', label: t('Saturday') },
    { key: 'sunday', label: t('Sunday') },
  ];

  return (
    <SettingsSection
      title={t("Working Days Settings")}
      description={t("Configure which days are working days for your organization")}
      action={
        <Button type="submit" form="working-days-form" size="sm">
          <Save className="h-4 w-4 mr-2" />
          {t("Save Changes")}
        </Button>
      }
    >
      <Card>
        <CardContent className="pt-6">
          <form id="working-days-form" onSubmit={handleSubmit}>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {days.map((day) => (
                <div key={day.key} className="flex items-center space-x-3 p-4 border rounded-lg">
                  <div className="flex items-center gap-2">
                    <Clock className="h-4 w-4 text-muted-foreground" />
                    <Label htmlFor={day.key} className="font-medium">
                      {day.label}
                    </Label>
                  </div>
                  <Switch
                    id={day.key}
                    checked={workingDays[day.key as keyof typeof workingDays]}
                    onCheckedChange={(checked) => handleWorkingDayChange(day.key, checked)}
                  />
                </div>
              ))}
            </div>
          </form>
        </CardContent>
      </Card>
    </SettingsSection>
  );
}