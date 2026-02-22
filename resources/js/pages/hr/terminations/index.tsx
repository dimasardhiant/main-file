// pages/hr/terminations/index.tsx
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
import { Plus } from 'lucide-react';
import { format } from 'date-fns';
import MediaPicker from '@/components/MediaPicker';

export default function Terminations() {
  const { t } = useTranslation();
  const { auth, terminations, employees, terminationTypes, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];

  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedEmployee, setSelectedEmployee] = useState(pageFilters.employee_id || '');
  const [selectedTerminationType, setSelectedTerminationType] = useState(pageFilters.termination_type || '');
  const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
  const [dateFrom, setDateFrom] = useState(pageFilters.date_from || '');
  const [dateTo, setDateTo] = useState(pageFilters.date_to || '');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isStatusModalOpen, setIsStatusModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');

  // Check if any filters are active
  const hasActiveFilters = () => {
    return selectedEmployee !== '' ||
      selectedTerminationType !== '' ||
      selectedStatus !== 'all' ||
      dateFrom !== '' ||
      dateTo !== '' ||
      searchTerm !== '';
  };

  // Count active filters
  const activeFilterCount = () => {
    return (selectedEmployee !== '' ? 1 : 0) +
      (selectedTerminationType !== '' ? 1 : 0) +
      (selectedStatus !== 'all' ? 1 : 0) +
      (dateFrom !== '' ? 1 : 0) +
      (dateTo !== '' ? 1 : 0) +
      (searchTerm !== '' ? 1 : 0);
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };

  const applyFilters = () => {
    router.get(route('hr.terminations.index'), {
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee || undefined,
      termination_type: selectedTerminationType || undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

    router.get(route('hr.terminations.index'), {
      sort_field: field,
      sort_direction: direction,
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee || undefined,
      termination_type: selectedTerminationType || undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
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
      case 'change-status':
        setIsStatusModalOpen(true);
        break;
      case 'download-document':
        window.open(route('hr.terminations.download-document', item.id), '_blank');
        break;
    }
  };

  const handleAddNew = () => {
    setCurrentItem(null);
    setFormMode('create');
    setIsFormModalOpen(true);
  };

  const handleFormSubmit = (formData: any) => {
    const data = formData;

    if (formMode === 'create') {
      toast.loading(t('Creating termination...'));

      router.post(route('hr.terminations.store'), data, {
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
            toast.error(t('Failed to create termination: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating termination...'));

      router.put(route('hr.terminations.update', currentItem.id), data, {
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
            toast.error(t('Failed to update termination: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    }
  };

  const handleStatusChange = (formData: any) => {
    toast.loading(t('Updating termination status...'));

    router.put(route('hr.terminations.change-status', currentItem.id), formData, {
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
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to update termination status: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting termination...'));

    router.delete(route('hr.terminations.destroy', currentItem.id), {
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
          toast.error(t('Failed to delete termination: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleResetFilters = () => {
    setSearchTerm('');
    setSelectedEmployee('');
    setSelectedTerminationType('');
    setSelectedStatus('all');
    setDateFrom('');
    setDateTo('');
    setShowFilters(false);

    router.get(route('hr.terminations.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  // Define page actions
  const pageActions = [];

  // Add the "Add New Termination" button if user has permission
  if (hasPermission(permissions, 'create-terminations')) {
    pageActions.push({
      label: t('Add Termination'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.terminations.index') },
    { title: t('Terminations') }
  ];

  // Define table columns
  const columns = [
    {
      key: 'employee.name',
      label: t('Employee'),
      render: (_, row) => (
        <div>
          <div className="font-medium">{row.employee?.name || '-'}</div>
          <div className="text-xs text-gray-500">{row.employee?.employee_id || ''}</div>
        </div>
      )
    },
    {
      key: 'termination_type',
      label: t('Type'),
      render: (value) => value || '-'
    },
    {
      key: 'termination_date',
      label: t('Termination Date'),
      sortable: true,
      render: (value) => value ? (window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleString()) : '-'
    },
    {
      key: 'notice_date',
      label: t('Notice Date'),
      sortable: true,
      render: (value) => value ? (window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleString()) : '-'
    },
    {
      key: 'reason',
      label: t('Reason'),
      render: (value) => value || '-'
    },
    {
      key: 'status',
      label: t('Status'),
      render: (value) => {
        const statusClasses = {
          'planned': 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
          'in progress': 'bg-blue-50 text-blue-700 ring-blue-600/20',
          'completed': 'bg-green-50 text-green-700 ring-green-600/20'
        };

        return (
          <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusClasses[value] || ''}`}>
            {value.charAt(0).toUpperCase() + value.slice(1)}
          </span>
        );
      }
    },
    {
      key: 'documents',
      label: t('Documents'),
      render: (value, row) => value && value.trim() !== '' ? (
        <span
          className="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20 cursor-pointer"
          onClick={(e) => {
            e.stopPropagation();
            handleAction('download-document', row);
          }}
        >
          {t('View Document')}
        </span>
      ) : '-'
    }
  ];

  // Define table actions
  const actions = [
    {
      label: t('View'),
      icon: 'Eye',
      action: 'view',
      className: 'text-blue-500',
      requiredPermission: 'view-terminations'
    },
    {
      label: t('Edit'),
      icon: 'Edit',
      action: 'edit',
      className: 'text-amber-500',
      requiredPermission: 'edit-terminations'
    },
    {
      label: t('Change Status'),
      icon: 'RefreshCw',
      action: 'change-status',
      className: 'text-green-500',
      requiredPermission: 'edit-terminations'
    },
    {
      label: t('Delete'),
      icon: 'Trash2',
      action: 'delete',
      className: 'text-red-500',
      requiredPermission: 'delete-terminations'
    }
  ];

  // Prepare employee options for filter
  const employeeOptions = [
    { value: '', label: t('All Employees') },
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: `${emp.name} (${emp.employee_id})`
    }))
  ];

  // Prepare termination type options for filter
  const terminationTypeOptions = [
    { value: '', label: t('All Types') },
    ...(terminationTypes || []).map((type: string) => ({
      value: type,
      label: type
    }))
  ];

  // Prepare status options for filter
  const statusOptions = [
    { value: 'all', label: t('All Statuses') },
    { value: 'planned', label: t('Planned') },
    { value: 'in progress', label: t('In Progress') },
    { value: 'completed', label: t('Completed') }
  ];

  // Prepare termination type options for form
  const terminationTypeFormOptions = [
    { value: 'Voluntary', label: t('Voluntary') },
    { value: 'Involuntary', label: t('Involuntary') },
    { value: 'Layoff', label: t('Layoff') },
    { value: 'Retirement', label: t('Retirement') },
    { value: 'Contract Completion', label: t('Contract Completion') },
    { value: 'Probation Failure', label: t('Probation Failure') },
    { value: 'Misconduct', label: t('Misconduct') },
    { value: 'Performance Issues', label: t('Performance Issues') }
  ];

  return (
    <PageTemplate
      title={t("Terminations")}
      url="/hr/terminations"
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
            ...(hasPermission(permissions, 'manage-any-terminations') ? [{
              name: 'employee_id',
              label: t('Employee'),
              type: 'select',
              value: selectedEmployee,
              onChange: setSelectedEmployee,
              options: employeeOptions,
              searchable: true
            }] : []),
            {
              name: 'termination_type',
              label: t('Type'),
              type: 'select',
              value: selectedTerminationType,
              onChange: setSelectedTerminationType,
              options: terminationTypeOptions
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
            router.get(route('hr.terminations.index'), {
              page: 1,
              per_page: parseInt(value),
              search: searchTerm || undefined,
              employee_id: selectedEmployee || undefined,
              termination_type: selectedTerminationType || undefined,
              status: selectedStatus !== 'all' ? selectedStatus : undefined,
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
          data={terminations?.data || []}
          from={terminations?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-terminations',
            create: 'create-terminations',
            edit: 'edit-terminations',
            delete: 'delete-terminations'
          }}
        />

        {/* Pagination section */}
        <Pagination
          from={terminations?.from || 0}
          to={terminations?.to || 0}
          total={terminations?.total || 0}
          links={terminations?.links}
          entityName={t("terminations")}
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
              name: 'employee_id',
              label: t('Employee'),
              type: 'select',
              required: true,
              options: employeeOptions.filter(opt => opt.value !== ''),
              searchable: true
            },
            {
              name: 'termination_type',
              label: t('Termination Type'),
              type: 'select',
              required: true,
              options: terminationTypeFormOptions,
              searchable: true
            },
            {
              name: 'notice_date',
              label: t('Notice Date'),
              type: 'date',
              required: true
            },
            {
              name: 'termination_date',
              label: t('Termination Date'),
              type: 'date',
              required: true
            },
            {
              name: 'notice_period',
              label: t('Notice Period'),
              type: 'text',
              placeholder: 'e.g. 1 month, 2 weeks'
            },
            {
              name: 'reason',
              label: t('Reason'),
              type: 'text'
            },
            {
              name: 'description',
              label: t('Description'),
              type: 'textarea'
            },
            {
              name: 'documents',
              label: t('Documents'),
              type: 'custom',
              render: (field, formData, handleChange) => (
                <MediaPicker
                  value={String(formData[field.name] || '')}
                  onChange={(url) => handleChange(field.name, url)}
                  placeholder={t('Select document file...')}
                />
              )
            },
            ...(formMode === 'edit' ? [
              {
                name: 'status',
                label: t('Status'),
                type: 'select',
                options: [
                  { value: 'planned', label: t('Planned') },
                  { value: 'in progress', label: t('In Progress') },
                  { value: 'completed', label: t('Completed') }
                ]
              },
              {
                name: 'exit_interview_conducted',
                label: t('Exit Interview Conducted'),
                type: 'checkbox'
              },
              {
                name: 'exit_interview_date',
                label: t('Exit Interview Date'),
                type: 'date',
                showWhen: (formData) => formData.exit_interview_conducted
              },
              {
                name: 'exit_feedback',
                label: t('Exit Feedback'),
                type: 'textarea',
                showWhen: (formData) => formData.status === 'completed'
              }
            ] : [])
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem ? {
          ...currentItem,
          notice_date: currentItem.notice_date ? window.appSettings.formatDateTimeSimple(currentItem.notice_date, false) : currentItem.notice_date,
          termination_date: currentItem.termination_date ? window.appSettings.formatDateTimeSimple(currentItem.termination_date, false) : currentItem.termination_date
        } : null}
        title={
          formMode === 'create'
            ? t('Add New Termination')
            : formMode === 'edit'
              ? t('Edit Termination')
              : t('View Termination')
        }
        mode={formMode}
      />

      {/* Status Change Modal */}
      <CrudFormModal
        isOpen={isStatusModalOpen}
        onClose={() => setIsStatusModalOpen(false)}
        onSubmit={handleStatusChange}
        formConfig={{
          fields: [
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              required: true,
              options: [
                { value: 'planned', label: t('Planned') },
                { value: 'in progress', label: t('In Progress') },
                { value: 'completed', label: t('Completed') }
              ],
              defaultValue: currentItem?.status
            },
            {
              name: 'exit_interview_conducted',
              label: t('Exit Interview Conducted'),
              type: 'checkbox',
              showWhen: (formData) => formData.status === 'completed'
            },
            {
              name: 'exit_interview_date',
              label: t('Exit Interview Date'),
              type: 'date',
              showWhen: (formData) => formData.status === 'completed' && formData.exit_interview_conducted
            },
            {
              name: 'exit_feedback',
              label: t('Exit Feedback'),
              type: 'textarea',
              showWhen: (formData) => formData.status === 'completed'
            }
          ],
          modalSize: 'md'
        }}
        initialData={currentItem}
        title={t('Change Termination Status')}
        mode="edit"
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={`${currentItem?.employee?.name || ''}`}
        entityName="termination"
      />
    </PageTemplate>
  );
}