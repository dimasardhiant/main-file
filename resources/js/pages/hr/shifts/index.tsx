// pages/hr/shifts/index.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';

export default function Shifts() {
  const { t } = useTranslation();
  const { auth, shifts, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];

  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
  const [selectedShiftType, setSelectedShiftType] = useState(pageFilters.shift_type || 'all');
  const [showFilters, setShowFilters] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');

  // Check if any filters are active
  const hasActiveFilters = () => {
    return searchTerm !== '' || selectedStatus !== 'all' || selectedShiftType !== 'all';
  };

  // Count active filters
  const activeFilterCount = () => {
    return (searchTerm ? 1 : 0) + (selectedStatus !== 'all' ? 1 : 0) + (selectedShiftType !== 'all' ? 1 : 0);
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };

  const applyFilters = () => {
    router.get(route('hr.shifts.index'), {
      page: 1,
      search: searchTerm || undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
      shift_type: selectedShiftType !== 'all' ? selectedShiftType : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

    router.get(route('hr.shifts.index'), {
      sort_field: field,
      sort_direction: direction,
      page: 1,
      search: searchTerm || undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
      shift_type: selectedShiftType !== 'all' ? selectedShiftType : undefined,
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
      case 'toggle-status':
        handleToggleStatus(item);
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
      toast.loading(t('Creating shift...'));

      router.post(route('hr.shifts.store'), formData, {
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
            toast.error(`Failed to create shift: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating shift...'));

      router.put(route('hr.shifts.update', currentItem.id), formData, {
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
            toast.error(`Failed to update shift: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    }
  };

  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting shift...'));

    router.delete(route('hr.shifts.destroy', currentItem.id), {
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
          toast.error(`Failed to delete shift: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };

  const handleToggleStatus = (shift: any) => {
    const newStatus = shift.status === 'active' ? 'inactive' : 'active';
    toast.loading(`${newStatus === 'active' ? t('Activating') : t('Deactivating')} shift...`);

    router.put(route('hr.shifts.toggle-status', shift.id), {}, {
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
          toast.error(errors);
        } else {
          toast.error(`Failed to update shift status: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };

  const handleResetFilters = () => {
    setSearchTerm('');
    setSelectedStatus('all');
    setSelectedShiftType('all');
    setShowFilters(false);

    router.get(route('hr.shifts.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  // Define page actions
  const pageActions = [];

  // Add the "Add New Shift" button if user has permission
  if (hasPermission(permissions, 'create-shifts')) {
    pageActions.push({
      label: t('Add Shift'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Shift Management'), href: route('hr.shifts.index') },
    { title: t('Shifts') }
  ];

  // Define table columns
  const columns = [
    {
      key: 'name',
      label: t('Shift Name'),
      sortable: true
    },
    {
      key: 'start_time',
      label: t('Start Time'),
      render: (value: string) => (
        <span className="font-mono">{window.appSettings.formatTime(value) || '-'}</span>
      )
    },
    {
      key: 'end_time',
      label: t('End Time'),
      render: (value: string) => (
        <span className="font-mono">{window.appSettings.formatTime(value) || '-'}</span>
      )
    },
    {
      key: 'break_duration',
      label: t('Break (mins)'),
      render: (value: number) => (
        <span className="font-mono">{value}</span>
      )
    },
    {
      key: 'working_hours',
      label: t('Working Hours'),
      render: (value: any, row: any) => {
        // Calculate working hours from start_time and end_time
        if (row.start_time && row.end_time) {
          const start = new Date(`2000-01-01 ${row.start_time}`);
          let end = new Date(`2000-01-01 ${row.end_time}`);

          // Handle next day for night shifts
          if (end <= start) {
            end.setDate(end.getDate() + 1);
          }

          const totalMinutes = (end.getTime() - start.getTime()) / (1000 * 60);
          const workingMinutes = totalMinutes - (row.break_duration || 0);
          const workingHours = Math.max(0, workingMinutes / 60);

          return (
            <span className="font-mono text-green-600">{workingHours.toFixed(1)}h</span>
          );
        }
        return <span className="text-gray-400">-</span>;
      }
    },
    {
      key: 'grace_period',
      label: t('Grace (mins)'),
      render: (value: number) => (
        <span className="font-mono text-blue-600">{value}</span>
      )
    },
    {
      key: 'is_night_shift',
      label: t('Type'),
      render: (value: boolean) => (
        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${value
          ? 'bg-purple-50 text-purple-700 ring-1 ring-inset ring-purple-600/20'
          : 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20'
          }`}>
          {value ? t('Night') : t('Day')}
        </span>
      )
    },
    {
      key: 'status',
      label: t('Status'),
      render: (value: string) => {
        return (
          <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${value === 'active'
            ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
            : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
            }`}>
            {value === 'active' ? t('Active') : t('Inactive')}
          </span>
        );
      }
    }
  ];

  // Define table actions
  const actions = [
    {
      label: t('View'),
      icon: 'Eye',
      action: 'view',
      className: 'text-blue-500',
      requiredPermission: 'view-shifts'
    },
    {
      label: t('Edit'),
      icon: 'Edit',
      action: 'edit',
      className: 'text-amber-500',
      requiredPermission: 'edit-shifts'
    },
    {
      label: t('Toggle Status'),
      icon: 'Lock',
      action: 'toggle-status',
      className: 'text-amber-500',
      requiredPermission: 'edit-shifts'
    },
    {
      label: t('Delete'),
      icon: 'Trash2',
      action: 'delete',
      className: 'text-red-500',
      requiredPermission: 'delete-shifts'
    }
  ];

  // Prepare options for filters
  const statusOptions = [
    { value: 'all', label: t('All Statuses') , disabled : true },
    { value: 'active', label: t('Active') },
    { value: 'inactive', label: t('Inactive') }
  ];

  const shiftTypeOptions = [
    { value: 'all', label: t('All Types') , disabled : true},
    { value: 'day', label: t('Day Shift') },
    { value: 'night', label: t('Night Shift') }
  ];

  const workingDayOptions = [
    { value: 'monday', label: t('Monday') },
    { value: 'tuesday', label: t('Tuesday') },
    { value: 'wednesday', label: t('Wednesday') },
    { value: 'thursday', label: t('Thursday') },
    { value: 'friday', label: t('Friday') },
    { value: 'saturday', label: t('Saturday') },
    { value: 'sunday', label: t('Sunday') }
  ];

  return (
    <PageTemplate
      title={t("Shifts")}
      url="/hr/shifts"
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
              name: 'status',
              label: t('Status'),
              type: 'select',
              value: selectedStatus,
              onChange: setSelectedStatus,
              options: statusOptions,
            },
            {
              name: 'shift_type',
              label: t('Shift Type'),
              type: 'select',
              value: selectedShiftType,
              onChange: setSelectedShiftType,
              options: shiftTypeOptions
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
            router.get(route('hr.shifts.index'), {
              page: 1,
              per_page: parseInt(value),
              search: searchTerm || undefined,
              status: selectedStatus !== 'all' ? selectedStatus : undefined,
              shift_type: selectedShiftType !== 'all' ? selectedShiftType : undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      {/* Content section */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={shifts?.data || []}
          from={shifts?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-shifts',
            create: 'create-shifts',
            edit: 'edit-shifts',
            delete: 'delete-shifts'
          }}
        />

        {/* Pagination section */}
        <Pagination
          from={shifts?.from || 0}
          to={shifts?.to || 0}
          total={shifts?.total || 0}
          links={shifts?.links}
          entityName={t("shifts")}
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
            { name: 'name', label: t('Shift Name'), type: 'text', required: true },
            { name: 'description', label: t('Description'), type: 'textarea' },
            { name: 'start_time', label: t('Start Time'), type: 'time', required: true },
            { name: 'end_time', label: t('End Time'), type: 'time', required: true },
            { name: 'break_duration', label: t('Break Duration (minutes)'), type: 'number', required: true, min: 0, defaultValue: 60 },
            { name: 'break_start_time', label: t('Break Start Time'), type: 'time' },
            { name: 'break_end_time', label: t('Break End Time'), type: 'time' },
            { name: 'grace_period', label: t('Grace Period (minutes)'), type: 'number', required: true, min: 0, defaultValue: 15 },
            { name: 'is_night_shift', label: t('Night Shift'), type: 'checkbox', defaultValue: false },
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              options: [
                { value: 'active', label: 'Active' },
                { value: 'inactive', label: 'Inactive' }
              ],
              defaultValue: 'active'
            }
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem}
        title={
          formMode === 'create'
            ? t('Add New Shift')
            : formMode === 'edit'
              ? t('Edit Shift')
              : t('View Shift')
        }
        mode={formMode}
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.name || ''}
        entityName="shift"
      />
    </PageTemplate>
  );
}