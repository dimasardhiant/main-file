import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Edit, Calendar, MapPin, Building, DollarSign, Clock, Star } from 'lucide-react';

export default function ShowJobPosting() {
  const { t } = useTranslation();
  const { jobPosting, customQuestions } = usePage().props as any;

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Recruitment'), href: route('hr.recruitment.job-postings.index') },
    { title: t('Job Postings'), href: route('hr.recruitment.job-postings.index') },
    { title: jobPosting.title }
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Draft': return 'bg-yellow-50 text-yellow-700 ring-yellow-600/20';
      case 'Published': return 'bg-green-50 text-green-700 ring-green-600/20';
      case 'Closed': return 'bg-red-50 text-red-700 ring-red-600/10';
      default: return 'bg-yellow-50 text-yellow-700 ring-yellow-600/20';
    }
  };

  return (
    <PageTemplate
      title={jobPosting.title}
      breadcrumbs={breadcrumbs}
      actions={[
        {
          label: t('Back to List'),
          icon: <ArrowLeft className="h-4 w-4 mr-2" />,
          variant: 'outline',
          onClick: () => router.get(route('hr.recruitment.job-postings.index'))
        },
        {
          label: t('Edit'),
          icon: <Edit className="h-4 w-4 mr-2" />,
          variant: 'default',
          onClick: () => router.get(route('hr.recruitment.job-postings.edit', jobPosting.id))
        }
      ]}
    >
      <div className="space-y-6">
        {/* Header Card */}
        <Card>
          <CardHeader>
            <div className="flex items-start justify-between">
              <div>
                <CardTitle className="flex items-center gap-2">
                  {jobPosting.title}
                  {jobPosting.is_featured && <Star className="h-5 w-5 text-yellow-500 fill-current" />}
                </CardTitle>
                <p className="text-sm text-muted-foreground mt-1">
                  {t('Job Code')}: {jobPosting.job_code}
                </p>
              </div>
              <div className="flex gap-2">
                <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getStatusColor(jobPosting.status)}`}>
                  {t(jobPosting.status)}
                </span>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <div className="flex items-center gap-2">
                <Building className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">{t('Job Type')}</p>
                  <p className="text-sm text-muted-foreground">{jobPosting.job_type?.name || '-'}</p>
                </div>
              </div>
              
              <div className="flex items-center gap-2">
                <MapPin className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">{t('Location')}</p>
                  <p className="text-sm text-muted-foreground">{jobPosting.location?.name || '-'}</p>
                </div>
              </div>
              
              <div className="flex items-center gap-2">
                <Building className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">{t('Branch')}</p>
                  <p className="text-sm text-muted-foreground">{jobPosting.branch?.name || '-'}</p>
                </div>
              </div>
              
              <div className="flex items-center gap-2">
                <Building className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">{t('Department')}</p>
                  <p className="text-sm text-muted-foreground">{jobPosting.department?.name || '-'}</p>
                </div>
              </div>
              
              <div className="flex items-center gap-2">
                <Building className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">{t('Priority')}</p>
                  <p className="text-sm text-muted-foreground">{jobPosting.priority || '-'}</p>
                </div>
              </div>
              
              <div className="flex items-center gap-2">
                <Calendar className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">{t('Positions')}</p>
                  <p className="text-sm text-muted-foreground">{jobPosting.positions || '-'}</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Experience & Salary */}
        <Card>
          <CardHeader>
            <CardTitle>{t('Experience & Salary')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="flex items-center gap-2">
                <Clock className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">{t('Experience Required')}</p>
                  <p className="text-sm text-muted-foreground">
                    {jobPosting.min_experience} - {jobPosting.max_experience || '+'} {t('years')}
                  </p>
                </div>
              </div>
              
              <div className="flex items-center gap-2">
                <DollarSign className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">{t('Salary Range')}</p>
                  <p className="text-sm text-muted-foreground">
                    {jobPosting.min_salary && jobPosting.max_salary
                      ? `${window.appSettings?.formatCurrency(jobPosting.min_salary)} - ${window.appSettings?.formatCurrency(jobPosting.max_salary)}`
                      : jobPosting.min_salary
                        ? `${window.appSettings?.formatCurrency(jobPosting.min_salary)}+`
                        : '-'
                    }
                  </p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Job Requisition */}
        {jobPosting.requisition && (
          <Card>
            <CardHeader>
              <CardTitle>{t('Job Requisition')}</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                <div className="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                  <span className="text-sm font-semibold text-gray-900 min-w-fit">{t('Requisition Code')}:</span>
                  <span className="text-sm text-gray-700 font-mono bg-gray-50 px-2 py-1 rounded">{jobPosting.requisition.requisition_code}</span>
                </div>
                <div className="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                  <span className="text-sm font-semibold text-gray-900 min-w-fit">{t('Title')}:</span>
                  <span className="text-sm text-gray-700">{jobPosting.requisition.title}</span>
                </div>
              </div>
            </CardContent>
          </Card>
        )}

        {/* Job Description */}
        {jobPosting.description && (
          <Card>
            <CardHeader>
              <CardTitle className="text-lg font-semibold text-gray-900">{t('Job Description')}</CardTitle>
            </CardHeader>
            <CardContent>
              <div 
                className="prose prose-sm max-w-none prose-headings:text-gray-900 prose-headings:font-semibold prose-h1:text-xl prose-h2:text-lg prose-h3:text-base prose-p:text-gray-700 prose-p:leading-relaxed prose-strong:text-gray-900 prose-strong:font-semibold prose-ul:text-gray-700 prose-ol:text-gray-700 prose-li:my-1 prose-a:text-blue-600 prose-a:no-underline hover:prose-a:underline prose-blockquote:border-l-4 prose-blockquote:border-gray-300 prose-blockquote:pl-4 prose-blockquote:italic prose-code:bg-gray-100 prose-code:px-1 prose-code:py-0.5 prose-code:rounded prose-code:text-sm" 
                dangerouslySetInnerHTML={{ __html: jobPosting.description }} 
              />
            </CardContent>
          </Card>
        )}

        {/* Requirements */}
        {jobPosting.requirements && (
          <Card>
            <CardHeader>
              <CardTitle className="text-lg font-semibold text-gray-900">{t('Requirements')}</CardTitle>
            </CardHeader>
            <CardContent>
              <div 
                className="prose prose-sm max-w-none prose-headings:text-gray-900 prose-headings:font-semibold prose-h1:text-xl prose-h2:text-lg prose-h3:text-base prose-p:text-gray-700 prose-p:leading-relaxed prose-strong:text-gray-900 prose-strong:font-semibold prose-ul:text-gray-700 prose-ol:text-gray-700 prose-li:my-1 prose-a:text-blue-600 prose-a:no-underline hover:prose-a:underline prose-blockquote:border-l-4 prose-blockquote:border-gray-300 prose-blockquote:pl-4 prose-blockquote:italic prose-code:bg-gray-100 prose-code:px-1 prose-code:py-0.5 prose-code:rounded prose-code:text-sm" 
                dangerouslySetInnerHTML={{ __html: jobPosting.requirements }} 
              />
            </CardContent>
          </Card>
        )}

        {/* Benefits */}
        {jobPosting.benefits && (
          <Card>
            <CardHeader>
              <CardTitle className="text-lg font-semibold text-gray-900">{t('Benefits')}</CardTitle>
            </CardHeader>
            <CardContent>
              <div 
                className="prose prose-sm max-w-none prose-headings:text-gray-900 prose-headings:font-semibold prose-h1:text-xl prose-h2:text-lg prose-h3:text-base prose-p:text-gray-700 prose-p:leading-relaxed prose-strong:text-gray-900 prose-strong:font-semibold prose-ul:text-gray-700 prose-ol:text-gray-700 prose-li:my-1 prose-a:text-blue-600 prose-a:no-underline hover:prose-a:underline prose-blockquote:border-l-4 prose-blockquote:border-gray-300 prose-blockquote:pl-4 prose-blockquote:italic prose-code:bg-gray-100 prose-code:px-1 prose-code:py-0.5 prose-code:rounded prose-code:text-sm" 
                dangerouslySetInnerHTML={{ __html: jobPosting.benefits }} 
              />
            </CardContent>
          </Card>
        )}

        {/* Skills */}
        {jobPosting.skills && jobPosting.skills.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle>{t('Required Skills')}</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex flex-wrap gap-2">
                {jobPosting.skills.map((skill: string, index: number) => (
                  <Badge key={index} variant="outline" className="text-sm font-medium px-3 py-1">
                    {skill}
                  </Badge>
                ))}
              </div>
            </CardContent>
          </Card>
        )}

        {/* Custom Questions */}
        {jobPosting.custom_question && jobPosting.custom_question.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle>{t('Custom Questions')}</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {jobPosting.custom_question.map((questionId: number, index: number) => {
                  const question = customQuestions?.find((q: any) => q.id === questionId);
                  return (
                    <div key={index} className="p-4 bg-gray-50 rounded-lg border border-gray-200">
                      <div className="flex items-start justify-between gap-3">
                        <p className="text-sm font-medium text-gray-900 leading-relaxed flex-1">
                          {question?.question || `Question ID: ${questionId}`}
                        </p>
                        {question?.required === 1 && (
                          <Badge variant="destructive" className="text-xs shrink-0">Required</Badge>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            </CardContent>
          </Card>
        )}

        {/* Need to Ask */}
        {jobPosting.applicant && jobPosting.applicant.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle>{t('Need to Ask?')}</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex flex-wrap gap-2">
                {jobPosting.applicant.map((item: string, index: number) => (
                  <Badge key={index} variant="secondary">
                    {item === 'gender' && t('Gender')}
                    {item === 'date_of_birth' && t('Date Of Birth')}
                    {item === 'address' && t('Address')}
                  </Badge>
                ))}
              </div>
            </CardContent>
          </Card>
        )}

        {/* Need to Show Option */}
        {jobPosting.visibility && jobPosting.visibility.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle>{t('Need to Show Option?')}</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex flex-wrap gap-2">
                {jobPosting.visibility.map((item: string, index: number) => (
                  <Badge key={index} variant="secondary">
                    {item === 'profile_image' && t('Profile Image')}
                    {item === 'resume' && t('Resume')}
                    {item === 'cover_letter' && t('Cover Letter')}
                    {item === 'terms_and_conditions' && t('Terms And Conditions')}
                  </Badge>
                ))}
              </div>
            </CardContent>
          </Card>
        )}
        
        {/* Dates */}
        <Card>
          <CardHeader>
            <CardTitle className="text-lg font-semibold text-gray-900">{t('Important Dates')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="flex items-start gap-3">
                <Calendar className="h-5 w-5 text-blue-600 mt-0.5 shrink-0" />
                <div className="space-y-1">
                  <p className="text-sm font-semibold text-gray-900">{t('Start Date')}</p>
                  <p className="text-sm text-gray-700 font-medium">
                    {jobPosting.start_date 
                      ? new Date(jobPosting.start_date).toLocaleDateString('en-US', {
                          year: 'numeric',
                          month: 'long',
                          day: 'numeric'
                        })
                      : 'Not specified'
                    }
                  </p>
                </div>
              </div>
              
              <div className="flex items-start gap-3">
                <Calendar className="h-5 w-5 text-red-600 mt-0.5 shrink-0" />
                <div className="space-y-1">
                  <p className="text-sm font-semibold text-gray-900">{t('Application Deadline')}</p>
                  <p className="text-sm text-gray-700 font-medium">
                    {jobPosting.application_deadline 
                      ? new Date(jobPosting.application_deadline).toLocaleDateString('en-US', {
                          year: 'numeric',
                          month: 'long',
                          day: 'numeric'
                        })
                      : 'Not specified'
                    }
                  </p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader>
            <CardTitle className="text-lg font-semibold text-gray-900">{t('Additional Information')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div className="space-y-1">
                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide">{t('Created At')}</p>
                <p className="text-sm text-gray-900 font-medium">
                  {window.appSettings?.formatDateTimeSimple(jobPosting.created_at) || 
                   new Date(jobPosting.created_at).toLocaleDateString('en-US', {
                     year: 'numeric',
                     month: 'short',
                     day: 'numeric',
                     hour: '2-digit',
                     minute: '2-digit'
                   })}
                </p>
              </div>
              <div className="space-y-1">
                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide">{t('Updated At')}</p>
                <p className="text-sm text-gray-900 font-medium">
                  {window.appSettings?.formatDateTimeSimple(jobPosting.updated_at) || 
                   new Date(jobPosting.updated_at).toLocaleDateString('en-US', {
                     year: 'numeric',
                     month: 'short',
                     day: 'numeric',
                     hour: '2-digit',
                     minute: '2-digit'
                   })}
                </p>
              </div>
              {jobPosting.publish_date && (
                <div className="space-y-1">
                  <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide">{t('Published At')}</p>
                  <p className="text-sm text-gray-900 font-medium">
                    {window.appSettings?.formatDateTimeSimple(jobPosting.publish_date) || 
                     new Date(jobPosting.publish_date).toLocaleDateString('en-US', {
                       year: 'numeric',
                       month: 'short',
                       day: 'numeric',
                       hour: '2-digit',
                       minute: '2-digit'
                     })}
                  </p>
                </div>
              )}
            </div>
          </CardContent>
        </Card>
      </div>
    </PageTemplate>
  );
}