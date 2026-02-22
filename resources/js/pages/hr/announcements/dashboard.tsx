// pages/hr/announcements/dashboard.tsx
import { useState, useEffect } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTranslation } from 'react-i18next';
import { List, Bell, AlertTriangle, Star, Calendar } from 'lucide-react';
import { format } from 'date-fns';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import { hasPermission } from '@/utils/authorization';

export default function AnnouncementDashboard() {
  const { t } = useTranslation();
  const { 
    auth, 
    allAnnouncements, 
    featuredAnnouncements, 
    highPriorityAnnouncements, 
    upcomingAnnouncements,
    categories,
    departments,
    branches,
    employee,
    filters = {} 
  } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  // State
  const [readAnnouncements, setReadAnnouncements] = useState<number[]>([]);
  
  // Initialize read announcements from viewed_by
  useEffect(() => {
    if (employee && allAnnouncements) {
      const read = allAnnouncements
        .filter((announcement: any) => 
          announcement.viewed_by && 
          announcement.viewed_by.some((view: any) => view.id === employee.id)
        )
        .map((announcement: any) => announcement.id);
      
      setReadAnnouncements(read);
    }
  }, [allAnnouncements, employee]);
  

  
  // Handle view announcement
  const handleViewAnnouncement = (announcement: any) => {
    // Mark as read if employee
    if (employee && !readAnnouncements.includes(announcement.id)) {
      fetch(route('hr.announcements.mark-as-read', announcement.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          setReadAnnouncements([...readAnnouncements, announcement.id]);
        }
      });
    }
    
    router.get(route('hr.announcements.show', announcement.id));
  };
  
  // Handle back to list view
  const handleBackToList = () => {
    router.get(route('hr.announcements.index'));
  };
  
  // Handle create new announcement
  const handleCreateAnnouncement = () => {
    router.get(route('hr.announcements.index'), {}, {
      onSuccess: () => {
        // This is a hack to trigger the create modal in the index page
        // In a real implementation, you might want to use a more elegant approach
        setTimeout(() => {
          document.querySelector('[data-action="create-announcement"]')?.dispatchEvent(
            new MouseEvent('click', { bubbles: true })
          );
        }, 500);
      }
    });
  };
  
  // Define page actions
  const pageActions = [];
  
  // Add the "List View" button
  pageActions.push({
    label: t('List View'),
    icon: <List className="h-4 w-4 mr-2" />,
    variant: 'outline',
    onClick: handleBackToList
  });
  
  // Add the "Create Announcement" button if user has permission
  if (hasPermission(permissions, 'create-announcements')) {
    pageActions.push({
      label: t('Create Announcement'),
      icon: <Bell className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: handleCreateAnnouncement
    });
  }
  
  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.announcements.index') },
    { title: t('Announcements'), href: route('hr.announcements.index') },
    { title: t('Dashboard') }
  ];
  

  
  // Render announcement card
  const renderAnnouncementCard = (announcement: any, isHighPriority = false) => {
    const isRead = readAnnouncements.includes(announcement.id);
    
    const categoryClasses = {
      'company news': 'bg-blue-50 text-blue-700 ring-blue-600/20',
      'policy updates': 'bg-purple-50 text-purple-700 ring-purple-600/20',
      'events': 'bg-green-50 text-green-700 ring-green-600/20',
      'HR': 'bg-amber-50 text-amber-700 ring-amber-600/20',
      'IT updates': 'bg-indigo-50 text-indigo-700 ring-indigo-600/20'
    };
    
    const categoryClass = categoryClasses[announcement.category] || 'bg-gray-50 text-gray-700 ring-gray-600/20';
    
    return (
      <Card 
        key={announcement.id} 
        className={`mb-4 ${isHighPriority ? 'border-red-300 bg-red-50' : ''} ${!isRead && employee ? 'border-l-4 border-l-blue-500' : ''}`}
      >
        <CardHeader className="pb-2">
          <div className="flex justify-between items-start">
            <div>
              <CardTitle className="text-lg">{announcement.title}</CardTitle>
              <CardDescription className="mt-1">
                {announcement.description}
              </CardDescription>
            </div>
            <div className="flex flex-col items-end space-y-1">
              <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${categoryClass}`}>
                {announcement.category.charAt(0).toUpperCase() + announcement.category.slice(1)}
              </span>
              {announcement.is_featured && (
                <Badge variant="secondary" className="bg-purple-50 text-purple-700 hover:bg-purple-50">
                  <Star className="h-3 w-3 mr-1" /> {t('Featured')}
                </Badge>
              )}
              {announcement.is_high_priority && (
                <Badge variant="secondary" className="bg-red-50 text-red-700 hover:bg-red-50">
                  <AlertTriangle className="h-3 w-3 mr-1" /> {t('High Priority')}
                </Badge>
              )}
            </div>
          </div>
        </CardHeader>
        <CardContent className="pb-2">
          <div className="text-sm text-gray-500 mb-2">
            {announcement.start_date && (
              <div className="flex items-center">
                <Calendar className="h-4 w-4 mr-1" />
                {window.appSettings?.formatDateTimeSimple(announcement.start_date, false) || format(new Date(announcement.start_date), 'MMM dd, yyyy')}
                {announcement.end_date && ` - ${window.appSettings?.formatDateTimeSimple(announcement.end_date, false) || format(new Date(announcement.end_date), 'MMM dd, yyyy')}`}
              </div>
            )}
          </div>
          <div className="line-clamp-3 text-sm">
            {announcement.content.replace(/<[^>]*>?/gm, ' ').substring(0, 200)}...
          </div>
        </CardContent>
        <CardFooter>
          <Button 
            variant="default" 
            size="sm" 
            onClick={() => handleViewAnnouncement(announcement)}
          >
            {t('Read More')}
          </Button>
        </CardFooter>
      </Card>
    );
  };
  
  return (
    <PageTemplate 
      title={t("Announcement Dashboard")} 
      url="/hr/announcements/dashboard"
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >

      
      {/* Tabs */}
      <Tabs defaultValue="all" className="w-full">
        <TabsList className="mb-4">
          <TabsTrigger value="all">{t('All Announcements')}</TabsTrigger>
          <TabsTrigger value="high-priority">{t('High Priority')}</TabsTrigger>
          <TabsTrigger value="featured">{t('Featured')}</TabsTrigger>
          <TabsTrigger value="upcoming">{t('Upcoming')}</TabsTrigger>
        </TabsList>
        
        <TabsContent value="all" className="mt-0">
          {allAnnouncements && allAnnouncements.length > 0 ? (
            <div className="space-y-4">
              {allAnnouncements.map((announcement: any) => 
                renderAnnouncementCard(announcement, announcement.is_high_priority)
              )}
            </div>
          ) : (
            <Card>
              <CardContent className="pt-6 text-center">
                <p>{t('No announcements found.')}</p>
              </CardContent>
            </Card>
          )}
        </TabsContent>
        
        <TabsContent value="high-priority" className="mt-0">
          {highPriorityAnnouncements && highPriorityAnnouncements.length > 0 ? (
            <div className="space-y-4">
              {highPriorityAnnouncements.map((announcement: any) => 
                renderAnnouncementCard(announcement, true)
              )}
            </div>
          ) : (
            <Card>
              <CardContent className="pt-6 text-center">
                <p>{t('No high priority announcements found.')}</p>
              </CardContent>
            </Card>
          )}
        </TabsContent>
        
        <TabsContent value="featured" className="mt-0">
          {featuredAnnouncements && featuredAnnouncements.length > 0 ? (
            <div className="space-y-4">
              {featuredAnnouncements.map((announcement: any) => 
                renderAnnouncementCard(announcement, announcement.is_high_priority)
              )}
            </div>
          ) : (
            <Card>
              <CardContent className="pt-6 text-center">
                <p>{t('No featured announcements found.')}</p>
              </CardContent>
            </Card>
          )}
        </TabsContent>
        
        <TabsContent value="upcoming" className="mt-0">
          {upcomingAnnouncements.length > 0 ? (
            <div className="space-y-4">
              {upcomingAnnouncements.map((announcement: any) => 
                renderAnnouncementCard(announcement, announcement.is_high_priority)
              )}
            </div>
          ) : (
            <Card>
              <CardContent className="pt-6 text-center">
                <p>{t('No upcoming announcements found.')}</p>
              </CardContent>
            </Card>
          )}
        </TabsContent>
      </Tabs>
    </PageTemplate>
  );
}