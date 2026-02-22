// pages/hr/training/sessions/index.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Plus, Calendar } from 'lucide-react';
import { format } from 'date-fns';
import { Badge } from '@/components/ui/badge';

export default function TrainingSessions() {
  const { t } = useTranslation();
  const { auth, trainingSessions, trainingPrograms, employees, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedProgram, setSelectedProgram] = useState(pageFilters.training_program_id || '');
  const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || '');
  const [selectedLocationType, setSelectedLocationType] = useState(pageFilters.location_type || '');
  const [dateFrom, setDateFrom] = useState(pageFilters.date_from || '');
  const [dateTo, setDateTo] = useState(pageFilters.date_to || '');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  
  // Check if any filters are active
  const hasActiveFilters = () => {
    return selectedProgram !== '' || 
           selectedStatus !== '' || 
           selectedLocationType !== '' || 
           dateFrom !== '' || 
           dateTo !== '' || 
           searchTerm !== '';
  };
  
  // Count active filters
  const activeFilterCount = () => {
    return (selectedProgram !== '' ? 1 : 0) + 
           (selectedStatus !== '' ? 1 : 0) + 
           (selectedLocationType !== '' ? 1 : 0) + 
           (dateFrom !== '' ? 1 : 0) + 
           (dateTo !== '' ? 1 : 0) + 
           (searchTerm !== '' ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const handleViewCalendar = () => {
    router.get(route('hr.training-sessions.calendar'));
  };
  
  const applyFilters = () => {
    router.get(route('hr.training-sessions.index'), { 
      page: 1,
      search: searchTerm || undefined,
      training_program_id: selectedProgram || undefined,
      status: selectedStatus || undefined,
      location_type: selectedLocationType || undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('hr.training-sessions.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      training_program_id: selectedProgram || undefined,
      status: selectedStatus || undefined,
      location_type: selectedLocationType || undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleAction = (action: string, item: any) => {
    setCurrentItem(item);
    
    switch (action) {
      case 'view':
        router.get(route('hr.training-sessions.show', item.id));
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
    // Use form data as is since we only have date fields now
    const submitData = {
      ...formData
    };
    
    if (formMode === 'create') {
      toast.loading(t('Creating training session...'));

      router.post(route('hr.training-sessions.store'), submitData, {
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
            toast.error(t('Failed to create training session: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating training session...'));

      router.put(route('hr.training-sessions.update', currentItem.id), submitData, {
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
            toast.error(t('Failed to update training session: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    }
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting training session...'));
    
    router.delete(route('hr.training-sessions.destroy', currentItem.id), {
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
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to delete training session: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setSelectedProgram('');
    setSelectedStatus('');
    setSelectedLocationType('');
    setDateFrom('');
    setDateTo('');
    setShowFilters(false);
    
    router.get(route('hr.training-sessions.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  // Define page actions
  const pageActions = [];
  
  // Add the "Calendar View" button
  pageActions.push({
    label: t('Calendar View'),
    icon: <Calendar className="h-4 w-4 mr-2" />,
    variant: 'outline' as const,
    onClick: handleViewCalendar
  });
  
  // Add the "Add New Session" button if user has permission
  if (hasPermission(permissions, 'create-training-sessions')) {
    pageActions.push({
      label: t('Add Session'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default' as const,
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.training-sessions.index') },
    { title: t('Training Management'), href: route('hr.training-sessions.index') },
    { title: t('Training Sessions') }
  ];

  // Define table columns
  const columns = [
    { 
      key: 'program', 
      label: t('Program'),
      render: (_, row) => (
        <div>
          <div className="font-medium">{row.name || row.training_program?.name || '-'}</div>
          <div className="text-xs text-gray-500">{row.training_program?.name || '-'}</div>
        </div>
      )
    },
    { 
      key: 'date_time', 
      label: t('Date & Time'),
      sortable: true,
      sortField: 'start_date',
      render: (_, row) => (
        <div>
          <div>{window.appSettings?.formatDateTimeSimple(row.start_date, false) || format(new Date(row.start_date), 'MMM dd, yyyy')}</div>
          <div className="text-xs text-gray-500">
            {window.appSettings?.formatDateTimeSimple(row.start_date, true)?.split(' ').slice(-2).join(' ') || format(new Date(row.start_date), 'h:mm a')} - {window.appSettings?.formatDateTimeSimple(row.end_date, true)?.split(' ').slice(-2).join(' ') || format(new Date(row.end_date), 'h:mm a')}
          </div>
        </div>
      )
    },
    { 
      key: 'location', 
      label: t('Location'),
      render: (value, row) => (
        <div>
          <div>{value || '-'}</div>
          <Badge variant="outline" className={row.location_type === 'virtual' ? 'bg-blue-50 text-blue-700' : 'bg-green-50 text-green-700'}>
            {row.location_type === 'virtual' ? t('Virtual') : t('Physical')}
          </Badge>
        </div>
      )
    },
    { 
      key: 'status', 
      label: t('Status'),
      sortable: true,
      render: (value) => {
        const statusClasses = {
          'scheduled': 'bg-blue-50 text-blue-700 ring-blue-600/20',
          'in_progress': 'bg-amber-50 text-amber-700 ring-amber-600/20',
          'completed': 'bg-green-50 text-green-700 ring-green-600/20',
          'cancelled': 'bg-red-50 text-red-700 ring-red-600/20'
        };
        
        return (
          <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusClasses[value] || ''}`}>
            {value.charAt(0).toUpperCase() + value.slice(1).replace('_', ' ')}
          </span>
        );
      }
    },
    { 
      key: 'trainers', 
      label: t('Trainers'),
      render: (value) => {
        if (!value || value.length === 0) {
          return '-';
        }
        
        return (
          <div className="space-y-1">
            {value.slice(0, 2).map((trainer: any) => (
              <div key={trainer.id} className="text-sm">{trainer.name}</div>
            ))}
            {value.length > 2 && (
              <div className="text-xs text-gray-500">+{value.length - 2} more</div>
            )}
          </div>
        );
      }
    },
    { 
      key: 'attendance_count', 
      label: t('Attendance'),
      render: (value) => value || '0'
    }
  ];

  // Define table actions
  const actions = [
    { 
      label: t('View'), 
      icon: 'Eye', 
      action: 'view', 
      className: 'text-blue-500',
      requiredPermission: 'view-training-sessions'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-training-sessions'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-training-sessions'
    }
  ];

  // Prepare training program options for filter
  const trainingProgramOptions = [
    { value: '', label: t('All Programs') },
    ...(trainingPrograms || []).map((program: any) => ({
      value: program.id.toString(),
      label: program.name
    }))
  ];

  // Prepare status options for filter
  const statusOptions = [
    { value: '', label: t('All Statuses') },
    { value: 'scheduled', label: t('Scheduled') },
    { value: 'in_progress', label: t('In Progress') },
    { value: 'completed', label: t('Completed') },
    { value: 'cancelled', label: t('Cancelled') }
  ];

  // Prepare location type options for filter
  const locationTypeOptions = [
    { value: '', label: t('All Locations') },
    { value: 'physical', label: t('Physical') },
    { value: 'virtual', label: t('Virtual') }
  ];

  return (
    <PageTemplate 
      title={t("Training Sessions")} 
      url="/hr/training/sessions"
      actions={pageActions}
      breadcrumbs={breadcrumbs}
      noPadding
    >
      {/* Search and filters section */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
        <SearchAndFilterBar
          searchTerm={searchTerm}
          onSearchChange={setSearchTerm}
          onSearch={handleSearch}
          filters={[
            {
              name: 'training_program_id',
              label: t('Program'),
              type: 'select',
              value: selectedProgram,
              onChange: setSelectedProgram,
              options: trainingProgramOptions
            },
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              value: selectedStatus,
              onChange: setSelectedStatus,
              options: statusOptions
            },
            {
              name: 'location_type',
              label: t('Location Type'),
              type: 'select',
              value: selectedLocationType,
              onChange: setSelectedLocationType,
              options: locationTypeOptions
            },
            {
              name: 'date_from',
              label: t('Date From'),
              type: 'date',
              value: dateFrom,
              onChange: setDateFrom
            },
            {
              name: 'date_to',
              label: t('Date To'),
              type: 'date',
              value: dateTo,
              onChange: setDateTo
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
            router.get(route('hr.training-sessions.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              training_program_id: selectedProgram || undefined,
              status: selectedStatus || undefined,
              location_type: selectedLocationType || undefined,
              date_from: dateFrom || undefined,
              date_to: dateTo || undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      {/* Content section */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={trainingSessions?.data || []}
          from={trainingSessions?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-training-sessions',
            create: 'create-training-sessions',
            edit: 'edit-training-sessions',
            delete: 'delete-training-sessions'
          }}
        />

        {/* Pagination section */}
        <Pagination
          from={trainingSessions?.from || 0}
          to={trainingSessions?.to || 0}
          total={trainingSessions?.total || 0}
          links={trainingSessions?.links}
          entityName={t("training sessions")}
          onPageChange={(url) => router.get(url)}
        />
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
              options: trainingProgramOptions.filter(opt => opt.value !== '')
            },
            { 
              name: 'name', 
              label: t('Session Name'), 
              type: 'text',
              helpText: t('Leave blank to use program name')
            },
            { 
              name: 'start_date', 
              label: t('Start Date'), 
              type: 'date',
              required: true
            },
            { 
              name: 'end_date', 
              label: t('End Date'), 
              type: 'date',
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
              type: 'multi-select',
              options: (employees || []).map((employee: any) => ({
                value: employee.id.toString(),
                label: `${employee.name} (${employee.employee_id})`
              }))
            },
            { 
              name: 'is_recurring', 
              label: t('Recurring Session'), 
              type: 'checkbox',
              showWhen: (formData) => formMode === 'create'
            },
            { 
              name: 'recurrence_pattern', 
              label: t('Recurrence Pattern'), 
              type: 'select',
              options: [
                { value: 'daily', label: t('Daily') },
                { value: 'weekly', label: t('Weekly') },
                { value: 'monthly', label: t('Monthly') }
              ],
              showWhen: (formData) => formData.is_recurring
            },
            { 
              name: 'recurrence_count', 
              label: t('Number of Occurrences'), 
              type: 'number',
              min: 1,
              max: 52,
              showWhen: (formData) => formData.is_recurring
            }
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem ? {
          ...currentItem,
          start_date: currentItem.start_date ? currentItem.start_date.split(' ')[0] : '',
          start_time: currentItem.start_date ? currentItem.start_date.split(' ')[1]?.substring(0, 5) : '',
          end_date: currentItem.end_date ? currentItem.end_date.split(' ')[0] : '',
          end_time: currentItem.end_date ? currentItem.end_date.split(' ')[1]?.substring(0, 5) : '',
          trainer_ids: currentItem.trainers?.map((trainer: any) => trainer.id.toString())
        } : null}
        title={
          formMode === 'create'
            ? t('Add New Training Session')
            : formMode === 'edit'
              ? t('Edit Training Session')
              : t('View Training Session')
        }
        mode={formMode}
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.name || currentItem?.training_program?.name || ''}
        entityName="training session"
      />
    </PageTemplate>
  );
}