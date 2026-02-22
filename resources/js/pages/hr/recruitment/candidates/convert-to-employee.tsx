import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import MediaPicker from '@/components/MediaPicker';
import { getImagePath } from '@/utils/helpers';

export default function ConvertToEmployee() {
  const { t } = useTranslation();
  const { candidate, branches, departments, designations, documentTypes, shifts, attendancePolicies, generatedEmployeeId } = usePage().props as any;

  // Pre-fill form with candidate data
  const [formData, setFormData] = useState<Record<string, any>>({
    name: `${candidate.first_name} ${candidate.last_name}`,
    biometric_emp_id: '',
    email: candidate.email,
    password: '',
    phone: candidate.phone || '',
    date_of_birth: candidate.date_of_birth || '',
    gender: candidate.gender || '',
    branch_id: candidate.branch_id ? candidate.branch_id.toString() : '',
    department_id: candidate.department_id ? candidate.department_id.toString() : '',
    designation_id: '',
    shift_id: '',
    attendance_policy_id: '',
    date_of_joining: new Date().toISOString().split('T')[0],
    employment_type: 'Full-time',
    employee_status: 'active',
    address_line_1: candidate.address || '',
    address_line_2: '',
    city: candidate.city || '',
    state: candidate.state || '',
    country: candidate.country || '',
    postal_code: candidate.zip_code || '',
    emergency_contact_name: '',
    emergency_contact_relationship: '',
    emergency_contact_number: '',
    bank_name: '',
    account_holder_name: `${candidate.first_name} ${candidate.last_name}`,
    account_number: '',
    bank_identifier_code: '',
    bank_branch: '',
    tax_payer_id: '',
    salary: candidate.final_salary || '',
    documents: []
  });
  
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Filter departments based on selected branch
  const filteredDepartments = formData.branch_id
    ? departments.filter((dept: any) => String(dept.branch_id) === String(formData.branch_id))
    : departments;

  // Filter designations based on selected department
  const filteredDesignations = formData.department_id
    ? designations.filter((desig: any) => String(desig.department_id) === String(formData.department_id))
    : designations;

  const handleChange = (name: string, value: any) => {
    setFormData(prev => ({ ...prev, [name]: value }));

    // Clear error when field is changed
    if (errors[name]) {
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors[name];
        return newErrors;
      });
    }

    // Handle branch change - reset department and designation
    if (name === 'branch_id') {
      setFormData(prev => ({
        ...prev,
        branch_id: value,
        department_id: '',
        designation_id: ''
      }));
    }

    // Handle department change - reset designation
    if (name === 'department_id') {
      setFormData(prev => ({
        ...prev,
        department_id: value,
        designation_id: ''
      }));
    }
  };

  const handleDocumentChange = (index: number, field: string, value: any) => {
    const updatedDocuments = [...formData.documents];
    updatedDocuments[index] = { ...updatedDocuments[index], [field]: value };
    setFormData(prev => ({ ...prev, documents: updatedDocuments }));

    // Clear error
    const errorKey = `documents.${index}.${field}`;
    if (errors[errorKey]) {
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors[errorKey];
        return newErrors;
      });
    }
  };

  const addDocument = () => {
    setFormData(prev => ({
      ...prev,
      documents: [
        ...prev.documents,
        { document_type_id: '', file_path: '', expiry_date: '' }
      ]
    }));
  };

  const removeDocument = (index: number) => {
    const updatedDocuments = [...formData.documents];
    updatedDocuments.splice(index, 1);
    setFormData(prev => ({ ...prev, documents: updatedDocuments }));

    // Clear errors for this document
    const newErrors = { ...errors };
    Object.keys(newErrors).forEach(key => {
      if (key.startsWith(`documents.${index}.`)) {
        delete newErrors[key];
      }
    });
    setErrors(newErrors);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    // Create form data for submission
    const submitData = new FormData();

    // Add all form fields
    Object.entries(formData).forEach(([key, value]) => {
      if (key !== 'documents') {
        if (value !== null && value !== undefined && value !== '') {
          submitData.append(key, value);
        }
      }
    });

    // Add candidate ID
    submitData.append('candidate_id', candidate.id.toString());

    // Add documents
    formData.documents.forEach((doc: any, index: number) => {
      if (doc.document_type_id) {
        submitData.append(`documents[${index}][document_type_id]`, doc.document_type_id);
      }
      if (doc.file_path) {
        submitData.append(`documents[${index}][file_path]`, doc.file_path);
      }
      if (doc.expiry_date) {
        submitData.append(`documents[${index}][expiry_date]`, doc.expiry_date);
      }
    });

    // Submit the form to hr.employees.store route
    router.post(route('hr.employees.store'), submitData, {
      forceFormData: true,
      onSuccess: (page) => {
        setIsSubmitting(false);
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        }
        // Don't manually redirect - let the backend handle it
      },
      onError: (errors) => {
        setIsSubmitting(false);
        setErrors(errors);
        toast.error(t('Please correct the errors in the form'));
      }
    });
  };

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Recruitment'), href: route('hr.recruitment.candidates.index') },
    { title: t('Candidates'), href: route('hr.recruitment.candidates.index') },
    { title: t('Convert to Employee') }
  ];

  return (
    <PageTemplate
      title={t("Convert Candidate to Employee")}
      url={`/hr/recruitment/candidates/${candidate.id}/convert-to-employee`}
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
      {/* Candidate Info Card */}
      <Card className="mb-6">
        <CardHeader>
          <CardTitle>{t('Candidate Information')}</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
              <span className="font-medium">{t('Name')}:</span> {candidate.first_name} {candidate.last_name}
            </div>
            <div>
              <span className="font-medium">{t('Email')}:</span> {candidate.email}
            </div>
            <div>
              <span className="font-medium">{t('Job')}:</span> {candidate.job?.title}
            </div>
            <div>
              <span className="font-medium">{t('Experience')}:</span> {candidate.experience_years} {t('years')}
            </div>
            <div>
              <span className="font-medium">{t('Final Salary')}:</span> {candidate.final_salary ? window.appSettings?.formatCurrency(candidate.final_salary) : '-'}
            </div>
            <div>
              <span className="font-medium">{t('Status')}:</span> {candidate.status}
            </div>
          </div>
        </CardContent>
      </Card>

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Basic Information Card */}
        <Card>
          <CardHeader>
            <CardTitle>{t('Basic Information')}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="name" required>{t('Full Name')} </Label>
                <Input
                  id="name"
                  value={formData.name}
                  required
                  onChange={(e) => handleChange('name', e.target.value)}
                  className={errors.name ? 'border-red-500' : ''}
                />
                {errors.name && <p className="text-red-500 text-xs">{errors.name}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="employee_id" required>{t('Employee ID')}</Label>
                <Input
                  id="employee_id"
                  value={generatedEmployeeId}
                  readOnly
                  className="bg-muted"
                />
                <p className="text-sm text-muted-foreground">{t('Employee ID will be auto-generated')}</p>
              </div>

              <div className="space-y-2">
                <Label htmlFor="biometric_emp_id" required>{t('Employee Code')}</Label>
                <Input
                  id="biometric_emp_id"
                  required
                  value={formData.biometric_emp_id || ''}
                  onChange={(e) => handleChange('biometric_emp_id', e.target.value)}
                  className={errors.biometric_emp_id ? 'border-red-500' : ''}
                />
                {errors.biometric_emp_id && <p className="text-red-500 text-xs">{errors.biometric_emp_id}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="email" required>{t('Email')} </Label>
                <Input
                  id="email"
                  type="email"
                  required
                  value={formData.email}
                  onChange={(e) => handleChange('email', e.target.value)}
                  className={errors.email ? 'border-red-500' : ''}
                />
                {errors.email && <p className="text-red-500 text-xs">{errors.email}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="password" required>{t('Password')} </Label>
                <Input
                  id="password"
                  type="password"
                  required
                  value={formData.password}
                  onChange={(e) => handleChange('password', e.target.value)}
                  className={errors.password ? 'border-red-500' : ''}
                />
                {errors.password && <p className="text-red-500 text-xs">{errors.password}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="phone" required>{t('Phone Number')}</Label>
                <Input
                  id="phone"
                  required
                  value={formData.phone}
                  onChange={(e) => handleChange('phone', e.target.value)}
                  className={errors.phone ? 'border-red-500' : ''}
                />
                {errors.phone && <p className="text-red-500 text-xs">{errors.phone}</p>}
              </div>

              <div className="space-y-2" required>
                <Label htmlFor="date_of_birth">{t('Date of Birth')}</Label>
                <Input
                  id="date_of_birth"
                  required
                  type="date"
                  value={formData.date_of_birth}
                  onChange={(e) => handleChange('date_of_birth', e.target.value)}
                  className={errors.date_of_birth ? 'border-red-500' : ''}
                />
                {errors.date_of_birth && <p className="text-red-500 text-xs">{errors.date_of_birth}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="gender" required>{t('Gender')}</Label>
                <RadioGroup
                  value={formData.gender}
                  onValueChange={(value) => handleChange('gender', value)}
                  className="flex space-x-4"
                >
                  <div className="flex items-center space-x-2">
                    <RadioGroupItem value="male" id="gender-male" />
                    <Label htmlFor="gender-male">{t('Male')}</Label>
                  </div>
                  <div className="flex items-center space-x-2">
                    <RadioGroupItem value="female" id="gender-female" />
                    <Label htmlFor="gender-female">{t('Female')}</Label>
                  </div>
                  <div className="flex items-center space-x-2">
                    <RadioGroupItem value="other" id="gender-other" />
                    <Label htmlFor="gender-other">{t('Other')}</Label>
                  </div>
                </RadioGroup>
                {errors.gender && <p className="text-red-500 text-xs">{errors.gender}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="profile_image" required>{t('Profile Image')}</Label>
                <div className="flex flex-col gap-3">
                  <div className="border rounded-md p-4 flex items-center justify-center bg-muted/30 h-32">
                    {formData.profile_image ? (
                      <img
                        src={getImagePath(formData.profile_image)}
                        alt="Profile Image"
                        className="max-h-full max-w-full object-contain rounded-full"
                      />
                    ) : (
                      <div className="text-muted-foreground flex flex-col items-center gap-2">
                        <div className="h-12 w-12 bg-muted flex items-center justify-center rounded-full border border-dashed">
                          <span className="font-semibold text-xs text-muted-foreground">{t('Image')}</span>
                        </div>
                        <span className="text-xs">No image selected</span>
                      </div>
                    )}
                  </div>
                  <MediaPicker
                    label=""
                    value={formData.profile_image || ''}
                    onChange={(url) => handleChange('profile_image', url)}
                    placeholder="Select profile image..."
                    showPreview={false}
                  />
                </div>
                {errors.profile_image && <p className="text-red-500 text-xs">{errors.profile_image}</p>}
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Employment Details Card */}
        <Card>
          <CardHeader>
            <CardTitle>{t('Employment Details')}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="branch_id" required>{t('Branch')} </Label>
                <Select
                  value={formData.branch_id}
                  required
                  onValueChange={(value) => handleChange('branch_id', value)}
                >
                  <SelectTrigger className={errors.branch_id ? 'border-red-500' : ''}>
                    <SelectValue placeholder={t('Select Branch')} />
                  </SelectTrigger>
                  <SelectContent searchable={true}>
                    {branches.map((branch: any) => (
                      <SelectItem key={branch.id} value={branch.id.toString()}>
                        {branch.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.branch_id && <p className="text-red-500 text-xs">{errors.branch_id}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="department_id" required>{t('Department')} </Label>
                <Select
                  value={formData.department_id}
                  onValueChange={(value) => handleChange('department_id', value)}
                  disabled={!formData.branch_id}
                  required
                >
                  <SelectTrigger className={errors.department_id ? 'border-red-500' : ''}>
                    <SelectValue placeholder={formData.branch_id ? t('Select Department') : t('Select Branch First')} />
                  </SelectTrigger>
                  <SelectContent searchable={true}>
                    {filteredDepartments.map((department: any) => (
                      <SelectItem key={department.id} value={department.id.toString()}>
                        {department.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.department_id && <p className="text-red-500 text-xs">{errors.department_id}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="designation_id" required>{t('Designation')} </Label>
                <Select
                  value={formData.designation_id}
                  onValueChange={(value) => handleChange('designation_id', value)}
                  disabled={!formData.department_id}
                  required
                >
                  <SelectTrigger className={errors.designation_id ? 'border-red-500' : ''}>
                    <SelectValue placeholder={formData.department_id ? t('Select Designation') : t('Select Department First')} />
                  </SelectTrigger>
                  <SelectContent searchable={true}>
                    {filteredDesignations.map((designation: any) => (
                      <SelectItem key={designation.id} value={designation.id.toString()}>
                        {designation.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.designation_id && <p className="text-red-500 text-xs">{errors.designation_id}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="date_of_joining" required>{t('Date of Joining')} </Label>
                <Input
                  id="date_of_joining"
                  type="date"
                  required
                  value={formData.date_of_joining}
                  onChange={(e) => handleChange('date_of_joining', e.target.value)}
                  className={errors.date_of_joining ? 'border-red-500' : ''}
                />
                {errors.date_of_joining && <p className="text-red-500 text-xs">{errors.date_of_joining}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="employment_type">{t('Employment Type')}</Label>
                <Select
                  value={formData.employment_type}
                  onValueChange={(value) => handleChange('employment_type', value)}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Full-time">{t('Full-time')}</SelectItem>
                    <SelectItem value="Part-time">{t('Part-time')}</SelectItem>
                    <SelectItem value="Contract">{t('Contract')}</SelectItem>
                    <SelectItem value="Internship">{t('Internship')}</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="employee_status" required>{t('Employee Status')}</Label>
                <Select
                  value={formData.employee_status}
                  onValueChange={(value) => handleChange('employee_status', value)}
                >
                  <SelectTrigger className={errors.employee_status ? 'border-red-500' : ''}>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="active">{t('Active')}</SelectItem>
                    <SelectItem value="inactive">{t('Inactive')}</SelectItem>
                    <SelectItem value="probation">{t('Probation')}</SelectItem>
                    <SelectItem value="terminated">{t('Terminated')}</SelectItem>
                  </SelectContent>
                </Select>
                {errors.employee_status && <p className="text-red-500 text-xs">{errors.employee_status}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="shift_id">{t('Shift')}</Label>
                <Select
                  value={formData.shift_id}
                  onValueChange={(value) => handleChange('shift_id', value)}
                >
                  <SelectTrigger>
                    <SelectValue placeholder={t('Select Shift (Optional)')} />
                  </SelectTrigger>
                  <SelectContent>
                    {shifts?.map((shift: any) => (
                      <SelectItem key={shift.id} value={shift.id.toString()}>
                        {shift.name} ({shift.start_time} - {shift.end_time})
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="attendance_policy_id">{t('Attendance Policy')}</Label>
                <Select
                  value={formData.attendance_policy_id}
                  onValueChange={(value) => handleChange('attendance_policy_id', value)}
                >
                  <SelectTrigger>
                    <SelectValue placeholder={t('Select Attendance Policy (Optional)')} />
                  </SelectTrigger>
                  <SelectContent>
                    {attendancePolicies?.map((policy: any) => (
                      <SelectItem key={policy.id} value={policy.id.toString()}>
                        {policy.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Contact Information Card */}
        <Card>
          <CardHeader>
            <CardTitle>{t('Contact Information')}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="address_line_1" required>{t('Address Line 1')} </Label>
                <Input
                  id="address_line_1"
                  required
                  value={formData.address_line_1}
                  onChange={(e) => handleChange('address_line_1', e.target.value)}
                  className={errors.address_line_1 ? 'border-red-500' : ''}
                />
                {errors.address_line_1 && <p className="text-red-500 text-xs">{errors.address_line_1}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="address_line_2" required>{t('Address Line 2')}</Label>
                <Input
                  id="address_line_2"
                  required
                  value={formData.address_line_2}
                  onChange={(e) => handleChange('address_line_2', e.target.value)}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="city" required>{t('City')} </Label>
                <Input
                  id="city"
                  required
                  value={formData.city}
                  onChange={(e) => handleChange('city', e.target.value)}
                  className={errors.city ? 'border-red-500' : ''}
                />
                {errors.city && <p className="text-red-500 text-xs">{errors.city}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="state" required>{t('State/Province')} </Label>
                <Input
                  id="state"
                  required
                  value={formData.state}
                  onChange={(e) => handleChange('state', e.target.value)}
                  className={errors.state ? 'border-red-500' : ''}
                />
                {errors.state && <p className="text-red-500 text-xs">{errors.state}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="country" required>{t('Country')} </Label>
                <Input
                  id="country"
                  required
                  value={formData.country}
                  onChange={(e) => handleChange('country', e.target.value)}
                  className={errors.country ? 'border-red-500' : ''}
                />
                {errors.country && <p className="text-red-500 text-xs">{errors.country}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="postal_code" required>{t('Postal/Zip Code')} </Label>
                <Input
                  id="postal_code"
                  required
                  value={formData.postal_code}
                  onChange={(e) => handleChange('postal_code', e.target.value)}
                  className={errors.postal_code ? 'border-red-500' : ''}
                />
                {errors.postal_code && <p className="text-red-500 text-xs">{errors.postal_code}</p>}
              </div>
            </div>

            <div className="mt-6">
              <h3 className="text-lg font-medium mb-4">{t('Emergency Contact')}</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="emergency_contact_name" required>{t('Name')} </Label>
                  <Input
                    id="emergency_contact_name"
                    required
                    value={formData.emergency_contact_name}
                    onChange={(e) => handleChange('emergency_contact_name', e.target.value)}
                    className={errors.emergency_contact_name ? 'border-red-500' : ''}
                  />
                  {errors.emergency_contact_name && <p className="text-red-500 text-xs">{errors.emergency_contact_name}</p>}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="emergency_contact_relationship" required>{t('Relationship')} </Label>
                  <Input
                    id="emergency_contact_relationship"
                    required
                    value={formData.emergency_contact_relationship}
                    onChange={(e) => handleChange('emergency_contact_relationship', e.target.value)}
                    className={errors.emergency_contact_relationship ? 'border-red-500' : ''}
                  />
                  {errors.emergency_contact_relationship && <p className="text-red-500 text-xs">{errors.emergency_contact_relationship}</p>}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="emergency_contact_number" required>{t('Phone Number')} </Label>
                  <Input
                    id="emergency_contact_number"
                    required
                    value={formData.emergency_contact_number}
                    onChange={(e) => handleChange('emergency_contact_number', e.target.value)}
                    className={errors.emergency_contact_number ? 'border-red-500' : ''}
                  />
                  {errors.emergency_contact_number && <p className="text-red-500 text-xs">{errors.emergency_contact_number}</p>}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Banking Information Card */}
        <Card>
          <CardHeader>
            <CardTitle>{t('Banking Information')}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="bank_name" required>{t('Bank Name')} </Label>
                <Input
                  id="bank_name"
                  required
                  value={formData.bank_name}
                  onChange={(e) => handleChange('bank_name', e.target.value)}
                  className={errors.bank_name ? 'border-red-500' : ''}
                />
                {errors.bank_name && <p className="text-red-500 text-xs">{errors.bank_name}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="account_holder_name" required>{t('Account Holder Name')} </Label>
                <Input
                  id="account_holder_name"
                  required
                  value={formData.account_holder_name}
                  onChange={(e) => handleChange('account_holder_name', e.target.value)}
                  className={errors.account_holder_name ? 'border-red-500' : ''}
                />
                {errors.account_holder_name && <p className="text-red-500 text-xs">{errors.account_holder_name}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="account_number" required>{t('Account Number')} </Label>
                <Input
                  id="account_number"
                  required
                  value={formData.account_number}
                  onChange={(e) => handleChange('account_number', e.target.value)}
                  className={errors.account_number ? 'border-red-500' : ''}
                />
                {errors.account_number && <p className="text-red-500 text-xs">{errors.account_number}</p>}
              </div>

              <div className="space-y-2" >
                <Label htmlFor="bank_identifier_code" required>{t('Bank Identifier Code (BIC/SWIFT)')}</Label>
                <Input
                  id="bank_identifier_code"
                  required
                  value={formData.bank_identifier_code}
                  onChange={(e) => handleChange('bank_identifier_code', e.target.value)}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="bank_branch" required>{t('Bank Branch')}</Label>
                <Input
                  id="bank_branch"
                  required
                  value={formData.bank_branch}
                  onChange={(e) => handleChange('bank_branch', e.target.value)}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="tax_payer_id">{t('Tax Payer ID')}</Label>
                <Input
                  id="tax_payer_id"
                  value={formData.tax_payer_id}
                  onChange={(e) => handleChange('tax_payer_id', e.target.value)}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="salary" required>{t('Salary')} </Label>
                <Input
                  id="salary"
                  type="number"
                  min="0"
                  step="0.01"
                  required
                  value={formData.salary}
                  onChange={(e) => handleChange('salary', e.target.value)}
                  className={errors.salary ? 'border-red-500' : ''}
                />
                {errors.salary && <p className="text-red-500 text-xs">{errors.salary}</p>}
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Documents Card */}
        <Card>
          <CardHeader>
            <CardTitle>{t('Documents')}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {formData.documents.map((document: any, index: number) => (
              <div key={index} className="border rounded-md p-4 space-y-4">
                <div className="flex justify-between items-center">
                  <h3 className="text-lg font-medium">{t('Document')} #{index + 1}</h3>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => removeDocument(index)}
                  >
                    <Trash2 className="h-4 w-4 text-red-500" />
                  </Button>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>{t('Document Type')} </Label>
                    <Select
                      value={document.document_type_id}
                      onValueChange={(value) => handleDocumentChange(index, 'document_type_id', value)}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder={t('Select Document Type')} />
                      </SelectTrigger>
                      <SelectContent>
                        {documentTypes.map((type: any) => (
                          <SelectItem key={type.id} value={type.id.toString()}>
                            {type.name}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>

                  <div className="space-y-2">
                    <Label>{t('File')} </Label>
                    <MediaPicker
                      label=""
                      value={document.file_path || ''}
                      onChange={(url) => handleDocumentChange(index, 'file_path', url)}
                      placeholder="Select document file..."
                      showPreview={false}
                    />
                  </div>

                  <div className="space-y-2">
                    <Label>{t('Expiry Date')}</Label>
                    <Input
                      type="date"
                      value={document.expiry_date}
                      onChange={(e) => handleDocumentChange(index, 'expiry_date', e.target.value)}
                    />
                  </div>
                </div>
              </div>
            ))}

            <Button
              type="button"
              variant="outline"
              onClick={addDocument}
            >
              <Plus className="h-4 w-4 mr-2" />
              {t('Add Document')}
            </Button>
          </CardContent>
        </Card>

        {/* Submit Buttons */}
        <div className="flex justify-end space-x-4">
          <Button
            type="button"
            variant="outline"
            onClick={() => router.get(route('hr.recruitment.candidates.index'))}
          >
            {t('Cancel')}
          </Button>
          <Button
            type="submit"
            disabled={isSubmitting}
          >
            {isSubmitting ? t('Converting...') : t('Convert to Employee')}
          </Button>
        </div>
      </form>
    </PageTemplate>
  );
}