// pages/hr/employee-salaries/index.tsx
import { useState, useEffect } from 'react';
import React from 'react';
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

export default function EmployeeSalaries() {
  const { t } = useTranslation();
  const { auth, employeeSalaries, employees, salaryComponents, filters: pageFilters = {}, flash } = usePage().props as any;
  const permissions = auth?.permissions || [];



  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedEmployee, setSelectedEmployee] = useState(pageFilters.employee_id || 'all');
  const [selectedIsActive, setSelectedIsActive] = useState(pageFilters.is_active || 'all');
  const [showFilters, setShowFilters] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');

  // Check if any filters are active
  const hasActiveFilters = () => {
    return searchTerm !== '' || selectedEmployee !== 'all' || selectedIsActive !== 'all';
  };

  // Count active filters
  const activeFilterCount = () => {
    return (searchTerm ? 1 : 0) + (selectedEmployee !== 'all' ? 1 : 0) + (selectedIsActive !== 'all' ? 1 : 0);
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };

  const applyFilters = () => {
    router.get(route('hr.employee-salaries.index'), {
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee !== 'all' ? selectedEmployee : undefined,
      is_active: selectedIsActive !== 'all' ? selectedIsActive : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

    router.get(route('hr.employee-salaries.index'), {
      sort_field: field,
      sort_direction: direction,
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee !== 'all' ? selectedEmployee : undefined,
      is_active: selectedIsActive !== 'all' ? selectedIsActive : undefined,
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
      case 'show-payroll':
        handleShowPayroll(item);
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
      toast.loading(t('Creating employee salary...'));

      router.post(route('hr.employee-salaries.store'), formData, {
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
            toast.error(`Failed to create employee salary: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating employee salary...'));

      router.put(route('hr.employee-salaries.update', currentItem.id), formData, {
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
            toast.error(`Failed to update employee salary: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    }
  };

  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting employee salary...'));

    router.delete(route('hr.employee-salaries.destroy', currentItem.id), {
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
          toast.error(`Failed to delete employee salary: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };

  const handleToggleStatus = (salary: any) => {
    const newStatus = salary.is_active ? 'inactive' : 'active';
    toast.loading(`${newStatus === 'active' ? t('Activating') : t('Deactivating')} employee salary...`);

    router.put(route('hr.employee-salaries.toggle-status', salary.id), {}, {
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
          toast.error(`Failed to update employee salary status: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };

  const handleShowPayroll = (salary: any) => {
    router.get(route('hr.employee-salaries.show-payroll', salary.id), {}, {
      onSuccess: (page) => {
        if (page.props.flash?.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(t('Failed to load payroll calculation'));
        }
      }
    });
  };

  const handleResetFilters = () => {
    setSearchTerm('');
    setSelectedEmployee('all');
    setSelectedIsActive('all');
    setShowFilters(false);

    router.get(route('hr.employee-salaries.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  // Define page actions
  const pageActions = [];

  // Need to Remove Add the "Add New Salary" button if user has permission
  // if (hasPermission(permissions, 'create-employee-salaries')) {
  //   pageActions.push({
  //     label: t('Add Employee Salary'),
  //     icon: <Plus className="h-4 w-4 mr-2" />,
  //     variant: 'default',
  //     onClick: () => handleAddNew()
  //   });
  // }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Payroll Management'), href: route('hr.employee-salaries.index') },
    { title: t('Employee Salaries') }
  ];

  // Define table columns
  const columns = [
    {
      key: 'employee',
      label: t('Employee'),
      render: (value: any, row: any) => row.employee?.name || '-'
    },
    {
      key: 'basic_salary',
      label: t('Basic Salary'),
      render: (value: number) => (
        <span className="font-mono text-green-600">{window.appSettings?.formatCurrency(value || 0)}</span>
      )
    },
    {
      key: 'components',
      label: t('Components'),
      render: (value: any[], row: any) => {
        const componentNames = row.component_names || [];

        return (
          <div className="text-sm">
            {componentNames.length > 0 ? (
              <div className="flex flex-wrap gap-1">
                {componentNames.map((name: string, index: number) => {
                  const componentType = row.component_types?.[index];
                  const isEarning = componentType === 'earning';

                  return (
                    <span
                      key={index}
                      className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${isEarning
                          ? 'bg-green-50 text-green-700 ring-green-700/10'
                          : 'bg-red-50 text-red-700 ring-red-700/10'
                        }`}
                    >
                      {name}
                    </span>
                  );
                })}
              </div>
            ) : (
              <span className="text-gray-500">Basic only</span>
            )}
          </div>
        );
      }
    },
    {
      key: 'is_active',
      label: t('Status'),
      render: (value: boolean) => (
        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${value
          ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
          : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
          }`}>
          {value ? t('Active') : t('Inactive')}
        </span>
      )
    },
    {
      key: 'created_at',
      label: t('Created At'),
      sortable: true,
      render: (value: string) => window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleDateString()
    }
  ];

  // Define table actions
  const actions = [
    {
      label: t('View'),
      icon: 'Eye',
      action: 'view',
      className: 'text-blue-500',
      requiredPermission: 'view-employee-salaries'
    },
    {
      label: t('Edit'),
      icon: 'Edit',
      action: 'edit',
      className: 'text-amber-500',
      requiredPermission: 'edit-employee-salaries'
    },
    {
      label: t('Toggle Status'),
      icon: 'Lock',
      action: 'toggle-status',
      className: 'text-amber-500',
      requiredPermission: 'edit-employee-salaries'
    },
    {
      label: t('Show Payroll'),
      icon: 'BarChart3',
      action: 'show-payroll',
      className: 'text-blue-500',
      requiredPermission: 'view-employee-salaries',
    },
    {
      label: t('Delete'),
      icon: 'Trash2',
      action: 'delete',
      className: 'text-red-500',
      requiredPermission: 'delete-employee-salaries'
    }
  ];

  // Prepare options for filters and forms
  const employeeOptions = [
    { value: 'all', label: t('All Employees'), disabled : true },
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: emp.name
    }))
  ];

  const isActiveOptions = [
    { value: 'all', label: t('All Status') , disabled : true},
    { value: 'active', label: t('Active') },
    { value: 'inactive', label: t('Inactive') }
  ];

  return (
    <PageTemplate
      title={t("Employee Salaries")}
      url="/hr/employee-salaries"
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
              searchable : true
            },
            {
              name: 'is_active',
              label: t('Status'),
              type: 'select',
              value: selectedIsActive,
              onChange: setSelectedIsActive,
              options: isActiveOptions
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
            router.get(route('hr.employee-salaries.index'), {
              page: 1,
              per_page: parseInt(value),
              search: searchTerm || undefined,
              employee_id: selectedEmployee !== 'all' ? selectedEmployee : undefined,
              is_active: selectedIsActive !== 'all' ? selectedIsActive : undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      {/* Content section */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={employeeSalaries?.data || []}
          from={employeeSalaries?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-employee-salaries',
            create: 'create-employee-salaries',
            edit: 'edit-employee-salaries',
            delete: 'delete-employee-salaries'
          }}
        />

        {/* Pagination section */}
        <Pagination
          from={employeeSalaries?.from || 0}
          to={employeeSalaries?.to || 0}
          total={employeeSalaries?.total || 0}
          links={employeeSalaries?.links}
          entityName={t("employee salaries")}
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
              searchable : true,
              disabled: formMode === 'edit' || formMode === 'view',
              options: employees ? employees.map((emp: any) => ({
                value: emp.id.toString(),
                label: emp.name
              })) : []
            },
            { name: 'basic_salary', label: t('Basic Salary'), type: 'number', min: 0, step: 0.01, readOnly: true },
            {
              name: 'components',
              label: t('Salary Components'),
              type: 'multi-select',
              searchable : true,
              options: salaryComponents ? salaryComponents.map((comp: any) => ({
                value: comp.id.toString(),
                label: `${comp.name} (${comp.type}) - ${comp.calculation_type === 'percentage' ? comp.percentage_of_basic + '%' : 'Rs.' + comp.default_amount}`
              })) : [],
              placeholder: t('Select salary components')
            },
            { name: 'is_active', label: t('Is Active'), type: 'checkbox', defaultValue: true },
            { name: 'notes', label: t('Notes'), type: 'textarea', placeholder: t('Select components to be applied to this salary') }
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem ? {
          ...currentItem,
          components: currentItem.components ? currentItem.components.map((id: any) => id.toString()) : []
        } : null}
        title={
          formMode === 'create'
            ? t('Setup Employee Salary')
            : formMode === 'edit'
              ? t('Edit Employee Salary')
              : t('View Employee Salary')
        }
        mode={formMode}
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={`${currentItem?.employee?.name} - Basic Salary` || ''}
        entityName="employee salary"
      />
    </PageTemplate>
  );
}