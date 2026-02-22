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
import { Plus, FileText, User, DollarSign, Calendar, AlertTriangle } from 'lucide-react';
import { format } from 'date-fns';

export default function EmployeeContracts() {
  const { t } = useTranslation();
  const { auth, employeeContracts, contractTypes, employees, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [statusFilter, setStatusFilter] = useState(pageFilters.status || '_empty_');
  const [typeFilter, setTypeFilter] = useState(pageFilters.contract_type_id || '_empty_');
  const [employeeFilter, setEmployeeFilter] = useState(pageFilters.employee_id || '_empty_');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isStatusModalOpen, setIsStatusModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  
  const hasActiveFilters = () => {
    return statusFilter !== '_empty_' || typeFilter !== '_empty_' || employeeFilter !== '_empty_' || searchTerm !== '';
  };
  
  const activeFilterCount = () => {
    return (statusFilter !== '_empty_' ? 1 : 0) + (typeFilter !== '_empty_' ? 1 : 0) + (employeeFilter !== '_empty_' ? 1 : 0) + (searchTerm !== '' ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const applyFilters = () => {
    router.get(route('hr.contracts.employee-contracts.index'), { 
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      contract_type_id: typeFilter !== '_empty_' ? typeFilter : undefined,
      employee_id: employeeFilter !== '_empty_' ? employeeFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('hr.contracts.employee-contracts.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      contract_type_id: typeFilter !== '_empty_' ? typeFilter : undefined,
      employee_id: employeeFilter !== '_empty_' ? employeeFilter : undefined,
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
    }
  };
  
  const handleAddNew = () => {
    setCurrentItem(null);
    setFormMode('create');
    setIsFormModalOpen(true);
  };
  
  const handleFormSubmit = (formData: any) => {
    if (formMode === 'create') {
      toast.loading(t('Creating employee contract...'));

      router.post(route('hr.contracts.employee-contracts.store'), formData, {
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
            toast.error(`Failed to create employee contract: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating employee contract...'));

      router.put(route('hr.contracts.employee-contracts.update', currentItem.id), formData, {
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
            toast.error(`Failed to update employee contract: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    }
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting employee contract...'));

    router.delete(route('hr.contracts.employee-contracts.destroy', currentItem.id), {
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
          toast.error(`Failed to delete employee contract: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };
  
  const handleStatusUpdate = (formData: any) => {
    toast.loading(t('Updating contract status...'));

    router.put(route('hr.contracts.employee-contracts.update-status', currentItem.id), formData, {
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
          toast.error(`Failed to update contract status: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setStatusFilter('_empty_');
    setTypeFilter('_empty_');
    setEmployeeFilter('_empty_');
    setShowFilters(false);
    
    router.get(route('hr.contracts.employee-contracts.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const pageActions = [];
  
  if (hasPermission(permissions, 'create-employee-contracts')) {
    pageActions.push({
      label: t('Add Contract'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Contract Management'), href: route('hr.contracts.employee-contracts.index') },
    { title: t('Employee Contracts') }
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Draft': return 'bg-gray-50 text-gray-600 ring-gray-500/10';
      case 'Pending Approval': return 'bg-yellow-50 text-yellow-800 ring-yellow-600/20';
      case 'Active': return 'bg-green-50 text-green-700 ring-green-600/20';
      case 'Expired': return 'bg-red-50 text-red-700 ring-red-600/10';
      case 'Terminated': return 'bg-red-50 text-red-700 ring-red-600/10';
      case 'Renewed': return 'bg-blue-50 text-blue-700 ring-blue-600/20';
      default: return 'bg-gray-50 text-gray-600 ring-gray-500/10';
    }
  };

  const getDaysUntilExpiry = (endDate: string, status: string) => {
    if (!endDate || status !== 'Active') return null;
    const today = new Date();
    const expiry = new Date(endDate);
    const diffTime = expiry.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
  };

  const getTotalCompensation = (basicSalary: number) => {
    return basicSalary;
  };

  const columns = [
    { 
      key: 'contract_number', 
      label: t('Contract #'), 
      sortable: true,
      render: (value, row) => (
        <div className="flex items-center gap-2">
          <FileText className="h-4 w-4 text-gray-500" />
          <div>
            <div className="font-medium">{value}</div>
            <div className="text-xs text-gray-500">{row.contract_type?.name}</div>
          </div>
        </div>
      )
    },
    { 
      key: 'employee.name', 
      label: t('Employee'),
      render: (_, row) => (
        <div className="flex items-center gap-2">
          <User className="h-4 w-4 text-gray-500" />
          {row.employee?.name || '-'}
        </div>
      )
    },
    { 
      key: 'start_date', 
      label: t('Contract Period'),
      sortable: true,
      render: (value, row) => {
        const daysUntilExpiry = getDaysUntilExpiry(row.end_date, row.status);
        return (
          <div>
            <div className="flex items-center gap-1">
              <Calendar className="h-4 w-4 text-gray-500" />
              {window.appSettings?.formatDateTimeSimple(value, false) || format(new Date(value), 'MMM dd, yyyy')}
            </div>
            {row.end_date && (
              <div className="text-xs text-gray-500">
                to {window.appSettings?.formatDateTimeSimple(row.end_date, false) || format(new Date(row.end_date), 'MMM dd, yyyy')}
                {daysUntilExpiry !== null && daysUntilExpiry <= 30 && daysUntilExpiry > 0 && (
                  <div className="flex items-center gap-1 text-orange-600 mt-1">
                    <AlertTriangle className="h-3 w-3" />
                    {daysUntilExpiry} days left
                  </div>
                )}
              </div>
            )}
          </div>
        );
      }
    },
    { 
      key: 'basic_salary', 
      label: t('Compensation'),
      render: (value, row) => {
        const total = getTotalCompensation(value);
        return (
          <div className="flex items-center gap-1">
            <div>
              <div className="font-medium">{window.appSettings?.formatCurrency(total)}</div>
              <div className="text-xs text-gray-500">
                Base: {window.appSettings?.formatCurrency(value)}
              </div>
            </div>
          </div>
        );
      }
    },
    { 
      key: 'status', 
      label: t('Status'),
      render: (value) => (
        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getStatusColor(value)}`}>
          {t(value)}
        </span>
      )
    },
    { 
      key: 'approved_at', 
      label: t('Approved'),
      render: (value, row) => {
        if (!value) return '-';
        return (
          <div>
            <div className="text-sm">{window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleDateString()}</div>
            <div className="text-xs text-gray-500">{row.approver?.name}</div>
          </div>
        );
      }
    }
  ];

  const actions = [
    { 
      label: t('View'), 
      icon: 'Eye', 
      action: 'view', 
      className: 'text-blue-500',
      requiredPermission: 'view-employee-contracts'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-employee-contracts'
    },
    { 
      label: t('Update Status'), 
      icon: 'RefreshCw', 
      action: 'update-status', 
      className: 'text-green-500',
      requiredPermission: 'approve-employee-contracts'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-employee-contracts'
    }
  ];

  const statusOptions = [
    { value: '_empty_', label: t('All Statuses') , disabled: true},
    { value: 'Draft', label: t('Draft') },
    { value: 'Pending Approval', label: t('Pending Approval') },
    { value: 'Active', label: t('Active') },
    { value: 'Expired', label: t('Expired') },
    { value: 'Terminated', label: t('Terminated') },
    { value: 'Renewed', label: t('Renewed') }
  ];

  const typeOptions = [
    { value: '_empty_', label: t('All Types') , disabled: true },
    ...(contractTypes || []).map((type: any) => ({
      value: type.id.toString(),
      label: type.name
    }))
  ];

  const employeeOptions = [
    { value: '_empty_', label: t('All Employees') ,disabled: true},
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: emp.name
    }))
  ];

  const employeeSelectOptions = [
    { value: '_empty_', label: t('Select Employee') },
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: emp.name
    }))
  ];

  const typeSelectOptions = [
    { value: '_empty_', label: t('Select Contract Type') },
    ...(contractTypes || []).map((type: any) => ({
      value: type.id.toString(),
      label: type.name
    }))
  ];

  return (
    <PageTemplate 
      title={t("Employee Contracts")} 
      url="/hr/contracts/employee-contracts"
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
              name: 'contract_type_id',
              label: t('Contract Type'),
              type: 'select',
              value: typeFilter,
              onChange: setTypeFilter,
              options: typeOptions,
              searchable: true
            },
            {
              name: 'employee_id',
              label: t('Employee'),
              type: 'select',
              value: employeeFilter,
              onChange: setEmployeeFilter,
              options: employeeOptions,
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
            router.get(route('hr.contracts.employee-contracts.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              status: statusFilter !== '_empty_' ? statusFilter : undefined,
              contract_type_id: typeFilter !== '_empty_' ? typeFilter : undefined,
              employee_id: employeeFilter !== '_empty_' ? employeeFilter : undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={employeeContracts?.data || []}
          from={employeeContracts?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-employee-contracts',
            create: 'create-employee-contracts',
            edit: 'edit-employee-contracts',
            delete: 'delete-employee-contracts'
          }}
        />

        <Pagination
          from={employeeContracts?.from || 0}
          to={employeeContracts?.to || 0}
          total={employeeContracts?.total || 0}
          links={employeeContracts?.links}
          entityName={t("employee contracts")}
          onPageChange={(url) => {
            const urlObj = new URL(url);
            // Preserve current filters
            if (searchTerm) urlObj.searchParams.set('search', searchTerm);
            if (statusFilter !== '_empty_') urlObj.searchParams.set('status', statusFilter);
            if (typeFilter !== '_empty_') urlObj.searchParams.set('contract_type_id', typeFilter);
            if (employeeFilter !== '_empty_') urlObj.searchParams.set('employee_id', employeeFilter);
            if (pageFilters.sort_field) urlObj.searchParams.set('sort_field', pageFilters.sort_field);
            if (pageFilters.sort_direction) urlObj.searchParams.set('sort_direction', pageFilters.sort_direction);
            if (pageFilters.per_page) urlObj.searchParams.set('per_page', pageFilters.per_page);
            router.get(urlObj.toString());
          }}
        />
      </div>

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
              options: employeeSelectOptions.filter(opt => opt.value !== '_empty_'),
              searchable: true
            },
            { 
              name: 'contract_type_id', 
              label: t('Contract Type'), 
              type: 'select', 
              required: true,
              options: typeSelectOptions.filter(opt => opt.value !== '_empty_'),
              searchable: true
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
              helpText: t('Leave empty for permanent contracts')
            },
            { 
              name: 'basic_salary', 
              label: t('Basic Salary'), 
              type: 'number', 
              required: true,
              min: 0,
              step: 0.01
            },
            { 
              name: 'terms_conditions', 
              label: t('Terms & Conditions'), 
              type: 'textarea',
              rows: 6
            }
          ],
          modalSize: 'xl'
        }}
        initialData={currentItem ? {
          ...currentItem,
          start_date: currentItem.start_date ? window.appSettings.formatDateTimeSimple(currentItem.start_date, false) : currentItem.start_date,
          end_date: currentItem.end_date ? window.appSettings.formatDateTimeSimple(currentItem.end_date, false) : currentItem.end_date
        } : null}
        title={
          formMode === 'create'
            ? t('Add Employee Contract')
            : formMode === 'edit'
              ? t('Edit Employee Contract')
              : t('View Employee Contract')
        }
        mode={formMode}
      />

      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.contract_number || ''}
        entityName="employee contract"
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
                { value: 'Draft', label: t('Draft') },
                { value: 'Pending Approval', label: t('Pending Approval') },
                { value: 'Active', label: t('Active') },
                { value: 'Expired', label: t('Expired') },
                { value: 'Terminated', label: t('Terminated') },
                { value: 'Renewed', label: t('Renewed') }
              ]
            }
          ]
        }}
        initialData={currentItem ? { status: currentItem.status } : {}}
        title={t('Update Contract Status')}
        mode="edit"
      />
    </PageTemplate>
  );
}