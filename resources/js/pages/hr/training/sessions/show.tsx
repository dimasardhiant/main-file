// pages/hr/training/sessions/show.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { hasPermission } from '@/utils/authorization';
import { useTranslation } from 'react-i18next';
import { ArrowLeft, Edit, Trash, Check, X } from 'lucide-react';
import { format } from 'date-fns';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { Checkbox } from '@/components/ui/checkbox';

export default function TrainingSessionShow() {
  const { t } = useTranslation();
  const { auth, trainingSession, attendanceData } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  // State
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isAttendanceModalOpen, setIsAttendanceModalOpen] = useState(false);
  const [attendance, setAttendance] = useState(attendanceData || []);
  
  const handleBackToList = () => {
    router.get(route('hr.training-sessions.index'));
  };
  
  const handleEdit = () => {
    setIsFormModalOpen(true);
  };
  
  const handleDelete = () => {
    setIsDeleteModalOpen(true);
  };
  
  const handleManageAttendance = () => {
    setIsAttendanceModalOpen(true);
  };
  
  const handleAttendanceChange = (employeeId: number, isPresent: boolean) => {
    setAttendance(attendance.map((item: any) => 
      item.employee_id === employeeId ? { ...item, is_present: isPresent } : item
    ));
  };
  
  const handleAttendanceNoteChange = (employeeId: number, notes: string) => {
    setAttendance(attendance.map((item: any) => 
      item.employee_id === employeeId ? { ...item, notes } : item
    ));
  };
  
  const handleFormSubmit = (formData: any) => {
    // Combine date and time fields
    const submitData = {
      ...formData,
      start_date: formData.start_date && formData.start_time ? `${formData.start_date} ${formData.start_time}` : formData.start_date,
      end_date: formData.end_date && formData.end_time ? `${formData.end_date} ${formData.end_time}` : formData.end_date
    };
    
    // Remove separate time fields
    delete submitData.start_time;
    delete submitData.end_time;
    
    toast.loading(t('Updating session...'));

    router.put(route('hr.training-sessions.update', trainingSession.id), submitData, {
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
          toast.error(t('Failed to update session: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleAttendanceSubmit = () => {
    toast.loading(t('Updating attendance...'));

    router.post(route('hr.training-sessions.update-attendance', trainingSession.id), { attendance }, {
      onSuccess: (page) => {
        setIsAttendanceModalOpen(false);
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
          toast.error(t('Failed to update attendance: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting session...'));
    
    router.delete(route('hr.training-sessions.destroy', trainingSession.id), {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
        router.get(route('hr.training-sessions.index'));
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to delete session: {{errors}}', { errors: Object.values(errors).join(', ') }));
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
  if (hasPermission(permissions, 'edit-training-sessions')) {
    pageActions.push({
      label: t('Edit'),
      icon: <Edit className="h-4 w-4 mr-2" />,
      variant: 'default' as const,
      onClick: handleEdit
    });
  }
  
  if (hasPermission(permissions, 'delete-training-sessions')) {
    pageActions.push({
      label: t('Delete'),
      icon: <Trash className="h-4 w-4 mr-2" />,
      variant: 'destructive' as const,
      onClick: handleDelete
    });
  }
  
  // Add Manage Attendance button
  if (hasPermission(permissions, 'manage-attendance')) {
    pageActions.push({
      label: t('Manage Attendance'),
      icon: <Check className="h-4 w-4 mr-2" />,
      variant: 'default' as const,
      onClick: handleManageAttendance
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.training-sessions.index') },
    { title: t('Training Management'), href: route('hr.training-sessions.index') },
    { title: t('Training Sessions'), href: route('hr.training-sessions.index') },
    { title: trainingSession.name || trainingSession.training_program?.name || '' }
  ];
  
  // Status colors for badges
  const statusClasses = {
    'scheduled': 'bg-blue-50 text-blue-700 ring-blue-600/20',
    'in_progress': 'bg-amber-50 text-amber-700 ring-amber-600/20',
    'completed': 'bg-green-50 text-green-700 ring-green-600/20',
    'cancelled': 'bg-red-50 text-red-700 ring-red-600/20'
  };

  return (
    <PageTemplate 
      title={trainingSession.name || trainingSession.training_program?.name || ''} 
      url={`/hr/training/sessions/${trainingSession.id}`}
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Session Details */}
        <div className="lg:col-span-2">
          <Card className="mb-6">
            <CardHeader>
              <div className="flex justify-between items-start">
                <div>
                  <CardTitle className="text-2xl">{trainingSession.name || trainingSession.training_program?.name || ''}</CardTitle>
                  <CardDescription className="mt-2">
                    {trainingSession.training_program?.name || ''}
                  </CardDescription>
                </div>
                <Badge className={`${statusClasses[trainingSession.status] || ''}`}>
                  {trainingSession.status.charAt(0).toUpperCase() + trainingSession.status.slice(1).replace('_', ' ')}
                </Badge>
              </div>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Date')}</h3>
                  <p>{window.appSettings?.formatDateTimeSimple(trainingSession.start_date, false) || format(new Date(trainingSession.start_date), 'MMMM dd, yyyy')}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Time')}</h3>
                  <p>
                    {window.appSettings?.formatDateTimeSimple(trainingSession.start_date, true)?.split(' ').slice(-2).join(' ') || format(new Date(trainingSession.start_date), 'h:mm a')} - {window.appSettings?.formatDateTimeSimple(trainingSession.end_date, true)?.split(' ').slice(-2).join(' ') || format(new Date(trainingSession.end_date), 'h:mm a')}
                  </p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Duration')}</h3>
                  <p>
                    {new Date(trainingSession.end_date).getTime() - new Date(trainingSession.start_date).getTime() > 0 
                      ? `${Math.round((new Date(trainingSession.end_date).getTime() - new Date(trainingSession.start_date).getTime()) / (1000 * 60 * 60))} ${t('hours')}`
                      : '-'
                    }
                  </p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Location Type')}</h3>
                  <p>
                    <Badge variant="outline" className={trainingSession.location_type === 'virtual' ? 'bg-blue-50 text-blue-700' : 'bg-green-50 text-green-700'}>
                      {trainingSession.location_type === 'virtual' ? t('Virtual') : t('Physical')}
                    </Badge>
                  </p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">
                    {trainingSession.location_type === 'virtual' ? t('Meeting Link') : t('Location')}
                  </h3>
                  <p>
                    {trainingSession.location_type === 'virtual' 
                      ? (trainingSession.meeting_link 
                          ? <a href={trainingSession.meeting_link} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">{trainingSession.meeting_link}</a> 
                          : '-')
                      : (trainingSession.location || '-')
                    }
                  </p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Created')}</h3>
                  <p>{auth.user?.name || '-'}</p>
                </div>
              </div>
              
              {trainingSession.notes && (
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Notes')}</h3>
                  <p className="mt-1">{trainingSession.notes}</p>
                </div>
              )}
            </CardContent>
          </Card>
          
          {/* Attendance */}
          <Card>
            <CardHeader>
              <CardTitle>{t('Attendance')}</CardTitle>
            </CardHeader>
            <CardContent>
              {attendance && attendance.length > 0 ? (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>{t('Employee')}</TableHead>
                      <TableHead>{t('Status')}</TableHead>
                      <TableHead>{t('Notes')}</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {attendance.map((item: any) => (
                      <TableRow key={item.employee_id}>
                        <TableCell>
                          <div className="font-medium">{item.name}</div>
                          <div className="text-xs text-gray-500">{item.employee_id_display}</div>
                        </TableCell>
                        <TableCell>
                          {item.is_present ? (
                            <Badge variant="outline" className="bg-green-50 text-green-700">
                              {t('Present')}
                            </Badge>
                          ) : (
                            <Badge variant="outline" className="bg-red-50 text-red-700">
                              {t('Absent')}
                            </Badge>
                          )}
                        </TableCell>
                        <TableCell>
                          {item.notes || '-'}
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              ) : (
                <div className="text-center py-4 text-gray-500">
                  {t('No attendance data available')}
                </div>
              )}
              
              {hasPermission(permissions, 'manage-attendance') && (
                <div className="mt-4">
                  <Button 
                    variant="default" 
                    onClick={handleManageAttendance}
                  >
                    <Check className="h-4 w-4 mr-2" />
                    {t('Manage Attendance')}
                  </Button>
                </div>
              )}
            </CardContent>
          </Card>
        </div>
        
        {/* Program and Trainers */}
        <div>
          {/* Program Details */}
          <Card className="mb-6">
            <CardHeader>
              <CardTitle>{t('Program Details')}</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Program')}</h3>
                  <p className="font-medium">{trainingSession.training_program?.name || '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Training Program')}</h3>
                  <p>{trainingSession.training_program?.name || '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Duration')}</h3>
                  <p>{trainingSession.training_program?.duration ? `${trainingSession.training_program.duration} ${t('hours')}` : '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Cost')}</h3>
                  <p>{trainingSession.training_program?.cost ? window.appSettings?.formatCurrency(parseFloat(trainingSession.training_program.cost)) : '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Capacity')}</h3>
                  <p>{trainingSession.training_program?.capacity || '-'}</p>
                </div>
              </div>
              
              <div className="mt-4">
                <Button 
                  variant="outline" 
                  onClick={() => router.get(route('hr.training-programs.show', trainingSession.training_program_id))}
                  className="w-full"
                >
                  {t('View Program Details')}
                </Button>
              </div>
            </CardContent>
          </Card>
          
          {/* Trainers */}
          <Card>
            <CardHeader>
              <CardTitle>{t('Trainers')}</CardTitle>
            </CardHeader>
            <CardContent>
              {trainingSession.trainers && trainingSession.trainers.length > 0 ? (
                <div className="space-y-4">
                  {trainingSession.trainers.map((trainer: any) => (
                    <div key={trainer.id} className="flex items-center space-x-3">
                      <div className="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                        <span className="text-gray-600 font-medium">
                          {trainer.name ? trainer.name.charAt(0).toUpperCase() : '?'}
                        </span>
                      </div>
                      <div>
                        <div className="font-medium">{trainer.name}</div>
                        <div className="text-xs text-gray-500">{trainer.email}</div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-4 text-gray-500">
                  {t('No trainers assigned')}
                </div>
              )}
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
              name: 'training_program_id', 
              label: t('Training Program'), 
              type: 'select',
              required: true,
              options: [
                { value: trainingSession.training_program_id.toString(), label: trainingSession.training_program?.name || '' }
              ],
              disabled: true
            },
            { 
              name: 'name', 
              label: t('Session Name'), 
              type: 'text',
              helpText: t('Leave blank to use program name')
            },
            { 
              name: 'start_date', 
              label: t('Start Date & Time'), 
              type: 'datetime-local',
              required: true
            },
            { 
              name: 'end_date', 
              label: t('End Date & Time'), 
              type: 'datetime-local',
              required: true
            },
            { 
              name: 'location_type', 
              label: t('Location Type'), 
              type: 'select',
              required: true,
              options: [
                { value: 'physical', label: t('Physical') },
                { value: 'virtual', label: t('Virtual') }
              ]
            },
            { 
              name: 'location', 
              label: t('Location'), 
              type: 'text',
              showWhen: (formData) => formData.location_type === 'physical'
            },
            { 
              name: 'meeting_link', 
              label: t('Meeting Link'), 
              type: 'text',
              showWhen: (formData) => formData.location_type === 'virtual'
            },
            { 
              name: 'status', 
              label: t('Status'), 
              type: 'select',
              required: true,
              options: [
                { value: 'scheduled', label: t('Scheduled') },
                { value: 'in_progress', label: t('In Progress') },
                { value: 'completed', label: t('Completed') },
                { value: 'cancelled', label: t('Cancelled') }
              ]
            },
            { 
              name: 'notes', 
              label: t('Notes'), 
              type: 'textarea'
            },
            { 
              name: 'trainer_ids', 
              label: t('Trainers'), 
              type: 'multiselect',
              options: auth.trainers?.map((trainer: any) => ({
                value: trainer.id.toString(),
                label: trainer.name
              })) || []
            }
          ],
          modalSize: 'lg'
        }}
        initialData={{
          ...trainingSession,
          start_date: trainingSession.start_date ? trainingSession.start_date.split(' ')[0] : '',
          start_time: trainingSession.start_date ? trainingSession.start_date.split(' ')[1]?.substring(0, 5) : '',
          end_date: trainingSession.end_date ? trainingSession.end_date.split(' ')[0] : '',
          end_time: trainingSession.end_date ? trainingSession.end_date.split(' ')[1]?.substring(0, 5) : '',
          trainer_ids: trainingSession.trainers?.map((trainer: any) => trainer.id.toString())
        }}
        title={t('Edit Training Session')}
        mode="edit"
      />
      
      {/* Attendance Modal */}
      <CrudFormModal
        isOpen={isAttendanceModalOpen}
        onClose={() => setIsAttendanceModalOpen(false)}
        onSubmit={handleAttendanceSubmit}
        formConfig={{
          fields: [],
          modalSize: 'lg',
          customContent: (
            <div className="space-y-4">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>{t('Employee')}</TableHead>
                    <TableHead>{t('Present')}</TableHead>
                    <TableHead>{t('Notes')}</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {attendance.map((item: any) => (
                    <TableRow key={item.employee_id}>
                      <TableCell>
                        <div className="font-medium">{item.name}</div>
                        <div className="text-xs text-gray-500">{item.employee_id_display}</div>
                      </TableCell>
                      <TableCell>
                        <div className="flex items-center space-x-2">
                          <Checkbox 
                            id={`present-${item.employee_id}`}
                            checked={item.is_present}
                            onCheckedChange={(checked) => handleAttendanceChange(item.employee_id, !!checked)}
                          />
                          <label 
                            htmlFor={`present-${item.employee_id}`}
                            className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                          >
                            {t('Present')}
                          </label>
                        </div>
                      </TableCell>
                      <TableCell>
                        <input
                          type="text"
                          value={item.notes || ''}
                          onChange={(e) => handleAttendanceNoteChange(item.employee_id, e.target.value)}
                          className="w-full p-2 border rounded-md"
                          placeholder={t('Add notes')}
                        />
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )
        }}
        initialData={{}}
        title={t('Manage Attendance')}
        mode="custom"
        submitLabel={t('Save Attendance')}
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={trainingSession.name || trainingSession.training_program?.name || ''}
        entityName="training session"
      />
    </PageTemplate>
  );
}