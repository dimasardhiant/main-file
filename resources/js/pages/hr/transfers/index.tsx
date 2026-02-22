// pages/hr/transfers/index.tsx
import { useState, useEffect } from 'react';
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

export default function Transfers() {
  const { t } = useTranslation();
  const { auth, transfers, employees, branches, departments, designations, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];

  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedEmployee, setSelectedEmployee] = useState(pageFilters.employee_id || '');
  const [selectedBranch, setSelectedBranch] = useState(pageFilters.branch_id || '');
  const [selectedDepartment, setSelectedDepartment] = useState(pageFilters.department_id || '');
  const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
  const [dateFrom, setDateFrom] = useState(pageFilters.date_from || '');
  const [dateTo, setDateTo] = useState(pageFilters.date_to || '');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isApproveModalOpen, setIsApproveModalOpen] = useState(false);
  const [isRejectModalOpen, setIsRejectModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');

  // State for cascading form dropdowns
  const [selectedFormBranch, setSelectedFormBranch] = useState<string>('_none_');
  const [selectedFormDepartment, setSelectedFormDepartment] = useState<string>('_none_');
  const [filteredFormDepartments, setFilteredFormDepartments] = useState<any[]>([]);
  const [filteredFormDesignations, setFilteredFormDesignations] = useState<any[]>([]);

  // Filter departments based on selected branch for form
  useEffect(() => {
    if (selectedFormBranch === '_none_') {
      setFilteredFormDepartments([]);
    } else {
      const depts = departments.filter((dept: any) => dept.branch_id.toString() === selectedFormBranch);
      setFilteredFormDepartments(depts);
    }
    setSelectedFormDepartment('_none_');
    setFilteredFormDesignations([]);
  }, [selectedFormBranch, departments]);

  // Filter designations based on selected department for form
  useEffect(() => {
    if (selectedFormDepartment === '_none_') {
      setFilteredFormDesignations([]);
    } else {
      const filteredDesignations = (designations || []).filter((desig: any) => {
        return desig.department_id.toString() === selectedFormDepartment;
      });
      setFilteredFormDesignations(filteredDesignations);
    }
  }, [selectedFormDepartment, designations]);

  // Check if any filters are active
  const hasActiveFilters = () => {
    return selectedEmployee !== '' ||
      selectedBranch !== '' ||
      selectedDepartment !== '' ||
      selectedStatus !== 'all' ||
      dateFrom !== '' ||
      dateTo !== '' ||
      searchTerm !== '';
  };

  // Count active filters
  const activeFilterCount = () => {
    return (selectedEmployee !== '' ? 1 : 0) +
      (selectedBranch !== '' ? 1 : 0) +
      (selectedDepartment !== '' ? 1 : 0) +
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
    router.get(route('hr.transfers.index'), {
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee || undefined,
      branch_id: selectedBranch || undefined,
      department_id: selectedDepartment || undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

    router.get(route('hr.transfers.index'), {
      sort_field: field,
      sort_direction: direction,
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee || undefined,
      branch_id: selectedBranch || undefined,
      department_id: selectedDepartment || undefined,
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
      case 'approve':
        setIsApproveModalOpen(true);
        break;
      case 'reject':
        setIsRejectModalOpen(true);
        break;
      case 'download-document':
        window.open(route('hr.transfers.download-document', item.id), '_blank');
        break;
    }
  };

  const handleAddNew = () => {
    setCurrentItem(null);
    setFormMode('create');
    setSelectedFormBranch('_none_');
    setSelectedFormDepartment('_none_');
    setFilteredFormDepartments([]);
    setFilteredFormDesignations([]);
    setIsFormModalOpen(true);
  };

  const handleFormSubmit = (formData: any) => {
    const data = formData;

    if (formMode === 'create') {
      toast.loading(t('Creating transfer...'));

      router.post(route('hr.transfers.store'), data, {
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
            toast.error(t('Failed to create transfer: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating transfer...'));

      router.put(route('hr.transfers.update', currentItem.id), data, {
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
            toast.error(t('Failed to update transfer: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    }
  };

  const handleApprove = (formData: any) => {
    toast.loading(t('Approving transfer...'));

    router.put(route('hr.transfers.approve', currentItem.id), formData, {
      onSuccess: (page) => {
        setIsApproveModalOpen(false);
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
          toast.error(t('Failed to approve transfer: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleReject = (formData: any) => {
    toast.loading(t('Rejecting transfer...'));

    router.put(route('hr.transfers.reject', currentItem.id), formData, {
      onSuccess: (page) => {
        setIsRejectModalOpen(false);
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
          toast.error(t('Failed to reject transfer: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting transfer...'));

    router.delete(route('hr.transfers.destroy', currentItem.id), {
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
          toast.error(t('Failed to delete transfer: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleResetFilters = () => {
    setSearchTerm('');
    setSelectedEmployee('');
    setSelectedBranch('');
    setSelectedDepartment('');
    setSelectedStatus('all');
    setDateFrom('');
    setDateTo('');
    setShowFilters(false);

    router.get(route('hr.transfers.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  // Define page actions
  const pageActions = [];

  // Add the "Add New Transfer" button if user has permission
  if (hasPermission(permissions, 'create-employee-transfers')) {
    pageActions.push({
      label: t('Add Transfer'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.transfers.index') },
    { title: t('Employee Transfers') }
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
      key: 'transfer_type',
      label: t('Transfer Type'),
      render: (_, row) => {
        const types = [];
        if (row.from_branch_id && row.to_branch_id) types.push('Branch');
        if (row.from_department_id && row.to_department_id) types.push('Department');
        if (row.from_designation_id && row.to_designation_id) types.push('Designation');
        return types.join(', ') || '-';
      }
    },
    {
      key: 'from_to',
      label: t('From → To'),
      render: (_, row) => {
        const fromTo = [];

        if (row.to_branch_id) {
          fromTo.push(`${row.from_branch?.name || row.fromBranch?.name || '-'} → ${row.to_branch?.name || row.toBranch?.name || '-'}`);
        }

        if (row.to_department_id) {
          fromTo.push(`${row.from_department?.name || row.fromDepartment?.name || '-'} → ${row.to_department?.name || row.toDepartment?.name || '-'}`);
        }

        if (row.to_designation_id) {
          fromTo.push(`${row.from_designation?.name || row.fromDesignation?.name || '-'} → ${row.to_designation?.name || row.toDesignation?.name || '-'}`);
        }

        return fromTo.length > 0 ? (
          <div className="space-y-1">
            {fromTo.map((item, index) => (
              <div key={index} className="text-sm">{item}</div>
            ))}
          </div>
        ) : '-';
      }
    },
    {
      key: 'transfer_date',
      label: t('Transfer Date'),
      sortable: true,
      render: (value) => value ? (window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleString()) : '-'
    },
    {
      key: 'effective_date',
      label: t('Effective Date'),
      sortable: true,
      render: (value) => value ? (window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleString()) : '-'
    },
    {
      key: 'status',
      label: t('Status'),
      render: (value) => {
        const statusClasses = {
          'pending': 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
          'approved': 'bg-green-50 text-green-700 ring-green-600/20',
          'rejected': 'bg-red-50 text-red-700 ring-red-600/20'
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
        <Button
          variant="outline"
          size="sm"
          className="flex items-center text-blue-500"
          onClick={(e) => {
            e.stopPropagation();
            handleAction('download-document', row);
          }}
        >
          {t('View Document')}
        </Button>
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
      requiredPermission: 'view-employee-transfers'
    },
    {
      label: t('Edit'),
      icon: 'Edit',
      action: 'edit',
      className: 'text-amber-500',
      requiredPermission: 'edit-employee-transfers',
      condition: (item) => item.status === 'pending'
    },
    {
      label: t('Approve'),
      icon: 'CheckCircle',
      action: 'approve',
      className: 'text-green-500',
      requiredPermission: 'approve-employee-transfers',
      condition: (item) => item.status === 'pending'
    },
    {
      label: t('Reject'),
      icon: 'XCircle',
      action: 'reject',
      className: 'text-red-500',
      requiredPermission: 'reject-employee-transfers',
      condition: (item) => item.status === 'pending'
    },
    {
      label: t('Delete'),
      icon: 'Trash2',
      action: 'delete',
      className: 'text-red-500',
      requiredPermission: 'delete-employee-transfers',
      condition: (item) => item.status === 'pending'
    }
  ];

  // Prepare employee options for filter
  const employeeOptions = [
    { value: '_none_', label: t('All Employees'), disabled: true },
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: `${emp.name} (${emp.employee_id})`
    }))
  ];

  // Prepare branch options for filter
  const branchOptions = [
    { value: '_none_', label: t('All Branches'), disabled: true },
    ...(branches || []).map((branch: any) => ({
      value: branch.id.toString(),
      label: branch.name
    }))
  ];

  // Prepare department options for filter
  const departmentOptions = [
    { value: '_none_', label: t('All Departments'), disabled: true },
    ...(departments || []).map((dept: any) => ({
      value: dept.id.toString(),
      label: dept.name
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
      title={t("Employee Transfers")}
      url="/hr/transfers"
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
              searchable: true
            },
            {
              name: 'branch_id',
              label: t('Branch'),
              type: 'select',
              value: selectedBranch,
              onChange: setSelectedBranch,
              options: branchOptions,
              searchable: true
            },
            {
              name: 'department_id',
              label: t('Department'),
              type: 'select',
              value: selectedDepartment,
              onChange: setSelectedDepartment,
              options: departmentOptions,
              searchable: true
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
            router.get(route('hr.transfers.index'), {
              page: 1,
              per_page: parseInt(value),
              search: searchTerm || undefined,
              employee_id: selectedEmployee || undefined,
              branch_id: selectedBranch || undefined,
              department_id: selectedDepartment || undefined,
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
          data={transfers?.data || []}
          from={transfers?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-employee-transfers',
            create: 'create-employee-transfers',
            edit: 'edit-employee-transfers',
            delete: 'delete-employee-transfers'
          }}
        />

        {/* Pagination section */}
        <Pagination
          from={transfers?.from || 0}
          to={transfers?.to || 0}
          total={transfers?.total || 0}
          links={transfers?.links}
          entityName={t("transfers")}
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
              options: employeeOptions.filter(opt => opt.value !== '_none_'),
              searchable: true,
            },

            {
              name: 'to_branch_id',
              type: 'dependent-dropdown',
              // searchable:true,
              dependentConfig: [
                {
                  name: 'to_branch_id',
                  label: t('To Branch'),
                  required: true,
                  options: branchOptions ? branchOptions
                    .filter(opt => opt.value !== '_none_')
                    .map(opt => ({
                      value: opt.value,
                      label: opt.label
                    })) : []
                },
                {
                  name: 'to_department_id',
                  label: t('To Department'),
                  apiEndpoint: '/hr/transfers/get-department/{to_branch_id}',
                  showCurrentValue: true
                },
                {
                  name: 'to_designation_id',
                  label: t('To Designation'),
                  apiEndpoint: '/hr/transfers/get-designation/{to_department_id}',
                  showCurrentValue: true
                }
              ]
            },
            // {
            //   name: 'to_branch_id',
            //   label: t('To Branch'),
            //   type: 'select',
            //   options: [{ value: '_none_', label: t('No Change') }, ...branchOptions.filter(opt => opt.value !== '_none_')]
            // },
            // {
            //   name: 'to_department_id',
            //   label: t('To Department'),
            //   type: 'select',
            //   options: [{ value: '_none_', label: t('No Change') }, ...departmentOptions.filter(opt => opt.value !== '_none_')]
            // },   

            // { 
            //   name: 'to_designation_id', 
            //   label: t('To Designation'), 
            //   type: 'select',
            //   options: [
            //     { value: '_none_', label: t('No Change') },
            //     ...(designations || []).map((desig: any) => ({
            //       value: desig.id.toString(),
            //       label: desig.name
            //     }))
            //   ]
            // },
            {
              name: 'transfer_date',
              label: t('Transfer Date'),
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
              name: 'reason',
              label: t('Reason'),
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
                  { value: 'pending', label: t('Pending') },
                  { value: 'approved', label: t('Approved') },
                  { value: 'rejected', label: t('Rejected') }
                ]
              },
              {
                name: 'notes',
                label: t('Notes'),
                type: 'textarea'
              }
            ] : [])
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem ? {
          ...currentItem,
          transfer_date: currentItem.transfer_date ? window.appSettings.formatDateTimeSimple(currentItem.transfer_date, false) : currentItem.transfer_date,
          effective_date: currentItem.effective_date ? window.appSettings.formatDateTimeSimple(currentItem.effective_date, false) : currentItem.effective_date
        } : null}
        title={
          formMode === 'create'
            ? t('Add New Transfer')
            : formMode === 'edit'
              ? t('Edit Transfer')
              : t('View Transfer')
        }
        mode={formMode}
      />

      {/* Approve Modal */}
      <CrudFormModal
        isOpen={isApproveModalOpen}
        onClose={() => setIsApproveModalOpen(false)}
        onSubmit={handleApprove}
        formConfig={{
          fields: [
            {
              name: 'notes',
              label: t('Notes'),
              type: 'textarea',
              placeholder: t('Add any notes about this approval (optional)')
            }
          ],
          modalSize: 'sm'
        }}
        initialData={currentItem}
        title={t('Approve Transfer')}
        mode="edit"
      />

      {/* Reject Modal */}
      <CrudFormModal
        isOpen={isRejectModalOpen}
        onClose={() => setIsRejectModalOpen(false)}
        onSubmit={handleReject}
        formConfig={{
          fields: [
            {
              name: 'notes',
              label: t('Rejection Reason'),
              type: 'textarea',
              required: true,
              placeholder: t('Please provide a reason for rejecting this transfer')
            }
          ],
          modalSize: 'sm'
        }}
        initialData={currentItem}
        title={t('Reject Transfer')}
        mode="edit"
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={`${currentItem?.employee?.name || ''}`}
        entityName="transfer"
      />
    </PageTemplate>
  );
}