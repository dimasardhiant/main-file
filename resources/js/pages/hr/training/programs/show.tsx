// pages/hr/training/programs/show.tsx
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from 'react-i18next';
import { ArrowLeft, Edit, Users, Calendar, BarChart } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';

export default function TrainingProgramShow() {
  const { t } = useTranslation();
  const { trainingProgram, statistics } = usePage().props as any;
  
  const handleBack = () => {
    router.get(route('hr.training-programs.index'));
  };
  
  const handleEdit = () => {
    router.get(route('hr.training-programs.index'), {}, {
      onSuccess: () => {
        // Trigger edit modal - this would need to be implemented
        // For now, just redirect back to index
      }
    });
  };
  
  const pageActions = [
    {
      label: t('Back to List'),
      icon: <ArrowLeft className="h-4 w-4 mr-2" />,
      variant: 'outline' as const,
      onClick: handleBack
    },
    {
      label: t('Edit Program'),
      icon: <Edit className="h-4 w-4 mr-2" />,
      variant: 'default' as const,
      onClick: handleEdit
    }
  ];

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.training-programs.index') },
    { title: t('Training Programs'), href: route('hr.training-programs.index') },
    { title: trainingProgram.name }
  ];

  const statusClasses = {
    'draft': 'bg-gray-50 text-gray-700 ring-gray-600/20',
    'active': 'bg-green-50 text-green-700 ring-green-600/20',
    'completed': 'bg-blue-50 text-blue-700 ring-blue-600/20',
    'cancelled': 'bg-red-50 text-red-700 ring-red-600/20'
  };

  return (
    <PageTemplate 
      title={trainingProgram.name}
      url={`/hr/training/programs/${trainingProgram.id}`}
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      {/* Program Overview */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>{t('Program Details')}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <label className="text-sm font-medium text-gray-500">{t('Training Type')}</label>
              <p className="mt-1">{trainingProgram.training_type?.name || '-'}</p>
            </div>
            
            <div>
              <label className="text-sm font-medium text-gray-500">{t('Description')}</label>
              <p className="mt-1">{trainingProgram.description || '-'}</p>
            </div>
            
            <div>
              <label className="text-sm font-medium text-gray-500">{t('Prerequisites')}</label>
              <p className="mt-1">{trainingProgram.prerequisites || '-'}</p>
            </div>
            
            <div className="flex flex-wrap gap-2">
              {trainingProgram.is_mandatory && (
                <Badge variant="outline" className="bg-red-50 text-red-700">
                  {t('Mandatory')}
                </Badge>
              )}
              {trainingProgram.is_self_enrollment && (
                <Badge variant="outline" className="bg-blue-50 text-blue-700">
                  {t('Self-Enrollment')}
                </Badge>
              )}
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader>
            <CardTitle>{t('Program Info')}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <label className="text-sm font-medium text-gray-500">{t('Status')}</label>
              <div className="mt-1">
                <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusClasses[trainingProgram.status] || ''}`}>
                  {trainingProgram.status.charAt(0).toUpperCase() + trainingProgram.status.slice(1)}
                </span>
              </div>
            </div>
            
            <div>
              <label className="text-sm font-medium text-gray-500">{t('Duration')}</label>
              <p className="mt-1">{trainingProgram.duration ? `${trainingProgram.duration} ${t('hours')}` : '-'}</p>
            </div>
            
            <div>
              <label className="text-sm font-medium text-gray-500">{t('Cost')}</label>
              <p className="mt-1">{trainingProgram.cost ? window.appSettings?.formatCurrency(parseFloat(trainingProgram.cost)) : '-'}</p>
            </div>
            
            <div>
              <label className="text-sm font-medium text-gray-500">{t('Capacity')}</label>
              <p className="mt-1">{trainingProgram.capacity || '-'}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Statistics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-lg">{t('Total Sessions')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">{statistics.totalSessions}</div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-lg">{t('Completed Sessions')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-green-600">{statistics.completedSessions}</div>
            <div className="text-sm text-gray-500 mt-1">
              {statistics.totalSessions > 0 ? Math.round((statistics.completedSessions / statistics.totalSessions) * 100) : 0}% {t('completion rate')}
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-lg">{t('Total Employees')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">{statistics.totalTrainings}</div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-lg">{t('Completed Trainings')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-blue-600">{statistics.completedTrainings}</div>
            <div className="text-sm text-gray-500 mt-1">
              {statistics.totalTrainings > 0 ? Math.round((statistics.completedTrainings / statistics.totalTrainings) * 100) : 0}% {t('completion rate')}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Progress Overview */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>{t('Session Progress')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              <div className="flex justify-between text-sm">
                <span>{t('Sessions Completed')}</span>
                <span>{statistics.completedSessions || 0}/{statistics.totalSessions || 0}</span>
              </div>
              <Progress value={statistics.totalSessions > 0 ? (statistics.completedSessions / statistics.totalSessions) * 100 : 0} className="h-2" />
              <div className="text-xs text-gray-500">
                {statistics.totalSessions > 0 ? Math.round((statistics.completedSessions / statistics.totalSessions) * 100) : 0}% {t('of sessions completed')}
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader>
            <CardTitle>{t('Employee Progress')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              <div className="flex justify-between text-sm">
                <span>{t('Employees Completed')}</span>
                <span>{statistics.completedTrainings || 0}/{statistics.totalTrainings || 0}</span>
              </div>
              <Progress value={statistics.totalTrainings > 0 ? (statistics.completedTrainings / statistics.totalTrainings) * 100 : 0} className="h-2" />
              <div className="text-xs text-gray-500">
                {statistics.totalTrainings > 0 ? Math.round((statistics.completedTrainings / statistics.totalTrainings) * 100) : 0}% {t('of employees completed')}
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </PageTemplate>
  );
}