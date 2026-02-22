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
import { Plus, FileText, MessageSquare, CheckSquare, StickyNote, Gavel } from 'lucide-react';
import { format } from 'date-fns';

export default function MeetingMinutes() {
  const { t } = useTranslation();
  const { auth, meetingMinutes, meetings, employees, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [typeFilter, setTypeFilter] = useState(pageFilters.type || '_empty_');
  const [meetingFilter, setMeetingFilter] = useState(pageFilters.meeting_id || '_empty_');
  const [recorderFilter, setRecorderFilter] = useState(pageFilters.recorded_by || '_empty_');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  
  const hasActiveFilters = () => {
    return typeFilter !== '_empty_' || meetingFilter !== '_empty_' || recorderFilter !== '_empty_' || searchTerm !== '';
  };
  
  const activeFilterCount = () => {
    return (typeFilter !== '_empty_' ? 1 : 0) + (meetingFilter !== '_empty_' ? 1 : 0) + (recorderFilter !== '_empty_' ? 1 : 0) + (searchTerm !== '' ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const applyFilters = () => {
    router.get(route('meetings.meeting-minutes.index'), { 
      page: 1,
      search: searchTerm || undefined,
      type: typeFilter !== '_empty_' ? typeFilter : undefined,
      meeting_id: meetingFilter !== '_empty_' ? meetingFilter : undefined,
      recorded_by: recorderFilter !== '_empty_' ? recorderFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('meetings.meeting-minutes.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      type: typeFilter !== '_empty_' ? typeFilter : undefined,
      meeting_id: meetingFilter !== '_empty_' ? meetingFilter : undefined,
      recorded_by: recorderFilter !== '_empty_' ? recorderFilter : undefined,
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
    }
  };
  
  const handleAddNew = () => {
    setCurrentItem(null);
    setFormMode('create');
    setIsFormModalOpen(true);
  };
  
  const handleFormSubmit = (formData: any) => {
    // Combine date and time fields into recorded_at
    if (formData.recorded_date && formData.recorded_time) {
      formData.recorded_at = `${formData.recorded_date} ${formData.recorded_time}`;
    } else if (formData.recorded_date) {
      formData.recorded_at = `${formData.recorded_date} 00:00`;
    }
    
    // Remove separate date/time fields
    delete formData.recorded_date;
    delete formData.recorded_time;

    if (formMode === 'create') {
      toast.loading(t('Creating meeting minute...'));

      router.post(route('meetings.meeting-minutes.store'), formData, {
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
            toast.error(`Failed to create meeting minute: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating meeting minute...'));

      router.put(route('meetings.meeting-minutes.update', currentItem.id), formData, {
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
            toast.error(`Failed to update meeting minute: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    }
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting meeting minute...'));

    router.delete(route('meetings.meeting-minutes.destroy', currentItem.id), {
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
          toast.error(`Failed to delete meeting minute: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setTypeFilter('_empty_');
    setMeetingFilter('_empty_');
    setRecorderFilter('_empty_');
    setShowFilters(false);
    
    router.get(route('meetings.meeting-minutes.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const pageActions = [];
  
  if (hasPermission(permissions, 'create-meeting-minutes')) {
    pageActions.push({
      label: t('Add Minute'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Meetings'), href: route('meetings.meeting-minutes.index') },
    { title: t('Meeting Minutes') }
  ];

  const getTypeColor = (type: string) => {
    switch (type) {
      case 'Discussion': return 'bg-blue-50 text-blue-700 ring-blue-600/20';
      case 'Decision': return 'bg-green-50 text-green-700 ring-green-600/20';
      case 'Action Item': return 'bg-orange-50 text-orange-700 ring-orange-600/20';
      case 'Note': return 'bg-gray-50 text-gray-600 ring-gray-500/10';
      default: return 'bg-gray-50 text-gray-600 ring-gray-500/10';
    }
  };

  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'Discussion': return <MessageSquare className="h-3 w-3" />;
      case 'Decision': return <Gavel className="h-3 w-3" />;
      case 'Action Item': return <CheckSquare className="h-3 w-3" />;
      case 'Note': return <StickyNote className="h-3 w-3" />;
      default: return <FileText className="h-3 w-3" />;
    }
  };

  const columns = [
    { 
      key: 'meeting.title', 
      label: t('Meeting'),
      render: (_, row) => (
        <div>
          <div className="font-medium">{row.meeting?.title}</div>
          <div className="text-xs text-gray-500">
            {row.meeting?.meeting_date ? window.appSettings?.formatDateTimeSimple(row.meeting?.meeting_date, false) || new Date(row.meeting?.meeting_date).toLocaleDateString() : '-'}
          </div>
        </div>
      )
    },
    { 
      key: 'topic', 
      label: t('Topic'), 
      sortable: true,
      render: (value) => <div className="font-medium">{value}</div>
    },
    { 
      key: 'type', 
      label: t('Type'),
      render: (value) => (
        <span className={`inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getTypeColor(value)}`}>
          {getTypeIcon(value)}
          {t(value)}
        </span>
      )
    },
    { 
      key: 'content', 
      label: t('Content'),
      render: (value) => (
        <div className="max-w-xs">
          <div className="text-sm text-gray-600 truncate">
            {value ? value.substring(0, 100) + (value.length > 100 ? '...' : '') : '-'}
          </div>
        </div>
      )
    },
    { 
      key: 'recorder.name', 
      label: t('Recorded By'),
      render: (_, row) => row.recorder?.name || '-'
    },
    { 
      key: 'recorded_at', 
      label: t('Recorded At'),
      sortable: true,
      render: (value) => window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleDateString()
    }
  ];

  const actions = [
    { 
      label: t('View'), 
      icon: 'Eye', 
      action: 'view', 
      className: 'text-blue-500',
      requiredPermission: 'view-meeting-minutes'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-meeting-minutes'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-meeting-minutes'
    }
  ];

  const typeOptions = [
    { value: '_empty_', label: t('All Types') , disabled: true},
    { value: 'Discussion', label: t('Discussion') },
    { value: 'Decision', label: t('Decision') },
    { value: 'Action Item', label: t('Action Item') },
    { value: 'Note', label: t('Note') }
  ];

  const meetingOptions = [
    { value: '_empty_', label: t('All Meetings') , disabled: true},
    ...(meetings || []).map((meeting: any) => ({
      value: meeting.id.toString(),
      label: `${meeting.title} - ${format(new Date(meeting.meeting_date), 'MMM dd, yyyy')}`
    }))
  ];

  const recorderOptions = [
    { value: '_empty_', label: t('All Recorders'), disabled: true },
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: emp.name
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
    { value: '_empty_', label: t('Select Recorder') },
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: emp.name
    }))
  ];

  return (
    <PageTemplate 
      title={t("Meeting Minutes")} 
      url="/meetings/meeting-minutes"
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
              name: 'type',
              label: t('Type'),
              type: 'select',
              value: typeFilter,
              onChange: setTypeFilter,
              options: typeOptions
            },
            {
              name: 'meeting_id',
              label: t('Meeting'),
              type: 'select',
              value: meetingFilter,
              onChange: setMeetingFilter,
              options: meetingOptions,
              searchable: true
            },
            {
              name: 'recorded_by',
              label: t('Recorder'),
              type: 'select',
              value: recorderFilter,
              onChange: setRecorderFilter,
              options: recorderOptions,
              searchable: true
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
            router.get(route('meetings.meeting-minutes.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              type: typeFilter !== '_empty_' ? typeFilter : undefined,
              meeting_id: meetingFilter !== '_empty_' ? meetingFilter : undefined,
              recorded_by: recorderFilter !== '_empty_' ? recorderFilter : undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={meetingMinutes?.data || []}
          from={meetingMinutes?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-meeting-minutes',
            create: 'create-meeting-minutes',
            edit: 'edit-meeting-minutes',
            delete: 'delete-meeting-minutes'
          }}
        />

        <Pagination
          from={meetingMinutes?.from || 0}
          to={meetingMinutes?.to || 0}
          total={meetingMinutes?.total || 0}
          links={meetingMinutes?.links}
          entityName={t("meeting minutes")}
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
              name: 'topic', 
              label: t('Topic'), 
              type: 'text', 
              required: true 
            },
            { 
              name: 'type', 
              label: t('Type'), 
              type: 'select', 
              required: true,
              options: typeOptions.filter(opt => opt.value !== '_empty_')
            },
            { 
              name: 'content', 
              label: t('Content'), 
              type: 'textarea', 
              required: true,
              rows: 6
            },
            { 
              name: 'recorded_by', 
              label: t('Recorded By'), 
              type: 'select', 
              required: true,
              options: employeeOptions.filter(opt => opt.value !== '_empty_'),
              searchable: true
            },
            { 
              name: 'recorded_date', 
              label: t('Recorded Date'), 
              type: 'date',
              helpText: t('Leave empty to use current date')
            },
            { 
              name: 'recorded_time', 
              label: t('Recorded Time'), 
              type: 'time',
              helpText: t('Leave empty to use current time')
            }
          ],
          modalSize: 'xl'
        }}
        initialData={currentItem ? {
          ...currentItem,
          recorded_date: currentItem.recorded_at ? new Date(currentItem.recorded_at).toISOString().split('T')[0] : '',
          recorded_time: currentItem.recorded_at ? new Date(currentItem.recorded_at).toTimeString().substring(0, 5) : ''
        } : null}
        title={
          formMode === 'create'
            ? t('Add Meeting Minute')
            : formMode === 'edit'
              ? t('Edit Meeting Minute')
              : t('View Meeting Minute')
        }
        mode={formMode}
      />

      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.topic || ''}
        entityName="meeting minute"
      />
    </PageTemplate>
  );
}