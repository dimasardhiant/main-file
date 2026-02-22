import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Plus, User, Calendar, CheckCircle, XCircle, Clock, AlertCircle } from 'lucide-react';
import { format } from 'date-fns';

export default function MeetingAttendees() {
  const { t } = useTranslation();
  const { auth, meetingAttendees, meetings, employees, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [rsvpFilter, setRsvpFilter] = useState(pageFilters.rsvp_status || '_empty_');
  const [attendanceFilter, setAttendanceFilter] = useState(pageFilters.attendance_status || '_empty_');
  const [meetingFilter, setMeetingFilter] = useState(pageFilters.meeting_id || '_empty_');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isRsvpModalOpen, setIsRsvpModalOpen] = useState(false);
  const [isAttendanceModalOpen, setIsAttendanceModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  const [selectedRsvpStatus, setSelectedRsvpStatus] = useState('');
  const [selectedAttendanceStatus, setSelectedAttendanceStatus] = useState('');
  
  const hasActiveFilters = () => {
    return rsvpFilter !== '_empty_' || attendanceFilter !== '_empty_' || meetingFilter !== '_empty_' || searchTerm !== '';
  };
  
  const activeFilterCount = () => {
    return (rsvpFilter !== '_empty_' ? 1 : 0) + (attendanceFilter !== '_empty_' ? 1 : 0) + (meetingFilter !== '_empty_' ? 1 : 0) + (searchTerm !== '' ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const applyFilters = () => {
    router.get(route('meetings.meeting-attendees.index'), { 
      page: 1,
      search: searchTerm || undefined,
      rsvp_status: rsvpFilter !== '_empty_' ? rsvpFilter : undefined,
      attendance_status: attendanceFilter !== '_empty_' ? attendanceFilter : undefined,
      meeting_id: meetingFilter !== '_empty_' ? meetingFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('meetings.meeting-attendees.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      rsvp_status: rsvpFilter !== '_empty_' ? rsvpFilter : undefined,
      attendance_status: attendanceFilter !== '_empty_' ? attendanceFilter : undefined,
      meeting_id: meetingFilter !== '_empty_' ? meetingFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleAction = (action: string, item: any) => {
    setCurrentItem(item);
    
    switch (action) {
      case 'view':
        setFormMode('view');
        setIsFormModalOpen(true);
        break;
      case 'edit':
        setFormMode('edit');
        setIsFormModalOpen(true);
        break;
      case 'delete':
        setIsDeleteModalOpen(true);
        break;
      case 'update-rsvp':
        setSelectedRsvpStatus(item.rsvp_status);
        setIsRsvpModalOpen(true);
        break;
      case 'update-attendance':
        setSelectedAttendanceStatus(item.attendance_status);
        setIsAttendanceModalOpen(true);
        break;
    }
  };
  
  const handleAddNew = () => {
    setCurrentItem(null);
    setFormMode('create');
    setIsFormModalOpen(true);
  };
  
  const handleFormSubmit = (formData: any) => {
    if (formMode === 'create') {
      toast.loading(t('Adding meeting attendee...'));
      
      router.post(route('meetings.meeting-attendees.store'), formData, {
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
            toast.error(errors);
          } else {
            toast.error(`Failed to add attendee: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating meeting attendee...'));
      
      router.put(route('meetings.meeting-attendees.update', currentItem.id), formData, {
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
            toast.error(errors);
          } else {
            toast.error(`Failed to update attendee: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    }
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Removing meeting attendee...'));
    
    router.delete(route('meetings.meeting-attendees.destroy', currentItem.id), {
      onSuccess: (page) => {
        setIsDeleteModalOpen(false);
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
          toast.error(errors);
        } else {
          toast.error(`Failed to remove attendee: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };
  
  const handleRsvpUpdate = (formData: any) => {
    toast.loading(t('Updating RSVP status...'));
    
    router.put(route('meetings.meeting-attendees.update-rsvp', currentItem.id), {
      rsvp_status: formData.rsvp_status,
      decline_reason: formData.decline_reason
    }, {
      onSuccess: (page) => {
        setIsRsvpModalOpen(false);
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
          toast.error(errors);
        } else {
          toast.error(`Failed to update RSVP: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };
  
  const handleAttendanceUpdate = (formData: any) => {
    toast.loading(t('Updating attendance status...'));
    
    router.put(route('meetings.meeting-attendees.update-attendance', currentItem.id), {
      attendance_status: formData.attendance_status
    }, {
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
          toast.error(errors);
        } else {
          toast.error(`Failed to update attendance: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setRsvpFilter('_empty_');
    setAttendanceFilter('_empty_');
    setMeetingFilter('_empty_');
    setShowFilters(false);
    
    router.get(route('meetings.meeting-attendees.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const pageActions = [];
  
  if (hasPermission(permissions, 'create-meeting-attendees')) {
    pageActions.push({
      label: t('Add Attendee'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Meetings'), href: route('meetings.meeting-attendees.index') },
    { title: t('Meeting Attendees') }
  ];

  const getRsvpColor = (status: string) => {
    switch (status) {
      case 'Accepted': return 'bg-green-50 text-green-700 ring-green-600/20';
      case 'Declined': return 'bg-red-50 text-red-700 ring-red-600/10';
      case 'Tentative': return 'bg-yellow-50 text-yellow-800 ring-yellow-600/20';
      case 'Pending': return 'bg-gray-50 text-gray-600 ring-gray-500/10';
      default: return 'bg-gray-50 text-gray-600 ring-gray-500/10';
    }
  };

  const getAttendanceColor = (status: string) => {
    switch (status) {
      case 'Present': return 'bg-green-50 text-green-700 ring-green-600/20';
      case 'Late': return 'bg-yellow-50 text-yellow-800 ring-yellow-600/20';
      case 'Left Early': return 'bg-orange-50 text-orange-700 ring-orange-600/20';
      case 'Not Attended': return 'bg-red-50 text-red-700 ring-red-600/10';
      default: return 'bg-gray-50 text-gray-600 ring-gray-500/10';
    }
  };

  const getRsvpIcon = (status: string) => {
    switch (status) {
      case 'Accepted': return <CheckCircle className="h-3 w-3" />;
      case 'Declined': return <XCircle className="h-3 w-3" />;
      case 'Tentative': return <AlertCircle className="h-3 w-3" />;
      case 'Pending': return <Clock className="h-3 w-3" />;
      default: return <Clock className="h-3 w-3" />;
    }
  };

  const columns = [
    { 
      key: 'user.name', 
      label: t('Attendee'),
      render: (_, row) => (
        <div className="flex items-center gap-2">
          <User className="h-4 w-4 text-gray-500" />
          <div>
            <div className="font-medium">{row.user?.name}</div>
            <div className="text-xs text-gray-500">
              {row.type === 'Required' ? (
                <span className="text-red-600">{t('Required')}</span>
              ) : (
                <span className="text-blue-600">{t('Optional')}</span>
              )}
            </div>
          </div>
        </div>
      )
    },
    { 
      key: 'meeting.title', 
      label: t('Meeting'),
      render: (_, row) => (
        <div>
          <div className="font-medium">{row.meeting?.title}</div>
          <div className="text-xs text-gray-500 flex items-center gap-1">
            <Calendar className="h-3 w-3" />
            {row.meeting?.meeting_date ? window.appSettings?.formatDateTimeSimple(row.meeting?.meeting_date, false) : '-'}
          </div>
        </div>
      )
    },
    { 
      key: 'rsvp_status', 
      label: t('RSVP'),
      render: (value) => (
        <span className={`inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getRsvpColor(value)}`}>
          {getRsvpIcon(value)}
          {t(value)}
        </span>
      )
    },
    { 
      key: 'attendance_status', 
      label: t('Attendance'),
      render: (value) => (
        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getAttendanceColor(value)}`}>
          {t(value)}
        </span>
      )
    },
    { 
      key: 'rsvp_date', 
      label: t('RSVP Date'),
      render: (value) => window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleDateString()
    },
    { 
      key: 'decline_reason', 
      label: t('Decline Reason'),
      render: (value) => value || '-'
    }
  ];

  const actions = [
    { 
      label: t('View'), 
      icon: 'Eye', 
      action: 'view', 
      className: 'text-blue-500',
      requiredPermission: 'view-meeting-attendees'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-meeting-attendees'
    },
    { 
      label: t('Update RSVP'), 
      icon: 'MessageSquare', 
      action: 'update-rsvp', 
      className: 'text-green-500',
      requiredPermission: 'manage-meeting-rsvp-status'
    },
    { 
      label: t('Update Attendance'), 
      icon: 'UserCheck', 
      action: 'update-attendance', 
      className: 'text-purple-500',
      requiredPermission: 'manage-meeting-attendance'
    },
    { 
      label: t('Remove'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-meeting-attendees'
    }
  ];

  const rsvpOptions = [
    { value: '_empty_', label: t('All RSVP') , disabled: true},
    { value: 'Pending', label: t('Pending') },
    { value: 'Accepted', label: t('Accepted') },
    { value: 'Declined', label: t('Declined') },
    { value: 'Tentative', label: t('Tentative') }
  ];

  const attendanceOptions = [
    { value: '_empty_', label: t('All Attendance'), disabled: true },
    { value: 'Not Attended', label: t('Not Attended') },
    { value: 'Present', label: t('Present') },
    { value: 'Late', label: t('Late') },
    { value: 'Left Early', label: t('Left Early') }
  ];

  const meetingOptions = [
    { value: '_empty_', label: t('All Meetings'), disabled: true },
    ...(meetings || []).map((meeting: any) => ({
      value: meeting.id.toString(),
      label: `${meeting.title} - ${format(new Date(meeting.meeting_date), 'MMM dd, yyyy')}`
    }))
  ];

  const meetingSelectOptions = [
    { value: '_empty_', label: t('Select Meeting') },
    ...(meetings || []).map((meeting: any) => ({
      value: meeting.id.toString(),
      label: `${meeting.title} - ${format(new Date(meeting.meeting_date), 'MMM dd, yyyy')}`
    }))
  ];

  const employeeOptions = [
    { value: '_empty_', label: t('Select Employee') },
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: emp.name
    }))
  ];

  return (
    <PageTemplate 
      title={t("Meeting Attendees")} 
      url="/meetings/meeting-attendees"
      actions={pageActions}
      breadcrumbs={breadcrumbs}
      noPadding
    >
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
        <SearchAndFilterBar
          searchTerm={searchTerm}
          onSearchChange={setSearchTerm}
          onSearch={handleSearch}
          filters={[
            {
              name: 'rsvp_status',
              label: t('RSVP Status'),
              type: 'select',
              value: rsvpFilter,
              onChange: setRsvpFilter,
              options: rsvpOptions
            },
            {
              name: 'attendance_status',
              label: t('Attendance'),
              type: 'select',
              value: attendanceFilter,
              onChange: setAttendanceFilter,
              options: attendanceOptions
            },
            {
              name: 'meeting_id',
              label: t('Meeting'),
              type: 'select',
              value: meetingFilter,
              onChange: setMeetingFilter,
              options: meetingOptions,
              searchable : true
            }
          ]}
          showFilters={showFilters}
          setShowFilters={setShowFilters}
          hasActiveFilters={hasActiveFilters}
          activeFilterCount={activeFilterCount}
          onResetFilters={handleResetFilters}
          onApplyFilters={applyFilters}
          currentPerPage={pageFilters.per_page?.toString() || "10"}
          onPerPageChange={(value) => {
            router.get(route('meetings.meeting-attendees.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              rsvp_status: rsvpFilter !== '_empty_' ? rsvpFilter : undefined,
              attendance_status: attendanceFilter !== '_empty_' ? attendanceFilter : undefined,
              meeting_id: meetingFilter !== '_empty_' ? meetingFilter : undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={meetingAttendees?.data || []}
          from={meetingAttendees?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-meeting-attendees',
            create: 'create-meeting-attendees',
            edit: 'edit-meeting-attendees',
            delete: 'delete-meeting-attendees'
          }}
        />

        <Pagination
          from={meetingAttendees?.from || 0}
          to={meetingAttendees?.to || 0}
          total={meetingAttendees?.total || 0}
          links={meetingAttendees?.links}
          entityName={t("meeting attendees")}
          onPageChange={(url) => router.get(url)}
        />
      </div>

      <CrudFormModal
        isOpen={isFormModalOpen}
        onClose={() => setIsFormModalOpen(false)}
        onSubmit={handleFormSubmit}
        formConfig={{
          fields: [
            { 
              name: 'meeting_id', 
              label: t('Meeting'), 
              type: 'select', 
              required: true,
              options: meetingSelectOptions.filter(opt => opt.value !== '_empty_'),
              searchable: true
            },
            { 
              name: 'user_id', 
              label: t('Employee'), 
              type: 'select', 
              required: true,
              options: employeeOptions.filter(opt => opt.value !== '_empty_'),
              searchable: true
            },
            { 
              name: 'type', 
              label: t('Attendance Type'), 
              type: 'select', 
              required: true,
              options: [
                { value: 'Required', label: t('Required') },
                { value: 'Optional', label: t('Optional') }
              ]
            },
            { 
              name: 'rsvp_status', 
              label: t('RSVP Status'), 
              type: 'select',
              options: rsvpOptions.filter(opt => opt.value !== '_empty_')
            },
            { 
              name: 'attendance_status', 
              label: t('Attendance Status'), 
              type: 'select',
              options: attendanceOptions.filter(opt => opt.value !== '_empty_')
            },
            { 
              name: 'decline_reason', 
              label: t('Decline Reason'), 
              type: 'textarea',
              helpText: t('Required if RSVP status is Declined')
            }
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem}
        title={
          formMode === 'create'
            ? t('Add Meeting Attendee')
            : formMode === 'edit'
              ? t('Edit Meeting Attendee')
              : t('View Meeting Attendee')
        }
        mode={formMode}
      />

      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem ? `${currentItem.user?.name} - ${currentItem.meeting?.title}` : ''}
        entityName="meeting attendee"
      />

      <CrudFormModal
        isOpen={isRsvpModalOpen}
        onClose={() => setIsRsvpModalOpen(false)}
        onSubmit={handleRsvpUpdate}
        formConfig={{
          fields: [
            {
              name: 'rsvp_status',
              label: t('RSVP Status'),
              type: 'select',
              required: true,
              options: [
                { value: 'Pending', label: t('Pending') },
                { value: 'Accepted', label: t('Accepted') },
                { value: 'Declined', label: t('Declined') },
                { value: 'Tentative', label: t('Tentative') }
              ]
            },
            {
              name: 'decline_reason',
              label: t('Decline Reason'),
              type: 'textarea',
              helpText: t('Required if RSVP status is Declined')
            }
          ],
          modalSize: 'md'
        }}
        initialData={{ 
          rsvp_status: selectedRsvpStatus,
          decline_reason: currentItem?.decline_reason || ''
        }}
        title={t('Update RSVP Status')}
        mode="edit"
        submitButtonText={t('Update RSVP')}
      />

      <CrudFormModal
        isOpen={isAttendanceModalOpen}
        onClose={() => setIsAttendanceModalOpen(false)}
        onSubmit={handleAttendanceUpdate}
        formConfig={{
          fields: [
            {
              name: 'attendance_status',
              label: t('Attendance Status'),
              type: 'select',
              required: true,
              options: [
                { value: 'Not Attended', label: t('Not Attended') },
                { value: 'Present', label: t('Present') },
                { value: 'Late', label: t('Late') },
                { value: 'Left Early', label: t('Left Early') }
              ]
            }
          ],
          modalSize: 'sm'
        }}
        initialData={{ attendance_status: selectedAttendanceStatus }}
        title={t('Update Attendance Status')}
        mode="edit"
        submitButtonText={t('Update Attendance')}
      />
    </PageTemplate>
  );
}