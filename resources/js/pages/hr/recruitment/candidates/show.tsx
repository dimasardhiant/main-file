import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, User, Briefcase, MapPin, Clock, DollarSign, Calendar, Phone, Mail, Building, ExternalLink, GraduationCap, Award, Users, FileText } from 'lucide-react';
import { getImagePath } from '@/utils/helpers';

export default function CandidateShow() {
  const { t } = useTranslation();
  const { candidate } = usePage().props as any;

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Recruitment') },
    { title: t('Candidates'), href: route('hr.recruitment.candidates.index') },
    { title: `${candidate.first_name} ${candidate.last_name}` }
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'New': return 'bg-blue-50 text-blue-700 ring-blue-600/20';
      case 'Screening': return 'bg-yellow-50 text-yellow-800 ring-yellow-600/20';
      case 'Interview': return 'bg-purple-50 text-purple-700 ring-purple-600/20';
      case 'Offer': return 'bg-orange-50 text-orange-700 ring-orange-600/20';
      case 'Hired': return 'bg-green-50 text-green-700 ring-green-600/20';
      case 'Rejected': return 'bg-red-50 text-red-700 ring-red-600/10';
      default: return 'bg-gray-50 text-gray-600 ring-gray-500/10';
    }
  };

  return (
    <PageTemplate
      title={'Candidate Details'}
      breadcrumbs={breadcrumbs}
      actions={[
        {
          label: t('Back to Candidates'),
          icon: <ArrowLeft className="h-4 w-4 mr-2" />,
          variant: 'outline',
          onClick: () => router.get(route('hr.recruitment.candidates.index'))
        }
      ]}
    >
      <div className="space-y-6">
        {/* Candidate Header */}
        <Card>
          <CardContent className="p-6">
            <div className="flex items-start justify-between">
              <div className="flex items-center space-x-4">
                <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                  <User className="h-8 w-8 text-blue-600" />
                </div>
                <div>
                  <h1 className="text-2xl font-bold text-gray-900">
                    {candidate.first_name} {candidate.last_name}
                  </h1>
                  <p className="text-base text-gray-600 mt-1">{candidate.current_position || t('Job Applicant')}</p>
                  <p className="text-sm text-gray-500">{candidate.current_company}</p>
                  {candidate.rating && (
                    <div className="flex items-center mt-2">
                      <span className="text-sm font-medium text-gray-700 mr-2">{t('Rating')}:</span>
                      <div className="flex">
                        {[...Array(5)].map((_, i) => (
                          <span key={i} className={`text-lg ${i < candidate.rating ? 'text-yellow-400' : 'text-gray-300'}`}>★</span>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              </div>
              <div className="text-right">
                <Badge className={`inline-flex items-center rounded-md px-3 py-1 text-sm font-medium ring-1 ring-inset ${getStatusColor(candidate.status)}`}>
                  {t(candidate.status)}
                </Badge>
                <p className="text-sm text-gray-500 mt-2">
                  {t('Applied on')} {new Date(candidate.application_date).toLocaleDateString()}
                </p>
                {candidate.is_archive && (
                  <Badge variant="secondary" className="mt-2">{t('Archived')}</Badge>
                )}
                {candidate.is_employee && (
                  <Badge variant="outline" className="mt-2 ml-2">{t('Employee')}</Badge>
                )}
              </div>
            </div>
          </CardContent>
        </Card>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Contact Information */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg font-semibold text-gray-900">
                <Phone className="h-5 w-5" />
                {t('Contact Information')}
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center gap-3">
                <Mail className="h-4 w-4 text-gray-500" />
                <div>
                  <p className="text-sm font-medium text-gray-900">{t('Email')}</p>
                  <p className="text-sm text-gray-600">{candidate.email}</p>
                </div>
              </div>
              {candidate.phone && (
                <div className="flex items-center gap-3">
                  <Phone className="h-4 w-4 text-gray-500" />
                  <div>
                    <p className="text-sm font-medium text-gray-900">{t('Phone')}</p>
                    <p className="text-sm text-gray-600">{candidate.phone}</p>
                  </div>
                </div>
              )}
              {candidate.address && (
                <div className="flex items-center gap-3">
                  <MapPin className="h-4 w-4 text-gray-500" />
                  <div>
                    <p className="text-sm font-medium text-gray-900">{t('Address')}</p>
                    <p className="text-sm text-gray-600">{candidate.address}</p>
                  </div>
                </div>
              )}
              {candidate.city && (
                <div className="flex items-center gap-3">
                  <MapPin className="h-4 w-4 text-gray-500" />
                  <div>
                    <p className="text-sm font-medium text-gray-900">{t('City')}</p>
                    <p className="text-sm text-gray-600">{candidate.city}</p>
                  </div>
                </div>
              )}
              {candidate.state && (
                <div className="flex items-center gap-3">
                  <MapPin className="h-4 w-4 text-gray-500" />
                  <div>
                    <p className="text-sm font-medium text-gray-900">{t('State')}</p>
                    <p className="text-sm text-gray-600">{candidate.state}</p>
                  </div>
                </div>
              )}
              {candidate.country && (
                <div className="flex items-center gap-3">
                  <MapPin className="h-4 w-4 text-gray-500" />
                  <div>
                    <p className="text-sm font-medium text-gray-900">{t('Country')}</p>
                    <p className="text-sm text-gray-600">{candidate.country}</p>
                  </div>
                </div>
              )}
              {candidate.zip_code && (
                <div className="flex items-center gap-3">
                  <MapPin className="h-4 w-4 text-gray-500" />
                  <div>
                    <p className="text-sm font-medium text-gray-900">{t('Zip Code')}</p>
                    <p className="text-sm text-gray-600">{candidate.zip_code}</p>
                  </div>
                </div>
              )}
              {candidate.linkedin_url && (
                <div className="flex items-center gap-3">
                  <ExternalLink className="h-4 w-4 text-gray-500" />
                  <div>
                    <p className="text-sm font-medium text-gray-900">LinkedIn</p>
                    <a 
                      href={candidate.linkedin_url} 
                      target="_blank" 
                      rel="noopener noreferrer"
                      className="text-sm text-blue-600 hover:underline font-medium"
                    >
                      {t('View Profile')}
                    </a>
                  </div>
                </div>
              )}
              {candidate.portfolio_url && (
                <div className="flex items-center gap-3">
                  <ExternalLink className="h-4 w-4 text-gray-500" />
                  <div>
                    <p className="text-sm font-medium text-gray-900">{t('Portfolio')}</p>
                    <a 
                      href={candidate.portfolio_url} 
                      target="_blank" 
                      rel="noopener noreferrer"
                      className="text-sm text-blue-600 hover:underline font-medium"
                    >
                      {t('View Portfolio')}
                    </a>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Job Information */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg font-semibold text-gray-900">
                <Briefcase className="h-5 w-5" />
                {t('Applied Position')}
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <h3 className="text-lg font-bold text-gray-900">{candidate.job?.title}</h3>
                <p className="text-sm text-gray-600 mt-1">{candidate.job?.job_code}</p>
              </div>
              <div className="space-y-3">
                <div className="flex items-center gap-2">
                  <Building className="h-4 w-4 text-gray-500" />
                  <span className="text-sm text-gray-700">{candidate.branch?.name}</span>
                </div>
                <div className="flex items-center gap-2">
                  <MapPin className="h-4 w-4 text-gray-500" />
                  <span className="text-sm text-gray-700">{candidate.job?.location?.name}</span>
                </div>
                <div className="flex items-center gap-2">
                  <Clock className="h-4 w-4 text-gray-500" />
                  <span className="text-sm text-gray-700">{candidate.job?.job_type?.name}</span>
                </div>
                {candidate.department && (
                  <div className="flex items-center gap-2">
                    <Building className="h-4 w-4 text-gray-500" />
                    <span className="text-sm text-gray-700">{t('Department')}: {candidate.department.name}</span>
                  </div>
                )}
                {candidate.source && (
                  <div className="flex items-center gap-2">
                    <Users className="h-4 w-4 text-gray-500" />
                    <span className="text-sm text-gray-700">{t('Source')}: {candidate.source.name}</span>
                  </div>
                )}
                {candidate.referral_employee && (
                  <div className="flex items-center gap-2">
                    <User className="h-4 w-4 text-gray-500" />
                    <span className="text-sm text-gray-700">{t('Referred by')}: {candidate.referral_employee.name}</span>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>

          {/* Professional Information */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg font-semibold text-gray-900">
                <DollarSign className="h-5 w-5" />
                {t('Professional Information')}
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {candidate.experience_years !== null && (
                <div>
                  <p className="text-sm font-medium text-gray-900">{t('Experience')}</p>
                  <p className="text-sm text-gray-600">{candidate.experience_years} {t('years')}</p>
                </div>
              )}
              {candidate.current_salary && (
                <div>
                  <p className="text-sm font-medium text-gray-900">{t('Current Salary')}</p>
                  <p className="text-sm text-gray-600">${candidate.current_salary.toLocaleString()}</p>
                </div>
              )}
              {candidate.expected_salary && (
                <div>
                  <p className="text-sm font-medium text-gray-900">{t('Expected Salary')}</p>
                  <p className="text-sm text-gray-600">${candidate.expected_salary.toLocaleString()}</p>
                </div>
              )}
              {candidate.final_salary && (
                <div>
                  <p className="text-sm font-medium text-gray-900">{t('Final Salary')}</p>
                  <p className="text-sm text-gray-600">${candidate.final_salary.toLocaleString()}</p>
                </div>
              )}
              {candidate.notice_period && (
                <div>
                  <p className="text-sm font-medium text-gray-900">{t('Notice Period')}</p>
                  <p className="text-sm text-gray-600">{candidate.notice_period}</p>
                </div>
              )}
              {candidate.gender && (
                <div>
                  <p className="text-sm font-medium text-gray-900">{t('Gender')}</p>
                  <p className="text-sm text-gray-600 capitalize">{candidate.gender}</p>
                </div>
              )}
              {candidate.date_of_birth && (
                <div>
                  <p className="text-sm font-medium text-gray-900">{t('Date of Birth')}</p>
                  <p className="text-sm text-gray-600">{new Date(candidate.date_of_birth).toLocaleDateString()}</p>
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        {/* Documents */}
        {(candidate.resume_path || candidate.cover_letter_path) && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg font-semibold text-gray-900">
                <FileText className="h-5 w-5" />
                {t('Documents')}
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {candidate.resume_path && (
                <div className="flex items-center gap-3">
                  <FileText className="h-4 w-4 text-gray-500" />
                  <div>
                    <p className="text-sm font-medium text-gray-900">{t('Resume')}</p>
                    <a 
                      href={getImagePath(candidate.resume_path)} 
                      target="_blank" 
                      rel="noopener noreferrer"
                      className="text-sm text-blue-600 hover:underline font-medium"
                      download
                    >
                      {t('Download Resume')}
                    </a>
                  </div>
                </div>
              )}
              {candidate.cover_letter_path && (
                <div className="flex items-center gap-3">
                  <FileText className="h-4 w-4 text-gray-500" />
                  <div>
                    <p className="text-sm font-medium text-gray-900">{t('Cover Letter')}</p>
                    <a 
                      href={getImagePath(candidate.cover_letter_path)} 
                      target="_blank" 
                      rel="noopener noreferrer"
                      className="text-sm text-blue-600 hover:underline font-medium"
                      download
                    >
                      {t('Download Cover Letter')}
                    </a>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        )}

        {/* Cover Letter Message */}
        {candidate.coverletter_message && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg font-semibold text-gray-900">
                <Mail className="h-5 w-5" />
                {t('Cover Letter Message')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="bg-gray-50 p-4 rounded-lg">
                <p className="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{candidate.coverletter_message}</p>
              </div>
            </CardContent>
          </Card>
        )}

        {/* Custom Questions */}
        {candidate.custom_question && Object.keys(candidate.custom_question).length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg font-semibold text-gray-900">
                <Award className="h-5 w-5" />
                {t('Custom Questions & Answers')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {Object.entries(candidate.custom_question).map(([question, answer], index) => (
                  <div key={index} className="border-l-4 border-blue-200 pl-4">
                    <p className="text-sm font-medium text-gray-900">{question}</p>
                    <p className="text-sm text-gray-600 mt-1">{answer}</p>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        )}

        {/* Additional Information */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg font-semibold text-gray-900">
              <Clock className="h-5 w-5" />
              {t('Additional Information')}
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p className="text-sm font-medium text-gray-900">{t('Terms & Conditions')}</p>
                <Badge className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${
                  candidate.terms_condition_check === 'on'
                    ? 'bg-green-50 text-green-700 ring-green-600/20'
                    : 'bg-red-50 text-red-700 ring-red-600/20'
                }`}>
                  {candidate.terms_condition_check === 'on' ? t('Accepted') : t('Not Accepted')}
                </Badge>
              </div>
              <div>
                <p className="text-sm font-medium text-gray-900">{t('Applied Date')}</p>
                <p className="text-sm text-gray-600">{new Date(candidate.application_date).toLocaleDateString()}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </PageTemplate>
  );
}