import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { useState } from 'react';
import { Save, Bell } from 'lucide-react';
import { SettingsSection } from '@/components/settings-section';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useTranslation } from 'react-i18next';

// Define notification types by module
const notificationModules = [
  {
    id: 'general',
    name: 'General',
    icon: '🔔',
    notifications: [
      { id: 'system_update', name: 'System Updates', enabled: true },
      { id: 'security_alert', name: 'Security Alerts', enabled: true },
      { id: 'maintenance', name: 'Scheduled Maintenance', enabled: false },
      { id: 'login_activity', name: 'Login Activity', enabled: true },
      { id: 'password_reset', name: 'Password Reset', enabled: true }
    ]
  },
  {
    id: 'accounting',
    name: 'Accounting',
    icon: '💰',
    notifications: [
      { id: 'invoice_created', name: 'Invoice Created', enabled: true },
      { id: 'payment_received', name: 'Payment Received', enabled: true },
      { id: 'payment_reminder', name: 'Payment Reminder', enabled: true },
      { id: 'bill_due', name: 'Bill Due', enabled: false },
      { id: 'expense_approval', name: 'Expense Approval', enabled: true }
    ]
  },
  {
    id: 'appointment',
    name: 'Appointment',
    icon: '📅',
    notifications: [
      { id: 'appointment_created', name: 'Appointment Created', enabled: true },
      { id: 'appointment_reminder', name: 'Appointment Reminder', enabled: true },
      { id: 'appointment_cancelled', name: 'Appointment Cancelled', enabled: true },
      { id: 'appointment_rescheduled', name: 'Appointment Rescheduled', enabled: true }
    ]
  },
  {
    id: 'cmms',
    name: 'CMMS',
    icon: '🔧',
    notifications: [
      { id: 'maintenance_due', name: 'Maintenance Due', enabled: true },
      { id: 'work_order_created', name: 'Work Order Created', enabled: true },
      { id: 'work_order_assigned', name: 'Work Order Assigned', enabled: true },
      { id: 'work_order_completed', name: 'Work Order Completed', enabled: false },
      { id: 'inventory_low', name: 'Inventory Low', enabled: true }
    ]
  },
  {
    id: 'lms',
    name: 'LMS',
    icon: '📚',
    notifications: [
      { id: 'course_enrollment', name: 'Course Enrollment', enabled: true },
      { id: 'course_completion', name: 'Course Completion', enabled: true },
      { id: 'quiz_results', name: 'Quiz Results', enabled: true },
      { id: 'certificate_issued', name: 'Certificate Issued', enabled: true },
      { id: 'new_course_available', name: 'New Course Available', enabled: false }
    ]
  },
  {
    id: 'crm',
    name: 'CRM',
    icon: '👥',
    notifications: [
      { id: 'lead_created', name: 'Lead Created', enabled: true },
      { id: 'deal_won', name: 'Deal Won', enabled: true },
      { id: 'deal_lost', name: 'Deal Lost', enabled: false },
      { id: 'task_assigned', name: 'Task Assigned', enabled: true },
      { id: 'follow_up_reminder', name: 'Follow-up Reminder', enabled: true }
    ]
  },
  {
    id: 'hrm',
    name: 'HRM',
    icon: '👔',
    notifications: [
      { id: 'leave_request', name: 'Leave Request', enabled: true },
      { id: 'leave_approval', name: 'Leave Approval', enabled: true },
      { id: 'attendance_reminder', name: 'Attendance Reminder', enabled: false },
      { id: 'payroll_processed', name: 'Payroll Processed', enabled: true },
      { id: 'performance_review', name: 'Performance Review', enabled: true }
    ]
  },
  {
    id: 'project',
    name: 'Project',
    icon: '📊',
    notifications: [
      { id: 'project_created', name: 'Project Created', enabled: true },
      { id: 'task_assigned', name: 'Task Assigned', enabled: true },
      { id: 'milestone_completed', name: 'Milestone Completed', enabled: true },
      { id: 'project_deadline', name: 'Project Deadline', enabled: true },
      { id: 'budget_exceeded', name: 'Budget Exceeded', enabled: false }
    ]
  }
];

