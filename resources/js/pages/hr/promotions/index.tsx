// pages/hr/promotions/index.tsx
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
import { Badge } from '@/components/ui/badge';
import MediaPicker from '@/components/MediaPicker';

export default function Promotions() {
  const { t } = useTranslation();
  const { auth, promotions, employees, designations, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];

  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedEmployee, setSelectedEmployee] = useState(pageFilters.employee_id || '');
  const [selectedDesignation, setSelectedDesignation] = useState(pageFilters.designation_id || '');
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
    return selectedEmployee !== '' || selectedDesignation !== '' || selectedStatus !== 'all' || dateFrom !== '' || dateTo !== '' || searchTerm !== '';
  };

  // Count active filters
  const activeFilterCount = () => {
    return (selectedEmployee !== '' ? 1 : 0) +
      (selectedDesignation !== '' ? 1 : 0) +
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
    router.get(route('hr.promotions.index'), {
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee || undefined,
      designation_id: selectedDesignation || undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

    router.get(route('hr.promotions.index'), {
      sort_field: field,
      sort_direction: direction,
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee || undefined,
      designation_id: selectedDesignation || undefined,
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
      case 'update-status':
        setIsStatusModalOpen(true);
        break;
      case 'download-document':
        window.open(route('hr.promotions.download-document', item.id), '_blank');
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
      toast.loading(t('Creating promotion...'));

      router.post(route('hr.promotions.store'), data, {
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
            toast.error(t('Failed to create promotion: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating promotion...'));

      router.put(route('hr.promotions.update', currentItem.id), data, {
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
            toast.error(t('Failed to update promotion: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    }
  };

  const handleStatusUpdate = (status: string) => {
    toast.loading(t('Updating promotion status...'));

    router.put(route('hr.promotions.update-status', currentItem.id), { status }, {
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
          toast.error(t('Failed to update promotion status: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting promotion...'));

    router.delete(route('hr.promotions.destroy', currentItem.id), {
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
          toast.error(t('Failed to delete promotion: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleResetFilters = () => {
    setSearchTerm('');
    setSelectedEmployee('');
    setSelectedDesignation('');
    setSelectedStatus('all');
    setDateFrom('');
    setDateTo('');
    setShowFilters(false);

    router.get(route('hr.promotions.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  // Define page actions
  const pageActions = [];

  // Add the "Add New Promotion" button if user has permission
  if (hasPermission(permissions, 'create-promotions')) {
    pageActions.push({
      label: t('Add Promotion'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.promotions.index') },
    { title: t('Promotions') }
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
      key: 'previous_designation',
      label: t('Previous Designation'),
      render: (value) => value || '-'
    },
    {
      key: 'designation.name',
      label: t('New Designation'),
      render: (_, row) => row.designation?.name || '-'
    },
    {
      key: 'promotion_date',
      label: t('Promotion Date'),
      sortable: true,
      render: (value) => window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleDateString()
    },
    {
      key: 'effective_date',
      label: t('Effective Date'),
      sortable: true,
      render: (value) => window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleDateString()
    },
    {
      key: 'salary_adjustment',
      label: t('Salary Adjustment'),
      render: (value) => value ? window.appSettings.formatCurrency(value) : '-'
    },
    {
      key: 'status',
      label: t('Status'),
      render: (value) => {
        let badgeClass = '';
        switch (value) {
          case 'pending':
            badgeClass = 'bg-yellow-100 text-yellow-800 border-yellow-200';
            break;
          case 'approved':
            badgeClass = 'bg-green-100 text-green-800 border-green-200';
            break;
          case 'rejected':
            badgeClass = 'bg-red-100 text-red-800 border-red-200';
            break;
          default:
            badgeClass = 'bg-gray-100 text-gray-800 border-gray-200';
        }

        return (
          <Badge className={`${badgeClass} capitalize`}>
            {value}
          </Badge>
        );
      }
    },
    {
      key: 'document',
      label: t('Document'),
      render: (value, row) => (
        <div>
          {value && value.trim() !== '' && (
            <span
              className="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20 cursor-pointer"
              onClick={() => handleAction('download-document', row)}
            >
              {t('Document')}
            </span>
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
      requiredPermission: 'view-promotions'
    },
    {
      label: t('Edit'),
      icon: 'Edit',
      action: 'edit',
      className: 'text-amber-500',
      requiredPermission: 'edit-promotions'
    },
    {
      label: t('Update Status'),
      icon: 'RefreshCw',
      action: 'update-status',
      className: 'text-green-500',
      requiredPermission: 'edit-promotions'
    },
    {
      label: t('Delete'),
      icon: 'Trash2',
      action: 'delete',
      className: 'text-red-500',
      requiredPermission: 'delete-promotions'
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

  // Prepare designation options for filter
  const designationOptions = [
    { value: '', label: t('All Designations') },
    ...(designations || []).map((des: any) => ({
      value: des.id.toString(),
      label: `${des.name} - ${des.department?.name || ''} (${des.department?.branch?.name || ''})`
    }))
  ];

  // Prepare status options for filter
  const statusOptions = [
    { value: 'all', label: t('All Statuses') },
    { value: 'pending', label: t('Pending') },
    { value: 'approved', label: t('Approved') },
    { value: 'rejected', label: t('Rejected') }
  ];

  return (
    <PageTemplate
      title={t("Promotions")}
      url="/hr/promotions"
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
            ...(hasPermission(permissions, 'manage-any-promotions') ? [{
              name: 'employee_id',
              label: t('Employee'),
              type: 'select',
              value: selectedEmployee,
              onChange: setSelectedEmployee,
              options: employeeOptions,
              searchable: true,
            }] : []),
            {
              name: 'designation_id',
              label: t('Designation'),
              type: 'select',
              value: selectedDesignation,
              onChange: setSelectedDesignation,
              options: designationOptions,
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
            router.get(route('hr.promotions.index'), {
              page: 1,
              per_page: parseInt(value),
              search: searchTerm || undefined,
              employee_id: selectedEmployee || undefined,
              designation_id: selectedDesignation || undefined,
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
          data={promotions?.data || []}
          from={promotions?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-promotions',
            create: 'create-promotions',
            edit: 'edit-promotions',
            delete: 'delete-promotions'
          }}
        />

        {/* Pagination section */}
        <Pagination
          from={promotions?.from || 0}
          to={promotions?.to || 0}
          total={promotions?.total || 0}
          links={promotions?.links}
          entityName={t("promotions")}
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
              name: 'previous_designation',
              label: t('Previous Designation'),
              type: 'text',
              required: true
            },
            {
              name: 'designation_id',
              label: t('New Designation'),
              type: 'select',
              required: true,
              searchable: true,
              options: (designations || []).map((des: any) => ({
                value: des.id.toString(),
                label: `${des.name} - ${des.department?.name || ''} (${des.department?.branch?.name || ''})`
              }))
            },
            {
              name: 'promotion_date',
              label: t('Promotion Date'),
              type: 'date',
              required: true
            },
            {
              name: 'effective_date',
              label: t('Effective Date'),
              type: 'date',
              required: true
            },
            {
              name: 'salary_adjustment',
              label: t('Salary Adjustment'),
              type: 'number',
              min: 0,
              step: 0.01
            },
            {
              name: 'reason',
              label: t('Reason for Promotion'),
              type: 'textarea'
            },
            {
              name: 'document',
              label: t('Document'),
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
              name: 'status',
              label: t('Status'),
              type: 'select',
              options: [
                { value: 'pending', label: t('Pending') },
                { value: 'approved', label: t('Approved') },
                { value: 'rejected', label: t('Rejected') }
              ],
              defaultValue: 'pending'
            }
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem}
        title={
          formMode === 'create'
            ? t('Add New Promotion')
            : formMode === 'edit'
              ? t('Edit Promotion')
              : t('View Promotion')
        }
        mode={formMode}
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={`${currentItem?.employee?.name || ''} - ${currentItem?.designation?.name || ''}`}
        entityName="promotion"
      />

      {/* Status Update Modal */}
      <CrudFormModal
        isOpen={isStatusModalOpen}
        onClose={() => setIsStatusModalOpen(false)}
        onSubmit={(data) => handleStatusUpdate(data.status)}
        formConfig={{
          fields: [
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              required: true,
              options: [
                { value: 'pending', label: t('Pending') },
                { value: 'approved', label: t('Approved') },
                { value: 'rejected', label: t('Rejected') }
              ],
              defaultValue: currentItem?.status || 'pending'
            }
          ],
          modalSize: 'sm'
        }}
        initialData={currentItem ? { status: currentItem.status } : { status: 'pending' }}
        title={t('Update Promotion Status')}
        mode="edit"
      />
    </PageTemplate>
  );
}