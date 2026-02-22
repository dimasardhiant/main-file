// pages/hr/employees/show.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { hasPermission } from '@/utils/authorization';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from 'react-i18next';
import { Edit, Trash2, Download, FileText, Calendar, Phone, Mail, MapPin, Building, Briefcase, CreditCard, User, Lock, Unlock, ArrowLeft, Check, X } from 'lucide-react';
import { getImagePath } from '@/utils/helpers';

export default function EmployeeShow() {
  const { t } = useTranslation();
  const { auth, employee } = usePage().props as any;
  const permissions = auth?.permissions || [];
  const getInitials = useInitials();

  // State
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [activeTab, setActiveTab] = useState('basic_info');

  const handleEdit = () => {
    router.get(route('hr.employees.edit', employee.id));
  };

  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting employee...'));

    router.delete(route('hr.employees.destroy', employee.id), {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
        router.get(route('hr.employees.index'));
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to delete employee: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleToggleStatus = () => {
    const newStatus = employee.status === 'active' ? 'inactive' : 'active';
    toast.loading(`${newStatus === 'active' ? t('Activating') : t('Deactivating')} employee...`);

    router.put(route('hr.employees.toggle-status', employee.id), {}, {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to update employee status: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleDeleteDocument = (documentId: number) => {
    toast.loading(t('Deleting document...'));

    router.delete(route('hr.employees.documents.destroy', [employee.id, documentId]), {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
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

  const handleDocumentVerification = (documentId: number, status: 'verified' | 'rejected') => {
    const action = status === 'verified' ? 'approve' : 'reject';
    toast.loading(t(`${status === 'verified' ? 'Approving' : 'Rejecting'} document...`));

    router.put(route(`hr.employees.documents.${action}`, [employee.id, documentId]), {}, {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash?.success) {
          toast.success(t(page.props.flash.success));
        } else {
          toast.success(t(`Document ${status === 'verified' ? 'approved' : 'rejected'} successfully`));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        const errorMessage = errors?.message || Object.values(errors)[0] || `Failed to ${action} document`;
        toast.error(t(errorMessage));
      }
    });
  };

  // Define page actions
  const pageActions = [
    {
      label: t('Back to Employees'),
      icon: <ArrowLeft className="h-4 w-4 mr-2" />,
      variant: 'outline',
      onClick: () => router.get(route('hr.employees.index'))
    }
  ];

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.employees.index') },
    { title: t('Employees'), href: route('hr.employees.index') },
    { title: employee?.name || t('Employee Details') }
  ];

  return (
    <PageTemplate
      title={employee?.name || t("Employee Details")}
      url={`/hr/employees/${employee?.id}`}
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {/* Employee Profile Card */}
        <Card className="lg:col-span-1">
          <CardContent className="p-6">
            <div className="flex flex-col items-center">
              <div className="h-32 w-32 rounded-full bg-primary text-white flex items-center justify-center text-3xl font-bold mb-4 overflow-hidden">
                {employee.avatar ? (
                  <img src={getImagePath(employee.avatar)} alt={employee.name} className="h-full w-full object-cover" />
                ) : (
                  getInitials(employee.name)
                )}
              </div>
              <h2 className="text-xl font-bold mb-1">{employee.name}</h2>
              <p className="text-sm text-muted-foreground mb-2">{employee.employee?.designation?.name || '-'}</p>
              <div className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium mb-4 ${employee.employee?.employee_status === 'active'
                ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
                : employee.employee?.employee_status === 'inactive'
                  ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
                  : employee.employee?.employee_status === 'probation'
                    ? 'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20'
                    : employee.employee?.employee_status === 'terminated'
                      ? 'bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20'
                      : 'bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20'
                }`}>
                {employee.employee?.employee_status === 'active' && t('Active')}
                {employee.employee?.employee_status === 'inactive' && t('Inactive')}
                {employee.employee?.employee_status === 'probation' && t('Probation')}
                {employee.employee?.employee_status === 'terminated' && t('Terminated')}
                {!employee.employee?.employee_status && '-'}
              </div>

              <div className="w-full space-y-3">
                <div className="flex items-center">
                  <User className="h-4 w-4 mr-2 text-muted-foreground" />
                  <span className="text-sm">{t('Employee ID')}: {employee.employee?.employee_id}</span>
                </div>
                <div className="flex items-center">
                  <Mail className="h-4 w-4 mr-2 text-muted-foreground" />
                  <span className="text-sm">{employee.email}</span>
                </div>
                {employee.employee?.phone && (
                  <div className="flex items-center">
                    <Phone className="h-4 w-4 mr-2 text-muted-foreground" />
                    <span className="text-sm">{employee.employee.phone}</span>
                  </div>
                )}
                {employee.employee?.date_of_birth && (
                  <div className="flex items-center">
                    <Calendar className="h-4 w-4 mr-2 text-muted-foreground" />
                    <span className="text-sm">{t('DOB')}: {window.appSettings?.formatDateTimeSimple(employee.employee.date_of_birth, false) || new Date(employee.employee.date_of_birth).toLocaleDateString()}</span>
                  </div>
                )}
                {employee.employee?.date_of_joining && (
                  <div className="flex items-center">
                    <Calendar className="h-4 w-4 mr-2 text-muted-foreground" />
                    <span className="text-sm">{t('Joined')}: {window.appSettings?.formatDateTimeSimple(employee.employee.date_of_joining, false) || new Date(employee.employee.date_of_joining).toLocaleDateString()}</span>
                  </div>
                )}
                {employee.employee?.department?.name && (
                  <div className="flex items-center">
                    <Building className="h-4 w-4 mr-2 text-muted-foreground" />
                    <span className="text-sm">{employee.employee.department.name}</span>
                  </div>
                )}
                {employee.employee?.branch?.name && (
                  <div className="flex items-center">
                    <Building className="h-4 w-4 mr-2 text-muted-foreground" />
                    <span className="text-sm">{employee.employee.branch.name}</span>
                  </div>
                )}
                {employee.employee?.employment_type && (
                  <div className="flex items-center">
                    <Briefcase className="h-4 w-4 mr-2 text-muted-foreground" />
                    <span className="text-sm">{employee.employee.employment_type}</span>
                  </div>
                )}
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Employee Details Tabs */}
        <div className="lg:col-span-3">
          <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
            <TabsList className="grid grid-cols-5 mb-4">
              <TabsTrigger value="basic_info">{t('Basic Info')}</TabsTrigger>
              <TabsTrigger value="employment">{t('Employment')}</TabsTrigger>
              <TabsTrigger value="contact">{t('Contact')}</TabsTrigger>
              <TabsTrigger value="banking">{t('Banking')}</TabsTrigger>
              <TabsTrigger value="documents">{t('Documents')}</TabsTrigger>
            </TabsList>

            {/* Basic Info Tab */}
            <TabsContent value="basic_info">
              <Card>
                <CardHeader>
                  <CardTitle>{t('Basic Information')}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Full Name')}</h4>
                      <p>{employee.name}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Employee ID')}</h4>
                      <p>{employee.employee?.employee_id}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Email')}</h4>
                      <p>{employee.email}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Phone Number')}</h4>
                      <p>{employee.employee?.phone || '-'}</p>
                    </div>
                     <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Employee Code')}</h4>
                      <p>{employee.employee?.biometric_emp_id || '-'}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Date of Birth')}</h4>
                      <p>{employee.employee?.date_of_birth ? (window.appSettings?.formatDateTimeSimple(employee.employee.date_of_birth, false) || new Date(employee.employee.date_of_birth).toLocaleDateString()) : '-'}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Gender')}</h4>
                      <p>{employee.employee?.gender ? t(employee.employee.gender.charAt(0).toUpperCase() + employee.employee.gender.slice(1)) : '-'}</p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            {/* Employment Tab */}
            <TabsContent value="employment">
              <Card>
                <CardHeader>
                  <CardTitle>{t('Employment Details')}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Branch')}</h4>
                      <p>{employee.employee?.branch?.name || '-'}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Department')}</h4>
                      <p>{employee.employee?.department?.name || '-'}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Designation')}</h4>
                      <p>{employee.employee?.designation?.name || '-'}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Date of Joining')}</h4>
                      <p>{employee.employee?.date_of_joining ? (window.appSettings?.formatDateTimeSimple(employee.employee.date_of_joining, false) || new Date(employee.employee.date_of_joining).toLocaleDateString()) : '-'}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Employment Type')}</h4>
                      <p>{employee.employee?.employment_type || '-'}</p>
                    </div>

                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Shift')}</h4>
                      <p>{employee.employee?.shift ? `${employee.employee.shift.name} (${employee.employee.shift.start_time} - ${employee.employee.shift.end_time})` : '-'}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Attendance Policy')}</h4>
                      <p>{employee.employee?.attendance_policy?.name || '-'}</p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            {/* Contact Tab */}
            <TabsContent value="contact">
              <Card>
                <CardHeader>
                  <CardTitle>{t('Contact Information')}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Address Line 1')}</h4>
                      <p>{employee.employee?.address_line_1 || '-'}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Address Line 2')}</h4>
                      <p>{employee.employee?.address_line_2 || '-'}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('City')}</h4>
                      <p>{employee.employee?.city || '-'}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('State/Province')}</h4>
                      <p>{employee.employee?.state || '-'}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Country')}</h4>
                      <p>{employee.employee?.country || '-'}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Postal/Zip Code')}</h4>
                      <p>{employee.employee?.postal_code || '-'}</p>
                    </div>
                  </div>

                  <div className="mt-6">
                    <h3 className="text-lg font-medium mb-4">{t('Emergency Contact')}</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <h4 className="text-sm font-medium text-muted-foreground">{t('Name')}</h4>
                        <p>{employee.employee?.emergency_contact_name || '-'}</p>
                      </div>
                      <div>
                        <h4 className="text-sm font-medium text-muted-foreground">{t('Relationship')}</h4>
                        <p>{employee.employee?.emergency_contact_relationship || '-'}</p>
                      </div>
                      <div>
                        <h4 className="text-sm font-medium text-muted-foreground">{t('Phone Number')}</h4>
                        <p>{employee.employee?.emergency_contact_number || '-'}</p>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            {/* Banking Tab */}
            <TabsContent value="banking">
              <Card>
                <CardHeader>
                  <CardTitle>{t('Banking Information')}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Bank Name')}</h4>
                      <p>{employee.employee?.bank_name || '-'}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Account Holder Name')}</h4>
                      <p>{employee.employee?.account_holder_name || '-'}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Account Number')}</h4>
                      <p>{employee.employee?.account_number || '-'}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Bank Identifier Code (BIC/SWIFT)')}</h4>
                      <p>{employee.employee?.bank_identifier_code || '-'}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Bank Branch')}</h4>
                      <p>{employee.employee?.bank_branch || '-'}</p>
                    </div>
                    <div>
                      <h4 className="text-sm font-medium text-muted-foreground">{t('Tax Payer ID')}</h4>
                      <p>{employee.employee?.tax_payer_id || '-'}</p>
                    </div>
                    {employee.employee?.base_salary && (
                      <div>
                        <h4 className="text-sm font-medium text-muted-foreground">{t('Base Salary')}</h4>
                        <p>{employee.employee.base_salary}</p>
                      </div>
                    )}
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            {/* Documents Tab */}
            <TabsContent value="documents">
              <Card>
                <CardHeader>
                  <CardTitle>{t('Documents')}</CardTitle>
                </CardHeader>
                <CardContent>
                  {employee.employee?.documents && employee.employee.documents.length > 0 ? (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      {employee.employee.documents.map((document: any) => (
                        <Card key={document.id} className="border">
                          <CardContent className="p-4">
                            <div className="flex items-start justify-between">
                              <div className="flex items-center">
                                <FileText className="h-8 w-8 mr-3 text-primary" />
                                <div>
                                  <h4 className="font-medium">{document.document_type?.name}</h4>
                                  <p className="text-sm text-muted-foreground">
                                    {document.expiry_date ? `${t('Expires')}: ${window.appSettings?.formatDateTimeSimple(document.expiry_date, false) || new Date(document.expiry_date).toLocaleDateString()}` : t('No expiry date')}
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
                              <div className="flex flex-wrap gap-2">
                                <Button variant="outline" size="sm" onClick={() => window.open(route('hr.employees.documents.download', [employee.id, document.id]), '_blank')}>
                                  <Download className="h-4 w-4" />
                                </Button>
                                {hasPermission(permissions, 'edit-employees') && (
                                  <Button variant="outline" size="sm" onClick={() => handleDeleteDocument(document.id)}>
                                    <Trash2 className="h-4 w-4 text-red-500" />
                                  </Button>
                                )}
                                {hasPermission(permissions, 'edit-employees') && document.verification_status === 'pending' && (
                                  <>
                                    <Button
                                      variant="outline"
                                      size="sm"
                                      onClick={() => handleDocumentVerification(document.id, 'verified')}
                                      className="text-green-600 hover:text-green-700"
                                    >
                                      <Check className="h-4 w-4" />
                                    </Button>
                                    <Button
                                      variant="outline"
                                      size="sm"
                                      onClick={() => handleDocumentVerification(document.id, 'rejected')}
                                      className="text-red-600 hover:text-red-700"
                                    >
                                      <X className="h-4 w-4" />
                                    </Button>
                                  </>
                                )}
                              </div>
                            </div>
                          </CardContent>
                        </Card>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-8 text-muted-foreground">
                      {t('No documents found')}
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>
        </div>
      </div>

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={employee?.name || ''}
        entityName="employee"
      />
    </PageTemplate>
  );
}