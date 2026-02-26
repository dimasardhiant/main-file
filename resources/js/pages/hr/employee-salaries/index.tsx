// pages/hr/employee-salaries/index.tsx
import { useState, useEffect } from 'react';
import React from 'react';
import { PageTemplate, PageAction } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Plus, Download } from 'lucide-react';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { MultiSelectField } from '@/components/multi-select-field';

type Flash = {
  success?: string;
  error?: string;
};

const getFlash = (page: any): Flash => (page.props.flash as Flash) || {};

export default function EmployeeSalaries() {
  const { t } = useTranslation();
  const { auth, employeeSalaries, employees, salaryComponents, branches, departments, designations, filters: pageFilters = {}, flash } = usePage().props as any;
  const permissions = auth?.permissions || [];



  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedEmployee, setSelectedEmployee] = useState(pageFilters.employee_id || 'all');
  const [selectedIsActive, setSelectedIsActive] = useState(pageFilters.is_active || 'all');
  const [selectedBranch, setSelectedBranch] = useState(pageFilters.branch || 'all');
  const [selectedDepartment, setSelectedDepartment] = useState(pageFilters.department || 'all');
  const [selectedDesignation, setSelectedDesignation] = useState(pageFilters.designation || 'all');
  const [showFilters, setShowFilters] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');

  // Check if any filters are active
  const hasActiveFilters = () => {
    return searchTerm !== '' || selectedEmployee !== 'all' || selectedIsActive !== 'all' || selectedBranch !== 'all' || selectedDepartment !== 'all' || selectedDesignation !== 'all';
  };

  // Count active filters
  const activeFilterCount = () => {
    return (searchTerm ? 1 : 0) + (selectedEmployee !== 'all' ? 1 : 0) + (selectedIsActive !== 'all' ? 1 : 0) + (selectedBranch !== 'all' ? 1 : 0) + (selectedDepartment !== 'all' ? 1 : 0) + (selectedDesignation !== 'all' ? 1 : 0);
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
      branch: selectedBranch !== 'all' ? selectedBranch : undefined,
      department: selectedDepartment !== 'all' ? selectedDepartment : undefined,
      designation: selectedDesignation !== 'all' ? selectedDesignation : undefined,
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
      branch: selectedBranch !== 'all' ? selectedBranch : undefined,
      department: selectedDepartment !== 'all' ? selectedDepartment : undefined,
      designation: selectedDesignation !== 'all' ? selectedDesignation : undefined,
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
          const flash = getFlash(page);
          setIsFormModalOpen(false);
          toast.dismiss();
          if (flash.success) {
            toast.success(t(flash.success));
          } else if (flash.error) {
            toast.error(t(flash.error));
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
          const flash = getFlash(page);
          setIsFormModalOpen(false);
          toast.dismiss();
          if (flash.success) {
            toast.success(t(flash.success));
          } else if (flash.error) {
            toast.error(t(flash.error));
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
        const flash = getFlash(page);
        setIsDeleteModalOpen(false);
        toast.dismiss();
        if (flash.success) {
          toast.success(t(flash.success));
        } else if (flash.error) {
          toast.error(t(flash.error));
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
        const flash = getFlash(page);
        toast.dismiss();
        if (flash.success) {
          toast.success(t(flash.success));
        } else if (flash.error) {
          toast.error(t(flash.error));
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
        const flash = getFlash(page);
        if (flash.error) {
          toast.error(t(flash.error));
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
    setSelectedBranch('all');
    setSelectedDepartment('all');
    setSelectedDesignation('all');
    setShowFilters(false);

    router.get(route('hr.employee-salaries.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  // Define page actions
  const pageActions: PageAction[] = [];

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
    { value: 'all', label: t('All Employees'), disabled: true },
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: emp.name
    }))
  ];

  const isActiveOptions = [
    { value: 'all', label: t('All Status'), disabled: true },
    { value: 'active', label: t('Active') },
    { value: 'inactive', label: t('Inactive') }
  ];

  const branchOptions = [
    { value: 'all', label: t('All Branches') },
    ...(branches || []).map((branch: any) => ({
      value: branch.id.toString(),
      label: branch.name
    }))
  ];

  const departmentOptions = [
    { value: 'all', label: t('All Departments') },
    ...(departments || []).map((department: any) => ({
      value: department.id.toString(),
      label: `${department.name} (${department.branch?.name || t('No Branch')})`
    }))
  ];

  const designationOptions = [
    { value: 'all', label: t('All Designations') },
    ...(designations || []).map((designation: any) => ({
      value: designation.id.toString(),
      label: `${designation.name} (${designation.department?.name || t('No Department')})`
    }))
  ];

  return (
    <PageTemplate
      title={t("Employee Salaries")}
      description={t("Manage employee salary records")}
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
              name: 'branch',
              label: t('Branch'),
              type: 'select',
              value: selectedBranch,
              onChange: setSelectedBranch,
              options: branchOptions,
              searchable: true,
            },
            {
              name: 'department',
              label: t('Department'),
              type: 'select',
              value: selectedDepartment,
              onChange: setSelectedDepartment,
              options: departmentOptions,
              searchable: true,
            },
            {
              name: 'designation',
              label: t('Designation'),
              type: 'select',
              value: selectedDesignation,
              onChange: setSelectedDesignation,
              options: designationOptions,
              searchable: true,
            },
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
              is_active: selectedIsActive !== 'all' ? selectedIsActive : undefined,
              branch: selectedBranch !== 'all' ? selectedBranch : undefined,
              department: selectedDepartment !== 'all' ? selectedDepartment : undefined,
              designation: selectedDesignation !== 'all' ? selectedDesignation : undefined
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
        onSubmit={(formData: any) => {
          // Transform the components data before submitting
          const submissionData = { ...formData };

          // The components field stores the selected IDs as string[]
          // The component_overrides stores custom values per component
          const selectedIds = formData.components || [];
          const overrides = formData.component_overrides || {};

          // Build the new components array format: [{id, custom_amount, custom_percentage}, ...]
          submissionData.components = selectedIds.map((idStr: string) => {
            const id = parseInt(idStr);
            const override = overrides[idStr] || {};
            return {
              id: id,
              custom_amount: override.custom_amount || null,
              custom_percentage: override.custom_percentage || null,
            };
          });

          // Remove the temporary overrides field
          delete submissionData.component_overrides;

          handleFormSubmit(submissionData);
        }}
        formConfig={{
          fields: [
            {
              name: 'employee_id',
              label: t('Employee'),
              type: 'select',
              required: true,
              searchable: true,
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
              searchable: true,
              options: salaryComponents ? salaryComponents.map((comp: any) => ({
                value: comp.id.toString(),
                label: `${comp.name} (${comp.type}) - ${comp.calculation_type === 'percentage' ? comp.percentage_of_basic + '%' : window.appSettings?.formatCurrency(comp.default_amount) || 'Rp ' + comp.default_amount}`
              })) : [],
              placeholder: t('Select salary components'),
              render: (field: any, formData: any, handleChange: any) => {
                const selectedIds: string[] = Array.isArray(formData.components) ? formData.components : [];
                const overrides = formData.component_overrides || {};

                return (
                  <div className="space-y-3">
                    {/* Multi-select dropdown */}
                    <MultiSelectField
                      field={field}
                      formData={formData}
                      handleChange={(name: string, value: any) => {
                        handleChange(name, value);
                        // Initialize overrides for newly added components
                        const newOverrides = { ...overrides };
                        (value || []).forEach((id: string) => {
                          if (!newOverrides[id]) {
                            newOverrides[id] = { custom_amount: '', custom_percentage: '' };
                          }
                        });
                        // Remove overrides for removed components
                        Object.keys(newOverrides).forEach((id) => {
                          if (!(value || []).includes(id)) {
                            delete newOverrides[id];
                          }
                        });
                        handleChange('component_overrides', newOverrides);
                      }}
                    />

                    {/* Dynamic input fields for each selected component */}
                    {selectedIds.length > 0 && (
                      <div className="border rounded-lg p-3 bg-gray-50 dark:bg-gray-800 space-y-3">
                        <p className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                          {t('Custom Values per Component')}
                        </p>
                        {selectedIds.map((idStr: string) => {
                          const comp = salaryComponents?.find((c: any) => c.id.toString() === idStr);
                          if (!comp) return null;
                          const override = overrides[idStr] || {};
                          const isPercentage = comp.calculation_type === 'percentage';
                          const defaultDisplay = isPercentage
                            ? `${comp.percentage_of_basic}%`
                            : (window.appSettings?.formatCurrency(comp.default_amount) || `Rp ${comp.default_amount}`);

                          return (
                            <div key={idStr} className="flex flex-col gap-1.5 p-2.5 bg-white dark:bg-gray-900 rounded-md border border-gray-200 dark:border-gray-700">
                              <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                  <span className={`inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset ${comp.type === 'earning'
                                    ? 'bg-green-50 text-green-700 ring-green-700/10'
                                    : 'bg-red-50 text-red-700 ring-red-700/10'
                                    }`}>
                                    {comp.type === 'earning' ? t('Earning') : t('Deduction')}
                                  </span>
                                  <span className="font-medium text-sm">{comp.name}</span>
                                </div>
                                <span className="text-xs text-gray-400">
                                  {t('Default')}: {defaultDisplay}
                                </span>
                              </div>
                              <div className="flex gap-2">
                                {isPercentage ? (
                                  <div className="flex-1">
                                    <label className="text-xs text-gray-500 mb-0.5 block">{t('Custom Percentage (%)')}</label>
                                    <input
                                      type="number"
                                      step="0.01"
                                      min="0"
                                      max="100"
                                      placeholder={`${comp.percentage_of_basic}%`}
                                      value={override.custom_percentage || ''}
                                      onChange={(e) => {
                                        const newOverrides = { ...overrides };
                                        newOverrides[idStr] = {
                                          ...override,
                                          custom_percentage: e.target.value ? parseFloat(e.target.value) : ''
                                        };
                                        handleChange('component_overrides', newOverrides);
                                      }}
                                      className="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2.5 py-1.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary"
                                      disabled={formMode === 'view'}
                                    />
                                  </div>
                                ) : (
                                  <div className="flex-1">
                                    <label className="text-xs text-gray-500 mb-0.5 block">{t('Custom Amount')}</label>
                                    <input
                                      type="number"
                                      step="0.01"
                                      min="0"
                                      placeholder={comp.default_amount?.toString() || '0'}
                                      value={override.custom_amount || ''}
                                      onChange={(e) => {
                                        const newOverrides = { ...overrides };
                                        newOverrides[idStr] = {
                                          ...override,
                                          custom_amount: e.target.value ? parseFloat(e.target.value) : ''
                                        };
                                        handleChange('component_overrides', newOverrides);
                                      }}
                                      className="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2.5 py-1.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary"
                                      disabled={formMode === 'view'}
                                    />
                                  </div>
                                )}
                              </div>
                              {!override.custom_amount && !override.custom_percentage && (
                                <p className="text-xs text-gray-400 italic">{t('Using default template value')}</p>
                              )}
                            </div>
                          );
                        })}
                      </div>
                    )}
                  </div>
                );
              }
            },
            { name: 'is_active', label: t('Is Active'), type: 'checkbox', defaultValue: true },
            { name: 'notes', label: t('Notes'), type: 'textarea', placeholder: t('Additional notes for this salary record') }
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem ? (() => {
          // Parse the components field: could be old format [1,3,5] or new format [{id:1,custom_amount:...}, ...]
          const rawComponents = currentItem.components || [];
          const componentIds: string[] = [];
          const componentOverrides: Record<string, any> = {};

          rawComponents.forEach((entry: any) => {
            if (typeof entry === 'object' && entry !== null && entry.id) {
              // New format
              const idStr = entry.id.toString();
              componentIds.push(idStr);
              componentOverrides[idStr] = {
                custom_amount: entry.custom_amount || '',
                custom_percentage: entry.custom_percentage || '',
              };
            } else {
              // Old format (plain ID)
              const idStr = entry.toString();
              componentIds.push(idStr);
              componentOverrides[idStr] = { custom_amount: '', custom_percentage: '' };
            }
          });

          return {
            ...currentItem,
            components: componentIds,
            component_overrides: componentOverrides,
          };
        })() : null}
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