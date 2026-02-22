// pages/hr/announcements/show.tsx
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from 'react-i18next';
import { ArrowLeft, Download, BarChart, Edit, Trash } from 'lucide-react';
import { format } from 'date-fns';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { hasPermission } from '@/utils/authorization';
import { useState } from 'react';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';

export default function AnnouncementShow() {
  const { t } = useTranslation();
  const { auth, announcement, viewCount, totalEmployees, viewPercentage } = usePage().props as any;
  const permissions = auth?.permissions || [];

  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);

  // Handle back to list
  const handleBackToList = () => {
    router.get(route('hr.announcements.index'));
  };

  // Handle back to dashboard
  const handleBackToDashboard = () => {
    router.get(route('hr.announcements.dashboard'));
  };

  // Handle edit
  const handleEdit = () => {
    router.get(route('hr.announcements.index'), {}, {
      onSuccess: () => {
        // This is a hack to trigger the edit modal in the index page
        // In a real implementation, you might want to use a more elegant approach
        setTimeout(() => {
          const editButton = document.querySelector(`[data-announcement-id="${announcement.id}"][data-action="edit"]`);
          if (editButton) {
            editButton.dispatchEvent(new MouseEvent('click', { bubbles: true }));
          }
        }, 500);
      }
    });
  };

  // Handle view statistics
  const handleViewStatistics = () => {
    router.get(route('hr.announcements.statistics', announcement.id));
  };

  // Handle download attachment
  const handleDownloadAttachment = () => {
    window.open(route('hr.announcements.download-attachment', announcement.id), '_blank');
  };

  // Handle delete
  const handleDelete = () => {
    setIsDeleteModalOpen(true);
  };

  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting announcement...'));

    router.delete(route('hr.announcements.destroy', announcement.id), {
      onSuccess: (page) => {
        setIsDeleteModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        } else {
          toast.success(t('Announcement deleted successfully'));
        }
        router.get(route('hr.announcements.index'));
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(`Failed to delete announcement: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };

  // Define page actions
  const pageActions = [];

  // Add the "Back to List" button
  pageActions.push({
    label: t('Back to List'),
    icon: <ArrowLeft className="h-4 w-4 mr-2" />,
    variant: 'outline',
    onClick: handleBackToList
  });

  // Add the "Back to Dashboard" button
  pageActions.push({
    label: t('Dashboard'),
    icon: <ArrowLeft className="h-4 w-4 mr-2" />,
    variant: 'outline',
    onClick: handleBackToDashboard
  });

  // Add the "View Statistics" button if user has permission
  if (hasPermission(permissions, 'view-announcements')) {
    pageActions.push({
      label: t('Statistics'),
      icon: <BarChart className="h-4 w-4 mr-2" />,
      variant: 'outline',
      onClick: handleViewStatistics
    });
  }

  // Add the "Edit" button if user has permission
  if (hasPermission(permissions, 'edit-announcements')) {
    pageActions.push({
      label: t('Edit'),
      icon: <Edit className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: handleEdit
    });
  }

  // Add the "Delete" button if user has permission
  if (hasPermission(permissions, 'delete-announcements')) {
    pageActions.push({
      label: t('Delete'),
      icon: <Trash className="h-4 w-4 mr-2" />,
      variant: 'destructive',
      onClick: handleDelete
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.announcements.index') },
    { title: t('Announcements'), href: route('hr.announcements.index') },
    { title: announcement.title }
  ];

  // Get category class
  const categoryClasses = {
    'company news': 'bg-blue-50 text-blue-700 ring-blue-600/20',
    'policy updates': 'bg-purple-50 text-purple-700 ring-purple-600/20',
    'events': 'bg-green-50 text-green-700 ring-green-600/20',
    'HR': 'bg-amber-50 text-amber-700 ring-amber-600/20',
    'IT updates': 'bg-indigo-50 text-indigo-700 ring-indigo-600/20'
  };

  const categoryClass = categoryClasses[announcement.category] || 'bg-gray-50 text-gray-700 ring-gray-600/20';

  return (
    <PageTemplate
      title={announcement.title}
      url={`/hr/announcements/${announcement.id}`}
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      {/* Announcement header */}
      <Card className="mb-6">
        <CardHeader>
          <div className="flex flex-wrap justify-between items-start gap-2">
            <div>
              <CardTitle className="text-2xl">{announcement.title}</CardTitle>
              <CardDescription className="mt-2 text-base">
                {announcement.description}
              </CardDescription>
            </div>
            <div className="flex flex-wrap gap-2">
              <span className={`inline-flex items-center rounded-md px-2 py-1 text-sm font-medium ring-1 ring-inset ${categoryClass}`}>
                {announcement.category.charAt(0).toUpperCase() + announcement.category.slice(1)}
              </span>
              {announcement.is_featured && (
                <Badge variant="secondary" className="bg-purple-50 text-purple-700 hover:bg-purple-50">
                  {t('Featured')}
                </Badge>
              )}
              {announcement.is_high_priority && (
                <Badge variant="secondary" className="bg-red-50 text-red-700 hover:bg-red-50">
                  {t('High Priority')}
                </Badge>
              )}
            </div>
          </div>
          <div className="flex flex-wrap gap-4 mt-4 text-sm text-gray-500">
            <div>
              <span className="font-medium">{t('Start Date')}:</span> {announcement.start_date ? (window.appSettings?.formatDateTimeSimple(announcement.start_date, false) || new Date(announcement.start_date).toLocaleString()) : '-'}
            </div>
            {announcement.end_date && (
              <div>
                <span className="font-medium">{t('End Date')}:</span> {announcement.end_date ? (window.appSettings?.formatDateTimeSimple(announcement.end_date,false) || new Date(announcement.end_date).toLocaleString()) : '-'}
              </div>
            )}
            <div>
              <span className="font-medium">{t('Audience')}:</span> {announcement.is_company_wide ? t('Company-wide') : t('Targeted')}
            </div>
          </div>
        </CardHeader>
      </Card>

      {/* Announcement content */}
      <Card className="mb-6">
        <CardContent className="pt-6">
          <div className="prose max-w-none dark:prose-invert" dangerouslySetInnerHTML={{ __html: announcement.content }} />
        </CardContent>
        {announcement.attachments && (
          <CardFooter>
            <Button
              variant="outline"
              onClick={handleDownloadAttachment}
              className="flex items-center"
            >
              <Download className="h-4 w-4 mr-2" />
              {t('Download Attachment')}
            </Button>
          </CardFooter>
        )}
      </Card>

      {/* Audience details if not company-wide */}
      {!announcement.is_company_wide && (announcement.departments.length > 0 || announcement.branches.length > 0) && (
        <Card className="mb-6">
          <CardHeader>
            <CardTitle>{t('Target Audience')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {announcement.departments.length > 0 && (
                <div>
                  <h3 className="text-lg font-medium mb-2">{t('Departments')}</h3>
                  <div className="flex flex-wrap gap-2">
                    {announcement.departments.map((dept: any) => (
                      <Badge key={dept.id} variant="outline">
                        {dept.name}
                      </Badge>
                    ))}
                  </div>
                </div>
              )}

              {announcement.branches.length > 0 && (
                <div>
                  <h3 className="text-lg font-medium mb-2">{t('Branches')}</h3>
                  <div className="flex flex-wrap gap-2">
                    {announcement.branches.map((branch: any) => (
                      <Badge key={branch.id} variant="outline">
                        {branch.name}
                      </Badge>
                    ))}
                  </div>
                </div>
              )}
            </div>
          </CardContent>
        </Card>
      )}

      {/* View statistics */}
      {hasPermission(permissions, 'view-announcements') && (
        <Card>
          <CardHeader>
            <CardTitle>{t('Engagement Statistics')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-6">
              <div>
                <div className="flex justify-between mb-2">
                  <span>{t('Views')}</span>
                  <span>{viewCount} / {totalEmployees} ({viewPercentage}%)</span>
                </div>
                <Progress value={viewPercentage} className="h-2" />
              </div>

              <div className="flex justify-center">
                <Button
                  variant="outline"
                  onClick={handleViewStatistics}
                  className="flex items-center"
                >
                  <BarChart className="h-4 w-4 mr-2" />
                  {t('View Detailed Statistics')}
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={announcement.title}
        entityName="announcement"
      />
    </PageTemplate>
  );
}