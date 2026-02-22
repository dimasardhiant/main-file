import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Edit, Calendar, DollarSign, User, Building, FileText } from 'lucide-react';

export default function ShowOffer() {
  const { t } = useTranslation();
  const { offer } = usePage().props as any;

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Recruitment'), href: route('hr.recruitment.offers.index') },
    { title: t('Offers'), href: route('hr.recruitment.offers.index') },
    { title: t('View Offer') }
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Draft': return 'bg-gray-50 text-gray-600 ring-gray-500/10';
      case 'Sent': return 'bg-blue-50 text-blue-700 ring-blue-600/20';
      case 'Accepted': return 'bg-green-50 text-green-700 ring-green-600/20';
      case 'Negotiating': return 'bg-yellow-50 text-yellow-800 ring-yellow-600/20';
      case 'Declined': return 'bg-red-50 text-red-700 ring-red-600/10';
      case 'Expired': return 'bg-orange-50 text-orange-700 ring-orange-600/20';
      default: return 'bg-gray-50 text-gray-600 ring-gray-500/10';
    }
  };

  return (
    <PageTemplate
      title={t('Offer Details')}
      breadcrumbs={breadcrumbs}
      actions={[
        {
          label: t('Back to List'),
          icon: <ArrowLeft className="h-4 w-4 mr-2" />,
          variant: 'outline',
          onClick: () => router.get(route('hr.recruitment.offers.index'))
        },
        {
          label: t('Edit'),
          icon: <Edit className="h-4 w-4 mr-2" />,
          variant: 'default',
          onClick: () => router.get(route('hr.recruitment.offers.index'))
        }
      ]}
    >
      <div className="space-y-6">
        {/* Header Card */}
        <Card>
          <CardHeader>
            <div className="flex items-start justify-between">
              <div>
                <CardTitle className="text-2xl font-bold">
                  {offer.candidate?.first_name} {offer.candidate?.last_name}
                </CardTitle>
                <p className="text-sm text-muted-foreground mt-1">{offer.job?.job_code}</p>
              </div>
              <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getStatusColor(offer.status)}`}>
                {t(offer.status)}
              </span>
            </div>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div className="flex items-center gap-3">
                <User className="h-5 w-5 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">{t('Candidate')}</p>
                  <p className="text-sm text-muted-foreground">
                    {offer.candidate?.first_name} {offer.candidate?.last_name}
                  </p>
                </div>
              </div>

              <div className="flex items-center gap-3">
                <FileText className="h-5 w-5 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">{t('Position')}</p>
                  <p className="text-sm text-muted-foreground">{offer.position}</p>
                </div>
              </div>

              <div className="flex items-center gap-3">
                <Building className="h-5 w-5 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">{t('Department')}</p>
                  <p className="text-sm text-muted-foreground">{offer.department?.name || '-'}</p>
                </div>
              </div>

              <div className="flex items-center gap-3">
                <DollarSign className="h-5 w-5 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">{t('Salary')}</p>
                  <p className="text-sm text-muted-foreground">
                    {window.appSettings?.formatCurrency(offer.salary)}
                  </p>
                </div>
              </div>

              <div className="flex items-center gap-3">
                <Calendar className="h-5 w-5 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">{t('Start Date')}</p>
                  <p className="text-sm text-muted-foreground">
                    {window.appSettings?.formatDateTimeSimple(offer.start_date, false) || new Date(offer.start_date).toLocaleDateString()}
                  </p>
                </div>
              </div>

              <div className="flex items-center gap-3">
                <Calendar className="h-5 w-5 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">{t('Expiration Date')}</p>
                  <p className="text-sm text-muted-foreground">
                    {window.appSettings?.formatDateTimeSimple(offer.expiration_date, false) || new Date(offer.expiration_date).toLocaleDateString()}
                  </p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Benefits */}
        {offer.benefits && (
          <Card>
            <CardHeader>
              <CardTitle>{t('Benefits')}</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-muted-foreground whitespace-pre-wrap">{offer.benefits}</p>
            </CardContent>
          </Card>
        )}

        {/* Offer Details */}
        <Card>
          <CardHeader>
            <CardTitle>{t('Offer Information')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <p className="text-sm font-medium mb-1">{t('Offer Date')}</p>
                <p className="text-sm text-muted-foreground">
                  {window.appSettings?.formatDateTimeSimple(offer.offer_date, false) || new Date(offer.offer_date).toLocaleDateString()}
                </p>
              </div>

              {offer.approved_by && (
                <div>
                  <p className="text-sm font-medium mb-1">{t('Approved By')}</p>
                  <p className="text-sm text-muted-foreground">{offer.approver?.name || '-'}</p>
                </div>
              )}

              {offer.response_date && (
                <div>
                  <p className="text-sm font-medium mb-1">{t('Response Date')}</p>
                  <p className="text-sm text-muted-foreground">
                    {window.appSettings?.formatDateTimeSimple(offer.response_date, false) || new Date(offer.response_date).toLocaleDateString()}
                  </p>
                </div>
              )}

              {offer.decline_reason && (
                <div className="col-span-2">
                  <p className="text-sm font-medium mb-1">{t('Decline Reason')}</p>
                  <p className="text-sm text-muted-foreground">{offer.decline_reason}</p>
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Job Details */}
        {offer.job && (
          <Card>
            <CardHeader>
              <CardTitle>{t('Job Details')}</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                <div>
                  <p className="text-sm font-medium">{t('Job Title')}</p>
                  <p className="text-sm text-muted-foreground">{offer.job.title}</p>
                </div>
                <div>
                  <p className="text-sm font-medium">{t('Job Code')}</p>
                  <p className="text-sm text-muted-foreground">{offer.job.job_code}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        )}
      </div>
    </PageTemplate>
  );
}
