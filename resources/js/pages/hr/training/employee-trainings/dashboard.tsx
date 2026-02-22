// pages/hr/training/employee-trainings/dashboard.tsx
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';
import { List, BarChart3 } from 'lucide-react';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { format } from 'date-fns';
import { Progress } from '@/components/ui/progress';

export default function EmployeeTrainingDashboard() {
  const { t } = useTranslation();
  const { statistics, programStats, recentCompletions, upcomingTrainings } = usePage().props as any;
  
  const handleViewList = () => {
    router.get(route('hr.employee-trainings.index'));
  };

  // Define page actions
  const pageActions = [
    {
      label: t('List View'),
      icon: <List className="h-4 w-4 mr-2" />,
      variant: 'outline' as const,
      onClick: handleViewList
    }
  ];

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.employee-trainings.index') },
    { title: t('Training Management'), href: route('hr.employee-trainings.index') },
    { title: t('Employee Trainings'), href: route('hr.employee-trainings.index') },
    { title: t('Dashboard') }
  ];

  return (
    <PageTemplate 
      title={t("Training Dashboard")} 
      url="/hr/training/employee-trainings/dashboard"
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      {/* Training Status Overview */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-lg">{t('Total Trainings')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">{statistics.totalTrainings}</div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-lg">{t('Completed')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-green-600">{statistics.completedTrainings}</div>
            <div className="text-sm text-gray-500 mt-1">
              {statistics.completionRate}% {t('completion rate')}
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-lg">{t('In Progress')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-blue-600">{statistics.inProgressTrainings}</div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-lg">{t('Assigned')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-amber-600">{statistics.assignedTrainings}</div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-lg">{t('Failed')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-red-600">{statistics.failedTrainings}</div>
          </CardContent>
        </Card>
      </div>
      
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {/* Program Completion Rates */}
        <Card>
          <CardHeader>
            <CardTitle>{t('Program Completion Rates')}</CardTitle>
          </CardHeader>
          <CardContent>
            {programStats && programStats.length > 0 ? (
              <div className="space-y-4">
                {programStats.map((program: any, index: number) => (
                  <div key={index}>
                    <div className="flex justify-between mb-1">
                      <span className="font-medium">{program.name}</span>
                      <span>{program.completion_rate}%</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <Progress 
                        value={program.completion_rate} 
                        className="h-2 flex-1"
                      />
                      <span className="text-xs text-gray-500">
                        {program.completed}/{program.total}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-4 text-gray-500">{t('No program data available')}</div>
            )}
          </CardContent>
        </Card>
        
        {/* Recent Completions */}
        <Card>
          <CardHeader>
            <CardTitle>{t('Recent Completions')}</CardTitle>
          </CardHeader>
          <CardContent>
            {recentCompletions && recentCompletions.length > 0 ? (
              <div className="space-y-4">
                {recentCompletions.map((training: any) => (
                  <div key={training.id} className="flex items-start justify-between border-b pb-3">
                    <div>
                      <div className="font-medium">{training.employee?.name}</div>
                      <div className="text-sm text-gray-500">{training.training_program?.name}</div>
                      <div className="text-xs text-gray-500">
                        {t('Completed')}: {window.appSettings?.formatDateTimeSimple(training.completion_date, false) || format(new Date(training.completion_date), 'MMM dd, yyyy')}
                      </div>
                    </div>
                    <div className="text-right">
                      <div className="font-medium">{training.score ? `${training.score}%` : '-'}</div>
                      <div className="text-xs text-gray-500">
                        {training.is_passed ? (
                          <span className="text-green-600">{t('Passed')}</span>
                        ) : (
                          <span className="text-red-600">{t('Failed')}</span>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-4 text-gray-500">{t('No recent completions')}</div>
            )}
          </CardContent>
        </Card>
      </div>
      
      {/* Upcoming Trainings */}
      <Card>
        <CardHeader>
          <CardTitle>{t('Upcoming Trainings')}</CardTitle>
        </CardHeader>
        <CardContent>
          {upcomingTrainings && upcomingTrainings.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {upcomingTrainings.map((training: any) => (
                <Card key={training.id} className="border">
                  <CardHeader className="pb-2">
                    <CardTitle className="text-base">{training.training_program?.name}</CardTitle>
                    <CardDescription>{training.employee?.name}</CardDescription>
                  </CardHeader>
                  <CardContent className="pb-2">
                    <div className="text-sm">
                      <span className="font-medium">{t('Assigned')}:</span> {window.appSettings?.formatDateTimeSimple(training.assigned_date, false) || format(new Date(training.assigned_date), 'MMM dd, yyyy')}
                    </div>
                  </CardContent>
                  <CardFooter>
                    <Button 
                      variant="ghost" 
                      size="sm"
                      className="w-full"
                      onClick={() => router.get(route('hr.employee-trainings.show', training.id))}
                    >
                      {t('View Details')}
                    </Button>
                  </CardFooter>
                </Card>
              ))}
            </div>
          ) : (
            <div className="text-center py-4 text-gray-500">{t('No upcoming trainings')}</div>
          )}
        </CardContent>
      </Card>
    </PageTemplate>
  );
}