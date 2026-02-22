import { PageTemplate } from '@/components/page-template';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { ArrowLeft } from 'lucide-react';

export default function CandidateOnboardingShow() {
  const { t } = useTranslation();
  const { candidateOnboarding } = usePage().props as any;

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Recruitment'), href: route('hr.recruitment.candidate-onboarding.index') },
    { title: t('Candidate Onboarding'), href: route('hr.recruitment.candidate-onboarding.index') },
    { title: t('View Details') }
  ];

  const pageActions = [
    {
      label: t('Back to List'),
      icon: <ArrowLeft className="h-4 w-4 mr-2" />,
      variant: 'outline',
      onClick: () => window.history.back()
    }
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Pending': return 'bg-yellow-50 text-yellow-800 ring-yellow-600/20';
      case 'In Progress': return 'bg-blue-50 text-blue-700 ring-blue-600/20';
      case 'Completed': return 'bg-green-50 text-green-700 ring-green-600/20';
      default: return 'bg-gray-50 text-gray-600 ring-gray-500/10';
    }
  };

  return (
    <PageTemplate
      title={t("Onboarding Details")}
      url="/hr/recruitment/candidate-onboarding"
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      <div className="space-y-6">
        {/* Employee Information */}
        <div className="bg-white dark:bg-gray-900 rounded-lg shadow p-6">
          <h3 className="text-lg font-medium mb-4">{t('Employee Information')}</h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">{t('Employee Name')}</label>
              <p className="mt-1 text-sm text-gray-900 dark:text-gray-100">{candidateOnboarding.employee?.name || '-'}</p>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">{t('Email')}</label>
              <p className="mt-1 text-sm text-gray-900 dark:text-gray-100">{candidateOnboarding.employee?.email || '-'}</p>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">{t('Buddy Employee')}</label>
              <p className="mt-1 text-sm text-gray-900 dark:text-gray-100">{candidateOnboarding.buddy_employee?.name || '-'}</p>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">{t('Start Date')}</label>
              <p className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                {candidateOnboarding.start_date ? new Date(candidateOnboarding.start_date).toLocaleDateString() : '-'}
              </p>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">{t('Status')}</label>
              <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getStatusColor(candidateOnboarding.status)}`}>
                {t(candidateOnboarding.status)}
              </span>
            </div>
          </div>
        </div>

        {/* Onboarding Checklist */}
        <div className="bg-white dark:bg-gray-900 rounded-lg shadow p-6">
          <h3 className="text-lg font-medium mb-4">{t('Onboarding Checklist')}</h3>
          <div className="mb-4">
            <h4 className="font-medium text-gray-900 dark:text-gray-100">{candidateOnboarding.checklist?.name}</h4>
            <p className="text-sm text-gray-600 dark:text-gray-400">{candidateOnboarding.checklist?.description}</p>
          </div>
          
          {candidateOnboarding.checklist?.checklist_items && candidateOnboarding.checklist.checklist_items.length > 0 ? (
            <div className="space-y-3">
              {candidateOnboarding.checklist.checklist_items.map((item: any, index: number) => (
                <div key={item.id} className="flex items-start space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <div className="flex-shrink-0">
                    <span className="inline-flex items-center justify-center w-6 h-6 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">
                      {index + 1}
                    </span>
                  </div>
                  <div className="flex-1">
                    <h5 className="font-medium text-gray-900 dark:text-gray-100">{item.task_name}</h5>
                    {item.description && (
                      <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">{item.description}</p>
                    )}
                    {item.due_day && (
                      <p className="text-xs text-gray-500 mt-1">
                        {t('Due Day')}: {item.due_day}
                      </p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-gray-500 dark:text-gray-400">{t('No checklist items found')}</p>
          )}
        </div>
      </div>
    </PageTemplate>
  );
}