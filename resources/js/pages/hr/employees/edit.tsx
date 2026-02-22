// pages/hr/employees/edit.tsx
import { useState, useEffect } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';

import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import MediaPicker from '@/components/MediaPicker';
import { getImagePath } from '@/utils/helpers';

export default function EmployeeEdit() {
  const { t } = useTranslation();
  const { employee, branches, departments, designations, documentTypes, shifts, attendancePolicies } = usePage().props as any;

  // State
  const [formData, setFormData] = useState<Record<string, any>>({
    name: employee.name || '',
    employee_id: employee.employee?.employee_id || '',
    biometric_emp_id: employee.employee?.biometric_emp_id || '',
    email: employee.email || '',
    password: '',
    phone: employee.employee?.phone || '',
    date_of_birth: employee.employee?.date_of_birth || '',
    gender: employee.employee?.gender || '',
    branch_id: employee.employee?.branch_id ? employee.employee.branch_id.toString() : '',
    department_id: employee.employee?.department_id ? employee.employee.department_id.toString() : '',
    designation_id: employee.employee?.designation_id ? employee.employee.designation_id.toString() : '',
    shift_id: employee.employee?.shift_id ? employee.employee.shift_id.toString() : '',
    attendance_policy_id: employee.employee?.attendance_policy_id ? employee.employee.attendance_policy_id.toString() : '',
    date_of_joining: employee.employee?.date_of_joining || '',
    employment_type: employee.employee?.employment_type || 'Full-time',
    employee_status: employee.employee?.employee_status || 'active',
    address_line_1: employee.employee?.address_line_1 || '',
    address_line_2: employee.employee?.address_line_2 || '',
    city: employee.employee?.city || '',
    state: employee.employee?.state || '',
    country: employee.employee?.country || '',
    postal_code: employee.employee?.postal_code || '',
    emergency_contact_name: employee.employee?.emergency_contact_name || '',
    emergency_contact_relationship: employee.employee?.emergency_contact_relationship || '',
    emergency_contact_number: employee.employee?.emergency_contact_number || '',
    bank_name: employee.employee?.bank_name || '',
    account_holder_name: employee.employee?.account_holder_name || '',
    account_number: employee.employee?.account_number || '',
    bank_identifier_code: employee.employee?.bank_identifier_code || '',
    bank_branch: employee.employee?.bank_branch || '',
    tax_payer_id: employee.employee?.tax_payer_id || '',
    salary: employee.employee?.base_salary || '',
    documents: []
  });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [profileImage, setProfileImage] = useState<File | null>(null);
  const [profileImagePreview, setProfileImagePreview] = useState<string | null>(
    employee.avatar ? getImagePath(employee.avatar) : null
  );
  const [existingDocuments, setExistingDocuments] = useState<any[]>(employee.employee?.documents || []);
  const [newDocuments, setNewDocuments] = useState<any[]>([]);

  // Filter departments based on selected branch
  // const filteredDepartments = formData.branch_id
  //   ? departments.filter((dept: any) => dept.branch_id === parseInt(formData.branch_id))
  //   : departments;

  const filteredDepartments = formData.branch_id
    ? departments.filter((dept: any) => String(dept.branch_id) === String(formData.branch_id))
    : departments;

  // Filter designations based on selected department
  // const filteredDesignations = formData.department_id
  //   ? designations.filter((desig: any) => desig.department_id === parseInt(formData.department_id))
  //   : designations;

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

  const handleProfileImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      const file = e.target.files[0];
      setProfileImage(file);
      setProfileImagePreview(URL.createObjectURL(file));

      // Clear error
      if (errors['profile_image']) {
        setErrors(prev => {
          const newErrors = { ...prev };
          delete newErrors['profile_image'];
          return newErrors;
        });
      }
    }
  };

  const handleNewDocumentChange = (index: number, field: string, value: any) => {
    const updatedDocuments = [...newDocuments];
    updatedDocuments[index] = { ...updatedDocuments[index], [field]: value };
    setNewDocuments(updatedDocuments);

    // Clear error
    const errorKey = `new_documents.${index}.${field}`;
    if (errors[errorKey]) {
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors[errorKey];
        return newErrors;
      });
    }
  };

  const handleNewDocumentFileChange = (index: number, e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      const file = e.target.files[0];
      handleNewDocumentChange(index, 'file', file);
    }
  };

  const addNewDocument = () => {
    setNewDocuments([
      ...newDocuments,
      { document_type_id: '', file: null, expiry_date: '' }
    ]);
  };

  const removeNewDocument = (index: number) => {
    const updatedDocuments = [...newDocuments];
    updatedDocuments.splice(index, 1);
    setNewDocuments(updatedDocuments);

    // Clear errors for this document
    const newErrors = { ...errors };
    Object.keys(newErrors).forEach(key => {
      if (key.startsWith(`new_documents.${index}.`)) {
        delete newErrors[key];
      }
    });
    setErrors(newErrors);
  };

  const removeExistingDocument = (documentId: number) => {
    toast.loading(t('Deleting document...'));

    router.delete(route('hr.employees.documents.destroy', documentId), {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }

        // Update the existing documents list
        setExistingDocuments(existingDocuments.filter(doc => doc.id !== documentId));
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to delete document: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    // Create form data for submission
    const submitData = new FormData();

    // Add method override for PUT request
    submitData.append('_method', 'PUT');

    // Add all form fields
    Object.entries(formData).forEach(([key, value]) => {
      if (key !== 'documents') {
        if (value !== null && value !== undefined && value !== '') {
          submitData.append(key, value);
        }
      }
    });

    // Add profile image if selected
    if (profileImage) {
      submitData.append('profile_image', profileImage);
    }

    // Add new documents
    newDocuments.forEach((doc: any, index: number) => {
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

    // Submit the form using POST with FormData directly
    router.post(route('hr.employees.update', employee.employee?.id), submitData, {
      onSuccess: (page) => {
        setIsSubmitting(false);
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        }
        router.get(route('hr.employees.index'));
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
    { title: t('HR Management'), href: route('hr.employees.index') },
    { title: t('Employees'), href: route('hr.employees.index') },
    { title: t('Edit Employee') }
  ];

  return (
    <PageTemplate
      title={t("Edit Employee")}
      url={`/hr/employees/${employee.id}/edit`}
      breadcrumbs={breadcrumbs}
      actions={[
        {
          label: t('Back to Employees'),
          icon: <ArrowLeft className="h-4 w-4 mr-2" />,
          variant: 'outline',
          onClick: () => router.get(route('hr.employees.index'))
        }
      ]}
    >
      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Basic Information Card */}
        <Card>
          <CardHeader>
            <CardTitle>{t('Basic Information')}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="name" required>{t('Full Name')}</Label>
                <Input
                  id="name"
                  required
                  value={formData.name}
                  onChange={(e) => handleChange('name', e.target.value)}
                  className={errors.name ? 'border-red-500' : ''}
                />
                {errors.name && <p className="text-red-500 text-xs">{errors.name}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="employee_id">{t('Employee ID')}</Label>
                <Input
                  id="employee_id"
                  value={formData.employee_id}
                  readOnly
                  className="bg-muted"
                />
                <p className="text-sm text-muted-foreground">{t('Employee ID cannot be changed')}</p>
              </div>

              <div className="space-y-2">
                <Label htmlFor="biometric_emp_id" required>{t('Employee Code')}</Label>
                <Input
                  id="biometric_emp_id"
                  required
                  value={formData.biometric_emp_id || ''}
                  onChange={(e) => handleChange('biometric_emp_id', e.target.value)}
                  placeholder=""
                  className={errors.biometric_emp_id ? 'border-red-500' : ''}
                />
                <p className="text-sm text-muted-foreground">{t('This ID will be used to map employee with biometric device.')}</p>
                {errors.biometric_emp_id && <p className="text-red-500 text-xs">{errors.biometric_emp_id}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="email" required>{t('Email')}</Label>
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
                <Label htmlFor="password">{t('Password')} <span className="text-sm text-muted-foreground">{t('(Leave blank to keep current)')}</span></Label>
                <Input
                  id="password"
                  type="password"
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

              <div className="space-y-2">
                <Label htmlFor="date_of_birth" required>{t('Date of Birth')}</Label>
                <Input
                  id="date_of_birth"
                  type="date"
                  required
                  value={formData.date_of_birth}
                  onChange={(e) => handleChange('date_of_birth', e.target.value)}
                  className={errors.date_of_birth ? 'border-red-500' : ''}
                />
                {errors.date_of_birth && <p className="text-red-500 text-xs">{errors.date_of_birth}</p>}
              </div>

              <div className="space-y-2">
                <Label required>{t('Gender')}</Label>
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
                <Label required>{t('Profile Image')}</Label>
                <div className="flex flex-col gap-3">
                  <div className="border rounded-md p-4 flex items-center justify-center bg-muted/30 h-32">
                    {formData.profile_image || employee.avatar ? (
                      <img
                        src={formData.profile_image ? getImagePath(formData.profile_image) : getImagePath(employee.avatar)}
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
                    value={formData.profile_image || employee.avatar || ''}
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
                <Label htmlFor="branch_id" required>{t('Branch')}</Label>
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
                <Label htmlFor="department_id" required>{t('Department')}</Label>
                <Select
                  value={formData.department_id}
                  required
                  onValueChange={(value) => handleChange('department_id', value)}
                  disabled={!formData.branch_id}
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
                <Label htmlFor="designation_id" required>{t('Designation')}</Label>
                <Select
                  value={formData.designation_id}
                  required
                  onValueChange={(value) => handleChange('designation_id', value)}
                  disabled={!formData.department_id}
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
                <Label htmlFor="date_of_joining" required>{t('Date of Joining')}</Label>
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
                <Label htmlFor="employment_type" required>{t('Employment Type')}</Label>
                <Select
                  value={formData.employment_type}
                  required
                  onValueChange={(value) => handleChange('employment_type', value)}
                >
                  <SelectTrigger className={errors.employment_type ? 'border-red-500' : ''}>
                    <SelectValue placeholder={t('Select Employment Type')} />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Full-time">{t('Full-time')}</SelectItem>
                    <SelectItem value="Part-time">{t('Part-time')}</SelectItem>
                    <SelectItem value="Contract">{t('Contract')}</SelectItem>
                    <SelectItem value="Internship">{t('Internship')}</SelectItem>
                    <SelectItem value="Temporary">{t('Temporary')}</SelectItem>
                  </SelectContent>
                </Select>
                {errors.employment_type && <p className="text-red-500 text-xs">{errors.employment_type}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="employee_status" required>{t('Employee Status')}</Label>
                <Select
                  value={formData.employee_status}
                  required
                  onValueChange={(value) => handleChange('employee_status', value)}
                >
                  <SelectTrigger className={errors.employee_status ? 'border-red-500' : ''}>
                    <SelectValue placeholder={t('Select Employee Status')} />
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
                  <SelectTrigger className={errors.shift_id ? 'border-red-500' : ''}>
                    <SelectValue placeholder={t('Select Shift (Optional)')} />
                  </SelectTrigger>
                  <SelectContent searchable={true}>
                    {shifts?.map((shift: any) => (
                      <SelectItem key={shift.id} value={shift.id.toString()}>
                        {shift.name} ({shift.start_time} - {shift.end_time})
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.shift_id && <p className="text-red-500 text-xs">{errors.shift_id}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="attendance_policy_id">{t('Attendance Policy')}</Label>
                <Select
                  value={formData.attendance_policy_id}
                  onValueChange={(value) => handleChange('attendance_policy_id', value)}
                >
                  <SelectTrigger className={errors.attendance_policy_id ? 'border-red-500' : ''}>
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
                {errors.attendance_policy_id && <p className="text-red-500 text-xs">{errors.attendance_policy_id}</p>}
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
                <Label htmlFor="address_line_1" required>{t('Address Line 1')}</Label>
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
                  className={errors.address_line_2 ? 'border-red-500' : ''}
                />
                {errors.address_line_2 && <p className="text-red-500 text-xs">{errors.address_line_2}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="city" required>{t('City')}</Label>
                <Input
                  id="city"
                  value={formData.city}
                  required
                  onChange={(e) => handleChange('city', e.target.value)}
                  className={errors.city ? 'border-red-500' : ''}
                />
                {errors.city && <p className="text-red-500 text-xs">{errors.city}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="state" required >{t('State/Province')}</Label>
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
                <Label htmlFor="country" required>{t('Country')}</Label>
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
                <Label htmlFor="postal_code" required>{t('Postal/Zip Code')}</Label>
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
                  <Label htmlFor="emergency_contact_name" required>{t('Name')}</Label>
                  <Input
                    id="emergency_contact_name"
                    value={formData.emergency_contact_name}
                    required
                    onChange={(e) => handleChange('emergency_contact_name', e.target.value)}
                    className={errors.emergency_contact_name ? 'border-red-500' : ''}
                  />
                  {errors.emergency_contact_name && <p className="text-red-500 text-xs">{errors.emergency_contact_name}</p>}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="emergency_contact_relationship" required>{t('Relationship')}</Label>
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
                  <Label htmlFor="emergency_contact_number" required>{t('Phone Number')}</Label>
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
                <Label htmlFor="bank_name" required>{t('Bank Name')}</Label>
                <Input
                  id="bank_name"
                  value={formData.bank_name}
                  required
                  onChange={(e) => handleChange('bank_name', e.target.value)}
                  className={errors.bank_name ? 'border-red-500' : ''}
                />
                {errors.bank_name && <p className="text-red-500 text-xs">{errors.bank_name}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="account_holder_name" required>{t('Account Holder Name')}</Label>
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
                <Label htmlFor="account_number" >{t('Account Number')}</Label>
                <Input
                  id="account_number"
                  value={formData.account_number}
                  required
                  onChange={(e) => handleChange('account_number', e.target.value)}
                  className={errors.account_number ? 'border-red-500' : ''}
                />
                {errors.account_number && <p className="text-red-500 text-xs">{errors.account_number}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="bank_identifier_code" required>{t('Bank Identifier Code (BIC/SWIFT)')}</Label>
                <Input
                  id="bank_identifier_code"
                  value={formData.bank_identifier_code}
                  required
                  onChange={(e) => handleChange('bank_identifier_code', e.target.value)}
                  className={errors.bank_identifier_code ? 'border-red-500' : ''}
                />
                {errors.bank_identifier_code && <p className="text-red-500 text-xs">{errors.bank_identifier_code}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="bank_branch" required>{t('Bank Branch')}</Label>
                <Input
                  id="bank_branch"
                  value={formData.bank_branch}
                  required
                  onChange={(e) => handleChange('bank_branch', e.target.value)}
                  className={errors.bank_branch ? 'border-red-500' : ''}
                />
                {errors.bank_branch && <p className="text-red-500 text-xs">{errors.bank_branch}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="tax_payer_id" >{t('Tax Payer ID')}</Label>
                <Input
                  id="tax_payer_id"
                  value={formData.tax_payer_id}
                  onChange={(e) => handleChange('tax_payer_id', e.target.value)}
                  className={errors.tax_payer_id ? 'border-red-500' : ''}
                />
                {errors.tax_payer_id && <p className="text-red-500 text-xs">{errors.tax_payer_id}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="salary" required>{t('Base Salary')}</Label>
                <Input
                  id="salary"
                  required
                  type="number"
                  step="0.01"
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
            {/* Existing Documents */}
            {existingDocuments.length > 0 && (
              <div className="mb-6">
                <h3 className="text-lg font-medium mb-4">{t('Existing Documents')}</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {existingDocuments.map((document: any) => (
                    <Card key={document.id} className="border">
                      <CardContent className="p-4">
                        <div className="flex items-start justify-between">
                          <div className="flex items-center">
                            <div>
                              <h4 className="font-medium">{document.document_type?.name}</h4>
                              <p className="text-sm text-muted-foreground">
                                {document.expiry_date ? `${t('Expires')}: ${new Date(document.expiry_date).toLocaleDateString()}` : t('No expiry date')}
                              </p>
                              <div className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium mt-2 ${document.verification_status === 'verified'
                                ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
                                : document.verification_status === 'rejected'
                                  ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
                                  : 'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20'
                                }`}>
                                {document.verification_status === 'verified'
                                  ? t('Verified')
                                  : document.verification_status === 'rejected'
                                    ? t('Rejected')
                                    : t('Pending')}
                              </div>
                            </div>
                          </div>
                          <div className="flex space-x-2">
                            <Button
                              variant="outline"
                              size="sm"
                              onClick={() => window.open(`${document.file_path}`, '_blank')}
                            >
                              <Plus className="h-4 w-4" />
                            </Button>
                            <Button
                              variant="outline"
                              size="sm"
                              onClick={() => removeExistingDocument(document.id)}
                            >
                              <Trash2 className="h-4 w-4 text-red-500" />
                            </Button>
                          </div>
                        </div>
                      </CardContent>
                    </Card>
                  ))}
                </div>
              </div>
            )}

            {/* New Documents */}
            <div>
              <h3 className="text-lg font-medium mb-4">{t('Add New Documents')}</h3>
              {newDocuments.map((document: any, index: number) => (
                <div key={index} className="border rounded-md p-4 space-y-4 mb-4">
                  <div className="flex justify-between items-center">
                    <h3 className="text-lg font-medium">{t('Document')} #{index + 1}</h3>
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      onClick={() => removeNewDocument(index)}
                    >
                      <Trash2 className="h-4 w-4 text-red-500" />
                    </Button>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label htmlFor={`document_type_${index}`}>{t('Document Type')} <span className="text-red-500">*</span></Label>
                      <Select
                        value={document.document_type_id}
                        onValueChange={(value) => handleNewDocumentChange(index, 'document_type_id', value)}
                      >
                        <SelectTrigger className={errors[`new_documents.${index}.document_type_id`] ? 'border-red-500' : ''}>
                          <SelectValue placeholder={t('Select Document Type')} />
                        </SelectTrigger>
                        <SelectContent>
                          {documentTypes.map((type: any) => (
                            <SelectItem key={type.id} value={type.id.toString()}>
                              {type.name} {type.is_required && <span className="text-red-500">*</span>}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      {errors[`new_documents.${index}.document_type_id`] && (
                        <p className="text-red-500 text-xs">{errors[`new_documents.${index}.document_type_id`]}</p>
                      )}
                    </div>

                    <div className="space-y-2">
                      <Label>{t('File')} <span className="text-red-500">*</span></Label>
                      <div className="flex flex-col gap-3">
                        <div className="border rounded-md p-4 flex items-center justify-center bg-muted/30 h-20">
                          {document.file_path ? (
                            <img
                              src={getImagePath(document.file_path)}
                              alt="Document Preview"
                              className="max-h-full max-w-full object-contain"
                            />
                          ) : (
                            <div className="text-muted-foreground flex flex-col items-center gap-1">
                              <div className="h-8 w-8 bg-muted flex items-center justify-center rounded border border-dashed">
                                <span className="font-semibold text-xs text-muted-foreground">{t('Doc')}</span>
                              </div>
                              <span className="text-xs">No file selected</span>
                            </div>
                          )}
                        </div>
                        <MediaPicker
                          label=""
                          value={document.file_path || ''}
                          onChange={(url) => handleNewDocumentChange(index, 'file_path', url)}
                          placeholder="Select document file..."
                          showPreview={false}
                        />
                      </div>
                      {errors[`new_documents.${index}.file`] && (
                        <p className="text-red-500 text-xs">{errors[`new_documents.${index}.file`]}</p>
                      )}
                    </div>

                    <div className="space-y-2">
                      <Label htmlFor={`document_expiry_${index}`}>{t('Expiry Date')}</Label>
                      <Input
                        id={`document_expiry_${index}`}
                        type="date"
                        value={document.expiry_date}
                        onChange={(e) => handleNewDocumentChange(index, 'expiry_date', e.target.value)}
                        className={errors[`new_documents.${index}.expiry_date`] ? 'border-red-500' : ''}
                      />
                      {errors[`new_documents.${index}.expiry_date`] && (
                        <p className="text-red-500 text-xs">{errors[`new_documents.${index}.expiry_date`]}</p>
                      )}
                    </div>
                  </div>
                </div>
              ))}

              <Button
                type="button"
                variant="outline"
                onClick={addNewDocument}
                className="mt-4"
              >
                <Plus className="h-4 w-4 mr-2" />
                {t('Add Document')}
              </Button>
            </div>
          </CardContent>
        </Card>

        {/* Submit Buttons */}
        <div className="flex justify-end space-x-4">
          <Button
            type="button"
            variant="outline"
            onClick={() => router.get(route('hr.employees.index'))}
          >
            {t('Cancel')}
          </Button>
          <Button
            type="submit"
            disabled={isSubmitting}
          >
            {isSubmitting ? t('Saving...') : t('Update Employee')}
          </Button>
        </div>
      </form>
    </PageTemplate>
  );
}