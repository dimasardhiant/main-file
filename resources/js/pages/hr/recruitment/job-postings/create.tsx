import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router, useForm } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { TagInput } from '@/components/ui/tag-input';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { ArrowLeft } from 'lucide-react';

export default function CreateJobPosting() {
  const { t } = useTranslation();
  const { jobTypes, locations, branches, departments, customQuestions, companySlug } = usePage().props as any;
  
  const { data, setData, post, processing, errors } = useForm({
    title: '',
    job_type_id: '',
    location_id: '',
    branch_id: '',
    department_id: '',
    priority: 'Low',
    skills: [],
    positions: 1,
    min_experience: 0,
    max_experience: '',
    min_salary: '',
    max_salary: '',
    description: '',
    requirements: '',
    education: '',
    benefits: '',
    start_date: '',
    application_deadline: '',
    application_type: 'existing',
    application_url: companySlug ? route('career.index', companySlug) : route('career.index'),
    code: '',
    custom_question: [],
    applicant: [],
    visibility: [],
    is_featured: false,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    toast.loading(t('Creating job posting...'));
    
    post(route('hr.recruitment.job-postings.store'), {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to create job posting'));
        }
      }
    });
  };

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Recruitment'), href: route('hr.recruitment.job-postings.index') },
    { title: t('Job Postings'), href: route('hr.recruitment.job-postings.index') },
    { title: t('Create') }
  ];

  return (
    <PageTemplate
      title={t('Create Job Posting')}
      breadcrumbs={breadcrumbs}
      actions={[
        {
          label: t('Back to List'),
          icon: <ArrowLeft className="h-4 w-4 mr-2" />,
          variant: 'outline',
          onClick: () => router.get(route('hr.recruitment.job-postings.index'))
        }
      ]}
    >
      <form onSubmit={handleSubmit} className="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>{t('Basic Information')}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label htmlFor="title" required>{t('Job Title')} </Label>
                <Input
                  id="title"
                  value={data.title}
                  onChange={(e) => setData('title', e.target.value)}
                  placeholder={t('Enter job title')}
                  required
                />
                {errors.title && <p className="text-sm text-red-500">{errors.title}</p>}
              </div>

              <div>
                <Label htmlFor="job_type_id" required>{t('Job Type')}</Label>
                <Select value={data.job_type_id} onValueChange={(value) => setData('job_type_id', value)}>
                  <SelectTrigger>
                    <SelectValue placeholder={t('Select Job Type')} />
                  </SelectTrigger>
                  <SelectContent searchable={true}>
                    {jobTypes?.map((type: any) => (
                      <SelectItem key={type.id} value={type.id.toString()}>
                        {type.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.job_type_id && <p className="text-sm text-red-500">{errors.job_type_id}</p>}
              </div>

              <div>
                <Label htmlFor="location_id" required>{t('Location')} </Label>
                <Select value={data.location_id} onValueChange={(value) => setData('location_id', value)}>
                  <SelectTrigger>
                    <SelectValue placeholder={t('Select Location')} />
                  </SelectTrigger>
                  <SelectContent searchable={true}>
                    {locations?.map((loc: any) => (
                      <SelectItem key={loc.id} value={loc.id.toString()}>
                        {loc.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.location_id && <p className="text-sm text-red-500">{errors.location_id}</p>}
              </div>

              <div>
                <Label htmlFor="branch_id" required>{t('Branch')}</Label>
                <Select value={data.branch_id} onValueChange={(value) => {
                  setData('branch_id', value);
                  setData('department_id', '');
                }}>
                  <SelectTrigger>
                    <SelectValue placeholder={t('Select Branch')} />
                  </SelectTrigger>
                  <SelectContent searchable={true}>
                    {branches?.map((branch: any) => (
                      <SelectItem key={branch.id} value={branch.id.toString()}>
                        {branch.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.branch_id && <p className="text-sm text-red-500">{errors.branch_id}</p>}
              </div>

              <div>
                <Label htmlFor="department_id">{t('Department')}</Label>
                <Select 
                  value={data.department_id} 
                  onValueChange={(value) => setData('department_id', value)}
                  disabled={!data.branch_id}
                >
                  <SelectTrigger>
                    <SelectValue placeholder={t('Select Department')} />
                  </SelectTrigger>
                  <SelectContent searchable={true}>
                    {departments?.filter((dept: any) => dept.branch_id === parseInt(data.branch_id)).map((dept: any) => (
                      <SelectItem key={dept.id} value={dept.id.toString()}>
                        {dept.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.department_id && <p className="text-sm text-red-500">{errors.department_id}</p>}
              </div>

              <div>
                <Label htmlFor="priority" required>{t('Priority')}</Label>
                <Select value={data.priority} onValueChange={(value) => setData('priority', value)}>
                  <SelectTrigger>
                    <SelectValue placeholder={t('Select Priority')} />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Low">{t('Low')}</SelectItem>
                    <SelectItem value="Medium">{t('Medium')}</SelectItem>
                    <SelectItem value="High">{t('High')}</SelectItem>
                  </SelectContent>
                </Select>
                {errors.priority && <p className="text-sm text-red-500">{errors.priority}</p>}
              </div>
              <div>
                <Label htmlFor="skills" required>{t('Required Skills')} </Label>
                <TagInput
                  value={data.skills}
                  onChange={(skills) => setData('skills', skills)}
                  placeholder={t('Type Required Skills and press Enter')}
                />
                {errors.skills && <p className="text-sm text-red-500">{errors.skills}</p>}
              </div>

              <div>
                <Label htmlFor="start_date" required>{t('Start Date')}</Label>
                <Input
                  id="start_date"
                  type="date"
                  value={data.start_date}
                  onChange={(e) => setData('start_date', e.target.value)}
                />
                {errors.start_date && <p className="text-sm text-red-500">{errors.start_date}</p>}
              </div>

              <div>
                <Label htmlFor="application_deadline" required>{t('Application Deadline')}</Label>
                <Input
                  id="application_deadline"
                  type="date"
                  value={data.application_deadline}
                  onChange={(e) => setData('application_deadline', e.target.value)}
                />
                {errors.application_deadline && <p className="text-sm text-red-500">{errors.application_deadline}</p>}
              </div>

              <div>
                <Label htmlFor="application_type" required>{t('Job Application')}</Label>
                <Select value={data.application_type} onValueChange={(value) => {
                  setData('application_type', value);
                  if (value === 'existing') {
                    setData('application_url', companySlug ? route('career.index', companySlug) : route('career.index'));
                  } else {
                    setData('application_url', '');
                  }
                }}>
                  <SelectTrigger>
                    <SelectValue placeholder={t('Select Application Type')} />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="existing">{t('Existing Link')}</SelectItem>
                    <SelectItem value="custom">{t('Custom Link')}</SelectItem>
                  </SelectContent>
                </Select>
                {errors.application_type && <p className="text-sm text-red-500">{errors.application_type}</p>}
              </div>

              <div>
                <Label htmlFor="application_url" required>{t('Application URL')}</Label>
                <Input
                  id="application_url"
                  value={data.application_url}
                  onChange={(e) => setData('application_url', e.target.value)}
                  placeholder={t('Enter application URL')}
                  disabled={data.application_type === 'existing'}
                />
                {errors.application_url && <p className="text-sm text-red-500">{errors.application_url}</p>}
              </div>

              

              <div>
                <Label htmlFor="positions" required>{t('Number of Positions')} </Label>
                <Input
                  id="positions"
                  type="number"
                  min="1"
                  value={data.positions}
                  onChange={(e) => setData('positions', parseInt(e.target.value) || 1)}
                  placeholder={t('Enter number of positions')}
                />
                {errors.positions && <p className="text-sm text-red-500">{errors.positions}</p>}
              </div>
            </div>

            <div className="flex items-center space-x-2">
              <Checkbox
                id="is_featured"
                checked={data.is_featured}
                onCheckedChange={(checked) => setData('is_featured', checked as boolean)}
              />
              <Label htmlFor="is_featured">{t('Featured Job')}</Label>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>{t('Experience & Salary')}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label htmlFor="min_experience" required>{t('Min Experience (Years)')} </Label>
                <Input
                  id="min_experience"
                  type="number"
                  min="0"
                  step="0.1"
                  value={data.min_experience}
                  onChange={(e) => setData('min_experience', parseFloat(e.target.value) || 0)}
                />
                {errors.min_experience && <p className="text-sm text-red-500">{errors.min_experience}</p>}
              </div>

              <div>
                <Label htmlFor="max_experience" required>{t('Max Experience (Years)')}</Label>
                <Input
                  id="max_experience"
                  type="number"
                  min="0"
                  step="0.1"
                  value={data.max_experience}
                  onChange={(e) => setData('max_experience', e.target.value)}
                />
                {errors.max_experience && <p className="text-sm text-red-500">{errors.max_experience}</p>}
              </div>

              <div>
                <Label htmlFor="min_salary" required> {t('Min Salary')}</Label>
                <Input
                  id="min_salary"
                  type="number"
                  min="0"
                  step="0.01"
                  value={data.min_salary}
                  onChange={(e) => setData('min_salary', e.target.value)}
                />
                {errors.min_salary && <p className="text-sm text-red-500">{errors.min_salary}</p>}
              </div>

              <div>
                <Label htmlFor="max_salary" required>{t('Max Salary')}</Label>
                <Input
                  id="max_salary"
                  type="number"
                  min="0"
                  step="0.01"
                  value={data.max_salary}
                  onChange={(e) => setData('max_salary', e.target.value)}
                />
                {errors.max_salary && <p className="text-sm text-red-500">{errors.max_salary}</p>}
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>{t('Job Details')}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <Label htmlFor="description" required>{t('Job Description')}</Label>
              <RichTextEditor
                content={data.description}
                onChange={(content) => setData('description', content)}
                placeholder={t('Enter job description...')}
                className="[&_.ProseMirror]:min-h-[150px]"
              />
              {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
            </div>

            <div>
              <Label htmlFor="requirements" required>{t('Requirements')}</Label>
              <RichTextEditor
                content={data.requirements}
                onChange={(content) => setData('requirements', content)}
                placeholder={t('Enter job requirements...')}
                className="[&_.ProseMirror]:min-h-[150px]"
              />
              {errors.requirements && <p className="text-sm text-red-500">{errors.requirements}</p>}
            </div>

            <div>
              <Label htmlFor="benefits" required>{t('Benefits')}</Label>
              <RichTextEditor
                content={data.benefits}
                onChange={(content) => setData('benefits', content)}
                placeholder={t('Enter job benefits...')}
                className="[&_.ProseMirror]:min-h-[120px]"
              />
              {errors.benefits && <p className="text-sm text-red-500">{errors.benefits}</p>}
            </div>
          </CardContent>
        </Card>

        {customQuestions && customQuestions.length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle>{t('Custom Questions')}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {customQuestions.map((question: any) => (
                <div key={question.id} className="flex items-center space-x-2">
                  <Checkbox
                    id={`question_${question.id}`}
                    checked={data.custom_question.includes(question.id)}
                    onCheckedChange={(checked) => {
                      if (checked) {
                        setData('custom_question', [...data.custom_question, question.id]);
                      } else {
                        setData('custom_question', data.custom_question.filter(id => id !== question.id));
                      }
                    }}
                  />
                  <Label htmlFor={`question_${question.id}`} className="flex-1">
                    {question.question}
                    {question.required === 1 && <span className="text-red-500 ml-1">*</span>}
                  </Label>
                </div>
              ))}
            </CardContent>
          </Card>
        )}

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <Card>
            <CardHeader>
              <CardTitle>{t('Need to Ask?')}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {[
                { key: 'gender', label: t('Gender') },
                { key: 'date_of_birth', label: t('Date Of Birth') },
              ].map((item) => (
                <div key={item.key} className="flex items-center space-x-2">
                  <Checkbox
                    id={`applicant_${item.key}`}
                    checked={data.applicant.includes(item.key)}
                    onCheckedChange={(checked) => {
                      if (checked) {
                        setData('applicant', [...data.applicant, item.key]);
                      } else {
                        setData('applicant', data.applicant.filter(key => key !== item.key));
                      }
                    }}
                  />
                  <Label htmlFor={`applicant_${item.key}`}>{item.label}</Label>
                </div>
              ))}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>{t('Need to Show Option?')}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {[
                { key: 'cover_letter', label: t('Cover Letter') },
                { key: 'terms_and_conditions', label: t('Terms And Conditions') }
              ].map((item) => (
                <div key={item.key} className="flex items-center space-x-2">
                  <Checkbox
                    id={`visibility_${item.key}`}
                    checked={data.visibility.includes(item.key)}
                    onCheckedChange={(checked) => {
                      if (checked) {
                        setData('visibility', [...data.visibility, item.key]);
                      } else {
                        setData('visibility', data.visibility.filter(key => key !== item.key));
                      }
                    }}
                  />
                  <Label htmlFor={`visibility_${item.key}`}>{item.label}</Label>
                </div>
              ))}
            </CardContent>
          </Card>
        </div>

        <div className="flex justify-end space-x-2">
          <Button
            type="button"
            variant="outline"
            onClick={() => router.get(route('hr.recruitment.job-postings.index'))}
          >
            {t('Cancel')}
          </Button>
          <Button type="submit" disabled={processing}>
            {processing ? t('Creating...') : t('Create Job Posting')}
          </Button>
        </div>
      </form>
    </PageTemplate>
  );
}