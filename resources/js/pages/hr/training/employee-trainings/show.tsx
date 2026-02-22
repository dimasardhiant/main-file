// pages/hr/training/employee-trainings/show.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { hasPermission } from '@/utils/authorization';
import { useTranslation } from 'react-i18next';
import { ArrowLeft, Edit, Trash, Download, FileText } from 'lucide-react';
import { format } from 'date-fns';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

export default function EmployeeTrainingShow() {
  const { t } = useTranslation();
  const { auth, employeeTraining, availableAssessments } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  // State
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isAssessmentModalOpen, setIsAssessmentModalOpen] = useState(false);
  
  const handleBackToList = () => {
    router.get(route('hr.employee-trainings.index'));
  };
  
  const handleEdit = () => {
    setIsFormModalOpen(true);
  };
  
  const handleDelete = () => {
    setIsDeleteModalOpen(true);
  };
  
  const handleRecordAssessment = () => {
    setIsAssessmentModalOpen(true);
  };
  
  const handleDownloadCertification = () => {
    window.open(route('hr.employee-trainings.download-certification', employeeTraining.id), '_blank');
  };
  
  const handleFormSubmit = (formData: any) => {
    // Convert form data to FormData object for file uploads
    const data = new FormData();
    
    // Add all form fields to FormData
    Object.keys(formData).forEach(key => {
      if (formData[key] !== null && formData[key] !== undefined) {
        if (key === 'certification') {
          // Only add file if it's a File object (not a string path)
          if (formData[key] instanceof File) {
            data.append(key, formData[key]);
          }
        } else {
          data.append(key, formData[key]);
        }
      }
    });
    
    toast.loading(t('Updating training...'));
    
    // Add _method field for Laravel to recognize as PUT request
    data.append('_method', 'PUT');

    router.post(route('hr.employee-trainings.update', employeeTraining.id), data, {
      onSuccess: (page) => {
        setIsFormModalOpen(false);
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
          toast.error(t('Failed to update training: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleAssessmentSubmit = (formData: any) => {
    toast.loading(t('Recording assessment result...'));

    router.post(route('hr.employee-trainings.record-assessment', employeeTraining.id), formData, {
      onSuccess: (page) => {
        setIsAssessmentModalOpen(false);
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
          toast.error(t('Failed to record assessment result: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting training assignment...'));
    
    router.delete(route('hr.employee-trainings.destroy', employeeTraining.id), {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
        router.get(route('hr.employee-trainings.index'));
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to delete training assignment: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  // Define page actions
  const pageActions = [];
  
  // Add the "Back to List" button
  pageActions.push({
    label: t('Back to List'),
    icon: <ArrowLeft className="h-4 w-4 mr-2" />,
    variant: 'outline' as const,
    onClick: handleBackToList
  });
  
  // Add action buttons based on permissions
  if (hasPermission(permissions, 'edit-employee-trainings')) {
    pageActions.push({
      label: t('Edit'),
      icon: <Edit className="h-4 w-4 mr-2" />,
      variant: 'default' as const,
      onClick: handleEdit
    });
  }
  
  if (hasPermission(permissions, 'delete-employee-trainings')) {
    pageActions.push({
      label: t('Delete'),
      icon: <Trash className="h-4 w-4 mr-2" />,
      variant: 'destructive' as const,
      onClick: handleDelete
    });
  }
  
  // Add Record Assessment button if there are available assessments
  if (hasPermission(permissions, 'record-assessment-results') && availableAssessments && availableAssessments.length > 0) {
    pageActions.push({
      label: t('Record Assessment'),
      icon: <FileText className="h-4 w-4 mr-2" />,
      variant: 'default' as const,
      onClick: handleRecordAssessment
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.employee-trainings.index') },
    { title: t('Training Management'), href: route('hr.employee-trainings.index') },
    { title: t('Employee Trainings'), href: route('hr.employee-trainings.index') },
    { title: `${employeeTraining.employee?.name || ''} - ${employeeTraining.training_program?.name || ''}` }
  ];
  
  // Status colors for badges
  const statusClasses = {
    'assigned': 'bg-blue-50 text-blue-700 ring-blue-600/20',
    'in_progress': 'bg-amber-50 text-amber-700 ring-amber-600/20',
    'completed': 'bg-green-50 text-green-700 ring-green-600/20',
    'failed': 'bg-red-50 text-red-700 ring-red-600/20'
  };

  return (
    <PageTemplate 
      title={`${employeeTraining.employee?.name || ''} - ${employeeTraining.training_program?.name || ''}`} 
      url={`/hr/training/employee-trainings/${employeeTraining.id}`}
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Training Details */}
        <div className="lg:col-span-2">
          <Card className="mb-6">
            <CardHeader>
              <div className="flex justify-between items-start">
                <div>
                  <CardTitle className="text-2xl">{employeeTraining.training_program?.name || t('Unknown Program')}</CardTitle>
                  <CardDescription className="mt-2">
                    {employeeTraining.training_program?.training_type?.name || '-'}
                  </CardDescription>
                </div>
                <Badge className={`${statusClasses[employeeTraining.status] || 'bg-gray-50 text-gray-700 ring-gray-600/20'}`}>
                  {employeeTraining.status === 'in_progress' ? t('In Progress') : 
                   employeeTraining.status === 'assigned' ? t('Assigned') :
                   employeeTraining.status === 'completed' ? t('Completed') :
                   employeeTraining.status === 'failed' ? t('Failed') :
                   employeeTraining.status}
                </Badge>
              </div>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Employee')}</h3>
                  <p className="font-medium">{employeeTraining.employee?.name || '-'}</p>
                  <p className="text-sm text-gray-500">{employeeTraining.employee?.employee?.employee_id || '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Assigned By')}</h3>
                  <p>{employeeTraining.assigner?.name || '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Assigned Date')}</h3>
                  <p>{employeeTraining.assigned_date ? (window.appSettings?.formatDateTimeSimple(employeeTraining.assigned_date, false) || format(new Date(employeeTraining.assigned_date), 'MMM dd, yyyy')) : '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Completion Date')}</h3>
                  <p>{employeeTraining.completion_date ? (window.appSettings?.formatDateTimeSimple(employeeTraining.completion_date, false) || format(new Date(employeeTraining.completion_date), 'MMM dd, yyyy')) : '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Score')}</h3>
                  <p>{employeeTraining.score !== null ? `${employeeTraining.score}%` : '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Result')}</h3>
                  <p>
                    {employeeTraining.is_passed === null ? '-' : (
                      employeeTraining.is_passed ? (
                        <Badge variant="outline" className="bg-green-50 text-green-700">
                          {t('Passed')}
                        </Badge>
                      ) : (
                        <Badge variant="outline" className="bg-red-50 text-red-700">
                          {t('Failed')}
                        </Badge>
                      )
                    )}
                  </p>
                </div>
              </div>
              
              {employeeTraining.feedback && (
                <div className="mb-4">
                  <h3 className="text-sm font-medium text-gray-500">{t('Feedback')}</h3>
                  <p className="mt-1">{employeeTraining.feedback}</p>
                </div>
              )}
              
              {employeeTraining.notes && (
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Notes')}</h3>
                  <p className="mt-1">{employeeTraining.notes}</p>
                </div>
              )}
              
              {employeeTraining.certification && (
                <div className="mt-4">
                  <Button 
                    variant="outline" 
                    onClick={handleDownloadCertification}
                    className="flex items-center"
                  >
                    <Download className="h-4 w-4 mr-2" />
                    {t('Download Certification')}
                  </Button>
                </div>
              )}
            </CardContent>
          </Card>
          
          {/* Tabs for Program Details and Assessment Results */}
          <Tabs defaultValue="program" className="w-full">
            <TabsList className="grid grid-cols-2 mb-4">
              <TabsTrigger value="program">{t('Program Details')}</TabsTrigger>
              <TabsTrigger value="assessments">{t('Assessment Results')}</TabsTrigger>
            </TabsList>
            
            {/* Program Details Tab */}
            <TabsContent value="program">
              <Card>
                <CardHeader>
                  <CardTitle>{t('Program Details')}</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                      <h3 className="text-sm font-medium text-gray-500">{t('Duration')}</h3>
                      <p>{employeeTraining.training_program?.duration ? `${employeeTraining.training_program.duration} ${t('hours')}` : '-'}</p>
                    </div>
                    <div>
                      <h3 className="text-sm font-medium text-gray-500">{t('Cost')}</h3>
                      <p>{employeeTraining.training_program?.cost ? `$${parseFloat(employeeTraining.training_program.cost).toFixed(2)}` : '-'}</p>
                    </div>
                    <div>
                      <h3 className="text-sm font-medium text-gray-500">{t('Status')}</h3>
                      <p>
                        <Badge variant="outline" className={`${statusClasses[employeeTraining.training_program?.status] || ''}`}>
                          {employeeTraining.training_program?.status?.charAt(0).toUpperCase() + employeeTraining.training_program?.status?.slice(1) || '-'}
                        </Badge>
                      </p>
                    </div>
                    <div>
                      <h3 className="text-sm font-medium text-gray-500">{t('Flags')}</h3>
                      <div className="flex flex-wrap gap-1 mt-1">
                        {employeeTraining.training_program?.is_mandatory && (
                          <Badge variant="outline" className="bg-red-50 text-red-700">
                            {t('Mandatory')}
                          </Badge>
                        )}
                        {employeeTraining.training_program?.is_self_enrollment && (
                          <Badge variant="outline" className="bg-blue-50 text-blue-700">
                            {t('Self-Enrollment')}
                          </Badge>
                        )}
                      </div>
                    </div>
                  </div>
                  
                  {employeeTraining.training_program?.description && (
                    <div className="mb-4">
                      <h3 className="text-sm font-medium text-gray-500">{t('Description')}</h3>
                      <p className="mt-1">{employeeTraining.training_program.description}</p>
                    </div>
                  )}
                  
                  {employeeTraining.training_program?.prerequisites && (
                    <div>
                      <h3 className="text-sm font-medium text-gray-500">{t('Prerequisites')}</h3>
                      <p className="mt-1">{employeeTraining.training_program.prerequisites}</p>
                    </div>
                  )}
                  
                  {employeeTraining.training_program?.materials && (
                    <div className="mt-4">
                      <Button 
                        variant="outline" 
                        onClick={() => window.open(route('hr.training-programs.download-materials', employeeTraining.training_program.id), '_blank')}
                        className="flex items-center"
                      >
                        <Download className="h-4 w-4 mr-2" />
                        {t('Download Materials')}
                      </Button>
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>
            
            {/* Assessment Results Tab */}
            <TabsContent value="assessments">
              <Card>
                <CardHeader>
                  <CardTitle>{t('Assessment Results')}</CardTitle>
                </CardHeader>
                <CardContent>
                  {employeeTraining.assessment_results && employeeTraining.assessment_results.length > 0 ? (
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>{t('Assessment')}</TableHead>
                          <TableHead>{t('Type')}</TableHead>
                          <TableHead>{t('Date')}</TableHead>
                          <TableHead>{t('Score')}</TableHead>
                          <TableHead>{t('Result')}</TableHead>
                          <TableHead>{t('Assessed By')}</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {employeeTraining.assessment_results.map((result: any) => (
                          <TableRow key={result.id}>
                            <TableCell className="font-medium">
                              {result.training_assessment?.name || '-'}
                            </TableCell>
                            <TableCell>
                              {result.training_assessment?.type?.charAt(0).toUpperCase() + result.training_assessment?.type?.slice(1) || '-'}
                            </TableCell>
                            <TableCell>
                              {window.appSettings?.formatDateTimeSimple(result.assessment_date, false) || format(new Date(result.assessment_date), 'MMM dd, yyyy')}
                            </TableCell>
                            <TableCell>
                              {result.score}%
                            </TableCell>
                            <TableCell>
                              {result.is_passed ? (
                                <Badge variant="outline" className="bg-green-50 text-green-700">
                                  {t('Passed')}
                                </Badge>
                              ) : (
                                <Badge variant="outline" className="bg-red-50 text-red-700">
                                  {t('Failed')}
                                </Badge>
                              )}
                            </TableCell>
                            <TableCell>
                              {result.assessor?.name || '-'}
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  ) : (
                    <div className="text-center py-4 text-gray-500">
                      {t('No assessment results available')}
                    </div>
                  )}
                  
                  {availableAssessments && availableAssessments.length > 0 && hasPermission(permissions, 'record-assessment-results') && (
                    <div className="mt-4">
                      <Button 
                        variant="default" 
                        onClick={handleRecordAssessment}
                      >
                        <FileText className="h-4 w-4 mr-2" />
                        {t('Record Assessment')}
                      </Button>
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>
        </div>
        
        {/* Employee Details */}
        <div>
          <Card>
            <CardHeader>
              <CardTitle>{t('Employee Details')}</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Name')}</h3>
                  <p className="font-medium">{employeeTraining.employee?.name || '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Employee ID')}</h3>
                  <p>{employeeTraining.employee?.employee?.employee_id || '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Department')}</h3>
                  <p>{employeeTraining.employee?.employee?.department?.name || '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Designation')}</h3>
                  <p>{employeeTraining.employee?.employee?.designation?.name || '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Email')}</h3>
                  <p>{employeeTraining.employee?.email || '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Phone')}</h3>
                  <p>{employeeTraining.employee?.employee?.phone || '-'}</p>
                </div>
              </div>
              
              <div className="mt-4">
                <Button 
                  variant="outline" 
                  onClick={() => router.get(route('hr.employees.show', employeeTraining.employee?.employee?.id))}
                  className="w-full"
                >
                  {t('View Employee Profile')}
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
      
      {/* Form Modal */}
      <CrudFormModal
        isOpen={isFormModalOpen}
        onClose={() => setIsFormModalOpen(false)}
        onSubmit={handleFormSubmit}
        formConfig={{
          fields: [
            { 
              name: 'status', 
              label: t('Status'), 
              type: 'select',
              required: true,
              options: [
                { value: 'assigned', label: t('Assigned') },
                { value: 'in_progress', label: t('In Progress') },
                { value: 'completed', label: t('Completed') },
                { value: 'failed', label: t('Failed') }
              ]
            },
            { 
              name: 'completion_date', 
              label: t('Completion Date'), 
              type: 'date',
              showWhen: (formData) => ['completed', 'failed'].includes(formData.status)
            },
            { 
              name: 'certification', 
              label: t('Certification'), 
              type: 'file',
              accept: '.pdf,.doc,.docx,.jpg,.jpeg,.png',
              helpText: t('Upload certification file (max 5MB)'),
              showWhen: (formData) => formData.status === 'completed'
            },
            { 
              name: 'score', 
              label: t('Score (%)'), 
              type: 'number',
              min: 0,
              max: 100,
              step: 0.01,
              showWhen: (formData) => ['completed', 'failed'].includes(formData.status)
            },
            { 
              name: 'is_passed', 
              label: t('Passed'), 
              type: 'checkbox',
              showWhen: (formData) => ['completed', 'failed'].includes(formData.status)
            },
            { 
              name: 'feedback', 
              label: t('Feedback'), 
              type: 'textarea',
              showWhen: (formData) => ['completed', 'failed'].includes(formData.status)
            },
            { 
              name: 'notes', 
              label: t('Notes'), 
              type: 'textarea'
            }
          ],
          modalSize: 'lg'
        }}
        initialData={employeeTraining}
        title={t('Edit Training Assignment')}
        mode="edit"
      />
      
      {/* Assessment Modal */}
      <CrudFormModal
        isOpen={isAssessmentModalOpen}
        onClose={() => setIsAssessmentModalOpen(false)}
        onSubmit={handleAssessmentSubmit}
        formConfig={{
          fields: [
            { 
              name: 'training_assessment_id', 
              label: t('Assessment'), 
              type: 'select',
              required: true,
              options: availableAssessments?.map((assessment: any) => ({
                value: assessment.id.toString(),
                label: assessment.name
              })) || []
            },
            { 
              name: 'score', 
              label: t('Score (%)'), 
              type: 'number',
              required: true,
              min: 0,
              max: 100,
              step: 0.01
            },
            { 
              name: 'is_passed', 
              label: t('Passed'), 
              type: 'checkbox'
            },
            { 
              name: 'feedback', 
              label: t('Feedback'), 
              type: 'textarea'
            },
            { 
              name: 'assessment_date', 
              label: t('Assessment Date'), 
              type: 'date',
              required: true,
              defaultValue: new Date().toISOString().split('T')[0]
            },
            { 
              name: 'update_training_status', 
              label: t('Update Training Status'), 
              type: 'checkbox',
              helpText: t('Update the training status based on this assessment result')
            }
          ],
          modalSize: 'md'
        }}
        initialData={{}}
        title={t('Record Assessment Result')}
        mode="create"
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={`${employeeTraining.employee?.name || ''} - ${employeeTraining.training_program?.name || ''}`}
        entityName="training assignment"
      />
    </PageTemplate>
  );
}