export default function EmailNotificationSettings() {
  const { t } = useTranslation();
  const [modules, setModules] = useState(notificationModules);
  const [activeTab, setActiveTab] = useState('general');

  // Handle notification toggle
  const handleNotificationToggle = (moduleId: string, notificationId: string, enabled: boolean) => {
    setModules(prevModules => 
      prevModules.map(module => 
        module.id === moduleId 
          ? {
              ...module,
              notifications: module.notifications.map(notification => 
                notification.id === notificationId 
                  ? { ...notification, enabled } 
                  : notification
              )
            }
          : module
      )
    );
  };

  // Handle toggle all notifications for a module
  const handleToggleAllModule = (moduleId: string, enabled: boolean) => {
    setModules(prevModules => 
      prevModules.map(module => 
        module.id === moduleId 
          ? {
              ...module,
              notifications: module.notifications.map(notification => 
                ({ ...notification, enabled })
              )
            }
          : module
      )
    );
  };

  // Count enabled notifications for a module
  const getEnabledCount = (moduleId: string) => {
    const module = modules.find(m => m.id === moduleId);
    if (!module) return 0;
    return module.notifications.filter(n => n.enabled).length;
  };

  // Handle form submission
  const submitNotificationSettings = (e: React.FormEvent) => {
    e.preventDefault();
    // Example API call: notificationSettingsPatch(route('settings.notifications.update'), { modules });
  };

  return (
    <SettingsSection
      title={t("Email Notification Settings")}
      description={t("Configure which email notifications are sent for different modules")}
      action={
        <Button type="submit" form="notification-settings-form" size="sm">
          <Save className="h-4 w-4 mr-2" />
          {t("Save Changes")}
        </Button>
      }
    >
      <form id="notification-settings-form" onSubmit={submitNotificationSettings}>
        <div className="flex flex-col md:flex-row gap-6">
          {/* Module Navigation */}
          <div className="md:w-64 flex-shrink-0">
            <div className="sticky top-24">
              <div className="text-sm font-medium mb-2 flex items-center">
                <Bell className="h-4 w-4 mr-2" />
                {t("Modules")}
              </div>
              <ScrollArea className="h-[calc(100vh-12rem)]">
                <div className="space-y-1 pr-4">
                  {modules.map(module => (
                    <Button
                      key={module.id}
                      variant="ghost"
                      className="w-full justify-start"
                      onClick={() => setActiveTab(module.id)}
                      data-active={activeTab === module.id}
                    >
                      <span className="mr-2">{module.icon}</span>
                      <span className="flex-1 text-left">{module.name}</span>
                      <Badge variant="outline" className="ml-2">
                        {getEnabledCount(module.id)}/{module.notifications.length}
                      </Badge>
                    </Button>
                  ))}
                </div>
              </ScrollArea>
            </div>
          </div>

          {/* Notification Settings */}
          <div className="flex-1">
            <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
              <TabsList className="mb-4 w-full overflow-x-auto flex-nowrap">
                {modules.map(module => (
                  <TabsTrigger key={module.id} value={module.id} className="flex items-center gap-2">
                    <span>{module.icon}</span>
                    {module.name}
                  </TabsTrigger>
                ))}
              </TabsList>

              {modules.map(module => (
                <TabsContent key={module.id} value={module.id} className="space-y-4">
                  <div className="flex items-center justify-between p-4 border rounded-md bg-muted/30">
                    <div>
                      <h3 className="text-base font-medium flex items-center gap-2">
                        <span>{module.icon}</span>
                        {module.name} {t("Notifications")}
                      </h3>
                      <p className="text-sm text-muted-foreground mt-1">
                        {getEnabledCount(module.id)} of {module.notifications.length} {t("notifications enabled")}
                      </p>
                    </div>
                    <div className="flex items-center gap-2">
                      <Label htmlFor={`toggle-all-${module.id}`} className="text-sm">
                        {t("Toggle All")}
                      </Label>
                      <Switch
                        id={`toggle-all-${module.id}`}
                        checked={getEnabledCount(module.id) === module.notifications.length}
                        onCheckedChange={(checked) => handleToggleAllModule(module.id, checked)}
                      />
                    </div>
                  </div>

                  <div className="grid gap-4">
                    {module.notifications.map(notification => (
                      <div 
                        key={notification.id} 
                        className="flex items-center justify-between p-3 border rounded-md hover:bg-muted/20"
                      >
                        <Label 
                          htmlFor={`${module.id}-${notification.id}`} 
                          className="cursor-pointer flex-1"
                        >
                          {notification.name}
                        </Label>
                        <Switch
                          id={`${module.id}-${notification.id}`}
                          checked={notification.enabled}
                          onCheckedChange={(checked) => 
                            handleNotificationToggle(module.id, notification.id, checked)
                          }
                        />
                      </div>
                    ))}
                  </div>
                </TabsContent>
              ))}
            </Tabs>
          </div>
        </div>
      </form>
    </SettingsSection>
  );
}