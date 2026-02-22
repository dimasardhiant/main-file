import { useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { ArrowLeft, Upload, FileText, User, Mail, Phone, MapPin } from 'lucide-react';
import CareerHeader from '@/components/career/CareerHeader';
import CareerFooter from '@/components/career/CareerFooter';
import { getImagePath } from '@/utils/helpers';
import { useTranslation } from 'react-i18next';
import { useFavicon } from '@/hooks/use-favicon';
import { useBrandTheme } from '@/hooks/use-brand-theme';

export default function JobApplication() {
  const { t } = useTranslation();
  const { jobPosting, customQuestions, candidateSources, applicantFields, companyId, companySettings, userSlug } = usePage().props as any;
  const visibilityFields = jobPosting?.visibility || [];
  const [showSuccessModal, setShowSuccessModal] = useState(false);
  const [resumeFile, setResumeFile] = useState<File | null>(null);
  const [coverLetterFile, setCoverLetterFile] = useState<File | null>(null);

  // Use favicon hook and brand theme like other career pages
  useFavicon();
  useBrandTheme();

  const { data, setData, post, processing, errors } = useForm({
    // Personal Information
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    state: '',
    zip_code: '',
    country: 'Afghanistan',
    gender: 'male',
    date_of_birth: '',

    // Professional Information
    current_position: '',
    current_company: '',
    current_salary: '',
    experience_years: '',
    expected_salary: '',

    // Application Details
    coverletter_message: '',
    source_id: candidateSources && candidateSources.length > 0 ? candidateSources[0].id.toString() : 'company-website',

    // Files
    resume: null,
    cover_letter_file: null,

    // Checkboxes
    terms_condition_check: false,
  });

  const handleFileUpload = (file: File, type: 'resume' | 'coverLetter') => {
    if (type === 'resume') {
      setResumeFile(file);
      setData('resume', file);
    } else {
      setCoverLetterFile(file);
      setData('cover_letter_file', file);
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    // Process custom questions
    let customAnswers = {};
    if (customQuestions && customQuestions.length > 0) {
      customQuestions.forEach(question => {
        const answer = data[`custom_question_${question.id}`] || '';
        customAnswers[question.question] = answer;
      });
    }

    // Create FormData manually
    const formData = new FormData();
    
    // Add all fields except terms_condition_check
    Object.keys(data).forEach(key => {
      if (key !== 'terms_condition_check' && key !== 'resume' && key !== 'cover_letter_file') {
        formData.append(key, data[key] || '');
      }
    });
    
    // Add processed values
    formData.append('terms_condition_check', (data.terms_condition_check === true || data.terms_condition_check === '1' || data.terms_condition_check === 1) ? 'on' : 'off');
    
    if (Object.keys(customAnswers).length > 0) {
      formData.append('custom_question', JSON.stringify(customAnswers));
    }
    
    // Add files
    if (data.resume) {
      formData.append('resume', data.resume);
    }
    if (data.cover_letter_file) {
      formData.append('cover_letter_file', data.cover_letter_file);
    }

    post(route('career.job.submit', [userSlug, jobPosting.code]), {
      data: formData,
      forceFormData: true,
      onSuccess: () => {
        // Reset form data
        setData({
          first_name: '',
          last_name: '',
          email: '',
          phone: '',
          address: '',
          city: '',
          state: '',
          zip_code: '',
          country: 'Afghanistan',
          gender: 'male',
          date_of_birth: '',
          current_position: '',
          current_company: '',
          current_salary: '',
          experience_years: '',
          expected_salary: '',
          coverletter_message: '',
          source_id: candidateSources && candidateSources.length > 0 ? candidateSources[0].id.toString() : 'company-website',
          resume: null,
          cover_letter_file: null,
          terms_condition_check: false,
        });
        
        // Reset file states
        setResumeFile(null);
        setCoverLetterFile(null);
        
        // Show success modal
        setShowSuccessModal(true);
        setTimeout(() => {
          setShowSuccessModal(false);
          window.location.href = route('career.index', userSlug);
        }, 5000);
      },
      onError: (errors) => {
        console.error('Submission errors:', errors);
      }
    });
  };

  return (
    <>
      <Head title={`${t('Apply for')} ${jobPosting.title} - ${t('Career Application')}`}>
        {companySettings?.favIcon && (
          <>
            <link rel="icon" href={getImagePath(companySettings.favIcon)} />
            <link rel="shortcut icon" href={getImagePath(companySettings.favIcon)} />
            <link rel="apple-touch-icon" href={getImagePath(companySettings.favIcon)} />
          </>
        )}
      </Head>

      <div className="min-h-screen bg-gray-50">
        {/* Success Modal with Animation */}
        {showSuccessModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 pointer-events-none">
            {/* Modal */}
            <div className="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-auto transform transition-all duration-300 animate-in zoom-in-95 slide-in-from-bottom-4 pointer-events-auto">
              <div className="p-6 text-center">
                {/* Success Icon with Animation */}
                <div className="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4 animate-in zoom-in duration-500 delay-200">
                  <svg className="h-8 w-8 text-green-600 animate-bounce" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                  </svg>
                </div>
                
                {/* Title */}
                <h3 className="text-lg font-semibold text-gray-900 mb-2 animate-in slide-in-from-bottom-2 duration-500 delay-300">
                  {t('Application Submitted Successfully!')}
                </h3>
                
                {/* Message */}
                <p className="text-sm text-gray-600 mb-6 animate-in slide-in-from-bottom-2 duration-500 delay-400">
                  {t('We will review your application and get back to you soon.')}
                </p>
                
                {/* Close Button */}
                <button
                  onClick={() => {
                    setShowSuccessModal(false);
                    window.location.href = route('career.index', userSlug);
                  }}
                  className="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors duration-200 animate-in slide-in-from-bottom-2 duration-500 delay-500"
                >
                  {t('Close')}
                </button>
              </div>
            </div>
          </div>
        )}
        
        <CareerHeader logoOnly={true} companySettings={companySettings} />

        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          {/* Back Button */}
          <div className="mb-6">
            <Link href={route('career.job-details', [userSlug, jobPosting.code])} className="inline-flex items-center text-blue-600 hover:text-blue-800">
              <ArrowLeft className="h-4 w-4 mr-2" />
              {t('Back to Job Details')}
            </Link>
          </div>

          {/* Job Info Header */}
          <Card className="mb-8">
            <CardContent className="p-6">
              <div>
                <h1 className="text-2xl font-bold text-gray-900 mb-2">
                  {t('Apply for')} {jobPosting.title}
                </h1>
                <p className="text-gray-600">
                  {jobPosting.branch?.name || 'General'} • {jobPosting.location?.name || 'Remote'} • {jobPosting.job_type?.name || 'Full-time'}
                </p>
              </div>
            </CardContent>
          </Card>

          <form onSubmit={handleSubmit} className="space-y-8">
            {/* General Error Display */}
            {errors.error && (
              <div className="bg-red-50 border border-red-200 rounded-md p-4">
                <p className="text-sm text-red-600">{errors.error}</p>
              </div>
            )}

            {/* Personal Information */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                  <User className="h-5 w-5" />
                  {t('Personal Information')}
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <Label required htmlFor="firstName" className="text-sm font-medium text-gray-700"> {t('First Name')} </Label>
                    <Input
                      id="firstName"
                      value={data.first_name}
                      onChange={(e) => setData('first_name', e.target.value)}
                      placeholder={t('Enter your first name')}
                      className="mt-1"
                      required
                    />
                    {errors.first_name && <p className="text-sm text-red-500 mt-1">{errors.first_name}</p>}
                  </div>
                  <div>
                    <Label htmlFor="lastName" className="text-sm font-medium text-gray-700" required>{t('Last Name')} </Label>
                    <Input
                      id="lastName"
                      value={data.last_name}
                      onChange={(e) => setData('last_name', e.target.value)}
                      placeholder={t('Enter your last name')}
                      className="mt-1"
                      required
                    />
                    {errors.last_name && <p className="text-sm text-red-500 mt-1">{errors.last_name}</p>}
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <Label htmlFor="email" className="text-sm font-medium text-gray-700" required>{t('Email Address')}</Label>
                    <Input
                      id="email"
                      type="email"
                      value={data.email}
                      onChange={(e) => setData('email', e.target.value)}
                      placeholder="your.email@example.com"
                      className="mt-1"
                      required
                    />
                    {errors.email && <p className="text-sm text-red-500 mt-1">{errors.email}</p>}
                  </div>
                  <div>
                    <Label htmlFor="phone" className="text-sm font-medium text-gray-700" required>{t('Phone Number')}</Label>
                    <Input
                      id="phone"
                      type="tel"
                      value={data.phone}
                      onChange={(e) => setData('phone', e.target.value)}
                      placeholder="+1 (555) 123-4567"
                      className="mt-1"
                      required
                    />
                    {errors.phone && <p className="text-sm text-red-500 mt-1">{errors.phone}</p>}
                  </div>
                </div>
                {applicantFields && applicantFields.length > 0 && (
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {applicantFields.includes('gender') && (
                      <div>
                        <Label htmlFor="gender" className="text-sm font-medium text-gray-700">{t('Gender')}</Label>
                        <Select value={data.gender} onValueChange={(value) => setData('gender', value)}>
                          <SelectTrigger className="mt-1">
                            <SelectValue placeholder={t('Select Gender')} />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="male">{t('Male')}</SelectItem>
                            <SelectItem value="female">{t('Female')}</SelectItem>
                            <SelectItem value="other">{t('Other')}</SelectItem>
                          </SelectContent>
                        </Select>
                        {errors.gender && <p className="text-sm text-red-500 mt-1">{errors.gender}</p>}
                      </div>
                    )}
                    {applicantFields.includes('date_of_birth') && (
                      <div>
                        <Label htmlFor="date_of_birth" className="text-sm font-medium text-gray-700">{t('Date of Birth')}</Label>
                        <Input
                          id="date_of_birth"
                          type="date"
                          value={data.date_of_birth}
                          onChange={(e) => setData('date_of_birth', e.target.value)}
                          className="mt-1"
                        />
                        {errors.date_of_birth && <p className="text-sm text-red-500 mt-1">{errors.date_of_birth}</p>}
                      </div>
                    )}
                  </div>
                )}



                <div>
                  <Label htmlFor="address" className="text-sm font-medium text-gray-700" required>{t('Address')}</Label>
                  <Input
                    id="address"
                    value={data.address}
                    onChange={(e) => setData('address', e.target.value)}
                    placeholder={t('Street address')}
                    className="mt-1"
                  />
                  {errors.address && <p className="text-sm text-red-500 mt-1">{errors.address}</p>}
                </div>

                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div>
                    <Label htmlFor="city" className="text-sm font-medium text-gray-700" required>{t('City')}</Label>
                    <Input
                      id="city"
                      value={data.city}
                      onChange={(e) => setData('city', e.target.value)}
                      placeholder={t('City')}
                      className="mt-1"
                    />
                    {errors.city && <p className="text-sm text-red-500 mt-1">{errors.city}</p>}
                  </div>
                  <div>
                    <Label htmlFor="state" className="text-sm font-medium text-gray-700" required>{t('State')}</Label>
                    <Input
                      id="state"
                      value={data.state}
                      onChange={(e) => setData('state', e.target.value)}
                      placeholder={t('State')}
                      className="mt-1"
                    />
                    {errors.state && <p className="text-sm text-red-500 mt-1">{errors.state}</p>}
                  </div>
                  <div>
                    <Label htmlFor="zip_code" className="text-sm font-medium text-gray-700" required>{t('ZIP Code')}</Label>
                    <Input
                      id="zip_code"
                      value={data.zip_code}
                      onChange={(e) => setData('zip_code', e.target.value)}
                      placeholder="12345"
                      className="mt-1"
                    />
                    {errors.zip_code && <p className="text-sm text-red-500 mt-1">{errors.zip_code}</p>}
                  </div>
                  <div>
                    <Label htmlFor="country" className="text-sm font-medium text-gray-700" required>{t('Country')}</Label>
                    <Select value={data.country} onValueChange={(value) => setData('country', value)}>
                      <SelectTrigger className="mt-1">
                        <SelectValue placeholder={t('Select Country')} />
                      </SelectTrigger>
                      <SelectContent searchable={true}>
                        <SelectItem value="Afghanistan">{t('Afghanistan')}</SelectItem>
                        <SelectItem value="Albania">{t('Albania')}</SelectItem>
                        <SelectItem value="Algeria">{t('Algeria')}</SelectItem>
                        <SelectItem value="Argentina">{t('Argentina')}</SelectItem>
                        <SelectItem value="Australia">{t('Australia')}</SelectItem>
                        <SelectItem value="Belgium">{t('Belgium')}</SelectItem>
                        <SelectItem value="Brazil">{t('Brazil')}</SelectItem>
                        <SelectItem value="Canada">{t('Canada')}</SelectItem>
                        <SelectItem value="China">{t('China')}</SelectItem>
                        <SelectItem value="Colombia">{t('Colombia')}</SelectItem>
                        <SelectItem value="Denmark">{t('Denmark')}</SelectItem>
                        <SelectItem value="Egypt">{t('Egypt')}</SelectItem>
                        <SelectItem value="Finland">{t('Finland')}</SelectItem>
                        <SelectItem value="France">{t('France')}</SelectItem>
                        <SelectItem value="Germany">{t('Germany')}</SelectItem>
                        <SelectItem value="Greece">{t('Greece')}</SelectItem>
                        <SelectItem value="India">{t('India')}</SelectItem>
                        <SelectItem value="Indonesia">{t('Indonesia')}</SelectItem>
                        <SelectItem value="Ireland">{t('Ireland')}</SelectItem>
                        <SelectItem value="Italy">{t('Italy')}</SelectItem>
                        <SelectItem value="Japan">{t('Japan')}</SelectItem>
                        <SelectItem value="Kenya">{t('Kenya')}</SelectItem>
                        <SelectItem value="Malaysia">{t('Malaysia')}</SelectItem>
                        <SelectItem value="Mexico">{t('Mexico')}</SelectItem>
                        <SelectItem value="Netherlands">{t('Netherlands')}</SelectItem>
                        <SelectItem value="New Zealand">{t('New Zealand')}</SelectItem>
                        <SelectItem value="Nigeria">{t('Nigeria')}</SelectItem>
                        <SelectItem value="Norway">{t('Norway')}</SelectItem>
                        <SelectItem value="Pakistan">{t('Pakistan')}</SelectItem>
                        <SelectItem value="Philippines">{t('Philippines')}</SelectItem>
                        <SelectItem value="Poland">{t('Poland')}</SelectItem>
                        <SelectItem value="Portugal">{t('Portugal')}</SelectItem>
                        <SelectItem value="Russia">{t('Russia')}</SelectItem>
                        <SelectItem value="Saudi Arabia">{t('Saudi Arabia')}</SelectItem>
                        <SelectItem value="Singapore">{t('Singapore')}</SelectItem>
                        <SelectItem value="South Africa">{t('South Africa')}</SelectItem>
                        <SelectItem value="South Korea">{t('South Korea')}</SelectItem>
                        <SelectItem value="Spain">{t('Spain')}</SelectItem>
                        <SelectItem value="Sweden">{t('Sweden')}</SelectItem>
                        <SelectItem value="Switzerland">{t('Switzerland')}</SelectItem>
                        <SelectItem value="Thailand">{t('Thailand')}</SelectItem>
                        <SelectItem value="Turkey">{t('Turkey')}</SelectItem>
                        <SelectItem value="United Arab Emirates">{t('United Arab Emirates')}</SelectItem>
                        <SelectItem value="United Kingdom">{t('United Kingdom')}</SelectItem>
                        <SelectItem value="United States">{t('United States')}</SelectItem>
                        <SelectItem value="Vietnam">{t('Vietnam')}</SelectItem>
                      </SelectContent>
                    </Select>
                    {errors.country && <p className="text-sm text-red-500 mt-1">{errors.country}</p>}
                  </div>
                </div>


              </CardContent>
            </Card>

            {/* Professional Information */}
            <Card>
              <CardHeader>
                <CardTitle className="text-lg font-semibold">{t('Professional Information')}</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <Label htmlFor="currentPosition" className="text-sm font-medium text-gray-700" required>{t('Current Position')}</Label>
                    <Input
                      id="currentPosition"
                      value={data.current_position}
                      onChange={(e) => setData('current_position', e.target.value)}
                      placeholder={t('e.g., Software Engineer')}
                      className="mt-1"
                    />
                    {errors.current_position && <p className="text-sm text-red-500 mt-1">{errors.current_position}</p>}
                  </div>
                  <div>
                    <Label htmlFor="currentCompany" className="text-sm font-medium text-gray-700" required>{t('Current Company')}</Label>
                    <Input
                      id="currentCompany"
                      value={data.current_company}
                      onChange={(e) => setData('current_company', e.target.value)}
                      placeholder={t('e.g., Tech Corp')}
                      className="mt-1"
                    />
                    {errors.current_company && <p className="text-sm text-red-500 mt-1">{errors.current_company}</p>}
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <Label htmlFor="experience" className="text-sm font-medium text-gray-700" required>{t('Years of Experience')}</Label>
                    <Input
                      id="experience"
                      type="number"
                      step="0.5"
                      min="0"
                      max="50"
                      value={data.experience_years}
                      onChange={(e) => setData('experience_years', e.target.value)}
                      placeholder={t('e.g., 2.5')}
                      className="mt-1"
                      required
                    />
                    {errors.experience_years && <p className="text-sm text-red-500 mt-1">{errors.experience_years}</p>}
                  </div>
                  <div>
                    <Label required htmlFor="currentSalary" className="text-sm font-medium text-gray-700">{t('Current Salary')}</Label>
                    <Input
                      id="currentSalary"
                      type="number"
                      step="0.01"
                      min="0"
                      value={data.current_salary}
                      onChange={(e) => setData('current_salary', e.target.value)}
                      placeholder="e.g., 60000"
                      className="mt-1"
                    />
                    {errors.current_salary && <p className="text-sm text-red-500 mt-1">{errors.current_salary}</p>}
                  </div>
                  <div>
                    <Label htmlFor="expectedSalary" className="text-sm font-medium text-gray-700" required>{t('Expected Salary')}</Label>
                    <Input
                      id="expectedSalary"
                      type="number"
                      step="0.01"
                      min="0"
                      value={data.expected_salary}
                      onChange={(e) => setData('expected_salary', e.target.value)}
                      placeholder="e.g., 80000"
                      className="mt-1"
                    />
                    {errors.expected_salary && <p className="text-sm text-red-500 mt-1">{errors.expected_salary}</p>}
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Documents */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                  <FileText className="h-5 w-5" />
                  {t('Documents')}
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-6">
                <div>
                  <Label className="text-sm font-medium text-gray-700" required>{t('Resume/CV')}</Label>
                  <div className="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-400 transition-colors">
                    <div className="space-y-2 text-center">
                      <Upload className="mx-auto h-10 w-10 text-gray-400" />
                      <div className="text-sm text-gray-600">
                        <label htmlFor="resume" className="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-blue-500">
                          <span>{t('Upload your resume')}</span>
                          <input
                            id="resume"
                            type="file"
                            className="sr-only"
                            onChange={(e) => e.target.files?.[0] && handleFileUpload(e.target.files[0], 'resume')}
                          />
                        </label>
                        <span className="text-gray-500"> {t('or drag and drop')}</span>
                      </div>
                      {resumeFile && (
                        <p className="text-sm text-green-600 font-medium flex items-center justify-center gap-1">
                          <span className="text-green-500">✓</span> {resumeFile.name}
                        </p>
                      )}
                      {errors.resume && <p className="text-sm text-red-500 mt-1">{errors.resume}</p>}
                    </div>
                  </div>
                </div>

                {visibilityFields && visibilityFields.includes('cover_letter') && (
                  <div>
                    <Label className="text-sm font-medium text-gray-700" required>{t('Cover Letter')} <span className="text-gray-500 text-xs">({t('Optional')})</span></Label>
                    <div className="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-400 transition-colors">
                      <div className="space-y-2 text-center">
                        <Upload className="mx-auto h-10 w-10 text-gray-400" />
                        <div className="text-sm text-gray-600">
                          <label htmlFor="coverLetterFile" className="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-blue-500">
                            <span>{t('Upload cover letter')}</span>
                            <input
                              id="coverLetterFile"
                              type="file"
                              className="sr-only"
                              onChange={(e) => e.target.files?.[0] && handleFileUpload(e.target.files[0], 'coverLetter')}
                            />
                          </label>
                          <span className="text-gray-500"> {t('or drag and drop')}</span>
                        </div>
                        {coverLetterFile && (
                          <p className="text-sm text-green-600 font-medium flex items-center justify-center gap-1">
                            <span className="text-green-500">✓</span> {coverLetterFile.name}
                          </p>
                        )}
                        {errors.cover_letter_file && <p className="text-sm text-red-500 mt-1">{errors.cover_letter_file}</p>}
                      </div>
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Additional Information */}
            <Card>
              <CardHeader>
                <CardTitle className="text-lg font-semibold">{t('Additional Information')}</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                {visibilityFields && visibilityFields.includes('cover_letter') && (
                  <div>
                    <Label htmlFor="coverLetter" className="text-sm font-medium text-gray-700" required>{t('Cover Letter Message')}</Label>
                    <Textarea
                      id="coverLetter"
                      value={data.coverletter_message}
                      onChange={(e) => setData('coverletter_message', e.target.value)}
                      placeholder={t('Tell us why you\'re interested in this position...')}
                      rows={4}
                      className="mt-1 resize-none"
                    />
                    {errors.coverletter_message && <p className="text-sm text-red-500 mt-1">{errors.coverletter_message}</p>}
                  </div>
                )}

                <div>
                  <Label required htmlFor="howDidYouHear" className="text-sm font-medium text-gray-700">{t('How did you hear about this position?')}</Label>
                  <Select value={data.source_id} onValueChange={(value) => setData('source_id', value)}>
                    <SelectTrigger className="mt-1">
                      <SelectValue placeholder={t('Select an option')} />
                    </SelectTrigger>
                    <SelectContent searchable={true}>
                      {candidateSources && candidateSources.length > 0 && (
                        candidateSources.map((source) => (
                          <SelectItem key={source.id} value={source.id.toString()}>{source.name}</SelectItem>
                        ))
                      )}
                    </SelectContent>
                  </Select>
                  {errors.source_id && <p className="text-sm text-red-500 mt-1">{errors.source_id}</p>}
                </div>
              </CardContent>
            </Card>

            {/* Custom Questions */}
            <Card>
              <CardHeader>
                <CardTitle className="text-lg font-semibold">{t('Additional Questions')}</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                {customQuestions && customQuestions.length > 0 ? (
                  customQuestions.map((question, index) => (
                    <div key={question.id}>
                      <Label className="text-sm font-medium text-gray-700" required={question.required === 1}>
                        {question.question}
                      </Label>
                      <Textarea
                        value={data[`custom_question_${question.id}`] || ''}
                        onChange={(e) => setData(`custom_question_${question.id}`, e.target.value)}
                        placeholder={t('Your answer...')}
                        rows={3}
                        className="mt-1 resize-none"
                        required={question.required === 1}
                      />
                      {errors.custom_question && <p className="text-sm text-red-500 mt-1">{errors.custom_question}</p>}
                    </div>
                  ))
                ) : (
                  <p className="text-sm text-gray-600">{t('No additional questions at this time.')}</p>
                )}
              </CardContent>
            </Card>

            {/* Terms and Conditions */}
            {visibilityFields && visibilityFields.includes('terms_and_conditions') && (
              <Card>
                <CardContent className="pt-6 space-y-4">
                  <div className="flex items-start space-x-3">
                    <Checkbox
                      id="acceptTerms"
                      checked={data.terms_condition_check || false}
                      onCheckedChange={(checked) => setData('terms_condition_check', checked as boolean)}
                      className="mt-0.5"
                    />
                    <Label htmlFor="acceptTerms" className="text-sm leading-5 text-gray-700">
                      {t('I accept the terms and conditions for this position')}
                    </Label>
                  </div>
                  {errors.terms_condition_check && <p className="text-sm text-red-500 mt-1">{errors.terms_condition_check}</p>}
                </CardContent>
              </Card>
            )}

            {/* Submit Button */}
            <div className="flex justify-end space-x-4">
              <Link href={route('career.job-details', [userSlug, jobPosting.code])}>
                <Button type="button" variant="outline">
                  {t('Cancel')}
                </Button>
              </Link>
              <Button
                type="submit"
                disabled={processing || !data.resume}
                className="px-8"
              >
                {processing ? t('Submitting...') : t('Submit Application')}
              </Button>
            </div>
          </form>
        </div>

        <CareerFooter companySettings={companySettings} />
      </div>
    </>
  );
}