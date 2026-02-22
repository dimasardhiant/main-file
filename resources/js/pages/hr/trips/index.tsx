// pages/hr/trips/index.tsx
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
import { Plus, FileText } from 'lucide-react';
import { format } from 'date-fns';
import MediaPicker from '@/components/MediaPicker';

export default function Trips() {
  const { t } = useTranslation();
  const { auth, trips, employees, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];

  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedEmployee, setSelectedEmployee] = useState(pageFilters.employee_id || '');
  const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
  const [dateFrom, setDateFrom] = useState(pageFilters.date_from || '');
  const [dateTo, setDateTo] = useState(pageFilters.date_to || '');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isStatusModalOpen, setIsStatusModalOpen] = useState(false);
  const [isAdvanceStatusModalOpen, setIsAdvanceStatusModalOpen] = useState(false);
  const [isReimbursementStatusModalOpen, setIsReimbursementStatusModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');

  // Check if any filters are active
  const hasActiveFilters = () => {
    return selectedEmployee !== '' ||
      selectedStatus !== 'all' ||
      dateFrom !== '' ||
      dateTo !== '' ||
      searchTerm !== '';
  };

  // Count active filters
  const activeFilterCount = () => {
    return (selectedEmployee !== '' ? 1 : 0) +
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
    router.get(route('hr.trips.index'), {
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee || undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

    router.get(route('hr.trips.index'), {
      sort_field: field,
      sort_direction: direction,
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee || undefined,
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
      case 'advance-status':
        setIsAdvanceStatusModalOpen(true);
        break;
      case 'reimbursement-status':
        setIsReimbursementStatusModalOpen(true);
        break;
      case 'download-document':
        window.open(route('hr.trips.download-document', item.id), '_blank');
        break;
      case 'view-expenses':
        router.get(route('hr.trips.expenses', item.id));
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
      toast.loading(t('Creating trip...'));

      router.post(route('hr.trips.store'), data, {
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
            toast.error(t('Failed to create trip: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating trip...'));

      router.put(route('hr.trips.update', currentItem.id), data, {
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
            toast.error(t('Failed to update trip: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    }
  };

  const handleStatusChange = (formData: any) => {
    toast.loading(t('Updating trip status...'));

    router.put(route('hr.trips.change-status', currentItem.id), formData, {
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
          toast.error(t('Failed to update trip status: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleAdvanceStatusChange = (formData: any) => {
    toast.loading(t('Updating advance status...'));

    router.put(route('hr.trips.update-advance-status', currentItem.id), formData, {
      onSuccess: (page) => {
        setIsAdvanceStatusModalOpen(false);
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
          toast.error(t('Failed to update advance status: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleReimbursementStatusChange = (formData: any) => {
    toast.loading(t('Updating reimbursement status...'));

    router.put(route('hr.trips.update-reimbursement-status', currentItem.id), formData, {
      onSuccess: (page) => {
        setIsReimbursementStatusModalOpen(false);
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
          toast.error(t('Failed to update reimbursement status: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting trip...'));

    router.delete(route('hr.trips.destroy', currentItem.id), {
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
          toast.error(t('Failed to delete trip: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleResetFilters = () => {
    setSearchTerm('');
    setSelectedEmployee('');
    setSelectedStatus('all');
    setDateFrom('');
    setDateTo('');
    setShowFilters(false);

    router.get(route('hr.trips.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  // Define page actions
  const pageActions = [];

  // Add the "Add New Trip" button if user has permission
  if (hasPermission(permissions, 'create-trips')) {
    pageActions.push({
      label: t('Add Trip'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.trips.index') },
    { title: t('Trips') }
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
      key: 'purpose',
      label: t('Purpose'),
      render: (value) => value || '-'
    },
    {
      key: 'destination',
      label: t('Destination'),
      render: (value) => value || '-'
    },
    {
      key: 'start_date',
      label: t('Start Date'),
      sortable: true,
      render: (value) => value ? (window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleString()) : '-'
    },
    {
      key: 'end_date',
      label: t('End Date'),
      sortable: true,
      render: (value) => value ? (window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleString()) : '-'
    },
    {
      key: 'status',
      label: t('Status'),
      render: (value) => {
        const statusClasses = {
          'planned': 'bg-blue-50 text-blue-700 ring-blue-600/20',
          'ongoing': 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
          'completed': 'bg-green-50 text-green-700 ring-green-600/20',
          'cancelled': 'bg-red-50 text-red-700 ring-red-600/20'
        };

        return (
          <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusClasses[value] || ''}`}>
            {value.charAt(0).toUpperCase() + value.slice(1)}
          </span>
        );
      }
    },
    {
      key: 'advance_amount',
      label: t('Advance'),
      render: (value, row) => {
        if (!value || parseFloat(value) === 0) return '-';

        const advanceStatusClasses = {
          'requested': 'bg-blue-50 text-blue-700 ring-blue-600/20',
          'approved': 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
          'paid': 'bg-green-50 text-green-700 ring-green-600/20',
          'reconciled': 'bg-purple-50 text-purple-700 ring-purple-600/20'
        };

        return (
          <div>
            <div> {window.appSettings.formatCurrency(value)}</div>
            {row.advance_status && (
              <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${advanceStatusClasses[row.advance_status] || ''}`}>
                {row.advance_status.charAt(0).toUpperCase() + row.advance_status.slice(1)}
              </span>
            )}
          </div>
        );
      }
    },
    {
      key: 'total_expenses',
      label: t('Expenses'),
      render: (value, row) => {
        if (!value || parseFloat(value) === 0) return '-';

        const reimbursementStatusClasses = {
          'pending': 'bg-blue-50 text-blue-700 ring-blue-600/20',
          'approved': 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
          'paid': 'bg-green-50 text-green-700 ring-green-600/20'
        };

        return (
          <div>
            <div>{window.appSettings.formatCurrency(value)}</div>
            {row.reimbursement_status && (
              <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${reimbursementStatusClasses[row.reimbursement_status] || ''}`}>
                {row.reimbursement_status.charAt(0).toUpperCase() + row.reimbursement_status.slice(1)}
              </span>
            )}
          </div>
        );
      }
    },
    {
      key: 'actions',
      label: t('Actions'),
      render: (_, row) => (
        <div className="flex space-x-2">
          <Button
            variant="outline"
            size="sm"
            className="flex items-center text-blue-500"
            onClick={(e) => {
              e.stopPropagation();
              handleAction('view-expenses', row);
            }}
          >
            <FileText className="h-4 w-4 mr-1" />
            {t('Expenses')}
          </Button>
          {row.documents && row.documents.trim() !== '' && (
            <Button
              variant="outline"
              size="sm"
              className="flex items-center text-green-500"
              onClick={(e) => {
                e.stopPropagation();
                handleAction('download-document', row);
              }}
            >
              {t('Documents')}
            </Button>
          )}
        </div>
      )
    }
  ];

  // Define table actions
  const actions = [
    {
      label: t('View'),
      icon: 'Eye',
      action: 'view',
      className: 'text-blue-500',
      requiredPermission: 'view-trips'
    },
    {
      label: t('Edit'),
      icon: 'Edit',
      action: 'edit',
      className: 'text-amber-500',
      requiredPermission: 'edit-trips'
    },
    {
      label: t('Change Status'),
      icon: 'RefreshCw',
      action: 'change-status',
      className: 'text-green-500',
      requiredPermission: 'edit-trips'
    },
    {
      label: t('Advance Status'),
      icon: 'DollarSign',
      action: 'advance-status',
      className: 'text-purple-500',
      requiredPermission: 'edit-trips',
      showWhen: (item) => item.advance_amount > 0
    },
    {
      label: t('Reimbursement Status'),
      icon: 'CreditCard',
      action: 'reimbursement-status',
      className: 'text-indigo-500',
      requiredPermission: 'edit-trips',
      showWhen: (item) => item.total_expenses > 0
    },
    {
      label: t('Delete'),
      icon: 'Trash2',
      action: 'delete',
      className: 'text-red-500',
      requiredPermission: 'delete-trips'
    }
  ];

  // Prepare employee options for filter
  const employeeOptions = [
    { value: '', label: t('All Employees'), disabled: true },
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: `${emp.name} (${emp.employee_id})`
    }))
  ];

  // Prepare status options for filter
  const statusOptions = [
    { value: 'all', label: t('All Statuses') },
    { value: 'planned', label: t('Planned') },
    { value: 'ongoing', label: t('Ongoing') },
    { value: 'completed', label: t('Completed') },
    { value: 'cancelled', label: t('Cancelled') }
  ];

  return (
    <PageTemplate
      title={t("Trips")}
      url="/hr/trips"
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
              name: 'employee_id',
              label: t('Employee'),
              type: 'select',
              value: selectedEmployee,
              onChange: setSelectedEmployee,
              options: employeeOptions,
              searchable: true,
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
              label: t('Start Date From'),
              type: 'date',
              value: dateFrom,
              onChange: setDateFrom
            },
            {
              name: 'date_to',
              label: t('End Date To'),
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
            router.get(route('hr.trips.index'), {
              page: 1,
              per_page: parseInt(value),
              search: searchTerm || undefined,
              employee_id: selectedEmployee || undefined,
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
          data={trips?.data || []}
          from={trips?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-trips',
            create: 'create-trips',
            edit: 'edit-trips',
            delete: 'delete-trips'
          }}
        />

        {/* Pagination section */}
        <Pagination
          from={trips?.from || 0}
          to={trips?.to || 0}
          total={trips?.total || 0}
          links={trips?.links}
          entityName={t("trips")}
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
              name: 'purpose',
              label: t('Purpose'),
              type: 'text',
              required: true
            },
            {
              name: 'destination',
              label: t('Destination'),
              type: 'text',
              required: true
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
              name: 'description',
              label: t('Description'),
              type: 'textarea'
            },
            {
              name: 'expected_outcomes',
              label: t('Expected Outcomes'),
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
            {
              name: 'advance_amount',
              label: t('Advance Amount'),
              type: 'number',
              min: 0,
              step: 0.01
            },
            ...(formMode === 'edit' ? [
              {
                name: 'status',
                label: t('Status'),
                type: 'select',
                options: [
                  { value: 'planned', label: t('Planned') },
                  { value: 'ongoing', label: t('Ongoing') },
                  { value: 'completed', label: t('Completed') },
                  { value: 'cancelled', label: t('Cancelled') }
                ]
              },
              {
                name: 'advance_status',
                label: t('Advance Status'),
                type: 'select',
                options: [
                  { value: 'requested', label: t('Requested') },
                  { value: 'approved', label: t('Approved') },
                  { value: 'paid', label: t('Paid') },
                  { value: 'reconciled', label: t('Reconciled') }
                ],
                showWhen: (formData) => formData.advance_amount > 0
              },
              {
                name: 'reimbursement_status',
                label: t('Reimbursement Status'),
                type: 'select',
                options: [
                  { value: 'pending', label: t('Pending') },
                  { value: 'approved', label: t('Approved') },
                  { value: 'paid', label: t('Paid') }
                ],
                showWhen: (formData) => formData.total_expenses > 0
              },
              {
                name: 'trip_report',
                label: t('Trip Report'),
                type: 'textarea',
                showWhen: (formData) => formData.status === 'completed'
              }
            ] : [])
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem}
        title={
          formMode === 'create'
            ? t('Add New Trip')
            : formMode === 'edit'
              ? t('Edit Trip')
              : t('View Trip')
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
                { value: 'ongoing', label: t('Ongoing') },
                { value: 'completed', label: t('Completed') },
                { value: 'cancelled', label: t('Cancelled') }
              ],
              defaultValue: currentItem?.status
            }
          ],
          modalSize: 'sm'
        }}
        initialData={currentItem}
        title={t('Change Trip Status')}
        mode="edit"
      />

      {/* Advance Status Modal */}
      <CrudFormModal
        isOpen={isAdvanceStatusModalOpen}
        onClose={() => setIsAdvanceStatusModalOpen(false)}
        onSubmit={handleAdvanceStatusChange}
        formConfig={{
          fields: [
            {
              name: 'advance_status',
              label: t('Advance Status'),
              type: 'select',
              required: true,
              options: [
                { value: 'requested', label: t('Requested') },
                { value: 'approved', label: t('Approved') },
                { value: 'paid', label: t('Paid') },
                { value: 'reconciled', label: t('Reconciled') }
              ],
              defaultValue: currentItem?.advance_status
            }
          ],
          modalSize: 'sm'
        }}
        initialData={currentItem}
        title={t('Change Advance Status')}
        mode="edit"
      />

      {/* Reimbursement Status Modal */}
      <CrudFormModal
        isOpen={isReimbursementStatusModalOpen}
        onClose={() => setIsReimbursementStatusModalOpen(false)}
        onSubmit={handleReimbursementStatusChange}
        formConfig={{
          fields: [
            {
              name: 'reimbursement_status',
              label: t('Reimbursement Status'),
              type: 'select',
              required: true,
              options: [
                { value: 'pending', label: t('Pending') },
                { value: 'approved', label: t('Approved') },
                { value: 'paid', label: t('Paid') }
              ],
              defaultValue: currentItem?.reimbursement_status
            }
          ],
          modalSize: 'sm'
        }}
        initialData={currentItem}
        title={t('Change Reimbursement Status')}
        mode="edit"
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={`${currentItem?.employee?.name || ''} - ${currentItem?.purpose || ''}`}
        entityName="trip"
      />
    </PageTemplate>
  );
}