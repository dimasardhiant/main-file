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
import { Plus, Clock, Calendar, User, DiscAlbum } from 'lucide-react';
import { format } from 'date-fns';

export default function Meetings() {
  const { t } = useTranslation();
  const { auth, meetings, meetingTypes, meetingRooms, employees, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [statusFilter, setStatusFilter] = useState(pageFilters.status || '_empty_');
  const [typeFilter, setTypeFilter] = useState(pageFilters.type_id || '_empty_');
  const [organizerFilter, setOrganizerFilter] = useState(pageFilters.organizer_id || '_empty_');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isStatusModalOpen, setIsStatusModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  const [selectedStatus, setSelectedStatus] = useState('');
  
  const hasActiveFilters = () => {
    return statusFilter !== '_empty_' || typeFilter !== '_empty_' || organizerFilter !== '_empty_' || searchTerm !== '';
  };
  
  const activeFilterCount = () => {
    return (statusFilter !== '_empty_' ? 1 : 0) + (typeFilter !== '_empty_' ? 1 : 0) + (organizerFilter !== '_empty_' ? 1 : 0) + (searchTerm !== '' ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const applyFilters = () => {
    router.get(route('meetings.meetings.index'), { 
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      type_id: typeFilter !== '_empty_' ? typeFilter : undefined,
      organizer_id: organizerFilter !== '_empty_' ? organizerFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('meetings.meetings.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      type_id: typeFilter !== '_empty_' ? typeFilter : undefined,
      organizer_id: organizerFilter !== '_empty_' ? organizerFilter : undefined,
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
      case 'update-status':
        setSelectedStatus(item.status);
        setIsStatusModalOpen(true);
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
      toast.loading(t('Creating meeting...'));
      
      router.post(route('meetings.meetings.store'), formData, {
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
            toast.error(`Failed to create meeting: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating meeting...'));
      
      router.put(route('meetings.meetings.update', currentItem.id), formData, {
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
            toast.error(`Failed to update meeting: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    }
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting meeting...'));
    
    router.delete(route('meetings.meetings.destroy', currentItem.id), {
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
          toast.error(`Failed to delete meeting: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };
  
  const handleStatusUpdate = (formData: any) => {
    toast.loading(t('Updating status...'));
    
    router.put(route('meetings.meetings.update-status', currentItem.id), { status: formData.status }, {
      onSuccess: (page) => {
        setIsStatusModalOpen(false);
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
          toast.error(`Failed to update status: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setStatusFilter('_empty_');
    setTypeFilter('_empty_');
    setOrganizerFilter('_empty_');
    setShowFilters(false);
    
    router.get(route('meetings.meetings.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const pageActions = [];
  
  if (hasPermission(permissions, 'create-meetings')) {
    pageActions.push({
      label: t('Schedule Meeting'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Meetings'), href: route('meetings.meetings.index') },
    { title: t('Meetings') }
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Scheduled': return 'bg-blue-50 text-blue-700 ring-blue-600/20';
      case 'In Progress': return 'bg-yellow-50 text-yellow-800 ring-yellow-600/20';
      case 'Completed': return 'bg-green-50 text-green-700 ring-green-600/20';
      case 'Cancelled': return 'bg-red-50 text-red-700 ring-red-600/10';
      default: return 'bg-gray-50 text-gray-600 ring-gray-500/10';
    }
  };

  const getRecurrenceColor = (recurrence: string) => {
    if (recurrence === 'None') return 'bg-gray-50 text-gray-600 ring-gray-500/10';
    return 'bg-purple-50 text-purple-700 ring-purple-600/20';
  };

  const columns = [
    { 
      key: 'title', 
      label: t('Meeting'),
      sortable: true,
      render: (value, row) => (
        <div>
          <div className="font-medium">{value}</div>
          <div className="text-xs text-gray-500 flex items-center gap-1">
            <div 
              className="w-2 h-2 rounded-full" 
              style={{ backgroundColor: row.type?.color || '#3B82F6' }}
            ></div>
            {row.type?.name}
          </div>
        </div>
      )
    },
    { 
      key: 'meeting_date', 
      label: t('Date & Time'),
      sortable: true,
      render: (value, row) => (
        <div>
          <div className="font-medium flex items-center gap-1">
            <Calendar className="h-4 w-4 text-gray-500" />
            {window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleDateString()}
          </div>
          <div className="text-xs text-gray-500 flex items-center gap-1">
            <Clock className="h-3 w-3" />
            {window.appSettings.formatTime(row.start_time)} - {window.appSettings.formatTime(row.end_time)} 
          </div>
        </div>
      )
    },
    { 
      key: 'organizer.name', 
      label: t('Organizer'),
      render: (_, row) => (
        <div className="flex items-center gap-1">
          <User className="h-4 w-4 text-gray-500" />
          {row.organizer?.name || '-'}
        </div>
      )
    },
    { 
      key: 'room.name', 
      label: t('Room'),
      render: (_, row) => row.room?.name || '-'
    },
    { 
      key: 'recurrence', 
      label: t('Recurrence'),
      render: (value) => (
        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getRecurrenceColor(value)}`}>
          {t(value)}
        </span>
      )
    },
    { 
      key: 'status', 
      label: t('Status'),
      render: (value) => (
        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getStatusColor(value)}`}>
          {t(value)}
        </span>
      )
    }
  ];

  const actions = [
    { 
      label: t('View'), 
      icon: 'Eye', 
      action: 'view', 
      className: 'text-blue-500',
      requiredPermission: 'view-meetings'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-meetings'
    },
    { 
      label: t('Update Status'), 
      icon: 'RefreshCw', 
      action: 'update-status', 
      className: 'text-green-500',
      requiredPermission: 'manage-meeting-status'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-meetings'
    }
  ];

  const statusOptions = [
    { value: '_empty_', label: t('All Statuses'), disabled: true },
    { value: 'Scheduled', label: t('Scheduled') },
    { value: 'In Progress', label: t('In Progress') },
    { value: 'Completed', label: t('Completed') },
    { value: 'Cancelled', label: t('Cancelled') }
  ];

  const typeOptions = [
    { value: '_empty_', label: t('All Types'), disabled: true },
    ...(meetingTypes || []).map((type: any) => ({
      value: type.id.toString(),
      label: type.name
    }))
  ];

  const organizerOptions = [
    { value: '_empty_', label: t('All Organizers'), disabled: true },
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: emp.name
    }))
  ];

  const typeSelectOptions = [
    { value: '_empty_', label: t('Select Type') },
    ...(meetingTypes || []).map((type: any) => ({
      value: type.id.toString(),
      label: type.name
    }))
  ];

  const roomSelectOptions = [
    { value: '_empty_', label: t('Select Room') },
    ...(meetingRooms || []).map((room: any) => ({
      value: room.id.toString(),
      label: `${room.name} (${room.type})`
    }))
  ];

  const organizerSelectOptions = [
    { value: '_empty_', label: t('Select Organizer') },
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: emp.name
    }))
  ];

  return (
    <PageTemplate 
      title={t("Meetings")} 
      url="/meetings/meetings"
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
              name: 'status',
              label: t('Status'),
              type: 'select',
              value: statusFilter,
              onChange: setStatusFilter,
              options: statusOptions,
              
            },
            {
              name: 'type_id',
              label: t('Type'),
              type: 'select',
              value: typeFilter,
              onChange: setTypeFilter,
              options: typeOptions,
              searchable : true
            },
            {
              name: 'organizer_id',
              label: t('Organizer'),
              type: 'select',
              value: organizerFilter,
              onChange: setOrganizerFilter,
              options: organizerOptions,
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
            router.get(route('meetings.meetings.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              status: statusFilter !== '_empty_' ? statusFilter : undefined,
              type_id: typeFilter !== '_empty_' ? typeFilter : undefined,
              organizer_id: organizerFilter !== '_empty_' ? organizerFilter : undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={meetings?.data || []}
          from={meetings?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-meetings',
            create: 'create-meetings',
            edit: 'edit-meetings',
            delete: 'delete-meetings'
          }}
        />

        <Pagination
          from={meetings?.from || 0}
          to={meetings?.to || 0}
          total={meetings?.total || 0}
          links={meetings?.links}
          entityName={t("meetings")}
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
              name: 'title', 
              label: t('Meeting Title'), 
              type: 'text', 
              required: true 
            },
            { 
              name: 'description', 
              label: t('Description'), 
              type: 'textarea' 
            },
            { 
              name: 'type_id', 
              label: t('Meeting Type'), 
              type: 'select', 
              required: true,
              options: typeSelectOptions.filter(opt => opt.value !== '_empty_'),
              searchable : true
            },
            { 
              name: 'room_id', 
              label: t('Meeting Room'), 
              type: 'select',
              options: roomSelectOptions.filter(opt => opt.value !== '_empty_'),
              searchable : true
            },
            { 
              name: 'meeting_date', 
              label: t('Meeting Date'), 
              type: 'date', 
              required: true 
            },
            { 
              name: 'start_time', 
              label: t('Start Time'), 
              type: 'time', 
              required: true 
            },
            { 
              name: 'end_time', 
              label: t('End Time'), 
              type: 'time', 
              required: true 
            },
            { 
              name: 'organizer_id', 
              label: t('Organizer'), 
              type: 'select', 
              required: true,
              options: organizerSelectOptions.filter(opt => opt.value !== '_empty_'),
              searchable : true
            },
            { 
              name: 'recurrence', 
              label: t('Recurrence'), 
              type: 'select', 
              required: true,
              options: [
                { value: 'None', label: t('None') },
                { value: 'Daily', label: t('Daily') },
                { value: 'Weekly', label: t('Weekly') },
                { value: 'Monthly', label: t('Monthly') }
              ]
            },
            { 
              name: 'recurrence_end_date', 
              label: t('Recurrence End Date'), 
              type: 'date',
              helpText: t('Required for recurring meetings')
            },
            { 
              name: 'agenda', 
              label: t('Agenda'), 
              type: 'textarea',
              rows: 4
            }
          ],
          modalSize: 'xl'
        }}
        initialData={currentItem ? {
          ...currentItem,
          meeting_date: currentItem.meeting_date ? window.appSettings.formatDateTimeSimple(currentItem.meeting_date, false) : currentItem.meeting_date
        } : null}
        title={
          formMode === 'create'
            ? t('Schedule New Meeting')
            : formMode === 'edit'
              ? t('Edit Meeting')
              : t('View Meeting')
        }
        mode={formMode}
      />

      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.title || ''}
        entityName="meeting"
      />

      <CrudFormModal
        isOpen={isStatusModalOpen}
        onClose={() => setIsStatusModalOpen(false)}
        onSubmit={handleStatusUpdate}
        formConfig={{
          fields: [
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              required: true,
              options: [
                { value: 'Scheduled', label: t('Scheduled') },
                { value: 'In Progress', label: t('In Progress') },
                { value: 'Completed', label: t('Completed') },
                { value: 'Cancelled', label: t('Cancelled') }
              ]
            }
          ],
          modalSize: 'sm'
        }}
        initialData={{ status: selectedStatus }}
        title={t('Update Meeting Status')}
        mode="edit"
        submitButtonText={t('Update Status')}
      />
    </PageTemplate>
  );
}