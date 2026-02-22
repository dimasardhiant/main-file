import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ArrowLeft, Users, Eye, TrendingUp } from 'lucide-react';
import { format } from 'date-fns';

export default function AnnouncementStatistics() {
  const { t } = useTranslation();
  const { announcement, totalEmployees, viewedCount, viewPercentage, departmentStats, branchStats } = usePage().props as any;

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.announcements.index') },
    { title: t('Announcements'), href: route('hr.announcements.index') },
    { title: t('Statistics') }
  ];

  const pageActions = [
    {
      label: t('Back to Announcements'),
      icon: <ArrowLeft className="h-4 w-4 mr-2" />,
      variant: 'outline',
      onClick: () => router.get(route('hr.announcements.index'))
    }
  ];

  return (
    <PageTemplate
      title={t("Announcement Statistics")}
      url="/hr/announcements/statistics"
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      {/* Announcement Details */}
      <Card className="mb-6">
        <CardHeader>
          <CardTitle className="flex items-center justify-between">
            <span>{announcement.title}</span>
            <div className="flex gap-2">
              {announcement.is_featured && (
                <Badge variant="secondary" className="bg-purple-50 text-purple-700">
                  {t('Featured')}
                </Badge>
              )}
              {announcement.is_high_priority && (
                <Badge variant="secondary" className="bg-red-50 text-red-700">
                  {t('High Priority')}
                </Badge>
              )}
            </div>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
              <p className="text-sm text-gray-600">{t('Category')}</p>
              <p className="font-medium">{announcement.category}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">{t('Target Branch')}</p>
              <p className="font-medium">{announcement.branches?.[0]?.name || t('Company-wide')}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">{t('Target Department')}</p>
              <p className="font-medium">{announcement.departments?.[0]?.name || t('All Departments')}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">{t('Start Date')}</p>
              <p className="font-medium">{announcement.start_date ? (window.appSettings?.formatDateTimeSimple(announcement.start_date, false) || new Date(announcement.start_date).toLocaleString()) : '-'}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">{t('End Date')}</p>
              <p className="font-medium">
                {announcement.end_date ? (window.appSettings?.formatDateTimeSimple(announcement.end_date, false) || new Date(announcement.end_date).toLocaleString()) : t('Ongoing')}
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Overall Statistics */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center">
              <Users className="h-8 w-8 text-blue-500" />
              <div className="ml-4">
                <p className="text-2xl font-bold">{totalEmployees}</p>
                <p className="text-gray-600">{t('Total Employees')}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center">
              <Eye className="h-8 w-8 text-green-500" />
              <div className="ml-4">
                <p className="text-2xl font-bold">{viewedCount}</p>
                <p className="text-gray-600">{t('Views')}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center">
              <TrendingUp className="h-8 w-8 text-purple-500" />
              <div className="ml-4">
                <p className="text-2xl font-bold">{viewPercentage}%</p>
                <p className="text-gray-600">{t('View Rate')}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Target Department Statistics */}
      {departmentStats && departmentStats.length > 0 && (
        <Card className="mb-6">
          <CardHeader>
            <CardTitle>{t('Target Department Statistics')}</CardTitle>
          </CardHeader>
          <CardContent>
            {departmentStats.map((branchData: any, branchIndex: number) => (
              <div key={branchIndex}>
                {branchData.departments.map((dept: any, deptIndex: number) => (
                  <div key={deptIndex} className="flex items-center justify-between p-4 border rounded-lg">
                    <div>
                      <h4 className="font-medium">{dept.department}</h4>
                      <p className="text-sm text-gray-600">
                        {dept.viewed} of {dept.total} employees viewed
                      </p>
                    </div>
                    <div className="text-right">
                      <div className="text-2xl font-bold">{dept.percentage}%</div>
                      <div className="w-24 bg-gray-200 rounded-full h-2 mt-1">
                        <div
                          className="bg-blue-500 h-2 rounded-full"
                          style={{ width: `${dept.percentage}%` }}
                        ></div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            ))}
          </CardContent>
        </Card>
      )}

      {/* Target Branch Statistics */}
      {branchStats && branchStats.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>{t('Target Branch Statistics')}</CardTitle>
          </CardHeader>
          <CardContent>
            {branchStats.map((branch: any, index: number) => (
              <div key={index} className="flex items-center justify-between p-4 border rounded-lg">
                <div>
                  <h4 className="font-medium">{branch.branch}</h4>
                  <p className="text-sm text-gray-600">
                    {branch.viewed} of {branch.total} employees viewed
                  </p>
                </div>
                <div className="text-right">
                  <div className="text-2xl font-bold">{branch.percentage}%</div>
                  <div className="w-24 bg-gray-200 rounded-full h-2 mt-1">
                    <div
                      className="bg-green-500 h-2 rounded-full"
                      style={{ width: `${branch.percentage}%` }}
                    ></div>
                  </div>
                </div>
              </div>
            ))}
          </CardContent>
        </Card>
      )}
    </PageTemplate>
  );
}