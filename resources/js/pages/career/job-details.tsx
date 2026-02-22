import { Head, Link, usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { MapPin, Clock, Building, DollarSign, Calendar, Users, Star } from 'lucide-react';
import CareerHeader from '@/components/career/CareerHeader';
import CareerFooter from '@/components/career/CareerFooter';
import { getImagePath } from '@/utils/helpers';
import { useTranslation } from 'react-i18next';
import { useFavicon } from '@/hooks/use-favicon';
import { useBrandTheme } from '@/hooks/use-brand-theme';

export default function JobDetails() {
  const { t } = useTranslation();
  const { jobPosting, relatedJobs, companyId, companySettings, userSlug } = usePage().props as any;

  // Use favicon hook and brand theme like career index
  useFavicon();
  useBrandTheme();

  return (
    <>
      <Head title={`${jobPosting.title} - ${t("Career Opportunities")}`}>
        {companySettings?.favIcon && (
          <>
            <link rel="icon" href={getImagePath(companySettings.favIcon)} />
            <link rel="shortcut icon" href={getImagePath(companySettings.favIcon)} />
            <link rel="apple-touch-icon" href={getImagePath(companySettings.favIcon)} />
          </>
        )}
      </Head>
      
      <div className="min-h-screen bg-gray-50">
        <CareerHeader logoOnly={true} companySettings={companySettings} />

        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          {/* Back Button */}
          <div className="mb-6">
            <Link href={userSlug ? route('career.index', userSlug) : '/career'} className="inline-flex items-center text-blue-600 hover:text-blue-800">
              ← {t("Back to All Jobs")}
            </Link>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* Main Content */}
            <div className="lg:col-span-2 space-y-6">
              {/* Job Header */}
              <Card>
                <CardHeader>
                  <div className="flex items-start justify-between">
                    <div>
                      <CardTitle className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        {jobPosting.title}
                        {jobPosting.is_featured && <Star className="h-6 w-6 text-yellow-500 fill-current" />}
                      </CardTitle>
                      <p className="text-lg text-gray-600 mt-1">{jobPosting.branch?.name || 'General'}</p>
                    </div>
                    <div className="flex gap-2">
                      <Badge variant="outline" className="bg-green-50 text-green-700 border-green-200">
                        {jobPosting.status}
                      </Badge>
                      {jobPosting.priority === 'High' && (
                        <Badge variant="destructive">{t("High Priority")}</Badge>
                      )}
                    </div>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div className="flex items-center gap-2">
                      <MapPin className="h-5 w-5 text-gray-500" />
                      <div>
                        <p className="text-sm font-medium text-gray-900">{t("Location")}</p>
                        <p className="text-sm text-gray-600">{jobPosting.location?.name || 'Remote'}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Building className="h-5 w-5 text-gray-500" />
                      <div>
                        <p className="text-sm font-medium text-gray-900">{t("Department")}</p>
                        <p className="text-sm text-gray-600">{jobPosting.department?.name || '-'}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Clock className="h-5 w-5 text-gray-500" />
                      <div>
                        <p className="text-sm font-medium text-gray-900">{t("Type")}</p>
                        <p className="text-sm text-gray-600">{jobPosting.job_type?.name || 'Full-time'}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Users className="h-5 w-5 text-gray-500" />
                      <div>
                        <p className="text-sm font-medium text-gray-900">{t("Positions")}</p>
                        <p className="text-sm text-gray-600">{jobPosting.positions || 1}</p>
                      </div>
                    </div>
                  </div>
                  
                  <div className="mb-6">
                    <div className="flex items-center gap-2">
                      <DollarSign className="h-5 w-5 text-gray-500" />
                      <div>
                        <p className="text-sm font-medium text-gray-900">{t("Salary Range")}</p>
                        <p className="text-sm text-gray-600">
                          {jobPosting.min_salary && jobPosting.max_salary 
                            ? `$${jobPosting.min_salary} - $${jobPosting.max_salary}`
                            : t('Competitive')
                          }
                        </p>
                      </div>
                    </div>
                  </div>
                  
                  <div className="flex justify-center">
                    {jobPosting.application_type === 'existing' ? (
                      <Button 
                        size="lg" 
                        className="px-8"
                        onClick={() => window.open(route('career.apply', [userSlug, jobPosting.code]), '_blank')}
                      >
                        {t("Apply for this Position")}
                      </Button>
                    ) : (
                      <Button 
                        size="lg" 
                        className="px-8"
                        onClick={() => window.open(jobPosting.application_url, '_blank')}
                      >
                        {t("Apply for this Position")}
                      </Button>
                    )}
                  </div>
                </CardContent>
              </Card>

              {/* Job Description */}
              <Card>
                <CardHeader>
                  <CardTitle>{t("Job Description")}</CardTitle>
                </CardHeader>
                <CardContent>
                  <div 
                    className="prose prose-sm max-w-none prose-headings:text-gray-900 prose-headings:font-semibold prose-h3:text-lg prose-p:text-gray-700 prose-p:leading-relaxed prose-ul:text-gray-700 prose-li:my-1"
                    dangerouslySetInnerHTML={{ __html: jobPosting.description }}
                  />
                </CardContent>
              </Card>

              {/* Requirements */}
              {jobPosting.requirements && (
                <Card>
                  <CardHeader>
                    <CardTitle>{t("Requirements")}</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div 
                      className="prose prose-sm max-w-none prose-headings:text-gray-900 prose-headings:font-semibold prose-h3:text-lg prose-p:text-gray-700 prose-p:leading-relaxed prose-ul:text-gray-700 prose-li:my-1"
                      dangerouslySetInnerHTML={{ __html: jobPosting.requirements }}
                    />
                  </CardContent>
                </Card>
              )}

              {/* Benefits */}
              {jobPosting.benefits && (
                <Card>
                  <CardHeader>
                    <CardTitle>{t("Benefits & Perks")}</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div 
                      className="prose prose-sm max-w-none prose-headings:text-gray-900 prose-headings:font-semibold prose-h3:text-lg prose-p:text-gray-700 prose-p:leading-relaxed prose-ul:text-gray-700 prose-li:my-1"
                      dangerouslySetInnerHTML={{ __html: jobPosting.benefits }}
                    />
                  </CardContent>
                </Card>
              )}

              {/* Skills */}
              {jobPosting.skills && jobPosting.skills.length > 0 && (
                <Card>
                  <CardHeader>
                    <CardTitle>{t("Required Skills")}</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="flex flex-wrap gap-2">
                      {jobPosting.skills.map((skill, index) => (
                        <Badge key={index} variant="secondary" className="text-sm">
                          {skill}
                        </Badge>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              )}
            </div>

            {/* Sidebar */}
            <div className="space-y-6">
              {/* Quick Apply */}
              <Card>
                <CardHeader>
                  <CardTitle className="text-lg">{t("Quick Apply")}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  {jobPosting.application_type === 'existing' ? (
                    <Button 
                      className="w-full" 
                      size="lg"
                      onClick={() => window.open(route('career.apply', [userSlug, jobPosting.code]), '_blank')}
                    >
                      {t("Apply Now")}
                    </Button>
                  ) : (
                    <Button 
                      className="w-full" 
                      size="lg"
                      onClick={() => window.open(jobPosting.application_url, '_blank')}
                    >
                      {t("Apply Now")}
                    </Button>
                  )}
                  <p className="text-sm text-gray-600 text-center">
                    {t("Application takes less than 5 minutes")}
                  </p>
                </CardContent>
              </Card>

              {/* Job Info */}
              <Card>
                <CardHeader>
                  <CardTitle className="text-lg">{t("Job Information")}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="flex items-center gap-3">
                    <Calendar className="h-5 w-5 text-gray-500" />
                    <div>
                      <p className="text-sm font-medium text-gray-900">{t("Posted Date")}</p>
                      <p className="text-sm text-gray-600">
                        {new Date(jobPosting.created_at).toLocaleDateString('en-US', {
                          year: 'numeric',
                          month: 'long',
                          day: 'numeric'
                        })}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <Calendar className="h-5 w-5 text-red-500" />
                    <div>
                      <p className="text-sm font-medium text-gray-900">{t("Application Deadline")}</p>
                      <p className="text-sm text-red-600 font-medium">
                        {new Date(jobPosting.application_deadline).toLocaleDateString('en-US', {
                          year: 'numeric',
                          month: 'long',
                          day: 'numeric'
                        })}
                      </p>
                    </div>
                  </div>
                  {(jobPosting.min_experience || jobPosting.max_experience) && (
                    <div className="flex items-center gap-3">
                      <Building className="h-5 w-5 text-gray-500" />
                      <div>
                        <p className="text-sm font-medium text-gray-900">{t("Experience Required")}</p>
                        <p className="text-sm text-gray-600">
                          {jobPosting.min_experience && jobPosting.max_experience 
                            ? `${jobPosting.min_experience} - ${jobPosting.max_experience} ${t('years')}`
                            : jobPosting.min_experience 
                              ? `${jobPosting.min_experience}+ ${t('years')}`
                              : `${jobPosting.max_experience} ${t('years')}`
                          }
                        </p>
                      </div>
                    </div>
                  )}
                  {jobPosting.experience_level && (
                    <div className="flex items-center gap-3">
                      <Building className="h-5 w-5 text-gray-500" />
                      <div>
                        <p className="text-sm font-medium text-gray-900">{t("Experience Level")}</p>
                        <p className="text-sm text-gray-600">{jobPosting.experience_level}</p>
                      </div>
                    </div>
                  )}
                </CardContent>
              </Card>

              {/* Similar Jobs */}
              {relatedJobs && relatedJobs.length > 0 && (
                <Card>
                  <CardHeader>
                    <CardTitle className="text-lg">{t("Similar Jobs")}</CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    {relatedJobs.map((relatedJob, index) => (
                      <Link key={index} href={route('career.job-details', [userSlug, relatedJob.code])}>
                        <div className="p-4 border rounded-lg hover:bg-gray-50 hover:border-blue-200 cursor-pointer transition-all duration-200 mb-3">
                          <h4 className="font-semibold text-gray-900 mb-2">{relatedJob.title}</h4>
                          <p className="text-sm text-gray-600 mb-3">{relatedJob.branch?.name || 'General'}</p>
                          <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2 text-xs text-gray-500">
                              <MapPin className="h-3 w-3" />
                              <span>{relatedJob.location?.name || 'Remote'}</span>
                            </div>
                            <Badge variant="outline" className="text-xs">
                              {relatedJob.positions || 1} {t("Positions")}
                            </Badge>
                          </div>
                        </div>
                      </Link>
                    ))}
                  </CardContent>
                </Card>
              )}
            </div>
          </div>
        </div>
        
        <CareerFooter companySettings={companySettings} />
      </div>
    </>
  );
}